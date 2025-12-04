<?PHP
// $Id: config-pivencode.php 3263 2018-01-31 17:16:23Z sdiaz $
// Service configuration file
//    xsvc-pivencode.xas?id=useracid

require_once("../../appconfig/config-app.php");

$_cred_basedn = $ldap_authcred.",credentials=authentx,ounit=credentials,".$ldap_treetop;
$_token_basedn = $ldap_credentials.",ounit=credentials,".$ldap_treetop;
$_service_envelope = "pivencode";
$_service_version = "1.1";

// flag to turn on/off key history
$_key_history = true;

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
"token:gcoid=A000000308.5fc101:xblk:999999:gco|A00000030800001000|5fc101|b64:bin|0x00",
"token:gcoid=A000000308.5fc102:xblk:999999:gco|A00000030800001000|5fc102|b64:bin|0x00",
"token:gcoid=A000000308.5fc103:xblk:999999:gco|A00000030800001000|5fc103|b64:bin|0x00",
"token:gcoid=A000000308.5fc105:xblk:999999:gco|A00000030800001000|5fc105|b64:bin|0x00",
"token:gcoid=A000000308.5fc106:xblk:999999:gco|A00000030800001000|5fc106|b64:bin|0x00",
"token:gcoid=A000000308.5fc107:xblk:999999:gco|A00000030800001000|5fc107|b64:bin|0x00",
"token:gcoid=A000000308.5fc108:xblk:999999:gco|A00000030800001000|5fc108|b64:bin|0x00",
"token:gcoid=A000000308.5fc109:xblk:999999:gco|A00000030800001000|5fc109|b64:bin|0x00",
"token:gcoid=A000000308.5fc10a:xblk:999999:gco|A00000030800001000|5fc10a|b64:bin|0x00",
"token:gcoid=A000000308.5fc10c:xblk:999999:gco|A00000030800001000|5fc10c|b64:bin|0x00",
);

$_db_elements_escrow = array (
"token:gcoid=A000000308.5fc10b:xblk:999999:gco|A00000030800001000|5fc10b|b64:bin|0x00",
//added to encode 5 additional escrow keys in the card
"token:gcoid=A000000308.5fc10d:xblk:999999:gco|A00000030800001000|5fc10d|b64:bin|0x00",
"token:gcoid=A000000308.5fc10e:xblk:999999:gco|A00000030800001000|5fc10e|b64:bin|0x00",
"token:gcoid=A000000308.5fc10f:xblk:999999:gco|A00000030800001000|5fc10f|b64:bin|0x00",
"token:gcoid=A000000308.5fc110:xblk:999999:gco|A00000030800001000|5fc110|b64:bin|0x00",
"token:gcoid=A000000308.5fc111:xblk:999999:gco|A00000030800001000|5fc111|b64:bin|0x00",
"token:gcoid=A000000308.5fc112:xblk:999999:gco|A00000030800001000|5fc112|b64:bin|0x00",
"token:gcoid=A000000308.5fc113:xblk:999999:gco|A00000030800001000|5fc113|b64:bin|0x00",
"token:gcoid=A000000308.5fc114:xblk:999999:gco|A00000030800001000|5fc114|b64:bin|0x00",
"token:gcoid=A000000308.5fc115:xblk:999999:gco|A00000030800001000|5fc115|b64:bin|0x00",
"token:gcoid=A000000308.5fc116:xblk:999999:gco|A00000030800001000|5fc116|b64:bin|0x00",
"token:gcoid=A000000308.5fc117:xblk:999999:gco|A00000030800001000|5fc117|b64:bin|0x00",
"token:gcoid=A000000308.5fc118:xblk:999999:gco|A00000030800001000|5fc118|b64:bin|0x00",
"token:gcoid=A000000308.5fc119:xblk:999999:gco|A00000030800001000|5fc119|b64:bin|0x00",
"token:gcoid=A000000308.5fc11a:xblk:999999:gco|A00000030800001000|5fc11a|b64:bin|0x00",
"token:gcoid=A000000308.5fc11b:xblk:999999:gco|A00000030800001000|5fc11b|b64:bin|0x00",
"token:gcoid=A000000308.5fc11c:xblk:999999:gco|A00000030800001000|5fc11c|b64:bin|0x00",
"token:gcoid=A000000308.5fc11d:xblk:999999:gco|A00000030800001000|5fc11d|b64:bin|0x00",
"token:gcoid=A000000308.5fc11e:xblk:999999:gco|A00000030800001000|5fc11e|b64:bin|0x00",
"token:gcoid=A000000308.5fc11f:xblk:999999:gco|A00000030800001000|5fc11f|b64:bin|0x00",
"token:gcoid=A000000308.5fc120:xblk:999999:gco|A00000030800001000|5fc120|b64:bin|0x00",
);

$_constant_elements = array (
);

