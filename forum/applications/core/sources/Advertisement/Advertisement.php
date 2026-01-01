<?php
/**
 * @brief		Advertisements Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Sept 2013
 */

namespace IPS\core;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Content;
use IPS\Db;
use IPS\Dispatcher;
use IPS\Email;
use IPS\Extensions\AdvertisementLocationsAbstract;
use IPS\File;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Node;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Select;
use IPS\Lang;
use IPS\Member;
use IPS\Member\Group;
use IPS\Output;
use IPS\Patterns\ActiveRecord;
use IPS\Redis;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use OutOfRangeException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Advertisements Model
 */
class Advertisement extends ActiveRecord
{
	/**
	 * @brief	HTML ad
	 */
	const AD_HTML	= 1;

	/**
	 * @brief	Images ad
	 */
	const AD_IMAGES	= 2;

	/**
	 * @brief	Email ad
	 */
	const AD_EMAIL	= 3;

	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_advertisements';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'ad_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;
		
	/**
	 * @brief	Advertisements loaded during this request (used to update impression count)
	 * @see		static::updateImpressions()
	 */
	public static array $advertisementIds	= array();

	/**
	 * @brief	Advertisements sent via email (used to update impression count)
	 * @see		static::updateEmailImpressions()
	 */
	public static array $advertisementIdsEmail = array();

	/**
	 * @brief	Stored advertisements we can display on this page
	 */
	protected static ?array $advertisements = NULL;

	/**
	 * @brief	Stored advertisements we can send in emails
	 */
	protected static ?array $emailAdvertisements = NULL;

	/**
	 * @brief	Stored advertisements for widgets
	 */
	protected static ?array $widgetAdvertisements = NULL;

	/**
	 * @brief	List all default location codes
	 */
	public static array $defaultLocations = [
		'ad_global_header'	=> array( 'ad_apps' ),
		'ad_global_footer'	=> array( 'ad_apps' ),
		'ad_sidebar'		=> array( 'ad_apps' )
	];

	/**
	 * Return an array of form elements that will handle location-based settings
	 *
	 * @param array $currentValues
	 * @param array $currentLocations
	 * @return array
	 */
	public static function locationFields( array $currentValues, array $currentLocations ) : array
	{
		$settingFields = [];
		$applications = [];
		$appToggles = [];
		$nodeFields = [];
		$defaultLocations = static::$defaultLocations;
		foreach( Application::enabledApplications() as $app )
		{
			$applications[ $app->directory ] = $app->_title;
			foreach( $app->extensions( 'core', 'ContentRouter' ) as $router )
			{
				if( !isset( $appToggles[ $app->directory ] ) )
				{
					$appToggles[ $app->directory ] = [];
				}

				foreach( $router->classes as $itemClass )
				{
					if( isset( $itemClass::$containerNodeClass ) )
					{
						$nodeClass = $itemClass::$containerNodeClass;

						/* Skip nodes that can be created by users, such as blogs */
						if( !empty( $nodeClass::$ownerTypes ) )
						{
							continue;
						}

						$key = str_replace( '\\', '_', $nodeClass );
						$field = new Node( $key, $currentValues[ $key ] ?? 0, false, [
							'class' => $nodeClass,
							'multiple' => true,
							'zeroVal' => 'all',
							'nodeGroups' => true
						], null, null, null, $key );
						$field->label = Member::loggedIn()->language()->addToStack( $nodeClass::$nodeTitle );
						$nodeFields[] = $field;
						$appToggles[ $app->directory ][] = $key;
					}
				}
			}
		}

		/* Add a field to restrict by app */
		$settingFields['ad_apps'] = new Select( 'ad_apps', $currentValues['ad_apps'] ?? -1, false, array(
			'options' => $applications,
			'multiple' => true,
			'unlimitedLang' => 'all',
			'toggles' => $appToggles
		), null, null, null, 'ad_apps' );

		foreach( $nodeFields as $field )
		{
			$settingFields[ $field->htmlId ] = $field;
		}

		/* Now grab ad location extensions */
		foreach ( Application::allExtensions( 'core', 'AdvertisementLocations', FALSE, 'core' ) as $key => $extension )
		{
			/* @var AdvertisementLocationsAbstract $extension */
			$result	= $extension->getSettings( $currentValues );

			$defaultLocations	= array_merge( $defaultLocations, $result['locations'] );

			/* If this is a list view, add setting fields for interaval and repeat */
			if( $extension::$listView )
			{
				$settingFields['ad_view_type'] = new Radio( 'ad_view_type', $currentValues['ad_view_type'] ?? null, true, [
					'options' => [
						'interval' => 'ad_view_type__interval',
						'fixed' => 'ad_view_type__fixed'
					],
					'toggles' => [
						'interval' => [ 'ad_view_number', 'ad_view_repeat' ],
						'fixed' => [ 'ad_view_position' ]
					]
				], null, null, null, 'ad_view_type' );

				$settingFields['ad_view_number'] = new Number( 'ad_view_number', ( isset( $currentValues['ad_view_number'] ) AND $currentValues['ad_view_number'] ) ? $currentValues['ad_view_number'] : 1, null, array( 'min' => 1 ), NULL, NULL, Member::loggedIn()->language()->addToStack('ad_view_number_suffix'), 'ad_view_number' );

				$settingFields['ad_view_repeat'] = new Number( 'ad_view_repeat', $currentValues['ad_view_repeat'] ?? 1, FALSE, array( 'unlimited' => -1 ), NULL, NULL, Member::loggedIn()->language()->addToStack('ad_view_repeat_suffix'), 'ad_view_repeat' );

				$settingFields['ad_view_position'] = new Number( 'ad_view_position', $currentValues['ad_view_position'] ?? 1, null, id: 'ad_view_position' );

				foreach( $result['locations'] as $location => $toggles )
				{
					$defaultLocations[ $location ][] = 'ad_view_type';
				}
			}

			if( isset( $result['settings'] ) )
			{
				$settingFields = $settingFields + $result['settings'];
			}
		}

		/* Make location optional so that you can have an active ad for widgets only */
		$locationField = new CheckboxSet( 'ad_location',
				array_intersect( array_keys( $defaultLocations ), $currentLocations ),
				false,
				array(
					'options'	=> array_combine( array_keys( $defaultLocations ), array_keys( $defaultLocations ) ),
					'toggles'	=> $defaultLocations,
					'noDefault' => true
				),
				NULL,
				NULL,
				NULL,
				'ad_location'
			);

		return [ 'ad_location' => $locationField ] + $settingFields;
	}

