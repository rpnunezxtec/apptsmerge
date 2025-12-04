<?PHP
// $Id: config-asalogin.php 44 2008-10-29 06:06:24Z atlas $
// Service configuration file
//    xsvc-eslogin.xas?id=useracid

require_once("../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "eslogin";
$_service_version = "1.1";

// config array:
// source spec | applet id | container | conversion options | flags
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
"credential:cid:credential|cid|cid|txt:txt|0x00",
"credential:role:credential|role|role|txt:txt|0x01",
"credential:userPassword:credential|password|password|b64:hex|0x00",
"entity:status:entity|status|status|txt:txt|0x00",
"token:status:credential|status|status|txt:txt|0x00",
"token:tokentype:credential|tokentype|tokentype|txt:txt|0x00",
);

$_constant_elements = array (
);

$_constant_lists = array(
array("list-docs.php", "listdocs", "docs"),
array("list-pob.php", "listpob", "T2_POB.A.2"),
array("list-countries.php", "listcountries", "T2_CTZ.A.2"),
);

// em list documents for offline setup
$_emsetup_lists = array (
array("list-docs.php", "listdocs", "docs"),
);

/*
<EMSetup_EncodeDLLOptions>
oberthurUSBtokenKnownDiverKey|C,9B-yubikey.aes128|C,yubikey.aes128|C,9E-oberthurv8.1|C,9B-oberthurv8.1|C,oberthurv8.1|C,9E-gemaltoaes128|3,9B-gemaltoaes128|8,gemaltoaes128|8,gemalto|3,gemaltoretkey|3,oberthur24|3,oberthur16|1,9E-oberthuraes128|3,9B-oberthuraes128|8,oberthuraes128|8,default|3</EMSetup_EncodeDLLOptions>
 */

$_constant_EMConfig = "<EMSetup>
        <CreateNewUser>False</CreateNewUser>
        <UpdateStatuses>False</UpdateStatuses>
</EMSetup>
<EMSetup_SearchOptions>User ID|@@@,SSN+DOB|SDB,EDIPI|EDI,SSN|SSN,Email|EML,Other|</EMSetup_SearchOptions>
<keydomain>00000000000000000000000000000000</keydomain>
<EMSetup_UsePOSTOnly>False</EMSetup_UsePOSTOnly>
<EMSetup_SaveLocal>False</EMSetup_SaveLocal>
<EMSetup_OpenLocal>False</EMSetup_OpenLocal>
<EMSetup_DeleteLocal>False</EMSetup_DeleteLocal>
<EMSetup_wflow>pivi</EMSetup_wflow>
<badgetemplatesfolder>C:\Program Files (x86)\AuthentX\Enrollment Manager\Templates</badgetemplatesfolder>
<EMSetup_EncodeDLLOptions>oberthurUSBtokenKnownDiverKey|C,9B-yubikey.aes128|C,yubikey.aes128|C,9E-oberthurv8.1|C,9B-oberthurv8.1|C,oberthurv8.1|C,9E-gemaltoaes128|3,9B-gemaltoaes128|8,gemaltoaes128|8,gemalto|3,gemaltoretkey|3,oberthur24|3,oberthur16|1,9E-oberthuraes128|3,9B-oberthuraes128|8,oberthuraes128|8,default|3</EMSetup_EncodeDLLOptions>
<EMsetup_portraitcam>0</EMsetup_portraitcam>
<EMsetup_portraitPrint_width>240</EMsetup_portraitPrint_width>
<EMsetup_portraitPrint_height>320</EMsetup_portraitPrint_height>
<EMsetup_portrait_width>420</EMsetup_portrait_width>
<EMsetup_portrait_height>560</EMsetup_portrait_height>
<EMsetup_portrait_headgap>120</EMsetup_portrait_headgap>
<EMsetup_portrait_multiplier>1.8</EMsetup_portrait_multiplier>
<EMsetup_portrait_res>75</EMsetup_portrait_res>
<EMsetup_portrait_zoom>2</EMsetup_portrait_zoom>
<EMsetup_portrait_sizemode>Zoom</EMsetup_portrait_sizemode>
<EFTSsendtoidms>False</EFTSsendtoidms>
<EFTSsavelocal>False</EFTSsavelocal>
<AuthenticateDLL>4</AuthenticateDLL>
<EMsetup_permission_registrar>registrar</EMsetup_permission_registrar>
<EMsetup_permission_registrar_Exclusive>False</EMsetup_permission_registrar_Exclusive>
<EMsetup_permission_activator>activator</EMsetup_permission_activator>
<EMsetup_forcecaptureAll>False</EMsetup_forcecaptureAll>
<EMsetup_viewItemsToBeCaptured>True</EMsetup_viewItemsToBeCaptured>
<EMSetup_PrintingCarrier>
        <UpperWindow>1.56,0.86,lastname, firstname mi agency</UpperWindow>
        <LowerWindow>1.56,1.98,User ID: userid pickuploc address1 address2</LowerWindow>
        <PlaceHolders>firstname,lastname,mi,agency,pickuploc,userid</PlaceHolders>
        <Enabled>False</Enabled>
        <PrinterName></PrinterName>
        <FontName>Verdana</FontName>
        <FontStyle>Bold, Italic, Underline, Strikeout</FontStyle>
        <FontSize>10</FontSize>
