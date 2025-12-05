<?php
// $Id:$

// popup to edit site details
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-editsitedetail.html";
// the geometry required for this popup
$windowx = 500;
$windowy = 1200;

include("config.php");
include("vec-clappointments.php");
include('../appcore/vec-clforms.php');
$myform = new authentxforms();
$myappt = new authentxappointments();
date_default_timezone_set(DATE_TIMEZONE);

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
// siteid: site id (optional). If not supplied it is assumed a new site is being added.
if (isset($_GET["siteid"]))
{
	$siteid = $_GET["siteid"];
	// check and sanitise it
	if (!is_numeric($siteid))
	{
		print "<script type=\"text/javascript\">alert('Invalid siteid.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	
	// AVC is only present when the SITEID is submitted, to verify it
	if (isset($_GET["avc"]))
		$avc = $_GET["avc"];
	else
	{
		print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// Check the AVC mac for validity
	$testavc = $myappt->session_createmac($siteid);
	
	if (strcasecmp($avc, $testavc) != 0)
	{
		print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
	$siteid = false;

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
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	// Process submissions here
	if (isset($_POST["submit_site"]))
	{
		if ($siteid !== false)
		{
			// Update an existing site
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
						. "\n sitename='".$dbh->real_escape_string($p_sitename)."', "
						. "\n siteaddress='".$dbh->real_escape_string($p_siteaddress)."', "
						. "\n siteaddrcity='".$dbh->real_escape_string($p_siteaddrcity)."', "
						. "\n siteaddrstate='".$dbh->real_escape_string($p_siteaddrstate)."', "
						. "\n siteaddrcountry='".$dbh->real_escape_string($p_siteaddrcountry)."', "
						. "\n siteaddrzip='".$dbh->real_escape_string($p_siteaddrzip)."', "
						. "\n siteregion='".$dbh->real_escape_string($p_siteregion)."', "
						. "\n sitecomponent='".$dbh->real_escape_string($p_sitecomponent)."', "
						. "\n sitetype='".$dbh->real_escape_string($p_sitetype)."', "
						. "\n siteactivity='".$dbh->real_escape_string($p_siteactivity)."', "
						. "\n sitecontactname='".$dbh->real_escape_string($p_sitecontact)."', "
						. "\n sitecontactphone='".$dbh->real_escape_string($p_sitephone)."', "
						. "\n sitenotifyemail='".$dbh->real_escape_string($p_sitenotifyemail)."', "
						. "\n display='".$dbh->real_escape_string($p_sitevisible)."', "
						. "\n timezone='".$dbh->real_escape_string($p_sitetimezone)."' "
						. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
						. "\n limit 1 "
						;
				
					$s_site = $dbh->query($q_site);
					if ($s_site)
					{
						$logstring = "Site ".$p_sitename." (".$siteid.") updated.";
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
					}
					else
					{
						$logstring = "Error updating site ".$p_sitename." (".$siteid.").";
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
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
					
				$hmapid = $_POST["hmapid"];
				if ($hmapid == "")
					$hmapid = false;
					
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
					$q_time .= "\n slottime='".$dbh->real_escape_string($slottime)."', ";
				$q_time .= "\n siteblockout='".$dbh->real_escape_string($p_siteblockout)."', ";
				if ($hmapid !== false)
					$q_time .= "\n hmapid='".$dbh->real_escape_string($hmapid)."', ";
				else
					$q_time .= "\n hmapid=NULL, ";
				if ($startsun !== false)
					$q_time .= "\n startsun='".$dbh->real_escape_string($startsun)."', ";
				else
					$q_time .= "\n startsun=NULL, ";
				if ($endsun !== false)
					$q_time .= "\n endsun='".$dbh->real_escape_string($endsun)."', ";
				else
					$q_time .= "\n endsun=NULL, ";
				if ($startmon !== false)
					$q_time .= "\n startmon='".$dbh->real_escape_string($startmon)."', ";
				else
					$q_time .= "\n startmon=NULL, ";
				if ($endmon !== false)
					$q_time .= "\n endmon='".$dbh->real_escape_string($endmon)."', ";
				else
					$q_time .= "\n endmon=NULL, ";
				if ($starttue !== false)
					$q_time .= "\n starttue='".$dbh->real_escape_string($starttue)."', ";
				else
					$q_time .= "\n starttue=NULL, ";
				if ($endtue !== false)
					$q_time .= "\n endtue='".$dbh->real_escape_string($endtue)."', ";
				else
					$q_time .= "\n endtue=NULL, ";
				if ($startwed !== false)
					$q_time .= "\n startwed='".$dbh->real_escape_string($startwed)."', ";
				else
					$q_time .= "\n startwed=NULL, ";
				if ($endwed !== false)
					$q_time .= "\n endwed='".$dbh->real_escape_string($endwed)."', ";
				else
					$q_time .= "\n endwed=NULL, ";
				if ($startthu !== false)
					$q_time .= "\n startthu='".$dbh->real_escape_string($startthu)."', ";
				else
					$q_time .= "\n startthu=NULL, ";
				if ($endthu !== false)
					$q_time .= "\n endthu='".$dbh->real_escape_string($endthu)."', ";
				else
					$q_time .= "\n endthu=NULL, ";
				if ($startfri !== false)
					$q_time .= "\n startfri='".$dbh->real_escape_string($startfri)."', ";
				else
					$q_time .= "\n startfri=NULL, ";
				if ($endfri !== false)
					$q_time .= "\n endfri='".$dbh->real_escape_string($endfri)."', ";
				else
					$q_time .= "\n endfri=NULL, ";
				if ($startsat !== false)
					$q_time .= "\n startsat='".$dbh->real_escape_string($startsat)."', ";
				else
					$q_time .= "\n startsat=NULL, ";
				if ($endsat !== false)
					$q_time .= "\n endsat='".$dbh->real_escape_string($endsat)."', ";
				else
					$q_time .= "\n endsat=NULL, ";
				if ($starthol !== false)
					$q_time .= "\n starthol='".$dbh->real_escape_string($starthol)."', ";
				else
					$q_time .= "\n starthol=NULL, ";
				if ($endhol !== false)
					$q_time .= "\n endhol='".$dbh->real_escape_string($endhol)."' ";
				else
					$q_time .= "\n endhol=NULL ";
				$q_time .= "\n where siteid='".$dbh->real_escape_string($siteid)."' "
					. "\n limit 1 "
					;
				$s_time = $dbh->query($q_time);
				if ($s_time)
				{
					// get the site name
					$q_os = "select siteid, sitename "
						. "\n from site "
						. "\n where siteid='".$siteid."' "
						;
					$s_os = $dbh->query($q_os);
					$r_os = $s_os->fetch_assoc();
					$osname = $r_os["sitename"];
					$s_os->free();
					
					$logstring = "Site ".$osname." (".$siteid.") operating times updated.";
					$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
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
					. "\n status='".$sstat."' "
					. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
					. "\n limit 1 "
					;
				$s_stat = $dbh->query($q_stat);
				if ($s_stat)
				{
					// get the site name
					$q_os = "select siteid, sitename "
						. "\n from site "
						. "\n where siteid='".$siteid."' "
						;
					$s_os = $dbh->query($q_os);
					$r_os = $s_os->fetch_assoc();
					$osname = $r_os["sitename"];
					$s_os->free();
					
					$logstring = "Site ".$osname." (".$siteid.") status set to ".($sstat == 0 ? "unavailable" : "available");
					$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_STATUSSITE);
				}
			}
			
			// update calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
		else
		{
			// Create a new site
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

				// Check for required fields: sitename
				if (($p_sitename == "") || ($p_sitetimezone == "") || ($p_siteaddrcity == "") || ($p_siteaddrstate == "") || ($p_sitetype == "") || ($p_siteactivity == ""))
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					$q_site = "insert into site "
						. "\n (sitename, "
						. "\n siteaddress, "
						. "\n siteaddrcity, "
						. "\n siteaddrstate, "
						. "\n siteaddrcountry, "
						. "\n siteaddrzip, "
						. "\n siteregion, "
						. "\n sitecomponent, "
						. "\n sitetype, "
						. "\n siteactivity, "
						. "\n sitecontactname, "
						. "\n sitecontactphone, "
						. "\n sitenotifyemail, "
						. "\n display, "
						. "\n timezone) "
						. "\n values "
						. "\n ('".$dbh->real_escape_string($p_sitename)."', "
						. "\n '".$dbh->real_escape_string($p_siteaddress)."', "
						. "\n '".$dbh->real_escape_string($p_siteaddrcity)."', "
						. "\n '".$dbh->real_escape_string($p_siteaddrstate)."', "
						. "\n '".$dbh->real_escape_string($p_siteaddrcountry)."', "
						. "\n '".$dbh->real_escape_string($p_siteaddrzip)."', "
						. "\n '".$dbh->real_escape_string($p_siteregion)."', "
						. "\n '".$dbh->real_escape_string($p_sitecomponent)."', "
						. "\n '".$dbh->real_escape_string($p_sitetype)."', "
						. "\n '".$dbh->real_escape_string($p_siteactivity)."', "
						. "\n '".$dbh->real_escape_string($p_sitecontact)."', "
						. "\n '".$dbh->real_escape_string($p_sitephone)."', "
						. "\n '".$dbh->real_escape_string($p_sitenotifyemail)."', "
						. "\n '".$dbh->real_escape_string($p_sitevisible)."', "
						. "\n '".$dbh->real_escape_string($p_sitetimezone)."') "
						;
				
					$s_site = $dbh->query($q_site);
					if ($s_site)
					{
						$siteid = $dbh->insert_id;
						$avc = $myappt->session_createmac($siteid);
						$logstring = "Site ".$p_sitename." (".$siteid.") created.";
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_NEWSITE);
					}
					else
					{
						print "<script type=\"text/javascript\">alert('Error creating new site.')</script>\n";
						$logmsg = "Error creating new site: ".htmlentities($dbh->error).".";
						$myappt->createlogentry($dbh, $logmsg, $myappt->session_getuuid(), ALOG_ERRORSITE);
					}
				}
			}
			
			// Second part - hours, timeslot and holiday mapping requires shours privilege
			if ($priv_shours)
			{
				if ($siteid !== false)
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
						
					$hmapid = $_POST["hmapid"];
					if (!is_numeric($hmapid))
						$hmapid = false;
						
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
						$q_time .= "\n slottime='".$dbh->real_escape_string($slottime)."', ";
					$q_time .= "\n siteblockout='".$dbh->real_escape_string($p_siteblockout)."', ";					
					if ($hmapid !== false)
						$q_time .= "\n hmapid='".$dbh->real_escape_string($hmapid)."', ";
					else
						$q_time .= "\n hmapid=NULL, ";
					if ($startsun !== false)
						$q_time .= "\n startsun='".$dbh->real_escape_string($startsun)."', ";
					else
						$q_time .= "\n startsun=NULL, ";
					if ($endsun !== false)
						$q_time .= "\n endsun='".$dbh->real_escape_string($endsun)."', ";
					else
						$q_time .= "\n endsun=NULL, ";
					if ($startmon !== false)
						$q_time .= "\n startmon='".$dbh->real_escape_string($startmon)."', ";
					else
						$q_time .= "\n startmon=NULL, ";
					if ($endmon !== false)
						$q_time .= "\n endmon='".$dbh->real_escape_string($endmon)."', ";
					else
						$q_time .= "\n endmon=NULL, ";
					if ($starttue !== false)
						$q_time .= "\n starttue='".$dbh->real_escape_string($starttue)."', ";
					else
						$q_time .= "\n starttue=NULL, ";
					if ($endtue !== false)
						$q_time .= "\n endtue='".$dbh->real_escape_string($endtue)."', ";
					else
						$q_time .= "\n endtue=NULL, ";
					if ($startwed !== false)
						$q_time .= "\n startwed='".$dbh->real_escape_string($startwed)."', ";
					else
						$q_time .= "\n startwed=NULL, ";
					if ($endwed !== false)
						$q_time .= "\n endwed='".$dbh->real_escape_string($endwed)."', ";
					else
						$q_time .= "\n endwed=NULL, ";
					if ($startthu !== false)
						$q_time .= "\n startthu='".$dbh->real_escape_string($startthu)."', ";
					else
						$q_time .= "\n startthu=NULL, ";
					if ($endthu !== false)
						$q_time .= "\n endthu='".$dbh->real_escape_string($endthu)."', ";
					else
						$q_time .= "\n endthu=NULL, ";
					if ($startfri !== false)
						$q_time .= "\n startfri='".$dbh->real_escape_string($startfri)."', ";
					else
						$q_time .= "\n startfri=NULL, ";
					if ($endfri !== false)
						$q_time .= "\n endfri='".$dbh->real_escape_string($endfri)."', ";
					else
						$q_time .= "\n endfri=NULL, ";
					if ($startsat !== false)
						$q_time .= "\n startsat='".$dbh->real_escape_string($startsat)."', ";
					else
						$q_time .= "\n startsat=NULL, ";
					if ($endsat !== false)
						$q_time .= "\n endsat='".$dbh->real_escape_string($endsat)."', ";
					else
						$q_time .= "\n endsat=NULL, ";
					if ($starthol !== false)
						$q_time .= "\n starthol='".$dbh->real_escape_string($starthol)."', ";
					else
						$q_time .= "\n starthol=NULL, ";
					if ($endhol !== false)
						$q_time .= "\n endhol='".$dbh->real_escape_string($endhol)."' ";
					else
						$q_time .= "\n endhol=NULL ";
					$q_time .= "\n where siteid='".$dbh->real_escape_string($siteid)."' "
						. "\n limit 1 "
						;
					$s_time = $dbh->query($q_time);
					if ($s_time)
					{
						// get the site name
						$q_os = "select siteid, sitename "
							. "\n from site "
							. "\n where siteid='".$siteid."' "
							;
						$s_os = $dbh->query($q_os);
						$r_os = $s_os->fetch_assoc();
						$osname = $r_os["sitename"];
						$logstring = "Site ".$osname." (".$siteid.") operating times updated.";
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
						$s_os->free();
					}
				}
			}
			
			// Third part - site status requires sstat privilege
			if ($priv_sstat && ($_axsitesync_enable === false))
			{
				if ($siteid !== false)
				{
					$sstat = $_POST["status"];
					if (!is_numeric($sstat))
						$sstat = 0;
					if ($sstat > 0)
						$sstat = 1;
						
					$q_stat = "update site set "
						. "\n status='".$sstat."' "
						. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
						. "\n limit 1 "
						;
					$s_stat = $dbh->query($q_stat);
					if ($s_stat)
					{
						// get the site name
						$q_os = "select siteid, sitename "
							. "\n from site "
							. "\n where siteid='".$siteid."' "
							;
						$s_os = $dbh->query($q_os);
						$r_os = $s_os->fetch_assoc();
						$osname = $r_os["sitename"];
						$logstring = "Site ".$osname." (".$siteid.") status set to ".($sstat == 0 ? "unavailable" : "available");
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_STATUSSITE);
					}
				}
			}
			
			// update calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
	}
	
	// Get a list of holidaymaps
	$q_hol = "select hmapid, mapname "
		. "\n from holidaymap "
		. "\n order by mapname "
		;
	$s_hol = $dbh->query($q_hol);
	$n_hol = $s_hol->num_rows;
	
	// Get the site detail for the form
	if ($siteid !== false)
	{
		$q_s = "select * from site "
			. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
			;
		$s_s = $dbh->query($q_s);
		$n_s = $s_s->num_rows;
		if ($n_s == 0)
		{
			$s_s->free();
			$s_hol->free();
			$dbh->close();
			print "<script type=\"text/javascript\">alert('Site not found.')</script>\n";
			print "<script type=\"text/javascript\">window.close()</script>\n";
			die();
		}
		$r_s = $s_s->fetch_assoc();
		$sitename = $r_s["sitename"];
		$siteaddress = $r_s["siteaddress"];
		$siteaddrcity = $r_s["siteaddrcity"];
		$siteaddrstate = $r_s["siteaddrstate"];
		$siteaddrcountry = $r_s["siteaddrcountry"];
		$siteaddrzip = $r_s["siteaddrzip"];
		$siteregion = $r_s["siteregion"];
		$sitecomponent = $r_s["sitecomponent"];
		$sitetype = $r_s["sitetype"];
		$siteactivity = $r_s["siteactivity"];
		$sitecontact = $r_s["sitecontactname"];
		$sitephone = $r_s["sitecontactphone"];
		$sitenotifyemail = $r_s["sitenotifyemail"];
		$siteblockout = $r_s["siteblockout"];
		$sstat = $r_s["status"];
		$slottime = $r_s["slottime"];
		$sitetimezone = $r_s["timezone"];
		$sitevisible = $r_s["display"];
		$hmapid = $r_s["hmapid"];
		
		// get the holmapname
		if ($hmapid != NULL)
		{
			$q_h = "select hmapid, mapname "
				. "\n from holidaymap "
				. "\n where hmapid='".$hmapid."' "
				;
			$s_h = $dbh->query($q_h);
			if ($s_h)
			{
				$r_h = $s_h->fetch_assoc();
				$holmapname = $r_h["mapname"];
				$s_h->free();
			}
			else
				$holmapname = "";
		}
		
		// Get the type text from the list
		$sitetype_txt = "";
		if ($sitetype != "")
		{
			foreach ($listsitetype as $x)
			{
				if (strcasecmp($x[0], $sitetype) == 0)
					$sitetype_txt = $x[1];
			}
		}
		
		// Get the activity text from the list
		$siteactivity_txt = "";
		if ($siteactivity != "")
		{
			foreach ($listsiteactivity as $x)
			{
				if (strcasecmp($x[0], $siteactivity) == 0)
					$siteactivity_txt = $x[1];
			}
		}
		
		// get the times and split them
		$v = $r_s["startsun"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$ssun_h = $t[0];
			$ssun_m = $t[1];
		}
		else
		{
			$ssun_h = "";
			$ssun_m = "";
		}
		$v = $r_s["endsun"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$esun_h = $t[0];
			$esun_m = $t[1];
		}
		else
		{
			$esun_h = "";
			$esun_m = "";
		}
		$v = $r_s["startmon"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$smon_h = $t[0];
			$smon_m = $t[1];
		}
		else
		{
			$smon_h = "";
			$smon_m = "";
		}
		$v = $r_s["endmon"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$emon_h = $t[0];
			$emon_m = $t[1];
		}
		else
		{
			$emon_h = "";
			$emon_m = "";
		}
		$v = $r_s["starttue"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$stue_h = $t[0];
			$stue_m = $t[1];
		}
		else
		{
			$stue_h = "";
			$stue_m = "";
		}
		$v = $r_s["endtue"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$etue_h = $t[0];
			$etue_m = $t[1];
		}
		else
		{
			$etue_h = "";
			$etue_m = "";
		}
		$v = $r_s["startwed"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$swed_h = $t[0];
			$swed_m = $t[1];
		}
		else
		{
			$swed_h = "";
			$swed_m = "";
		}
		$v = $r_s["endwed"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$ewed_h = $t[0];
			$ewed_m = $t[1];
		}
		else
		{
			$ewed_h = "";
			$ewed_m = "";
		}
		$v = $r_s["startthu"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$sthu_h = $t[0];
			$sthu_m = $t[1];
		}
		else
		{
			$sthu_h = "";
			$sthu_m = "";
		}
		$v = $r_s["endthu"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$ethu_h = $t[0];
			$ethu_m = $t[1];
		}
		else
		{
			$ethu_h = "";
			$ethu_m = "";
		}
		$v = $r_s["startfri"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$sfri_h = $t[0];
			$sfri_m = $t[1];
		}
		else
		{
			$sfri_h = "";
			$sfri_m = "";
		}
		$v = $r_s["endfri"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$efri_h = $t[0];
			$efri_m = $t[1];
		}
		else
		{
			$efri_h = "";
			$efri_m = "";
		}
		$v = $r_s["startsat"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$ssat_h = $t[0];
			$ssat_m = $t[1];
		}
		else
		{
			$ssat_h = "";
			$ssat_m = "";
		}
		$v = $r_s["endsat"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$esat_h = $t[0];
			$esat_m = $t[1];
		}
		else
		{
			$esat_h = "";
			$esat_m = "";
		}
		$v = $r_s["starthol"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$shol_h = $t[0];
			$shol_m = $t[1];
		}
		else
		{
			$shol_h = "";
			$shol_m = "";
		}
		$v = $r_s["endhol"];
		if ($v != NULL)
		{
			$t = explode(":", $v);
			$ehol_h = $t[0];
			$ehol_m = $t[1];
		}
		else
		{
			$ehol_h = "";
			$ehol_m = "";
		}
		$s_s->free();
	}
	else
	{
		$sitename = "";
		$siteaddress = "";
		$siteaddrcity = "";
		$siteaddrstate = "";
		$siteaddrcountry = "";
		$siteaddrzip = "";
		$siteregion = "";
		$sitecomponent = "";
		$sitetype = "";
		$sitetype_txt = "";
		$siteactivity = "";
		$siteactivity_txt = "";
		$sitecontact = "";
		$sitephone = "";
		$sitenotifyemail = "";
		$siteblockout = 0;
		$sstat = "";
		$slottime = "";
		$sitetimezone = "";
		$sitetzone = 0;
		$sitevisible = 1;
		$siteisdst = 0;
		$hmapid = "";
		
		$ssun_h = "";
		$ssun_m = "";
		$smon_h = "";
		$smon_m = "";
		$stue_h = "";
		$stue_m = "";
		$swed_h = "";
		$swed_m = "";
		$sthu_h = "";
		$sthu_m = "";
		$sfri_h = "";
		$sfri_m = "";
		$ssat_h = "";
		$ssat_m = "";
		$shol_h = "";
		$shol_m = "";
		
		$esun_h = "";
		$esun_m = "";
		$emon_h = "";
		$emon_m = "";
		$etue_h = "";
		$etue_m = "";
		$ewed_h = "";
		$ewed_m = "";
		$ethu_h = "";
		$ethu_m = "";
		$efri_h = "";
		$efri_m = "";
		$esat_h = "";
		$esat_m = "";
		$ehol_h = "";
		$ehol_m = "";
	}
}
else 
{
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}
// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams['css'] = array_merge($cfg_stdcss, ['../appcore/css/authentx.css']);
$headparams["jscript_file"] = $cfg_stdjscript;

