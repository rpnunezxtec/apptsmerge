<?php
// $Id:$

// popup to view the site availability exception
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-siteexception.php";
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

// center: centeruuid.
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
	
	// AVC is only present when the centeruuid is submitted, to verify it
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
	if (isset($_POST["submit_aex"]))
	{
		// Need shours privilege to add this
		if ($priv_shours)
		{
			// Adding an exception to this site
			// posted elements are: axdate_dd, axdate_mm, axdate_yy, 
			// ax_dow, axstime_hh, axstime_mm, axetime_hh, axetime_mm
		
			$axdateset = array();
			if (isset($_POST["axdate_dd"]))
				$axdateset[2] = $_POST["axdate_dd"];
			else
				$axdateset[2] = NULL;
				
			if (isset($_POST["axdate_mm"]))
				$axdateset[1] = $_POST["axdate_mm"];
			else
				$axdateset[1] = NULL;	
		
			if (isset($_POST["axdate_yy"]))
				$axdateset[0] = $_POST["axdate_yy"];
			else
				$axdateset[0] = NULL;
				
			// build a '/' delimited string	for the date
			if (($axdateset[0] != NULL) && ($axdateset[1] != NULL) && ($axdateset[2] != NULL))
				$axdate = $axdateset[0]."/".$axdateset[1]."/".$axdateset[2];
			else
				$axdate = NULL;
			
			if (isset($_POST["axdow"]))
			{
				$axdow = $_POST["axdow"];
				if (is_nan($axdow))
					$axdow = 0;
			}
			else
				$axdow = 0;
			
			if (isset($_POST["axstime_hh"]))
			{
				$axstime_hh = $_POST["axstime_hh"];
				if (is_nan($axstime_hh) || ($axstime_hh == ""))
					$axstime_hh = false;
			}
			else
				$axstime_hh = false;
				
			if (isset($_POST["axstime_mm"]))
			{
				$axstime_mm = $_POST["axstime_mm"];
				if (is_nan($axstime_mm) || ($axstime_mm == ""))
					$axstime_mm = false;
			}
			else
				$axstime_mm = false;
			
			// build the time string
			if (($axstime_hh !== false) && ($axstime_mm !== false))
				$axstime = $axstime_hh.":".$axstime_mm;
			else
				$axstime = false;
				
			if (isset($_POST["axetime_hh"]))
			{
				$axetime_hh = $_POST["axetime_hh"];
				if (is_nan($axetime_hh) || ($axetime_hh == ""))
					$axetime_hh = false;
			}
			else
				$axetime_hh = false;
				
			if (isset($_POST["axetime_mm"]))
			{
				$axetime_mm = $_POST["axetime_mm"];
				if (is_nan($axetime_mm) || ($axetime_mm == ""))
					$axetime_mm = false;
			}
			else
				$axetime_mm = false;
			
			// build the time string
			if (($axetime_hh !== false) && ($axetime_mm !== false))
				$axetime = $axetime_hh.":".$axetime_mm;
			else
				$axetime = false;	
				
			// Save the new value to the database if there is at least one valid value
			$q_axentry = false;
			
			if (($axstime !== false) && ($axetime !== false))
			{
				$axuuid = $myappt->makeuniqueuuid($sdbh, "availexception", "axuuid");

				$q_axentry = "insert into availexception set "
						. "\n centeruuid='".$sdbh->real_escape_string($centeruuid)."', "
						. "\n axuuid='".$sdbh->real_escape_string($axuuid)."', "
						. "\n axdate='".$sdbh->real_escape_string($axdate)."', "
						. "\n axday='".$sdbh->real_escape_string($axdow)."', "
						. "\n axstart='".$sdbh->real_escape_string($axstime)."', "
						. "\n axend='".$sdbh->real_escape_string($axetime)."', "
						. "\n xsyncmts='".time()."' "
						;
			}
			elseif (($axdate != NULL) || ($axdow != 0))
			{
				$q_axentry = "insert into availexception set "
						. "\n centeruuid='".$sdbh->real_escape_string($centeruuid)."', "
						. "\n axuuid='".$sdbh->real_escape_string($axuuid)."', "
						. "\n axdate='".$sdbh->real_escape_string($axdate)."', "
						. "\n axday='".$sdbh->real_escape_string($axdow)."', "
						. "\n xsyncmts='".time()."' "
						;
			}
			if ($q_axentry)
				$s_axentry = $sdbh->query($q_axentry);
			else
				$s_axentry = false;
				
			$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
			$osname = $sitedetails["sitename"];
			if ($s_axentry)
			{
				$logstring = "Site ".$osname." (centeruuid: ".$centeruuid.") availability exception added.";
				$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
			}
		}
	}

	// Get a list of exceptions for this site
	$axdetails = $myappt->readavailexceptions($sdbh, $centeruuid);
	$nax = count($axdetails);
	
	// Get site details
	$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);

	$sdbh->close();
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
<title>Site Availability Exceptions</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr height="40">
<td width="70%" valign="top" align="left"><span class="lblblktext">Availability Exceptions for site: <?php print $sitedetails["sitename"] ?></span></td>
<td width="30%" valign="top" align="left"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()"></td>
</tr></table><p>
<table width="100%" border="1" cellpadding="1" cellspacing="0">
<tr height="20">
<td width="5%" class="matrixheading"><span class="tableheading">No</span></td>
<td width="25%" class="matrixheading"><span class="tableheading">Date (mm/dd/yyyy)</span></td>
<td width="20%" class="matrixheading"><span class="tableheading">Day of week</span></td>
<td width="25%" class="matrixheading"><span class="tableheading">Start Time</span></td>
<td width="25%" class="matrixheading"><span class="tableheading">End Time</span></td>
</tr>
<?php
for ($i = 0; $i < $nax; $i++)
{
	// get the data for the row
	if (isset($axdetails[$i]["axdate"]))
	{
		$axdate = $axdetails[$i]["axdate"];
		if ($axdate != NULL)
		{
			$axdateset = explode("/", $axdate);
			if ($axdateset[0] == "")
				$axdate_yy = false;
			else
				$axdate_yy = $axdateset[0];
				
			if ($axdateset[1] == "")
				$axdate_mm = false;
			else
				$axdate_mm = $axdateset[1];
				
			if ($axdateset[2] == "")
				$axdate_dd = false;
			else
				$axdate_dd = $axdateset[2];
			
			if ($axdate_yy === false && $axdate_mm === false && $axdate_dd === false)
				$axd = "-";
			else
				$axd = (($axdate_mm === false) ? "-/" : $axdate_mm."/").(($axdate_dd === false) ? "-/" : $axdate_dd."/").(($axdate_yy === false) ? "-" : $axdate_yy);
		}
		else
			$axd = "-";
	}
	else
		$axd = "-";

	if (isset($axdetails[$i]["axday"]))
	{
		$axday = $axdetails[$i]["axday"];
		if ($axday != NULL)
		{
			switch ($axday)
			{
				case 1:
						$axdow = "Sun";
						break;
				case 2:
						$axdow = "Mon";
						break;
				case 3:
						$axdow = "Tue";
						break;
				case 4:
						$axdow = "Wed";
						break;
				case 5:
						$axdow = "Thu";
						break;
				case 6:
						$axdow = "Fri";
						break;
				case 7:
						$axdow = "Sat";
						break;
				default:
						$axdow = "-";
						break;
			}
		}
		else
			$axdow = "-";
	}
	else
		$axdow = "-";
		
	if (isset($axdetails[$i]["axstart"]))
	{
		$axstime = $axdetails[$i]["axstart"];
		if ($axstime != NULL)
			$axst = substr($axstime, 0, 5);
		else
			$axst = "-";
	}
	else
		$axst = "-";
		
	if (isset($axdetails[$i]["axend"]))
	{
		$axetime = $axdetails[$i]["axend"];
		if ($axetime != NULL)
			$axet = substr($axetime, 0, 5);
		else
			$axet = "-";
	}
	else
		$axet = "-";
	
	if (strcmp($axst, $axet) == 0)
	{
		$axst = "-";
		$axet = "-";
	}
	
	$axuuid = $axdetails[$i]["axuuid"];
	$avc = $myappt->session_createmac($axuuid.$centeruuid);
	
	// output each row for this page
?>
<tr>
<td class="matrixline"><span class="tabletext">
<?php
	if ($priv_shours)
	{
?>
<a href="javascript:popupOpener('pop-editexception.php?axuuid=<?php print urlencode($axuuid) ?>&center=<?php print urlencode($centeruuid)."&avc=".urlencode($avc) ?>','editaex',350,500)" title="Edit/delete exception entry"><?php print htmlentities($i) ?></a>
<?php
	}
	else 
	{
		print htmlentities($i);
	}
?>
</span></td>
<td class="matrixline"><span class="tabletext"><?php print $axd ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print $axdow ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print $axst ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print $axet ?></span></td>
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
<p>
<hr>
<p>
<form name="add_ex" method="post" action="<?php print $formfile."?center=".$centeruuid."&avc=".$avc ?>"  autocomplete="off" >
<span class="lblblktext">Add an availability exception</span><p>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr height="40">
<td align="left" valign="top" width="35%">
<span class="lblblktext">Date (mm-dd-yyyy)</span><br>
<select name="axdate_mm" tabindex="1" style="width: 4em">
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
<select name="axdate_dd" tabindex="2" style="width: 4em">
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
<select name="axdate_yy" tabindex="3" style="width: 6em">
<?php
	$y_start = date("Y");
	for ($i = 0; $i < 11; $i++)
		print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
	print "<option selected value=\"\"></option>\n";
?>
</select>
</td>
<td align="left" valign="top" width="35%">
<span class="lblblktext">Day of week</span><br>
<select name="axdow" tabindex="4" style="width: 6em">
<option selected value="0"></option>
<option value="1">Sun</option>
<option value="2">Mon</option>
<option value="3">Tue</option>
<option value="4">Wed</option>
<option value="5">Thu</option>
<option value="6">Fri</option>
<option value="7">Sat</option>
</select>
</td>
<td align="left" valign="top" width="30%">&nbsp;</td>
</tr>
<tr height="40">
<td align="left" valign="top">
<span class="lblblktext">Start Time</span><br>
<select name="axstime_hh" tabindex="5" style="width: 4em">
<?php
	for ($i = 0; $i < 24; $i++)
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
	print "<option selected value=\"\"></option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="axstime_mm" tabindex="6" style="width: 4em">
<?php
	for ($i = 0; $i < 12; $i++)
	{
		$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
	print "<option selected value=\"\"></option>\n";
?>
</select>
</td>
<td align="left" valign="top">
<span class="lblblktext">End Time</span><br>
<select name="axetime_hh" tabindex="7" style="width: 4em">
<?php
	for ($i = 0; $i < 24; $i++)
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
	print "<option selected value=\"\"></option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="axetime_mm" tabindex="8" style="width: 4em">
<?php
	for ($i = 0; $i < 12; $i++)
	{
		$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
	print "<option selected value=\"\"></option>\n";
?>
</select>
</td>
<td align="left" valign="top"><span class="lblblktext">&nbsp;</span><br>
<input type="submit" value="Add" class="btntext" name="submit_aex" tabindex="9">
</td>
</tr>

<tr height="20"><td align="left" valign="top" colspan="3"></td></tr>

<tr>
<td align="left" valign="top" colspan="3"><span class="smlgryitext">
<strong>Note:</strong>
Day-of-Week setting will render this day as an exception for <strong>ALL</strong> dates, regardless of the date setting. Leave blank unless you need to
disable a particular day.<br/>
Date setting is used to apply the exception to a single particular date only.<br/>
</span></td>
</tr>
</table>
</form>
<?php
}
?>
</body></html>