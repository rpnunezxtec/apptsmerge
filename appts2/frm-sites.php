<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-sites.php";

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

if (!$tab_sites)
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
	
$f_component = false;
$f_status = false;
$f_state = false;
$urlf = "";

// privilege to 'add' a site
$priv_screate = $myappt->checkprivilege(PRIV_SCREATE);

// find out what page we are on
if (isset($_GET["pg"]))
{
	$pg = $_GET["pg"];
	if (is_nan($pg))
		$pg = 1;
}
else
	$pg = 1;

// Filters for other pages
if (isset($_GET["f_component"]))
{
	$f_component = $_GET["f_component"];
	if (trim($f_component == ""))
		$f_component = false;
	else
		$urlf .= "&f_component=".urlencode($f_component);
}

if (isset($_GET["f_status"]))
{
	$f_status = $_GET["f_status"];
	if (!is_numeric($f_status))
		$f_status = false;
	else
		$urlf .= "&f_status=".urlencode($f_status);
}

if (isset($_GET["f_state"]))
{
	$f_state = $_GET["f_state"];
	if (trim($f_state == ""))
		$f_state = false;
	else
		$urlf .= "&f_state=".urlencode($f_state);
}

// Posted filters
if (isset($_POST["btn_filter"]))
{
	// component
	if (isset($_POST["f_component"]))
	{
		$f_component = trim($_POST["f_component"]);
		if ($f_component == "")
			$f_component = false;
		else
			$urlf .= "&f_component=".urlencode($f_component);
	}

	// status
	if (isset($_POST["f_status"]))
	{
		$f_status = $_POST["f_status"];
		if (!is_numeric($f_status))
			$f_status = false;
		else
			$urlf .= "&f_status=".urlencode($f_status);
	}
	
	// state
	if (isset($_POST["f_state"]))
	{
		$f_state = trim($_POST["f_state"]);
		if ($f_state == "")
			$f_state = false;
		else
			$urlf .= "&f_state=".urlencode($f_state);
	}
}

// Get the site data from the database
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	$q_site = "select siteid, "
		. "\n centeruuid, "
		. "\n sitename, "
		. "\n sitecomponent, "
		. "\n sitetype, "
		. "\n siteactivity, "
		. "\n sitecontactphone, "
		. "\n sitecontactname, "
		. "\n siteaddrstate, "
		. "\n siteaddrcity, "
		. "\n timezone, "
		. "\n hmapuuid, "
		. "\n slottime, "
		. "\n siteblockout, "
		. "\n status "
		. "\n from site "
		. "\n where siteid>0 "
		;
		
	if ($f_state !== false)
		$q_site .= "\n and siteaddrstate='".$sdbh->real_escape_string($f_state)."' ";
	if ($f_component !== false)
		$q_site .= "\n and sitecomponent='".$sdbh->real_escape_string($f_component)."' ";
	if ($f_status !== false)
		$q_site .= "\n and status='".$sdbh->real_escape_string($f_status)."' ";
	
	$q_site .= "\n order by siteaddrstate, siteaddrcity, sitename ";
	
	$s_site = $sdbh->query($q_site);
	if ($s_site)
	{
		$rnum = 1;
		$dset = array();
		$nds = 0;
		$nr = $s_site->num_rows;
		
		while ($r_site = $s_site->fetch_assoc())
		{
			if ($rpp > 0)
				$rptest = ($rnum > ($rpp * ($pg-1))) && ($rnum <= ($rpp * $pg));
			else
				$rptest = 1;
			
			if ($rptest)
			{
				$dset[$nds]["sitename"] = $r_site["sitename"];
				$dset[$nds]["status"] = ($r_site["status"] == 0 ? "unavailable" : "available");
				$dset[$nds]["slottime"] = $r_site["slottime"];
				$dset[$nds]["centeruuid"] = $r_site["centeruuid"];
				$dset[$nds]["timezone"] = $r_site["timezone"];
				$dset[$nds]["sitecomponent"] = $r_site["sitecomponent"];
				$dset[$nds]["sitetype"] = $r_site["sitetype"];
				$dset[$nds]["siteactivity"] = $r_site["siteactivity"];
				$dset[$nds]["siteaddrstate"] = $r_site["siteaddrstate"];
				$dset[$nds]["siteaddrcity"] = $r_site["siteaddrcity"];
				$dset[$nds]["siteblockout"] = $r_site["siteblockout"];
				if ($dset[$nds]["siteblockout"] == NULL)
					$dset[$nds]["siteblockout"] = 0;
					
				$centeruuid = $r_site["centeruuid"];
				// Find the number of WS for this site
				$q_ws = "select count(*) as n "
						. "\n from workstation "
						. "\n where centeruuid='".$centeruuid."'"
						;
				$s_ws = $sdbh->query($q_ws);
				if ($s_ws)
				{
					$r_ws = $s_ws->fetch_assoc();
					if ($r_ws)
						$dset[$nds]["numws"] = $r_ws["n"];
					$s_ws->free();
				}
				else
					$dset[$nds]["numws"] = 0;
						
				// Find the holiday map name for this site
				$hmapuuid = $r_site["hmapuuid"];
				if ($hmapuuid != NULL)
				{
					$q_hol = "select hmapuuid, mapname "
							. "\n from holidaymap "
							. "\n where hmapuuid='".$hmapuuid."' "
							;
					$s_hol = $sdbh->query($q_hol);
					if ($s_hol)
					{
						$r_hol = $s_hol->fetch_assoc();
						if ($r_hol)
							$dset[$nds]["hmapname"] = $r_hol["mapname"];
						$s_hol->free();
					}
					else
						$dset[$nds]["hmapname"] = "-";
				}
				else
					$dset[$nds]["hmapname"] = "-";
					
				$dset[$nds]["avc"] = $myappt->session_createmac($centeruuid);
				
				$rnum++;
				$nds++;
			}
		}
		$s_site->free();
	}
	
	$sdbh->close();
}
else 
{
	$dset = array();
	$nds = 0;
	$nr = 0;
}

