<?PHP
// $Id: config-resetpin.php 660 2008-09-04 03:00:46Z atlas $
// Service configuration file
//    xsvc-resetpin.xas?id=tokenacid

require_once("../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "resetpinresult";
$_service_version = "1.0";

// config array:
// source spec | xml tag | caption | conversion options | flags
// source spec: same as in form configuration.
// xml tag: the tag identifying the item in the incoming xml data
// form caption: the caption, not used in this service. Here for compatibility only.
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


//some initializations:
$threshold = "0.3";
$commandName = "resetPIN";
$forceBioCheck = true;
//$pivinitPath = "/authentx/app/https/authentx/servicesasa/pivinit";

$_db_elements = array (
"entity:bioid=PIV_Primary:xblk:biometric|PIV_Primary_Template|PIV Primary Template|hex:bin|0x00",
"entity:bioid=PIV_Secondary:xblk:biometric|PIV_Secondary_Template|PIV Secondary Template|hex:bin|0x00",
"token:tokenclass:credential|tokentype|Token Type|txt:txt|0x00",
);


?>