	/**
	 * Format the location settings for saving
	 *
	 * @param array $values
	 * @return array
	 */
	public static function processLocationFields( array $values ) : array
	{
		/* Any additional settings to save? */
		$additionalSettings	= array(
			'ad_apps' => ( isset( $values['ad_apps'] ) AND is_array( $values['ad_apps'] ) ) ? array_values( $values['ad_apps'] ) : 0
		);

		if( is_array( $additionalSettings['ad_apps'] ) )
		{
			foreach( $additionalSettings['ad_apps'] as $app )
			{
				foreach( Application::load( $app )->extensions( 'core', 'ContentRouter' ) as $router )
				{
					foreach( $router->classes as $itemClass )
					{
						if( isset( $itemClass::$containerNodeClass ) )
						{
							$key = str_replace( '\\', '_', $itemClass::$containerNodeClass );
							if( !empty( $values[ $key ] ) and is_array( $values[ $key ] ) )
							{
								$additionalSettings[ $key ] = array_keys( $values[ $key ] );
							}
						}
					}
				}
			}
		}

		foreach ( Application::allExtensions( 'core', 'AdvertisementLocations', FALSE, 'core' ) as $key => $extension )
		{
			if( $extension::$listView )
			{
				$additionalSettings['ad_view_type'] = $values['ad_view_type'] ?? 'interval';
				if( $additionalSettings['ad_view_type'] == 'fixed' )
				{
					$additionalSettings['ad_view_position'] = $values['ad_view_position'] ?? 1;
				}
				else
				{
					$additionalSettings['ad_view_number'] = $values['ad_view_number'] ?? 1;
					$additionalSettings['ad_view_repeat'] = $values['ad_view_repeat'] ?? 0;
				}
			}

			$settings	= $extension->parseSettings( $values );

			$additionalSettings	= array_merge( $additionalSettings, $settings );
		}

		return $additionalSettings;
	}

