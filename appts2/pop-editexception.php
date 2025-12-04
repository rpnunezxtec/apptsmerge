<?php
// $Id:$

// popup to edit a site availability exception record
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-editexception.php";
// the geometry required for this popup
$windowx = 600;
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

if (!$priv_shours)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// axuuid: availability exception unique identifier
if (isset($_GET["axuuid"]))
{
	$axuuid = $_GET["axuuid"];
	if (strlen($axuuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid exception ID.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else 
{
	print "<script type=\"text/javascript\">alert('Exception ID missing.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// centeruuid: site id.
if (isset($_GET["center"]))
{
	$centeruuid = $_GET["center"];
	// check and sanitise it
	if (strlen($centeruuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid center ID.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
{
	print "<script type=\"text/javascript\">alert('Center ID not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}
	
// AVC is used to check axid.siteid combination validity
if (isset($_GET["avc"]))
	$avc = $_GET["avc"];
else
{
	print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Check the AVC mac for validity
$testavc = $myappt->session_createmac($axuuid.$centeruuid);
	
if (strcasecmp($avc, $testavc) != 0)
{
	print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

$daytext = array ("", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);

	// Process submissions here
	if (isset($_POST["submit_aex_edit"]))
	{
		// Editing an exception for this site
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
			if (!is_numeric($axdow))
				$axdow = 0;
		}
		else
			$axdow = 0;
			
		if (isset($_POST["axstime_hh"]))
		{
			$axstime_hh = $_POST["axstime_hh"];
			if (!is_numeric($axstime_hh))
				$axstime_hh = false;
		}
		else
			$axstime_hh = false;
				
		if (isset($_POST["axstime_mm"]))
		{
			$axstime_mm = $_POST["axstime_mm"];
			if (!is_numeric($axstime_mm))
				$axstime_mm = false;
		}
		else
			$axstime_mm = false;
			
		// build the time string
		if (($axstime_hh == "") || ($axstime_mm == ""))
		{
			$axstime_hh = false;
			$axstime_mm = false;
			$axstime = "";
		}
		elseif (($axstime_hh !== false) && ($axstime_mm !== false))
			$axstime = $axstime_hh.":".$axstime_mm;
		else
			$axstime = false;
				
		if (isset($_POST["axetime_hh"]))
		{
			$axetime_hh = $_POST["axetime_hh"];
			if (!is_numeric($axetime_hh))
				$axetime_hh = false;
		}
		else
			$axetime_hh = false;
				
		if (isset($_POST["axetime_mm"]))
		{
			$axetime_mm = $_POST["axetime_mm"];
			if (!is_numeric($axetime_mm))
				$axetime_mm = false;
		}
		else
			$axetime_mm = false;
			
		// build the time string
		if (($axetime_hh == "") || ($axetime_mm == ""))
		{
			$axetime_hh = false;
			$axetime_mm = false;
			$axetime = "";
		}
		elseif (($axetime_hh !== false) && ($axetime_mm !== false))
			$axetime = $axetime_hh.":".$axetime_mm;
		else
			$axetime = false;	
				
		// Update the database if there is at least one valid value
		$q_axentry = false;
			
		if (($axstime !== false) && ($axetime !== false))
		{
			$q_axentry = "update availexception set "
					. "\n axdate='".$sdbh->real_escape_string($axdate)."', "
					. "\n axday='".$sdbh->real_escape_string($axdow)."', "
					. "\n axstart='".$sdbh->real_escape_string($axstime)."', "
					. "\n axend='".$sdbh->real_escape_string($axetime)."', "
					. "\n xsyncmts='".time()."' "
					. "\n where axuuid='".$sdbh->real_escape_string($axuuid)."' "
					;
		}
		elseif (($axdate != "//") || ($axdow != 0))
		{
			$q_axentry = "update availexception set "
					. "\n axdate='".$sdbh->real_escape_string($axdate)."', "
					. "\n axday='".$sdbh->real_escape_string($axdow)."', "
					. "\n xsyncmts='".time()."' "
					. "\n where axuuid='".$sdbh->real_escape_string($axuuid)."' "
					;
		}
		if ($q_axentry)
			$s_axentry = $sdbh->query($q_axentry);
		else
			$s_axentry = false;

		if ($s_axentry)
		{
			$logstring = "Site ".$sitedetails["sitename"]." (centeruuid: ".$centeruuid.") availability exception updated.";
			$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
			
			// update the calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
	}
	elseif (isset($_POST["submit_aex_delete"]))
	{
		// delete the record and close the popup
		$q_axentry = "delete from availexception "
				. "\n where axuuid='".$sdbh->real_escape_string($axuuid)."' "
				. "\n limit 1 "
				;
		$s_axentry = $sdbh->query($q_axentry);
		
		if ($s_axentry)
		{
			$myappt->adddeletedrow($sdbh, "availexception", "axuuid", $axuuid);
			$logstring = "Site ".$sitedetails["sitename"]." (centeruuid: ".$centeruuid.") availability exception ".$axuuid." deleted.";
			$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
		}
		
		$sdbh->close();
		print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		print "<script type=\"text/javascript\">alert('Exception deleted.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// Get the exception detail
	$axdetail = $myappt->readavailexception($sdbh, $axuuid);
		
	$axdate_yy = false;
	$axdate_mm = false;
	$axdate_dd = false;
	$axday = 0;
	$axst_hh = false;
	$axst_mm = false;
	$axet_hh = false;
	$axet_mm = false;
			
	if (isset($axdetail["axdate"]))
	{
		$axdate = $axdetail["axdate"];
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
		}
	}

	if (isset($axdetail["axday"]))
	{
		$axday = $axdetail["axday"];
		if ($axday == "")
			$axday = 0;
	}
	
	if (isset($axdetail["axstart"]))
	{
		$axstime = $axdetail["axstart"];
		if ($axstime != NULL)
		{
			$axst_hh = substr($axstime, 0, 2);
			$axst_mm = substr($axstime, 3, 2);
		}
	}
		
	if (isset($axdetail["axend"]))
	{
		$axetime = $axdetail["axend"];
		if ($axetime != NULL)
		{
			$axet_hh = substr($axetime, 0, 2);
			$axet_mm = substr($axetime, 3, 2);
		}
	}
	
	if ((isset($axstime)) && (isset($axetime)))
	{
		if (strcmp($axstime, $axetime) == 0)
		{
			$axst_hh = "";
			$axst_mm = "";
			$axet_hh = "";
			$axet_mm = "";
		}
	}
	
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
<title>Edit Site Availability Exception</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr height="40">
<td width="70%" valign="top" align="left"><span class="lblblktext">Availability Exception for site: <?php print $sitedetails["sitename"] ?></span></td>
<td width="30%" valign="top" align="left"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()"></td>
</tr></table><p>
<form name="edit_ex" method="post"  autocomplete="off" action="<?php print $formfile."?axuuid=".urlencode($axuuid)."&center=".urlencode($centeruuid)."&avc=".urlencode($testavc) ?>" >
<span class="lblblktext">Edit availability exception</span><p>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr height="40">
<td align="left" valign="top" width="40%">
<span class="lblblktext">Date (mm-dd-yyyy)</span><br>
<select name="axdate_mm" tabindex="1" style="width: 4em">
<?php
for ($i = 1; $i < 13; $i++)
{
	if ($axdate_mm !== false)
	{
		if ($i == $axdate_mm)
		{
			$v = str_pad($i, 2, "0", STR_PAD_LEFT);
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		}
		else 
		{
			$v = str_pad($i, 2, "0", STR_PAD_LEFT);
			print "<option value=\"".$v."\">".$v."</option>\n";
		}
	}
	else
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
}
if ($axdate_mm === false)
	print "<option selected value=\"\"></option>\n";
else
	print "<option value=\"\"></option>\n";
?>
</select>
&nbsp;-&nbsp;
<select name="axdate_dd" tabindex="2" style="width: 4em">
<?php
for ($i = 1; $i < 32; $i++)
{
	if ($axdate_dd !== false)
	{
		if ($i == $axdate_dd)
		{
			$v = str_pad($i, 2, "0", STR_PAD_LEFT);
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		}
		else 
		{
			$v = str_pad($i, 2, "0", STR_PAD_LEFT);
			print "<option value=\"".$v."\">".$v."</option>\n";
		}
	}
	else
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
}
if ($axdate_dd === false)
	print "<option selected value=\"\"></option>\n";
else
	print "<option value=\"\"></option>\n";
?>
</select>
&nbsp;-&nbsp;
<select name="axdate_yy" tabindex="3" style="width: 6em">
<?php
$y_start = date("Y")-1;
for ($i = 0; $i < 12; $i++)
{
	if ($axdate_yy !== false)
	{
		if (($i + $y_start) == $axdate_yy)
			print "<option selected value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
		else 
			print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
	}
	else
		print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
}
if ($axdate_yy === false)
	print "<option selected value=\"\"></option>\n";
else
	print "<option value=\"\"></option>\n";
?>
</select>
</td>
<td align="left" valign="top" width="30%">
<span class="lblblktext">Day of week</span><br>
<select name="axdow" tabindex="4" style="width: 6em">
<?php
for ($i = 0; $i < 8; $i++)
{
	if ($axday == $i)
		print "<option selected value=\"".$i."\">".htmlentities($daytext[$i])."</option>\n";
	else
		print "<option value=\"".$i."\">".htmlentities($daytext[$i])."</option>\n";
}
?>
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
	if ($axst_hh !== false)
	{
		if ($i == $axst_hh)
		{
			$v = str_pad($i, 2, "0", STR_PAD_LEFT);
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		}
		else 
		{
			$v = str_pad($i, 2, "0", STR_PAD_LEFT);
			print "<option value=\"".$v."\">".$v."</option>\n";
		}
	}
	else
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
}
if ($axst_hh === false)
	print "<option selected value=\"\"></option>\n";
else
	print "<option value=\"\"></option>\n";
?>
</select>
&nbsp;:&nbsp;
<select name="axstime_mm" tabindex="6" style="width: 4em">
<?php
for ($i = 0; $i < 12; $i++)
{
	if ($axst_mm !== false)
	{
		if (($i*5) == $axst_mm)
		{
			$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		}
		else 
		{
			$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
			print "<option value=\"".$v."\">".$v."</option>\n";
		}
	}
	else
	{
		$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
}
if ($axst_mm === false)
	print "<option selected value=\"\"></option>\n";
else
	print "<option value=\"\"></option>\n";
?>
</select>
</td>
<td align="left" valign="top">
<span class="lblblktext">End Time</span><br>
<select name="axetime_hh" tabindex="7" style="width: 4em">
<?php
for ($i = 0; $i < 24; $i++)
{
	if ($axet_hh !== false)
	{
		if ($i == $axet_hh)
		{
			$v = str_pad($i, 2, "0", STR_PAD_LEFT);
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		}
		else 
		{
			$v = str_pad($i, 2, "0", STR_PAD_LEFT);
			print "<option value=\"".$v."\">".$v."</option>\n";
		}
	}
	else
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
}
if ($axet_hh === false)
	print "<option selected value=\"\"></option>\n";
else
	print "<option value=\"\"></option>\n";

?>
</select>
&nbsp;:&nbsp;
<select name="axetime_mm" tabindex="8" style="width: 4em">
<?php
for ($i = 0; $i < 12; $i++)
{
	if ($axet_mm !== false)
	{
		if (($i*5) == $axet_mm)
		{
			$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
			print "<option selected value=\"".$v."\">".$v."</option>\n";
		}
		else 
		{
			$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
			print "<option value=\"".$v."\">".$v."</option>\n";
		}
	}
	else
	{
		$v = str_pad(($i*5), 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
}
if ($axet_mm === false)
	print "<option selected value=\"\"></option>\n";
else
	print "<option value=\"\"></option>\n";
?>
</select>
</td>
<td align="left" valign="top"><span class="lblblktext">&nbsp;</span><br>
<input type="submit" value="Update" class="btntext" name="submit_aex_edit" tabindex="9">
&nbsp;&nbsp;
<input type="submit" value="Delete" class="btntext" name="submit_aex_delete" tabindex="10">
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
</body></html>