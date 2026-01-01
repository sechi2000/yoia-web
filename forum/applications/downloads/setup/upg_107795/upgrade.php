<?php
/**
 * @brief		4.7.20 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		14 Nov 2024
 */

namespace IPS\downloads\setup\upg_107795;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\downloads\Field;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Db;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.7.20 Beta 1 Upgrade Code
 */
class _Upgrade
{
	/**
	 * This step sets default permissions on existing Downloads custom fields.
	 * Previous versions will not have any permissions set and will cause fields to be inaccessible to all groups,
	 * so we set permissions that make sense.
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
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
			switch ( $k )
			{
				case 'view':
				case 'edit':
				case 'add':
					$perms["perm_{$v}"] = '*';
					break;
			}
		}

		foreach( $fields as $field )
		{
			/* @var Field $field */
			try
			{
				$current = \IPS\Db::i()->select( '*', 'core_permission_index', array( 'app=? AND perm_type=? AND perm_type_id=?', 'downloads', 'fields', $field->_id ) )->first();

				/* This shouldn't happen, but just in case */
				if( !empty( $current['perm_view'] ) )
				{
					continue;
				}

				Db::i()->update( 'core_permission_index', $perms, [ 'perm_id=?', $current['perm_id'] ] );
			}
			catch( \UnderflowException $e )
			{
				/* Recommended permissions */
				$update = array_merge( $perms, [ 'app' => 'downloads', 'perm_type' => 'fields', 'perm_type_id' => $field->_id ] );
				Db::i()->insert( 'core_permission_index', $update );
			}
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}