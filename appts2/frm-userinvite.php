<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-userinvite.php";

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

// validate the session
$sr = $myappt->validatesession($_siteid);
if ($sr !== true)
{
	print "<script type=\"text/javascript\">alert('".$sr."')</script>\n";
	$myappt->vectormeto($page_denied);
}

// Check user tab permissions
$tab_users = $myappt->checktabmask(TAB_U);
$tab_sites = $myappt->checktabmask(TAB_S);
$tab_ws = $myappt->checktabmask(TAB_WS);
$tab_holmaps = $myappt->checktabmask(TAB_HOL);
$tab_reports = $myappt->checktabmask(TAB_RPT);
$tab_invite = $myappt->checktabmask(TAB_INVITE);
$tab_mailtmpl = $myappt->checktabmask(TAB_MAILTMPL);
$tab_repldash = $myappt->checktabmask(TAB_REPLDASH);

if (!$tab_invite)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	$myappt->vectormeto($page_denied);
}

$ntabs = 1;
if ($tab_users)
	$ntabs++;
if ($tab_sites)
	$ntabs++;
if ($tab_ws)
	$ntabs++;
if ($tab_holmaps)
	$ntabs++;
if ($tab_reports)
	$ntabs++;
if ($tab_invite)
	$ntabs++;
if ($tab_mailtmpl)
	$ntabs++;
if ($tab_repldash)
	$ntabs++;
	
$uuid = $myappt->session_getuuid();

