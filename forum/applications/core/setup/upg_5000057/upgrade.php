<?php


/**
 * @brief		5.0.3 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		19 Feb 2025
 */

namespace IPS\core\setup\upg_5000057;

use IPS\Content;
use IPS\Db;
use function class_exists;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.3 Upgrade Code
 */
class Upgrade
{
	/**
	 * ...
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function finish() : bool|array
	{
		/* Loop through promoted content and make sure the "featured" flag is properly set.
		This can happen if content was promoted in v4.
		Do it by content type so that we can bulk-update */
		$types = iterator_to_array(
			Db::i()->select( 'distinct promote_class', 'core_content_promote' )
		);

		foreach( $types as $class )
		{
			try
			{
				if ( ! class_exists( $class ) )
				{
					continue;
				}

				/* @var Content $class */
				if( isset( $class::$databaseColumnMap['featured'] ) )
				{
					/* Get all the IDs and update them all at once */
					Db::i()->update( $class::$databaseTable, [ $class::$databasePrefix . $class::$databaseColumnMap['featured'] => 1 ], [
					[ $class::$databasePrefix . $class::$databaseColumnId . ' IN (?)',
					  Db::i()->select( 'promote_class_id', 'core_content_promote', [ 'promote_class=?', $class ] ) ]
					] );
				}
			}
			catch( \Exception $e )
			{

			}
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}