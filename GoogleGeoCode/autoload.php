<?php

class GoogleAPIAutoload {
	
	public function __construct ( $dir ) {
		$this -> dir	=	$dir;
		spl_autoload_register( array( $this , 'load' ) );
	}
	public static function register ( $dir = __DIR__ ) { new self( $dir ); }
	public function load ( $class ) {
		$file			=	self::verify( $class );
		if ( !$file === false ) include( $file );
	}
	public function verify ( $class ) {
		if ( strstr( $class , '\\' ) ) $class = str_replace( '\\' , '/' , $class );
		$files 			= 	glob( $this -> dir . '/' . strtolower( $class ) . '*.php' );
		return empty( $files ) ? false : $files[0];
	}
	
}

GoogleAPIAutoload::register( __DIR__ );

?>