// calculate the number of pages to show
$np = intval($nr/$rpp);
if (($nr % $rpp) > 0)
	$np++;

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
	print "<td width=\"105\" class=\"tabcell_on\"><span class=\"tabtext\">Sites</span></td>\n";
if ($tab_ws)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-emws.php\"><span class=\"tabtext\">EMWS</span></a></td>\n";
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

<tr height="40"><td colspan="20" valign="top"><span class="lblblktext">Sites</span></td></tr>

<form name="filters" method="post" action="<?php print $formfile ?>" > 
<tr height="20">
<td colspan="20" valign="top"><span class="lblblktext">Filters:</span></td>
</tr>

<tr height="40">
<td colspan="5" valign="top">
<span class="lblblktext">Component</span><br/>
<select name="f_component" style="width: 15em">
<?php
$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listcomponent);
for ($i = 0; $i < $rc; $i++)
{
	if (strcasecmp($listcomponent[$i][0], $f_component) == 0)
		print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
	else
		print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
}
?>
</select>
</td>
<td colspan="5" valign="top">
<span class="lblblktext">State</span><br/>
<select name="f_state" style="width: 15em">
<?php
$rc = count($liststates);
$selected = false;
for ($i = 0; $i < $rc; $i++)
{
	if ($f_state == $liststates[$i][0])
	{
		print "<option selected value=\"".$liststates[$i][0]."\">".$liststates[$i][1]."</option>\n";
		$selected = true;
	}
	else
		print "<option value=\"".$liststates[$i][0]."\">".$liststates[$i][1]."</option>\n";
}
if ($selected === true)
	print "<option value=\"\">Any</option>\n";
else
	print "<option selected value=\"\">Any</option>\n";
?>
</select>
</td>
<td colspan="5" valign="top">
<span class="lblblktext">Status</span><br/>
<select name="f_status" style="width: 15em">
<option <?php print ($f_status == 0 ? "selected" : "") ?> value="0">Unavailable</option>
<option <?php print ($f_status == 1 ? "selected" : "") ?> value="1">Available</option>
<option <?php print ($f_status === false ? "selected" : "") ?> value="">Any</option>
?>
</select>
</td>
<td colspan="5" valign="top" valiggn="right">
<input type="submit" name="btn_filter" class="btntext" value="Apply" >
</td>
</tr>
</form>

<?php
// Only print this table if the privilege bits are present
if ($myappt->checkprivilege(PRIV_SCREATE) || $myappt->checkprivilege(PRIV_SITEEDIT) || $myappt->checkprivilege(PRIV_SHOURS) || $myappt->checkprivilege(PRIV_SSTAT))
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
<td width="25%" class="matrixheading"><span class="tableheading">Site Name</span></td>
<td width="7%" class="matrixheading"><span class="tableheading">State</span></td>
<td width="15%" class="matrixheading"><span class="tableheading">City</span></td>
<td width="7%" class="matrixheading"><span class="tableheading"># WS</span></td>
<td width="12%" class="matrixheading"><span class="tableheading">Status</span></td>
<td width="7%" class="matrixheading"><span class="tableheading">Slot</span></td>
<td width="10%" class="matrixheading"><span class="tableheading">Blockout</span></td>
<td width="17%" class="matrixheading"><span class="tableheading">Holiday Map</span></td>
</tr>
<?php 
if ($priv_screate && $_axsitesync_enable === false)
{
?>
<tr>
<td class="matrixline"><span class="tabletext">
<a href="javascript:popupOpener('pop-editsitedetail.php','editsitedetail',350,500)">Add...</a>
</span></td>
<td class="matrixline"><span class="tabletext">&nbsp;</span></td>
<td class="matrixline"><span class="tabletext">&nbsp;</span></td>
<td class="matrixline"><span class="tabletext">&nbsp;</span></td>
<td class="matrixline"><span class="tabletext">&nbsp;</span></td>
<td class="matrixline"><span class="tabletext">&nbsp;</span></td>
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
<a href="javascript:popupOpener('pop-editsitedetail.php?centeruuid=<?php print urlencode($dset[$i]["centeruuid"])."&avc=".urlencode($dset[$i]["avc"]) ?>','editsitedetail',350,500)"><?php print htmlentities($dset[$i]["sitename"]) ?></a>
</span></td>
<td class="matrixline"><span class="tabletext"><?php print $dset[$i]["siteaddrstate"] ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print $dset[$i]["siteaddrcity"] ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print $dset[$i]["numws"] ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print $dset[$i]["status"] ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print $dset[$i]["slottime"] ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print $dset[$i]["siteblockout"] ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print htmlentities($dset[$i]["hmapname"]) ?></span></td>
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
<td width="12" height="14"><img height="14" src="../appcore/images/box_mtl_botl.gif" width="12"></td>
<td valign="top" background="../appcore/images/box_mtl_bot.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="12"><img height="14" src="../appcore/images/box_mtl_botr.gif" width="12" /></td>
</tr>
</table>
</td>
</tr>
</table>
</body></html>