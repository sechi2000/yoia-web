<?php

/**
 * @brief        Group
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        5/5/2025
 */

namespace IPS\Node;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Data\Store;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Translatable;
use IPS\Lang;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use OutOfRangeException;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class NodeGroup extends Model
{
    /**
     * @brief       [ActiveRecord] Multiton Store
     */
    protected static array $multitons;
       
    /**
     * @brief       [ActiveRecord] Database Table
     */
    public static ?string $databaseTable = 'core_node_groups';
       
    /**
     * @brief       [ActiveRecord] Database Prefix
     */
    public static string $databasePrefix = 'group_';
       
    /**
     * @brief       [Node] Node Title
     */
    public static string $nodeTitle = 'node_groups';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$titleLangPrefix}_{$id}" as the key
	 */
	public static ?string $titleLangPrefix = 'core_node_group_';

	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'nodeGroups' );
       
    /**
     * @brief       Bitwise values for members_bitoptions field
     */
    public static array $bitOptions = array(
        'bitoptions' => array(
            'bitoptions' => array(
            )
        )
    );

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
    {
		$form->add( new Translatable( 'node_group_name', NULL, TRUE, array( 'app' => 'core', 'key' => ( $this->id ?  static::$titleLangPrefix . $this->id : NULL ) ) ) );

		/* The node class should already be set at this point, but let's be sane */
		if( $this->class )
		{
			$field = new Node( 'node_group_nodes', iterator_to_array( $this->nodes() ), false, [
				'class' => $this->class,
				'multiple' => true
			] );

			$nodeClass = $this->class;
			$field->label = Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle );
			$form->add( $field );
		}

		parent::form( $form );
    }

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( !$this->id )
		{
			$this->save();
		}

		Lang::saveCustom( 'core', static::$titleLangPrefix . $this->id, $values['node_group_name'] );
		unset( $values['node_group_name'] );

		if( !empty( $values['node_group_nodes'] ) )
		{
			$current = iterator_to_array(
				Db::i()->select( 'node_id', 'core_node_groups_nodes', [ 'group_id=?', $this->id ] )
			);

			$usedIds = [];
			foreach( $values['node_group_nodes'] as $node )
			{
				$usedIds[] = $node->_id;
				if( !in_array( $node->_id, $current ) )
				{
					Db::i()->insert( 'core_node_groups_nodes', [ 'group_id' => $this->id, 'node_id' => $node->id ] );
				}
			}

			/* Anything to delete? */
			$toDelete = array_diff( $current, $usedIds );
			if( count( $toDelete ) )
			{
				Db::i()->delete( 'core_node_groups_nodes', [
					[ 'group_id=?', $this->id ],
					[ Db::i()->in( 'node_id', $toDelete ) ]
				]);
			}
		}

		unset( $values['node_group_nodes'] );

		return $values;
	}

	/**
	 * Return all nodes that are part of this group
	 *
	 * @return ActiveRecordIterator
	 */
	public function nodes() : ActiveRecordIterator
	{
		$nodeClass = $this->class;
		return new ActiveRecordIterator(
			Db::i()->select( '*', $nodeClass::$databaseTable, Db::i()->in( $nodeClass::$databasePrefix . $nodeClass::$databaseColumnId, $this->nodeIds() ) ),
				$nodeClass
		);
	}

	/**
	 * Easier way to get the node IDs from the cache
	 *
	 * @return array
	 */
	public function nodeIds() : array
	{
		return static::getStore()[ $this->class ][ $this->id ]['nodes'] ?? [];
	}

	/**
	 * Attempt to load cached data
	 *
	 * @note	This should be overridden in your class if you enable $loadFromCache
	 * @see		ActiveRecord::$loadFromCache
	 * @return    array
	 */
	public static function getStore(): array
	{
		try
		{
			$cache = Store::i()->nodeGroups;
		}
		catch( OutOfRangeException )
		{
			$cache = [];
			foreach( Db::i()->select( '*', static::$databaseTable ) as $row )
			{
				if( !isset( $cache[ $row['group_class'] ] ) )
				{
					$cache[ $row['group_class'] ] = [];
				}

				$row['nodes'] = iterator_to_array(
					Db::i()->select( 'node_id', 'core_node_groups_nodes', [ 'group_id=?', $row['group_id'] ] )
				);

				$cache[ $row['group_class'] ][ $row['group_id'] ] = $row;
			}
			Store::i()->nodeGroups = $cache;
		}

		return $cache;
	}

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete() : void
	{
		parent::delete();

		Db::i()->delete( 'core_node_groups_nodes', [ 'group_id=?', $this->id ] );
	}
}