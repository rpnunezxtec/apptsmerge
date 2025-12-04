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
$_service_envelope = "cardregistrationresult";
$_service_version = "1.1";

$_default_belongsto = "TEST";
$default_token_status = "active";

//used for agency specific edi number.
$agency_prefix = "EDI";

// This flag permits the creation of new users if the token/userid are not found.
//$_allowusercreation = false;
$_allowusercreation = true;

$_db_elements = array (
"entity:sysid=logs:objectlog:xsystem|fpauthstatus|FP Authentication Status Log|txt:txt|0x01",
"entity:firstname:entity|firstname|none|txt:txt|0x00",
"entity:mi:entity|mi|none|txt:txt|0x00",
"entity:lastname:entity|lastname|none|txt:txt|0x00",
"credential:acid:credential|acid|none|txt:txt|0x01",
"credential:acid:credential|userid|none|txt:txt|0x08",
"credential:userPassword:credential|password|none|txt:txt|0x08",
"credential:userPassword:credential|proofcardtestsresults|none|txt:txt|0x08",
"entity:emplid=demo:email:employment|email|none|txt:txt|0x00",
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
"token:gcoid=A000000308.5FC101:xblk:000000:gco|A00000030800001000.5FC101.00|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:00004E:gco|A00000030800001000.5FC101.4E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:00004C:gco|A00000030800001000.5FC101.4C|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000047:gco|A00000030800001000.5FC101.47|none|b64:bin|0x08",
"token:gcoid=A000000308.5FC101:xblk:000069:gco|A00000030800001000.5FC101.69|none|b64:bin|0x08",
"token:gcoid=A000000308.5FC101:xblk:000070:gco|A00000030800001000.5FC101.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000070:gco|cardauthcert|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000071:gco|A00000030800001000.5FC101.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000079:gco|A00000030800001000.5FC101.79|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000081:gco|A00000030800001000.5FC101.81|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000096:gco|A00000030800001000.5FC101.96|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:00001A:gco|A00000030800001000.5FC101.1A|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000051:gco|A00000030800001000.5FC101.51|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000054:gco|A00000030800001000.5FC101.54|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:00008C:gco|A00000030800001000.5FC101.8C|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000A9:gco|A00000030800001000.5FC101.A9|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000B9:gco|A00000030800001000.5FC101.B9|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000CD:gco|A00000030800001000.5FC101.CD|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000DA:gco|A00000030800001000.5FC101.DA|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000E8:gco|A00000030800001000.5FC101.E8|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000F3:gco|A00000030800001000.5FC101.F3|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000FE:gco|A00000030800001000.5FC101.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000030:gco|A00000030800001000.5FC102.30|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000032:gco|A00000030800001000.5FC102.32|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000033:gco|A00000030800001000.5FC102.33|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000034:gco|A00000030800001000.5FC102.34|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000035:gco|A00000030800001000.5FC102.35|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000036:gco|A00000030800001000.5FC102.36|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:00003E:gco|A00000030800001000.5FC102.3E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:0000EE:gco|A00000030800001000.5FC102.EE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:0000FE:gco|A00000030800001000.5FC102.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000BC:gco|A00000030800001000.5FC103.BC|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000F3:gco|A00000030800001000.5FC103.F3|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000FE:gco|A00000030800001000.5FC103.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000000:gco|A00000030800001000.5FC105.00|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:00001E:gco|A00000030800001000.5FC105.1E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:00003C:gco|A00000030800001000.5FC105.3C|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:00004C:gco|A00000030800001000.5FC105.4C|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:00004D:gco|A00000030800001000.5FC105.4D|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000053:gco|A00000030800001000.5FC105.53|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000070:gco|A00000030800001000.5FC105.70|none|b64:bin|0x08",
"token:gcoid=A000000308.5FC105:xblk:000070:gco|pivauthcert|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000071:gco|A00000030800001000.5FC105.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000089:gco|A00000030800001000.5FC105.89|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000092:gco|A00000030800001000.5FC105.92|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000A4:gco|A00000030800001000.5FC105.A4|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000C5:gco|A00000030800001000.5FC105.C5|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000DA:gco|A00000030800001000.5FC105.DA|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000F0:gco|A00000030800001000.5FC105.F0|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000F5:gco|A00000030800001000.5FC105.F5|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000FE:gco|A00000030800001000.5FC105.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000F3:gco|A00000030800001000.5FC105.F3|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000FD:gco|A00000030800001000.5FC105.FD|none|b64:bin|0x00",
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
"token:bioid=portrait:jpgpic:biometric|portrait|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000001:gco|A00000030800001000.5FC109.01|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000002:gco|A00000030800001000.5FC109.02|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000003:gco|A00000030800001000.5FC109.03|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000004:gco|A00000030800001000.5FC109.04|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000005:gco|A00000030800001000.5FC109.05|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000006:gco|A00000030800001000.5FC109.06|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000007:gco|A00000030800001000.5FC109.07|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000008:gco|A00000030800001000.5FC109.08|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000000:gco|A00000030800001000.5FC10A.00|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000008:gco|A00000030800001000.5FC10A.08|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000036:gco|A00000030800001000.5FC10A.36|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000047:gco|A00000030800001000.5FC10A.47|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000070:gco|A00000030800001000.5FC10A.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000071:gco|A00000030800001000.5FC10A.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000C0:gco|A00000030800001000.5FC10A.C0|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000CF:gco|A00000030800001000.5FC10A.CF|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000FE:gco|A00000030800001000.5FC10A.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000087:gco|A00000030800001000.5FC10A.87|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000075:gco|A00000030800001000.5FC10A.75|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:00001B:gco|A00000030800001000.5FC10B.1B|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000070:gco|A00000030800001000.5FC10B.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000000:gco|A00000030800001000.5FC10B.00|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000031:gco|A00000030800001000.5FC10B.31|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000018:gco|A00000030800001000.5FC10B.18|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000071:gco|A00000030800001000.5FC10B.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000A4:gco|A00000030800001000.5FC10B.A4|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000C2:gco|A00000030800001000.5FC10B.C2|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000C5:gco|A00000030800001000.5FC10B.C5|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000FE:gco|A00000030800001000.5FC10B.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10C:xblk:0000C1:gco|A00000030800001000.5FC10C.C1|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10C:xblk:0000C2:gco|A00000030800001000.5FC10C.C2|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10C:xblk:0000F3:gco|A00000030800001000.5FC10C.F3|none|b64:bin|0x00",
"token:sysid=icccin:sysval:xsystem|icccin|none|txt:txt|0x00",
"entity:bioid=PIV_Primary:xblk:biometric|minutiae|none|b64:bin|0x00",
"token:gcoid=A000000308.7E:xblk:00007E:gco|A00000030800001000.7E|none|b64:bin|0x00",
"token:gcoid=garbage:xblk:000001:gco|cardauthchallenge|none|txt:txt|0x08",
"token:gcoid=garbage:xblk:000001:gco|cardauthencdata|none|txt:txt|0x08",
"token:gcoid=garbage:xblk:000001:gco|cardauthcerttype|none|txt:txt|0x08",
"token:gcoid=garbage:xblk:000001:gco|pivauthchallenge|none|txt:txt|0x08",
"token:gcoid=garbage:xblk:000001:gco|pivauthchallengehash|none|txt:txt|0x08",
"token:gcoid=garbage:xblk:000001:gco|pivauthencdata|none|txt:txt|0x08",
"token:gcoid=garbage:xblk:000001:gco|pivauthcerttype|none|txt:txt|0x08",
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
"fidacid" => "fidacid",
"adminid" => "adminid",
"adminpwd" => "adminpwd",
"chuidedi" => "chuidedi",
"email" => "email",
"userid" => "userid",
"edi" => "edi",
"belongsto" => "belongsto",
"userid" => "userid",
"userpwd" => "userpwd",
"fascnparsed" => "fascnparsed",
"proofcardtestsresults" => "ProofCardTestsResults",
"lastname" => "lastname",
"firstname" => "firstname",
//"pivauthcert" => "A00000030800001000.5FC105.70",
"pivauthcert" => "pivauthcert",
//"cardauthcert" => "A00000030800001000.5FC101.70",
"cardauthcert" => "cardauthcert",
"pivauthsig" => "pivAuthEncData",
"cardauthsig" => "cardAuthEncData",
"pivauthdigest" => "pivAuthChallengeHash", 
"cardauthdigest" => "cardAuthChallengeHash", 
"pivauthcerttype" => "pivAuthCertType", 
"cardauthcerttype" => "cardAuthCertType", 
"chuidcontents" => "A00000030800001000.5FC102",
"chuidsdo" => "A00000030800001000.5FC102.3E",
"fingerprints" => "A00000030800001000.5FC103.BC",
"portrait" => "A00000030800001000.5FC108.BC",
"secobjmap" => "A00000030800001000.5FC106.BA",
"secobjsdo" => "A00000030800001000.5FC106.BB",
);

