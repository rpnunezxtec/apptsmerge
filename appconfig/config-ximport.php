<?PHP

// $Id: config-ximport.php 174 2009-03-05 07:28:53Z atlas $
// Configuration for the ximport system functionality
require_once("../appconfig/config-app.php");

// For the connection to the remote servers
$_service_envelope = "ximport";
$_service_version = "1.0";

// used to turn on connection debugging
$_debug = false;

// List of server URL's to contact in order of preference
$_source_url_list = array (
"https://gsatenant.authentx.com/gsatenant/services/xsvc-xexport.xas",
//"https://www.authentx.com/authentx/services/xsvc-xexport.xas",
//"https://dev.authentx.com/si/services/xsvc-xexport.xas",
//"https://159.142.134.17/gsa/services/xsvc-xexport.xas",
);

$_ca_clientcertificate = "/authentx/app/https/authentx/appconfig/adrootca64.crt";
$_ca_clientpassword = "password";

//a region MUST be specified, so if it couldn't be downloaded, use a default:
$_default_belongsto = "imports";
$_default_entity_status = "active";

// The base DN's for the application branches
$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_entity_basedn = $ldap_entities.",ounit=entities,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;

// The following are the data mapping specifications.
// This maps returned data elements identified by their XML tags to specific
// database locations. It also includes translation configuration between the data
// received and the database storage requirements.

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

