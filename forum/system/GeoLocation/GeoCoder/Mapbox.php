<?php
/**
 * @brief		Mapbox GeoCoder
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		11 Dec 2017
 */

namespace IPS\GeoLocation\GeoCoder;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadFunctionCallException;
use BadMethodCallException;
use IPS\GeoLocation;
use IPS\GeoLocation\GeoCoder;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Settings;
use RuntimeException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Mapbox GeoCoder class
 */
class Mapbox extends GeoCoder
{
	/**
	 * Get by location string
	 *
	 * @param string $location
	 * @return GeoLocation
	 * @throws BadFunctionCallException
	 */
	public static function decodeLocation( string $location ): GeoLocation
	{
		if ( Settings::i()->mapbox AND Settings::i()->mapbox_api_key )
		{
			$data = Url::external( "https://api.mapbox.com/geocoding/v5/mapbox.places/{$location}.json" )->setQueryString( array(
				'access_token'		=> Settings::i()->mapbox_api_key,
			) )->request()->get()->decodeJson();

			$obj = new GeoLocation;

			if( !empty( $data['features'] ) )
			{
				if( !empty( $data['features'][0]['geometry'] ) )
				{
					$obj->long = $data['features'][0]['geometry']['coordinates'][0];
					$obj->lat = $data['features'][0]['geometry']['coordinates'][1];
				}

				$obj->placeName = $data['features'][0]['place_name'];

				/* If we are at address level, the address will not be in a property */
				if( $data['features'][0]['place_type'][0] == 'address' )
				{
					$obj->addressLines = [ $data['features'][0]['address'] . ' ' . $data['features'][0]['text'] ];
				}

				foreach( $data['features'][0]['context'] as $property )
				{
					$propertyType = mb_substr( $property['id'], 0, mb_strpos( $property['id'], '.' ) );
					switch( $propertyType )
					{
						case 'locality':
							$obj->city = $property['text'];
							break;

						case 'postcode':
							$obj->postalCode = $property['text'];
							break;

						case 'country':
							$obj->country = $property['text'];
							break;

						case 'region':
							$obj->region = $property['text'];
							break;

						case 'district':
							$obj->county = $property['text'];
							break;
					}
				}
			}

			return $obj;
		}
		else
		{
			throw new BadFunctionCallException;
		}
	}

	/**
	 * Get by latitude and longitude
	 *
	 * @param float $lat	Latitude
	 * @param float $long	Longitude
	 * @return	GeoLocation
	 * @throws	BadFunctionCallException
	 * @throws	Exception
	 */
	public static function decodeLatLong( float $lat, float $long ): GeoLocation
	{
		if ( Settings::i()->mapbox AND Settings::i()->mapbox_api_key )
		{
			$location = $long . ',' . $lat;
			$data = Url::external( "https://api.mapbox.com/geocoding/v5/mapbox.places/{$location}.json" )->setQueryString( array(
				'access_token'		=> Settings::i()->mapbox_api_key,
			) )->request()->get()->decodeJson();
			
			$obj = new GeoLocation;
			$obj->lat			= $lat;
			$obj->long			= $long;

			if( !empty( $data['features'] ) )
			{
				$obj->placeName = $data['features'][0]['place_name'];

				/* If we are at address level, the address will not be in a property */
				if( $data['features'][0]['place_type'][0] == 'address' )
				{
					$obj->addressLines = [ $data['features'][0]['address'] . ' ' . $data['features'][0]['text'] ];
				}

				foreach( $data['features'][0]['context'] as $property )
				{
					$propertyType = mb_substr( $property['id'], 0, mb_strpos( $property['id'], '.' ) );
					switch( $propertyType )
					{
						case 'locality':
							$obj->city = $property['text'];
							break;

						case 'postcode':
							$obj->postalCode = $property['text'];
							break;

						case 'country':
							$obj->country = $property['text'];
							break;

						case 'region':
							$obj->region = $property['text'];
							break;

						case 'district':
							$obj->county = $property['text'];
							break;
					}
				}
			}

			return $obj;
		}
		else
		{
			throw new BadFunctionCallException;
		}
	}

	/**
	 * Get the latitude and longitude for the current object. Address must be set.
	 *
	 * @param	GeoLocation	$geoLocation	Geolocation object
	 * @param bool $setAddress		Whether or not to update the address information from the GeoCoder service
	 * @return	void
	 * @throws	BadMethodCallException
	 */
	public function setLatLong( GeoLocation &$geoLocation, bool $setAddress=FALSE ) : void
	{
		if ( Settings::i()->mapbox AND Settings::i()->mapbox_api_key AND $geoLocation->toString() )
		{
			try
			{
				$data = Url::external( "https://api.mapbox.com/geocoding/v5/mapbox.places/" . urlencode( html_entity_decode( $geoLocation->toString() ) ) . ".json" )->setQueryString( array(
					'access_token'		=> Settings::i()->mapbox_api_key,
				) )->request()->get()->decodeJson();
			}
			catch( RuntimeException $e )
			{
				return;
			}

			if ( !isset( $data['features'] ) or !count( $data['features'] ) )
			{
				return;
			}

			$_address	= NULL;

			$geoLocation->long	= $data['features'][0]['center'][0];
			$geoLocation->lat	= $data['features'][0]['center'][1];

			if( $setAddress === TRUE )
			{
				$geoLocation->placeName = $data['features'][0]['place_name'];

				/* If we are at address level, the address will not be in a property */
				if( $data['features'][0]['place_type'][0] == 'address' )
				{
					$geoLocation->addressLines = [ $data['features'][0]['address'] . ' ' . $data['features'][0]['text'] ];
				}

				foreach( $data['features'][0]['context'] as $property )
				{
					$propertyType = mb_substr( $property['id'], 0, mb_strpos( $property['id'], '.' ) );
					switch( $propertyType )
					{
						case 'locality':
							$geoLocation->city = $property['text'];
							break;

						case 'postcode':
							$geoLocation->postalCode = $property['text'];
							break;

						case 'country':
							$geoLocation->country = $property['text'];
							break;

						case 'region':
							$geoLocation->region = $property['text'];
							break;

						case 'district':
							$geoLocation->county = $property['text'];
							break;
					}
				}
			}
		}
		else
		{
			throw new BadFunctionCallException;
		}
	}
}
