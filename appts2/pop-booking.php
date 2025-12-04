<?php
// $Id:$

// popup to perform booking creation/cancellation
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-booking.php";
// the geometry required for this popup
$windowx = 500;
$windowy = 560;

include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
require_once(_AUTHENTX5APPCORE."/cl-mail.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);
$mymail = new authentxmail();

// validate the session
$sr = $myappt->validatesession($_siteid);
if ($sr !== true)
{
	print "<script type=\"text/javascript\">alert('".$sr."')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Get admin privileges: make appointment privileges.
if ($myappt->checkprivilege(PRIV_APPT) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

$priv_apptedit = $myappt->checkprivilege(PRIV_APPTEDIT);

// GET arguments: 
// st: slot start timestamp, 
// uuid: user uuid, 
// apptuuid: uuid of appointment being selected - only present when viewing/cancelling an appointment
// avc: mac using st.uuid or st.uuid.apptuuid as the base.

if (isset($_GET["st"]))
{
	$slotstamp = $_GET["st"];
	// check and sanitise it
	if (!is_numeric($slotstamp))
	{
		print "<script type=\"text/javascript\">alert('Invalid time.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
{
	print "<script type=\"text/javascript\">alert('Time not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

if (isset($_GET["uuid"]))
{
	$u_uuid = $_GET["uuid"];
	// check and sanitise it
	if (strlen($u_uuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid uuid.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
{
	print "<script type=\"text/javascript\">alert('UUID not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// apptuuid only specified if we are viewing/cancelling an appointment
if (isset($_GET["apptuuid"]))
{
	$apptuuid = $_GET["apptuuid"];
	// check and sanitise it
	if (strlen($apptuuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid appointment ID.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
	$apptuuid = false;
	
// centeruuid only specified if we are creating a new appointment
if (isset($_GET["center"]))
{
	$centeruuid = $_GET["center"];
	// check and sanitise it
	if (strlen($centeruuid) != 36)
	{
		print "<script type=\"text/javascript\">alert('Invalid center ID.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
{
	// if this is a new appointment we need to know the site
	if ($apptuuid === false)	// Is a new appt, so we must have the center to continue.
	{
		print "<script type=\"text/javascript\">alert('Center ID not specified.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	else
		$centeruuid = false;	// Not a new appt. Will read the center details from the appt record.
}
	
if (isset($_GET["avc"]))
	$avc = $_GET["avc"];
else
{
	print "<script type=\"text/javascript\">alert('AVC not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Check the AVC mac for validity
if ($apptuuid === false)
	$testavc = $myappt->session_createmac($slotstamp.$u_uuid.$centeruuid);	// Creating a new appt
else
	$testavc = $myappt->session_createmac($slotstamp.$u_uuid.$apptuuid);		// Viewing/cancelling existing appt
	
if (strcasecmp($avc, $testavc) != 0)
{
	print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Read database info for form
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Mail templates
	$et_booking = $myappt->getmailtemplate($sdbh, MTDB_BOOKING);
	$et_confirm = $myappt->getmailtemplate($sdbh, MTDB_CONFIRM);
	$et_cancel = $myappt->getmailtemplate($sdbh, MTDB_CANCEL);
	$et_invite = $myappt->getmailtemplate($sdbh, MTDB_INVITE);
	
	// Get the user detail
	$userdetail = $myappt->readuserdetail($sdbh, $u_uuid);
	if (count($userdetail) ==0)
	{
		$sdbh->close();
		print "<script type=\"text/javascript\">alert('User not found.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}

	// How many future appointments does this user have?
	$n_fappt = $myappt->readuserfutureapptqty($sdbh, $u_uuid);
	
	// Get the appt detail (if required) which includes the center and appt owner detail
	if ($apptuuid !== false)
	{
		$apptdetail = $myappt->readappointmentdetail($sdbh, $apptuuid);
		if (count($apptdetail) == 0)
		{
			$sdbh->close();
			print "<script type=\"text/javascript\">alert('Appointment not found.')</script>\n";
			print "<script type=\"text/javascript\">window.close()</script>\n";
			die();
		}
	}
	else
	{
		// New appt, so populate with the user info for the form
		$apptdetail["uuid"] = $u_uuid;
		$apptdetail["uname"] = $userdetail["uname"];
		$apptdetail["email"] = $userdetail["email"];
		$apptdetail["phone"] = $userdetail["phone"];
		$apptdetail["component"] = $userdetail["component"];
	}
	
	// Get the site detail (if required)
	if ($centeruuid !== false)
	{
		$sitedetail = $myappt->readsitedetail($sdbh, $centeruuid);
		if (count($sitedetail) == 0)
		{
			$sdbh->close();
			print "<script type=\"text/javascript\">alert('Site not found.')</script>\n";
			print "<script type=\"text/javascript\">window.close()</script>\n";
			die();
		}

		if (!isset($apptdetail["sitename"]))
		{
			$apptdetail["sitenotifyemail"] = $sitedetail["sitenotifyemail"];
			$apptdetail["sitename"] = $sitedetail["sitename"];
			$apptdetail["siteaddress"] = $sitedetail["siteaddress"];
			$apptdetail["siteaddrstate"] = $sitedetail["siteaddrstate"];
			$apptdetail["siteaddrcountry"] = $sitedetail["siteaddrcountry"];
		}
	}

	
	$appt_booked = false;
	//*** Process booking create/cancel submission here
	if (isset($_POST["submit_appt"]))
	{
		if ($priv_apptedit)
		{
			//*** Making or deleting an appointment on behalf of others

			if ($apptuuid === false)
			{
				//*** Create new appt

				// check for user email to locate or create a new appointment owner
				if (isset($_POST["u_email"]))
				{
					$u_email = filter_var(trim($_POST["u_email"]), FILTER_SANITIZE_EMAIL);
					$u_email = strip_tags($u_email);
					if (strpos($u_email, '@') === false)
						$u_email = "";
					if ($u_email == "")
						$u_email = false;
				}
				else
					$u_email = false;

				if ($u_email === false)
				{
					if (!empty($userdetail["email"]))
						$u_email = $userdetail["email"];
				}
				
				// User name
				$u_name = trim($_POST["u_name"]);
					
				// Contact phone number
				if (isset($_POST["phone"]))
				{
					$u_phone = trim($_POST["phone"]);
					$u_phone = htmlspecialchars($u_phone, ENT_QUOTES | ENT_HTML5);
					$u_phone = strip_tags($u_phone);
					if ($u_phone == "")
						$u_phone = false;
				}
				else
					$u_phone = false;

				if ($u_phone === false)
				{
					if (!empty($userdetail["phone"]))
						$u_phone = $userdetail["phone"];
				}
				
				if (($u_phone === false) && ($_cfg_appt_nophone === false))
				{
					// Phone is mandatory, so this is an error
					$sdbh->close();
					print "<script type=\"text/javascript\">alert('Contact phone number is required.')</script>\n";
					print "<script type=\"text/javascript\">window.close()</script>\n";
					die();
				}
				
				// Component
				if (isset($_POST["component"]))
				{
					$component = trim($_POST["component"]);
					$component = strip_tags($component);
					if ($component == "")
						$component = false;
				}
				else
					$component = false;

				if ($component === false)
				{
					if (!empty($userdetail["component"]))
						$component = $userdetail["component"];
				}
				
				if (($component === false) && ($_cfg_appt_nocomponent === false))
				{
					// Component is mandatory, so this is an error
					$sdbh->close();
					print "<script type=\"text/javascript\">alert('Component is required.')</script>\n";
					print "<script type=\"text/javascript\">window.close()</script>\n";
					die();
				}
				
				// Appointment reason
				if (isset($_POST["u_apptrsn"]))
				{
					$u_apptrsn = trim($_POST["u_apptrsn"]);
					$u_apptrsn = strip_tags($u_apptrsn);
					if ($u_apptrsn == "")
						$u_apptrsn = false;
				}
				else
					$u_apptrsn = false;
				
				if (($u_apptrsn === false) && ($_cfg_appt_noreason === false))
				{
					// Appt reason is mandatory, so this is an error
					$sdbh->close();
					print "<script type=\"text/javascript\">alert('Appointment reason is required.')</script>\n";
					print "<script type=\"text/javascript\">window.close()</script>\n";
					die();
				}
			
				if ($u_email === false)
				{
					if ($_cfg_appt_noemail === true)
					{
						// create a new user with a system generated userid (':' + time + random 2 chars)
						$r1 = @rand(0, 26);
						$rc1 = chr(ord('A') + $r1);
						$uts = time();
						$r2 = @rand(0, 26);
						$rc2 = chr(ord('A') + $r2);
						$u_userid = ":".$uts.$rc1.$rc2;
						
						$u_uuid = $myappt->makeuniqueuuid($sdbh, "user", "uuid");
						
						$q_nu = "insert into user "
							. "\n set "
							. "\n userid='".$sdbh->real_escape_string($u_userid)."', "
							. "\n uuid='".$sdbh->real_escape_string($u_uuid)."', "
							. "\n uname='".$sdbh->real_escape_string($u_name)."', "
							. "\n component='".$sdbh->real_escape_string($component)."', "
							. "\n phone='".$sdbh->real_escape_string($u_phone)."', "
							. "\n status='".USTATUS_ACTIVE."', "
							. "\n ucreate='".(gmdate("Y-m-d H:i:s"))."', "
							. "\n privilege='0', "
							. "\n tabmask='0', "
							. "\n xsyncmts='".time()."' "
							;
							
						$s_nu = $sdbh->query($q_nu);

						if ($s_nu)
						{
							// Create a log entry
							$logstring = "User ".$u_name." (Userid: ".$u_userid.") (UUID: ".$u_uuid.") created.";
							$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_NEWUSER);
						}
					}
					else
					{
						// Email address is mandatory
						$sdbh->close();
						print "<script type=\"text/javascript\">alert('Email address is required.')</script>\n";
						print "<script type=\"text/javascript\">window.close()</script>\n";
						die();
					}
				}
				else
				{
					// We have an email, check whether the user exists (it may even be the admin making an appointment)
					$re = $myappt->readuserdetailbyemail($sdbh, $u_email);
					if (count($re) > 0)
					{
						// existing user
						$u_uuid = $re["uuid"];
						$u_name = $re["uname"];
						$u_email = $re["email"];
						$vc = $myappt->buildquerystring($u_name, $u_email);
						$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
					}
					else
					{
						// can't find the user - create a new one using the email address as the userid
						$u_userid = $u_email;
						$u_uuid = $myappt->makeuniqueuuid($sdbh, "user", "uuid");
						
						$q_nu = "insert into user "
							. "\n set "
							. "\n userid='".$sdbh->real_escape_string($u_userid)."', "
							. "\n uuid='".$sdbh->real_escape_string($u_uuid)."', "
							. "\n email='".$sdbh->real_escape_string($u_email)."', "
							. "\n component='".$sdbh->real_escape_string($component)."', "
							. "\n uname='".$sdbh->real_escape_string($u_name)."', "
							. "\n phone='".$sdbh->real_escape_string($u_phone)."', "
							. "\n status='".USTATUS_ACTIVE."', "
							. "\n ucreate='".(gmdate("Y-m-d H:i:s"))."', "
							. "\n privilege='1', "
							. "\n tabmask='0', "
							. "\n xsyncmts='".time()."' "
							;

						$s_nu = $sdbh->query($q_nu);
						if ($s_nu)
						{
							// Create a log entry
							$logstring = "User ".$u_name." (UUID: ".$u_uuid.") created.";
							$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_NEWUSER);

							if ($u_email != "")
							{
								// Send an email to the user with a URL for self-managing the appointment
								$vc = $myappt->buildquerystring($u_name, $u_email);
								$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
							}
							else
								$mail_vurl="No email address supplied";
									
							// email the invitation
							$mailbody = $et_booking["mtbody"];
							$mail_adminname = $myappt->session_getuuname();
							$mailbody = str_ireplace(ET_ADMINNAME, $mail_adminname, $mailbody);
							$mailbody = str_ireplace(ET_VURL, $mail_vurl, $mailbody);
							$mailbody = wordwrap($mailbody, 70);
								
							$mymail->setFrom($et_booking["mtfrom"]);
							$mymail->clearaddresses();
							$mymail->clearcc();
							$mymail->addaddress($u_email);
							// Site notify email address as cc
							if ($apptdetail["sitenotifyemail"] !== false)
								$mymail->addcclist($apptdetail["sitenotifyemail"]);
							$mymail->setSubject($et_booking["mtsubject"]);
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
								$logstring = "Management email sent for user ".$u_name." (".$u_email.").";
								$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_INVITEUSER);
							}
						}
					}
				}
				
				// Find each site that the user has future appointments at.
				// Array (numeric) of all detail for each future appointment.
				$userappointments = $myappt->readfutureappointmentsforuser($sdbh, $u_uuid);
				$n_fappt = count($userappointments);
				
				// in case contact phone number was being changed	
				$u_phone = trim($_POST["phone"]);
				$q_phone = "update user set "
						. "\n phone='".$sdbh->real_escape_string($u_phone)."', "
						. "\n xsyncmts='".time()."' "
						. "\n where uuid='".$sdbh->real_escape_string($u_uuid)."' "
						. "\n limit 1"
						;
				$s_phone = $sdbh->query($q_phone);
					
				// check user's appointment count
				if (MAXUSERAPPTS != 0)
				{
					if ($n_fappt >= MAXUSERAPPTS)
					{
						$sdbh->close();
						print "<script type=\"text/javascript\">alert('Maximum of ".MAXUSERAPPTS." active appointments per user.')</script>\n";
						print "<script type=\"text/javascript\">window.close()</script>\n";
						die();
					}
				}
				
				// Check availability first for the site and slotstamp specified in the form
				$slotdt = date("Y-m-d H:i:s", $slotstamp);
				
				if ($myappt->isslotavailable($sdbh, $centeruuid, $slotdt, $sitedetail["slottime"], $sitedetail["tzoneoffset"]))
				{
					// Available - create the appointment entry, save and set appt_booked to true to show detail
					// Create a reference number using (u_uuid.centeruuid.slotstamp.time)
					$t_now = time();
					$ar_hex = $myappt->session_createmac($u_uuid.$centeruuid.$slotstamp.$t_now);
					$apptref_hex = substr($ar_hex, -9, 3)."-".substr($ar_hex, -6, 3)."-".substr($ar_hex, -3, 3);
					
					// Create a uuid for the appointment record
					$apptuuid = $myappt->makeuniqueuuid($sdbh, "appointment", "apptuuid");
									
					$q_book = "insert into appointment "
							. "\n set "
							. "\n uuid='".$sdbh->real_escape_string($u_uuid)."', "
							. "\n apptuuid='".$sdbh->real_escape_string($apptuuid)."', "
							. "\n starttime='".$sdbh->real_escape_string($slotdt)."', "
							. "\n apptref='".$sdbh->real_escape_string($apptref_hex)."', "
							. "\n apptcreate='".date("Y-m-d H:i:s")."', "
							. "\n apptrsn='".$sdbh->real_escape_string($u_apptrsn)."', "
							. "\n centeruuid='".$sdbh->real_escape_string($centeruuid)."', "
							. "\n xsyncmts='".time()."' "
							;
					$s_book = $sdbh->query($q_book);
					
					if ($s_book)
					{
						// log the appointment
						$sitedetail = $myappt->readsitedetail($sdbh, $centeruuid);
						$logstring = "Appointment created for user ".$u_name." at ".$sitedetail["sitename"]." on ".date("Y-m-d H:i T", $slotstamp);
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_NEWAPPT);
					}
					else
					{
						$sitedetail = $myappt->readsitedetail($sdbh, $centeruuid);
						$logstring = "Appointment failed to create for user ".$u_name." at ".$sitedetail["sitename"]." on ".date("Y-m-d H:i T", $slotstamp);
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_ERRORAPPT);
					}

					// Read the appointment detail to display
					$apptdetail = $myappt->readappointmentdetail($sdbh, $apptuuid);
					if (count($apptdetail) == 0)
					{
						$sdbh->close();
						print "<script type=\"text/javascript\">alert('Appointment failed to save.')</script>\n";
						print "<script type=\"text/javascript\">window.close()</script>\n";
						die();
					}
					
					// Send an email confirming the appointment creation
					if ($_apptemail_confirm === true)
					{
						if (($u_email != "") || ($apptdetail["sitenotifyemail"] !== false))
						{
							$mime_boundary = "APPOINTMENT_BOOKING_".md5(time());
							
							if ($_apptemail_ical)
								$mailbody = "--".$mime_boundary."\n";
							else 
								$mailbody = "";
							if ($_apptemail_ical)
							{
								$mailbody .= "Content-Type: text/plain\n";
								$mailbody .= "Content-Transfer-Encoding: 8bit\n\n";
							}
							$mailbody .= $et_confirm["mtbody"];
							$mail_apptdetail = "New Appointment Detail\n"
										. "Date: ".date("D jS M Y", $slotstamp)."\n"
										. "Time: ".date("H:i", $slotstamp)."\n"
										. "Reason: ".$apptdetail["apptrsn"]."\n"
										. "Ref: ".$apptdetail["apptref"]."\n"
										. "Site: ".$apptdetail["sitename"]."\n"
										. "Address: ".$apptdetail["siteaddress"]."\n"
										. "City: ".$apptdetail["siteaddrcity"]."\n"
										. "State: ".$apptdetail["siteaddrstate"]."\n"
										. "Zip: ".$apptdetail["siteaddrzip"]."\n"
										. "Country: ".$apptdetail["siteaddrcountry"]."\n"
										. "Contact name: ".$apptdetail["sitecontactname"]."\n"
										. "Contact phone: ".$apptdetail["sitecontactphone"]."\n"
										;
							$mailbody = str_ireplace(ET_APPTDETAIL, $mail_apptdetail, $mailbody);
							$mailbody = wordwrap($mailbody, 70);
							
							if ($_apptemail_ical)
							{
								// Add the icalendar vevent
								$mailbody .= "--".$mime_boundary."\n";
								$mailbody .= "Content-Type:application/calendar;name=\"Add_appointment.ics\";charset=utf-8\r\n";
								$mailbody .= "Content-Disposition:attachment;filename=\"Add_appointment.ics\"\r\n";
		    					$mailbody .= "Content-Transfer-Encoding:quoted-printable\r\n\n";
		    					
		    					if ($u_email != "")
		    					{
		    						// Send an email to the user with a URL for self-managing the appointment
		    						$vc = $myappt->buildquerystring($u_name, $u_email);
		    						$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
		    					}
		    					
		    					$p = array();
		    					$p["PRODID"] = "-//XTec Inc//Authentx//EN";
		    					$p["VERSION"] = "2.0";
		    					$p["METHOD"] = "PUBLISH";
		    					if ($u_email != "")
			    					$p["URL"] = $mail_vurl;
			    				if (isset($mail_adminname))
			    					$p["ORGANIZER"] = $mail_adminname;
			    				else 
			    					$p["ORGANIZER"] = $u_name;
			    				$p["DTSTART"] = gmdate("Ymd\THis\Z", $slotstamp);
			    				$p["DTEND"] = gmdate("Ymd\THis\Z", ($slotstamp + $apptdetail["slottime"] * 60));
			    				$p["DTSTAMP"] = gmdate("Ymd\THis\Z");
			    				$p["LAST-MODIFIED"] = gmdate("Ymd\THis\Z");
			    				$p["LOCATION"] = $apptdetail["sitename"]." (".str_replace(",", "", $apptdetail["siteaddress"])." ".$apptdetail["siteaddrcountry"].")";
			    				$p["CONTACT"] = $apptdetail["sitecontactname"]." (".$apptdetail["sitecontactphone"].")";
			    				$p["UID"] = $apptdetail["apptref"];
			    				$p["SUMMARY"] = $apptdetail["apptrsn"];
			    				$p["DESCRIPTION"] = $apptdetail["apptrsn"]." at ".$p["LOCATION"];
			    				$p["TRANSP"] = "OPAQUE";
			    				$p["PRIORITY"] = "5";
			    				$p["STATUS"] = "CONFIRMED";
			    				$p["CLASS"] = "PUBLIC";
			    				$p["SEQUENCE"] = "0";
			    				
			    				$mailbody .= $myappt->cal_build_ical_event($p);
								$mailbody .= "--".$mime_boundary."\n";
						
    							$mymail->setContenttype("multipart/mixed; boundary=\"".$mime_boundary."\"");
							}

							$mymail->setFrom($et_confirm["mtfrom"]);
							$mymail->clearaddresses();
							$mymail->clearcc();
							if ($u_email != "")
							{
								$mymail->addaddress($u_email);
								// Site notify email address as cc
								if ($apptdetail["sitenotifyemail"] !== false)
									$mymail->addcclist($apptdetail["sitenotifyemail"]);
							}
							elseif ($apptdetail["sitenotifyemail"] !== false)
								$mymail->addaddresslist($apptdetail["sitenotifyemail"]);
								
							$mymail->setSubject($et_confirm["mtsubject"]);
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
								$logstring = "Confirmation email sent for user ".$u_name." (".$u_email.").";
								$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
							}
						}
						else 
						{
							// create a log entry
							$logstring = "Confirmation email could not be sent for user ".$u_name." (missing email address).";
							$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
						}
					}
					
					// refresh the calling form
					print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
					$appt_booked = true;
				}
				else
				{
					// Not available - alert the user, update the calling form and close
					$sdbh->close();
					print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
					print "<script type=\"text/javascript\">alert('Appointment no longer available.')</script>\n";
					print "<script type=\"text/javascript\">window.close()</script>\n";
					die();
				}
			}
			else 
			{
				//*** Cancel existing appt

				// Log the action
				$logstring = "Appointment deleted for user ".$apptdetail["uname"]." at ".$apptdetail["sitename"]." on ".date("Y-m-d H:i", $slotstamp)." by ".$userdetail["uname"];
				$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_DELETEAPPT);
				
				// Read the appointment detail to display
				$apptdetail = $myappt->readappointmentdetail($sdbh, $apptuuid);
					
				// delete the entry
				$q_can = "delete from appointment "
					. "\n where apptuuid='".$sdbh->real_escape_string($apptuuid)."' "
					. "\n limit 1"
					;
				$s_can = $sdbh->query($q_can);
				if ($s_can)
					$myappt->adddeletedrow($sdbh, "appointment", "apptuuid", $apptuuid);
					
				// Send an email confirming the appointment cancellation
				if ($_apptemail_cancel === true)
				{		
					if ((($apptdetail["email"] != "") && ($apptdetail["email"] != NULL)) || ($apptdetail["sitenotifyemail"] !== false))
					{
						$mime_boundary = "APPOINTMENT_CANCELLATION_".md5(time());
								
						if ($_apptemail_ical)
							$mailbody = "--".$mime_boundary."\n";
						else
							$mailbody = "";
						if ($_apptemail_ical)
						{
							$mailbody .= "Content-Type: text/plain\n";
							$mailbody .= "Content-Transfer-Encoding: 8bit\n\n";
						}
						$mailbody .= $et_cancel["mtbody"];
						$mail_apptdetail = "Cancelled Appointment Detail\n"
									. "Date: ".date("D jS M Y", $slotstamp)."\n"
									. "Time: ".date("H:i", $slotstamp)."\n"
									. "Reason: ".$apptdetail["apptrsn"]."\n"
									. "Ref: ".$apptdetail["apptref"]."\n"
									. "Site: ".$apptdetail["sitename"]."\n"
									. "Address: ".$apptdetail["siteaddress"]."\n"
									. "City: ".$apptdetail["siteaddrcity"]."\n"
									. "State: ".$apptdetail["siteaddrstate"]."\n"
									. "Zip: ".$apptdetail["siteaddrzip"]."\n"
									. "Country: ".$apptdetail["siteaddrcountry"]."\n"
									. "Contact name: ".$apptdetail["sitecontactname"]."\n"
									. "Contact phone: ".$apptdetail["sitecontactphone"]."\n"
									;
						$mailbody = str_ireplace(ET_APPTDETAIL, $mail_apptdetail, $mailbody);
						$mailbody = wordwrap($mailbody, 70);
									
						if ($_apptemail_ical)
						{
							// Add the icalendar vevent
							$mailbody .= "--".$mime_boundary."\n";
							$mailbody .= "Content-Type:application/calendar;name=\"Cancel_appointment.ics\";charset=utf-8\r\n";
							$mailbody .= "Content-Disposition:attachment;filename=\"Cancel_appointment.ics\"\r\n";
							$mailbody .= "Content-Transfer-Encoding:quoted-printable\r\n\n";
									
							$p = array();
							$p["PRODID"] = "-//XTec Inc//Authentx//EN";
							$p["VERSION"] = "2.0";
							$p["METHOD"] = "PUBLISH";
							if (isset($mail_adminname))
								$p["ORGANIZER"] = $mail_adminname;
							else 
								$p["ORGANIZER"] = $userdetail["uname"];
							$p["DTSTART"] = gmdate("Ymd\THis\Z", $slotstamp);
							$p["DTEND"] = gmdate("Ymd\THis\Z", ($slotstamp + $apptdetail["slottime"] * 60));
							$p["DTSTAMP"] = gmdate("Ymd\THis\Z");
							$p["LAST-MODIFIED"] = gmdate("Ymd\THis\Z");
							$p["LOCATION"] = $apptdetail["sitename"]." (".str_replace(",", "", $apptdetail["siteaddress"])." ".$apptdetail["siteaddrcountry"].")";
							$p["CONTACT"] = $apptdetail["sitecontactname"]." (".$apptdetail["sitecontactphone"].")";
							$p["UID"] = $apptdetail["apptref"];
							$p["SUMMARY"] = $apptdetail["apptrsn"];
							$p["DESCRIPTION"] = $apptdetail["apptrsn"]." at ".$p["LOCATION"];
							$p["TRANSP"] = "OPAQUE";
							$p["PRIORITY"] = "5";
							$p["STATUS"] = "CANCELLED";
							$p["CLASS"] = "PUBLIC";
							$p["SEQUENCE"] = "1";
									
							$mailbody .= $myappt->cal_build_ical_event($p);
							$mailbody .= "--".$mime_boundary."\n";
								
							$mymail->setContenttype("multipart/mixed; boundary=\"".$mime_boundary."\"");
						}
							
						$mymail->setFrom($et_cancel["mtfrom"]);
						$mymail->clearaddresses();
						$mymail->clearcc();
						if ($userdetail["email"] != "")
						{
							$mymail->addaddress($userdetail["email"]);
							// Site notify email address as cc
							if ($apptdetail["sitenotifyemail"] !== false)
								$mymail->addcclist($apptdetail["sitenotifyemail"]);
						}
						elseif ($apptdetail["sitenotifyemail"] !== false)
							$mymail->addaddresslist($apptdetail["sitenotifyemail"]);
							
						$mymail->setSubject($et_cancel["mtsubject"]);
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
							$logstring = "Cancellation email sent for user ".$userdetail["uname"]." (".$userdetail["email"].").";
							$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
						}
					}	// cancel missing info
					else 
					{
						// create a log entry
						$logstring = "Cancellation email could not be sent for user ".$userdetail["uname"]." (missing email address).";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
					}
				}	// cancel email enabled

				// Let the user know, update the calling form and close
				$sdbh->close();
				print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
				print "<script type=\"text/javascript\">alert('Appointment cancelled.')</script>\n";
				print "<script type=\"text/javascript\">window.close()</script>\n";
				die();
			}	// cancel existing appt
		}	// creating or cancelling on behalf of others
		else
		{
			//*** Making own appointment - proceed as normal.

			// Update the database with the contents of the phone entry
			if (isset($_POST["phone"]))
			{
				$u_phone = htmlspecialchars($_POST["phone"], ENT_QUOTES | ENT_HTML5);
				$u_phone = strip_tags($u_phone);
				$q_phone = "update user "
					. "\n set phone='".$sdbh->real_escape_string($u_phone)."', "
					. "\n xsyncmts='".time()."' "
					. "\n where uuid='".$sdbh->real_escape_string($u_uuid)."' "
					. "\n limit 1"
					;
				$s_phone = $sdbh->query($q_phone);
			}

			// Component may need to be entered as it is not part of the invitation process
			if (isset($_POST["component"]))
			{
				$component = htmlspecialchars($_POST["component"], ENT_QUOTES | ENT_HTML5);
				$component = strip_tags($component);
				$q_component = "update user "
					. "\n set component='".$sdbh->real_escape_string($component)."', "
					. "\n xsyncmts='".time()."' "
					. "\n where uuid='".$sdbh->real_escape_string($u_uuid)."' "
					. "\n limit 1"
					;
				$s_component = $sdbh->query($q_component);
			}
		
			//*** Create new appt
			if ($apptuuid === false)
			{	
				// check user's appointment count
				if (MAXUSERAPPTS != 0)
				{
					if ($n_fappt >= MAXUSERAPPTS)
					{
						$sdbh->close();
						print "<script type=\"text/javascript\">alert('Maximum of ".MAXUSERAPPTS." active appointments reached.')</script>\n";
						print "<script type=\"text/javascript\">window.close()</script>\n";
						die();
					}
				}
				
				// appointment reason
				$u_apptrsn = trim($_POST["u_apptrsn"]);
				$u_apptrsn = strip_tags($u_apptrsn);
				
				// Check availability first
				$slotdt = date("Y-m-d H:i:s", $slotstamp);
				if ($myappt->isslotavailable($sdbh, $centeruuid, $slotdt, $sitedetail["slottime"], $sitedetail["tzoneoffset"]))
				{
					// Available - create the appointment entry, save and set appt_booked to true to show detail
					// Create a reference number using (uuid.centeruuid.slotstamp.time)
					$t_now = time();
					// Get a unique index for the appointment
					$apptuuid = $myappt->makeuniqueuuid($sdbh, "appointment", "apptuuid");
					
					$ar_hex = $myappt->session_createmac($u_uuid.$centeruuid.$slotstamp.$t_now);
					$apptref_hex = substr($ar_hex, -9, 3)."-".substr($ar_hex, -6, 3)."-".substr($ar_hex, -3, 3);
								
					$q_book = "insert into appointment "
							. "\n set "
							. "\n uuid='".$sdbh->real_escape_string($u_uuid)."', "
							. "\n starttime='".$sdbh->real_escape_string($slotdt)."', "
							. "\n apptref='".$sdbh->real_escape_string($apptref_hex)."', "
							. "\n apptcreate='".date("Y-m-d H:i:s")."', "
							. "\n apptrsn='".$sdbh->real_escape_string($u_apptrsn)."', "
							. "\n centeruuid='".$sdbh->real_escape_string($centeruuid)."', "
							. "\n apptuuid='".$sdbh->real_escape_string($apptuuid)."', "
							. "\n xsyncmts='".time()."' "
							;
					$s_book = $sdbh->query($q_book);
					
					if ($s_book)
					{
						// log the appointment
						$logstring = "Appointment created for user ".$userdetail["uname"]." at ".$sitedetail["sitename"]." on ".date("Y-m-d H:i", $slotstamp);
						$myappt->createlogentry($sdbh, $logstring, $u_uuid, ALOG_NEWAPPT);
					}
					else
					{
						$logstring = "Appointment failed to create for user ".$userdetail["uname"]." at ".$sitedetail["sitename"]." on ".date("Y-m-d H:i", $slotstamp);
						$myappt->createlogentry($sdbh, $logstring, $u_uuid, ALOG_ERRORAPPT);
					}

					// Read the appointment detail to display
					$apptdetail = $myappt->readappointmentdetail($sdbh, $apptuuid);
					if (count($apptdetail) == 0)
					{
						$sdbh->close();
						print "<script type=\"text/javascript\">alert('Appointment failed to save.')</script>\n";
						print "<script type=\"text/javascript\">window.close()</script>\n";
						die();
					}

					$u_userid = $apptdetail["userid"];
					$u_name = $apptdetail["uname"];
					$u_email = $apptdetail["email"];
					if ($u_email != "")
					{
						$vc = $myappt->buildquerystring($u_name, $u_email);
						$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
					}
					
					// Send an email confirming the appointment creation
					if ($_apptemail_confirm === true)
					{
						if (($u_email != "") || ($apptdetail["sitenotifyemail"] !== false))
						{
							$mime_boundary = "APPOINTMENT_BOOKING_".md5(time());
								
							if ($_apptemail_ical)
								$mailbody = "--".$mime_boundary."\n";
							else
								$mailbody = "";
							if ($_apptemail_ical)
							{
								$mailbody .= "Content-Type: text/plain\n";
								$mailbody .= "Content-Transfer-Encoding: 8bit\n\n";
							}
							$mailbody .= $et_confirm["mtbody"];
							$mail_apptdetail = "New Appointment Detail\n"
										. "Date: ".date("D jS M Y", $slotstamp)."\n"
										. "Time: ".date("H:i", $slotstamp)."\n"
										. "Reason: ".$apptdetail["apptrsn"]."\n"
										. "Ref: ".$apptdetail["apptref"]."\n"
										. "Site: ".$apptdetail["sitename"]."\n"
										. "Address: ".$apptdetail["siteaddress"]."\n"
										. "City: ".$apptdetail["siteaddrcity"]."\n"
										. "State: ".$apptdetail["siteaddrstate"]."\n"
										. "Zip: ".$apptdetail["siteaddrzip"]."\n"
										. "Country: ".$apptdetail["siteaddrcountry"]."\n"
										. "Contact name: ".$apptdetail["sitecontactname"]."\n"
										. "Contact phone: ".$apptdetail["sitecontactphone"]."\n"
										;
							$mailbody = str_ireplace(ET_APPTDETAIL, $mail_apptdetail, $mailbody);
							$mailbody = wordwrap($mailbody, 70);
								
							if ($_apptemail_ical)
							{
								// Add the icalendar vevent
								$mailbody .= "--".$mime_boundary."\n";
								$mailbody .= "Content-Type:application/calendar;name=\"Add_appointment.ics\";charset=utf-8\r\n";
								$mailbody .= "Content-Disposition:attachment;filename=\"Add_appointment.ics\"\r\n";
								$mailbody .= "Content-Transfer-Encoding:quoted-printable\r\n\n";
									
								$p = array();
								$p["PRODID"] = "-//XTec Inc//Authentx//EN";
								$p["VERSION"] = "2.0";
								$p["METHOD"] = "PUBLISH";
								if ($u_email != "")
									$p["URL"] = $mail_vurl;
								if (isset($mail_adminname))
									$p["ORGANIZER"] = $mail_adminname;
								else 
									$p["ORGANIZER"] = $u_name;
								$p["DTSTART"] = gmdate("Ymd\THis\Z", $slotstamp);
								$p["DTEND"] = gmdate("Ymd\THis\Z", ($slotstamp + $apptdetail["slottime"] * 60));
								$p["DTSTAMP"] = gmdate("Ymd\THis\Z");
								$p["LAST-MODIFIED"] = gmdate("Ymd\THis\Z");
								$p["LOCATION"] = $apptdetail["sitename"]." (".str_replace(",", "", $apptdetail["siteaddress"])." ".$apptdetail["siteaddrcountry"].")";
								$p["CONTACT"] = $apptdetail["sitecontactname"]." (".$apptdetail["sitecontactphone"].")";
								$p["UID"] = $apptdetail["apptref"];
								$p["SUMMARY"] = $apptdetail["apptrsn"];
								$p["DESCRIPTION"] = $apptdetail["apptrsn"]." at ".$p["LOCATION"];
								$p["TRANSP"] = "OPAQUE";
								$p["PRIORITY"] = "5";
								$p["STATUS"] = "CONFIRMED";
								$p["CLASS"] = "PUBLIC";
								$p["SEQUENCE"] = "0";
									
								$mailbody .= $myappt->cal_build_ical_event($p);
								$mailbody .= "--".$mime_boundary."\n";
								
								$mymail->setContenttype("multipart/mixed; boundary=\"".$mime_boundary."\"");
							}
									
							$mymail->setFrom($et_confirm["mtfrom"]);
							$mymail->clearaddresses();
							$mymail->clearcc();
							if ($u_email != "")
							{
								$mymail->addaddress($u_email);
								// Site notify email address as cc
								if ($apptdetail["sitenotifyemail"] !== false)
									$mymail->addcclist($apptdetail["sitenotifyemail"]);
							}
							elseif($apptdetail["sitenotifyemail"] !== false)
								$mymail->addaddresslist($apptdetail["sitenotifyemail"]);
							$mymail->setSubject($et_confirm["mtsubject"]);
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
								$logstring = "Confirmation email sent for user ".$u_name."(".$u_email.").";
								$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
							}
							else 
							{
								// create a log entry
								$logstring = "Confirmation email could not be sent for user ".$u_name."(missing email address).";
								$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
							}
						}
					}
					
					// refresh the calling form
					print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
					$appt_booked = true;
				}
				else
				{
					// Not available - alert the user, update the calling form and close
					$sdbh->close();
					print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
					print "<script type=\"text/javascript\">alert('Appointment no longer available.')</script>\n";
					print "<script type=\"text/javascript\">window.close()</script>\n";
					die();
				}
			}
			else 
			{
				//*** Cancel existing appt

				// Log the action first
				$logstring = "Appointment deleted for user ".$userdetail["uname"]." at ".$apptdetail["sitename"]." on ".date("Y-m-d H:i", $slotstamp);
				$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_DELETEAPPT);
				
				// Read the appointment detail
				$apptdetail = $myappt->readappointmentdetail($sdbh, $apptuuid);

				$u_userid = $apptdetail["userid"];
				$u_name = $apptdetail["uname"];
				$u_email = $apptdetail["email"];
				if ($u_email != "")
				{
					$vc = $myappt->buildquerystring($u_name, $u_email);
					$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
				}

				// delete the entry
				$q_can = "delete from appointment "
					. "\n where apptuuid='".$sdbh->real_escape_string($apptuuid)."' "
					. "\n limit 1"
					;
				$s_can = $sdbh->query($q_can);
				if ($s_can)
					$myappt->adddeletedrow($sdbh, "appointment", "apptuuid", $apptuuid);
				
				// Send an email confirming the appointment cancellation
				if ($_apptemail_cancel === true)
				{		
					if (($u_email != "") || ($apptdetail["sitenotifyemail"] !== false))
					{
						$mime_boundary = "APPOINTMENT_CANCELLATION_".md5(time());
						
						if ($_apptemail_ical)
							$mailbody = "--".$mime_boundary."\n";
						else
							$mailbody = "";
								
						$mailbody .= "Content-Type: text/plain\n";
						$mailbody .= "Content-Transfer-Encoding: 8bit\n\n";
						$mailbody = $et_cancel["mtbody"];
						$mail_apptdetail = "Cancelled Appointment Detail\n"
									. "Date: ".date("D jS M Y", $slotstamp)."\n"
									. "Time: ".date("H:i", $slotstamp)."\n"
									. "Reason: ".$apptdetail["apptrsn"]."\n"
									. "Ref: ".$apptdetail["apptref"]."\n"
									. "Site: ".$apptdetail["sitename"]."\n"
									. "Address: ".$apptdetail["siteaddress"]."\n"
									. "City: ".$apptdetail["siteaddrcity"]."\n"
									. "State: ".$apptdetail["siteaddrstate"]."\n"
									. "Zip: ".$apptdetail["siteaddrzip"]."\n"
									. "Country: ".$apptdetail["siteaddrcountry"]."\n"
									. "Contact name: ".$apptdetail["sitecontactname"]."\n"
									. "Contact phone: ".$apptdetail["sitecontactphone"]."\n"
									;
						$mailbody = str_ireplace(ET_APPTDETAIL, $mail_apptdetail, $mailbody);
						$mailbody = wordwrap($mailbody, 70);
								
						if ($_apptemail_ical)
						{
							// Add the icalendar vevent
							$mailbody .= "--".$mime_boundary."\n";
							$mailbody .= "Content-Type:application/calendar;name=\"Cancel_appointment.ics\";charset=utf-8\r\n";
							$mailbody .= "Content-Disposition:attachment;filename=\"Cancel_appointment.ics\"\r\n";
							$mailbody .= "Content-Transfer-Encoding:quoted-printable\r\n\n";
										
							$p = array();
							$p["PRODID"] = "-//XTec Inc//Authentx//EN";
							$p["VERSION"] = "2.0";
							$p["METHOD"] = "PUBLISH";
							if ($u_email != "")
								$p["URL"] = $mail_vurl;
							if (isset($mail_adminname))
								$p["ORGANIZER"] = $mail_adminname;
							else 
								$p["ORGANIZER"] = $u_name;
							$p["DTSTART"] = gmdate("Ymd\THis\Z", $slotstamp);
							$p["DTEND"] = gmdate("Ymd\THis\Z", ($slotstamp + $apptdetail["slottime"] * 60));
							$p["DTSTAMP"] = gmdate("Ymd\THis\Z");
							$p["LAST-MODIFIED"] = gmdate("Ymd\THis\Z");
							$p["LOCATION"] = $apptdetail["sitename"]." (".str_replace(",", "", $apptdetail["siteaddress"])." ".$apptdetail["siteaddrcountry"].")";
							$p["CONTACT"] = $apptdetail["sitecontactname"]." (".$apptdetail["sitecontactphone"].")";
							$p["UID"] = $apptdetail["apptref"];
							$p["SUMMARY"] = $apptdetail["apptrsn"];
							$p["DESCRIPTION"] = $apptdetail["apptrsn"]." at ".$p["LOCATION"];
							$p["TRANSP"] = "OPAQUE";
							$p["PRIORITY"] = "5";
							$p["STATUS"] = "CANCELLED";
							$p["CLASS"] = "PUBLIC";
							$p["SEQUENCE"] = "1";
										
							$mailbody .= $myappt->cal_build_ical_event($p);
							$mailbody .= "--".$mime_boundary."\n";
									
							$mymail->setContenttype("multipart/mixed; boundary=\"".$mime_boundary."\"");
						}
							
						$mymail->setFrom($et_cancel["mtfrom"]);
						$mymail->clearaddresses();
						$mymail->clearcc();
						if ($u_email != "")
						{
							$mymail->addaddress($u_email);
							// Site notify email address as cc
							if ($apptdetail["sitenotifyemail"] !== false)
								$mymail->addcclist($apptdetail["sitenotifyemail"]);
						}
						elseif($apptdetail["sitenotifyemail"] !== false)
							$mymail->addaddresslist($apptdetail["sitenotifyemail"]);
						$mymail->setSubject($et_cancel["mtsubject"]);
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
							$logstring = "Cancellation email sent for user ".$u_name."(".$u_email.").";
							$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
						}
					}
					else 
					{
						// create a log entry
						$logstring = "Cancellation email could not be sent for user ".$u_name."(missing email address).";
						$myappt->createlogentry($sdbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
					}
				}

				// Let the user know, update the calling form and close
				$sdbh->close();
				print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
				print "<script type=\"text/javascript\">alert('Appointment cancelled.')</script>\n";
				print "<script type=\"text/javascript\">window.close()</script>\n";
				die();
			}	// Cancel appt
		}	// Own appt
	}	// Form submission
	$sdbh->close();
}
else 
{
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Display appointment or confirmation notice

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<title>Appointment</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<?php
// build a function to copy the admin user's data across
if ($priv_apptedit && ($apptuuid === false))
{
?>
<script language="javascript">
function copymydata()
{
	document.forms["apptprops"].elements["u_name"].value = "<?php print htmlentities($userdetail["uname"]) ?>";
	document.forms["apptprops"].elements["u_email"].value = "<?php print htmlentities($userdetail["email"]) ?>";
	document.forms["apptprops"].elements["phone"].value = "<?php print htmlentities($userdetail["phone"]) ?>";
	for (var i = 0; i < document.forms["apptprops"].elements["component"].options.length; i++) 
	{
    	if (document.forms["apptprops"].elements["component"].options[i].value == '<?php print htmlentities($userdetail["component"]) ?>')
	        document.forms["apptprops"].elements["component"].options[i].selected = true;
	}

}	
</script>
<?php
}
?>
<?php print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n"; ?>
</head>
<body>
<?php
if ($appt_booked === false)
{
	// show the appointment create/cancel form
?>
<table border="1" cellspacing="0" cellpadding="5" style='border: 1px gray;' width="440" bgcolor="#ffffff">
<tr height="30">
<td valign="top" align="right" colspan="2"><input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" tabindex="200">
</td>
</tr>
<form name="apptprops" method="post"  autocomplete="off" action="<?php print $formfile."?st=".urlencode($slotstamp)."&uuid=".urlencode($u_uuid).($apptuuid === false ? "" : "&apptuuid=".urlencode($apptuuid)).($centeruuid === false ? "" : "&center=".urlencode($centeruuid))."&avc=".urlencode($avc) ?>" >
<tr height="30">
<td valign="top" width="200" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Date</span></td>
<td valign="top" width="240" style='border-bottom: 1px gray;'><span class="proptext"><?php print date("D jS M Y", $slotstamp) ?></span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Time</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print date("H:i", $slotstamp) ?></span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Reference</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print (isset($apptdetail["apptref"]) ? htmlentities($apptdetail["apptref"]) : "&nbsp;") ?></span></td>
</tr><tr height="30">
<?php
	if ($priv_apptedit)
	{
		if ($apptuuid === false)
		{
			// Can enter the Name and email details
?>
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Full Name *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<input type="text" size="36" maxlength="60" tabindex="10" name="u_name" id="u_name" value="" /></span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Email *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<input type="text" size="36" maxlength="60" tabindex="20" name="u_email" id="u_email" value="" /></span></td>
<tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Agency *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<select name="component" id="component" tabindex="30" style="width: 22em">
<?php
			$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
			$rc = count($listcomponent);
			for ($i = 0; $i < $rc; $i++)
			{
				if (strcasecmp($userdetail["component"], $listcomponent[$i][0]) == 0)
					print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
				else
					print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
			}
?>
</select>
</span></td>
<?php
		}
		else
		{
?>
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Full Name *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print htmlentities($apptdetail["uname"]) ?></span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Email *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<?php 
			// Email not present, but is required - allow entering it here
			if ((trim($apptdetail["email"]) == "") && ($_cfg_appt_noemail === false))
			{
				print "<input type=\"text\" size=\"36\" maxlength=\"60\" tabindex=\"20\" name=\"u_email\" id=\"u_email\" value=\"".htmlentities($apptdetail["email"])."\" />";
			}
			else
			{
				print htmlentities($apptdetail["email"]); 
			}
?>
</span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Agency *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<?php
			if ((trim($apptdetail["component"]) == "") && ($_cfg_appt_nocomponent === false))
			{
				print "<select name=\"component\" id=\"component\" tabindex=\"30\" style=\"width: 22em\">\n";
				$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
				$rc = count($listcomponent);
				for ($i = 0; $i < $rc; $i++)
				{
					if (strcasecmp($apptdetail["component"], $listcomponent[$i][0]) == 0)
						print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
					else
						print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
				}
				print "</select>\n";
			}
			else
				print htmlentities($apptdetail["component"]);
?>
</span></td>
<?php
		}
	}
	else
	{
		// No apptedit privilege
?>
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Full Name *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print htmlentities($apptdetail["uname"]) ?></span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Email *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<?php 
		// Email not present, but is required - allow entering it here
		if ((trim($apptdetail["email"]) == "") && ($_cfg_appt_noemail === false))
		{
			print "<input type=\"text\" size=\"36\" maxlength=\"60\" tabindex=\"20\" name=\"u_email\" id=\"u_email\" value=\"".htmlentities($apptdetail["email"])."\" />";
		}
		else
		{
			print htmlentities($apptdetail["email"]);
		}
?>
</span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Agency *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<?php
		// Component not present and is required - allow entering it here
		if ((trim($apptdetail["component"]) == "") && ($_cfg_appt_nocomponent === false))
		{
			print "<select name=\"component\" id=\"component\" tabindex=\"30\" style=\"width: 22em\">\n";
			$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
			$rc = count($listcomponent);
			for ($i = 0; $i < $rc; $i++)
			{
				if (strcasecmp($apptdetail["component"], $listcomponent[$i][0]) == 0)
					print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
				else
					print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
			}
			print "</select>\n";
		}
		else
			print htmlentities($apptdetail["component"]); 
?>
</span></td>
<?php
	}
?>
</tr><tr height="30">
<?php
	if ($priv_apptedit)
	{
		if ($apptuuid === false)
		{
			// Can enter the phone details - initialise as blank
?>
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Phone *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><input type="text" size="36" maxlength="40" tabindex="20" name="phone" id="phone" value="" /></td>
<?php
		}
		else
		{
?>	
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Phone *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<?php 
			// Phone not present, but is required - allow entering it here
			if ((trim($apptdetail["phone"]) == "") && ($_cfg_appt_nophone === false))
			{
				print "<input type=\"text\" size=\"36\" maxlength=\"60\" tabindex=\"20\" name=\"u_email\" id=\"u_email\" value=\"".htmlentities($apptdetail["phone"])."\" />";
			}
			else
			{
				print htmlentities($apptdetail["phone"]);
			}
?>
</span></td>
<?php
		}
	}
	else
	{
?>
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Phone *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><input type="text" size="36" maxlength="40" tabindex="20" name="phone" value="<?php print $userdetail["phone"] ?>" /></td>
<?php
	}
?>
</tr><tr height="30">
<?php
	if ($priv_apptedit)
	{
		if ($apptuuid === false)
		{
			// Can enter the appointment reason from dropdown list
?>
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Reason</span></td>
<td valign="top" style='border-bottom: 1px gray;'>
<select name="u_apptrsn" id="u_apptrsn" tabindex="50" style="width: 22em">
<?php
			$listapptrsn = $myappt->sortlistarray($listapptrsn, 1, SORT_ASC, SORT_REGULAR);
			$rc = count($listapptrsn);
			for ($i = 0; $i < $rc; $i++)
				print "<option value=\"".$listapptrsn[$i][0]."\">".$listapptrsn[$i][1]."</option>\n";
?>
</select>
</td>
<?php
		}
		else
		{
?>	
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Reason</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print htmlentities($apptdetail["apptrsn"]) ?></span></td>
<?php
		}
	}
	else
	{
?>
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Reason</span></td>
<td valign="top" style='border-bottom: 1px gray;'>
<select name="u_apptrsn" id="u_apptrsn" tabindex="50" style="width: 22em">
<?php
		$listapptrsn = $myappt->sortlistarray($listapptrsn, 1, SORT_ASC, SORT_REGULAR);
		$rc = count($listapptrsn);
		for ($i = 0; $i < $rc; $i++)
		{
			if (isset($apptdetail["apptrsn"]))
			{
				if (strcasecmp($listapptrsn[$i][0], $apptdetail["apptrsn"]) == 0)
					print "<option selected value=\"".$listapptrsn[$i][0]."\">".$listapptrsn[$i][1]."</option>\n";
				else
					print "<option value=\"".$listapptrsn[$i][0]."\">".$listapptrsn[$i][1]."</option>\n";
			}
			else
				print "<option value=\"".$listapptrsn[$i][0]."\">".$listapptrsn[$i][1]."</option>\n";
		}
?>
</select>
</td>
<?php
	}
?>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Site</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print htmlentities($apptdetail["sitename"]) ?></span></td>
</tr><tr>
<td valign="top" style='border-right: 1px gray;'><span class="proplabel">Address</span></td>
<td valign="top"><span class="proptext"><?php print htmlentities($apptdetail["siteaddress"]) ?></span></td>
</tr>
<tr>
<td valign="top" style='border-right: 1px gray;'><span class="proplabel">State</span></td>
<td valign="top"><span class="proptext"><?php print htmlentities($apptdetail["siteaddrstate"]) ?></span></td>
</tr>
<tr>
<td valign="top" style='border-right: 1px gray;'><span class="proplabel">Country</span></td>
<td valign="top"><span class="proptext"><?php print htmlentities($apptdetail["siteaddrcountry"]) ?></span></td>
</tr>
</table>
<table cellspacing="0" cellpadding="10" width="440" border="0" style="border-collapse:collapse;">
<tr height="40">
<td valign="top" align="left">
<input type="submit" name="submit_appt" class="btntext" value="<?php print ($apptuuid === false ? "Book Appointment" : "Cancel Appointment") ?>" tabindex="100" />
</td>
<?php
	if ($priv_apptedit && ($apptuuid === false))
	{
		// copies the admin user's data into the fields to assist in making own appointments
?>
<td valign="top" align="right">
<input type="button" name="btn_copymydata" class="btntext" value="My Appointment" onclick="javascript:copymydata()" title="Copy my data into this form" />
</td>
<?php
	}
?>
</tr></table>
</form>
<?php
}
else
{
	// show the new appointment details
?>
<script type="text/javascript">window.resizeTo(500,500)</script>
<span class="lblblktext">Appointment Detail</span><p/>
<span class="proplabel">Date: </span><span class="proptext"><?php print date("D jS M Y", $slotstamp) ?></span><br/>
<span class="proplabel">Time: </span><span class="proptext"><?php print date("H:i", $slotstamp) ?></span><br/>
<span class="proplabel">Reason: </span><span class="proptext"><?php print htmlentities($apptdetail["apptrsn"]) ?></span><br/>
<span class="proplabel">Ref: </span><span class="proptext"><?php print htmlentities($apptdetail["apptref"]) ?></span><br/>
<span class="proplabel">Site: </span><span class="proptext"><?php print htmlentities($apptdetail["sitename"]) ?></span><br/>
<span class="proplabel">Address: </span><span class="proptext"><?php print htmlentities($apptdetail["siteaddress"]) ?></span><br/>
<span class="proplabel">State: </span><span class="proptext"><?php print htmlentities($apptdetail["siteaddrstate"]) ?></span><br/>
<span class="proplabel">Country: </span><span class="proptext"><?php print htmlentities($apptdetail["siteaddrcountry"]) ?></span><br/>
<span class="proplabel">Contact name: </span><span class="proptext"><?php print htmlentities($apptdetail["sitecontactname"]) ?></span><br/>
<span class="proplabel">Contact phone: </span><span class="proptext"><?php print htmlentities($apptdetail["sitecontactphone"]) ?></span><br/>
<p/>
<input type="button" name="close" class="btntext" value="Close" onclick="javascript:window.close()" />
<?php
}
?>
</body></html>