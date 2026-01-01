<?php
/**
 * @brief		tags
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		29 Mar 2024
 */

namespace IPS\core\modules\admin\discovery;

use InvalidArgumentException;
use IPS\Content\Tag;
use IPS\courses\Module as ModuleClass;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use function defined;
use function in_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * tags
 */
class tags extends Controller
{
	/**
	 * @var bool
	 */
    public static bool $csrfProtected = true;

	/**
	 * Node Class
	 */
	protected string $nodeClass = Tag::class;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'tags_manage' );
		parent::execute();
	}

	/**
	 * @return int|null
	 */
	public function _getRootsPerPage(): ?int
	{
		return 100;
	}

	/**
	 * Get Root Rows
	 *
	 * @return	array
	 */
	public function _getRoots(): array
	{
		$args = func_get_args(); // We actually send [ start, limit ] to this method
		$nodeClass = $this->nodeClass;
		$rows = array();
		$start = $args[0][0] ?? 0;
		$limit = $args[0][1] ?? $this->_getRootsPerPage();

		foreach( new ActiveRecordIterator(
			Db::i()->select( '*', Tag::$databaseTable, null, 'tag_recommended desc, tag_text', [ $start, $limit ] ),
			Tag::class
				 ) as $node )
		{
			$rows[ $node->_id ] = $this->_getRow($node);
		}

		return $rows;
	}
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->sidebar['actions']['settings'] = [
			'icon' => 'cogs',
			'link' => $this->url->setQueryString( 'do', 'settings' ),
			'title' => 'tag_settings'
		];

		Output::i()->sidebar['actions']['tools'] = [
			'icon' => 'cog',
			'title' => 'tag_tools',
			'menu' => [
				'merge' => [
					'icon' => 'level-up',
					'title' => 'merge_tag_tool',
					'link' => $this->url->setQueryString( 'do', 'merge' ),
					'data' => [ 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'merge_tag_tool') ]
				],
				'delete' => [
					'icon' => 'trash',
					'title' => 'tags_mass_delete',
					'link' => $this->url->setQueryString( 'do', 'massDelete' ),
					'data' => [ 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack( 'tags_mass_delete') ]
				]
			]
		];

		Output::i()->jsFiles = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_core.js', 'core', 'admin' ) );

		parent::manage();
	}

	/**
	 * Get form
	 *
	 * @param	Form	$form	The form as returned by _addEditForm()
	 * @return	string
	 */
	protected function _showForm( Form $form ) : string
	{
		Output::i()->breadcrumb[] = [ $this->url, Tag::$nodeTitle ];
		if( isset( Request::i()->id ) )
		{
			try
			{
				Output::i()->title = Tag::load( Request::i()->id )->text;
				Output::i()->breadcrumb[] = [ null, Output::i()->title ];
			}
			catch( OutOfRangeException ){}
		}
		else
		{
			Output::i()->breadcrumb[] = [ null, 'add' ];
		}

		return parent::_showForm( $form );
	}

	/**
	 * Allow overloading to change how the title is displayed in the tree
	 *
	 * @param	$node    Model    Node
	 * @return string
	 */
	protected static function nodeTitle( Model $node ): string
	{
		return $node->text;
	}

	/**
	 * Search
	 *
	 * @return	void
	 */
	protected function search() : void
	{
		$rows = array();

		/* @var Model $nodeClass */
		$nodeClass = $this->nodeClass;
		$results = $nodeClass::search( 'tag_text', Request::i()->input, 'tag_text' );

		/* Convert to HTML */
		foreach ( $results as $result )
		{
			$rows[ $result->_id ] = $this->_getRow( $result, FALSE, TRUE );
		}

		Output::i()->output = Theme::i()->getTemplate( 'trees', 'core' )->rows( $rows, '' );
	}

	/**
	 * @return void
	 */
	protected function settings() : void
	{
		$form = new Form();

		$form->add( new YesNo( 'tags_enabled', Settings::i()->tags_enabled, FALSE, array( 'togglesOn' => array( 'tags_can_prefix', 'tags_force_lower', 'tags_min', 'tags_max' ) ) ) );
		$form->add( new YesNo( 'tags_can_prefix', Settings::i()->tags_can_prefix, FALSE, array(), NULL, NULL, NULL, 'tags_can_prefix' ) );
		$form->add( new YesNo( 'tags_force_lower', Settings::i()->tags_force_lower, FALSE, array(), NULL, NULL, NULL, 'tags_force_lower' ) );
		$form->add( new Number( 'tags_min', Settings::i()->tags_min, FALSE, array( 'unlimited' => 0, 'unlimitedLang' => 'tags_min_none', 'unlimitedToggles' => array( 'tags_min_req' ), 'unlimitedToggleOn' => FALSE ), NULL, NULL, NULL, 'tags_min' ) );
		$form->add( new YesNo( 'tags_min_req', Settings::i()->tags_min_req, FALSE, array(), NULL, NULL, NULL, 'tags_min_req' ) );
		$form->add( new Number( 'tags_max', Settings::i()->tags_max, FALSE, array( 'unlimited' => 0 ), NULL, NULL, NULL, 'tags_max' ) );

		if ( $values = $form->values() )
		{
			$form->saveAsSettings( $values );

			Session::i()->log( 'acplogs__tag_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=discovery&controller=tags' ), 'saved' );
		}

		Output::i()->title = Member::loggedIn()->language()->addToStack( 'tag_settings' );
		Output::i()->breadcrumb[] = [ $this->url, Member::loggedIn()->language()->addToStack( 'menu__core_discovery_tags' ) ];
		Output::i()->breadcrumb[] = [ null, Member::loggedIn()->language()->addToStack( 'tag_settings' ) ];
		Output::i()->output = (string) $form;
	}

	/**
	 * @return void
	 */
	protected function merge() : void
	{
		/* Skip any tags that are being merged */
		$source = [];
		foreach( new ActiveRecordIterator(
			Db::i()->select( '*', 'core_tags_data', null, 'tag_text' ),
			Tag::class
				 ) as $tag )
		{
			if( !$tag->mergeInProgress() )
			{
				$source[] = $tag->text;
			}
		}

		$form = new Form( 'form', 'continue' );
		$form->add( new Text( 'tag_merge_from', null, true, [
			'autocomplete' => array(
				'unique' => TRUE,
				'source' => $source,
				'resultItemTemplate' => 'core.autocomplete.tagsResultItem',
				'freeChoice' => false,
				'minimized' => false,
				'alphabetical' => true,
				'addTokenText' => Member::loggedIn()->language()->get( 'tags_optional' ),
			)
		], function( $val ){
            if( !empty( $val ) )
            {
                foreach( $val as $_v )
                {
                    if( $_v == Request::i()->tag_merge_to )
                    {
                        throw new InvalidArgumentException( 'err__bad_tag_merge' );
                    }
                }
            }
        } ) );

		$form->add( new Text( 'tag_merge_to', null, true, [
			'autocomplete' => array(
				'unique' => TRUE,
				'source' => $source,
				'resultItemTemplate' => 'core.autocomplete.tagsResultItem',
				'freeChoice' => false,
				'minimized' => false,
				'alphabetical' => true,
				'maxItems' => 1,
				'addTokenText' => Member::loggedIn()->language()->get( 'tags_optional' ),
			)
		] ) );

		if( $values = $form->values() )
		{
			foreach( $values['tag_merge_from'] as $tag )
			{
				$tag = Tag::load( $tag, 'tag_text' );

				/* Yes, this is technically used for totals, but we're wiping this anyway,
				so just put a placeholder here */
				$data = $tag->totals;
				$data['merge'] = true;
				$tag->totals = $data;
				$tag->save();

                /* Queue the background task to update the tags */
				Task::queue( 'core', 'UpdateTaggedItems', [ 'tag' => $tag->text, 'new' => $values['tag_merge_to'], 'merge' => true ], 5, [ 'tag' ] );
			}

            Session::i()->log( 'acplogs__tag_merge', [ implode( ", ", $values['tag_merge_from'] ) => false, $values['tag_merge_to'] => false ] );

			Output::i()->redirect( $this->url, Member::loggedIn()->language()->addToStack( 'tag_merge_requested' ) );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * Bulk delete tool
	 *
	 * @return void
	 */
	protected function massDelete() : void
	{
		/* Skip any tags that are being merged */
		$source = [];
		foreach( new ActiveRecordIterator(
					 Db::i()->select( '*', 'core_tags_data', null, 'tag_text' ),
					 Tag::class
				 ) as $tag )
		{
			if( !$tag->mergeInProgress() )
			{
				$source[] = $tag->text;
			}
		}

		$form = new Form( 'form', 'submit' );
		$form->attributes['data-controller'] = 'core.admin.core.tags';

		$form->add( new Text( 'tag_to_delete', null, true, [
			'autocomplete' => array(
				'unique' => TRUE,
				'source' => $source,
				'resultItemTemplate' => 'core.autocomplete.tagsResultItem',
				'freeChoice' => false,
				'minimized' => false,
				'alphabetical' => true,
				'addTokenText' => Member::loggedIn()->language()->get( 'tags_optional' ),
			)
		] ) );

		if( $values = $form->values() )
		{
			foreach( $values['tag_to_delete'] as $tag )
			{
				Tag::load( $tag, 'tag_text' )->delete();
			}

            Session::i()->log( 'acplogs__tag_bulk_delete', [ implode( ", ", $values['tag_to_delete'] ) => false ] );

			Output::i()->redirect( $this->url );
		}

		Output::i()->output = (string) $form;
	}

	/**
	 * Mark/unmark a tag as recommended
	 *
	 * @return void
	 */
	protected function recommend() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$tag = Tag::load( Request::i()->id );
			$tag->recommended = !$tag->recommended;
			$tag->save();
		}
		catch( OutOfRangeException ){}

		Output::i()->redirect( Url::internal( "app=core&module=discovery&controller=tags" ) );
	}
}