if (AJAX_SESSIONREFRESH_ENABLE === true)
{
	$headparams["jscript_file"][] = "../appcore/scripts/js-ajax_sessionrefresh.js";
	$headparams["jscript_local"][] = "xhrservice = '".AJAX_SESSIONREFRESH_SERVICE."';\n"
								. "refreshinterval='".(SESSION_TIMEOUT - SESSION_TIMEOUT_GRACE)."';\n"
								. "gracetime='".SESSION_TIMEOUT_GRACE."';\n"
								. "sessionTime='".SESSION_TIMEOUT."';\n";
}
$myform->frmrender_head($headparams);

$bodyparams = array();
if (AJAX_SESSIONREFRESH_ENABLE === true)
{
	$bodyparams["onload"][] = "startRefresh()";
	$bodyparams["onload"][] = "startSessionTimer()";
}

if (AJAX_APPT_ENABLE === true)
{
	print "<script language=\"javascript\" src=\"../appcore/scripts/js-ajax_appt.js\"></script>\n";
	print "<script language=\"javascript\">\n";
	print "xhrservice = '".AJAX_APPTSERVICE."'\n";
	if ($siteid !== false)
		print "site='".urlencode($siteid)."'\n";
	if ($wkstamp !== false)
		print "wk='".urlencode($wkstamp)."'\n";
	print "refreshinterval='".$refresh_appt."'\n";
	print "</script>\n";
}

