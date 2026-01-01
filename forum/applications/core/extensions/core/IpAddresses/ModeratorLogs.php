<?php
/**
 * @brief		IP Address Lookup: Moderator Logs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Oct 2016
 */

namespace IPS\core\extensions\core\IpAddresses;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\IpAddressesAbstract;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use function count;
use function defined;
use function implode;
use function iterator_to_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup: Moderator Logs
 */
class ModeratorLogs extends IpAddressesAbstract
{
	/**
	 * Supported in the ModCP IP address lookup tool?
	 *
	 * @return	bool
	 * @note	If the method does not exist in an extension, the result is presumed to be TRUE
	 */
	public function supportedInModCp(): bool
	{
		return FALSE;
	}

	/** 
	 * Find Records by IP
	 *
	 * @param	string			$ip			The IP Address
	 * @param	Url|null	$baseUrl	URL table will be displayed on or NULL to return a count
	 * @return	string|int|null
	 */
	public function findByIp( string $ip, ?Url $baseUrl = NULL ): string|int|null
	{
		/* Return count */
		if ( $baseUrl === NULL )
		{
			return Db::i()->select( 'COUNT(*)', 'core_moderator_logs', array( "ip_address LIKE ?", $ip ) )->first();
		}
		
		/* Init Table */
		$table = new TableDb( 'core_moderator_logs', $baseUrl, array( "ip_address LIKE ?", $ip ) );
				
		/* Columns we need */
		$table->include = array( 'member_id', 'action', 'ctime', 'ip_address' );
		$table->mainColumn = 'ctime';
		$table->langPrefix = 'modlogs_';

		$table->tableTemplate  = array( Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'table' );
		$table->rowsTemplate  = array( Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'rows' );
				
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'ctime';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Custom parsers */
		$table->parsers = array(
			'member_id'			=> function( $val, $row )
			{
				$member = Member::load( $val );
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . ' ' . $member->link();
			},
			'ctime'			=> function( $val, $row )
			{
				return DateTime::ts( $val );
			},
			'action'		=> function( $val, $row )
			{
				if ( $row['lang_key'] )
				{
					$langKey = $row['lang_key'];
					$params = array();
					$note = json_decode( $row['note'], TRUE );
					if ( !empty( $note ) )
					{
						foreach ($note as $k => $v)
						{
							$params[] = $v ? Member::loggedIn()->language()->addToStack($k) : $k;
						}
					}
					return Member::loggedIn()->language()->addToStack( $langKey, FALSE, array( 'sprintf' => $params ) );
				}
				else
				{
					return $row['note'];
				}
			},
		);
		
		/* Return */
		return (string) $table;
	}
	
	/**
	 * Find IPs by Member
	 *
	 * @code
	 	return array(
	 		'::1' => array(
	 			'ip'		=> '::1'// string (IP Address)
		 		'count'		=> ...	// int (number of times this member has used this IP)
		 		'first'		=> ... 	// int (timestamp of first use)
		 		'last'		=> ... 	// int (timestamp of most recent use)
		 	),
		 	...
	 	);
	 * @endcode
	 * @param	Member	$member	The member
	 * @return	array|Select
	 */
	public function findByMember( Member $member ) : array|Select
	{
		return Db::i()->select( "ip_address AS ip, count(*) AS count, MIN(ctime) AS first, MAX(ctime) AS last", 'core_moderator_logs', array( 'member_id=?', $member->member_id ), NULL, NULL, 'ip_address' )->setKeyField( 'ip' );
	}

	/**
	 * Removes the logged IP address
	 *
	 * @param int $time
	 * @return void
	 */
	public function pruneIpAddresses( int $time ) : void
	{
		/* Get all admins */
		$adminMember = [];
		$adminGroup = [];
		$query = [];
		$where = [];

		foreach ( Db::i()->select( '*', 'core_admin_permission_rows' ) as $mod )
		{
			if ( $mod['row_id_type'] == 'group' )
			{
				$adminGroup[] = $mod['row_id'];
			}
			else
			{
				$adminMember[] = $mod['row_id'];
			}
		}

		if ( count( $adminGroup ) )
		{
			$query[] = Db::i()->in( 'member_group_id', $adminGroup );
			$query[] = Db::i()->findInSet( 'mgroup_others', $adminGroup );
		}

		if ( count( $adminMember ) )
		{
			$query[] = Db::i()->in( 'member_id', $adminMember );
		}

		/* I mean $query should never be empty, but let's not wait for a ticket and have to release a patch to find out that it could be... */
		if ( count( $query ) )
		{
			$where[] = [ 'member_id IN(?)', Db::i()->select( 'member_id', 'core_members', implode( ' OR ', $query ) ) ];
		}

		$admins = Db::i()->select( 'member_id', 'core_members', $where, NULL, [ 0, 500 ] );

		/* Get all moderators */
		$modMember = [];
		$modGroup = [];
		$query = [];
		$where = [];

		foreach ( Db::i()->select( '*', 'core_moderators' ) as $mod )
		{
			if ( $mod['type'] == 'g' )
			{
				$modGroup[] = $mod['id'];
			}
			else
			{
				$modMember[] = $mod['id'];
			}
		}

		if ( count( $modGroup ) )
		{
			$query[] = Db::i()->in( 'member_group_id', $modGroup );
			$query[] = Db::i()->findInSet( 'mgroup_others', $modGroup );
		}

		if ( count( $modMember ) )
		{
			$query[] = Db::i()->in( 'member_id', $modMember );
		}

		/* I mean $query should never be empty, but let's not wait for a ticket and have to release a patch to find out that it could be... */
		if ( count( $query ) )
		{
			$where[] = [ 'member_id IN(?)', Db::i()->select( 'member_id', 'core_members', implode( ' OR ', $query ) ) ];
		}

		$moderators = Db::i()->select( 'member_id', 'core_members', $where, NULL, [ 0, 500 ] );

		$staff = iterator_to_array( $moderators ) + iterator_to_array( $admins );

		Db::i()->update( 'core_moderator_logs', array( 'ip_address' => '' ), array( [ 'ctime < ?', $time ], [ Db::i()->in( 'member_id', $staff, true ) ] ) );
	}
}