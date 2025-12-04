<?php

// fido and user table config
$db_host = "mysqldb";
$db_dbname = "authentx_usaccess";
$db_user = "root";
$db_pw = "usaccess";

// client domain filter
// Update to domain beign deployed
$rpId = "localhost";

// YubiKey Hardware FIDO2 AAGUIDs
// AAGUID => Product Name or Laser Marking and Level
$aaguid_mapping = array (
		"c1f9a0bc1dd2404ab27f8e29047a43fd" => array("YubiKey 5C NFC FIPS", "Level 2"),
		"default" => array("Undefined", ""),
	);

?>