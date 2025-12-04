<?PHP
// $Id: config-print.php 984 2010-02-12 02:40:43Z atlas $
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

require_once('config-app.php');

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "tokenwflowresult";
$_service_version = "1.0";

$_db_elements = array(
"token:tokenclass:credential|tokentype|Token Workflow|txt:txt|0x00",
);

$_map_tokentype = array (
"piv" => "piv",
"pivi" => "pivi",
"dac" => "dac",
);


?>
