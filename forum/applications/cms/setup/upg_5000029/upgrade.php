<?php
/**
 * @brief		5.0.0 Beta 3 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		24 Oct 2024
 */

namespace IPS\cms\setup\upg_5000029;

use IPS\cms\Pages\Page;
use IPS\Db;
use IPS\Widget\Area;
use function array_keys;
use function array_merge;
use function defined;
use function in_array;
use function json_decode;
use function json_encode;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 5.0.0 Beta 3 Upgrade Code
 */
class Upgrade
{
	/**
	 * Fix an issue where Database widgets dropped into a header/sidebar/footer area in v4 cause issues in v5 as they must be in col1
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		$areas = [];
		foreach( Db::i()->select( '*', 'cms_page_widget_areas', [], 'area_page_id ASC' ) as $row )
		{
			$areas[ $row['area_page_id'] ][ $row['area_area'] ] = $row;
		}

        /* Now check for a Database widget in the wrong area.
        We have to do this by database rows because if we try to build an area
        with an invalid widget (e.g. database in a protected area), it will just ignore the widget */
		foreach( $areas as $pageId => $areaRows )
		{
			try
			{
				$page = Page::load( $pageId );

				foreach( [ 'header', 'footer', 'sidebar' ] as $protectedArea )
				{
					if ( in_array( $protectedArea, array_keys( $areaRows ) ) and $areaRows[ $protectedArea ]['area_widgets'] )
					{
						if( $widgets = json_decode( $areaRows[ $protectedArea ]['area_widgets'], true ) )
						{
							foreach( $widgets as $idx => $widget )
							{
								/* Well, this shouldn't be here */
								if ( $widget['app'] === 'cms' and $widget['key'] == 'Database' )
								{
									/* Let's load the main area for this page */
									$mainArea = $page->getAreasFromDatabase( 'col1' )['col1'] ?? new Area( [], 'col1' );
									$mainArea->addWidget( $widget );
									$page->saveArea( $mainArea, false );
									unset( $widgets[ $idx ] );
									break;
								}
							}

							/* If there are no more widgets here, delete the area entirely */
							if( empty( $widgets ) )
							{
								Db::i()->delete( 'cms_page_widget_areas', [ 'area_area=? and area_page_id=?', $protectedArea, $pageId ] );
							}
						}
					}
				}
			}
			catch( \OutOfRangeException $e )
			{
				/* Page doesn't exist, we can just delete it */
				Db::i()->delete( 'cms_page_widget_areas', [ 'area_page_id=?', $pageId ] );
			}

		}

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}