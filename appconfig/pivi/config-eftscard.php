<?PHP
// $Id: config-eftscard.php 105 2008-11-18 01:44:38Z gswan $
// Service configuration file
//    xsvc-eftscard.xas?id=useracid

require_once("../../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "eftsresult";
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

$_db_elements = array (
"entity:ssn:entity|T2_SOC.A.9|SSN|txt:txt|0x200",
"entity:firstname:entity|T2_NAM.A.30.1|First Name|txt:txt|0x00",
"entity:mi:entity|T2_NAM.A.30.2|Middle Name|txt:txt|0x00",
"entity:lastname:entity|T2_NAM.A.30.3|Last Name|txt:txt|0x00",
"entity:dob:entity|T2_DOB.D.8|Date of Birth|txt:txt|0x04",
"entity:hght:entity|T2_HGT.A.3|Height|txt:txt|0x00",
"entity:weight:entity|T2_WGT.A.3|Weight|txt:txt|0x00",
"entity:gcoid=personal:xblk:000003:gco|T2_POB.A.2|Place of Birth|txt:txt|0x00",
"entity:nationality:entity|T2_CTZ.A.2|Citizenship|txt:txt|0x00",
"entity:hrclr:entity|T2_HAI.A.3|Hair Color|txt:txt|0x00",
"entity:gndr:entity|T2_SEX.A.1|Gender|txt:txt|0x00",
"entity:eyeclr:entity|T2_EYE.A.3|Eye Color|txt:txt|0x00",
"entity:race:entity|T2_RAC.A.1|Race|txt:txt|0x00",
"entity:gcoid=general:xblk:002508:gco|SON|Person SON|txt:txt|0x00",
"entity:gcoid=general:xblk:00250A:gco|SOI|Person SOI|txt:txt|0x00",
"entity:bioid=R_Index:modifyTimestamp:biometric|T2_DPR.D.8|Date of Live Scan Capture|txt:txt|0x04",
"entity:bioid=R_Thumb_CS:wsqdata:biometric|AW_RIGHT_THUMB|Card Scan Right Thumb WSQ|b64:bin|0x00",
"entity:bioid=R_Index_CS:wsqdata:biometric|AW_RIGHT_INDEX_FINGER|Card Scan Right Index WSQ|b64:bin|0x00",
"entity:bioid=R_Middle_CS:wsqdata:biometric|AW_RIGHT_MIDDLE_FINGER|Card Scan Right Middle WSQ|b64:bin|0x00",
"entity:bioid=R_Ring_CS:wsqdata:biometric|AW_RIGHT_RING_FINGER|Live Scan Right Ring WSQ|b64:bin|0x00",
"entity:bioid=R_Little_CS:wsqdata:biometric|AW_RIGHT_LITTLE_FINGER|Card Scan Right Little WSQ|b64:bin|0x00",
"entity:bioid=RT_Plain_CS:wsqdata:biometric|AW_PLAIN_RIGHT_THUMB|Card Scan Right Thumb Plain WSQ|b64:bin|0x00",
"entity:bioid=R_Slap_CS:wsqdata:biometric|AW_PLAIN_RIGHT_FOUR_FINGERS|Card Scan Right Slap WSQ|b64:bin|0x00",
"entity:bioid=L_Thumb_CS:wsqdata:biometric|AW_LEFT_THUMB|Card Scan Left Thumb WSQ|b64:bin|0x00",
"entity:bioid=L_Index_CS:wsqdata:biometric|AW_LEFT_INDEX_FINGER|Card Scan Left Index WSQ|b64:bin|0x00",
"entity:bioid=L_Middle_CS:wsqdata:biometric|AW_LEFT_MIDDLE_FINGER|Card Scan Left Middle WSQ|b64:bin|0x00",
"entity:bioid=L_Ring_CS:wsqdata:biometric|AW_LEFT_RING_FINGER|Card Scan Left Ring WSQ|b64:bin|0x00",
"entity:bioid=L_Little_CS:wsqdata:biometric|AW_LEFT_LITTLE_FINGER|Card Scan Left Little WSQ|b64:bin|0x00",
"entity:bioid=LT_Plain_CS:wsqdata:biometric|AW_PLAIN_LEFT_THUMB|Card Scan Left Thumb Plain WSQ|b64:bin|0x00",
"entity:bioid=L_Slap_CS:wsqdata:biometric|AW_PLAIN_LEFT_FOUR_FINGERS|Card Scan Left Slap WSQ|b64:bin|0x00",
"entity:bioid=R_Thumb_CS:rem:biometric|CS_R_Thumb_AMP|Card Scan Right Thumb AMP|txt:txt|0x00",
"entity:bioid=R_Index_CS:rem:biometric|CS_R_Index_AMP|Card Scan Right Index AMP|txt:txt|0x00",
"entity:bioid=R_Middle_CS:rem:biometric|CS_R_Middle_AMP|Card Scan Right Middle AMP|txt:txt|0x00",
"entity:bioid=R_Ring_CS:rem:biometric|CS_R_Ring_AMP|Card Scan Right Ring AMP|txt:txt|0x00",
"entity:bioid=R_Little_CS:rem:biometric|CS_R_Little_AMP|Card Scan Right Little AMP|txt:txt|0x00",
"entity:bioid=L_Thumb_CS:rem:biometric|CS_L_Thumb_AMP|Card Scan Left Thumb AMP|txt:txt|0x00",
"entity:bioid=L_Index_CS:rem:biometric|CS_L_Index_AMP|Card Scan Left Index AMP|txt:txt|0x00",
"entity:bioid=L_Middle_CS:rem:biometric|CS_L_Middle_AMP|Card Scan Left Middle AMP|txt:txt|0x00",
"entity:bioid=L_Ring_CS:rem:biometric|CS_L_Ring_AMP|Card Scan Left Ring AMP|txt:txt|0x00",
"entity:bioid=L_Little_CS:rem:biometric|CS_L_Little_AMP|Card Scan Left Little AMP|txt:txt|0x00",
);

$_constant_elements = array (
"T1_VER.A.4" => "0200",
"T1_PRY.A.1" => "2",
"T1_DAI.A.9" => "WVIAFIS0Z",
"T1_ORI.A.9" => "USOPM000Z",
"T1_NSR.A.5" => "19.69",
"T1_NTR.A.5" => "19.69",
"T2_IMA.A.100" => "CrossMatch Technologies,LSCAN500C,E2006",
"T2_RAP.A.1" => "Y",
);

?>