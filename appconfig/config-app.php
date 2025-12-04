<?PHP
// $Id: config-app.php 437 2009-06-20 03:56:45Z  $
// The page a user is directed to if they are denied access
if (!isset($page_denied))
	$page_denied = "index.html";
// The page a user is directed to if their session or credential has expired
if (!isset($page_expired))
	$page_expired = "index.html";
// The page a user is directed to when they logout
if (!isset($page_logout))
	$page_logout = "../portal-cert/index.html";
// The poratl page a user is directed to when they logout
if (!isset($site_portal))
	$site_portal = "../portal-cert/portal.html";
// The non-cert portal page
if (!isset($page_portal))
	$page_portal = "../portal-cert/index.html";
// The cert portal page. Set to the same as the non-cert portal if no cert portal is present.
if (!isset($page_certportal))
	$page_certportal = "../portal-cert/index.html";
if (!isset($page_sclaccessdenied))
	$page_sclaccessdenied = "../portal-cert/accessdenied.html";
// Single-signon credential ticket timeout in seconds. 0 for unlimited.
if (!defined("XAUTH_SSO_TIMEOUT"))
	define("XAUTH_SSO_TIMEOUT", 600);

// Site directory name
$site = "usaccess";

// SET THIS TO TRUE TO AUTOMATICALLY CONFIGURE AD
$configad = false;

// Emplid to be used across the site
$cfg_emplid = "usaccess";

// Make use of udh values to separate entity records if true
if (!defined("USE_UDH"))
	define("USE_UDH", false);
// Set to true to allow implicit 'm' rights to the admin's udh group
if (!defined("UDH_IMPLICIT"))
	define("UDH_IMPLICIT", false);
// Full path to the UDH config file
if (!defined("UDH_CONFIG"))
	define("UDH_CONFIG", "/authentx/app/https/usaccess/appconfig/config-udh.php");
// The session name to use for appointments
if (!defined("SESS_NAME"))
	define("SESS_NAME", "authentxappts");
// The ldap host to perform operations on
if (!isset($ldap_host))
	$ldap_host = "openldap";
if (!isset($authentx_ldap_port))
	$authentx_ldap_port = 389;

// The site ldap tree top
if (!isset($ldap_treetop))
	$ldap_treetop = "dc=authentx";
// The tree under which the user's authentx credential can be found for this application
// ie $ldap_authcred,credentials=authentx,ounit=credentials,dc=authentx
if (!isset($ldap_authcred))
	$ldap_authcred = "credentials=usaccess";
// This is an optional authentx credential search base. It is under the 'ounit=credentials' branch.
// When commented out the $ldap_authcred is used. This allows potentially all credentials to be searched, 
// or just those under the authentx branch.
// ie $ldap_searchcred,ounit=credentials,dc=authentx
if (!isset($ldap_searchcred))
	$ldap_searchcred = "credentials=usaccess,credentials=authentx";
// The tree under which new users will have their badge credential entry stored
// ie $ldap_credentials,ounit=credentials,dc=authentx
if (!isset($ldap_credentials))
	$ldap_credentials = "credentials=usaccess,credentials=tokens";
// The tree that will be used for the token credential search base. It is under the 'ounit=credentials' branch.
// ie $ldap_searchtoken,ounit=credentials,dc=authentx
if (!isset($ldap_searchtoken))
	$ldap_searchtoken = "credentials=usaccess,credentials=tokens";
// The tree under which new users will have their entity entry stored
// ie $ldap_entities,ounit=entities,dc=authentx
if (!isset($ldap_entities))
	$ldap_entities = "entities=usaccess";
// The list of regions that this site is restricted to retrieving
if (!isset($ldap_regionlist))
	$ldap_regionlist = array("va","treasury","usda","doj","gsa","reston","miami","usaccess", "xtec");
// The tree under which new users will have their permissions entry stored
// ie $ldap_permissions,ounit=permissions,dc=authentx
if (!isset($ldap_permissions))
	$ldap_permissions = "permissions=usaccess";
	
// The access control permissions branch.
if (!isset($ldap_accessbranch))
	$ldap_accessbranch = "permissions=access";
// The branch head names for access control branches
if (!isset($ldap_ac_devices))
	$ldap_ac_devices = "ounit=devices";
if (!isset($ldap_ac_devgroups))
	$ldap_ac_devgroups = "ounit=devicegroups";
if (!isset($ldap_ac_accessgroups))
	$ldap_ac_accessgroups = "ounit=accessgroups";

