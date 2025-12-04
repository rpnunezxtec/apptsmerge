<?php
// $Id:$

// popup to show personal future appointment schedule
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-showmyappts.html";
// the geometry required for this popup
$windowx = 1200;
$windowy = 800;

include("config.php");
include("vec-clappointments.php");
include('../appcore/vec-clforms.php');

$myappt = new authentxappointments();
$myform = new authentxforms();
date_default_timezone_set(DATE_TIMEZONE);

// validate the session
$sr = $myappt->validatesession($_siteid);
if ($sr !== true)
{
	print "<script type=\"text/javascript\">alert('".$sr."')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Read database info for form
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	// Calculate the start and end datetimes for the search
	$uid = $myappt->session_getuuid();
	
	// Find each site that the user has appointments at.
	$q_ua = "select * "
		. "\n from appointment "
		. "\n left join site on site.siteid=appointment.siteid "
		. "\n where uid='".$dbh->real_escape_string($uid)."' "
		;
	$s_ua = $dbh->query($q_ua);
	$n_fa = 0;
	$appt_fa = array();
	while ($r_ua = $s_ua->fetch_assoc())
	{
		$sitetimezone = $r_ua["timezone"];
		if (($sitetimezone == "") || ($sitetimezone == NULL))
			$sitezoneoffset = 0;
		else
		{
			$mytzone = new DateTimeZone($sitetimezone);
			$mydatetime = new DateTime("now", $mytzone);
			$sitezoneoffset = $mytzone->getOffset($mydatetime);
		}
		$currenttimestamp = $myappt->gmtime() + $sitezoneoffset;
		
		$apptdatetime = $r_ua["starttime"];
		$apptdatetimestamp = strtotime($apptdatetime);
		if ($apptdatetimestamp > $currenttimestamp)
		{
			// Record column data:
			// Date, Time, Site, Ref, Reason, Contact Name, Contact Phone
			$adt = $r_ua["starttime"];
			$ds_Y = substr($adt, 0, 4);
			$ds_M = substr($adt, 5, 2);
			$ds_D = substr($adt, 8, 2);
			$ds_h = substr($adt, 11, 2);
			$ds_m = substr($adt, 14, 2);
			$ds_s = substr($adt, 17, 2);
		 	$ats = mktime($ds_h, $ds_m, $ds_s, $ds_M, $ds_D, $ds_Y);
	
		 	$appt_fa[$n_fa]["atime"] = date("H:i", $ats); 
		 	$appt_fa[$n_fa]["adate"] = date("D M jS", $ats);
			$appt_fa[$n_fa]["aref"] = $r_ua["apptref"];
			$appt_fa[$n_fa]["asite"] = $r_ua["siteid"];
			$appt_fa[$n_fa]["arsn"] = $r_ua["apptrsn"];
			$appt_fa[$n_fa]["sitename"] = $r_ua["sitename"];
			$appt_fa[$n_fa]["sitecontact"] = $r_ua["sitecontactname"];
			$appt_fa[$n_fa]["sitecontactphone"] = $r_ua["sitecontactphone"];
			
			$n_fa++;
		}
	}
	$s_ua->free();
	$dbh->close();
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
?>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
<span class="nameheading">Appointment Schedule for <?php print htmlentities($myappt->session_getuuname()) ?></span><p/>

<table class="table" cellspacing="0" cellpadding="0" >
	<span class="proplabel">Run Time: </span><span class="proptext"><?php print date("D M jS H:i:s") ?></span><br/>
	<p/>

	<tr class="tableheading">
		<th class="tablehead" style="width: 10%">Date</th>
		<th class="tablehead" style="width: 8%">Time</th>
		<th class="tablehead" style="width: 20%">Site</th>
		<th class="tablehead" style="width: 10%">Appt Ref</th>
		<th class="tablehead" style="width: 20%">Reason</th>
		<th class="tablehead" style="width: 17%">Site Contact</th>
		<th class="tablehead" style="width: 15%">Contact Phone</th>
	</tr>
<?php
if ($n_fa == 0)
{
?>
<tr height="20"><td colspan="7" class="matrixline">No Appointments</td></tr>
<?php
}
else 
{
	for ($i = 0; $i < $n_fa; $i++)
	{
?>
<tr height="20">
<td class="apptlater"><?php print $appt_fa[$i]["adate"] ?></td>
<td class="apptlater"><?php print $appt_fa[$i]["atime"] ?></td>
<td class="apptlater"><?php print htmlentities($appt_fa[$i]["sitename"]) ?></td>
<td class="apptlater"><?php print htmlentities($appt_fa[$i]["aref"]) ?></td>
<td class="apptlater"><?php print ($appt_fa[$i]["arsn"] == "" ? "&nbsp;" : htmlentities($appt_fa[$i]["arsn"])) ?></td>
<td class="apptlater"><?php print ($appt_fa[$i]["sitecontact"] == "" ? "&nbsp;" : htmlentities($appt_fa[$i]["sitecontact"])) ?></td>
<td class="apptlater"><?php print ($appt_fa[$i]["sitecontactphone"] == "" ? "&nbsp;" : htmlentities($appt_fa[$i]["sitecontactphone"])) ?></td>
</tr>
<?php
	}
}
?>
</table>
</body></html>