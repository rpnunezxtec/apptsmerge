<?PHP
// $Id:$
// The site ID string
$_siteid = "gsaappt";

// Site validation URL - required for emailing a link invitation
define("SITE_VALIDATEURL", "https://appt/authentx/appointments/validate.php");

// The banner heading for the top of the page next to the logo
if (!defined("BANNERHEADING1"))
	define("BANNERHEADING1", "APPOINTMENT");
if (!defined("BANNERHEADING2"))
	define("BANNERHEADING2", "ADMINISTRATION");

// The page a user is directed to if access is granted
$page_granted = "frm-appt.html";
$page_denied = "index.html";

$cfg_tabs = array(
		// array("href" => "frm-fidotokens-TEMPLATE.html", "onclick" => "return frmCheckDirty();", "label" => "FIDO Template", "tabindex" => "1", ),
		array("href" => "frm-appt.html", "onclick" => "return frmCheckDirty();", "label" => "Appts", "tabindex" => "1", ),
		array("href" => "frm-user.html", "onclick" => "return frmCheckDirty();", "label" => "Users", "tabindex" => "1", ),
		array("href" => "frm-sites.html", "onclick" => "return frmCheckDirty();", "label" => "Sites", "tabindex" => "1", ),
		array("href" => "frm-emws.html", "onclick" => "return frmCheckDirty();", "label" => "EMWS", "tabindex" => "1", ),
		array("href" => "frm-holmap.html", "onclick" => "return frmCheckDirty();", "label" => "Holidays", "tabindex" => "1", ),
		array("href" => "frm-reports.html", "onclick" => "return frmCheckDirty();", "label" => "Reports", "tabindex" => "1", ),
		array("href" => "frm-userinvite.html", "onclick" => "return frmCheckDirty();", "label" => "Invite", "tabindex" => "1", ),
		array("href" => "frm-mailtmpl.html", "onclick" => "return frmCheckDirty();", "label" => "Templates", "tabindex" => "1", ),
		array("href" => "frm-fidotokens.html", "onclick" => "return frmCheckDirty();", "label" => "Passkeys", "tabindex" => "1", ),
		);

// Configuration for form rendering
$cfg_stdmeta = array(
		"http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\"", 
		"http-equiv=\"Pragma\" content=\"no-cache\"",
		"http-equiv=\"Cache-Control\" content=\"no-store,no-Cache\"",
		"name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"",
		);
$cfg_stdtitle = "Authentx Credential Management System";
$cfg_stdcss = array(
		"../appcore/css/mobilea5.css",
		// "../appcore/css/authentx.css",
		"../appcore/css/authentxappointment.css",
		);
		
$cfg_stdjscript = array(
		"../appcore/scripts/js-formstd.js",
		"../appcore/scripts/js-formext.js",
		"../appcore/scripts/js-sponsor.js",
		"../appcore/scripts/js-formdate.js",
		"../appcore/scripts/js-dropdownmenu.js",		
		);
		
// Pop-up buttons
$cfg_btn_popsubmit = array(
		"value" => "Submit",
		"class" => "popupbuttontxt",
		"type" => "submit",
		"title" => "Create login identity",
		"tabindex" => "50",
);

$cfg_btn_popcancel = array(
		"value" => "Cancel",
		"class" => "popupbuttontxt",
		"type" => "reset",
		"tabindex" => "51",
);

$upload_dir_infohelp = "../apphelp/";

// User dropdown		
// get info and help path first
$info_path = file_exists($upload_dir_infohelp.$_siteid."-info.html")? $upload_dir_infohelp.$_siteid."-info.html" : $upload_dir_infohelp."info.html";
$help_path = file_exists($upload_dir_infohelp.$_siteid."-help.html")? $upload_dir_infohelp.$_siteid."-help.html" : $upload_dir_infohelp."help.html";

$cfg_userdropdown = array(
		array("label" => "Info", "href" => "javascript: popupOpener('".$info_path."','info',400,600)", "imgurl" => "../appcore/images/info.png", "title" => "Open an information popup window", "tabindex" => "2", ),
		array("label" => "Help", "href" => "javascript: popupOpener('".$help_path."','help',400,600)", "imgurl" => "../appcore/images/help.png", "title" => "Open a help popup window", "tabindex" => "3", ),
		array("label" => "Logoff", "href" => "vec-logout.php", "onclick" => "return frmCheckDirty();", "imgurl" => "../appcore/images/logoff.png", "title" => "Log off the system", "tabindex" => "3", ),
		);