// The inventory objects branch
if (!isset($ldap_inventory_branch))
        $ldap_inventory_branch = "ounit=usaccess,ounit=inventory,".$ldap_treetop;
if (!isset($ldap_inventory_cardstock))
        $ldap_inventory_cardstock = "ounit=cardstock,".$ldap_inventory_branch;

// unused status values for inventory:
$unusedstatvals = array("unused", "unissued");

//see if transfers are allowed by EMs:
if (!isset($enable_cardstock))
        $enable_cardstock = true;
if (!isset($cardstock_restrictions))
        $cardstock_restrictions = false;
//enforcement of cardstock -- if not in inventory cannot use
if (!isset($cardstock_enforcement))
        $cardstock_enforcement = false;

// The workstation/EM permissions branch.
if (!isset($ldap_embranch))
	$ldap_embranch = "permissions=em";
// The branch head names for enrollment workstation branches
if (!isset($ldap_em_devices))
	$ldap_em_devices = "ounit=devices";
if (!isset($ldap_em_devgroups))
	$ldap_em_devgroups = "ounit=devicegroups";
if (!isset($ldap_em_accessgroups))
	$ldap_em_accessgroups = "ounit=accessgroups";
	
// The application control permissions branch.
if (!isset($ldap_applicationbranch))
	$ldap_applicationbranch = "permissions=applications";
// The branch head names for application control branches
if (!isset($ldap_app_apps))
	$ldap_app_apps = "ounit=apps";
if (!isset($ldap_app_appgroups))
	$ldap_app_appgroups = "ounit=appgroups";
if (!isset($ldap_app_accessgroups))
	$ldap_app_accessgroups = "ounit=accessgroups";
	
// The hspd12 permissions branch.
if (!isset($ldap_application_hspd12branch))
	$ldap_application_hspd12branch = "permissions=hspd12_usaccess";

// The deleted objects branch DN.
if (!isset($ldap_application_deletedbranch))
	$ldap_application_deletedbranch = "ounit=usaccess,ounit=deletedobjects,".$ldap_treetop;
	
// The UDN DB is defined locally per application
if (!defined("UDNDBHOST"))
	define ("UDNDBHOST", "127.0.0.1");
//if (!defined("UDNDBASE"))
//	define ("UDNDBASE", "authentxudn_usaccess");
if (!defined("UDNFILE"))
	define ("UDNFILE", "/authentx/app/https/usaccess/downloads/files/udnentry.txt");

// Set this define to true to enable client certificate authentication.
// The 'upn' attribute is used as the login identifier for the user.
// This also requires apache to be configured for client cert auth and the appropriate CA certs installed.
if (!defined("ENABLE_AUTH_CLIENT"))
	define("ENABLE_AUTH_CLIENT", true);

// Enables certificate authentication for services-auth.
// Uses CN for authentication (see ENABLE_AUTH_CLIENT).
if (!defined("ENABLE_AUTH_SERVICES"))
	define("ENABLE_AUTH_SERVICES", true);

// Allows the use of the client cert serial if the UPN fails.
// OK if all serials can be guaranteed unique.
if (!defined("ALLOW_AUTH_CLIENT_SERIAL"))
	define("ALLOW_AUTH_CLIENT_SERIAL", false);
	
// Allow userID/password authentication to sites.
// Set to true to enable the site to be accessed using passwords.
// Used in conjunction with client cert auth to limit the methods that can be used for login.
if (!defined("ENABLE_AUTH_PASSWORD"))
	define("ENABLE_AUTH_PASSWORD", false);

// Allow userID/password single-signon authentication to sites.
// Set to true to enable the site to be accessed using stored userid/password authentication.
if (!defined("ENABLE_SINGLE_SIGNON"))
	define("ENABLE_SINGLE_SIGNON", true);

// Allow the site to display the users roles, rights, lists etc from the online config or from a php file depending on the sites set up.
// If the site does not have 'ENABLE_ONLINE_CONFIG', the data will come from php files.
if (!defined("ENABLE_ONLINE_CONFIG"))
	define("ENABLE_ONLINE_CONFIG", false);

// Enable the use of agency restrictions on user records. If set to true, it will restrict user access to records within their agency only. 
if (!defined("ENABLE_AGENCY_RESTRICTIONS"))
	define("ENABLE_AGENCY_RESTRICTIONS", true);

// Set this to true to close the entire application from access.
// Otherwise set it to false and set it in each site's siteconfig to close specific sites.
if (!isset($_accessdisabled))
	$_accessdisabled = false;

// This is a short text message that appears in the login screen when disabled
if (!isset($_accessdisabledmessage))
	$_accessdisabledmessage = "System Offline";

