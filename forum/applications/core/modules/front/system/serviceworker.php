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

use function mb_strlen;
use const LIBXML_NOWARNING;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Service worker controller
 */
class _serviceworker extends \IPS\Dispatcher\Controller
{	
	/**
	 * View Notifications
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$cachedUrls = array();

		$notificationIcon = NULL;

		/* Get an icon to use in notifications */
		$homeScreenIcons = json_decode( \IPS\Settings::i()->icons_homescreen, TRUE ) ?? array();
		
		if( \count( $homeScreenIcons ) )
		{
			foreach( $homeScreenIcons as $name => $image )
			{
				if( isset( $image['width'] ) and $image['width'] == 192 )
				{
					$notificationIcon = \IPS\File::get( 'core_Icons', $image['url'] )->url;
					break;
				}
			}
		}

		/* VARIABLES TO PASS THROUGH TO JS */
		$CACHE_VERSION = \IPS\Theme::i()->cssCacheBustKey();
		$variables = [
			"DEBUG" => boolval( ( \IPS\IN_DEV and \IPS\DEV_DEBUG_JS ) or \IPS\DEBUG_JS ),
			"BASE_URL" => \IPS\Settings::i()->base_url,
			"CACHED_ASSETS" => $cachedUrls,
			"CACHE_NAME" => "invision-community-{$CACHE_VERSION}",
			"CACHE_VERSION" => $CACHE_VERSION,
			"NOTIFICATION_ICON" => $notificationIcon ?: null,
			"DEFAULT_NOTIFICATION_TITLE" => \IPS\Member::loggedIn()->language()->addToStack('default_notification_title'),
			"DEFAULT_NOTIFICATION_BODY" => \IPS\Member::loggedIn()->language()->addToStack('default_notification_body'),
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

		\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $output );
		$output .= file_get_contents( \IPS\ROOT_PATH . '/applications/core/interface/js/serviceWorker.js' );
		$cacheHeaders	= \IPS\IN_DEV !== true ? \IPS\Output::getCacheHeaders(time(), 86400) : array();
		\IPS\Output::i()->sendOutput($output, 200, 'text/javascript', $cacheHeaders);
	}

	/**
	 * Return template for offline page
	 *
	 * @return string
	 */
	protected function buildCollapsedOfflinePage() : string
	{
		$html = \IPS\Theme::i()->getTemplate( 'global', 'core', 'global' )->swOffline();
		\IPS\Member::loggedIn()->language()->parseOutputForDisplay( $html );

		$doc = new \IPS\Xml\DOMDocument( "2.0", "utf-8" );
		$doc->preserveWhiteSpace = false;
		$doc->loadHTML( $html, LIBXML_NOBLANKS );
		$doc->formatOutput = true;
		$compact = $doc->saveHTML();

		// We don't need these segments of whitespace. HTML entities can be used if it's absolutely necessary
		return preg_replace( "/\s+/", " ", $compact );
	}
}