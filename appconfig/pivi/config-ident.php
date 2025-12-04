<?PHP
// $Id: config-ident.php 105 2008-11-18 01:44:38Z gswan $
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
"PersonFirstName" 	=> "entity:firstname:entity|j:PersonGivenName|First Name|txt:txt|0x00",
"PersonMiddleName"	=> "entity:mi:entity|j:PersonMiddleName|Middle Name|txt:txt|0x00",
"PersonLastname" 	=> "entity:lastname:entity|j:PersonSurName|Last Name|txt:txt|0x00",
"PersonDOB" 		=> "entity:dob:entity|j:PersonBirthDate|Date of Birth|txt:txt|0x00",
"PersonPOB"			=> "entity:gcoid=personal:xblk:000003:gco|j:PersonBirthPlaceCode|Place of Birth|txt:txt|0x00",
"NationalityCode"	=> "entity:nationality:entity|j:PersonNationalityCode.iso3166Alpha3|Citizenship|txt:txt|0x00",
"PersonSex" 		=> "entity:gndr:entity|j:PersonSexCode|Gender|txt:txt|0x00",
"Portrait"	 		=> "entity:bioid=portrait:jpgpic:biometric|J:Portrait|Portrait|b64:bin|0x00",

"faceCaptureDate"	=> "entity:bioid=portrait:modifyTimestamp:biometric|j:BinaryCaptureDate|Portrait Capture Date|txt:txt|0x00",
"faceCaptureTime"	=> "",

"R_Thumb" 			=> "entity:bioid=R_Thumb:wsqdata:biometric|AW_RIGHT_THUMB|Live Scan Right Thumb WSQ|b64:bin|0x00",
"R_Index" 			=> "entity:bioid=R_Index:wsqdata:biometric|AW_RIGHT_INDEX_FINGER|Live Scan Right Index WSQ|b64:bin|0x00",
"R_Middle" 			=> "entity:bioid=R_Middle:wsqdata:biometric|AW_RIGHT_MIDDLE_FINGER|Live Scan Right Middle WSQ|b64:bin|0x00",
"R_Ring" 			=> "entity:bioid=R_Ring:wsqdata:biometric|AW_RIGHT_RING_FINGER|Live Scan Right Ring WSQ|b64:bin|0x00",
"R_Little" 			=> "entity:bioid=R_Little:wsqdata:biometric|AW_RIGHT_LITTLE_FINGER|Live Scan Right Little WSQ|b64:bin|0x00",
"L_Thumb"			=> "entity:bioid=L_Thumb:wsqdata:biometric|AW_LEFT_THUMB|Live Scan Left Thumb WSQ|b64:bin|0x00",
"L_Index" 			=> "entity:bioid=L_Index:wsqdata:biometric|AW_LEFT_INDEX_FINGER|Live Scan Left Index WSQ|b64:bin|0x00",
"L_Middle" 			=> "entity:bioid=L_Middle:wsqdata:biometric|AW_LEFT_MIDDLE_FINGER|Live Scan Left Middle WSQ|b64:bin|0x00",
"L_Ring" 			=> "entity:bioid=L_Ring:wsqdata:biometric|AW_LEFT_RING_FINGER|Live Scan Left Ring WSQ|b64:bin|0x00",
"L_Little" 			=> "entity:bioid=L_Little:wsqdata:biometric|AW_LEFT_LITTLE_FINGER|Live Scan Left Little WSQ|b64:bin|0x00",

"RT_Plain_LS" 		=> "entity:bioid=RT_Plain_LS:wsqdata:biometric|AW_PLAIN_RIGHT_THUMB|Live Scan Right Thumb Plain WSQ|b64:bin|0x00",
"LT_Plain_LS" 		=> "entity:bioid=LT_Plain_LS:wsqdata:biometric|AW_PLAIN_LEFT_THUMB|Live Scan Left Thumb Plain WSQ|b64:bin|0x00",
"R_Slap_LS" 		=> "entity:bioid=R_Slap_LS:wsqdata:biometric|AW_PLAIN_RIGHT_FOUR_FINGERS|Live Scan Right Slap WSQ|b64:bin|0x00",
"L_Slap_LS" 		=> "entity:bioid=L_Slap_LS:wsqdata:biometric|AW_PLAIN_LEFT_FOUR_FINGERS|Live Scan Left Slap WSQ|b64:bin|0x00",

