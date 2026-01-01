<?php
/**
 * @brief		Service worker output
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Feb 2021
 */
 
namespace IPS\core\modules\front\system;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use IPS\Xml\DOMDocument;
use function count;
use function defined;
use const IPS\DEBUG_JS;
use const IPS\DEV_DEBUG_JS;
use const IPS\IN_DEV;
use const IPS\ROOT_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Service worker controller
 */
class serviceworker extends Controller
{	
	/**
	 * View Notifications
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$cachedUrls = array();

		$notificationIcon = NULL;

		/* Get an icon to use in notifications */
		$homeScreenIcons = json_decode( Settings::i()->icons_homescreen, TRUE ) ?? array();
		
		if( count( $homeScreenIcons ) )
		{
			foreach( $homeScreenIcons as $name => $image )
			{
				if( isset( $image['width'] ) and $image['width'] == 192 )
				{
					$notificationIcon = File::get( 'core_Icons', $image['url'] )->url;
					break;
				}
			}
		}

		/* VARIABLES TO PASS THROUGH TO JS */
		$CACHE_VERSION = Theme::i()->cssCacheBustKey();
		$variables = [
			"DEBUG" => boolval( ( IN_DEV and DEV_DEBUG_JS ) or DEBUG_JS ),
			"BASE_URL" => Settings::i()->base_url,
			"CACHED_ASSETS" => $cachedUrls,
			"CACHE_NAME" => "invision-community-{$CACHE_VERSION}",
			"CACHE_VERSION" => $CACHE_VERSION,
			"NOTIFICATION_ICON" => $notificationIcon ?: null,
			"DEFAULT_NOTIFICATION_TITLE" => Member::loggedIn()->language()->addToStack( 'default_notification_title'),
			"DEFAULT_NOTIFICATION_BODY" => Member::loggedIn()->language()->addToStack( 'default_notification_body'),
			"OFFLINE_PAGE" => $this->buildCollapsedOfflinePage(),
		];

		$variables["OFFLINE_PAGE_SIZE"] = \strlen( $variables["OFFLINE_PAGE"] );

		$output = "\"use strict;\"\n\n";
		foreach ( $variables as $var => $value )
		{
			$toEncode = json_encode( $value, JSON_UNESCAPED_SLASHES );
			$output .= <<<JAVASCRIPT
const {$var} = {$toEncode};

JAVASCRIPT;

		}


		Member::loggedIn()->language()->parseOutputForDisplay( $output );
		$output .= file_get_contents( ROOT_PATH . '/applications/core/interface/js/serviceWorker.js' );
		$cacheHeaders	= !IN_DEV ? Output::getCacheHeaders(time(), 86400) : array();
		Output::i()->sendOutput($output, 200, 'text/javascript', $cacheHeaders);
	}


	/**
	 * Return template for offline page
	 *
	 * @return string
	 */
	protected function buildCollapsedOfflinePage() : string
	{
		$html = Theme::i()->getTemplate( 'global', 'core', 'global' )->offline();
		Member::loggedIn()->language()->parseOutputForDisplay( $html );

		$doc = new DOMDocument( "2.0", "utf-8" );
		$doc->preserveWhiteSpace = false;
		$doc->loadHTML( $html, LIBXML_NOBLANKS );
		$doc->formatOutput = true;
		$compact = $doc->saveHTML();

		// We don't need these segments of whitespace. HTML entities can be used if it's absolutely necessary
		return preg_replace( "/\s+/", " ", $compact );
	}
}