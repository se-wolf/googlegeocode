<?php

class GoogleElevation {
	
	const elevation_api_url = 'https://maps.googleapis.com/maps/api/elevation/json?';
	
	private static function requestJSON ( $param , $key ) {
		
		if ( !function_exists( 'curl_init' ) ) throw new GoogleElevationException( 'cURL required but not installed.' );
		if ( empty( $param[ 'locations' ] ) && empty( $param[ 'path' ] ) ) return false;
		$callback	=	function ( $locations ) { 
			setlocale( LC_NUMERIC , 'en_US' );
			if ( !is_array( $locations ) && strstr( $locations , '|' ) ) $locations = explode( '|' , $locations ); 
			if ( GeoFunctions::validateCoordinate( $locations ) ) return implode( ',' , $locations ); 
			$return = array(); 
			foreach ( $locations as $location ) { 
				if ( GeoFunctions::validateCoordinate( $location ) ) { 
					$return[] = is_array( $location ) ? implode( ',' , $location ) : $location; 
				}
			}
			return implode( '|' , $return );
		};
		
		if ( !empty( $param[ 'locations' ] ) ) 	
			$locations = ( !is_array( $param[ 'locations' ] ) && GeoFunctions::validateCoordinate( $param[ 'locations' ] ) ) ? 
			$param[ 'locations' ] : call_user_func( $callback , $param[ 'locations' ] );
		if ( !empty( $param[ 'path' ] ) ) {
			$path 		= 	( !is_array( $param[ 'path' ] ) && GeoFunctions::validateCoordinate( $param[ 'path' ] ) ) ? $param[ 'path' ] : call_user_func( $callback , $param[ 'path' ] );
			$samples	=	!empty( $param[ 'samples' ] ) ? $param[ 'samples' ] : ( is_array( $param[ 'path' ] ) && !GeoFunctions::validateCoordinate( $param[ 'path' ] ) ) ? count( $param[ 'path' ] ) : 1; 
		}
		
		$url = self::elevation_api_url . ( ( !empty( $path ) && !empty( $samples ) ) ? ( 'path=' . $path . '&samples=' . $samples ) : ( 'locations=' . $locations ) ) . '&key=' . $key;
		
		$curl		=	curl_init();
		curl_setopt( $curl , CURLOPT_URL , $url );
		curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
		curl_setopt( $curl , CURLOPT_TIMEOUT , 10 );
		curl_setopt( $curl , CURLOPT_FOLLOWLOCATION , true );
		$response	=	curl_exec( $curl );
		
		if ( !empty( curl_errno( $curl ) ) ) throw new GoogleElevationException( curl_error( $curl ) );
		
		curl_close( $curl );
		
		$return 			=	json_decode( $response );
		$return -> url		=	$url;
		$return -> param	=	json_encode( $param );
		
		return $return;
		
	}
	
	/* GoogleElevationResult object getElevation ( array param , string key )
	 * @param param = array( array locations , array path , int samples ) See: https://developers.google.com/maps/documentation/elevation/intro
	 * @param key valid Google API key
	 * param may either be locations array or path array with one or more geo coordinates as string or array. If path option is used, samples is necessary  
	 */
	public static function getElevation ( $param , $key ) {
		
		$json						=	self::requestJSON( $param , $key );
		if ( $json === false ) return false;
		
		$elevation_obj				=	new GoogleElevationResult();
		$elevation_obj -> status	=	$json -> status;
		$elevation_obj -> url = $json -> url;
		$elevation_obj -> param = $json -> param;
		if ( $json -> status != 'OK' ) {
			$elevation_obj -> error_message = $json -> error_message;
			return $elevation_obj;
		}
		$elevation_obj -> rowCount	=	count( $json -> results );
		$elevation_obj -> results	=	call_user_func( function ( $json ) {
			$return = array();
			foreach( $json -> results as $result ) { 
				$map = (object)array( 'location' => (object)array( 'lat' => false , 'lng' => false ) , 'elevation' => false , 'resolution' => false );
				foreach ( $result as $key => $value ) $map -> $key = $value;
				$return[] = $map;
			}
			return $return;
		} , $json );
		
		return $elevation_obj;
		
	}
	
}

class GoogleElevationException extends \Exception {}
class GoogleElevationResult {}