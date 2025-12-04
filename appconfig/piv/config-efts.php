<?PHP
// $Id: config-efts.php 105 2008-11-18 01:44:38Z gswan $
// Service configuration file

$_service_envelope = "eftsresult";
$_service_version = "1.0";

require_once("../../appconfig/config-app.php");

$email_to = "eft@xtec.com";
$email_from = "eft test account <eft@xtec.com>";
$email_replyto = "eft test account <eft@xtec.com>";
$email_returnpath = "eft test account <eft@xtec.com>";
$email_subject = "EFTS ".date("Ymd H:i:s");

?>