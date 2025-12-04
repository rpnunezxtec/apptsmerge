<?php

// calculate x any y coordinates in svg file from map coordinates 

function get_location($devgpslat, $devgpslong, $mia_xy, $sea_xy, $mia_ll, $sea_ll, $pid)
{
	$devgpslat = preg_replace('/([^\-0-9\.,])/i', '', $devgpslat);
	$devgpslong = preg_replace('/([^\-0-9\.,])/i', '', $devgpslong);
	
	// calculate x
	$x_loc = $sea_xy[0] + ($devgpslong - $sea_ll[1]) * ( ($mia_xy[0] - $sea_xy[0]) / ($mia_ll[1] - $sea_ll[1]) );
	
	// calculate y
	$y_loc = $sea_xy[1] + ($devgpslat - $sea_ll[0]) * (($mia_xy[1] - $sea_xy[1]) / ($mia_ll[0] - $sea_ll[0]));
	
	return array($x_loc, $y_loc);
}

?>

