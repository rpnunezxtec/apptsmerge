<?PHP
// $Id: config-reqcr.php 271 2008-04-22 21:41:23Z gswan $
// Service configuration file
//    xsvc-reqcr.xas?id=tokenacid

require_once("config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "tokentype";
$_service_version = "1.3";

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
//  0x01 : multiple instance attribute. Signifies that the attribute is not to be replaced
//  0x02 : 
//  0x04 : date format conversion required from/to openldap format and MM-DD-YYYY format
//  0x10 :
//  0x20 :
//  0x40 :
//  0x80 :

$_db_elements = array (
//"entity:ediident:entity|ediident|x|txt:txt|0x00",
"token:tokenclass:credential|tokentype|Token Type|txt:txt|0x00",
"token:tokenclass:credential|tokenclass|Token Type|txt:txt|0x00",
);

$_map_tokentype = array (
"pivi" => "pivi",
"piv" => "piv",
"fac" => "fac",
"dac" => "dac",
);


?>
