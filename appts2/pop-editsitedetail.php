<?php
// $Id:$

// popup to edit site details
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-editsitedetail.php";
// the geometry required for this popup
$windowx = 500;
$windowy = 1200;

include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

// validate the session
$sr = $myappt->validatesession($_siteid);
if ($sr !== true)
{
	print "<script type=\"text/javascript\">alert('".$sr."')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Validate access to this form - requires User tab permissions
if ($myappt->checktabmask(TAB_S) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Get admin privileges: site alteration privileges.
$priv_screate = $myappt->checkprivilege(PRIV_SCREATE);
$priv_sedit = $myappt->checkprivilege(PRIV_SITEEDIT);
$priv_shours = $myappt->checkprivilege(PRIV_SHOURS);
$priv_sstat = $myappt->checkprivilege(PRIV_SSTAT);

// GET arguments: 
// centeruuid: site id (optional). If not supplied it is assumed a new site is being added.
if (isset($_GET["centeruuid"]))
{
	$centeruuid = $_GET["centeruuid"];
	// check and sanitise it
	if (strlen($centeruuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid center ID.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	
	// AVC is only present when the centeruuid is submitted, to verify it
	if (isset($_GET["avc"]))
		$avc = $_GET["avc"];
	else
	{
		print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// Check the AVC mac for validity
	$testavc = $myappt->session_createmac($centeruuid);
	
	if (strcasecmp($avc, $testavc) != 0)
	{
		print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
	$centeruuid = false;

// Create a list of timezones
$listtimezones = array();
$ntz = 0;
$tzidset = DateTimeZone::listIdentifiers();
foreach ($tzidset as $tzval)
{
	$listtimezones[$ntz][0] = $tzval;
	$listtimezones[$ntz][1] = $tzval;
	$ntz++;
}

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Process submissions here
	if (isset($_POST["submit_site"]))
	{
		if ($centeruuid !== false)
		{
			//*** Update an existing site

			// First part - general info requires site edit/creation privilege and local sourcing
			if (($priv_screate || $priv_sedit) && ($_axsitesync_enable === false))
			{
				// Posted info to use:
				// sitename, siteaddress, sitecontact, sitephone
				$p_sitename = trim($_POST["sitename"]);
				$p_siteaddress = trim($_POST["siteaddress"]);
				$p_siteaddrcity = trim($_POST["siteaddrcity"]);
				$p_siteaddrstate = trim($_POST["siteaddrstate"]);
				$p_siteaddrcountry = trim($_POST["siteaddrcountry"]);
				$p_siteaddrzip = trim($_POST["siteaddrzip"]);
				$p_siteregion = trim($_POST["siteregion"]);
				$p_sitecomponent = trim($_POST["sitecomponent"]);
				$p_sitetype = trim($_POST["sitetype"]);
				$p_siteactivity = trim($_POST["siteactivity"]);
				$p_sitecontact = trim($_POST["sitecontact"]);
				$p_sitephone = trim($_POST["sitephone"]);
				$p_sitetimezone = trim($_POST["sitetimezone"]);
				$p_sitenotifyemail = trim($_POST["sitenotifyemail"]);
				$p_sitevisible = trim($_POST["sitevisible"]);
				if ($p_sitevisible != 0)
					$p_sitevisible = 1;

				// Check for required fields: sitename
				if (($p_sitename == "") || ($p_sitetimezone == "") || ($p_siteaddrcity == "") || ($p_siteaddrstate == "") || ($p_sitetype == "") || ($p_siteactivity == ""))
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					$q_site = "update site set "
						. "\n sitename='".$sdbh->real_escape_string($p_sitename)."', "
						. "\n siteaddress='".$sdbh->real_escape_string($p_siteaddress)."', "
						. "\n siteaddrcity='".$sdbh->real_escape_string($p_siteaddrcity)."', "
						. "\n siteaddrstate='".$sdbh->real_escape_string($p_siteaddrstate)."', "
						. "\n siteaddrcountry='".$sdbh->real_escape_string($p_siteaddrcountry)."', "
						. "\n siteaddrzip='".$sdbh->real_escape_string($p_siteaddrzip)."', "
						. "\n siteregion='".$sdbh->real_escape_string($p_siteregion)."', "
						. "\n sitecomponent='".$sdbh->real_escape_string($p_sitecomponent)."', "
						. "\n sitetype='".$sdbh->real_escape_string($p_sitetype)."', "
						. "\n siteactivity='".$sdbh->real_escape_string($p_siteactivity)."', "
						. "\n sitecontactname='".$sdbh->real_escape_string($p_sitecontact)."', "
						. "\n sitecontactphone='".$sdbh->real_escape_string($p_sitephone)."', "
						. "\n sitenotifyemail='".$sdbh->real_escape_string($p_sitenotifyemail)."', "
						. "\n display='".$sdbh->real_escape_string($p_sitevisible)."', "
						. "\n timezone='".$sdbh->real_escape_string($p_sitetimezone)."', "
						. "\n xsyncmts='".time()."' "
						. "\n where centeruuid='".$sdbh->real_escape_string($centeruuid)."' "
						. "\n limit 1 "
						;
				
					$s_site = $sdbh->query($q_site);
					if ($s_site)
					{
						$logstring = "Site ".$p_sitename." (centeruuid: ".$centeruuid.") updated.";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
					}
					else
					{
						$logstring = "Error updating site ".$p_sitename." (centeruuid: ".$centeruuid.").";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
					}
				}
			}
			
			// Second part - hours, timeslot and holiday mapping requires shours privilege
			if ($priv_shours)
			{
				// Get the posted slottime, holiday map and hours of operation.
				// A null value for the hours means not in operation and is stored as a NULL in the database
				$p_siteblockout = $_POST["siteblockout"];
				if (is_nan($p_siteblockout))
					$p_siteblockout = 0;
				if (($p_siteblockout < 0) || ($p_siteblockout > SITEBLOCKOUT_MAX))
					$p_siteblockout = 0;
				
				$slottime = $_POST["slottime"];
				if (is_nan($slottime))
					$slottime = false;
					
				$hmapuuid = $_POST["hmapuuid"];
				if ($hmapuuid == "")
					$hmapuuid = false;
					
				$h = $_POST["ssun_h"];
				$m = $_POST["ssun_m"];
				if (($h == "") || ($m == ""))
					$startsun = false;
				else
					$startsun = $h.":".$m;
					
				$h = $_POST["esun_h"];
				$m = $_POST["esun_m"];
				if (($h == "") || ($m == ""))
					$endsun = false;
				else
					$endsun = $h.":".$m;
				
				$h = $_POST["smon_h"];
				$m = $_POST["smon_m"];
				if (($h == "") || ($m == ""))
					$startmon = false;
				else
					$startmon = $h.":".$m;
					
				$h = $_POST["emon_h"];
				$m = $_POST["emon_m"];
				if (($h == "") || ($m == ""))
					$endmon = false;
				else
					$endmon = $h.":".$m;	
				
				$h = $_POST["stue_h"];
				$m = $_POST["stue_m"];
				if (($h == "") || ($m == ""))
					$starttue = false;
				else
					$starttue = $h.":".$m;
					
				$h = $_POST["etue_h"];
				$m = $_POST["etue_m"];
				if (($h == "") || ($m == ""))
					$endtue = false;
				else
					$endtue = $h.":".$m;
				
				$h = $_POST["swed_h"];
				$m = $_POST["swed_m"];
				if (($h == "") || ($m == ""))
					$startwed = false;
				else
					$startwed = $h.":".$m;
					
				$h = $_POST["ewed_h"];
				$m = $_POST["ewed_m"];
				if (($h == "") || ($m == ""))
					$endwed = false;
				else
					$endwed = $h.":".$m;
				
				$h = $_POST["sthu_h"];
				$m = $_POST["sthu_m"];
				if (($h == "") || ($m == ""))
					$startthu = false;
				else
					$startthu = $h.":".$m;
					
				$h = $_POST["ethu_h"];
				$m = $_POST["ethu_m"];
				if (($h == "") || ($m == ""))
					$endthu = false;
				else
					$endthu = $h.":".$m;
					
				$h = $_POST["sfri_h"];
				$m = $_POST["sfri_m"];
				if (($h == "") || ($m == ""))
					$startfri = false;
				else
					$startfri = $h.":".$m;
					
				$h = $_POST["efri_h"];
				$m = $_POST["efri_m"];
				if (($h == "") || ($m == ""))
					$endfri = false;
				else
					$endfri = $h.":".$m;
					
				$h = $_POST["ssat_h"];
				$m = $_POST["ssat_m"];
				if (($h == "") || ($m == ""))
					$startsat = false;
				else
					$startsat = $h.":".$m;
					
				$h = $_POST["esat_h"];
				$m = $_POST["esat_m"];
				if (($h == "") || ($m == ""))
					$endsat = false;
				else
					$endsat = $h.":".$m;
				
				$h = $_POST["shol_h"];
				$m = $_POST["shol_m"];
				if (($h == "") || ($m == ""))
					$starthol = false;
				else
					$starthol = $h.":".$m;
					
				$h = $_POST["ehol_h"];
				$m = $_POST["ehol_m"];
				if (($h == "") || ($m == ""))
					$endhol = false;
				else
					$endhol = $h.":".$m;
				
				// update the database with the new times
				$q_time = "update site set ";
				if ($slottime !== false)
					$q_time .= "\n slottime='".$sdbh->real_escape_string($slottime)."', ";
				$q_time .= "\n siteblockout='".$sdbh->real_escape_string($p_siteblockout)."', ";
				if ($hmapuuid !== false)
					$q_time .= "\n hmapuuid='".$sdbh->real_escape_string($hmapuuid)."', ";
				else
					$q_time .= "\n hmapuuid=NULL, ";
				if ($startsun !== false)
					$q_time .= "\n startsun='".$sdbh->real_escape_string($startsun)."', ";
				else
					$q_time .= "\n startsun=NULL, ";
				if ($endsun !== false)
					$q_time .= "\n endsun='".$sdbh->real_escape_string($endsun)."', ";
				else
					$q_time .= "\n endsun=NULL, ";
				if ($startmon !== false)
					$q_time .= "\n startmon='".$sdbh->real_escape_string($startmon)."', ";
				else
					$q_time .= "\n startmon=NULL, ";
				if ($endmon !== false)
					$q_time .= "\n endmon='".$sdbh->real_escape_string($endmon)."', ";
				else
					$q_time .= "\n endmon=NULL, ";
				if ($starttue !== false)
					$q_time .= "\n starttue='".$sdbh->real_escape_string($starttue)."', ";
				else
					$q_time .= "\n starttue=NULL, ";
				if ($endtue !== false)
					$q_time .= "\n endtue='".$sdbh->real_escape_string($endtue)."', ";
				else
					$q_time .= "\n endtue=NULL, ";
				if ($startwed !== false)
					$q_time .= "\n startwed='".$sdbh->real_escape_string($startwed)."', ";
				else
					$q_time .= "\n startwed=NULL, ";
				if ($endwed !== false)
					$q_time .= "\n endwed='".$sdbh->real_escape_string($endwed)."', ";
				else
					$q_time .= "\n endwed=NULL, ";
				if ($startthu !== false)
					$q_time .= "\n startthu='".$sdbh->real_escape_string($startthu)."', ";
				else
					$q_time .= "\n startthu=NULL, ";
				if ($endthu !== false)
					$q_time .= "\n endthu='".$sdbh->real_escape_string($endthu)."', ";
				else
					$q_time .= "\n endthu=NULL, ";
				if ($startfri !== false)
					$q_time .= "\n startfri='".$sdbh->real_escape_string($startfri)."', ";
				else
					$q_time .= "\n startfri=NULL, ";
				if ($endfri !== false)
					$q_time .= "\n endfri='".$sdbh->real_escape_string($endfri)."', ";
				else
					$q_time .= "\n endfri=NULL, ";
				if ($startsat !== false)
					$q_time .= "\n startsat='".$sdbh->real_escape_string($startsat)."', ";
				else
					$q_time .= "\n startsat=NULL, ";
				if ($endsat !== false)
					$q_time .= "\n endsat='".$sdbh->real_escape_string($endsat)."', ";
				else
					$q_time .= "\n endsat=NULL, ";
				if ($starthol !== false)
					$q_time .= "\n starthol='".$sdbh->real_escape_string($starthol)."', ";
				else
					$q_time .= "\n starthol=NULL, ";
				if ($endhol !== false)
					$q_time .= "\n endhol='".$sdbh->real_escape_string($endhol)."', ";
				else
					$q_time .= "\n endhol=NULL, ";
				$q_time .= "\n xsyncmts='".time()."' ";
				$q_time .= "\n where centeruuid='".$sdbh->real_escape_string($centeruuid)."' "
					. "\n limit 1 "
					;
				$s_time = $sdbh->query($q_time);
				if ($s_time)
				{
					// get the site detail
					$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
					$osname = $sitedetails["sitename"];
					
					$logstring = "Site ".$osname." (centeruuid: ".$centeruuid.") operating times updated.";
					$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
				}
			}
			
			// Third part - site status requires sstat privilege
			if ($priv_sstat && ($_axsitesync_enable === false))
			{
				$sstat = $_POST["status"];
				if (is_nan($sstat))
					$sstat = 0;
				if ($sstat > 0)
					$sstat = 1;
					
				$q_stat = "update site set "
					. "\n status='".$sstat."', "
					. "\n xsyncmts='".time()."' "
					. "\n where centeruuid='".$sdbh->real_escape_string($centeruuid)."' "
					. "\n limit 1 "
					;
				$s_stat = $sdbh->query($q_stat);
				if ($s_stat)
				{
					// get the site name
					$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
					$osname = $sitedetails["sitename"];
					
					$logstring = "Site ".$osname." (centeruuid: ".$centeruuid.") status set to ".($sstat == 0 ? "unavailable" : "available");
					$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_STATUSSITE);
				}
			}
			
			// update calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
		else
		{
			//*** Create a new site
			// First part - general info requires site creation privilege and local sourcing
			if ($priv_screate && ($_axsitesync_enable === false))
			{
				// Posted info to use:
				// sitename, siteaddress, sitecontact, sitephone
				$p_sitename = trim($_POST["sitename"]);
				$p_siteaddress = trim($_POST["siteaddress"]);
				$p_siteaddrcity = trim($_POST["siteaddrcity"]);
				$p_siteaddrstate = trim($_POST["siteaddrstate"]);
				$p_siteaddrcountry = trim($_POST["siteaddrcountry"]);
				$p_siteaddrzip = trim($_POST["siteaddrzip"]);
				$p_siteregion = trim($_POST["siteregion"]);
				$p_sitecomponent = trim($_POST["sitecomponent"]);
				$p_sitetype = trim($_POST["sitetype"]);
				$p_siteactivity = trim($_POST["siteactivity"]);
				$p_sitecontact = trim($_POST["sitecontact"]);
				$p_sitephone = trim($_POST["sitephone"]);
				$p_sitetimezone = trim($_POST["sitetimezone"]);
				$p_sitenotifyemail = trim($_POST["sitenotifyemail"]);
				$p_sitevisible = trim($_POST["sitevisible"]);
				if ($p_sitevisible != 0)
					$p_sitevisible = 1;
				// Create a uuid for the site record
				$centeruuid = $myappt->makeuniqueuuid($sdbh, "site", "centeruuid");

				// Check for required fields: sitename
				if (($p_sitename == "") || ($p_sitetimezone == "") || ($p_siteaddrcity == "") || ($p_siteaddrstate == "") || ($p_sitetype == "") || ($p_siteactivity == ""))
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					$q_site = "insert into site set "
						. "\n sitename='".$sdbh->real_escape_string($p_sitename)."', "
						. "\n siteaddress='".$sdbh->real_escape_string($p_siteaddress)."', "
						. "\n siteaddrcity='".$sdbh->real_escape_string($p_siteaddrcity)."', "
						. "\n siteaddrstate='".$sdbh->real_escape_string($p_siteaddrstate)."', "
						. "\n siteaddrcountry='".$sdbh->real_escape_string($p_siteaddrcountry)."', "
						. "\n siteaddrzip='".$sdbh->real_escape_string($p_siteaddrzip)."', "
						. "\n siteregion='".$sdbh->real_escape_string($p_siteregion)."', "
						. "\n sitecomponent='".$sdbh->real_escape_string($p_sitecomponent)."', "
						. "\n sitetype='".$sdbh->real_escape_string($p_sitetype)."', "
						. "\n siteactivity='".$sdbh->real_escape_string($p_siteactivity)."', "
						. "\n sitecontactname='".$sdbh->real_escape_string($p_sitecontact)."', "
						. "\n sitecontactphone='".$sdbh->real_escape_string($p_sitephone)."', "
						. "\n sitenotifyemail='".$sdbh->real_escape_string($p_sitenotifyemail)."', "
						. "\n display='".$sdbh->real_escape_string($p_sitevisible)."', "
						. "\n timezone='".$sdbh->real_escape_string($p_sitetimezone)."', "
						. "\n centeruuid='".$centeruuid."', "
						. "\n xsyncmts='".time()."' "
						;
				
					$s_site = $sdbh->query($q_site);
					if ($s_site)
					{
						$avc = $myappt->session_createmac($centeruuid);
						$logstring = "Site ".$p_sitename." (centeruuid: ".$centeruuid.") created.";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_NEWSITE);
					}
					else
					{
						print "<script type=\"text/javascript\">alert('Error creating new site.')</script>\n";
						$logmsg = "Error creating new site: ".htmlentities($sdbh->error).".";
						$myappt->createlogentry($sdbh, $logmsg, $myappt->session_getuuid(), ALOG_ERRORSITE);
					}
				}
			}
			
			// Second part - hours, timeslot and holiday mapping requires shours privilege
			if ($priv_shours)
			{
				if ($centeruuid !== false)
				{
					// Get the posted slottime, holiday map and hours of operation.
					// A null value for the hours means not in operation and is stored as a NULL in the database
					$p_siteblockout = $_POST["siteblockout"];
					if (!is_numeric($p_siteblockout))
						$p_siteblockout = 0;
					if (($p_siteblockout < 0) || ($p_siteblockout > SITEBLOCKOUT_MAX))
						$p_siteblockout = 0;
					
					$slottime = $_POST["slottime"];
					if (!is_numeric($slottime))
						$slottime = false;
						
					$hmapuuid = $_POST["hmapuuid"];
					if (strlen($hmapuuid) != 36)
						$hmapuuid = false;
						
					$h = $_POST["ssun_h"];
					$m = $_POST["ssun_m"];
					if (($h == "") || ($m == ""))
						$startsun = false;
					else
						$startsun = $h.":".$m;
						
					$h = $_POST["esun_h"];
					$m = $_POST["esun_m"];
					if (($h == "") || ($m == ""))
						$endsun = false;
					else
						$endsun = $h.":".$m;
					
					$h = $_POST["smon_h"];
					$m = $_POST["smon_m"];
					if (($h == "") || ($m == ""))
						$startmon = false;
					else
						$startmon = $h.":".$m;
						
					$h = $_POST["emon_h"];
					$m = $_POST["emon_m"];
					if (($h == "") || ($m == ""))
						$endmon = false;
					else
						$endmon = $h.":".$m;	
					
					$h = $_POST["stue_h"];
					$m = $_POST["stue_m"];
					if (($h == "") || ($m == ""))
						$starttue = false;
					else
						$starttue = $h.":".$m;
						
					$h = $_POST["etue_h"];
					$m = $_POST["etue_m"];
					if (($h == "") || ($m == ""))
						$endtue = false;
					else
						$endtue = $h.":".$m;
					
					$h = $_POST["swed_h"];
					$m = $_POST["swed_m"];
					if (($h == "") || ($m == ""))
						$startwed = false;
					else
						$startwed = $h.":".$m;
						
					$h = $_POST["ewed_h"];
					$m = $_POST["ewed_m"];
					if (($h == "") || ($m == ""))
						$endwed = false;
					else
						$endwed = $h.":".$m;
					
					$h = $_POST["sthu_h"];
					$m = $_POST["sthu_m"];
					if (($h == "") || ($m == ""))
						$startthu = false;
					else
						$startthu = $h.":".$m;
						
					$h = $_POST["ethu_h"];
					$m = $_POST["ethu_m"];
					if (($h == "") || ($m == ""))
						$endthu = false;
					else
						$endthu = $h.":".$m;
						
					$h = $_POST["sfri_h"];
					$m = $_POST["sfri_m"];
					if (($h == "") || ($m == ""))
						$startfri = false;
					else
						$startfri = $h.":".$m;
						
					$h = $_POST["efri_h"];
					$m = $_POST["efri_m"];
					if (($h == "") || ($m == ""))
						$endfri = false;
					else
						$endfri = $h.":".$m;
						
					$h = $_POST["ssat_h"];
					$m = $_POST["ssat_m"];
					if (($h == "") || ($m == ""))
						$startsat = false;
					else
						$startsat = $h.":".$m;
						
					$h = $_POST["esat_h"];
					$m = $_POST["esat_m"];
					if (($h == "") || ($m == ""))
						$endsat = false;
					else
						$endsat = $h.":".$m;
					
					$h = $_POST["shol_h"];
					$m = $_POST["shol_m"];
					if (($h == "") || ($m == ""))
						$starthol = false;
					else
						$starthol = $h.":".$m;
						
					$h = $_POST["ehol_h"];
					$m = $_POST["ehol_m"];
					if (($h == "") || ($m == ""))
						$endhol = false;
					else
						$endhol = $h.":".$m;
					
					// update the database with the new times
					$q_time = "update site set ";
					if ($slottime !== false)
						$q_time .= "\n slottime='".$sdbh->real_escape_string($slottime)."', ";
					$q_time .= "\n siteblockout='".$sdbh->real_escape_string($p_siteblockout)."', ";					
					if ($hmapuuid !== false)
						$q_time .= "\n hmapuuid='".$sdbh->real_escape_string($hmapuuid)."', ";
					else
						$q_time .= "\n hmapuuid=NULL, ";
					if ($startsun !== false)
						$q_time .= "\n startsun='".$sdbh->real_escape_string($startsun)."', ";
					else
						$q_time .= "\n startsun=NULL, ";
					if ($endsun !== false)
						$q_time .= "\n endsun='".$sdbh->real_escape_string($endsun)."', ";
					else
						$q_time .= "\n endsun=NULL, ";
					if ($startmon !== false)
						$q_time .= "\n startmon='".$sdbh->real_escape_string($startmon)."', ";
					else
						$q_time .= "\n startmon=NULL, ";
					if ($endmon !== false)
						$q_time .= "\n endmon='".$sdbh->real_escape_string($endmon)."', ";
					else
						$q_time .= "\n endmon=NULL, ";
					if ($starttue !== false)
						$q_time .= "\n starttue='".$sdbh->real_escape_string($starttue)."', ";
					else
						$q_time .= "\n starttue=NULL, ";
					if ($endtue !== false)
						$q_time .= "\n endtue='".$sdbh->real_escape_string($endtue)."', ";
					else
						$q_time .= "\n endtue=NULL, ";
					if ($startwed !== false)
						$q_time .= "\n startwed='".$sdbh->real_escape_string($startwed)."', ";
					else
						$q_time .= "\n startwed=NULL, ";
					if ($endwed !== false)
						$q_time .= "\n endwed='".$sdbh->real_escape_string($endwed)."', ";
					else
						$q_time .= "\n endwed=NULL, ";
					if ($startthu !== false)
						$q_time .= "\n startthu='".$sdbh->real_escape_string($startthu)."', ";
					else
						$q_time .= "\n startthu=NULL, ";
					if ($endthu !== false)
						$q_time .= "\n endthu='".$sdbh->real_escape_string($endthu)."', ";
					else
						$q_time .= "\n endthu=NULL, ";
					if ($startfri !== false)
						$q_time .= "\n startfri='".$sdbh->real_escape_string($startfri)."', ";
					else
						$q_time .= "\n startfri=NULL, ";
					if ($endfri !== false)
						$q_time .= "\n endfri='".$sdbh->real_escape_string($endfri)."', ";
					else
						$q_time .= "\n endfri=NULL, ";
					if ($startsat !== false)
						$q_time .= "\n startsat='".$sdbh->real_escape_string($startsat)."', ";
					else
						$q_time .= "\n startsat=NULL, ";
					if ($endsat !== false)
						$q_time .= "\n endsat='".$sdbh->real_escape_string($endsat)."', ";
					else
						$q_time .= "\n endsat=NULL, ";
					if ($starthol !== false)
						$q_time .= "\n starthol='".$sdbh->real_escape_string($starthol)."', ";
					else
						$q_time .= "\n starthol=NULL, ";
					if ($endhol !== false)
						$q_time .= "\n endhol='".$sdbh->real_escape_string($endhol)."', ";
					else
						$q_time .= "\n endhol=NULL, ";
					$q_time .= "\n xsyncmts='".time()."' ";
					$q_time .= "\n where centeruuid='".$sdbh->real_escape_string($centeruuid)."' "
						. "\n limit 1 "
						;
					$s_time = $sdbh->query($q_time);
					if ($s_time)
					{
						$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);	
						$logstring = "Site ".$sitedetails["sitename"]." (centeruuid: ".$centeruuid.") operating times updated.";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
					}
				}
			}
			
			// Third part - site status requires sstat privilege
			if ($priv_sstat && ($_axsitesync_enable === false))
			{
				if ($centeruuid !== false)
				{
					$sstat = $_POST["status"];
					if (!is_numeric($sstat))
						$sstat = 0;
					if ($sstat > 0)
						$sstat = 1;
						
					$q_stat = "update site set "
						. "\n status='".$sstat."', "
						. "\n xsyncmts='".time()."' "
						. "\n where centeruuid='".$sdbh->real_escape_string($centeruuid)."' "
						. "\n limit 1 "
						;
					$s_stat = $sdbh->query($q_stat);
					if ($s_stat)
					{
						$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
						$logstring = "Site ".$sitedetails["sitename"]." (centeruuid: ".$centeruuid.") status set to ".($sstat == 0 ? "unavailable" : "available");
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_STATUSSITE);
					}
				}
			}
			
			// update calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
	}
	
	// Get a list of holidaymaps
	$nh = 0;
	$hlist = array();
	$q_hol = "select hmapuuid, mapname "
		. "\n from holidaymap "
		. "\n order by mapname "
		;
	$s_hol = $sdbh->query($q_hol);
	if ($s_hol)
	{
		while ($r_hol = $s_hol->fetch_assoc())
		{
			$hlist[$nh][0] = $r_hol["hmapuuid"];
			$hlist[$nh][1] = $r_hol["mapname"];
			$nh++;
		}
		$s_hol->free();
	}
	
	// Get the site detail for the form
	$sitedetails = array();
	$sitetimes = array();
	$sitetype_txt = "";
	$siteactivity_txt = "";

	if ($centeruuid !== false)
	{
		$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
	
		if (count($sitedetails) == 0)
		{
			$sdbh->close();
			print "<script type=\"text/javascript\">alert('Site not found.')</script>\n";
			print "<script type=\"text/javascript\">window.close()</script>\n";
			die();
		}
		
		// Get the type text from the list
		if ($sitedetails["sitetype"] != NULL)
		{
			foreach ($listsitetype as $x)
			{
				if (strcasecmp($x[0], $sitedetails["sitetype"]) == 0)
					$sitetype_txt = $x[1];
			}
		}
		
		// Get the activity text from the list
		if ($sitedetails["siteactivity"] != "")
		{
			foreach ($listsiteactivity as $x)
			{
				if (strcasecmp($x[0], $sitedetails["siteactivity"]) == 0)
					$siteactivity_txt = $x[1];
			}
		}
		
		// get the times and split them
		$sitetimes = $myappt->splitsitetimes($sitedetails);
	}

	$sdbh->close();
}
else 
{
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<title>Site Properties</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<?php
if ($priv_screate || $priv_sedit || $priv_shours || $priv_sstat)
{
	print "<form name=\"siteprops\" method=\"post\"  autocomplete=\"off\" action=\"".$formfile.($centeruuid === false ? "" : "?centeruuid=".urlencode($centeruuid)."&avc=".urlencode($avc))."\">\n";
}
if (($priv_screate || $priv_sedit)  && ($_axsitesync_enable === false))
{
	// can edit general detail
?>
<table border="1" cellspacing="0" cellpadding="5" width="440">
<tr height="30">
<td valign="top" align="right" colspan="2"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" tabindex="50"></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Name *</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="36" maxlength="50" tabindex="10" name="sitename" value="<?php print (isset($sitedetails["sitename"]) ? $sitedetails["sitename"] : "") ?>" />
</span></td>
</tr><tr height="60">
<td valign="top" width="200"><span class="proplabel">Site Address</span></td>
<td valign="top" width="240"><span class="proptext">
<textarea cols="30" rows="3" tabindex="20" name="siteaddress"><?php print (isset($sitedetails["siteaddress"]) ? $sitedetails["siteaddress"] : "") ?></textarea>
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site City *</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="36" maxlength="100" tabindex="25" name="siteaddrcity" value="<?php print (isset($sitedetails["siteaddrcity"]) ? $sitedetails["siteaddrcity"] : "") ?>" />
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">State/Province *</span></td>
<td valign="top" width="240"><span class="proptext">
<select name="siteaddrstate" style="width: 22em" tabindex="30">
<?php
$liststates = $myappt->sortlistarray($liststates, 1, SORT_ASC, SORT_REGULAR);
$rc = count($liststates);
for ($i = 0; $i < $rc; $i++)
{
	if (isset($sitedetails["siteaddrstate"]))
	{
		if (strcasecmp($liststates[$i][0], $sitedetails["siteaddrstate"]) == 0)
			print "<option selected value=\"".$liststates[$i][0]."\">".$liststates[$i][1]."</option>\n";
		else
			print "<option value=\"".$liststates[$i][0]."\">".$liststates[$i][1]."</option>\n";
	}
	else
		print "<option value=\"".$liststates[$i][0]."\">".$liststates[$i][1]."</option>\n";
}
?>
</select>
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Country</span></td>
<td valign="top" width="240"><span class="proptext">
<select name="siteaddrcountry" style="width: 22em" tabindex="40">
<?php
$listcountries = $myappt->sortlistarray($listcountries, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listcountries);
for ($i = 0; $i < $rc; $i++)
{
	if (isset($sitedetails["siteaddrcountry"]))
	{
		// Default to US if adding a new site
		if ($sitedetails["siteaddrcountry"] == "")
		{
			if (strcasecmp($listcountries[$i][0], "US") == 0)
				print "<option selected value=\"".$listcountries[$i][0]."\">".$listcountries[$i][1]."</option>\n";
		}
		elseif (strcasecmp($listcountries[$i][0], $sitedetails["siteaddrcountry"]) == 0)
			print "<option selected value=\"".$listcountries[$i][0]."\">".$listcountries[$i][1]."</option>\n";
		else
			print "<option value=\"".$listcountries[$i][0]."\">".$listcountries[$i][1]."</option>\n";
	}
	else
	{
		if (strcasecmp($listcountries[$i][0], "US") == 0)
				print "<option selected value=\"".$listcountries[$i][0]."\">".$listcountries[$i][1]."</option>\n";
		else
			print "<option value=\"".$listcountries[$i][0]."\">".$listcountries[$i][1]."</option>\n";
	}
}
	
?>
</select>
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Zip</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="36" maxlength="100" tabindex="25" name="siteaddrzip" value="<?php print (isset($sitedetails["siteaddrzip"]) ? $sitedetails["siteaddrzip"] : "") ?>" />
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Region</span></td>
<td valign="top" width="240"><span class="proptext">
<select name="siteregion" style="width: 22em" tabindex="50">
<?php
$listregions = $myappt->sortlistarray($listregions, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listregions);
for ($i = 0; $i < $rc; $i++)
{
	if (isset($sitedetails["siteregion"]))
	{
		if (strcasecmp($listregions[$i][0], $sitedetails["siteregion"]) == 0)
			print "<option selected value=\"".$listregions[$i][0]."\">".$listregions[$i][1]."</option>\n";
		else
			print "<option value=\"".$listregions[$i][0]."\">".$listregions[$i][1]."</option>\n";
	}
	else
		print "<option value=\"".$listregions[$i][0]."\">".$listregions[$i][1]."</option>\n";
}
?>
</select>
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Component</span></td>
<td valign="top" width="240"><span class="proptext">
<select name="sitecomponent" style="width: 22em" tabindex="50">
<?php
$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listcomponent);
for ($i = 0; $i < $rc; $i++)
{
	if (isset($sitedetails["sitecomponent"]))
	{
		if (strcasecmp($listcomponent[$i][0], $sitedetails["sitecomponent"]) == 0)
			print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
		else
			print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
	}
	else
		print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
}
?>
</select>
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Type *</span></td>
<td valign="top" width="240"><span class="proptext">
<select name="sitetype" style="width: 22em" tabindex="50">
<?php
$listsitetype = $myappt->sortlistarray($listsitetype, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listsitetype);
for ($i = 0; $i < $rc; $i++)
{
	if (isset($sitedetails["sitetype"]))
	{
		if (strcasecmp($listsitetype[$i][0], $sitedetails["sitetype"]) == 0)
			print "<option selected value=\"".$listsitetype[$i][0]."\">".$listsitetype[$i][1]."</option>\n";
		else
			print "<option value=\"".$listsitetype[$i][0]."\">".$listsitetype[$i][1]."</option>\n";
	}
	else
		print "<option value=\"".$listsitetype[$i][0]."\">".$listsitetype[$i][1]."</option>\n";
}
?>
</select>
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Activity *</span></td>
<td valign="top" width="240"><span class="proptext">
<select name="siteactivity" style="width: 22em" tabindex="50">
<?php
$listsiteactivity = $myappt->sortlistarray($listsiteactivity, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listsiteactivity);
for ($i = 0; $i < $rc; $i++)
{
	if (isset($sitedetails["siteactivity"]))
	{
		if (strcasecmp($listsiteactivity[$i][0], $sitedetails["siteactivity"]) == 0)
			print "<option selected value=\"".$listsiteactivity[$i][0]."\">".$listsiteactivity[$i][1]."</option>\n";
		else
			print "<option value=\"".$listsiteactivity[$i][0]."\">".$listsiteactivity[$i][1]."</option>\n";
	}
	else
		print "<option value=\"".$listsiteactivity[$i][0]."\">".$listsiteactivity[$i][1]."</option>\n";
}
?>
</select>
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Contact Name</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="36" maxlength="120" tabindex="70" name="sitecontact" value="<?php print (isset($sitedetails["sitecontactname"]) ? $sitedetails["sitecontactname"] : "") ?>" />
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Contact Phone</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="36" maxlength="40" tabindex="80" name="sitephone" value="<?php print (isset($sitedetails["sitecontactphone"]) ? $sitedetails["sitecontactphone"] : "") ?>" />
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Timezone</span></td>
<td valign="top" width="240"><span class="proptext">
<select name="sitetimezone" style="width: 22em" tabindex="90">
<?php
$rc = count($listtimezones);
$selected = false;
for ($i = 0; $i < $rc; $i++)
{
	if (isset($sitedetails["timezone"]))
	{
		if (strcasecmp($listtimezones[$i][0], $sitedetails["timezone"]) == 0)
		{
			print "<option selected value=\"".$listtimezones[$i][0]."\">".$listtimezones[$i][1]."</option>\n";
			$selected = true;
		}
		else
			print "<option value=\"".$listtimezones[$i][0]."\">".$listtimezones[$i][1]."</option>\n";
	}
	else
		print "<option value=\"".$listtimezones[$i][0]."\">".$listtimezones[$i][1]."</option>\n";
}
if ($selected === false)
	print "<option selected value=\"\"></option>\n";
?>
</select>
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Notify Email</span><br/>
<span class="smlgryitext">Space separated list of email addresses</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="36" maxlength="2048" tabindex="100" name="sitenotifyemail" value="<?php print (isset($sitedetails["sitenotifyemail"]) ? $sitedetails["sitenotifyemail"] : "") ?>" />
</span></td>
</tr>
</table>
<?php
}
else 
{
	// can view general detail only
?>
<table border="1" cellspacing="0" cellpadding="5" width="440">
<tr height="30">
<td valign="top" align="right" colspan="2">
<input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" tabindex="5" /></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Name *</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["sitename"]) ? $sitedetails["sitename"] : "") ?></span></td>
</tr><tr height="60">
<td valign="top" width="200"><span class="proplabel">Site Address</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["siteaddress"]) ? $sitedetails["siteaddress"] : "") ?></span></td>
</tr><tr height="60">
<td valign="top" width="200"><span class="proplabel">Site City *</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["siteaddrcity"]) ? $sitedetails["siteaddrcity"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">State/Province *</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["siteaddrstate"]) ? $sitedetails["siteaddrstate"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Country</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["siteaddrcountry"]) ? $sitedetails["siteaddrcountry"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Zip</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["siteaddrzip"]) ? $sitedetails["siteaddrzip"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Site Region</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["siteregion"]) ? $sitedetails["siteregion"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Component</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["sitecomponent"]) ? $sitedetails["sitecomponent"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Type *</span></td>
<td valign="top" width="240"><span class="proptext"><?php print $sitetype_txt ?></span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Activity *</span></td>
<td valign="top" width="240"><span class="proptext"><?php print $siteactivity_txt ?></span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Contact Name</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["sitecontactname"]) ? $sitedetails["sitecontactname"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Contact Phone</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["sitecontactphone"]) ? $sitedetails["sitecontactphone"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Timezone</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($sitedetails["timezone"]) ? $sitedetails["timezone"] : "") ?></span></td>
</tr>
</table>
<?php
}
if ($priv_shours)
{
	// can edit site times
?>
<p/>
<table cellSpacing="0" cellPadding="5" width="440" border="1">
<tr height="30">
<td colspan="3" valign="top"><span class="lblblktext">Daily Operating Hours</span></td>
</tr>
<tr height="30">
<td width="120" valign="top"><span class="proplabel">Day</span></td>
<td width="160" valign="top"><span class="proplabel">Start Time (hh:mm)</span></td>
<td width="160" valign="top"><span class="proplabel">End Time (hh:mm)</span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Sunday</span></td>
<td valign="top">
<select name="ssun_h" tabindex="200">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["ssun_h"]))
	{
		if (strcmp($v, $sitetimes["ssun_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["ssun_h"]))
{
	if ($sitetimes["ssun_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="ssun_m" tabindex="210">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["ssun_m"]))
	{
		if (strcmp($v, $sitetimes["ssun_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["ssun_m"]))
{
	if ($sitetimes["ssun_m"])
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td valign="top">
<select name="esun_h" tabindex="220">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["esun_h"]))
	{
		if (strcmp($v, $sitetimes["esun_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["esun_h"]))
{
	if ($sitetimes["esun_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="esun_m" tabindex="230">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["esun_m"]))
	{
		if (strcmp($v, $sitetimes["esun_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["esun_m"]))
{
	if ($sitetimes["esun_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td valign="top"><span class="proplabel">Monday</span></td>
<td valign="top">
<select name="smon_h" tabindex="240">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["smon_h"]))
	{
		if (strcmp($v, $sitetimes["smon_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["smon_h"]))
{
	if ($sitetimes["smon_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="smon_m" tabindex="250">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["smon_m"]))
	{
		if (strcmp($v, $sitetimes["smon_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["smon_m"]))
{
	if ($sitetimes["smon_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td valign="top">
<select name="emon_h" tabindex="260">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["emon_h"]))
	{
		if (strcmp($v, $sitetimes["emon_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["emon_h"]))
{
	if ($sitetimes["emon_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="emon_m" tabindex="270">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["emon_m"]))
	{
		if (strcmp($v, $sitetimes["emon_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["emon_m"]))
{
	if ($sitetimes["emon_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td valign="top"><span class="proplabel">Tuesday</span></td>
<td valign="top">
<select name="stue_h" tabindex="280">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["stue_h"]))
	{
		if (strcmp($v, $sitetimes["stue_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["stue_h"]))
{
	if ($sitetimes["stue_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="stue_m" tabindex="290">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["stue_m"]))
	{
		if (strcmp($v, $sitetimes["stue_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["stue_m"]))
{
	if ($sitetimes["stue_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td valign="top">
<select name="etue_h" tabindex="300">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["etue_h"]))
	{
		if (strcmp($v, $sitetimes["etue_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["etue_h"]))
{
	if ($sitetimes["etue_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="etue_m" tabindex="310">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["etue_m"]))
	{
		if (strcmp($v, $sitetimes["etue_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["etue_m"]))
{
	if ($sitetimes["etue_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td valign="top"><span class="proplabel">Wednesday</span></td>
<td valign="top">
<select name="swed_h" tabindex="320">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["swed_h"]))
	{
		if (strcmp($v, $sitetimes["swed_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["swed_h"]))
{
	if ($sitetimes["swed_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="swed_m" tabindex="330">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["swed_m"]))
	{
		if (strcmp($v, $sitetimes["swed_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["swed_m"]))
{
	if ($sitetimes["swed_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td valign="top">
<select name="ewed_h" tabindex="340">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["ewed_h"]))
	{
		if (strcmp($v, $sitetimes["ewed_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["ewed_h"]))
{
	if ($sitetimes["ewed_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="ewed_m" tabindex="350">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["ewed_m"]))
	{
		if (strcmp($v, $sitetimes["ewed_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["ewed_m"]))
{
	if ($sitetimes["ewed_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td valign="top"><span class="proplabel">Thursday</span></td>
<td valign="top">
<select name="sthu_h" tabindex="360">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["sthu_h"]))
	{
		if (strcmp($v, $sitetimes["sthu_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["sthu_h"]))
{
	if ($sitetimes["sthu_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="sthu_m" tabindex="370">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["sthu_m"]))
	{
		if (strcmp($v, $sitetimes["sthu_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["sthu_m"]))
{
	if ($sitetimes["sthu_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td valign="top">
<select name="ethu_h" tabindex="380">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["ethu_h"]))
	{
		if (strcmp($v, $sitetimes["ethu_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["ethu_h"]))
{
	if ($sitetimes["ethu_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="ethu_m" tabindex="390">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["ethu_m"]))
	{
		if (strcmp($v, $sitetimes["ethu_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["ethu_m"]))
{
	if ($sitetimes["ethu_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td valign="top"><span class="proplabel">Friday</span></td>
<td valign="top">
<select name="sfri_h" tabindex="400">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["sfri_h"]))
	{
		if (strcmp($v, $sitetimes["sfri_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["sfri_h"]))
{
	if ($sitetimes["sfri_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="sfri_m" tabindex="410">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["sfri_m"]))
	{
		if (strcmp($v, $sitetimes["sfri_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["sfri_m"]))
{
	if ($sitetimes["sfri_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td valign="top">
<select name="efri_h" tabindex="420">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["efri_h"]))
	{
		if (strcmp($v, $sitetimes["efri_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["efri_h"]))
{
	if ($sitetimes["efri_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="efri_m" tabindex="430">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["efri_m"]))
	{
		if (strcmp($v, $sitetimes["efri_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["efri_m"]))
{
	if ($sitetimes["efri_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td valign="top"><span class="proplabel">Saturday</span></td>
<td valign="top">
<select name="ssat_h" tabindex="440">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["ssat_h"]))
	{
		if (strcmp($v, $sitetimes["ssat_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["ssat_h"]))
{
	if ($sitetimes["ssat_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="ssat_m" tabindex="450">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["ssat_m"]))
	{
		if (strcmp($v, $sitetimes["ssat_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["ssat_m"]))
{
	if ($sitetimes["ssat_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td valign="top">
<select name="esat_h" tabindex="460">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["esat_h"]))
	{
		if (strcmp($v, $sitetimes["esat_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["esat_h"]))
{
	if ($sitetimes["esat_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="esat_m" tabindex="470">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["esat_m"]))
	{
		if (strcmp($v, $sitetimes["esat_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["esat_m"]))
{
	if ($sitetimes["esat_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td valign="top"><span class="proplabel">Holidays</span></td>
<td valign="top">
<select name="shol_h" tabindex="480">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["shol_h"]))
	{
		if (strcmp($v, $sitetimes["shol_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["shol_h"]))
{
	if ($sitetimes["shol_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="shol_m" tabindex="490">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["shol_m"]))
	{
		if (strcmp($v, $sitetimes["shol_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["shol_m"]))
{
	if ($sitetimes["shol_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td valign="top">
<select name="ehol_h" tabindex="500">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["ehol_h"]))
	{
		if (strcmp($v, $sitetimes["ehol_h"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["ehol_h"]))
{
	if ($sitetimes["ehol_h"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="ehol_m" tabindex="510">
<?php
for ($i = 0; $i < 12; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitetimes["ehol_m"]))
	{
		if (strcmp($v, $sitetimes["ehol_m"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitetimes["ehol_m"]))
{
	if ($sitetimes["ehol_m"] == "")
		print "<option selected value=\"\">-</option>\n";
	else
		print "<option value=\"\">-</option>\n";
}
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td valign="top"><span class="proplabel">Slot Time (min)*</span></td>
<td colspan="2" valign="top">
<select name="slottime" tabindex="520" style="width:18em;">
<?php
for ($i = 1; $i < 13; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (isset($sitedetails["slottime"]))
	{
		if (strcmp($v, $sitedetails["slottime"]) == 0)
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		else
			print "<option value=\"".$v."\">".$v."</option>\n";
	}
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if (isset($sitedetails["slottime"]))
{
	if ($sitedetails["slottime"] == "")
		print "<option selected value=\"\"></option>\n";
	else
		print "<option value=\"\"></option>\n";
}
else
	print "<option value=\"\"></option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td valign="top"><span class="proplabel">Blockout (hours)</span></td>
<td colspan="2" valign="top">
<select name="siteblockout" tabindex="530" style="width:18em;">
<?php
for ($i = 0; $i < (SITEBLOCKOUT_MAX +1); $i++)
{
	if (isset($sitedetails["siteblockout"]))
	{
		if ($i == $sitedetails["siteblockout"])
			print "<option selected value=\"".$i."\">".$i."</option>\n";
		else
			print "<option value=\"".$i."\">".$i."</option>\n";
	}
	else
		print "<option value=\"".$i."\">".$i."</option>\n";
}
if (isset($sitedetails["siteblockout"]))
{
	if ($sitedetails["siteblockout"] == NULL)
		print "<option selected value=\"0\">0</option>\n";
}
else
	print "<option selected value=\"0\">0</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td valign="top"><span class="proplabel">Holiday Map</span></td>
<td colspan="2" valign="top">
<select name="hmapuuid" tabindex="540" style="width:18em;">
<?php
for ($i = 0; $i < $nh; $i++)
{
	if (isset($sitedetails["hmapuuid"]))
	{
		if ($hlist[$i][0] == $sitedetails["hmapuuid"])
			print "<option selected value=\"".$hlist[$i][0]."\">".$hlist[$i][1]."</option>\n";
		else
			print "<option value=\"".$hlist[$i][0]."\">".$hlist[$i][1]."</option>\n";
	}
	else
		print "<option value=\"".$hlist[$i][0]."\">".$hlist[$i][1]."</option>\n";
}
if (isset($sitedetails["hmapuuid"]))
{
	if ($sitedetails["hmapuuid"] == "")
		print "<option selected value=\"\"></option>\n";
	else
		print "<option value=\"\"></option>\n";
}
else
	print "<option value=\"\"></option>\n";
?>
</select>
</td></tr>
</table>
<?php
}
else
{
	// show the values only
?>
<p/>
<table cellspacing="0" cellpadding="5" width="440" border="1">
<tr height="30">
<td colspan="3" valign="top"><span class="lblblktext">Daily Operating Hours</span></td>
</tr>
<tr height="30">
<td width="120" valign="top"><span class="proplabel">Day</span></td>
<td width="160" valign="top"><span class="proplabel">Start Time (hh:mm)</span></td>
<td width="160" valign="top"><span class="proplabel">End Time (hh:mm)</span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Sunday</span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["ssun_h"]) ? $sitetimes["ssun_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["ssun_m"]) ? $sitetimes["ssun_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["esun_h"]) ? $sitetimes["esun_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["esun_m"]) ? $sitetimes["esun_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Monday</span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["smon_h"]) ? $sitetimes["smon_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["smon_m"]) ? $sitetimes["smon_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["emon_h"]) ? $sitetimes["emon_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["emon_m"]) ? $sitetimes["emon_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Tuesday</span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["stue_h"]) ? $sitetimes["stue_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["stue_m"]) ? $sitetimes["stue_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["etue_h"]) ? $sitetimes["etue_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["etue_m"]) ? $sitetimes["etue_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Wednesday</span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["swed_h"]) ? $sitetimes["swed_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["swed_m"]) ? $sitetimes["swed_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["ewed_h"]) ? $sitetimes["ewed_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["ewed_m"]) ? $sitetimes["ewed_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Thursday</span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["sthu_h"]) ? $sitetimes["sthu_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["sthu_m"]) ? $sitetimes["sthu_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["ethu_h"]) ? $sitetimes["ethu_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["ethu_m"]) ? $sitetimes["ethu_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Friday</span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["sfri_h"]) ? $sitetimes["sfri_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["sfri_m"]) ? $sitetimes["sfri_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["efri_h"]) ? $sitetimes["efri_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["efri_m"]) ? $sitetimes["efri_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Saturday</span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["ssat_h"]) ? $sitetimes["ssat_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["ssat_m"]) ? $sitetimes["ssat_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["esat_h"]) ? $sitetimes["esat_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["esat_m"]) ? $sitetimes["esat_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Holidays</span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["shol_h"]) ? $sitetimes["shol_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["shol_m"]) ? $sitetimes["shol_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
<td valign="top"><span class="proptext"><?php print str_pad((isset($sitetimes["ehol_h"]) ? $sitetimes["ehol_h"] : ""), 2, "0", STR_PAD_LEFT).":".str_pad((isset($sitetimes["ehol_m"]) ? $sitetimes["ehol_m"] : ""), 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Slot Time (min)*</span></td>
<td colspan="2" valign="top"><span class="proptext"><?php print (isset($sitedetails["slottime"]) ? $sitedetails["slottime"] : "") ?></span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Blockout (hours)</span></td>
<td colspan="2" valign="top"><span class="proptext"><?php print (isset($sitedetails["siteblockout"]) ? $sitedetails["siteblockout"] : "") ?></span></td>
</tr>
<tr height="30">
<td valign="top"><span class="proplabel">Holiday Map</span></td>
<td colspan="2" valign="top"><span class="proptext"><?php print (isset($sitedetails["mapname"]) ? $sitedetails["mapname"] : "") ?></span></td>
</tr>
</table>
<?php
}

if (isset($sitedetails["status"]))
	$sstat = $sitedetails["status"];
else
	$sstat = 0;

if ($priv_sstat && ($_axsitesync_enable === false))
{
	// can edit site status
?>
<p/>
<table cellSpacing="0" cellPadding="5" width="440" border="0">
<tr height="30">
<td width="120" valign="top"><span class="proplabel">Site Status</span></td>
<td width="320" valign="top">
<select name="status" tabindex="600" style="width:18em;">
<option <?php print ($sstat == 0 ? "selected" : "") ?> value="0">Unavailable</option>
<option <?php print ($sstat == 1 ? "selected" : "") ?> value="1">Available</option>
</select>
</td></tr>
</table>
<?php
}
else 
{
	// show the status only
?>
<p/>
<table cellSpacing="0" cellPadding="5" width="440" border="0">
<tr height="30">
<td width="120" valign="top"><span class="proplabel">Site Status</span></td>
<td width="320" valign="top"><span class="proptext"><?php print ($sstat == 1 ? "Available" : "Unavailable") ?><span></td>
</tr>
</table>
<?php
}

if (isset($sitedetails["display"]))
	$sitevisible = $sitedetails["display"];
else
	$sitevisible = 0;

if ($priv_screate || $priv_sedit)
{
	// can edit site visibility in centers page
?>
<p/>
<table cellSpacing="0" cellPadding="5" width="440" border="0">
<tr height="30">
<td width="120" valign="top"><span class="proplabel">Site visible in centers page</span></td>
<td width="320" valign="top">
<select name="sitevisible" tabindex="600" style="width:18em;">
<option <?php print ($sitevisible == 0 ? "selected" : "") ?> value="0">No</option>
<option <?php print ($sitevisible == 1 ? "selected" : "") ?> value="1">Yes</option>
</select>
</td></tr>
<tr height="30">
<td colspan="2" valign="top"><span class="proplabel">* Required items.</span></td>
</tr>
</table>
<?php
}
else 
{
	// show the site visibility only
?>
<p/>
<table cellSpacing="0" cellPadding="5" width="440" border="0">
<tr height="30">
<td width="120" valign="top"><span class="proplabel">Site visible in centers page</span></td>
<td width="320" valign="top"><span class="proptext"><?php print ($sitevisible == 1 ? "Yes" : "No") ?><span></td>
</tr>
<tr height="30">
<td colspan="2" valign="top"><span class="proplabel">* Required items.</span></td>
</tr>
</table>
<?php
}

// Add the buttons if privileges allow
if ((($priv_screate || $priv_sedit) && ($_axsitesync_enable === false)) || $priv_shours || ($priv_sstat && ($_axsitesync_enable === false)))
{
	// submit button and end of form
	print "<table cellSpacing=\"0\" cellPadding=\"0\" width=\"440\" border=\"0\">\n";
	print "<tr height=\"40\">\n";
	print "<td width=\"120\" valign=\"center\" align=\"left\">\n";
	print "<input type=\"submit\" name=\"submit_site\" class=\"btntext\" value=\"Save\" tabindex=\"45\">\n";
	print "</form>\n";
	print "</td>\n";
	if ($priv_shours && ($centeruuid !== false))
	{
		$siteavc = $myappt->session_createmac($centeruuid);
		print "<td width=\"120\" valign=\"center\" align=\"left\">\n";
		print "<input type=\"button\" name=\"btn_aex\" class=\"btntext\" value=\"Exceptions\" onclick=\"javascript:popupOpener('pop-siteexception.php?center=".urlencode($centeruuid)."&avc=".urlencode($siteavc)."', 'availexcept', 400, 400)\" title=\"Add exceptions to the site availability.\">\n";
		print "</td>\n";
	}
	else
		print "<td width=\"120\" valign=\"center\" align=\"left\">&nbsp;</td>\n";
	
	if ($priv_shours && ($centeruuid !== false))
	{
		$siteavc = $myappt->session_createmac($centeruuid);
		print "<td width=\"120\" valign=\"center\" align=\"left\">\n";
		print "<input type=\"button\" name=\"btn_slo\" class=\"btntext\" value=\"Open Dates\" onclick=\"javascript:popupOpener('pop-siteopenlimits.php?center=".urlencode($centeruuid)."&avc=".urlencode($siteavc)."', 'siteopenlimits', 400, 400)\" title=\"Restrict site operating dates.\">\n";
		print "</td>\n";
	}
	else
		print "<td width=\"120\" valign=\"center\" align=\"left\">&nbsp;</td>\n";
	
	print "</tr>\n";
	print "</table>\n";
}

?>
</body></html>