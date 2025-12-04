<?php
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-repldash.php";

include("config.php");
require_once("replication/config/config-repl.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
require_once(_AUTHENTX5APPCORE."/cl-forms.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myform = new authentxforms();
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

if (!$tab_repldash)
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

// form section. 0=status form, 1=logs form, 2=config form
// The buttons change this value when submitted.
// Default is status
$f_section = 0;

if (isset($_GET["f_section"]))
{
	$f_section = $_GET["f_section"];
	if (!is_numeric($f_section))
		$f_section = 0;
	else
		$urlf = $formfile."?f_section=".$f_section;
}

if ($f_section == 0)	// Status display
{
	$rset = array();
	$pset = array();
	$sdbh = new mysqli(DB_DBHOST_APPTSREPLOG, DB_DBUSER_APPTSREPLOG, DB_DBPASSWD_APPTSREPLOG, DB_DBNAME_APPTSREPLOG, DB_DBPORT_APPTSREPLOG);
	if (!$sdbh->connect_error)
	{
		// Get the last 25 consumer requests from this server
		$q_r = "select "
			. "\n providerurl, "
			. "\n csrstamp, "
			. "\n tablename, "
			. "\n csrmodifystamp, "
			. "\n respqty, "
			. "\n consumersetrequest.setid, "
			. "\n rqty "
			. "\n from consumersetrequest "
			. "\n left join consumerrowrequest on consumerrowrequest.csreqid=consumersetrequest.csreqid "
			. "\n order by csrstamp desc "
			. "\n limit 25 "
			;
		$s_r = $sdbh->query($q_r);
		
		$n = 0;
		if ($s_r)
		{
			while ($r_r = $s_r->fetch_assoc())
			{
				$rset[$n]["url"] = $r_r["providerurl"];
				$rset[$n]["csrstamp"] = $r_r["csrstamp"];
				$rset[$n]["csrmodstamp"] = $r_r["csrmodifystamp"];
				$rset[$n]["respqty"] = $r_r["respqty"];
				$rset[$n]["setid"] = $r_r["setid"];
				$rset[$n]["table"] = $r_r["tablename"];
				if (isset($r_r["rqty"]))
				{
					if ($r_r["rqty"] == "")
						$rset[$n]["rqty"] = 0;
					else
						$rset[$n]["rqty"] = $r_r["rqty"];
				}
				else 
					$rset[$n]["rqty"] = 0;
				$n++;
			}
			$s_r->free();
		}

		// Get the last 25 provider requests to this server
		$q_p = "select "
			. "\n consumerhost, "
			. "\n psrstamp, "
			. "\n psrupdatestamp, "
			. "\n searchtime, "
			. "\n respqty, "
			. "\n providersetrequest.setid, "
			. "\n rqty, "
			. "\n tablename "
			. "\n from providersetrequest "
			. "\n left join providerrowrequest on providerrowrequest.psreqid=providersetrequest.psreqid "
			. "\n order by psrstamp desc "
			. "\n limit 25 "
			;
		$s_p = $sdbh->query($q_p);
		
		$n = 0;
		if ($s_p)
		{
			while ($r_p = $s_p->fetch_assoc())
			{
				$pset[$n]["host"] = $r_p["consumerhost"];
				$pset[$n]["psrstamp"] = $r_p["psrstamp"];
				$pset[$n]["psrmodstamp"] = $r_p["psrupdatestamp"];
				$pset[$n]["respqty"] = $r_p["respqty"];
				$pset[$n]["searchtime"] = $r_p["searchtime"];
				$pset[$n]["setid"] = $r_p["setid"];
				$pset[$n]["table"] = $r_p["tablename"];
				if ($r_p["rqty"] == "")
					$pset[$n]["rqty"] = 0;
				else
					$pset[$n]["rqty"] = $r_p["rqty"];
				$n++;
			}
			$s_p->free();
		}

		$sdbh->close();
	}
}
elseif ($f_section == 1)	// Logs display
{
	$logset = array();
	$n = 0;
	$sdbh = new mysqli(DB_DBHOST_APPTSREPLOG, DB_DBUSER_APPTSREPLOG, DB_DBPASSWD_APPTSREPLOG, DB_DBNAME_APPTSREPLOG, DB_DBPORT_APPTSREPLOG);
	if (!$sdbh->connect_error)
	{
		// Get the last 100 log messages
		$q_log = "select "
			. "\n errmsg, "
			. "\n errstamp, "
			. "\n setid "
			. "\n from errorlog "
			. "\n order by errstamp desc"
			. "\n limit 100 "
			;
		$s_log = $sdbh->query($q_log);
		if ($s_log)
		{
			while ($r_log = $s_log->fetch_assoc())
			{
				$logset[$n]["logmsg"] = $r_log["errmsg"];
				$logset[$n]["setid"] = $r_log["setid"];
				$logset[$n]["logdate"] = date("m/d/Y H:i:s", $r_log["errstamp"]);
				$n++;
			}
			$s_log->free();
		}
		$sdbh->close();
	}
}
elseif ($f_section == 2)	// Config section
{
	$providerset = array();
	$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
	if (!$sdbh->connect_error)
	{
		// A table of providers and edit/add links to add or modify a provider.
		// $providerset[n][rsid], [providerid], [providerurl], [rstatus], [repxsyncmts], [lastreq]
		$providerset = $myappt->getproviders($sdbh, false);

		$sdbh->close();
	}
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-store,no-Cache" />
<title>Authentx Appointments Management System</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<link rel=stylesheet type="text/css" href="../appcore/css/formpanel.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php
if ($f_section == 0)	// Status
{
	if (AJAX_REPLSTATUS_ENABLE === true)
	{
		print "<script language=\"javascript\" src=\"../appcore/scripts/js-ajax_replstatus.js\"></script>\n";
		print "<script language=\"javascript\">\n";
		print "xhrservice = '".AJAX_REPLSTATUSSERVICE."'\n";
		print "refreshinterval='".$refresh_replstatus."'\n";
		print "</script>\n";
	}
}
elseif ($f_section == 1)	// Logs
{
	if (AJAX_REPLLOGS_ENABLE === true)
	{
		print "<script language=\"javascript\" src=\"../appcore/scripts/js-ajax_repllogs.js\"></script>\n";
		print "<script language=\"javascript\">\n";
		print "xhrservice = '".AJAX_REPLLOGSSERVICE."'\n";
		print "refreshinterval='".$refresh_repllogs."'\n";
		print "</script>\n";
	}
}
?>
</head>
<?php
if ((AJAX_REPLSTATUS_ENABLE === true) || (AJAX_REPLLOGS_ENABLE === true))
{
	if (($refresh_replstatus !== false) || ($refresh_repllogs !== false))
		print "<body onload='startRefresh();'>\n";
	else 
		print "<body>\n";
}
else 
	print "<body>\n";
?>
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
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-mailtmpl.php\"><span class=\"tabtext\">Templates</span></a></td>\n";
if ($tab_repldash)
	print "<td width=\"105\" class=\"tabcell_on\"><span class=\"tabtext\">Replication</span></td>\n";
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
<tr><td align="left" valign="top">
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
<!-- Put BUTTONS here -->
<tr height="40">
<td colspan="4" valign="top">
	<input type="button" class="btntext" value="Status" onClick="window.location.href='<?php print $formfile."?f_section=0" ?>'" />
</td>
<td colspan="4" valign="top">
	<input type="button" class="btntext" value="Logs" onClick="window.location.href='<?php print $formfile."?f_section=1" ?>'" />
</td>
<td colspan="4" valign="top">
	<input type="button" class="btntext" value="Config" onClick="window.location.href='<?php print $formfile."?f_section=2" ?>'" />
</td>
<td colspan="4" valign="top"></td>
<td colspan="4" valign="top"></td>
</tr>
<hr />

<?php
if ($f_section == 0)	// Status
{
?>
	<tr>
	<td colspan="20" valign="top" id="pagedata">
		<table class="contentpanel">
		<?php $myform->frmrender_grid(960, 24) ?>
			<tr class="contentrow_40">
			<td class="contentcell_lt" colspan="24">
				<span class="lblblktext">Consumer (Incoming) Replication Status at <?php print gmdate("Y-m-d H:i:s T") ?></span>
			</td>
			</tr>
		
			<tr>
			<td class="contentcell_lt" colspan="24">
				<table class="dataview_noborder">
					<tr>
					<th width="30%" class="dataview_l">Request URL</th>
					<th width="20%" class="dataview_l">Date (<?php print date("T") ?>)</th>
					<th width="20%" class="dataview_l">Mod Date (<?php print date("T") ?>)</th>
					<th width="20%" class="dataview_l">Table</th>
					<th width="10%" class="dataview_l">Updates</th>
					</tr>
			
					<?php
					$np = count($rset);
					for ($i = 0; $i < $np; $i++)
					{
						if (strlen($rset[$i]["url"]) > 37)
							$url = substr($rset[$i]["url"], 0, 37)."...";
						else 
							$url = $rset[$i]["url"];
						
						if (strlen($rset[$i]["setid"]) > 17)
							$rsetid = substr($rset[$i]["setid"], 0, 17)."...";
						else 
							$rsetid = $rset[$i]["setid"];
						
						if (strlen($rset[$i]["table"]) > 17)
							$rtable = substr($rset[$i]["table"], 0, 17)."...";
						else
							$rtable = $rset[$i]["table"];
						
						print "<tr>";
						print "<td class=\"dataview_lt\" title=\"".$rset[$i]["url"]."\">".$url."</td>";
						print "<td class=\"dataview_lt\">".date("Y-m-d H:i:s", $rset[$i]["csrstamp"])."</td>";
						print "<td class=\"dataview_lt\">".date("Y-m-d H:i:s", $rset[$i]["csrmodstamp"])."</td>";
						print "<td class=\"dataview_lt\" title=\"".$rset[$i]["table"]."\">".$rtable."</td>";
						if ($rset[$i]["respqty"] > 0)
							print "<td class=\"dataview_lt\">".$rset[$i]["rqty"]."/".$rset[$i]["respqty"]."</td>";
						else
							print "<td class=\"dataview_lt\">".$rset[$i]["respqty"]."</td>";
						print "</tr>";
					}
					?>
				</table>
			<br/>
			<hr/>
			</td>
			</tr>

			<tr class="contentrow_40">
			<td class="contentcell_lt" colspan="24">
				<span class="lblblktext">Provider (Outgoing) Replication Status at <?php print gmdate("Y-m-d H:i:s T") ?></span>
			</td>
			</tr>
		
			<tr>
			<td class="contentcell_lt" colspan="24">
			<table class="dataview_noborder">
				<tr>
				<th width="25%" class="dataview_l">Consumer</th>
				<th width="20%" class="dataview_l">Date (Z)</th>
				<th width="20%" class="dataview_l">Mod Date (Z)</th>
				<th width="25%" class="dataview_l">Table</th>
				<th width="10%" class="dataview_l">Updates</th>
				</tr>

				<?php
				$np = count($pset);
				for ($i = 0; $i < $np; $i++)
				{
					if (strlen($pset[$i]["setid"]) > 17)
						$psetid = substr($pset[$i]["setid"], 0, 17)."...";
					else 
						$psetid = $pset[$i]["setid"];
					
					if (strlen($pset[$i]["table"]) > 17)
						$ptable = substr($pset[$i]["table"], 0, 17)."...";
					else
						$ptable = $pset[$i]["table"];
					
					print "<tr>";
					print "<td class=\"dataview_lt\">".$pset[$i]["host"]."</td>";
					print "<td class=\"dataview_lt\">".$pset[$i]["psrstamp"]."</td>";
					print "<td class=\"dataview_lt\">".$pset[$i]["psrmodstamp"]."</td>";
					print "<td class=\"dataview_lt\" title=\"".$pset[$i]["table"]."\">".$ptable."</td>";
					if ($pset[$i]["respqty"] > 0)
						print "<td class=\"dataview_lt\">".$pset[$i]["rqty"]."/".$pset[$i]["respqty"]."</td>";
					else
						print "<td class=\"dataview_lt\">".$pset[$i]["respqty"]."</td>";
					print "</tr>";
				}
				?>
			</table>
			</td>
			</tr>
		</table>
	</td>
	</tr>
<?php
}
elseif ($f_section == 1)	// Logs
{
?>
	<tr>
	<td colspan="20" valign="top" id="pagedata">
		<table class="contentpanel">
		<?php $myform->frmrender_grid(960, 24) ?>
			<tr class="contentrow_40">
			<td class="contentcell_lt" colspan="24">
				<span class="lblblktext">Replication Log Messages at <?php print gmdate("Y-m-d H:i:s T") ?></span>
			</td>
			</tr>

			<tr>
			<td class="contentcell_lt" colspan="24">
			<table class="dataview_noborder">
				<tr>
				<th width="18%" class="dataview_l">Time (<?php print date("T") ?>)</th>
				<th width="64%" class="dataview_l">Message</th>
				<th width="18%" class="dataview_l">SetID</th>
				</tr>

				<?php
				$np = count($logset);
				for ($i = 0; $i < $np; $i++)
				{
					$logmsg = $logset[$i]["logmsg"];
					if (strlen($logmsg) > 67)
						$logmsg = substr($logmsg, 0, 67)."...";
					$logsetid = $logset[$i]["setid"];
					if (strlen($logsetid) > 17)
						$logsetid = substr($logsetid, 0, 17)."...";
					print "<tr>";
					print "<td class=\"dataview_lt\">".$logset[$i]["logdate"]."</span></td>";
					print "<td class=\"dataview_lt\" title=\"".$logset[$i]["logmsg"]."\">".$logmsg."</td>";
					print "<td class=\"dataview_lt\" title=\"".$logset[$i]["setid"]."\">".$logsetid."</td>";
					print "</tr>";
				}
				?>
			</table>
			</td>
			</tr>
		</table>
	</td>
	</tr>
<?php
}
elseif ($f_section == 2)	// Config
{
?>
	<tr>
	<td colspan="20" valign="top">
		<table class="contentpanel">
		<?php $myform->frmrender_grid(960, 24) ?>
			<tr class="contentrow_40">
			<td class="contentcell_lt" colspan="24">
				<span class="lblblktext">Authentx Replication Configuration - Providers</span>
			</td>
			</tr>

			<tr>
			<td class="contentcell_lt" colspan="24">
				<table class="dataview_noborder">
					<tr>
					<th width="30%" class="dataview_l">Server ID</th>
					<th width="10%" class="dataview_l">Status</th>
					<th width="40%" class="dataview_l">Repl URL</th>
					<th width="20%" class="dataview_l">Repl Date (<?php print date("T") ?>)</th>
					</tr>
					
					<tr>
					<td class="dataview_lt" colspan="4">
					<a href="javascript:popupOpener('pop-providerconfig.php','providerconfig',600,600)">Add...</a>
					</td>
					</tr>

					<?php
					$np = count($providerset);
					for ($i = 0; $i < $np; $i++)
					{
						$urlargs = "?providerid=".urlencode($providerset[$i]["providerid"]);
						$syncdate = "";
						if (($providerset[$i]["repxsyncmts"] != NULL) && ($providerset[$i]["repxsyncmts"] != 0))
							$syncdate = date("m/d/Y H:i:s", $providerset[$i]["repxsyncmts"]);
						print "<tr>";
						// Edit link on the providerID
						if (strlen($providerset[$i]["providerid"]) > 60)
							$pstr = substr($providerset[$i]["providerid"], 0, 60)." ...";
						else
							$pstr = $providerset[$i]["providerid"];
						print "<td class=\"dataview_lt\">"
						. "<a href=\"javascript:popupOpener('pop-providerconfig.php".$urlargs."','providerconfig',600,600)\" title=\"".htmlentities($providerset[$i]["providerid"])."\" >"
						. htmlentities($pstr)
						. "</a>"
						. "</td>";
						print "<td class=\"dataview_lt\">".$providerset[$i]["rstatus"]."</td>";
						print "<td class=\"dataview_lt\" title=\"".htmlentities($providerset[$i]["providerurl"])."\">".htmlentities(substr($providerset[$i]["providerurl"], 0, 55))."...</td>";
						print "<td class=\"dataview_lt\">".$syncdate."</td>";
						print "</tr>";
					}
					?>
				</table>
			</td>
			</tr>
		</table>
	</td>
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