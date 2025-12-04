<?php

// $Id:$

// Service that wakes just after midnight and scans for any appointments anywhere for
// the day after today. If an email is present it sends out a reminder to the applicant.

require_once("../../config.php");
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
	$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
	if ($sdbh->connect_error)
	{
		// alert - could not get a connection to the database
		print "ERROR: Could not connect to appointments database.\n";
	}
	else
	{
		// Get the reminder email template
		$et = $myappt->getmailtemplate($sdbh, MTDB_REMINDER);
		
		// Search for all appointments for the next day
		// for users with an email.
		$tdate = date("Y-m-d", (time() + 86400));
		$a_start = $tdate." 00:00:00";
		$a_end = $tdate." 23:59:59";
		print "Searching for appointments between ".$a_start." and ".$a_end."...\n";
		
		$q_appt = "select * from appointment "
			. "\n left join site on site.centeruuid=appointment.centeruuid "
			. "\n left join user on user.uuid=appointment.uuid "
			. "\n where starttime>'".$sdbh->real_escape_string($a_start)."' "
			. "\n and starttime<'".$sdbh->real_escape_string($a_end)."' "
			. "\n and email is not null "
			;
		$s_appt = $sdbh->query($q_appt);
		if ($s_appt)
		{
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
						
					$mymail->setFrom($et["mtfrom"]);
					$mymail->clearaddresses();
					$mymail->addaddress($u_email);
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
					print "Email sent to ".$u_email." for appointment at ".$r_appt["sitename"]." on ".$r_appt["starttime"]."\n";
				}
			}
			$s_appt->free();
		}
		print "Completed: ".$n_appt." found.\n";
		$sdbh->close();
	}
	sleep(60);
}

?>