// The $_xemagencyid is for logging with an agency identifier and for the counter appid
if (!isset($_xemagencyid))
	$_xemagencyid = "00000000000000000000000000030015";

// Set to true to allow single signon button to be active and appear if user returns to agency portal
if (!isset($singlesignon_active))
	$singlesignon_active = true;

// Counter ID's
// Each event requiring counting should have a unique counter ID assigned to it.
// The stats counter class uses this ID to add or update the counter when requested.
if (!defined("CTRLOGINS_ACCESSADMIN"))
	define("CTRLOGINS_ACCESSADMIN", "301501");
if (!defined("CTRLOGINS_ACCESSCONFIG"))
	define("CTRLOGINS_ACCESSCONFIG", "301502");
if (!defined("CTRLOGINS_ACCESSREPORTS"))
	define("CTRLOGINS_ACCESSREPORTS", "301503");
if (!defined("CTRLOGINS_ADMIN"))
	define("CTRLOGINS_ADMIN", "301504");
if (!defined("CTRLOGINS_LISTMNGR"))
	define("CTRLOGINS_LISTMNGR", "301505");
if (!defined("CTRLOGINS_CM"))
	define("CTRLOGINS_CM", "301506");
if (!defined("CTRLOGINS_CARDSTOCK"))
	define("CTRLOGINS_CARDSTOCK", "301507");
if (!defined("CTRLOGINS_DEVCON"))
	define("CTRLOGINS_DEVCON", "301508");
if (!defined("CTRLOGINS_DNLOADS"))
	define("CTRLOGINS_DNLOADS", "301509");
if (!defined("CTRLOGINS_LOGVIEW"))
	define("CTRLOGINS_LOGVIEW", "30150A");
if (!defined("CTRLOGINS_REPORTS"))
	define("CTRLOGINS_REPORTS", "30150B");
if (!defined("CTRLOGINS_REVOKE"))
	define("CTRLOGINS_REVOKE", "30150C");
if (!defined("CTRLOGINS_ROLEEDIT"))
	define("CTRLOGINS_ROLEEDIT", "30150D");
if (!defined("CTRLOGINS_STATUS"))
	define("CTRLOGINS_STATUS", "30150E");
if (!defined("CTRLOGINS_USER"))
	define("CTRLOGINS_USER", "30150F");
if (!defined("CTRLOGINS_EMCONFIG"))
	define("CTRLOGINS_EMCONFIG", "301510");
if (!defined("CTRLOGINS_EMSTAT"))
	define("CTRLOGINS_EMSTAT", "301511");
if (!defined("CTRLOGINS_CRED"))
	define("CTRLOGINS_CRED", "301512");
if (!defined("CTRLOGINS_CHKMNGR"))
	define("CTRLOGINS_CHKMNGR", "301513");
if (!defined("CTRLOGINS_ACCESSMON"))
	define("CTRLOGINS_ACCESSMON", "301514");
if (!defined("CTRLOGINS_ACCESSGUARD"))
	define("CTRLOGINS_ACCESSGUARD", "301515");
if (!defined("CTRLOGINS_ISMSIMPORT"))
	define("CTRLOGINS_ISMSIMPORT", "300516");
if (!defined("CTRLOGINS_DASHBOARD"))
	define("CTRLOGINS_DASHBOARD", "30151A");
if (!defined("CTRLOGINS_IDMSIMPORT"))
	define("CTRLOGINS_IDMSIMPORT", "30051C");
if (!defined("CTRLOGINS_FILEUPLOAD"))
	define("CTRLOGINS_FILEUPLOAD", "30051D");
if (!defined("CTRLOGINS_ACCESSADMIN"))
	define("CTRLOGINS_ACCESSADMIN", "30151E");
if (!defined("CTRLOGINS_ACCESSCONFIG"))
	define("CTRLOGINS_ACCESSCONFIG", "30151F");
if (!defined("CTRLOGINS_ACCESSREPORTS"))
	define("CTRLOGINS_ACCESSREPORTS", "301520");
if (!defined("CTRLOGINS_REPLDASHBOARD"))
	define("CTRLOGINS_REPLDASHBOARD", "301521");
if (!defined("CTRLOGINS_XDSPDASHBOARD"))
	define("CTRLOGINS_XDSPDASHBOARD", "301522");
if (!defined("CTRLOGINS_XCR"))
	define("CTRLOGINS_XCR", "301531");
if (!defined("CTRLOGINS_XIO"))
	define("CTRLOGINS_XIO", "301532");
	
