<?PHP
// $Id: config-validate.php 105 2008-11-18 01:44:38Z gswan $
// Service configuration file
//    xsvc-readdata.xas?id=useracid

require_once("../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "validateresult";
$_service_version = "1.3";

// add cartypes that dont need print
$hide_ounit = array(
	"dac" => array("yubikey"),
);

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
"entity:ediident:entity|edipi|EDIPI|txt:txt|0x00",
"entity:mi:entity|mi|Middle Name|txt:txt|0x00",
"entity:lastname:entity|lastname|Last Name|txt:txt|0x00",
"entity:suffix:entity|suffix|Suffix|txt:txt|0x00",
"entity:dob:entity|dob|Date of Birth|txt:txt|0x04",
"entity:status:entity|entity_status|Entity Status|txt:txt|0x00",
"entity:bioid=portrait:jpgpic:biometric|portrait|Portrait|b64:bin|0x00",
"entity:bioid=PIV_Primary:xblk:biometric|LS_R_Index_ANSI378|Live Scan Right Index ANSI378 Template|b64:bin|0x00",
"entity:bioid=PIV_Primary:xblk:biometric|LS_L_Index_ANSI378|Live Scan Left Index ANSI378 Template|b64:bin|0x00",
"entity:bioid=ANSI378_Template_R_Index_CS,bioid=R_Index_CS:xblk:biometric|CS_R_Index_ANSI378|Card Scan Right Index ANSI378 Template|b64:bin|0x00",
"entity:bioid=ANSI378_Template_L_Index_CS,bioid=L_Index_CS:xblk:biometric|CS_L_Index_ANSI378|Card Scan Left Index ANSI378 Template|b64:bin|0x00",
"entity:bioid=PIV_Primary:rem:biometric|PIV_Primary_Finger_Number|PIV Primary Template Finger Number|txt:txt|0x00",
"entity:bioid=PIV_Secondary:rem:biometric|PIV_Secondary_Finger_Number|PIV Secondary Template Finger Number|txt:txt|0x00",
//"entity:bioid=portrait:jpgpic:biometric|portrait|Portrait|b64:bin|0x00",
"entity:nationality:entity|nationality|Nationality|txt:txt|0x00",
"entity:gcoid=personal:xblk:0020A7:gco|dual|Dual Nationality|txt:txt|0x00",
"entity:gndr:entity|gender|Gender|txt:txt|0x00",
"entity:ssn:entity|ssn|Social Security Number|txt:txt|0x200",
"entity:upi:entity|fsn|Employee Unique ID Number|txt:txt|0x00",
"entity:addrid=home:street:address|homestreet|Home Street|txt:txt|0x00",
"entity:gcoid=general:xblk:002351:gco|homeapartment|Apartment Number|txt:txt|0x00",
"entity:addrid=home:city:address|homecity|Home City|txt:txt|0x00",
"entity:addrid=home:st:address|homestate|Home State|txt:txt|0x00",
"entity:addrid=home:postcode:address|homezip|Home Zip Code|txt:txt|0x00",
"entity:emplid=usaccess:employer:employment|emplname|Employer Name|txt:txt|0x00",
"entity:emplid=usaccess:phone:employment|emplphone|Employer Phone|txt:txt|0x00",
"entity:emplid=usaccess:phonefax:employment|emplfax|Employer Fax|txt:txt|0x00",
"entity:emplid=usaccess:street:employment|emplstreet|Employer Street Address|txt:txt|0x00",
"entity:emplid=usaccess:suite:employment|emplsuite|Employer Suite|txt:txt|0x00",
"entity:emplid=usaccess:city:employment|emplcity|Employer City|txt:txt|0x00",
"entity:emplid=usaccess:st:employment|emplstate|Employer State|txt:txt|0x00",
"entity:emplid=usaccess:postcode:employment|emplzip|Employer Zip Code|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:000055:gco|doscardother|Type of DOS Card Other|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:0022a0:gco|escortauth|Escort Authority|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:0022a1:gco|accesshours|Hours of Access|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:000056:gco|accesstype|Type of Access|txt:txt|0x00",
"entity:gcoid=contractor:xblk:0022a2:gco|cnumber|Contract Number|txt:txt|0x00",
"entity:gcoid=contractor:xblk:0022a5:gco|ccontract|Classified Contract|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:000001:gco|sfirstname|State Requester First Name|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:002002:gco|smi|State Requester MI|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:000003:gco|slastname|State Requester Last Name|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:000004:gco|ssuffix|State Requester Suffix|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:000005:gco|sphone|Requester Phone Number|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:00229c:gco|stype|Requester Type|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:000007:gco|sother|Requester Type Other|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:000008:gco|soffsym|Requester Office Symbol|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:002290:gco|sponsorid|Requester ID Card Number|txt:txt|0x00",
"entity:gcoid=sponsor:xblk:000006:gco|ssigdate|Requester Date Signed|txt:txt|0x04",
"entity:gcoid=clearance:xblk:002401:gco|pinissued|Pin Issued|txt:txt|0x00",
"entity:gcoid=clearance:xblk:002402:gco|cardnumreturn|Card Number Returned|txt:txt|0x00",
"entity:gcoid=clearance:xblk:002400:gco|isstype|Issuance Type|txt:txt|0x00",
"entity:gcoid=clearance:xblk:00230a:gco|previousclearancelevel|Previous Clearance Level|txt:txt|0x00",
"entity:gcoid=clearance:xblk:002301:gco|currentclearancelevel|Current Clearance Level|txt:txt|0x00",
"entity:gcoid=clearance:xblk:002302:gco|dategranted|Date Granted|txt:txt|0x04",
"entity:gcoid=clearance:xblk:00230b:gco|grantingagency|Granting Agency|txt:txt|0x00",
"entity:gcoid=clearance:xblk:00230c:gco|processor|Processor|txt:txt|0x00",
"entity:gcoid=clearance:xblk:00230d:gco|issuer|Issuer Name|txt:txt|0x00",
"entity:gcoid=clearance:xblk:00230e:gco|supervisor|Supervisor Name|read|0x00",
"entity:gcoid=clearance:xblk:00230f:gco|dateissued|Date Issued|txt:txt|0x04",
"entity:gcoid=clearance:xblk:003101:gco|sciclearancestatus|SCI Clearance Status|txt:txt|0x00",
"entity:gcoid=clearance:xblk:003102:gco|dateauthorized|SCI Clearance Date Authorized|txt:txt|0x04",
"entity:gcoid=clearance:xblk:003103:gco|authorizedby|SCI Authorized By|txt:txt|0x00",
"entity:gcoid=clearance:xblk:003104:gco|accessstatus|SCI Access Status|txt:txt|0x00",
"entity:gcoid=clearance:xblk:003105:gco|dateauthorized2|INR Access Date Authorized|txt:txt|0x04",
"entity:gcoid=clearance:xblk:003106:gco|authorizedby2|INR Access Authorized By|txt:txt|0x00",
"entity:gcoid=clearance:xblk:003107:gco|bureauexecutiveoffice|Bureau Exec Off Authorized Req|txt:txt|0x00",
"entity:gcoid=clearance:xblk:003108:gco|bureaudate|Bureau Exec Data|txt:txt|0x04",
"entity:gcoid=clearance:xblk:003109:gco|bureauauthorizedby|Bureau Authorized|txt:txt|0x00",
"entity:gcoid=clearance:xblk:00310a:gco|comments|Comments|txt:txt|0x00",
"entity:gcoid=personal:xblk:000201:gco|ecfirstname|Emergency Contact First Name|txt:txt|0x00",
"entity:gcoid=personal:xblk:000202:gco|eclastname|Emergency Contact Last Name|txt:txt|0x00",
"entity:gcoid=personal:xblk:000203:gco|ecphone|Emergency Contact Phone|txt:txt|0x00",
"entity:addrid=home:cntry:address|homecountry|Home Country|txt:txt|0x00",
"entity:gcoid=general:xblk:002502:gco|affiliation|Applicant Affiliation|txt:txt|0x00",
"entity:gcoid=clearance:xblk:002306:gco|briefing|Received Security Briefing|txt:txt|0x00",
"entity:gcoid=general:xblk:002300:gco|applicantstatus|Applicant Status|txt:txt|0x00",
);


