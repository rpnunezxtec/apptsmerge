<?php
// $Id:$

// popup to edit a site opening date range record
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-editsiteopenlimit.php";
// the geometry required for this popup
$windowx = 700;
$windowy = 400;

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

// slouuid: availability exception unique identifier
if (isset($_GET["slouuid"]))
{
	$slouuid = $_GET["slouuid"];
	if (strlen($slouuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid site open limit ID.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else 
{
	print "<script type=\"text/javascript\">alert('Site open limit ID missing.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// center: site uuid.
if (isset($_GET["center"]))
{
	$centeruuid = $_GET["center"];
	// check and sanitise it
	if (strlen($centeruuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid siteid.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
{
	print "<script type=\"text/javascript\">alert('Siteid not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}
	
// AVC is used to check slouuid.centeruuid combination validity
if (isset($_GET["avc"]))
	$avc = $_GET["avc"];
else
{
	print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Check the AVC mac for validity
$testavc = $myappt->session_createmac($slouuid.$centeruuid);
	
if (strcasecmp($avc, $testavc) != 0)
{
	print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Process submissions here
	if (isset($_POST["submit_slo_edit"]))
	{
		// Editing a limit pair for this site
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
				
		// Update the database if there is at least one valid value
		$q_solentry = false;
			
		if (($solstartdate != NULL) || ($solenddate != NULL))
		{
			$q_solentry = "update sitelimitopen set "
					. "\n slostartdate='".$sdbh->real_escape_string($solstartdate)."', "
					. "\n sloenddate='".$sdbh->real_escape_string($solenddate)."', "
					. "\n xsyncmts='".time()."' "
					. "\n where slouuid='".$sdbh->real_escape_string($slouuid)."' "
					;
		}
		if ($q_solentry)
			$s_solentry = $sdbh->query($q_solentry);
		else
			$s_solentry = false;
		
		$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
		
		if ($s_solentry)
		{
			$logstring = "Site ".$sitedetails["sitename"]." (centeruuid: ".$centeruuid.") site opening date limit updated.";
			$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
			
			// update the calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
	}
	elseif (isset($_POST["submit_slo_delete"]))
	{
		// delete the record and close the popup
		$q_solentry = "delete from sitelimitopen "
				. "\n where slouuid='".$sdbh->real_escape_string($slouuid)."' "
				. "\n limit 1 "
				;
		$s_solentry = $sdbh->query($q_solentry);
		
		$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
		
		if ($s_solentry)
		{
			$myappt->adddeletedrow($sdbh, "sitelimitopen", "slouuid", $slouuid);
			$logstring = "Site ".$sitedetails["sitename"]." (centeruuid: ".$centeruuid.") site opening date limit deleted.";
			$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
		}
		
		print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		print "<script type=\"text/javascript\">alert('Dopen date limit deleted.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// Get the detail
	$slodetails = $myappt->readsitelimitopen($sdbh, $slouuid);
	
	if (isset($slodetails["slostartdate"]))
	{
		$slostartdate = $slodetails["slostartdate"];
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
		}
	}
	
	if (isset($slodetails["sloenddate"]))
	{
		$sloenddate = $slodetails["sloenddate"];
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
		}
	}
	
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
<title>Edit Site Opening Limits</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr height="40">
<td width="70%" valign="top" align="left"><span class="lblblktext">Opening Limit Date for site: <?php print $sitedetails["sitename"] ?></span></td>
<td width="30%" valign="top" align="left"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()"></td>
</tr></table><p>
<form name="edit_ex" method="post"  autocomplete="off" action="<?php print $formfile."?slouuid=".urlencode($slouuid)."&center=".urlencode($centeruuid)."&avc=".urlencode($testavc) ?>" >
<span class="lblblktext">Edit site open limit dates</span><p>
<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr height="40">
<td align="left" valign="top" width="35%">
<span class="lblblktext">Start Date (mm-dd-yyyy)</span><br>
<select name="soldatestart_mm" tabindex="10" style="width: 4em">
<?php
for ($i = 1; $i < 13; $i++)
{
	if (isset($slodetails["slostartdate"]))
	{
		if ($slostartdate_mm !== false)
		{
			if ($i == $slostartdate_mm)
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
	else
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
}
if (isset($slodetails["slostartdate"]))
{
	if ($slostartdate_mm === false)
		print "<option selected value=\"\"></option>\n";
	else
		print "<option value=\"\"></option>\n";
}
else
	print "<option value=\"\"></option>\n";
?>
</select>
&nbsp;-&nbsp;
<select name="soldatestart_dd" tabindex="20" style="width: 4em">
<?php
for ($i = 1; $i < 32; $i++)
{
	if (isset($slodetails["slostartdate"]))
	{
		if ($slostartdate_dd !== false)
		{
			if ($i == $slostartdate_dd)
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
	else
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
}
if (isset($slodetails["slostartdate"]))
{
	if ($slostartdate_dd === false)
		print "<option selected value=\"\"></option>\n";
	else
		print "<option value=\"\"></option>\n";
}
else
	print "<option value=\"\"></option>\n";
?>
</select>
&nbsp;-&nbsp;
<select name="soldatestart_yy" tabindex="30" style="width: 6em">
<?php
$y_start = date("Y")-1;
for ($i = 0; $i < 12; $i++)
{
	if (isset($slodetails["slostartdate"]))
	{
		if ($slostartdate_yy !== false)
		{
			if (($i + $y_start) == $slostartdate_yy)
				print "<option selected value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
			else 
				print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
		}
		else
			print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
	}
	else
		print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
}
if (isset($slodetails["slostartdate"]))
{
	if ($slostartdate_yy === false)
		print "<option selected value=\"\"></option>\n";
	else
		print "<option value=\"\"></option>\n";
}
else
	print "<option value=\"\"></option>\n";
?>
</select>
</td>

<td align="left" valign="top" width="35%">
<span class="lblblktext">End Date (mm-dd-yyyy)</span><br>
<select name="soldateend_mm" tabindex="40" style="width: 4em">
<?php
for ($i = 1; $i < 13; $i++)
{
	if (isset($slodetails["sloenddate"]))
	{
		if ($sloenddate_mm !== false)
		{
			if ($i == $sloenddate_mm)
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
	else
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
}
if (isset($slodetails["sloenddate"]))
{
	if ($sloenddate_mm === false)
		print "<option selected value=\"\"></option>\n";
	else
		print "<option value=\"\"></option>\n";
}
else
	print "<option value=\"\"></option>\n";
?>
</select>
&nbsp;-&nbsp;
<select name="soldateend_dd" tabindex="50" style="width: 4em">
<?php
for ($i = 1; $i < 32; $i++)
{
	if (isset($slodetails["sloenddate"]))
	{
		if ($sloenddate_dd !== false)
		{
			if ($i == $sloenddate_dd)
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
	else
	{
		$v = str_pad($i, 2, "0", STR_PAD_LEFT);
		print "<option value=\"".$v."\">".$v."</option>\n";
	}
}
if (isset($slodetails["sloenddate"]))
{
	if ($sloenddate_dd === false)
		print "<option selected value=\"\"></option>\n";
	else
		print "<option value=\"\"></option>\n";
}
else
	print "<option value=\"\"></option>\n";
?>
</select>
&nbsp;-&nbsp;
<select name="soldateend_yy" tabindex="60" style="width: 6em">
<?php
$y_start = date("Y")-1;
for ($i = 0; $i < 12; $i++)
{
	if (isset($slodetails["sloenddate"]))
	{
		if ($sloenddate_yy !== false)
		{
			if (($i + $y_start) == $sloenddate_yy)
				print "<option selected value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
			else 
				print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
		}
		else
			print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
	}
	else
		print "<option value=\"".($i + $y_start)."\">".($i + $y_start)."</option>\n";
}
if (isset($slodetails["sloenddate"]))
{
	if ($sloenddate_yy === false)
		print "<option selected value=\"\"></option>\n";
	else
		print "<option value=\"\"></option>\n";
}
else
	print "<option value=\"\"></option>\n";
?>
</select>
</td>

<td align="left" valign="top" width="30%"><span class="lblblktext">&nbsp;</span><br/>
<input type="submit" value="Update" class="btntext" name="submit_slo_edit" tabindex="70" />
&nbsp;&nbsp;
<input type="submit" value="Delete" class="btntext" name="submit_slo_delete" tabindex="80" />
</td>

</tr>
</table>
</form>
</body></html>