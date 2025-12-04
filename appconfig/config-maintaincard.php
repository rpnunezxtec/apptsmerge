<?PHP
// $Id: config-maintaincard.php 660 2008-09-04 03:00:46Z atlas $
// Service configuration file
//    xsvc-maintaincard.xas?id=tokenacid

require_once("../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "maintaincardresult";
$_service_version = "1.0";

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

// FLAGS:
// Set this to true if you want to override the administrator.  It will assume action=reqCerts.
$admin_override = true;

$_db_elements = array (
"token:tokenclass:credential|tokentype|Token Type|txt:txt|0x00",
"token:action:credential|maintenance|Token Mainatenance|txt:txt|0x01",
);



$_db_tokengroups = array(
"token" => array(
        "token:acid:credential|cuid|Token CUID|txt:txt|0x00",
        "token:issdate:credential|issdate|Issue Date|txt:txt|0x04",
        "token:status:credential|status|Token Status|txt:txt|0x00",
        ),
);


?>
