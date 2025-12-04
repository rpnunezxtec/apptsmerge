<?PHP
// $Id: config-processdiscovery.php 44 2008-10-29 06:06:24Z atlas $
// Service configuration file
//    xsvc-readdata.xas?id=useracid

require_once("../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "processdiscoveryresult";
$_service_version = "1.2";

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

$_db_elements = array (
"entity:firstname:entity|firstname|First Name|txt:txt|0x00",
"entity:mi:entity|mi|Middle Name|txt:txt|0x00",
"entity:lastname:entity|lastname|Last Name|txt:txt|0x00",
"entity:suffix:entity|suffix|Suffix|txt:txt|0x00",
"entity:dob:entity|dob|Date of Birth|txt:txt|0x04",
"entity:status:entity|staus|Entity Status|txt:txt|0x00",
"entity:gcoid=card:xblk:002055:gco|cardpickup|Card Pickup|txt:txt|0x00",
"entity:bioid=portrait:jpgpic:biometric|portrait|Portrait|b64:bin|0x00",
"token:acid:credential|cuid|Token CUID|txt:txt|0x00",
"token:status:credential|tokenstatus|Token Status|txt:txt|0x00",
"token:gcoid=A000000308.5fc102:xblk:000030:gco|A000000308|5fc102|b64:bin|0x00",
);

// source spec | xmltag | conversion options | flags
// Source spec applies to the base DN given. These can be one of:
// attribute:objectclass for an attribute from the object.
// rdn:attribute:objectclass for an attribute from a subordinate object
// rdn:attribute:gcotag:objectclass for a GCO xblk decoded attribute
// This set of data will be returned for every process object in the workflow (except 'token')
// procid does not have to be included
$_process_elements = array (
"status:process|status|txt:txt|0x00",
"rem:process|comment|txt:txt|0x00",
"startdate:process|startdate|txt:txt|0x04",
"startby:process|startby|txt:txt|0x00",
"enddate:process|enddate|txt:txt|0x04",
"endby:process|endby|txt:txt|0x00",
"duedate:process|duedate|txt:txt|0x04",
"adjuddate:process|adjuddate|txt:txt|0x04",
"adjudby:process|adjudby|txt:txt|0x00",
"objectlog:process|objectlog|txt:txt|0x00",
"objnote:process|objnote|txt:txt|0x00",
);

// This set of data will be returned for the 'token' element only in the workflow (procid does not have to be included)
$_process_token_elements = array (
"status:process|status|txt:txt|0x00",
"rem:process|comment|txt:txt|0x00",
"objectlog:process|objectlog|txt:txt|0x00",
"gcoid=token:xblk:0020c6:gco|cardtop|txt:txt|0x00",
"gcoid=token:xblk:002055:gco|cardpickup|txt:txt|0x00",
"gcoid=token:xblk:000054:gco|cardtype|txt:txt|0x00",
"gcoid=token:upin:gco|pin|txt:txt|0x00",
"gcoid=token:xblk:000053:gco|crdexpdate|txt:txt|0x04",
"gcoid=token:xblk:002081:gco|lawenfrcment|txt:txt|0x00",
"gcoid=token:xblk:0020c5:gco|emergencyresp|txt:txt|0x00",
"gcoid=token:xblk:002082:gco|propertypass|txt:txt|0x00",
"gcoid=token:xblk:002083:gco|childcare|txt:txt|0x00",
"gcoid=token:xblk:002073:gco|agencycode|txt:txt|0x00",
"gcoid=token:xblk:002074:gco|systemcode|txt:txt|0x00",
"gcoid=token:xblk:002075:gco|crednumber|txt:txt|0x00",
"gcoid=token:xblk:002076:gco|csseries|txt:txt|0x00",
"gcoid=token:xblk:002077:gco|isscode|txt:txt|0x00",
"gcoid=token:xblk:00207c:gco|personid|txt:txt|0x00",
"gcoid=token:xblk:00207d:gco|orgcat|txt:txt|0x00",
"gcoid=token:xblk:00207e:gco|orgid|txt:txt|0x00",
"gcoid=token:xblk:00207f:gco|poacat|txt:txt|0x00",
"gcoid=token:xblk:000034:gco|cuid|txt:txt|0x00",
);

$_workflow_constant_elements = array (
"sponsor" => "Sponsor",
"photocap" => "Photo Capture",
"fpcap" => "Fingerprint Capture",
"doccap" => "Document Capture",
"print" => "Print",
"encode" => "Encode",
"ship" => "Delivery",
"activate" => "Activation",
"authorize" => "Authorize",
"ichk" => "Impersonation Check", 
"token" => "Token",
"fbi" => "FBI Check",
"bgi" => "Background Investigation",
);

$_constant_elements = array (
"EMsetup_portrait_width" => "420",
"EMsetup_portrait_height" => "560",
"EMsetup_portrait_headgap" => "20",
);

?>