<?php

class GoogleGeoCode {
	
	const geocode_api_url = 'https://maps.googleapis.com/maps/api/geocode/json';
	
	private static function requestJSON ( array $param , $key = false ) {
		
		if ( !function_exists( 'curl_init' ) ) throw new GoogleGeoCodeException( 'cURL required but not installed.' );
		
		$url = self::geocode_api_url . '?' . implode( '&' , call_user_func( function ( $keys , $values ) { $return = array(); foreach ( $keys as $key ) $return[] = $key . '=' . urlencode( $values[ $key ] ); return $return; } , array_keys( $param ) , $param ) ) . ( !empty( $key ) ? ( '&key=' . $key ) : '' );
		
		$curl		=	curl_init();
		curl_setopt( $curl , CURLOPT_URL , $url );
		curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
		curl_setopt( $curl , CURLOPT_TIMEOUT , 10 );
		curl_setopt( $curl , CURLOPT_FOLLOWLOCATION , true );
		$response	=	curl_exec( $curl );
		
		if ( !empty( curl_errno( $curl ) ) ) throw new GoogleGeoCodeException( curl_error( $curl ) );
		
		curl_close( $curl );
		
		return json_decode( $response );
		
	}
	
	/* GoogleGeoCodeResult object getPlace( array param , string key , array options = array )
	 * @param array param : Array with valid param for Google's Geocoding API. See: https://developers.google.com/maps/documentation/geocoding/intro
	 * @param string key : Valid Google API key. May be set to false if no panorama is requested.
	 * @param array options 
	 */ 
	public static function getPlace ( array $param , $key , array $options = array() ) {
		
		if ( empty( $param[ 'address' ] ) && empty( $param[ 'components' ] ) && empty( $param[ 'latlng' ] ) && empty( $param[ 'place_id' ] ) ) return false;
		
		if ( !empty( $param[ 'address' ] ) ) $request = array( 'address' => $param[ 'address' ] );
		if ( !empty( $param[ 'latlng' ] ) ) $request = array( 'latlng' => $param[ 'latlng' ] );
		if ( !empty( $param[ 'place_id' ] ) ) $request = array( 'place_id' => $param[ 'place_id' ] );
		if ( !empty( $param[ 'region' ] ) ) $request[ 'region' ] = $param[ 'region' ];
		if ( !empty( $param[ 'bounds' ] ) ) $request[ 'bounds' ] = is_array( $param[ 'bounds' ] ) ? ( empty( array_filter( $param[ 'bounds' ] , 'is_array' ) ) ? implode( '|' , $param[ 'bounds' ] ) : call_user_func( function ( $bounds ) { $return = array(); foreach( $bounds as $bound ) $return[] = is_array( $bound ) ? implode( ',' , $bound ) : $bound; return implode( '|' , $return ); } , $param[ 'bounds' ] ) ) : $param[ 'region' ];
		if ( !empty( $param[ 'components' ] ) ) $request[ 'components' ] = is_array( $request[ 'components' ] ) ? call_user_func( function ( $components ) { $return = array(); foreach( $components as $key => $value ) $return[] = $key . ':' . $value; return implode( '|' , $return ); } , $request[ 'components' ] ) : $request[ 'components' ];
		if ( !empty( $param[ 'result_type' ] ) ) $request[ 'result_type' ] = is_array( $param[ 'result_type' ] ) ? implode( '|' , $param[ 'result_type' ] ) : $param[ 'result_type' ];
		if ( !empty( $param[ 'location_type' ] ) ) $request[ 'location_type' ] = is_array( $param[ 'location_type' ] ) ? implode( '|' , $param[ 'location_type' ] ) : $param[ 'location_type' ];
		$request[ 'language' ] = empty( $param[ 'language' ] ) ? $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ] : $param[ 'language' ];
		
		$json						=	self::requestJSON( $request , $key );
		
		$geo_code_obj				=	new GoogleGeoCodeResult();
		$geo_code_obj -> status		=	$json -> status;
		if ( !empty( $json -> error_message ) ) $geo_code_obj -> error_message = $json -> error_message;
		$geo_code_obj -> rowCount	=	count( $json -> results );
		$geo_code_obj -> results	=	call_user_func( function ( $json , $key , $options , $language ) {
			
			$return							=	array();
			foreach ( $json -> results as $result ) {
				$map							= 	(object)array( 
						'type' => false , 
						'address' => (object)array( 'formatted' => false , 'street' => false , 'street_number' => false , 'postal_code' => false , 'city' => false , 'area' => false , 'state' => false , 'country' => false , 'country_code' => false ) , 
						'location' => (object)array( 'type' => false , 'lat' => false , 'lng' => false , 'place_id' => false , 'viewport' => false , 'bounds' => false ) 
				);
				$map -> type					=	$result -> types[0];
				$map -> address -> formatted 	=	$result -> formatted_address;
				foreach ( $result -> address_components as $comp ) {
					if ( $comp -> types[0] == 'route' ) $map -> address -> street = $comp -> long_name;
					if ( $comp -> types[0] == 'street_number' ) $map -> address -> street_number = $comp -> long_name;
					if ( $comp -> types[0] == 'postal_code' ) $map -> address -> postal_code = $comp -> long_name;
					if ( $comp -> types[0] == 'locality' ) $map -> address -> city = $comp -> long_name;
					if ( $comp -> types[0] == 'administrative_area_level_2' ) $map -> address -> area = $comp -> long_name;
					if ( $comp -> types[0] == 'administrative_area_level_1' ) $map -> address -> state = $comp -> long_name;
					if ( $comp -> types[0] == 'country' ) { $map -> address -> country = $comp -> long_name; $map -> address -> country_code = $comp -> short_name; }
				}
				$map -> location -> type		= 	$result -> geometry -> location_type;
				$map -> location -> lat			= 	$result -> geometry -> location -> lat;
				$map -> location -> lng			= 	$result -> geometry -> location -> lng;
				$map -> location -> place_id	=	$result -> place_id;
				$map -> location -> viewport	=	!empty( $result -> geometry -> viewport ) ? $result -> geometry -> viewport : false;
				$map -> location -> bounds		=	!empty( $result -> geometry -> bounds ) ? $result -> geometry -> bounds : false;
				if ( !empty( $options[ 'panorama' ] ) ) {
					$panorama						=	GoogleStreetView::getPanoramaMeta( array( 'location' => $map -> address -> formatted , 'language' => $language ) , $key , array( 'location' => array( $map -> location -> lat , $map -> location -> lng ) ) );
					$map -> panorama 				= 	( $panorama -> status != 'OK' ) ? false : $panorama -> panorama;
				}
				$return[]						=	$map;
			}
			
			return $return;
			
		} ,  $json , $key , $options , $request[ 'language' ] );
		
		return $geo_code_obj;
		
	}
	
}

class GoogleGeoCodeException extends \Exception {}
class GoogleGeoCodeResult { }