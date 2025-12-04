<?php
// $Id:$

// Reports from transaction log
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "rpt-logs.php";
// default rows per page
$rpp_default = 25;

// the geometry required for this popup
$windowx = 850;
$windowy = 900;

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
// sd: startdate (YYYY-MM-DD). If not supplied then all transactions prior to ed will be shown.
// ed: enddate (YYYY-MM-DD). If not supplied then all transactions after sd are shown.
// ltp: logtype (integer). If not supplied then all log types will be included.
// rpp: rows per page (integer). If not supplied then the default rpp will be used.
// pg: page number (integer). If not supplied then page 1 is assumed.
// ri: refresh interval (integer). Auto-refresh of report in seconds.

if (isset($_GET["sd"]))
{
	$sd = $_GET["sd"];
}
else
	$sd = false;
	
if (isset($_GET["ed"]))
{
	$ed = $_GET["ed"];
}
else
	$ed = false;
	
if (isset($_GET["ltp"]))
{
	$lt = $_GET["ltp"];
	if (!is_numeric($lt))
		$lt = false;
}
else
	$lt = false;

if (isset($_GET["rpp"]))
{
	$rpp = $_GET["rpp"];
	if (!is_numeric($rpp))
		$rpp = $rpp_default;
}
else
	$rpp = $rpp_default;

if (isset($_GET["ri"]))
{
	$ri = $_GET["ri"];
	if (!is_numeric($ri))
		$ri = 0;
}
else
	$ri = 0;

if (isset($_GET["pg"]))
{
	$pg = $_GET["pg"];
	if (!is_numeric($pg))
		$pg = 1;
}
else
	$pg = 1;

// now check for posted values from form
if (isset($_POST["submit_param"]))
{
	if (isset($_POST["ltp"]))
		$p_lt = $_POST["ltp"];
	if (is_numeric($p_lt))
		$lt = $p_lt;
	if (strcasecmp($p_lt, "all") == 0)
		$lt = false;

	if (isset($_POST["rpp"]))
		$p_rpp = $_POST["rpp"];
	if (is_numeric($p_rpp))
		$rpp = $p_rpp;

	if (isset($_POST["ri"]))
		$p_ri = $_POST["ri"];
	if (is_numeric($p_ri))
		$ri = $p_ri;
}

$logs = array();
$nlogs = 0;

// Get the log entries for display
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	$qs = array();
	if ($lt !== false)
		$qs[] = " txntype='".$sdbh->real_escape_string($lt)."' ";
	if ($sd !== false)
	{
		$sdt = $sd." 00:00:00";
		$qs[] = " logdate>='".$sdbh->real_escape_string($sdt)."' ";
	}
	if ($ed !== false)
	{
		$edt = $ed." 23:59:59";
		$qs[] = " logdate<='".$sdbh->real_escape_string($edt)."' ";
	}
		
	$q_log = "select * from log ";
	$nq = count($qs);
	for ($i = 0; $i < $nq; $i++)
	{
		if ($i == 0)
			$q_log .= "where ".$qs[$i];
		else 
			$q_log .= "and ".$qs[$i];
	}
	
	$q_log .= "\n order by logdate desc ";
	$q_log .= "\n limit ".$rpp." offset ".($pg * $rpp);
	
	$s_log = $sdbh->query($q_log);

	if ($s_log)
	{
		while ($r_log = $s_log->fetch_assoc())
		{
			$logs[$nlogs]["logdate"] = $r_log["logdate"];
			$logs[$nlogs]["logstring"] = $r_log["logstring"];
			$nlogs++;
		} 
		$s_log->free();
	}

	$sdbh->close();
}
else 
{
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
}

// Create a URL from the submitted parameters
$url = htmlentities($formfile)."?rpp=".urlencode($rpp)."&ri=".urlencode($ri);
if ($lt !== false)
	$url = $url."&ltp=".urlencode($lt);	
if ($sd !== false)
	$url = $url."&sd=".urlencode($sd);
if ($ed !== false)
	$url = $url."&ed=".urlencode($ed);

// calculate the number of pages to show
if ($rpp == 0)
	$np = 0;
else
{
	$np = intval($nlogs/$rpp);
	if (($nlogs % $rpp) > 0)
		$np++;
}

