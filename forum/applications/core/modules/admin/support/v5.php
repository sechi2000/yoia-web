<?php
/**
 * @brief		v5
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		19 Sep 2024
 */

namespace IPS\core\modules\admin\support;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\Db;
use IPS\Http\Url;
use IPS\Login;
use IPS\Member;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use function count;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * v5
 */
class _v5 extends \IPS\Dispatcher\Controller
{
	public static bool $csrfProtected = true;

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$actions = $this->v5Actions();

		Output::i()->cssFiles	= array_merge( Output::i()->cssFiles, Theme::i()->css( 'support/dashboard.css', 'core', 'admin' ) );
		Output::i()->title = Member::loggedIn()->language()->addToStack( 'prepare_v5_title' );
		Output::i()->output = Theme::i()->getTemplate( 'support' )->v5( ...$actions );
	}

	public function v5actions()
	{
		if( \IPS\CIC )
		{
			$phpCheck = true;
			$mySqlCheck = true;
			$innoDb = true;
			$elasticsearch = false;
		}
		else
		{
			/* Check PHP */
			$phpCheck = ( version_compare( PHP_VERSION, "8.1.0" ) >= 0 );

			/* Check MySQL */
			$mySqlVersion = Db::i()->server_info;
			$mySqlCheck = ( version_compare( $mySqlVersion, "8.0.13" ) >=0 );

			/* Check InnoDb */
			$innoDb = !count( iterator_to_array( Db::i()->query( "SHOW TABLE STATUS WHERE Engine!='InnoDB'" ) ) );

			/* Are we using elasticsearch */
			$elasticsearch = ( \IPS\Settings::i()->search_method == 'elastic' AND \IPS\Settings::i()->search_elastic_server );
		}

		Output::i()->sidebar['actions']['deprecations'] = array(
			'icon'		=> 'question-circle',
			'title'		=> 'deprecation_tracker',
			'link'		=> Url::ips( 'docs/deprecation_tracker' ),
			'target'	=> '_blank'
		);

		$deprecations = [];

		$status = (bool) Db::i()->select( 'count(*)', 'core_member_status_updates' )->first();
		if( $status )
		{
			$deprecations['status'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_status' );
		}

		$login = (bool) Db::i()->select( 'count(*)', 'core_login_methods', [
			[ 'login_enabled=?', 1 ],
			[ Db::i()->in( 'login_classname', [
				'IPS\Login\Handler\Standard',
				'IPS\Login\Handler\OAuth2\Apple',
				'IPS\Login\Handler\OAuth2\Facebook',
				'IPS\Login\Handler\OAuth2\Google',
				'IPS\Login\Handler\OAuth2\LinkedIn',
				'IPS\Login\Handler\OAuth2\Microsoft',
				'IPS\Login\Handler\OAuth1\Twitter',
				'IPS\Login\Handler\OAuth2\Invision',
				'IPS\Login\Handler\OAuth2\Wordpress',
				'IPS\Login\Handler\OAuth2\Custom',
				'IPS\Login\Handler\ExternalDatabase',
				'IPS\Login\Handler\LDAP',
				'IPS\convert\Login'
			], true ) ]
		] )->first();
		if( $login )
		{
			$deprecations['login'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_login' );
		}

		$login = new Login;
		if( $login->authType() & Login::AUTH_TYPE_USERNAME )
		{
			$deprecations['display_names'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_display_name_login' );
		}

		if( \IPS\CP_DIRECTORY != 'admin' )
		{
			$deprecations['cp_directory'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_cp_directory' );
		}

		if( Settings::i()->tags_open_system )
		{
			$deprecations['open_tagging'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_open_tagging' );
		}

		if( (bool) Db::i()->select('count(*)', 'core_social_promote_sharers', [ 'sharer_key!=? AND sharer_enabled=?', 'Internal', 1 ] )->first() )
		{
			$deprecations['social_promotes'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_social_promotes' );
		}

		if( !\IPS\CIC )
		{
			if( !Settings::i()->use_friendly_urls )
			{
				$deprecations['core_furls'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_core_furls' );
			}

			$dummyRequest = Url::external('https://invisioncommunity.com')->request();
			if( ( $dummyRequest instanceof \IPS\Http\Request\Sockets ) )
			{
				$deprecations['classic_sockets'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_classic_sockets' );
			}
		}

		/* Pages Checks */
		if( Application::appIsEnabled( 'cms' ) )
		{
			$appName = Application::load('cms')->_title;
			foreach( Db::i()->select( '*', 'cms_blocks', [ 'block_type=?', 'custom'] ) as $row )
			{
				$config = json_decode( $row['block_config'], true );
				if( isset( $config['editor'] ) and $config['editor'] == 'php' )
				{
					$deprecations['php_blocks'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_php_blocks', false, [ 'sprintf' => [ $appName ] ] );
					break;
				}
			}
		}

		/* Commerce checks */
		if( Application::appIsEnabled( 'nexus' ) )
		{
			$appName = Application::load('nexus')->_title;
			$physical = (bool) Db::i()->select( 'count(*)', 'nexus_packages_products', [ 'p_physical=?', 1 ] )->first();
			if( $physical )
			{
				$deprecations['physical'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_physical', false, [ 'sprintf' => [ $appName ] ] );
			}

			$shipping = (bool) Db::i()->select( 'count(*)', 'nexus_shipping' )->first();
			$shipOrders = (bool) Db::i()->select( 'count(*)', 'nexus_ship_orders' )->first();
			if( $shipping or $shipOrders )
			{
				$deprecations['shipping'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_shipping', false, [ 'sprintf' => [ $appName ] ] );
			}

			$requests = (bool) Db::i()->select( 'count(*)', 'nexus_support_requests' )->first();
			if( $requests )
			{
				$deprecations['support'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_support', false, [ 'sprintf' => [ $appName ] ] );
			}

			$gateways = iterator_to_array(
				Db::i()->select( 'distinct m_gateway', 'nexus_paymethods', Db::i()->in( 'm_gateway', [ 'Braintree', 'AuthorizeNet', 'TwoCheckout' ] ) )
			);
			if( count( $gateways ) )
			{
				$deprecations['gateways'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_gateways', false, [ 'sprintf' => [ $appName, implode( ", ", $gateways ) ] ] );
			}

			if( Settings::i()->easypost_enable )
			{
				$deprecations['easypost'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_easypost', false, [ 'sprintf' => [ $appName ] ] );
			}
		}

		/* Forums checks */
		if( Application::appIsEnabled( 'forums' ) )
		{
			$appName = Application::load('forums')->_title;
			if( (bool) \IPS\Db::i()->select( 'COUNT(*)', 'forums_forums', \IPS\Db::i()->bitwiseWhere( \IPS\forums\Forum::$bitOptions['forums_bitoptions'], 'bw_enable_answers' ) )->first() )
			{
				$deprecations['qa'] = Member::loggedIn()->language()->addToStack( 'v5_deprecate_forums_qa', false, [ 'sprintf' => [ $appName ] ] );
			}
		}

		return [ 'phpCheck' => $phpCheck, 'mySqlCheck' => $mySqlCheck, 'innoDb' => $innoDb, 'elasticsearch' => $elasticsearch, 'deprecations' => $deprecations ];
	}
}