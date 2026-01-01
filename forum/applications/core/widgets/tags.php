<?php
/**
 * @brief		tags Widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
{subpackage}
 * @since		05 Aug 2024
 */

namespace IPS\core\widgets;

use IPS\Content\Comment;
use IPS\Content\Search\ContentFilter;
use IPS\Content\Search\Query;
use IPS\Content\Search\Result\Content as SearchResultContent;
use IPS\Content\Tag;
use IPS\Db;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Member;
use IPS\Widget\Customizable;
use IPS\Widget\PermissionCache;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * tags Widget
 */
class tags extends PermissionCache implements Customizable
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'tags';
	
	/**
	 * @brief	App
	 */
	public string $app = 'core';
	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|Form	$form	Form object
	 * @return	Form
	 */
	public function configuration( Form &$form=null ): Form
	{
		$form = parent::configuration( $form );

		$form->add( new Text( 'tag_block_title', $this->configuration['tag_block_title'] ?? Member::loggedIn()->language()->get( 'block_tags' ), true ) );

		$tags = iterator_to_array(
			Db::i()->select( 'tag_id, tag_text', 'core_tags_data', null, 'tag_text' )
				->setKeyField( 'tag_id' )
				->setValueField( 'tag_text' )
		);
		$form->add( new Select( 'tag_block_tags', $this->configuration['tag_block_tags'] ?? null, true, [
			'options' => $tags,
			'multiple' => true,
			'noDefault' => true
		] ) );

		$form->add( new Number( 'tag_block_limit', $this->configuration['tag_block_limit'] ?? 5, true ) );

 		return $form;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */
	public function render(): string
	{
		$data = [];
		$limit = $this->configuration['tag_block_limit'] ?? 5;

        $filters = [];
        foreach( Tag::getTaggableContentTypes() as $type )
        {
            $filters[] = ContentFilter::init( $type );
        }

		foreach( ( $this->configuration['tag_block_tags'] ?? [] ) as $tag )
		{
			try
			{
				$tag = Tag::load( $tag );
			}
			catch( OutOfRangeException )
			{
				continue;
			}

            $query = Query::init();
            $query->setLimit( $limit );
            $query->filterByContent( $filters );
            $query->setOrder( Query::ORDER_NEWEST_CREATED );

            $items = [];
            foreach( $query->search( null, [ $tag->text ], Query::TAGS_MATCH_ITEMS_ONLY ) as $result )
            {
                /* @var SearchResultContent $result */
                $result = $result->asArray();

                try
                {
                    $itemClass = $result['indexData']['index_class'];
                    $id = $result['indexData']['index_object_id'];
                    if( is_subclass_of( $itemClass, Comment::class ) )
                    {
                        $itemClass = $itemClass::$itemClass;
                        $id = $result['indexData']['index_item_id'];
                    }
                    $items[] = $itemClass::load( $id );
                }
                catch( OutOfRangeException ){}
            }

			if( count( $items ) )
			{
				$data[ $tag->text ] = $items;
			}
		}

		if( !count( $data ) )
		{
			return "";
		}

		$title = $this->configuration['tag_block_title'] ?? Member::loggedIn()->language()->get( 'block_tags' );
		return $this->output( $title, $data );
	}
}