<?php
// $Id:$

// popup to edit emws details
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-editwsdetail.html";
// the geometry required for this popup
$windowx = 500;
$windowy = 400;

include("config.php");
include("vec-clappointments.php");
include('../appcore/vec-clforms.php');
$myform = new authentxforms();
$myappt = new authentxappointments();
date_default_timezone_set(DATE_TIMEZONE);

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

$wsname = "";
$wsstat = 0;

// GET arguments: 
// wsid: workstation id (optional). If not supplied it is assumed a new ws is being added.
if (isset($_GET["wsid"]))
{
	$wsid = $_GET["wsid"];
	// check and sanitise it
	if (!is_numeric($wsid))
	{
		print "<script type=\"text/javascript\">alert('Invalid wsid.')</script>\n";
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
	$testavc = $myappt->session_createmac($wsid);
	
	if (strcasecmp($avc, $testavc) != 0)
	{
		print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
	$wsid = false;

// Read database info for form
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	// Process submissions here
	if (isset($_POST["submit_ws"]))
	{
		if ($wsid !== false)
		{
			// Update an existing ws
			// First part - general info requires ws creation privilege and local sourcing
			if ($priv_wscreate && ($_axsitesync_enable === false))
			{
				// Posted info to use:
				// wsname
				$p_wsname = $_POST["wsname"];
				
				// Check for required fields: sitename
				if ($p_wsname == "")
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					$q_ws = "update workstation set "
						. "\n wsname='".$dbh->real_escape_string($p_wsname)."' "
						. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
						. "\n limit 1 "
						;
				
					$s_ws = $dbh->query($q_ws);
					
					if ($s_ws)
					{
						$logstring = "Workstation ".$p_wsname." (".$wsid.") updated.";
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITWS);
					}
				}
			}
			
			// Second part - assignment of workstation to a site and local sourcing
			if ($priv_wsasgn && ($_axsitesync_enable === false))
			{
				// Get the posted siteid.
				// A null value for the siteid means not assigned and is stored as a NULL in the database
				$siteid = $_POST["siteid"];
				if (!is_numeric($siteid) || ($siteid == ""))
					$siteid = false;

				// get the wsname
				$q_ws = "select wsid, wsname, siteid "
					. "\n from workstation "
					. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
					;
				$s_ws = $dbh->query($q_ws);
				$r_ws = $s_ws->fetch_assoc();
				$wsname = $r_ws["wsname"];
				$wssite = $r_ws["siteid"];
				$s_ws->free();
				
				if ($siteid === false)
				{
					// unassign the ws for sites
					$q_wsa = "update workstation set "
						. "\n siteid=NULL "
						. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
						. "\n limit 1 "
						;
				}
				else
				{
					$q_wsa = "update workstation set "
						. "\n siteid='".$dbh->real_escape_string($siteid)."' "
						. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
						. "\n limit 1 "
						;
				}
				$s_wsa = $dbh->query($q_wsa);
				
				if ($s_wsa)
				{
					if ($siteid === false)
					{
						// get the old site name
						$q_os = "select siteid, sitename "
							. "\n from site "
							. "\n where siteid='".$wssite."' "
							;
						$s_os = $dbh->query($q_os);
						$r_os = $s_os->fetch_assoc();
						$osname = $r_os["sitename"];
						$s_os->free();
						$logstring = "Workstation ".$wsname." (".$wsid.") unassigned from ".$osname."(".$wssite.").";
					}
					else 
					{
						// get new site name
						$q_ns = "select siteid, sitename "
							. "\n from site "
							. "\n where siteid='".$siteid."' "
							;
						$s_ns = $dbh->query($q_ns);
						$r_ns = $s_ns->fetch_assoc();
						$nsname = $r_ns["sitename"];
						$s_ns->free();
						$logstring = "Workstation ".$wsname." (".$wsid.") assigned to ".$nsname."(".$siteid.").";
					}
						
					$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITWS);
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
				$q_ws = "select wsid, wsname, siteid "
					. "\n from workstation "
					. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
					;
				$s_ws = $dbh->query($q_ws);
				$r_ws = $s_ws->fetch_assoc();
				$wsname = $r_ws["wsname"];
				$s_ws->free();
				
				$q_wstat = "update workstation set "
					. "\n status='".$wsstat."' "
					. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
					. "\n limit 1 "
					;
				$s_wstat = $dbh->query($q_wstat);
				
				if ($s_wstat)
				{
					$logstring = "Workstation ".$wsname." (".$wsid.") status set to ".($wsstat == 0 ? "unavailable" : "available");
					$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_STATUSWS);
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
				// wsname
				$p_wsname = $_POST["wsname"];
				
				// Check for required fields: sitename
				if ($p_wsname == "")
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					$q_ws = "insert into workstation "
						. "\n (wsname) "
						. "\n values "
						. "\n ('".$dbh->real_escape_string($p_wsname)."') "
						;
				
					$s_ws = $dbh->query($q_ws);
					if ($s_ws)
					{
						$wsid = $dbh->insert_id;
						$avc = $myappt->session_createmac($wsid);
						$logstring = "Workstation ".$p_wsname." (".$wsid.") created.";
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_NEWWS);
					}
					else
						print "<script type=\"text/javascript\">alert('Error creating new workstation: ".htmlentities($dbh->error).".')</script>\n";
				}
			}
			
			// Second part - assignment of workstation to a site and local sourcing
			if ($priv_wsasgn && ($_axsitesync_enable === false))
			{
				if ($wsid !== false)
				{
					// Get the posted siteid.
					// A null value for the siteid means not assigned and is stored as a NULL in the database
					$siteid = $_POST["siteid"];
					if (!is_numeric($siteid) || ($siteid == ""))
						$siteid = false;

					if ($siteid === false)
					{
						// unassign the ws for sites
						$q_wsa = "update workstation set "
							. "\n siteid=NULL "
							. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
							. "\n limit 1 "
							;
					}
					else
					{
						$q_wsa = "update workstation set "
							. "\n siteid='".$dbh->real_escape_string($siteid)."' "
							. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
							. "\n limit 1 "
							;
					}
					$s_wsa = $dbh->query($q_wsa);
					if ($s_wsa)
					{
						if ($siteid === false)
						{
							// get the old site name
							$q_os = "select siteid, sitename "
								. "\n from site "
								. "\n where siteid='".$wssite."' "
								;
							$s_os = $dbh->query($q_os);
							$r_os = $s_os->fetch_assoc();
							$osname = $r_os["sitename"];
							$s_os->free();
							$logstring = "Workstation ".$wsname." (".$wsid.") unassigned from ".$osname."(".$wssite.").";
						}
						else 
						{
							// get new site name
							$q_ns = "select siteid, sitename "
								. "\n from site "
								. "\n where siteid='".$siteid."' "
								;
							$s_ns = $dbh->query($q_ns);
							$r_ns = $s_ns->fetch_assoc();
							$nsname = $r_ns["sitename"];
							$s_ns->free();
							$logstring = "Workstation ".$wsname." (".$wsid.") assigned to ".$nsname."(".$siteid.").";
						}
						
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITWS);
					}
				}
			}
			
			// Third part - ws status requires wsstat privilege
			if ($priv_wsstat && ($_axsitesync_enable === false))
			{
				if ($wsid !== false)
				{
					$wsstat = $_POST["status"];
					if (!is_numeric($wsstat))
						$wsstat = 0;
					if ($wsstat > 0)
						$wsstat = 1;
						
					// get the wsname
					$q_ws = "select wsid, wsname, siteid "
						. "\n from workstation "
						. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
						;
					$s_ws = $dbh->query($q_ws);
					$r_ws = $s_ws->fetch_assoc();
					$wsname = $r_ws["wsname"];
					$s_ws->free();
			
					$q_wstat = "update workstation set "
						. "\n status='".$wsstat."' "
						. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
						. "\n limit 1 "
						;
					$s_wstat = $dbh->query($q_wstat);
					if ($s_wstat)
					{
						$logstring = "Workstation ".$wsname." (".$wsid.") status set to ".($wsstat == 0 ? "unavailable" : "available");
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_STATUSWS);
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
	$q_sites = "select siteid, sitename "
		. "\n from site "
		. "\n order by sitename "
		;
	$s_sites = $dbh->query($q_sites);
	$n_sites = $s_sites->num_rows;
	
	// Get the ws detail for the form
	if ($wsid !== false)
	{
		$q_ws = "select * from workstation "
			. "\n where wsid='".$dbh->real_escape_string($wsid)."' "
			;
		$s_ws = $dbh->query($q_ws);
		$n_ws = $s_ws->num_rows;
		if ($n_ws == 0)
		{
			$s_ws->free();
			$dbh->close();
			print "<script type=\"text/javascript\">alert('Workstation not found.')</script>\n";
			print "<script type=\"text/javascript\">window.close()</script>\n";
			die();
		}
		$r_ws = $s_ws->fetch_assoc();
		$wsname = $r_ws["wsname"];
		$wsstat = $r_ws["status"];
		$siteid = $r_ws["siteid"];
		$s_ws->free();
		
		// get the sitename
		if ($siteid != NULL)
		{
			$q_sn = "select siteid, sitename "
				. "\n from site "
				. "\n where siteid='".$siteid."' "
				;
			$s_sn = $dbh->query($q_sn);
			if ($s_sn)
			{
				$r_sn = $s_sn->fetch_assoc();
				$sitename = $r_sn["sitename"];
				$s_sn->free();
			}
			else
				$sitename = "";
		}
	}
	else
	{
		$wsname = "";
		$wsstat = "";
		$siteid = "";
	}
	
	$dbh->close();
}
else 
{
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}
// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams['css'] = array_merge($cfg_stdcss, ['../appcore/css/authentx.css']);
$headparams["jscript_file"] = $cfg_stdjscript;

if (AJAX_SESSIONREFRESH_ENABLE === true)
{
	$headparams["jscript_file"][] = "../appcore/scripts/js-ajax_sessionrefresh.js";
	$headparams["jscript_local"][] = "xhrservice = '".AJAX_SESSIONREFRESH_SERVICE."';\n"
								. "refreshinterval='".(SESSION_TIMEOUT - SESSION_TIMEOUT_GRACE)."';\n"
								. "gracetime='".SESSION_TIMEOUT_GRACE."';\n"
								. "sessionTime='".SESSION_TIMEOUT."';\n";
}
$myform->frmrender_head($headparams);

$bodyparams = array();
if (AJAX_SESSIONREFRESH_ENABLE === true)
{
	$bodyparams["onload"][] = "startRefresh()";
	$bodyparams["onload"][] = "startSessionTimer()";
}

if (AJAX_APPT_ENABLE === true)
{
	print "<script language=\"javascript\" src=\"../appcore/scripts/js-ajax_appt.js\"></script>\n";
	print "<script language=\"javascript\">\n";
	print "xhrservice = '".AJAX_APPTSERVICE."'\n";
	if ($siteid !== false)
		print "site='".urlencode($siteid)."'\n";
	if ($wkstamp !== false)
		print "wk='".urlencode($wkstamp)."'\n";
	print "refreshinterval='".$refresh_appt."'\n";
	print "</script>\n";
}

if (AJAX_APPT_ENABLE === true)
{
	if ($refresh_appt !== false)
		print "<body onload='startRefresh();f_init()'>\n";
	else 
		print "<body onload='f_init()'>\n";
}
else 
	print "<body onload='f_init()'>\n";

$myform->frmrender_bodytag($bodyparams);
print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n";

if ($priv_wscreate || $priv_wsasgn || $priv_wsstat)
{
	print "<form name=\"wsprops\" method=\"POST\"  autocomplete=\"off\" action=\"".$formfile.($wsid === false ? "" : "?wsid=".urlencode($wsid)."&avc=".urlencode($avc))."\">\n";
}
if ($priv_wscreate && ($_axsitesync_enable === false))
{
	// can edit general detail
?>
<div style="width:440px;display:flex;justify-content:flex-end;margin:0 0 6px;">
  <input type="button" name="close" class="inputbtn darkblue" value="Close" onclick="window.close()" tabindex="21">
</div>

<table border="0" cellspacing="0" cellpadding="5" width="440">


<tr height="30">
<td valign="top" width="200"><span class="proplabel">Workstation Name *</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="34" maxlength="50" tabindex="1" name="wsname" value="<?php print $wsname ?>" />
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
<td valign="top" width="200"><span class="proplabel">Workstation Name*</span></td>
<td valign="top" width="240"><span class="proptext"><?php print $wsname ?></span></td>
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
<select name="siteid" tabindex="2" style="width:18em;">
<?php
if ($n_sites > 0)
{
	while ($r_sites = $s_sites->fetch_assoc())
	{
		$sid = $r_sites["siteid"];
		$sname = $r_sites["sitename"];
		if ($sid == $siteid)
			print "<option selected value=\"".$sid."\">".$sname."</option>\n";
		else
			print "<option value=\"".$sid."\">".$sname."</option>\n";
	}
}
if ($siteid == "")
	print "<option selected value=\"\"></option>\n";
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
<td valign="top" width="240"><span class="proptext"><?php print $sitename ?></span></td>
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
<option <?php print ($wsstat == 0 ? "selected" : "") ?> value="0">Unavailable</option>
<option <?php print ($wsstat == 1 ? "selected" : "") ?> value="1">Available</option>
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
<td width="240" valign="top"><span class="proptext"><?php print ($wsstat == 1 ? "Available" : "Unavailable") ?><span></td>
</tr>
</table>
<?php
}
// Add the buttons if privileges allow
if (($priv_wscreate&& ($_axsitesync_enable === false)) || ($priv_wsasgn&& ($_axsitesync_enable === false)) || ($priv_wsstat&& ($_axsitesync_enable === false)))
{
	// submit button and end of form
	print "<table cellSpacing=\"0\" cellPadding=\"0\" width=\"440\" border=\"0\">\n";
	print "<tr height=\"40\">\n";
	print "<td width=\"200\" valign=\"center\" align=\"left\">\n";
	print "<input type=\"submit\" name=\"submit_ws\" class=\"inputbtn darkblue\" value=\"Save\">\n";
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