// the site heading string - makes it easier to change over the entire site.
define("SITEHEADING", "Appointments");

// gsanstration mode - Allows a gsa to be setup for a time period
// Set activate to false to disable the gsanstration mode.
define("gsa_ACTIVATE", false);
define("gsa_DATEEND", "20131001");

// Use AJAX to continuously update the appointment form view
define("AJAX_APPT_ENABLE", true);
define("AJAX_APPTSERVICE", "xsvc-appt.xas");
// refresh time in seconds, false for no refresh
$refresh_appt = 15;

// Use AJAX to continuously update the daily schedule view
define("AJAX_DSCHED_ENABLE", true);
define("AJAX_DSCHEDSERVICE", "xsvc-dsched.xas");
// refresh time in seconds, false for no refresh
$refresh_dsched = 30;

// database connection
$_db_host = "mysqldb";
$_db_database = "authentx_usaccess";
$_db_user = "root";
$_db_passwd = "usaccess";

define ("DB_GETMODE_NUM", 0);
define ("DB_GETMODE_ASSOC", 1);

// Recent log display controls
// If true, shows logs for everyone. If false, only shows logs for logged-in user.
$_logs_showall = true;
// Number of log lines to show
$_logs_limit = 200;

// Determines whether to allow missing email when scheduling an appointment on behalf of others.
// This results in a random userid and no email alerts etc
$_cfg_appt_noemail = true;
// Allow/deny empty component when booking appointment. False to make component mandatory.
$_cfg_appt_nocomponent = false;
// Allow/deny empty phone number when booking appointment. False to make phone number mandatory.
$_cfg_appt_nophone = false;
// Allow/deny empty reason when booking appointment. False to make appt reason mandatory.
$_cfg_appt_noreason = false;

// Allow access to site removal form.
// This is not available if sitesync is enabled
$_cfg_site_removal = false;

//*** Authentx5 site/workstation integration configuration
// Enable the consumer service integration to get site and emws details from authentx
// This makes it authoritative, local changes or creation are no longer 
// permitted to the fields coming from the authentx5 system.
$_axsitesync_enable = false;

// Time between consumer checks
$_sleeptime_apptsitesync = 600;

// Flag to enable token sorting 
$tokensorting = true;

$cfg_forms = array(
	"FIDO Tokens" => array(
		"elements" => array(
		),
		"table_title" => "Fido Tokens",
		"table_columns" => array(
			array("header" => "","value" => "More..","width" => "5%","type" => "link","href" => "javascript:popupOpener('prp-token.html?dn=<DN>&avc=<AVC>','viewtoken',400,600)","title" => "View additional information for this token",),
			array("header" => "Token ID","value" => "cid","width" => "30%","type" => "text",),
			array("header" => "Issue Date","value" => "issdate","width" => "18%","type" => "text",),
			array("header" => "Expiration Date","value" => "expdate","width" => "18%","type" => "text",),
			array("header" => "Token Type","value" => "tokenclass","width" => "14%","type" => "text",),
			array("header" => "Status","value" => "status","width" => "15%","type" => "text",),
		),
	),
);

// Provider URL and xticket 
define("AX_SITESYNCURL", "https://DBHOST/wapi/apptsync/sitesync");
define("AX_APPTXTICKET", "WAPI XTICKET");

// Map the authentx status values to appt status numbers. These must be lower case for comparison
$_apptsitestatus = array (
	0 => array("unavailable", "closed", "inactive", "!unavailable", "!decommissioned"),
	1 => array("available", "open", "active"),	
);

$_apptemwsstatus = array (
	0 => array("unavailable", "closed", "inactive", "!unavailable", "!decommissioned", "lock"),
	1 => array("available", "open", "active", "unlock", "none"),
);


//*** DB Cleanup configuration
// Parameters for the automated background service that keeps the appointments DB clean
// Enable the service
$_apptdbpurge_enable = false;
// Time between checks
$_sleeptime_apptdbpurge = 86400;

