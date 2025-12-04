<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-mailtmpl.php";

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

if (!$tab_mailtmpl)
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

// Get the template data from the database
$dset = array();
$nds = 0;
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	$q_t = "select * from mailtemplate order by mtname";
	$s_t = $sdbh->query($q_t);
	if ($s_t)
	{
		$nr = $s_t->num_rows;
	
		while ($r_t = $s_t->fetch_assoc())
		{
			// get the uuid and create a MAC
			$dset[$nds]["mtid"] = $r_t["mtid"];
			$dset[$nds]["mtuuid"] = $r_t["mtuuid"];
			$dset[$nds]["avc"] = $myappt->session_createmac($r_t["mtuuid"]);
			$dset[$nds]["mtname"] = $r_t["mtname"];
			$dset[$nds]["mtsubject"] = "";
			$dset[$nds]["mtfrom"] = "";
			if (!empty($r_t["mtfrom"]))
				$dset[$nds]["mtfrom"] = base64_decode($r_t["mtfrom"]);
			if (!empty($r_t["mtsubject"]))
				$dset[$nds]["mtsubject"] = base64_decode($r_t["mtsubject"]);
			
			$nds++;
		}
		$s_t->free();
	}
	$sdbh->close();
}
else 
{
	$nr = 0;
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8">
<title>Authentx Appointments Management System</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico">
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css">
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
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-reports.php\"><span class=\"tabtext\">Reports</span></a></td>\n";
if ($tab_invite)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-userinvite.php\"><span class=\"tabtext\">Invite</span></a></td>\n";
if ($tab_mailtmpl)
	print "<td width=\"105\" class=\"tabcell_on\"><span class=\"tabtext\">Templates</span></td>\n";
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
<p/>
<table cellSpacing="0" cellPadding="0" align="center" border="0" width="858">
<tr><td><img height="12" src="../appcore/images/logoslice_1.gif" width="200" border="0"></td>
</tr>
<tr><td valign="top" background="../appcore/images/box_mtl_ctr.gif">
<table cellSpacing="0" cellPadding="0" border="0" width="858">
<tr height="12"><td width="12" height="12"><img height="12" src="../appcore/images/box_mtl_topl.gif" width="12"></td>
<td background="../appcore/images/box_mtl_top.gif"><img height="12" src="../appcore/images/logoslice_2.gif" width="188" border="0"></td>
<td width="12"><img height="12" src="../appcore/images/box_mtl_topr.gif" width="12"></td>
</tr>
<tr valign="top">
<td width="12" background="../appcore/images/box_mtl_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1"></td>
<td align="middle" background="../appcore/images/bg_spacer.gif">
<table cellSpacing="0" cellPadding="0" border="0" width="834">
<tr><td align="middle">
<table cellSpacing="0" cellPadding="0" border="0" width="834">
<tr height="0"><td align="left" width="220"><img height="56" src="../appcore/images/logoslice_3.gif" width="188" border="0"></td>
<td align="right">
<table cellSpacing="0" cellPadding="0" border="0" width="610">
<tr>
<td align="left" width="450">
<table  cellSpacing="0" cellPadding="0" border="0" width="450">
<tr height="28"><td valign="top"><span class="siteheading"><?php print SITEHEADING ?></span></td>
</tr><tr height="28"><td valign="top"><span class="nameheading"></span></td></tr>
</table>
</td>
<td align="right" width="160">
<table cellSpacing="0" cellPadding="0" border="0" width="160">
<tr height="28" valign="middle">
<td align="middle" width="80"></td>
<td align="middle" width="40"></td>
<td align="middle" width="40"></td>
</tr>
<tr height="28" valign="middle">
<td align="middle"></td>
<td align="middle" colspan="2"><a href="vec-logout.php" title="Log off the system"><img src="../appcore/images/icon-btnlogoff.gif" width="75" height="24" border="0" onclick='return frmCheckDirty()'></a></td>
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
<table cellSpacing="0" cellPadding="0" border="0" width="834">
<tr height="2"><td width="2" height="2"><img height="2" src="../appcore/images/bevel_topl.gif" width="2"></td>
<td background="../appcore/images/bevel_top.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1"></td>
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_topr.gif" width="2"></td>
</tr>
<tr><td width="2" background="../appcore/images/bevel_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1"></td>
<td valign="center" align="left">
<table border="0" cellspacing="0" cellpadding="10" width="830" bgcolor="#ffffff">
<tr><td align="left">
<table border="0" cellspacing="0" cellpadding="0" style='table-layout:fixed' width="800" bgcolor="#ffffff">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">

<tr><td colspan="20" valign="top">
<table width="100%" border="1" cellpadding="1" cellspacing="0">
<tr height="20">
<td width="20%" class="matrixheading"><span class="tableheading">Template</span></td>
<td width="20%" class="matrixheading"><span class="tableheading">From</span></td>
<td width="60%" class="matrixheading"><span class="tableheading">Subject</span></td>
</tr>
<?php
for ($i = 0; $i < $nds; $i++)
{
?>
<tr>
<td class="matrixline"><span class="tabletext">
<a href="javascript:popupOpener('pop-editmtdetail.php?mtuuid=<?php print urlencode($dset[$i]["mtuuid"])."&avc=".urlencode($dset[$i]["avc"]) ?>','editmtdetail',350,500)"><?php print htmlentities($dset[$i]["mtname"]) ?></a>
</span></td>
<td class="matrixline"><span class="tabletext"><?php print htmlentities($dset[$i]["mtfrom"]) ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print htmlentities($dset[$i]["mtsubject"]) ?></span></td>
<?php 
}
?>
</table>
</td></tr>
</table>
</td></tr></table>
</td>
<td width="2" background="../appcore/images/bevel_r.gif"><img height="1" src="../appcore/images/spacer.gif" width="1"></td>
</tr>
<tr height="2">
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_botl.gif" width="2"></td>
<td background="../appcore/images/bevel_bot.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1"></td>
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_botr.gif" width="2"></td>
</tr>
</table>
</tr>
<tr>
<td align="center" valign="bottom">
<table cellSpacing="0" cellPadding="0" border="0" width="834">
<tr>
<td width="30%" align="left">
&nbsp;
</td>
<td width="40%" align="center">
<span class="smlgrytext">&nbsp;</span>
</td>
<td width="30%" align="right">
<span align="right"><img height="25" src="../appcore/images/AuthentX-logo-plain-gray6.gif" width="94"></span>
</td>
</tr>
</table>
</tr>
</table>
</td>
<td width="12" background="../appcore/images/box_mtl_r.gif"><img height="1" src="../appcore/images/spacer.gif" width="1"></td>
</tr>
<tr height="14">
<td width="12" height="14"><img height="14" src="../appcore/images/box_mtl_botl.gif" width="12"></td>
<td valign="top" background="../appcore/images/box_mtl_bot.gif"><img height="1" src="../appcore/images/spacer.gif" width="1"></td>
<td width="12"><img height="14" src="../appcore/images/box_mtl_botr.gif" width="12"></td>
</tr>
</table>
</td>
</tr>
</table>
</body></html>