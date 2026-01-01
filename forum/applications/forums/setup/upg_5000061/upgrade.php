<?php
/**
 * @brief		5.0.4 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		07 Mar 2025
 */

namespace IPS\forums\setup\upg_5000061;

use IPS\Db;
use IPS\Member\Group;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.4 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
	 * Previous to this version, everyone could mark a post as helpful. Now, only certain groups can, so lets restore the previous permissions by giving all groups the permission to mark a post as helpful.
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		/* Get all groups inc admin, exc guests */
		foreach( Group::groups( true, false ) as $group )
		{
			$group = Group::load( $group->g_id );
			$group->g_bitoptions['gbw_can_mark_helpful'] = 1;
			$group->save();
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}