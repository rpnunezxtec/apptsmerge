<?PHP
// $Id:$
// Service configuration file
//    xsvc-emstat.xas

require_once("config-app.php");

$_device_basedn = $ldap_em_devices.",permissions=em,".$ldap_permissions.",ounit=permissions,".$ldap_treetop;
$_service_envelope = "emstatresult";
$_service_version = "1.0";

// Allow status updates to LDAP. Don't enable this unless absolutely required.
$_emldapupdate = false;

// list of default email addresses to send change-of-state emails.
$_alert_email_list = array(
"gswan@xtec.com",
"sdiaz@xtec.com",
);

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

$_db_elements = array (
"entity:firstname:entity|firstname|First Name|txt:txt|0x00",
"entity:mi:entity|mi|Middle Name|txt:txt|0x00",
"entity:lastname:entity|lastname|Last Name|txt:txt|0x00",
"entity:suffix:entity|suffix|Suffix|txt:txt|0x00",
"entity:dob:entity|dob|Date of Birth|txt:txt|0x04",
"entity:ssn:entity|ssn|Social Security Number|txt:txt|0x200",
"entity:fsn:entity|fsn|Employee Unique ID|txt:txt|0x200", 
);


?>