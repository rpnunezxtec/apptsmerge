<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-holmap.php";

include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

$rpp= 25;

// validate the session
$sr = $myappt->validatesession($_siteid);
if ($sr !== true)
{
	print "<script type=\"text/javascript\">alert('".$sr."')</script>\n";
	$myappt->vectormeto($page_denied);
}

// Check user tab permissions
$tab_users = $myappt->checktabmask(TAB_U);
$tab_sites = $myappt->checktabmask(TAB_S);
$tab_ws = $myappt->checktabmask(TAB_WS);
$tab_holmaps = $myappt->checktabmask(TAB_HOL);
$tab_reports = $myappt->checktabmask(TAB_RPT);
$tab_invite = $myappt->checktabmask(TAB_INVITE);
$tab_mailtmpl = $myappt->checktabmask(TAB_MAILTMPL);
$tab_repldash = $myappt->checktabmask(TAB_REPLDASH);

if (!$tab_holmaps)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	$myappt->vectormeto($page_denied);
}

$ntabs = 1;
if ($tab_users)
	$ntabs++;
if ($tab_sites)
	$ntabs++;
if ($tab_ws)
	$ntabs++;
if ($tab_holmaps)
	$ntabs++;
if ($tab_reports)
	$ntabs++;
if ($tab_invite)
	$ntabs++;
if ($tab_mailtmpl)
	$ntabs++;
if ($tab_repldash)
	$ntabs++;
	
if (isset($_GET["calyear"]))
	$calyear = $_GET["calyear"];
else
	$calyear = date('Y');
if ((!is_numeric($calyear)) || ($calyear < 1970))
	$calyear = date('Y');

