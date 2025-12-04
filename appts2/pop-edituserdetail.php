<?php
// $Id:$

// popup to create/edit user details
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-edituserdetail.php";
// the geometry required for this popup
$windowx = 500;
$windowy = 900;

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
if ($myappt->checktabmask(TAB_U) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Get admin privileges: create users and set user privs.
$priv_user = $myappt->checkprivilege(PRIV_UCREATE);
$priv_role = $myappt->checkprivilege(PRIV_UROLES);

// GET arguments: 
// uuid: user uuid (optional). If not supplied it is assumed a new user is being added.
if (isset($_GET["uuid"]))
{
	$uuid = $_GET["uuid"];
	// check and sanitise it
	if (strlen($uuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid uuid.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	
	// AVC is only present when the UID is submitted, to verify it
	if (isset($_GET["avc"]))
		$avc = $_GET["avc"];
	else
	{
		print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// Check the AVC mac for validity
	$testavc = $myappt->session_createmac($uuid);
	
	if (strcasecmp($avc, $testavc) != 0)
	{
		print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	
	// Check for attempts to edit own record - remove the privileges if it is
	// still OK to change the password though
	if ($uuid == $myappt->session_getuuid())
	{
		$priv_user = false;
		$priv_role = false;
		$pwchange_self = true;
	}
}
else
	$uuid = false;

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Process user submission here
	if (isset($_POST["submit_user"]))
	{
		if ($uuid !== false)
		{
			// Update an existing user
			// First part - general info requires user creation privilege
			if ($priv_user)
			{
				// Posted info to use:
				// userid, uname, email, phone
				$p_userid = trim($_POST["userid"]);
				$p_uname = trim($_POST["uname"]);
				$p_email = trim($_POST["email"]);
				$p_status = trim($_POST["status"]);
				$p_component = trim($_POST["component"]);
				$p_phone = trim($_POST["phone"]);
				if (!is_numeric($p_status))
					$p_status = USTATUS_INACTIVE;
				
				// Check for required fields: userid and uname, component
				if (($p_userid == "") || ($p_uname == "") || ($p_component == ""))
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					// read the userid of the existing record
					$userdetails = $myappt->readuserdetail($sdbh, $uuid);
					if (count($userdetails) > 0)
					{
						$olduserid = $userdetails["userid"];
						$userid_save = true;
						if (strcmp($olduserid, $p_userid) != 0)
						{
							// changing userid - check for uniqueness
							$newuserdetails = $myappt->readuserdetailbyuserid($sdbh, $p_userid);

							if (count($newuserdetails) > 0)
							{
								$userid_save = false;
								print "<script type=\"text/javascript\">alert('Userid: ".htmlentities($p_userid)." is already in use.')</script>\n";
							}
						}
					}
				
					if ($userid_save)
					{
						$q_user = "update user set "
							. "\n userid='".$sdbh->real_escape_string($p_userid)."', "
							. "\n uname='".$sdbh->real_escape_string($p_uname)."', "
							. "\n email='".$sdbh->real_escape_string($p_email)."', "
							. "\n status='".$sdbh->real_escape_string($p_status)."', "
							. "\n component='".$sdbh->real_escape_string($p_component)."', "
							. "\n phone='".$sdbh->real_escape_string($p_phone)."', "
							. "\n xsyncmts='".time()."' "
							. "\n where uuid='".$sdbh->real_escape_string($uuid)."' "
							. "\n limit 1 "
							;
					}
					else
					{
						$q_user = "update user set "
							. "\n uname='".$sdbh->real_escape_string($p_uname)."', "
							. "\n email='".$sdbh->real_escape_string($p_email)."', "
							. "\n status='".$sdbh->real_escape_string($p_status)."', "
							. "\n component='".$sdbh->real_escape_string($p_component)."', "
							. "\n phone='".$sdbh->real_escape_string($p_phone)."', "
							. "\n xsyncmts='".time()."' "
							. "\n where uuid='".$sdbh->real_escape_string($uuid)."' "
							. "\n limit 1 "
							;
					}
				
					$s_user = $sdbh->query($q_user);
					if ($s_user)
					{
						$logstring = "User ".$p_uname." (uuid: ".$uuid.") updated.";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITUSER);
					}
				}
			}
			
			// Second part - privileges requires role privilege
			if ($priv_role)
			{
				$new_priv = 0;
				$new_tabs = 0;
				
				if (isset($_POST["priv"]))
				{
					$privset = $_POST["priv"];
				
					if (in_array("login", $privset))
						$new_priv |= PRIV_LOGIN;
					if (in_array("holmap", $privset))
						$new_priv |= PRIV_HOLMAP;
					if (in_array("sstat", $privset))
						$new_priv |= PRIV_SSTAT;
					if (in_array("rpt", $privset))
						$new_priv |= PRIV_RPT;
					if (in_array("wsasgn", $privset))
						$new_priv |= PRIV_WSASGN;
					if (in_array("ucreate", $privset))
						$new_priv |= PRIV_UCREATE;
					if (in_array("shours", $privset))
						$new_priv |= PRIV_SHOURS;
					if (in_array("uroles", $privset))
						$new_priv |= PRIV_UROLES;
					if (in_array("screate", $privset))
						$new_priv |= PRIV_SCREATE;
					if (in_array("uinvite", $privset))
						$new_priv |= PRIV_UINVITE;
					if (in_array("apptsched", $privset))
						$new_priv |= PRIV_APPTSCHED;
					if (in_array("wscreate", $privset))
						$new_priv |= PRIV_WSCREATE;
					if (in_array("apptedit", $privset))
						$new_priv |= PRIV_APPTEDIT;
					if (in_array("siteedit", $privset))
						$new_priv |= PRIV_SITEEDIT;
					if (in_array("appt", $privset))
						$new_priv |= PRIV_APPT;
					if (in_array("wsstat", $privset))
						$new_priv |= PRIV_WSSTAT;
				}

				if (isset($_POST["tab"]))
				{
					$tabset = $_POST["tab"];
					
					if (in_array("user", $tabset))
						$new_tabs |= TAB_U;
					if (in_array("holmaps", $tabset))
						$new_tabs |= TAB_HOL;
					if (in_array("sites", $tabset))
						$new_tabs |= TAB_S;
					if (in_array("reports", $tabset))
						$new_tabs |= TAB_RPT;
					if (in_array("workstations", $tabset))
						$new_tabs |= TAB_WS;
					if (in_array("invite", $tabset))
						$new_tabs |= TAB_INVITE;
					if (in_array("templates", $tabset))
						$new_tabs |= TAB_MAILTMPL;
				}
				
				// update the database with the new privileges
				$q_privs = "update user set "
					. "\n privilege='".$new_priv."', "
					. "\n tabmask='".$new_tabs."', "
					. "\n xsyncmts='".time()."' "
					. "\n where uuid='".$sdbh->real_escape_string($uuid)."' "
					. "\n limit 1 "
					;
				$s_privs = $sdbh->query($q_privs);
				if ($s_privs)
				{
					$userdetails = $myappt->readuserdetail($sdbh, $uuid);
					$logstring = "User ".$userdetails["uname"]." (uuid: ".$uuid.") privileges updated.";
					$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITUSER);
				}
			}
			
			// update calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
		else
		{
			// Create a new user
			// First part - general info requires user creation privilege
			if ($priv_user)
			{
				// Posted info to use:
				// userid, uname, email, phone
				$p_userid = trim($_POST["userid"]);
				$p_uname = trim($_POST["uname"]);
				$p_email = trim($_POST["email"]);
				$p_status = trim($_POST["status"]);
				$p_component = trim($_POST["component"]);
				$p_phone = trim($_POST["phone"]);
				if (!is_numeric($p_status))
					$p_status = USTATUS_INACTIVE;

				// Create a uuid for the user record
				$uuid = $myappt->makeuniqueuuid($sdbh, "user", "uuid");
				
				// Check for required fields: userid and uname
				if (($p_userid == "") || ($p_uname == "") || ($p_email == "") || ($p_component == ""))
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					// userid - check for uniqueness
					$newuserdetails = $myappt->readuserdetailbyuserid($sdbh, $p_userid);

					if (count($newuserdetails) > 0)
					{
						$userid_save = false;
						print "<script type=\"text/javascript\">alert('Userid: ".htmlentities($p_userid)." is already in use.')</script>\n";
					}
					else
					{
						$q_user = "insert into user "
							. "\n set "
							. "\n userid='".$sdbh->real_escape_string($p_userid)."', "
							. "\n uname='".$sdbh->real_escape_string($p_uname)."', "
							. "\n email='".$sdbh->real_escape_string($p_email)."', "
							. "\n status='".$sdbh->real_escape_string($p_status)."', "
							. "\n ucreate='".(gmdate("Y-m-d H:i:s"))."', "
							. "\n component='".$sdbh->real_escape_string($p_component)."', "
							. "\n phone='".$sdbh->real_escape_string($p_phone)."', "
							. "\n uuid='".$sdbh->real_escape_string($uuid)."', "
							. "\n xsyncmts='".time()."' "
							;
				
						$s_user = $sdbh->query($q_user);
						if ($s_user)
						{
							$avc = $myappt->session_createmac($uuid);
							$logstring = "User ".$p_uname." (uuid: ".$uuid.") created.";
							$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_NEWUSER);
						}
						else
						{
							print "<script type=\"text/javascript\">alert('Error creating new user: ".htmlentities($sdbh->error).".')</script>\n";
						}
					}
				}
			}
			
			// Second part - privileges requires role privilege
			// also requires a uid from the previous step
			if ($priv_role)
			{
				if ($uuid !== false)
				{
					$new_priv = 0;
					$new_tabs = 0;
					
					if (isset($_POST["priv"]))
					{
						$privset = $_POST["priv"];
					
						if (in_array("login", $privset))
							$new_priv |= PRIV_LOGIN;
						if (in_array("holmap", $privset))
							$new_priv |= PRIV_HOLMAP;
						if (in_array("sstat", $privset))
							$new_priv |= PRIV_SSTAT;
						if (in_array("rpt", $privset))
							$new_priv |= PRIV_RPT;
						if (in_array("wsasgn", $privset))
							$new_priv |= PRIV_WSASGN;
						if (in_array("ucreate", $privset))
							$new_priv |= PRIV_UCREATE;
						if (in_array("shours", $privset))
							$new_priv |= PRIV_SHOURS;
						if (in_array("uroles", $privset))
							$new_priv |= PRIV_UROLES;
						if (in_array("screate", $privset))
							$new_priv |= PRIV_SCREATE;
						if (in_array("uinvite", $privset))
							$new_priv |= PRIV_UINVITE;
						if (in_array("apptsched", $privset))
							$new_priv |= PRIV_APPTSCHED;
						if (in_array("wscreate", $privset))
							$new_priv |= PRIV_WSCREATE;
						if (in_array("appt", $privset))
							$new_priv |= PRIV_APPT;
						if (in_array("siteedit", $privset))
							$new_priv |= PRIV_SITEEDIT;
						if (in_array("apptedit", $privset))
							$new_priv |= PRIV_APPTEDIT;
						if (in_array("wsstat", $privset))
							$new_priv |= PRIV_WSSTAT;
					}
						
					if (isset($_POST["tab"]))
					{
						$tabset = $_POST["tab"];
						
						if (in_array("user", $tabset))
							$new_tabs |= TAB_U;
						if (in_array("holmaps", $tabset))
							$new_tabs |= TAB_HOL;
						if (in_array("sites", $tabset))
							$new_tabs |= TAB_S;
						if (in_array("reports", $tabset))
							$new_tabs |= TAB_RPT;
						if (in_array("workstations", $tabset))
							$new_tabs |= TAB_WS;
						if (in_array("invite", $tabset))
							$new_tabs |= TAB_INVITE;
						if (in_array("templates", $tabset))
							$new_tabs |= TAB_MAILTMPL;
					}
				
					// update the database with the new privileges
					$q_privs = "update user set "
						. "\n privilege='".$new_priv."', "
						. "\n tabmask='".$new_tabs."', "
						. "\n xsyncmts='".time()."' "
						. "\n where uuid='".$sdbh->real_escape_string($uuid)."' "
						. "\n limit 1 "
						;
					$s_privs = $sdbh->query($q_privs);
					if ($s_privs)
					{
						$userdetails = $myappt->readuserdetail($sdbh, $uuid);
						$logstring = "User ".$userdetails["uname"]." (uuid: ".$uuid.") privileges updated.";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITUSER);
					}
				}
			}

			// update calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
	}
	elseif (isset($_POST["submit_passwd"]))
	{
		if ($uuid !== false)
		{
			// Set the user's password - requires user creation privilege
			if ($priv_user || $pwchange_self)
			{
				$passwd = $_POST["passwd"];
				$vpasswd = $_POST["verifypasswd"];
				if (strcmp($passwd, $vpasswd) == 0)
				{
					if (strlen($passwd) < PW_MINLENGTH)
					{
						print "<script type=\"text/javascript\">alert('New password must be at least ".PW_MINLENGTH." characters.')</script>\n";
					}
					else
					{
						// process the password update
						$newpwhash = $myappt->create_ssha1passwd($passwd);
						$q_pw = "update user set "
							. "\n passwd='".$newpwhash."', "
							. "\n xsyncmts='".time()."' "
							. "\n where uuid='".$sdbh->real_escape_string($uuid)."' "
							;
						$s_pw = $sdbh->query($q_pw);
						if ($s_pw)
						{
							$userdetails = $myappt->readuserdetail($sdbh, $uuid);
							$logstring = "User ".$userdetails["uname"]." (uuid: ".$uuid.") password changed.";
							$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_EDITUSER);

							print "<script type=\"text/javascript\">alert('Password change successful.')</script>\n";
						}
						else 
							print "<script type=\"text/javascript\">alert('Password change failed.')</script>\n";
					}
				}
				else
				{
					print "<script type=\"text/javascript\">alert('Password verification failed.')</script>\n";
				}
			}
		}
		else
		{
			print "<script type=\"text/javascript\">alert('No user specified.')</script>\n";
		}
	}
	
	// Get the user detail for the form
	if ($uuid !== false)
	{
		$userdetails = $myappt->readuserdetail($sdbh, $uuid);
		if (count($userdetails) == 0)
		{
			$sdbh->close();
			print "<script type=\"text/javascript\">alert('User not found.')</script>\n";
			print "<script type=\"text/javascript\">window.close()</script>\n";
			die();
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
<META http-equiv="Default-Style" content="text/html;charset=UTF-8">
<title>User Properties</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico">
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css">
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<?php
if ($priv_user || $priv_role)
{
	print "<form name=\"userprops\" method=\"POST\" autocomplete=\"off\"  action=\"".$formfile.($uuid === false ? "" : "?uuid=".urlencode($uuid)."&avc=".urlencode($avc))."\">\n";
}
if ($priv_user)
{
	// can edit general detail
?>
<table border="1" cellspacing="0" cellpadding="5" width="440">
<tr height="30">
<td valign="top" align="right" colspan="2"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" tabindex="21"></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">User Login ID *</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="36" maxlength="60" tabindex="10" name="userid" value="<?php print (isset($userdetails["userid"]) ? $userdetails["userid"] : "") ?>" />
</span></td>
</tr><tr height="30">
<td valign="top""><span class="proplabel">Full Name *</span></td>
<td valign="top""><span class="proptext">
<input type="text" size="36" maxlength="120" tabindex="20" name="uname" value="<?php print (isset($userdetails["uname"]) ? $userdetails["uname"] : "") ?>" />
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Agency *</span></td>
<td valign="top"><span class="proptext">
<select name="component" tabindex="30" style="width: 22em">
<?php
$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listcomponent);
for ($i = 0; $i < $rc; $i++)
{
	if (isset($userdetails["component"]))
	{
		if (strcasecmp($userdetails["component"], $listcomponent[$i][0]) == 0)
			print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
		else
			print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
	}
	else
		print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
}
?>
</select>
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Email *</span></td>
<td valign="top"><span class="proptext">
<input type="text" size="36" maxlength="60" tabindex="40" name="email" value="<?php print (isset($userdetails["email"]) ? $userdetails["email"] : "") ?>" />
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Status *</span></td>
<td valign="top"><span class="proptext">
<select name="status" tabindex="45" style="width: 22em">
<?php
$rc = count($listuserstatus);
for ($i = 0; $i < $rc; $i++)
{
	if (isset($userdetails["status"]))
	{
		if ($userdetails["status"] == $listuserstatus[$i][0])
			print "<option selected value=\"".$listuserstatus[$i][0]."\">".$listuserstatus[$i][1]."</option>\n";
		else
			print "<option value=\"".$listuserstatus[$i][0]."\">".$listuserstatus[$i][1]."</option>\n";
	}
	else
		print "<option value=\"".$listuserstatus[$i][0]."\">".$listuserstatus[$i][1]."</option>\n";
}	
?>
</select>
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Phone</span></td>
<td valign="top"><span class="proptext">
<input type="text" size="36" maxlength="40" tabindex="50" name="phone" value="<?php print (isset($userdetails["phone"]) ? $userdetails["phone"] : "") ?>" />
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Last Login</span></td>
<td valign="top"><span class="proptext"><?php print (isset($userdetails["lastlogin"]) ? $userdetails["lastlogin"] : "")." [".$userdetails["logincount"]."]" ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Creation Date</span></td>
<td valign="top"><span class="proptext"><?php print (isset($userdetails["ucreate"]) ? $userdetails["ucreate"] : "") ?></span></td>
</tr>
</table>
<?php
}
else 
{
	// can view general detail only
	$textstatus = "";
	$ns = count($listuserstatus);
	if (isset($userdetails["status"]))
	{
		for ($i = 0; $i < $ns; $i++)
		{
			if ($listuserstatus[$i][0] == $userdetails["status"])
				$textstatus = $listuserstatus[$i][1];
		}
	}
	else
		$textstatus = "";
?>
<table border="1" cellspacing="0" cellpadding="5" width="440">
<tr height="30">
<td valign="top" align="right" colspan="2"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" tabindex="21"></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">User Login ID *</span></td>
<td valign="top" width="240"><span class="proptext"><?php print (isset($userdetails["userid"]) ? $userdetails["userid"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Full Name *</span></td>
<td valign="top"><span class="proptext"><?php print (isset($userdetails["uname"]) ? $userdetails["uname"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Agency *</span></td>
<td valign="top"><span class="proptext"><?php print (isset($userdetails["component"]) ? $userdetails["component"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Email *</span></td>
<td valign="top"><span class="proptext"><?php print (isset($userdetails["email"]) ? $userdetails["email"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Status *</span></td>
<td valign="top"><span class="proptext"><?php print $textstatus ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Phone</span></td>
<td valign="top"><span class="proptext"><?php print (isset($userdetails["phone"]) ? $userdetails["phone"] : "") ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Last Login</span></td>
<td valign="top"><span class="proptext"><?php print (isset($userdetails["lastlogin"]) ? $userdetails["lastlogin"] : "")." [".$userdetails["logincount"]."]" ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Creation Date</span></td>
<td valign="top"><span class="proptext"><?php print (isset($userdetails["ucreate"]) ? $userdetails["ucreate"] : "") ?></span></td>
</tr>
</table>
<?php
}
if ($priv_role)
{
	// can edit user roles
	// display user privilege and tabmask bits as a set of checkboxes
	$up_login = false;
	$up_sstat = false;
	$up_wsasgn = false;
	$up_shours = false;
	$up_screate = false;
	$up_wscreate = false;
	$up_wsstat = false;
	$up_holmap = false;
	$up_rpt = false;
	$up_ucreate = false;
	$up_uroles = false;
	$up_uinvite = false;
	$up_apptsched = false;
	$up_apptedit = false;
	$up_siteedit = false;
	$up_appt = false;

	if (isset($userdetails["privilege"]))
		$privilege = $userdetails["privilege"];
	else
		$privilege = 0;

	if (isset($userdetails["tabmask"]))
		$tabmask = $userdetails["tabmask"];
	else
		$tabmask = 0;
	
	if ($privilege & PRIV_LOGIN)
		$up_login = true;
		
	if ($privilege & PRIV_SSTAT)
		$up_sstat = true;
	
	if ($privilege & PRIV_WSASGN)
		$up_wsasgn = true;
		
	if ($privilege & PRIV_SHOURS)
		$up_shours = true;
		
	if ($privilege & PRIV_SCREATE)
		$up_screate = true;
		
	if ($privilege & PRIV_WSCREATE)
		$up_wscreate = true;
	
	if ($privilege & PRIV_WSSTAT)
		$up_wsstat = true;
		
	if ($privilege & PRIV_HOLMAP)
		$up_holmap = true;
		
	if ($privilege & PRIV_RPT)
		$up_rpt = true;
		
	if ($privilege & PRIV_UCREATE)
		$up_ucreate = true;
		
	if ($privilege & PRIV_UROLES)
		$up_uroles = true;
		
	if ($privilege & PRIV_UINVITE)
		$up_uinvite = true;
		
	if ($privilege & PRIV_APPTSCHED)
		$up_apptsched = true;
		
	if ($privilege & PRIV_APPTEDIT)
		$up_apptedit = true;
	
	if ($privilege & PRIV_SITEEDIT)
		$up_siteedit = true;
		
	if ($privilege & PRIV_APPT)
		$up_appt = true;
		
	$ut_user = false;
	$ut_sites = false;
	$ut_workstations = false;
	$ut_holmaps = false;
	$ut_reports = false;
	$ut_invite = false;
	$ut_templates = false;
	
	if ($tabmask & TAB_U)
		$ut_user = true;
		
	if ($tabmask & TAB_S)
		$ut_sites = true;
		
	if ($tabmask & TAB_WS)
		$ut_workstations = true;
		
	if ($tabmask & TAB_HOL)
		$ut_holmaps = true;
		
	if ($tabmask & TAB_RPT)
		$ut_reports = true;
		
	if ($tabmask & TAB_INVITE)
		$ut_invite = true;
	
	if ($tabmask & TAB_MAILTMPL)
		$ut_templates = true;
	
	// show the checkboxes
?>
<p/>
<table cellSpacing="0" cellPadding="0" width="100%" border="0">
<tr height="30">
<td colspan="2" valign="top"><span class="lblblktext">Privileges</span></td>
</tr>
<tr height="30">
<td width="50%" valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_login ? "checked" : "") ?> value="login" ><span class="cbtext">Login</span>
</td><td width="50%" valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_holmap ? "checked" : "") ?> value="holmap" ><span class="cbtext">Create/edit holiday maps</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_sstat ? "checked" : "") ?> value="sstat" ><span class="cbtext">Set site status</span>
</td><td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_rpt ? "checked" : "") ?> value="rpt" ><span class="cbtext">Run reports</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_wsasgn ? "checked" : "") ?> value="wsasgn" ><span class="cbtext">Assign workstation to site</span>
</td><td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_ucreate ? "checked" : "") ?> value="ucreate" ><span class="cbtext">Create/edit user detail</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_shours ? "checked" : "") ?> value="shours" ><span class="cbtext">Set site working hours</span>
</td><td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_uroles ? "checked" : "") ?> value="uroles" ><span class="cbtext">Set user privileges</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_screate ? "checked" : "") ?> value="screate" ><span class="cbtext">Create sites</span>
</td><td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_uinvite ? "checked" : "") ?> value="uinvite" ><span class="cbtext">Invite users</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_wscreate ? "checked" : "") ?> value="wscreate" ><span class="cbtext">Create/edit workstations</span>
</td><td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_appt ? "checked" : "") ?> value="appt" ><span class="cbtext">Make own appointments</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_wsstat ? "checked" : "") ?> value="wsstat" ><span class="cbtext">Set workstation status</span>
</td><td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_apptsched ? "checked" : "") ?> value="apptsched" ><span class="cbtext">View site appointment schedule</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_apptedit ? "checked" : "") ?> value="apptedit" ><span class="cbtext">View/delete any appointment</span>
</td><td valign="top">
<input type="checkbox" name="priv[]" <?php print ($up_siteedit ? "checked" : "") ?> value="siteedit" ><span class="cbtext">Edit site detail</span>
</td>
</tr>
</table>
<p/>
<table cellSpacing="0" cellPadding="0" width="100%" border="0">
<tr height="30">
<td colspan="2" valign="top"><span class="lblblktext">Tabs</span></td>
</tr>
<tr height="30">
<td width="50%" valign="top">
<input type="checkbox" name="tab[]" <?php print ($ut_user ? "checked" : "") ?> value="user" ><span class="cbtext">User tab</span>
</td><td width="50%" valign="top">
<input type="checkbox" name="tab[]" <?php print ($ut_holmaps ? "checked" : "") ?> value="holmaps" ><span class="cbtext">Holidays tab</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" name="tab[]" <?php print ($ut_sites ? "checked" : "") ?> value="sites" ><span class="cbtext">Sites tab</span>
</td><td valign="top">
<input type="checkbox" name="tab[]" <?php print ($ut_reports ? "checked" : "") ?> value="reports" ><span class="cbtext">Reports tab</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" name="tab[]" <?php print ($ut_workstations ? "checked" : "") ?> value="workstations" ><span class="cbtext">Workstations tab</span>
</td><td valign="top">
<input type="checkbox" name="tab[]" <?php print ($ut_invite ? "checked" : "") ?> value="invite" ><span class="cbtext">Invite tab</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" name="tab[]" <?php print ($ut_templates ? "checked" : "") ?> value="templates" ><span class="cbtext">Mail Templates tab</span>
</td><td valign="top">

