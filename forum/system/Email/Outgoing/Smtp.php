<?php
/**
 * @brief		SMTP Email Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		17 Apr 2013
 */

namespace IPS\Email\Outgoing;

use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Password;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Login;
use IPS\Http\Url;
use IPS\Settings;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	SMTP Email Class
 */
class _SMTP extends \IPS\Email
{
	/**
	 * @brief	SMTP Protocol ("tls", "ssl" or "plain")
	 */
	protected $smtpProtocol;
	
	/**
	 * @brief	SMTP Host
	 */
	protected $smtpHost;
	
	/**
	 * @brief	SMTP Port
	 */
	protected $smtpPort;
	
	/**
	 * @brief	SMTP Username
	 */
	protected $smtpUser;
	
	/**
	 * @brief	SMTP Password
	 */
	protected $smtpPass;
	
	/**
	 * @brief	SMTP Connections
	 */
	protected static $smtp = array();
	
	/**
	 * @brief	Connection Key
	 */
	protected $connectionKey;
	
	/**
	 * @brief	Log
	 */
	protected $log = '';
	
	/**
	 * Constructor
	 *
	 * @param	string	$smtpProtocol	Protocol to use
	 * @param	string	$smtpHost		Hostname to connect to
	 * @param	int		$smtpPort		Port to connect to
	 * @param	string	$smtpUser		Username
	 * @param	string	$smtpPass		Password
	 * @return	void
	 */
	public function __construct( $smtpProtocol, $smtpHost, $smtpPort, $smtpUser, $smtpPass )
	{
		$this->smtpProtocol = $smtpProtocol;
		$this->smtpHost = $smtpHost;
		$this->smtpPort = $smtpPort;
		$this->smtpUser = $smtpUser;
		$this->smtpPass = $smtpPass;
		$this->connectionKey = md5( $smtpProtocol . $smtpHost . $smtpPort . $smtpUser . $smtpPass );
	}
	
	/**
	 * Connect to server
	 *
	 * @param	bool	$checkSsl	If set to FALSE, will skip peer certificate verification for TLS connections
	 * @return void
	 */
	public function connect( $checkSsl=TRUE )
	{
		/* Do we already have a connection? */
		if( array_key_exists( $this->connectionKey, static::$smtp ) )
		{
			return;
		}

		/* Connect */
		$connection = @fsockopen( ( ( $this->smtpProtocol == 'ssl' ) ? 'ssl://' : '' ) . $this->smtpHost, $this->smtpPort, $errno, $errstr, \IPS\Request::isCliEnvironment() ? \IPS\VERY_LONG_REQUEST_TIMEOUT : \IPS\DEFAULT_REQUEST_TIMEOUT );
		if ( !$connection )
		{
			throw new \IPS\Email\Outgoing\Exception( $errstr, $errno );
		}
		static::$smtp[ $this->connectionKey ] = $connection;
		register_shutdown_function(function( $object ){
			$object->_sendCommand( 'quit' );
			@fclose( static::$smtp[ $object->connectionKey ] );
			unset( static::$smtp[ $object->connectionKey ] );
		}, $this );

		/* Check the initial response is okay */
		$announce		= $this->_getResponse();
		$responseCode	= mb_substr( $announce, 0, 3 );
		if ( $responseCode != 220 )
		{
			throw new \IPS\Email\Outgoing\Exception( 'smtpmail_fsock_error_initial', 0, NULL, array( $responseCode ) );
		}

		/* HELO/EHLO */
		$ehloFqdn = \IPS\Http\Url::internal('')->data['host'];
		try
		{
			$helo = 'EHLO';
			$responseCode = $this->_sendCommand( 'EHLO ' . $ehloFqdn, 250 );
		}
		catch ( \IPS\Email\Outgoing\Exception $e )
		{
			$helo = 'HELO';
			$responseCode = $this->_sendCommand( 'HELO ' . $ehloFqdn, 250 );
		}

		/* Is TLS being used? */
		if( $this->smtpProtocol == 'tls' )
		{
			if ( $checkSsl )
			{
				@stream_context_set_option( static::$smtp[ $this->connectionKey ], 'ssl', 'verify_peer', false );
			}
			
			$this->_sendCommand( 'STARTTLS', 220 );
			if ( !@\stream_socket_enable_crypto( static::$smtp[ $this->connectionKey ], TRUE, STREAM_CRYPTO_METHOD_SSLv23_CLIENT ) )
			{
				if ( $checkSsl )
				{
					/* Try again, but ignore SSL checks in case the certificate was self-signed, which will fail when initializing TLS. This will be slightly slower to connect, but will avoid an error in most instances. */
					$this->connect( FALSE );
				}
				else
				{
					/* If it still failed on the second connection attempt, throw the exception */
					throw new \IPS\Email\Outgoing\Exception( 'smtpmail_tls_failed' );
				}
			}

			/* Exchange server (at least) wants EHLO resending for STARTTLS */
			$this->_sendCommand( $helo . ' ' . $ehloFqdn, 250 );
		}

		/* Authenticate */
		if ( $this->smtpUser )
		{
			$responseCode = $this->_sendCommand( 'AUTH LOGIN', 334 );
			$responseCode = $this->_sendCommand( base64_encode( $this->smtpUser ), 334 );
			$responseCode = $this->_sendCommand( base64_encode( $this->smtpPass ), 235 );
		}
	}
		
