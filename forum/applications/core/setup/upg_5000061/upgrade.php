<?php
/**
 * @brief		5.0.4 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		07 Mar 2025
 */

namespace IPS\core\setup\upg_5000061;

use Exception;
use IPS\core\CustomBadge;
use IPS\Db;
use IPS\File;
use IPS\Log;
use UnexpectedValueException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.4 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
	 * Save custom badge files. There shouldn't be that many of them (please God)
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		/* Allow SVGs without the obscure hash removing the file extension */
		File::$safeFileExtensions[] = 'svg';

		$badges = iterator_to_array( Db::i()->select( "*", 'core_custom_badges' ) );
		foreach( $badges as $badge )
		{
			if ( is_null( $badge['raw'] ) )
			{
				try
				{
					$custombadge = CustomBadge::constructFromData( $badge );
					$custombadge->generateSVG( true );
					$badge['raw'] = $custombadge->raw;
					if ( !is_string( $badge['raw'] ) )
					{
						throw new UnexpectedValueException( "Could not generate SVG for badge {$badge['id']}" );
					}
				}
				catch ( Exception $e )
				{
					Log::log( $e, "Upgrade 5.0.4" );
					continue;
				}
			}

			$file = (string) File::create( 'core_Icons', 'custombadge-' . $badge['id'] . '.svg', $badge['raw'] );
			Db::i()->update( 'core_custom_badges', [ 'file' => $file ], [ 'id=?', $badge['id'] ] );
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}