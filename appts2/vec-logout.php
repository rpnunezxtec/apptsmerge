<?PHP
// $Id:$

session_start();
header("Cache-control: private");

include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

$myappt->clearsession();

if (isset($page_logout))
	$myappt->vectormeto($page_logout);
else
	$myappt->vectormeto($page_denied);

?>