if (!defined("CTRLOGINS_MOB_CARDQUERY"))
	define("CTRLOGINS_MOB_CARDQUERY", "301540");
if (!defined("CTRLOGINS_MOB_ACCESSWATCH"))
	define("CTRLOGINS_MOB_ACCESSWATCH", "301541");

if (!defined("CTRLOGINS_ACCESSDEV"))
	define("CTRLOGINS_ACCESSDEV", "301542");

if (!defined("CTRLOGINS_HELPDESKADMIN"))
	define("CTRLOGINS_HELPDESKADMIN", "301543");

// The AGENCY string constant - used to populate the agency system object when a new user is created.
if (!defined("AGENCY"))
	define("AGENCY", "Department of Justice");
	
if (!defined("PAGE_TITLE"))
	define("PAGE_TITLE", "Authentx Credential Management System");

if (!isset($listbase))
	$listbase = "/authentx/app/https/".$site."/applists";

if (!isset($xrestrictaccess))
	$xrestrictaccess = true;

// Switches on the restriction to access sites only via the portal.
if (!isset($xportlavector))
	$xportalvector = true;
	
// This flag will direct the core to use encryption/decryption on the flagged elements
if (!isset($xcrypt_on))
	$xcrypt_on = true;
// The domain key to use for all element crypto operations using libxcrypt
if (!defined("XCRYPT_DOMAIN"))
	define("XCRYPT_DOMAIN", "0010555334313030000000000002000B");
// This flag will direct the core to perform searches on encrypted SSN values
if (!isset($xcrypt_search))
	$xcrypt_search = true;
	
// Set this to true to allow this server to request records from other servers to import
if (!isset($_enableimportrequest))
	$_enableimportrequest = false;
	
// Set this to true to allow this server to respond with export records to remote requests
if (!isset($_enableexportresponse))
	$_enableexportresponse = false;

// Set this to true to allow the server to respond to syncrep requests
if (!isset($_enablesyncrepresponse))
	$_enablesyncrepresponse = false;

// Set this to true to allow the server to sync with remote servers
if (!isset($_enablesyncreprequest))
	$_enablesyncreprequest = false;

// Set this to allow default permissions to be assigned to new token records
if(!isset($_enabledefaultpermit))
    $_enabledefaultpermit = true;

//Allowable token types for SCL
if (!isset($_allowedtokens))
	// $_allowedtokens = array();
    $_allowedtokens = array();

// dn userid suffix base on device type
$uid_suffix = array(
	"default" => "DAC", 
	"dac" => "DAC", 
	"yubikey" => "DAC2", 
);

// Filter to remove unallowed characters from string
if (!defined("ILLEGAL_CHARS_FILTER"))
	define("ILLEGAL_CHARS_FILTER", "/[\\/+;<>\"]/");

// Number sets from the database
if (!defined("AUTHENTXEDIPI"))
	define("AUTHENTXEDIPI", "00000000100000007000000000010015");
if (!defined("EDIPREFIX"))
	define("EDIPREFIX", "1");

if (!defined("AUTHENTXCREQNUM"))
	define("AUTHENTXCREQNUM", "99010000000000000000000000000002");

if (!defined("AUTHENTXXSYNCNUM"))
	define("AUTHENTXXSYNCNUM", "99010000000000000000000000000004");

if (!defined("AUTHENTXCID"))
	define("AUTHENTXCID", "00000000100000007000000000010011");
if (!defined("AUTHENTXEID"))
	define("AUTHENTXEID", "00000000100000007000000000010012");
if (!defined("AUTHENTXPID"))
	define("AUTHENTXPID", "00000000100000007000000000010013");
if (!defined("ACCESSPID"))
	define("ACCESSPID", "00000000100000007000000000010013");
if (!defined("TOKENCID"))
	define("TOKENCID", "00000000100000007000000000010014");

if (!defined("PREFIX_LOGIN"))
	define("PREFIX_LOGIN", "@@@");

// password rules
if (!defined("PWMINDIFFS"))
	define ("PWMINDIFFS", 0);
if (!defined("PWMINLENGTH"))
	define ("PWMINLENGTH", 8);
if (!defined("PWMINDIGITS"))
	define ("PWMINDIGITS", 1);
if (!defined("PWMINUCASE"))
	define ("PWMINUCASE", 1);
if (!defined("PWMINLCASE"))
	define ("PWMINLCASE", 1);
if (!defined("PWMINOTHER"))
	define ("PWMINOTHER", 1);
if (!defined("PWMAXATTEMPTS"))
	define ("PWMAXATTEMPTS", 3);
