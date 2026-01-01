<?php
/**
 * @brief		Postmark Email Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 November 2024
 */

namespace IPS\Email\Outgoing;

use IPS\core\Advertisement;
use IPS\core\extensions\core\CommunityEnhancements\Postmark as PostmarkIntegration;
use IPS\Db;
use IPS\Email;
use IPS\Http\Url;
use IPS\Lang;
use IPS\Member;
use IPS\Settings;
use const IPS\LONG_REQUEST_TIMEOUT;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Postmark Email Class
 */
class Postmark extends Email
{
	/* !Configuration */

	/**
	 * @brief	The number of emails that can be sent in one "go"
	 */
	const MAX_EMAILS_PER_GO = 500; // Postmark can accept 500 messages per batch call

	/**
	 * @brief	API Key
	 */
	protected string $apiKey;

	/**
	 * Constructor
	 *
	 * @param	string	$apiKey	API Key
	 * @return	void
	 */
	public function __construct( string $apiKey )
	{
		$this->apiKey = $apiKey;
	}

	/**
	 * Send the email
	 *
	 * @param	mixed	$to					The member or email address, or array of members or email addresses, to send to
	 * @param mixed $cc					Addresses to CC (can also be email, member or array of either)
	 * @param mixed $bcc				Addresses to BCC (can also be email, member or array of either)
	 * @param mixed $fromEmail			The email address to send from. If NULL, default setting is used
	 * @param mixed $fromName			The name the email should appear from. If NULL, default setting is used
	 * @param array $additionalHeaders	Additional headers to send
	 * @return	void
	 * @throws    Exception
	 */
	public function _send( mixed $to, mixed $cc=array(), mixed $bcc=array(), mixed $fromEmail = NULL, mixed $fromName = NULL, array $additionalHeaders = array() ) : void
	{
		$emailData = $this->_constructEmailRequest( $to, $cc, $bcc, $fromEmail, $fromName, $additionalHeaders );

		/* Send */
		$response = $this->api( 'email', $emailData );
		if ( !empty( $response['ErrorCode'] ) )
		{
			throw new Exception( $response['Message'], $response['ErrorCode'] );
		}
	}

	/**
	 * Is this email class usable?
	 *
	 * @param string $type Email Type
	 * @return  bool
	 */
	public static function isUsable( string $type ): bool
	{
		if( ( new PostmarkIntegration() )->enabled )
		{
			return true;
		}

		return false;
	}

	/**
	 * Merge and Send
	 *
	 * @param array $recipients			Array where the keys are the email addresses to send to and the values are an array of variables to replace
	 * @param mixed $fromEmail			The email address to send from. If NULL, default setting is used. NOTE: This should always be a site-controlled domin. Some services like Sparkpost require the domain to be validated.
	 * @param mixed $fromName			The name the email should appear from. If NULL, default setting is used
	 * @param array $additionalHeaders	Additional headers to send. Merge tags can be used like in content.
	 * @param	Lang|NULL	$language			The language the email content should be in
	 * @return	int				Number of successful sends
	 */
	public function mergeAndSend( array $recipients, mixed $fromEmail = NULL, mixed $fromName = NULL, array $additionalHeaders = array(), Lang $language = NULL ): int
	{
		/* Get the current locale, and then set the language's locale so datetime formatting in templates is correct for this language */
		$currentLocale = setlocale( LC_ALL, '0' );
		$language->setLocale();

		$emailData = $emailDataForLog = [];

		/* We need to know before we start if tracking has been completed or not for the content already */
		$trackingCompleted = $this->trackingCompleted;

		foreach ( $recipients as $address => $vars )
		{
			/* Before compiling our content, reset the "tracking completed flag", otherwise if it hasn't been done yet, the flag is set during the first loop and never reset (so tracking isn't performed) for subsequent loops */
			$this->trackingCompleted = $trackingCompleted;

			$e = $this->_constructEmailRequest( to: $address, fromEmail: $fromEmail, fromName: $fromName, language: $language );

			foreach ( $vars as $k => $v )
			{
				$language->parseEmail( $v );

				$e['HtmlBody'] = str_replace( "*|{$k}|*", htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE ), $e['HtmlBody'] );
				$e['TextBody'] = str_replace( "*|{$k}|*", $v, $e['TextBody'] );
				$e['Subject'] = str_replace( "*|{$k}|*", $v, $e['Subject'] );

				foreach ( $e['Headers'] as $i => $header )
				{
					$e['Headers'][ $i ]['Value'] = str_replace( "*|{$k}|*", $v, $header['Value'] );
				}
			}

			$emailDataForLog[ $address ] = [
				'Subject'   => $e['Subject'],
				'HtmlBody'   => $e['HtmlBody'],
			];
			$emailData[] = $e;
		}

		try
		{
			/* Send */
			$response = $this->api( 'email/batch', $emailData );
		}
		catch( \JsonException |Exception $e )
		{
			$first = array_shift( $emailData );
			Db::i()->insert( 'core_mail_error_logs', [
				'mlog_date'			=> time(),
				'mlog_to'			=> json_encode( array_keys( $emailDataForLog ) ),
				'mlog_from'			=> $fromEmail ?: Settings::i()->email_out,
				'mlog_subject'		=> $first['Subject'],
				'mlog_content'		=> $first['HtmlBody'],
				'mlog_resend_data'	=> json_encode( [ 'type' => $this->type ] ),
				'mlog_msg'			=> json_encode( [ 'message' => $e->getMessage() ] ),
				'mlog_smtp_log'		=> NULL
			] );

			return 0;
		}

