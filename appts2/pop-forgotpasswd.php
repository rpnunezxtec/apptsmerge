<?php
// $Id:$

// popup to email a new password to the user
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-forgotpasswd.php";
// the geometry required for this popup
$windowx = 520;
$windowy = 300;

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

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Process registration submission here
	if (isset($_POST["submit_passwd"]))
	{
		// Posted info to use:
		// email
		$p_email = trim($_POST["email"]);
				
		// Check for required fields: userid and uname, and passwords
		if ($p_email == "")
		{
			print "<script type=\"text/javascript\">alert('Email address is required.')</script>\n";
		}
		else
		{
			// Look for the user by email address
			$userdetails = $myappt->readuserdetailbyemail($sdbh, $p_email);
			if (count($userdetails) > 0)
			{
				$uuid = $userdetails["uuid"];
				$uname = $userdetails["uname"];
				
				// generate a new random password end email it to the user
				$p_passwd = md5(microtime().date("YmdHis"));
				$p_passwd = strtoupper(substr($p_passwd, 0, PW_MINLENGTH));
				
				$pwhash = $myappt->create_ssha1passwd($p_passwd);

				$q_u = "update user set "
					. "\n passwd='".$sdbh->real_escape_string($pwhash)."', "
					. "\n xsyncmts='".time()."' "
					. "\n where uuid='".$sdbh->real_escape_string($uuid)."' "
					;
				$s_u = $sdbh->query($q_u);
				if ($s_u === false)
				{
					print "<script type=\"text/javascript\">alert('Error updating password for user.')</script>\n";
				}
				else 
				{
					// email the password
					$mailbody = "A password reset request was received from ".$_SERVER["REMOTE_ADDR"]." on ".(date("Y-m-d H:i")).".\n"
							. "Your new password for userid ".$userdetails["userid"]." has been set to ".$p_passwd."\n\n"
							;
					$mailbody = wordwrap($mailbody, 70);
								
					$mymail->setFrom = MAIL_FROM;
					$mymail->clearaddresses();
					$mymail->addaddress($p_email);
					$mymail->setSubject = "Appointments system password change";
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
						$logstring = "Password change email sent for user ".$uname."(".$p_email.").";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_MAILPASSWD);
					}

					// Close the popup with a message
					$sdbh->close();
					print "<script type=\"text/javascript\">alert('Password updated and sent - please check your email.')</script>\n";
					print "<script type=\"text/javascript\">self.close()</script>\n";
					die();
				}
			}
			else 
			{
				print "<script type=\"text/javascript\">alert('No user found with this email address.')</script>\n";
			}
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
<title>Password Request</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico">
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css">
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<form name="pwchange" method="post"  autocomplete="off" action="<?php print $formfile ?>">
<table border="1" cellspacing="0" cellpadding="5" width="480" class="proptable">
<tr height="30">
<td valign="top" align="right" colspan="2"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" tabindex="400"></td>
</tr><tr height="30">
<td valign="top" width="240"><span class="proplabel">Email Address *</span></td>
<td valign="top" width="240"><span class="proptext">
<input type="text" size="36" maxlength="60" tabindex="10" name="email" value="" />
</span></td>
</tr>
</table>
<p/>
<table cellSpacing="0" cellPadding="0" width="440" border="0">
<tr height="40">
<td width="200" valign="center" align="left">
<input type="submit" name="submit_passwd" class="btntext" value="Reset Password" title="Reset and email new password" tabindex="80">
</form>
</td>
<td width="240" valign="center" align="left">
<span class="proplabel">* Required items.</span>
</td>
</tr>
</table>
</body></html>