<?PHP
// $Id: config-pivencodestatus.php 105 2008-11-18 01:44:38Z gswan $
// Service configuration file
//    xsvc-pivencodestatus.xas?id=useracid

require_once("../../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "pivencodestatus";
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
//  0x01 : MULTI
//  0x02 : 
//  0x04 : Date formatting required from/to LDAP format to MM-DD-YYYY format.
//  0x08 : Nosave. Data is not saved to the database.
//  0x10 :
//  0x20 :
//  0x40 :
//  0x80 :
//  0x100:
//	0x200: The element is to be encrypted/decrypted for database storage

$_db_elements = array (
"token:gcoid=A000000308.5FC105:status:gco|A000000308|5fc105|txt:txt|0x00",
"token:gcoid=A000000308.5fc10a:status:gco|A000000308|5fc10a|txt:txt|0x00",
"token:gcoid=A000000308.5fc10b:status:gco|A000000308|5fc10b|txt:txt|0x00",
"token:gcoid=A000000308.5fc101:status:gco|A000000308|5fc101|txt:txt|0x00",
);

$_constant_elements = array (
);

?>
