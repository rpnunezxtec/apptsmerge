<?php
// $Id:$

// Validates a user connected either by an encrypted URL or username/password form

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

// Check if demo has expired
$demoexpired = false;
if (DEMO_ACTIVATE === true)
	$demoexpired = $myappt->demo_check_expired();

if ($demoexpired === true)
{
	print "<script type=\"text/javascript\">alert('Demonstration period has expired.')</script>\n";
	$myappt->vectormeto($page_denied);
}
else
{
	// connect to the database
	$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
	if (!$sdbh->connect_error)
	{
		// check for URL query string
		if (isset($_GET["vc"]))
			$vc = $_GET["vc"];
		else
			$vc = false;
		
		if ($vc !== false)
		{
			// process the URL string
			$rslt = $myappt->validateuser($sdbh, false, false, $vc);
			if (stripos($rslt, "error") !== false)
			{
				// log validation error
				$logstring = "Validation error: ".$rslt;
				$myappt->createlogentry($sdbh, $logstring, 0, ALOG_ERRORVALIDATE);
				
				$sdbh->close();
				print "<script type=\"text/javascript\">alert('".htmlentities($rslt)."')</script>\n";
				$myappt->vectormeto($page_denied);
			}
			else 
			{
				// log validation success
				$logstring = "Validation successful for ".$myappt->session_getuuname()." (".$myappt->session_getuuid().")";
				$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_OKVALIDATE);
				
				// session is setup and we can proceed to the granted page
				$sdbh->close();
				$myappt->vectormeto($page_granted);
			}
		}
		else
		{
			// check for posted values from form
			if (isset($_POST["userid"]))
				$userid = trim($_POST["userid"]);
			else
				$userid = false;

			if (isset($_POST["passwd"]))
				$passwd = $_POST["passwd"];
			else
				$passwd = false;
				
			$rslt = $myappt->validateuser($sdbh, $userid, $passwd, false);
			if (stripos($rslt, "error") !== false)
			{
				// log login error
				$logstring = "Login error: ".$rslt;
				$myappt->createlogentry($sdbh, $logstring, 0, ALOG_ERRORLOGIN);
				
				$sdbh->close();
				print "<script type=\"text/javascript\">alert('".htmlentities($rslt)."')</script>\n";
				$myappt->vectormeto($page_denied);
			}
			else 
			{
				// log login success
				$logstring = "Login successful for ".$myappt->session_getuuname()." (".$myappt->session_getuuid().")";
				$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_OKLOGIN);
				
				// session is setup and we can proceed to the granted page
				$sdbh->close();
				$myappt->vectormeto($page_granted);
			}
		}
	}
	else
	{
		print "<script type=\"text/javascript\">alert('Could not connect to database')</script>\n";
		$myappt->vectormeto($page_denied);
	}
}

?>