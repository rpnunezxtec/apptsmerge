<?PHP

// $Id: config-credcreate.php 105 2008-11-18 01:44:38Z gswan $
// This file contains configuration values used by the Token Creation function

require_once("../../appconfig/config-app.php");

define("LOCALDIR", ".");
define("XCOPY_ACTION", "create");
define("XCOPY_XMLFILE", "../../appconfig/piv/carddef-usaccess-piv.xml");

$localdir = ".";
$form_action = "create";
$xml_filename = "carddef-usaccess-piv.xml";

define("XROOT_BRANCH", $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop);
define("TOKEN_BRANCH", $ldap_credentials.",ounit=credentials,".$ldap_treetop);

// Location of local CA Certificate
define("LOCALCACRT_PATH", "/authentx/app/certs/authentx-piviSigner-2017.DER"); 			// location of signing cert to get dn
define("LOCALCAKEY_PATH", "file:///authentx/app/certs/authentx-piviSigner-private-2017.key"); 		// used for soft cert
define("LOCALCAKEY_PSWD", "password");  									// used for soft cert

// Command line to sign data contents using Luna hsm
// the luna requires these parameters: [partition label] [token secret PIN] [data to sign in hex format without spaces]
define("LUNASIG_CMD", "/opt/src/certsign/certsign EIMSTest bG4G-NXdP-MC3x-K9C4");

// Executable to run photo compression
$_compress_photo_exe = "/authentx/app/https/authentx/appcore/CompressLinux";

//standard webservice definitions:
$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "credcreateresult";
$_service_version = "1.0";

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

$_status_check = "pivPending";
$ounit = "piv";

$_rules_apply = true;

$_rulechecks = array(
"piv" => array("photocap", "fpcap", "doccap"),
);


$_db_tokengroups = array(
"token" => array(
	"token:cid:credential|cid|Token CID|txt:txt|0x00",
	"token:acid:credential|cuid|Token CUID|txt:txt|0x01",
	"token:status:credential|status|Token Status|txt:txt|0x00",
	),
);

// Include the email templates for selection
include_once("../../appconfig/email_templates.php");

// The email templates to use in this service
if (!defined("ET_PRINTING_PIV"))
	define("ET_PRINTING_PIV", "process_printing");	
	
	
?>