// This config set is used to extract the keyblob for each container and decrypt/decode it
// The data source is used along with the caption field only. The XML tag, conversion and flags are not used.
// The keyblob is assumed to be encrypted if $xcrypt_on is true and will be decoded into
// components and returned as base64 using the tags defined in the key_component_tags array.
$_key_elements = array (
"token:gcoid=A000000308.5fc101:xblk:000073:gco|escrowkeys|x|x|0x00",
"token:gcoid=A000000308.5fc102:xblk:000073:gco|escrowkeys|x|x|0x00",
"token:gcoid=A000000308.5fc103:xblk:000073:gco|escrowkeys|x|x|0x00",
"token:gcoid=A000000308.5fc105:xblk:000073:gco|escrowkeys|x|x|0x00",
"token:gcoid=A000000308.5fc106:xblk:000073:gco|escrowkeys|x|x|0x00",
"token:gcoid=A000000308.5fc107:xblk:000073:gco|escrowkeys|x|x|0x00",
"token:gcoid=A000000308.5fc108:xblk:000073:gco|escrowkeys|x|x|0x00",
"token:gcoid=A000000308.5fc109:xblk:000073:gco|escrowkeys|x|x|0x00",
"token:gcoid=A000000308.5fc10a:xblk:000073:gco|escrowkeys|x|x|0x00",
"token:gcoid=A000000308.5fc10c:xblk:000073:gco|escrowkeys|x|x|0x00",
);

$_key_elements_escrow = array (
"token:gcoid=A000000308.5fc10b:xblk:000073:gco|escrowkeys|9d|x|0x00",
//added to encode 5 additional escrow keys in the card
"token:gcoid=A000000308.5fc10d:xblk:000073:gco|escrowkeys|82|x|0x00",
"token:gcoid=A000000308.5fc10e:xblk:000073:gco|escrowkeys|83|x|0x00",
"token:gcoid=A000000308.5fc10f:xblk:000073:gco|escrowkeys|84|x|0x00",
"token:gcoid=A000000308.5fc110:xblk:000073:gco|escrowkeys|85|x|0x00",
"token:gcoid=A000000308.5fc111:xblk:000073:gco|escrowkeys|86|x|0x00",
"token:gcoid=A000000308.5fc112:xblk:000073:gco|escrowkeys|87|x|0x00",
"token:gcoid=A000000308.5fc113:xblk:000073:gco|escrowkeys|88|x|0x00",
"token:gcoid=A000000308.5fc114:xblk:000073:gco|escrowkeys|89|x|0x00",
"token:gcoid=A000000308.5fc115:xblk:000073:gco|escrowkeys|8a|x|0x00",
"token:gcoid=A000000308.5fc116:xblk:000073:gco|escrowkeys|8b|x|0x00",
"token:gcoid=A000000308.5fc117:xblk:000073:gco|escrowkeys|8c|x|0x00",
"token:gcoid=A000000308.5fc118:xblk:000073:gco|escrowkeys|8d|x|0x00",
"token:gcoid=A000000308.5fc119:xblk:000073:gco|escrowkeys|8e|x|0x00",
"token:gcoid=A000000308.5fc11a:xblk:000073:gco|escrowkeys|8f|x|0x00",
"token:gcoid=A000000308.5fc11b:xblk:000073:gco|escrowkeys|90|x|0x00",
"token:gcoid=A000000308.5fc11c:xblk:000073:gco|escrowkeys|91|x|0x00",
"token:gcoid=A000000308.5fc11d:xblk:000073:gco|escrowkeys|92|x|0x00",
"token:gcoid=A000000308.5fc11e:xblk:000073:gco|escrowkeys|93|x|0x00",
"token:gcoid=A000000308.5fc11f:xblk:000073:gco|escrowkeys|94|x|0x00",
"token:gcoid=A000000308.5fc120:xblk:000073:gco|escrowkeys|95|x|0x00",
);

//standard number of elements encoded on each card
$base_elements = count($_db_elements);

$_key_component_tags = array (
"modulus"=>"modulus",
"pubkeyexp"=>"public_key_exponent",
"privkeyexp"=>"private_key_exponent",
"prime1"=>"prime_1",
"prime2"=>"prime_2",
"exp1"=>"exponent_1",
"exp2"=>"exponent_2",
"coeff"=>"coefficient",
);

//Max # of escrowed keys that can be placed on each card type
$card_max_key = array(
"02200"=>6,
"02204"=>6,
"02208"=>6,
"02209"=>6,
"03308"=>21,
"03309"=>21,
"03310"=>21,
"default"=>1,
);

//Max # of escrowed certs that can be placed on each card type
$card_max_cert = array(
"02200"=>6,
"02204"=>6,
"02208"=>6,
"02209"=>6,
"03308"=>21,
"03309"=>21,
"03310"=>21,
"default"=>1,
);

?>