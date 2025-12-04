<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-emws.php";

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
$searchvar = "emsearch";

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

if (!$tab_ws)
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

$f_site = false;
$urlf = "";

// find out what page we are on
if (isset($_GET["pg"]))
{
	$pg = $_GET["pg"];
	if (!is_numeric($pg))
		$pg = 1;
}
else
	$pg = 1;

// Filters for other pages
if (isset($_GET["f_site"]))
{
	$f_site = $_GET["f_site"];
	if (!is_numeric($f_site))
		$f_site = false;
	else
		$urlf .= "&f_site=".urlencode($f_site);
}

// Search seeding
if (isset($_POST["btn_emsearch"]))
{
	if (isset($_POST["emsearch"]))
	{
		$emsearch = trim($_POST["emsearch"]);
		if (!empty($emsearch))
			$myappt->session_setvar($searchvar, $emsearch);
		else
			$myappt->session_setvar($searchvar, NULL);
	}
}
$emsearch = $myappt->session_getvar($searchvar);
if (empty($emsearch))
	$emsearch = "";

// Filter selection
if (isset($_POST["btn_filter"]))
{
	// site
	if (isset($_POST["f_site"]))
	{
		$f_site = trim($_POST["f_site"]);
		if (!is_numeric($f_site))
			$f_site = false;
		else
			$urlf .= "&f_site=".urlencode($f_site);
	}
}

// Get the ws data from the database
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	$q_ws = "select ws.wsid, "
		. "\n ws.wsuuid, "
		. "\n ws.wsname, "
		. "\n ws.status, "
		. "\n ws.centeruuid, "
		. "\n s.sitename, "
		. "\n s.centeruuid "
		. "\n from workstation as ws "
		. "\n left join site as s on ws.centeruuid=s.centeruuid "
		. "\n where ws.wsid>0"
		;
		
	if ($f_site !== false)
		$q_ws .= "\n and ws.centeruuid='".$sdbh->real_escape_string($f_site)."' ";
	
	if (!empty($emsearch))
		$q_ws .= "\n and ws.wsname like '%".$sdbh->real_escape_string($emsearch)."%' ";
	
	$q_ws .= "\n order by s.sitename, ws.wsname ";
	
	$s_ws = $sdbh->query($q_ws);
	if ($s_ws)
	{
		$nr = $s_ws->num_rows;
		$dset = array();
		$nds = 0;
		$rnum = 1;

		while ($r_ws = $s_ws->fetch_assoc())
		{
			if ($rpp > 0)
				$rptest = ($rnum > ($rpp * ($pg-1))) && ($rnum <= ($rpp * $pg));
			else
				$rptest = 1;
			
			if ($rptest)
			{
				$dset[$nds]["wsname"] = $r_ws["wsname"];
				$dset[$nds]["sitename"] = $r_ws["sitename"];
				$dset[$nds]["status"] = ($r_ws["status"] == 0 ? "unavailable" : "available");
				$dset[$nds]["wsuuid"] = $r_ws["wsuuid"];
				$dset[$nds]["avc"] = $myappt->session_createmac($r_ws["wsuuid"]);
				
				$rnum++;
				$nds++;
			}
		}
		$s_ws->free();
	}
}
else 
{
	$nr = 0;
	$nds = 0;
	$dset = array();
}

// calculate the number of pages to show
$np = intval($nr/$rpp);
if (($nr % $rpp) > 0)
	$np++;

if (!($sdbh->connect_error))
{
	// Create a list array of sites with workstations
	// [0] = centeruuid, [1] = sitename
	$listsites = array();
	$q_s = "select distinct ws.centeruuid, "
		. "\n s.sitename "
		. "\n from workstation as ws "
		. "\n left join site as s on s.centeruuid=ws.centeruuid "
		. "\n where s.sitename is not NULL "
		. "\n order by s.sitename "
		;
		
	$s_s = $sdbh->query($q_s);
	$ns = 0;
	if ($s_s)
	{
		while ($r_s = $s_s->fetch_assoc())
		{
			$listsites[$ns] = array($r_s["centeruuid"], $r_s["sitename"]);
			$ns++;
		}
		$s_s->free();
	}

	$sdbh->close();
}

?>
<!DOCTYPE html>
<html>
<head>
<META http-equiv="Default-Style" content="text/html;charset=UTF-8">
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
	print "<td width=\"105\" class=\"tabcell_on\"><span class=\"tabtext\">EMWS</span></td>\n";