$_certtypes = array(
"RSA" => "1",
"ECC" => "0",
);

$_test_mappings = array(
"TEST1" => "V01 General Tests",
"TEST2" => "V02 General Tests",
"TEST3" => "V03 Manipulated Credential: Keypair Checks ",
"TEST4" => "V04 Manipulated Credential: CHUID Object Checks ",
"TEST5" => "V05 VManipulated Credential: PIV/Card Auth Cert Checks ",
"TEST6" => "V06 Manipulated Credential: Facial Image Object Checks ",
"TEST7" => "V07 Manipulated Credential: Fingerprint Object Checks ",
"TEST8" => "V08 Manipulated Credential: Security Object Checks ",
"TEST9" => "V09 Date Checks: CHUID Signer Valid Date Checks ",
"TEST10" => "V10 Date Checks: Cert Signer Valid Date Checks ",
"TEST11" => "V11 Date Checks: PIV/Card Auth Date Expiring after CHUID Checks ",
"TEST12" => "V12 Date Checks: PIV/Card Auth Not-Yet-Valid Checks ",
"TEST13" => "V13 Date Checks: PIV/Card Auth Cert Date Checks ",
"TEST14" => "V14 Date Checks: CHUID Object Date Checks ",
"TEST15" => "V15 Skimmed Credential: PIV CHUID Checks ",
"TEST16" => "V16 Copied Credential: PIV Card Auth Cert Checks ",
"TEST17" => "V17 Copied Credential: PIV Facial Image Checks ",
"TEST18" => "V18 Copied Credential: PIV Fingerprint Checks ",
"TEST19" => "V19 Skimmed Credential: PIV-I CHUID Checks ",
"TEST20" => "V20 Copied Credential: PIV-I Card Auth Cert Checks ",
"TEST21" => "V21 Copied Credential: PIV-I Facial Image Checks ",
"TEST22" => "V22 Copied Credential: PIV-I Fingerprint Checks ",
"TEST23" => "V23 Skimmed Credential: Public/Private Key Checks ",
"TEST24" => "V24 Revoked Credential: PIV/Card Auth Cert Checks  ",
"PVal" => "Path Validation Tests ",
);