	/**
	 * Send the email
	 * 
	 * @param	mixed	$to					The member or email address, or array of members or email addresses, to send to
	 * @param	mixed	$cc					Addresses to CC (can also be email, member or array of either)
	 * @param	mixed	$bcc				Addresses to BCC (can also be email, member or array of either)
	 * @param	mixed	$fromEmail			The email address to send from. If NULL, default setting is used
	 * @param	mixed	$fromName			The name the email should appear from. If NULL, default setting is used
	 * @param	array	$additionalHeaders	Additional headers to send
	 * @return	void
	 * @throws	\IPS\Email\Outgoing\Exception
	 */
	public function _send( $to, $cc=array(), $bcc=array(), $fromEmail = NULL, $fromName = NULL, $additionalHeaders = array() )
	{
		/* Get the from email */
		$fromEmail = $fromEmail ?: Settings::i()->email_out;
		
		/* SMTP requires you to do CC/BCC by sending a RCPT TO command for each recipient. We'll hide BCC by not actually setting that header */ 
		$recipientsForSmtp = explode( ',', static::_parseRecipients( $to, TRUE ) );
		if ( $cc )
		{
			$recipientsForSmtp = array_merge( $recipientsForSmtp, explode( ',', static::_parseRecipients( $cc, TRUE ) ) );
		}
		if ( $bcc )
		{
			$recipientsForSmtp = array_merge( $recipientsForSmtp, explode( ',', static::_parseRecipients( $bcc, TRUE ) ) );
		}
		$recipientsForSmtp = array_unique( array_map( 'trim', $recipientsForSmtp ) );

		if( empty( $additonalHeaders['Message-ID'] ) )
		{
			$additionalHeaders['Message-ID'] = $this->generateMessageId();
		}
		
		/* Send */
		$this->_sendCompiled( $fromEmail, $recipientsForSmtp, $this->compileFullEmail( $to, $cc, array(), $fromEmail, $fromName, $additionalHeaders ) );
	}
	
	/**
	 * Send an email
	 * 
	 * @param	string	$fromEmail			The email address to send from
	 * @param	array	$toEmails			Array of email addresses to send to
	 * @param	string	$email				The full email (with headers, etc.) except the Bcc header
	 * @return	void
	 * @throws	\IPS\Email\Outgoing\Exception
	 */
	public function _sendCompiled( $fromEmail, $toEmails, $email )
	{
		$this->connect();
		
		$this->_sendCommand( "MAIL FROM:<{$fromEmail}>", 250 );
		
		foreach ( $toEmails as $toEmail )
		{
			$this->_sendCommand( "RCPT TO:<{$toEmail}>", 250, TRUE );
		}
				
		$this->_sendCommand( 'DATA', 354 );
		$this->_sendCommand( $email . "\r\n.", 250 );
	}
	
