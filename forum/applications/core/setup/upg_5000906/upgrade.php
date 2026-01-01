<?php
/**
 * @brief		5.0.10 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		01 Jul 2025
 */

namespace IPS\core\setup\upg_5000906;

use IPS\Application;
use IPS\Db;
use function defined;
use function file_get_contents;
use function json_decode;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.10 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
	 * We moved app-specific columns in core_groups to the core schema.
	 * Sync the table so that all columns are present, regardless of
	 * whether the app is installed.
	 * @see \IPS\core\modules\admin\developer\schema::resolveSchemaConflicts()
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		$schema = json_decode( file_get_contents( Application::getRootPath( 'core' ) . '/applications/core/data/schema.json' ), true );
		$schemaDefinition = $schema['core_groups'];

		foreach( $schemaDefinition['columns'] as $column )
		{
			if ( ! Db::i()->checkForColumn( $schemaDefinition['name'], $column['name'] ) )
			{
				try
				{
					Db::i()->addColumn( $schemaDefinition['name'], $column );
				}
				catch( \Exception $e )
				{
					/* Something went wrong (classic..) but the database sync at the end of the upgrade should get it,
					if not the support tool will nag about it. Either way, blocking the upgrade at this point with no way to recover it
					would suck a lot. */
				}
			}
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}