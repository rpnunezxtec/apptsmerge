<?php
// $Id:$

// popup to edit site details
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-editmtdetail.html";
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

// Validate access to this form
if ($myappt->checktabmask(TAB_MAILTMPL) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// GET arguments: 
// mtid: mail template id (mandatory).
if (isset($_GET["mtid"]))
{
	$mtid = $_GET["mtid"];
	// check and sanitise it
	if (!is_numeric($mtid))
	{
		print "<script type=\"text/javascript\">alert('Invalid template ID.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	
	// AVC is only present when the MTID is submitted, to verify it
	if (isset($_GET["avc"]))
		$avc = $_GET["avc"];
	else
	{
		print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// Check the AVC mac for validity
	$testavc = $myappt->session_createmac($mtid);
	
	if (strcasecmp($avc, $testavc) != 0)
	{
		print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
{
	print "<script type=\"text/javascript\">alert('Template ID missing.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Read database info for form
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	// Get the template detail
	$q_t = "select * from mailtemplate "
		. "\n where mtid='".$dbh->real_escape_string($mtid)."' "
		;
	$s_t = $dbh->query($q_t);
	$r_t = $s_t->fetch_assoc();
	$mtname = $r_t["mtname"];
	
	// Process submissions here
	if (isset($_POST["submit_mt"]))
	{
		// Update an existing template - always
		// Posted info to use: mtfrom, mtsubject, mtbody
		$p_mtfrom = trim($_POST["mtfrom"]);
		$p_mtsubject = trim($_POST["mtsubject"]);
		$p_mtbody = trim($_POST["mtbody"]);
		$mtfrom_b64 = base64_encode($p_mtfrom);
		$mtsubject_b64 = base64_encode($p_mtsubject);
		$mtbody_b64 = base64_encode($p_mtbody);

		$q_t = "update mailtemplate set "
			. "\n mtfrom='".$dbh->real_escape_string($mtfrom_b64)."', "
			. "\n mtsubject='".$dbh->real_escape_string($mtsubject_b64)."', "
			. "\n mtbody='".$dbh->real_escape_string($mtbody_b64)."' "
			. "\n where mtid='".$dbh->real_escape_string($mtid)."' "
			. "\n limit 1 "
			;
				
		$s_t = $dbh->query($q_t);
		if ($s_t)
		{
			$logstring = "Template ".$mtname." (".$mtid.") updated.";
			$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITTEMPLATE);
		}
		else
		{
			$logstring = "Error updating template ".$mtname." (".$mtid.").";
			$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITTEMPLATE);
		}
	}
			
	// update calling form
	print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
	
	// Get the detail for the form
	$q_t = "select * from mailtemplate "
		. "\n where mtid='".$dbh->real_escape_string($mtid)."' "
		;
	$s_t = $dbh->query($q_t);
	$r_t = $s_t->fetch_assoc();
	$mtname = $r_t["mtname"];
	$mtfrom_b64 = $r_t["mtfrom"];
	if (!empty($mtfrom_b64))
		$mtfrom = base64_decode($mtfrom_b64);
	else
		$mtfrom = "";
	
	$mtsubject_b64 = $r_t["mtsubject"];
	if (!empty($mtsubject_b64))
		$mtsubject = base64_decode($mtsubject_b64);
	else
		$mtsubject = "";
	
	$mtbody_b64 = $r_t["mtbody"];
	if (!empty($mtbody_b64))
		$mtbody = base64_decode($mtbody_b64);
	else
		$mtbody = "";
	$s_t->free();
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

<form name="mtprops" method="post"  autocomplete="off" action="<?php print $formfile."?mtid=".urlencode($mtid)."&avc=".urlencode($avc) ?>" >
<table border="1" cellspacing="0" cellpadding="5" width="750">
<tr height="30">
<td valign="top" align="right" colspan="2"><input type="button" name="close" class="inputbtn darkblue" value="Close" onclick="javascript:window.close()" tabindex="50"></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Template Name</span></td>
<td valign="top" width="550"><span class="proptext">
<input type="text" size="60" maxlength="50" tabindex="10" name="mtname" readonly value="<?php print $mtname ?>" />
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">From</span></td>
<td valign="top"><span class="proptext">
<input type="text" size="60" maxlength="100" tabindex="20" name="mtfrom" value="<?php print $mtfrom ?>" />
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Subject</span></td>
<td valign="top"><span class="proptext">
<input type="text" size="60" maxlength="100" tabindex="30" name="mtsubject" value="<?php print $mtsubject ?>" />
</span></td>
</tr><tr>
<td valign="top"><span class="proplabel">Body</span></td>
<td valign="top"><span class="proptext">
<textarea cols="55" rows="20" tabindex="40" name="mtbody"><?php print $mtbody ?></textarea>
</span></td>
</tr><tr height="30">
<td valign="top" colspan="2"><span class="proplabel">Notes:</span><p/>
<span class="proptext">
The following substitution strings are available for use in mail templates:<br/>
%vurl% : Validation URL for appointments system. Used when there is no login for the user.<br/>
%adminname% : Name of administrator making the booking in the case of a third-paty booking.<br/>
%apptdetail% : Appointment details. Valid only where an appointment exists.<br/>
</span></td>
</tr>
</table>

<table cellSpacing="0" cellPadding="0" width="780" border="0">
<tr height="40">
<td width="150" valign="center" align="left">
<input type="submit" name="submit_mt" class="inputbtn darkblue" value="Save" tabindex="50" />
</td>
</form>
<td valign="center" align="left">&nbsp;</td>
</tr>
</table>

</body></html>