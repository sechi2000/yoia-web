<?php
/**
 * @brief		Assignments API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		05 Mai 2025
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\core\Assignments\Assignment;
use IPS\Db;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Assignments API
 */
class assignments extends Controller
{
	/**
	 * GET /core/assignments
	 * Get list of assignments
	 * 
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apiparam	int		member_id		Member ID to return only assigned items for the member.
	 * @apiparam	int		team_id			Team ID to return only assigned items for this team.
	 * @apireturn		PaginatedResponse<IPS\core\Assignments\Assignment>
	 * @return PaginatedResponse<Assignment>
	 */
	public function GETindex(): Response
	{
		$page		= isset( Request::i()->page ) ? Request::i()->page : 1;
		$perPage	= isset( Request::i()->perPage ) ? Request::i()->perPage : 25;

		$where = [];
		if( Request::i()->member_id )
		{
			$where[] = [ 'assign_type=?', Assignment::ASSIGNMENT_MEMBER ];
			$where[] = [ 'assign_to=?', Request::i()->member_id ];
		}

		if( Request::i()->team_id )
		{
			$where[] = [ 'assign_type=?', Assignment::ASSIGNMENT_TEAM ];
			$where[] = [ 'assign_to=?', Request::i()->team_id ];
		}
	
		/* Return */
		return new PaginatedResponse(
		200,
		Db::i()->select( '*', Assignment::$databaseTable, $where ),
		$page,
		'IPS\core\Assignments\Assignment',
		Db::i()->select( 'COUNT(*)', Assignment::$databaseTable, $where )->first(),
		$this->member,
		$perPage
		);
	}
}