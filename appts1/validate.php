<?php
// $Id:$

// Validates a user connected either by an encrypted URL or username/password form
//must remain above session start. DO NOT MOVE
require_once('../appcore/vec-clconfigsite.php');
require_once("../appconfig/config-app.php");
require_once("../appconfig/config-siteglobalconfig.php");
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config.php");
include("vec-clappointments.php");
require_once("../appcore/vec-clsession.php");
$mysession = new authentxsession();

$myappt = new authentxappointments();

// Check if gsa has expired
$gsaexpired = false;
if (gsa_ACTIVATE === true)
	$gsaexpired = $myappt->gsa_check_expired();

if ($gsaexpired === true)
{
	print "<script type=\"text/javascript\">alert('gsanstration period has expired.')</script>\n";
	$myappt->vectormeto($page_denied);
}
else
{
	// connect to the database
	$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
	if (!($dbh->connect_error))
	{
		// check for URL query string
		if (isset($_GET["vc"]))
			$vc = $_GET["vc"];
		else
			$vc = false;
		
		if ($vc !== false)
		{
			// process the URL string
			$rslt = $myappt->validateuser($dbh, false, false, $vc);
			if (stripos($rslt, "error") !== false)
			{
				// log validation error
				$logstring = "Validation error: ".$rslt;
				$myappt->createlogentry($dbh, $logstring, 0, ALOG_ERRORVALIDATE);
				
				$dbh->close();
				print "<script type=\"text/javascript\">alert('".htmlentities($rslt)."')</script>\n";
				$myappt->vectormeto($page_denied);
			}
			else 
			{
				// log validation success
				$logstring = "Validation successful for ".$myappt->session_getuuname()." (".$myappt->session_getuuid().")";
				$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_OKVALIDATE);
				
				// session is setup and we can proceed to the granted page
				$dbh->close();
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
			
			
			
			//todo DEBUG AND SEE WHAT WE GET BACK
			if (preg_match('/@([^.]+)(?=\.)/', $userid, $m)) 
			{
				$userAgency = $m[1];  

				// set site in session
				$mysession->setsite($userAgency);
				$mysession->setsiteid($siteid);
				
				// create dynamic site configuration
				$siteconfig = new authentxconfigsite($userAgency);
				$siteconfig->configure($globalConfig);

				// set session with site configuration
				$mysession->setsiteconfiguration($siteconfig);	
			}
			else 
			{
				$userAgency = null;
			}
			

			$rslt = $myappt->validateuser($dbh, $userid, $passwd, false);
			if (stripos($rslt, "error") !== false)
			{
				// log login error
				$logstring = "Login error: ".$rslt;
				$myappt->createlogentry($dbh, $logstring, 0, ALOG_ERRORLOGIN);
				
				$dbh->close();
				print "<script type=\"text/javascript\">alert('".htmlentities($rslt)."')</script>\n";
				$myappt->vectormeto($page_denied);
			}
			else 
			{
				// log login success
				$logstring = "Login successful for ".$myappt->session_getuuname()." (".$myappt->session_getuuid().")";
				$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_OKLOGIN);
				
				// session is setup and we can proceed to the granted page
				$dbh->close();
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