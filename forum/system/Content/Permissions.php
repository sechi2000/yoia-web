<?php

/**
 * @brief        Permissions
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        4/16/2025
 */

namespace IPS\Content;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Application\Module;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Extensions\PermissionsAbstract;
use IPS\IPS;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Model;
use IPS\Settings;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Permissions
{
	/**
	 * @brief	Used for Permissions extensions; override default permissions
	 */
	const PERM_ALLOW = 1;

	/**
	 * @brief	Used for Permissions extensions; override default permissions
	 */
	const PERM_DENY = 2;

	/**
	 * @brief	Used for Permissions extensions; revert to default permission checks
	 */
	const PERM_DEFAULT = 0;

	/**
	 * @var array
	 */
	protected static array $nodeIncludesMap = [];

	/**
	 * @var array
	 */
	protected static array $nodeExcludeMap = [];

	/**
	 * Load all the inclusion/exclusion mappings from extensions
	 *
	 * @param string $permission
	 * @param string $nodeClass
	 * @param Member $member
	 * @return void
	 */
	protected static function loadMappings( string $permission, string $nodeClass, Member $member ) : void
	{
		if( !isset( static::$nodeIncludesMap[ $nodeClass ][ $permission ] ) )
		{
			$include = [];
			$exclude = [];
			foreach( Application::allExtensions( 'core', 'Permissions' ) as $ext )
			{
				/* @var PermissionsAbstract $ext */
				$include = array_merge( $include, $ext->nodeIdsToAllow( $permission, $nodeClass, $member ) );
				$exclude = array_merge( $exclude, $ext->nodeIdsToDeny( $permission, $nodeClass, $member ) );
			}

			static::$nodeIncludesMap[ $nodeClass ][ $permission ] = $include;
			static::$nodeExcludeMap[ $nodeClass ][ $permission ] = $exclude;
		}
	}

	/**
	 * Easily load included IDs, for my sanity
	 *
	 * @param string $permission
	 * @param string $nodeClass
	 * @param Member|null $member
	 * @param int $permType
	 * @return array|null
	 */
	protected static function getMapping( string $permission, string $nodeClass, ?Member $member, int $permType ) : ?array
	{
		/* Only allow these extensions to run on the front-end or from the API */
		if( !Dispatcher::hasInstance() or ( Dispatcher::i()->controllerLocation != 'front' and Dispatcher::i()->controllerLocation != 'api' ) )
		{
			return null;
		}

		$member = $member ?: Member::loggedIn();

		static::loadMappings( $permission, $nodeClass, $member );

		switch( $permType )
		{
			case static::PERM_ALLOW:
				return count( static::$nodeIncludesMap[ $nodeClass ][ $permission ] ) ? static::$nodeIncludesMap[ $nodeClass ][ $permission ] : null;

			case static::PERM_DENY:
				return count( static::$nodeExcludeMap[ $nodeClass ][ $permission ] ) ? static::$nodeExcludeMap[ $nodeClass ][ $permission ] : null;
		}

		return null;
	}

	/**
	 * Wrapper method to call extension
	 *
	 * @param string $permission
	 * @param object $object
	 * @param Member|Group|null $member
	 * @return int
	 */
	public static function can( string $permission, object $object, Member|Group|null $member=null ) : int
	{
		/* Rather than do a breaking change, skip this check if we pass in a group */
		if( $member instanceof Group )
		{
			return static::PERM_DEFAULT;
		}

		/* Only allow these extensions to run on the front-end or from the API */
		if( !Dispatcher::hasInstance() or ( Dispatcher::i()->controllerLocation != 'front' and Dispatcher::i()->controllerLocation != 'api' ) )
		{
			return static::PERM_DEFAULT;
		}

		foreach( Application::allExtensions( 'core', 'Permissions' ) as $ext )
		{
			/* @var PermissionsAbstract $ext */
			$result = $ext->checkPermission( $permission, $object, $member );
			if( $result !== static::PERM_DEFAULT )
			{
				return $result;
			}
		}

		return static::PERM_DEFAULT;
	}

	/**
	 * Build a query clause to pull node IDs from the permissions table.
	 *
	 * @param string $permission
	 * @param string $nodeClass
	 * @param Member|null $member
	 * @param bool $includeClubs
	 * @return array
	 */
	public static function nodePermissionClause( string $permission, string $nodeClass, ?Member $member=null, bool $includeClubs=true ) : array
	{
		$member = $member ?: Member::loggedIn();

		/* If clubs are disabled, or we don't have permission, force it to exclude them */
		if( !Settings::i()->clubs or !$member->canAccessModule( Module::get( 'core', 'clubs', 'front' ) ) or !IPS::classUsesTrait( $nodeClass, ClubContainer::class ) )
		{
			$includeClubs = false;
		}

		/* @var Model $nodeClass */
		/* @var array $permissionMap */

		$clause = [
			Db::i()->findInSet( 'core_permission_index.perm_' . $nodeClass::$permissionMap[ $permission ], ( $includeClubs ? $member->permissionArray() : $member->groups ) ),
			'core_permission_index.perm_' . $nodeClass::$permissionMap[ $permission ] . '=?'
		];
		$binds = [
			'*'
		];

		/* If we have any specific inclusions, put them in */
		if( $includes = static::getMapping( $permission, $nodeClass, $member, static::PERM_ALLOW ) )
		{
			$clause[] = 'core_permission_index.perm_type_id in (' . implode( ",", $includes ) . ')';
		}

		$return = [
			array_merge( array( '(' . implode( ' OR ', $clause ) . ')' ), $binds )
		];

		/* If we have any specific exclusions, we need it separate so that we make it an AND */
		if( $excludes = static::getMapping( $permission, $nodeClass, $member, static::PERM_DENY ) )
		{
			$return[] = [ Db::i()->in( 'core_permission_index.perm_type_id', $excludes, true ) ];
		}

		return $return;
	}
}