<?php
// $Id:$

// popup to view the site opening date limitation
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-siteopenlimits.html";
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
				$q_solentry = "insert into sitelimitopen "
						. "\n (siteid, "
						. "\n slostartdate, "
						. "\n sloenddate) "
						. "\n values "
						. "\n ('".$dbh->real_escape_string($siteid)."', "
						. "\n '".$dbh->real_escape_string($solstartdate)."', "
						. "\n '".$dbh->real_escape_string($solenddate)."') "
						;
			}
			if ($q_solentry)
				$s_solentry = $dbh->query($q_solentry);
			else
				$s_solentry = false;
				
			$q_os = "select siteid, sitename "
				. "\n from site "
				. "\n where siteid='".$siteid."' "
				;
			$s_os = $dbh->query($q_os);
			$r_os = $s_os->fetch_assoc();
			$osname = $r_os["sitename"];
			$s_os->free();
			if ($s_solentry)
			{
				$logstring = "Site ".$osname." (".$siteid.") site opening date limit added.";
				$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITSITE);
			}
		}
	}

	// Get a list of exceptions for this site
	$q_sol = "select * "
		. "\n from sitelimitopen "
		. "\n where siteid='".$siteid."' "
		;
	$s_sol = $dbh->query($q_sol);
	$n_sol = $s_sol->num_rows;
	
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
<div class="lblblktext">Site Opening Date Limits for site: <?php print $sitename ?></div>
<?php
	print "<table class=\"striped\">";
	print "<thead>";
	print "<tr class=\"light-xtec-blue\">";
	print "<th class=\"tableheader\">No</th>";
	print "<th class=\"tableheader\">Start Date (mm/dd/yyyy)</th>";					
	print "<th class=\"tableheader\">End Date (mm/dd/yyyy)<th>";												
	print "</tr>";
	print "</thead>";
	print "<tbody>";

$rnum = 1;
while ($r_sol = $s_sol->fetch_assoc())
{
	// get the data for the row
	if (isset($r_sol["slostartdate"]))
	{
		$slostartdate = $r_sol["slostartdate"];
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

	if (isset($r_sol["sloenddate"]))
	{
		$sloenddate = $r_sol["sloenddate"];
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
	
	
	$sloid = $r_sol["sloid"];
	$avc = $myappt->session_createmac($sloid.$siteid);
	
	// output each row for this page
	print "<tr>";
	print "<td style=\"text-align: center\" class=\"matrixline\"><span class=\"tabletext\">";
	if ($priv_shours)
	{
		print "<a href=\"javascript:popupOpener('pop-editsiteopenlimit.html?sloid=" . urlencode($sloid) . "&sid=" . urlencode($siteid) . "&avc=".urlencode($avc) . "','editaex',350,500)\" title=\"Edit/delete opening date limit entry\">" . htmlentities($rnum, ENT_QUOTES, 'UTF-8') . "</a>";
	}
	else 
	{
		print htmlentities($rnum);
	}
	print "</span></td>";
	print "</tr>";

	print "<tr>";
	print '<td><p>' . htmlspecialchars((string)$slosd, ENT_QUOTES, 'UTF-8') . '</p></td>';
	print '<td><p>' . htmlspecialchars((string)$sloed, ENT_QUOTES, 'UTF-8') . '</p></td>';
	print "</tr>";

	$rnum++;
}
$s_sol->free();
$dbh->close();
print "</table>";

if ($priv_shours)
{
	$avc = $myappt->session_createmac($siteid);
?>
<p/>
<hr/>
<p/>
<form name="add_slo" method="post" action="<?php print $formfile."?sid=".$siteid."&avc=".$avc ?>"  autocomplete="off" >
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
<input type="submit" value="Add" class="inputbtn darkblue" name="submit_sol" tabindex="70">
</td>
</tr>
</table>
</form>
<?php
}
?>
</body></html>