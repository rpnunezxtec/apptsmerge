<?PHP
// $Id: config-printcarrier.php 105 2008-11-18 01:44:38Z gswan $
// Service configuration file
//    xsvc-printcarrier.xas?id=tokenacid
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

require_once("../../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "printcarrierresult";
$_service_version = "1.1";

$_db_elements = array (
"token:gcoid=topoinfo:xblk:990104:gco|firstnamemi|firstnamemi|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002102:gco|lastname|lastname|txt:txt|0x00",
"entity:eid:entity|userid|User ID|txt:txt|0x00",
"entity:gcoid=general:xblk:000005:gco|agency|Agency|txt:txt|0x00",
"entity:gcoid=token,procid=token,ounit=piv:xblk:002055:gco|pickuploc|Pickup Location|txt:txt|0x00",
);

$_constant_elements = array (
);

?>