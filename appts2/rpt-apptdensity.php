<?php
// $Id:$

// show resource usage/appointment density against time for selected site
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "rpt-apptdensity.php";

// the geometry required for this popup
$windowx = 850;
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

define("DENSITYSLOTS", 36);

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
	
if (isset($_GET["center"]))
	$centeruuid = $_GET["center"];
else
	$centeruuid = false;

// now check for posted values from form
if (isset($_POST["submit_param"]))
{
	if (isset($_POST["center"]))
		$centeruuid = $_POST["center"];
	else
		$centeruuid = false;
}

// Get the appointment entries for display
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	if ($centeruuid !== false)
	{
		// get the site info
		$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);

		if (count($sitedetails) == 0)
		{
			print "<script type=\"text/javascript\">alert('Site not found.')</script>\n";
			$sitename = false;
			$slottime = false;
			$st_secs = 1;
		}
		else
		{
			$sitename = $sitedetails["sitename"];
			$slottime = $sitedetails["slottime"];
			$st_secs = $slottime * 60;
		}
		
		// get the start and end timestamps
		if ($sd === false)
		{
			// get the first appointment in the database
			$q_a = "select min(starttime) as st from appointment "
				. "\n where centeruuid='".$sdbh->real_escape_string($centeruuid)."' "
				;
			$s_a = $sdbh->query($q_a);
			if ($s_a)
			{
				$r_a = $s_a->fetch_assoc();
				if ($r_a)
					$sdt = $r_a["st"];
				else
					$sdt = false;
				$s_a->free();
			}
			else
				$sdt = false;
		}
		else
			$sdt = $sd." 00:00:00";
			
		if ($ed === false)
		{
			// get the last appointment in the database
			$q_a = "select max(starttime) as st from appointment "
				. "\n where centeruuid='".$sdbh->real_escape_string($centeruuid)."' "
				;
			$s_a = $sdbh->query($q_a);
			if ($s_a)
			{
				$r_a = $s_a->fetch_assoc();
				if ($r_a)
					$edt = $r_a["st"];
				else
					$edt = false;
				$s_a->free();
			}
			else
				$edt = false;
		}
		else
			$edt = $ed." 23:59:59";

		if (($sdt !== false) && ($edt !== false))
		{
			$ds_Y = substr($sdt, 0, 4);
			$ds_M = substr($sdt, 5, 2);
			$ds_D = substr($sdt, 8, 2);
			$ds_h = substr($sdt, 11, 2);
			$ds_m = substr($sdt, 14, 2);
			$ds_s = substr($sdt, 17, 2);
 			$sts = mktime($ds_h, $ds_m, $ds_s, $ds_M, $ds_D, $ds_Y);
	
 			$ds_Y = substr($edt, 0, 4);
			$ds_M = substr($edt, 5, 2);
			$ds_D = substr($edt, 8, 2);
			$ds_h = substr($edt, 11, 2);
			$ds_m = substr($edt, 14, 2);
			$ds_s = substr($edt, 17, 2);
 			$ets = mktime($ds_h, $ds_m, $ds_s, $ds_M, $ds_D, $ds_Y);
	
 			// divide the time difference into DENSITYSLOTS time slots
 			$tdif = $ets - $sts;
 			$tslot = intval($tdif/DENSITYSLOTS) + 1;
 			// create an array with the start and end timestamps for each slot
 			// the counts will be evaluated with queries
 			$apptdensity = array();
 			 
 			for ($i = 0; $i < DENSITYSLOTS; $i++)
 			{
 				$apptdensity[$i]["start"] = $sts + $tslot*$i;
 				$apptdensity[$i]["end"] = $sts + $tslot*($i+1);
 				$apptdensity[$i]["count"] = 0;
 				// get the appointment count for this timeslot
 				$dt_start = date("Y-m-d H:i:s", $apptdensity[$i]["start"]);
 				$dt_end = date("Y-m-d H:i:s", $apptdensity[$i]["end"]);
 				
 				// Slots of slottime width within the tslot time width
 				// need to find the peak usage between the tslot times
 				$n_is = intval($tslot/$st_secs) + 1;
 				for ($j = 0; $j < $n_is; $j++)
 				{
 					$dts_int_s = $apptdensity[$i]["start"] + $st_secs*$j;
 					$dts_int_e = $apptdensity[$i]["start"] + $st_secs*($j+1);
 					$dt_int_s = date("Y-m-d H:i:s", $dts_int_s);
 					$dt_int_e = date("Y-m-d H:i:s", $dts_int_e);
 				
 					$q_ad = "select count(*) as n "
 						. "\n from appointment "
 						. "\n where centeruuid='".$sdbh->real_escape_string($centeruuid)."' "
 						. "\n and starttime>='".$dt_int_s."' "
	 					. "\n and starttime<'".$dt_int_e."' "
	 					;
 					$s_ad = $sdbh->query($q_ad);
					if ($s_ad)
					{
						$r_ad = $s_ad->fetch_assoc();
						if ($r_ad)
						{
							$n_ia = $r_ad["n"];
							if ($n_ia > $apptdensity[$i]["count"])
								$apptdensity[$i]["count"] = $n_ia;
						}
						$s_ad->free();
					}
 				}
 			}
		}
		
		// Find out how many workstations are assigned to this site
		$q_ws = "select count(*) as n "
			. "\n from workstation "
			. "\n where centeruuid='".$sdbh->real_escape_string($centeruuid)."' "
			;
		$s_ws = $sdbh->query($q_ws);
		if ($s_ws)
		{
			$r_ws = $s_ws->fetch_assoc();
			if ($r_ws)
				$n_ws = $r_ws["n"];
			else
				$n_ws = 0;
			$s_ws->free();
		}
		else
			$n_ws = 0;
	}
	// Get a list of sites for the dropdown
	$sitelist = $myappt->readsiteslist($sdbh);
	$n_site = count($sitelist);

	$sdbh->close();
}
else 
{
	$sdt = false;
	$edt = false;
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
}

