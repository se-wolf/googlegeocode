<?php

class GooglePlaces {
	
	//const places_api_url = 'https://maps.googleapis.com/maps/api/place/details/json';
	const places_api_url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json';

	private static function requestJSON ( array $param , $key = false ) {
		
		if ( !function_exists( 'curl_init' ) ) throw new GooglePlacesException( 'cURL required but not installed.' );
		
		$url = self::places_api_url . '?' . implode( '&' , call_user_func( function ( $keys , $values ) { $return = array(); foreach ( $keys as $key ) $return[] = $key . '=' . urlencode( $values[ $key ] ); return $return; } , array_keys( $param ) , $param ) ) . ( !empty( $key ) ? ( '&key=' . $key ) : '' );
		
		$curl		=	curl_init();
		curl_setopt( $curl , CURLOPT_URL , $url );
		curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
		curl_setopt( $curl , CURLOPT_TIMEOUT , 10 );
		curl_setopt( $curl , CURLOPT_FOLLOWLOCATION , true );
		$response	=	curl_exec( $curl );
		
		if ( !empty( curl_errno( $curl ) ) ) throw new GooglePlacesException( curl_error( $curl ) );
		
		curl_close( $curl );
		
		return json_decode( $response );
		
	}
	
	/* GoogleGeoCodeResult object getPlace( array param , string key , array options = array )
	 * @param array param : Array with valid param for Google's Geocoding API. See: https://developers.google.com/maps/documentation/geocoding/intro
	 * @param string key : Valid Google API key. May be set to false if no panorama is requested.
	 * @param array options
	 */
	public static function getPlace ( array $param , $key , array $options = array() ) {
		
		return self::requestJSON( $param , $key );
			
	}

}

class GooglePlacesException extends \Exception {}
class GooglePlacesResult { }

?>