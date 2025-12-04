<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-reports.php";

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

if (!$tab_reports)
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
	
// date values for today
$dt_y = date("Y");
$dt_m = date("m");
$dt_d = date("d");

// process the report execution request
if (isset($_POST["submit_report"]))
{
	// setup the dates
	if (isset($_POST["sd_d"]))
	{
		$sd_d = $_POST["sd_d"];
		if (($sd_d < 1) || ($sd_d > 31))
			$sd_d = false;
	}
	else
		$sd_d = false;
	
	if (isset($_POST["sd_m"]))
	{
		$sd_m = $_POST["sd_m"];
		if (($sd_m < 1) || ($sd_m > 12))
			$sd_m = false;
	}
	else
		$sd_m = false;
	
	if (isset($_POST["sd_y"]))
	{
		$sd_y = $_POST["sd_y"];
		if (is_nan($sd_y))
			$sd_y = false;
	}
	else
		$sd_y = false;
	
	if (($sd_d === false) || ($sd_m === false) || ($sd_y === false))
		$sdate = false;
	else
		$sdate = str_pad($sd_y, 4, "0", STR_PAD_LEFT)."-".str_pad($sd_m, 2, "0", STR_PAD_LEFT)."-".str_pad($sd_d, 2, "0", STR_PAD_LEFT);
	
	if (isset($_POST["ed_d"]))
	{
		$ed_d = $_POST["ed_d"];
		if (($ed_d < 1) || ($ed_d > 31))
			$ed_d = false;
	}
	else
		$ed_d = false;
	
	if (isset($_POST["ed_m"]))
	{
		$ed_m = $_POST["ed_m"];
		if (($ed_m < 1) || ($ed_m > 12))
			$ed_m = false;
	}
	else
		$ed_m = false;
	
	if (isset($_POST["ed_y"]))
	{
		$ed_y = $_POST["ed_y"];
		if (is_nan($ed_y))
			$ed_y = false;
	}
	else
		$ed_y = false;
	
	if (($ed_d === false) || ($ed_m === false) || ($ed_y === false))
		$edate = false;
	else
		$edate = str_pad($ed_y, 4, "0", STR_PAD_LEFT)."-".str_pad($ed_m, 2, "0", STR_PAD_LEFT)."-".str_pad($ed_d, 2, "0", STR_PAD_LEFT);
		
	// Call the report
	if ($sdate !== false)
	{
		$urlq = "?sd=".urlencode($sdate);
		if ($edate !== false)
			$urlq .= "&ed=".urlencode($edate);
	}
	elseif ($edate !== false)
		$urlq = "?ed=".urlencode($edate);
	else
		$urlq = "";
		
	if (isset($_POST["report"]))
	{
		$rpt = $_POST["report"];
		$reporturl = htmlentities($rpt).$urlq;	

		$myappt->popmeup($reporturl, "report", "toolbar=no,width=800,height=750,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=yes");
	}
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<title>Authentx Appointments Management System</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
</head>
<body>
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
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-holmap.php\"><span class=\"tabtext\">Holidays</span></a></td>\n";
if ($tab_reports)
	print "<td width=\"105\" class=\"tabcell_on\"><span class=\"tabtext\">Reports</span></td>\n";
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

<tr height="40"><td colspan="20" valign="top"><span class="lblblktext">Reports</span></td></tr>
<?php
// Only print this table if the privilege bits are present
if ($myappt->checkprivilege(PRIV_RPT))
{
?>
<form name="mainform" method="post" action="<?php print $formfile ?>"  autocomplete="off" >
<tr height="40">
<td colspan="7" valign="top">
<span class="lblblktext">Select Report</span><br/>
<select name="report" style="width: 22em" tabindex="1">
<?php
$listreportset = $myappt->sortlistarray($listreportset, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listreportset);
for ($i = 0; $i < $rc; $i++)
	print "<option value=\"".$listreportset[$i][0]."\">".$listreportset[$i][1]."</option>\n";
?>
</select>
</td>
<td colspan="6" valign="top">
<span class="lblblktext">Start Date (mm-dd-yyyy)</span><br/>
<input type="text" size="4" maxlength="2" tabindex="2" name="sd_m" value="<?php print $dt_m ?>" />
-
<input type="text" size="4" maxlength="2" tabindex="3" name="sd_d" value="<?php print $dt_d ?>" />
-
<input type="text" size="6" maxlength="4" tabindex="4" name="sd_y" value="<?php print $dt_y ?>" />
</td>
<td colspan="6" valign="top">
<span class="lblblktext">End Date (mm-dd-yyyy)</span><br/>
<input type="text" size="4" maxlength="2" tabindex="2" name="ed_m" value="<?php print $dt_m ?>" />
-
<input type="text" size="4" maxlength="2" tabindex="3" name="ed_d" value="<?php print $dt_d ?>" />
-
<input type="text" size="6" maxlength="4" tabindex="4" name="ed_y" value="<?php print $dt_y ?>" />
</td>
</tr>
<tr height="40">
<td colspan="7" valign="top">
<span class="lblblktext">&nbsp;</span><br/>
<input type="submit" name="submit_report" class="btntext" value="Run Report">
</td>
<td colspan="6" valign="top">&nbsp;</td>
<td colspan="6" valign="top">&nbsp;</td>
</tr>
<?php
}
?>
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