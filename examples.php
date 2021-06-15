<pre>
<?php

define( 'GOOGLE_API_KEY' , 'YOUR_API_KEY' );
include( 'GoogleGeoCode/autoload.php' );

$geocode = GoogleGeoCode::getPlace( array( 'address' => 'Domkloster 4, 50667 Cologne' ) , GOOGLE_API_KEY, array( 'panorama' => true ) );
print_r( $geocode );

print_r( GooglePlaces::getPlace( array( 
		'location' => $geocode -> results[0] -> location -> lat . ',' . $geocode -> results[0] -> location -> lng , 
		'radius' => 100 , 
		'type' => 'political' , 
		'orderby' => 'distance'
		) , GOOGLE_API_KEY ) );

$pano = GoogleStreetView::getPanoramaImage( array( 'pano' => $geocode -> results[0] -> panorama -> id , 'heading' => $geocode -> results[0] -> panorama -> heading , 'pitch' => $geocode -> results[0] -> panorama -> pitch , 'fov' => 90 , 'size' => '800x400' ) , GOOGLE_API_KEY );
print_r( $pano );

?>
</pre>
<img src="<?php echo $pano -> src; ?>" width="<?php echo $pano -> width; ?>" height="<?php echo $pano -> height; ?>" />