// The tokengroups extract token data from the database and maintain the data set elements grouped together
// For example, if a user has many tokens:
// <token>
//		<cuid>
//			<caption>captionstring</caption>
//			<content>cuidstring 1</content>
//		</cuid>
//		<issdate>
//			<caption>captionstring</caption>
//			<content>issdatestring 1</content>
//		<issdate>
//		<status>
//			<caption>captionstring</caption>
//			<content>statusstring 1</content>
//		</status>
// </token>
// <token>
//		<cuid>
//			<caption>captionstring</caption>
//			<content>cuidstring 2</content>
//		</cuid>
//		<issdate>
//			<caption>captionstring</caption>
//			<content>issdatestring 2</content>
//		<issdate>
//		<status>
//			<caption>captionstring</caption>
//			<content>statusstring 2</content>
//		</status>
// </token>
//
// The envelope tag for each group is taken from the group array name.

$_db_tokengroups = array(
"token" => array(
	"token:acid:credential|cuid|Token CUID|txt:txt|0x00",
	"token:issdate:credential|issdate|Issue Date|txt:txt|0x04",
	"token:status:credential|status|Token Status|txt:txt|0x00",
	),
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
"verifier" => "Document Verifier",
"doccap" => "Document Capture",
"print" => "Print",
"encode" => "Encode",
"ship" => "Delivery",
"activate" => "Activation",
"authorize" => "Authorize",
"ichk" => "Impersonation Check", 
"token" => "Token",
);

