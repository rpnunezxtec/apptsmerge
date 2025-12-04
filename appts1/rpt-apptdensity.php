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
include("vec-clappointments.php");
include('../appcore/vec-clforms.php');
$myform = new authentxforms();
$myappt = new authentxappointments();
date_default_timezone_set(DATE_TIMEZONE);

define("DENSITYSLOTS", 36);

$fullname = $_SESSION["authentxappts"]["user"]["uname"];
$namearray = explode(" ", $fullname);
$firstname = $namearray[0];

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

// now check for posted values from form
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
		$n_site = $s_site->num_rows;
		if ($n_site == 0)
		{
			$s_site->free();
			$dbh->close();
			print "<script type=\"text/javascript\">alert('Site not found.')</script>\n";
			$sitename = false;
			$slottime = false;
			$st_secs = 1;
		}
		else
		{
			$r_site = $s_site->fetch_assoc();
			$sitename = $r_site["sitename"];
			$slottime = $r_site["slottime"];
			$st_secs = $slottime * 60;
			$s_site->free();
		}
		
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
 				
 					$q_ad = "select count(*) "
 						. "\n from appointment "
 						. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
 						. "\n and starttime>='".$dt_int_s."' "
	 					. "\n and starttime<'".$dt_int_e."' "
	 					;
 					$s_ad = $dbh->query($q_ad);
					$r_ad = $s_ad->fetch_assoc();
					$n_ia = $r_ad["count(*)"];
					if ($n_ia > $apptdensity[$i]["count"])
						$apptdensity[$i]["count"] = $n_ia;
					$s_ad->free();
 				}
 			}
		}
		
		// Find out how many workstations are assigned to this site
		$q_ws = "select count(*) "
			. "\n from workstation "
			. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
			;
		$s_ws = $dbh->query($q_ws);
		if ($s_ws)
		{
			$r_ws = $s_ws->fetch_assoc();
			$n_ws = $r_ws["count(*)"];
			$s_ws->free();
		}
		else
			$n_ws = 0;
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

// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams['css'] = array_merge($cfg_stdcss, ['../appcore/css/authentx.css']);

$headparams["jscript_file"] = $cfg_stdjscript;
// $headparams["jscript_file"][] = "../appcore/scripts/js-tablesort.js";
// $headparams["jscript_file"][] = "../appcore/scripts/js-checkall.js";
// $headparams["jscript_file"][] = "../appcore/scripts/js-expandall.js";

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

$myform->frmrender_bodytag($bodyparams);
?>

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
<input type="submit" name="submit_param" class="inputbtn darkblue" value="Select">
</td>
<td colspan="9" valign="top"><span class="lblblktext">&nbsp;</span></td>
</tr>
</form>
<tr height="40"><td colspan="20" valign="top"><hr/></td></tr>
</table>
<?php
if (($siteid !== false) && ($sdt !== false) && ($edt !== false))
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