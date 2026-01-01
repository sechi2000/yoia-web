<?php
/**
 * @brief		Advertisement locations extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		16 Jul 2014
 */

namespace IPS\forums\extensions\core\AdvertisementLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Advertisement;
use IPS\Extensions\AdvertisementLocationsAbstract;
use IPS\forums\Topic;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Advertisement locations extension
 */
class TopicView extends AdvertisementLocationsAbstract
{
	/**
	 * If this ad location is used in a list view, we allow the admin
	 * to define intervals or fixed positions within the list
	 *
	 * @var bool
	 */
	public static bool $listView = true;

	/** 
	 * Get the locations and the additional settings
	 *
	 * @param	array	$settings	Current setting values
	 * @return	array	Array with two elements: 'locations' which should have keys as the location keys and values as the fields to toggle, and 'settings' which are additional fields to add to the form
	 */
	public function getSettings( array $settings ): array
	{
		return array( 'locations' => array( 'ad_topic_view' => array( 'IPS_forums_Forum' ) ), 'settings' => array() );
	}

	/** 
	 * Return an array of setting values to store
	 *
	 * @param	array	$values	Values from the form submission
	 * @return	array 	Array of setting key => value to store
	 */
	public function parseSettings( array $values ): array
	{
		return array();
	}

	/**
	 * Check if the advertisement can be displayed, based on settings
	 *
	 * @param Advertisement $advertisement
	 * @param string $location
	 * @return bool
	 */
	public function canShow( Advertisement $advertisement, string $location ) : bool
	{
		if( isset( $advertisement->_additional_settings['IPS_forums_Forum'] ) )
		{
			try
			{
				return in_array( Topic::load( Request::i()->id )->forum_id, $advertisement->_additional_settings['IPS_forums_Forum'] );
			}
			catch( OutOfRangeException )
			{
				return false;
			}
		}

		return TRUE;
	}
}