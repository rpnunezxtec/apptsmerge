<?PHP

// $Id: config-credupdate.php 66 2013-05-28 01:30:32Z hjackson $

// This file contains configuration values used by the Token Creation function
// --used for update to add/update the cbeff signed data objects at DOS

//if (!defined('_XC_DEBUG')) define('_XC_DEBUG', true);

// global variables requred by credcreate - for upddate
require_once("../../appconfig/config-app.php");

define("XCOPY_ACTION", "update");
define("XCOPY_XMLFILE", "../../appconfig/pivi/carddef-usaccess-pivi-update.xml");

define("XROOT_BRANCH", $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop);
define("TOKEN_BRANCH", $ldap_credentials.",ounit=credentials,".$ldap_treetop);

// Location of local CA Certificate
define("LOCALCACRT_PATH", "/authentx/app/certs/authentx-piviSigner-2017.DER"); 			// location of signing cert to get dn
define("LOCALCAKEY_PATH", "file:///authentx/app/certs/authentx-piviSigner-private-2017.key"); 		// used for soft cert
define("LOCALCAKEY_PSWD", "password");  									// used for soft cert

// Command line to sign data contents using Luna hsm
// the luna requires these parameters: [partition label] [token secret PIN] [data to sign in hex format without spaces]
// define("LUNASIG_CMD", "/opt/src/certsign/certsign EIMSTest bG4G-NXdP-MC3x-K9C4");

?>
