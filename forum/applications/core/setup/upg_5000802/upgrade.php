<?php
/**
 * @brief		5.0.8 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		12 May 2025
 */

namespace IPS\core\setup\upg_5000802;

use Brick\Math\Exception\DivisionByZeroException;
use IPS\Db;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.8 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
	 * Remove theme editor language strings that we no longer use
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		Db::i()->delete( 'core_sys_lang_words', [ "word_key like concat( ?, '%') and word_is_custom=?", 'themeeditor_cat_', 1 ] );
		Db::i()->delete( 'core_sys_lang_words', [ "word_key like concat( ?, '%') and word_is_custom=?", 'themeeditor_var_', 1 ] );

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}