<?php
// $Id:$

// popup to view the site availability exception
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-siteexception.html";
// the geometry required for this popup
$windowx = 800;
$windowy = 800;

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
	$testavc = $myappt->session_createmac($siteid);
	
	if (strcasecmp($avc, $testavc) != 0)
	{
		print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
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

// Read database info for form
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
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
				if (!is_numeric($axdow))
					$axdow = 0;
			}
			else
				$axdow = 0;
			
			if (isset($_POST["axstime_hh"]))
			{
				$axstime_hh = $_POST["axstime_hh"];
				if (!is_numeric($axstime_hh) || ($axstime_hh == ""))
					$axstime_hh = false;
			}
			else
				$axstime_hh = false;
				
			if (isset($_POST["axstime_mm"]))
			{
				$axstime_mm = $_POST["axstime_mm"];
				if (!is_numeric($axstime_mm) || ($axstime_mm == ""))
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
				if (!is_numeric($axetime_hh) || ($axetime_hh == ""))
					$axetime_hh = false;
			}
			else
				$axetime_hh = false;
				
			if (isset($_POST["axetime_mm"]))
			{
				$axetime_mm = $_POST["axetime_mm"];
				if (!is_numeric($axetime_mm) || ($axetime_mm == ""))
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
				$q_axentry = "insert into availexception "
						. "\n (siteid, "
						. "\n axdate, "
						. "\n axday, "
						. "\n axstart, "
						. "\n axend) "
						. "\n values "
						. "\n ('".$dbh->real_escape_string($siteid)."', "
						. "\n '".$dbh->real_escape_string($axdate)."', "
						. "\n '".$dbh->real_escape_string($axdow)."', "
						. "\n '".$dbh->real_escape_string($axstime)."', "
						. "\n '".$dbh->real_escape_string($axetime)."') "
						;
			}
			elseif (($axdate != NULL) || ($axdow != 0))
			{
				$q_axentry = "insert into availexception "
						. "\n (siteid, "
						. "\n axdate, "
						. "\n axday) "
						. "\n values "
						. "\n ('".$dbh->real_escape_string($siteid)."', "
						. "\n '".$dbh->real_escape_string($axdate)."', "
						. "\n '".$dbh->real_escape_string($axdow)."') "
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
				$logstring = "Site ".$osname." (".$siteid.") availability exception added.";
				$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
			}
		}
	}

	// Get a list of exceptions for this site
	$q_ax = "select * "
		. "\n from availexception "
		. "\n where siteid='".$siteid."' "
		;
	$s_ax = $dbh->query($q_ax);
	$n_ax = $s_ax->num_rows;
	
	// Get site details
	$q_s = "select * from site "
		. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
		;
	$s_s = $dbh->query($q_s);
	$r_s = $s_s->fetch_assoc();
	$sitename = $r_s["sitename"];
	$s_s->free();
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
<div style="width:440px;display:flex;justify-content:flex-end;margin:0 0 6px;">
  <input type="button" name="close" class="inputbtn darkblue" value="Close" onclick="window.close()" tabindex="21">
</div>
<div class="lblblktext">Availability Exceptions for site: <?php print $sitename ?></div>
<?php
	print "<table class=\"striped\">";
	print "<thead>";
	print "<tr class=\"light-xtec-blue\">";
	print "<th class=\"tableheader\">No</th>";
	print "<th class=\"tableheader\">Date (mm/dd/yyyy)</th>";					
	print "<th class=\"tableheader\">Day of week</th>";					
	print "<th class=\"tableheader\">Start Time</th>";					
	print "<th class=\"tableheader\">Status</th>";					
	print "<th class=\"tableheader\">End Time</th>";								
	print "</tr>";
	print "</thead>";
	print "<tbody>";

$rnum = 1;
while ($r_ax = $s_ax->fetch_assoc())
{
	// get the data for the row
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

	if (isset($r_ax["axday"]))
	{
		$axday = $r_ax["axday"];
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
		
	if (isset($r_ax["axstart"]))
	{
		$axstime = $r_ax["axstart"];
		if ($axstime != NULL)
			$axst = substr($axstime, 0, 5);
		else
			$axst = "-";
	}
	else
		$axst = "-";
		
	if (isset($r_ax["axend"]))
	{
		$axetime = $r_ax["axend"];
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
	
	$axid = $r_ax["axid"];
	$avc = $myappt->session_createmac($axid.$siteid);
	
	// output each row for this page
	print "<tr>";
	print "<td style=\"text-align: center\" class=\"matrixline\"><span class=\"tabletext\">";
	if ($priv_shours)
	{
		print "<a href=\"javascript:popupOpener('pop-editexception.html?axid=" . urlencode($axid) . "&sid=" . urlencode($siteid) . "&avc=" . urlencode($avc) . "','editaex',350,500)\" title=\"Edit/delete exception entry\">" . htmlentities($rnum, ENT_QUOTES, 'UTF-8') . "</a>";
	}
	else 
	{
		print htmlentities($rnum);
	}
	print "</span></td>";
	print "</tr>";
	
	print "<tr>";
	print '<td><p>' . htmlspecialchars((string)$axd, ENT_QUOTES, 'UTF-8') . '</p></td>';
	print '<td><p>' . htmlspecialchars((string)$axdow, ENT_QUOTES, 'UTF-8') . '</p></td>';
	print '<td><p>' . htmlspecialchars((string)$axst, ENT_QUOTES, 'UTF-8') . '</p></td>';
	print '<td><p>' . htmlspecialchars((string)$axet, ENT_QUOTES, 'UTF-8') . '</p></td>';
	print "</tr>";

	$rnum++;
}
$s_ax->free();
$dbh->close();
print "</table>";

if ($priv_shours)
{
	$avc = $myappt->session_createmac($siteid);
?>
<p>
<hr>
<p>
<form name="add_ex" method="post" action="<?php print $formfile."?sid=".$siteid."&avc=".$avc ?>"  autocomplete="off" >
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
<input type="submit" value="Add" class="inputbtn darkblue" name="submit_aex" tabindex="9">
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