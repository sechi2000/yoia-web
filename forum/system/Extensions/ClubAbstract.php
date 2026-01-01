<?php

/**
 * @brief        ClubAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        08/04/2024
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Badge;
use IPS\Helpers\Menu\Buttons;
use IPS\Helpers\Menu\MenuItem;
use IPS\Member;
use IPS\Member\Club;
use IPS\Member\Club\Page as ClubPage;
use IPS\Node\Model;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class ClubAbstract
{
	/**
	 * Tabs
	 *
	 * @param 	Club $club		The club
	 * @param	Model|ClubPage|null		$container	Container
	 * @return	array
	 */
	public function tabs( Club $club, Model|ClubPage|null $container = NULL ): array
	{
		return [];
	}

	/**
	 * Return an array of menu elements to add to the club menu
	 *
	 * @param Club $club
	 * @param Model|ClubPage|null $container
	 * @return array<string,MenuItem>
	 */
	public function menu( Club $club, Model|ClubPage|null $container = null ) : array
	{
		return [];
	}

	/**
	 * Return an array of menu elements to add to the buttons list
	 * in the club header. The menu will be rendered as individual buttons and not
	 * as a dropdown menu.
	 *
	 * @see Buttons::button()
	 * @param Club $club
	 * @return array
	 */
	public function buttons( Club $club ) : array
	{
		return [];
	}

	/**
	 * Return an array of badges to show on the club card and header
	 *
	 * @param Club $club
	 * @return array<Badge>
	 */
	public function badges( Club $club ) : array
	{
		return [];
	}

	/***
	 * Return an array of form fields that will be added to the club creation form
	 *
	 * @param Club|null $club
	 * @return array
	 */
	public function formElements( ?Club $club ) : array
	{
		return [];
	}

	/**
	 * Handle any custom fields that were added in the formElements method
	 * This is executed after the club has been saved
	 *
	 * @param Club $club
	 * @param array $values
	 * @param bool $new
	 * @return void
	 */
	public function saveForm( Club $club, array $values, bool $new=false ) : void
	{

	}

	/**
	 * Custom filters for retrieving a list of clubs that are visible to a member
	 *
	 * @param Member|null $member
	 * @return array
	 */
	public function clubsWhere( ?Member $member ) : array
	{
		return [];
	}
}