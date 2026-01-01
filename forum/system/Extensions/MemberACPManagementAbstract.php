<?php

/**
 * @brief        MemberManagementAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        10/10/2025
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Callback;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Helpers\Table\Table;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class MemberACPManagementAbstract
{
	/**
	 * Additional columns allowed in the member export.
	 * Return an array where the key is the column name
	 * and the value is a language string
	 *
	 * @return array<string,string>
	 */
	public function exportColumns() : array
	{
		return [];
	}

	/**
	 * Custom formatting for any columns defined in exportColumns
	 *
	 * @param string $column
	 * @param array $member
	 * @return string
	 */
	public function exportColumnValue( string $column, array $member ) : string
	{
		return $member[ $column ] ?? '';
	}

	/**
	 * Additional columns to show in the ACP member list
	 * Return an array where the key is the column name
	 * and the value is an optional callback method for @see Table::$parsers
	 *
	 * @return array<string,?Callback>
	 */
	public function acpColumns() : array
	{
		/* Example:
		return array(
			'column1' => null,
			'column2' => function( $val, $row )
			{
				return '';
			}
		);*/

		return [];
	}

	/**
	 * Additional tables to join on the ACP member list
	 * Format should match @see TableDb::$joins
	 * @note If you are including a column in acpColumns(), make sure
	 * you add it to the 'select' element of your join clause
	 *
	 * @return array
	 */
	public function acpJoins() : array
	{
		return [];
	}
}