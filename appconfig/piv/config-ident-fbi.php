<?PHP
// $Id: config-ident.php 253 2008-05-08 21:59:56Z hjackson $
// Service configuration file
//    xsvc-ident.xas?id=useracid

require_once("../../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "identsend";
$_service_version = "1.1";

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

$_db_elements = array 
(
"AuthentxCID" 		=> "credential:cid:credential|j:ID|cid|txt:txt|0x00",
//"PersonID" 		=> "entity:ssn:entity|j:ID|SSN|txt:txt|0x200",
);


$_constant_elements = array 
(
	'IDTypeCode' 		=> 'IDMSID',

	'sendTo' 			=> '//usvisit.dhs.gov/soap/services',
	'sentFrom' 			=> 'hostURI://from_TSA@dhs.gov',
	'replyToURL' 		=> '//www.w3.org/2005/08/addressing/anonymous',
	'Action' 			=> 'http://visit.dhs.gov/ident/ixm/Identify',

	'ORIID'				=> 'USDHS001A',
	'ORIIDTypeText'		=> 'IDMS ORI',
	
	'ActivityTypeCode' 	=> 'IDMSID',
	'ActivityTypeTxt' 	=> 'Application',
	'ActivityReason' 	=> 'PIV Card Issuance',

	'OrgName'			=> 'DHS',
	'OrgUnitName'		=> 'HQ',
	'OrgSubUnitName'	=> 'IDMS',

	'ActivityUserLogin' => 'GX5001',
	'ActivitySiteCode' 	=> 'UNK',
);


$_email_headers = array 
(
//	'To' => 'hjackson@xtec.com',  
	'To' => 'dmccarthy@xtec.com',  
//	'To' => 'gduplan@161.214.190.12',  
//	'To' => 'ident@161.214.190.12',  
	'From' => 'eft@xtec.com',
	'Reply-To' => 'eft test account <eft@xtec.com>',
	'Return-Path' => 'eft test account <eft@xtec.com>',
	'Subject' => 'DHS submission to USVISIT_IDENT ',
)


?>