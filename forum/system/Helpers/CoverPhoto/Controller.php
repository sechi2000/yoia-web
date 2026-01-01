<?php
/**
 * @brief		Cover Photo Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 May 2014
 */

namespace IPS\Helpers\CoverPhoto;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller as DispatcherController;
use IPS\Helpers\CoverPhoto;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Node\Model;
use IPS\Output;
use IPS\Output\UI\UiExtension;
use IPS\Request;
use IPS\Session;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Cover Photo Controller
 */
abstract class Controller extends DispatcherController
{		
	/**
	 * Upload Cover Photo
	 *
	 * @return	void
	 */
	protected function coverPhotoUpload() : void
	{	
		$photo = $this->_coverPhotoGet();
		if ( !$photo->editable )
		{
			Output::i()->error( 'no_module_permission', '2S216/1', 403, '' );
		}

		$form = new Form( 'coverPhoto' );
		$form->class = 'ipsForm--vertical ipsForm--cover-photo ipsForm--noLabels';
		$form->add( new Upload( 'cover_photo', NULL, TRUE, array( 'image' => [ 'maxWidth' => NULL, 'maxHeight' => NULL ], 'allowStockPhotos' => TRUE, 'minimize' => FALSE, 'maxFileSize' => ( $photo->maxSize and $photo->maxSize != -1 ) ? $photo->maxSize / 1024 : NULL, 'storageExtension' => $this->_coverPhotoStorageExtension() ) ) );
		if ( $values = $form->values() )
		{
			try
			{
				$photo->delete();
			}
			catch ( Exception $e ) { }

			$coverPhoto = new CoverPhoto( $values['cover_photo'], 0 );
			$this->_coverPhotoSet( $coverPhoto, 'new' );

			/* Call any UI Extensions that might be linked to this object */
			if( $photo->object instanceof Content or $photo->object instanceof Model )
			{
				UiExtension::i()->run( $photo->object, 'formPostSave', [ [ 'cover_photo' => $coverPhoto ] ] );
			}

			Output::i()->redirect( $this->_coverPhotoReturnUrl()->setQueryString( array( '_position' => 1 ) ) );
		}
		
		if ( Dispatcher::hasInstance() and Dispatcher::i()->controllerLocation == 'admin' )
		{
			Output::i()->output = $form;
		}
		else
		{
			Output::i()->output = $form->customTemplate( array( Theme::i()->getTemplate( 'forms', 'core' ), 'popupTemplate' ) );
		}
	}
	
	/**
	 * Remove Cover Photo
	 *
	 * @return	void
	 */
	protected function coverPhotoRemove() : void
	{
		Session::i()->csrfCheck();
		$photo = $this->_coverPhotoGet();
		if ( !$photo->editable )
		{
			Output::i()->error( 'no_module_permission', '2S216/2', 403, '' );
		}
		
		try
		{
			$photo->delete();
		}
		catch ( Exception $e ) { }
		
		$this->_coverPhotoSet( new CoverPhoto( NULL, 0 ), 'remove' );

		/* Call any UI Extensions that might be linked to this object */
		if( $photo->object instanceof Content or $photo->object instanceof Model )
		{
			UiExtension::i()->run( $photo->object, 'formPostSave', [ [ 'cover_photo' => null, 'cover_photo_offset' => 0 ] ] );
		}

		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->redirect( $this->_coverPhotoReturnUrl() );
		}
	}
	
	/**
	 * Reposition Cover Photo
	 *
	 * @return	void
	 */
	protected function coverPhotoPosition() : void
	{
		Session::i()->csrfCheck();
		$photo = $this->_coverPhotoGet();
		if ( !$photo->editable )
		{
			Output::i()->error( 'no_module_permission', '2S216/3', 403, '' );
		}
		
		$photo->offset = Request::i()->offset;
		$this->_coverPhotoSet( $photo, 'reposition' );

		/* Call any UI Extensions that might be linked to this object */
		if( $photo->object instanceof Content or $photo->object instanceof Model )
		{
			UiExtension::i()->run( $photo->object, 'formPostSave', [ [ 'cover_photo_offset' => $photo->offset ] ] );
		}
		
		if ( Request::i()->isAjax() )
		{
			Output::i()->json( 'OK' );
		}
		else
		{
			Output::i()->redirect( $this->_coverPhotoReturnUrl() );
		}
	}
	
	/**
	 * Get Cover Photo Storage Extension
	 *
	 * @return	string
	 */
	abstract protected function _coverPhotoStorageExtension(): string;
	
	/**
	 * Set Cover Photo
	 *
	 * @param	CoverPhoto	$photo	New Photo
	 * @return	void
	 */
	abstract protected function _coverPhotoSet( CoverPhoto $photo ) : void;
	
	/**
	 * Get Cover Photo
	 *
	 * @return	CoverPhoto
	 */
	abstract protected function _coverPhotoGet(): CoverPhoto;
	
	/**
	 * Get URL to return to after editing cover photo
	 *
	 * @return	Url
	 */
	protected function _coverPhotoReturnUrl(): Url
	{
		return Request::i()->referrer() ?: Request::i()->url()->stripQueryString( array( 'do', 'csrfKey' ) );
	}
}