</td>
</tr>
</table>
<p/>
<?php
}
else 
{
	// Display the user privilege status only
	$up_login = false;
	$up_sstat = false;
	$up_wsasgn = false;
	$up_shours = false;
	$up_screate = false;
	$up_wscreate = false;
	$up_wsstat = false;
	$up_holmap = false;
	$up_rpt = false;
	$up_ucreate = false;
	$up_uroles = false;
	$up_uinvite = false;
	$up_apptsched = false;
	$up_apptedit = false;
	$up_siteedit = false;
	$up_appt = false;

	if (isset($userdetails["privilege"]))
		$privilege = $userdetails["privilege"];
	else
		$privilege = 0;

	if (isset($userdetails["tabmask"]))
		$tabmask = $userdetails["tabmask"];
	else
		$tabmask = 0;
	
	if ($privilege & PRIV_LOGIN)
		$up_login = true;
		
	if ($privilege & PRIV_SSTAT)
		$up_sstat = true;
	
	if ($privilege & PRIV_WSASGN)
		$up_wsasgn = true;
		
	if ($privilege & PRIV_SHOURS)
		$up_shours = true;
		
	if ($privilege & PRIV_SCREATE)
		$up_screate = true;
		
	if ($privilege & PRIV_WSCREATE)		
		$up_wscreate = true;
	
	if ($privilege & PRIV_WSSTAT)
		$up_wsstat = true;
		
	if ($privilege & PRIV_HOLMAP)
		$up_holmap = true;
		
	if ($privilege & PRIV_RPT)
		$up_rpt = true;
		
	if ($privilege & PRIV_UCREATE)
		$up_ucreate = true;
		
	if ($privilege & PRIV_UROLES)
		$up_uroles = true;
		
	if ($privilege & PRIV_UINVITE)
		$up_uinvite = true;
		
	if ($privilege & PRIV_APPTSCHED)
		$up_apptsched = true;
		
	if ($privilege & PRIV_APPTEDIT)
		$up_apptedit = true;
	
	if ($privilege & PRIV_SITEEDIT)
		$up_siteedit = true;
		
	if ($privilege & PRIV_APPT)
		$up_appt = true;
		
	$ut_user = false;
	$ut_sites = false;
	$ut_workstations = false;
	$ut_holmaps = false;
	$ut_reports = false;
	$ut_invite = false;
	$ut_templates = false;
	
	if ($tabmask & TAB_U)
		$ut_user = true;
		
	if ($tabmask & TAB_S)
		$ut_sites = true;
		
	if ($tabmask & TAB_WS)
		$ut_workstations = true;
		
	if ($tabmask & TAB_HOL)
		$ut_holmaps = true;
		
	if ($tabmask & TAB_RPT)
		$ut_reports = true;

	if ($tabmask & TAB_INVITE)
		$ut_invite = true;
	
	if ($tabmask & TAB_MAILTMPL)
		$ut_templates = true;
		
?>
<p/>
<table cellSpacing="0" cellPadding="0" width="100%" border="0">
<tr height="30">
<td colspan="2" valign="top"><span class="lblblktext">Privileges</span></td>
</tr>
<tr height="30">
<td width="50%" valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_login ? "checked" : "") ?> value="login" ><span class="cbtext">Login</span>
</td><td width="50%" valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_holmap ? "checked" : "") ?> value="holmap" ><span class="cbtext">Create/edit holiday maps</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_sstat ? "checked" : "") ?> value="sstat" ><span class="cbtext">Set site status</span>
</td><td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_rpt ? "checked" : "") ?> value="rpt" ><span class="cbtext">Run reports</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_wsasgn ? "checked" : "") ?> value="wsasgn" ><span class="cbtext">Assign workstation to site</span>
</td><td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_ucreate ? "checked" : "") ?> value="ucreate" ><span class="cbtext">Create/edit user detail</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_shours ? "checked" : "") ?> value="shours" ><span class="cbtext">Set site working hours</span>
</td><td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_uroles ? "checked" : "") ?> value="uroles" ><span class="cbtext">Set user privileges</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_screate ? "checked" : "") ?> value="screate" ><span class="cbtext">Create sites</span>
</td><td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_uinvite ? "checked" : "") ?> value="uinvite" ><span class="cbtext">Invite users</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_wscreate ? "checked" : "") ?> value="wscreate" ><span class="cbtext">Create/edit workstations</span>
</td><td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_appt ? "checked" : "") ?> value="appt" ><span class="cbtext">Make own appointments</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_wsstat ? "checked" : "") ?> value="wsstat" ><span class="cbtext">Set workstation status</span>
</td><td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_apptsched ? "checked" : "") ?> value="apptsched" ><span class="cbtext">View site appointment schedule</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_apptedit ? "checked" : "") ?> value="apptedit" ><span class="cbtext">View/delete any appointment</span>
</td><td valign="top">
<input type="checkbox" disabled name="priv[]" <?php print ($up_siteedit ? "checked" : "") ?> value="siteedit" ><span class="cbtext">Edit site detail</span>
</td>
</tr>
</table>
<p/>
<table cellSpacing="0" cellPadding="0" width="100%" border="0">
<tr height="30">
<td colspan="2" valign="top"><span class="lblblktext">Tabs</span></td>
</tr>
<tr height="30">
<td width="50%" valign="top">
<input type="checkbox" disabled name="tab[]" <?php print ($ut_user ? "checked" : "") ?> value="user" ><span class="cbtext">User tab</span>
</td><td width="50%" valign="top">
<input type="checkbox" disabled name="tab[]" <?php print ($ut_holmaps ? "checked" : "") ?> value="holmaps" ><span class="cbtext">Holidays tab</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" disabled name="tab[]" <?php print ($ut_sites ? "checked" : "") ?> value="sites" ><span class="cbtext">Sites tab</span>
</td><td valign="top">
<input type="checkbox" disabled name="tab[]" <?php print ($ut_reports ? "checked" : "") ?> value="reports" ><span class="cbtext">Reports tab</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" disabled name="tab[]" <?php print ($ut_workstations ? "checked" : "") ?> value="workstations" ><span class="cbtext">Workstations tab</span>
</td><td valign="top">
<input type="checkbox" disabled name="tab[]" <?php print ($ut_invite ? "checked" : "") ?> value="invite" ><span class="cbtext">Invite tab</span>
</td>
</tr><tr height="30">
<td valign="top">
<input type="checkbox" disabled name="tab[]" <?php print ($ut_templates ? "checked" : "") ?> value="templates" ><span class="cbtext">Mail Templates tab</span>
</td><td valign="top">

