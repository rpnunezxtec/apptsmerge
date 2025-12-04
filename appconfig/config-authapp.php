<?PHP
// $Id:$
// Service configuration file
//    xsvc-authapp.xas

require_once("../appconfig/config-app.php");

$_service_envelope = "authapp";
$_service_version = "1.0";

// Branches
$_application_basedn = $ldap_applicationbranch.",".$ldap_permissions.",ounit=permissions,".$ldap_treetop;
$_apps_basedn = $ldap_app_apps.",".$_application_basedn;
$_appgroups_basedn = $ldap_app_appgroups.",".$_application_basedn;
$_accessgroups_basedn = $ldap_app_accessgroups.",".$_application_basedn;
$_authcred_basedn = $ldap_searchcred.",ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_searchtoken.",ounit=credentials,".$ldap_treetop;
$_entity_basedn = $ldap_entities.",ounit=entities,".$ldap_treetop;

// Extension command
$cmd_getappaccess = "/authentx/core/http7/getappaccess ";

?>