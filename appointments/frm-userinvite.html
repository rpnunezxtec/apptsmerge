<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-userinvite.html";
$form_name = "invite";
$tab_name = ucfirst($form_name);

include("config.php");
include("vec-clappointments.php");
include_once("../appcore/vec-clmail.php");
include('../appcore/vec-clforms.php');
$myform = new authentxforms();
$myappt = new authentxappointments();
$mymail = new authentxmail();

$fullname = $_SESSION["authentxappts"]["user"]["uname"];
$namearray = explode(" ", $fullname);
$firstname = $namearray[0];

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
	
$uid = $myappt->session_getuuid();

// Get the data from the database
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	$et = $myappt->getmailtemplate($dbh, MTDB_INVITE);
	
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
			$q_uid = "select uid, "
				. "\n userid "
				. "\n from user "
				. "\n where userid='".$dbh->real_escape_string($p_useremail)."' "
				;
			$s_uid = $dbh->query($q_uid);
			if ($s_uid)
			{
				$n_uid = $s_uid->num_rows;
				$s_uid->free();
			}
			
			if ($n_uid > 0)
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
					
					$mymail->from = $et["mtfrom"];
					$mymail->clearaddresses();
					$mymail->addaddress($p_useremail);
					if (empty($et["mtsubject"]))
						$mymail->subject = "Appointments system invitation.";
					else
						$mymail->subject = $et["mtsubject"];
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
						$logstring = "Invitation email sent for user ".$p_uname."(".$p_useremail.").";
						$myappt->createlogentry($dbh, $logstring, $uid, ALOG_INVITEUSER);
							
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
			$q_uid = "select uid, userid from user "
				. "\n where userid='".$dbh->real_escape_string($p_userid)."' "
				;
			$s_uid = $dbh->query($q_uid);
			if ($s_uid)
			{
				$n_uid = $s_uid->num_rows;
				$s_uid->free();
			}
			if ($n_uid > 0)
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
								
								$mymail->from = $et["mtfrom"];
								$mymail->clearaddresses();
								$mymail->addaddress($p_useremail);
								if (empty($et["mtsubject"]))
									$mymail->subject = "Appointments system invitation.";
								else
									$mymail->subject = $et["mtsubject"];
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
									$logstring = "Invitation email sent for user ".$p_uname."(".$p_useremail.").";
									$myappt->createlogentry($dbh, $logstring, $uid, ALOG_INVITEUSER);
									
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
								
								$q_user = "insert into user "
									. "\n set "
									. "\n userid='".$dbh->real_escape_string($p_userid)."', "
									. "\n passwd='".$dbh->real_escape_string($pwhash)."', "
									. "\n uname='".$dbh->real_escape_string($p_uname)."', "
									. "\n email='".$dbh->real_escape_string($p_useremail)."', "
									. "\n status='".USTATUS_ACTIVE."', "
									. "\n ucreate='".(gmdate("Y-m-d H:i:s"))."', "
									. "\n phone='".$dbh->real_escape_string($p_phone)."', "
									. "\n privilege='".$dbh->real_escape_string($p_privilege)."', "
									. "\n tabmask='".$dbh->real_escape_string($p_tabmask)."' "
									;
								$s_user = $dbh->query($q_user);
								$newuid = $dbh->insert_id;
								if ($s_user)
								{
									$logstring = "User account ".$p_userid." for ".$p_uname." created.";
									$myappt->createlogentry($dbh, $logstring, $uid, ALOG_NEWUSER);
								}
								else
								{
									$logstring = "Failed to create user account ".$p_userid." for ".$p_uname.".";
									$myappt->createlogentry($dbh, $logstring, $uid, ALOG_ERRORUSER);
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
							$myappt->createlogentry($dbh, $logstring, $uid, ALOG_UPLOADUSERFILE);

							// Attempt the processing in this script, rather than an async separate process.
							// open the file
							$fh = fopen($uploadfile, "r");
							if ($fh !== false)
							{
								$mailsubject = $et["mtsubject"];
								if (empty($mailsubject))
									$mailsubject = "Appointments system invitation.";
								$mymail->subject = $mailsubject;
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
							
												$mymail->from = $et["mtfrom"];
												$mymail->clearaddresses();
												$mymail->addaddress($uem);
												$mymail->subject = $et["mtsubject"];
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
													$logstring = "Invitation email sent for user ".$unm."(".$uem.").";
													$myappt->createlogentry($dbh, $logstring, $uid, ALOG_INVITEUSER);
												}
											}
											else
											{
												// create a log entry
												$logstring = "Error: null email on line ".$line." for ".$unm.".";
												$myappt->createlogentry($dbh, $logstring, $uid, ALOG_ERRORUSER);
											}
										}
										else 
										{
											// create a log entry
											$logstring = "Error: incorrect number of fields (".count($ep).") on line ".$line.".";
											$myappt->createlogentry($dbh, $logstring, $uid, ALOG_ERRORUSER);
										}
									}
									$line++;
								}
								// create a log entry
								$logstring = "Upload file ".$fsourcename." process complete. Total: ".$line." lines processed.";
								$myappt->createlogentry($dbh, $logstring, $uid, ALOG_UPLOADUSERFILE);

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
							$myappt->createlogentry($dbh, $logstring, $uid, ALOG_UPLOADUSERFILE);
						}
					}
					else
					{
						$logstring = "Email file ".$fsourcename." zero size.";
						$myappt->createlogentry($dbh, $logstring, $uid, ALOG_UPLOADUSERFILE);
					}
				}
				else
				{
					$logstring = "Email file upload error.";
					$myappt->createlogentry($dbh, $logstring, $uid, ALOG_UPLOADUSERFILE);
				}
			}
		}
	}

	if ($_logs_showall === false)
	{
		// get the last logs for this user
		$uid = $myappt->session_getuuid();
		$q_log = "select * from log "
			. "\n where sourceid='".$uid."' "
			. "\n order by logdate desc "
			. "\n limit ".$_logs_limit
			;
	}
	else
	{
		// get the last logs for all users
		$uid = $myappt->session_getuuid();
		$q_log = "select * from log "
			. "\n order by logdate desc "
			. "\n limit ".$_logs_limit
			;
	}
	$s_log = $dbh->query($q_log);
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
	
	$dbh->close();
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

