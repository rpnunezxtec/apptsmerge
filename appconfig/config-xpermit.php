<?PHP

// $Id:$
// Configuration for the xpermit system functionality


//$_service_envelope = "xpermit";
//$_service_version = "1.0";

// used to turn on connection debugging
$_debug = false;

require_once("/authentx/core/http7/config-base.php");

//if you want the permission to be created with a default status :
$_default_permit_status = "active";

// The base DN's for the application branches
$_cred_basedn = "credentials=usaccess,credentials=authentx,ounit=credentials,dc=authentx";
$_entity_basedn = "entities=usaccess,ounit=entities,dc=authentx";
$_token_basedn = "credentials=usaccess,credentials=tokens,ounit=credentials,dc=authentx";

$_permit_basedn = "permissions=access,permissions=usaccess,ounit=permissions,dc=authentx";
$_permit_issued = "ounit=issued";
$_permit_accessgroups = "ounit=accessgroups";
$_permit_default = "pid=XDefault Access Group,ounit=XDefault";

// permission issued needs access group.
$_default_accessgroup_dn = $_permit_default.",".$_permit_accessgroups.",".$_permit_basedn;

$_permit_adminname = "XDefault Access Script";
$_default_comment = "XDefault Access";

date_default_timezone_set(DATE_TIMEZONE);
$permitexpdate = date("YmdHis", mktime(0, 0, 0, date("m"), date("d"), date("Y")+5));
$permitexpdate .= TIMEZONE;


?>