if ($ri > 0)
	header('Refresh: '.$ri."; url=".$url."&pg=".$pg);

?>
<!DOCTYPE html>
<html>
<head>
<META http-equiv="Default-Style" content="text/html;charset=UTF-8">
<title>Log Report</title>
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
<form name="logparams" method="post" action="<?php print $url."&pg=".$pg ?>"  autocomplete="off" >
<tr height="40">
<td colspan="7" valign="top"><span class="lblblktext">Log Type</span><br>
<select name="ltp" style="width: 22em;">
<?php
$listlogtype = $myappt->sortlistarray($listlogtype, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listlogtype);
$ltname = "All";
for ($i = 0; $i < $rc; $i++)
{
	if ($listlogtype[$i][0] == $lt)
	{
		print "<option selected value=\"".$listlogtype[$i][0]."\">".$listlogtype[$i][1]."</option>\n";
		$ltname = $listlogtype[$i][1];
	}
	else
		print "<option value=\"".$listlogtype[$i][0]."\">".$listlogtype[$i][1]."</option>\n";
}
?>
</select>
</td>
<td colspan="4" valign="top"><span class="lblblktext">Rows per page</span><br>
<select name="rpp" style="width: 12em;">
<?php
$rc = count($listrpp);
for ($i = 0; $i < $rc; $i++)
{
	if ($listrpp[$i][0] == $rpp)
		print "<option selected value=\"".$listrpp[$i][0]."\">".$listrpp[$i][1]."</option>\n";
	else
		print "<option value=\"".$listrpp[$i][0]."\">".$listrpp[$i][1]."</option>\n";
}
?>
</select>
</td>
<td colspan="4" valign="top"><span class="lblblktext">Refresh interval</span><br>
<select name="ri" style="width: 12em;">
<?php
$rc = count($listri);
for ($i = 0; $i < $rc; $i++)
{
	if ($listri[$i][0] == $ri)
		print "<option selected value=\"".$listri[$i][0]."\">".$listri[$i][1]."</option>\n";
	else
		print "<option value=\"".$listri[$i][0]."\">".$listri[$i][1]."</option>\n";
}
?>
</select>
</td>
<td colspan="5" valign="top" align="right"><span class="lblblktext">&nbsp;</span><br>
<input type="submit" name="submit_param" class="btntext" value="Refresh">
</td></tr>
</form>
<tr height="40"><td colspan="20" valign="top"><hr/></td></tr>
</table>
<table width="800" cellspacing="0" cellpadding="3" border="0">
<tr>
<td width="500" valign="top"><span class="nameheading">Log Report: <?php print htmlentities($ltname) ?></span></td>
<td width="300" valign="top">
<span class="proplabel">Run Time: </span><span class="proptext"><?php print date("D M jS H:i:s") ?></span><br/>
<span class="proplabel">Run By: </span><span class="proptext"><?php print htmlentities($myappt->session_getuuname()) ?></span><br/>
<span class="proplabel">Start Date: </span><span class="proptext"><?php print ($sd === false ? "Any" : $sd) ?></span><br/>
<span class="proplabel">End Date: </span><span class="proptext"><?php print ($ed === false ? "Any" : $ed) ?></span>
</td></tr></table>
<p/>
<?php
if ($rpp > 0)
{
	// print page numbers for records
	print "<span class=\"pageon\">Page: </span>\n";
	for ($i = 0; $i < $np; $i++)
	{
		if ($pg == $i+1)
			print "&nbsp;<span class=\"pageon\">".($i+1)."</span>&nbsp;\n";
		else
			print "<a href=\"".$url."&pg=".($i+1)."\">&nbsp;<span class=\"pageoff\">".($i+1)."</span>&nbsp;</a>\n";
	}
}
?>
<p/>
<table width="800" cellspacing="0" cellpadding="3" border="0">
<tr>
<td width="200" class="matrixheading"><span class="tableheading">Date</span></td>
<td width="600" class="matrixheading"><span class="tableheading">Log Entry</span></td>
</tr>
<?php
for ($i = 0; $i < $nlogs; $i++)
{
?>
<tr>
<td class="matrixline"><span class="tabletext"><?php print $logs[$i]["logdate"] ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print htmlentities($logs[$i]["logstring"]) ?></span></td>
</tr>
<?php
}
?>
</table>
</body></html>