<?php
/**
 * @brief		ACP Member Profile: Basic Information Block
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 Nov 2017
 */

namespace IPS\core\extensions\core\MemberACPProfileBlocks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application;
use IPS\core\DataLayer;
use IPS\core\MemberACPProfile\Block;
use IPS\Helpers\Menu;
use IPS\Helpers\Menu\Link;
use IPS\Http\Url;
use IPS\Login;
use IPS\Login\Handler;
use IPS\Login\Handler\Standard;
use IPS\Member;
use IPS\nexus\Subscription;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	ACP Member Profile: Basic Information Block
 */
class BasicInformation extends Block
{
	/**
	 * Get output
	 *
	 * @return	string
	 */
	public function output(): string
	{
		$hasPassword = FALSE;
		$canChangePassword = Handler::findMethod( 'IPS\Login\Handler\Standard' );
		$activeIntegrations = array();
		if ( Member::loggedIn()->hasAcpRestriction('core', 'members', 'member_edit') )
		{
			/* Is this an admin? */
			if ( $this->member->isAdmin() AND !Member::loggedIn()->hasAcpRestriction('core', 'members', 'member_edit_admin' ) )
			{
				$canChangePassword = FALSE;
			}
			
			if ( $canChangePassword !== FALSE )
			{
				foreach ( Login::methods() as $method )
				{
					if ( $method->canProcess( $this->member ) )
					{
						if ( !( $method instanceof Standard ) )
						{
							$activeIntegrations[] = $method->_title;
						}
						if ( $method->canChangePassword( $this->member ) )
						{
							$hasPassword = TRUE;
							$canChangePassword = TRUE;
						}
					}
				}
			}
		}
		else
		{
			$canChangePassword = FALSE;
		}
		
		$accountActions = new Menu( 'account_actions', css: 'ipsButton ipsButton--primary ipsButton--large' );
		if ( Member::loggedIn()->member_id != $this->member->member_id AND Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_login' ) AND !$this->member->isBanned() )
		{
			$accountActions->add( new Link( Url::internal( "app=core&module=members&controller=members&do=login&id={$this->member->member_id}" )->csrf(), Member::loggedIn()->language()->addToStack( 'login_as_x', false, [ 'sprintf' => [ $this->member->name ] ] ), dataAttributes: [ 'target' => '_blank' ], icon: 'fa-solid fa-key' ) );
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'members_merge' ) )
		{
			$accountActions->add( new Link( Url::internal( "app=core&module=members&controller=members&do=merge&id={$this->member->member_id}" ), 'merge_with_another_account', dataAttributes: [ 'data-ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'merge' ) ], opensDialog: true, icon: 'fa-solid fa-level-up' ) );
		}
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete' ) and ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_delete_admin' ) or !$this->member->isAdmin() ) and $this->member->member_id != Member::loggedIn()->member_id )
		{
			$accountActions->add( new Link( Url::internal( "app=core&module=members&controller=members&do=delete&id={$this->member->member_id}" ), 'delete', dataAttributes: [ 'data-ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'delete' ) ], opensDialog: true, icon: 'fa-solid fa-times-circle' ) );
		}
		
		if ( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_export_pi' ) )
		{
			$accountActions->add( new Link( Url::internal( "app=core&module=members&controller=members&do=exportPersonalInfo&id={$this->member->member_id}" ), 'member_export_pi_title', dataAttributes: [ 'data-ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'member_export_pi_title' ) ], opensDialog: true, icon: 'fa-solid fa-download' ) );
		}

		/* Data Layer PII Option */
		if (
			DataLayer::enabled() AND
			Settings::i()->core_datalayer_include_pii AND
			Settings::i()->core_datalayer_member_pii_choice AND
			( ( $this->member->isAdmin() AND Member::loggedIn()->hasAcpRestriction('core', 'members', 'member_edit_admin' ) ) OR Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
		)
		{
			$enabled = !$this->member->members_bitoptions['datalayer_pii_optout'];
			$accountActions->add( new Link( Url::internal( "app=core&module=members&controller=members&do=toggleDataLayerPii&id={$this->member->member_id}" ), ( $enabled ? 'datalayer_omit_member_pii' : 'datalayer_collect_member_pii' ), icon: 'fa-solid fa-id-card' ) );
		}
		
		if( Member::loggedIn()->hasAcpRestriction( 'core', 'members', 'member_edit' ) )
		{
			$accountActions->add( new Link( $this->member->acpUrl()->setQueryString( 'do', 'edit' ), 'edit_member_account', dataAttributes: [ 'data-ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'edit_member_account' ) ], opensDialog: true, icon: 'fa-solid fa-sliders' ) );
		}
		
		if( $canChangePassword )
		{
			$accountActions->add( new Link( $this->member->acpUrl()->setQueryString( 'do', 'password' ), ( $hasPassword ? 'edit_password' : 'set_password' ), dataAttributes: [ 'data-ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'edit_password' ) ], opensDialog: true, icon: 'fa-solid fa-lock' ) );
		}

		/* Extensions */
		foreach( Application::allExtensions( 'core', 'UserMenu' ) as $ext )
		{
			foreach( $ext->acpAccountActionsMenu( $this->member ) as $element )
			{
				$accountActions->add( $element );
			}
		}

		$activeSubscription = FALSE;
		if ( Application::appIsEnabled('nexus') and Settings::i()->nexus_subs_enabled ) // I know... this should really be a hook... I won't tell if you won't
		{
			$activeSubscription = Subscription::loadByMember( $this->member, true );
		}
		
		return (string) Theme::i()->getTemplate('memberprofile')->basicInformation( $this->member, $activeIntegrations, $accountActions, $activeSubscription );
	}
}