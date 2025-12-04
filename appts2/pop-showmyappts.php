<?php
// $Id:$

// popup to show personal future appointment schedule
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-showmyappts.php";
// the geometry required for this popup
$windowx = 900;
$windowy = 400;

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

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Calculate the start and end datetimes for the search
	$uuid = $myappt->session_getuuid();
	
	// Find each site that the user has appointments at.
	$appointmentdetails = $myappt->readfutureappointmentsforuser($sdbh, $uuid);

	$na = count($appointmentdetails);
	for ($i = 0; $i < $na; $i++)
	{
		// Separate date and time for the appointment start ("Y-m-d H:i:s" => "H:i" and "D M jS")
		$adt = $appointmentdetails[$i]["starttime"];
		$ds_Y = substr($adt, 0, 4);
		$ds_M = substr($adt, 5, 2);
		$ds_D = substr($adt, 8, 2);
		$ds_h = substr($adt, 11, 2);
		$ds_m = substr($adt, 14, 2);
		$ds_s = substr($adt, 17, 2);
	 	$ats = mktime($ds_h, $ds_m, $ds_s, $ds_M, $ds_D, $ds_Y);
	
	 	$appointmentdetails[$i]["atime"] = date("H:i", $ats); 
	 	$appointmentdetails[$i]["adate"] = date("D M jS", $ats);
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
<META http-equiv="Default-Style" content="text/html;charset=UTF-8">
<title>Personal Appointment Schedule</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico">
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css">
<link rel=stylesheet type="text/css" href="../appcore/css/authentxappointment.css">
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<span class="nameheading">Appointment Schedule for <?php print htmlentities($myappt->session_getuuname()) ?></span><p/>
<span class="proplabel">Run Time: </span><span class="proptext"><?php print date("D M jS H:i:s") ?></span><br/>
<p/>
<table width="100%" border="1" cellpadding="1" cellspacing="0">
<tr height="20">
<td width="10%" class="matrixheading"><span class="tableheading">Date</span></td>
<td width="8%" class="matrixheading"><span class="tableheading">Time</span></td>
<td width="20%" class="matrixheading"><span class="tableheading">Site</span></td>
<td width="10%" class="matrixheading"><span class="tableheading">Appt Ref</span></td>
<td width="20%" class="matrixheading"><span class="tableheading">Reason</span></td>
<td width="17%" class="matrixheading"><span class="tableheading">Site Contact</span></td>
<td width="15%" class="matrixheading"><span class="tableheading">Contact Phone</span></td>
</tr>
<?php
if ($na == 0)
{
?>
<tr height="20"><td colspan="7" class="matrixline">No Appointments</td></tr>
<?php
}
else 
{
	for ($i = 0; $i < $na; $i++)
	{
?>
<tr height="20">
<td class="apptlater"><?php print $appointmentdetails[$i]["adate"] ?></td>
<td class="apptlater"><?php print $appointmentdetails[$i]["atime"] ?></td>
<td class="apptlater"><?php print htmlentities($appointmentdetails[$i]["sitename"]) ?></td>
<td class="apptlater"><?php print htmlentities($appointmentdetails[$i]["apptref"]) ?></td>
<td class="apptlater"><?php print ($appointmentdetails[$i]["apptrsn"] == "" ? "&nbsp;" : htmlentities($appointmentdetails[$i]["apptrsn"])) ?></td>
<td class="apptlater"><?php print ($appointmentdetails[$i]["sitecontactname"] == "" ? "&nbsp;" : htmlentities($appointmentdetails[$i]["sitecontactname"])) ?></td>
<td class="apptlater"><?php print ($appointmentdetails[$i]["sitecontactphone"] == "" ? "&nbsp;" : htmlentities($appointmentdetails[$i]["sitecontactphone"])) ?></td>
</tr>
<?php
	}
}
?>
</table>
</body></html>