if (!defined("PWEXPIRYDAYS"))
	define ("PWEXPIRYDAYS", 90);
if (!defined("PWHISTORY"))
	define ("PWHISTORY", 5);
if (!defined("PWCHANGEFORM"))
	define ("PWCHANGEFORM", "../pwchange/frm-pwchange.html");
// Set to true to lock a user out when reaching the max passwd retries
if (!defined("PWLOCKOUT"))
	define ("PWLOCKOUT", false);

if (!defined("SESSION_TIMEOUT"))
	define("SESSION_TIMEOUT", 900);
if (!defined("SESSION_TIMEOUT_GRACE"))
	define("SESSION_TIMEOUT_GRACE", 120);
if (!defined("AJAX_SESSIONREFRESH_ENABLE"))
	define("AJAX_SESSIONREFRESH_ENABLE", true);
if (!defined("AJAX_SESSIONREFRESH_SERVICE"))
	define("AJAX_SESSIONREFRESH_SERVICE", "../appcore/xsvc-sessionrefresh.xas");

if (!defined("AJAX_QUEUE_ENABLE"))
        define("AJAX_QUEUE_ENABLE", true);
if (!defined("AJAX_QUEUE_STATUS_SERVICE"))
        define("AJAX_QUEUE_STATUS_SERVICE", "xsvc-sripqueuestatus.xas");

// the current time zone relative to GMT
if (!defined("DATE_TIMEZONE"))
	define ("DATE_TIMEZONE", "US/Eastern");

// The SMTP mail server settings
if (!defined("MAILER"))
	define("MAILER", "qmail");
	
if (!defined("MAIL_FROM"))
	define("MAIL_FROM", "authentx@xtec.com");
	
if (!defined("SMTP_SERVER"))
	define ("SMTP_SERVER", "66.165.167.60");
	
if (!defined("SMTP_PORT"))
	define ("SMTP_PORT", 25);

if (!defined("SMTP_AUTH"))
	define ("SMTP_AUTH", true);
	
if (!defined("SMTP_AUTHUSER"))
	define ("SMTP_AUTHUSER", "authuser");
	
if (!defined("SMTP_AUTHPASSWD"))
	define ("SMTP_AUTHPASSWD", "authpassword");
	

// application role configurator: sites that can be configured
$arc_sitelist = array (
"accessadmin",
"accessconfig",
"accessguard",
"accessdev",
"auxiliary",
"accessmonitor",
"accessreports",
"admin",
"cardstock",
"clearanceadmin",
"cm",
"dashboard",
"devcon",
"disclaimer",
"downloads",
"emailconfig",
"emconfig",
"emstat",
"idmsimport",
"inventory",
"listmanager",
"logview",
"reports",
"reportsbuilder",
"revoke",
"roleeditor",
"siteeditor",
"srip",
"sripmanagement",
"sripqueuemanager",
"status",
"upload",
"issologs",
"webenrollment",
"rpatest",
"npetest",
);

// Moved wflow related configs here
include("config-wflow.php");

/*
// Workflow types defined for this application along with process names for each workflow.
// Each workflow type is a key to a list of process object ID's that are associated with it.
// All workflows contain HSPD12 common object info, so these do not have to be identified here.
$workflow_types = array(
"piv" => array("sponsor", "fpcap", "doccap", "photocap", "ichk", "print", "encode", "ship", "activate", "token", "hspd12"),
"fac" => array("sponsor", "fpcap", "doccap", "photocap", "ichk", "print", "encode", "ship", "activate", "token", "hspd12"),
"fac2" => array("sponsor", "fpcap", "doccap", "photocap", "ichk", "print", "encode", "ship", "activate", "token", "hspd12"),
"pivi" => array("sponsor", "fpcap", "doccap", "photocap", "iriscap", "sigcap", "ichk", "print", "encode", "ship", "activate", "token", "hspd12"),
"visitor" => array("sponsor", "fpcap", "doccap", "photocap", "ichk", "print", "encode", "ship", "activate", "token", "hspd12"),
"enrollment" => array("sponsor", "fpcap", "doccap", "photocap", "ichk", "hspd12"),
"dac" => array("sponsor", "print", "encode", "ship", "activate", "token"),
);

// The captions for each of the process objects for the processdiscovery service to use 
// when creating the XML output.
$workflow_captions = array(
"piv" => array(
"sponsor" => "Sponsor", 
"fpcap" => "Fingerprint Capture", 
"doccap" => "Document Capture", 
"photocap" => "Portrait Capture", 
"ichk" => "Impersonation Check", 
"print" => "Print", 
"encode" => "Encode", 
"ship" => "Ship", 
"activate" => "Activate", 
"token" => "Token",
),
"fac" => array(
"sponsor" => "Sponsor", 
"fpcap" => "Fingerprint Capture", 
"doccap" => "Document Capture", 
"photocap" => "Portrait Capture", 
"ichk" => "Impersonation Check", 
"print" => "Print", 
"encode" => "Encode", 
"ship" => "Ship", 
"activate" => "Activate", 
"token" => "Token",
),
"fac2" => array(
"sponsor" => "Sponsor", 
"fpcap" => "Fingerprint Capture", 
"doccap" => "Document Capture", 
"photocap" => "Portrait Capture", 
"ichk" => "Impersonation Check", 
"print" => "Print", 
"encode" => "Encode", 
"ship" => "Ship", 
"activate" => "Activate", 
"token" => "Token",
),
"pivi" => array(
"sponsor" => "Sponsor",
"fpcap" => "Fingerprint Capture",
"doccap" => "Document Capture",
"photocap" => "Portrait Capture",
"iriscap" => "Iris Capture",
"sigcap" => "Signature Capture",
"ichk" => "Impersonation Check",
"print" => "Print",
"encode" => "Encode",
"ship" => "Ship",
"activate" => "Activate",
"token" => "Token",
),
);
 */