	/**
	 * Array of form elements for email advertisement configuration
	 *
	 * @param array $currentValues
	 * @return array
	 */
	public static function emailFields( array $currentValues ) : array
	{
		$fields = [];

		/* Container restrictions for email ads */
		$containerClasses = array();
		$containerToggles = array();

		foreach( Content::routedClasses( FALSE, FALSE, TRUE ) as $contentItemClass )
		{
			if( isset( $contentItemClass::$containerNodeClass ) AND $contentItemClass::$containerNodeClass AND !isset( $containerClasses[ $contentItemClass::$containerNodeClass ] ) )
			{
				$containerClass = $contentItemClass::$containerNodeClass;

				$containerClasses[ $containerClass ] = $contentItemClass::$title . '_pl';
				$containerToggles[ $containerClass ] = array( 'node_' . md5( $containerClass ) );
			}
		}

		if( count( $containerClasses ) )
		{
			$fields['_ad_email_container'] = new Select( '_ad_email_container', $currentValues['email_container'] ?? '*', FALSE, array( 'options' => $containerClasses, 'multiple' => FALSE, 'unlimited' => '*', 'unlimitedLang' => 'unrestricted', 'toggles' => $containerToggles ), NULL, NULL, NULL, '_ad_email_container' );

			foreach( $containerClasses as $classname => $lang )
			{
				$containerField = new Node( 'ad_node_' . md5( $classname ), $currentValues['email_node'] ?? 0, FALSE, array( 'class' => $classname, 'zeroVal' => 'any', 'multiple' => TRUE, 'forceOwner' => FALSE ), NULL, NULL, NULL, 'node_' . md5( $classname ) );
				$containerField->label = Member::loggedIn()->language()->addToStack( $classname::$nodeTitle );
				$fields[ 'node_' . md5( $classname ) ] = $containerField;
			}
		}

		return $fields;
	}

	/**
	 * Format email fields for saving
	 *
	 * @param array $values
	 * @return array
	 */
	public static function processEmailFields( array $values ) : array
	{
		return [
			'email_container' => $values['_ad_email_container'],
			'email_node' => ( $values['_ad_email_container'] != '*' ) ? ( ( $values['ad_node_' . md5( $values['_ad_email_container'] ) ] == 0 ) ? 0 : array_keys( $values['ad_node_' . md5( $values['_ad_email_container'] ) ] ) ) : 0
		];
	}

