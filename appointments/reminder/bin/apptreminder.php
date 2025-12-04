<?php

// $Id:$

// Service that wakes just after midnight and scans for any appointments anywhere for
// the day after today. If an email is present it sends out a reminder to the applicant.

require("../../config.php");
include_once("../../../appcore/vec-clmail.php");
include("../../vec-clappointments.php");
$myappt = new authentxappointments();
$mymail = new authentxmail();
date_default_timezone_set(DATE_TIMEZONE);

$matchtime = false;

while ($matchtime === false)
{
	// include any changes to configuration
	include("../../config.php");
	
	// Check whether it is time to start yet
	$thm = date("H:i");
	if (strcmp($reminder_check_time, $thm) == 0)
		$matchtime = true;
	else 
		sleep(60);
}

if ($matchtime === true)
{
	// Start the reminder process
	$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
	if (!$dbh)
	{
		// alert - could not get a connection to the database
		print "ERROR: Could not connect to appointments database.\n";
	}
	else
	{
		// Get the reminder email template
		$et = $myappt->getmailtemplate($dbh, MTDB_REMINDER);
		
		// Search for all appointments for the next day
		// for users with an email.
		$tdate = date("Y-m-d", (time() + 86400));
		$a_start = $tdate." 00:00:00";
		$a_end = $tdate." 23:59:59";
		print "Searching for appointments between ".$a_start." and ".$a_end."...\n";
		
		$q_appt = "select * from appointment "
			. "\n left join site on site.siteid=appointment.siteid "
			. "\n left join user on user.uid=appointment.uid "
			. "\n where starttime>'".$dbh->real_escape_string($a_start)."' "
			. "\n and starttime<'".$dbh->real_escape_string($a_end)."' "
			. "\n and email is not null "
			;
		$s_appt = $dbh->query($q_appt);
		$n_appt = $s_appt->num_rows;
		if ($n_appt > 0)
		{
			while ($r_appt = $s_appt->fetch_assoc())
			{
				$u_email = $r_appt["email"];
				
				$mailbody = $et["mtbody"];
				$appt_dt = $r_appt["starttime"];
				$slotstamp = strtotime($appt_dt);
				$mail_apptdetail = "Your Appointment Detail\n"
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
				//$mailbody = wordwrap($mailbody, 70);
						
				$mymail->from = $et["mtfrom"];
				$mymail->clearaddresses();
				$mymail->addaddress($u_email);
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
				print "Email sent to ".$u_email." for appointment at ".$r_appt["sitename"]." on ".$r_appt["starttime"]."\n";
			}
			$s_appt->free();
		}
		print "Completed: ".$n_appt." found.\n";
		$dbh->close();
	}
	sleep(60);
}

?>