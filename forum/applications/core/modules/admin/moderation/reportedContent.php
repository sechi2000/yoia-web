<?php
/**
 * @brief		reportedContent
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		07 Dec 2017
 */

namespace IPS\core\modules\admin\moderation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Output;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * reportedContent
 */
class _reportedContent extends \IPS\Node\Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\core\Reports\Rules';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=moderation&controller=report" ), 'menu__core_moderation_report' );
		Output::i()->breadcrumb[] = array( NULL, 'manage_automatic_rules' );

		\IPS\Output::i()->output = \IPS\Theme::i()->getTemplate('forms')->blurb( 'automaticmoderation_blurb' );
		
		if ( ! \IPS\Settings::i()->automoderation_enabled )
		{
			\IPS\Output::i()->output .= \IPS\Theme::i()->getTemplate('forms')->blurb( 'automaticmoderation_disabled_blurb' );
		}
		
		\IPS\Dispatcher::i()->checkAcpPermission( 'reportedContent_manage' );
		parent::execute();
	}
}