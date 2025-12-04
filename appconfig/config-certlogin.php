<?PHP
// $Id: config-certlogin.php 44 2008-10-29 06:06:24Z atlas $
// Service configuration file
//    xsvc-certlogin.xas?id=tokenacid

require_once("../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_permit_basedn = "ounit=issued,".$ldap_accessbranch.",".$ldap_permissions.",ounit=permissions,".$ldap_treetop;

$_service_envelope = "certlogin";
$_service_version = "1.1";

// config array:
// source spec | applet id | container | conversion options | flags
// source spec: same as in form configuration.
// xml tag: the tag identifying the item in the incoming xml data
// form caption: the caption to appear on the client form
// conversion options: delimited options to signify modification of the data:
//  XMLreq format : database format : extension parameters 
//      txt       :     txt                           = no conversion performed
//      b64       :     b64                           = Base64 format, no conversion performed
//      b64       :     bin                           = Base64 to/from binary conversion
//      hex       :     bin                           = hex characters to/from binary conversion
//      b64       :     hex                           = Base64 to/from hex characters conversion
// flags: Bits to signify extension operations
//  0x00 : no extension required
//  0x01 : 
//  0x02 : 
//  0x04 : Date formatting required from/to LDAP format to MM-DD-YYYY format.
//  0x10 :
//  0x20 :
//  0x40 :
//  0x80 :

$_db_elements = array (
"entity:firstname:entity|firstname|firstname|txt:txt|0x00",
"entity:lastname:entity|lastname|lastname|txt:txt|0x00",
"credential:role:credential|role|role|txt:txt|0x01",
"entity:status:entity|status|status|txt:txt|0x00",
);

$_constant_elements = array (
);

?>
