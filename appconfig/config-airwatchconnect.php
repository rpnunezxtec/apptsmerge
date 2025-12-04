<?php

include_once("../appconfig/config-caconnect.php");

// Connection parameters for AW
$cfg_params = array (
//		"clcertfile" => "/authentx/app/https/cbp/airwatch/siteconfig/airwatch_xtec.pem",	// Must be PEM format
//		"clcertpasswd" => "Hpe01Api",
		//"awhost" => "https://10.127.12.121/",
		//"awhost" => "https://Pptds.omnipresenteagle.com/", old
		//"awhost" => "https://as2098.awfed.com/", //DEA Test UAT
		//"awhost" => "https://as800.airwatchportals.com/", //XTec Test
		"awhost" => "https://as2097.awfed.com/",
);
    
$auth_awtagid = "10023";
$auth_username = "SBU\svc-Aw-XTEC"; // DEA PROD
$auth_passwd = 'yrAL#Uy2aMK2uc'; // DEA PROD
$auth_awtenantcode = "Tp4kMJRa/TblEZ9W86zjbrZMb273hZwp54Dv53PUV8A="; // DEA PROD

// AW headers
$httpheaders = array (
		"Content-Type: application/json; charset=utf-8",
		"Authorization: Basic ".base64_encode($auth_username.":".$auth_passwd),
		"aw-tenant-code: ".$auth_awtenantcode,
);

// CA Gateway connection configuration
$caconnection = "dcrt";
$cfg_caparams = array (
	'test' => array (    
		// directs to socket 9250 at gateway
		'gateway_url' => 'https://xca.xpki.com/axcamanager/certrequest/makecertrequest',
		'careq' => 'PIVI_Test_Demo_CA',
		'origin' => 'XTECGC',
	),

	'dcrt' => array (  
		'gateway_url' => 'https://xca.xpki.com/axcamanager/certrequest/makecertrequest',
		'careq' => 'PIVI_PROD',
		'origin' => 'DEADerived',
	),
);

$cfg_caheaders = array (
	"Content-Type: application/xml; charset=utf-8",
);

$cfg_opcontrols = array (
	array(
	'name' => 'operationsType',
	'val' => 'KeyGen',
	),
	array(
	'name' => 'keyDisposition',
	'val' => 'EscrowAndReturn',
	)
);

$cfg_fncontrols = array(
	"9A" => array(
		array(
		'name' => 'pkiProfileID',
		'val' => 'FIPS201piv9A',
		),
		array(
		'name' => 'certClass',
		'val' => 'PIVI',		// ASCII Numeric 0=PIV, 1=PIVI, 2=FLAC, 3=DCRT (derived cert) PER RON SHOULD BE PIV.
		),
		array(
		'name' => 'tokenType',
		'val' => 'PIVI',		// ASCII Numeric 0=PIV, 1=PIVI, 2=FLAC, 3=DCRT (derived cert) PER RON SHOULD BE PIV.
		),
		array(
		'name' => 'certType',
		'val' => 'DCRT-9A',		// DCRT-9A, DCRT-9C, DCRT-9D  ------- THIS ONE NEEDS TO BE ONLY 9A
		),
	),
	
	"9C" => array(
		array(
		'name' => 'pkiProfileID',
		'val' => 'FIPS201piv9C',
		),
		array(
		'name' => 'certClass',
		'val' => 'PIVI',		// ASCII Numeric 0=PIV, 1=PIVI, 2=FLAC, 3=DCRT (derived cert) PER RON SHOULD BE PIV.
		),
		array(
		'name' => 'tokenType',
		'val' => 'PIVI',		// ASCII Numeric 0=PIV, 1=PIVI, 2=FLAC, 3=DCRT (derived cert) PER RON SHOULD BE PIV.
		),
		array(
		'name' => 'certType',
		'val' => 'DCRT-9C',		// DCRT-9A, DCRT-9C, DCRT-9D  ------- THIS ONE NEEDS TO BE ONLY 9A
		),
	),
);

// Cert parameter configuration
// Number of days that the certificate lasts
$cert_daystoexpiration = "P730D";
// Prefix used in userid portion of subjectName DN
$cfg_mdm_prefix = 'DPCDEA'; // to be used for production certificates

// *** Note: Once these are set they should never be changed!!
// Derived cert container prefix name (eg gcoid=derivedcert.cert,...)
// The derived cert container uses the cert serial number after this prefix and before the suffix.
$cfg_cert_container = "derivedcert";

// Token type name
$cfg_ctype = array(
	"9A" => "DCRT-9A",
	"9C" => "DCRT-9C",
);

$keyid = array(
	"9A" => "DCRT-9A",
	"9C" => "DCRT-9C",
);

// default token status
$default_token_status = "active";

$issby = "Airwatch DEA";

// Number of days before cert expdate that the cert is considered expired for the purposes of requesting another
$cfg_preexpirytime = 60;

// Derived certificate types to display in certs table
$cfg_ctype_arr = array(
    "userdevice",
    "DCRT-9A",
    "DCRT-9C",
);
    
?>