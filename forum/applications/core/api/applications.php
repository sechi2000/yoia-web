<?php
/**
 * @brief		applications API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 Juli 2025
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Application;
use IPS\core\Assignments\Assignment;
use IPS\Db;
use IPS\Node\Api\NodeController;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	applications API
 */
class applications extends NodeController
{
	protected string $class = Application::class;
	
	/**
	 * GET /core/applications
	 * Get list of installed applications
	 *
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\Application>
	 * @return PaginatedResponse<Application>
	 */
	public function GETindex(): Response
	{
		$where = ['app_enabled=?', 1];
		return $this->_list($where);
	}
}