// Create a URL from the parameters
$url = htmlentities($formfile)."?center=".($centeruuid === false ? "" : urlencode($centeruuid));
if ($sd !== false)
{
	$url .= "&sd=".urlencode($sd);
	if ($ed !== false)
		$url .= "&ed=".urlencode($ed);
}
elseif ($ed !== false)
	$url .= "&ed=".urlencode($ed);

?>
<!DOCTYPE html>
<html>
<head>
<META http-equiv="Default-Style" content="text/html;charset=UTF-8">
<title>Appointment Density</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico">
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css">
<link rel=stylesheet type="text/css" href="../appcore/css/authentxappointment.css">
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
<select name="center" style="width: 22em;">
<?php
for ($i = 0; $i < $n_site; $i++)
{
	// List array is [0]=centeruuid, ][1]=sitename
	$l_sitename = $sitelist[$i][1];
	$l_centeruuid = $sitelist[$i][0];
	if ($centeruuid !== false)
	{
		if ($l_centeruuid == $centeruuid)
			print "<option selected value=\"".$l_centeruuid."\">".$l_sitename."</option>\n";
		else
			print "<option value=\"".$l_centeruuid."\">".$l_sitename."</option>\n";
	}
	else
		print "<option value=\"".$l_centeruuid."\">".$l_sitename."</option>\n";
}
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
if (($centeruuid !== false) && ($sdt !== false) && ($edt !== false))
{
	// plot the results in the graphical table format
	// The resource ceiling for the site is given by $n_ws
	// Each timeslot in the array has a start and end timestamp and a count of
	// peak appointments for timeslots during that time
	$table_slotwidth = intval(720/DENSITYSLOTS);
	$table_yscalewidth = 800 - (DENSITYSLOTS*$table_slotwidth);
	
?>
<table width="800" cellspacing="0" cellpadding="3" border="0">
<tr>
<td width="500" valign="top"><span class="nameheading">Appointment Density: <?php print htmlentities($sitename) ?></span></td>
<td width="300" valign="top">
<span class="proplabel">Run Time: </span><span class="proptext"><?php print date("D M jS H:i:s") ?></span><br/>
<span class="proplabel">Run By: </span><span class="proptext"><?php print htmlentities($myappt->session_getuuname()) ?></span><br/>
<span class="proplabel">Start Date: </span><span class="proptext"><?php print ($sd === false ? "Any" : $sd) ?></span><br/>
<span class="proplabel">End Date: </span><span class="proptext"><?php print ($ed === false ? "Any" : $ed) ?></span>
</td></tr></table>
<p/>
<table width="800" cellspacing="1" cellpadding="0" border="1">
<tr height="30">
<td width="<?php print $table_yscalewidth ?>" class="rpt_yscale"><?php print SLOTDIVISIONS ?></td>
<?php
	// render the top row
	for ($i = 0; $i < DENSITYSLOTS; $i++)
	{
		if ($n_ws == SLOTDIVISIONS)
			print "<td width=\"".$table_slotwidth."\" class=\"rpt_unshadedcell_line\">&nbsp;</td>\n";
		else
			print "<td width=\"".$table_slotwidth."\" class=\"rpt_unshadedcell\">&nbsp;</td>\n";
	}
	print "</tr>\n";
	// Render the next rows, with Y axis and shading
	for ($j = SLOTDIVISIONS; $j > 0; $j--)
	{
		print "<tr height=\"100\">\n";
		print "<td width=\"".$table_yscalewidth."\" class=\"rpt_yscale_border\">".($j-1)."</td>\n";
		for ($i = 0; $i < DENSITYSLOTS; $i++)
		{
			if ($n_ws == ($j-1))
			{
				if ($apptdensity[$i]["count"] >= $j)
					print "<td class=\"rpt_shadedcell_line\">&nbsp;</td>\n";
				else 
					print "<td class=\"rpt_unshadedcell_line\">&nbsp;</td>\n";
			}
			else 
			{
				if ($apptdensity[$i]["count"] >= $j)
					print "<td class=\"rpt_shadedcell\">&nbsp;</td>\n";
				else
					print "<td class=\"rpt_unshadedcell\">&nbsp;</td>\n";
			}
		}
		print "</tr>\n";
	}
	print "<tr height=\"30\">\n";
	$cspan_left = intval(DENSITYSLOTS/2);
	$cspan_right = DENSITYSLOTS - $cspan_left;
	print "<td class=\"rpt_yscale\"></td>\n";
	print "<td colspan=\"".$cspan_left."\" class=\"rpt_xscale_border\" align=\"left\" valign=\"center\"><span class=\"proptext\">".(date("Y-m-d", $sts))."</span></td>\n";
	print "<td colspan=\"".$cspan_right."\" class=\"rpt_xscale_border\" align=\"right\" valign=\"center\"><span class=\"proptext\">".(date("Y-m-d", $ets))."</span></td>\n";
	print "</tr>\n";
?>
</table>
<?php
}
?>
</body></html>