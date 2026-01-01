<?php
/**
 * @brief		4.7.22 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		17 Jun 2025
 */

namespace IPS\core\setup\upg_107815;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Settings;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.7.22 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
	 * Classic: If using our global key, move the key to 'custom' setting, so it's retained after we wipe the default.
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		// This step runs later in 5.0.9 Beta 1

		return TRUE;
	}
}