<?php

// $Id:$

// xsvc-replerrors.xas
// AJAX posting from repl log form
// GET: no parameters passed

// Returns simple HTML for the middle data table.

include("config.php");
require_once("../replication/config-repl.php");
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

// Validate access to this form - requires User tab permissions
if ($myappt->checktabmask(TAB_REPLDASH) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

if (AJAX_REPLSTATUS_ENABLE !== true)
	die();

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

$resultstring = "<table class=\"contentpanel\">";
$resultstring .= $myform->frmrender_grid(960, 24, true);
$resultstring .= "<tr class=\"contentrow_40\">";
$resultstring .= "<td class=\"contentcell_lt\" colspan=\"24\">";
$resultstring .= "<span class=\"lblblktext\">Replication Log Messages at ".gmdate("Y-m-d H:i:s T")."</span>";
$resultstring .= "</td>";
$resultstring .= "</tr>";

$resultstring .= "<tr>";
$resultstring .= "<td class=\"contentcell_lt\" colspan=\"24\">";
$resultstring .= "<table class=\"dataview_noborder\">";
$resultstring .= "<tr>";
$resultstring .= "<th width=\"18%\" class=\"dataview_l\">Time (".date("T").")</th>";
$resultstring .= "<th width=\"64%\" class=\"dataview_l\">Message</th>";
$resultstring .= "<th width=\"18%\" class=\"dataview_l\">SetID</th>";
$resultstring .= "</tr>";

$np = count($logset);
for ($i = 0; $i < $np; $i++)
{
	$logmsg = $logset[$i]["logmsg"];
	if (strlen($logmsg) > 67)
		$logmsg = substr($logmsg, 0, 67)."...";
	$logsetid = $logset[$i]["setid"];
	if (strlen($logsetid) > 17)
		$logsetid = substr($logsetid, 0, 17)."...";
	$resultstring .= "<tr>";
	$resultstring .= "<td class=\"dataview_lt\">".$logset[$i]["logdate"]."</span></td>";
	$resultstring .= "<td class=\"dataview_lt\" title=\"".$logset[$i]["logmsg"]."\">".$logmsg."</td>";
	$resultstring .= "<td class=\"dataview_lt\" title=\"".$logset[$i]["setid"]."\">".$logsetid."</td>";
	$resultstring .= "</tr>";
}

$resultstring .= "</table>";
$resultstring .= "</td>";
$resultstring .= "</tr>";
$resultstring .= "</table>";

header('Content-type: text/html; charset=utf-8');
print $resultstring;
	
?>