$_ignoretests = array(
"TEST3",
"TEST4",
"TEST5",
"TEST6",
"TEST7",
//"TEST8",
"TEST10",
//"TEST15",
//"TEST17",
//"TEST18",
"TEST24",
"CARDTYPE",

);

// this still need to report on the app -- but shouldn't make the registration station fail.  
$_warnings = array(
"WARNING50",
"WARNING52",
);

$_servertest_mappings = array(
"TEST3" => "V03s Manipulated Credential: Keypair Checks ",
"TEST4" => "V04s Manipulated Credential: CHUID Object Checks ",
"TEST5" => "V05s Manipulated Credential: PIV/Card Auth Cert Checks ",
"TEST6" => "V06s Manipulated Credential: Facial Image Object Checks ",
"TEST7" => "V07s Manipulated Credential: Fingerprint Object Checks ",
"TEST8" => "V08s Manipulated Credential: Security Object Checks ",
"TEST10" => "V10s Date Checks: Cert Signer Valid Date Checks ",
"TEST15" => "V15s Skimmed Credential: PIV CHUID Checks ",
"TEST17" => "V17s Copied Credential: PIV Facial Image Checks ",
"TEST18" => "V18s Copied Credential: PIV Fingerprint Checks ",
"TEST24" => "V24s Revoked Credential: PIV/Card Auth Cert Checks  ",
"PVal" => "Path Validation Tests ",
"WARNING50" => "Warning 50",
"WARNING52" => "Warning 52",
);


