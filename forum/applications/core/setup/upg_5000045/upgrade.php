<?php
/**
 * @brief		5.0.0 Beta 13 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		24 Jan 2025
 */

namespace IPS\core\setup\upg_5000045;

use IPS\Db;
use IPS\Settings;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Beta 13 Upgrade Code
 */
class Upgrade
{
	/**
	 * Show SendGrid for those that have it configured
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		try
		{
			Db::i()->select( 'conf_value', 'core_sys_conf_settings', [ 'conf_key=?', 'sendgrid_deprecated' ] )->first();
		}
		catch( \UnderflowException $ex )
		{
			$insert = [
				'conf_key'      => 'sendgrid_deprecated',
				'conf_value'    => '',
				'conf_default'  => '1',
				'conf_keywords' => '',
				'conf_app'      => 'core',
				'conf_report'   => 'full'
			];

			/* This key was added recently so it may not exist */
			Db::i()->insert( 'core_sys_conf_settings', $insert );
		}

		/* Make we see's our new one */
		Settings::i()->clearCache();

		/* Set our value */
		Settings::i()->changeValues( [ 'sendgrid_deprecated' => Settings::i()->sendgrid_api_key ? 0 : 1 ] );


		return TRUE;
	}
}