// Set of acids and scids for the authentx credential. 
// A * before the prefix means it is a scid.
// If the data is unbound (ie 'x' for the source spec) then the acid/scid is not derived.
$application_acids = array(
"x|@@@|0x00",
"entity:ssn:entity|*SSN|0x01",
"entity:upi:entity|UPI|0x00",
"entity:ediregion:entity|EDR|0x00",
"entity:sdbregion:entity|SDR|0x01",
"entity:sdb:entity|SDB|0x01",
"entity:ediident:entity|*EDI|0x00",
"entity:lastname:entity|*LNM|0x00",
"entity:fsn:entity|*FSN|0x01",
"entity:email:entity|EML|0x00",
"entity:emplid=usaccess:email:employment|EML|0x00",
"token:crdnum:credential|BNO|0x00",
"credential:sysid=certid:sysval:xsystem|XCI|0x00",
);

$application_acid_composites = true;
$application_sdb_scid = true;

// Set of acids/scids for the token credential object.
// Same format as the authentx credential list.
$application_token_acids = array(
"token:crdnum:credential|BNO|0x00",
"token:gcoid=card:xblk:002070:gco|FAS|0x00",
"token:cuid:credential|CUI|0x00",
"token:gcoid=A000000308.5FC105:xblk:000022:gco|CSD|0x00",
"token:sysid=icccin:sysval:xsystem|ICN|0x00",
"token:gcoid=card:xblk:002069:gco|FAX|0x00",
"x|SWG|0x00",
"x|FID|0x00",
);

// Set of acid prefixes and XML tags for the import system to 
// associate GLOBALLY unique data with acid searches to double check
// that the user is not currently in the database
//$application_gu_acids = array(
//"xmltag" => "acidprefix",
//);

// Active directory transfer command
if (!isset($xadtxfrcmd))
	$xadtxfrcmd = "/authentx/app/https/usaccess/appcore/addnewuser2ad";

// The privacy info file
if (!defined("PRIVACY_INFO_FILE"))
	define('PRIVACY_INFO_FILE', '/authentx/app/https/usaccess/appconfig/privacy.html');

// primarily used by services that need to put entries in locations
$_default_belongsto = "region01";

// Decide whether to show all access transactions in reports or only those belonging to the:
// $ldap_entities branch defined above (if false will not show any transactions from users not belonging to the application, eg foreign cards)
$_report_txn_entities_showall = true;
// $ldap_permissions branch defined above (if false will only show transactions from those devices belonging to the application)
$_report_txn_devices_showall = false;

// Do not change these settings unless you really know what you are doing.
$_system_identifiers = array(
"xphp",
"xac",
"@@@sys",
"sys",
"usaccesssu",
"@@@usaccesssu",
);

// The server name to appear on forms to identify which server is being used.
if (!defined("SERVERID"))
	define("SERVERID", exec("uname -n"));
	
// Enable logging of record retrieval in the entity log
if (!defined("ENABLE_LOG_RETRIEVAL"))
	define("ENABLE_LOG_RETRIEVAL", true);

