<?PHP
// $Id: config-pivinit.php 105 2008-11-18 01:44:38Z gswan $
// Service configuration file

require_once("../../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;

$_service_envelope = "pivinitresult";
$_service_version = "1.0";


$_constant_elements = array (
);

?>