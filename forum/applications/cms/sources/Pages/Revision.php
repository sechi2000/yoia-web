<?php

/**
 * @brief        Revision
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        6/25/2025
 */

namespace IPS\cms\Pages;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Lang;
use IPS\Member;
use IPS\Patterns\ActiveRecord;
use IPS\Widget\Area;
use UnderflowException;
use function count;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Revision extends ActiveRecord
{
	/**
	 * @brief       Database Table
	 */
	public static ?string $databaseTable = 'cms_page_revisions';

	/**
	 * @brief       Database Prefix
	 */
	public static string $databasePrefix = 'revision_';

	/**
	 * @brief       Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @return Page
	 */
	public function get_page() : Page
	{
		return Page::load( $this->page_id );
	}

	/**
	 * @return array
	 */
	public function get_data() : array
	{
		return isset( $this->_data['data'] ) ? json_decode( $this->_data['data'], true ) : [];
	}

	/**
	 * @param mixed $val
	 * @return void
	 */
	public function set_data( mixed $val ) : void
	{
		$this->_data['data'] = ( is_array( $val ) and count( $val ) ) ? json_encode( $val ) : null;
	}

	/**
	 * @return Member
	 */
	public function author() : Member
	{
		return Member::load( $this->member_id );
	}

	/**
	 * Store a version of the page history
	 *
	 * @param Page $page
	 * @param bool $manualSave Whether this is being called manually (e.g. from finish editing)
	 * @return void
	 */
	public static function store( Page $page, bool $manualSave=false ) : void
	{
		$data = [
			'settings' => [
				'name' => Member::loggedIn()->language()->get( Page::$titleLangPrefix . $page->id ),
				'js_css_ids' => $page->js_css_ids,
				'wrapper_template' => $page->wrapper_template,
				'theme' => $page->theme,
				'template' => $page->template,
				'ipb_wrapper' => $page->ipb_wrapper,
				'default' => $page->default
			],
			'areas' => []
		];

		if( $page->type == 'html' )
		{
			$data['settings']['content'] = $page->content;
		}

		/* Now handle all the areas */
		foreach( $page->getAreasFromDatabase() as $area )
		{
			$data['areas'][ $area->id ] = $area->toArray();
		}

		/* Get the latest revision; if nothing has changed, don't store it */
		if( $previous = static::previousVersion( $page ) )
		{
			if( ! $manualSave and ( $previous->data === $data ) )
			{
				return;
			}
		}

		$obj = new static;
		$obj->page_id = $page->id;
		$obj->data = $data;
		$obj->manual_save = (int) $manualSave;
		$obj->save();
	}

	/**
	 * Make this the latest version.
	 * If no version is specified, just use the latest
	 *
	 * @return void
	 */
	public function revert() : void
	{
		/* @var Page $page */
		$page = $this->page;

		/* First, store the current page as is */
		static::store( $page );

		/* Now loop through the page configuration and set all properties */
		foreach( $this->data['settings'] as $k => $v )
		{
			$page->$k = $v;
			if( $k == 'name' )
			{
				Lang::saveCustom( 'cms', Page::$titleLangPrefix . $page->id, $v );
			}
		}
		$page->save();

		/* Load the current areas, in case we need to remove one */
		$currentAreas = $page->getAreasFromDatabase();

		/* Now the areas */
		$newAreas = [];
		foreach( $this->data['areas'] as $id => $tree )
		{
			$newAreas[] = $id;
			$area = new Area( $tree, $id );
			$page->saveArea( $area, false );
		}

		/* Remove any areas that we no longer need */
		foreach( $currentAreas as $area )
		{
			if( !in_array( $area->id, $newAreas ) )
			{
				Db::i()->delete( 'cms_page_widget_areas', [ 'area_area=? and area_page_id=?', $area->id, $page->id ] );
			}
		}
	}

	/**
	 * Get the previously saved version
	 *
	 * @param Page $page
	 * @return static|null
	 */
	public static function previousVersion( Page $page ) : static|null
	{
		try
		{
			$row = Db::i()->select( '*', static::$databaseTable, [ 'revision_page_id=?', $page->id ], 'revision_date desc', [ 0, 1 ] )->first();
			return static::constructFromData( $row );
		}
		catch( UnderflowException ){}

		return null;
	}

	/**
	 * Save Changed Columns
	 *
	 * @return    void
	 */
	public function save() : void
	{
		if( $this->_new )
		{
			$this->member_id = Member::loggedIn()->member_id;
			$this->date = time();
		}

		parent::save();
	}
}