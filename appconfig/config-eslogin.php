<?PHP
// $Id: config-eslogin.php 44 2008-10-29 06:06:24Z atlas $
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
"token:status:entity|status|status|txt:txt|0x00",
);

$_constant_elements = array (
);

$_constant_EMConfig = "<EnrollmentManager>
<badgetemplatesfolder>C:\Program Files (x86)\Authentx\AuthentX Printing Client\Templates</badgetemplatesfolder>
<keydomain>734F93180494A63E8C25E4842F1263B6</keydomain>
<EMsetup_portraitPrint_width>240</EMsetup_portraitPrint_width>
<EMsetup_portraitPrint_height>320</EMsetup_portraitPrint_height>
<EMsetup_portrait_width>420</EMsetup_portrait_width>
<EMsetup_portrait_height>560</EMsetup_portrait_height>
<EMsetup_portrait_headgap>70</EMsetup_portrait_headgap>
<EMsetup_portrait_multiplier>1.8</EMsetup_portrait_multiplier>
<EMsetup_portrait_res>L</EMsetup_portrait_res>
<EMsetup_portrait_zoom>2</EMsetup_portrait_zoom>
<EMsetup_portrait_sizemode>StretchImage</EMsetup_portrait_sizemode>
<EFTSsendtoidms>False</EFTSsendtoidms>
<EFTSsavelocal>True</EFTSsavelocal>
<AuthenticateDLL>0</AuthenticateDLL>
<IDMSurl>https://www.authentx.com/si/services</IDMSurl>
<EMsetup_permission_registrar>registrar</EMsetup_permission_registrar>
<EMsetup_permission_registrar_Exclusive>False</EMsetup_permission_registrar_Exclusive>
<EMsetup_permission_activator>activator</EMsetup_permission_activator>
<EMsetup_forcecaptureAll>False</EMsetup_forcecaptureAll>
<EMsetup_viewItemsToBeCaptured>True</EMsetup_viewItemsToBeCaptured>
<EMSetup_forceStatusCheck>
	<CaputurePending>True</CaputurePending>
	<PrintingPending>True</PrintingPending>
	<ActivationPenting>True</ActivationPenting>
	<DeliveryPending>True</DeliveryPending>
	<EntityActive>True</EntityActive>
</EMSetup_forceStatusCheck>
<EMSetup_forceLocalLog>
	<eftslive>True</eftslive>
	<initcard>True</initcard>
	<reqcr>True</reqcr>
	<getcert>True</getcert>
	<eftscard>True</eftscard>
	<capture>True</capture>
	<pivencode>True</pivencode>
	<efts>True</efts>
	<validate>True</validate>
	<fsvalidate>True</fsvalidate>
	<clspiv>True</clspiv>
	<livescan>True</livescan>
	<eslogin>True</eslogin>
	<cardlog>True</cardlog>
	<testcard>True</testcard>
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
</EnrollmentManager>"

?>