$_db_elements = array(
"credential:acid:credential|acid|Acid|txt:txt|0x01",

"credential:issby:credential|credential.issby|none|txt:txt|0x00",
"credential:issdate:credential|credential.issuedate|none|txt:txt|0x04",
"credential:rightsto:credential|rightsto|none|txt:txt|0x01",
"credential:role:credential|role|none|txt:txt|0x01",
"credential:userPassword:credential|userPassword|none|txt:txt|0x00",
"credential:xrole:credential|xrole|none|txt:txt|0x01",
"entity:addrid=home:city:address|homecity|none|txt:txt|0x00",
"entity:addrid=home:cntry:address|homecountry|none|txt:txt|0x00",
"entity:addrid=home:email:address|homeemail|none|txt:txt|0x00",
"entity:addrid=home:phone:address|homephone|none|txt:txt|0x00",
"entity:addrid=home:postcode:address|homepostcode|none|txt:txt|0x00",
"entity:addrid=home:st:address|homestate|none|txt:txt|0x00",
"entity:addrid=home:street:address|homestreet|none|txt:txt|0x00",
"entity:bioid=L_Index:jpgpic:biometric|biolindexpic|none|b64:bin|0x00",
"entity:bioid=L_Index:xblk:biometric|biolindxblk|none|b64:bin|0x00",
"entity:bioid=PIV_Primary:rem:biometric|PIV_Primary_Finger_Number|none|txt:txt|0x00",
"entity:bioid=PIV_Primary:xblk:biometric|LS_L_Index_ANSI378|none|b64:bin|0x00",
"entity:bioid=PIV_Primary:xblk:biometric|LS_R_Index_ANSI378|none|b64:bin|0x00",
"entity:bioid=PIV_Secondary:rem:biometric|PIV_Secondary_Finger_Number|none|txt:txt|0x00",
"entity:bioid=portrait:jpgpic:biometric|portraitjpg|none|b64:bin|0x00",
"entity:bioid=portrait_compressed:jpgpic:biometric|portraitcompjpg|none|b64:bin|0x00",
"entity:bioid=R_Index:jpgpic:biometric|biorindexpic|none|b64:bin|0x00",
"entity:bioid=R_Index:xblk:biometric|biorindxblk|none|b64:bin|0x00",
"entity:emplid=contract:city:employment|contractor.city|none|txt:txt|0x00",
"entity:emplid=contract:cntry:employment|contractor.country|none|txt:txt|0x00",
"entity:emplid=contract:employer:employment|contractor.employer|none|txt:txt|0x00",
"entity:emplid=contract:postcode:employment|contractor.postcode|none|txt:txt|0x00",
"entity:emplid=contract:st:employment|contractor.state|none|txt:txt|0x00",
"entity:emplid=contract:street:employment|contractor.street|none|txt:txt|0x00",
"entity:emplid=usaccess:city:employment|officecity|none|txt:txt|0x00",
"entity:emplid=usaccess:cntry:employment|officecountry|none|txt:txt|0x00",
"entity:emplid=usaccess:email:employment|officeemail|none|txt:txt|0x00",
"entity:emplid=usaccess:phone:employment|officephone|none|txt:txt|0x00",
"entity:emplid=usaccess:position:employment|position|none|b64:txt|0x00",
"entity:emplid=usaccess:postcode:employment|officepostcode|none|txt:txt|0x00",
"entity:emplid=usaccess:st:employment|officestate|none|txt:txt|0x00",
"entity:emplid=usaccess:street:employment|officestreet|none|txt:txt|0x00",
"entity:eyeclr:entity|eyecolor|none|txt:txt|0x00",
"entity:firstname:entity|firstname|First Name|txt:txt|0x00",
"entity:mi:entity|mi|Middle Name|txt:txt|0x00",
"entity:lastname:entity|lastname|Last Name|txt:txt|0x00",
"entity:suffix:entity|suffix|Suffix|txt:txt|0x00",
"entity:status:entity|entitystatus|none|txt:txt|0x00",
"entity:ssn:entity|ssn|SSN|txt:txt|0x200",
"entity:gndr:entity|gender|Gender|txt:txt|0x00",
"entity:dob:entity|dob|Date of Birth|txt:txt|0x04",
"entity:gcoid=general:xblk:002502:gco|affiliation|Affiliation|txt:txt|0x00",
"entity:pob:entity|pob|Place of Birth|txt:txt|0x00",
"entity:gcoid=general:xblk:00001f:gco|bldgassignment|Building Assignment|txt:txt|0x00",
"credential:belongsto:credential|belongsto|Belongs To|txt:txt|0x00",
"entity:sponsor:entity|sponsorname|Sponsor Name|txt:txt|0x00",
"entity:gcoid=general:xblk:000029:gco|sponsoremail|Sponsor Email|txt:txt|0x00",
"entity:gcoid=general:xblk:00002a:gco|sponsorphone|Sponsor Phone|txt:txt|0x00",
"entity:gcoid=general:xblk:00002b:gco|sponsoraffiliation|Sponsor Affiliation|txt:txt|0x00",
"entity:docid=toletter:xblk:xdocument|tol|Tentative Offer Letter|b64:bin|0x00",
"entity:docid=toletter:issdate:xdocument|tldate|Tentative Letter Date|txt:txt|0x04",
"entity:docid=toletter:desc:xdocument|tlfile|Tentative Letter File Name|txt:txt|0x00",
"entity:gcoid=personal:xblk:000021:gco|priorbgi|Prior BGI|txt:txt|0x00",
"entity:gcoid=general:xblk:00002c:gco|contractnum|Contract Number|txt:txt|0x00",
"entity:gcoid=general:xblk:00002d:gco|contractexpdate|Contract Exp Date|txt:txt|0x04",
"entity:sysid=entitybranchname:sysval:xsystem|agency|Agency|txt:txt|0x00",
"entity:gcoid=contractor:xblk:0020D8:gco|contractor.x0020D8.supname|none|txt:txt|0x00",
"entity:gcoid=contractor:xblk:0020D9:gco|contractor.x0020D9.supphone|none|txt:txt|0x00",
"entity:gcoid=contractor:xblk:0020DE:gco|contractor.x0020DE.supemail|none|txt:txt|0x00",
"entity:gcoid=general:xblk:000005:gco|agency|none|txt:txt|0x00",
"entity:gcoid=general:xblk:000010:gco|officebldgname|none|txt:txt|0x00",
"entity:gcoid=general:xblk:000011:gco|officebldgnumber|none|txt:txt|0x00",
"entity:gcoid=general:xblk:000018:gco|officephoneext|none|txt:txt|0x00",
"entity:gcoid=general:xblk:000019:gco|officefax|none|txt:txt|0x00",
"entity:gcoid=general:xblk:00001b:gco|officeroomnum|none|txt:txt|0x00",
"entity:gcoid=general:xblk:002029:gco|officecell|none|txt:txt|0x00",
"entity:gcoid=general:xblk:00202e:gco|officesym|none|txt:txt|0x00",
"entity:gcoid=general:xblk:002046:gco|gsageneral.x002046.paygrade|none|txt:txt|0x00",
"entity:gcoid=general:xblk:0020DE:gco|gsageneral.x0020DE.supemail|none|txt:txt|0x00",
"entity:gcoid=general:xblk:002502:gco|affiliation|none|txt:txt|0x00",
"entity:gcoid=general:xblk:002508:gco|son|none|txt:txt|0x00",
"entity:gcoid=general:xblk:00250a:gco|soi|none|txt:txt|0x00",
"entity:gcoid=medical:xblk:0020D0:gco|gsamedical.x0020D0|none|txt:txt|0x00",
"entity:gcoid=medical:xblk:0020D1:gco|gsamedical.x0020D1|none|txt:txt|0x00",
"entity:gcoid=medical:xblk:0020D3:gco|gsamedical.x0020D3|none|txt:txt|0x00",
"entity:gcoid=medical:xblk:0020D4:gco|gsamedical.x0020D4|none|txt:txt|0x00",
"entity:gcoid=medical:xblk:0020D5:gco|gsamedical.x0020D5|none|txt:txt|0x00",
"entity:gcoid=medical:xblk:0020D6:gco|gsamedical.x0020D6|none|txt:txt|0x00",
"entity:gcoid=medical:xblk:0020D7:gco|gsamedical.x0020D7|none|txt:txt|0x00",
"entity:gcoid=medical:xblk:0020DA:gco|gsamedical.x0020DA|none|txt:txt|0x00",
"entity:gcoid=personal:xblk:000001:gco|birthplace|none|txt:txt|0x00",
"entity:gcoid=personal:xblk:000002:gco|birthstate|none|txt:txt|0x00",
"entity:gcoid=personal:xblk:000003:gco|birthcountry|none|txt:txt|0x00",
"entity:gcoid=security:xblk:000040:gco|gsasecurity.x000040.userid|none|txt:txt|0x00",
"entity:gcoid=security:xblk:000041:gco|gsasecurity.x000041.logindomain|none|txt:txt|0x00",
"entity:gcoid=security:xblk:000042:gco|gsasecurity.x000042.loginpasswd|none|txt:txt|0x00",
"entity:gcoid=security:xblk:002031:gco|gsasecurity.x002031.accesspin|none|txt:txt|0x00",
);

