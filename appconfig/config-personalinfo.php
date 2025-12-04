<?PHP
// $Id: config-personalinfo.php 174 2009-03-05 07:28:53Z atlas $
// Service configuration file
//    xsvc-personalinfo.xas?id=useracid
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
$_service_envelope = "personalinforesult";
$_service_version = "1.1";


$server_url = "https://66.165.167.155/authentx/servicesasa";

$clientcert_sn = "585445430091";

$_db_elements = array(
"credential:acid:credential|acid|Acid|txt:txt|0x01",
"entity:firstname:entity|firstname|First Name|txt:txt|0x00",
"entity:mi:entity|mi|Middle Name|txt:txt|0x00",
"entity:lastname:entity|lastname|Last Name|txt:txt|0x00",
"entity:bioid=portrait:jpgpic:biometric|portrait|Portrait|b64:bin|0x00",
"token:acid:credential|tokenacid|Token Acid|txt:txt|0x01",
);

$_constant_elements = array (
);

// The tokengroups extract token data from the database and maintain the data set elements grouped together
// For example, if a user has many tokens:
// <token>
//		<cuid>cuidstring 1</cuid>
//		<issdate>issdatestring 1<issdate>
//		<status>statusstring 1</status>
// </token>
// <token>
//		<cuid>cuidstring 2</cuid>
//		<issdate>issdatestring 2<issdate>
//		<status>statusstring 2</status>
// </token>
//
// The envelope tag for each group is taken from the group array name.


$_db_tokengroups = array(
//"token" => array(
//	),
);

$_constant_CMUConfig = "<CMUSetup>
<ServerSite>".$server_url."</ServerSite>
<ClientCert>".$clientcert_sn."</ClientCert>
</CMUSetup>";

?>