//default statuses,
$default_authentx_status = "active";
$default_entity_status = "active";
$default_token_status = "active";
//$token_testfailed_status = "failed";
$token_testfailed_status = "active";	//temp for AC testing

$isby = "Card Registration System";

//for
$_authorized_roles = array(
);

$_nocardauthok = true;

// used for the xscvp exe
$xscvphost = "192.168.18.32";
//$xscvphost = "66.165.167.30";
//$xscvphost = "192.168.16.30";

$cardtype_mappings = array(
"PIV" => "piv",
"PIVI" => "pivi",
);

//****************************
// FOR OCSP


// Set to true if existing ocsp objects are to be skipped. False if they are to be rewritten
$skipocsprefresh = true;
// Set to true to force the use of the cache to fetch certs. This is useful after it has been
// configured with the ocsp server URI's so that the cache is populated with initial responses.
$uselocalcahe = false;
// Set to true to send ocsp request as well as generate it. (Should be set true for local cache use)
// False simply generates the request without sending it
$sendocsprequest = false;
// Set this to true to perform cache seeding. This will read the ocsp requests from the ldap db
// and send them to the cache for processing, which will seed the cache with the request entries.
// This option is usually set after the initial database seeding, as it does not perform the ldap
// database seeding.
$performcacheseeding = false;

// List of cert gcoid to convert/create ocsp objects for
$certlist = array (
		0 => array ("keynum" => "9A", "gcoid" => "A000000308.5FC105"),
		1 => array ("keynum" => "9E", "gcoid" => "A000000308.5FC101"),
);

$cacert_store = "/authentx/app/cacerts/";
$tempdir = "/tmp/";

$gco_prefix = "A000000308.ocsp.";
$gcotag_dercert = "000070";
$gcotag_ocsprequest = "000001";
$gcotag_certserial = "000002";
$gcotag_certexpdate = "000003";

// Command to convert a DER cert to PEM format
// openssl x509 -inform der -in CERT.der -outform pem -out CERT.pem
$cmd_cert_der2pem_1 = "openssl x509 -inform der -in ";
$cmd_cert_der2pem_2 = " -outform pem -out ";

// Command to unzip a file to stdout
$cmd_unzip = "gzip -d -c ";

// Commands to extract information from the certificate
// Common first part for getting cert detail
$cmd_x509 = "openssl x509 -inform pem -text -noout -in ";
// Serial Number : 1252577091 (0x4aa8cf43)
$cmd_ocsp_serial = "openssl ocsp -req_text -reqin ";
$cmd_serial = " | grep \"Serial Number\" | sed 's/^.*\: //' ";
// OCSP URL : nfiocsp.managed.entrust.com
$cmd_ocspurl = " | grep \"OCSP - URI:http\" | sed 's/OCSP - URI\://' ";
// expdate : Mar 25 04:00:00 2016 GMT
$cmd_expdate = " | grep \"Not After\" | sed 's/Not After \://' ";
// issuer URL : nfimediumsspweb.managed.entrust.com/AIA/CertsIssuedToNFIMediumSSPCA.p7c
$cmd_issuerurl = " | grep \"CA Issuers - URI:http\" | sed 's/CA Issuers = URI\://' ";

// Command to send an ocsp request to a responder
$cmd_sendocspreq = "openssl ocsp -url http://127.0.0.1 -noverify -reqin ";

// Command to fetch a file, with 5 retries
$cmd_wget = "wget -t 5 -P ".$tempdir." ";

// Command to convert a p7 der cert to a pem format cert
$cmd_cacert_p7c2pem_1 = "openssl pkcs7 -print_certs -inform der -in ";
$cmd_cacert_p7c2pem_2 = " -out ";

// Command to generate an ocsp request in a file
// openssl ocsp -issuer CACert.pem -cert CERT.pem -url ocspURL -reqout CERT.ocspreq -noverify -no_nonce
$cmd_ocspreq_1 = "openssl ocsp -noverify -no_nonce -issuer ";
$cmd_ocspreq_2 = " -cert ";
$cmd_ocspreq_3 = " -url ";
$cmd_ocspreq_4 = " -reqout ";

//******************************

?>
