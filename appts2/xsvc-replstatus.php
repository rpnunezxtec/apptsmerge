<?PHP

// $Id:$

// xsvc-replstatus.xas
// AJAX posting from repl status form
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

$resultstring = "<table class=\"contentpanel\">";
$resultstring .= $myform->frmrender_grid(960, 24, true);
$resultstring .= "<tr class=\"contentrow_40\">";
$resultstring .= "<td class=\"contentcell_lt\" colspan=\"24\">";
$resultstring .= "<span class=\"lblblktext\">Consumer (Incoming) Replication Status at ".gmdate("Y-m-d H:i:s T")."</span>";
$resultstring .= "</td>";
$resultstring .= "</tr>";
		
$resultstring .= "<tr>";
$resultstring .= "<td class=\"contentcell_lt\" colspan=\"24\">";
$resultstring .= "<table class=\"dataview_noborder\">";
$resultstring .= "<tr>";
$resultstring .= "<th width=\"30%\" class=\"dataview_l\">Request URL</th>";
$resultstring .= "<th width=\"20%\" class=\"dataview_l\">Date (Z)</th>";
$resultstring .= "<th width=\"20%\" class=\"dataview_l\">Mod Date (Z)</th>";
$resultstring .= "<th width=\"20%\" class=\"dataview_l\">Table</th>";
$resultstring .= "<th width=\"10%\" class=\"dataview_l\">Updates</th>";
$resultstring .= "</tr>";
			
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
						
	$resultstring .= "<tr>";
	$resultstring .= "<td class=\"dataview_lt\" title=\"".$rset[$i]["url"]."\">".$url."</td>";
	$resultstring .= "<td class=\"dataview_lt\">".$rset[$i]["csrstamp"]."</td>";
	$resultstring .= "<td class=\"dataview_lt\">".$rset[$i]["csrmodstamp"]."</td>";
	$resultstring .= "<td class=\"dataview_lt\" title=\"".$rset[$i]["table"]."\">".$rtable."</td>";
	if ($rset[$i]["respqty"] > 0)
		$resultstring .= "<td class=\"dataview_lt\">".$rset[$i]["rqty"]."/".$rset[$i]["respqty"]."</td>";
	else
		$resultstring .= "<td class=\"dataview_lt\">".$rset[$i]["respqty"]."</td>";
	$resultstring .= "</tr>";
}

$resultstring .= "</table>";
$resultstring .= "<br/>";
$resultstring .= "<hr/>";
$resultstring .= "</td>";
$resultstring .= "</tr>";

$resultstring .= "<tr class=\"contentrow_40\">";
$resultstring .= "<td class=\"contentcell_lt\" colspan=\"24\">";
$resultstring .= "<span class=\"lblblktext\">Provider (Outgoing) Replication Status at ".gmdate("Y-m-d H:i:s T")."</span>";
$resultstring .= "</td>";
$resultstring .= "</tr>";
		
$resultstring .= "<tr>";
$resultstring .= "<td class=\"contentcell_lt\" colspan=\"24\">";
$resultstring .= "<table class=\"dataview_noborder\">";
$resultstring .= "<tr>";
$resultstring .= "<th width=\"25%\" class=\"dataview_l\">Consumer</th>";
$resultstring .= "<th width=\"20%\" class=\"dataview_l\">Date (Z)</th>";
$resultstring .= "<th width=\"20%\" class=\"dataview_l\">Mod Date (Z)</th>";
$resultstring .= "<th width=\"25%\" class=\"dataview_l\">Table</th>";
$resultstring .= "<th width=\"10%\" class=\"dataview_l\">Updates</th>";
$resultstring .= "</tr>";

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
					
	$resultstring .= "<tr>";
	$resultstring .= "<tr>";
	$resultstring .= "<td class=\"dataview_lt\">".$pset[$i]["host"]."</td>";
	$resultstring .= "<td class=\"dataview_lt\">".$pset[$i]["psrstamp"]."</td>";
	$resultstring .= "<td class=\"dataview_lt\">".$pset[$i]["psrmodstamp"]."</td>";
	$resultstring .= "<td class=\"dataview_lt\" title=\"".$pset[$i]["table"]."\">".$ptable."</td>";
	if ($pset[$i]["respqty"] > 0)
		$resultstring .= "<td class=\"dataview_lt\">".$pset[$i]["rqty"]."/".$pset[$i]["respqty"]."</td>";
	else
		$resultstring .= "<td class=\"dataview_lt\">".$pset[$i]["respqty"]."</td>";
	$resultstring .= "</tr>";
}

$resultstring .= "</table>";
$resultstring .= "</td>";
$resultstring .= "</tr>";
$resultstring .= "</table>";

header('Content-type: text/html; charset=utf-8');
print $resultstring;
	
?>