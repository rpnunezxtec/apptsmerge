<?php
// $Id:$

// popup to self-register
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-register.html";
// the geometry required for this popup
$windowx = 800;
$windowy = 800;

include("config.php");
include_once("vec-clappointments.php");
include_once("../appcore/vec-clmail.php");
require_once('../appcore/vec-clforms.php');

$myappt = new authentxappointments();
$mymail = new authentxmail();
$myform = new authentxforms();

date_default_timezone_set(DATE_TIMEZONE);

$process_user = false;

if ($_allowselfregister !== true)
{
	print "<script type=\"text/javascript\">alert('Self registration not permitted.')</script>\n";
	print "<script type=\"text/javascript\">self.close()</script>\n";
	die();
}

// Read database info for form
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	$et_register = $myappt->getmailtemplate($dbh, MTDB_REGISTER);
	
	// Process registration submission here
	if (isset($_POST["submit_user"]))
	{
		$avcpass = false;
		if (isset($_GET["avc"]))
		{
			$pavc = trim($_GET["avc"]);
			$avcstring = $myappt->session_getvar("avcstring");
			$testavc = $myappt->session_createmac($avcstring);
			if (strcasecmp($pavc, $testavc) == 0)
				$avcpass = true;
		}
		
		if ($avcpass)
		{
			// Create a new user
			// First part - general info
	
			// Posted info to use:
			// uname, email, phone
			//$p_userid = $_POST["userid"];
			$p_uname = filter_var(trim($_POST["uname"]), FILTER_SANITIZE_STRING, (FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK));
			$p_email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
			$p_email = strip_tags($p_email);
			if (strpos($p_email, '@') === false)
				$p_email = "";
			$p_userid = $p_email;		// Set userID as the email address for activation email
			$p_component = filter_var(trim($_POST["component"]), FILTER_SANITIZE_STRING, (FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK));
			$p_component = strip_tags($p_component);
			$p_phone = filter_var(trim($_POST["phone"]), FILTER_SANITIZE_STRING, (FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK));
			$p_phone = strip_tags($p_phone);
			$p_passwd = trim($_POST["passwd"]);
			$p_vpasswd = trim($_POST["verifypasswd"]);
			
			if ($_selfregister_emailactivation === true)
				$p_status = USTATUS_UNACTIVATED;
			else
				$p_status = USTATUS_ACTIVE;
					
			// Check for required fields: userid and uname, and passwords
			if (($p_userid == "") || ($p_uname == "") || ($p_passwd == "") || ($p_vpasswd == "") || ($p_email == ""))
			{
				print "<script type=\"text/javascript\">alert('Required fields are missing.')</script>\n";
			}
			else
			{
				// userid - check for uniqueness
				$q_uid = "select uid, "
						. "\n userid, "
						. "\n email "
						. "\n from user "
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
					// Check password
					if (strlen($p_passwd) < PW_MINLENGTH)
					{
						print "<script type=\"text/javascript\">alert('Password must be at least ".PW_MINLENGTH." characters.')</script>\n";
						$process_user = false;
					}
					else
					{
						if (strcmp($p_passwd, $p_vpasswd) == 0)
						{
							$pwhash = $myappt->create_ssha1passwd($p_passwd);
	
							// Check anti-spam image contents
							$p_scode = trim($_POST["securitycode"]);
							$orig_scode = $myappt->session_getvar("scode_text");
							
							if (strcasecmp($p_scode, $orig_scode) == 0)
							{
								// check component
								if ($p_component !== "")
									$process_user = true;
								else
								{
									print "<script type=\"text/javascript\">alert('Agency/Component cannot be empty.')</script>\n";
									$process_user = false;
								}
							}
							else 
							{
								print "<script type=\"text/javascript\">alert('Security code entered incorrectly.')</script>\n";
								$process_user = false;
							}
						}
						else 
						{
							print "<script type=\"text/javascript\">alert('Password verification failed.')</script>\n";
							$process_user = false;
						}
					}
				
					$uid = false;
					if ($process_user === true)
					{
						$q_user = "insert into user set "
							. "\n userid='".$dbh->real_escape_string($p_userid)."', "
							. "\n uname='".$dbh->real_escape_string($p_uname)."', "
							. "\n passwd='".$dbh->real_escape_string($pwhash)."', "
							. "\n email='".$dbh->real_escape_string($p_email)."', "
							. "\n status='".$p_status."', "
							. "\n ucreate='".(gmdate("Y-m-d H:i:s"))."', "
							. "\n component='".$dbh->real_escape_string($p_component)."', "
							. "\n phone='".$dbh->real_escape_string($p_phone)."' "
							;
					
						$s_user = $dbh->query($q_user);
						if ($s_user)
						{
							// Get the new uid if successful
							$uid = $dbh->insert_id;
							$logstring = "User ".$p_uname." (".$uid.") created [self register].";
							$myappt->createlogentry($dbh, $logstring, 0, ALOG_NEWUSER);
						}
						else
						{
							print "<script type=\"text/javascript\">alert('Error creating new user: ".htmlentities($dbh->error).".')</script>\n";
						}
					}
				
					// Second part - privileges requires role privilege
					// also requires a uid from the previous step
					if ($uid !== false)
					{
						$new_priv = PRIV_APPT | PRIV_LOGIN;
						$new_tabs = 0;
								
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
							$s_os->free();
							$logstring = "User ".$osname." (".$uid.") privileges updated [self register].";
							$myappt->createlogentry($dbh, $logstring, 0, ALOG_EDITUSER);
							
							// Send the activation email if enabled
							if ($_selfregister_emailactivation === true)
							{
								$notifymessage = "";
								$vc = $myappt->buildquerystring($p_uname, $p_email);
								// email the invitation
								$mailbody = $et_register["mtbody"];
								$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
								$mail_adminname = $myappt->session_getuuname();
								$mailbody = str_ireplace(ET_ADMINNAME, $mail_adminname, $mailbody);
								$mailbody = str_ireplace(ET_VURL, $mail_vurl, $mailbody);
								$mailbody = wordwrap($mailbody, 70);
									
								$mymail->from = $et_register["mtfrom"];
								$mymail->clearaddresses();
								$mymail->addaddress($p_email);
								if (empty($et_register["mtsubject"]))
									$mymail->subject = "Appointments system registration activation.";
								else
									$mymail->subject = $et_register["mtsubject"];
								$mymail->body = $mailbody;
								
								if (strcasecmp(MAILER, "mail") == 0)
									$mymail->ismail();
								elseif (strcasecmp(MAILER, "qmail") == 0)
								$mymail->isqmail();
								elseif (strcasecmp(MAILER, "smtp") == 0)
								{
									$mymail->issmtp();
									$mymail->smtphost = SMTP_SERVER;
									$mymail->smtpport = SMTP_PORT;
									$mymail->smtpauth = SMTP_AUTH;
									$mymail->smtpuser = SMTP_AUTHUSER;
									$mymail->smtppassword = SMTP_AUTHPASSWD;
								}
								
								$mymail->send();
								$m = !$mymail->iserror();
								
								if ($m === true)
								{
									// create a log entry
									$logstring = "Registration activation email sent for user ".$p_uname."(".$p_email.").";
									$myappt->createlogentry($dbh, $logstring, $uid, ALOG_INVITEUSER);
										
									$notifymessage .= "Registration activation email sent.";
								}
								else
									$notifymessage .= "Mail error in sending registration activation: ".$mymail->mailerror.".";
								
							}
							else
								$notifymessage = "User account created.";
						}
						
						// Close the popup with a message
						print "<script type=\"text/javascript\">alert('".$notifymessage."')</script>\n";
						print "<script type=\"text/javascript\">self.close()</script>\n";
						die();
					}
				}
			}
		
			$userid = $p_userid;
			$uname = $p_uname;
			$email = $p_email;
			$phone = $p_phone;
			$component = $p_component;
		}
		else
		{
			print "<script type=\"text/javascript\">alert('Verification failed - user not created.')</script>\n";
			$userid = "";
			$uname = "";
			$email = "";
			$phone = "";
			$component = "";
		}
	}
	else 
	{
		$userid = "";
		$uname = "";
		$email = "";
		$phone = "";
		$component = "";
	}
	$dbh->close();
}
else 
{
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Create a new security code and save into the session.
$rstring = md5(microtime() * time());
$rstring = substr($rstring, 0, 5);
$myappt->session_setvar("scode_text", $rstring);
$i = rand(1, 4);
$myappt->session_setvar("scode_background", "../appcore/images/scode_bg".$i.".png");

// Save a random value and generate an avc for later checking
$avcstring = md5(microtime() * time());
$myappt->session_setvar("avcstring", $avcstring);
$avc = $myappt->session_createmac($avcstring);

// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams['css'] = $cfg_stdcss;
$headparams["jscript_file"] = $cfg_stdjscript;
// $headparams["jscript_file"][] = "../appcore/scripts/js-tablesort.js";
// $headparams["jscript_file"][] = "../appcore/scripts/js-checkall.js";
// $headparams["jscript_file"][] = "../appcore/scripts/js-expandall.js";
$myform->frmrender_head($headparams);

$bodyparams = array();
$bodyparams["id"] = "popup";
$myform->frmrender_bodytag($bodyparams);

print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n";
// The page container
print "<div class=\"main\">\n";

?>


<form name="userregister" method="post"  autocomplete="off" action="<?php print $formfile."?avc=".urlencode($avc) ?>">
<?php
	$myform->frmrender_popclose();
?>
	<div class="smlblacktext" style="display:flex; align-items:center; gap:12px; margin:12px 0 16px; padding:12px; border:1px solid #d0d7de; border-radius:8px;">
	STEP 1: Enter your name and contact information. This information is used to confirm your appointment via
	email/phone (appointments, cancellations, etc.) Your Login ID will be your email address.
	</div>
	<div style="display:flex; align-items:center; gap:12px; margin:10px 0; padding:12px; border:1px solid #d0d7de; border-radius:8px;">
		<label for="uname" class="proplabel" style="margin:0; min-width:14rem; white-space:nowrap;">Full Name *</label>
		<div class="proptext" style="flex:1;">
			<input id="uname" type="text" size="36" maxlength="120" tabindex="10"name="uname"value="<?php print $uname ?>" style="width:100%; max-width:36ch;">
		</div>
	</div>
	<div style="display:flex; align-items:center; gap:12px; margin:10px 0; padding:12px; border:1px solid #d0d7de; border-radius:8px;">
		<label for="email" class="proplabel" style="margin:0; min-width:14rem; white-space:nowrap;">Email *</label>
		<div class="proptext" style="flex:1;">
			<input id="email" type="text" size="36" maxlength="60" tabindex="20" name="email" value="<?php print $email ?>" style="width:100%; max-width:36ch;">
		</div>
	</div>
	<div style="display:flex; align-items:center; gap:12px; margin:10px 0; padding:12px; border:1px solid #d0d7de; border-radius:8px;">
		<label for="phone" class="proplabel" style="margin:0; min-width:14rem; white-space:nowrap;">Phone</label>
		<div class="proptext" style="flex:1;">
			<input id="phone" type="text" size="36" maxlength="40" tabindex="30" name="phone" value="<?php print $phone ?>" style="width:100%; max-width:36ch;">
		</div>
	</div>
	<div style="display:flex; align-items:center; gap:12px; margin:10px 0; padding:12px; border:1px solid #d0d7de; border-radius:8px;">
		<label for="component" class="proplabel" style="margin:0; min-width:14rem; white-space:nowrap;">Agency/Component *</label>
		<div class="proptext" style="flex:1;">
			<select id="component" name="component" tabindex="40" style="width:100%; max-width:36ch;">
			<?php
			$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
			$rc = count($listcomponent);
			for ($i = 0; $i < $rc; $i++) {
				if (strcasecmp($component, $listcomponent[$i][0]) == 0)
				print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
				else
				print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
			}
			?>
			</select>
		</div>
	</div>
	<div class="smlblacktext" style="margin:16px 0 8px; padding:10px 12px; border:1px solid #d0d7de; border-radius:8px; background:#f7f9fc;">
		STEP 2: Create a User Password
	</div>
	<div style="display:flex; align-items:center; gap:12px; margin:10px 0; padding:12px; border:1px solid #d0d7de; border-radius:8px;">
		<label for="passwd" class="proplabel" style="margin:0; min-width:14rem; white-space:nowrap;">Password *</label>
		<div class="proptext" style="flex:1;">
			<input id="passwd" type="password" name="passwd" value="" size="36" maxlength="40" tabindex="60" style="width:100%; max-width:36ch;">
		</div>
	</div>
	<div style="display:flex; align-items:center; gap:12px; margin:10px 0; padding:12px; border:1px solid #d0d7de; border-radius:8px;">
		<label for="verifypasswd" class="proplabel" style="margin:0; min-width:14rem; white-space:nowrap;">Verify Password *</label>
		<div class="proptext" style="flex:1;">
			<input id="verifypasswd" type="password" name="verifypasswd" value="" size="36" maxlength="40" tabindex="70" style="width:100%; max-width:36ch;">
		</div>
	</div>
	<div style="display:flex; align-items:center; gap:12px; margin:10px 0; padding:12px; border:1px solid #d0d7de; border-radius:8px; flex-wrap:wrap;">
		<label for="securitycode" class="proplabel" style="margin:0; min-width:14rem; white-space:nowrap;">
			Enter code shown in image *
		</label>
		<div class="proptext" style="flex:1; display:flex; align-items:center; gap:12px;">
			<input id="securitycode" type="text" name="securitycode" size="40" maxlength="6" tabindex="80" style="max-width:18ch; width:100%;">
			<img src="securityimage.php" alt="Security Code Image"  style="height:80px; margin-left:15%;">
		</div>
	</div>
	<div style="display:flex; align-items:center; flex-wrap:wrap; gap:8px; margin:10px 0; padding:12px; #d0d7de; border-radius:8px;">
		<input type="submit" name="submit_user" class="popupbuttontxt" value="Save" title="Create login identity" style="margin:0;">
		<!-- <input type="button" class="inputbtn darkblue" value="Close" onclick="window.close()" style="margin-left:15px;"> -->
	</div>
	<div class="proplabel">* Required items.</div>
	<br>
</form>
</body></html>