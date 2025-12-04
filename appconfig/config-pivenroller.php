<?PHP
// $Id:$
// Service configuration file
//    xsvc-pivenroller.xas
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
$entbasedn = $ldap_entities.",ounit=entities,".$ldap_treetop;
$_service_envelope = "pivenrollerresult";
$_service_version = "1.1";

$_default_belongsto = "miami";
$default_token_status = "active";

//used for agency specific edi number. 
$agency_prefix = "EDI";

// This flag permits the creation of new users if the token/userid are not found.
$_allowusercreation = false;
//$_allowusercreation = true;

$_db_elements = array (
"entity:firstname:entity|firstname|none|txt:txt|0x00",
"entity:mi:entity|mi|none|txt:txt|0x00",
"entity:lastname:entity|lastname|none|txt:txt|0x00",
"credential:acid:credential|acid|none|txt:txt|0x01",
"credential:acid:credential|userid|none|txt:txt|0x08",
"credential:userPassword:credential|password|none|txt:txt|0x08",
"entity:emplid=usaccess:email:employment|email|none|txt:txt|0x00",
"entity:ediident:entity|edi|none|txt:txt|0x08",
"token:acid:credential|tokenacid|none|txt:txt|0x01",
"token:cuid:credential|cuid|none|txt:txt|0x00",
"token:gcoid=card:xblk:002070:gco|fasacid|none|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002039:gco|fascnparsed|none|txt:txt|0x00",
"token:gcoid=card:xblk:002069:gco|faxacid|none|txt:txt|0x00",
"token:gcoid=card:xblk:002069:gco|swgacid|none|txt:txt|0x08",
"token:gcoid=topoinfo:xblk:002040:gco|fidacid|none|txt:txt|0x00",
"token:expdate:credential|tokenexpdate|none|txt:txt|0x04",
"credential:acid:credential|adminid|none|txt:txt|0x08",
"credential:userPassword:credential|adminpwd|none|txt:txt|0x08",
"credential:userPassword:credential|userpwd|none|txt:txt|0x08",
"entity:ssn:entity|ssn|none|txt:txt|0x200",
"entity:dob:entity|dob|none|txt:txt|0x04",
"token:gcoid=A000000308.5FC101:xblk:999999:gco|A00000030800001000.5FC101|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:999999:gco|A00000030800001000.5FC102|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:999999:gco|A00000030800001000.5FC103|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:999999:gco|A00000030800001000.5FC105|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC106:xblk:999999:gco|A00000030800001000.5FC106|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:999999:gco|A00000030800001000.5FC107|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC108:xblk:999999:gco|A00000030800001000.5FC108|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:999999:gco|A00000030800001000.5FC109|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:999999:gco|A00000030800001000.5FC10A|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:999999:gco|A00000030800001000.5FC10B|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10C:xblk:999999:gco|A00000030800001000.5FC10C|none|b64:bin|0x00",
"entity:gcoid=general:xblk:000040:gco|chuidedi|none|txt:txt|0x00",
"token:gcoid=A000000308.5FC101:xblk:000016:gco|A00000030800001000.5FC101.16|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000070:gco|A00000030800001000.5FC101.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000071:gco|A00000030800001000.5FC101.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000FE:gco|A00000030800001000.5FC101.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000C0:gco|A00000030800001000.5FC101.C0|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:00008F:gco|A00000030800001000.5FC101.8F|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000AF:gco|A00000030800001000.5FC101.AF|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:00003B:gco|A00000030800001000.5FC101.3B|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000015:gco|A00000030800001000.5FC101.15|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000089:gco|A00000030800001000.5FC101.89|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000DB:gco|A00000030800001000.5FC101.DB|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:00007E:gco|A00000030800001000.5FC101.7E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:00006E:gco|A00000030800001000.5FC101.6E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:00006C:gco|A00000030800001000.5FC101.6C|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000020:gco|A00000030800001000.5FC101.20|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000061:gco|A00000030800001000.5FC101.61|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000DE:gco|A00000030800001000.5FC101.DE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000A4:gco|A00000030800001000.5FC101.A4|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000049:gco|A00000030800001000.5FC101.49|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000BD:gco|A00000030800001000.5FC101.BD|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000BF:gco|A00000030800001000.5FC101.BF|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000067:gco|A00000030800001000.5FC101.67|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000030:gco|A00000030800001000.5FC102.30|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000034:gco|A00000030800001000.5FC102.34|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000035:gco|A00000030800001000.5FC102.35|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:00003E:gco|A00000030800001000.5FC102.3E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:0000FE:gco|A00000030800001000.5FC102.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000BC:gco|A00000030800001000.5FC103.BC|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000FE:gco|A00000030800001000.5FC103.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000070:gco|A00000030800001000.5FC105.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000071:gco|A00000030800001000.5FC105.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000086:gco|A00000030800001000.5FC105.86|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000FE:gco|A00000030800001000.5FC105.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000C0:gco|A00000030800001000.5FC105.C0|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000ED:gco|A00000030800001000.5FC105.ED|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:00009E:gco|A00000030800001000.5FC105.9E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:00006C:gco|A00000030800001000.5FC105.6C|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000B3:gco|A00000030800001000.5FC105.B3|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000096:gco|A00000030800001000.5FC105.96|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000031:gco|A00000030800001000.5FC105.31|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000D4:gco|A00000030800001000.5FC105.D4|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:00002E:gco|A00000030800001000.5FC105.2E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:00005F:gco|A00000030800001000.5FC105.5F|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000AA:gco|A00000030800001000.5FC105.AA|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000A6:gco|A00000030800001000.5FC105.A6|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000C4:gco|A00000030800001000.5FC105.C4|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:00001B:gco|A00000030800001000.5FC105.1B|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000022:gco|A00000030800001000.5FC105.22|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000AC:gco|A00000030800001000.5FC105.AC|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC106:xblk:0000BA:gco|A00000030800001000.5FC106.BA|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC106:xblk:0000BB:gco|A00000030800001000.5FC106.BB|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC106:xblk:0000FE:gco|A00000030800001000.5FC106.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F0:gco|A00000030800001000.5FC107.F0|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F1:gco|A00000030800001000.5FC107.F1|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F2:gco|A00000030800001000.5FC107.F2|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F3:gco|A00000030800001000.5FC107.F3|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F4:gco|A00000030800001000.5FC107.F4|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F5:gco|A00000030800001000.5FC107.F5|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F6:gco|A00000030800001000.5FC107.F6|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC108:xblk:0000BC:gco|A00000030800001000.5FC108.BC|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC108:xblk:0000FE:gco|A00000030800001000.5FC108.FE|none|b64:bin|0x00",
"entity:bioid=portrait:jpgpic:biometric|portrait|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000001:gco|A00000030800001000.5FC109.01|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000002:gco|A00000030800001000.5FC109.02|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000003:gco|A00000030800001000.5FC109.03|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000004:gco|A00000030800001000.5FC109.04|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000005:gco|A00000030800001000.5FC109.05|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000006:gco|A00000030800001000.5FC109.06|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000022:gco|A00000030800001000.5FC10A.22|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000070:gco|A00000030800001000.5FC10A.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000071:gco|A00000030800001000.5FC10A.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000FE:gco|A00000030800001000.5FC10A.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000DD:gco|A00000030800001000.5FC10A.DD|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:00000E:gco|A00000030800001000.5FC10A.0E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000D2:gco|A00000030800001000.5FC10A.D2|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000C4:gco|A00000030800001000.5FC10A.C4|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000043:gco|A00000030800001000.5FC10A.43|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000F4:gco|A00000030800001000.5FC10A.F4|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000EF:gco|A00000030800001000.5FC10A.EF|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000064:gco|A00000030800001000.5FC10A.6A|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:00009B:gco|A00000030800001000.5FC10A.9B|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000051:gco|A00000030800001000.5FC10A.51|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000BD:gco|A00000030800001000.5FC10A.BD|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000049:gco|A00000030800001000.5FC10A.49|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000AA:gco|A00000030800001000.5FC10A.AA|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000000:gco|A00000030800001000.5FC10A.00|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000044:gco|A00000030800001000.5FC10A.44|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000067:gco|A00000030800001000.5FC10A.67|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000088:gco|A00000030800001000.5FC10A.88|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000A0:gco|A00000030800001000.5FC10A.A0|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000F6:gco|A00000030800001000.5FC10B.F6|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000070:gco|A00000030800001000.5FC10B.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000071:gco|A00000030800001000.5FC10B.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000FE:gco|A00000030800001000.5FC10B.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000F0:gco|A00000030800001000.5FC10B.F0|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:00008B:gco|A00000030800001000.5FC10B.8B|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000035:gco|A00000030800001000.5FC10B.35|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000D5:gco|A00000030800001000.5FC10B.D5|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000012:gco|A00000030800001000.5FC10B.12|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000C2:gco|A00000030800001000.5FC10B.C2|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000C7:gco|A00000030800001000.5FC10B.C7|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000023:gco|A00000030800001000.5FC10B.23|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000074:gco|A00000030800001000.5FC10B.74|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000059:gco|A00000030800001000.5FC10B.59|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:00008E:gco|A00000030800001000.5FC10B.8E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:000059:gco|A00000030800001000.5FC10B.03|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000A7:gco|A00000030800001000.5FC10B.A7|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000C3:gco|A00000030800001000.5FC10B.C3|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:000041:gco|A00000030800001000.5FC10B.41|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:000043:gco|A00000030800001000.5FC10B.43|none|b64:bin|0x00",
"token:sysid=icccin:sysval:xsystem|icccin|none|txt:txt|0x00",
"entity:bioid=PIV_Primary:xblk:biometric|minutiae|none|b64:bin|0x00",

);

 // these are required as part of the code.  
 // if configured differently, in $_db_elements array, the values should be modified to use the new xmltags
 // key is what's used in the code.  should not be changed.
 // value is what is configured as part of $_db_elements
$_xml_mappings = array(
"cuid" => "cuid",
"swgacid" => "swgacid",
"fasacid" => "fasacid",
"faxacid" => "faxacid",
"cinacid" => "cinacid",
"adminid" => "adminid",
"adminpwd" => "adminpwd",
"chuidedi" => "chuidedi",
"email" => "email",
"userid" => "userid",
"edi" => "edi",
"belongsto" => "belongsto",
"userid" => "userid",
"userpwd" => "userpwd", 
);

//default statuses, 
$default_authentx_status = "active";
$default_entity_status = "active";
$default_token_status = "active";



//for 
$_authorized_roles = array(
);

?>