if (AJAX_APPT_ENABLE === true)
{
	if ($refresh_appt !== false)
		print "<body onload='startRefresh();f_init()'>\n";
	else 
		print "<body onload='f_init()'>\n";
}
else 
	print "<body onload='f_init()'>\n";

$myform->frmrender_bodytag($bodyparams);
print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n";

if ($priv_screate || $priv_sedit || $priv_shours || $priv_sstat)
{
	print "<form name=\"siteprops\" method=\"post\"  autocomplete=\"off\" action=\"".$formfile.($siteid === false ? "" : "?siteid=".urlencode($siteid)."&avc=".urlencode($avc))."\">\n";
}
if (($priv_screate || $priv_sedit)  && ($_axsitesync_enable === false))
{
	// can edit general detail
?>

<div style="width:440px;display:flex;justify-content:flex-end;margin:0 0 6px;">
  <input type="button" name="close" class="inputbtn darkblue" value="Close" onclick="window.close()" tabindex="21">
</div>

<table border="1" cellspacing="0" cellpadding="0" style="border-collapse:collapse;table-layout:fixed;width:440px;">
	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Name *</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<input type="text" size="36" maxlength="60" tabindex="10" name="sitename"value="<?php print $sitename ?>"style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Address</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<input type="text" size="36" maxlength="120" tabindex="20" name="siteaddress" value="<?php print $siteaddress ?>"style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site City *</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<input type="text" size="36" maxlength="120" tabindex="20" name="siteaddrcity" value="<?php print $siteaddrcity ?>"style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">State/Province *</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<select name="siteaddrstate" tabindex="30"
					style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
			<?php
				$liststates = $myappt->sortlistarray($liststates, 1, SORT_ASC, SORT_REGULAR);
				$rc = count($liststates);
				for ($i = 0; $i < $rc; $i++)
				{
					if (strcasecmp($liststates[$i][0], $siteaddrstate) == 0)
						print "<option selected value=\"".$liststates[$i][0]."\">".$liststates[$i][1]."</option>\n";
					else
						print "<option value=\"".$liststates[$i][0]."\">".$liststates[$i][1]."</option>\n";
				}
			?>
			</select>
		</div>
		</td>
	</tr>

	<tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Country</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <select name="siteaddrcountry" tabindex="30"
                style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
		<?php
			$listcountries = $myappt->sortlistarray($listcountries, 1, SORT_ASC, SORT_REGULAR);
			$rc = count($listcountries);
			for ($i = 0; $i < $rc; $i++)
			{
				// Default to US if adding a new site
				if ($siteaddrcountry == "")
				{
					if (strcasecmp($listcountries[$i][0], "US") == 0)
						print "<option selected value=\"".$listcountries[$i][0]."\">".$listcountries[$i][1]."</option>\n";
				}
				elseif (strcasecmp($listcountries[$i][0], $siteaddrcountry) == 0)
					print "<option selected value=\"".$listcountries[$i][0]."\">".$listcountries[$i][1]."</option>\n";
				else
					print "<option value=\"".$listcountries[$i][0]."\">".$listcountries[$i][1]."</option>\n";
			}
				
		?>
        </select>
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Zip</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <input type="text" size="36" maxlength="120" tabindex="20" name="siteaddrzip" value="<?php print $siteaddrzip ?>"style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
      </div>
    </td>
  </tr>

   <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Region</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <select name="siteregion" tabindex="30"
                style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
		<?php
			$listregions = $myappt->sortlistarray($listregions, 1, SORT_ASC, SORT_REGULAR);
			$rc = count($listregions);
			for ($i = 0; $i < $rc; $i++)
			{
				if (strcasecmp($listregions[$i][0], $siteregion) == 0)
					print "<option selected value=\"".$listregions[$i][0]."\">".$listregions[$i][1]."</option>\n";
				else
					print "<option value=\"".$listregions[$i][0]."\">".$listregions[$i][1]."</option>\n";
			}
		?>
        </select>
      </div>
    </td>
  </tr>

   <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Component</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <select name="component" tabindex="30"
                style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
          <?php
            $listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
            $rc = count($listcomponent);
            for ($i=0; $i<$rc; $i++) {
              if (strcasecmp($component, $listcomponent[$i][0]) == 0)
                print "<option selected value=\"{$listcomponent[$i][0]}\">{$listcomponent[$i][1]}</option>\n";
              else
                print "<option value=\"{$listcomponent[$i][0]}\">{$listcomponent[$i][1]}</option>\n";
            }
          ?>
        </select>
      </div>
    </td>
  </tr>

   <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Type *</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <select name="sitetype" tabindex="30"
                style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
		<?php
			$listsitetype = $myappt->sortlistarray($listsitetype, 1, SORT_ASC, SORT_REGULAR);
			$rc = count($listsitetype);
			for ($i = 0; $i < $rc; $i++)
			{
				if (strcasecmp($listsitetype[$i][0], $sitetype) == 0)
					print "<option selected value=\"".$listsitetype[$i][0]."\">".$listsitetype[$i][1]."</option>\n";
				else
					print "<option value=\"".$listsitetype[$i][0]."\">".$listsitetype[$i][1]."</option>\n";
			}
		?>
        </select>
      </div>
    </td>
  </tr>

   <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Activity *</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <select name="siteactivity" tabindex="30"
                style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
		<?php
			$listsiteactivity = $myappt->sortlistarray($listsiteactivity, 1, SORT_ASC, SORT_REGULAR);
			$rc = count($listsiteactivity);
			for ($i = 0; $i < $rc; $i++)
			{
				if (strcasecmp($listsiteactivity[$i][0], $siteactivity) == 0)
					print "<option selected value=\"".$listsiteactivity[$i][0]."\">".$listsiteactivity[$i][1]."</option>\n";
				else
					print "<option value=\"".$listsiteactivity[$i][0]."\">".$listsiteactivity[$i][1]."</option>\n";
			}
		?>
        </select>
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Contact Name</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <input type="text" size="36" maxlength="120" tabindex="20" name="sitecontact" value="<?php print $sitecontact ?>"style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Contact Phone</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <input type="text" size="36" maxlength="120" tabindex="20" name="sitephone" value="<?php print $sitephone ?>"style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Timezone *</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <select name="sitetimezone" tabindex="30"
                style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
		<?php
			$rc = count($listtimezones);
			$selected = false;
			for ($i = 0; $i < $rc; $i++)
			{
				if (strcasecmp($listtimezones[$i][0], $sitetimezone) == 0)
				{
					print "<option selected value=\"".$listtimezones[$i][0]."\">".$listtimezones[$i][1]."</option>\n";
					$selected = true;
				}
				else
					print "<option value=\"".$listtimezones[$i][0]."\">".$listtimezones[$i][1]."</option>\n";
			}
			if ($selected === false)
				print "<option selected value=\"\"></option>\n";
		?>
        </select>
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Notify Email</span>
		<!-- <span class="smlgryitext">Space separated list of email addresses</span></td> -->
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <input type="text" size="36" maxlength="120" tabindex="20" name="sitenotifyemail" value="<?php print $sitenotifyemail ?>"style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
      </div>
    </td>
  </tr>
</table>

<?php
}
else 
{
	// can view general detail only
?>
<table border="1" cellspacing="0" cellpadding="0" style="border-collapse:collapse;table-layout:fixed;width:440px;">
	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Name *</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $sitename ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Address</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $siteaddress ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site City *</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $siteaddrcity ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">State/Province *</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $siteaddrstate ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Country</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $siteaddrcountry ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Zip</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $siteaddrzip ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Site Region</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $siteregion ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Component</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $sitecomponent ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Type *</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $sitetype_txt ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Activity *</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $siteactivity_txt ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Contact Name</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $sitecontact ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Contact Phone</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $sitephone ?>
			</span>
		</div>
		</td>
	</tr>

	<tr>
		<td style="padding:0;border:1px solid #000;">
		<div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
			<span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Timezone</span>
			<div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
			<span class="proptext"
				style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
			<?php print $sitetimezone ?>
			</span>
		</div>
		</td>
	</tr>
</table>
<?php
}
if ($priv_shours)
{
	// can edit site times
?>
<p/>
<table cellSpacing="0" cellPadding="5" width="440" border="1"  style="border-collapse:collapse;table-layout:fixed; padding:5%;">
<tr height="30">
<td colspan="3" style="text-align: center" valign="top"><span  class="lblblktext">Daily Operating Hours</span></td>
</tr>
<tr height="30">
<td width="120" style="text-align: center" valign="top"><span class="proplabel">Day</span></td>
<td width="160" style="text-align: center" valign="top"><span class="proplabel">Start Time (hh:mm)</span></td>
<td width="160"  style="text-align: center"valign="top"><span class="proplabel">End Time (hh:mm)</span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Sunday</span></td>
<td style="text-align: center" valign="top">
<select name="ssun_h" tabindex="200">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $ssun_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($ssun_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $ssun_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($ssun_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td style="text-align: center" valign="top">
<select name="esun_h" tabindex="220">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $esun_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($esun_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $esun_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($esun_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Monday</span></td>
<td style="text-align: center" valign="top">
<select name="smon_h" tabindex="240">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $smon_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($smon_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $smon_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($smon_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td style="text-align: center" valign="top">
<select name="emon_h" tabindex="260">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $emon_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($emon_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $emon_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($emon_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Tuesday</span></td>
<td style="text-align: center" valign="top">
<select name="stue_h" tabindex="280">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $stue_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($stue_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $stue_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($stue_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td style="text-align: center" valign="top">
<select name="etue_h" tabindex="300">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $etue_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($etue_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $etue_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($etue_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Wednesday</span></td>
<td style="text-align: center" valign="top">
<select name="swed_h" tabindex="320">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $swed_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($swed_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $swed_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($swed_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td style="text-align: center" valign="top">
<select name="ewed_h" tabindex="340">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $ewed_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($ewed_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $ewed_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($ewed_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Thursday</span></td>
<td style="text-align: center" valign="top">
<select name="sthu_h" tabindex="360">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $sthu_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($sthu_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $sthu_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($sthu_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td style="text-align: center" valign="top">
<select name="ethu_h" tabindex="380">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $ethu_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($ethu_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $ethu_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($ethu_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Friday</span></td>
<td style="text-align: center" valign="top">
<select name="sfri_h" tabindex="400">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $sfri_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($sfri_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $sfri_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($sfri_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td style="text-align: center" valign="top">
<select name="efri_h" tabindex="420">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $efri_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($efri_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $efri_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($efri_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Saturday</span></td>
<td style="text-align: center" valign="top">
<select name="ssat_h" tabindex="440">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $ssat_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($ssat_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $ssat_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($ssat_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td style="text-align: center" valign="top">
<select name="esat_h" tabindex="460">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $esat_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($esat_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $esat_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($esat_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Holidays</span></td>
<td style="text-align: center" valign="top">
<select name="shol_h" tabindex="480">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $shol_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($shol_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $shol_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($shol_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td><td style="text-align: center" valign="top">
<select name="ehol_h" tabindex="500">
<?php
for ($i = 0; $i < 24; $i++)
{
	$v = str_pad($i, 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $ehol_h) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($ehol_h == "")
	print "<option selected value=\"\">-</option>\n";
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
	if (strcmp($v, $ehol_m) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($ehol_m == "")
	print "<option selected value=\"\">-</option>\n";
else
	print "<option value=\"\">-</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Slot Time (min)*</span></td>
<td colspan="2" style="text-align: center" valign="top">
<select name="slottime" tabindex="520" style="width:18em;">
<?php
for ($i = 1; $i < 13; $i++)
{
	$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
	if (strcmp($v, $slottime) == 0)
		print "<option selected value=\"".$v."\">".$v."</option>\n";
	else
		print "<option value=\"".$v."\">".$v."</option>\n";
}
if ($slottime == "")
	print "<option selected value=\"\"></option>\n";
else
	print "<option value=\"\"></option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Blockout (hours)</span></td>
<td colspan="2" style="text-align: center" valign="top">
<select name="siteblockout" tabindex="530" style="width:18em;">
<?php
for ($i = 0; $i < (SITEBLOCKOUT_MAX +1); $i++)
{
	if ($i == $siteblockout)
		print "<option selected value=\"".$i."\">".$i."</option>\n";
	else
		print "<option value=\"".$i."\">".$i."</option>\n";
}
if ($siteblockout == NULL)
	print "<option selected value=\"0\">0</option>\n";
?>
</select>
</td></tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Holiday Map</span></td>
<td colspan="2" style="text-align: center" valign="top">
<select name="hmapid" tabindex="540" style="width:18em;">
<?php
if ($n_hol > 0)
{
	while ($r_hol = $s_hol->fetch_assoc())
	{
		$hmid = $r_hol["hmapid"];
		$hmname = $r_hol["mapname"];
		if ($hmid == $hmapid)
			print "<option selected value=\"".$hmid."\">".$hmname."</option>\n";
		else
			print "<option value=\"".$hmid."\">".$hmname."</option>\n";
	}
	$s_hol->free();
}
if ($hmapid == "")
	print "<option selected value=\"\"></option>\n";
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
<td colspan="3" style="text-align: center" valign="top"><span class="lblblktext">Daily Operating Hours</span></td>
</tr>
<tr height="30">
<td width="120" style="text-align: center" valign="top"><span class="proplabel">Day</span></td>
<td width="160" style="text-align: center" valign="top"><span class="proplabel">Start Time (hh:mm)</span></td>
<td width="160" style="text-align: center" valign="top"><span class="proplabel">End Time (hh:mm)</span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Sunday</span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($ssun_h, 2, "0", STR_PAD_LEFT).":".str_pad($ssun_m, 2, "0", STR_PAD_LEFT) ?></span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($esun_h, 2, "0", STR_PAD_LEFT).":".str_pad($esun_m, 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Monday</span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($smon_h, 2, "0", STR_PAD_LEFT).":".str_pad($smon_m, 2, "0", STR_PAD_LEFT) ?></span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($emon_h, 2, "0", STR_PAD_LEFT).":".str_pad($emon_m, 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Tuesday</span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($stue_h, 2, "0", STR_PAD_LEFT).":".str_pad($stue_m, 2, "0", STR_PAD_LEFT) ?></span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($etue_h, 2, "0", STR_PAD_LEFT).":".str_pad($etue_m, 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Wednesday</span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($swed_h, 2, "0", STR_PAD_LEFT).":".str_pad($swed_m, 2, "0", STR_PAD_LEFT) ?></span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($ewed_h, 2, "0", STR_PAD_LEFT).":".str_pad($ewed_m, 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Thursday</span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($sthu_h, 2, "0", STR_PAD_LEFT).":".str_pad($sthu_m, 2, "0", STR_PAD_LEFT) ?></span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($ethu_h, 2, "0", STR_PAD_LEFT).":".str_pad($ethu_m, 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Friday</span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($sfri_h, 2, "0", STR_PAD_LEFT).":".str_pad($sfri_m, 2, "0", STR_PAD_LEFT) ?></span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($efri_h, 2, "0", STR_PAD_LEFT).":".str_pad($efri_m, 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Saturday</span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($ssat_h, 2, "0", STR_PAD_LEFT).":".str_pad($ssat_m, 2, "0", STR_PAD_LEFT) ?></span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($esat_h, 2, "0", STR_PAD_LEFT).":".str_pad($esat_m, 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Holidays</span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($shol_h, 2, "0", STR_PAD_LEFT).":".str_pad($shol_m, 2, "0", STR_PAD_LEFT) ?></span></td>
<td style="text-align: center" valign="top"><span class="proptext"><?php print str_pad($ehol_h, 2, "0", STR_PAD_LEFT).":".str_pad($ehol_m, 2, "0", STR_PAD_LEFT) ?></span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Slot Time (min)*</span></td>
<td colspan="2" style="text-align: center" valign="top"><span class="proptext"><?php print $slottime ?></span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Blockout (hours)</span></td>
<td colspan="2" style="text-align: center" valign="top"><span class="proptext"><?php print $siteblockout ?></span></td>
</tr>
<tr height="30">
<td style="text-align: center" valign="top"><span class="proplabel">Holiday Map</span></td>
<td colspan="2" style="text-align: center" valign="top"><span class="proptext"><?php print $holmapname ?></span></td>
</tr>
</table>
<?php
}
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
	print "<input type=\"submit\" name=\"submit_site\" class=\"inputbtn darkblue\" value=\"Save\" tabindex=\"45\">\n";
	print "</form>\n";
	print "</td>\n";
	if ($priv_shours && ($siteid !== false))
	{
		$siteavc = $myappt->session_createmac($siteid);
		print "<td width=\"120\" valign=\"center\" align=\"left\">\n";
		print "<input type=\"button\" name=\"btn_aex\" class=\"inputbtn darkblue\" value=\"Exceptions\" onclick=\"javascript:popupOpener('pop-siteexception.html?sid=".urlencode($siteid)."&avc=".urlencode($siteavc)."', 'availexcept', 400, 400)\" title=\"Add exceptions to the site availability.\">\n";
		print "</td>\n";
	}
	else
		print "<td width=\"120\" valign=\"center\" align=\"left\">&nbsp;</td>\n";
	
	if ($priv_shours && ($siteid !== false))
	{
		$siteavc = $myappt->session_createmac($siteid);
		print "<td width=\"120\" valign=\"center\" align=\"left\">\n";
		print "<input type=\"button\" name=\"btn_slo\" class=\"inputbtn darkblue\" value=\"Open Dates\" onclick=\"javascript:popupOpener('pop-siteopenlimits.html?sid=".urlencode($siteid)."&avc=".urlencode($siteavc)."', 'siteopenlimits', 400, 400)\" title=\"Restrict site operating dates.\">\n";
		print "</td>\n";
	}
	else
		print "<td width=\"120\" valign=\"center\" align=\"left\">&nbsp;</td>\n";
	
	print "</tr>\n";
	print "</table>\n";
}

$dbh->close();
?>
</body></html>