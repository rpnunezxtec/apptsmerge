<?php
// $Id: replappts_logmanage.php$

// Log manager daemon
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once("../../config/config-repl.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setreploghost(DB_DBHOST_APPTSREPLOG);
$myappt->setreplogport(DB_DBPORT_APPTSREPLOG);
$myappt->setreplogdbname(DB_DBNAME_APPTSREPLOG);
$myappt->setreplogdbuser(DB_DBUSER_APPTSREPLOG);
$myappt->setreplogdbpasswd(DB_DBPASSWD_APPTSREPLOG);
$myappt->setsiteid($cfg_siteid);
$myappt->setagencyid($cfg_agencyid);

print "--- Appointments Replication Log Manager start.\n";
// Manage the SQL database logs - prevent filling the system up with old junk
// Remove err logs older that expdays_errlog
if ($expdays_errlog > 0)
{
	print "  Error Log.\n";
	$n = $myappt->trimlog_err($expdays_errlog);
	print "   ".$n." entries trimmed (".$expdays_errlog." days).\n";
}

// Accumulate and remove consumer (incoming data) transactions older than expdays_consumer
if ($expdays_consumer > 0)
{
	print "  Consumer transactions.\n";
	$n = $myappt->trimlog_consumer($expdays_consumer);	
	print "   ".$n." entries accumulated and trimmed (".$expdays_consumer." days).\n";
}

// Accumulate and remove provider (outgoing data) transactions older that expdays_provider
if ($expdays_provider > 0)
{
	print "  Provider transactions.\n";
	$n = $myappt->trimlog_provider($expdays_provider);
	print "   ".$n." entries accumulated and trimmed (".$expdays_provider." days).\n";
}

print "--- Appointments Replication Log Manager end (".REPLAPPTLOGMGRSLEEPTIME.").\n";
flush();
sleep(REPLAPPTLOGMGRSLEEPTIME);
exit(0);

?>