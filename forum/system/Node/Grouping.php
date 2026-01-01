<?php

/**
 * @brief        Grouping
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        5/6/2025
 */

namespace IPS\Node;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

trait Grouping
{
	/**
	 * Return all groups available for this node class
	 *
	 * @return array
	 */
	public static function availableNodeGroups() : array
	{
		$return = [];
		$groups = NodeGroup::getStore()[ get_called_class() ] ?? [];
		if( !empty( $groups ) )
		{
			foreach( $groups as $group )
			{
				$return[ $group['group_id'] ] = NodeGroup::constructFromData( $group );
			}
		}
		return $return;
	}

	/**
	 * @var array|null
	 */
	protected ?array $_nodeGroups = null;

	/**
	 * Return the groups to which this node belongs
	 *
	 * @return array
	 */
	public function groups() : array
	{
		if( $this->_nodeGroups === null )
		{
			$this->_nodeGroups = iterator_to_array(
				new ActiveRecordIterator(
					Db::i()->select( 'core_node_groups.*', 'core_node_groups_nodes', [ 'core_node_groups_nodes.node_id=?', $this->_id ] )
						->join( 'core_node_groups', 'core_node_groups_nodes.group_id=core_node_groups.group_id' ),
					NodeGroup::class
				)
			);
		}

		return $this->_nodeGroups;
	}
}