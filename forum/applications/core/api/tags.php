<?php
/**
 * @brief        Tags API
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @since        3 July 2025
 */

namespace IPS\core\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Controller;
use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Content\Tag;
use IPS\Db;
use IPS\Member\Group;
use IPS\Node\Api\NodeController;
use IPS\Request;
use OutOfRangeException;
use function defined;

if( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER[ 'SERVER_PROTOCOL' ]??'HTTP/1.0' ).' 403 Forbidden' );
	exit;
}

/**
 * @brief    Tags API
 */
class tags extends NodeController
{
	protected string $class = Tag::class;

	/**
	 * GET /core/tags
	 * Get list of tags
	 *
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @apireturn	PaginatedResponse<IPS\Content\Tag>
	 * @return		PaginatedResponse
	 */
	public function GETindex(): PaginatedResponse
	{
		return $this->_list();
	}

	/**
	 * GET /core/tags/{id}
	 * Get information about a specific tag
	 *
	 * @param	int			$id				ID Number
	 * @return	Response
	 * @throws	1C358/1		INVALID_ID		The group ID does not exist
	 * @apireturn			\IPS\Content\Tag
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			return $this->_view( $id );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1TAG', 404 );
		}
	}
}