<?php 

class GoogleStreetView {
	
	const streetview_metadata_api_url 		= 	'https://maps.googleapis.com/maps/api/streetview/metadata?';
	const streetview_image_api_url 			= 	'https://maps.googleapis.com/maps/api/streetview?';
	
	private static function requestJSON ( $param , $key ) {
		
		if ( !function_exists( 'curl_init' ) ) throw new GoogleStreetViewException( 'cURL required but not installed.' );
		
		$url = self::streetview_metadata_api_url . implode( '&' , call_user_func( function ( $keys , $values ) { $return = array(); foreach ( $keys as $key ) $return[] = $key . '=' . urlencode( $values[ $key ] ); return $return; } , array_keys( $param ) , $param ) ) . '&key=' . $key;
		
		$curl		=	curl_init();
		curl_setopt( $curl , CURLOPT_URL , $url );
		curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
		curl_setopt( $curl , CURLOPT_TIMEOUT , 10 );
		curl_setopt( $curl , CURLOPT_FOLLOWLOCATION , true );
		$response	=	curl_exec( $curl );
		
		if ( !empty( curl_errno( $curl ) ) ) throw new GoogleStreetViewException( curl_error( $curl ) );
		
		curl_close( $curl );
		
		return json_decode( $response );
		
	}
	
	public static function getPanoramaMeta ( array $param , $key , array $options = array() ) {
		
		if ( empty( $param[ 'location' ] ) && empty( $param[ 'pano' ] ) ) return false;
		if ( !empty( $param[ 'location' ] ) ) $request = array( 'location' => ( is_array( $param[ 'location' ] ) ? implode( ',' , $param[ 'location' ] ) : $param[ 'location' ] ) );
		if ( !empty( $param[ 'pano' ] ) ) $request = array( 'pano' => $param[ 'pano' ] );
		$request[ 'language' ] = empty( $param[ 'language' ] ) ? $_SERVER[ 'HTTP_ACCEPT_LANGUAGE' ] : $param[ 'language' ];
		
		$json									=	self::requestJSON( $request , $key );
		
		$streetview_obj							=	new GoogleStreetViewResult();
		$streetview_obj -> status				=	$json -> status;
		
		if ( $json -> status != 'OK' ) { 
			if ( !empty( $json -> error_message ) ) $streetview_obj-> error_message = $json -> error_message;
			return $streetview_obj;
		}
		
		$streetview_obj -> panorama				=	(object)array( 'id' => $json -> pano_id , 'lat' => $json -> location -> lat , 'lng' => $json -> location -> lng );
		
		if ( ( !empty( $param[ 'location' ] ) && GeoFunctions::validateCoordinate( $param[ 'location' ] ) ) || ( !empty( $options[ 'location' ] ) && GeoFunctions::validateCoordinate( $options[ 'location' ] ) ) ) {
			$elevation								=	GoogleElevation::getElevation( array( 'locations' => array( array( $streetview_obj -> panorama -> lat , $streetview_obj -> panorama -> lng ) , ( empty( $options[ 'location' ] ) ? $param[ 'location' ] : $options[ 'location' ] ) ) ) , $key );
			$streetview_obj -> panorama -> heading 	= 	GeoFunctions::getHeading( array( $streetview_obj -> panorama -> lat , $streetview_obj -> panorama -> lng ) , ( empty( $options[ 'location' ] ) ? $param[ 'location' ] : $options[ 'location' ] ) );
			$streetview_obj -> panorama -> pitch	=	( $elevation -> status != 'OK' ) ? $elevation : GeoFunctions::getPitch( array_values( (array)$elevation -> results[0] -> location ) , array_values( (array)$elevation -> results[1] -> location ) , $elevation -> results[0] -> elevation , $elevation -> results[1] -> elevation );
		}
		
		return $streetview_obj;
		
	}
	
	public static function getPanoramaImage ( array $param , $key ) {
		
		if ( empty( $param[ 'location' ] ) && empty( $param[ 'pano' ] ) && empty( $param[ 'size' ] ) ) return false;
		$url_parameter							=	array();
		$url_parameter[]						=	!empty( $param[ 'pano' ] ) ? ( 'pano=' . urlencode( $param[ 'pano' ] ) ) : ( 'location=' . urlencode( $param[ 'location' ] ) );
		$url_parameter[]						=	'size=' . urlencode( $param[ 'size' ] );
		$size									=	explode( 'x' , $param[ 'size' ] );
		if ( !empty( $param[ 'heading' ] ) ) $url_parameter[]	= 'heading=' . urlencode( $param[ 'heading' ] );
		if ( !empty( $param[ 'pitch' ] ) ) $url_parameter[]	= 'pitch=' . urlencode( $param[ 'pitch' ] );
		if ( !empty( $param[ 'fov' ] ) ) $url_parameter[]	= 'fov=' . urlencode( $param[ 'fov' ] );
		
		$streetview_obj							=	new GoogleStreetViewResult();
		$streetview_obj -> src					=	self::streetview_image_api_url . implode( '&' , $url_parameter ) . '&key=' . $key;
		$streetview_obj -> width				=	$size[0];
		$streetview_obj -> height				=	$size[1];
		
		return $streetview_obj;
		
	}
	
}

class GoogleStreetViewException extends \Exception {}
class GoogleStreetViewResult {}

?>