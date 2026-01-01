<?php
/**
 * @brief		5.0.4 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		04 Feb 2025
 */

namespace IPS\cms\setup\upg_5000061;

use IPS\Db;
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
	 * Minor cleanup on pages from v4
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		/* Clear out old invalid templates */
		Db::i()->update( 'cms_pages', [ 'page_template' => null, 'page_ipb_wrapper' => 1 ], [ 'page_template=? or page_template=?', 'page_builder__single_column__page_page_builder_single_column', 'page_builder__single_column__page_builder_single_column' ] );

		return TRUE;
	}

	/**
	 * Handle legacy database templates from v4; they're all broken
	 *
	 * @return bool|array
	 */
	public function step2() : bool|array
	{
		/* Lucky for us, v4 had a bug where editing a master template set the original_group to null.
		We can use this to find any templates that were edited in v4 but not in v5. */
		foreach( Db::i()->select( '*', 'cms_templates', [ 'template_master=? and template_user_edited=? and template_user_created=? and template_type=? and template_original_group is null', 0, 1, 0, 'template' ] ) as $row )
		{
			/* Add a prefix to the group and key so that it's clear this is a v4 backup */
			Db::i()->update( 'cms_templates', [
				'template_group' => 'v4_' . $row['template_group'],
				'template_key' => $row['template_location'] . '_v4_' . $row['template_group'] . '_' . $row['template_title'],
			], [ 'template_id=?', $row['template_id'] ] );
		}

		return true;
	}

	/**
	 * Force a rebuild of any custom CSS templates
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step3() : bool|array
	{
		/* We had a bug where CSS templates were not being parsed properly.
		Clear these out so that we force a rebuild the next time the template is lodaed. */
		Db::i()->update( 'cms_templates', [ 'template_file_object' => null ], [ 'template_type=?', 'css' ] );
		Db::i()->update( 'cms_pages', [ 'page_js_css_objects' => null ] );

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}