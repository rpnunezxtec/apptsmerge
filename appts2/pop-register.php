<?php
// $Id:$

// popup to self-register
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-register.php";
// the geometry required for this popup
$windowx = 600;
$windowy = 600;

include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
require_once(_AUTHENTX5APPCORE."/cl-mail.php");
date_default_timezone_set(DATE_TIMEZONE);

$mymail = new authentxmail();
$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

if ($_allowselfregister !== true)
{
	print "<script type=\"text/javascript\">alert('Self registration not permitted.')</script>\n";
	print "<script type=\"text/javascript\">self.close()</script>\n";
	die();
}

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	$et_register = $myappt->getmailtemplate($sdbh, MTDB_REGISTER);
	
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
			$p_uname = htmlspecialchars($p_uname, ENT_QUOTES | ENT_HTML5);
			$p_email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
			$p_email = strip_tags($p_email);
			if (strpos($p_email, '@') === false)
				$p_email = "";
			$p_userid = $p_email;		// Set userID as the email address for activation email
			
			$p_component = htmlspecialchars($p_component, ENT_QUOTES | ENT_HTML5);
			$p_component = strip_tags($p_component);
			$p_phone = htmlspecialchars($p_phone, ENT_QUOTES | ENT_HTML5);
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
				$userisunique = $myappt->isuniquecol($sdbh, "user", "userid", $p_userid);
				if (!$userisunique)
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
							$process_user = true;
						else 
						{
							print "<script type=\"text/javascript\">alert('Password verification failed.')</script>\n";
							$process_user = false;
						}
					}
				
					$pwhash = $myappt->create_ssha1passwd($p_passwd);
	
					// Check anti-spam image contents
					$p_scode = trim($_POST["securitycode"]);
					$orig_scode = $myappt->session_getvar("scode_text");
					
					if (strcasecmp($p_scode, $orig_scode) == 0)
						$process_user = true;
					else 
					{
						print "<script type=\"text/javascript\">alert('Security code entered incorrectly.')</script>\n";
						$process_user = false;
					}
				
					$uuid = false;
					if ($process_user === true)
					{
						// Create a uuid for the user record
						$uuid = $myappt->makeuniqueuuid($sdbh, "user", "uuid");

						$q_user = "insert into user set "
							. "\n userid='".$sdbh->real_escape_string($p_userid)."', "
							. "\n uuid='".$uuid."', "
							. "\n uname='".$sdbh->real_escape_string($p_uname)."', "
							. "\n passwd='".$sdbh->real_escape_string($pwhash)."', "
							. "\n email='".$sdbh->real_escape_string($p_email)."', "
							. "\n status='".$p_status."', "
							. "\n ucreate='".(gmdate("Y-m-d H:i:s"))."', "
							. "\n component='".$sdbh->real_escape_string($p_component)."', "
							. "\n phone='".$sdbh->real_escape_string($p_phone)."', "
							. "\n xsyncmts='".time()."' "
							;
					
						$s_user = $sdbh->query($q_user);
						if ($s_user)
						{
							$logstring = "User ".$p_uname." (uuid: ".$uuid.") created [self register].";
							$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_NEWUSER);
						}
						else
						{
							$uuid = false;
							print "<script type=\"text/javascript\">alert('Error creating new user: ".htmlentities($sdbh->error).".')</script>\n";
						}
					}
				
					// Second part - privileges requires role privilege
					// also requires a uid from the previous step
					if ($uuid !== false)
					{
						$new_priv = PRIV_APPT | PRIV_LOGIN;
						$new_tabs = 0;
								
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
							// get the user details
							$userdetails = $myappt->readuserdetail($sdbh, $uuid);
							$osname = $userdetails["uname"];
							$logstring = "User ".$osname." (uuid: ".$uuid.") privileges updated [self register].";
							$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_EDITUSER);
							
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
									
								$mymail->setFrom = $et_register["mtfrom"];
								$mymail->clearaddresses();
								$mymail->addaddress($p_email);
								if (empty($et_register["mtsubject"]))
									$mymail->setSubject = "Appointments system registration activation.";
								else
									$mymail->setSubject = $et_register["mtsubject"];
								$mymail->setBody = $mailbody;
								
								if (strcasecmp(MAILER, "mail") == 0)
									$mymail->ismail();
								elseif (strcasecmp(MAILER, "qmail") == 0)
									$mymail->isqmail();
								elseif (strcasecmp(MAILER, "smtp") == 0)
								{
									$mymail->issmtp();
									$mymail->setSmtphost = SMTP_SERVER;
									$mymail->setSmtpport = SMTP_PORT;
									$mymail->setSmtpauth = SMTP_AUTH;
								}
								
								$mymail->send();
								$m = !$mymail->iserror();
								
								if ($m === true)
								{
									// create a log entry
									$logstring = "Registration activation email sent for user ".$p_uname." (".$p_email.").";
									$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_INVITEUSER);
										
									$notifymessage .= "Registration activation email sent.";
								}
								else
									$notifymessage .= "Mail error in sending registration activation: ".$mymail->mailerror.".";
							}
							else
								$notifymessage = "User account created.";
						}
						
						// Close the popup with a message
						$sdbh->close();
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
	$sdbh->close();
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

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<title>User Properties</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<form name="userregister" method="post"  autocomplete="off" action="<?php print $formfile."?avc=".urlencode($avc) ?>">
<table border="1" cellspacing="0" cellpadding="5" width="540" class="proptable">
<tr height="30">
<td valign="top" align="right" colspan="2"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" tabindex="400"></td>
</tr><tr>
<td colspan="2" valign="top"><span class="smlblacktext">
STEP 1: Enter your name and contact information.  This information is 
used to confirm your appointment via email/phone (appointments, cancellations, etc.)
Your Login ID will be your email address.
</span></td>
</tr><tr height="30">
<td valign="top" width="300"><span class="proplabel">Full Name *</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="36" maxlength="120" tabindex="10" name="uname" value="<?php print $uname ?>" />
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Email *</span></td>
<td valign="top"><span class="proptext">
<input type="text" size="36" maxlength="60" tabindex="20" name="email" value="<?php print $email ?>" />
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Phone</span></td>
<td valign="top"><span class="proptext">
<input type="text" size="36" maxlength="40" tabindex="30" name="phone" value="<?php print $phone ?>" />
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Agency/Component</span></td>
<td valign="top"><span class="proptext">
<select name="component" tabindex="40" style="width: 22em">
<?php
$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
$rc = count($listcomponent);
for ($i = 0; $i < $rc; $i++)
{
	if (strcasecmp($component, $listcomponent[$i][0]) == 0)
		print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
	else
		print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
}
?>
</select>
</span></td>
</tr><tr>
<td colspan="2" valign="top"><span class="smlblacktext">
STEP 2: Create a User Password
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Password *</span></td>
<td valign="top"><span class="proptext">
<input type="password" name="passwd" value="" size="36" maxlength="40" tabindex="60">
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Verify Password *</span></td>
<td valign="top"><span class="proptext">
<input type="password" name="verifypasswd" value="" size="36" maxlength="40" tabindex="70">
</span></td>
</tr><tr height="30">
<td valign="top"><span class="proplabel">Enter code shown in image *</span></td>
<td valign="top"><span class="proptext">
<input type="text" name="securitycode" size="36" maxlength="6" tabindex="80" >
</span></td>
</tr><tr>
<td valign="top"><span class="proplabel">&nbsp;</span></td>
<td valign="top">
<img src="securityimage.php" alt="Security Code Image" />
</td>
</tr>
</table>
<p/>
<table cellspacing="0" cellpadding="0" width="480" border="0">
<tr height="40">
<td width="300" valign="center" align="left">
<input type="submit" name="submit_user" class="btntext" value="Save" title="Create login identity" tabindex="200">
</td>
<td width="240" valign="center" align="left">
<span class="proplabel">* Required items.</span>
</td>
</tr>
</table>
</form>
</body></html>