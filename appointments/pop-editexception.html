<?php
// $Id:$

// popup to edit a site availability exception record
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-editexception.html";
// the geometry required for this popup
$windowx = 700;
$windowy = 700;

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

// axid: availability exception unique identifier
if (isset($_GET["axid"]))
{
	$axid = $_GET["axid"];
	if (!is_numeric($axid))
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

// sid: site id.
if (isset($_GET["sid"]))
{
	$siteid = $_GET["sid"];
	// check and sanitise it
	if (!is_numeric($siteid))
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
$testavc = $myappt->session_createmac($axid.$siteid);
	
if (strcasecmp($avc, $testavc) != 0)
{
	print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

$daytext = array ("", "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat");

// Read database info for form
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
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
			
		if (($axstime !== false) && ($axetime !== false) && (trim($axstime) != "") && (trim($axetime) != ""))
		{
			$q_axentry = "update availexception "
					. "\n set " 
					. "\n axdate='".$dbh->real_escape_string($axdate)."', "
					. "\n axday='".$dbh->real_escape_string($axdow)."', "
					. "\n axstart='".$dbh->real_escape_string($axstime)."', "
					. "\n axend='".$dbh->real_escape_string($axetime)."' "
					. "\n where axid='".$dbh->real_escape_string($axid)."' "
					;
		}
		elseif (($axdate != "//") || ($axdow != 0))
		{
			$q_axentry = "update availexception "
					. "\n set " 
					. "\n axdate='".$dbh->real_escape_string($axdate)."', "
					. "\n axday='".$dbh->real_escape_string($axdow)."' "
					. "\n where axid='".$dbh->real_escape_string($axid)."' "
					;
		}
		if ($q_axentry)
			$s_axentry = $dbh->query($q_axentry);
		else
			$s_axentry = false;
				
		$q_os = "select siteid, sitename "
			. "\n from site "
			. "\n where siteid='".$siteid."' "
			;
		$s_os = $dbh->query($q_os);
		$r_os = $s_os->fetch_assoc();
		$osname = $r_os["sitename"];
		$s_os->free();

		if ($s_axentry)
		{
			$logstring = "Site ".$osname." (".$siteid.") availability exception updated.";
			$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
			
			// update the calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
	}
	elseif (isset($_POST["submit_aex_delete"]))
	{
		// delete the record and close the popup
		$q_axentry = "delete from availexception "
				. "\n where axid='".$dbh->real_escape_string($axid)."' "
				. "\n limit 1 "
				;
		$s_axentry = $dbh->query($q_axentry);
		
		$q_os = "select siteid, sitename "
			. "\n from site "
			. "\n where siteid='".$siteid."' "
			;
		$s_os = $dbh->query($q_os);
		$r_os = $s_os->fetch_assoc();
		$osname = $r_os["sitename"];
		$s_os->free();

		if ($s_axentry)
		{
			$logstring = "Site ".$osname." (".$siteid.") availability exception deleted.";
			$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
		}
		
		print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		print "<script type=\"text/javascript\">alert('Exception deleted.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// Get the exception detail
	$q_ax = "select * "
		. "\n from availexception "
		. "\n where axid='".$dbh->real_escape_string($axid)."' "
		;
	$s_ax = $dbh->query($q_ax);
	$r_ax = $s_ax->fetch_assoc();
	
	// Extract the data
	$axdate_yy = false;
	$axdate_mm = false;
	$axdate_dd = false;
	$axday = 0;
	$axst_hh = false;
	$axst_mm = false;
	$axet_hh = false;
	$axet_mm = false;
			
	if (isset($r_ax["axdate"]))
	{
		$axdate = $r_ax["axdate"];
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

	if (isset($r_ax["axday"]))
	{
		$axday = $r_ax["axday"];
		if ($axday == "")
			$axday = 0;
	}
	
	if (isset($r_ax["axstart"]))
	{
		$axstime = $r_ax["axstart"];
		if ($axstime != NULL)
		{
			$axst_hh = substr($axstime, 0, 2);
			$axst_mm = substr($axstime, 3, 2);
		}
	}
		
	if (isset($r_ax["axend"]))
	{
		$axetime = $r_ax["axend"];
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
			$axst_hh = false;
			$axst_mm = false;
			$axet_hh = false;
			$axet_mm = false;
		}
	}
	
	// Get site details
	$q_s = "select * from site "
		. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
		;
	$s_s = $dbh->query($q_s);
	$r_s = $s_s->fetch_assoc();
	$sitename = $r_s["sitename"];
	$s_s->free();
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
?>

<table width="100%" cellspacing="0" cellpadding="0" border="0">
<tr height="40">
<td width="70%" valign="top" align="left"><span class="lblblktext">Availability Exception for site: <?php print $sitename ?></span></td>
<td width="30%" valign="top" align="left"><input type="button" name="close" class="inputbtn darkblue" value="Close" onclick="javascript:window.close()"></td>
</tr></table><p>
<form name="edit_ex" method="post"  autocomplete="off" action="<?php print $formfile."?axid=".urlencode($axid)."&sid=".urlencode($siteid)."&avc=".urlencode($testavc) ?>" >
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
<input type="submit" value="Update" class="inputbtn darkblue" name="submit_aex_edit" tabindex="9">
&nbsp;&nbsp;
<input type="submit" value="Delete" class="inputbtn darkblue" name="submit_aex_delete" tabindex="10">
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