$myform->frmrender_bodytag($bodyparams);

// The page container
print "<div class=\"main\">\n";

// Top banner
$topparams = array();
$topparams["logoimgurl"]   = $cfg_logoimgurl;
$topparams["logoalt"]      = $cfg_logoalt;
$topparams["bannerheading1"]= BANNERHEADING1;
$topparams["bannerheading2"]= BANNERHEADING2;
$topparams["pageportal"] = $page_certportal;
$topparams["dropdown"]     = array_merge($cfg_userdropdown, $cfg_tabs);
$myform->frmrender_topbanner($topparams);

// Left side
$asideparams = array();
$asideparams["tabs"] = $cfg_tabs;
$asideparams["tabon"] = $tab_name;
$asideparams["side"] = "aSide";
$myform->frmrender_side($asideparams);

$tableparams = array();
$tableparams["title"]   = $cfg_forms[$form_name]["table_title"];
$tableparams["data"]    = $tokenmatrix;
$tableparams["columns"] = $cfg_forms[$form_name]["table_columns"];

$bsideparams = array();
$bsideparams["side"] = "bSide";
$bsideparams["firstname"] = $firstname;
$bsideparams["userdropdown"] = $cfg_userdropdown;
$bsideparams["authentxlogoimgurl"] = $cfg_authentxlogourl;

$footerparams = array();
$footerparams["poweredbylogo"] = $cfg_authentxlogourl_white;
$footerparams["serverid"] = SERVERID;
?>

<div id="content">
	<div class="fullscreenscrollbox">
		<div style="max-width:1100px;margin:0 auto;display:flex;flex-direction:column;gap:16px;padding:8px 16px;">
			<div style="display:flex;flex-direction:column;align-items:stretch;gap:20px;padding:10px 0;">
			<div class="titletextwhite">Invite User to Access System</div>
