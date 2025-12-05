<?php
// $Id:$

// popup to show daily appointment schedule for a site
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-dailysched.html";
// the geometry required for this popup
$windowx = 950;
$windowy = 800;

include("config.php");
include("vec-clappointments.php");
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

// Get admin privileges: view daily schedule privileges.
if ($myappt->checkprivilege(PRIV_APPTSCHED) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

if (AJAX_DSCHED_ENABLE !== true)
{
	header("Cache-control: must-revalidate");
	header('Expires: ' . gmdate('D, d M Y H:i:s', time()+24*60*60) . ' GMT');
	if ($refresh_dsched > 0)
		header('Refresh: '.$refresh_dsched);
}

// GET arguments: 
// siteid: ID of site, 
// datestamp: timestamp (s) for start of the day of interest, 
// avc: mac using siteid.datestamp as the base.

if (isset($_GET["siteid"]))
{
	$siteid = $_GET["siteid"];
	// check and sanitise it
	if (!is_numeric($siteid))
	{
		print "<script type=\"text/javascript\">alert('Invalid site ID.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
{
	print "<script type=\"text/javascript\">alert('Site not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

if (isset($_GET["datestamp"]))
{
	$datestamp = $_GET["datestamp"];
	// check and sanitise it
	if (!is_numeric($datestamp))
	{
		print "<script type=\"text/javascript\">alert('Invalid datestamp.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
{
	print "<script type=\"text/javascript\">alert('Datestamp not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

if (isset($_GET["avc"]))
	$avc = $_GET["avc"];
else
{
	print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Check the AVC mac for validity
$testavc = $myappt->session_createmac($datestamp.$siteid);
	
if (strcasecmp($avc, $testavc) != 0)
{
	print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

$urlargs = "?siteid=".urlencode($siteid)."&datestamp=".urlencode($datestamp)."&avc=".urlencode($avc);

// Read database info for form
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	// If the attendance link is clicked we process this here
	if (isset($_GET["atten"]))
	{
		$attenval = trim($_GET["atten"]);
		$apid = trim($_GET["apptid"]);
		if ($attenval == "n")
			$av = 1;
		else 
			$av = 0;
		
		if (is_numeric($apid))
		{
			$q_appt = "update appointment "
				. "\n set attendance='".$av."' "
				. "\n where apptid='".$dbh->real_escape_string($apid)."' "
				;
			$s_appt = $dbh->query($q_appt);
		}
	}
	
	// Calculate the start and end datetimes for the search
	$startdt = date("Y-m-d H:i:s", $datestamp);
	$enddt = date("Y-m-d H:i:s", ($datestamp+24*60*60));
	
	// get the appointments for the site/date ordered by time
	$q_appt = "select * from appointment "
		. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
		. "\n and starttime>='".$startdt."' "
		. "\n and starttime<'".$enddt."' "
		. "\n order by starttime "
		;
	$s_appt = $dbh->query($q_appt);
	
	// Get the site detail
	$q_site = "select * from site "
		. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
		;
	$s_site = $dbh->query($q_site);
	$n_site = $s_site->num_rows;
	if ($n_site == 0)
	{
		$s_site->free();
		$dbh->close();
		print "<script type=\"text/javascript\">alert('Site not found.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	$r_site = $s_site->fetch_assoc();
	$s_site->free();
	
	// Site current date/time
	$sitetimezone = $r_site["timezone"];
	if (($sitetimezone == "") || ($sitetimezone == NULL))
		$sitezoneoffset = 0;
	else
	{
		$mytzone = new DateTimeZone($sitetimezone);
		$mydatetime = new DateTime("now", $mytzone);
		$sitezoneoffset = $mytzone->getOffset($mydatetime);
	}
	$currenttimestamp = $myappt->gmtime() + $sitezoneoffset;
}
else 
{
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<META http-equiv="Default-Style" content="text/html;charset=UTF-8">
<META http-equiv="Pragma" content="no-cache">
<META http-equiv="Cache-Control" content="no-store,no-Cache">
<title>Appointment Schedule</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico">
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css">
<link rel=stylesheet type="text/css" href="../appcore/css/authentxappointment.css">
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php
if (AJAX_DSCHED_ENABLE === true)
{
	print "<script language=\"javascript\" src=\"../appcore/scripts/js-ajax_dsched.js\"></script>\n";

	print "<script language=\"javascript\">";
	
	// Set up the javascript initial values 

	// 1. The service to call on each event
	print "xhrservice = '".AJAX_DSCHEDSERVICE."'\n";

	// 2. Setup the refresh interval timer
	if ($refresh_dsched > 0)
		print "refreshinterval='".$refresh_dsched."'\n";
	else 
		$refresh_dsched = false;
		
	// 3. Setup the GET parameters
	// The selected site
	print "site='".urlencode($siteid)."'\n";
	// The selected date stamp
	print "datestamp='".urlencode($datestamp)."'\n";

	print "</script>\n";
}
?>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<?php
if (AJAX_DSCHED_ENABLE === true)
{
	if ($refresh_dsched !== false)
		print "<body onload='startRefresh()'>\n";
	else 
		print "<body>\n";
}
else 
	print "<body>\n";
?>
<div id="pagedata">
<span class="nameheading">Appointment Schedule</span><p/>
<span class="proplabel">Site: </span><span class="proptext"><?php print htmlentities($r_site["sitename"]) ?></span><br/>
<span class="proplabel">For: </span><span class="proptext"><?php print date("D M jS", $datestamp) ?></span><br/>
<span class="proplabel">Run Time: </span><span class="proptext"><?php print date("D M jS H:i:s", $currenttimestamp) ?></span><br/>
<span class="proplabel">Run By: </span><span class="proptext"><?php print htmlentities($myappt->session_getuuname()) ?></span><br/>
<p/>
<table width="100%" border="1" cellpadding="1" cellspacing="0">
<tr height="20">
<td width="8%" class="matrixheading"><span class="tableheading">Time</span></td>
<td width="18%" class="matrixheading"><span class="tableheading">Name</span></td>
<td width="14%" class="matrixheading"><span class="tableheading">Component</span></td>
<td width="10%" class="matrixheading"><span class="tableheading">Appt Ref</span></td>
<td width="15%" class="matrixheading"><span class="tableheading">Reason</span></td>
<td width="20%" class="matrixheading"><span class="tableheading">Email</span></td>
<td width="15%" class="matrixheading"><span class="tableheading">Phone</span></td>
</tr>
<?php
while ($r_appt = $s_appt->fetch_assoc())
{
	$adt = $r_appt["starttime"];
	$ds_Y = substr($adt, 0, 4);
	$ds_M = substr($adt, 5, 2);
	$ds_D = substr($adt, 8, 2);
	$ds_h = substr($adt, 11, 2);
	$ds_m = substr($adt, 14, 2);
	$ds_s = substr($adt, 17, 2);
 	$ats = mktime($ds_h, $ds_m, $ds_s, $ds_M, $ds_D, $ds_Y);
	
 	$atime = date("H:i", $ats); 
	$auid = $r_appt["uid"];
	$aref = $r_appt["apptref"];
	$arsn = $r_appt["apptrsn"];
	$atten = $r_appt["attendance"];
	$apptid = $r_appt["apptid"];
	
	if (($atten == 0) || ($atten == "") || ($atten == NULL))
		$attendance = true;
	else 
		$attendance = false;
	
	$q_u = "select * from user "
		. "\n where uid='".$auid."' "
		;
	$s_u = $dbh->query($q_u);
	$r_u = $s_u->fetch_assoc();
	$s_u->free();
	
	$uname = $r_u["uname"];
	$component = $r_u["component"];
	$uemail = $r_u["email"];
	$uphone = $r_u["phone"];
	
	// mark the past appointments, current appointment and future appointments with separate styles.
	$nowtime = $currenttimestamp;
	$slottime = $r_site["slottime"];
	$stsecs = $slottime * 60;
	$ate = $ats + $stsecs;
	// current appointment slot
	if (($nowtime > $ats) && ($nowtime < $ate))
		$lc = "apptnow";
	elseif (($nowtime > $ats) && ($nowtime > $ate))
		$lc = "apptpast";
	else 
		$lc = "apptlater";
	if ($attendance === false)
		$lc = "apptnoshow";
	
?>
<tr height="20">
<td class="<?php print $lc ?>">
<?php
if ($attendance === false)
{
	// Marked as noshow - add the link to allow this to be reversed
	print "<a href=\"".$formfile.$urlargs."&apptid=".urlencode($apptid)."&atten=y"."\" title=\"Mark this appointment as attended\">";
}
else 
{
	// Marked as attended (default) - add the link to allow this to be marked as a noshow
	print "<a href=\"".$formfile.$urlargs."&apptid=".urlencode($apptid)."&atten=n"."\" title=\"Mark this appointment as not attended\">";
}
?>
<?php print $atime ?></a></td>
<td class="<?php print $lc ?>"><?php print htmlentities($uname) ?></td>
<td class="<?php print $lc ?>"><?php print ($component == "" ? "&nbsp;" : htmlentities($component)) ?></td>
<td class="<?php print $lc ?>"><?php print $aref ?></td>
<td class="<?php print $lc ?>"><?php print $arsn ?></td>
<td class="<?php print $lc ?>"><?php print ($uemail == "" ? "&nbsp;" : htmlentities($uemail)) ?></td>
<td class="<?php print $lc ?>"><?php print ($uphone == "" ? "&nbsp;" : htmlentities($uphone)) ?></td>
</tr>
<?php
}
$s_appt->free();
$dbh->close();
?>
</table>
</div>
</body></html>