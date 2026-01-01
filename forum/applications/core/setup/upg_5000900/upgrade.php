<?php
/**
 * @brief		5.0.9 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		16 Jun 2025
 */

namespace IPS\core\setup\upg_5000900;

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
 * 5.0.9 Beta 1 Upgrade Code
 */
class Upgrade
{
	private string $defaultKey = '6LcH7UEUAAAAAIGWgOoyBKAqjLmOIKzfJTOjyC7z';

	/**
	 * Classic: If using our global key, move the key to 'custom' setting, so it's retained after we wipe the default.
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		$captchaType = Settings::i()->bot_antispam_type;
		$recaptchaPublicKey = Settings::i()->recaptcha2_public_key;
		$recaptchaSecretKey = Settings::i()->recaptcha2_private_key;

		/* Are we using the services with the global key? */
		if( !\IPS\CIC AND in_array( $captchaType, [ 'invisible', 'recaptcha2' ] ) AND $recaptchaPublicKey == $this->defaultKey )
		{
			/* Set the defaults */
			Db::i()->update( 'core_sys_conf_settings', [ 'conf_default' => '', ], [ 'conf_key=? OR conf_key=?', 'recaptcha2_public_key', 'recaptcha2_private_key' ] );
			Db::i()->update( 'core_sys_conf_settings', [ 'conf_default' => 'none', ], [ 'conf_key=?', 'bot_antispam_type' ] );
			Settings::i()->clearCache();

			/* Set the global keys to custom settings for now */
			Settings::i()->changeValues( [
				'bot_antispam_type' 		=> $captchaType,
				'recaptcha2_public_key' 	=> $recaptchaPublicKey,
				'recaptcha2_private_key'	=> $recaptchaSecretKey
			] );
		}
		/* Switch Cloud customers to use Turnstile */
		elseif( \IPS\CIC AND in_array( $captchaType, [ 'invisible', 'recaptcha2' ] ) AND $recaptchaPublicKey == $this->defaultKey )
		{
			Settings::i()->changeValues( [ 'bot_antispam_type' => 'turnstile' ] );
		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}