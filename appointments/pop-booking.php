<?php
// $Id:$

// popup to perform booking creation/cancellation
session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-booking.html";
// the geometry required for this popup
$windowx = 550;
$windowy = 600;

include("config.php");
include("vec-clappointments.php");
include_once("../appcore/vec-clmail.php");
require_once('../appcore/vec-clforms.php');

$myappt = new authentxappointments();
$mymail = new authentxmail();
$myform = new authentxforms();

date_default_timezone_set(DATE_TIMEZONE);

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
// uid: user id, 
// apptid: id of appointment being selected - only present when viewing/cancelling an appointment
// avc: mac using st.uid or st.uid.apptid as the base.

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
}
else
{
	print "<script type=\"text/javascript\">alert('UID not specified.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// apptid only specified if we are viewing/cancelling an appointment
if (isset($_GET["apptid"]))
{
	$apptid = $_GET["apptid"];
	// check and sanitise it
	if (!is_numeric($apptid))
	{
		print "<script type=\"text/javascript\">alert('Invalid appointment.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
	$apptid = false;
	
// siteid only specified if we are creating a new appointment
if (isset($_GET["site"]))
{
	$siteid = $_GET["site"];
	// check and sanitise it
	if (!is_numeric($siteid))
	{
		print "<script type=\"text/javascript\">alert('Invalid site.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
}
else
{
	// if this is a new appointment we need to know the site
	if ($apptid === false)
	{
		print "<script type=\"text/javascript\">alert('Site not specified.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
		die();
	}
	else
		$siteid = false;
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
if ($apptid === false)
	$testavc = $myappt->session_createmac($slotstamp.$uid.$siteid);
else
	$testavc = $myappt->session_createmac($slotstamp.$uid.$apptid);
	
if (strcasecmp($avc, $testavc) != 0)
{
	print "<script type=\"text/javascript\">alert('Invalid AVC.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Read database info for form
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	// Mail templates
	$et_booking = $myappt->getmailtemplate($dbh, MTDB_BOOKING);
	$et_confirm = $myappt->getmailtemplate($dbh, MTDB_CONFIRM);
	$et_cancel = $myappt->getmailtemplate($dbh, MTDB_CANCEL);
	$et_invite = $myappt->getmailtemplate($dbh, MTDB_INVITE);
	
	// Get the user detail
	$q_u = "select * from user "
		. "\n where uid='".$dbh->real_escape_string($uid)."' "
		;
	$s_u = $dbh->query($q_u);
	if ($s_u)
	{
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
		$s_u->free();
	}

	// How many future appointments does this user have?
	$q_fa = "select count(*) from appointment "
		. "\n where uid='".$dbh->real_escape_string($uid)."' "
		. "\n and starttime>'".date("Y-m-d H:i:s")."' "
		;
	$s_fa = $dbh->query($q_fa);
	$r_fa = $s_fa->fetch_assoc();
	if ($s_fa)
	{
		$n_fa = $r_fa["count(*)"];
		$s_fa->free();
	}
	
	// Get the appt detail (if required)
	if ($apptid !== false)
	{
		$q_appt = "select * from appointment "
			. "\n where apptid='".$dbh->real_escape_string($apptid)."' "
			;
		$s_appt = $dbh->query($q_appt);
		if ($s_appt)
		{
			$n_appt = $s_appt->num_rows;
			if ($n_appt == 0)
			{
				$s_appt->free();
				$dbh->close();
				print "<script type=\"text/javascript\">alert('Appointment not found.')</script>\n";
				print "<script type=\"text/javascript\">window.close()</script>\n";
				die();
			}
			$r_appt = $s_appt->fetch_assoc();
			$s_appt->free();
			
			// Get the site details for the appt
			$sid = $r_appt["siteid"];
			$q_site = "select * from site "
				. "\n where siteid='".$sid."' "
				;
			$s_site = $dbh->query($q_site);
			if ($s_site)
			{
				$r_site = $s_site->fetch_assoc();
				$s_site->free();
				
				$sitenotifyemail = $r_site["sitenotifyemail"];
				if ($sitenotifyemail == NULL)
					$sitenotifyemail = false;
				
				// Get appointment owner's details
				$uid_appt = $r_appt["uid"];
				$q_u_appt = "select * from user "
						. "\n where uid='".$uid_appt."' "
						;
				$s_u_appt = $dbh->query($q_u_appt);
				if ($s_u_appt)
				{
					$r_u_appt = $s_u_appt->fetch_assoc();
					$s_u_appt->free();
				}
			}
		}
	}
	
	// Get the site detail (if required)
	if ($siteid !== false)
	{
		$q_site = "select * from site "
			. "\n where siteid='".$dbh->real_escape_string($siteid)."' "
			;
		$s_site = $dbh->query($q_site);
		if ($s_site)
		{
			$n_site = $s_site->num_rows;
			if ($n_site == 0)
			{
				$s_site->free();
				$dbh->close();
				print "<script type=\"text/javascript\">alert('Site not found.')</script>\n";
				print "<script type=\"text/javascript\">window.close()</script>\n";
				die();
			}
			$r_site = $s_site->fetch_assoc();
			$s_site->free();
			
			$sitenotifyemail = $r_site["sitenotifyemail"];
			if ($sitenotifyemail == NULL)
				$sitenotifyemail = false;
			
			// calculate the timezone offset - automatically adjusted for DST
			$sitetimezone = $r_site["timezone"];
			if (($sitetimezone == "") || ($sitetimezone == NULL))
				$tzoneoffset = 0;
			else
			{
				$mytzone = new DateTimeZone($sitetimezone);
				$mydatetime = new DateTime("now", $mytzone);
				$tzoneoffset = $mytzone->getOffset($mydatetime);
			}
		}
	}
	
	
	$appt_booked = false;
	// Process booking create/cancel submission here
	if (isset($_POST["submit_appt"]))
	{
		if ($priv_apptedit)
		{
			// could be making or deleting an appointment on behalf of others
			if ($apptid === false)
			{
				// Create new appt
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
					if (!empty($r_u["email"]))
						$u_email = $r_u["email"];
				}
				
				// User name
				$u_name = trim($_POST["u_name"]);
					
				// Contact phone number
				if (isset($_POST["phone"]))
				{
					$u_phone = filter_var(trim($_POST["phone"]), FILTER_SANITIZE_STRING, (FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK));
					$u_phone = strip_tags($u_phone);
					if ($u_phone == "")
						$u_phone = false;
				}
				else
					$u_phone = false;
				if ($u_phone === false)
				{
					if (!empty($r_u["phone"]))
						$u_phone = $r_u["phone"];
				}
				
				if (($u_phone === false) && ($_cfg_appt_nophone === false))
				{
					// Phone is mandatory, so this is an error
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
					if (!empty($r_u["component"]))
						$component = $r_u["component"];
				}
				
				if (($component === false) && ($_cfg_appt_nocomponent === false))
				{
					// Component is mandatory, so this is an error
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
						
						$q_nu = "insert into user "
							. "\n set "
							. "\n userid='".$dbh->real_escape_string($u_userid)."', "
							. "\n uname='".$dbh->real_escape_string($u_name)."', "
							. "\n component='".$dbh->real_escape_string($component)."', "
							. "\n phone='".$dbh->real_escape_string($u_phone)."', "
							. "\n status='".USTATUS_ACTIVE."', "
							. "\n ucreate='".(gmdate("Y-m-d H:i:s"))."', "
							. "\n privilege='0', "
							. "\n tabmask='0' "
							;
							
						$s_nu = $dbh->query($q_nu);
						if ($s_nu)
						{
							$uid_appt = $dbh->insert_id;
						
							// Create a log entry
							$logstring = "User ".$u_name." [".$u_userid."] (".$uid_appt.") created.";
							$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_NEWUSER);
						}
					}
					else
					{
						// Email address is mandatory
						print "<script type=\"text/javascript\">alert('Email address is required.')</script>\n";
						print "<script type=\"text/javascript\">window.close()</script>\n";
						die();
					}
				}
				else
				{
					// check whether the user exists (it may even be the admin making an appointment)
					$q_eu = "select * from user "
						. "\n where userid='".$dbh->real_escape_string($u_email)."' "
						. "\n or email='".$dbh->real_escape_string($u_email)."' "
						;
					$s_eu = $dbh->query($q_eu);
					if ($s_eu)
					{
						$n_eu = $s_eu->num_rows;
						if ($n_eu > 0)
						{
							// existing user - get the uid
							$r_eu = $s_eu->fetch_assoc();
							$uid_appt = $r_eu["uid"];
							$u_name = $r_eu["uname"];
							$u_email = $r_eu["email"];
							$vc = $myappt->buildquerystring($u_name, $u_email);
							$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
							$s_eu->free();
						}
						else
						{
							// can't find the user - create a new one using the email address as the userid
							$u_userid = $u_email;
							$q_nu = "insert into user "
								. "\n set "
								. "\n userid='".$dbh->real_escape_string($u_userid)."', "
								. "\n email='".$dbh->real_escape_string($u_email)."', "
								. "\n component='".$dbh->real_escape_string($component)."', "
								. "\n uname='".$dbh->real_escape_string($u_name)."', "
								. "\n phone='".$dbh->real_escape_string($u_phone)."', "
								. "\n status='".USTATUS_ACTIVE."', "
								. "\n ucreate='".(gmdate("Y-m-d H:i:s"))."', "
								. "\n privilege='1', "
								. "\n tabmask='0' "
								;
							
							$s_nu = $dbh->query($q_nu);
							if ($s_nu)
							{
								$uid_appt = $dbh->insert_id;
							
								// Create a log entry
								$logstring = "User ".$u_name." (".$uid_appt.") created.";
								$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_NEWUSER);
							
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
								
								$mymail->from = $et_booking["mtfrom"];
								$mymail->clearaddresses();
								$mymail->clearcc();
								$mymail->addaddress($u_email);
								// Site notify email address as cc
								if ($sitenotifyemail !== false)
									$mymail->addcclist($sitenotifyemail);
								$mymail->subject = $et_booking["mtsubject"];
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
									$logstring = "Management email sent for user ".$u_name."(".$u_email.").";
									$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_INVITEUSER);
								}
							}
						}
					}
				}
				
				// Find each site that the user has appointments at.
				$q_ua = "select * "
					. "\n from appointment "
					. "\n left join site on site.siteid=appointment.siteid "
					. "\n where uid='".$dbh->real_escape_string($uid_appt)."' "
					;
				$s_ua = $dbh->query($q_ua);
				$n_fa = 0;
				if ($s_ua)
				{
					while ($r_ua = $s_ua->fetch_assoc())
					{
						$sitetimezone = $r_ua["timezone"];
						if (($sitetimezone == "") || ($sitetimezone == NULL))
							$sitezoneoffset = 0;
						else
						{
							$mytzone = new DateTimeZone($sitetimezone);
							$mydatetime = new DateTime("now", $mytzone);
							$sitezoneoffset = $mytzone->getOffset($mydatetime);
						}
						$currenttimestamp = $myappt->gmtime() + $sitezoneoffset;
						
						$apptdatetime = $r_ua["starttime"];
						$apptdatetimestamp = strtotime($apptdatetime);
						if ($apptdatetimestamp > $currenttimestamp)
							$n_fa++;
					}
					$s_ua->free();
				}
				
				// in case contact phone number was being changed	
				$u_phone = trim($_POST["phone"]);
				$q_phone = "update user "
						. "\n set phone='".$dbh->real_escape_string($u_phone)."' "
						. "\n where uid='".$dbh->real_escape_string($uid_appt)."' "
						. "\n limit 1"
						;
				$s_phone = $dbh->query($q_phone);
					
				// check user's appointment count
				if (MAXUSERAPPTS != 0)
				{
					if ($n_fa >= MAXUSERAPPTS)
					{
						print "<script type=\"text/javascript\">alert('Maximum of ".MAXUSERAPPTS." active appointments per user.')</script>\n";
						print "<script type=\"text/javascript\">window.close()</script>\n";
						die();
					}
				}
				
				// Check availability first
				$slotdt = date("Y-m-d H:i:s", $slotstamp);
				
				if ($myappt->isslotavailable($dbh, $siteid, $slotdt, $r_site["slottime"], $tzoneoffset))
				{
					// Available - create the appointment entry, save and set appt_booked to true to show detail
					// Create a reference number using (uid.siteid.slotstamp.time)
					$t_now = time();
					$ar_hex = $myappt->session_createmac($uid.$siteid.$slotstamp.$t_now);
					$apptref_hex = substr($ar_hex, -9, 3)."-".substr($ar_hex, -6, 3)."-".substr($ar_hex, -3, 3);
									
					$q_book = "insert into appointment "
							. "\n set "
							. "\n uid='".$dbh->real_escape_string($uid_appt)."', "
							. "\n starttime='".$dbh->real_escape_string($slotdt)."', "
							. "\n apptref='".$dbh->real_escape_string($apptref_hex)."', "
							. "\n apptcreate='".date("Y-m-d H:i:s")."', "
							. "\n apptrsn='".$dbh->real_escape_string($u_apptrsn)."', "
							. "\n siteid='".$dbh->real_escape_string($siteid)."' "
							;
					$s_book = $dbh->query($q_book);
					$apptid = $dbh->insert_id;
					if ($s_book)
					{
						// log the appointment
						$logstring = "Appointment created for user ".$u_name." at ".$r_site["sitename"]." on ".date("Y-m-d H:i", $slotstamp);
						$myappt->createlogentry($dbh, $logstring, $uid, ALOG_NEWAPPT);
					}
					else
					{
						$logstring = "Appointment failed to create for user ".$u_name." at ".$r_site["sitename"]." on ".date("Y-m-d H:i", $slotstamp);
						$myappt->createlogentry($dbh, $logstring, $uid, ALOG_ERRORAPPT);
					}

					// Read the appointment detail to display
					$q_appt = "select * from appointment "
						. "\n left join site on site.siteid=appointment.siteid "
						. "\n where apptid='".$dbh->real_escape_string($apptid)."' "
						;
					$s_appt = $dbh->query($q_appt);
					if ($s_appt)
					{
						$n_appt = $s_appt->num_rows;
						if ($n_appt == 0)
						{
							$s_appt->free();
							$dbh->close();
							print "<script type=\"text/javascript\">alert('Appointment failed to save.')</script>\n";
							print "<script type=\"text/javascript\">window.close()</script>\n";
							die();
						}
						$r_appt = $s_appt->fetch_assoc();
						$s_appt->free();
					}
					
					// Send an email confirming the appointment creation
					if ($_apptemail_confirm === true)
					{
						if (($u_email != "") || ($sitenotifyemail !== false))
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
							$mailbody .= $et_confirm["mtbody"]."\n\n";
							
							$mail_apptdetail = "New Appointment Detail\n"
										. "Date: ".date("D jS M Y", $slotstamp)."\n"
										. "Time: ".date("H:i", $slotstamp)."\n"
										. "Reason: ".$r_appt["apptrsn"]."\n"
										. "Ref: ".$r_appt["apptref"]."\n"
										. "Site: ".$r_appt["sitename"]."\n"
										. "Address: ".$r_appt["siteaddress"]."\n"
										. "City: ".$r_appt["siteaddrcity"]."\n"
										. "State: ".$r_appt["siteaddrstate"]."\n"
										. "Zip: ".$r_appt["siteaddrzip"]."\n"
										. "Country: ".$r_appt["siteaddrcountry"]."\n"
										. "Contact name: ".$r_appt["sitecontactname"]."\n"
										. "Contact phone: ".$r_appt["sitecontactphone"]."\n"
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
			    				$p["DTEND"] = gmdate("Ymd\THis\Z", ($slotstamp + $r_site["slottime"] * 60));
			    				$p["DTSTAMP"] = gmdate("Ymd\THis\Z");
			    				$p["LAST-MODIFIED"] = gmdate("Ymd\THis\Z");
			    				$p["LOCATION"] = $r_appt["sitename"]." (".str_replace(",", "", $r_appt["siteaddress"])." ".$r_appt["siteaddrcountry"].")";
			    				$p["CONTACT"] = $r_appt["sitecontactname"]." (".$r_appt["sitecontactphone"].")";
			    				$p["UID"] = $r_appt["apptref"];
			    				$p["SUMMARY"] = $r_appt["apptrsn"];
			    				$p["DESCRIPTION"] = $r_appt["apptrsn"]." at ".$p["LOCATION"];
			    				$p["TRANSP"] = "OPAQUE";
			    				$p["PRIORITY"] = "5";
			    				$p["STATUS"] = "CONFIRMED";
			    				$p["CLASS"] = "PUBLIC";
			    				$p["SEQUENCE"] = "0";
			    				
			    				$mailbody .= $myappt->cal_build_ical_event($p);
								$mailbody .= "--".$mime_boundary."\n";
						
    							$mymail->contenttype = "multipart/mixed; boundary=\"".$mime_boundary."\"";
							}

							$mymail->from = $et_confirm["mtfrom"];
							$mymail->clearaddresses();
							$mymail->clearcc();
							if ($u_email != "")
							{
								$mymail->addaddress($u_email);
								// Site notify email address as cc
								if ($sitenotifyemail !== false)
									$mymail->addcclist($sitenotifyemail);
							}
							elseif ($sitenotifyemail !== false)
								$mymail->addaddresslist($sitenotifyemail);
								
							$mymail->subject = $et_confirm["mtsubject"];
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
								$logstring = "Confirmation email sent for user ".$u_name."(".$u_email.").";
								$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
							}
						}
						else 
						{
							// create a log entry
							$logstring = "Confirmation email could not be sent for user ".$u_name."(missing email address).";
							$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
						}
					}
					
					// refresh the calling form
					print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
					$appt_booked = true;
				}
				else
				{
					// Not available - alert the user, update the calling form and close
					print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
					print "<script type=\"text/javascript\">alert('Appointment no longer available.')</script>\n";
					print "<script type=\"text/javascript\">window.close()</script>\n";
					die();
				}
			}
			else 
			{
				// Cancel existing appt
				// Log the action
				$logstring = "Appointment deleted for user ".$r_u_appt["uname"]." at ".$r_site["sitename"]." on ".date("Y-m-d H:i", $slotstamp)." by ".$r_u["uname"];
				$myappt->createlogentry($dbh, $logstring, $uid, ALOG_DELETEAPPT);
				
				// Read the appointment detail to display
				$q_appt = "select * from appointment "
					. "\n left join site on site.siteid=appointment.siteid "
					. "\n left join user on user.uid=appointment.uid "
					. "\n where apptid='".$dbh->real_escape_string($apptid)."' "
					;
				$s_appt = $dbh->query($q_appt);
				if ($s_appt)
				{
					$n_appt = $s_appt->num_rows;
					$r_appt = $s_appt->fetch_assoc();
					$s_appt->free();
					
					// delete the entry
					$q_can = "delete from appointment "
						. "\n where apptid='".$dbh->real_escape_string($apptid)."' "
						. "\n limit 1"
						;
					$s_can = $dbh->query($q_can);
					
					// Send an email confirming the appointment cancellation
					if ($_apptemail_cancel === true)
					{
						$u_userid = $r_appt["userid"];
						$u_name = $r_appt["uname"];
						$u_email = $r_appt["email"];
						if ($u_email != "")
						{
							$vc = $myappt->buildquerystring($u_name, $u_email);
							$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
						}
							
						if ((($u_email != "") && ($u_email != NULL)) || ($sitenotifyemail !== false))
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
										. "Reason: ".$r_appt["apptrsn"]."\n"
										. "Ref: ".$r_appt["apptref"]."\n"
										. "Site: ".$r_appt["sitename"]."\n"
										. "Address: ".$r_appt["siteaddress"]."\n"
										. "City: ".$r_appt["siteaddrcity"]."\n"
										. "State: ".$r_appt["siteaddrstate"]."\n"
										. "Zip: ".$r_appt["siteaddrzip"]."\n"
										. "Country: ".$r_appt["siteaddrcountry"]."\n"
										. "Contact name: ".$r_appt["sitecontactname"]."\n"
										. "Contact phone: ".$r_appt["sitecontactphone"]."\n"
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
								$p["DTEND"] = gmdate("Ymd\THis\Z", ($slotstamp + $r_site["slottime"] * 60));
								$p["DTSTAMP"] = gmdate("Ymd\THis\Z");
								$p["LAST-MODIFIED"] = gmdate("Ymd\THis\Z");
								$p["LOCATION"] = $r_appt["sitename"]." (".str_replace(",", "", $r_appt["siteaddress"])." ".$r_appt["siteaddrcountry"].")";
								$p["CONTACT"] = $r_appt["sitecontactname"]." (".$r_appt["sitecontactphone"].")";
								$p["UID"] = $r_appt["apptref"];
								$p["SUMMARY"] = $r_appt["apptrsn"];
								$p["DESCRIPTION"] = $r_appt["apptrsn"]." at ".$p["LOCATION"];
								$p["TRANSP"] = "OPAQUE";
								$p["PRIORITY"] = "5";
								$p["STATUS"] = "CANCELLED";
								$p["CLASS"] = "PUBLIC";
								$p["SEQUENCE"] = "1";
									
								$mailbody .= $myappt->cal_build_ical_event($p);
								$mailbody .= "--".$mime_boundary."\n";
								
								$mymail->contenttype = "multipart/mixed; boundary=\"".$mime_boundary."\"";
							}
							
							$mymail->from = $et_cancel["mtfrom"];
							$mymail->clearaddresses();
							$mymail->clearcc();
							if ($u_email != "")
							{
								$mymail->addaddress($u_email);
								// Site notify email address as cc
								if ($sitenotifyemail !== false)
									$mymail->addcclist($sitenotifyemail);
							}
							elseif ($sitenotifyemail !== false)
								$mymail->addaddresslist($sitenotifyemail);
								
							$mymail->subject = $et_cancel["mtsubject"];
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
								$logstring = "Cancellation email sent for user ".$u_name."(".$u_email.").";
								$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
							}
						}
						else 
						{
							// create a log entry
							$logstring = "Cancellation email could not be sent for user ".$u_name."(missing email address).";
							$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
						}
					}
					
					// Let the user know, update the calling form and close
					print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
					print "<script type=\"text/javascript\">alert('Appointment cancelled.')</script>\n";
					print "<script type=\"text/javascript\">window.close()</script>\n";
					die();
				}
			}
		}
		else
		{
			// Making own appointment - proceed as normal.
			// Update the database with the contents of the phone entry
			$u_phone = filter_var(trim($_POST["phone"]), FILTER_SANITIZE_STRING, (FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK));
			$u_phone = strip_tags($u_phone);
			
			// Contact phone number
			if (isset($_POST["phone"]))
			{
				$u_phone = filter_var(trim($_POST["phone"]), FILTER_SANITIZE_STRING, (FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK));
				$u_phone = strip_tags($u_phone);
				if ($u_phone == "")
					$u_phone = false;
			}
			else
				$u_phone = false;
			if ($u_phone === false)
			{
				if (!empty($r_u["phone"]))
					$u_phone = $r_u["phone"];
			}
			
			if (($u_phone === false) && ($_cfg_appt_nophone === false))
			{
				// Phone is mandatory, so this is an error
				print "<script type=\"text/javascript\">alert('Contact phone number is required.')</script>\n";
				print "<script type=\"text/javascript\">window.close()</script>\n";
				die();
			}
				
			
			$q_phone = "update user "
					. "\n set phone='".$dbh->real_escape_string($u_phone)."' "
					. "\n where uid='".$dbh->real_escape_string($uid)."' "
					. "\n limit 1"
					;
			$s_phone = $dbh->query($q_phone);
		
			// Create new appt
			if ($apptid === false)
			{	
				// check user's appointment count
				if (MAXUSERAPPTS != 0)
				{
					if ($n_fa >= MAXUSERAPPTS)
					{
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
				if ($myappt->isslotavailable($dbh, $siteid, $slotdt, $r_site["slottime"], $tzoneoffset))
				{
					// Available - create the appointment entry, save and set appt_booked to true to show detail
					// Create a reference number using (uid.siteid.slotstamp.time)
					$t_now = time();
					$ar_hex = $myappt->session_createmac($uid.$siteid.$slotstamp.$t_now);
					$apptref_hex = substr($ar_hex, -9, 3)."-".substr($ar_hex, -6, 3)."-".substr($ar_hex, -3, 3);
									
					$q_book = "insert into appointment "
							. "\n set "
							. "\n uid='".$dbh->real_escape_string($uid)."', "
							. "\n starttime='".$dbh->real_escape_string($slotdt)."', "
							. "\n apptref='".$dbh->real_escape_string($apptref_hex)."', "
							. "\n apptcreate='".date("Y-m-d H:i:s")."', "
							. "\n apptrsn='".$dbh->real_escape_string($u_apptrsn)."', "
							. "\n siteid='".$dbh->real_escape_string($siteid)."' "
							;
					$s_book = $dbh->query($q_book);
					$apptid = $dbh->insert_id;
					if ($s_book)
					{
						// log the appointment
						$logstring = "Appointment created for user ".$r_u["uname"]." at ".$r_site["sitename"]." on ".date("Y-m-d H:i", $slotstamp);
						$myappt->createlogentry($dbh, $logstring, $uid, ALOG_NEWAPPT);
					}
					else
					{
						$logstring = "Appointment failed to create for user ".$r_u["uname"]." at ".$r_site["sitename"]." on ".date("Y-m-d H:i", $slotstamp);
						$myappt->createlogentry($dbh, $logstring, $uid, ALOG_ERRORAPPT);
					}
					// Read the appointment detail to display
					$q_appt = "select * from appointment "
						. "\n left join site on site.siteid=appointment.siteid "
						. "\n where apptid='".$dbh->real_escape_string($apptid)."' "
						;
					$s_appt = $dbh->query($q_appt);
					if ($s_appt)
					{
						$n_appt = $s_appt->num_rows;
						if ($n_appt == 0)
						{
							$s_appt->free();
							$dbh->close();
							print "<script type=\"text/javascript\">alert('Appointment failed to save.')</script>\n";
							print "<script type=\"text/javascript\">window.close()</script>\n";
							die();
						}
						$r_appt = $s_appt->fetch_assoc();
						$s_appt->free();
					
						// Send an email confirming the appointment creation
						if ($_apptemail_confirm === true)
						{
							$u_userid = $r_u["userid"];
							$u_name = $r_u["uname"];
							$u_email = $r_u["email"];
							if ($u_email != "")
							{
								$vc = $myappt->buildquerystring($u_name, $u_email);
								$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
							}
							
							if (($u_email != "") || ($sitenotifyemail !== false))
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
								$mailbody .= $et_confirm["mtbody"]."\n\n";
								
								$mail_apptdetail = "New Appointment Detail\n"
											. "Date: ".date("D jS M Y", $slotstamp)."\n"
											. "Time: ".date("H:i", $slotstamp)."\n"
											. "Reason: ".$r_appt["apptrsn"]."\n"
											. "Ref: ".$r_appt["apptref"]."\n"
											. "Site: ".$r_appt["sitename"]."\n"
											. "Address: ".$r_appt["siteaddress"]."\n"
											. "City: ".$r_appt["siteaddrcity"]."\n"
											. "State: ".$r_appt["siteaddrstate"]."\n"
											. "Zip: ".$r_appt["siteaddrzip"]."\n"
											. "Country: ".$r_appt["siteaddrcountry"]."\n"
											. "Contact name: ".$r_appt["sitecontactname"]."\n"
											. "Contact phone: ".$r_appt["sitecontactphone"]."\n"
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
									$p["DTEND"] = gmdate("Ymd\THis\Z", ($slotstamp + $r_site["slottime"] * 60));
									$p["DTSTAMP"] = gmdate("Ymd\THis\Z");
									$p["LAST-MODIFIED"] = gmdate("Ymd\THis\Z");
									$p["LOCATION"] = $r_appt["sitename"]." (".str_replace(",", "", $r_appt["siteaddress"])." ".$r_appt["siteaddrcountry"].")";
									$p["CONTACT"] = $r_appt["sitecontactname"]." (".$r_appt["sitecontactphone"].")";
									$p["UID"] = $r_appt["apptref"];
									$p["SUMMARY"] = $r_appt["apptrsn"];
									$p["DESCRIPTION"] = $r_appt["apptrsn"]." at ".$p["LOCATION"];
									$p["TRANSP"] = "OPAQUE";
									$p["PRIORITY"] = "5";
									$p["STATUS"] = "CONFIRMED";
									$p["CLASS"] = "PUBLIC";
									$p["SEQUENCE"] = "0";
									
									$mailbody .= $myappt->cal_build_ical_event($p);
									$mailbody .= "--".$mime_boundary."\n";
								
									$mymail->contenttype = "multipart/mixed; boundary=\"".$mime_boundary."\"";
								}
									
								$mymail->from = $et_confirm["mtfrom"];
								$mymail->clearaddresses();
								$mymail->clearcc();
								if ($u_email != "")
								{
									$mymail->addaddress($u_email);
									// Site notify email address as cc
									if ($sitenotifyemail !== false)
										$mymail->addcclist($sitenotifyemail);
								}
								elseif($sitenotifyemail !== false)
									$mymail->addaddresslist($sitenotifyemail);
								$mymail->subject = $et_confirm["mtsubject"];
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
									$logstring = "Confirmation email sent for user ".$u_name."(".$u_email.").";
									$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
								}
							}
							else 
							{
								// create a log entry
								$logstring = "Confirmation email could not be sent for user ".$u_name."(missing email address).";
								$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
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
					print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
					print "<script type=\"text/javascript\">alert('Appointment no longer available.')</script>\n";
					print "<script type=\"text/javascript\">window.close()</script>\n";
					die();
				}
			}
			else 
			{
				// Cancel existing appt
				// Log the action first
				$logstring = "Appointment deleted for user ".$r_u["uname"]." at ".$r_site["sitename"]." on ".date("Y-m-d H:i", $slotstamp);
				$myappt->createlogentry($dbh, $logstring, $uid, ALOG_DELETEAPPT);
				
				// Read the appointment detail to display
				$q_appt = "select * from appointment "
					. "\n left join site on site.siteid=appointment.siteid "
					. "\n where apptid='".$dbh->real_escape_string($apptid)."' "
					;
				$s_appt = $dbh->query($q_appt);
				if ($s_appt)
				{
					$n_appt = $s_appt->num_rows;
					$r_appt = $s_appt->fetch_assoc();
					$s_appt->free();
					
					// delete the entry
					$q_can = "delete from appointment "
						. "\n where apptid='".$dbh->real_escape_string($apptid)."' "
						. "\n limit 1"
						;
					$s_can = $dbh->query($q_can);
					
					// Send an email confirming the appointment cancellation
					if ($_apptemail_cancel === true)
					{
						$u_userid = $r_u["userid"];
						$u_name = $r_u["uname"];
						$u_email = $r_u["email"];
						if ($u_email != "")
						{
							$vc = $myappt->buildquerystring($u_name, $u_email);
							$mail_vurl = SITE_VALIDATEURL."?vc=".urlencode($vc);
						}
							
						if (($u_email != "") || ($sitenotifyemail !== false))
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
										. "Reason: ".$r_appt["apptrsn"]."\n"
										. "Ref: ".$r_appt["apptref"]."\n"
										. "Site: ".$r_appt["sitename"]."\n"
										. "Address: ".$r_appt["siteaddress"]."\n"
										. "City: ".$r_appt["siteaddrcity"]."\n"
										. "State: ".$r_appt["siteaddrstate"]."\n"
										. "Zip: ".$r_appt["siteaddrzip"]."\n"
										. "Country: ".$r_appt["siteaddrcountry"]."\n"
										. "Contact name: ".$r_appt["sitecontactname"]."\n"
										. "Contact phone: ".$r_appt["sitecontactphone"]."\n"
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
								$p["DTEND"] = gmdate("Ymd\THis\Z", ($slotstamp + $r_site["slottime"] * 60));
								$p["DTSTAMP"] = gmdate("Ymd\THis\Z");
								$p["LAST-MODIFIED"] = gmdate("Ymd\THis\Z");
								$p["LOCATION"] = $r_appt["sitename"]." (".str_replace(",", "", $r_appt["siteaddress"])." ".$r_appt["siteaddrcountry"].")";
								$p["CONTACT"] = $r_appt["sitecontactname"]." (".$r_appt["sitecontactphone"].")";
								$p["UID"] = $r_appt["apptref"];
								$p["SUMMARY"] = $r_appt["apptrsn"];
								$p["DESCRIPTION"] = $r_appt["apptrsn"]." at ".$p["LOCATION"];
								$p["TRANSP"] = "OPAQUE";
								$p["PRIORITY"] = "5";
								$p["STATUS"] = "CANCELLED";
								$p["CLASS"] = "PUBLIC";
								$p["SEQUENCE"] = "1";
									
								$mailbody .= $myappt->cal_build_ical_event($p);
								$mailbody .= "--".$mime_boundary."\n";
								
								$mymail->contenttype = "multipart/mixed; boundary=\"".$mime_boundary."\"";
							}
							
							$mymail->from = $et_cancel["mtfrom"];
							$mymail->clearaddresses();
							$mymail->clearcc();
							if ($u_email != "")
							{
								$mymail->addaddress($u_email);
								// Site notify email address as cc
								if ($sitenotifyemail !== false)
									$mymail->addcclist($sitenotifyemail);
							}
							elseif($sitenotifyemail !== false)
								$mymail->addaddresslist($sitenotifyemail);
							$mymail->subject = $et_cancel["mtsubject"];
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
								$logstring = "Cancellation email sent for user ".$u_name."(".$u_email.").";
								$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
							}
						}
						else 
						{
							// create a log entry
							$logstring = "Cancellation email could not be sent for user ".$u_name."(missing email address).";
							$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_MAILCONFIRM);
						}
					}
					
					// Let the user know, update the calling form and close
					print "<script type=\"text/javascript\">window.opener.location.href=window.opener.location.href</script>\n";
					print "<script type=\"text/javascript\">alert('Appointment cancelled.')</script>\n";
					print "<script type=\"text/javascript\">window.close()</script>\n";
					die();
				}
			}
		}
	}
}
else 
{
	print "<script type=\"text/javascript\">alert('Could not connect to database.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Display appointment or confirmation notice

// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams["css"] = $cfg_stdcss;
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
$bodyparams["id"] = "popup";

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

// The page container
print "<div class=\"main\">\n";

// Form input fields
$inputparams = array();
$inputparams["inputfields"] = $cfg_forms[$form_name][$input_fields];
$inputparams["listbase"] = $listbase;
$inputparams["formname"] = $form_name;

// Form buttons
$buttonparams = array();
$buttonparams["buttons"] = array(
	"btn_adjudicate" => $cfg_btn_popsave,
	"reset" => $cfg_btn_popcancel,
	"adtxfr" => $cfg_btn_popad,
);
$buttonparams["dn"] = $dn;

?>
	<div class="buttonrow">

<?php
		$myform->frmrender_popclose();


// build a function to copy the admin user's data across
if ($priv_apptedit && ($apptid === false))
{
?>
<script language="javascript">
function copymydata()
{
	document.forms["apptprops"].elements["u_name"].value = "<?php print htmlentities($r_u["uname"]) ?>";
	document.forms["apptprops"].elements["u_email"].value = "<?php print htmlentities($r_u["email"]) ?>";
	document.forms["apptprops"].elements["phone"].value = "<?php print htmlentities($r_u["phone"]) ?>";
	for (var i = 0; i < document.forms["apptprops"].elements["component"].options.length; i++) 
	{
    	if (document.forms["apptprops"].elements["component"].options[i].value == '<?php print htmlentities($r_u["component"]) ?>')
	        document.forms["apptprops"].elements["component"].options[i].selected = true;
	}

}	
</script>
<?php
}
print "<script type=\"text/javascript\">window.resizeTo(".$windowx.",".$windowy.")</script>\n";

if ($appt_booked === false)
{
	// show the appointment create/cancel form
?>
<table border="1" cellspacing="0" cellpadding="5" style='border: 1px gray;' width="440" bgcolor="#ffffff">
<tr height="30">

</tr>
<form name="apptprops" method="post"  autocomplete="off" action="<?php print $formfile."?st=".urlencode($slotstamp)."&uid=".urlencode($uid).($apptid === false ? "" : "&apptid=".urlencode($apptid)).($siteid === false ? "" : "&site=".urlencode($siteid))."&avc=".urlencode($avc) ?>" >
<tr height="30">
<td valign="top" width="200" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Date</span></td>
<td valign="top" width="240" style='border-bottom: 1px gray;'><span class="proptext"><?php print date("D jS M Y", $slotstamp) ?></span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Time</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print date("H:i", $slotstamp) ?></span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Reference</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print (isset($r_appt["apptref"]) ? htmlentities($r_appt["apptref"]) : "&nbsp;") ?></span></td>
</tr><tr height="30">
<?php
	if ($priv_apptedit)
	{
		if ($apptid === false)
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
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Component</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<select name="component" id="component" tabindex="30" style="width: 22em">
<?php
			$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
			$rc = count($listcomponent);
			for ($i = 0; $i < $rc; $i++)
			{
				if (strcasecmp($r_u["component"], $listcomponent[$i][0]) == 0)
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
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print htmlentities($r_u_appt["uname"]) ?></span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Email *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<?php 
			// Email not present, but is required - allow entering it here
			if ((trim($r_u_appt["email"]) == "") && ($_cfg_appt_noemail === false))
			{
				print "<input type=\"text\" size=\"36\" maxlength=\"60\" tabindex=\"20\" name=\"u_email\" id=\"u_email\" value=\"".htmlentities($r_u_appt["email"])."\" />";
			}
			else
			{
				print htmlentities($r_u_appt["email"]); 
			}
?>
</span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Component *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<?php
			if ((trim($r_u_appt["component"]) == "") && ($_cfg_appt_nocomponent === false))
			{
				print "<select name=\"component\" id=\"component\" tabindex=\"30\" style=\"width: 22em\">\n";
				$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
				$rc = count($listcomponent);
				for ($i = 0; $i < $rc; $i++)
				{
					if (strcasecmp($r_u_appt["component"], $listcomponent[$i][0]) == 0)
						print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
					else
						print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
				}
				print "</select>\n";
			}
			else
				print htmlentities($r_u_appt["component"]);
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
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print htmlentities($r_u["uname"]) ?></span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Email *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<?php 
		// Email not present, but is required - allow entering it here
		if ((trim($r_u["email"]) == "") && ($_cfg_appt_noemail === false))
		{
			print "<input type=\"text\" size=\"36\" maxlength=\"60\" tabindex=\"20\" name=\"u_email\" id=\"u_email\" value=\"".htmlentities($r_u["email"])."\" />";
		}
		else
		{
			print htmlentities($r_u["email"]);
		}
?>
</span></td>
</tr><tr height="30">
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Component *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<?php
		// Component not present and is required - allow entering it here
		if ((trim($r_u["component"]) == "") && ($_cfg_appt_nocomponent === false))
		{
			print "<select name=\"component\" id=\"component\" tabindex=\"30\" style=\"width: 22em\">\n";
			$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
			$rc = count($listcomponent);
			for ($i = 0; $i < $rc; $i++)
			{
				if (strcasecmp($r_u["component"], $listcomponent[$i][0]) == 0)
					print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
				else
					print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
			}
			print "</select>\n";
		}
		else
			print htmlentities($r_u["component"]); 
?>
</span></td>
<?php
	}
?>
</tr><tr height="30">
<?php
	if ($priv_apptedit)
	{
		if ($apptid === false)
		{
			// Can enter the phone details - initialise as blank
?>
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Phone</span></td>
<td valign="top" style='border-bottom: 1px gray;'><input type="text" size="36" maxlength="40" tabindex="3" name="phone" id="phone" value="" /></td>
<?php
		}
		else
		{
?>	
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Phone *</span></td>
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext">
<?php 
			// Phone not present, but is required - allow entering it here
			if ((trim($r_u_appt["phone"]) == "") && ($_cfg_appt_nophone === false))
			{
				print "<input type=\"text\" size=\"36\" maxlength=\"60\" tabindex=\"20\" name=\"u_email\" id=\"u_email\" value=\"".htmlentities($r_u_appt["phone"])."\" />";
			}
			else
			{
				print htmlentities($r_u_appt["phone"]);
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
<td valign="top" style='border-bottom: 1px gray;'><input type="text" size="36" maxlength="40" tabindex="1" name="phone" value="<?php print $r_u["phone"] ?>" /></td>
<?php
	}
?>
</tr><tr height="30">
<?php
	if ($priv_apptedit)
	{
		if ($apptid === false)
		{
			// Can enter the appointment reason from dropdown list
?>
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Reason</span></td>
<td valign="top" style='border-bottom: 1px gray;'>
<select name="u_apptrsn" id="u_apptrsn" tabindex="4" style="width: 22em">
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
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print htmlentities($r_appt["apptrsn"]) ?></span></td>
<?php
		}
	}
	else
	{
?>
<td valign="top" style='border-right: 1px gray; border-bottom: 1px gray;'><span class="proplabel">Reason</span></td>
<td valign="top" style='border-bottom: 1px gray;'>
<select name="u_apptrsn" id="u_apptrsn" tabindex="4" style="width: 22em">
<?php
		$listapptrsn = $myappt->sortlistarray($listapptrsn, 1, SORT_ASC, SORT_REGULAR);
		$rc = count($listapptrsn);
		for ($i = 0; $i < $rc; $i++)
		{
			if (isset($r_appt["apptrsn"]))
			{
				if (strcasecmp($listapptrsn[$i][0], $r_appt["apptrsn"]) == 0)
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
<td valign="top" style='border-bottom: 1px gray;'><span class="proptext"><?php print htmlentities($r_site["sitename"]) ?></span></td>
</tr>
<tr height="40">
<td valign="top" style='border-right: 1px gray;'><span class="proplabel">Address</span></td>
<td valign="top"><span class="proptext"><?php print htmlentities($r_site["siteaddress"]) ?></span></td>
</tr>
<tr height="40">
<td valign="top" style='border-right: 1px gray;'><span class="proplabel">State</span></td>
<td valign="top"><span class="proptext"><?php print htmlentities($r_site["siteaddrstate"]) ?></span></td>
</tr>
<tr height="40">
<td valign="top" style='border-right: 1px gray;'><span class="proplabel">Country</span></td>
<td valign="top"><span class="proptext"><?php print htmlentities($r_site["siteaddrcountry"]) ?></span></td>
</tr>
</table>
<table cellspacing="0" cellpadding="10" width="440" border="0" style="border-collapse:collapse;">
<tr height="40">
<td valign="top" align="left">
	<input type="submit" name="submit_appt" class="inputbtn darkblue" value="<?php print ($apptid === false ? "Book Appointment" : "Cancel Appointment") ?>" tabindex="3" />
</td>
<?php
	if ($priv_apptedit && ($apptid === false))
	{
		// copies the admin user's data into the fields to assist in making own appointments
?>
<td valign="top" align="right">
<input type="button" name="btn_copymydata" class="inputbtn darkblue" value="My Appointment" onclick="javascript:copymydata()" title="Copy my data into this form" />
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
<span class="proplabel">Reason: </span><span class="proptext"><?php print htmlentities($r_appt["apptrsn"]) ?></span><br/>
<span class="proplabel">Ref: </span><span class="proptext"><?php print htmlentities($r_appt["apptref"]) ?></span><br/>
<span class="proplabel">Site: </span><span class="proptext"><?php print htmlentities($r_site["sitename"]) ?></span><br/>
<span class="proplabel">Address: </span><span class="proptext"><?php print htmlentities($r_site["siteaddress"]) ?></span><br/>
<span class="proplabel">State: </span><span class="proptext"><?php print htmlentities($r_site["siteaddrstate"]) ?></span><br/>
<span class="proplabel">Country: </span><span class="proptext"><?php print htmlentities($r_site["siteaddrcountry"]) ?></span><br/>
<span class="proplabel">Contact name: </span><span class="proptext"><?php print htmlentities($r_site["sitecontactname"]) ?></span><br/>
<span class="proplabel">Contact phone: </span><span class="proptext"><?php print htmlentities($r_site["sitecontactphone"]) ?></span><br/>
<p/>
<?php
}
?>
</body></html>