	/**
	 * Fetch advertisements and return the appropriate one to display
	 *
	 * @param	string		$location	Advertisement location
	 * @param 	int|null	$position	Position within a list
	 * @return    Advertisement|NULL
	 */
	public static function loadByLocation( string $location, ?int $position=null ) : ?static
	{
		/* If we know there are no ads, we don't need to bother */
		if ( !Settings::i()->ads_exist )
		{
			return NULL;
		}
		
		/* Fetch our advertisements, if we haven't already done so */
		if( static::$advertisements  === NULL )
		{
			static::$advertisements = array();

			$where[] = array( "ad_type!=?", static::AD_EMAIL );
			$where[] = array( "ad_active=1" );
			$where[] = array( "ad_start<?", time() );
			$where[] = array( "(ad_end=0 OR ad_end>?)", time() );

			if ( Dispatcher::hasInstance() and ( !isset( Dispatcher::i()->dispatcherController ) or !Dispatcher::i()->dispatcherController->isContentPage ) )
			{
				$where[] = array( 'ad_nocontent_page_output=?', 1 );
			}

			foreach ( Db::i()->select( '*', 'core_advertisements', $where ) as $row )
			{
				foreach ( explode( ',', $row['ad_location'] ) as $_location )
				{
					static::$advertisements[ $_location ][] = static::constructFromData( $row );
				}
			}

			foreach ( static::$advertisements as $adLocation => $ads )
			{
				foreach ( $ads as $index => $ad )
				{
					/* Weed out any we don't see due to our group. This is done after loading the advertisements so that the cache can be properly primed regardless of group. Note that $ad->exempt, is, confusingly who to SHOW to, not who is exempt */
					if ( !empty( $ad->exempt ) and $ad->exempt != '*' )
					{
						$groupsToHideFrom = array_diff( array_keys( Group::groups() ), json_decode( $ad->exempt, TRUE ) );

						if ( Member::loggedIn()->inGroup( $groupsToHideFrom ) )
						{
							unset( static::$advertisements[ $adLocation ][ $index ] );
							continue;
						}
					}

					/* Weed out any ads that we don't see due to the application settings */
					if ( array_key_exists( $adLocation, static::$defaultLocations ) and isset( $ad->_additional_settings['ad_apps'] ) and ( is_array( $ad->_additional_settings['ad_apps'] ) and count( $ad->_additional_settings['ad_apps'] ) ) and $ad->_additional_settings != 0 )
					{
						if ( !in_array( Dispatcher::i()->application->directory, $ad->_additional_settings['ad_apps'] ) )
						{
							unset( static::$advertisements[ $adLocation ][ $index ] );
							continue;
						}

						/* Check if we have a content class defined */
						if( isset( Output::i()->bodyAttributes['contentClass'] ) )
						{
							$contentId = Request::i()->id ?? 0;
							$class = Output::i()->bodyAttributes['contentClass'];
							if( isset( $class::$containerNodeClass ) )
							{
								if( isset( Request::i()->id ) )
								{
									try
									{
										$item = $class::load( Request::i()->id );
										$contentId = $item->mapped( 'container' );
									}
									catch( OutOfRangeException ){}
								}

								$class = $class::$containerNodeClass;
							}

							$key = str_replace( '\\', '_', $class );
							if( isset( $ad->_additional_settings[ $key ] ) and is_array( $ad->_additional_settings[ $key ] ) and !in_array( $contentId, $ad->_additional_settings[ $key ] ) )
							{
								unset( static::$advertisements[ $adLocation ][ $index ] );
							}
						}
						else
						{
							/* If we defined specific nodes, and we are on a general page (e.g. an index page), skip this ad */
							foreach( $ad->_additional_settings as $k => $v )
							{
								if( str_starts_with( $k, 'IPS_' . Dispatcher::i()->application->directory ) )
								{
									unset( static::$advertisements[ $adLocation ][ $index ] );
								}
							}
						}
					}
				}

				if( !array_key_exists( $adLocation, static::$defaultLocations ) )
				{
					foreach ( $ads as $index => $ad )
					{
						/* Remove advertisements that can't be shown based on settings */
						if ( $extension = static::loadExtension( $location ) )
						{
							if ( !$extension->canShow( $ad, $location ) )
							{
								unset( static::$advertisements[ $adLocation ][ $index ] );
							}
						}
					}
				}
			}
		}

		/* No advertisements? Just return then */
		if( !count( static::$advertisements ) OR !isset( static::$advertisements[ $location ] ) OR !count( static::$advertisements[ $location ] ) )
		{
			return NULL;
		}

		return static::selectAdvertisement( static::$advertisements[ $location ], $position );
	}

	/**
	 * Store loaded extensions
	 * @var array
	 */
	static array $_loadedExtensions = [];

	/**
	 * Find the Extension that handles this ad location
	 *
	 * @param string $location
	 * @return AdvertisementLocationsAbstract|null
	 */
	protected static function loadExtension( string $location ) : ?AdvertisementLocationsAbstract
	{
		/* Check for a loaded extension */
		if( isset( static::$_loadedExtensions[ $location ] ) )
		{
			return static::$_loadedExtensions[ $location ];
		}

		foreach( Application::allExtensions( 'core', 'AdvertisementLocations' ) as $ext )
		{
			/* @var AdvertisementLocationsAbstract $ext */
			$extensionSettings = $ext->getSettings( array() );
			if( array_key_exists( $location, $extensionSettings['locations'] ) )
			{
				return static::$_loadedExtensions[ $location ] = $ext;
			}
		}

		return null;
	}

