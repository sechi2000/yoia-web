<?php
/**
 * @brief		reportedContentTypes
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		14 Dec 2017
 */

namespace IPS\core\modules\admin\moderation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Url;
use IPS\Output;
use IPS\Request;
use IPS\Theme;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * reportedContentTypes
 */
class _reportedContentTypes extends \IPS\Node\Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 */
	protected $nodeClass = '\IPS\core\Reports\Types';
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		Output::i()->breadcrumb[] = array( Url::internal( "app=core&module=moderation&controller=report" ), 'menu__core_moderation_report' );
		Output::i()->breadcrumb[] = array( NULL, 'reportedContent_types' );

		if ( ! Request::i()->do )
		{
			Output::i()->output = Theme::i()->getTemplate( 'forms', 'core' )->blurb( 'moderation_reporttypes_desc' );
		}

		\IPS\Dispatcher::i()->checkAcpPermission( 'reportedContentTypes_manage' );
		parent::execute();
	}
}