<?php
			if ($myappt->checkprivilege(PRIV_UINVITE))
			{
?>
			<form name="uinvite" method="post" action="<?php print $formfile ?>" autocomplete="off"style="display:flex;flex-direction:column;align-items:stretch;gap:5px;width:100%;">
				<div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:flex-start; gap:15%;">
					<div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:16rem;">
						<label class="lblblktext" for="useremail" style="text-align:left;font-weight:700;">User Email *</label>
						<input type="text" name="useremail" id="useremail" value="<?php print htmlentities($p_useremail) ?>" maxlength="30" style="width:100%;">
					</div>

					<div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:16rem;">
						<label class="lblblktext" for="username" style="text-align:left;font-weight:700;">Full Name *</label>
						<input type="text" name="username" id="username" value="<?php print htmlentities($p_uname) ?>" maxlength="30" style="width:100%;">
					</div>

					<div style="align-self:flex-end;">
						<input type="submit" name="submit_urlinvite" class="inputbtn darkblue" value="Email URL Invite">
					</div>
				</div>

				<hr style="border:0;border-top:1px solid #8f9091ff;margin:10px 0;width:100%;">

				<div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:flex-start; gap:15%;">
					<div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:16rem;">
						<label class="lblblktext" for="userid" style="text-align:left;font-weight:700;">User Login ID *</label>
						<input type="text" name="userid" id="userid" value="<?php print htmlentities($p_userid) ?>" maxlength="30" style="width:100%;">
					</div>

					<div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:16rem;">
						<label class="lblblktext" for="username2" style="text-align:left;font-weight:700;">Full Name *</label>
						<input type="text" name="username" id="username2" value="<?php print htmlentities($p_uname) ?>" maxlength="30" style="width:100%;">
					</div>
				</div>

				<div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:flex-start; gap:15%;">
					<div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:16rem;">
						<label class="lblblktext" for="useremaila" style="text-align:left;font-weight:700;">User Email</label>
						<input type="text" name="useremaila" id="useremaila" value="<?php print htmlentities($p_useremail) ?>" maxlength="30" style="width:100%;">
					</div>

					<div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:16rem;">
						<label class="lblblktext" for="userphone" style="text-align:left;font-weight:700;">User Phone</label>
						<input type="text" name="userphone" id="userphone" value="<?php print htmlentities($p_phone) ?>" maxlength="30" style="width:100%;">
					</div>
				</div>

				<div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:flex-start; gap:15%;">
					<div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:16rem;">
						<label class="lblblktext" for="passwd" style="text-align:left;font-weight:700;">Password</label>
						<input type="password" name="passwd" id="passwd" maxlength="30" style="width:100%;">
					</div>

					<div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:16rem;">
						<label class="lblblktext" for="verifypasswd" style="text-align:left;font-weight:700;">Re-enter Password</label>
						<input type="password" name="verifypasswd" id="verifypasswd" maxlength="30" style="width:100%;">
					</div>

					<div style="align-self:flex-end;">
						<input type="submit" name="submit_account" class="inputbtn darkblue" value="Create User Account">
					</div>

					<hr style="border:0;border-top:1px solid #8f9091ff;margin:10px 0;width:100%;">
				</div>
			</form>

<?php
			if ($_allowemailupload === true)
			{
?>
			<form name="uploadform" id="uploadform" method="post" action="<?php print $formfile ?>" autocomplete="off" enctype="multipart/form-data" style="display:flex;flex-direction:column;align-items:stretch;gap:10px;width:100%;">
				<div style="display:flex;flex-wrap:nowrap;gap:15%;align-items:start;justify-content:start;">
					<div style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:16rem;">
						<div style="font-weight:700;"class="lblblktext">Upload User File (<?php print $maxsize ?> bytes maximum)</div>
						<input type="hidden" name="MAX_FILE_SIZE" value="<?php print $maxsize ?>">
						<div class="popupbuttonrow">
							<label for="uploadfile" class="popupbuttontxt" style="font-weight: normal;">Choose File</label>
							<span id="file-chosen" style="font-weight: normal;">No file chosen</span>
						</div>
						<input type="file" id="uploadfile" name="uploadfile" hidden />
					</div>
					
					<div style="align-self:flex-end;">
						<input type="submit" name="btn_upload" class="inputbtn darkblue" value="Upload Email File">
					</div>
				</div>
				<hr style="border:0;border-top:1px solid #8f9091ff;margin:10px 0;width:100%;">
			</form>
<?php
			}
?>

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
			print "<table class=\"striped\">";
			print "<thead>";
			print "<tr class=\"light-xtec-blue\">";
			print "<th class=\"tableheader\">Date</th>";
			print "<th class=\"tableheader\">Action</th>";					
			print "</tr>";
			print "</thead>";
			print "<tbody>";			
			for ($i = 0; $i < $nds; $i++)
			{
				print "<tr>";
				print "<td><p>" . htmlentities($dset[$i]['ldate'], ENT_QUOTES, 'UTF-8') . "</p></td>";
				print "<td><p>" . htmlentities($dset[$i]['lmsg'], ENT_QUOTES, 'UTF-8') . "</p></td>";
				print "</tr>";
			}
			print "</table>";
		}
?>
			</div>
		</div>
	</div>
</div>
<?php
	$myform->frmrender_side($bsideparams);
	print "</div>\n";// end of inner-flex class
?>	
</div>
<?php
	$myform->frmrender_footer_wlogo($footerparams);
	print "</div>\n"; // close .main

	echo "<script>";
	echo "useridInp = \"".$userid."\"";
	echo "</script>";
?>
</body></html>