	/**
	 * Fetch advertisements for a particular widget
	 *
	 * @param	array	$additionalWhere
	 * @param	array|null	$ids	Specify the IDs to choose from
	 * @param 	int|null	$limit
	 * @return    array|NULL
	 */
	public static function loadForWidget( array $additionalWhere=array(), ?array $ids=null, ?int $limit=null ) : ?array
	{
		/* If we know there are no ads, we don't need to bother */
		if ( !Settings::i()->ads_exist )
		{
			return NULL;
		}

		/* Fetch our advertisements, if we haven't already done so */
		if( static::$widgetAdvertisements  === NULL )
		{
			static::$widgetAdvertisements = array();

			$where = [
				[ "ad_type!=?", static::AD_EMAIL ],
				[ "ad_active=?", 1 ],
				[ "ad_start<?", time() ],
				[ "(ad_end=0 OR ad_end>?)", time() ]
			];

			foreach( $additionalWhere as $clause )
			{
				$where[] = $clause;
			}

			foreach( Db::i()->select( '*' ,'core_advertisements', $where ) as $row )
			{
				static::$widgetAdvertisements[] = static::constructFromData( $row );
			}
		}

		$widgetsToUse = [];
		foreach( static::$widgetAdvertisements as $index => $ad )
		{
			/* Weed out any we don't see due to our group. This is done after loading the advertisements so that the cache can be properly primed regardless of group. Note that $ad->exempt, is, confusingly who to SHOW to, not who is exempt */
			if ( ! empty( $ad->exempt ) and $ad->exempt != '*' )
			{
				$groupsToHideFrom = array_diff( array_keys( Group::groups() ), json_decode( $ad->exempt, TRUE ) );

				if ( Member::loggedIn()->inGroup( $groupsToHideFrom ) )
				{
					continue;
				}
			}

			/* If the widget is not in our specified list, remove it */
			if( is_array( $ids ) and count( $ids ) and !in_array( $ad->id, $ids ) )
			{
				continue;
			}

			$widgetsToUse[] = $ad;
		}

		/* No advertisements? Just return then */
		if( !count( $widgetsToUse ) )
		{
			return NULL;
		}

		/* Shuffle so we display randomly */
		$limit = $limit ?? 1;
		if( count( $widgetsToUse ) > $limit )
		{
			shuffle( $widgetsToUse );
		}

		return array_slice( $widgetsToUse, 0, $limit );
	}

	/**
	 * Fetch advertisements for emails and return the appropriate one to display
	 *
	 * @param	array|null	$container	The container that spawned the email, or NULL
	 * @return    Advertisement|NULL
	 */
	public static function loadForEmail( ?array $container=NULL ) : ?static
	{
		/* If we know there are no ads, we don't need to bother */
		if ( !Settings::i()->ads_exist )
		{
			return NULL;
		}
		
		/* Fetch our advertisements, if we haven't already done so */
		if( static::$emailAdvertisements  === NULL )
		{
			static::$emailAdvertisements = array();

			foreach( Db::i()->select( '*' ,'core_advertisements', array( "ad_type=? AND ad_active=1 AND ad_start < ? AND ( ad_end=0 OR ad_end > ? )", static::AD_EMAIL, time(), time() ) ) as $row )
			{
				foreach ( explode( ',', $row['ad_location'] ) as $_location )
				{
					static::$emailAdvertisements[] = static::constructFromData( $row );
				}
			}
		}

		/* Whittle down the advertisements to use based on container limitations */
		$adsToCheckFrom = array();

		/* First see if we have any for this specific configuration */
		if( $container !== NULL )
		{
			foreach( static::$emailAdvertisements as $advertisement )
			{
				if( isset( $advertisement->_additional_settings['email_container'] ) AND isset( $advertisement->_additional_settings['email_node'] ) )
				{
					if( $advertisement->_additional_settings['email_container'] == $container['className'] AND $advertisement->_additional_settings['email_node'] == $container['id'] )
					{
						$adsToCheckFrom[] = $advertisement;
					}
				}
			}
		}

		/* If we didn't find any, then look for generic ones for the node class */
		if( $container !== NULL )
		{
			if( !count( $adsToCheckFrom ) )
			{
				foreach( static::$emailAdvertisements as $advertisement )
				{
					if( isset( $advertisement->_additional_settings['email_container'] ) AND ( !isset( $advertisement->_additional_settings['email_node'] ) OR !$advertisement->_additional_settings['email_node'] ) )
					{
						if( $advertisement->_additional_settings['email_container'] == $container['className'] )
						{
							$adsToCheckFrom[] = $advertisement;
						}
					}
				}
			}
		}

		/* If we still don't have any, look for generic ones allowed in all emails */
		if( !count( $adsToCheckFrom ) )
		{
			foreach( static::$emailAdvertisements as $advertisement )
			{
				if( !isset( $advertisement->_additional_settings['email_container'] ) OR $advertisement->_additional_settings['email_container'] == '*' )
				{
					$adsToCheckFrom[] = $advertisement;
				}
			}
		}

		/* No advertisements? Just return then */
		if( !count( $adsToCheckFrom ) )
		{
			return NULL;
		}

		return static::selectAdvertisement( $adsToCheckFrom );
	}

	/**
	 * @brief	Track positioning of each ad in a listing view
	 * @var array
	 */
	protected static array $listingView = [];