		$errorCount = 0;
		foreach( $response as $k => $result )
		{
			if( !empty( $result['ErrorCode'] ) )
			{
				$errorCount++;
				Db::i()->insert( 'core_mail_error_logs', array(
					'mlog_date'			=> time(),
					'mlog_to'			=> $emailData[ $k ]['To'],
					'mlog_from'			=> $fromEmail ?: Settings::i()->email_out,
					'mlog_subject'		=> $emailData[ $k ]['Subject'],
					'mlog_content'		=> $emailData[ $k ]['HtmlBody'],
					'mlog_resend_data'	=> json_encode( [ 'type' => $this->type ] ),
					'mlog_msg'			=> json_encode( [ 'code' => $result['ErrorCode'], 'message' => $result['Message'] ] ),
					'mlog_smtp_log'		=> NULL
				) );
			}
		}

		$successCount = ( \count( $recipients ) - $errorCount );

		/* Update ad impression count */
		Advertisement::updateEmailImpressions( $successCount );

		/* Now restore the locale we started with */
		Lang::restoreLocale( $currentLocale );

		return $successCount;
	}

	/**
	 * Construct email array for Postmar
	 *
	 * @param Member|string|array   $to                     Recipient addresses
	 * @param string|array          $cc                     CC addresses
	 * @param string|array          $bcc                    BCC Addresses
	 * @param string|null           $fromEmail              From Email Address
	 * @param string|null           $fromName               From Name
	 * @param array|null            $additionalHeaders      Additional headers
	 * @param Lang|NULL             $language
	 * @return array
	 */
	protected function _constructEmailRequest( Member|string|array $to, string|array $cc=[], string|array $bcc=[], ?string $fromEmail=NULL, ?string $fromName=NULL, ?array $additionalHeaders=array(), Lang $language=NULL ): array
	{
		$fromName = $fromName ?: Settings::i()->board_name;
		$fromEmail = $fromEmail ?: Settings::i()->email_out;

		/* Add the recipients */
		$sendTo = $sendCc = $sendBcc = [];
		foreach ( [ 'to', 'cc', 'bcc' ] as $type )
		{
			$sendVar = 'send' . ucfirst( $type );
			if ( \is_array( $$type ) )
			{
				foreach ( $$type as $recipient )
				{
					if ( $recipient instanceof Member )
					{
						$$sendVar[] = $recipient->email;
					}
					else
					{
						$$sendVar[] = $recipient;
					}
				}
			}
			elseif ( $$type )
			{
				$recipient = $$type;
				if ( $recipient instanceof Member )
				{
					$$sendVar[] = $recipient->email;
				}
				else
				{
					$$sendVar[] = $recipient;
				}
			}
		}

		$email = [
			'From' => "{$fromName} <{$fromEmail}>",
			'To' => implode( ',', $sendTo ),
			'Cc' => implode( ',', $sendCc ),
			'Bcc' => implode( ',', $sendBcc ),
			'Subject' => $this->compileSubject( static::_getMemberFromRecipients( $to ), $language ),
			'HtmlBody' => $this->compileContent( 'html', static::_getMemberFromRecipients( $to ), $language ),
			'TextBody' => $this->compileContent( 'plaintext', static::_getMemberFromRecipients( $to ), $language ),
			'Headers' => [],
			'MessageStream' => $this->getMessageStreamId( $this->type ),
			'TrackOpens'    => Settings::i()->postmark_track_opens,
			'TrackLinks'    => Settings::i()->postmark_track_opens ? 'HtmlAndText' : 'None'
		];

		if( $additionalHeaders )
		{
			foreach( $additionalHeaders as $id => $header )
			{
				$email['Headers'][] = [
					'Name' => $id,
					'Value' => $header,
				];
			}
		}

		return $email;
	}

	/**
	 * Make API call
	 *
	 * @param	string	$method	Method
	 * @param	array	$args	Arguments
	 * @return    array|null
	 *@throws  Exception   Indicates an invalid JSON response or HTTP error
	 */
	public function api( $method, $args=NULL )
	{
		$request = Url::external( 'https://api.postmarkapp.com/' . $method )
			->request( LONG_REQUEST_TIMEOUT )
			->setHeaders( [
				'Accept'                    => 'application/json',
				'Content-Type'              => 'application/json',
				'X-Postmark-Server-Token'   => $this->apiKey
			] );

		try
		{
			if ( $args )
			{
				$response = $request->post( json_encode( $args ) );
			}
			else
			{
				$response = $request->get();
			}

			if ( $response->content )
			{
				$response = $response->decodeJson();
			}
			else
			{
				$response = null;
			}

			return $response;
		}
		catch ( \RuntimeException | \IPS\Http\Request\Exception $e )
		{
			throw new Exception( $e->getMessage(), $e->getCode() );
		}
	}

	/**
	 * @var array|null Unpacked message stream config
	 */
	protected static null|array $messageStreams = null;

	/**
	 * Get Message Stream ID based on config
	 *
	 * @param   string $type        TYPE_* constant
	 * @return  string
	 */
	public function getMessageStreamId( string $type ): string
	{
		if( self::$messageStreams === null )
		{
			$setting = json_decode( Settings::i()->postmark_streams, TRUE );
			self::$messageStreams = [
				static::TYPE_TRANSACTIONAL => $setting['transactional'],
				static::TYPE_LIST => $setting['bulk'],
				static::TYPE_BULK => $setting['bulk']
			];
		}

		return self::$messageStreams[ $type ];
	}
}