	/**
	 * Send SMTP Command
	 *
	 * @param	string		$command			The command
	 * @param	int|NULL	$expectedResponse	The expected response code. Will throw an exception if different.
	 * @param	bool		$resetOnFailure		If the command fails, issue a RSET (reset) command
	 * @return	string	Response
	 * @throws	\IPS\Email\Outgoing\Exception
	 */
	protected function _sendCommand( $command, $expectedResponse=NULL, $resetOnFailure=FALSE )
	{
		/* Log */
		$this->log .= "> {$command}\r\n";
		
		/* Send */
		fputs( static::$smtp[ $this->connectionKey ], $command . "\r\n" );
		
		/* Read */
		$response = $this->_getResponse();
		
		/* Get response code */
		$code = \intval( mb_substr( $response, 0, 3 ) );
		if ( $expectedResponse !== NULL and $code !== $expectedResponse )
		{
			if( $resetOnFailure === TRUE )
			{
				$this->_sendCommand( 'RSET' );
			}

			throw new \IPS\Email\Outgoing\Exception( $response, $code );
		}
		
		/* Return */
		return $response;
	}

	/**
	 * Get response
	 *
	 * @return	string	Response
	 */
	protected function _getResponse()
	{
		/* Read */
		$response = '';
		while ( $line = @fgets( static::$smtp[ $this->connectionKey ], 515 ) )
		{			
			$response .= $line;
			if ( mb_substr($line, 3, 1) == " " )
			{
				break;
			}
		}
		
		/* Log */
		$this->log .= mb_convert_encoding( $response, 'UTF-8', 'ASCII' );
		
		/* Return */
		return $response;
	}

	/**
	 * Generate random message-id
	 *
	 * @return string
	 */
	public function generateMessageId(): string
	{
		$randomString = base_convert( microtime(false ) . '.' . Login::generateRandomString(), 10, 36 );

		return '<' . $randomString . '@' . Url::internal('')->data[ Url::COMPONENT_HOST ] . '>';
	}
	
	/**
	 * Return the SMTP log
	 *
	 * @return string
	 */
	public function getLog()
	{
		return $this->log;
	}

	/**
	 * Form fields for email handler
	 * These will automatically toggle on when the email handler is selected to be used
	 *
	 * @return array
	 */
	public static function form(): array
	{
		$form = [];
		$options = array( 'plain' => 'smtp_plaintext', 'ssl' => 'smtp_ssl', 'tls' => 'smtp_tls' );
		$form['smtp_host'] = new Text( 'smtp_host', Settings::i()->smtp_host, FALSE, [], NULL, NULL, NULL, 'smtp_host' );
		$form['smtp_protocol'] = new Select( 'smtp_protocol', Settings::i()->smtp_protocol, FALSE, [ 'options' => $options ], NULL, NULL, NULL, 'smtp_protocol' );
		$form['smtp_port'] = new Number( 'smtp_port', Settings::i()->smtp_port, FALSE, [], NULL, NULL, NULL, 'smtp_port' );
		$form['smtp_user'] = new Text( 'smtp_user', Settings::i()->smtp_user, FALSE, [], NULL, NULL, NULL, 'smtp_user' );
		$form['smtp_pass'] = new Password( 'smtp_pass', Settings::i()->smtp_pass, FALSE, [ 'enforceMaxLimit' => FALSE ], NULL, NULL, NULL, 'smtp_pass' ) ;

		return $form;
	}

	/**
	 * Is this email class usable?
	 *
	 * @param string $type Email Type
	 * @return  bool
	 */
	public static function isUsable( string $type ): bool
	{
		return true;
	}
}