<?php

/**
 * @brief        PermissionsAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/20/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Item;
use IPS\Content\Permissions;
use IPS\Member;
use IPS\Node\Model;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class PermissionsAbstract
{
	/**
	 * Get node classes
	 *
	 * @return	array
	 */
	public function getNodeClasses() : array
	{
		return [];
	}

	/**
	 * Override individual permissions for specific items
	 * Return one of @see Permissions::PERM_ALLOW, Permissions::PERM_DENY, Permissions::PERM_DEFAULT
	 * PERM_DEFAULT will essentially do nothing and default to the standard permission checks.
	 * PERM_ALLOW and PERM_DENY will force an override.
	 *
	 * @note Check the object class before executing your custom logic.
	 * You MUST return PERM_DEFAULT for any object that you are not checking.
	 *
	 * @param string $permission
	 * @param object $object
	 * @param Member|null $member
	 * @return int
	 */
	public function checkPermission( string $permission, object $object, ?Member $member=null ) : int
	{
		return Permissions::PERM_DEFAULT;
	}

	/**
	 * Returns an array of node IDs with the permission equivalent of @see Permissions::PERM_ALLOW
	 * Used in queries that retrieve nodes and/or items.
	 *
	 * @see Model::nodesWithPermission()
	 * @see Item::getItemsWithPermission()
	 * @param string $permission
	 * @param string $class
	 * @param Member|null $member
	 * @return array
	 */
	public function nodeIdsToAllow( string $permission, string $class, ?Member $member=null ) : array
	{
		return [];
	}

	/**
	 * Returns an array of node IDs with the permission equivalent of @see Permissions::PERM_DENY
	 * Used in queries that retrieve nodes and/or items.
	 *
	 * @see Model::nodesWithPermission()
	 * @see Item::getItemsWithPermission()
	 * @param string $permission
	 * @param string $class
	 * @param Member|null $member
	 * @return array
	 */
	public function nodeIdsToDeny( string $permission, string $class, ?Member $member=null ) : array
	{
		return [];
	}
}