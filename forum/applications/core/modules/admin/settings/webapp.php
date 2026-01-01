<?php
/**
 * @brief		webapp
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		28 Mar 2023
 */

namespace IPS\core\modules\admin\settings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Data\Store;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\File;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Color;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Text;
use IPS\Helpers\Form\TextArea;
use IPS\Helpers\Form\Upload;
use IPS\Helpers\Form\YesNo;
use IPS\Helpers\MultipleRedirect;
use IPS\Http\Url;
use IPS\Http\Url\Friendly;
use IPS\Image;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use function count;
use function defined;
use function mb_strstr;
use function strlen;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * webapp
 */
class webapp extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'webapp_manage' );
		parent::execute();
	}

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		$form = new Form;

		$form->addTab( 'webapp_tab_icons' );

		/* Homescreen icons - we accept one upload and create the images we need */
		$homeScreen = Settings::i()->icons_homescreen ? json_decode( Settings::i()->icons_homescreen, TRUE ) :[];
		$form->add( new Upload( 'icons_homescreen', ( isset( $homeScreen['original'] ) ) ? File::get( 'core_Icons', $homeScreen['original'] ) : NULL, FALSE, array( 'image' => true, 'storageExtension' => 'core_Icons', 'allowedFileTypes' => array( 'png' ) ) ) );

		$homeScreenMaskable = Settings::i()->icons_homescreen_maskable ? json_decode( Settings::i()->icons_homescreen_maskable, TRUE ) : array();
		$form->add( new Upload( 'icons_homescreen_maskable', ( isset( $homeScreenMaskable['original'] ) ) ? File::get( 'core_Icons', $homeScreenMaskable['original'] ) : NULL, FALSE, array( 'image' => true, 'storageExtension' => 'core_Icons', 'allowedFileTypes' => array( 'png' ) ) ) );

		/* Apple startup screen logo - we accept one upload and create the images we need */
		$apple = Settings::i()->icons_apple_startup ? json_decode( Settings::i()->icons_apple_startup, TRUE ) : array();
		$form->add( new Upload( 'icons_apple_startup', ( isset( $apple['original'] ) ) ? File::get( 'core_Icons', $apple['original'] ) : NULL, FALSE, array( 'image' => true, 'storageExtension' => 'core_Icons', 'allowedFileTypes' => array( 'png' ) ) ) );

		/* Safari pinned tabs icon and highlight color */
		$form->add( new Upload( 'icons_mask_icon', Settings::i()->icons_mask_icon ? File::get( 'core_Icons', Settings::i()->icons_mask_icon ) : NULL, FALSE, array( 'allowedFileTypes' => array( 'svg' ), 'storageExtension' => 'core_Icons', 'obscure' => FALSE ) ) );
		$form->add( new Color( 'icons_mask_color', Settings::i()->icons_mask_color, FALSE ) );

		$form->addTab( 'webapp_tab_manifest' );

		/* And finally, additional manifest and livetile details */
		$manifestDetails = Settings::i()->manifest_details ? json_decode( Settings::i()->manifest_details, TRUE ) : [];
		if ( isset( $manifestDetails['cache_key'] ) )
		{
			unset( $manifestDetails['cache_key'] );
		}

		$form->add( new YesNo( 'configure_manifest', count( $manifestDetails ) > 0, FALSE, array(
			'togglesOn'	=> array( 'manifest_shortname', 'manifest_fullname', 'manifest_description', 'manifest_defaultapp', 'manifest_themecolor', 'manifest_bgcolor', 'manifest_custom_url_toggle' ),
		) ) );

		$form->add( new Text( 'manifest_shortname', ( isset( $manifestDetails['short_name'] ) ) ? $manifestDetails['short_name'] : '', FALSE, array(), NULL, NULL, NULL, 'manifest_shortname' ) );
		$form->add( new Text( 'manifest_fullname', ( isset( $manifestDetails['name'] ) ) ? $manifestDetails['name'] : '', FALSE, array(), NULL, NULL, NULL, 'manifest_fullname' ) );
		$form->add( new TextArea( 'manifest_description', ( isset( $manifestDetails['description'] ) ) ? $manifestDetails['description'] : '', FALSE, array(), NULL, NULL, NULL, 'manifest_description' ) );

		$formStartUrl = '';
		if( isset( $manifestDetails['start_url'] ) )
		{
			$formStartUrl = str_replace( 'index.php?/', '', Friendly::fixComponentPath( $manifestDetails['start_url'] ) );
		}

		$form->add( new YesNo( 'manifest_custom_url_toggle', ( isset( $manifestDetails['start_url'] ) and !empty( $formStartUrl ) ), FALSE, array(
			'togglesOn'	=> array( 'manifest_short_url' ),
		), NULL, NULL, NULL, 'manifest_custom_url_toggle' ) );

		$form->add( new Text( 'manifest_short_url', $formStartUrl, FALSE, array(), function( $val )
		{
			if ( $val and Request::i()->manifest_custom_url_toggle_checkbox )
			{
				if ( mb_substr( $val, -1 ) !== '/' )
				{
					$val .= '/';
				}

				$response = Url::external( Url::baseUrl() . ( Settings::i()->htaccess_mod_rewrite ? $val : 'index.php?/' . $val ) )->request( NULL, NULL, FALSE )->get();
				if ( $response->httpResponseCode != 200 and $response->httpResponseCode != 303 and ( Settings::i()->site_online OR $response->httpResponseCode != 503 ) )
				{
					throw new LogicException( 'pwa_start_url_incorrect' );
				}
			}
		}, Url::baseUrl() . ( !Settings::i()->htaccess_mod_rewrite ? 'index.php?/' : '' ), NULL, 'manifest_short_url' ) );

		$form->add( new Color( 'manifest_themecolor', ( isset( $manifestDetails['theme_color'] ) ) ? $manifestDetails['theme_color'] : NULL, FALSE, array( 'allowNoneLanguage' => 'manifest_themecolor_none', 'allowNone' => true ), NULL, NULL, NULL, 'manifest_themecolor' ) );
		$form->add( new Color( 'manifest_bgcolor', ( isset( $manifestDetails['background_color'] ) ) ? $manifestDetails['background_color'] : NULL, FALSE, array(), NULL, NULL, NULL, 'manifest_bgcolor' ) );

		if( $values = $form->values() )
		{
			$path = Url::createFromString( Url::baseUrl() )->data[ Url::COMPONENT_PATH ];
			$startUrl = !empty( $path ) ? $path : '/';

			if ( $values['manifest_custom_url_toggle'] !== FALSE and ! empty( $values['manifest_short_url'] ) )
			{
				$startUrl = '/' . trim( $values['manifest_short_url'], '/' ) . '/';

				if( !empty( $path ) )
				{
					$startUrl = '/' . trim( $path . ( !Settings::i()->htaccess_mod_rewrite ? 'index.php?/' : '' ) . ltrim( $values['manifest_short_url'], '/' ), '/' ) . '/';
				}
			}

			/* Homescreen icon is the hardest part, as we need to generate different sizes.. */
			$values = static::processApplicationIcon( $values, $homeScreen, $homeScreenMaskable );

			/* The start screen requires more different sizes so we'll rebuild that after submit */
			$rebuildStartScreen = FALSE;

			if( $values['icons_apple_startup'] AND ( !isset( $apple['original'] ) OR !$apple['original'] OR (string) $values['icons_apple_startup'] != $apple['original'] ) )
			{
				$rebuildStartScreen = TRUE;
			}

			if( ( !isset( $manifestDetails['background_color'] ) AND $values['manifest_bgcolor'] ) OR ( isset( $manifestDetails['background_color'] ) AND !$values['manifest_bgcolor'] ) OR ( isset( $manifestDetails['background_color'] ) AND $values['manifest_bgcolor'] != $manifestDetails['background_color'] ) )
			{
				if( $values['icons_apple_startup'] )
				{
					$rebuildStartScreen = TRUE;
				}
			}

			$values = static::processAppleStartupScreen( $values, $apple );

			/* We need the string value of this uploaded file as well */
			$values['icons_mask_icon'] = (string) $values['icons_mask_icon'];

			/* Finally, handle the manifest details */
			$values['manifest_details'] = array();

			if( $values['configure_manifest'] )
			{
				$values['manifest_details']['short_name']		= $values['manifest_shortname'];
				$values['manifest_details']['start_url']		= $startUrl;
				$values['manifest_details']['name']				= $values['manifest_fullname'];
				$values['manifest_details']['description']		= $values['manifest_description'];
				$values['manifest_details']['theme_color']		= $values['manifest_themecolor'];
				$values['manifest_details']['background_color']	= $values['manifest_bgcolor'];
			}

			unset( $values['configure_manifest'], $values['manifest_shortname'], $values['manifest_fullname'], $values['manifest_description'], $values['manifest_bgcolor'], $values['manifest_themecolor'], $values['manifest_custom_url_toggle'], $values['manifest_short_url'] );

			$values['manifest_details'] = json_encode( array_merge( $values['manifest_details'], [ 'cache_key' => time() ] ) );

			/* Save the settings */
			$form->saveAsSettings( $values );

			/* Clear manifest data store */
			unset( Store::i()->manifest );

			/* And log */
			Session::i()->log( 'acplogs__webapp' );

			/* And Redirect */
			if( $rebuildStartScreen === TRUE )
			{
				Output::i()->redirect( $this->url->setQueryString( 'do', 'buildStartupScreenImages' ), 'saved' );
			}
			else
			{
				Output::i()->redirect( $this->url, 'saved' );
			}
		}

		Output::i()->title		= Member::loggedIn()->language()->addToStack('menu__core_settings_webapp');
		Output::i()->output	.= Theme::i()->getTemplate( 'global' )->block( 'menu__core_settings_webapp', $form );
	}

	/**
	 * Process application icon
	 *
	 * @param	array	$values				Values from form submission
	 * @param	array	$homeScreen			Existing values, if any
	 * @param	array	$homeScreenMaskable	Existing values, if any
	 * @return	array
	 */
	public static function processApplicationIcon( array $values, array $homeScreen = array(), array $homeScreenMaskable = array() ) : array
	{
		if( ( isset( $values['icons_homescreen'] ) AND $values['icons_homescreen'] ) or ( isset( $values['icons_homescreen_maskable' ] ) AND $values['icons_homescreen_maskable'] ) )
		{
			foreach( [ 'icons_homescreen', 'icons_homescreen_maskable' ] as $type )
			{
				$setting = [];
				if( isset( $values[ $type ] ) AND $values[ $type ] )
				{
					$sizes = array(
						'android-chrome-192x192' => array(192, 192),
						'android-chrome-512x512' => array(512, 512),
						'apple-touch-icon-180x180' => array(180, 180),
					);

					$setting = array('original' => (string)$values[ $type ]);

					foreach ( $sizes as $filename => $_sizes )
					{
						if ( $type == 'icons_homescreen_maskable' )
						{
							$filename .= "-masked";

							if ( ! mb_strstr( $filename, 'android-chrome' ) )
							{
								continue;
							}
						}
						try
						{
							$image = Image::create( $values[ $type ]->contents() );

							if ( $image::exifSupported() )
							{
								$image->setExifData( $values[ $type ]->contents() );
							}

							$image->crop( $_sizes[0], $_sizes[1] );

							$setting[$filename] = array(
								'url' => (string)File::create( 'core_Icons', $filename . '.png', (string)$image, NULL, TRUE, NULL, FALSE ),
								'width' => $image->width,
								'height' => $image->height
							);
						}
						catch ( Exception $e )
						{
						}
					}
				}


				$values[ $type ] = json_encode( $setting );
			}
		}
		else
		{
			/* Delete any images that may already exist */
			foreach( $homeScreen as $key => $image )
			{
				try
				{
					File::get( 'core_Icons', ( $key == 'original' ) ? $image : $image['url'] )->delete();
				}
				catch( Exception $e ){}
			}

			foreach( $homeScreenMaskable as $key => $image )
			{
				try
				{
					File::get( 'core_Icons', ( $key == 'original' ) ? $image : $image['url'] )->delete();
				}
				catch( Exception $e ){}
			}

			$values['icons_homescreen'] = '';
		}

		return $values;
	}

	/**
	 * @brief	Minimum padding on either side of startup image (in px)
	 */
	const MINIMUM_STARTUP_IMAGE_PADDING = 50;

	/**
	 * Process Apple startup screen images
	 *
	 * @param	array	$values		Values from form submission
	 * @param	array	$apple		Existing values, if any
	 * @return	array
	 */
	public static function processAppleStartupScreen( array $values, array $apple = array() ) : array
	{
		if( $values['icons_apple_startup'] )
		{
			if( empty( $apple['original'] ) or (string) $values['icons_apple_startup'] != $apple['original'] )
			{
				$values['icons_apple_startup'] = json_encode( array( 'original' => (string) $values['icons_apple_startup'] ) );
			}
			else
			{
				$values['icons_apple_startup'] = json_encode( $apple );
			}
		}
		else
		{
			/* Delete any images that may already exist */
			foreach( $apple as $key => $image )
			{
				try
				{
					File::get( 'core_Icons', ( $key == 'original' ) ? $image : $image['url'] )->delete();
				}
				catch( Exception $e ){}
			}

			$values['icons_apple_startup'] = '';
		}

		return $values;
	}

	/**
	 * Process Apple startup screen images
	 *
	 * @return	array|null
	 */
	public function buildStartupScreenImages() : ?array
	{
		$self = $this;

		$multiRedirect = new MultipleRedirect(
			$this->url->setQueryString('do', 'buildStartupScreenImages'),
			function( $data )
			{
				/* Get the necessary data */
				$manifestDetails	= json_decode( Settings::i()->manifest_details, TRUE );
				$setting			= json_decode( Settings::i()->icons_apple_startup, TRUE ) ?? array();

				if( isset( $setting['original'] ) )
				{
					$sizes = array(
						// iPhone 12, 12 Pro, 13, 13 Pro, 14
						'apple-startup-1170x2532'			=> array( 390, 844, 3, 'portrait' ),
						// iPhone 12 Pro Max, 13 Pro Max, 14 Plus
						'apple-startup-1284x2778'			=> array( 428, 926, 3, 'portrait' ),
						// iPhone 14 Pro, iPhone 15, iPhone 15 Pro, iPhone 16
						'apple-startup-1179x2556'			=> array( 393, 852, 3, 'portrait' ),
						// iPhone 15 Plus, iPhone 15 Pro Max, iPhone 16 Plus
						'apple-startup-1290x2796'			=> array( 430, 932, 3, 'portrait' ),
						// iPhone 16 Pro
						'apple-startup-1206x2622'			=> array( 402, 874, 3, 'portrait' ),
						// iPhone 16 Pro Max
						'apple-startup-1320x2868'			=> array( 440, 956, 3, 'portrait' ),
						// iPad Air 13" + iPad Pro 12.9"
						'apple-startup-2048x2732'			=> array( 1024, 1366, 2, 'portrait' ),
						'apple-startup-2732x2048'			=> array( 1024, 1366, 2, 'landscape' ),
						// iPad Air 4+ (11" displays)
						'apple-startup-1640x2360'			=> array( 820, 1180, 2, 'portrait' ),
						'apple-startup-2360x1640'			=> array( 820, 1180, 2, 'landscape' ),
						// iPad Pro 11" (1st to 4th gen)
						'apple-startup-1668x2388'			=> array( 834, 1194, 2, 'portrait' ),
						'apple-startup-2388x1668'			=> array( 834, 1194, 2, 'landscape' ),
						// iPad Pro 10.5" and 11" (1st & 2nd gen)
						'apple-startup-1668x2224'			=> array( 834, 1112, 2, 'portrait' ),
						'apple-startup-2224x1668'			=> array( 834, 1112, 2, 'landscape' ),
						// iPad Mini 6 and 7
						'apple-startup-1488x2266'			=> array( 430, 932, 2, 'portrait' ),
						'apple-startup-2266x1488'			=> array( 430, 932, 2, 'landscape' ),
					);

					$file = File::get( 'core_Icons', $setting['original'] );
					$originalContents = $file->contents();

					$backgroundColor = ( isset( $manifestDetails['background_color'] ) ) ? str_replace( '#', '', $manifestDetails['background_color'] ) : 'FFFFFF';

					$rgb = array();

					if ( strlen( $backgroundColor ) == 3 )
					{
						$rgb[] = hexdec( substr( $backgroundColor, 0, 1 ) . substr( $backgroundColor, 0, 1 ) ); // R
						$rgb[] = hexdec( substr( $backgroundColor, 1, 1 ) . substr( $backgroundColor, 1, 1 ) ); // G
						$rgb[] = hexdec( substr( $backgroundColor, 2, 1 ) . substr( $backgroundColor, 2, 1 ) ); // B
					}
					else
					{
						$rgb[] = hexdec( substr( $backgroundColor, 0, 2 ) ); // R
						$rgb[] = hexdec( substr( $backgroundColor, 2, 2 ) ); // G
						$rgb[] = hexdec( substr( $backgroundColor, 4, 2 ) ); // B
					}

					$did = 0;

					foreach( $sizes as $filename => $_sizes )
					{
						$did++;

						if( isset( $setting[ $filename ] ) )
						{
							continue;
						}

						try
						{
							$width = ( $_sizes[3] == 'landscape' ) ? $_sizes[1] * $_sizes[2] : $_sizes[0] * $_sizes[2];
							$height = ( $_sizes[3] == 'portrait' ) ? $_sizes[1] * $_sizes[2] : $_sizes[0] * $_sizes[2];

							$logoImage	= Image::create( $originalContents );
							$canvas		= Image::newImageCanvas( $width, $height, $rgb );

							$logoImage->resizeToMax( $width - static::MINIMUM_STARTUP_IMAGE_PADDING, $height - static::MINIMUM_STARTUP_IMAGE_PADDING );

							$xPos = ( $width - $logoImage->width ) / 2;
							$yPos = ( $height - $logoImage->height ) / 2;

							$canvas->impose( $logoImage, $xPos, $yPos );

							if( $canvas::exifSupported() )
							{
								$canvas->setExifData( $originalContents );
							}

							$setting[ $filename ] = array(
								'url' 			=> (string) File::create( 'core_Icons', $filename . '.png', (string) $canvas, NULL, TRUE, NULL, FALSE ),
								'width'			=> $canvas->width,
								'height'		=> $canvas->height,
								'density'		=> $_sizes[2],
								'orientation'	=> $_sizes[3],
							);
						}
						catch ( Exception $e )
						{
							Log::log( $e, 'apple-startup-image' );
						}

						break;
					}

					/* Are we done? */
					if( $did == count( $sizes ) )
					{
						return NULL;
					}

					Settings::i()->changeValues( array( 'icons_apple_startup' => json_encode( $setting ) ) );
				}

				return array( $data, Member::loggedIn()->language()->addToStack('build_start_images_title'), ( $did ) ? round( ( 100 / count( $sizes ) * $did ), 2 ) : 0 );
			},
			function() use( $self )
			{
				$manifest = json_decode( Settings::i()->manifest_details, TRUE );
				$manifest['cache_key'] = time();

				Settings::i()->changeValues( [ 'manifest_details' => json_encode( $manifest ) ] );
				Output::i()->redirect( $self->url, 'completed' );
			}
		);

		Output::i()->title = Member::loggedIn()->language()->addToStack('build_start_images_title');
		Output::i()->output = $multiRedirect;
		return null;
	}
}