	/**
	 * Select an advertisement from an array and return it
	 *
	 * @param	array		$ads	Array of advertisements to select from
	 * @param int|null $position Position within a list
	 * @return	static|null
	 */
	static protected function selectAdvertisement( array $ads, ?int $position=null ) : ?static
	{
		/* Reset so we don't throw an error */
		$ads = array_values( $ads );

		/* If we have more than one ad, sort them according to the circulation settings */
		if( count( $ads ) > 1 )
		{
			/* Figure out which one to show you */
			switch ( Settings::i()->ads_circulation )
			{
				case 'random':
					shuffle( $ads );
					break;

				case 'newest':
					usort( $ads, function ( $a, $b )
					{
						return strcmp( $a->start, $b->start );
					} );
					break;

				case 'oldest':
					usort( $ads, function ( $a, $b )
					{
						return strcmp( $b->start, $a->start );
					} );
					break;

				case 'least':
					usort( $ads, function ( $a, $b )
					{
						if ( $a->impressions == $b->impressions )
						{
							return 0;
						}

						return ( $a->impressions < $b->impressions ) ? -1 : 1;
					} );
					break;
			}
		}

		/* Loop through the ads and find one that matches, based on positions and other settings */
		foreach( $ads as $ad )
		{
			/* @var Advertisement $ad */
			/* If we have no position specified, just grab the first one and stop */
			if( $position === null )
			{
				$advertisement = $ad;
				break;
			}

			/* Fixed position ads have a set position and do not repeat */
			if( isset( $ad->_additional_settings['ad_view_type'] ) and $ad->_additional_settings['ad_view_type'] == 'fixed' )
			{
				$indexNumber = $ad->_additional_settings['ad_view_position'] ?? 1;
				$repeat = 1;

				/* Ignore this entirely if we are not in the correct position */
				if( $position != $indexNumber )
				{
					continue;
				}
			}
			else
			{
				$indexNumber = $ad->_additional_settings[ 'ad_view_number' ] ?? false;
				$repeat = $ad->_additional_settings[ 'ad_view_repeat' ] ?? false;
			}

			if( $indexNumber !== false )
			{
				/* Figure out the last position it was shown */
				if( empty( static::$listingView[ $ad->id ] ) )
				{
					static::$listingView[ $ad->id ] = [];
					$lastShown = 0;
				}
				else
				{
					$lastIndex = count( static::$listingView[ $ad->id ] ) - 1;
					$lastShown = static::$listingView[ $ad->id ][ $lastIndex ];
				}

				/* Total times it was shown */
				$adsShown = count( static::$listingView[ $ad->id ] );

				/* Check the position and see if it's a match.
				We use >= instead of == because of circulation settings; we might not have
				an exact match to the interval. */
				if( $position - $lastShown >= $indexNumber )
				{
					/* Did we already show it the maximum times? */
					if( $repeat !== false )
					{
						if( $repeat === -1 OR $repeat > $adsShown )
						{
							$advertisement = $ad;
							break;
						}
					}
					else
					{
						$advertisement = $ad;
						break;
					}
				}
			}
		}

		/* Store the position so that we can track it for the next round */
		if( isset( $advertisement ) and $position !== null )
		{
			static::$listingView[ $advertisement->id ][] = $position;
		}

		return $advertisement ?? null;
	}

	/**
	 * Convert the advertisement to an HTML string
	 *
	 * @param	string				$emailType	html or plaintext email advertisement
	 * @param	Email|NULL		$email		For an email advertisement, this will be the email object, otherwise NULL
	 * @return	string
	 */
	public function toString( string $emailType='html', ?Email $email=NULL ) : string
	{
		/* Showing HTML or an image? */
		if( $this->type == static::AD_HTML )
		{
			if( Request::i()->isSecure() AND $this->html_https_set )
			{
				$result	= $this->html_https;
			}
			else
			{
				$result	= $this->html;
			}
		}
		elseif( $this->type == static::AD_IMAGES )
		{
			$result	= Theme::i()->getTemplate( 'global', 'core', 'global' )->advertisementImage( $this );
		}
		elseif( $this->type == static::AD_EMAIL )
		{
			$result = Email::template( 'core', 'advertisement', $emailType, array( $this, $email ) );
		}

		/* Did we just hit the maximum impression count? If so, disable and then clear the cache so it will rebuild next time. */
		if( $this->maximum_unit == 'i' AND $this->maximum_value > -1 AND $this->impressions + 1 >= $this->maximum_value )
		{
			$this->active	= 0;
			$this->save();
			
			if ( !Db::i()->select( 'COUNT(*)', 'core_advertisements', 'ad_active=1' )->first() )
			{
				Settings::i()->changeValues( array( 'ads_exist' => 0 ) );
			}			
		}

		/* Store the id so we can update impression count and return the ad */
		if( $this->type == static::AD_EMAIL )
		{
			static::$advertisementIdsEmail[] = $this->id;
		}
		else
		{
			static::$advertisementIds[]	= $this->id;
		}
		
		return $result ?? '';
	}

