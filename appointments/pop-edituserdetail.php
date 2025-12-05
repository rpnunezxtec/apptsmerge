<?php
// $Id:$

// popup to create/edit user details
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-edituserdetail.html";
// the geometry required for this popup
$windowx = 500;
$windowy = 900;

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
// uid: user id (optional). If not supplied it is assumed a new user is being added.
if (isset($_GET["uid"]))
{
	$uid = $_GET["uid"];
	// check and sanitise it
	if (!is_numeric($uid))
	{
		print "<script type=\"text/javascript\">alert('Invalid uid.')</script>\n";
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
	$testavc = $myappt->session_createmac($uid);
	
	if (strcasecmp($avc, $testavc) != 0)
	{
		print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	
	// Check for attempts to edit own record - remove the privileges if it is
	// still OK to change the password though
	if ($uid == $myappt->session_getuuid())
	{
		$priv_user = false;
		$priv_role = false;
		$pwchange_self = true;
	}
}
else
	$uid = false;

// Read database info for form
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	// Process user submission here
	if (isset($_POST["submit_user"]))
	{
		if ($uid !== false)
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
				
				// Check for required fields: userid and uname
				if (($p_userid == "") || ($p_uname == ""))
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					// read the userid of the existing record
					$q_u = "select uid, userid from user "
						. "\n where uid='".$dbh->real_escape_string($uid)."' "
						;
					$s_u = $dbh->query($q_u);
					$r_u = $s_u->fetch_assoc();
					$olduserid = $r_u["userid"];
					$userid_save = true;
					if (strcmp($olduserid, $p_userid) != 0)
					{
						// changing userid - check for uniqueness
						$q_uid = "select uid, userid from user "
							. "\n where userid='".$dbh->real_escape_string($p_userid)."' "
							;
						$s_uid = $dbh->query($q_uid);
						$n_uid = $s_uid->num_rows;
						if ($n_uid > 0)
						{
							$userid_save = false;
							print "<script type=\"text/javascript\">alert('Userid: ".htmlentities($p_userid)." is already in use.')</script>\n";
						}
						$s_uid->free();
					}
				
					if ($userid_save)
					{
						$q_user = "update user set "
							. "\n userid='".$dbh->real_escape_string($p_userid)."', "
							. "\n uname='".$dbh->real_escape_string($p_uname)."', "
							. "\n email='".$dbh->real_escape_string($p_email)."', "
							. "\n status='".$dbh->real_escape_string($p_status)."', "
							. "\n component='".$dbh->real_escape_string($p_component)."', "
							. "\n phone='".$dbh->real_escape_string($p_phone)."' "
							. "\n where uid='".$dbh->real_escape_string($uid)."' "
							. "\n limit 1 "
							;
					}
					else
					{
						$q_user = "update user set "
							. "\n uname='".$dbh->real_escape_string($p_uname)."', "
							. "\n email='".$dbh->real_escape_string($p_email)."', "
							. "\n status='".$dbh->real_escape_string($p_status)."', "
							. "\n component='".$dbh->real_escape_string($p_component)."', "
							. "\n phone='".$dbh->real_escape_string($p_phone)."' "
							. "\n where uid='".$dbh->real_escape_string($uid)."' "
							. "\n limit 1 "
							;
					}
				
					$s_user = $dbh->query($q_user);
					if ($s_user)
					{
						$logstring = "User ".$p_uname." (".$uid.") updated.";
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITUSER);
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
					. "\n tabmask='".$new_tabs."' "
					. "\n where uid='".$dbh->real_escape_string($uid)."' "
					. "\n limit 1 "
					;
				$s_privs = $dbh->query($q_privs);
				if ($s_privs)
				{
					// get the user name
					$q_os = "select uid, uname "
						. "\n from user "
						. "\n where uid='".$uid."' "
						;
					$s_os = $dbh->query($q_os);
					$r_os = $s_os->fetch_assoc();
					$osname = $r_os["uname"];
					$logstring = "User ".$osname." (".$uid.") privileges updated.";
					$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITUSER);
					$s_os->free();
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
				
				// Check for required fields: userid and uname
				if (($p_userid == "") || ($p_uname == "") || ($p_email == ""))
				{
					print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
				}
				else
				{
					// userid - check for uniqueness
					$q_uid = "select uid, userid from user "
						. "\n where userid='".$dbh->real_escape_string($p_userid)."' "
						;
					$s_uid = $dbh->query($q_uid);
					$n_uid = $s_uid->num_rows;
					if ($n_uid > 0)
					{
						$userid_save = false;
						print "<script type=\"text/javascript\">alert('Userid: ".htmlentities($p_userid)." is already in use.')</script>\n";
					}
					else
					{
						$q_user = "insert into user "
							. "\n set "
							. "\n userid='".$dbh->real_escape_string($p_userid)."', "
							. "\n uname='".$dbh->real_escape_string($p_uname)."', "
							. "\n email='".$dbh->real_escape_string($p_email)."', "
							. "\n status='".$dbh->real_escape_string($p_status)."', "
							. "\n ucreate='".(gmdate("Y-m-d H:i:s"))."', "
							. "\n component='".$dbh->real_escape_string($p_component)."', "
							. "\n phone='".$dbh->real_escape_string($p_phone)."' "
							;
				
						$s_user = $dbh->query($q_user);
						if ($s_user)
						{
							// Get the new uid if successful
							$uid = $dbh->insert_id;
							$avc = $myappt->session_createmac($uid);
							$logstring = "User ".$p_uname." (".$uid.") created.";
							$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_NEWUSER);
						}
						else
						{
							print "<script type=\"text/javascript\">alert('Error creating new user: ".htmlentities($dbh->error).".')</script>\n";
						}
					}
				}
			}
			
			// Second part - privileges requires role privilege
			// also requires a uid from the previous step
			if ($priv_role)
			{
				if ($uid !== false)
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
						. "\n tabmask='".$new_tabs."' "
						. "\n where uid='".$dbh->real_escape_string($uid)."' "
						. "\n limit 1 "
						;
					$s_privs = $dbh->query($q_privs);
					if ($s_privs)
					{
						// get the user name
						$q_os = "select uid, uname "
							. "\n from user "
							. "\n where uid='".$uid."' "
							;
						$s_os = $dbh->query($q_os);
						$r_os = $s_os->fetch_assoc();
						$osname = $r_os["uname"];
						$logstring = "User ".$osname." (".$uid.") privileges updated.";
						$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITUSER);
						$s_os->free();
					}
				}
			}

			// update calling form
			print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
		}
	}
	elseif (isset($_POST["submit_passwd"]))
	{
		if ($uid !== false)
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
							. "\n passwd='".$newpwhash."' "
							. "\n where uid='".$dbh->real_escape_string($uid)."' "
							;
						$s_pw = $dbh->query($q_pw);
						if ($s_pw)
						{
							// get the user name
							$q_os = "select uid, uname "
								. "\n from user "
								. "\n where uid='".$uid."' "
								;
							$s_os = $dbh->query($q_os);
							$r_os = $s_os->fetch_assoc();
							$osname = $r_os["uname"];
							$logstring = "User ".$osname." (".$uid.") password changed.";
							$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_EDITUSER);
							$s_os->free();

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
	if ($uid !== false)
	{
		// Get the user detail
		$q_u = "select * from user "
			. "\n where uid='".$dbh->real_escape_string($uid)."' "
			;
		$s_u = $dbh->query($q_u);
		$n_u = $s_u->num_rows;
		if ($n_u == 0)
		{
			$s_u->free();
			$dbh->close();
			print "<script type=\"text/javascript\">alert('User not found.')</script>\n";
			print "<script type=\"text/javascript\">window.close()</script>\n";
			die();
		}
		$r_u = $s_u->fetch_assoc();
		
		$userid = $r_u["userid"];
		$uname = $r_u["uname"];
		$email = $r_u["email"];
		$status = $r_u["status"];
		$ucreate = $r_u["ucreate"];
		$component = $r_u["component"];
		$phone = $r_u["phone"];
		$tabmask = $r_u["tabmask"];
		$privilege = $r_u["privilege"];
		$lastlogin = $r_u["lastlogin"];
		$s_u->free();
	}
	else
	{
		$userid = "";
		$uname = "";
		$email = "";
		$status = "";
		$ucreate = "";
		$component = "";
		$phone = "";
		$tabmask = 0;
		$privilege = 0;
		$lastlogin = NULL;
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
?>


<div style="width:440px;display:flex;justify-content:flex-end;margin:0 0 6px;">
  <input type="button" name="close" class="inputbtn darkblue" value="Close" onclick="window.close()" tabindex="21">
</div>
<?php
if ($priv_user || $priv_role)
{
	print "<form name=\"userprops\" method=\"POST\" autocomplete=\"off\"  action=\"".$formfile.($uid === false ? "" : "?uid=".urlencode($uid)."&avc=".urlencode($avc))."\">\n";
}
if ($priv_user)
{
	// can edit general detail
?>

<table border="1" cellspacing="0" cellpadding="0" style="border-collapse:collapse;table-layout:fixed;width:440px;">
  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">User Login ID *</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <input type="text" size="36" maxlength="60" tabindex="10" name="userid"value="<?php print $userid ?>"  style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Full Name *</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <input type="text" size="36" maxlength="120" tabindex="20" name="uname" value="<?php print $uname ?>"style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Component</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <select name="component" tabindex="30"
                style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
          <?php
            $listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
            $rc = count($listcomponent);
            for ($i=0; $i<$rc; $i++) {
              if (strcasecmp($component, $listcomponent[$i][0]) == 0)
                print "<option selected value=\"{$listcomponent[$i][0]}\">{$listcomponent[$i][1]}</option>\n";
              else
                print "<option value=\"{$listcomponent[$i][0]}\">{$listcomponent[$i][1]}</option>\n";
            }
          ?>
        </select>
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Email *</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <input type="text" size="36" maxlength="60" tabindex="40" name="email"
               value="<?php print $email ?>"
               style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Status *</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <select name="status" tabindex="45"
                style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
          <?php
            $rc = count($listuserstatus);
            for ($i=0; $i<$rc; $i++) {
              if ($status == $listuserstatus[$i][0])
                print "<option selected value=\"{$listuserstatus[$i][0]}\">{$listuserstatus[$i][1]}</option>\n";
              else
                print "<option value=\"{$listuserstatus[$i][0]}\">{$listuserstatus[$i][1]}</option>\n";
            }
          ?>
        </select>
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Phone</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <input type="text" size="36" maxlength="40" tabindex="50" name="phone"
               value="<?php print $phone ?>"
               style="flex:1 1 auto;width:100%;display:block;height:24px;line-height:24px;">
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Last Login</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <span class="proptext"
              style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
          <?php print ($lastlogin ? $lastlogin : "&nbsp;") ?>
        </span>
      </div>
    </td>
  </tr>

  <tr>
    <td style="padding:0;border:1px solid #000;">
      <div style="display:flex;align-items:center;min-height:30px;padding:4px 6px;box-sizing:border-box;">
        <span class="proplabel" style="width:200px;flex:0 0 200px;margin:0;">Creation Date</span>
        <div style="align-self:stretch;width:1px;background:#a0a0a0;margin:0 8px;"></div>
        <span class="proptext"
              style="flex:1 1 auto;display:block;height:24px;line-height:24px;">
          <?php print ($ucreate ? $ucreate : "&nbsp;") ?>
        </span>
      </div>
    </td>
  </tr>

</table>


<?php
}
else 
{
	// can view general detail only
	$textstatus = "";
	$ns = count($listuserstatus);
	for ($i = 0; $i < $ns; $i++)
	{
		if ($listuserstatus[$i][0] == $status)
			$textstatus = $listuserstatus[$i][1];
	}
	
?>
<table border="1" cellspacing="0" cellpadding="5" width="440">
<tr height="30">
<td valign="top" align="right" colspan="2"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" tabindex="21"></td>
</tr><tr height="30">
<td valign="top" width="200"><span class="proplabel">User Login ID *</span></td>
<td valign="top" width="240"><span class="proptext"><?php print $userid ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Full Name *</span></td>
<td valign="top"><span class="proptext"><?php print $uname ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Component *</span></td>
<td valign="top"><span class="proptext"><?php print $component ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Email *</span></td>
<td valign="top"><span class="proptext"><?php print $email ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Status *</span></td>
<td valign="top"><span class="proptext"><?php print $textstatus ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Phone</span></td>
<td valign="top"><span class="proptext"><?php print $phone ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Last Login</span></td>
<td valign="top"><span class="proptext"><?php print $lastlogin ?></span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Creation Date</span></td>
<td valign="top"><span class="proptext"><?php print $ucreate ?></span></td>
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
<?php
//Login needs to be checked by default
$up_login = "checked"
?>
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
	print "<input type=\"submit\" name=\"submit_user\" class=\"inputbtn darkblue\" value=\"Save\">\n";
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
	print "<form name=\"passwdform\" method=\"POST\"  autocomplete=\"off\" action=\"".$formfile.($uid === false ? "" : "?uid=".urlencode($uid)."&avc=".urlencode($avc))."\">\n";
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
<td valign="top"><input type="submit" name="submit_passwd" class="inputbtn darkblue" value="Set Password" ></td>
</tr>
</table>
</form>
<?php
}

?>
</body></html>
