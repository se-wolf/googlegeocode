<?php

class GeoFunctions {
	
	const R = 6378137;
	
	/* float validateCoordinate ( mixed latlng )
	 * latlng may be a formatted string 'Latidue,Longitude' or an array with the first item being latitude and second being longitude.
	 * Returns true if latlng is a valid geo coordinate
	 */
	public static function validateCoordinate ( $latlng ) {
		
		$latlng					=	!is_array( $latlng ) ? explode( ',' , $latlng ) : $latlng;
		return ( !empty( $latlng[0] ) && is_numeric( $latlng[0] ) && $latlng[0] <= 90 && $latlng[0] >= -90 ) && ( !empty( $latlng[1] ) && is_numeric( $latlng[1] ) && $latlng[1] <= 180 && $latlng[1] >= -180 );
		
	}
	
	/* float getDistance ( mixed latlng1 , mixed latlng2 )
	 * latlng1 & latlng2 may be formatted strings 'Latidue,Longitude' or arrays with the first item being latitude and second being longitude.
	 * Returns float in meters.
	 */
	public static function getDistance ( $latlng1 , $latlng2 ) {
		
		$latlng1				=	!is_array( $latlng1 ) ? explode( ',' , $latlng1 ) : $latlng1;
		$latlng2				=	!is_array( $latlng2 ) ? explode( ',' , $latlng2 ) : $latlng2;
		return	self::R * acos ( sin( deg2rad( $latlng1[0] ) ) * sin( deg2rad( $latlng2[0] ) ) + cos( deg2rad( $latlng1[0] ) ) * cos( deg2rad( $latlng2[0] ) ) * cos( deg2rad( $latlng1[1] - $latlng2[1] ) ) );
		
	}
	
	/* float getAngleBetween ( mixed latlng1 , mixed latlng2 )
	 * latlng1 & latlng2 may be formatted strings 'Latidue,Longitude' or arrays with the first item being latitude and second being longitude.
	 * Returns float in degrees between two geo coordinates.
	 */
	public static function getAngleBetween ( $latlng1 , $latlng2 ) {
		
		$latlng1				=	!is_array( $latlng1 ) ? explode( ',' , $latlng1 ) : $latlng1;
		$latlng2				=	!is_array( $latlng2 ) ? explode( ',' , $latlng2 ) : $latlng2;
		return deg2rad( ( $latlng1[0] == $latlng2[0] && $latlng1[1] == $latlng2[1] ) ? 0 : ( 360 * ( self::getDistance( $latlng1 , $latlng2 ) / ( 2 * pi() * self::R ) ) ) );
		
	}

	/* float getHeading ( mixed latlng1 , mixed latlng2 )
	 * latlng1 & latlng2 may be formatted strings 'Latidue,Longitude' or arrays with the first item being latitude and second being longitude.
	 * Returns float in degrees from 0 to 360 with 0 being geographical north. 
	 */
	public static function getHeading ( $latlng1 , $latlng2 ) {
		
		$latlng1				=	!is_array( $latlng1 ) ? explode( ',' , $latlng1 ) : $latlng1;
		$latlng2				=	!is_array( $latlng2 ) ? explode( ',' , $latlng2 ) : $latlng2;
		$heading				=	rad2deg( atan2( sin( deg2rad( $latlng2[1] ) - deg2rad( $latlng1[1] ) ) * cos( deg2rad( $latlng2[0] ) ) , cos( deg2rad( $latlng1[0] ) ) * sin( deg2rad( $latlng2[0] ) ) - sin( deg2rad( $latlng1[0]) ) * cos( deg2rad( $latlng2[0] ) ) * cos( deg2rad( $latlng2[1] ) - deg2rad( $latlng1[1] ) ) ) ) + 360;
		return ( $heading > 0 && $heading < 360 ) ? $heading : ( ( $heading < 0 ) ? ( 360 + $heading ) : ( $heading - 360 ) );
		
	}
	
	/* float getPitch ( mixed latlng1 , mixed latlng2 , int e1 = 0 , int e2 = 0 )
	 * latlng1 & latlng2 may be formatted strings 'Latidue,Longitude' or arrays with the first item being latitude and second being longitude.
	 * e1 & e1 are elvations above the sea level in meters
	 * Returns float in degrees from -90 to 90 with 0 being horizontal horizontal orientation.
	 */
	public static function getPitch ( $latlng1 , $latlng2 , $e1 = 0 , $e2 = 0 ) { 
		
		if ( $e1 == $e2 ) return 0;
		$latlng1				=	!is_array( $latlng1 ) ? explode( ',' , $latlng1 ) : $latlng1;
		$latlng2				=	!is_array( $latlng2 ) ? explode( ',' , $latlng2 ) : $latlng2;
		$alpha					=	rad2deg( asin( abs( $e2 - $e1 ) / ( sqrt( pow( ( self::R + $e2 ) , 2 ) + pow( ( self::R + $e1 ) , 2 ) - ( 2 * ( self::R + $e1 ) * ( self::R + $e2 ) * cos( self::getAngleBetween ( $latlng1 , $latlng2 ) ) ) ) ) ) );
		return ( abs( $e1 ) > abs( $e2 ) ) ? -$alpha : $alpha;
		
	}
	
}