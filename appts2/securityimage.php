<?php
// $Id:$

// security code image
// the text is retrieved from the appointments session var
// and used with the background image to create a security code
// image and output it to the browser
	
session_start();

include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

$scode_text = $myappt->session_getvar("scode_text");
$scode_background = $myappt->session_getvar("scode_background");
$scode_font = "./codefont-0".(rand(1, 8)).".gdf";

// outputs the header and png image
$myappt->showsecurityimage($scode_text, $scode_background, $scode_font);

?>