"R_Thumb_time"		=> "entity:bioid=R_Thumb:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"R_Index_time" 		=> "entity:bioid=R_Index:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"R_Middle_time"		=> "entity:bioid=R_Middle:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"R_Ring_time"		=> "entity:bioid=R_Ring:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"R_Little_time"		=> "entity:bioid=R_Little:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",

"L_Thumb_time"		=> "entity:bioid=L_Thumb:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"L_Index_time"		=> "entity:bioid=L_Index:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"L_Middle_time"		=> "entity:bioid=L_Middle:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"L_Ring_time"		=> "entity:bioid=L_Ring:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"L_Little_time"		=> "entity:bioid=L_Little:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",

"RT_Plain_LS_time"	=> "entity:bioid=RT_Plain_LS:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"LT_Plain_LS_time"	=> "entity:bioid=LT_Plain_LS:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"R_Slap_LS_time"	=> "entity:bioid=R_Slap_LS:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",
"L_Slap_LS_time"	=> "entity:bioid=L_Slap_LS:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x00",

"R_Thumb_nfiq" 		=> "entity:bioid=R_Thumb:nfiq:biometric|AW_RIGHT_THUMB_NFIQ|Live Scan Right Thumb NFIQ|txt:txt|0x00",
"R_Index_nfiq" 		=> "entity:bioid=R_Index:nfiq:biometric|AW_RIGHT_INDEX_FINGER_NFIQ|Live Scan Right Index NFIQ|txt:txt|0x00",
"R_Middle_nfiq" 	=> "entity:bioid=R_Middle:nfiq:biometric|AW_RIGHT_MIDDLE_FINGER_NFIQ|Live Scan Right Middle NFIQ|txt:txt|0x00",
"R_Ring_nfiq" 		=> "entity:bioid=R_Ring:nfiq:biometric|AW_RIGHT_RING_FINGER_NFIQ|Live Scan Right Ring NFIQ|txt:txt|0x00",
"R_Little_nfiq" 	=> "entity:bioid=R_Little:nfiq:biometric|AW_RIGHT_LITTLE_FINGER_NFIQ|Live Scan Right Little NFIQ|txt:txt|0x00",
"L_Thumb_nfiq"		=> "entity:bioid=L_Thumb:nfiq:biometric|AW_LEFT_THUMB_NFIQ|Live Scan Left Thumb NFIQ|txt:txt|0x00",
"L_Index_nfiq" 		=> "entity:bioid=L_Index:nfiq:biometric|AW_LEFT_INDEX_FINGER_NFIQ|Live Scan Left Index NFIQ|txt:txt|0x00",
"L_Middle_nfiq" 	=> "entity:bioid=L_Middle:nfiq:biometric|AW_LEFT_MIDDLE_FINGER_NFIQ|Live Scan Left Middle NFIQ|txt:txt|0x00",
"L_Ring_nfiq" 		=> "entity:bioid=L_Ring:nfiq:biometric|AW_LEFT_RING_FINGER|_NFIQLive Scan Left Ring NFIQ|txt:txt|0x00",
"L_Little_nfiq" 	=> "entity:bioid=L_Little:nfiq:biometric|AW_LEFT_LITTLE_FINGER_NFIQ|Live Scan Left Little NFIQ|txt:txt|0x00",

"RT_Plain_LS_nfiq" 	=> "entity:bioid=RT_Plain_LS:nfiq:biometric|AW_PLAIN_RIGHT_THUMB_NFIQ|Live Scan Right Thumb Plain NFIQ|b64:bin|0x00",
"LT_Plain_LS_nfiq" 	=> "entity:bioid=LT_Plain_LS:nfiq:biometric|AW_PLAIN_LEFT_THUMB|Live Scan Left Thumb Plain WSQ|b64:bin|0x00",
"R_Slap_LS_nfiq" 	=> "entity:bioid=R_Slap_LS:nfiq:biometric|AW_PLAIN_RIGHT_FOUR_FINGERS_NFIQ|Live Scan Right Slap NFIQ|b64:bin|0x00",
"L_Slap_LS_nfiq" 	=> "entity:bioid=L_Slap_LS:nfiq:biometric|AW_PLAIN_LEFT_FOUR_FINGERS|Live Scan Left Slap WSQ|b64:bin|0x00",

