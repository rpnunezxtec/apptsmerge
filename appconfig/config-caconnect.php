<?PHP
// $Id: config-carevrequest.php 1718 2012-02-16 22:25:57Z hjackson $
// global ca connection configuration file

// These tables are used to direct the certificate and certificate revocation
// requests through the CA-gateway to the correct CA interface.

//New configuration for A5 Gateway
// set to true if using AX CA
$use_axca = TRUE;

// private key to be used to sign the xml
$_ca_admin_pk_path = "/authentx/app/certs/xca_data_signing_02122020.key";
$adminpwd = "desRsAmD5Sha512aEs256";

//This has been updated to use the new Authentx CA
// set LRA admin acid
$adminid = "EDI8241514766";

// add component to UID
$component_region = "usaccess";

$_ca_config = array (
	'piv' => array (
		'gateway_url' => 'https://66.165.167.180/axcamanager/certrequest/makecertrequest',
		'careq' => 'PIVI_Test_Demo_CA',
		'origin' => 'XTECGC',
		),
	'9E' => array (
		'gateway_url' => 'http://smweb1:18080/CAWeb/Cert_Request',
		'careq' => 'PIV_Test_Demo_CA',
		'origin' => 'DHSCARDAUTH',
		),
	'pivi' => array (
		'gateway_url' => 'https://66.165.167.180/axcamanager/certrequest/makecertrequest',
		'careq' => 'PIVI_Test_Demo_CA',
		'origin' => 'XTECGC',
		),
	'dac' => array (
                'gateway_url' => 'https://66.165.167.180/axcamanager/certrequest/makecertrequest',
                'careq' => 'PIVI_Test_Demo_CA',
                'origin' => 'XTECGC',
                ),
);
?>
