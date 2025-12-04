<?php

// $Id:$

// Service that checks the appointments database for extraneous inactive users and aged
// appointments history and removes it according to configuration

require("../../config.php");
include("../../vec-clappointments.php");
$myappt = new authentxappointments();
date_default_timezone_set(DATE_TIMEZONE);

$ts = time();

if ($_apptdbpurge_enable === true)
{
	$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
	if ($dbh === false)
	{
		print "ERROR: Could not connect to appointments database.\n";
	}
	else
	{
		print "Checking for DB cleanup.\n";
		// Check for inactive users
		if ($_apptdbpurge_inactiveuser_enable === true)
		{
			$ustamp = time() - (86400 * $_apptdbpurge_inactiveuser_days);
			if ($ustamp > 0)
			{
				$udate = gmdate("Y-m-d H:i:s", $ustamp);
				print "  Inactive user check: ".$udate."\n";
				
				$q_p = "select uid, "
					. "\n userid, "
					. "\n uname, "
					. "\n lastlogin "
					. "\n from user "
					. "\n where lastlogin<'".$udate."'"
					;
					
				$s_p = $dbh->query($q_p);
				$n_p = $s_p->num_rows;
				print "     ".$n_p." inactive users found.\n";
				if ($n_p > 0)
				{
					while ($r_p = $s_p->fetch_assoc())
					{
						$uid = $r_p["uid"];
						$uname = $r_p["uname"];
						// Remove the user's appointments
						$q = "delete from appointment where uid='".$uid."' ";
						$s = $dbh->query($q);
						
						// Remove the user
						$q = "delete from user where uid='".$uid."' ";
						$s = $dbh->query($q);
						
						$logstring = "Inactive user: ".$uname." (".$uid.") removed.";
						$myappt->createlogentry($dbh, $logstring, 0, ALOG_EDITUSER);
						print "   ".$logstring."\n";
					}
				}
			}
		}
		
		// Check for unactivated users (ie self-registrations that were not activated)
		if ($_apptdbpurge_unactivatedusers_enable === true)
		{
			$ustamp = time() - (86400 * $_apptdbpurge_unactivatedusers_days);
			if ($ustamp > 0)
			{
				$udate = gmdate("Y-m-d H:i:s", $ustamp);
				print "  Unnactived user check: ".$udate."\n";
			
				$q_p = "select uid, "
					. "\n userid, "
					. "\n uname, "
					. "\n status, "
					. "\n ucreate "
					. "\n from user "
					. "\n where ucreate<'".$udate."' "
					. "\n and status='".USTATUS_UNACTIVATED."' "
					;
																
				$s_p = $dbh->query($q_p);
				$n_p = $s_p->num_rows;
				print "     ".$n_p." expired unactived users found.\n";
				if ($n_p > 0)
				{
					while ($r_p = $s_p->fetch_assoc())
					{
						$uid = $r_p["uid"];
						$uname = $r_p["uname"];
			
						// Remove the user
						$q = "delete from user where uid='".$uid."' ";
						$s = $dbh->query($q);
			
						$logstring = "Unactived user: ".$uname." (".$uid.") removed.";
						$myappt->createlogentry($dbh, $logstring, 0, ALOG_EDITUSER);
						print "   ".$logstring."\n";
					}
					$s_p->free();
				}
			}
		}
		
		// Check for aged appointment history
		if ($_apptdbpurge_apptflush_enable === true)
		{
			$astamp = time() - (86400 * $_apptdbpurge_apptflush_days);
			if ($astamp > 0)
			{
				$adate = gmdate("Y-m-d H:i:s", $astamp);
				print "  Aged appointment check: ".$adate."\n";
				
				$q_a = "select apptid, "
					. "\n starttime "
					. "\n from appointment "
					. "\n where starttime<'".$adate."' "
					;
					
				$s_a = $dbh->query($q_a);
				$n_a = $s_a->num_rows;
				print "      ".$n_a." aged appointments found.\n";
				if ($n_a > 0)
				{
					while ($r_a = $s_a->fetch_assoc())
					{
						$apptid = $r_a["apptid"];
						// Remove the appointment
						$q = "delete from appointment where appid='".$apptid."' ";
						$s = $dbh->query($q);
						
						$logstring = "Aged appointment: ".$apptid." removed.";
						$myappt->createlogentry($dbh, $logstring, 0, ALOG_DELETEAPPT);
						print "   ".$logstring."\n";
					}
					$s_a->free();
				}
			}
		}
		
		$dbh->close();
	}	
}

$tf = time();
$te = $tf - $ts;
$tr = $_sleeptime_apptdbpurge - $te;
if ($tr > 0)
	sleep($tr);

?>