// Notification system tag dictionary
// An array indexed by tagname, contains parameters to enable the retrieval from ldap db.
// [0]=>basedn indicator, [1]=>subtree dn path, [2]=>attr, [3]=>readfilter, [4]=>gcotag, [5]=>objclass, [6]=>dateconversion string (as in php date function)
// Set to 'false' if they do not apply, these are used directly with getldapattr() from ldap class.
$_notification_tag_dictionary = array (
"agency" => array("uedn", "sysid=entitybranchname", "sysval", false, false, "xsystem", false),
"shiptoaddress" => array("uwfdn", "gcoid=token,procid=token", "xblk", false, "002055", "gco", false),
"estdeliverydate" => array("uwfdn", "procid=ship", "duedate", false, false, "process", "D jS F, Y"),
"upin" => array("uwfdn", "gcoid=token,procid=token", "upin", false, false, "gco", false),

);

// include the application id's
include_once("/authentx/core/http7/config-xem.php");
include_once("/authentx/core/http7/config-sql.php");

// The $_default_upn_type is used to govern creation of the UPN.  
// Possible values are: edipi | none.  NULL is the same as none.
if (!isset($_default_upn_type))
	$_default_upn_type = "none"; // ["none" | "edipi"]
if (!isset($_upn_domain))
	$_upn_domain = "xtec.com";

// prefix used with userid/serial-number for certificate request
$_req_userid_prefix = 'usaccess';

// AUTHENTX 5 CONFIGURATION:
include_once("config-emstat_dbconfig.php");

// NEW WORKFLOW AGREEMENT FORM REQUIREMENTS:
$_worflow_agreementform_required = array(
"piv" => "true",
"fac" => "false",
"fac2" => "false",
"pivi" => "false",
"visitor" => "false",
);

$_cardconfigs = <<<CARDCONFIG
<?xml version="1.0"?>
<configcard>
        <cardinfo name="oberthurv8.1" cardid="3033333038">
                <opcodes xmlname="defaultCMK" opcode="110401"></opcodes>
                <opcodes xmlname="defaultPIN" opcode="110601"></opcodes>
                <opcodes xmlname="defaultPUK" opcode="110701"></opcodes>
                <opcodes xmlname="defaultAdminPIN" opcode="110801"></opcodes>
	</cardinfo>
        <cardinfo name="oberthurUSBtokenKnownDiverKey" cardid="3033333939">
                <opcodes xmlname="defaultCMK" opcode="110401"></opcodes>
                <opcodes xmlname="defaultPIN" opcode="110601"></opcodes>
                <opcodes xmlname="defaultPUK" opcode="110701"></opcodes>
                <opcodes xmlname="defaultAdminPIN" opcode="110801"></opcodes>
        </cardinfo>
        <cardinfo name="yubikey" cardid="3035313031">
                <opcodes xmlname="defaultCMK" opcode="120400"></opcodes>
                <opcodes xmlname="defaultCMKAlgorid" opcode="140400"></opcodes>
                <opcodes xmlname="divCMKAlgorid" opcode="130400"></opcodes>
                <opcodes xmlname="defaultPIN" opcode="120600"></opcodes>
                <opcodes xmlname="defaultPUK" opcode="120700"></opcodes>
                <opcodes xmlname="divPUK" opcode="110701"></opcodes>
                <opcodes xmlname="divCMK" opcode="110401"></opcodes>
        </cardinfo>
</configcard>

CARDCONFIG;

//Icon for browser tabs
$cfg_stdicon = "../appcore/images/icon-authentx.ico";

//Site logo for webpages
$cfg_logoimgurl = "../appcore/images/usaccess_banner_no_fill.png";

//Site logo banner alternative text
$cfg_logoalt = "USAccess Logo";

//Gray Authenx logo for gray box
$cfg_authentxlogourl_white = "../appcore/images/authentX-logo-02.png";
$cfg_authentxlogourl_blue = "../appcore/images/authentX-logo-01.png";

$cfg_xteclogourl = "../appcore/images/XTecWhiteLogo.png";

// This database contains the srip tables 
$sripdb_user = "";
$sripdb_passwd = "";
$sripdb_database = "";
$sripdb_host = "";

// queue info
$queue_table_name = "sripqueue";

// capture info
$capture_table_name = "sripcapture";

// capture info
$operator_table_name = "sripoperator";

// srip sessionstat values
if(!defined("SESSION_OFFLINE"))
	define("SESSION_OFFLINE", 0);
	
if(!defined("SESSION_ONLINE"))
	define("SESSION_ONLINE", 1);
	
if(!defined("SESSION_WAITING"))
	define("SESSION_WAITING", 2);
	