// Get the data from the database
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	$et = $myappt->getmailtemplate($sdbh, MTDB_INVITE);
	
	$p_userid = "";
	$p_uname = "";
	$p_useremail = "";
	$p_phone = "";
	
	// process the email URL invitation submission
	if (isset($_POST["submit_urlinvite"]))
	{
		if (isset($_POST["useremail"]))
			$p_useremail = trim($_POST["useremail"]);
		if ($p_useremail == "")
			print "<script type=\"text/javascript\">alert('User email is required.')</script>\n";
		else
		{
			// check for uniqueness, since the email address will be used to create a new account
			$unique = $myappt->isuniquecol($sdbh, "user", "userid", $p_useremail);
			
			if (!$unique)
				print "<script type=\"text/javascript\">alert('Userid: ".htmlentities($p_useremail)." is already in use.')</script>\n";
			else
			{
				// look for other fields
				if (isset($_POST["username"]))
					$p_uname = trim($_POST["username"]);
				
				if ($p_uname == "")
					print "<script type=\"text/javascript\">alert('User Name is required.')</script>\n";
				else
				{
					$notifymessage = "";
					$vc = $myappt->buildquerystring($p_uname, $p_useremail);
					// email the invitation
					$mailbody = $et["mtbody"];
					$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
					$mail_adminname = $myappt->session_getuuname();
					$mailbody = str_ireplace(ET_ADMINNAME, $mail_adminname, $mailbody);
					$mailbody = str_ireplace(ET_VURL, $mail_vurl, $mailbody);
					//$mailbody = wordwrap($mailbody, 70);
					
					$mymail->setFrom($et["mtfrom"]);
					$mymail->clearaddresses();
					$mymail->addaddress($p_useremail);
					if (empty($et["mtsubject"]))
						$mymail->setSubject("Appointments system invitation.");
					else
						$mymail->setSubject($et["mtsubject"]);
					$mymail->setBody($mailbody);

					if (strcasecmp(MAILER, "mail") == 0)
						$mymail->ismail();
					elseif (strcasecmp(MAILER, "qmail") == 0)
						$mymail->isqmail();
					elseif (strcasecmp(MAILER, "smtp") == 0)
					{
						$mymail->issmtp();
						$mymail->setSmtphost(SMTP_SERVER);
						$mymail->setSmtpport(SMTP_PORT);
						$mymail->setSmtpauth(SMTP_AUTH);
					}
						
					$mymail->send();
					$m = !$mymail->iserror();
						
					if ($m === true)
					{	
						// create a log entry
						$logstring = "Invitation email sent for user ".$p_uname."(".$p_useremail.").";
						$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_INVITEUSER);
							
						$notifymessage .= "Invitation sent.";
					}
					else
						$notifymessage .= "Mail error in sending invitation: ".$mymail->mailerror.".";
						
					// Clear the values now that this user has been processed
					$p_userid = "";
					$p_uname = "";
					$p_useremail = "";
					$p_phone = "";

					print "<script type=\"text/javascript\">alert('".$notifymessage.".')</script>\n";
				}
			}
		}
	}
	elseif (isset($_POST["submit_account"]))
	{
		// process the Create Account selection
		if (isset($_POST["userid"]))
			$p_userid = trim($_POST["userid"]);
		if ($p_userid == "")
			print "<script type=\"text/javascript\">alert('User ID is required.')</script>\n";
		else
		{
			// check for uniqueness
			$unique = $myappt->isuniquecol($sdbh, "user", "userid", $p_userid);
			
			if (!$unique > 0)
				print "<script type=\"text/javascript\">alert('Userid: ".htmlentities($p_userid)." is already in use.')</script>\n";
			else
			{
				// look for other fields
				if (isset($_POST["usernamea"]))
					$p_uname = trim($_POST["usernamea"]);
				
				if ($p_uname == "")
					print "<script type=\"text/javascript\">alert('User Name is required.')</script>\n";
				else
				{
					if (isset($_POST["userphone"]))
						$p_phone = trim($_POST["userphone"]);
					
					if (isset($_POST["useremaila"]))
						$p_useremail = trim($_POST["useremaila"]);
					
					if ($_cfg_appt_noemail !== true)
					{
						if ($p_useremail == "")
							print "<script type=\"text/javascript\">alert('User Email is required.')</script>\n";
					}
					else
					{
						// Do the email invitation
						if ($p_useremail != "")
							$process_email = true;
						
						// Check for password
						$passwd = "";
						$verifypasswd = "";
						if (isset($_POST["passwd"]))
							$passwd = $_POST["passwd"];
						if ($passwd != "")
						{
							if (strlen($passwd) < PW_MINLENGTH)
							{
								print "<script type=\"text/javascript\">alert('Password must be at least ".PW_MINLENGTH." characters.')</script>\n";
								$process_user = false;
							}
							else
							{
								if (isset($_POST["verifypasswd"]))
									$verifypasswd = $_POST["verifypasswd"];
								if (strcmp($passwd, $verifypasswd) == 0)
								{
									$process_user = true;
									$process_account = true;
								}
								else 
								{
									print "<script type=\"text/javascript\">alert('Password verification failed.')</script>\n";
									$process_user = false;
								}
							}
								
						}
						else
							$process_user = true;
						
						// everything OK, process the user invitation
						if ($process_user === true)
						{
							$notifymessage = "";
							if ($process_email === true)
							{
								$vc = $myappt->buildquerystring($p_uname, $p_useremail);
								// email the invitation
								$mailbody = $et["mtbody"];
								$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
								$mail_adminname = $myappt->session_getuuname();
								$mailbody = str_ireplace(ET_ADMINNAME, $mail_adminname, $mailbody);
								$mailbody = str_ireplace(ET_VURL, $mail_vurl, $mailbody);
								//$mailbody = wordwrap($mailbody, 70);
								
								$mymail->setFrom($et["mtfrom"]);
								$mymail->clearaddresses();
								$mymail->addaddress($p_useremail);
								if (empty($et["mtsubject"]))
									$mymail->setSubject("Appointments system invitation.");
								else
									$mymail->setSubject($et["mtsubject"]);
								$mymail->setBody($mailbody);
			
								if (strcasecmp(MAILER, "mail") == 0)
									$mymail->ismail();
								elseif (strcasecmp(MAILER, "qmail") == 0)
									$mymail->isqmail();
								elseif (strcasecmp(MAILER, "smtp") == 0)
								{
									$mymail->issmtp();
									$mymail->setSmtphost(SMTP_SERVER);
									$mymail->setSmtpport(SMTP_PORT);
									$mymail->setSmtpauth(SMTP_AUTH);
								}
									
								$mymail->send();
								$m = !$mymail->iserror();
						
								if ($m === true)
								{
									// create a log entry
									$logstring = "Invitation email sent for user ".$p_uname."(".$p_useremail.").";
									$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_INVITEUSER);
									
									$notifymessage .= "Invitation sent.";
								}
								else
									$notifymessage .= "Mail error in sending invitation: ".$mymail->mailerror.".";
							}
							
							if ($process_account === true)
							{
								$pwhash = $myappt->create_ssha1passwd($passwd);
								$p_privilege = PRIV_APPT | PRIV_LOGIN;
								$p_tabmask = 0;
								do
								{
									$p_uuid = $myappt->getuuid_ascii();
									$unique = $myappt->isuniquecol($sdbh, "user", "uuid", $p_uuid);
								} while (!$unique);
								
								
								$q_user = "insert into user "
									. "\n set "
									. "\n uuid='".$sdbh->real_escape_string($p_uuid)."', "
									. "\n userid='".$sdbh->real_escape_string($p_userid)."', "
									. "\n passwd='".$sdbh->real_escape_string($pwhash)."', "
									. "\n uname='".$sdbh->real_escape_string($p_uname)."', "
									. "\n email='".$sdbh->real_escape_string($p_useremail)."', "
									. "\n status='".USTATUS_ACTIVE."', "
									. "\n ucreate='".(gmdate("Y-m-d H:i:s"))."', "
									. "\n phone='".$sdbh->real_escape_string($p_phone)."', "
									. "\n privilege='".$sdbh->real_escape_string($p_privilege)."', "
									. "\n tabmask='".$sdbh->real_escape_string($p_tabmask)."', "
									. "\n xsyncmts='".time()."' "
									;
								$s_user = $sdbh->query($q_user);
								if ($s_user)
								{
									$logstring = "User account ".$p_userid." for ".$p_uname." created.";
									$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_NEWUSER);
								}
								else
								{
									$logstring = "Failed to create user account ".$p_userid." for ".$p_uname.".";
									$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_ERRORUSER);
								}
								
								$notifymessage .= " User account created.";
							}
							
							// Clear the values now that this user has been processed
							$p_userid = "";
							$p_uname = "";
							$p_useremail = "";
							$p_phone = "";
	
							print "<script type=\"text/javascript\">alert('".$notifymessage.".')</script>\n";
						}
					}
				}
			}
		}
	}
	
	// process the email file submission if permitted
	if ($_allowemailupload === true)
	{
		if (isset($_POST["btn_upload"]))
		{
			if (isset($_FILES["useremailfile"]))
			{
				if ($_FILES["useremailfile"]["error"] == UPLOAD_ERR_OK)
				{
					// get the file mime type
					$ftype = $_FILES["useremailfile"]["type"];
					// get the file tmp name
					$ftmpname = $_FILES["useremailfile"]["tmp_name"];
					// get the original file name
					$fsourcename = $_FILES["useremailfile"]["name"];
					// get the file size
					$fsize = $_FILES["useremailfile"]["size"];

					if ($fsize > 0)
					{
						$uploadfile = $upload_dir.basename($fsourcename);
						if (move_uploaded_file($ftmpname, $uploadfile))
						{
							$logstring = "Email file ".$fsourcename." uploaded.";
							$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_UPLOADUSERFILE);

							// Attempt the processing in this script, rather than an async separate process.
							// open the file
							$fh = fopen($uploadfile, "r");
							if ($fh !== false)
							{
								$mailsubject = $et["mtsubject"];
								if (empty($mailsubject))
									$mailsubject = "Appointments system invitation.";
								$mymail->setSubject($mailsubject);
								$line = 1;
								
								while (!feof($fh))
								{
									$fline = trim(fgets($fh));
									// each line should have : email | name on it
									if ($fline != "")
									{
										$ep = explode("|", $fline);
										if (count($ep) == 2)
										{
											$uem = trim($ep[0]);
											$unm = trim($ep[1]);
											if ($uem != "")
											{
												// check for uniqueness - not required since email will be going to the recipient 
												// who owns the email address, even if they already exist in the system.
												$vc = $myappt->buildquerystring($unm, $uem);
												// email the invitation
												$mailbody = $et["mtbody"];
												$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
												$mail_adminname = $myappt->session_getuuname();
												$mailbody = str_ireplace(ET_ADMINNAME, $mail_adminname, $mailbody);
												$mailbody = str_ireplace(ET_VURL, $mail_vurl, $mailbody);
												$mailbody = wordwrap($mailbody, 70);
							
												$mymail->setFrom($et["mtfrom"]);
												$mymail->clearaddresses();
												$mymail->addaddress($uem);
												$mymail->setSubject($et["mtsubject"]);
												$mymail->setBody($mailbody);
							
												if (strcasecmp(MAILER, "mail") == 0)
													$mymail->ismail();
												elseif (strcasecmp(MAILER, "qmail") == 0)
													$mymail->isqmail();
												elseif (strcasecmp(MAILER, "smtp") == 0)
												{
													$mymail->issmtp();
													$mymail->setSmtphost(SMTP_SERVER);
													$mymail->setSmtpport(SMTP_PORT);
													$mymail->setSmtpauth(SMTP_AUTH);
												}
													
												$mymail->send();
												$m = !$mymail->iserror();
							
												if ($m === true)
												{
													// create a log entry
													$logstring = "Invitation email sent for user ".$unm."(".$uem.").";
													$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_INVITEUSER);
												}
											}
											else
											{
												// create a log entry
												$logstring = "Error: null email on line ".$line." for ".$unm.".";
												$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_ERRORUSER);
											}
										}
										else 
										{
											// create a log entry
											$logstring = "Error: incorrect number of fields (".count($ep).") on line ".$line.".";
											$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_ERRORUSER);
										}
									}
									$line++;
								}
								// create a log entry
								$logstring = "Upload file ".$fsourcename." process complete. Total: ".$line." lines processed.";
								$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_UPLOADUSERFILE);

								fclose($fh);
							}
								
							// Now trigger the asynchronous processing script and return to the original form.
							// Syntax is /usr/local/bin/php -f inviteemail filename
							//$call = $upload_process_exec." ".$uploadfile;
							//pclose(popen($call.' &', 'r'));
						}
						else
						{
							$logstring = "Email file ".$fsourcename." failed to upload.";
							$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_UPLOADUSERFILE);
						}
					}
					else
					{
						$logstring = "Email file ".$fsourcename." zero size.";
						$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_UPLOADUSERFILE);
					}
				}
				else
				{
					$logstring = "Email file upload error.";
					$myappt->createlogentry($sdbh, $logstring, $uuid, ALOG_UPLOADUSERFILE);
				}
			}
		}
	}

	if ($_logs_showall === false)
	{
		// get the last logs for this user
		$uuid = $myappt->session_getuuid();
		$q_log = "select * from log "
			. "\n where sourceid='".$uuid."' "
			. "\n order by logdate desc "
			. "\n limit ".$_logs_limit
			;
	}
	else
	{
		// get the last logs for all users
		$q_log = "select * from log "
			. "\n order by logdate desc "
			. "\n limit ".$_logs_limit
			;
	}
	$s_log = $sdbh->query($q_log);
	$nds = 0;
	$dset = array();
	
	if ($s_log)
	{
		$n_log = $s_log->num_rows;
		
		while ($r_log = $s_log->fetch_assoc())
		{
			$dset[$nds]["ldate"] = $r_log["logdate"];
			$dset[$nds]["lmsg"] = $r_log["logstring"];
			
			$nds++;
		}
		$s_log->free();
	}
	
	$sdbh->close();
}
else
{
	$p_userid = "";
	$p_uname = "";
	$p_useremail = "";
	$p_phone = "";
	$s_log = false;
	$n_log = 0;
	$nds = 0;
	$dset = array();
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<title>Authentx Appointments Management System</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentxappointment.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
</head>
<body>
<table width="100%" cellspacing="0" cellpadding="0" border="0" nowrap="nowrap" scope="row" class="tabtable">
<tr height="33" valign="center" align="center">
<td>
<?php
// Determine user's tab display
print "<table width=\"".($ntabs*105)."\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" nowrap=\"nowrap\" scope=\"row\">\n";
print "<tr height=\"33\" valign=\"center\">\n";
print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-appt.php\"><span class=\"tabtext\">Appts</span></a></td>\n";
if ($tab_users)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-user.php\"><span class=\"tabtext\">Users</span></a></td>\n";
if ($tab_sites)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-sites.php\"><span class=\"tabtext\">Sites</span></a></td>\n";
if ($tab_ws)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-emws.php\"><span class=\"tabtext\">EMWS</span></a></td>\n";
if ($tab_holmaps)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-holmap.php\"><span class=\"tabtext\">Holidays</span></a></td>\n";
if ($tab_reports)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-reports.php\"><span class=\"tabtext\">Reports</span></a></td>\n";
if ($tab_invite)
	print "<td width=\"105\" class=\"tabcell_on\"><span class=\"tabtext\">Invite</span></td>\n";
if ($tab_mailtmpl)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-mailtmpl.php\"><span class=\"tabtext\">Templates</span></a></td>\n";
if ($tab_repldash)
	print "<td width=\"105\" class=\"tabcell_off\"><a href=\"frm-repldash.php\"><span class=\"tabtext\">Replication</span></a></td>\n";
print "</tr>\n";
print "</table>\n";
?>
</td>
</tr>
<tr height="20">
<td valign="top" colspan="9">
<table width="100%" cellspacing="0" cellpadding="0" border="0" id="bartable">
<tr height="18" valign="center">
<td>&nbsp;</td>
</tr>
</table>
</td>
</tr>
</table>
<p/>
<table cellspacing="0" cellpadding="0" align="center" border="0" width="858">
<tr><td><img height="12" src="../appcore/images/logoslice_1.gif" width="200" border="0" /></td>
</tr>
<tr><td valign="top" background="../appcore/images/box_mtl_ctr.gif">
<table cellspacing="0" cellpadding="0" border="0" width="858">
<tr height="12"><td width="12" height="12"><img height="12" src="../appcore/images/box_mtl_topl.gif" width="12" /></td>
<td background="../appcore/images/box_mtl_top.gif"><img height="12" src="../appcore/images/logoslice_2.gif" width="188" border="0" /></td>
<td width="12"><img height="12" src="../appcore/images/box_mtl_topr.gif" width="12" /></td>
</tr>
<tr valign="top">
<td width="12" background="../appcore/images/box_mtl_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td align="middle" background="../appcore/images/bg_spacer.gif">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr><td align="middle">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr height="0"><td align="left" width="220"><img height="56" src="../appcore/images/logoslice_3.gif" width="188" border="0" /></td>
<td align="right">
<table cellspacing="0" cellpadding="0" border="0" width="610">
<tr>
<td align="left" width="450">
<table  cellspacing="0" cellpadding="0" border="0" width="450">
<tr height="28"><td valign="top"><span class="siteheading"><?php print SITEHEADING ?></span></td>
</tr><tr height="28"><td valign="top"><span class="nameheading"></span></td></tr>
</table>
</td>
<td align="right" width="160">
<table cellspacing="0" cellpadding="0" border="0" width="160">
<tr height="28" valign="middle">
<td align="middle" width="80"></td>
<td align="middle" width="40"></td>
<td align="middle" width="40"></td>
</tr>
<tr height="28" valign="middle">
<td align="middle"></td>
<td align="middle" colspan="2"><a href="vec-logout.php" title="Log off the system"><img src="../appcore/images/icon-btnlogoff.gif" width="75" height="24" border="0" onclick='return frmCheckDirty()' /></a></td>
</tr></table>
</td>
</tr></table>
</td></tr>
<tr height="8" valign="top">
<td></td>
<td></td>
</tr></table>
</td></tr>
<tr><td align="center" valign="bottom">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr height="2"><td width="2" height="2"><img height="2" src="../appcore/images/bevel_topl.gif" width="2"></td>
<td background="../appcore/images/bevel_top.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1"></td>
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_topr.gif" width="2"></td>
</tr>
<tr><td width="2" background="../appcore/images/bevel_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1"></td>
<td valign="center" align="left">
<table border="0" cellspacing="0" cellpadding="10" width="830" bgcolor="#ffffff">
<tr><td align="left">
<table border="0" cellspacing="0" cellpadding="0" style='table-layout:fixed' width="800" bgcolor="#ffffff">
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />

<tr height="1">
<td></td><td></td><td></td><td></td><td></td>
<td></td><td></td><td></td><td></td><td></td>
<td></td><td></td><td></td><td></td><td></td>
<td></td><td></td><td></td><td></td><td></td>
</tr>

<tr height="40"><td colspan="20" valign="top"><span class="lblblktext">Invite User to Access System</span></td></tr>
<?php
if ($myappt->checkprivilege(PRIV_UINVITE))
{
?>
<form name="uinvite" method="post" action="<?php print $formfile ?>" autocomplete="off" >
<tr height="40">
<td colspan="6" valign="top"><span class="lblblktext">User Email *</span><br/>
<input type="text" size="30" maxlength="60" name="useremail" value="<?php print $p_useremail ?>" /></td>
<td>&nbsp;</td>
<td colspan="6" valign="top"><span class="lblblktext">Full Name *</span><br/>
<input type="text" size="30" maxlength="120" name="username" value="<?php print $p_uname ?>" /></td>
<td>&nbsp;</td>
<td colspan="6" valign="top"><span class="lblblktext">&nbsp;</span><br/>
<input type="submit" name="submit_urlinvite" class="btntext" value="Email URL Invite" /></td>
</tr>
<tr height="40"><td colspan="20" valign="center"><hr/></td></tr>
<tr height="40">
<td colspan="6" valign="top"><span class="lblblktext">User Login ID *</span><br/>
<input type="text" size="30" maxlength="60" name="userid" value="<?php print $p_userid ?>" /></td>
<td>&nbsp;</td>
<td colspan="6" valign="top"><span class="lblblktext">Full Name *</span><br/>
<input type="text" size="30" maxlength="50" name="usernamea" value="<?php print $p_uname ?>" /></td>
<td>&nbsp;</td>
<td colspan="6" valign="top"><span class="lblblktext">&nbsp;</span><br/></td>
</tr>
<tr height="40">
<td colspan="6" valign="top"><span class="lblblktext">User Email</span><br/>
<input type="text" size="30" maxlength="60" name="useremaila" value="<?php print $p_useremail ?>" /></td>
<td>&nbsp;</td>
<td colspan="6" valign="top"><span class="lblblktext">User Phone</span><br/>
<input type="text" size="30" maxlength="30" name="userphone" value="<?php print $p_phone ?>" /></td>
<td>&nbsp;</td>
</tr>
<tr height="40">
<td colspan="6" valign="top"><span class="lblblktext">Password</span><br/>
<input type="password" size="30" maxlength="30" name="passwd" value="" /></td>
<td>&nbsp;</td>
<td colspan="6" valign="top"><span class="lblblktext">Re-enter Password</span><br/>
<input type="password" size="30" maxlength="30" name="verifypasswd" value="" /></td>
<td>&nbsp;</td>
<td colspan="6" valign="top"><span class="lblblktext">&nbsp;</span><br/>
<input type="submit" name="submit_account" class="btntext" value="Create User Account" /></td>
</tr>
</form>
<?php
if ($_allowemailupload === true)
{
?>
<tr height="40"><td colspan="20" valign="top"><hr/></td></tr>
<form name="uploadform" id="uploadform" method="post" action="<?php print $formfile ?>"  autocomplete="off" enctype="multipart/form-data">
<tr height="40">
<td colspan="14" valign="top"><span class="lblblktext">Upload User File (<?php print $maxsize ?> bytes maximum)</span><br/>
<input type="hidden" name="MAX_FILE_SIZE" value="<?php print $maxsize ?>" /><input type="file" name="useremailfile" size="60" />
</td>
<td colspan="6" valign="top"><span class="lblblktext">&nbsp;</span><br/>
<input type="submit" name="btn_upload" class="btntext" value="Upload Email File" /></td>
</tr>
</form>
<?php
}
?>
<tr height="40"><td colspan="20" valign="top"><hr/></td></tr>
<tr height="40"><td colspan="20" valign="top"><span class="lblblktext">
<?php 
if ($_logs_showall === false)
{
?>
Recent Activity by <?php print $myappt->session_getuuname() ?>
<?php 
}
else
{
?>
Recent Activity
<?php 
}
?>
</span></td></tr>
<tr><td colspan="20" valign="top">
<div class="logbox">
<table width="800" cellspacing="0" cellpadding="3" border="0">
<tr>
<td width="200" class="matrixheading"><span class="tableheading">Date</span></td>
<td width="600" class="matrixheading"><span class="tableheading">Action</span></td>
</tr>
<?php
	for ($i = 0; $i < $nds; $i++)
	{
?>
<tr>
<td class="matrixline"><span class="tabletext"><?php print $dset[$i]["ldate"] ?></span></td>
<td class="matrixline"><span class="tabletext"><?php print htmlentities($dset[$i]["lmsg"]) ?></span></td>
</tr>
<?php
	}
?>
</table>
</div>
</td></tr>
<?php
}
?>
</table>
</td></tr></table>
</td>
<td width="2" background="../appcore/images/bevel_r.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
</tr>
<tr height="2">
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_botl.gif" width="2" /></td>
<td background="../appcore/images/bevel_bot.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_botr.gif" width="2" /></td>
</tr>
</table>
</tr>
<tr>
<td align="center" valign="bottom">
<table cellspacing="0" cellpadding="0" border="0" width="834">
<tr>
<td width="30%" align="left">
&nbsp;
</td>
<td width="40%" align="center">
<span class="smlgrytext">&nbsp;</span>
</td>
<td width="30%" align="right">
<span align="right"><img height="25" src="../appcore/images/AuthentX-logo-plain-gray6.gif" width="94"/></span>
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
</body></html>