"R_Thumb_rem" 		=> "entity:bioid=R_Thumb:rem:biometric|LS_R_Thumb_AMP|Live Scan Right Thumb AMP|txt:txt|0x00",
"R_Index_rem" 		=> "entity:bioid=R_Index:rem:biometric|LS_R_Index_AMP|Live Scan Right Index AMP|txt:txt|0x00",
"R_Middle_rem" 		=> "entity:bioid=R_Middle:rem:biometric|LS_R_Middle_AMP|Live Scan Right Middle AMP|txt:txt|0x00",
"R_Ring_rem" 		=> "entity:bioid=R_Ring:rem:biometric|LS_R_Ring_AMP|Live Scan Right Ring AMP|txt:txt|0x00",
"R_Littl_rem" 		=> "entity:bioid=R_Little:rem:biometric|LS_R_Little_AMP|Live Scan Right Little AMP|txt:txt|0x00",
"L_Thumb_rem" 		=> "entity:bioid=L_Thumb:rem:biometric|LS_L_Thumb_AMP|Live Scan Left Thumb AMP|txt:txt|0x00",
"L_Index_rem" 		=> "entity:bioid=L_Index:rem:biometric|LS_L_Index_AMP|Live Scan Left Index AMP|txt:txt|0x00",
"L_Middle_rem" 		=> "entity:bioid=L_Middle:rem:biometric|LS_L_Middle_AMP|Live Scan Left Middle AMP|txt:txt|0x00",
"L_Ring_rem" 		=> "entity:bioid=L_Ring:rem:biometric|LS_L_Ring_AMP|Live Scan Left Ring AMP|txt:txt|0x00",
"L_Little_rem" 		=> "entity:bioid=L_Little:rem:biometric|LS_L_Little_AMP|Live Scan Left Little AMP|txt:txt|0x00",
);

$_constant_elements = array 
(
	"fpHeightValue" 	=> "750",
	"fpWidthValue" 		=> "800",
	"fpSlapHeight" 		=> "1500",
	"fpSlapWidth" 		=> "1600",
	"faceCaptureTime"	=> "00:00:00",
	"fpCaptureTime"		=> "00:00:00",

	"sendTo" 			=> "//usvisit.dhs.gov/soap/services",
	"sentFrom" 			=> "hostURI://from_TSA@dhs.gov",
	"replyToURL" 		=> "//www.w3.org/2005/08/addressing/anonymous",
	"Action" 			=> "http://visit.dhs.gov/ident/ixm/Identify",
	"ActivityReason" 	=> "Identify",

	"RetainCode" 		=> "Enroll",
	"FormatCode" 		=> "Summary",
	"BiometricsCode" 	=> "None",

	"OrgName"			=> "DHS",
	"OrgUnitName"		=> "HSPD12",
	"IDTypeCode" 		=> "SystemId",
	"ActivityType" 		=> "Other",
	"ActivityReason" 	=> "Identify",
	"ActivityUserLogin" => "GX5001",
	"ActivitySiteCode" 	=> "UNK",
	"facePoseCode" 		=> "Frontal",
);

$_finger_codes = array 
(
	"R_Thumb" 		=> "1",
	"R_Index"		=> "2",
	"R_Middle"		=> "3",
	"R_Ring" 		=> "4",
	"R_Little" 		=> "5",
	"L_Thumb" 		=> "6",
	"L_Index" 		=> "7",
	"L_Middle" 		=> "8",
	"L_Ring" 		=> "9",
	"L_Little" 		=> "10",
	"RT_Plain_LS" 	=> "11",
	"LT_Plain_LS" 	=> "12",
	"R_Slap_LS" 	=> "13",
	"L_Slap_LS" 	=> "14",
);

$_fp_elements_list = array
(
	"R_Thumb", 		
	"R_Index", 		
	"R_Middle" ,		
	"R_Ring", 		
	"R_Little", 		
	"L_Thumb",		
	"L_Index" ,		
	"L_Middle", 		
	"L_Ring",		
	"L_Little" ,		
	"RT_Plain_LS", 	
	"LT_Plain_LS" ,	
	"R_Slap_LS", 	
	"L_Slap_LS" ,	
);

$_email_headers = array 
(
	"To" => "gduplan@161.214.190.12",  
	//"To" => "ident@161.214.190.12",  
	"From" => "eft@xtec.com",
	"Reply-To" => "eft test account <eft@xtec.com>",
	"Return-Path" => "eft test account <eft@xtec.com>",
	"Subject" => "SI submission to USVISIT_IDENT ",
)

?>