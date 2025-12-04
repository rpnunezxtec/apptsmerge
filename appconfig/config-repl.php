<?PHP
// $Id:$
// Configuration file for replication system

if (!defined("_REPLDEFS"))
{
	define ("_REPLDEFS", true);
	
	// The replication application. 
	// Used to locate provider objects in the replication branch for the application.
	$repl_application = "usaccess";

	// Database for dashboard and logs
	define ("DB_DBHOST_REPL", "127.0.0.1");
	define ("DB_DBNAME_REPL", "authentxreplication_".$repl_application);
	define ("DB_DBUSER_REPL", "repluser_".$repl_application);
	define ("DB_DBPASSWD_REPL", "935sbGDtlkasiwe");

	// Force a sleep after an authentication failure (to prevent password dictionary attacks)
	// Sleep time in seconds (0 is none).
	$repl_authsleep = 5;
	
	// This is the margin (in seconds) allowed between modifyTimestamp and xsyncdate in
	// an object in order to still be considered a replication result. If the difference is 
	// greater than this margin then it is considered locally modified.
	$repl_margintime = 2;
	
	// Replication sleep time in seconds between requesting provider sets.
	$repl_consumersleep = 300;
	
	// INCLUSION BRANCHES
	// The branches to include in replication from remote providers
	$repl_branches = array (
		$ldap_application_deletedbranch,
		$ldap_searchcred.",ounit=credentials,".$ldap_treetop,
		$ldap_searchtoken.",ounit=credentials,".$ldap_treetop,
		$ldap_entities.",ounit=entities,".$ldap_treetop,
		"permissions=issued,".$ldap_accessbranch.",".$ldap_permissions.",ounit=permissions,".$ldap_treetop,
		$ldap_ac_devices.",".$ldap_accessbranch.",".$ldap_permissions.",ounit=permissions,".$ldap_treetop,
		$ldap_ac_devgroups.",".$ldap_accessbranch.",".$ldap_permissions.",ounit=permissions,".$ldap_treetop,
		$ldap_ac_accessgroups.",".$ldap_accessbranch.",".$ldap_permissions.",ounit=permissions,".$ldap_treetop,
	);

	// EXCLUSION FILTERS
	// The filters to apply to the DN to exclude it from replication.
	// e.g. if portrait images are not required then uncomment 'bioid=portrait' in this list
	$repl_exclusion_filters = array (
//		"bioid=portrait",
//		"docid=driverslic",
//		"docid=usbirthcert",
//		"docid=passport",
//		"docid=cizenship",
//		"docid=naturalization",
//		"docid=foreignpassport",
//		"docid=resident",
//		"docid=tempresident",
//		"docid=employment",
//		"docid=reentry",
//		"docid=refugee",
//		"docid=dhsemplyment",
//		"docid=govid",
//		"docid=schoolid",
//		"docid=voters",
//		"docid=usmilid",
//		"docid=usmildependant",
//		"docid=uscgmac",
//		"docid=nativeamerican",
//		"docid=schoolrecord",
//		"docid=medrecord",
//		"docid=nurseryrecord",
//		"docid=ussocsec",
//		"docid=birthcertdos",
//		"docid=usbirthcert",
//		"docid=uscitizedid",
//		"docid=formds1838",
//		"docid=other",
//		"bioid=R_Thumb",
//		"bioid=R_Index",
//		"bioid=R_Middle",
//		"bioid=R_Ring",
//		"bioid=R_Little",
//		"bioid=L_Thumb",
//		"bioid=L_Index",
//		"bioid=L_Middle",
//		"bioid=L_Ring",
//		"bioid=L_Little",
//		"bioid=R_Slap_LS",
//		"bioid=L_Slap_LS",
//		"bioid=RT_Plain_LS",
//		"bioid=LT_Plain_LS",
//		"bioid=R_Thumb_CS",
//		"bioid=R_Index_CS",
//		"bioid=R_Middle_CS",
//		"bioid=R_Ring_CS",
//		"bioid=R_Little_CS",
//		"bioid=L_Thumb_CS",
//		"bioid=L_Index_CS",
//		"bioid=L_Middle_CS",
//		"bioid=L_Ring_CS",
//		"bioid=L_Little_CS",
//		"bioid=R_Slap_CS",
//		"bioid=L_Slap_CS",
//		"bioid=RT_Plain_CS",
//		"bioid=LT_Plain_CS",
//		"bioid=PIV_Primary",
//		"bioid=PIV_Secondary",

);

	// Resilience mode (used with the replication seeding tool) will continue to request
	// the current object whilst ldap connection fails on the provider server.
	$_resilience_mode = true;
	
}

?>