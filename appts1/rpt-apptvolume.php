<?php
// $Id:$
// Show weekly appointment volume for selected site

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "rpt-apptvolume.php";

// the geometry required for this popup
$windowx = 850;
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

// Validate access to this form - requires Reports tab permissions
if ($myappt->checktabmask(TAB_RPT) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Get admin privileges: report view privileges.
if ($myappt->checkprivilege(PRIV_RPT) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// GET arguments: 
// sd: startdate (YYYY-MM-DD). If not supplied then all appointments prior to ed will be shown.
// ed: enddate (YYYY-MM-DD). If not supplied then all appointments after sd are shown.
if (isset($_GET["sd"]))
	$sd = $_GET["sd"];
else
	$sd = false;
	
if (isset($_GET["ed"]))
	$ed = $_GET["ed"];
else
	$ed = false;
	
if (isset($_GET["site"]))
	$siteid = $_GET["site"];
else
	$siteid = false;

// now check for posted values from this form
if (isset($_POST["submit_param"]))
{
	if (isset($_POST["site"]))
		$siteid = $_POST["site"];
	else
		$siteid = false;
}

// Get the appointment entries for display
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	if ($siteid !== false)
	{
		// get the site info
		$q_site = "select * from site "
			. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
			;
		$s_site = $dbh->query($q_site);
		$r_site = $s_site->fetch_assoc();
		$sitename = $r_site["sitename"];
		
		$sitetimezone = $r_site["timezone"];
		if (($sitetimezone == "") || ($sitetimezone == NULL))
			$sitezoneoffset = 0;
		else
		{
			$mytzone = new DateTimeZone($sitetimezone);
			$mydatetime = new DateTime("now", $mytzone);
			$sitezoneoffset = $mytzone->getOffset($mydatetime);
		}
		$s_site->free();
		
		// get the start and end timestamps
		if ($sd === false)
		{
			// get the first appointment in the database
			$q_a = "select * from appointment "
				. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
				. "\n order by starttime asc "
				. "\n limit 1 "
				;
			$s_a = $dbh->query($q_a);
			$n_a = $s_a->num_rows;
			if ($n_a > 0)
			{
				$r_a = $s_a->fetch_assoc();
				$sdt = $r_a["starttime"];
			}
			else
				$sdt = false;
			$s_a->free();
		}
		else
			$sdt = $sd." 00:00:00";
			
		if ($ed === false)
		{
			// get the last appointment in the database
			$q_a = "select * from appointment "
				. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
				. "\n order by starttime desc "
				. "\n limit 1 "
				;
			$s_a = $dbh->query($q_a);
			$n_a = $s_a->num_rows;
			if ($n_a > 0)
			{
				$r_a = $s_a->fetch_assoc();
				$edt = $r_a["starttime"];
			}
			else
				$edt = false;
			$s_a->free();
		}
		else
			$edt = $ed." 23:59:59";

		if (($sdt !== false) && ($edt !== false))
		{
			// Get the start and end dates for each week for the report range
 			// Start on Sunday, end on Saturday.
 			
 			// First week, align to a Sunday
 			$ds_Y = substr($sdt, 0, 4);
			$ds_M = substr($sdt, 5, 2);
			$ds_D = substr($sdt, 8, 2);
			$ds_h = substr($sdt, 11, 2);
			$ds_m = substr($sdt, 14, 2);
			$ds_s = substr($sdt, 17, 2);
 			$sts = mktime($ds_h, $ds_m, $ds_s, $ds_M, $ds_D, $ds_Y);

			// check and sanitise it
			if ($sts <= 0)
				$sts = $myappt->gmtime() + $sitezoneoffset;
			if (is_nan($sts))
				$sts = $myappt->gmtime() + $sitezoneoffset;
						
			$wdate_start = getdate($sts);

			// Align to the previous Sunday
			$wdtstamp_start = $wdate_start[0];
			// 0=sun ... 6=sat
			$wday_start = $wdate_start["wday"];
			// calculate an offset to get back to Sunday
			$offset = $wday_start * 24 * 60 * 60;
			$suntstamp = $wdtstamp_start - $offset;
			$sundate = getdate($suntstamp);
	
			// calculate the week starting timestamp on Sunday at 00:00:00
			$wkstamp_start = mktime(0, 0, 0, $sundate["mon"], $sundate["mday"], $sundate["year"]);
 			
			// Calculate the end of the last selected week as a Saturday 23:59:59
			$ds_Y = substr($edt, 0, 4);
			$ds_M = substr($edt, 5, 2);
			$ds_D = substr($edt, 8, 2);
			$ds_h = substr($edt, 11, 2);
			$ds_m = substr($edt, 14, 2);
			$ds_s = substr($edt, 17, 2);
 			$ets = mktime($ds_h, $ds_m, $ds_s, $ds_M, $ds_D, $ds_Y);
 			
 			// check and sanitise it
			if ($ets <= 0)
				$ets = $myappt->gmtime() + $sitezoneoffset;
			if (is_nan($ets))
				$ets = $myappt->gmtime() + $sitezoneoffset;
						
			$wdate_end = getdate($ets);
 			
			// Align to the next Saturday
			$wdtstamp_end = $wdate_end[0];
			// 0=sun ... 6=sat
			$wday_end = $wdate_end["wday"];
			// calculate an offset to go forward to Saturday
			$offset = (6 - $wday_end) * 24 * 60 * 60;
			$sattstamp = $wdtstamp_end + $offset;
			$satdate = getdate($sattstamp);
	
			// Calculate the week ending timestamp on Saturday at 23:59:59
			$wkstamp_end = mktime(23, 59, 59, $satdate["mon"], $satdate["mday"], $satdate["year"]);
			
			// Create a data set for each week comprising of
			// startstamp, endstamp, appt count
			$report_set = array();
			$nw = 0;
			
			$cur_wkstamp_start = $wkstamp_start;
			$cur_wkstamp_end = $cur_wkstamp_start + 7 * 24 * 60 * 60;
			do
			{
				$dt_s = date("Y-m-d H:i:s", $cur_wkstamp_start);
 				$dt_e = date("Y-m-d H:i:s", $cur_wkstamp_end);
				$q_na = "select count(*) as apptcount "
					. "\n from appointment "
					. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
					. "\n and starttime>='".$dt_s."' "
					. "\n and starttime<'".$dt_e."' "
					;
				$s_na = $dbh->query($q_na);
				$r_na = $s_na->fetch_assoc();
				
				$report_set[$nw]["ss"] = $cur_wkstamp_start;
				$report_set[$nw]["es"] = $cur_wkstamp_end;
				$report_set[$nw]["na"] = $r_na["apptcount"];
				
				$nw++;
				$cur_wkstamp_start = $cur_wkstamp_end;
				$cur_wkstamp_end = $cur_wkstamp_start + 7 * 24 * 60 * 60;
				$s_na->free();
			}
			while ($cur_wkstamp_end < $wkstamp_end);
		}
	}
	// Get a list of sites for the dropdown
	$q_site = "select siteid, sitename from site "
		. "\n order by sitename "
		;
	$s_site = $dbh->query($q_site);
	$n_site = $s_site->num_rows;
}
else 
{
	$sdt = false;
	$edt = false;
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
}

// Create a URL from the parameters
$url = htmlentities($formfile)."?site=".($siteid === false ? "" : urlencode($siteid));
if ($sd !== false)
{
	$url .= "&sd=".urlencode($sd);
	if ($ed !== false)
		$url .= "&ed=".urlencode($ed);
}
elseif ($ed !== false)
	$url .= "&ed=".urlencode($ed);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<META http-equiv="Default-Style" content="text/html;charset=UTF-8">
<title>Appointment Density</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico">
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css">
<link rel=stylesheet type="text/css" href="../appcore/css/authentxappointment.css">
<link rel=stylesheet type="text/css" href="../appcore/css/authentxreports.css">
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<TABLE border="0" cellspacing="0" cellpadding="0" STYLE='table-layout:fixed' width="800">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">
<tr height="40"><td colspan="20" valign="top"><span class="lblblktext">Report Parameters</span></td></tr>
<form name="adparams" method="post" action="<?php print $url ?>"  autocomplete="off" >
<tr height="40">
<td colspan="7" valign="top"><span class="lblblktext">Site</span><br>
<select name="site" style="width: 22em;">
<?php
if ($n_site > 0)
{
	while ($r_site = $s_site->fetch_assoc())
	{
		$l_sitename = $r_site["sitename"];
		$l_siteid = $r_site["siteid"];
		if ($siteid !== false)
		{
			if ($l_siteid == $siteid)
				print "<option selected value=\"".$l_siteid."\">".$l_sitename."</option>\n";
			else
				print "<option value=\"".$l_siteid."\">".$l_sitename."</option>\n";
		}
		else
			print "<option value=\"".$l_siteid."\">".$l_sitename."</option>\n";
	}
}
$s_site->free();
$dbh->close();
?>
</select>
</td>
<td colspan="4" valign="top" align="right"><span class="lblblktext">&nbsp;</span><br>
<input type="submit" name="submit_param" class="btntext" value="Select">
</td>
<td colspan="9" valign="top"><span class="lblblktext">&nbsp;</span></td>
</tr>
</form>
<tr height="40"><td colspan="20" valign="top"><hr/></td></tr>
</table>
<?php
if ($siteid !== false)
{
?>
<table width="800" cellspacing="0" cellpadding="3" border="0">
<tr>
<td width="500" valign="top"><span class="nameheading">Weekly Appointment Volume: <?php print htmlentities($sitename) ?></span></td>
<td width="300" valign="top">
<span class="proplabel">Run Time: </span><span class="proptext"><?php print date("D M jS H:i:s") ?></span><br/>
<span class="proplabel">Run By: </span><span class="proptext"><?php print htmlentities($myappt->session_getuuname()) ?></span><br/>
<span class="proplabel">Start Date: </span><span class="proptext"><?php print ($sd === false ? "Any" : $sd) ?></span><br/>
<span class="proplabel">End Date: </span><span class="proptext"><?php print ($ed === false ? "Any" : $ed) ?></span>
</td></tr></table>
<p/>

<table width="90%" cellspacing="0" cellpadding="2" border="1" align="center">
<tr height="20">
<td width="30%" class="sysrpttablehead">Week Start Date</td>
<td width="30%" class="sysrpttablehead">Week End Date</td>
<td width="20%" class="sysrpttablehead">Appointments</td>
<td width="20%" class="sysrpttablehead">Cumulative</td>
</tr>
<?php
	if ($nw > 0)
	{
		$line = 0;
		$accna = 0;
		
		for ($i = 0; $i < $nw; $i++)
		{
			$lineclass = $line % 2;
			$ss = $report_set[$i]["ss"];
			$wsd = date("m/d/Y", $ss);
			$es = $report_set[$i]["es"] - 3600;
			$wed = date("m/d/Y", $es);
			$wna = $report_set[$i]["na"];
			$accna += $wna;
			
			print "<tr>\n";
			print "<td class=\"sysrpttableline".$lineclass."\">"
			. htmlentities($wsd)
			. "</td>\n";
			print "<td class=\"sysrpttableline".$lineclass."\">"
			. htmlentities($wed)
			. "</td>\n";
			print "<td class=\"sysrpttableline".$lineclass."\">"
			. htmlentities($wna)
			. "</td>\n";
			print "<td class=\"sysrpttableline".$lineclass."\">"
			. htmlentities($accna)
			. "</td>\n";
			print "</tr>\n";
			
			$line++;
		}
	}
	?>
</table>
<?php
}
?>
</body></html>