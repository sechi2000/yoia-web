<?php
/**
 * @brief		Upgrader: Custom Upgrade Options
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 November 2024
 */

$options = array();
require "../upgrade/lang.php";

/* Show us what is deprecated */
if ( \IPS\Settings::i()->post_before_registering AND \IPS\Settings::i()->bot_antispam_type === 'none' )
{
	$options[] = new \IPS\Helpers\Form\Custom( '107783_pbr_captcha', options: array( 'getHtml' => function( $element ) {
		return "<p>In an effort to reduce sending spam via email, Post Before Registering now requires that CAPTCHA protection is enabled. 
Post Before Registering will be disabled until a CAPTCHA is enabled via AdminCP > Members > Spam Prevention > CAPTCHA.</p>";
	} ), customValidationCode: function( $val ) {}, id: '107783_pbr_captcha' );
}
