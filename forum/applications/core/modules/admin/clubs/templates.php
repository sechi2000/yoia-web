<?php
/**
 * @brief		templates
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		09 Jul 2025
 */

namespace IPS\core\modules\admin\clubs;

use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * templates
 */
class templates extends Controller
{
    public static bool $csrfProtected = true;

	/**
	 * Node Class
	 */
	protected string $nodeClass = 'IPS\Member\Club\Template';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'clubs_settings_manage' );
		parent::execute();

		Output::i()->breadcrumb[] = [ Url::internal( "app=core&module=clubs&controller=settings" ), Member::loggedIn()->language()->addToStack( 'menu__core_clubs_settings') ];
		Output::i()->breadcrumb[] = [ Url::internal( "app=core&module=clubs&controller=templates" ), Member::loggedIn()->language()->addToStack( 'menu__core_clubs_templates' ) ];
	}

	/**
	 * Redirect after save
	 *
	 * @param Model|null $old			A clone of the node as it was before or NULL if this is a creation
	 * @param Model $new			The node now
	 * @param bool|string $lastUsedTab	The tab last used in the form
	 * @return	void
	 */
	protected function _afterSave( ?Model $old, Model $new, bool|string $lastUsedTab = FALSE ): void
	{
		if( $old === null )
		{
			Output::i()->redirect( Url::internal( "app=core&module=clubs&controller=templates&do=form&id=" . $new->id ) );
		}

		parent::_afterSave( $old, $new, $lastUsedTab );
	}
}