</EMSetup_PrintingCarrier>
<EMSetup_forceStatusCheck>
        <CaputurePending>False</CaputurePending>
        <PrintingPending>False</PrintingPending>
        <ActivationPending>False</ActivationPending>
        <EncodePending>False</EncodePending>
        <DeliveryPending>False</DeliveryPending>
        <EntityActive>False</EntityActive>
</EMSetup_forceStatusCheck>
<EMSetup_forceLocalLog>
        <eftslive>False</eftslive>
        <credcreate>False</credcreate>
        <printcarrier>False</printcarrier>
        <initcard>False</initcard>
        <reqcr>False</reqcr>
        <getcert>False</getcert>
        <eftscard>False</eftscard>
        <capture>False</capture>
        <pivencode>False</pivencode>
        <efts>False</efts>
        <validate>False</validate>
        <enroll>False</enroll>
        <fsvalidate>False</fsvalidate>
        <clspiv>False</clspiv>
        <livescan>False</livescan>
        <eslogin>False</eslogin>
        <cardlog>False</cardlog>
	<testcard>False</testcard>
	<clsYubiKey>False</clsYubiKey>
</EMSetup_forceLocalLog>
<EMSetup_Authenticate>
        <ForceFPCapture>False</ForceFPCapture>
        <WsqToWsq>True</WsqToWsq>
        <AMPtoAMP>True</AMPtoAMP>
        <ANSIITemplate>True</ANSIITemplate>
</EMSetup_Authenticate>
<EMSetup_Validate>
        <seclevel>2</seclevel>
</EMSetup_Validate>
<EMSetup_forceLogin>True</EMSetup_forceLogin>
<EMSetup_forceBindbeforePrint>False</EMSetup_forceBindbeforePrint>
<EMSetup_forceCardOnlyLogin>true</EMSetup_forceCardOnlyLogin>
<EMSetup_AuthenticateUserActivate>false</EMSetup_AuthenticateUserActivate>
<EMSetup_AuthenticateUserPrinting>false</EMSetup_AuthenticateUserPrinting>
<EMSetup_AuthenticateUserReqCerts>false</EMSetup_AuthenticateUserReqCerts>
<EMSetup_DisplayStatusWindow>true</EMSetup_DisplayStatusWindow>
<EMsetup_permission_efts>fingerprintadmin</EMsetup_permission_efts>
<EMSetup_LogoutTimer>900</EMSetup_LogoutTimer>
<IsCardUploadEnabled>true</IsCardUploadEnabled>
<Customer_Name>AuthentX Enrollment Station</Customer_Name>
<Customer_Logo>C:\Program Files (x86)\AuthentX\Enrollment Manager\AuthentX Cloud triple CLOUDS ONLY.png</Customer_Logo>
<CheckRemoteCertificateNotAvailable>false</CheckRemoteCertificateNotAvailable>
<EMSetup_AuthenticateUserActivateNewWay>true</EMSetup_AuthenticateUserActivateNewWay>
<CheckRemoteCertificateChainErrors>false</CheckRemoteCertificateChainErrors>
<CheckCertificateRevocationList>false</CheckCertificateRevocationList>
<CheckRemoteCertificateNameMismatch>false</CheckRemoteCertificateNameMismatch>
<EMSetup_EnableOberthurv8_1CardZeroized>false</EMSetup_EnableOberthurv8_1CardZeroized>
<IDMSurlDefault>https://dev.authentx.com/authentx/servicesasa</IDMSurlDefault>
<IDMSurl>https://dev.authentx.com/authentx/servicesasa</IDMSurl>
<IsOfflineModeEnabled>true</IsOfflineModeEnabled>
<OfflineWorkflowList>piv|pivi|pivo|fac|dac|enrollment</OfflineWorkflowList>
<OfflineWorkFlowOptions>piv:Personal Identification Verification Card:fpcap,doccap,photocap|enrollment::fpcap,doccap,photocap|</OfflineWorkFlowOptions>
<EMSetup_AuthenticateUserActivateIgnoreWF>dac|visitor|npe</EMSetup_AuthenticateUserActivateIgnoreWF>"

?>
