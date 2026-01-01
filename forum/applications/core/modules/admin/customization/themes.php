<?php
/**
 * @brief		themes
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Apr 2013
 */

namespace IPS\core\modules\admin\customization;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ErrorException;
use Exception;
use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Node\Controller;
use IPS\Node\Model;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use IPS\Theme\Editor\Category;
use IPS\Theme\Editor\Setting;
use IPS\Xml\XMLReader;
use OutOfRangeException;
use UnderflowException;
use UnexpectedValueException;
use XMLWriter;
use function array_keys;
use function array_merge;
use function base64_decode;
use function count;
use function defined;
use function implode;
use function in_array;
use function intval;
use function is_array;
use function is_int;
use function preg_match;
use function str_replace;
use function substr;
use function time;
use function uniqid;
use const IPS\TEMP_DIRECTORY;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * themes
 */
class themes extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;
	
	/**
	 * Node Class
	 *
	 * @var Theme|string
	 */
	protected string $nodeClass = 'IPS\Theme';
	
	/**
	 * @brief	If true, will prevent any item from being moved out of its current parent, only allowing them to be reordered within their current parent
	 */
	protected bool $lockParents = TRUE;
	
	/**
	 * Title can contain HTML?
	 */
	public bool $_titleHtml = TRUE;

	/**
	 * Description can contain HTML?
	 */
	public bool $_descriptionHtml = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'theme_sets_manage' );
		
		parent::execute();
	}
	
	/**
	 * Manage
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		Output::i()->sidebar['actions']['add'] = array(
			'primary'	=> true,
			'icon'		=> 'plus',
			'title'		=> 'add',
			'link'		=> Url::internal( 'app=core&module=customization&controller=themes&do=form' )
		);

		Output::i()->sidebar['actions']['import'] = array(
			'primary'	=> false,
			'icon'		=> 'upload',
			'title'		=> 'upload',
			'link'		=> Url::internal( 'app=core&module=customization&controller=themes&do=upload' ),
			'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('upload') )
		);

		if ( \IPS\IN_DEV )
		{
			Output::i()->sidebar['actions']['rebuildstatic'] = array(
				'primary'	=> false,
				'icon'	=> 'cogs',
				'title'	=> 'theme_set_import_to_static',
				'link'	=> Url::internal( "app=core&module=customization&controller=themes&do=rebuildStatic" )->csrf()
			);
		}

		parent::manage();
		Output::i()->output .= Theme::i()->getTemplate('customization')->designerModeToggle();
	}

	/**
	 * Allow overloading to change how the title is displayed in the tree
	 *
	 * @param    $node    Model    Node
	 * @return string
	 * @throws ErrorException
	 */
	protected static function nodeTitle( Model $node ): string
	{
		return Theme::i()->getTemplate('customization')->themeRowTitle( $node );
	}
	
	/**
	 * Get Root Buttons
	 *
	 * @return	array
	 */
	public function _getRootButtons(): array
	{
		return array();
	}

	/**
	 * Add/Edit Form
	 *
	 * @return void
	 * @throws ErrorException
	 */
	protected function form() : void
	{
		if ( Request::i()->id )
		{
			/* Is this theme currenly being edited? */
			try
			{
				$theme = Theme::load( Request::i()->id );

				if ( $theme->edit_in_progress )
				{
					$memberEditing = Member::load( $theme->edit_in_progress );

					if ( $memberEditing->member_id == Member::loggedIn()->member_id )
					{
						$message = Member::loggedIn()->language()->addToStack( 'theme_editing_you_are_editing' );
					}
					else
					{
						$message = Member::loggedIn()->language()->addToStack( 'theme_editing_they_are_editing', null, [ 'sprintf' => [ $memberEditing->name ] ] );
					}

					Output::i()->output .= Theme::i()->getTemplate('global', 'core', 'admin')->message( Theme::i()->getTemplate('customization')->themeEditingMessage( $theme, $message ), 'info i-margin-bottom_2', null, null );
				}
				else
				{
					Output::i()->sidebar['actions']['editor'] = array(
						'primary'	=> true,
						'icon'		=> 'brush',
						'title'		=> 'theme_editor_open',
						'link'		=> Url::internal( 'app=core&module=customization&controller=themes&do=startEditing&id=' . $theme->id ),
						'target'	=> "blank",
						'tooltip'   => Member::loggedIn()->language()->addToStack( 'theme_editor_open_tooltip' )
					);
				}
			}
			catch ( OutOfRangeException $e )
			{
				Output::i()->error( 'node_error', '2S101/Z', 404, '' );
			}
		}

		parent::form();
	}

	/**
	 * End any theme editing sessions for this theme
	 *
	 * @return void
	 * @throws Exception
	 */
	public function startEditing() : void
	{
		$theme = Theme::load( Request::i()->id );
		$theme->editingStart();

		Output::i()->redirect( Url::internal( 'app=core&module=system&controller=themeeditor', 'front', 'theme_editor' ) );
	}

	/**
	 * End any theme editing sessions for this theme
	 *
	 * @return void
	 */
	public function endEditing() : void
	{
		$theme = Theme::load( Request::i()->id );

		if ( $theme->edit_in_progress )
		{
			try
			{
				$member = Member::load( $theme->edit_in_progress );
				$theme->editingFinish( $member );
				Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes&do=form&id=' . $theme->id ), 'theme_editing_ended' );
			}
			catch( Exception )
			{
				Output::i()->error( 'no_permission', '2S101/Y', 404, '' );
			}
		}
	}

	/**
	 * Revert theme setting
	 *
	 * @return void
	 * @throws Exception
	 */
	public function revertThemeSetting(): void
	{
		Session::i()->csrfCheck();
		
		$theme   = Theme::load( Request::i()->id );
		$value   = NULL;

		try
		{
			$themeSetting = Db::i()->select( 'sc.*, sv.sv_value', array('core_theme_settings_fields', 'sc'), array( "sc_set_id=? AND sc_key=?", $theme->id, Request::i()->key ) )
								->join( array('core_theme_settings_values', 'sv'), 'sv.sv_id=sc.sc_id' )
								->first();
		}
		catch( UnderflowException $e )
		{
			return;
		}

		foreach( $theme->parents() as $parent )
		{
			try
			{
				$setting = Db::i()->select( 'sc.*, sv.sv_value', array('core_theme_settings_fields', 'sc'), array( "sc_set_id=? AND sc_key=?", $parent->id, Request::i()->key ) )
							->join( array('core_theme_settings_values', 'sv'), 'sv.sv_id=sc.sc_id' )
							->first();

				if ( $setting['sv_value'] !== $themeSetting['sv_value'] )
				{
					/* Value different from theme set we're reverting from? use this, then */
					$value = $setting['sv_value'];
					break;
				}
			}
			catch( UnderflowException $e ) { }
		}

		if ( $value === NULL )
		{
			/* Just use the default */
			$value = $themeSetting['sc_default'];
		}

		Session::i()->log( 'acplogs__theme_setting_deleted', array( $themeSetting['sc_key'] => FALSE ) );

		if ( Request::i()->isAjax() )
		{
			/* Just return the value */
			Output::i()->json( array( 'value' => $value ) );
		}
		else
		{
			/* Update */
			Db::i()->update( 'core_theme_settings_values', array( 'sv_value' => $value ), array( 'sv_id=?', $themeSetting['sc_id'] ) );
			Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes&do=form&id=' . Request::i()->id ), 'completed' );
		}
	}

	/**
	 * Manual Theme Upload
	 *
	 * @return void
	 * @throws ErrorException
	 */
	public function upload(): void
	{
		Dispatcher::i()->checkAcpPermission( 'theme_download_upload' );
		
		$form = new Form( 'form', 'next' );
		$form->add(
			new Upload(
				'core_theme_set_new_import', NULL, FALSE, array(
				'allowedFileTypes' => array( 'xml' ),
				'temporary'        => TRUE
			), NULL, NULL, NULL, 'core_theme_set_new_import'
			)
		);
		
		if ( $values = $form->values() )
		{
			/* Move it to a temporary location */
			$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['core_theme_set_new_import'], $tempFile );
			
			$max = Db::i()->select( 'MAX(set_order)', 'core_themes' )->first();
			
			/* Create a default theme */
			$theme = new Theme;
			$theme->editor_skin	 = 'ips';
			$theme->order        = $max + 1;
			$theme->save();
			
			/* Initate a redirector */
			Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes&do=import' )->setQueryString( array( 'file' => $tempFile, 'key' => md5_file( $tempFile ), 'id' => $theme->id ) )->csrf() );
		}
		
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'theme_set_add_button', $form, FALSE );
	}
	
	/**
	 * Import from upload
	 *
	 * @return	void
	 */
	public function import(): void
	{
		Session::i()->csrfCheck();
		
		if ( !file_exists( Request::i()->file ) or md5_file( Request::i()->file ) !== Request::i()->key )
		{
			Output::i()->error( 'generic_error', '3C130/1', 500, '' );
		}
		
		Output::i()->output = new MultipleRedirect(
			Url::internal( 'app=core&module=customization&controller=themes&do=import' )->setQueryString( array( 'file' => Request::i()->file, 'key' =>  Request::i()->key, 'id' => Request::i()->id ) )->csrf(),
			function( $data )
			{
				$set    = Theme::load( Request::i()->id );
				$iMap	= $set->resource_map;

				$css	   = Theme::load( Request::i()->id )->getAllCss( null, null, null, Theme::RETURN_ALL_NO_CONTENT, true );
				$masterCss = Theme::load( Request::i()->id )->getAllCss( null, null, null, Theme::RETURN_BIT_NAMES, false );

				/* Open XML file */
				$xml = XMLReader::safeOpen( Request::i()->file );
				
				if ( ! @$xml->read() )
				{
					@unlink( Request::i()->file );

					Output::i()->error( 'xml_upload_invalid', '2C163/1', 403, '' );
				}
				
				/* Is this the first batch? */
				if ( !is_array( $data ) )
				{
					$_SESSION['theme_import'] = array( 'css' => array(), 'isNewSet' => false );
					
					/* Save snapshot */
					Theme::load( Request::i()->id )->saveHistorySnapshot();
					
					/* No Name? Then this is a brand-new theme */
					if ( empty( $set->name ) )
					{
						$_SESSION['theme_import']['isNewSet'] = TRUE;
						while ( $xml->read() )
						{
							if ( $xml->name == 'theme' )
							{
								$groups	= array_keys( Member::administrators()['g'] );

								$set->saveSet( array(
									'set_name'         		=> $xml->getAttribute('name'),
									'set_author_name' 		=> $xml->getAttribute('author_name'),
									'set_author_url'   		=> $xml->getAttribute('author_url'),
									'set_version'      		=> $xml->getAttribute('version'),
									'set_update_check' 		=> $xml->getAttribute('update_check'),
									'set_long_version' 		=> ( $xml->getAttribute('long_version') ) ? $xml->getAttribute('long_version') : Application::load('core')->long_version,
									'set_is_default'   		=> $set->is_default,
									'set_permissions'  		=> implode( ',', $groups )
								) );
								
								if ( $xml->getAttribute('easy_mode') )
								{
									$set->save();
								}
								
								break;
							}
						}
					}
					else
					{
						/* We are importing an update to a theme */
						while ( $xml->read() )
						{
							if ( $xml->name == 'theme' )
							{
								$set->saveSet( array(
									'set_author_name'  => $xml->getAttribute('author_name'),
									'set_author_url'   => $xml->getAttribute('author_url'),
									'set_version'      => $xml->getAttribute('version'),
									'set_update_check' => $xml->getAttribute('update_check'),
									'set_long_version' => ( $xml->getAttribute('long_version') ) ? $xml->getAttribute('long_version') : Application::load('core')->long_version
								) );
								
								break;
							}
						}
					}
					
					/* Start importing */
					$data = array( 'apps' => array() );
					return array( $data, Member::loggedIn()->language()->addToStack('processing') );
				}
				
				/* Move to correct app */
				$appKey = NULL;
				$version = Theme::load( Request::i()->id )->long_version;

				$xml->read();
				while ( $xml->read() )
				{
					/* If this is a closing tag, OR if the tag is empty, move on */
					if( $xml->nodeType == $xml::END_ELEMENT or empty( $xml->readInnerXml() ) )
					{
						$xml->next();
						continue;
					}

					$name = $xml->name;
					switch( $name )
					{
						case 'header':
							$set->custom_header = $xml->readString();
							break;

						case 'footer':
							$set->custom_footer = $xml->readString();
							break;

						case 'css':
							$set->custom_css = $xml->readString();
							break;

						case 'layout':
							$set->view_options = $xml->readString();
							break;

						case 'core_js':
							$set->core_js = $xml->readString();
							break;

						case 'core_css':
							$set->core_css = $xml->readString();

							/* Rebuild any designer core data */
							$content = Theme::minifyCss( $set->core_css );
							/* Replace any <fileStore.xxx> tags in the CSS */
							Output::i()->parseFileObjectUrls( $content );

							$set->core_css_filename = File::create( 'core_Theme', 'theme.css', $content );
							break;

						case 'editor_data':
							$editorData = $xml->readString();
							$set->theme_editor_data = !empty( $editorData ) ? json_decode( $editorData, true ) : null;
							break;

						case 'css_variables':
							$set->css_variables = $xml->readString();
							break;

						case 'editor_category':
							$key = $xml->getAttribute( 'key' );
							try
							{
								$row = Db::i()->select( '*', 'core_theme_editor_categories', [ 'cat_key=? and cat_set_id=?', $key, $set->id ] )->first();
								$category = Category::constructFromData( $row );
							}
							catch( UnderflowException )
							{
								$category = new Category;
								$category->key = $key;
								$category->set_id = $set->id;

								/* Set the position first, in case there is no parent specified */
								$position = (int) Db::i()->select( 'max(cat_position)', 'core_theme_editor_categories', array( 'cat_parent=?', 0 ) )->first();
								$category->position = $position + 1;
							}

							$category->app = $xml->getAttribute( 'app' );

							$xml->read();
							while( $xml->read() )
							{
								if( $xml->nodeType == $xml::END_ELEMENT )
								{
									break;
								}

								switch( $xml->name )
								{
									case 'name':
										$category->name = $xml->readString();
										break;

									case 'icon':
										$category->icon = json_encode( Category::buildIconData( $xml->readString() ) );
										break;

									case 'parent':
										try
										{
											$parent = Db::i()->select( '*', 'core_theme_editor_categories', [ 'cat_key=? and (cat_set_id=? or cat_set_id=?)', $xml->readString(), $set->id, 0 ], 'cat_set_id desc' )->first();
											$parentId = $parent['cat_id'];

											/* Reset the position if we changed the category */
											if( $parentId != $category->parent )
											{
												$position = (int) Db::i()->select( 'max(cat_position)', 'core_theme_editor_categories', array( 'cat_parent=?', $category->parent ) )->first();
												$category->position = $position + 1;
											}

											$category->parent = $parentId;
										}
										catch( UnderflowException ){}
										break;
								}

								$category->save();
								$xml->next();
							}
							break;

						case 'editor_setting':
							$key = $xml->getAttribute( 'key' );
							try
							{
								$row = Db::i()->select( '*', 'core_theme_editor_settings', [ 'setting_key=? and setting_set_id=?', $key, $set->id ] )->first();
								$setting = Setting::constructFromData( $row );
							}
							catch( UnderflowException )
							{
								$setting = new Setting;
								$setting->key = $key;
								$setting->set_id = $set->id;
							}

							$setting->app = $xml->getAttribute( 'app' );
							$setting->type = $xml->getAttribute( 'type' );

							/* Figure out the category - it will either be custom to this theme, or it will be in the master */
							try
							{
								$category = Db::i()->select( '*', 'core_theme_editor_categories', [ 'cat_key=? and (cat_set_id=? or cat_set_id=?)', $xml->getAttribute( 'category' ), $set->id, 0 ], 'cat_set_id desc' )->first();
								$setting->category_id = $category['cat_id'];
							}
							catch( UnderflowException )
							{
								/* This should never happen, but we'll do a fail-safe here so we don't break everything */
								$setting->category_id = 0;
							}

							if( !$setting->position )
							{
								$position = (int) Db::i()->select( 'max(setting_position)', 'core_theme_editor_settings', array( 'setting_category_id=?', $setting->category_id ) )->first();
								$setting->position = $position + 1;
							}

							$setting->refresh = (bool) $xml->getAttribute( 'refresh' );

							$xml->read();
							while( $xml->read() )
							{
								if( $xml->nodeType == $xml::END_ELEMENT )
								{
									break;
								}

								switch( $xml->name )
								{
									case 'name':
										$setting->name = $xml->readString();
										break;

									case 'desc':
										$setting->desc = $xml->readString();
										break;

									case 'default':
									case 'data':
										$field = $xml->name;
										$value = trim( $xml->readString() );
										$setting->$field = $value ?: null;
										break;
								}

								$setting->save();
								$xml->next();
							}
							break;

						case 'app':
							$appKey = $xml->getAttribute('key');
							if( Application::appIsEnabled( $appKey ) )
							{
								/* Import */
								$xml->read();
								while ( $xml->read() )
								{
									if( $xml->nodeType == $xml::END_ELEMENT )
									{
										break;
									}

									switch ( $xml->name )
									{
										case 'css':
											$location = $xml->getAttribute('css_location');
											$path     = $xml->getAttribute('css_path');
											$name     = $xml->getAttribute('css_name');

											/* Keep this */
											$_SESSION['theme_import']['css'][] = Db::i()->replace( 'core_theme_css', array(
												'css_set_id'     => Request::i()->id,
												'css_app'        => $appKey,
												'css_location'   => $xml->getAttribute('css_location'),
												'css_path'       => $xml->getAttribute('css_path'),
												'css_name'       => $xml->getAttribute('css_name'),
												'css_attributes' => $xml->getAttribute('css_attributes'),
												'css_content'    => $xml->readString(),
												'css_version'	 => $version,
												'css_user_edited'=> 0
											), true );
											break;
										case 'resource':
											/* Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded. */
											$content  = base64_decode( $xml->readString() );
											$name     = Theme::makeBuiltTemplateLookupHash( $appKey, $xml->getAttribute('location'), $xml->getAttribute('path') ) . '_' . $xml->getAttribute('name');
											$fileName = (string) File::create( 'core_Theme', $name, $content, 'set_resources_' . Request::i()->id, TRUE, NULL, FALSE );

											try
											{
												$existingImage = Db::i()->select( '*', 'core_theme_resources', array(
													'resource_set_id=? AND resource_app=? AND resource_location=? AND resource_path=? AND resource_name=?',
													Request::i()->id, $appKey, $xml->getAttribute('location'), $xml->getAttribute('path'), $xml->getAttribute('name')
												) )->first();

												if ( $existingImage['resource_filename'] )
												{
													try
													{
														File::get( 'core_Theme', $existingImage['resource_filename'] )->delete();
													}
													catch( Exception $e ) { }
												}

												Db::i()->delete( 'core_theme_resources', array( 'resource_id=?', $existingImage['resource_id'] ) );
											}
											catch( UnderflowException $e ) { }

											Db::i()->replace( 'core_theme_resources', array(
												'resource_set_id'      => Request::i()->id,
												'resource_app'         => $appKey,
												'resource_location'    => $xml->getAttribute('location'),
												'resource_path'        => $xml->getAttribute('path'),
												'resource_name'        => $xml->getAttribute('name'),
												'resource_data'        => $content,
												'resource_added'	   => time(),
												'resource_filename'    => $fileName,
												'resource_user_edited' => intval( $xml->getAttribute('user_edited') )
											) );
											break;
									}

									$xml->next();
								}

								/* Update set so far so that mappings are saved */
								$set->saveSet();
							}
							break;

						case 'language':
							$xml->read();
							while ( $xml->read() )
							{
								if ( $xml->name == 'word' )
								{
									$languageIds = $languageIds ?? array_keys( Lang::languages() );
									foreach ( $languageIds as $langId )
									{
										$default = $xml->readString();
										$exists  = Db::i()->select( 'COUNT(*)', 'core_sys_lang_words', array( array( 'lang_id=? and word_key=? and word_theme=?', $langId, $xml->getAttribute('key'), Request::i()->id ) ) )->first();

										if ( $exists )
										{
											Db::i()->update( 'core_sys_lang_words', array(
												'word_default' 		   => $default,
												'word_default_version' => $version,
											), array( array( 'lang_id=? and word_key=? and word_theme=?', $langId, $xml->getAttribute('key'), Request::i()->id ) ) );
										}
										else
										{
											Db::i()->insert( 'core_sys_lang_words', array(
												'lang_id'				=> $langId,
												'word_app'				=> NULL,
												'word_theme'			=> Request::i()->id,
												'word_key'				=> $xml->getAttribute('key'),
												'word_default'			=> $default,
												'word_custom'			=> NULL,
												'word_default_version'	=> $version,
												'word_custom_version'	=> NULL,
												'word_js'				=> FALSE,
												'word_export'			=> TRUE
											) );
										}
									}
								}

								if( $xml->nodeType == $xml::END_ELEMENT )
								{
									break;
								}

								$xml->next();
							}
							break;

						case 'templates':
							$xml->read();
							while ( $xml->read() )
							{
								if ( $xml->name == 'template' )
								{
									$hookPoint = $xml->getAttribute( 'hookpoint' );
									$key = $xml->getAttribute( 'key' );

									$where = [
										[ 'template_set_id=?', Request::i()->id ],
										[ 'template_name=?', $xml->getAttribute('name') ],
										[ 'template_hookpoint_type=?', $xml->getAttribute( 'hookpoint_type' ) ]
									];
									if( empty( $hookPoint ) )
									{
										$where[] = [ 'template_hookpoint is null'];
									}
									else
									{
										$where[] = [ 'template_hookpoint=?', $hookPoint ];
									}

									Db::i()->delete( 'core_theme_templates_custom', $where );
									Db::i()->insert( 'core_theme_templates_custom', [
										'template_set_id' 	=> Request::i()->id,
										'template_name' => $xml->getAttribute('name'),
										'template_hookpoint' => ( $hookPoint ?: null ),
										'template_hookpoint_type' => $xml->getAttribute('hookpoint_type'),
										'template_key' => ( $key ?: null ),
										'template_version' => $xml->getAttribute('version'),
										'template_content' => $xml->readString(),
										'template_updated' => time(),
									] );

									if( $xml->nodeType == $xml::END_ELEMENT )
									{
										break;
									}

									$xml->next();
								}
							}
							break;

						case 'logo':
							$type = $xml->getAttribute('type');
							$name = $xml->getAttribute('name');

							/* Sharer logo and favicons may be present from themes generated in 4.3 and earlier, but are no longer used */
							if( $type != 'sharer' AND $type != 'favicon' )
							{
								if( isset( $set->logo[ $type ]['url'] ) )
								{
									File::get( 'core_Theme', $set->logo[ $type ]['url'] )->delete();
								}

								File::$safeFileExtensions[] = 'svg';
								$url = (string) File::create( 'core_Theme', $name, base64_decode( $xml->readString() ) );

								$set->saveSet( array( 'logo' => array( $type => array( 'filename' => $name, 'setting_height' => $xml->getAttribute('height') ?? 100 ) ) ) );
							}
							break;
					}

					$set->save();
					$xml->next();
				}
				return null;
			},
			function()
			{
                $set = Theme::load( Request::i()->id );

				/* Do we need to clean up orphaned CSS files? */
				if ( $_SESSION['theme_import']['isNewSet'] === false and isset( $_SESSION['theme_import']['css'] ) )
				{
					Db::i()->delete( 'core_theme_css', array(
                        array( Db::i()->in( 'css_id', $_SESSION['theme_import']['css'], true ) ),
                        array( 'css_set_id=?', $set->id )
                    ) );
				}
				
				Theme::deleteCompiledResources( null, null, null, null, $set->id );
				Theme::deleteCompiledTemplate( null, null, null, $set->id );
				Theme::deleteCompiledCss( null, null, null, null, $set->id );
				
				Store::i()->delete( 'core_theme_import_' . md5_file( Request::i()->file ) );

				@unlink( Request::i()->file );
				
				/* Update theme settings */
				foreach( Application::applications() as $app )
				{
					$app->installThemeEditorSettings();
				}

				/* Clear the entire data store, there are too many to list here */
				Store::i()->clearAll();

				Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes' ) );
			}
		);
	}

	/**
	 * Upload a new version
	 *
	 * @return    void
	 * @throws ErrorException
	 */
	public function importForm(): void
	{
		$id = intval( Request::i()->id );
		
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'theme_download_upload' );
		
		$themeSet = Theme::load( $id );

		$form = new Form( 'form', 'theme_set_import_button' );
		
		$form->add( new Upload( 'core_theme_set_new_import', NULL, FALSE, array( 'allowedFileTypes' => array( 'xml' ), 'temporary' => TRUE ), NULL, NULL, NULL, 'core_theme_set_new_import' ) );
		
		if ( $values = $form->values() )
		{
			/* Move it to a temporary location */
			$tempFile = tempnam( TEMP_DIRECTORY, 'IPS' );
			move_uploaded_file( $values['core_theme_set_new_import'], $tempFile );
			
			/* Initate a redirector */
			Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes&do=import' )->setQueryString( array( 'file' => $tempFile, 'key' => md5_file( $tempFile ), 'id' => Request::i()->id) )->csrf() );
		}
		
		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( Member::loggedIn()->language()->addToStack('theme_set_import_title', FALSE, array( 'sprintf' => array( $themeSet->name ) ) ), $form, FALSE );
	}
	
	/**
	 * Export a theme set form
	 *
	 * @return	void
	 */
	public function exportForm(): void
	{
		$id = intval( Request::i()->id );
		
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'theme_download_upload' );
		
		$themeSet = Theme::load( $id );

		$form = new Form( 'form', 'theme_set_export_button' );
		
		$storedAuthor = ( isset( Store::i()->theme_stored_author ) AND is_array( Store::i()->theme_stored_author ) ) ? Store::i()->theme_stored_author : null;
		
		$form->addHeader( Member::loggedIn()->language()->addToStack('theme_set_export_title', FALSE, array( 'sprintf' => array( $themeSet->_title ) ) ) );
		
		if ( \IPS\IN_DEV )
		{
			$form->add( new Text( 'theme_template_export_author_name', ( $storedAuthor !== null ) ? $storedAuthor['name'] : false, false ) );
			$form->add( new Text( 'theme_template_export_author_url' , ( $storedAuthor !== null ) ? $storedAuthor['url']  : false, false ) );
			$form->add( new Text( 'theme_update_check' , $themeSet->update_check, false ) );
			
			$form->add( new Text( 'theme_template_export_version'        , $themeSet->version     , false, array( 'placeholder' => '1.0.0' ) ) );
			$form->add( new Number( 'theme_template_export_long_version' , $themeSet->long_version, false ) );
		}
		
		if ( $values = $form->values() or Request::i()->form_submitted )
		{
			$authorName = $values['theme_template_export_author_name'] ?: $themeSet->author_name;
			$authorUrl  = $values['theme_template_export_author_url'] ?: $themeSet->author_url;
			$version = $values['theme_template_export_version'] ?: $themeSet->version;
			$longVersion = is_int( $values['theme_template_export_long_version'] ) ? $values['theme_template_export_long_version'] : $themeSet->long_version;
			$updateCheck = $values['theme_update_check'] ?: $themeSet->update_check;
			
			Store::i()->theme_stored_author = array(
				'name' => $authorName,
				'url'  => $authorUrl,
			);
			
			/* Init */
			$xml = new XMLWriter;
			$xml->openMemory();
			$xml->setIndent( TRUE );
			$xml->startDocument( '1.0', 'UTF-8' );
			
			/* Root tag */
			$xml->startElement('theme');
			$xml->startAttribute('name');
			$xml->text( Member::loggedIn()->language()->get('core_theme_set_title_' . $themeSet->_id ) );
			$xml->endAttribute();

			$xml->startAttribute('author_name');
			$xml->text( $authorName );
			$xml->endAttribute();
			$xml->startAttribute('author_url');
			$xml->text( $authorUrl );
			$xml->endAttribute();
			
			$xml->startAttribute('version');
			$xml->text( $version );
			$xml->endAttribute();
			$xml->startAttribute('long_version');
			$xml->text( $longVersion );
			$xml->endAttribute();
			
			$xml->startAttribute('update_check');
			$xml->text( $updateCheck );
			$xml->endAttribute();

			$xml->startElement('header');
			$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', (string) $themeSet->custom_header ) );
			$xml->endElement();

			$xml->startElement('footer');
			$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', (string) $themeSet->custom_footer ) );
			$xml->endElement();

			$xml->startElement('css');
			$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', (string) $themeSet->custom_css ) );
			$xml->endElement();

			$xml->startElement('layout');
			$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', (string) $themeSet->view_options ) );
			$xml->endElement();

			$xml->startElement('core_js');
			$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', (string) $themeSet->core_js ) );
			$xml->endElement();

			$xml->startElement('core_css');
			$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', (string) $themeSet->core_css ) );
			$xml->endElement();

			$xml->startElement('css_variables');
			$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', (string) $themeSet->css_variables ) );
			$xml->endElement();

			$xml->startElement('editor_data');
			$editorData = $themeSet->theme_editor_data;
			$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', ( $editorData === Theme::$defaultThemeEditorData ? '' : json_encode( $editorData ) ) ) );
			$xml->endElement();

			/* Copy logos */
			if( $logos = $themeSet->logo )
			{
				if( is_array( $logos ) )
				{
					foreach ( $logos as $file => $data )
					{
						if( isset( $data['filename'] ) )
						{
							/* Start the XML */
							$xml->startElement('logo');

							$xml->startAttribute('type');
							$xml->text( $file );
							$xml->endAttribute();

							if( isset( $data['setting_height'] ) )
							{
								$xml->startAttribute('height');
								$xml->text( $data['setting_height'] );
								$xml->endAttribute();
							}

							/* Get image data */
							$original = File::get( 'core_Theme', $data['url'] );

							$xml->startAttribute('name');
							$xml->text( $data['filename'] );
							$xml->endAttribute();

							$xml->text( base64_encode( $original->contents() ) );
											
							/* Close the <template> tag */
							$xml->endElement();
						}
					}
				}
			}

			/* Theme Editor Categories */
			$lang = Lang::load( Lang::defaultLanguage() );
			$categories = iterator_to_array( Db::i()->select( '*', 'core_theme_editor_categories', array( 'cat_set_id=?', $themeSet->id ) ) );
			if( count( $categories ) )
			{
				foreach( $categories as $cat )
				{
					$xml->startElement( 'editor_category' );

					$xml->startAttribute( 'key' );
					$xml->text( $cat['cat_key'] );
					$xml->endAttribute();

					$xml->startAttribute( 'app' );
					$xml->text( $cat['cat_app'] ?: 'core' );
					$xml->endAttribute();

					if( isset( $cat['cat_parent'] ) and $cat['cat_parent'] )
					{
						$xml->startElement( 'parent' );
						$xml->text( Category::constructFromData( $cat )->parent()->key );
						$xml->endElement();
					}

					$xml->startElement( 'name' );
					$xml->text( (string) $cat['cat_name'] );
					$xml->endElement();

					$xml->startElement( 'icon' );
					$xml->text( Category::constructFromData( $cat )->icon() );
					$xml->endElement();

					$xml->endElement();
				}
			}

			/* Theme Editor Settings */
			$settings = iterator_to_array( Db::i()->select( '*', 'core_theme_editor_settings', [ 'setting_set_id=?', $themeSet->_id ], 'setting_position' )
				->join( 'core_theme_editor_categories', 'setting_category_id=core_theme_editor_categories.cat_id' ) );
			if( count( $settings ) )
			{
				foreach( $settings as $setting )
				{
					$xml->startElement( 'editor_setting' );

					$xml->startAttribute( 'key' );
					$xml->text( $setting['setting_key'] );
					$xml->endAttribute();

					$xml->startAttribute( 'type' );
					$xml->text( $setting['setting_type'] );
					$xml->endAttribute();

					$xml->startAttribute( 'category' );
					$xml->text( $setting['cat_key'] );
					$xml->endAttribute();

					$xml->startAttribute( 'app' );
					$xml->text( $setting['setting_app'] ?: 'core' );
					$xml->endAttribute();

					if( $setting['setting_refresh'] )
					{
						$xml->startAttribute( 'refresh' );
						$xml->text( 'true' );
						$xml->endAttribute();
					}

					$xml->startElement( 'name' );
					$xml->text( (string) $setting['setting_name'] );
					$xml->endElement();

					$xml->startElement( 'desc' );
					$xml->writeCdata( (string) $setting['setting_desc'] );
					$xml->endElement();

					$xml->startElement( 'data' );
					$xml->writeCdata( $setting['setting_data'] ?? '[]' );
					$xml->endElement();

					$xml->startElement( 'default' );
					$xml->writeCdata( $setting['setting_default'] );
					$xml->endElement();

					$xml->endElement();
				}
			}
						
			/* Loop applications */
			foreach ( Application::applications() as $appDir )
			{
				if ( ! $appDir->enabled )
				{
					continue;
				}
				
				/* Initiate the <app> tag */
				$xml->startElement('app');
					
				/* Set key */
				$xml->startAttribute('key');
				$xml->text( $appDir->directory );
				$xml->endAttribute();
					
				/* Set version */
				$xml->startAttribute('version');
				$xml->text( $appDir->long_version );
				$xml->endAttribute();
				
				/* CSS */
				$css = $themeSet->getAllCss( $appDir->directory, '', '', Theme::RETURN_ALL );

				if ( 1==2 and isset( $css[ $appDir->directory] ) )
				{
					foreach( $css[ $appDir->directory ] as $loc => $lv )
					{
						foreach( $css[ $appDir->directory ][ $loc ] as $path => $gv )
						{
							foreach( $css[ $appDir->directory ][ $loc ][ $path ] as $name => $data )
							{
								/* Remove original template bits */
								if ( $data['InheritedValue'] != 'original' and trim( $data['css_content'] ) )
								{
									$xml->startElement('css');

									foreach( $css[ $appDir->directory ][ $loc ][ $path ][ $name ] as $k => $v )
									{
										if ( in_array( substr( $k, 4 ), array( 'location', 'path', 'name', 'attributes' ) ) )
										{
											$xml->startAttribute($k);
											$xml->text( $v );
											$xml->endAttribute();
										}
									}

									/* Write value */
									if ( preg_match( '/[<>&]/', $data['css_content'] ) )
									{
										$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $data['css_content'] ) );
									}
									else
									{
										$xml->text( $data['css_content'] );
									}

									$xml->endElement();
								}
							}
						}
					}
				}
				
				$parents = array( $themeSet->id, 0 );
				try
				{
					foreach( $themeSet->parents() as $parent )
					{
						$parents[] = $parent->_id;
					}
				}
				catch( OutOfRangeException $e ) { }
				
				$resources = array();
				
				/* Resources */
				foreach ( Db::i()->select(
					'*, CONCAT( resource_app, resource_location, resource_path, resource_name) as thekey, INSTR(\',' . implode( ',' , $parents ) . ',\', CONCAT(\',\',resource_set_id,\',\') ) as theorder',
					'core_theme_resources',
					array( 'resource_user_edited=1 and resource_set_id IN(' . implode( ',' , $parents ) . ') and resource_app=?', $appDir->directory ),
					'theorder desc'
				) as $data )
				{
					$resources[ $data['thekey'] ] = $data;
				}
				
				foreach( $resources as $key => $data )
				{					
					$xml->startElement('resource');
					
					$xml->startAttribute('name');
					$xml->text( $data['resource_name'] );
					$xml->endAttribute();
					
					$xml->startAttribute('location');
					$xml->text( $data['resource_location'] );
					$xml->endAttribute();
					
					$xml->startAttribute('path');
					$xml->text( $data['resource_path'] );
					$xml->endAttribute();
					
					$xml->startAttribute('user_edited');
					$xml->text( $data['resource_user_edited'] );
					$xml->endAttribute();
					
					/* Theme resources should be raw binary data everywhere (filesystem and DB) except in the theme XML download where they are base64 encoded. */
					$xml->text( base64_encode( $data['resource_data'] ) );
					
					$xml->endElement();
				}
				
				/* Close the <app> tag */
				$xml->endElement();
			}
			
			/* Language strings */
			$xml->startElement('language');
		
			$words = array();
			
			foreach ( Db::i()->select( '*', 'core_sys_lang_words', array( 'word_theme=?', $themeSet->id ) ) as $data )
			{
				$words[ $data['word_key'] ] = $data;
			}

			foreach ( $words as $row )
			{
				$xml->startElement( 'word' );
				$xml->startAttribute('key');
				$xml->text( $row['word_key'] );
				$xml->endAttribute();
				if ( preg_match( '/[<>&]/', $row['word_default'] ) )
				{
					$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $row['word_default'] ) );
				}
				else
				{
					$xml->text( $row['word_default'] );
				}
				$xml->endElement();
			}

			$xml->endElement();

			/* Custom templates */
			$xml->startElement('templates');
			foreach( Db::i()->select( '*', 'core_theme_templates_custom', array( 'template_set_id=?', $themeSet->id ) ) as $template )
			{
				$xml->startElement( 'template' );
				$xml->startAttribute('hookpoint');
				$xml->text( (string) $template['template_hookpoint'] );
				$xml->startAttribute('name');
				$xml->text( (string) $template['template_name'] );
				$xml->startAttribute('hookpoint_type');
				$xml->text( (string) $template['template_hookpoint_type'] );
				$xml->startAttribute('key');
				$xml->text( (string) $template['template_key'] );
				$xml->endAttribute();
				$xml->startAttribute('version');
				$xml->text( (int) $template['template_version'] );
				$xml->endAttribute();
				if ( preg_match( '/[<>&]/', $template['template_content'] ) )
				{
					$xml->writeCData( str_replace( ']]>', ']]]]><![CDATA[>', $template['template_content'] ) );
				}
				else
				{
					$xml->text( (string) $template['template_content'] );
				}
				$xml->endElement();
			}
			$xml->endElement();
			
			/* Finish */
			$xml->endDocument();
			
			Session::i()->log( 'acplog__theme_exported', array( "core_theme_set_title_{$themeSet->_id}" => TRUE ) );

			$filename = Member::loggedIn()->language()->get('core_theme_set_title_' . $themeSet->_id  ) . ( $version ? " {$version}" : "" ) . ".xml";
			Output::i()->sendOutput( $xml->outputMemory(), 200, 'application/xml', array( 'Content-Disposition' => Output::getContentDisposition( 'attachment', $filename ), FALSE, FALSE ) );
		}
		
		/* Display */
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block(  Member::loggedIn()->language()->addToStack('theme_set_export_title', FALSE, array( 'sprintf' => array( $themeSet->_title ) ) ), $form, FALSE );
	}
	
	/**
	 * Delete Theme
	 *
	 * @return	void
	 */
	public function delete(): void
	{
		/* Check permission */
		Dispatcher::i()->checkAcpPermission( 'theme_sets_manage' );

		try
		{
			$theme = Theme::load( Request::i()->id );
			if ( $theme->is_default )
			{
				Output::i()->error( 'cannot_delete_default_theme', '1C163/3', 403, '' );
			}
		}
		catch ( OutOfRangeException $e ) {}

		parent::delete();
	}
	
	/**
	 * Manually build CSS and HTML ready for use by the output engine
	 *
	 * @return	void
	 */
	public function build(): void
	{
		Session::i()->csrfCheck();
		
		$set = Theme::load( Request::i()->id );
		
		/* Resources has to come before CSS otherwise CSS url()s are out of date as resource build changes resource URL after CSS has been built */
		$set->compileTemplates();
		$set->buildResourceMap();
		$set->compileCss();
		
 		Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes' ), 'completed' );
	}

	/**
	 * Rebuild master templates into /static/
	 *
	 * @return    void
	 * @throws Exception
	 */
	public function rebuildStatic(): void
	{
		Session::i()->csrfCheck();

		foreach( IPS::$ipsApps as $app )
		{
			Application::load( $app )->buildThemeTemplates();
			Theme::compileStatic( $app );
		}

		Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes' ), 'completed' );
	}

	/**
	 * Set Members
	 *
	 * @return	void
	 */
	public function setMembers(): void
	{
		$form = new Form;
		$form->hiddenValues['id'] = Request::i()->id;
		$form->add( new CheckboxSet( 'member_reset_where', '*', TRUE, array( 'options' => Group::groups( TRUE, FALSE ), 'multiple' => TRUE, 'parse' => 'normal', 'unlimited' => '*', 'unlimitedLang' => 'all', 'impliedUnlimited' => TRUE ) ) );

		if ( $values = $form->values() )
		{
			if ( $values['member_reset_where'] === '*' )
			{
				$where = NULL;
			}
			else
			{
				$where = Db::i()->in( 'member_group_id', $values['member_reset_where'] );
			}
			
			if ( $where )
			{
				Db::i()->update( 'core_members', array( 'skin' => Request::i()->id ), $where );
			}
			else
			{
				Member::updateAllMembers( array( 'skin' => Request::i()->id ) );
			}
			
			Session::i()->log( 'acplog__theme_member_reset' );
			
			Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes' ), 'member_theme_reset' );
		}

		Output::i()->output = $form;
	}

	/**
	 * Toggles designer mode
	 *
	 * @return void
	 * @throws Exception
	 */
	public function toggleDesignerMode(): void
	{
		Settings::i()->changeValues( [ 'theme_designer_mode' => Settings::i()->theme_designer_mode ? 0 : 1 ] );

		Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes' ), 'updated' );
	}

	/**
	 * Show designer core tools
	 * 
	 * @return void
	 * @throws ErrorException
	 */
	public function designerCore(): void
	{
		if ( ! Settings::i()->theme_designer_mode and ! \IPS\IN_DEV )
		{
			Output::i()->error( 'theme_designer_mode_not_enabled', '2T300/1', 403, '' );
		}

		$theme = Theme::load( Request::i()->set_id );

		Output::i()->cssFiles = array_merge( Output::i()->cssFiles, Theme::i()->css( 'customization/themes.css', 'core', 'admin' ) );
		Output::i()->jsFiles  = array_merge( Output::i()->jsFiles, Output::i()->js( 'admin_customization.js', 'core', 'admin' ) );

		$form = new Form( 'form' );
		$form->hiddenValues['id'] = $theme->id;
		$form->class = 'ipsForm--vertical ipsForm--designer-core';

		$form->addTab( 'theme_designer_form_css' );
		$form->addMessage('theme_designer_form_css_info');
		$form->add( new Codemirror( 'theme_designer_form_css_form', $theme->core_css, FALSE, [ 'codeModeAllowedLanguages' => [ 'ipscss' ], 'height' => 800 ] ) );
		$form->addTab( 'theme_designer_form_js' );
		$form->addMessage('theme_designer_form_js_info');
		$form->add( new Codemirror( 'theme_designer_form_js_form', $theme->core_js, FALSE, [ 'codeModeAllowedLanguages' => [ 'javascript' ], 'height' => 800 ] ) );

		if ( $values = $form->values() )
		{
			$theme->core_css = $values['theme_designer_form_css_form'];
			$theme->core_js = $values['theme_designer_form_js_form'];

			if ( $theme->core_css_filename )
			{
				try
				{
					File::get( 'core_Theme', $theme->core_css_filename )->delete();
				}
				catch( Exception ) { }
			}

			$theme->core_css_filename = File::create( 'core_Theme', 'theme.css', Theme::compileCustomCss( $theme->core_css ) );
			$theme->save();

			if ( Request::i()->isAjax() )
			{
				Output::i()->json( ['result' => 'ok'] );
			}
			else
			{
				Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes&do=designerCore&id=' . $theme->id ), 'saved' );
			}
		}

		Output::i()->output = Theme::i()->getTemplate( 'customization' )->designerCoreForm( $theme, $form );
	}

	/**
	 * Removes custom CSS variables added by the theme editor to reset it
	 *
	 * @return void
	 */
	public function revertCustomizations(): void
	{
		$theme = Theme::load( Request::i()->set_id );
		$theme->removeThemeEditorCustomizations();

		Output::i()->redirect( Url::internal( 'app=core&module=customization&controller=themes' ), 'theme_set_revert_done' );
	}

	/**
	 * Copy
	 *
	 * @return	void
	 */
	protected function copy() : void
	{
		Session::i()->csrfCheck();

		try
		{
			$theme = Theme::load( Request::i()->id );
		}
		catch( OutOfRangeException )
		{
			Output::i()->error( 'node_error', '2S101/Z', 404 );
		}

		Output::i()->output = new MultipleRedirect(
			$this->url->setQueryString( [ 'do' => 'copy', 'id' => $theme->id, 'csrfKey' => Session::i()->csrfKey ] ),
			function( $data ) use ( $theme )
			{
				if( !is_array( $data ) or !$data['newId'] )
				{
					$newTheme = clone $theme;
					$data['newId'] = $newTheme->id;
					$data['step'] = 'templates';
					$data['resources'] = (int) Db::i()->select( 'count(*)', 'core_theme_resources', array( 'resource_set_id=?', $theme->id ) )->first();
					$data['offset'] = 0;
				}

				if( !isset( $newTheme ) )
				{
					$newTheme = Theme::load( $data['newId'] );
				}

				switch( $data['step'] )
				{
					case 'templates':
						/* Dynamically produce insert list, so we don't have to update each time the table changes */
						$templateTable  = Db::i()->getTableDefinition( 'core_theme_templates', TRUE );
						$templateFields = array_keys( (array)$templateTable['columns'] );
						array_walk( $templateFields, function( &$name, $i, $setId )
						{
							switch( $name )
							{
								case 'template_id':
									$name = 'null';
									break;
								case 'template_set_id':
									$name = $setId;
									break;
							}
						}, $newTheme->id );

						/* Copy across any template bits */
						Db::i()->insert( 'core_theme_templates', Db::i()->select( implode(',', $templateFields ), 'core_theme_templates', array( 'template_set_id=?', $theme->id ) ) );

						$data['step'] = 'css';
						break;

					case 'css':
						$cssTable       = Db::i()->getTableDefinition( 'core_theme_css', TRUE );
						$cssFields      = array_keys( (array)$cssTable['columns'] );
						array_walk( $cssFields, function( &$name, $i, $setId )
						{
							switch( $name )
							{
								case 'css_id':
									$name = 'null';
									break;
								case 'css_set_id':
									$name = $setId;
									break;
							}
						}, $newTheme->id );

						/* Copy across any CSS bits */
						Db::i()->insert( 'core_theme_css', Db::i()->select( implode(',', $cssFields ), 'core_theme_css', array( 'css_set_id=?', $theme->id ) ) );

						$data['step'] = 'resources';
						break;

					case 'resources':
						/* Resources are the sloweest on cloud, so we're going to loop through */
						$offset = $data['offset'] ?? 0;
						if( $data['offset'] == 0 )
						{
							Db::i()->delete( 'core_theme_resources', array( 'resource_set_id=?', $newTheme->id ) );
							$newTheme->resource_map = [];
						}
						$limit = 15;
						$newTheme->copyResourcesFromSet( $theme->id, $offset, $limit );
						$data['offset'] = $offset + $limit;
						if( $data['offset'] >= $data['resources'] )
						{
							$data['step'] = 'lang';
						}
						break;

					case 'lang':
						/* Copy any language keys */
						foreach(Db::i()->select( '*', 'core_sys_lang_words', array( 'word_theme=?', $theme->id ) ) AS $word )
						{
							$word['word_theme'] = $newTheme->id;
							unset( $word['word_id'] );
							Db::i()->insert( 'core_sys_lang_words', $word );
						}

						$data['step'] = 'logo';
						break;

					case 'logo':
						$newTheme->logo_data = NULL;
						$newTheme->copyLogosFromSet( $theme->id );
						$data['step'] = 'editor';
						break;

					case 'editor':
						/* Copy custom settings and categories */
						$newTheme->copyEditorSettings( $theme->id );
						$data['step'] = 'customtemplates';
						break;

					case 'customtemplates':
						/* Copy custom templates and hooks */
						$newTheme->copyCustomTemplates( $theme->id );
						return null;
				}

				return array( $data, Member::loggedIn()->language()->addToStack('copying__theme_' . $data['step']) );
			},
			function() use ( $theme )
			{
				Session::i()->log( 'acplog__node_copied_c', array( $theme->title => TRUE, $theme->titleForLog() => FALSE ) );
				Output::i()->redirect( $this->url, 'saved' );
			});
	}
}