// Check for inactive users enable
$_apptdbpurge_inactiveuser_enable = true;
// Number of days of inactivity before the user is purged.
$_apptdbpurge_inactiveuser_days = 366;

// Check for unactivated users enable
$_apptdbpurge_unactivatedusers_enable = true;
// Number of days before activation is not possible and user is purged
$_apptdbpurge_unactivatedusers_days = 5;

// Check for old appointments
$_apptdbpurge_apptflush_enable = true;
// Days before an appointment becomes flushable
$_apptdbpurge_apptflush_days = 366;


// Status values
define("USTATUS_ACTIVE", 0x01);
define("USTATUS_INACTIVE", 0x02);
define("USTATUS_UNACTIVATED", 0x03);


// privilege bits
define("PRIV_LOGIN", 0x8000);
define("PRIV_SSTAT", 0x4000);
define("PRIV_WSASGN", 0x2000);
define("PRIV_SHOURS", 0x1000);
define("PRIV_SCREATE", 0x0800);
define("PRIV_WSCREATE", 0x0400);
define("PRIV_WSSTAT", 0x0200);
define("PRIV_HOLMAP", 0x0100);
define("PRIV_RPT", 0x0080);
define("PRIV_UCREATE", 0x0040);
define("PRIV_UROLES", 0x0020);
define("PRIV_UINVITE", 0x0010);
define("PRIV_APPTSCHED", 0x0008);
define("PRIV_APPTEDIT", 0x0004);
define("PRIV_SITEEDIT", 0x0002);
define("PRIV_APPT", 0x0001);

// tab permission bits
define("TAB_SPARE15", 0x8000);
define("TAB_U", 0x4000);
define("TAB_S", 0x2000);
define("TAB_WS", 0x1000);
define("TAB_HOL", 0x0800);
define("TAB_RPT", 0x0400);
define("TAB_INVITE", 0x0200);
define("TAB_MAILTMPL", 0x0100);
define("TAB_FIDOTKN", 0x0080);
define("TAB_SPARE6", 0x0040);
define("TAB_SPARE5", 0x0020);
define("TAB_SPARE4", 0x0010);
define("TAB_SPARE3", 0x0008);
define("TAB_SPARE2", 0x0004);
define("TAB_SPARE1", 0x0002);
define("TAB_SPARE0", 0x0001);

// Upload email user file functionality
$_allowemailupload = true;
$maxsize = 100000;
$upload_dir = "useruploads/";

// Enables the ability to have users self-register, instead of individual emails.
$_allowselfregister = true;
// If set to true, requires an email activation process by the user
$_selfregister_emailactivation = false;

// session defs
define("SESS_NAME", "authentxappts");
define("SESS_TIME", 1200);

// password rules
define("PW_MINLENGTH", 8);

// Appointment ceiling per user - set to 0 for unlimited appointments
define("MAXUSERAPPTS", 1);

// Number of divisions per timeslot
define("SLOTDIVISIONS", 4);

// slot division status code
define("DIVSTAT_UNAVAIL", 0);
define("DIVSTAT_BOOKED", 1);
define("DIVSTAT_VACANT", 2);
define("DIVSTAT_CONFLICT", 3);
define("DIVSTAT_PASTBOOKED", 4);
define("DIVSTAT_PASTVACANT", 5);
define("DIVSTAT_BLOCKOUTBOOKED", 6);
define("DIVSTAT_BLOCKOUTVACANT", 7);

// Site blockout maximum hours
define("SITEBLOCKOUT_MAX", 24);

