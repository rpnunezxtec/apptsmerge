<?PHP
// $Id: config-print.php 105 2008-11-18 01:44:38Z gswan $
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

require_once("../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_ent_basedn = $ldap_entities.",ounit=entities,".$ldap_treetop;
//$_ent_basedn = "entities=".$ldap_entities.",ounit=entities,".$ldap_treetop;
$_service_envelope = "checkin";
$_service_version = "1.0";

$x4 = "00";

$_db_elements = array(
"entity:gcoid=auxiliary:xblk:000001:gco|alertinfo|ALERT!|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:000002:gco|leo|Law Enforcement Officer|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:000003:gco|weapons|Weapons Bearer|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:000004:gco|clearance|Security Clearance|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:000005:gco|fero|Emergency Responder|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:000006:gco|feronrfesf|FERO National Response Framework Emergency Support Function (NRF-ESF)|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:000007:gco|feronipp|FERO National Infrastruction Protection Plan (NIPP)|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:000008:gco|ferocog|Continuity of Government (COG)|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:000009:gco|ferocoop|Continuity of Operations (COOP)|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:00000A:gco|feroopron|Operation Rendezvous (OPRON)|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:00000B:gco|vaccination|COVID Vaccinated|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:00000C:gco|vaccinationdate|Vaccination Date|txt:txt|0x04",
"entity:gcoid=auxiliary:xblk:00000D:gco|covidtest|COVID Tested|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:00000E:gco|ncovidtestdate|Negative COVID Test Date|txt:txt|0x04",
"entity:gcoid=auxiliary:xblk:00000F:gco|citizen|US Citizen|txt:txt|0x00",
"entity:gcoid=auxiliary:xblk:000010:gco|regvoter|Registered Voter|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:990202:gco|yyyymmddexpirationdata|Token Expiration Date|txt:txt|0x00",
"token:gcoid=topoinfo:xblk:990105:gco|issuedate|Token Issue Date|txt:txt|0x00",
"entity:status:entity|entstatus|Entity Status|txt:txt|0x00",
"token:status:credential|tokenstatus|Token Status|txt:txt|0x00",
"entity:rem:entity|comment|Messages/Instructions|txt:txt|0x00",
);

$_db_write_elements = array(
"entity:firstname:entity|firstname|none|txt:txt|0x00",
"entity:mi:entity|middlename|none|txt:txt|0x00",
"entity:lastname:entity|lastname|none|txt:txt|0x00",
"entity:dob:entity|dob|none|txt:txt|0x04",
"entity:addrid=home:street:address|streetaddress|none|txt:txt|0x00",
"entity:addrid=home:city:address|city|none|txt:txt|0x00",
"entity:addrid=home:st:address|state|none|txt:txt|0x00",
"entity:addrid=home:postcode:address|zipcode|none|txt:txt|0x00",
"token:issdate:credential|issuedate|none|txt:txt|0x04",
"token:expdate:credential|expdate|none|txt:txt|0x04",
//"token:gcoid=identifiers:xblk:000003:gco|userid|none|txt:txt|0x00",
"token:gcoid=driverslic:xblk:000001:gco|blob|none|txt:txt|0x00",

"entity:gcoid=nosave:xblk:000001:gco|online|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|source|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|dlnumber|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|srcid|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|sid|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|dom|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|srcip|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|qstamp|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|x5|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|x1|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|x17|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|x18|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|x2|none|txt:txt|0x08",
"entity:gcoid=nosave:xblk:000001:gco|x16|none|txt:txt|0x08",

);



$_constant_elements = array (
"alertinfo" => "1",
"leo" => "0",
"weapons" => "0",
"clearance" => "2",
"fero" => "0",
"feronrfesf" => "0",
"feronipp" => "0",
"ferocog" => "0",
"ferocoop" => "0",
"feroopron" => "0",
//"entstatus" => "0",
"tokenstatus" => "0",
"comment" => "0",
"vaccination" => "0",
"vaccinationdate" => "0",
"covidtest" => "0",
"ncovidtestdate" => "0",
"citizen" => "0",
"regvoter" => "0",
);

$default_authentx_status = "active";
$default_entity_status = "active";
$default_token_status = "active";
$default_name = "Checkin enrollment service";



?>
