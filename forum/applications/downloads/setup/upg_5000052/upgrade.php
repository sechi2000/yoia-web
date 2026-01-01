<?php
/**
 * @brief		5.0.1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		04 Feb 2025
 */

namespace IPS\downloads\setup\upg_5000052;

use IPS\Db;
use IPS\downloads\Field;
use IPS\Patterns\ActiveRecordIterator;
use UnderflowException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.1 Upgrade Code
 */
class Upgrade
{
	/**
	 * Set default permissions for Downloads Custom Fields.
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		/* Set default permissions on custom fields */
		$fields = iterator_to_array(
			new ActiveRecordIterator(
				Db::i()->select( '*', 'downloads_cfields' ),
				Field::class
			)
		);

		/* Set up recommended permissions */
		$perms = [];
		foreach ( Field::$permissionMap as $k => $v )
		{
			$perms[ "perm_{$v}" ] = '*';
		}

		foreach( $fields as $field )
		{
			/* @var Field $field */
			try
			{
				/* If we already have permissions (maybe from v4), don't touch them */
				$current = Db::i()->select( '*', 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id=?', 'downloads', 'fields', $field->_id ) )->first();
			}
			catch( UnderflowException $e )
			{
				/* Recommended permissions */
				$insert = array_merge( $perms, [ 'app' => 'downloads', 'perm_type' => 'fields', 'perm_type_id' => $field->_id ] );
				Db::i()->insert( 'core_permission_index', $insert );
			}
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}