<?php
// $Id:$

// popup to show daily appointment schedule for a site
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-dailysched.php";
// the geometry required for this popup
$windowx = 950;
$windowy = 800;

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
}

// GET arguments: 
// center: ID of site, 
// datestamp: timestamp (s) for start of the day of interest, 
// avc: mac using siteid.datestamp as the base.

if (isset($_GET["center"]))
{
	$centeruuid = $_GET["center"];
	// check and sanitise it
	if (strlen($centeruuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid center ID.')</script>\n";
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
$testavc = $myappt->session_createmac($datestamp.$centeruuid);
	
if (strcasecmp($avc, $testavc) != 0)
{
	print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

$urlargs = "?center=".urlencode($centeruuid)."&datestamp=".urlencode($datestamp)."&avc=".urlencode($avc);

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// If the attendance link is clicked we process this here
	// DB value: 0 = not attended (default), 1 = attended
	if (isset($_GET["atten"]))
	{
		$attenval = trim($_GET["atten"]);
		$apptuuid = trim($_GET["apptuuid"]);
		if ($attenval == "n")
			$av = 0;
		else 
			$av = 1;
		
		if (strlen($apptuuid) == 36)
		{
			$q_appt = "update appointment "
				. "\n set attendance='".$av."', "
				. "\n xsyncmts='".time()."' "
				. "\n where apptuuid='".$sdbh->real_escape_string($apptuuid)."' "
				;
			$s_appt = $sdbh->query($q_appt);
		}
	}
	
	// Calculate the start and end datetimes for the search
	$startdt = date("Y-m-d H:i:s", $datestamp);
	$enddt = date("Y-m-d H:i:s", ($datestamp + 24 * 60 * 60));
	
	// get the appointments for the site/date ordered by time - include the user detail
	$dailyappointments = array();
	$nda = 0;
	$q_appt = "select * from appointment "
			. "\n left join user on user.uuid=appointment.uuid "
			. "\n where centeruuid='".$sdbh->real_escape_string($centeruuid)."' "
			. "\n and starttime>='".$startdt."' "
			. "\n and starttime<'".$enddt."' "
			. "\n order by starttime "
			;
	$s_appt = $sdbh->query($q_appt);
	if ($s_appt)
	{
		while ($r_appt = $s_appt->fetch_assoc())
			$dailyappointments[$nda++] = $r_appt;
	
		$s_appt->free();
	}
	
	// Get the site detail
	$sitedetail = $myappt->readsitedetail($sdbh, $centeruuid);
	if (count($sitedetail) == 0)
	{
		$sdbh->close();
		print "<script type=\"text/javascript\">alert('Site not found.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	$sitedetail["currenttimestamp"] = $sitedetail["tzoneoffset"] + time();

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
	print "site='".urlencode($centeruuid)."'\n";
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
<span class="proplabel">Site: </span><span class="proptext"><?php print htmlentities($sitedetail["sitename"]) ?></span><br/>
<span class="proplabel">For: </span><span class="proptext"><?php print date("D M jS", $datestamp) ?></span><br/>
<span class="proplabel">Run Time: </span><span class="proptext"><?php print date("D M jS H:i:s", $sitedetail["currenttimestamp"]) ?></span><br/>
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
for ($i = 0; $i < $nda; $i++)
{
	$adt = $dailyappointments[$i]["starttime"];
	$ds_Y = substr($adt, 0, 4);
	$ds_M = substr($adt, 5, 2);
	$ds_D = substr($adt, 8, 2);
	$ds_h = substr($adt, 11, 2);
	$ds_m = substr($adt, 14, 2);
	$ds_s = substr($adt, 17, 2);
 	$ats = mktime($ds_h, $ds_m, $ds_s, $ds_M, $ds_D, $ds_Y);
	
 	$atime = date("H:i", $ats); 
	$auuid = $dailyappointments[$i]["uuid"];
	$aref = $dailyappointments[$i]["apptref"];
	$arsn = $dailyappointments[$i]["apptrsn"];
	$atten = $dailyappointments[$i]["attendance"];
	$apptuuid = $dailyappointments[$i]["apptuuid"];
	
	if (($atten == 0) || ($atten == NULL))
		$attendance = false;
	else 
		$attendance = true;
	
	$uname = $dailyappointments[$i]["uname"];
	$component = $dailyappointments[$i]["component"];
	$uemail = $dailyappointments[$i]["email"];
	$uphone = $dailyappointments[$i]["phone"];
	
	// mark the past appointments, current appointment and future appointments with separate styles.
	$nowtime = $sitedetail["currenttimestamp"];
	$slottime = $sitedetail["slottime"];
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
		// Marked as noshow (default) - add the link to allow this to be reversed
		print "<a href=\"".$formfile.$urlargs."&apptuuid=".urlencode($apptuuid)."&atten=y"."\" title=\"Mark this appointment as attended\">";
	}
	else 
	{
		// Marked as attended - add the link to allow this to be marked as a noshow
		print "<a href=\"".$formfile.$urlargs."&apptuuid=".urlencode($apptuuid)."&atten=n"."\" title=\"Mark this appointment as not attended\">";
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
?>
</table>
</div>
</body></html>