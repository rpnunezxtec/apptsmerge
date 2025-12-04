<?php
include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-store,no-Cache" />
<title>Authentx Credential Management System</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
</head>
<body>
<script language="javascript">
if (top.frames.length!=0)
	top.location=self.document.location;
</script>
<br/><br/><br/><br/>&nbsp;<br/>
<table cellspacing="0" cellpadding="0" align="center" border="0">
<tr><td><img height="12" src="../appcore/images/logoslice_1.gif" width="200" border="0" /></td>
</tr>
<tr><td valign="top" background="../appcore/images/box_mtl_ctr.gif">
<table cellspacing="0" cellpadding="0" border="0">
<tr height="12"><td width="12" height="12"><img height="12" src="../appcore/images/box_mtl_topl.gif" width="12" /></td>
<td background="../appcore/images/box_mtl_top.gif"><img height="12" src="../appcore/images/logoslice_2.gif" width="188" border="0" /></td>
<td width="12"><img height="12" src="../appcore/images/box_mtl_topr.gif" width="12" /></td>
</tr>
<tr valign="top">
<td width="12" background="../appcore/images/box_mtl_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td align="middle" background="../appcore/images/bg_spacer.gif">
<table cellspacing="0" cellpadding="0" border="0">
<tr><td align="middle">
<table cellspacing="0" cellpadding="0" width="100%" border="0">
<tr><td align="left" width="220"><a href="<?php print $page_logout ?>"><img height="56" src="../appcore/images/logoslice_3.gif" width="188" border="0" /></a></td>
<td align="right">
<table cellspacing="0" cellpadding="0" width="100%" border="0">
<tr><td align="left" width="78%">&nbsp;</td>
<td align=right>&nbsp;</td>
<td align="right">&nbsp;</td>
<td align="right">&nbsp;</td>
</tr></table>
</td></tr>
<tr height="8" valign="top">
<td></td>
<td></td>
</tr></table>
</td></tr>
<tr><td align="center" valign="bottom">
<table cellspacing="0" cellpadding="0" width="100%" border="0">
<tr height="2"><td width="2" height="2"><img height="2" src="../appcore/images/bevel_topl.gif" width="2" /></td>
<td background="../appcore/images/bevel_top.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_topr.gif" width="2" /></td>
</tr>
<tr><td width="2" background="../appcore/images/bevel_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td valign="center" align="left">
<table border="0" cellspacing="0" cellpadding="10" width="430" bgcolor="#ffffff">
<tr><td align="left">
<form method="post" action="validate.php" autocomplete="off" >
<?php
if ($_accessdisabled === false)
{
	$demoexpired = false;
	if (DEMO_ACTIVATE === true)
	{
		// Check for demo expiration
		$demoexpired = $myappt->demo_check_expired();
		$demodays = $myappt->demo_check_days();
	}

	if ($demoexpired === true)
	{
		// Show the demo expiration form instead
?>
<table cellspacing="0" cellpadding="10" width="400" border="0">
<tr height="100">
<td colspan="2" valign="center" align="center"">
<span class="siteheading" align="center">Demonstration period has expired.</span>
</td>
</tr>
</table>
<?php
	}
	else
	{
		if ($_allowselfregister === true)
		{
?>
<table class="tableborders" width="400" cellpadding="7">
<tr>
<td width="50%" valign="top" class="cell_rhline">
<span class="blacktext">User ID:</span><br/>
<input type="text" name="userid" value="" size="25" maxlength="100" tabindex="1" />
</td>
<td valign="top" rowspan="3" width="50%">
<span class="smlblacktext">
In order to use the appointments system, please either register by clicking the Register button below,
or log in using your existing userid and password.
</span>
</td>
</tr>
<tr>
<td valign="top" class="cell_rhline">
<span class="blacktext">Password:</span><br>
<input type="password" name="passwd" value="" size="25" maxlength="100" tabindex="2" />
</td>
</tr>
<tr>
<td valign="top" class="cell_rhline"><a href="javascript:popupOpener('pop-forgotpasswd.php','poppasswd',520,300)"><span class="lblblutext">Forgot password ...</span></a></td>
</tr>
<tr>
<td valign="top" class="cell_rhline">
<input type="submit" name="submit" class="btntext" value="Login" tabindex="4" />
</td>
<td valign="top">
<input type="button" class="btntext" name="btn_register" value="Register..." tabindex="100" title="Register to use the appointments system" onclick="javascript:popupOpener('pop-register.php','pop_register',400,600)" />
</td>
</tr>
</table>
<?php
		}
		else
		{
			// Can't self-register, so show the regular login form
?>
<table cellspacing="0" cellpadding="10" width="400" border="0">
<tr height="25">
<td width="40%" valign="top" align="right"><span class="blacktext">User ID:</span></td>
<td valign="top" align="left" width="60%"><input type="text" name="userid" value="" size="20" maxlength="100" tabindex="1" /></td>
</tr>
<tr height="25">
<td valign="top" align="right"><span class="blacktext">Password:</span></td>
<td valign="top" align="left"><input type="password" name="passwd" value="" size="20" maxlength="100" tabindex="2" /></td>
</tr>
<tr height="25">
<td valign="top" align="right">&nbsp;</td>
<td valign="top" align="left">&nbsp;</td>
</tr>
<tr>
<td valign="top" align="right">&nbsp;</td>
<td valign="top" align="left"><input type="submit" name="submit" class="btntext" value="Login" tabindex="4" /></td>
</tr>
</table>
<?php
		}
	}
}
else
{
?>
<table cellspacing="0" cellpadding="10" width="400" border="0">
<tr height="100">
<td colspan="2" valign="center" align="center"">
<span class="siteheading" align="center"><?php print htmlentities($_accessdisabledmessage) ?></span>
</td>
</tr>
</table>
<?php
}
?>
</form>
</td></tr></table>
</td>
<td width="2" background="../appcore/images/bevel_r.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
</tr>
<tr height="2">
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_botl.gif" width="2"></td>
<td background="../appcore/images/bevel_bot.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_botr.gif" width="2"></td>
</tr>
</table>
</tr>
<tr>
<td align="center" valign="bottom">
<table cellspacing="0" cellpadding="0" width="100%" border="0">
<tr>
<td width="70%" align="left">
<span class="siteheading">
<?php
print SITEHEADING;
if (DEMO_ACTIVATE)
	print " Demo: ".$demodays." days left";
