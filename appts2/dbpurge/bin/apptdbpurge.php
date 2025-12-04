<?php

// $Id:$

// Service that checks the appointments database for extraneous inactive users and aged
// appointments history and removes it according to configuration

require_once("../../config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

$ts = time();

if ($_apptdbpurge_enable === true)
{
	$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
	if ($sdbh->connect_error)
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
				
				$q_p = "select uuid, "
					. "\n userid, "
					. "\n uname, "
					. "\n lastlogin "
					. "\n from user "
					. "\n where lastlogin<'".$udate."'"
					;
					
				$s_p = $sdbh->query($q_p);
				if ($s_p)
				{
					$n_p = $s_p->num_rows;
					print "     ".$n_p." inactive users found.\n";
					if ($n_p > 0)
					{
						while ($r_p = $s_p->fetch_assoc())
						{
							$uuid = $r_p["uuid"];
							$uname = $r_p["uname"];
							
							// First identify the user's appointments so that delete operations can be replicated
							$qa = "select apptuuid from appointment where uuid='".$uuid."' ";
							$sa = $sdbh->query($qa);
							if ($sa)
							{
								$na = $sa->num_rows;
								print "User: ".$uname.", uuid: ".$uuid." removing ".$na." appointments.\n";
								while ($ra = $sa->fetch_assoc())
								{
									$apptuuid = $ra["apptuuid"];
									// Delete this appointment and record the delete operation
									$qd = "delete from appointment where apptuuid='".$apptuuid."' limit 1";
									$sd = $sdbh->query($qd);
									if ($sd)
										$myappt->adddeletedrow($sdbh, "appointment", "apptuuid", $apptuuid);
								}
								$sa->free();
							}
						
							// Remove the user
							$qu = "delete from user where uuid='".$uuid."' limit 1";
							$su = $sdbh->query($qu);
							if ($su)
							{
								$myappt->adddeletedrow($sdbh, "user", "uuid", $uuid);
								$logstring = "Inactive user: ".$uname." (uuid: ".$uuid.") removed.";
								$myappt->createlogentry($sdbh, $logstring, 0, ALOG_EDITUSER);
								print "   ".$logstring."\n";
							}
						}
					}
					$s_p->free();
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
			
				$q_p = "select uuid, "
					. "\n userid, "
					. "\n uname, "
					. "\n status, "
					. "\n ucreate "
					. "\n from user "
					. "\n where ucreate<'".$udate."' "
					. "\n and status='".USTATUS_UNACTIVATED."' "
					;
																
				$s_p = $sdbh->query($q_p);
				if ($s_p)
				{
					$n_p = $s_p->num_rows;
					print "     ".$n_p." expired unactived users found.\n";
					if ($n_p > 0)
					{
						while ($r_p = $s_p->fetch_assoc())
						{
							$uuid = $r_p["uuid"];
							$uname = $r_p["uname"];
			
							// Remove the user - an unactivated user will not have any appointments
							$qu = "delete from user where uuid='".$uuid."' limit 1";
							$su = $sdbh->query($qu);

							if ($su)
							{
								$myappt->adddeletedrow($sdbh, "user", "uuid", $uuid);
								$logstring = "Unactived user: ".$uname." (uuid: ".$uuid.") removed.";
								$myappt->createlogentry($sdbh, $logstring, 0, ALOG_EDITUSER);
								print "   ".$logstring."\n";
							}
						}
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
				
				$q_a = "select apptuuid, "
					. "\n starttime "
					. "\n from appointment "
					. "\n where starttime<'".$adate."' "
					;
					
				$s_a = $sdbh->query($q_a);
				if ($s_a)
				{
					$n_a = $s_a->num_rows;
					print "      ".$n_a." aged appointments found.\n";
					if ($n_a > 0)
					{
						while ($r_a = $s_a->fetch_assoc())
						{
							$apptuuid = $r_a["apptuuid"];

							// Remove the appointment and add a deleted row event
							$qd = "delete from appointment where apptuuidid='".$apptuuid."' ";
							$sd = $sdbh->query($qd);
							if ($sd)
							{
								$myappt->adddeletedrow($sdbh, "appointment", "apptuuid", $apptuuid);
								$logstring = "Aged appointment: ".$apptuuid." removed.";
								$myappt->createlogentry($sdbh, $logstring, 0, ALOG_DELETEAPPT);
								print "   ".$logstring."\n";
							}
						}
					}
					$s_a->free();
				}
			}
		}
		
		$sdbh->close();
	}	
}

$tf = time();
$te = $tf - $ts;
$tr = $_sleeptime_apptdbpurge - $te;
if ($tr > 0)
	sleep($tr);

?>