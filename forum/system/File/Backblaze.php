<?php
/**
 * @brief		File Handler: Backblaze
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Oct 2024
 */

namespace IPS\File;

/* Set use statements to make Uncle Matt happy */
use IPS\DateTime AS DT;
use IPS\File\Amazon;
use IPS\Http\Url;
use IPS\Http\Request\Exception as RequestException;
use IPS\Member;
use DateInterval;

use function array_filter;
use function array_map;
use function debug_backtrace;
use function defined;
use function header;
use function in_array;
use function mb_strstr;
use function trim;

use const ARRAY_FILTER_USE_KEY;
use const DEBUG_BACKTRACE_IGNORE_ARGS;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _Backblaze extends Amazon
{
	/**
	 * Settings
	 *
	 * @param	array	$configuration		Configuration if editing a setting, or array() if creating a setting.
	 * @return	array
	 */
	public static function settings( $configuration=array() )
	{
		$default = ( isset( $configuration['custom_url'] ) and ! empty( $configuration['custom_url'] ) ) ? TRUE : FALSE;
		$return = parent::settings( $configuration );
		unset( $return['toggle'], $return['custom_url'] );
		
		$return['endpoint']		= array( 'type'	=> 'Text', 'default' => 's3.backblazeb2.com' );
		$return['toggle']		= array( 'type' => 'YesNo', 'default' => $default, 'options' => array(
			'togglesOn' 			=> array( 'Backblaze_custom_url' )
		) );
		$return['custom_url']	= array( 'type' => 'Text', 'default' => '' );
		return $return;
	}
	
	/**
	 * Display name
	 *
	 * @param	array	$settings	Configuration settings
	 * @return	string
	 */
	public static function displayName( $settings )
	{
		return Member::loggedIn()->language()->addToStack( 'filehandler_display_name', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack('filehandler__Backblaze'), $settings['bucket'] ) ) );
	}
	
	/**
	 * Sign and make request
	 *
	 * @param	string		$uri				The URI (relative to the bucket)
	 * @param	string		$verb				The HTTP verb to use
	 * @param	array 		$configuration		The configuration for this instance
	 * @param	int			$configurationId	The configuration ID
	 * @param	string|null	$content			The content to send
	 * @param	string|null	$storageExtension	Storage extension
	 * @param	bool		$skipExtraChecks	Skips the endpoint check (to prevent infinite looping)
	 * @param	bool		$isPrivate			This can be set to true to access/store private files (i.e. that are not publicly readable)
	 * @param	array 		$queryString		Array of key => value pairs
	 * @return	\IPS\Http\Response
	 * @throws	\IPS\Http\Request\Exception
	 */
	protected static function makeRequest( $uri, $verb, $configuration, $configurationId, $content=NULL, $storageExtension=NULL, $skipExtraChecks=FALSE, $isPrivate=false, $queryString=array() )
	{
		/* Amazon requires filename characters to be properly encoded - let's urlencode the filename here */
		$uriPieces	= explode( '/', $uri );
		$filename	= array_pop( $uriPieces );
		$uri		= ltrim( implode( '/', $uriPieces ) . '/' . rawurlencode( $filename ), '/' );
		
		/* Build a request */
		$url = \IPS\Http\Url::external( static::buildBaseUrl( $configuration ) . $uri );
		if ( $queryString )
		{
			$url = $url->setQueryString( $queryString );
		}
		$request = $url->request( \IPS\VERY_LONG_REQUEST_TIMEOUT, NULL, FALSE ); # Amazon will send a 301 header code, but no Location header, if we need to try another endpoint
		
		/* When using virtual hosted–style buckets with SSL, the SSL wild card certificate only matches buckets that do not contain periods. To work around this, use HTTP or write your own certificate verification logic. @link http://docs.aws.amazon.com/AmazonS3/latest/dev/BucketRestrictions.html */
		if ( \IPS\Request::i()->isSecure() and mb_strstr( $configuration['bucket'], '.' ) )
		{
			$request->sslCheck( FALSE );
		}
		
		/* Set headers */
		$headers = array(
			'Content-Type'	=> \IPS\File::getMimeType( $uri ),
			'Content-MD5'	=> base64_encode( md5( $content, TRUE ) ),
		);
	
		/* If uploading a file, need to specify length and cache control */
		if( mb_strtoupper( $verb ) === 'PUT' )
		{
			$headers['Content-Length']	= \strlen( $content );
	
			$cacheSeconds = 3600 * 24 * 365;
	
			/* Custom Cache-Control */
			if( $storageExtension !== NULL AND mb_strpos( $storageExtension, '_' ) !== FALSE )
			{
				$bits     = explode( '_', $storageExtension );
				$class    = '\IPS\\' . $bits[0] . '\extensions\core\FileStorage\\' . $bits[1];
	
				if ( isset( $class::$cacheControlTtl ) and $class::$cacheControlTtl )
				{
					$cacheSeconds = $class::$cacheControlTtl;
				}
			}
			
			$headers['Cache-Control'] = 'public, max-age=' . $cacheSeconds;
		}
		
		/* We need to strip query string parameters for the signature, but not always (e.g. a subresource such as ?acl needs to be included and multi-
			object delete requests must include the query string params).  Let the callee decide to do this or not. */
		if( isset( $configuration['_strip_querystring'] ) AND $configuration['_strip_querystring'] === TRUE )
		{
			$uri = preg_replace( "/^(.*?)\?.*$/", "$1", $uri );
		}
		
		/* Sign the request */
		$authorization = static::signature( $configuration, $verb, $uri, $headers, $queryString, $content );
		$headers['Authorization'] = $authorization;
		unset( $headers['Host'] );
		$request->setHeaders( $headers );
		
		/* Make the request */
		$verb = mb_strtolower( $verb );
		$response = $request->$verb( $content );
	
		/* If we are skipping extra checks, return response now */
		if( $skipExtraChecks )
		{
			return $response;
		}
		
		/* Change endpoint if necessary */
		if ( $response->httpResponseCode == 301 )
		{
			$xml = $response->decodeXml();
			if ( isset( $xml->Endpoint ) )
			{
				/* We have an endpoint, but if we called s3.amazonaws.com then it might be wrong. Try to detect the correct one. */
				$configuration['endpoint'] = 's3-us-west-1.amazonaws.com';
	
				$endpointResponse	= static::makeRequest( $uri, $verb, $configuration, $configurationId, $content, NULL, TRUE );
				$update				= FALSE;
	
				/* If the response code is 200, we got lucky and that's our endpoint */
				if( $endpointResponse->httpResponseCode == 200 )
				{
					$update = TRUE;
				}
				/* If it's a 301 response, we should be able to pull out the correct endpoint now */
				elseif( $endpointResponse->httpResponseCode == 301 )
				{
					$xml = $endpointResponse->decodeXml();
					if ( isset( $xml->Endpoint ) )
					{
						/* Strip out the bucket from the endpoint */
						$configuration['endpoint'] = preg_replace( '/^' . preg_quote( $configuration['bucket'], '/' ) . '\./', '', (string) $xml->Endpoint );
						$update = TRUE;
					}
				}
	
				/* If we need to update, do it now and return the result */
				if( $update === TRUE )
				{
					static::$updatedEndpoint	= $configuration['endpoint'];
	
					if ( $configurationId )
					{
						\IPS\Db::i()->update( 'core_file_storage', array( 'configuration' => json_encode( $configuration ) ), array( "id=?", $configurationId ) );
						unset( \IPS\Data\Store::i()->storageConfigurations );
					}
				}
	
				return static::makeRequest( $uri, $verb, $configuration, $configurationId, $content );
			}
		}
		
		/* Change region if necessary */
		if ( $response->httpResponseCode == 400 )
		{
			try
			{
				$xml = $response->decodeXml();
				if ( isset( $xml->Region ) )
				{
					$configuration['region'] = (string) $xml->Region;
					if ( $configurationId )
					{
						\IPS\Db::i()->update( 'core_file_storage', array( 'configuration' => json_encode( $configuration ) ), array( 'id=?', $configurationId ) );
						unset( \IPS\Data\Store::i()->storageConfigurations );
					}
					return static::makeRequest( $uri, $verb, $configuration, $configurationId, $content );
				}
			}
			catch ( \Exception $e ) { }
		}
	
		/* Return */
		return $response;		
	}
	
	/**
	 * Generate a temporary download URL the user can be redirected to
	 *
	 * @param	$validForSeconds	int	The number of seconds the link should be valid for
	 * @return	Url
	 */
	public function generateTemporaryDownloadUrl( $validForSeconds = 1200 )
	{
		$expires = ( new DT )->add( new DateInterval( "PT{$validForSeconds}S" ) );
		
		/* Authorize */
		$authorize = Url::external( "https://api.backblazeb2.com/b2api/v3/b2_authorize_account" )
			->request()
			->login( $this->configuration['access_key'], $this->configuration['secret_key'] )
			->get();
		$response = $authorize->decodeJson();
		if ( $authorize->httpResponseCode !== '200' )
		{
			throw new Exception( $response['code'] );
		}
		
		$download = URL::external( "{$response['apiInfo']['storageApi']['apiUrl']}/b2api/v3/get_download_authorization" )
			->request()
			->setHeaders( array(
				'Authorization'	=> $response['authorizationToken']
			) )
			->post( json_encode( array(
				'bucketId'					=> $response['apiInfo']['storageApi']['bucketId'],
				'fileNamePrefix'			=> "{$this->container}/",
				'validDurationInSeconds'	=> $expires->getTimestamp(),
				'b2ContentDisposition'		=> 'attachment; filename="' . $this->originalFilename . '"',
				'b2Expires'					=> $expires->rfc1123(),
				'b2ContentType'				=> static::getMimeType( $this->originalFilename ),
				
			) ) )
			->decodeJson();
		
		$query = http_build_query( array(
			'Authorization'			=> $download['authorizationToken'],
			'b2ContentDisposition'		=> 'attachment; filename="' . $this->originalFilename . '"',
			'b2Expires'				=> $expires->rfc1123(),
			'b2ContentType'			=> static::getMimeType( $this->originalFilename ),
		) );
		
		return Url::external( "{$response['apiInfo']['storageApi']['downloadUrl']}/file/{$this->configuration['bucket']}/{$this->container}/{$this->filename}?{$query}" );
	}
	
	/**
	 * Build up the base Amazon URL
	 * @param   array   $configuration  Configuration data
	 * @return string
	 */
	public static function buildBaseUrl( $configuration )
	{
		if ( mb_strstr( $configuration['bucket'], '.' ) )
		{
			return (
			\IPS\Request::i()->isSecure() ? "https" : "http" ) . "://"
			. ( isset( $configuration['endpoint'] ) ? $configuration['endpoint'] : "s3.backblazeb2.com" )
			. "/{$configuration['bucket']}"
			. static::bucketPath( $configuration )
			. '/';
		}
		else
		{
			return (
			\IPS\Request::i()->isSecure() ? "https" : "http" ) . "://{$configuration['bucket']}."
			. ( isset( $configuration['endpoint'] ) ? $configuration['endpoint'] : "s3.backblazeb2.com" )
			. static::bucketPath( $configuration )
			. '/';
		}
	}

	/**
	 * Do not allow gzip versions for Backblaze
	 *
	 * @return boolean
	 */
	public function needsGzipVersion()
	{
		return false;
	}
}