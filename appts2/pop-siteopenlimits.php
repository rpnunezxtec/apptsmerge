<?php
// $Id:$

// popup to view the site opening date limitation
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-siteopenlimits.php";
// the geometry required for this popup
$windowx = 700;
$windowy = 700;

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

// Validate access to this form - requires Site tab permissions
if ($myappt->checktabmask(TAB_S) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Get admin privileges: site hours privileges.
$priv_shours = $myappt->checkprivilege(PRIV_SHOURS);

// center: site centeruuid.
if (isset($_GET["center"]))
{
	$centeruuid = $_GET["center"];
	// check and sanitise it
	if (strlen($centeruuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid center uuid.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	
	// AVC is only present when the SID is submitted, to verify it
	if (isset($_GET["avc"]))
		$avc = $_GET["avc"];
	else
	{
		print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// Check the AVC mac for validity
	$testavc = $myappt->session_createmac($centeruuid);
	
	if (strcasecmp($avc, $testavc) != 0)
	{
		print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
{
	print "<script type=\"text/javascript\">alert('Center UUID not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Process submissions here
	if (isset($_POST["submit_sol"]))
	{
		// Need shours privilege to add this
		if ($priv_shours)
		{
			// Adding an opening date limitation to this site
			// posted elements are: soldatestart_dd, soldatestart_mm, soldatestart_yy, 
			// soldateend_dd, soldateend_mm, soldateend_yy, 
		
			$solstartdateset = array();
			if (isset($_POST["soldatestart_dd"]))
				$solstartdateset[2] = $_POST["soldatestart_dd"];
			else
				$solstartdateset[2] = NULL;
				
			if (isset($_POST["soldatestart_mm"]))
				$solstartdateset[1] = $_POST["soldatestart_mm"];
			else
				$solstartdateset[1] = NULL;	
		
			if (isset($_POST["soldatestart_yy"]))
				$solstartdateset[0] = $_POST["soldatestart_yy"];
			else
				$solstartdateset[0] = NULL;
				
			// build a '/' delimited string	for the start date
			if (($solstartdateset[0] != NULL) && ($solstartdateset[1] != NULL) && ($solstartdateset[2] != NULL))
				$solstartdate = $solstartdateset[0]."/".$solstartdateset[1]."/".$solstartdateset[2];
			else
				$solstartdate = NULL;
			
			$solenddateset = array();
			if (isset($_POST["soldateend_dd"]))
				$solenddateset[2] = $_POST["soldateend_dd"];
			else
				$solenddateset[2] = NULL;
			
			if (isset($_POST["soldateend_mm"]))
				$solenddateset[1] = $_POST["soldateend_mm"];
			else
				$solenddateset[1] = NULL;
			
			if (isset($_POST["soldateend_yy"]))
				$solenddateset[0] = $_POST["soldateend_yy"];
			else
				$solenddateset[0] = NULL;
			
			// build a '/' delimited string	for the start date
			if (($solenddateset[0] != NULL) && ($solenddateset[1] != NULL) && ($solenddateset[2] != NULL))
				$solenddate = $solenddateset[0]."/".$solenddateset[1]."/".$solenddateset[2];
			else
				$solenddate = NULL;
			
			// Save the new value to the database if there is at least one valid value
			$q_solentry = false;
			
			if (($solstartdate != NULL) || ($solenddate != NULL))
			{
				$slouuid = $myappt->makeuniqueuuid($sdbh, "sitelimitopen", "slouuid");

				$q_solentry = "insert into sitelimitopen set "
						. "\n centeruuid='".$sdbh->real_escape_string($centeruuid)."', "
						. "\n slostartdate='".$sdbh->real_escape_string($solstartdate)."', "
						. "\n sloenddate='".$sdbh->real_escape_string($solenddate)."', "
						. "\n slouuid='".$slouuid."', "
						. "\n xsyncmts='".time()."' "
						;
			}
			if ($q_solentry)
				$s_solentry = $sdbh->query($q_solentry);
			else
				$s_solentry = false;

			$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
			$osname = $sitedetails["sitename"];
			if ($s_solentry)
			{
				$logstring = "Site ".$osname." (centeruuid: ".$centeruuid.") site opening date limit added.";
				$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
			}
		}
	}

	// Get a list of exceptions for this site
	$slodetails = $myappt->readsitelimitopens($sdbh, $centeruuid);
	$nslo = count($slodetails);
	
	// Get site details
	$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
}
else 
{
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<title>Site Opening Date Limits</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr height="40">
<td width="70%" valign="top" align="left"><span class="lblblktext">Site Opening Date Limits for site: <?php print $sitedetails["sitename"] ?></span></td>
<td width="30%" valign="top" align="left"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()"></td>
</tr></table><p>
<table width="100%" border="1" cellpadding="1" cellspacing="0">
<tr height="20">
<td width="5%" class="matrixheading"><span class="tableheading">No</span></td>
<td width="45%" class="matrixheading"><span class="tableheading">Start Date (mm/dd/yyyy)</span></td>
<td width="50%" class="matrixheading"><span class="tableheading">End Date (mm/dd/yyyy)</span></td>
</tr>
<?php
for ($i = 0; $i < $nslo; $i++)
{
	// get the data for the row
	if (isset($slodetails[$i]["slostartdate"]))
	{
		$slostartdate = $slodetails[$i]["slostartdate"];
		if ($slostartdate != NULL)
		{
			$slostartdateset = explode("/", $slostartdate);
			if ($slostartdateset[0] == "")
				$slostartdate_yy = false;
			else
				$slostartdate_yy = $slostartdateset[0];
				
			if ($slostartdateset[1] == "")
				$slostartdate_mm = false;
			else
				$slostartdate_mm = $slostartdateset[1];
				
			if ($slostartdateset[2] == "")
				$slostartdate_dd = false;
			else
				$slostartdate_dd = $slostartdateset[2];
			
			if ($slostartdate_yy === false && $slostartdate_mm === false && $slostartdate_dd === false)
				$slosd = "-";
			else
				$slosd = (($slostartdate_mm === false) ? "-/" : $slostartdate_mm."/").(($slostartdate_dd === false) ? "-/" : $slostartdate_dd."/").(($slostartdate_yy === false) ? "-" : $slostartdate_yy);
		}
		else
			$slosd = "-";
	}
	else
		$slosd = "-";

	if (isset($slodetails[$i]["sloenddate"]))
	{
		$sloenddate = $slodetails[$i]["sloenddate"];
		if ($sloenddate != NULL)
		{
			$sloenddateset = explode("/", $sloenddate);
			if ($sloenddateset[0] == "")
				$sloenddate_yy = false;
			else
				$sloenddate_yy = $sloenddateset[0];
	
			if ($sloenddateset[1] == "")
				$sloenddate_mm = false;
			else
				$sloenddate_mm = $sloenddateset[1];
	
			if ($sloenddateset[2] == "")
				$sloenddate_dd = false;
			else
				$sloenddate_dd = $sloenddateset[2];
				
			if ($sloenddate_yy === false && $sloenddate_mm === false && $sloenddate_dd === false)
				$sloed = "-";
			else
				$sloed = (($sloenddate_mm === false) ? "-/" : $sloenddate_mm."/").(($sloenddate_dd === false) ? "-/" : $sloenddate_dd."/").(($sloenddate_yy === false) ? "-" : $sloenddate_yy);
		}
		else
			$sloed = "-";
	}
	else
		$sloed = "-";
	
	
	$slouuid = $slodetails[$i]["slouuid"];
	$avc = $myappt->session_createmac($slouuid.$centeruuid);
	
	// output each row for this page
?>
<tr>
<td class="matrixline"><span class="tabletext">
<?php
	if ($priv_shours)
	{
?>
<a href="javascript:popupOpener('pop-editsiteopenlimit.php?slouuid=<?php print urlencode($slouuid) ?>&center=<?php print urlencode($centeruuid)."&avc=".urlencode($avc) ?>','editaex',350,500)" title="Edit/delete opening date limit entry"><?php print htmlentities($i) ?></a>
<?php
	}
	else 
	{
		print htmlentities($i);
	}
?>
</span></td>
<td class="matrixline"><span class="tabletext"><?php print $slosd ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print $sloed ?></span></td>
</tr>
<?php
}
?>
</table>
<?php
if ($priv_shours)
{
	$avc = $myappt->session_createmac($centeruuid);
?>
<p/>
<hr/>
<p/>
<form name="add_slo" method="post" action="<?php print $formfile."?center=".$centeruuid."&avc=".$avc ?>"  autocomplete="off" >
<span class="lblblktext">Add site opening date range</span><br/>
<span class="blktext">(Leave blank for open-ended start or end date)</span>
<p/>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr height="40">
<td align="left" valign="top" width="35%">
<span class="lblblktext">Start Date (mm-dd-yyyy)</span><br/>
<select name="soldatestart_mm" tabindex="10" style="width: 4em">
<?php
	for ($i = 1; $i < 13; $i++)
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
	print "<option selected value=\"\"></option>\n";
?>
</select>
&nbsp;-&nbsp;
<select name="soldatestart_dd" tabindex="20" style="width: 4em">
<?php
	for ($i = 1; $i < 32; $i++)
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
	print "<option selected value=\"\"></option>\n";
?>
</select>
&nbsp;-&nbsp;
<select name="soldatestart_yy" tabindex="30" style="width: 6em">
<?php
	$y_start = date("Y");
	for ($i = 0; $i < 11; $i++)
		print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
	print "<option selected value=\"\"></option>\n";
?>
</select>
</td>

<td align="left" valign="top" width="35%">
<span class="lblblktext">End Date (mm-dd-yyyy)</span><br/>
<select name="soldateend_mm" tabindex="40" style="width: 4em">
<?php
	for ($i = 1; $i < 13; $i++)
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
	print "<option selected value=\"\"></option>\n";
?>
</select>
&nbsp;-&nbsp;
<select name="soldateend_dd" tabindex="50" style="width: 4em">
<?php
	for ($i = 1; $i < 32; $i++)
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
	print "<option selected value=\"\"></option>\n";
?>
</select>
&nbsp;-&nbsp;
<select name="soldateend_yy" tabindex="60" style="width: 6em">
<?php
	$y_start = date("Y");
	for ($i = 0; $i < 11; $i++)
		print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
	print "<option selected value=\"\"></option>\n";
?>
</select>
</td>

<td align="left" valign="top"><span class="lblblktext">&nbsp;</span><br/>
<input type="submit" value="Add" class="btntext" name="submit_sol" tabindex="70">
</td>
</tr>
</table>
</form>
<?php
}
?>
</body></html>