?>
</span></td>
<td width="30%" align="right">
<span align="right"><img height="25" src="../appcore/images/AuthentX-logo-plain-gray6.gif" width="94" /></span>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
<td width="12" background="../appcore/images/box_mtl_r.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
</tr>
<tr height="14">
<td width="12" height="14"><img height="14" src="../appcore/images/box_mtl_botl.gif" width="12" /></td>
<td valign="top" background="../appcore/images/box_mtl_bot.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="12"><img height="14" src="../appcore/images/box_mtl_botr.gif" width="12" /></td>
</tr>
</table>
</td>
</tr>
</table>
<br/>
<table width="100%">

<tr>
<td width="10%">&nbsp;</td>
<td width="80%" align="center">
<a href="frm-centerlist.php">Open Centers Page</a>
</td>
<td width="10%">&nbsp;</td></tr>

<tr>
<td width="10%">&nbsp;</td>
<td width="80%" align="center">
<?php
// Output the privacy text if any
if (file_exists(PRIVACY_INFO_FILE))
	$p = file_get_contents(PRIVACY_INFO_FILE);
else
	$p = "";

print $p;

?>
</td>
<td width="10%">&nbsp;</td></tr>

<tr><td colspan="3">&nbsp;</td></tr>

<tr><td>&nbsp;</td><td><span class="smlblucnttxt">&nbsp;</span></td><td>&nbsp;</td></tr>
</table>
</body></html>