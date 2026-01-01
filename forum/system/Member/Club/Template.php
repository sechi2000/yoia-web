<?php

/**
 * @brief        Template
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        7/9/2025
 */

namespace IPS\Member\Club;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Select;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\YesNo;
use IPS\Lang;
use IPS\Member\Club;
use IPS\Node\Model;
use IPS\Request;
use IPS\Theme;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class Template extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_clubs_templates';

	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'template_';

	/**
	 * @brief	language string
	 */
	public static string $nodeTitle = 'clubs_templates';

	/**
	 * @brief	[Node] Order Database Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief	Show form in a modal?
	 */
	public static bool $modalForms = true;

	/**
	 * [Node] Get Title
	 *
	 * @return	string
	 */
	protected function get__title(): string
	{
		return $this->name ?? '';
	}

	/**
	 * @return array
	 */
	public function get_node_data() : array
	{
		return isset( $this->_data['node_data'] ) ? json_decode( $this->_data['node_data'], TRUE ) : [];
	}

	/**
	 * @param array|null $val
	 * @return void
	 */
	public function set_node_data( ?array $val ) : void
	{
		$this->_data['node_data'] = is_array( $val ) ? json_encode( $val ) : null;
	}

	/**
	 * Get the title for a node using the specified language object
	 * This is commonly used where we cannot use the logged in member's language, such as sending emails
	 *
	 * @param Lang $language	Language object to fetch the title with
	 * @param array $options	What options to use for language parsing
	 * @return	string
	 */
	public function getTitleForLanguage( Lang $language, array $options=array() ): string
	{
		return $this->_title;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		parent::form( $form );

		$form->add( new Text( 'template_template_name', $this->name, true ) );

		if( $this->_new )
		{
			/* Override the submit button language string */
			$form->actionButtons = [
				Theme::i()->getTemplate( 'forms', 'core', 'global' )->button( 'continue', 'submit', null, 'ipsButton ipsButton--primary', array( 'tabindex' => '2', 'accesskey' => 's' ) )
			];

			$nodeOptions = array();
			foreach( Club::availableNodeTypes() as $nodeType )
			{
				$nodeOptions[ $nodeType ] = $nodeType::clubFrontTitle();
			}

			$form->add( new Select( 'template_node_class', NULL, TRUE, array( 'options' => $nodeOptions ) ) );
		}
		else
		{
			$this->nodeForm( $form );
		}
	}

	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		if( $this->id )
		{
			$nodeData = [];
			foreach( $values as $k => $v )
			{
				if( $k != 'template_template_name' )
				{
					$nodeData[ $k ] = $v;
					unset( $values[ $k ] );
				}
			}

			$values['node_data'] = $nodeData;
		}

		$values['template_name'] = $values['template_template_name'];
		unset( $values['template_template_name'] );

		return parent::formatFormValues( $values );
	}

	/**
	 * Get node form
	 *
	 * @param Form $form
	 * @return void $form    Form
	 */
	public function nodeForm( Form &$form ) : void
	{
		$node = new ( $this->node_class );
		$node->clubForm( $form, new Club );
		$submitted = $form->id . "_submitted";

		foreach( $form->elements as $tab => $elements )
		{
			foreach( $elements as $key => $element )
			{
				if( $element instanceof Editor )
				{
					unset( $form->elements[ $tab ][ $key ] );
				}
				elseif( isset( $this->node_data[ $key ] ) and !isset( Request::i()->$submitted ) )
				{
					$form->elements[ $tab ][ $key ]->value = $this->node_data[ $key ];
				}
			}
		}

		$form->add( new YesNo( 'clubtemplates_leader_edit', $this->node_data['clubtemplates_leader_edit'] ?? null, TRUE ) );
	}

	/**
	 * Search
	 *
	 * @param string $column	Column to search
	 * @param string $query	Search query
	 * @param string|null $order	Column to order by
	 * @param mixed $where	Where clause
	 * @return	array
	 */
	public static function search( string $column, string $query, ?string $order=NULL, mixed $where=array() ): array
	{
		if( $column === '_title' )
		{
			$column = 'template_name';
			$order = 'template_name';
		}

		return parent::search( $column, $query, $order, $where );
	}
}