// log transaction type identifiers
define ("ALOG_NEWAPPT", 0x0001);
define ("ALOG_DELETEAPPT", 0x0002);
define ("ALOG_ERRORAPPT", 0x0004);
define ("ALOG_NEWUSER", 0x0011);
define ("ALOG_EDITUSER", 0x0012);
define ("ALOG_ERRORUSER", 0x0013);
define ("ALOG_INVITEUSER", 0x0014);
define ("ALOG_UPLOADUSERFILE", 0x0015);
define ("ALOG_NEWSITE", 0x0021);
define ("ALOG_EDITSITE", 0x0022);
define ("ALOG_STATUSSITE", 0x0023);
define ("ALOG_ERRORSITE", 0x0024);
define ("ALOG_NEWWS", 0x0031);
define ("ALOG_EDITWS", 0x0032);
define ("ALOG_STATUSWS", 0x0033);
define ("ALOG_ERRORWS", 0x0034);
define ("ALOG_HOLMAP", 0x0041);
define ("ALOG_OKVALIDATE", 0x0051);
define ("ALOG_ERRORVALIDATE", 0x0052);
define ("ALOG_OKLOGIN", 0x0053);
define ("ALOG_ERRORLOGIN", 0x0054);
define ("ALOG_MAILCONFIRM", 0x0061);
define ("ALOG_MAILCANCEL", 0x0062);
define ("ALOG_MAILPASSWD", 0x0063);
define ("ALOG_EDITTEMPLATE", 0x0071);

// Mail Templates in DB - this is the template name column value
define("MTDB_BOOKING", "booking");
define("MTDB_CANCEL", "cancel");
define("MTDB_CONFIRM", "confirm");
define("MTDB_INVITE", "invite");
define("MTDB_REGISTER", "register");
define("MTDB_REMINDER", "reminder");

// The email template tags
define ("ET_VURL", "%vurl%");
define ("ET_APPTDETAIL", "%apptdetail%");
define ("ET_ADMINNAME", "%adminname%");

// When to send emails
$_apptemail_confirm = true;		// send an email to confirm the creation of an appointment
$_apptemail_cancel = true;		// send an email to confirm the cancellation of an appointment
$_apptemail_reminder = true;	// send an email reminder automatically 24 hours prior.
$_apptemail_ical = true;		// Add the ical event notification to the email

// Email settings
define("MAILER", "qmail");
define("MAIL_FROM", "authentx@xtec.com");
define("SMTP_SERVER", "mail.xtec.com");
define("SMTP_PORT", 25);
define("SMTP_AUTH", true);
define("SMTP_AUTHUSER", "authentx@xtec.com");
define("SMTP_AUTHPASSWD", "ihesheyoelella");

// Display on centers page
define("CENTERDISPLAY_OFF", 0);
define("CENTERDISPLAY_ON", 1);

// *** The appointment reminder service config settings
// Time (hh:mm) for server to start the reminder check
// This is the local server time in 24 hour format and is usually set to midnight
$reminder_check_time = "00:00";

// *** LISTS ***
// The reports - like a list file
$listreportset = array (
array("rpt-logs.php", "Log Reports"),
array("rpt-apptdensity.php", "Appointment Density"),
array("rpt-apptvolume.php", "Weekly Appointment Volume"),
);

// The log type list
$listlogtype = array (
array("All", "All"),
array(ALOG_NEWAPPT, "New appointment"),
array(ALOG_DELETEAPPT, "Delete appointment"),
array(ALOG_ERRORAPPT, "Appointment error"),
array(ALOG_NEWUSER, "New user"),
array(ALOG_EDITUSER, "Edit user"),
array(ALOG_ERRORUSER, "User error"),
array(ALOG_INVITEUSER, "Invite user"),
array(ALOG_UPLOADUSERFILE, "Upload User email file"),
array(ALOG_NEWSITE, "New site"),
array(ALOG_EDITSITE, "Edit site"),
array(ALOG_STATUSSITE, "Change site status"),
array(ALOG_ERRORSITE, "Site error"),
array(ALOG_NEWWS, "New workstation"),
array(ALOG_EDITWS, "Edit workstation"),
array(ALOG_STATUSWS, "Change workstation status"),
array(ALOG_ERRORWS, "Workstation error"),
array(ALOG_HOLMAP, "Edit holiday map"),
array(ALOG_OKVALIDATE, "User validation successful"),
array(ALOG_ERRORVALIDATE, "User validation error"),
array(ALOG_OKLOGIN, "User login successful"),
array(ALOG_ERRORLOGIN, "User login error"),
array(ALOG_EDITTEMPLATE, "Edit mail template"),
);

// User status list
$listuserstatus = array (
array(USTATUS_ACTIVE, "Active"),		
array(USTATUS_INACTIVE, "Inactive"),		
array(USTATUS_UNACTIVATED, "Unactivated"),		
);

// The RPP list
$listrpp = array (
array(0, "Single page"),
array(25, "25 rows per page"),
array(50, "50 rows per page"),
array(100, "100 rows per page"),
);

