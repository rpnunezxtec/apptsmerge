<?PHP
// $Id:$
// Service configuration file
//    xsvc-kioskenroll.xas
// config array:
// source spec | xml tag | form caption | conversion options | flags
//
// source spec: same as in form configuration.
// xml tag: the tag identifying the item in the incoming xml data
// form caption: the caption, not used in the writedata service. Here for compatibility only
// conversion options: delimited options to signify modification of the data:
//  XMLreq format : database format : extension parameters
//      txt       :     txt                           = no conversion performed
//      b64       :     b64                           = Base64 format, no conversion performed
//      b64       :     bin                           = Base64 to/from binary conversion
//      hex       :     bin                           = hex characters to/from binary conversion
//      b64       :     hex                           = Base64 to/from hex characters conversion
// flags: Bits to signify extension operations
//  0x00 : no extension required
//  0x01 : multiple instance attribute. Signifies that the attribute is not to be replaced
//  0x02 :
//  0x04 : date format conversion required from/to openldap format and MM-DD-YYYY format
//  0x10 :
//  0x20 :
//  0x40 :
//  0x80 :


require_once("../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "kioskenrollresult";
$_service_version = "1.1";

//default statuses, 
$default_authentx_status = "active";
$default_entity_status = "active";
$default_token_status = "active";

// This flag permits the creation of new users if the token/userid are not found.
$_allowusercreation = true;

$_db_elements = array (
"token:gcoid=A000000308.5fc101:xblk:999999:gco|A00000030800001000.5C035FC101|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc102:xblk:999999:gco|A00000030800001000.5C035FC102|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc103:xblk:999999:gco|A00000030800001000.5C035FC103|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc105:xblk:999999:gco|A00000030800001000.5C035FC105|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc106:xblk:999999:gco|A00000030800001000.5C035FC106|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc107:xblk:999999:gco|A00000030800001000.5C035FC107|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc108:xblk:999999:gco|A00000030800001000.5C035FC108|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc109:xblk:999999:gco|A00000030800001000.5C035FC109|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc10a:xblk:999999:gco|A00000030800001000.5C035FC10a|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc10b:xblk:999999:gco|A00000030800001000.5C035FC10b|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc10c:xblk:999999:gco|A00000030800001000.5C035FC10c|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000030:gco|A00000030800001000.5C035FC102.30|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000034:gco|A00000030800001000.5C035FC102.34|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000035:gco|A00000030800001000.5C035FC102.35|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:00003E:gco|A00000030800001000.5C035FC102.3E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:0000FE:gco|A00000030800001000.5C035FC102.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000BC:gco|A00000030800001000.5C035FC103.BC|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000FE:gco|A00000030800001000.5C035FC103.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000070:gco|A00000030800001000.5C035FC105.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000071:gco|A00000030800001000.5C035FC105.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000FE:gco|A00000030800001000.5C035FC105.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC106:xblk:0000BA:gco|A00000030800001000.5C035FC106.BA|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC106:xblk:0000BB:gco|A00000030800001000.5C035FC106.BB|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC106:xblk:0000FE:gco|A00000030800001000.5C035FC106.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC108:xblk:0000BC:gco|A00000030800001000.5C035FC108.BC|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC108:xblk:0000FE:gco|A00000030800001000.5C035FC108.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000070:gco|A00000030800001000.5C035FC10A.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000071:gco|A00000030800001000.5C035FC10A.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000FE:gco|A00000030800001000.5C035FC10A.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000070:gco|A00000030800001000.5C035FC10B.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000071:gco|A00000030800001000.5C035FC10B.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000FE:gco|A00000030800001000.5C035FC10B.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000070:gco|A00000030800001000.5C035FC101.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000071:gco|A00000030800001000.5C035FC101.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000FE:gco|A00000030800001000.5C035FC101.FE|none|b64:bin|0x00",
"token:gcoid=card:xblk:002070:gco|fasc_n|Fascn|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002039:gco|fasc_n.parsed|none|txt:txt|0x00",
"token:cuid:credential|cuid|none|txt:txt|0x00",
"token:expdate:credential|expdate|none|txt:txt|0x04",
"entity:firstname:entity|firstname|none|txt:txt|0x00",
"entity:mi:entity|mi|none|txt:txt|0x00",
"entity:lastname:entity|lastname|none|txt:txt|0x00",
"entity:ssn:entity|ssn|none|txt:txt|0x200",
"entity:dob:entity|dob|none|txt:txt|0x04",
"credential:acid:credential|userid|none|txt:txt|0x08",
"credential:userPassword:credential|password|none|txt:txt|0x08",
"token:gcoid=card:xblk:999999:gco|seiwgacid|none|txt:txt|0x08",
"entity:bioid=portrait:jpgpic:biometric|image|none|b64:bin|0x00",
"entity:bioid=PIV_Primary:xblk:biometric|minutiae|none|b64:bin|0x00",
);

?>