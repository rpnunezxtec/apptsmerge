<?php
// $Id:$

// security code image
// the text is retrieved from the appointments session var
// and used with the background image to create a security code
// image and output it to the browser
	
session_start();
include("config.php");
include_once("vec-clappointments.php");
$myappt = new authentxappointments();

$scode_text = $myappt->session_getvar("scode_text");
$scode_background = $myappt->session_getvar("scode_background");
$scode_font = "./codefont-0".(rand(1, 8)).".gdf";

// outputs the header and png image
$myappt->showsecurityimage($scode_text, $scode_background, $scode_font);

?>