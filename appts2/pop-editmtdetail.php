<?php
// $Id:$

// popup to edit site details
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-editmtdetail.php";
// the geometry required for this popup
$windowx = 800;
$windowy = 800;

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

// Validate access to this form
if ($myappt->checktabmask(TAB_MAILTMPL) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// GET arguments: 
// mtuuid: mail template uuid (mandatory).
if (isset($_GET["mtuuid"]))
{
	$mtuuid = $_GET["mtuuid"];
	// check and sanitise it
	if (strlen($mtuuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid template ID.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	
	// AVC is only present when the MTUUID is submitted, to verify it
	if (isset($_GET["avc"]))
		$avc = $_GET["avc"];
	else
	{
		print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// Check the AVC mac for validity
	$testavc = $myappt->session_createmac($mtuuid);
	
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
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Get the template detail
	$templatedetails = $myappt->readmailtemplate($sdbh, $mtuuid);
	$mtname = $templatedetails["mtname"];
	
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
			. "\n mtfrom='".$sdbh->real_escape_string($mtfrom_b64)."', "
			. "\n mtsubject='".$sdbh->real_escape_string($mtsubject_b64)."', "
			. "\n mtbody='".$sdbh->real_escape_string($mtbody_b64)."', "
			. "\n xsyncmts='".time()."' "
			. "\n where mtuuid='".$sdbh->real_escape_string($mtuuid)."' "
			. "\n limit 1 "
			;
				
		$s_t = $sdbh->query($q_t);
		if ($s_t)
		{
			$logstring = "Template ".$mtname." (mtuuid: ".$mtuuid.") updated.";
			$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITTEMPLATE);
		}
		else
		{
			$logstring = "Error updating template ".$mtname." (mtuuid: ".$mtuuid.").";
			$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITTEMPLATE);
		}
	}
			
	// update calling form
	print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
	
	// decode some of the detail for the form
	if (isset($templatedetails["mtfrom"]))
		$templatedetails["dec_mtfrom"] = base64_decode($templatedetails["mtfrom"]);
	else
		$templatedetails["dec_mtfrom"] = "";

	if (isset($templatedetails["mtsubject"]))
		$templatedetails["dec_mtsubject"] = base64_decode($templatedetails["mtsubject"]);
	else
		$templatedetails["dec_mtsubject"] = "";

	if (isset($templatedetails["mtbody"]))
		$templatedetails["dec_mtbody"] = base64_decode($templatedetails["mtbody"]);
	else
		$templatedetails["dec_mtbody"] = "";

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
<title>Mail Template Properties</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<form name="mtprops" method="post"  autocomplete="off" action="<?php print $formfile."?mtuuid=".urlencode($mtuuid)."&avc=".urlencode($avc) ?>" >
<table border="1" cellspacing="0" cellpadding="5" width="750">
<tr height="30">
<td valign="top" align="right" colspan="2"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" tabindex="50"></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">Template Name</span></td>
<td valign="top" width="550"><span class="proptext">
<input type="text" size="60" maxlength="50" tabindex="10" name="mtname" readonly value="<?php print $templatedetails["mtname"] ?>" />
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">From</span></td>
<td valign="top"><span class="proptext">
<input type="text" size="60" maxlength="100" tabindex="20" name="mtfrom" value="<?php print$templatedetails["dec_mtfrom"] ?>" />
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Subject</span></td>
<td valign="top"><span class="proptext">
<input type="text" size="60" maxlength="100" tabindex="30" name="mtsubject" value="<?php print $templatedetails["dec_mtsubject"] ?>" />
</span></td>
</tr><tr>
<td valign="top"><span class="proplabel">Body</span></td>
<td valign="top"><span class="proptext">
<textarea cols="55" rows="20" tabindex="40" name="mtbody"><?php print $templatedetails["dec_mtbody"] ?></textarea>
</span></td>
</tr><tr height="30">
<td valign="top" colspan="2"><span class="proplabel">Notes:</span><p/>
<span class="proptext">
The following substitution strings are available fro use in mail templates:<br/>
%vurl% : Validation URL for appointments system. Used when there is no login for the user.<br/>
%adminname% : Name of administrator making the booking in the case of a third-paty booking.<br/>
%apptdetail% : Appointment details. Valid only where an appointment exists.<br/>
</span></td>
</tr>
</table>

<table cellSpacing="0" cellPadding="0" width="780" border="0">
<tr height="40">
<td width="150" valign="center" align="left">
<input type="submit" name="submit_mt" class="btntext" value="Save" tabindex="50" />
</td>
</form>
<td valign="center" align="left">&nbsp;</td>
</tr>
</table>

</body></html>