// Get the holmap data from the database
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// process the form submission
	if (isset($_POST["submit_holmap_save"]))
	{
		// can't save unless privileges allow
		if ($myappt->checkprivilege(PRIV_HOLMAP) !== true)
		{
			print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
		}
		else 
		{
			// saves the current set of holidays into the named map in the database
			$badpost = false;
			// get the submitted holiday map name and the value.
			if (isset($_POST["hmapname"]))
				$hmapname = trim($_POST["hmapname"]);
			else
				$badpost = true;
			if ($hmapname == '')
				$badpost = true;
				
			if (isset($_POST["holidaymap"]))
				$hmapvalue = $_POST["holidaymap"];
			else
				$badpost = true;
			if (strlen($hmapvalue) != 96)
				$badpost = true;
	
			if ($badpost)
				print "<script type=\"text/javascript\">alert('Bad map name or value.')</script>\n";
			else
			{
				// See if the mapname already exists (ie update or insert)
				$n_map = 0;
				$q_map = "select count(*) as c "
					. "\n from holidaymap "
					. "\n where mapname='".$sdbh->real_escape_string($hmapname)."' "
					;
				$s_map = $sdbh->query($q_map);
				if ($s_map)
				{
					$r_map = $s_map->fetch_assoc();
					$n_map = $r_map["c"];
					$s_map->free();
				}
			
				if ($n_map > 0)
					$hmapexists = true;
				else
					$hmapexists = false;

				if ($hmapexists)
				{
					$q_map = "select * "
						. "\n from holidaymap "
						. "\n where mapname='".$sdbh->real_escape_string($hmapname)."' "
						;

					$s_map = $sdbh->query($q_map);
					if ($s_map)
					{
						$r_map = $s_map->fetch_assoc();
						if ($r_map)
						{
							$hmapuuid = $r_map["hmapuuid"];
								
							// perform the update
							$q_umap = "update holidaymap set "
									. "\n holmap='".$sdbh->real_escape_string($hmapvalue)."', "
									. "\n xsyncmts='".time()."' "
									. "\n where hmapuuid='".$hmapuuid."' "
									. "\n limit 1 "
									;
							$s_umap = $sdbh->query($q_umap);
							
							// log success
							if ($s_umap)
							{
								$logstring = "Holiday map ".$hmapname." (".$hmapuuid.") updated";
								$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_HOLMAP);
							}
						}
						$s_map->free();
					}
				}
				else
				{
					// perform the insert
					$hmapuuid = $myappt->makeuniqueuuid($sdbh, "holidaymap", "hmapuuid");
					
					$q_umap = "insert into holidaymap set "
						. "\n mapname='".$sdbh->real_escape_string($hmapname)."', "
						. "\n holmap='".$sdbh->real_escape_string($hmapvalue)."', "
						. "\n hmapuuid='".$hmapuuid."', "
						. "\n xsyncmts='".time()."' "
						;
					$s_umap = $sdbh->query($q_umap);

					// log success
					if ($s_umap)
					{
						$logstring = "Holiday map ".$hmapname." (".$hmapuuid.") created";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_HOLMAP);
					}
				}
			}
		}
	}
	
	// read the maps for the selection dropdown
	$q_hmaps = "select * "
		. "\n from holidaymap "
		;
	$s_hmaps = $sdbh->query($q_hmaps);
	// create a list array with the map data and names
	$listholidaymap = array();
	$n_hol = 0;
	if ($s_hmaps)
	{
		while ($r_hmaps = $s_hmaps->fetch_assoc())
		{
			$listholidaymap[$n_hol][0] = $r_hmaps["holmap"];
			$listholidaymap[$n_hol][1] = $r_hmaps["mapname"];
			$n_hol++;
		}
		$s_hmaps->free();
	}
	
	$sdbh->close();
}
else 
{
	$listholidaymap = array();
	$n_hol = 0;
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<title>Authentx Appointments Management System</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentxappointment.css" />
<script language="javascript" src="../appcore/scripts/js-formapptholmap.js"></script>
</head>
<body onload="mapholdata()">
<table width="100%" cellspacing="0" cellpadding="0" border="0" nowrap="nowrap" scope="row" class="tabtable">
<tr height="33" valign="center" align="center">
<td>
<?php
// Determine user's tab display
print "<table width=\"".($ntabs*105)."\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" nowrap=\"nowrap\" scope=\"row\">\n";
print "<tr height=\"33\" valign=\"center\">\n";
print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-appt.php\"><span class=\"tabtext\">Appts</span></a></td>\n";
if ($tab_users)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-user.php\"><span class=\"tabtext\">Users</span></a></td>\n";
if ($tab_sites)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-sites.php\"><span class=\"tabtext\">Sites</span></a></td>\n";
if ($tab_ws)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-emws.php\"><span class=\"tabtext\">EMWS</span></a></td>\n";
if ($tab_holmaps)
	print "<td width=\"105\" class=\"tabcell_on\"><span class=\"tabtext\">Holidays</span></td>\n";
if ($tab_reports)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-reports.php\"><span class=\"tabtext\">Reports</span></a></td>\n";
if ($tab_invite)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-userinvite.php\"><span class=\"tabtext\">Invite</span></a></td>\n";
if ($tab_mailtmpl)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-mailtmpl.php\"><span class=\"tabtext\">Templates</span></a></td>\n";
if ($tab_repldash)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-repldash.php\"><span class=\"tabtext\">Replication</span></a></td>\n";
print "</tr>\n";
print "</table>\n";
?>
</td>
</tr>
<tr height="20">
<td valign="top" colspan="9">
<table width="100%" cellspacing="0" cellpadding="0" border="0" id="bartable">
<tr height="18" valign="center">
<td>&nbsp;</td>
</tr>
</table>
</td>
</tr>
</table>
<p>
<table cellspacing="0" cellpadding="0" align="center" border="0" width="858">
<tr><td><img height="12" src="../appcore/images/logoslice_1.gif" width="200" border="0" /></td>
</tr>
<tr><td valign="top" background="../appcore/images/box_mtl_ctr.gif">
<table cellspacing="0" cellpadding="0" border="0" width="858">
<tr height="12"><td width="12" height="12"><img height="12" src="../appcore/images/box_mtl_topl.gif" width="12" /></td>
<td background="../appcore/images/box_mtl_top.gif"><img height="12" src="../appcore/images/logoslice_2.gif" width="188" border="0" /></td>
<td width="12"><img height="12" src="../appcore/images/box_mtl_topr.gif" width="12" /></td>
</tr>
<tr valign="top">
<td width="12" background="../appcore/images/box_mtl_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td align="middle" background="../appcore/images/bg_spacer.gif">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr><td align="middle">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr height="0"><td align="left" width="220"><img height="56" src="../appcore/images/logoslice_3.gif" width="188" border="0" /></td>
<td align="right">
<table cellspacing="0" cellpadding="0" border="0" width="610">
<tr>
<td align="left" width="450">
<table  cellspacing="0" cellpadding="0" border="0" width="450">
<tr height="28"><td valign="top"><span class="siteheading"><?php print SITEHEADING ?></span></td>
</tr><tr height="28"><td valign="top"><span class="nameheading"></span></td></tr>
</table>
</td>
<td align="right" width="160">
<table cellspacing="0" cellpadding="0" border="0" width="160">
<tr height="28" valign="middle">
<td align="middle" width="80"></td>
<td align="middle" width="40"></td>
<td align="middle" width="40"></td>
</tr>
<tr height="28" valign="middle">
<td align="middle"></td>
<td align="middle" colspan="2"><a href="vec-logout.php" title="Log off the system"><img src="../appcore/images/icon-btnlogoff.gif" width="75" height="24" border="0" onclick='return frmCheckDirty()' /></a></td>
</tr></table>
</td>
</tr></table>
</td></tr>
<tr height="8" valign="top">
<td></td>
<td></td>
</tr></table>
</td></tr>
<tr><td align="center" valign="bottom">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr height="2"><td width="2" height="2"><img height="2" src="../appcore/images/bevel_topl.gif" width="2" /></td>
<td background="../appcore/images/bevel_top.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_topr.gif" width="2" /></td>
</tr>
<tr><td width="2" background="../appcore/images/bevel_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td valign="center" align="left">
<table border="0" cellspacing="0" cellpadding="10" width="830" bgcolor="#ffffff">
<tr><td align="left">
<table border="0" cellspacing="0" cellpadding="0" STYLE='table-layout:fixed' width="800" bgcolor="#ffffff">
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />

<tr height="1">
<td></td><td></td><td></td><td></td><td></td>
<td></td><td></td><td></td><td></td><td></td>
<td></td><td></td><td></td><td></td><td></td>
<td></td><td></td><td></td><td></td><td></td>
</tr>

<tr height="40"><td colspan="20" valign="top"><span class="lblblktext">Holidays</span></td></tr>
<form name="mapform" method="post" id="mapform"  autocomplete="off" action="<?php print $formfile ?>?calyear=<?php print $calyear ?>" >
<tr height="40">
<td colspan="6" valign="top">
<span class="lblblktext">View/edit template</span><br>
<select name="holidaymap" id="holidaymap" style="width:22em;" onchange="mapholdata()">
<?php
$listholidaymap = $myappt->sortlistarray($listholidaymap, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listholidaymap);
for ($i = 0; $i < $rc; $i++)
	print "<option value=\"".$listholidaymap[$i][0]."\">".$listholidaymap[$i][1]."</option>\n";
if ($rc == 0)
	print "<option value=\"000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000\">None</option>\n";
?>
</select></td>
<td>&nbsp;</td>
<td colspan="6" valign="top">
<span class="lblblktext">Template name</span><br>
<input type="text" size="30" maxlength="30" name="hmapname" id="hmapname" value="" />
</td>
<td>&nbsp;</td>
<td colspan="6" valign="top">
<span class="lblblktext">&nbsp;</span><br>
<input type="submit" name="submit_holmap_save" id="submit_holmap_save" value="Save template" class="btntext" />
</td>
</tr>
</form>
<tr><td colspan="20">
<table width=100% cellspacing="0" cellpadding="0" border="0">
<tr><th colspan="4" class="cal_year">
<?php
if ($calyear == 1970)
	print "<img src=\"../appcore/images/appt_arrow_left_off.jpg\" width=\"27\" height=\"19\" border=\"0\">"
		. " ".$calyear
		. " <a href=\"".$formfile."?calyear=".($calyear+1)."\">"
		. "<img src=\"../appcore/images/appt_arrow_right.jpg\" width=\"27\" height=\"19\" border=\"0\">"
		. "</a>\n";
else
	print "<a href=\"".$formfile."?calyear=".($calyear-1)."\" title=\"Previous year\">"
		. "<img src=\"../appcore/images/appt_arrow_left.jpg\" width=\"27\" height=\"19\" border=\"0\">"
		. "</a> ".$calyear
		. " <a href=\"".$formfile."?calyear=".($calyear+1)."\" title=\"Next year\">"
		. "<img src=\"../appcore/images/appt_arrow_right.jpg\" width=\"27\" height=\"19\" border=\"0\">"
		. "</a>\n";
?>
</th></tr>
<?php
for ($calrow = 0; $calrow < 3; $calrow++)
{
?>
<tr class="cal_rows">
<?php
	for ($calcol = 0; $calcol < 4; $calcol++)
	{
		$calmonthnum = ($calrow * 4) + $calcol + 1;
		// the day of week of the month start: 0=sun  .. 6=sat
		$utime = strtotime($calyear.'/'.$calmonthnum.'/01');
		$calwd = date('w', $utime);
		$calndays = date('t', $utime);
		$calmonthname = date('M', $utime);
?>
<td>
<table cellspacing="0" cellpadding="0" border="0">
<tr><th colspan="7" class="cal_monthname"><?php print $calmonthname ?></th></tr>
<tr class="cal_days">
<th>S</th>
<th>M</th>
<th>T</th>
<th>W</th>
<th>T</th>
<th>F</th>
<th>S</th>
</tr>
<?PHP
		// output the month up to 6 rows of 7 columns
		$daynum = 1;
		$tdd = 1;
	
		for ($r = 0; $r < 6; $r++)
		{
			print "<tr>\n";
			for ($d = 0; $d < 7; $d++)
			{
				// the bit number in the holiday map. Each cell needs to be identified by its bit number
				$bitnum = 32 * ($calmonthnum - 1) + ($daynum -1);
				if (($daynum == 1) && ($d < $calwd))
					$strt = 0;
				else
					$strt = 1;
			
				// print the trailing squares
				if ($daynum > $calndays)
				{
					if (($d == 0) || ($d == 6))
						print "<td class=\"cal_weekend\"><div>";
					else
						print "<td class=\"cal_weekday\"><div>";
					print "&nbsp;";
				}
				else
				{
					if ($strt)
					{
						// print the squares with dates in them. These need an id for js style mods.
						if (($d == 0) || ($d == 6))
							print "<td class=\"cal_weekend\" id=\"calbit".$bitnum."\" onclick=\"cellprocess('calbit".$bitnum."')\"><div>";
						else
							print "<td class=\"cal_weekday\" id=\"calbit".$bitnum."\" onclick=\"cellprocess('calbit".$bitnum."')\"><div>";
						print "<div class=\"cal_date\">".$daynum."</div>";
					}
					else
					{
						// print the leading squares
						if (($d == 0) || ($d == 6))
							print "<td class=\"cal_weekend\"><div>";
						else
							print "<td class=\"cal_weekday\"><div>";
						print "&nbsp;";
					}
				}
				print "</div>";
				if ($strt)
					$daynum++;
				print "</td>\n";
			}
			print "</tr>\n";
		}
		// end of month table
		print "</table>\n";
		// end of month cell
		print "</td>\n";
	}
	// next row of months
	print "</tr>\n";
}
?>
</table>
</td></tr>
</table>
</td></tr></table>
</td>
<td width="2" background="../appcore/images/bevel_r.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
</tr>
<tr height="2">
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_botl.gif" width="2" /></td>
<td background="../appcore/images/bevel_bot.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_botr.gif" width="2" /></td>
</tr>
</table>
</tr>
<tr>
<td align="center" valign="bottom">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr>
<td width="30%" align="left">
&nbsp;
</td>
<td width="40%" align="center">
<span class="smlgrytext">&nbsp;</span>
</td>
<td width="30%" align="right">
<span align="right"><img height="25" src="../appcore/images/AuthentX-logo-plain-gray6.gif" width="94" /></span>
</td>
</tr>
</table>
</tr>
</table>
</td>
<td width="12" background="../appcore/images/box_mtl_r.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
</tr>
<tr height="14">
<td width="12" height="14"><img height="14" src="../appcore/images/box_mtl_botl.gif" width="12" /></td>
<td valign="top" background="../appcore/images/box_mtl_bot.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="12"><img height="14" src="../appcore/images/box_mtl_botr.gif" width="12" /></td>
</tr>
</table>
</td>
</tr>
</table>
</body></html>