	/**
	 * Convert the advertisement to an HTML string
	 *
	 * @return	string
	 */
	public function __toString()
	{
		return $this->toString();
	}

	/**
	 * Get images
	 *
	 * @return	array
	 */
	public function get__images() : array
	{
		if( !isset( $this->_data['_images'] ) )
		{
			$this->_data['_images']	= $this->_data['images'] ? json_decode( $this->_data['images'], TRUE ) : array();
		}

		return $this->_data['_images'];
	}
	
	/**
	 * Get additional settings
	 *
	 * @return	array
	 */
	public function get__additional_settings() : array
	{
		if( !isset( $this->_data['_additional_settings'] ) )
		{
			$this->_data['_additional_settings'] = $this->_data['additional_settings'] ? json_decode( $this->_data['additional_settings'], TRUE ) : array();
		}

		return $this->_data['_additional_settings'];
	}

	/**
	 * Get the file system storage extension
	 *
	 * @return string
	 */
	public function storageExtension() : string
	{
		if ( $this->member )
		{
			return 'nexus_Ads';
		}
		else
		{
			return 'core_Advertisements';
		}
	}
	
	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		/* If we have images, delete them */
		if( count( $this->_images ) )
		{
			File::get( $this->storageExtension(), $this->_images['large'] )->delete();

			if( isset( $this->_images['small'] ) )
			{
				File::get( $this->storageExtension(), $this->_images['small'] )->delete();
			}

			if( isset( $this->_images['medium'] ) )
			{
				File::get( $this->storageExtension(), $this->_images['medium'] )->delete();
			}
		}

		/* Delete the translatable title */
		Lang::deleteCustom( 'core', "core_advert_{$this->id}" );
		
		/* Delete */
		parent::delete();
		
		/* Make sure we still have active ads */
		if ( !Db::i()->select( 'COUNT(*)', 'core_advertisements', 'ad_active=1' )->first() )
		{
			Settings::i()->changeValues( array( 'ads_exist' => 0 ) );
		}
	}

	/**
	 * Update ad impressions for advertisements loaded
	 *
	 * @return	void
	 */
	public static function updateImpressions() : void
	{
		if( count( static::$advertisementIds ) )
		{
			static::updateCounter( static::$advertisementIds );

			/* Reset in case execution continues and more ads are shown */
			static::$advertisementIds = array();
		}
	}

	/**
	 * Update ad impressions for advertisements sent in emails
	 *
	 * @param	int		$impressions	Number of impressions (may be more than one if mergeAndSend() was called)
	 * @return	void
	 */
	public static function updateEmailImpressions( int $impressions=1 ) : void
	{
		if( count( static::$advertisementIdsEmail ) )
		{
			static::updateCounter( static::$advertisementIdsEmail, $impressions );

			/* Reset in case execution continues and more ads are sent */
			static::$advertisementIdsEmail = array();
		}
	}

	/**
	 * Update the advert impression counters
	 *
	 * @param array $ids	Array of IDs
	 * @param int $by		Number to increment by
	 * @return void
	 */
	protected static function updateCounter( array $ids, int $by=1 ) : void
	{
		$countUpdated = false;
		if ( Redis::isEnabled() )
		{
			foreach( $ids as $id )
			{
				try
				{
					Redis::i()->zIncrBy( 'advert_impressions', $by, $id );
					$countUpdated = true;
				}
				catch ( Exception $e )
				{
				}
			}
		}

		if ( ! $countUpdated )
		{
			Db::i()->update( 'core_advertisements', "ad_impressions=ad_impressions+" . $by . ", ad_daily_impressions=ad_daily_impressions+" . $by, "ad_id IN(" . implode( ',', $ids ) . ")" );
		}
	}
}