// The refresh interval list
$listri = array (
array(0, "No refresh"),
array(15, "15 seconds"),
array(30, "30 seconds"),
array(60, "1 minute"),
array(120, "2 minutes"),
array(300, "5 minutes"),
array(600, "10 minutes"),
array(1800, "30 minutes"),
array(3600, "60 minutes"),
);

// Regions list
$listregions = array(
array("1","New England (Rgn 1)"),
array("2","Northeast/Carribean (Rgn 2)"),
array("3","Mid-Atlantic (Rgn 3)"),
array("4","Southeast (Rgn 4)"),
array("5","Great Lakes (Rgn 5)"),
array("6","The Heartland (Rgn 6)"),
array("7","Greater Southwest (Rgn 7)"),
array("8","Rocky Mountain (Rgn 8)"),
array("9","Pacific Rim (Rgn 9)"),
array("10","Northwest/Arctic (Rgn 10)"),
array("11","National Capital (Rgn 11)"),
);

// The US state list
$liststates = array(
array("-", " Outside US "),
array("AL", "Alabama"),
array("AK", "Alaska"),
array("AZ", "Arizona"),
array("AR", "Arkansas"),
array("CA", "California"),
array("CO", "Colorado"),
array("CT", "Connecticut"),
array("DE", "Delaware"),
array("DC", "District of Columbia"),
array("FL", "Florida"),
array("GA", "Georgia"),
array("HI", "Hawaii"),
array("ID", "Idaho"),
array("IL", "Illinois"),
array("IN", "Indiana"),
array("IA", "Iowa"),
array("KS", "Kansas"),
array("KY", "Kentucky"),
array("LA", "Louisiana"),
array("ME", "Maine"),
array("MD", "Maryland"),
array("MA", "Massachusetts"),
array("MI", "Michigan"),
array("MN", "Minnesota"),
array("MS", "Mississippi"),
array("MO", "Missouri"),
array("MT", "Montana"),
array("NE", "Nebraska"),
array("NV", "Nevada"),
array("NH", "New Hampshire"),
array("NJ", "New Jersey"),
array("NM", "New Mexico"),
array("NY", "New York"),
array("NC", "North Carolina"),
array("ND", "North Dakota"),
array("OH", "Ohio"),
array("OK", "Oklahoma"),
array("OR", "Oregon"),
array("PA", "Pennsylvania"),
array("RI", "Rhode Island"),
array("SC", "South Carolina"),
array("SD", "South Dakota"),
array("TN", "Tennessee"),
array("TX", "Texas"),
array("UT", "Utah"),
array("VT", "Vermont"),
array("VA", "Virginia"),
array("WA", "Washington"),
array("WV", "West Virginia"),
array("WI", "Wisconsin"),
array("WY", "Wyoming"),
);

