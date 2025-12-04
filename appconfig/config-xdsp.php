<?PHP
// $Id:$
// Configuration file for replication system

if (!defined("_XDSPDEFS"))
{
	define ("_XDSPDEFS", true);
	
	// The xdsp application. 
	// Used to locate consumer objects in the replication branch for the application.
	$xdsp_application = "usaccess";

	// Database for dashboard and logs
	define ("DB_DBHOST_XDSP", "127.0.0.1");
	define ("DB_DBNAME_XDSP", "authentxdsp_".$xdsp_application);
	define ("DB_DBUSER_XDSP", "xdspuser_".$xdsp_application);
	define ("DB_DBPASSWD_XDSP", "84hR9bHSLubfKff");

	// Force a sleep after an authentication failure (to prevent password dictionary attacks)
	// Sleep time in seconds (0 is none).
	$xdsp_authsleep = 5;
	
	// The set of permissable branches that request branches and DNs must be contained in
	$application_toplevelbranchset = array(
			$ldap_searchcred.",ounit=credentials,".$ldap_treetop,
			$ldap_credentials.",ounit=credentials,".$ldap_treetop,
			$ldap_entities.",ounit=entities,".$ldap_treetop,
			$ldap_permissions.",ounit=permissions,".$ldap_treetop,
	);
	
}

?>