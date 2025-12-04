<?PHP
// $Id: config-print.php 105 2008-11-18 01:44:38Z gswan $
// Service configuration file
//    xsvc-print.xas?id=tokenacid
// config array:
// source spec | xml tag | dynamic field name | conversion options | flags
// source spec: same as in form configuration.
// xml tag: the tag identifying the item in the incoming xml data
// dynamic field name: unique name the badge template uses to identify the data coming from the web service
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
$_service_envelope = "printresult";
$_service_version = "1.0";

$_db_elements = array(
"token:gcoid=topoinfo:xblk:990104:gco|topoinfofirstnamemi|firstnamemi|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002102:gco|topoinfolastname|lastname|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002102:gco|topoinfolastname|lastnamesuffix|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:990202:gco|topoinfoexpdate|yyyymmmddexpirationdate|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:990105:gco|topoinfoissdate|issuedate|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:990302:gco|topoinfoexpdateshort|expdateshort|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002502:gco|empaffiliation|empaffiliation|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:990205:gco|agency|agency|txt:txt|0x00",
"entity:bioid=portrait:jpgpic:biometric|portrait|portrait|b64:bin|0x00",
"entity:eyeclr:entity|eyecolor|eyecolor|txt:txt|0x00",
"entity:hght:entity|height|height|txt:txt|0x00",
"entity:hrclr:entity|hairrcolor|haircolor|txt:txt|0x00",
"token:cardtopology:credential|templatename|templatename|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002039:gco|track2data|track2data|txt:txt|0x00",
"token:gcoid=card:xblk:002305:gco|clearance|clearance|txt:txt|0x00",
"token:gcoid=card:xblk:002081:gco|leo|leo|txt:txt|0x00",
"token:gcoid=card:xblk:0020C5:gco|er|er|txt:txt|0x00",
);

?>