// The countries list
$listcountries = array
(
array("Aruban","Aruban",),
array("Antigua and Barbudan","Antigua and Barbudan",),
array("Afghanistan","Afghanistan",),
array("Algeria","Algeria",),
array("Azerbaijan","Azerbaijan",),
array("Albania","Albania",),
array("Armenia","Armenia",),
array("Andorra","Andorra",),
array("Angola","Angola",),
array("American Samoa","American Samoa",),
array("Argentina","Argentina",),
array("Australia","Australia",),
array("Ashmore and Cartier Islands","Ashmore and Cartier Islands",),
array("Austria","Austria",),
array("Anguilla","Anguilla",),
array("Antarctica","Antarctica",),
array("Bahrain","Bahrain",),
array("Barbados","Barbados",),
array("Bermuda","Bermuda",),
array("Bahamas","Bahamas",),
array("Bangladesh","Bangladesh",),
array("Belize","Belize",),
array("Bosnia and Herzegovina","Bosnia and Herzegovina",),
array("Bolivia","Bolivia",),
array("Burma","Burma",),
array("Benin","Benin",),
array("Botswana","Botswana",),
array("Solomon Islands","Solomon Islands",),
array("Navassa Island","Navassa Island",),
array("Brazil","Brazil",),
array("Bassas Da India","Bassas Da India",),
array("Byelarus/Belarus","Byelarus/Belarus",),
array("Bhutan","Bhutan",),
array("Bulgaria","Bulgaria",),
array("Bouvet Island","Bouvet Island",),
array("Brunei","Brunei",),
array("Burundi","Burundi",),
array("Germany, Berlin","Germany, Berlin",),
array("Canada","Canada",),
array("Cambodia","Cambodia",),
array("Chad","Chad",),
array("Sri Lanka","Sri Lanka",),
array("Congo","Congo",),
array("Zaire","Zaire",),
array("China","China",),
array("Chile","Chile",),
array("Cayman Islands","Cayman Islands",),
array("Cocos (Keeling) Islands","Cocos (Keeling) Islands",),
array("Cameroon","Cameroon",),
array("Comoros","Comoros",),
array("Columbia","Columbia",),
array("Northern Mariana Islands","Northern Mariana Islands",),
array("Coral Sea Island","Coral Sea Island",),
array("Costa Rica","Costa Rica",),
array("Central African Republic","Central African Republic",),
array("Cuba","Cuba",),
array("Cape Verde","Cape Verde",),
array("Cook Islands","Cook Islands",),
array("Cyprus","Cyprus",),
array("Denmark","Denmark",),
array("Djibouti","Djibouti",),
array("Dominica","Dominica",),
array("Jarvis Island","Jarvis Island",),
array("Dominican Republic","Dominican Republic",),
array("Ecuador","Ecuador",),
array("Egypt","Egypt",),
array("Ireland","Ireland",),
array("Equatorial Guinea","Equatorial Guinea",),
array("Estonia","Estonia",),
array("El Salvador","El Salvador",),
array("Ethiopia","Ethiopia",),
array("Europa Island","Europa Island",),
array("Czech Republic","Czech Republic",),
array("French Guiana","French Guiana",),
array("Fiji","Fiji",),
array("Falkland Islands (Isles Malvinas)","Falkland Islands (Isles Malvinas)",),
array("Federated States of Micronesia","Federated States of Micronesia",),
array("Faroe Islands","Faroe Islands",),
array("French Polynesia","French Polynesia",),
array("Baker Island","Baker Island",),
array("France","France",),
array("French Southern and Antarctic Lands","French Southern and Antarctic Lands",),
array("The Gambia","The Gambia",),
array("Gabon","Gabon",),
array("Germany, Federal Republic of","Germany, Federal Republic of",),
array("Georgia","Georgia",),
array("Ghana","Ghana",),
array("Gibraltar","Gibraltar",),
array("Grenada","Grenada",),
array("Guernsey","Guernsey",),
array("Greenland","Greenland",),
array("Glorioso Islands","Glorioso Islands",),
array("Guadeloupe","Guadeloupe",),
array("Guam","Guam",),
array("Greece","Greece",),
array("Guatemala","Guatemala",),
array("Guinea","Guinea",),
array("Guyana","Guyana",),
array("Gaza Strip","Gaza Strip",),
array("Haiti","Haiti",),
array("Hong Kong","Hong Kong",),
array("Heard Island and McDonald Islands","Heard Island and McDonald Islands",),
array("Honduras","Honduras",),
array("Howland Island","Howland Island",),
array("Croatia","Croatia",),
array("Hungary","Hungary",),
array("Iceland","Iceland",),
array("Indonesia","Indonesia",),
array("Mann, Isle of","Mann, Isle of",),
array("India","India",),
array("British Indian Ocean Territory","British Indian Ocean Territory",),
array("Clipperton Island","Clipperton Island",),
array("United States Miscellaneous Pacific Islands","United States Miscellaneous Pacific Islands",),
array("Iran","Iran",),
array("Israel","Israel",),
array("Italy","Italy",),
array("Cote D'Ivoire","Cote D'Ivoire",),
array("Iraq","Iraq",),
array("Japan","Japan",),
array("Jersey","Jersey",),
array("Jamaica","Jamaica",),
array("Jan Mayen","Jan Mayen",),
array("Jordan","Jordan",),
array("Johnston Atoll","Johnston Atoll",),
array("Juan de Nova Island","Juan de Nova Island",),
array("Kenya","Kenya",),
array("Kyrgyzstan","Kyrgyzstan",),
array("Korea, gsacratic People's Republic of","Korea, gsacratic People's Republic of",),
array("Kingman Reef","Kingman Reef",),
array("Kiribati","Kiribati",),
array("Korea, Republic of","Korea, Republic of",),
array("Christmas Island","Christmas Island",),
array("Kuwait","Kuwait",),
array("Kazakhstan","Kazakhstan",),
array("Laos","Laos",),
array("Lebanon","Lebanon",),
array("Latvia","Latvia",),
array("Lithuania","Lithuania",),
array("Liberia","Liberia",),
array("Slovakia","Slovakia",),
array("Palmyra Atoll","Palmyra Atoll",),
array("Liechtenstein","Liechtenstein",),
array("Lesotho","Lesotho",),
array("Luxembourg","Luxembourg",),
array("Libya","Libya",),
array("Madagascar","Madagascar",),
array("Martinique","Martinique",),
array("Macau","Macau",),
array("Maldova","Maldova",),
array("Mayotte","Mayotte",),
array("Mongolia","Mongolia",),
array("Montserrat","Montserrat",),
array("Malawi","Malawi",),
array("Macedonia","Macedonia",),
array("Mali","Mali",),
array("Monaco","Monaco",),
array("Morocco","Morocco",),
array("Mauritius","Mauritius",),
array("Midway Islands","Midway Islands",),
array("Mauritania","Mauritania",),
array("Malta","Malta",),
array("Oman","Oman",),
array("Maldives","Maldives",),
array("Montenegro","Montenegro",),
array("Mexico","Mexico",),
array("Malaysia","Malaysia",),
array("Mozambique","Mozambique",),
array("New Caledonia","New Caledonia",),
array("Niue","Niue",),
array("Norfolk Island","Norfolk Island",),
array("Niger","Niger",),
array("Vanuatu","Vanuatu",),
array("Nigeria","Nigeria",),
array("Netherlands","Netherlands",),
array("Norway","Norway",),
array("Nepal","Nepal",),
array("Nauru","Nauru",),
array("Suriname","Suriname",),
array("Netherlands Antilles","Netherlands Antilles",),
array("Nicaragua","Nicaragua",),
array("New Zealand","New Zealand",),
array("Paraguay","Paraguay",),
array("Pitcairn Islands","Pitcairn Islands",),
array("Peru","Peru",),
array("Paracel Islands","Paracel Islands",),
array("Spratly Islands","Spratly Islands",),
array("Pakistan","Pakistan",),
array("Poland","Poland",),
array("Panama","Panama",),
array("Portugal","Portugal",),
array("Papua and New Guinea","Papua and New Guinea",),
array("Trust Territory of the Pacific Islands","Trust Territory of the Pacific Islands",),
array("Guinea-Bissau, Republic of","Guinea-Bissau, Republic of",),
array("Qatar","Qatar",),
array("Reunion Island","Reunion Island",),
array("Marshall Islands","Marshall Islands",),
array("Romania","Romania",),
array("Philippines","Philippines",),
array("Puerto Rico","Puerto Rico",),
array("Russia","Russia",),
array("Rwanda","Rwanda",),
array("Saudi Arabia","Saudi Arabia",),
array("St. Pierre and Miquelon","St. Pierre and Miquelon",),
array("St. Kitts and Nevis","St. Kitts and Nevis",),
array("Seychelles","Seychelles",),
array("South Africa","South Africa",),
array("Senegal","Senegal",),
array("St. Helena","St. Helena",),
array("Slovenia","Slovenia",),
array("Sierra Leone","Sierra Leone",),
array("San Marino","San Marino",),
array("Singapore","Singapore",),
array("Somalia","Somalia",),
array("Serbia","Serbia",),
array("St. Lucia","St. Lucia",),
array("Sudan","Sudan",),
array("Svalbard","Svalbard",),
array("Sweden","Sweden",),
array("South Georgia","South Georgia",),
array("Syria","Syria",),
array("Switzerland","Switzerland",),
array("United Arab Emirates","United Arab Emirates",),
array("Trinidad and Tobago","Trinidad and Tobago",),
array("Tromelin Island","Tromelin Island",),
array("Thailand","Thailand",),
array("Tajikistan","Tajikistan",),
array("Turks and Caicos Islands","Turks and Caicos Islands",),
array("Tokelau","Tokelau",),
array("Tonga","Tonga",),
array("Togo","Togo",),
array("Sao Tome and Principe","Sao Tome and Principe",),
array("Tunisia","Tunisia",),
array("Turkey","Turkey",),
array("Tuvalu","Tuvalu",),
array("Taiwan","Taiwan",),
array("Turkmenistan","Turkmenistan",),
array("Tanzania, United Republic of","Tanzania, United Republic of",),
array("Uganda","Uganda",),
array("United Kingdom","United Kingdom",),
array("Ukraine","Ukraine",),
array("US","United States",),
array("Burkina","Burkina",),
array("Uruguay","Uruguay",),
array("Uzbekistan","Uzbekistan",),
array("St. Vincent and the Grenadines","St. Vincent and the Grenadines",),
array("Venezuela","Venezuela",),
array("British Virgin Islands","British Virgin Islands",),
array("Vietnam","Vietnam",),
array("Virgin Islands of the United States","Virgin Islands of the United States",),
array("Vatican City","Vatican City",),
array("Namibia","Namibia",),
array("West Bank","West Bank",),
array("Wallis and Futuna","Wallis and Futuna",),
array("Western Sahara","Western Sahara",),
array("Wake Island","Wake Island",),
array("Western Samoa","Western Samoa",),
array("Swaziland","Swaziland",),
array("Yemen","Yemen",),
array("Yugoslavia","Yugoslavia",),
array("Zambia","Zambia",),
);

