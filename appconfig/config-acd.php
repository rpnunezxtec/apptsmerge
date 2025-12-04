<?PHP
// $Id: config-acd.php 44 2008-10-29 06:06:24Z atlas $
// Service configuration file
//    xsvc-acd.xas?id=useracid
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
//  0x01 :
//  0x02 :
//  0x04 : Date formatting required from/to LDAP format to MM-DD-YYYY format.
//  0x10 :
//  0x20 :
//  0x40 :
//  0x80 :

require_once("../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "acdresult";
$_service_version = "1.1";

$_db_elements = array(
"entity:firstname:entity|firstname|First Name|txt:txt|0x00",
"entity:mi:entity|mi|Middle Name|txt:txt|0x00",
"entity:lastname:entity|lastname|Last Name|txt:txt|0x00",
"entity:suffix:entity|suffix|Suffix|txt:txt|0x00",
"entity:dob:entity|dob|Date of Birth|txt:txt|0x04",
"entity:bioid=portrait:jpgpic:biometric|portrait|Portrait|b64:bin|0x00",
"entity:addrid=home:street:address|street|Home Address|txt:txt|0x00",
"entity:addrid=home:city:address|city|Home City|txt:txt|0x00",
"entity:addrid=home:st:address|st|Home State|txt:txt|0x00",
"entity:addrid=home:postcode:address|postcode|Home Zip|txt:txt|0x00",
"entity:addrid=home:cntry:address|cntry|Country|txt:txt|0x00",
);

$_constant_elements = array (
//"EMsetup_portrait_width" => "420",
//"EMsetup_portrait_height" => "560",
//"EMsetup_portrait_headgap" => "20",
);


$_db_tokengroups = array(
"token" => array(
	"token:acid:credential|cuid|Token CUID|txt:txt|0x00",
	"token:issdate:credential|issdate|Issue Date|txt:txt|0x04",
	"token:expdate:credential|expdate|Expiration Date|txt:txt|0x04",
	"token:status:credential|status|Token Status|txt:txt|0x00",
	"token:legnum:credential|legnum|Credential Number|txt:txt|0x00",
	"token:gcoid=card:upin:gco|upin|Physical Pin|txt:txt|0x00",
	),
);

?>