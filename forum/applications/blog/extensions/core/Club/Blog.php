<?php
/**
 * @brief		Club plugin
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blogs
 * @since		08 Jul 2025
 */

namespace IPS\blog\extensions\core\Club;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\blog\Blog as BlogClass;
use IPS\Content\ContentMenuLink;
use IPS\Helpers\Menu\MenuItem;
use IPS\Member\Club;
use IPS\Extensions\ClubAbstract;
use IPS\Member\Club\Page as ClubPage;
use IPS\Node\Model;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Club Menu plugin
 */
class Blog extends ClubAbstract
{
	/**
	 * Return an array of menu elements to add to the club menu
	 *
	 * @param Club $club
	 * @param Model|ClubPage|null $container
	 * @return array<string,MenuItem>
	 */
	public function menu( Club $club, Model|ClubPage|null $container = null ) : array
	{
		if( $container instanceof BlogClass and $container->canEdit() )
		{
			$categoryLink = new ContentMenuLink( $container->url()->setQueryString( 'do', 'manageCategories' ), 'blog_manage_entry_categories' );
			$categoryLink->opensDialog( 'blog_manage_entry_categories' );
			return [ $categoryLink ];
		}
		return [];
	}
}