</td>
</tr>
</table>
<p/>
<?php
}

// Add the buttons if privileges allow
if ($priv_user || $priv_role)
{
	// submit button and end of form
	print "<table cellSpacing=\"0\" cellPadding=\"0\" width=\"440\" border=\"0\">\n";
	print "<tr height=\"40\">\n";
	print "<td width=\"200\" valign=\"center\" align=\"left\">\n";
	print "<input type=\"submit\" name=\"submit_user\" class=\"btntext\" value=\"Save\">\n";
	print "</form>\n";
	print "</td>\n";
	print "<td width=\"240\" valign=\"center\" align=\"left\">\n";
	print "<span class=\"proplabel\">* Required items.</span>\n";
	print "</td>\n";
	print "</tr>\n";
	print "</table>\n";
}
?>
<p/>
<?php
if ($priv_user || $pwchange_self)
{
	// can set user passwords
	print "<form name=\"passwdform\" method=\"POST\"  autocomplete=\"off\" action=\"".$formfile.($uuid === false ? "" : "?uuid=".urlencode($uuid)."&avc=".urlencode($avc))."\">\n";
?>
<hr/>
<p/>
<table cellSpacing="0" cellPadding="0" width="440" border="0">
<tr height="30">
<td colspan="2" valign="top"><span class="lblblktext">Set User Password</span></td>
</tr>
<tr height="30">
<td width="200" valign="top"><span class="proplabel">Set Password</span></td>
<td width="240" valign="top"><input type="password" name="passwd" value="" size="30" maxlength="40"></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Verify Password</span></td>
<td valign="top"><input type="password" name="verifypasswd" value="" size="30" maxlength="40"></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">&nbsp;</span></td>
<td valign="top"><input type="submit" name="submit_passwd" class="btntext" value="Set Password" ></td>
</tr>
</table>
</form>
<?php
}

?>
</body></html>