// The appointment reason list
$listapptrsn = array
(
array("","",),
array("New","New",),
array("Replaced-Affiliation","Replaced-Affiliation",),
array("Replaced-Clearance","Replaced-Clearance",),
array("Replaced-Damaged Inoperable","Replaced-Damaged Inoperable",),
array("Replaced-Expired","Replaced-Expired",),
array("Replaced-FERO","Replaced-FERO",),
array("Replaced-LEO","Replaced-LEO",),
array("Replaced-Name Changed","Replaced-Name Changed",),
array("Replaced-Stolen","Replaced-Stolen",),
array("PIN reset","PIN reset",),
array("Card Update-Maintenance","Card Update-Maintenance",),
array("Other","Other",),
array("New PIV-I Card","New PIV-I Card",),
array("PIV-I Card renewal","PIV-I Card renewal",),
);

// The timezone list - maps offset seconds to zone labels
$listtzone = array
(
array("-43200","-12:00",),
array("-39600","-11:00",),
array("-36000","-10:00",),
array("-32400","-09:00",),
array("-28800","-08:00",),
array("-25200","-07:00",),
array("-21600","-06:00",),
array("-18000","-05:00",),
array("-14400","-04:00",),
array("-10800","-03:00",),
array("-7200","-02:00",),
array("-3600","-01:00",),
array("0","+00:00",),
array("3600","+01:00",),
array("7200","+02:00",),
array("10800","+03:00",),
array("14400","+04:00",),
array("18000","+05:00",),
array("21600","+06:00",),
array("25200","+07:00",),
array("28800","+08:00",),
array("32400","+09:00",),
array("36000","+10:00",),
array("39600","+11:00",),
array("43200","+12:00",),
);

// The component list
$listcomponent = array
(
array("","",),
array("DHS HQ","DHS HQ",),
array("FEMA","FEMA",),
array("CBP","CBP",),
array("FLETC","FLETC",),
array("ICE","ICE",),
array("TSA","TSA",),
array("USCG","USCG",),
array("CIS","USCIS",),
array("USSS","USSS",),
array("Other_Federal_Agency","Other Federal Agency",),
);

// Site status list (not used yet)
$listsitestatus = array (
array("open","open during operating hours"),
array("closed","closed"),
array("!unavailable","unavailable"),
array("!decommissioned","decommissioned"),
);

// Site Type list
$listsitetype = array (
array("S","Shared"),
array("D","Dedicated"),		
);

// Site Activity list
$listsiteactivity = array
(
array("EIWS","Enrollment and Issuance"),
array("LAS","Activation Station"),
array("FP","Fingerprinting Station"),
);

// Simple yes/no as an integer
$listyesnoint = array
(
array(0, "no"),
array(1, "yes"),
);


?>
