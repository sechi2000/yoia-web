<?php
/**
 * @brief		Field Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		3 Oct 2013
 */

namespace IPS\downloads;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Lang;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Field Node
 */
class _Field extends \IPS\CustomField implements \IPS\Node\Permissions
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static $multitons;
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static $databaseTable = 'downloads_cfields';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static $databasePrefix = 'cf_';
		
	/**
	 * @brief	[Node] Order Database Column
	 */
	public static $databaseColumnOrder = 'position';

	/**
	 * @brief	[CustomField] Column Map
	 */
	public static $databaseColumnMap = array(
		'not_null'	=> 'not_null'
	);
	
	/**
	 * @brief	[Node] Node Title
	 */
	public static $nodeTitle = 'ccfields';
	
	/**
	 * @brief	[CustomField] Title/Description lang prefix
	 */
	protected static $langKey = 'downloads_field';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static $titleLangPrefix = 'downloads_field_';
	
	/**
	 * @brief	[CustomField] Content Table
	 */
	public static $contentDatabaseTable = 'downloads_ccontent';
	
	/**
	 * @brief	[CustomField] Set to TRUE if uploads fields are capable of holding the submitted content for moderation
	 */
	public static $uploadsCanBeModerated = TRUE;

	/**
	 * @brief	The map of permission columns
	 */
	public static $permissionMap = array(
		'view' 				=> 'view',
		'edit'				=> 2,
		'add'               => 3
	);

	/**
	 * @brief	[Node] App for permission index
	 */
	public static $permApp = 'downloads';

	/**
	 * @brief	[Node] Type for permission index
	 */
	public static $permType = 'fields';

	/**
	 * @brief	[Node] Prefix string that is automatically prepended to permission matrix language strings
	 */
	public static $permissionLangPrefix = 'perm_cfield_';
	
	/**
	 * @brief	[Node] ACP Restrictions
	 */
	protected static $restrictions = array(
		'app'		=> 'downloads',
		'module'	=> 'downloads',
		'prefix'	=> 'fields_',
	);

	/**
	 * @brief	[CustomField] Editor Options
	 */
	public static $editorOptions = array( 'app' => 'downloads', 'key' => 'Downloads' );
	
	/**
	 * @brief	[CustomField] FileStorage Extension for Upload fields
	 */
	public static $uploadStorageExtension = 'downloads_FileField';

	/**
	 * @brief   [CustomField] An array of the 'keys' of the Field types toggles that shouldn't be an option. The main use is for excluding polls
	 */
	public static $disabledFieldTypes = array( 'Poll' );


	/**
	 * Get topic format
	 *
	 * @return	void
	 */
	public function get_topic_format()
	{
		return $this->format;
	}

	/**
	 *
	 * [Node] Does the currently logged in user have permission to edit permissions for this node?
	 *
	 * @return	bool
	 */
	public function canManagePermissions()
	{
		return true;
	}

	/**
	 * [Node] Add/Edit Form
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function form( &$form )
	{
		parent::form( $form );

		
		unset( $form->elements['']['pf_search_type'] );
		unset( $form->elements['']['pf_search_type_on_off'] );
		\IPS\Member::loggedIn()->language()->words['field_displayoptions'] = \IPS\Member::loggedIn()->language()->addToStack('pfield_displayoptions');

		$form->add( new \IPS\Helpers\Form\Radio( 'downloads_field_location', $this->id ? $this->display_location : 'below', FALSE, array( 'options' => array( 'sidebar' => 'idm_cfield_sidebar', 'below' => 'idm_cfield_below', 'tab' => 'idm_cfield_tab' ) ), NULL, NULL, NULL, 'idm_field_location' ) );

		if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->idm_nexus_on )
		{
			$form->add( new \IPS\Helpers\Form\YesNo( 'downloads_field_paid', $this->id ? $this->paid_field : FALSE, FALSE, array( 'togglesOff' => array( 'idm_cf_topic', 'idm_pf_format', 'form_' . ( $this->id ?? 'new' ) . '_header_category_forums_integration' ) ) ) );
		}

		if ( \IPS\Application::appIsEnabled( 'forums' ) )
		{
			$form->addHeader('category_forums_integration');
			$form->add( new \IPS\Helpers\Form\YesNo( 'cf_topic', $this->topic, FALSE, array(), NULL, NULL, NULL, 'idm_cf_topic' ) );
			$form->add( new \IPS\Helpers\Form\TextArea( 'pf_format', $this->id ? $this->topic_format : '', FALSE, array(), NULL, NULL, NULL, 'idm_pf_format' ) );

			\IPS\Member::loggedIn()->language()->words['pf_format_desc'] = \IPS\Member::loggedIn()->language()->addToStack('cf_format_desc');
		}
	}
	
	/**
	 * [Node] Format form values from add/edit form for save
	 *
	 * @param	array	$values	Values from the form
	 * @return	array
	 */
	public function formatFormValues( $values )
	{
		if ( \IPS\Application::appIsEnabled( 'forums' ) AND isset( $values['cf_topic'] ) )
		{
			/* Forcibly disable include in topic option if it is a paid field */
			$values['topic'] = ( isset( $values['downloads_field_paid'] ) AND $values['downloads_field_paid'] ) ? 0 : $values['cf_topic'];
			unset( $values['cf_topic'] );
		}

		$values['allow_attachments']	= $values['pf_allow_attachments'];
		unset( $values['pf_search_type'] );
		unset( $values['pf_allow_attachments'] );

		$values['display_location'] = $values['downloads_field_location'];
		$values['paid_field'] = ( isset( $values['downloads_field_paid'] ) ) ? $values['downloads_field_paid'] : 0;
		unset( $values['downloads_field_location'], $values['downloads_field_paid'] );

		return parent::formatFormValues( $values );
	}

	/**
	 * Get output for API
	 *
	 * @param	\IPS\Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @param	array				$fieldValues		Current values for a specific file
	 * @return	array
	 * @apiresponse	int			id								ID number
	 * @apiresponse	string		name							Name
	 * @apiresponse	string		description						Description
	 * @apiresponse	string		value							Raw field value from the database
	 * @apiresponse string|int|array|boolean	displayValue	Parsed Field value with all field data
	 */
	public function apiOutput( \IPS\Member $authorizedMember = NULL, $fieldValues=array() )
	{
		$lang = $authorizedMember ? $authorizedMember->language() : Lang::load( Lang::defaultLanguage() );
		return [
			'id' => $this->id,
			'name' => $lang->addToStack( 'downloads_field_' . $this->id ),
			'description' => $lang->addToStack( 'downloads_field_' . $this->id . '_desc' ),
			'value' => $fieldValues[ 'field_' . $this->id ] ?? null,
			'displayValue' => $this->apiValue( $fieldValues[ 'field_' . $this->id ] )
		];
	}
}