<?php
/**
 * @brief		5.0.11 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		04 Sep 2025
 */

namespace IPS\core\setup\upg_5001104;

use IPS\Db;
use function defined;
use function json_decode;
use function json_encode;
use function preg_match;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.11 Upgrade Code
 */
class Upgrade
{
	/**
	 * Fix logo data to prevent them from disappearing every time the cache is cleared
	 *
	 * @return bool|array
	 */
	public function step1() : bool|array
	{
		foreach( Db::i()->select( 'set_id,set_logo_data', 'core_themes' ) as $row )
		{
			$logoData = $row['set_logo_data'] ? json_decode( $row['set_logo_data'], true ) : [];
			foreach( $logoData as $type => $logo )
			{
				/* Check if fullUrl exists and is a string */
				if( isset( $logo['fullUrl'] ) AND is_string( $logo['fullUrl'] ) )
				{
					/* We previously stored this as a resource tag; extract the file name instead */
					preg_match( '/resource=\"custom\/(.+?)\"/is', $logo['fullUrl'], $match );
					if( !empty( $match ) )
					{
						$logoData[ $type ]['filename'] = $match[1];
					}
				}

				/* If we still don't have a filename, use the URL */
				if( isset( $logo['url'] ) AND !isset( $logoData[ $type ]['filename'] ) )
				{
					$logoData[ $type ]['filename'] = $logo['url'];
				}

				unset( $logoData[ $type ]['url'] );
				unset( $logoData[ $type ]['fullUrl'] );
			}

			Db::i()->update( 'core_themes', [ 'set_logo_data' => json_encode( $logoData ) ], [ 'set_id=?', $row['set_id'] ] );
		}

		return true;
	}


	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}