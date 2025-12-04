<?PHP

// $Id:$

// xsvc-dsched.xas
// AJAX posting from daily schedule popup to refresh the changed contents.
// GET: siteid, datestamp

// Returns simple HTML for the middle data table.

if (!isset($_SESSION["authentx"]))
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

$formfile = "pop-dailysched.php";

if (AJAX_DSCHED_ENABLE !== true)
	die();

// validate the session
$sr = $myappt->validatesession($_siteid);
if ($sr !== true)
{
	print "<script type=\"text/javascript\">alert('".$sr."')</script>\n";
	$myappt->vectormeto($page_denied);
}

// Get admin privileges: view daily schedule privileges.
if ($myappt->checkprivilege(PRIV_APPTSCHED) !== true)
	die();

// GET arguments: 
// center: ID of site, 
// datestamp: timestamp (s) for start of the day of interest, 
if (isset($_GET["center"]))
{
	$centeruuid = $_GET["center"];
	// check and sanitise it
	if (strlen($centeruuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid center ID.')</script>\n";
		die();
	}
}
else
{
	print "<script type=\"text/javascript\">alert('Site not specified.')</script>\n";
	die();
}

if (isset($_GET["datestamp"]))
{
	$datestamp = $_GET["datestamp"];
	// check and sanitise it
	if (!is_numeric($datestamp))
	{
		print "<script type=\"text/javascript\">alert('Invalid datestamp.')</script>\n";
		die();
	}
}
else
{
	print "<script type=\"text/javascript\">alert('Datestamp not specified.')</script>\n";
	die();
}

if (isset($_GET["avc"]))
	$avc = $_GET["avc"];
else 
	$avc = $myappt->session_createmac($datestamp.$centeruuid);

$urlargs = "?center=".urlencode($centeruuid)."&datestamp=".urlencode($datestamp)."&avc=".urlencode($avc);

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Calculate the start and end datetimes for the search
	$startdt = date("Y-m-d H:i:s", $datestamp);
	$enddt = date("Y-m-d H:i:s", ($datestamp+24*60*60));
	
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

$resultstring = "<span class=\"nameheading\">Appointment Schedule</span><p/>";
$resultstring .= "<span class=\"proplabel\">Site: </span>"
			. "<span class=\"proptext\">".htmlentities($sitedetail["sitename"])."</span><br/>"
			. "<span class=\"proplabel\">For: </span>"
			. "<span class=\"proptext\">".(date("D M jS", $datestamp))."</span><br/>"
			. "<span class=\"proplabel\">Run Time: </span>"
			. "<span class=\"proptext\">".(date("D M jS H:i:s", $sitedetail["currenttimestamp"]))."</span><br/>"
			. "<span class=\"proplabel\">Run By: </span>"
			. "<span class=\"proptext\">".htmlentities($myappt->session_getuuname())."</span><br/>";
$resultstring .= "<p/>";

$resultstring .= "<table width=\"100%\" border=\"1\" cellpadding=\"1\" cellspacing=\"0\">";

$resultstring .= "<tr height=\"20\">"
			. "<td width=\"8%\" class=\"matrixheading\"><span class=\"tableheading\">Time</span></td>"
			. "<td width=\"18%\" class=\"matrixheading\"><span class=\"tableheading\">Name</span></td>"
			. "<td width=\"14%\" class=\"matrixheading\"><span class=\"tableheading\">Component</span></td>"
			. "<td width=\"10%\" class=\"matrixheading\"><span class=\"tableheading\">Appt Ref</span></td>"
			. "<td width=\"15%\" class=\"matrixheading\"><span class=\"tableheading\">Reason</span></td>"
			. "<td width=\"20%\" class=\"matrixheading\"><span class=\"tableheading\">Email</span></td>"
			. "<td width=\"15%\" class=\"matrixheading\"><span class=\"tableheading\">Phone</span></td>"
			. "</tr>";

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

	$resultstring .= "<tr height=\"20\">"
				. "<td class=\"".$lc."\">";

	if ($attendance === false)
	{
		// Marked as noshow (default) - add the link to allow this to be reversed
		$resultstring .= "<a href=\"".$formfile.$urlargs."&apptuuid=".urlencode($apptuuid)."&atten=y"."\" title=\"Mark this appointment as attended\">";
	}
	else 
	{
		// Marked as attended - add the link to allow this to be marked as a noshow
		$resultstring .= "<a href=\"".$formfile.$urlargs."&apptuuid=".urlencode($apptuuid)."&atten=n"."\" title=\"Mark this appointment as not attended\">";
	}

	$resultstring .= $atime."</a></td>"
				. "<td class=\"".$lc."\">".htmlentities($uname)."</td>"
				. "<td class=\"".$lc."\">".($component == "" ? "&nbsp;" : htmlentities($component))."</td>"
				. "<td class=\"".$lc."\">".$aref."</td>"
				. "<td class=\"".$lc."\">".$arsn."</td>"
				. "<td class=\"".$lc."\">".($uemail == "" ? "&nbsp;" : htmlentities($uemail))."</td>"
				. "<td class=\"".$lc."\">".($uphone == "" ? "&nbsp;" : htmlentities($uphone))."</td>"
				. "</tr>";
}
$resultstring .= "</table>";

//header('Content-type: text/html; charset=utf-8');
print $resultstring;

?>