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
$_service_envelope = "docsresult";
$_service_version = "1.1";

$_db_elements = array(
"credential:acid:credential|acid|Acid|txt:txt|0x01",
//"entity:bioid=portrait:jpgpic:biometric|portrait|Portrait|b64:bin|0x00",
"entity:docid=passport:desc:xdocument|passport_device|US Passport Capture Device|txt:txt|0x00",
"entity:docid=passport:authval:xdocument|passport_id|US Passport ID|txt:txt|0x00",
"entity:docid=passport:jpgpic:xdocument|passport_visible|US Passport Visible Image|b64:bin|0x00",
"entity:docid=passport:issdate:xdocument|passport_issdate|US Passport Issue Date|txt:txt|0x04",
"entity:docid=passport:expdate:xdocument|passport_expdate|US Passport Expiration Date|txt:txt|0x04",
"entity:docid=passport:xblk:xdocument|passport_info|US Passport Information|txt:txt|0x00",
"entity:docid=cizenship:desc:xdocument|cizenship_device|Certificate of US Citizenship Capture Device|txt:txt|0x00",
"entity:docid=cizenship:authval:xdocument|cizenship_id|Certificate of US Citizenship ID|txt:txt|0x00",
"entity:docid=cizenship:jpgpic:xdocument|cizenship_visible|Certificate of US Citizenship Visible Image|b64:bin|0x00",
"entity:docid=cizenship:issdate:xdocument|cizenship_issdate|Certificate of US Citizenship Issue Date|txt:txt|0x04",
"entity:docid=cizenship:expdate:xdocument|cizenship_expdate|Certificate of US Citizenship Expiration Date|txt:txt|0x04",
"entity:docid=cizenship:xblk:xdocument|cizenship_info|Certificate of US Citizenship Information|txt:txt|0x00",
"entity:docid=naturalization:desc:xdocument|naturalization_device|Certificate of Naturalization Capture Device|txt:txt|0x00",
"entity:docid=naturalization:authval:xdocument|naturalization_id|Certificate of Naturalization ID|txt:txt|0x00",
"entity:docid=naturalization:jpgpic:xdocument|naturalization_visible|Certificate of Naturalization Visible Image|b64:bin|0x00",
"entity:docid=naturalization:issdate:xdocument|naturalization_issdate|Certificate of Naturalization Issue Date|txt:txt|0x04",
"entity:docid=naturalization:expdate:xdocument|naturalization_expdate|Certificate of Naturalization Expiration Date|txt:txt|0x04",
"entity:docid=naturalization:xblk:xdocument|naturalization_info|Certificate of Naturalization Information|txt:txt|0x00",
"entity:docid=foreignpassport:desc:xdocument|foreignpassport_device|Foreign Passport Capture Device|txt:txt|0x00",
"entity:docid=foreignpassport:authval:xdocument|foreignpassport_id|Foreign Passport ID|txt:txt|0x00",
"entity:docid=foreignpassport:jpgpic:xdocument|foreignpassport_visible|Foreign Passport Visible Image|b64:bin|0x00",
"entity:docid=foreignpassport:issdate:xdocument|foreignpassport_issdate|Foreign Passport Issue Date|txt:txt|0x04",
"entity:docid=foreignpassport:expdate:xdocument|foreignpassport_expdate|Foreign Passport Expiration Date|txt:txt|0x04",
"entity:docid=foreignpassport:xblk:xdocument|foreignpassport_info|Foreign Passport Information|txt:txt|0x00",
"entity:docid=resident:desc:xdocument|resident_device|Permanent Resident or Alien Registration Card Capture Device|txt:txt|0x00",
"entity:docid=resident:authval:xdocument|resident_id|Permanent Resident or Alien Registration Card ID|txt:txt|0x00",
"entity:docid=resident:jpgpic:xdocument|resident_visible|Permanent Resident or Alien Registration Card Visible Image|b64:bin|0x00",
"entity:docid=resident:issdate:xdocument|resident_issdate|Permanent Resident or Alien Registration Card Issue Date|txt:txt|0x04",
"entity:docid=resident:expdate:xdocument|resident_expdate|Permanent Resident or Alien Registration Card Expiration Date|txt:txt|0x04",
"entity:docid=resident:xblk:xdocument|resident_info|Permanent Resident or Alien Registration Card Information|txt:txt|0x00",
"entity:docid=tempresident:desc:xdocument|tempresident_device|Temporary Resident Card Capture Device|txt:txt|0x00",
"entity:docid=tempresident:authval:xdocument|tempresident_id|Temporary Resident Card ID|txt:txt|0x00",
"entity:docid=tempresident:jpgpic:xdocument|tempresident_visible|Temporary Resident Card Visible Image|b64:bin|0x00",
"entity:docid=tempresident:issdate:xdocument|tempresident_issdate|Temporary Resident Card Issue Date|txt:txt|0x04",
"entity:docid=tempresident:expdate:xdocument|tempresident_expdate|Temporary Resident Card Expiration Date|txt:txt|0x04",
"entity:docid=tempresident:xblk:xdocument|tempresident_info|Temporary Resident Card Information|txt:txt|0x00",
"entity:docid=employment:desc:xdocument|employment_device|Employment Authorization Card Capture Device|txt:txt|0x00",
"entity:docid=employment:authval:xdocument|employment_id|Employment Authorization Card ID|txt:txt|0x00",
"entity:docid=employment:jpgpic:xdocument|employment_visible|Employment Authorization Card Visible Image|b64:bin|0x00",
"entity:docid=employment:issdate:xdocument|employment_issdate|Employment Authorization Card Issue Date|txt:txt|0x04",
"entity:docid=employment:expdate:xdocument|employment_expdate|Employment Authorization Card Expiration Date|txt:txt|0x04",
"entity:docid=employment:xblk:xdocument|employment_info|Employment Authorization Card Information|txt:txt|0x00",
"entity:docid=reentry:desc:xdocument|reentry_device|Reentry Permit Capture Device|txt:txt|0x00",
"entity:docid=reentry:authval:xdocument|reentry_id|Reentry Permit ID|txt:txt|0x00",
"entity:docid=reentry:jpgpic:xdocument|reentry_visible|Reentry Permit Visible Image|b64:bin|0x00",
"entity:docid=reentry:issdate:xdocument|reentry_issdate|Reentry Permit Issue Date|txt:txt|0x04",
"entity:docid=reentry:expdate:xdocument|reentry_expdate|Reentry Permit Expiration Date|txt:txt|0x04",
"entity:docid=reentry:xblk:xdocument|reentry_info|Reentry Permit Information|txt:txt|0x00",
"entity:docid=refugee:desc:xdocument|refugee_device|Refugee Travel Document Capture Device|txt:txt|0x00",
"entity:docid=refugee:authval:xdocument|refugee_id|Refugee Travel Document ID|txt:txt|0x00",
"entity:docid=refugee:jpgpic:xdocument|refugee_visible|Refugee Travel Document Visible Image|b64:bin|0x00",
"entity:docid=refugee:issdate:xdocument|refugee_issdate|Refugee Travel Document Issue Date|txt:txt|0x04",
"entity:docid=refugee:expdate:xdocument|refugee_expdate|Refugee Travel Document Expiration Date|txt:txt|0x04",
"entity:docid=refugee:xblk:xdocument|refugee_info|Refugee Travel Document Information|txt:txt|0x00",
"entity:docid=dhsemplyment:desc:xdocument|dhsemplyment_device|DHS Employment Authorization Document Capture Device|txt:txt|0x00",
"entity:docid=dhsemplyment:authval:xdocument|dhsemplyment_id|DHS Employment Authorization Document ID|txt:txt|0x00",
"entity:docid=dhsemplyment:jpgpic:xdocument|dhsemplyment_visible|DHS Employment Authorization Document Visible Image|b64:bin|0x00",
"entity:docid=dhsemplyment:issdate:xdocument|dhsemplyment_issdate|DHS Employment Authorization Document Issue Date|txt:txt|0x04",
"entity:docid=dhsemplyment:expdate:xdocument|dhsemplyment_expdate|DHS Employment Authorization Document Expiration Date|txt:txt|0x04",
"entity:docid=dhsemplyment:xblk:xdocument|dhsemplyment_info|DHS Employment Authorization Document Information|txt:txt|0x00",
"entity:docid=driverslic:desc:xdocument|driverslic_device|Drivers License Capture Device|txt:txt|0x00",
"entity:docid=driverslic:authval:xdocument|driverslic_id|Drivers License ID|txt:txt|0x00",
"entity:docid=driverslic:jpgpic:xdocument|driverslic_visible|Drivers License Visible Image|b64:bin|0x00",
"entity:docid=driverslic:issdate:xdocument|driverslic_issdate|Drivers License Issue Date|txt:txt|0x04",
"entity:docid=driverslic:expdate:xdocument|driverslic_expdate|Drivers License Expiration Date|txt:txt|0x04",
"entity:docid=driverslic:xblk:xdocument|driverslic_info|Drivers License Information|txt:txt|0x00",
"entity:docid=govid:desc:xdocument|govid_device|Other Federal, State or Local ID Card Capture Device|txt:txt|0x00",
"entity:docid=govid:authval:xdocument|govid_id|Other Federal, State or Local ID Card ID|txt:txt|0x00",
"entity:docid=govid:jpgpic:xdocument|govid_visible|Other Federal, State or Local ID Card Visible Image|b64:bin|0x00",
"entity:docid=govid:issdate:xdocument|govid_issdate|Other Federal, State or Local ID Card Issue Date|txt:txt|0x04",
"entity:docid=govid:expdate:xdocument|govid_expdate|Other Federal, State or Local ID Card Expiration Date|txt:txt|0x04",
"entity:docid=govid:xblk:xdocument|govid_info|Other Federal, State or Local ID Card Information|txt:txt|0x00",
"entity:docid=schoolid:desc:xdocument|schoolid_device|School ID Card Capture Device|txt:txt|0x00",
"entity:docid=schoolid:authval:xdocument|schoolid_id|School ID Card ID|txt:txt|0x00",
"entity:docid=schoolid:jpgpic:xdocument|schoolid_visible|School ID Card Visible Image|b64:bin|0x00",
"entity:docid=schoolid:issdate:xdocument|schoolid_issdate|School ID Card Issue Date|txt:txt|0x04",
"entity:docid=schoolid:expdate:xdocument|schoolid_expdate|School ID Card Expiration Date|txt:txt|0x04",
"entity:docid=schoolid:xblk:xdocument|schoolid_info|School ID Card Information|txt:txt|0x00",
"entity:docid=voters:desc:xdocument|voters_device|Voters Registration Card Capture Device|txt:txt|0x00",
"entity:docid=voters:authval:xdocument|voters_id|Voters Registration Card ID|txt:txt|0x00",
"entity:docid=voters:jpgpic:xdocument|voters_visible|Voters Registration Card Visible Image|b64:bin|0x00",
"entity:docid=voters:issdate:xdocument|voters_issdate|Voters Registration Card Issue Date|txt:txt|0x04",
"entity:docid=voters:expdate:xdocument|voters_expdate|Voters Registration Card Expiration Date|txt:txt|0x04",
"entity:docid=voters:xblk:xdocument|voters_info|Voters Registration Card Information|txt:txt|0x00",
"entity:docid=usmilid:desc:xdocument|usmilid_device|US Military Card Capture Device|txt:txt|0x00",
"entity:docid=usmilid:authval:xdocument|usmilid_id|US Military Card ID|txt:txt|0x00",
"entity:docid=usmilid:jpgpic:xdocument|usmilid_visible|US Military Card Visible Image|b64:bin|0x00",
"entity:docid=usmilid:issdate:xdocument|usmilid_issdate|US Military Card Issue Date|txt:txt|0x04",
"entity:docid=usmilid:expdate:xdocument|usmilid_expdate|US Military Card Expiration Date|txt:txt|0x04",
"entity:docid=usmilid:xblk:xdocument|usmilid_info|US Military Card Information|txt:txt|0x00",
"entity:docid=usmildependant:desc:xdocument|usmildependant_device|US Miltary Dependant ID Card Capture Device|txt:txt|0x00",
"entity:docid=usmildependant:authval:xdocument|usmildependant_id|US Miltary Dependant ID Card ID|txt:txt|0x00",
"entity:docid=usmildependant:jpgpic:xdocument|usmildependant_visible|US Miltary Dependant ID Card Visible Image|b64:bin|0x00",
"entity:docid=usmildependant:issdate:xdocument|usmildependant_issdate|US Miltary Dependant ID Card Issue Date|txt:txt|0x04",
"entity:docid=usmildependant:expdate:xdocument|usmildependant_expdate|US Miltary Dependant ID Card Expiration Date|txt:txt|0x04",
"entity:docid=usmildependant:xblk:xdocument|usmildependant_info|US Miltary Dependant ID Card Information|txt:txt|0x00",
"entity:docid=uscgmac:desc:xdocument|uscgmac_device|US Coast Guard Mariner Card Capture Device|txt:txt|0x00",
"entity:docid=uscgmac:authval:xdocument|uscgmac_id|US Coast Guard Mariner Card ID|txt:txt|0x00",
"entity:docid=uscgmac:jpgpic:xdocument|uscgmac_visible|US Coast Guard Mariner Card Visible Image|b64:bin|0x00",
"entity:docid=uscgmac:issdate:xdocument|uscgmac_issdate|US Coast Guard Mariner Card Issue Date|txt:txt|0x04",
"entity:docid=uscgmac:expdate:xdocument|uscgmac_expdate|US Coast Guard Mariner Card Expiration Date|txt:txt|0x04",
"entity:docid=uscgmac:xblk:xdocument|uscgmac_info|US Coast Guard Mariner Card Information|txt:txt|0x00",
"entity:docid=nativeamerican:desc:xdocument|nativeamerican_device|Native American Tribal Document Capture Device|txt:txt|0x00",
"entity:docid=nativeamerican:authval:xdocument|nativeamerican_id|Native American Tribal Document ID|txt:txt|0x00",
"entity:docid=nativeamerican:jpgpic:xdocument|nativeamerican_visible|Native American Tribal Document Visible Image|b64:bin|0x00",
"entity:docid=nativeamerican:issdate:xdocument|nativeamerican_issdate|Native American Tribal Document Issue Date|txt:txt|0x04",
"entity:docid=nativeamerican:expdate:xdocument|nativeamerican_expdate|Native American Tribal Document Expiration Date|txt:txt|0x04",
"entity:docid=nativeamerican:xblk:xdocument|nativeamerican_info|Native American Tribal Document Information|txt:txt|0x00",
"entity:docid=schoolrecord:desc:xdocument|schoolrecord_device|School Record or Report Card Capture Device|txt:txt|0x00",
"entity:docid=schoolrecord:authval:xdocument|schoolrecord_id|School Record or Report Card ID|txt:txt|0x00",
"entity:docid=schoolrecord:jpgpic:xdocument|schoolrecord_visible|School Record or Report Card Visible Image|b64:bin|0x00",
"entity:docid=schoolrecord:issdate:xdocument|schoolrecord_issdate|School Record or Report Card Issue Date|txt:txt|0x04",
"entity:docid=schoolrecord:expdate:xdocument|schoolrecord_expdate|School Record or Report Card Expiration Date|txt:txt|0x04",
"entity:docid=schoolrecord:xblk:xdocument|schoolrecord_info|School Record or Report Card Information|txt:txt|0x00",
"entity:docid=medrecord:desc:xdocument|medrecord_device|Medical Record Capture Device|txt:txt|0x00",
"entity:docid=medrecord:authval:xdocument|medrecord_id|Medical Record ID|txt:txt|0x00",
"entity:docid=medrecord:jpgpic:xdocument|medrecord_visible|Medical Record Visible Image|b64:bin|0x00",
"entity:docid=medrecord:issdate:xdocument|medrecord_issdate|Medical Record Issue Date|txt:txt|0x04",
"entity:docid=medrecord:expdate:xdocument|medrecord_expdate|Medical Record Expiration Date|txt:txt|0x04",
"entity:docid=medrecord:xblk:xdocument|medrecord_info|Medical Record Information|txt:txt|0x00",
"entity:docid=nurseryrecord:desc:xdocument|nurseryrecord_device|Nursery School Record Capture Device|txt:txt|0x00",
"entity:docid=nurseryrecord:authval:xdocument|nurseryrecord_id|Nursery School Record ID|txt:txt|0x00",
"entity:docid=nurseryrecord:jpgpic:xdocument|nurseryrecord_visible|Nursery School Record Visible Image|b64:bin|0x00",
"entity:docid=nurseryrecord:issdate:xdocument|nurseryrecord_issdate|Nursery School Record Issue Date|txt:txt|0x04",
"entity:docid=nurseryrecord:expdate:xdocument|nurseryrecord_expdate|Nursery School Record Expiration Date|txt:txt|0x04",
"entity:docid=nurseryrecord:xblk:xdocument|nurseryrecord_info|Nursery School Record Information|txt:txt|0x00",
"entity:docid=ussocsec:desc:xdocument|ussocsec_device|US Social Security Card Capture Device|txt:txt|0x00",
"entity:docid=ussocsec:authval:xdocument|ussocsec_id|US Social Security Card ID|txt:txt|0x00",
"entity:docid=ussocsec:jpgpic:xdocument|ussocsec_visible|US Social Security Card Visible Image|b64:bin|0x00",
"entity:docid=ussocsec:issdate:xdocument|ussocsec_issdate|US Social Security Card Issue Date|txt:txt|0x04",
"entity:docid=ussocsec:expdate:xdocument|ussocsec_expdate|US Social Security Card Expiration Date|txt:txt|0x04",
"entity:docid=ussocsec:xblk:xdocument|ussocsec_info|US Social Security Card Information|txt:txt|0x00",
"entity:docid=birthcertdos:desc:xdocument|birthcertdos_device|DoS or Abroad Birth Certificate Capture Device|txt:txt|0x00",
"entity:docid=birthcertdos:authval:xdocument|birthcertdos_id|DoS or Abroad Birth Certificate ID|txt:txt|0x00",
"entity:docid=birthcertdos:jpgpic:xdocument|birthcertdos_visible|DoS or Abroad Birth Certificate Visible Image|b64:bin|0x00",
"entity:docid=birthcertdos:issdate:xdocument|birthcertdos_issdate|DoS or Abroad Birth Certificate Issue Date|txt:txt|0x04",
"entity:docid=birthcertdos:expdate:xdocument|birthcertdos_expdate|DoS or Abroad Birth Certificate Expiration Date|txt:txt|0x04",
"entity:docid=birthcertdos:xblk:xdocument|birthcertdos_info|DoS or Abroad Birth Certificate Information|txt:txt|0x00",
"entity:docid=usbirthcert:desc:xdocument|usbirthcert_device|US Birth Certificate Capture Device|txt:txt|0x00",
"entity:docid=usbirthcert:authval:xdocument|usbirthcert_id|US Birth Certificate ID|txt:txt|0x00",
"entity:docid=usbirthcert:jpgpic:xdocument|usbirthcert_visible|US Birth Certificate Visible Image|b64:bin|0x00",
"entity:docid=usbirthcert:issdate:xdocument|usbirthcert_issdate|US Birth Certificate Issue Date|txt:txt|0x04",
"entity:docid=usbirthcert:expdate:xdocument|usbirthcert_expdate|US Birth Certificate Expiration Date|txt:txt|0x04",
"entity:docid=usbirthcert:xblk:xdocument|usbirthcert_info|US Birth Certificate Information|txt:txt|0x00",
"entity:docid=uscitizedid:desc:xdocument|uscitizedid_device|US Citizen ID Card Capture Device|txt:txt|0x00",
"entity:docid=uscitizedid:authval:xdocument|uscitizedid_id|US Citizen ID Card ID|txt:txt|0x00",
"entity:docid=uscitizedid:jpgpic:xdocument|uscitizedid_visible|US Citizen ID Card Visible Image|b64:bin|0x00",
"entity:docid=uscitizedid:issdate:xdocument|uscitizedid_issdate|US Citizen ID Card Issue Date|txt:txt|0x04",
"entity:docid=uscitizedid:expdate:xdocument|uscitizedid_expdate|US Citizen ID Card Expiration Date|txt:txt|0x04",
"entity:docid=uscitizedid:xblk:xdocument|uscitizedid_info|US Citizen ID Card Information|txt:txt|0x00",
"entity:docid=formds1838:desc:xdocument|formds1838_device|DS-1838 Capture Device|txt:txt|0x00",
"entity:docid=formds1838:authval:xdocument|formds1838_id|DS-1838 ID|txt:txt|0x00",
"entity:docid=formds1838:jpgpic:xdocument|formds1838_visible|DS-1838 Visible Image|b64:bin|0x00",
"entity:docid=formds1838:issdate:xdocument|formds1838_issdate|DS-1838 Issue Date|txt:txt|0x04",
"entity:docid=formds1838:expdate:xdocument|formds1838_expdate|DS-1838 Expiration Date|txt:txt|0x04",
"entity:docid=formds1838:xblk:xdocument|formds1838_info|DS-1838 Information|txt:txt|0x00",
"entity:docid=other:desc:xdocument|other_device|Other Capture Device|txt:txt|0x00",
"entity:docid=other:authval:xdocument|other_id|Other ID|txt:txt|0x00",
"entity:docid=other:jpgpic:xdocument|other_visible|Other Visible Image|b64:bin|0x00",
"entity:docid=other:issdate:xdocument|other_issdate|Other Issue Date|txt:txt|0x04",
"entity:docid=other:expdate:xdocument|other_expdate|Other Expiration Date|txt:txt|0x04",
"entity:docid=other:xblk:xdocument|other_info|Other Information|txt:txt|0x00",
"entity:docid=toldoc:desc:xdocument|tentativeletter_device|T.O.L. Capture Device|txt:txt|0x00",
"entity:docid=toldoc:authval:xdocument|tentativeletter_id|T.O.L. ID|txt:txt|0x00",
"entity:docid=toldoc:jpgpic:xdocument|tentativeletter_visible|T.O.L. Visible Image|b64:bin|0x00",
"entity:docid=toldoc:issdate:xdocument|tentativeletter_issdate|T.O.L. Issue Date|txt:txt|0x04",
"entity:docid=toldoc:expdate:xdocument|tentativeletter_expdate|T.O.L. Expiration Date|txt:txt|0x04",
"entity:docid=toldoc:xblk:xdocument|tentativeletter_info|T.O.L. Information|txt:txt|0x00",
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



?>
