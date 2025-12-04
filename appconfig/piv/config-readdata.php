<?PHP
// $Id: config-readdata.php 105 2008-11-18 01:44:38Z gswan $
// Service configuration file
//    xsvc-readdata.xas?id=useracid

require_once("../../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "readdataresult";
$_service_version = "1.1";

// config array:
// source spec | xml tag | caption | conversion options | flags
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
"entity:firstname:entity|firstname|First Name|txt:txt|0x00",
"entity:mi:entity|mi|Middle Name|txt:txt|0x00",
"entity:lastname:entity|lastname|Last Name|txt:txt|0x00",
"entity:suffix:entity|suffix|Suffix|txt:txt|0x00",
"entity:ssn:entity|ssn|SSN|txt:txt|0x200",
"entity:dob:entity|dob|Date of Birth|txt:txt|0x04",
"entity:status:entity|status|Entity Status|txt:txt|0x00",
"entity:gcoid=card:xblk:002055:gco|cardpickup|Card Pickup|txt:txt|0x00",
"entity:bioid=portrait:jpgpic:biometric|portrait|Portrait|b64:bin|0x00",
"token:acid:credential|cuid|Token CUID|txt:txt|0x00",
"token:status:credential|tokenstatus|Token Status|txt:txt|0x00",
"token:gcoid=A000000308.5fc102:xblk:000030:gco|A000000308|5fc102|b64:bin|0x00",
);

$_constant_elements = array (
);

?>