$_workflow_constant_statuses = array(
"piv" => "Initiated|Pending|Alert|Approved|Rejected",
"pivi" => "Initiated|Pending|Alert|Approved|Rejected",
"pivo" => "Initiated|Pending|Alert|Approved|Rejected",
"fac" => "Initiated|Pending|Alert|Approved|Rejected",
"fac2" => "Initiated|Pending|Alert|Approved|Rejected",
"dac" => "Initiated|Pending|Alert|Approved|Rejected",
"npe" => "Initiated|Pending|Alert|Approved|Rejected",
"demo" => "Initiated|Pending|Alert|Approved|Rejected",
"enrollment" => "Initiated|Pending|Alert|Approved|Rejected",
"visitor" => "Initiated|Pending|Alert|Approved|Rejected",
);


$_workflow_constant_lists = array(
"piv" => array(
array("list-cardtopology.php", "listcardtopology", "cardtop"),
array("list-cardpickup.php", "listcardpickup", "cardpickup"),
array("list-cardtype.php", "listcardtype", "cardtype"),
),
"pivi" => array(
array("list-pivicardtopology.php", "listpivicardtopology", "cardtop"),
array("list-pivicardpickup.php", "listpivicardpickup", "cardpickup"),
array("list-pivicardtype.php", "listpivicardtype", "cardtype"),
),
"fac" => array(
array("list-cardtopology.php", "listcardtopology", "cardtop"),
array("list-cardpickup.php", "listcardpickup", "cardpickup"),
array("list-cardtype.php", "listcardtype", "cardtype"),
),
"fac2" => array(
array("list-cardtopology.php", "listcardtopology", "cardtop"),
array("list-cardpickup.php", "listcardpickup", "cardpickup"),
array("list-cardtype.php", "listcardtype", "cardtype"),
),
	
);

$_constant_lists = array(
array("list-docs.php", "listdocs", "docs"),
array("list-printerror.php", "listprinterror", "printerror"),
);

$_constant_elements = array (
"EMsetup_portrait_width" => "420",
"EMsetup_portrait_height" => "560",
"EMsetup_portrait_headgap" => "20",
);

?>
