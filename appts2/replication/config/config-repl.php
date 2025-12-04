<?PHP
// $Id:$
// Configuration file for the appointments replication system

if (!defined("_REPLAPPTSDEFS"))
{
	define ("_REPLAPPTSDEFS", true);
	
	// ************* COMMON CONFIGURATION (ie common to both provider and consumer)
	// This server's ID. This must be unique for all servers in the replication mesh
	// It can be any string, so long as it is unique. It should match the source ID in the xticket.
	$cfg_serverid = "SERVER SUBSERVER ID HERE";
	$cfg_siteid = "axappt";									// Application site ID for auth checks (dashboard)
	$cfg_agencyid = "00000000000000000000000000030015";		// Application/agency ID for logging.
	
	// Database for appointments to be replicated
	if (!defined("DB_DBHOST_APPTS"))
		define ("DB_DBHOST_APPTS", "127.0.0.1");
	if (!defined("DB_DBPORT_APPTS"))
		define ("DB_DBPORT_APPTS", 3306);
	if (!defined("DB_DBNAME_APPTS"))
		define ("DB_DBNAME_APPTS", "authentxappts2_ax");
	if (!defined("DB_DBUSER_APPTS"))
		define ("DB_DBUSER_APPTS", "axapptuser");
	if (!defined("DB_DBPASSWD_APPTS"))
		define ("DB_DBPASSWD_APPTS", "G72jNcxPR83Hsv");
	
	// Database for dashboard and logs (not replicated)
	if (!defined("DB_DBHOST_APPTSREPLOG"))
		define ("DB_DBHOST_APPTSREPLOG", "127.0.0.1");
	if (!defined("DB_DBPORT_APPTSREPLOG"))
		define ("DB_DBPORT_APPTSREPLOG", 3306);
	if (!defined("DB_DBNAME_APPTSREPLOG"))
		define ("DB_DBNAME_APPTSREPLOG", "authentxappts2replog_ax");
	if (!defined("DB_DBUSER_APPTSREPLOG"))
		define ("DB_DBUSER_APPTSREPLOG", "apptreploguser");
	if (!defined("DB_DBPASSWD_APPTSREPLOG"))
		define ("DB_DBPASSWD_APPTSREPLOG", "GwPKkcB5M8s2T4");
	
	// Perform xticket online status check
	$_xt_schk = false;
	
	// The replication application identifier. 
	if (!defined("APPID_REPLICATION"))
		define ("APPID_REPLICATION", "0000000000000000000000000003002D");
		
	// The tables and their unique replicatable key. Do not include the deletedrows table here.
	// tablename => keyname
	$cfg_tableset = array (
		"user" => "uuid",
		"appointment" => "apptuuid",
		"site" => "centeruuid",
		"workstation" => "wsuuid",
		"holidaymap" => "hmapuuid",
		"log" => "logevuuid",
		"availexception" => "axuuid",
		"sitelimitopen" => "slouuid",
		"mailtemplate" => "mtuuid",
	);
	
	// The auto increment column in tables, which is not replicated
	// tablename => aicolname
	$cfg_aicols = array (
		"user" => "uid",
		"appointment" => "apptid",
		"site" => "siteid",
		"workstation" => "wsid",
		"holidaymap" => "hmapid",
		"log" => "logid",
		"availexception" => "axid",
		"sitelimitopen" => "sloid",
		"mailtemplate" => "mtid",
	);
	
	// ************* PROVIDER CONFIGURATION (ie when this server is a provider)
	$cfg_permit_tables = array (
		"user",
		"appointment",
		"site",
		"workstation",
		"holidaymap",
		"log",
		"availexception",
		"sitelimitopen",
		"mailtemplate",
	);
	
	
	// ************* CONSUMER CONFIGURATION (ie when this server is a consumer)
	// Consumer client cert and passphrase - These MUST be provided for all replication requests.
	// These are example placeholders. A PEM certificate needs to be generated for each consumer using
	// an authentxapptsreplication issuing certificate for the server cluster.
	$clientcert = "/authentx/app/https/authentx/appts2/replication/config/consumercert.pem";
	$clientcertpassphrase = "CERTPASSPHRASE";
	
	// The consumer xticket. This must be present for the request to be successful and is generated using
	// the xticket management system. The appid MUST be the apptreplication appid in order to be accepted.
	// The value here is a placeholder only. The sourceid should match the server ID.
	$consumer_xticket = "APPTREPLICATION_XTICKET";
	
	// Proxy server and port if required, otherwise set the server to false to disable
	$consumer_proxyserver = false;
	$consumer_proxyport = 8080;
	// Replication sleep time in seconds between requesting provider sets.
	$repl_consumersleep = 60;
	// Timeout for the curl connection in seconds.
	$consumertimeout_conn = 30;
    // Timeout for the complete curl opertion. Should be fairly large if large result sets for out of date consumers are expected.
    $consumertimeout_exec = 21600;
    // Difference in s between xsyncmts and xreplmts where the row is considered 'replicated' and not included, to prevent reverse replication
    // in newly added rows.
    $cfg_mts_gap = 1;
	
	// Request multiple objects per transaction connection.
	// With multiple object fetch, a request is made for up to the quantity specified in a single connection.
	// The overcomes network latencies that can slow replication down for large numbers of objects.
	// object limit per connection
	$cfg_repl_multilimit = 100;
	
	// Overlap amount between replication searches in seconds. Allows for rows being written as the search was being carried out.
	// If using slave mode, be generous with this to allow for replicated sets of rows.
	$cfg_repl_overlap = 5;
    
    // Slave mode operation setting
    // When slave is set to true, the replication consumer acts as a slave, reading ALL modified objects from the timestamp
    // from the provider, including those replicated from another provider. 
    // This means that the slave consumer only needs to connect to ONE provider to receive all modified data.
    $cfg_mode_slave = false;
 
	
	// INCLUSION TABLES
	// The tables to include in replication from remote providers. Do not include the deletedrows table here.
	$cfg_repl_tables = array (
		"user",
		"appointment",
		"site",
		"workstation",
		"holidaymap",
		"log",
		"availexception",
		"sitelimitopen",
		"mailtemplate",
	);

	
	// ************* LOG MANAGER CONFIGURATION
	// Set this to the number of days of detailed transaction logs kept for provider transactions
	// Set to 0 for no transaction trimming
	$expdays_provider = 30;
	
	// Set this to the number of days of detailed transaction logs kept for consumer transactions
	// Set to 0 for no transaction trimming
	$expdays_consumer = 30;
	
	// Set this to the number of days of error logs kept
	// Set to 0 for no log trimming
	$expdays_errlog = 90;
	
	// Seconds between log manager checks. Normally a day or so will do
	if (!defined('REPLAPPTLOGMGRSLEEPTIME'))
		define ('REPLAPPTLOGMGRSLEEPTIME', 86400);
	
}

?>