if ($tab_holmaps)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-holmap.php\"><span class=\"tabtext\">Holidays</span></a></td>\n";
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
<TABLE border="0" cellspacing="0" cellpadding="10" width="830" bgcolor="#ffffff">
<tr><td align="left">
<TABLE border="0" cellspacing="0" cellpadding="0" STYLE='table-layout:fixed' width="800" bgcolor="#ffffff">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">
<col width="5%"><col width="5%"><col width="5%"><col width="5%"><col width="5%">

<form name="wsearch" method="post" action="<?php print $formfile ?>" > 
<tr height="40">
<td colspan="13" valign="top"><span class="lblblktext">Workstations</span></td>
<td colspan="7" valign="top" align="right">
<input type="text" name="emsearch" id="emsearch" value="<?php print htmlentities($emsearch) ?>" size="30" maxlength="30" placeholder="Name search.." />
<input type="submit" name="btn_emsearch" class="btntext" value="Q" />
</td>
</tr>

<tr height="20">
<td colspan="20" valign="top"><span class="lblblktext">Filters:</span></td>
</tr>

<tr height="40">
<td colspan="5" valign="top">
<span class="lblblktext">Site</span><br/>
<select name="f_site" style="width: 15em">
<?php
$rc = count($listsites);
$selected = false;
for ($i = 0; $i < $rc; $i++)
{
	if (strcasecmp($listsites[$i][0], $f_site) == 0)
	{
		print "<option selected value=\"".$listsites[$i][0]."\">".$listsites[$i][1]."</option>\n";
		$selected = true;
	}
	else
		print "<option value=\"".$listsites[$i][0]."\">".$listsites[$i][1]."</option>\n";
}

if ($selected === true)
	print "<option value=\"\">Any</option>\n";
else
	print "<option selected value=\"\">Any</option>\n";
	
?>
</select>
</td>
<td colspan="5" valign="top"><span class="lblblktext"></span><br/></td>
<td colspan="5" valign="top"><span class="lblblktext"></span><br/></td>
<td colspan="5" valign="top" valiggn="right">
<input type="submit" name="btn_filter" class="btntext" value="Apply" />
</td>
</tr>
</form>

<?php
// Only print this table if the privilege bits are present
if ($myappt->checkprivilege(PRIV_WSASGN) || $myappt->checkprivilege(PRIV_WSCREATE) || $myappt->checkprivilege(PRIV_WSSTAT))
{
	// print page numbers for records
	print "<tr><td colspan=\"20\" valign=\"top\">\n";
	print "<span class=\"pageon\">Page: </span>\n";
	for ($i = 0; $i < $np; $i++)
	{
		if ($pg == $i+1)
			print "&nbsp;<span class=\"pageon\">".($i+1)."</span>&nbsp;\n";
		else
			print "<a href=\"".htmlentities($formfile)."?pg=".($i+1).$urlf."\">&nbsp;<span class=\"pageoff\">".($i+1)."</span>&nbsp;</a>\n";
	}
	print "</td></tr>\n";	
?>
<tr><td colspan="20" valign="top">
<table width="100%" border="1" cellpadding="1" cellspacing="0">
<tr height="20">
<td width="35%" class="matrixheading"><span class="tableheading">WS Name</span></td>
<td width="35%" class="matrixheading"><span class="tableheading">Site</span></td>
<td width="30%" class="matrixheading"><span class="tableheading">Status</span></td>
</tr>
<?php 
	if ($_axsitesync_enable === false)
	{
?>
<tr>
<td class="matrixline"><span class="tabletext">
<a href="javascript:popupOpener('pop-editwsdetail.php','editwsdetail',350,500)">Add...</a>
</span></td>
<td class="matrixline"><span class="tabletext">&nbsp;</span></td>
<td class="matrixline"><span class="tabletext">&nbsp;</span></td>
</tr>
<?php 
	}
?>
<?php
	for ($i = 0; $i < $nds; $i++)
	{	
		// output each row for this page
?>
<tr>
<td class="matrixline"><span class="tabletext">
<a href="javascript:popupOpener('pop-editwsdetail.php?wsuuid=<?php print urlencode($dset[$i]["wsuuid"])."&avc=".urlencode($dset[$i]["avc"]) ?>','editwsdetail',350,500)"><?php print htmlentities($dset[$i]["wsname"]) ?></a>
</span></td>
<td class="matrixline"><span class="tabletext"><?php print htmlentities($dset[$i]["sitename"]) ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print $dset[$i]["status"] ?></span></td>
</tr>
<?php
	}
?>
</table>
</td></tr>
<?php
}
?>
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