if(!defined("SESSION_ACQUIRED"))
	define("SESSION_ACQUIRED", 3);
	
if(!defined("SESSION_COMPLETE"))
	define("SESSION_COMPLETE", 4);
	
if(!defined("SESSION_CANCEL_CLIENT"))
        define("SESSION_CANCEL_CLIENT", 6);

if(!defined("SESSION_CANCEL_OPERATOR"))
        define("SESSION_CANCEL_OPERATOR", 5);

// srip operation status values
if(!defined("OP_PENDING"))
    define("OP_PENDING", 0);

if(!defined("OP_RETRY"))
    define("OP_RETRY", 1);

if(!defined("OP_WAITING"))
    define("OP_WAITING", 2);

if(!defined("OP_COMPLETE"))
    define("OP_COMPLETE", 3);

if(!defined("OP_CANCEL"))
    define("OP_CANCEL", 4);

// srip stage to process mapping
$process = array(
    "1" => "photocap",
    "2" => "doccap",
    "3" => "fpcap",
);

// SRIP OPCMD 
// Format 
//    RESERVED | OPTION | ACTION | OPERATION
// 0x 00       | 00     | 00     | 00
$sripopcmd = array(
	// SRIP Operation
	"STANDBY" => "0x00000000",
	"PORTRAIT" => "0x00000001",
	"DOCUMENT" => "0x00000002",
	"FINGERPRINT" => "0x00000003",
	"SIGNATURE" => "0x00000004",
	"IRIS" => "0x00000005",
	// SRIP Action
	"SCAN" => "0x00000100",
	"VERIFY" => "0x00000200",
	// SRIP Capture Option
	"PASSPORT" => "0x00010000",
	"DRIVERSLIC" => "0x00020000",
	"USSOCSEC" => "0x00030000",
	"USBIRTHCERT" => "0x00040000",
	"EMPLOYMENT" => "0x00050000",
	"BIRTHCERTDOS" => "0x00060000",
	"CIZENSHIP" => "0x00070000",
	"NATURALIZATION" => "0x00080000",
	"FOREIGNPASSPORT" => "0x00090000",
	"RESIDENT" => "0x00110000",
	"TEMPRESIDENT" => "0x00120000",
	"REENTRY" => "0x00130000",
	"REFUGEE" => "0x00140000",
	"GOVID" => "0x00150000",
	"SCHOOLID" => "0x00160000",
	"VOTERS" => "0x00100000",
	"USMILID" => "0x00200000",
	"USMILDEPENDANT" => "0x00300000",
	"USCGMAC" => "0x00400000",
	"NATIVEAMERICAN" => "0x00500000",
	"SCHOOLRECORD" => "0x00600000",
	"MEDRECORD" => "0x00700000",
	"NURSERYRECORD" => "0x00800000",
	"USCITIZEDID" => "0x00900000",
	"TENTATIVELETTER" => "0x00170000",
	"OTHER" => "0x00B00000",
	"FLATS" => "0x00C00000",
	"ROLLS" => "0x00D00000",
	);

// capture data temp file base path
$srip_file_path = "/run/srip/";

$_constant_EMConfig = array(
        "SRIP01" => array (
                        "VIDEOurlDefault"=> "https://xtecvideoapp.azurewebsites.net/?groupId=",
                ),
);

/***********PENDING APPROVAL************/
// Max number of days card is valid
$card_exp = array(
    "piv" => "",
    "pivi" => "",
    "fac" => "",
    "fac2" => "",
	"dac" => "",
	"rpa" => "",
	"npe" => "",
);

foreach($card_exp as $tokentype => &$days)
{
	$carddefile = "/authentx/app/https/".$site."/appconfig/".$tokentype."/carddef-".$cfg_emplid."-".$tokentype.".xml";
	
	if(is_readable($carddefile))
	{
		$carddefxmlstr = file_get_contents($carddefile);

		$carddefxml = new SimpleXMLElement($carddefxmlstr);

		$found = false;

		foreach($carddefxml->dataTarget->targetRoot->copyitem as $copyitem)
		{
			foreach($copyitem->attributes() as $desc => $value)
			{
				if($desc == "name" && $value == "defaultexpdate")
					$found = true;

				if($found == true)
				{
					if($desc == "process")
					{
						$pos1 = strpos($value, ",,");
						$pos2 = strpos($value, "]");
						$expdays = substr($value, $pos1 + 2, $pos2 - $pos1 - 2);
						$days = $expdays;
						unset ($days);
						break(2);
					}
				}
			}
		}
	}
}
/********************************/

?>
