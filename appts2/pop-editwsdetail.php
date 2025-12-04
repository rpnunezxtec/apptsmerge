<?php
// $Id:$

// popup to edit emws details
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-editwsdetail.php";
// the geometry required for this popup
$windowx = 500;
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

// Validate access to this form - requires User tab permissions
if ($myappt->checktabmask(TAB_WS) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Get admin privileges: ws alteration privileges.
$priv_wscreate = $myappt->checkprivilege(PRIV_WSCREATE);
$priv_wsasgn = $myappt->checkprivilege(PRIV_WSASGN);
$priv_wsstat = $myappt->checkprivilege(PRIV_WSSTAT);

// GET arguments: 
// wsuuid: workstation id (optional). If not supplied it is assumed a new ws is being added.
if (isset($_GET["wsuuid"]))
{
	$wsuuid = $_GET["wsuuid"];
	// check and sanitise it
	if (strlen($wsuuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid wsuuid.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	
	// AVC is only present when the WSID is submitted, to verify it
	if (isset($_GET["avc"]))
		$avc = $_GET["avc"];
	else
	{
		print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// Check the AVC mac for validity
	$testavc = $myappt->session_createmac($wsuuid);
	
	if (strcasecmp($avc, $testavc) != 0)
	{
		print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
	$wsuuid = false;

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Process submissions here
	if (isset($_POST["submit_ws"]))
	{
		if ($wsuuid !== false)
		{
			// Update an existing ws
			// First part - general info requires ws creation privilege and local sourcing
			if ($priv_wscreate && ($_axsitesync_enable === false))
			{
				// Posted info to use:
				// wsname, deviceid
				$p_wsname = trim($_POST["wsname"]);
				$p_deviceid = trim($_POST["deviceid"]);
				
				// Check for required fields: sitename
				if ($p_wsname == "" || $p_deviceid == "")
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					$q_ws = "update workstation set "
						. "\n wsname='".$sdbh->real_escape_string($p_wsname)."', "
						. "\n deviceid='".$sdbh->real_escape_string($p_deviceid)."', "
						. "\n xsyncmts='".time()."' "
						. "\n where wsuuid='".$sdbh->real_escape_string($wsuuid)."' "
						. "\n limit 1 "
						;
				
					$s_ws = $sdbh->query($q_ws);
					
					if ($s_ws)
					{
						$logstring = "Workstation ".$p_wsname." (wsuuid: ".$wsuuid.") updated.";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITWS);
					}
				}
			}
			
			// Second part - assignment of workstation to a site and local sourcing
			if ($priv_wsasgn && ($_axsitesync_enable === false))
			{
				// Get the posted centeruuid.
				// A null value for the this means not assigned and is stored as a NULL in the database
				$centeruuid = $_POST["center"];
				if (strlen($centeruuid) != 36)
					$centeruuid = false;

				// get the wsname
				$wsdetails = $myappt->readwsdetail($sdbh, $wsuuid);
				
				$wsname = $wsdetails["wsname"];
				$wscenter = $wsdetails["centeruuid"];
				
				if ($centeruuid === false)
				{
					// unassign the ws for sites
					$q_wsa = "update workstation set "
						. "\n centeruuid=NULL, "
						. "\n xsyncmts='".time()."' "
						. "\n where wsuuid='".$sdbh->real_escape_string($wsuuid)."' "
						. "\n limit 1 "
						;
				}
				else
				{
					$q_wsa = "update workstation set "
						. "\n centeruuid='".$sdbh->real_escape_string($centeruuid)."', "
						. "\n xsyncmts='".time()."' "
						. "\n where wsuuid='".$sdbh->real_escape_string($wsuuid)."' "
						. "\n limit 1 "
						;
				}
				$s_wsa = $sdbh->query($q_wsa);
				
				if ($s_wsa)
				{
					if ($centeruuid === false)
					{
						// get the old site name
						$wssitedetails = $myappt->readsitedetail($sdbh, $wscenter);
						$logstring = "Workstation ".$wsname." (wsuuid: ".$wsuuid.") unassigned from ".$wssitedetails["sitename"]."(centeruuid: ".$wscenter.").";
					}
					else 
					{
						// get new site name
						$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
						$logstring = "Workstation ".$wsname." (wsuuid: ".$wsuuid.") assigned to ".$sitedetails["sitename"]."(centeruuid: ".$centeruuid.").";
					}
						
					$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITWS);
				}
			}
			
			// Third part - ws status requires wsstat privilege
			if ($priv_wsstat && ($_axsitesync_enable === false))
			{
				$wsstat = $_POST["status"];
				if (!is_numeric($wsstat))
					$wsstat = 0;
				if ($wsstat > 0)
					$wsstat = 1;
					
				// get the wsname
				$wsdetails = $myappt->readwsdetail($sdbh, $wsuuid);
				$wsname = $wsdetails["wsname"];
				
				$q_wstat = "update workstation set "
					. "\n status='".$wsstat."', "
					. "\n xsyncmts='".time()."' "
					. "\n where wsuuid='".$sdbh->real_escape_string($wsuuid)."' "
					. "\n limit 1 "
					;
				$s_wstat = $sdbh->query($q_wstat);
				
				if ($s_wstat)
				{
					$logstring = "Workstation ".$wsname." (wsuuid: ".$wsuuid.") status set to ".($wsstat == 0 ? "unavailable" : "available");
					$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_STATUSWS);
				}
			}
			
			// update calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
		else
		{
			// Create a new site
			// First part - general info requires ws creation privilege and local sourcing
			if ($priv_wscreate && ($_axsitesync_enable === false))
			{
				// Posted info to use:
				// wsname, deviceid
				$p_wsname = trim($_POST["wsname"]);
				$p_deviceid = trim($_POST["deviceid"]);
				
				// Check for required fields: sitename
				if ($p_wsname == "" || $p_deviceid == "")
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					// Create a wsuuid for the workstation record
					$wsuuid = $myappt->makeuniqueuuid($sdbh, "workstation", "wsuuid");

					$q_ws = "insert into workstation set "
						. "\n wsname='".$sdbh->real_escape_string($p_wsname)."', "
						. "\n deviceid='".$sdbh->real_escape_string($p_deviceid)."', "
						. "\n wsuuid='".$wsuuid."', "
						. "\n xsyncmts='".time()."' "
						;
				
					$s_ws = $sdbh->query($q_ws);
					if ($s_ws)
					{
						$avc = $myappt->session_createmac($wsuuid);
						$logstring = "Workstation ".$p_wsname." (wsuuid: ".$wsuuid.") created.";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_NEWWS);
					}
					else
						print "<script type=\"text/javascript\">alert('Error creating new workstation: ".htmlentities($sdbh->error).".')</script>\n";
				}
			}
			
			// Second part - assignment of workstation to a site and local sourcing
			if ($priv_wsasgn && ($_axsitesync_enable === false))
			{
				if ($wsuuid !== false)
				{
					$wsdetails = $myappt->readwsdetail($sdbh,$wsuuid);
					$wsname = $wsdetails["wsname"];
					$wscenteruuid = $wsdetails["centeruuid"];

					// Get the posted centeruuid.
					// A null value for the centeruuid means not assigned and is stored as a NULL in the database
					$centeruuid = $_POST["center"];
					if (strlen($centeruuid) != 36)
						$centeruuid = false;

					if ($centeruuid === false)
					{
						// unassign the ws for sites
						$q_wsa = "update workstation set "
							. "\n centeruuid=NULL, "
							. "\n xsyncmts='".time()."' "
							. "\n where wsuuid='".$sdbh->real_escape_string($wsuuid)."' "
							. "\n limit 1 "
							;
					}
					else
					{
						$q_wsa = "update workstation set "
							. "\n centeruuid='".$sdbh->real_escape_string($centeruuid)."', "
							. "\n xsyncmts='".time()."' "
							. "\n where wsuuid='".$sdbh->real_escape_string($wsuuid)."' "
							. "\n limit 1 "
							;
					}
					$s_wsa = $sdbh->query($q_wsa);
					if ($s_wsa)
					{
						if ($centeruuid === false)
						{
							// get the old site name
							$wssitedetails = $myappt->readsitedetail($sdbh, $wscenteruuid);
							$logstring = "Workstation ".$wsname." (wsuuid: ".$wsuuid.") unassigned from ".$wssitedetails["sitename"]."(centeruuid: ".$wscenteruuid.").";
						}
						else 
						{
							// get new site name
							$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
							$logstring = "Workstation ".$wsname." (wsuuid: ".$wsuuid.") assigned to ".$sitedetails["sitename"]."(centeruuid: ".$centeruuid.").";
						}
						
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITWS);
					}
				}
			}
			
			// Third part - ws status requires wsstat privilege
			if ($priv_wsstat && ($_axsitesync_enable === false))
			{
				if ($wsuuid !== false)
				{
					$wsstat = $_POST["status"];
					if (!is_numeric($wsstat))
						$wsstat = 0;
					if ($wsstat > 0)
						$wsstat = 1;
						
					// get the wsname
					$wsdetails = $myappt->readwsdetail($sdbh, $wsuuid);
					$wsname = $wsdetails["wsname"];
			
					$q_wstat = "update workstation set "
						. "\n status='".$wsstat."', "
						. "\n xsyncmts='".time()."' "
						. "\n where wsuuid='".$sdbh->real_escape_string($wsuuid)."' "
						. "\n limit 1 "
						;
					$s_wstat = $sdbh->query($q_wstat);
					if ($s_wstat)
					{
						$logstring = "Workstation ".$wsname." (wsuuid: ".$wsuuid.") status set to ".($wsstat == 0 ? "unavailable" : "available");
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_STATUSWS);
					}
				}
			}
			
			// update calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
			print "<script type=\"text/javascript\">alert('Workstation updated.')</script>\n";
			print "<script type=\"text/javascript\">window.close()</script>\n";
		}
	}
	
	// Get a list of sites
	$siteslist = $myappt->readsiteslist($sdbh);
	
	// Get the ws details. Includes assigned site if present
	if ($wsuuid !== false)
	{
		$wsdetails = $myappt->readwsdetail($sdbh, $wsuuid);
		
		if (count($wsdetails) == 0)
		{
			$sdbh->close();
			print "<script type=\"text/javascript\">alert('Workstation not found.')</script>\n";
			print "<script type=\"text/javascript\">window.close()</script>\n";
			die();
		}
	}
	else
		$wsdetails = array();
	
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
<META http-equiv="Default-Style" content="text/html;charset=UTF-8">
<title>Workstation Properties</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico">
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css">
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<?php
if ($priv_wscreate || $priv_wsasgn || $priv_wsstat)
{
	print "<form name=\"wsprops\" method=\"POST\"  autocomplete=\"off\" action=\"".$formfile.($wsuuid === false ? "" : "?wsuuid=".urlencode($wsuuid)."&avc=".urlencode($avc))."\">\n";
}
if ($priv_wscreate && ($_axsitesync_enable === false))
{
	// can edit general detail
?>
<table border="0" cellspacing="0" cellpadding="5" width="440">
<tr height="30">
<td valign="top" align="right" colspan="2"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" tabindex="21"></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Workstation Name *</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="34" maxlength="50" tabindex="1" name="wsname" value="<?php print (isset($wsdetails["wsname"]) ? $wsdetails["wsname"] : "") ?>" />
</span></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Device ID *</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="34" maxlength="50" tabindex="2" name="deviceid" value="<?php print (isset($wsdetails["deviceid"]) ? $wsdetails["deviceid"] : "") ?>" />
</span></td>
</tr>
</table>
<?php
}
else 
{
	// can view general detail only
?>
<table border="0" cellspacing="0" cellpadding="5" width="440">
<tr height="30">
<td valign="top" width="200"><span class="proplabel">Workstation Name *</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($wsdetails["wsname"]) ? $wsdetails["wsname"] : "") ?></span></td>
</tr>
<tr height="30">
<td valign="top" width="200"><span class="proplabel">Device ID *</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($wsdetails["deviceid"]) ? $wsdetails["deviceid"] : "") ?></span></td>
</tr>
</table>
<?php
}
if ($priv_wsasgn && ($_axsitesync_enable === false))
{
	// can assign ws to site
?>
<table cellSpacing="0" cellPadding="5" width="440" border="0">
<tr height="30">
<td valign="top" width="200"><span class="proplabel">Assigned to Site</span></td>
<td valign="top" width="240"><span class="proptext">
<select name="center" tabindex="2" style="width:18em;">
<?php
	for ($i = 0; $i < count($siteslist); $i++)
	{
		if (isset($wsdetails["centeruuid"]))
		{
			if (strcasecmp($wsdetails["centeruuid"], $siteslist[$i][0]) == 0)
				print "<option selected value=\"".$siteslist[$i][0]."\">".$siteslist[$i][1]."</option>\n";
			else
				print "<option value=\"".$siteslist[$i][0]."\">".$siteslist[$i][1]."</option>\n";
		}
		else
			print "<option value=\"".$siteslist[$i][0]."\">".$siteslist[$i][1]."</option>\n";
	}
	if (isset($wsdetails["centeruuid"]))
	{
		if ($wsdetails["centeruuid"] == "")
			print "<option selected value=\"\"></option>\n";
		else
			print "<option value=\"\"></option>\n";
	}
	else
		print "<option value=\"\"></option>\n";
?>
</select></span>
</td></tr>
</table>
<?php
}
else
{
	// show the assignment only
?>
<table cellSpacing="0" cellPadding="5" width="440" border="0">
<tr height="30">
<td valign="top" width="200"><span class="proplabel">Assigned to Site</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($wsdetails["sitename"]) ? $wsdetails["sitename"] : "") ?></span></td>
</tr>
</table>
<?php
}
if ($priv_wsstat && ($_axsitesync_enable === false))
{
	// can edit ws status
?>
<table cellSpacing="0" cellPadding="5" width="440" border="0">
<tr height="30">
<td width="200" valign="top"><span class="proplabel">Workstation Status</span></td>
<td width="240" valign="top">
<select name="status" tabindex="39" style="width:18em;">
<?php
	if (isset($wsdetails["wsstatus"]))
	{
?>
<option <?php print ($wsdetails["wsstatus"] == 1 ? "selected" : "") ?> value="1">Available</option>
<option <?php print ($wsdetails["wsstatus"] != 1 ? "selected" : "") ?> value="0">Unavailable</option>
<?php
	}
	else
	{
?>
<option value="0">Unavailable</option>
<option value="1">Available</option>
<?php
	}
?>
</select>
</td></tr>
</table>
<?php
}
else 
{
	// show the status only
?>
<table cellSpacing="0" cellPadding="5" width="440" border="0">
<tr height="30">
<td width="200" valign="top"><span class="proplabel">Workstation Status</span></td>
<?php
	if (isset($wsdetails["wsstatus"]))
	{
?>
<td width="240" valign="top"><span class="proptext"><?php print ($wsdetails["wsstatus"] == 1 ? "Available" : "Unavailable") ?><span></td>
<?php
	}
	else
	{
?>
<td width="240" valign="top"><span class="proptext"><span></td>
<?php
	}
?>
</tr>
</table>
<?php
}
// Add the buttons if privileges allow
if (($priv_wscreate && ($_axsitesync_enable === false)) || ($priv_wsasgn && ($_axsitesync_enable === false)) || ($priv_wsstat && ($_axsitesync_enable === false)))
{
	// submit button and end of form
	print "<table cellSpacing=\"0\" cellPadding=\"0\" width=\"440\" border=\"0\">\n";
	print "<tr height=\"40\">\n";
	print "<td width=\"200\" valign=\"center\" align=\"left\">\n";
	print "<input type=\"submit\" name=\"submit_ws\" class=\"btntext\" value=\"Save\">\n";
	print "</form>\n";
	print "</td>\n";
	print "<td width=\"240\" valign=\"center\" align=\"left\">\n";
	print "<span class=\"proplabel\">* Required items.</span>\n";
	print "</td>\n";
	print "</tr>\n";
	print "</table>\n";
}
?>
</body></html>