// data to be stored for each token set encountered. There may be many tokens returned
// for a given individual.

$_db_tokengroups = array(
"token:acid:credential|token.acids|none|txt:txt|0x01",
"token:gcoid=card:xblk:002070:gco|token.fasc_n|Fascn|txt:txt|0x00",
"token:gcoid=card:xblk:002069:gco|token.fax|Token FAX|hex:bin|0x00",
"token:action:credential|token.action|none|txt:txt|0x00",
"token:cardtopology:credential|token.cardtopology|none|txt:txt|0x00",
"token:ctype:credential|token.ctype|none|txt:txt|0x00",
"token:cuid:credential|token.cuid|none|txt:txt|0x00",
"token:desc:credential|token.description|none|txt:txt|0x00",
"token:expdate:credential|token.expdate|none|txt:txt|0x04",
"token:gcoid=A000000308.5FC101:xblk:000070:gco|A00000030800001000.5C035FC101.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:000071:gco|A00000030800001000.5C035FC101.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC101:xblk:0000FE:gco|A00000030800001000.5C035FC101.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc101:xblk:999999:gco|A00000030800001000.5C035FC101|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000030:gco|A00000030800001000.5C035FC102.30|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000034:gco|A00000030800001000.5C035FC102.34|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000035:gco|A00000030800001000.5C035FC102.35|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:00003E:gco|A00000030800001000.5C035FC102.3E|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:0000FE:gco|A00000030800001000.5C035FC102.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc102:xblk:999999:gco|A00000030800001000.5C035FC102|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000BC:gco|A00000030800001000.5C035FC103.BC|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC103:xblk:0000FE:gco|A00000030800001000.5C035FC103.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc103:xblk:999999:gco|A00000030800001000.5C035FC103|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000070:gco|A00000030800001000.5C035FC105.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:000071:gco|A00000030800001000.5C035FC105.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC105:xblk:0000FE:gco|A00000030800001000.5C035FC105.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc105:xblk:999999:gco|A00000030800001000.5C035FC105|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC106:xblk:0000BA:gco|A00000030800001000.5C035FC106.BA|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC106:xblk:0000BB:gco|A00000030800001000.5C035FC106.BB|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC106:xblk:0000FE:gco|A00000030800001000.5C035FC106.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc106:xblk:999999:gco|A00000030800001000.5C035FC106|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F0:gco|A00000030800001000.5C035FC107.F0|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F1:gco|A00000030800001000.5C035FC107.F1|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F2:gco|A00000030800001000.5C035FC107.F2|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F3:gco|A00000030800001000.5C035FC107.F3|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F4:gco|A00000030800001000.5C035FC107.F4|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F5:gco|A00000030800001000.5C035FC107.F5|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F6:gco|A00000030800001000.5C035FC107.F6|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000F7:gco|A00000030800001000.5C035FC107.F7|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000FA:gco|A00000030800001000.5C035FC107.FA|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000FB:gco|A00000030800001000.5C035FC107.FB|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000FC:gco|A00000030800001000.5C035FC107.FC|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC107:xblk:0000FD:gco|A00000030800001000.5C035FC107.FD|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc107:xblk:999999:gco|A00000030800001000.5C035FC107|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC108:xblk:0000BC:gco|A00000030800001000.5C035FC108.BC|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC108:xblk:0000FE:gco|A00000030800001000.5C035FC108.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc108:xblk:999999:gco|A00000030800001000.5C035FC108|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000000:gco|A00000030800001000.5C035FC109.00|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000001:gco|A00000030800001000.5C035FC109.01|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000002:gco|A00000030800001000.5C035FC109.02|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000003:gco|A00000030800001000.5C035FC109.03|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000004:gco|A00000030800001000.5C035FC109.04|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000005:gco|A00000030800001000.5C035FC109.05|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC109:xblk:000006:gco|A00000030800001000.5C035FC109.06|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc109:xblk:999999:gco|A00000030800001000.5C035FC109|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000070:gco|A00000030800001000.5C035FC10A.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:000071:gco|A00000030800001000.5C035FC10A.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10A:xblk:0000FE:gco|A00000030800001000.5C035FC10A.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc10a:xblk:999999:gco|A00000030800001000.5C035FC10a|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000070:gco|A00000030800001000.5C035FC10B.70|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:000071:gco|A00000030800001000.5C035FC10B.71|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC10B:xblk:0000FE:gco|A00000030800001000.5C035FC10B.FE|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc10b:xblk:999999:gco|A00000030800001000.5C035FC10b|none|b64:bin|0x00",
"token:gcoid=A000000308.5fc10c:xblk:999999:gco|A00000030800001000.5C035FC10c|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9A:xblk:000001:gco|A000000308.certdn.9A.01|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9A:xblk:000002:gco|A000000308.certdn.9A.02|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9A:xblk:000003:gco|A000000308.certdn.9A.03|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9A:xblk:000004:gco|A000000308.certdn.9A.04|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9A:xblk:000005:gco|A000000308.certdn.9A.05|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9A:xblk:000006:gco|A000000308.certdn.9A.06|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9A:xblk:000007:gco|A000000308.certdn.9A.07|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9C:xblk:000001:gco|A000000308.certdn.9C.01|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9C:xblk:000002:gco|A000000308.certdn.9C.02|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9C:xblk:000003:gco|A000000308.certdn.9C.03|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9C:xblk:000004:gco|A000000308.certdn.9C.04|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9C:xblk:000005:gco|A000000308.certdn.9C.05|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9C:xblk:000006:gco|A000000308.certdn.9C.06|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9C:xblk:000007:gco|A000000308.certdn.9C.07|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9D:xblk:000001:gco|A000000308.certdn.9D.01|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9D:xblk:000002:gco|A000000308.certdn.9D.02|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9D:xblk:000003:gco|A000000308.certdn.9D.03|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9D:xblk:000004:gco|A000000308.certdn.9D.04|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9D:xblk:000005:gco|A000000308.certdn.9D.05|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9D:xblk:000006:gco|A000000308.certdn.9D.06|none|b64:bin|0x00",
"token:gcoid=A000000308.certdn.9D:xblk:000007:gco|A000000308.certdn.9D.07|none|b64:bin|0x00",
"token:gcoid=A000000308.csr.9A:xblk:003070:gco|A000000308.pk.csr.9A.3070|none|b64:bin|0x00",
"token:gcoid=A000000308.csr.9C:xblk:003070:gco|A000000308.pk.csr.9C.3070|none|b64:bin|0x00",
"token:gcoid=A000000308.csr.9D:xblk:003070:gco|A000000308.pk.csr.9D.3070|none|b64:bin|0x00",
"token:gcoid=card:xblk:000031:gco|card.x000031.cardpin|none|b64:bin|0x00",
"token:gcoid=card:xblk:002055:gco|card.x002055.cardpickup|none|b64:bin|0x00",
"token:gcoid=card:xblk:002071:gco|card.x002071.cardseqnum|none|b64:bin|0x00",
"token:gcoid=card:xblk:002072:gco|card.x002072.cardcreatedate|none|b64:bin|0x00",
"token:gcoid=card:xblk:002073:gco|card.x002073.seiwgagencycode|none|b64:bin|0x00",
"token:gcoid=card:xblk:002074:gco|card.x002074.seiwgsyscode|none|b64:bin|0x00",
"token:gcoid=card:xblk:002075:gco|card.x002075.sewigcrednum|none|b64:bin|0x00",
"token:gcoid=card:xblk:002076:gco|card.x002076.seiwgcredser|none|b64:bin|0x00",
"token:gcoid=card:xblk:002077:gco|card.x002077.seiwgisscntr|none|b64:bin|0x00",
"token:gcoid=card:xblk:002078:gco|card.x002078.opid|none|b64:bin|0x00",
"token:gcoid=card:xblk:002079:gco|card.x002079.imageid|none|b64:bin|0x00",
"token:gcoid=card:xblk:00207A:gco|card.x00207A|none|b64:bin|0x00",
"token:gcoid=card:xblk:00207B:gco|card.x00207B|none|b64:bin|0x00",
"token:gcoid=card:xblk:002081:gco|card.x002081.yesnolawenf|none|b64:bin|0x00",
"token:gcoid=card:xblk:002082:gco|card.x002082.yesnoproppass|none|b64:bin|0x00",
"token:gcoid=card:xblk:002083:gco|card.x002083.yesnochildcare|none|b64:bin|0x00",
"token:gcoid=card:xblk:002084:gco|card.x002084.yesnoprintssnflag|none|b64:bin|0x00",
"token:gcoid=card:xblk:002085:gco|card.x002085.yesnoprintdobflag|none|b64:bin|0x00",
"token:gcoid=card:xblk:0020c5:gco|card.x0020c5.yesnoemergresp|none|b64:bin|0x00",
"token:gcoid=card:xblk:002320:gco|card.x002320|none|b64:bin|0x00",
"token:gcoid=card:xblk:002321:gco|card.x002321|none|b64:bin|0x00",
"token:gcoid=card:xblk:002336:gco|card.x002336.accesspin|none|b64:bin|0x00",
"token:gcoid=card:xblk:002340:gco|card.x002340|none|b64:bin|0x00",
"token:gcoid=card:xblk:002341:gco|card.x002341|none|b64:bin|0x00",
"token:gcoid=card:xblk:002342:gco|card.x002342|none|b64:bin|0x00",
"token:gcoid=card:xblk:002343:gco|card.x002343|none|b64:bin|0x00",
"token:gcoid=card:xblk:002344:gco|card.x002344|none|b64:bin|0x00",
"token:gcoid=card:xblk:002345:gco|card.x002345|none|b64:bin|0x00",
"token:gcoid=card:xblk:002346:gco|card.x002346|none|b64:bin|0x00",
"token:gcoid=card:xblk:002347:gco|card.x002347|none|b64:bin|0x00",
"token:gcoid=card:xblk:002455:gco|card.x002455|none|b64:bin|0x00",
"token:gcoid=card:xblk:002456:gco|card.x002456|none|b64:bin|0x00",
"token:gcoid=card:xblk:002521:gco|card.x002521.specdesign|none|b64:bin|0x00",
"token:sysid=diversifier:sysval:xsystem|diversifier.sysval|none|txt:txt|0x00",
"token:gcoid=card:xblk:000031:gco|gsacard.x000031.cardpin|none|b64:bin|0x00",
"token:gcoid=card:xblk:002055:gco|gsacard.x002055.cardpickup|none|b64:bin|0x00",
"token:gcoid=card:xblk:002081:gco|gsacard.x002081.yesnolawenf|none|b64:bin|0x00",
"token:gcoid=card:xblk:002082:gco|gsacard.x002082.yesnoproppass|none|b64:bin|0x00",
"token:gcoid=card:xblk:002083:gco|gsacard.x002083.yesnochildcare|none|b64:bin|0x00",
"token:gcoid=card:xblk:002084:gco|gsacard.x002084.yesnoprintssnflag|none|b64:bin|0x00",
"token:gcoid=card:xblk:002085:gco|gsacard.x002085.yesnoprintdobflag|none|b64:bin|0x00",
"token:gcoid=card:xblk:0020c5:gco|gsacard.x0020c5.yesnoemergresp|none|b64:bin|0x00",
"token:gcoid=card:xblk:002336:gco|gsacard.x002336.accesspin|none|b64:bin|0x00",
"token:gcoid=card:xblk:002521:gco|gsacard.x002521.specdesign|none|b64:bin|0x00",
"token:sysid=icccin:sysval:xsystem|icccin.sysval|none|txt:txt|0x00",
"token:gcoid=sohash:xblk:000001:gco|sohash.x000001|none|b64:bin|0x00",
"token:gcoid=sohash:xblk:000002:gco|sohash.x000002|none|b64:bin|0x00",
"token:gcoid=sohash:xblk:000004:gco|sohash.x000004|none|b64:bin|0x00",
"token:gcoid=sohash:xblk:000006:gco|sohash.x000006|none|b64:bin|0x00",
"token:gcoid=sohash:xblk:000007:gco|sohash.x000007|none|b64:bin|0x00",
"token:rem:credential|token.remark|none|txt:txt|0x00",
"token:status:credential|status|none|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002039:gco|seiwg|none|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002040:gco|fedid|none|txt:txt|0x00",
"token:gcoid=token:xblk:002073:gco|topoinfo.x002073.seiwgagencycode|none|b64:bin|0x00",
"token:gcoid=token:xblk:002074:gco|topoinfo.x002074.seiwgsyscode|none|b64:bin|0x00",
"token:gcoid=token:xblk:002075:gco|topoinfo.x002075.sewigcrednum|none|b64:bin|0x00",
"token:gcoid=token:xblk:002076:gco|topoinfo.x002076.seiwgcredser|none|b64:bin|0x00",
"token:gcoid=token:xblk:002077:gco|topoinfo.x002077.seiwgisscntr|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:002081:gco|topoinfo.x002081.ynlawenf|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:002082:gco|topoinfo.x002082.ynproppass|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:002083:gco|topoinfo.x002083.ynchildcare|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:0020c5:gco|topoinfo.x0020c5.ynemergresp|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:002101:gco|topoinfo.x002101.first|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:002102:gco|topoinfo.x002102.last|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:002355:gco|topoinfo.x002355.agencyrole|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:002356:gco|topoinfo.x002356.agencytext|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:002502:gco|topoinfo.x002502.affiliation|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:002521:gco|topoinfo.x002521.specdesignation|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990006:gco|topoinfo.x990006.height|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990007:gco|topoinfo.x990007.haircolor|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990008:gco|topoinfo.x990008.eyecolor|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:99000A:gco|topoinfo.x99000A.crdnum|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990101:gco|topoinfo.x990101.crdnum|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990102:gco|topoinfo.x990102.mmyyexpdate|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990104:gco|topoinfo.x990104.firstnamemi|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990105:gco|topoinfo.x990105.yyyymmmddissdate|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:99010B:gco|topoinfo.x99010B.fullname|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990202:gco|topoinfo.x990202.yyyymmmddexpdate|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990204:gco|topoinfo.x990204.region|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990205:gco|topoinfo.x990205.agency|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:990302:gco|topoinfo.x990302.mmmyyyyexpdate|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:9920c6:gco|topoinfo.x9920c6.cardtopo|none|b64:bin|0x00",
"token:gcoid=topoinfo:xblk:002073:gco|agency|Token Agency ID|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002074:gco|system|Token System ID|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:002075:gco|crednum|Token Crednum|txt:txt|0x00",
//conversions
"token:gcoid=A000000308.5FC102:xblk:000030:gco|A000000A01.3000.30|none|b64:bin|0x00",
"token:gcoid=A000000308.5FC102:xblk:000034:gco|A000000A01.3000.34|none|b64:bin|0x00",
"token:gcoid=card:xblk:002070:gco|fas|Fascn|txt:txt|0x00",
"token:cuid:credential|cuid|none|txt:txt|0x00",
);

// Data to be stored after the other data has been saved and the HSPD12 objects created
// and acid/scid set built. This data relies on these things to be complete.
$_db_elements_postproc = array(
);

?>