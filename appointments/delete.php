<?PHP
// $Id: frm-tokens.html 214 2009-03-17 22:46:06Z atlas $

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-create.html";
$form_name = "Appts";
$tab_name = ucfirst($form_name);

include("config.php");
include("vec-clappointments.php");
include('../appcore/vec-clforms.php');
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappointments();
$myform = new authentxforms();

$userinfo = array();

$fullname = $_SESSION["authentxappts"]["user"]["uname"];
$namearray = explode(" ", $fullname);
$firstname = $namearray[0];

$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);

// validate the session
$sr = $myappt->validatesession($_siteid);
if ($sr !== true)
{
	print "<script type=\"text/javascript\">alert('".$sr."')</script>\n";
	$myappt->vectormeto($page_denied);
}

// Check user tab permissions
$tab_users = $myappt->checktabmask(TAB_U);
$tab_sites = $myappt->checktabmask(TAB_S);
$tab_ws = $myappt->checktabmask(TAB_WS);
$tab_holmaps = $myappt->checktabmask(TAB_HOL);
$tab_reports = $myappt->checktabmask(TAB_RPT);
$tab_invite = $myappt->checktabmask(TAB_INVITE);
$tab_mailtmpl = $myappt->checktabmask(TAB_MAILTMPL);
$tab_fidotokens = $myappt->checktabmask(TAB_FIDOTKN);

$ntabs = 1;
if ($tab_users)
	$ntabs++;
if ($tab_sites)
	$ntabs++;
if ($tab_ws)
	$ntabs++;
if ($tab_holmaps)
	$ntabs++;
if ($tab_reports)
	$ntabs++;
if ($tab_invite)
	$ntabs++;
if ($tab_mailtmpl)
	$ntabs++;
if ($tab_fidotokens)
	$ntabs++;

$priv_apptsched = $myappt->checkprivilege(PRIV_APPTSCHED);
$priv_apptedit = $myappt->checkprivilege(PRIV_APPTEDIT);

// Initially the weekstamp should not be included - this gets calculated when we get a siteid
$wkstamp = false;

// Initialize wizard step if not set
if (!isset($_SESSION['wizard_step'])) {
    $_SESSION['wizard_step'] = 1;
}

// Track the furthest step reached (for "completed" styling)
if (!isset($_SESSION['max_step_reached'])) {
    $_SESSION['max_step_reached'] = 1;
}

// Initialize wizard data storage
if (!isset($_SESSION['wizard_data'])) {
    $_SESSION['wizard_data'] = array();
}

// // ==================== POST REQUEST HANDLING (PRG Pattern) ====================
// // All POST handlers redirect to prevent resubmission on refresh

// // Handle action selection (Step 1 - enroll/activate/update)
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
//     $_SESSION['wizard_data']['action'] = $_POST['action'];
    
//     if (isset($_POST['next_step'])) {
//         $_SESSION['wizard_data']['next_step'] = $_POST['next_step'];
//         $_SESSION['wizard_step'] = intval($_POST['next_step']);
//         $_SESSION['max_step_reached'] = max($_SESSION['max_step_reached'], $_SESSION['wizard_step']);
//     }
// }


// Handle circle navigation
 for ($i = 1; $i <= 5; $i++) {
    if (isset($_POST["circle{$i}"])) {
        // Only allow going back or to completed steps
        if ($i <= $_SESSION['max_step_reached'] || $i <= $_SESSION['wizard_step']) {
            $_SESSION['wizard_step'] = $i;
            
            // Build redirect URL with current parameters
            $redirect_url = $_SERVER['PHP_SELF'];
            $params = array();
            
            // Preserve site selection
            if (isset($_POST['site'])) {
                $myappt->session_setvar("site", $_POST['site']);
                $params[] = 'site=' . urlencode($_POST['site']);
            } elseif ($myappt->session_getvar("site") !== false) {
                $params[] = 'site=' . urlencode($myappt->session_getvar("site"));
            }
            
            // Preserve week selection
            if (isset($_POST['wk']) && $_POST['wk'] !== '') {
                $params[] = 'wk=' . urlencode($_POST['wk']);
            }
        }
        break;
    }
}

// Handle "Next" button
if (isset($_POST['next_step'])) {
    // Save current step data to session
    $_SESSION['wizard_data'] = array_merge($_SESSION['wizard_data'], $_POST);
    
    // Save site selection
    if (isset($_POST["site"])) {
        $myappt->session_setvar("site", $_POST["site"]);
    }
    
    // Save week selection
    if (isset($_POST["wk"])) {
        $_SESSION['wizard_data']['wk'] = $_POST["wk"];
    }
    
    // Move to next step
    if ($_SESSION['wizard_step'] < 5) {
        $_SESSION['wizard_step']++;
        $_SESSION['max_step_reached'] = max($_SESSION['max_step_reached'], $_SESSION['wizard_step']);
    }
}


// Handle site selection submission (Step 2)
if (isset($_POST["submit_site"])) {
    if (isset($_POST["site"])) {
        $myappt->session_setvar("site", $_POST["site"]);
        $_SESSION['wizard_data']['site'] = $_POST["site"];
    }

	$siteid = $myappt->session_getvar("site");
	$uid_appt = $_POST["uid"];
	$slot_timestamp = $_POST["slot_timestamp"];
	$u_apptrsn = $_POST["arsn"];

    if (isset($_POST["wk"])) {
        $_SESSION['wizard_data']['wk'] = $_POST["wk"];
    }
	// Check availability first
	$slotdt = date("Y-m-d H:i:s", $slot_timestamp);
	$q_site = "select * from site where siteid='".$dbh->real_escape_string($siteid)."'";
	$s_site = $dbh->query($q_site);
	if ($s_site) {
		$r_site = $s_site->fetch_assoc();
		$s_site->free();
	}

	// calculate the timezone offset - automatically adjusted for DST
	$sitetimezone = $r_site["timezone"];
	if (($sitetimezone == "") || ($sitetimezone == NULL))
		$tzoneoffset = 0;
	else
	{
		$mytzone = new DateTimeZone($sitetimezone);
		$mydatetime = new DateTime("now", $mytzone);
		$tzoneoffset = $mytzone->getOffset($mydatetime);
	}

	if ($myappt->isslotavailable($dbh, $siteid, $slotdt, $r_site["slottime"], $tzoneoffset))
	{
		// Available - create the appointment entry, save and set appt_booked to true to show detail
		// Create a reference number using (uid.siteid.slotstamp.time)
		$t_now = time();
		$ar_hex = $myappt->session_createmac($uid.$siteid.$slotstamp.$t_now);
		$apptref_hex = substr($ar_hex, -9, 3)."-".substr($ar_hex, -6, 3)."-".substr($ar_hex, -3, 3);
						
		$q_book = "insert into appointment "
				. "\n set "
				. "\n uid='".$dbh->real_escape_string($uid_appt)."', "
				. "\n starttime='".$dbh->real_escape_string($slotdt)."', "
				. "\n apptref='".$dbh->real_escape_string($apptref_hex)."', "
				. "\n apptcreate='".date("Y-m-d H:i:s")."', "
				. "\n apptrsn='".$dbh->real_escape_string($u_apptrsn)."', "
				. "\n siteid='".$dbh->real_escape_string($siteid)."' "
				;
		$s_book = $dbh->query($q_book);
		$apptid = $dbh->insert_id;
		if ($s_book)
		{
			// log the appointment
			$logstring = "Appointment created for user ".$u_name." at ".$r_site["sitename"]." on ".date("Y-m-d H:i", $slotstamp);
			$myappt->createlogentry($dbh, $logstring, $uid, ALOG_NEWAPPT);
		}
		else
		{
			$logstring = "Appointment failed to create for user ".$u_name." at ".$r_site["sitename"]." on ".date("Y-m-d H:i", $slotstamp);
			$myappt->createlogentry($dbh, $logstring, $uid, ALOG_ERRORAPPT);
		}

		// Read the appointment detail to display
		$q_appt = "select * from appointment "
			. "\n left join site on site.siteid=appointment.siteid "
			. "\n where apptid='".$dbh->real_escape_string($apptid)."' "
			;
		$s_appt = $dbh->query($q_appt);
		if ($s_appt)
		{
			$n_appt = $s_appt->num_rows;
			if ($n_appt == 0)
			{
				$s_appt->free();
				$dbh->close();
				print "<script type=\"text/javascript\">alert('Appointment failed to save.')</script>\n";
				print "<script type=\"text/javascript\">window.close()</script>\n";
				die();
			}
			$r_appt = $s_appt->fetch_assoc();
			$s_appt->free();
		}
	}   
}

// Handle final submission
if (isset($_POST['submit_wizard'])) {
    // Merge final step data
    $_SESSION['wizard_data'] = array_merge($_SESSION['wizard_data'], $_POST);
    
    // Process the complete wizard data here
    $wizardData = $_SESSION['wizard_data'];
    
    // Store success message
    $_SESSION['success_message'] = 'Your appointment has been successfully scheduled!';
    
    // Clear wizard session data
    unset($_SESSION['wizard_step']);
    unset($_SESSION['wizard_data']);
    unset($_SESSION['max_step_reached']);
    
    // Redirect to success page
    header('Location: success.php');
    exit;
}

// Get current step from session
$currentStep = $_SESSION['wizard_step'];

// Handle step parameter from URL (e.g., when clicking vacant slot)
if (isset($_GET['step'])) {
    $requested_step = intval($_GET['step']);
    // Only allow moving to this step if it's valid and accessible
    if ($requested_step >= 1 && $requested_step <= 5 && $requested_step <= $_SESSION['max_step_reached'] + 1) {
        $_SESSION['wizard_step'] = $requested_step;
        $_SESSION['max_step_reached'] = max($_SESSION['max_step_reached'], $requested_step);
        $currentStep = $requested_step;
    }
}

// Handle slot timestamp from URL (when clicking vacant slot)
if (isset($_GET['st'])) {
    $slot_timestamp = intval($_GET['st']);
    $_SESSION['wizard_data']['slot_timestamp'] = $slot_timestamp;
}

// Handle other URL parameters for step 4
if (isset($_GET['uid'])) {
    $_SESSION['wizard_data']['uid'] = $_GET['uid'];
}
if (isset($_POST['avc'])) {
    $avc = $_POST['avc'];
}

// Find out what site (if any) has been selected
if (isset($_GET["site"]))
{
	$siteid = $_GET["site"];
	$myappt->session_setvar("site", $siteid);
}
else
	$siteid = false;
	
if ($myappt->session_getvar("site") !== false)
	$siteid = $myappt->session_getvar("site");

function getCircleClass($stepNumber, $currentStep, $maxStepReached) {
    if ($stepNumber == $currentStep) {
        return 'circle current'; // Current step gets special styling
    } elseif ($stepNumber < $currentStep) {
        return 'circle completed';
    } elseif ($stepNumber <= $maxStepReached) {
        return 'circle completed'; // User has been here before
    } else {
        return 'circle';
    }
}

function getBarClass($stepNumber, $currentStep, $maxStepReached) {
    // stepNumber represents the bar AFTER that step
    if ($stepNumber < $currentStep) {
        return 'bar bar-completed';
    } else {
        return 'bar';
    }
}

// get data from the database
if (!($dbh->connect_error))
{
	// site list - only those with > 0 emws and available should be included
	$q_s = "select * from site "
		. "\n where status>'0' "
		. "\n order by siteaddrstate, siteaddrcity, sitename "
		;
		
	$s_s = $dbh->query($q_s);
	if ($s_s)
	{
		$n_s = $s_s->num_rows;
		$list_sites = array();
		$jsite = array();
		$nas = 0;
		$srep = array("\n", "\r", "\t", "\\", "\"");
		
		while ($r_s = $s_s->fetch_assoc())
		{
			$sid = $r_s["siteid"];
			$sname = $r_s["sitename"];
			$s_region = trim($r_s["siteregion"]);
			$s_addr = trim(str_replace($srep, " ", trim($r_s["siteaddress"])));
			$s_city = trim($r_s["siteaddrcity"]);
			$s_state = trim($r_s["siteaddrstate"]);
			$s_activity = $r_s["siteactivity"];
			
			$q_aws = "select count(*) as wscount "
				. "\n from workstation "
				. "\n where siteid='".$sid."' "
				. "\n and status>'0' "
				;
			$s_aws = $dbh->query($q_aws);
			if ($s_aws)
			{
				$r_aws = $s_aws->fetch_assoc();
				if ($r_aws)
				{
					if ($r_aws["wscount"] > 0)
					{
						// Create a list of everything, for the initial case
						$list_sites[$nas][0] = $sid;
						$list_sites[$nas][1] = $s_state." : ".$s_city." : ".$sname." (".$s_addr.") - $s_activity";
						
						// Create the javascript population sets
						// Collated sets of sites by state and city
						$jsite[$s_state][$s_city][] = array($sid, $s_state." : ".$s_city." : ".$sname." (".$s_addr.") - ".$s_activity);
						
						$nas++;
					}
				}
				$s_aws->free();
			}
		}
		$s_s->free();
	}
	
	if ($siteid !== false)
	{
		// get the current site details (if selected)
		$q_ts = "select * from site "
			. "\n where siteid='".($dbh->real_escape_string($siteid))."' "
			;
		$s_ts = $dbh->query($q_ts);
		if ($s_ts)
		{
			$n_ts = $s_ts->num_rows;
			if ($n_ts > 0)
			{
				$r_ts = $s_ts->fetch_assoc();
				$sitename = $r_ts["sitename"];
				$siteaddress = str_replace($srep, " ", trim($r_ts["siteaddress"]." ".$r_ts["siteaddrcity"]." ".$r_ts["siteaddrstate"]));
				
				// calculate the timezone offset - automatically adjusted for DST
				$sitetimezone = $r_ts["timezone"];
				if (($sitetimezone == "") || ($sitetimezone == NULL))
					$sitezoneoffset = 0;
				else
				{
					$mytzone = new DateTimeZone($sitetimezone);
					$mydatetime = new DateTime("now", $mytzone);
					$sitezoneoffset = $mytzone->getOffset($mydatetime);
				}
				$currenttime = date("m/d/Y H:i:s", ($myappt->gmtime() + $sitezoneoffset));
	
				// Find out what week we are on - should be a timestamp for a Sunday
				if (isset($_GET["wk"]))
				{
					$wkstamp = $_GET["wk"];
	
					// check and sanitise it
					if ($wkstamp <= 0)
						$wkstamp = $myappt->gmtime() + $sitezoneoffset;
					if (is_nan($wkstamp))
						$wkstamp = $myappt->gmtime() + $sitezoneoffset;
							
					$wdate = getdate($wkstamp);
				}
				else
				{
					// set the week to the current week, adjusted for time zone
					$wdate = getdate(time() + $sitezoneoffset);
				}
	
				// Align to the previous Sunday
				$wdtstamp = $wdate[0];
				// 0=sun ... 6=sat
				$wday = $wdate["wday"];
				// calculate an offset to get back to Sunday
				$offset = $wday * 24 * 60 * 60;
				$suntstamp = $wdtstamp - $offset;
				$sundate = getdate($suntstamp);
		
				// calculate the week starting timestamp on Sunday at 00:00:00
				$wkstamp = mktime(0, 0, 0, $sundate["mon"], $sundate["mday"], $sundate["year"]);
				if ($sundate["year"] < 2037)
					$wkstamp_next = $wkstamp + (7*25*60*60); // allow for DST crossover
				else
					$wkstamp_next = $wkstamp;
				if ($sundate["year"] > 1970)
					$wkstamp_prev = $wkstamp - (7*24*60*60);
				else
					$wkstamp_prev = $wkstamp;
				
				// slot time is in minutes
				$slottime = $r_ts["slottime"];
				
				// Number of timeslots for the site (based on the day with the longest operational time)
				// Ignore NULL settings, which mean that the day is not in operation.
				if ($r_ts["startsun"] != NULL)
					$daystart = $r_ts["startsun"];
				elseif ($r_ts["startmon"] != NULL)
					$daystart = $r_ts["startmon"];
				elseif ($r_ts["starttue"] != NULL)
					$daystart = $r_ts["starttue"];
				elseif ($r_ts["startwed"] != NULL)
					$daystart = $r_ts["startwed"];
				elseif ($r_ts["startthu"] != NULL)
					$daystart = $r_ts["startthu"];
				elseif ($r_ts["startfri"] != NULL)
					$daystart = $r_ts["startfri"];
				elseif ($r_ts["startsat"] != NULL)
					$daystart = $r_ts["startsat"];
				else
					$daystart = "09:00:00";
				
				if ($r_ts["startmon"] != NULL)
				{
					if ($r_ts["startmon"] < $daystart)
						$daystart = $r_ts["startmon"];
				}
				if ($r_ts["starttue"] != NULL)
				{
					if ($r_ts["starttue"] < $daystart)
						$daystart = $r_ts["starttue"];
				}
				if ($r_ts["startwed"] != NULL)
				{
					if ($r_ts["startwed"] < $daystart)
						$daystart = $r_ts["startwed"];
				}
				if ($r_ts["startthu"] != NULL)
				{
					if ($r_ts["startthu"] < $daystart)
						$daystart = $r_ts["startthu"];
				}
				if ($r_ts["startfri"] != NULL)
				{
					if ($r_ts["startfri"] < $daystart)
						$daystart = $r_ts["startfri"];
				}
				if ($r_ts["startsat"] != NULL)
				{
					if ($r_ts["startsat"] < $daystart)
						$daystart = $r_ts["startsat"];
				}
				if ($r_ts["starthol"] != NULL)
				{
					if ($r_ts["starthol"] < $daystart)
						$daystart = $r_ts["starthol"];
				}
	
				// Day's end
				if ($r_ts["endsun"] != NULL)
					$dayend = $r_ts["endsun"];
				elseif ($r_ts["endmon"] != NULL)
					$dayend = $r_ts["endmon"];
				elseif ($r_ts["endtue"] != NULL)
					$dayend = $r_ts["endtue"];
				elseif ($r_ts["endwed"] != NULL)
					$dayend = $r_ts["endwed"];
				elseif ($r_ts["endthu"] != NULL)
					$dayend = $r_ts["endthu"];
				elseif ($r_ts["endfri"] != NULL)
					$dayend = $r_ts["endfri"];
				elseif ($r_ts["endsat"] != NULL)
					$dayend = $r_ts["endsat"];
				else
					$dayend = "17:00:00";
				
				if ($r_ts["endmon"] != NULL)
				{
					if ($r_ts["endmon"] > $dayend)
						$dayend = $r_ts["endmon"];
				}
				if ($r_ts["endtue"] != NULL)
				{
					if ($r_ts["endtue"] > $dayend)
						$dayend = $r_ts["endtue"];
				}
				if ($r_ts["endwed"] != NULL)
				{
					if ($r_ts["endwed"] > $dayend)
						$dayend = $r_ts["endwed"];
				}
				if ($r_ts["endthu"] != NULL)
				{
					if ($r_ts["endthu"] > $dayend)
						$dayend = $r_ts["endthu"];
				}
				if ($r_ts["endfri"] != NULL)
				{
					if ($r_ts["endfri"] > $dayend)
						$dayend = $r_ts["endfri"];
				}
				if ($r_ts["endsat"] != NULL)
				{
					if ($r_ts["endsat"] > $dayend)
						$dayend = $r_ts["endsat"];
				}
				if ($r_ts["endhol"] != NULL)
				{
					if ($r_ts["endhol"] > $dayend)
						$dayend = $r_ts["endhol"];
				}
	
				// create a stamp - the date used is the Sunday for the week being viewed
				$ds_h = substr($daystart, 0, 2);
				$ds_m = substr($daystart, 3, 2);
				$ds_s = substr($daystart, 6, 2);
				$daystart_stamp = mktime($ds_h, $ds_m, $ds_s, $sundate["mon"], $sundate["mday"], $sundate["year"]);
				
				$de_h = substr($dayend, 0, 2);
				$de_m = substr($dayend, 3, 2);
				$de_s = substr($dayend, 6, 2);
				$dayend_stamp = mktime($de_h, $de_m, $de_s, $sundate["mon"], $sundate["mday"], $sundate["year"]);
				
				if ($slottime > 0)
				{
					// slottime is in minutes
					$n_timeslots = intval(($dayend_stamp - $daystart_stamp)/($slottime*60)) + 1;
				
					// For each timeslot between $daystart_stamp and $dayend_stamp for each day,
					// get the array of resource allocations for that slot. 
					// Build a table for the entire week.
					$slottable = array();
					for ($i = 0; $i < $n_timeslots; $i++)
					{
						for ($d = 0; $d < 7; $d++)
						{
							// Timeslot start stamp: start on Sun start time for the week, 
							// add a day offset and a slot offset to get the start of the timeslot
							$wkdayslotstartstamp = ($daystart_stamp + ($d*24*60*60) + ($i*$slottime*60));
							$wkdate = date("Y-m-d H:i:s", $wkdayslotstartstamp);
							// get the timeslot availability map
							$slottable[$i][$d] = $myappt->getslotallocation($dbh, $siteid, $wkdate, $slottime, SLOTDIVISIONS, $sitezoneoffset);
						}
					}
				}
			}
			else
			{
				$siteid = false;
				$sitename = "";
				$siteaddress = "";
				$currenttime = "";
			}
			$s_ts->free();
		}
		else
		{
			$siteid = false;
			$sitename = "";
			$siteaddress = "";
			$currenttime = "";
		}
	}
}
else 
{
	$n_s = 0;
	$siteid = false;
	$sitename = "";
	$siteaddress = "";
	$currenttime = "";
}

// Get current user data for auto-fill
$r_current_user = array();


$current_uid = $myappt->session_getuuid();
$q_current_user = "select * from user where uid='".$dbh->real_escape_string($current_uid)."'";
$s_current_user = $dbh->query($q_current_user);
if ($s_current_user) {
	$r_current_user = $s_current_user->fetch_assoc();
	$s_current_user->free();

}

// Handle appointment booking submission (Step 4)
if (isset($_POST['next_step']) && $_POST['next_step'] == '4') {
    // Get the database connection
    if (!($dbh->connect_error)) {
        
        // Get form data
        $slot_timestamp = isset($_POST['slot_timestamp']) ? intval($_POST['slot_timestamp']) : false;
        $current_user_uid = $myappt->session_getuuid(); // Get the logged-in user's UID
        $u_name = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
        $u_email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $u_phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $component = isset($_POST['component']) ? trim($_POST['component']) : '';
        $u_apptrsn = isset($_POST['arsn']) ? trim($_POST['arsn']) : '';
        
        
        // Sanitize inputs
        $u_name = strip_tags($u_name);
        $u_email = filter_var($u_email, FILTER_SANITIZE_EMAIL);
        $u_phone = strip_tags($u_phone);
        $component = strip_tags($component);
        $u_apptrsn = strip_tags($u_apptrsn);
        
        // Get current logged-in user's email to check if booking for self
        $q_current = "select email from user where uid='".$dbh->real_escape_string($current_user_uid)."'";
        $s_current = $dbh->query($q_current);
        $is_booking_for_self = false;
        if ($s_current) {
            $r_current = $s_current->fetch_assoc();
            if (strcasecmp($r_current['email'], $u_email) == 0) {
                $is_booking_for_self = true;
            }
            $s_current->free();
        }
        
        // Get site details
        if ($siteid !== false) {
            $q_site = "select * from site where siteid='".$dbh->real_escape_string($siteid)."'";
            $s_site = $dbh->query($q_site);
            if ($s_site) {
                $r_site = $s_site->fetch_assoc();
                $s_site->free();
                
                // Calculate timezone offset
                $sitetimezone = $r_site["timezone"];
                if (($sitetimezone == "") || ($sitetimezone == NULL))
                    $sitezoneoffset = 0;
                else {
                    $mytzone = new DateTimeZone($sitetimezone);
                    $mydatetime = new DateTime("now", $mytzone);
                    $sitezoneoffset = $mytzone->getOffset($mydatetime);
                }
                
                // If booking for self, use current user's UID
                if ($is_booking_for_self) {
                    $uid_appt = $current_user_uid;
                    
                    // Update current user's info
                    $q_update = "update user set "
                        . "uname='".$dbh->real_escape_string($u_name)."', "
                        . "phone='".$dbh->real_escape_string($u_phone)."', "
                        . "component='".$dbh->real_escape_string($component)."' "
                        . "where uid='".$dbh->real_escape_string($uid_appt)."' limit 1";
                    $dbh->query($q_update);
                } else {
                    // Booking for someone else - check if user exists, if not create new user
                    $q_eu = "select * from user where email='".$dbh->real_escape_string($u_email)."'";
                    $s_eu = $dbh->query($q_eu);
                    if ($s_eu) {
                        $n_eu = $s_eu->num_rows;
                        if ($n_eu > 0) {
                            // Existing user
                            $r_eu = $s_eu->fetch_assoc();
                            $uid_appt = $r_eu["uid"];
                            $s_eu->free();
                            
                            // Update user info
                            $q_update = "update user set "
                                . "uname='".$dbh->real_escape_string($u_name)."', "
                                . "phone='".$dbh->real_escape_string($u_phone)."', "
                                . "component='".$dbh->real_escape_string($component)."' "
                                . "where uid='".$dbh->real_escape_string($uid_appt)."' limit 1";
                            $dbh->query($q_update);
                        } else {
                            // Create new user
                            $u_userid = $u_email;
                            $q_nu = "insert into user set "
                                . "userid='".$dbh->real_escape_string($u_userid)."', "
                                . "email='".$dbh->real_escape_string($u_email)."', "
                                . "uname='".$dbh->real_escape_string($u_name)."', "
                                . "phone='".$dbh->real_escape_string($u_phone)."', "
                                . "component='".$dbh->real_escape_string($component)."', "
                                . "status='".USTATUS_ACTIVE."', "
                                . "ucreate='".(gmdate("Y-m-d H:i:s"))."', "
                                . "privilege='1', "
                                . "tabmask='0'";
                            
                            $s_nu = $dbh->query($q_nu);
                            if ($s_nu) {
                                $uid_appt = $dbh->insert_id;
                                
                                // Create log entry
                                $logstring = "User ".$u_name." (".$uid_appt.") created.";
                                $myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_NEWUSER);
                            }
                            $s_eu->free();
                        }
                    }
                }
                
                // Check slot availability
                $slotdt = date("Y-m-d H:i:s", $slot_timestamp);
                
                if ($myappt->isslotavailable($dbh, $siteid, $slotdt, $r_site["slottime"], $sitezoneoffset)) {
                    // Create appointment reference
                    $t_now = time();
                    $ar_hex = $myappt->session_createmac($uid_appt.$siteid.$slot_timestamp.$t_now);
                    $apptref_hex = substr($ar_hex, -9, 3)."-".substr($ar_hex, -6, 3)."-".substr($ar_hex, -3, 3);
                    
                    // Book the appointment
                    $q_book = "insert into appointment set "
                        . "uid='".$dbh->real_escape_string($uid_appt)."', "
                        . "starttime='".$dbh->real_escape_string($slotdt)."', "
                        . "apptref='".$dbh->real_escape_string($apptref_hex)."', "
                        . "apptcreate='".date("Y-m-d H:i:s")."', "
                        . "apptrsn='".$dbh->real_escape_string($u_apptrsn)."', "
                        . "siteid='".$dbh->real_escape_string($siteid)."'";
                    
                    $s_book = $dbh->query($q_book);
                    
                    if ($s_book) {
                        $apptid = $dbh->insert_id;
                        
                        // Log the appointment
                        $logstring = "Appointment created for user ".$u_name." at ".$r_site["sitename"]." on ".date("Y-m-d H:i", $slot_timestamp);
                        $myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_NEWAPPT);
                        
                        // Store appointment details in session for step 5
                        $_SESSION['wizard_data']['apptid'] = $apptid;
                        $_SESSION['wizard_data']['apptref'] = $apptref_hex;
                        $_SESSION['wizard_data']['fullname'] = $u_name;
                        $_SESSION['wizard_data']['email'] = $u_email;
                        $_SESSION['wizard_data']['phone'] = $u_phone;
                        $_SESSION['wizard_data']['component'] = $component;
                        $_SESSION['wizard_data']['arsn'] = $u_apptrsn;
                        
                        // Move to step 5
                        $_SESSION['wizard_step'] = 5;
                        $_SESSION['max_step_reached'] = 5;
                        
                        //todo come check these headers
                        // Redirect to step 5 (confirmation)
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?step=5&site=' . urlencode($siteid));
                        exit();
                    } else {
                        $_SESSION['error_message'] = 'Failed to create appointment. Please try again.';
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?step=4&site=' . urlencode($siteid));
                        exit();
                    }
                } else {
                    // Slot no longer available
                    $_SESSION['error_message'] = 'This appointment slot is no longer available. Please select another time.';
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?step=3&site=' . urlencode($siteid));
                    exit();
                }
            }
        }
        
    } else {
        $_SESSION['error_message'] = 'Database connection error.';
        header('Location: ' . $_SERVER['PHP_SELF'] . '?step=4');
        exit();
    }
}
$dbh->close();

// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams["css"] = $cfg_stdcss;
$headparams["jscript_file"] = $cfg_stdjscript;

if (AJAX_SESSIONREFRESH_ENABLE === true)
{
	$headparams["jscript_file"][] = "../appcore/scripts/js-ajax_sessionrefresh.js";
	$headparams["jscript_local"][] = "xhrservice = '".AJAX_SESSIONREFRESH_SERVICE."';\n"
								. "refreshinterval='".(SESSION_TIMEOUT - SESSION_TIMEOUT_GRACE)."';\n"
								. "gracetime='".SESSION_TIMEOUT_GRACE."';\n"
								. "sessionTime='".SESSION_TIMEOUT."';\n";
}
$myform->frmrender_head($headparams);

$bodyparams = array();
if (AJAX_SESSIONREFRESH_ENABLE === true)
{
	$bodyparams["onload"][] = "startRefresh()";
	$bodyparams["onload"][] = "startSessionTimer()";
}

if (AJAX_APPT_ENABLE === true)
{
	print "<script language=\"javascript\" src=\"../appcore/scripts/js-ajax_appt.js\"></script>\n";
	print "<script language=\"javascript\">\n";
	print "xhrservice = '".AJAX_APPTSERVICE."'\n";
	if ($siteid !== false)
		print "site='".urlencode($siteid)."'\n";
	if ($wkstamp !== false)
		print "wk='".urlencode($wkstamp)."'\n";
	print "refreshinterval='".$refresh_appt."'\n";
	print "</script>\n";
}
if (count($jsite) > 0)
{
	// Script for the filter dropdowns
	print "<script language=\"javascript\">\n";
	print "var states=[];\n";
	print "var cities=[];\n";
	print "var sites=[];\n";

	$n = 0;
	foreach ($jsite as $state => $stateset)
	{
		print "states[".$n."]=\"".$state."\";\n";
		print "cities[".$n."]=[];\n";
		print "sites[".$n."]=[];\n";
		$m = 0;
		foreach ($stateset as $city => $cityset)
		{
			print "cities[".$n."][".$m."]=\"".$city."\";\n";
			print "sites[".$n."][".$m."]=[];\n";
			$j = 0;
			foreach ($cityset as $siteset)
			{
				print "sites[".$n."][".$m."][".$j."]=[\"".$siteset[0]."\",\"".$siteset[1]."\"];\n";
				$j++;
			}
			$m++;
		}
		$n++;
	}
	
	// js functions
?>
function f_init()
{
	var nst = states.length;
	var sel_st = document.getElementById("fstate");

	sel_st.length = 0;
	for (i = 0; i < nst; i++)
		sel_st.options[i] = new Option(states[i], states[i]);
	sel_st.options[nst] = new Option("--Select State--", "");
	sel_st.options[nst].selected = true;
}

function filterstate()
{
	var sel_st = document.getElementById("fstate");
	var sel_st_idx = sel_st.selectedIndex;
	var nc = cities[sel_st_idx].length;
	var sel_c = document.getElementById("fcity");
	var sel_s = document.getElementById("site");
	var ns = 0;
	var x = 0;
	
	sel_c.length = 0;
	sel_s.length = 0;
	for (i = 0; i < nc; i++)
	{
		sel_c.options[i] = new Option(cities[sel_st_idx][i], cities[sel_st_idx][i]);
		
		ns = sites[sel_st_idx][i].length;
		for (j = 0; j < ns; j++)
			sel_s.options[x++] = new Option(sites[sel_st_idx][i][j][1], sites[sel_st_idx][i][j][0]);
	}
	sel_c.options[nc] = new Option("--Select City--", "");
	sel_c.options[nc].selected = true;
}

function filtercity()
{
	var sel_st = document.getElementById("fstate");
	var sel_st_idx = sel_st.selectedIndex;
	var sel_c = document.getElementById("fcity");
	var sel_c_idx = sel_c.selectedIndex;
	var sel_s = document.getElementById("site");
	var ns = sites[sel_st_idx][sel_c_idx].length;
	
	sel_s.length = 0;
	for (i = 0; i < ns; i++)
		sel_s.options[i] = new Option(sites[sel_st_idx][sel_c_idx][i][1], sites[sel_st_idx][sel_c_idx][i][0]);
}

<?php 
	print "</script>\n";
}
if (AJAX_APPT_ENABLE === true)
{
	if ($refresh_appt !== false)
		print "<body onload='startRefresh();f_init()'>\n";
	else 
		print "<body onload='f_init()'>\n";
}
else 
	print "<body onload='f_init()'>\n";



$myform->frmrender_bodytag($bodyparams);

// The page container
print "<div class=\"main\">\n";

// The top heading section
$topparams = array();
$topparams["logoimgurl"]   = $cfg_logoimgurl;
$topparams["logoalt"]      = $cfg_logoalt;
$topparams["bannerheading1"]= BANNERHEADING1;
$topparams["bannerheading2"]= BANNERHEADING2;
$topparams["pageportal"] = $page_certportal;
$topparams["dropdown"]     = array_merge($cfg_userdropdown, $cfg_tabs);
$myform->frmrender_topbanner($topparams);

// Left side section
$asideparams = array();
$asideparams["tabs"] = $cfg_tabs;
$asideparams["tabon"] = $tab_name;
$asideparams["side"] = "aSide";
$myform->frmrender_side($asideparams);

// Table
$tableparams = array();
$tableparams["title"] = $cfg_forms[$form_name]["table_title"];
$tableparams["data"] = $tokenmatrix;
$tableparams["columns"] = $cfg_forms[$form_name]["table_columns"];

// Right side section
$bsideparams = array();
$bsideparams["side"] = "bSide";
$bsideparams["firstname"] = $firstname;
$bsideparams["userdropdown"] = $cfg_userdropdown;
$bsideparams["authentxlogoimgurl"] = $cfg_authentxlogourl;

// Footer
$footerparams = array();
$footerparams["poweredbylogo"] = $cfg_authentxlogourl_white;
$footerparams["serverid"] = SERVERID;
/*
$x = $mysession->getformvalue($form_name, "edipi");
if (isset($x[0]))
	$footerparams["edipi"] = $x[0];
$x = $mysession->getformvalue($form_name, "entstatus");
if (isset($x[0]))
	$footerparams["entstatus"] = $x[0];
*/


?>	


			

<div id="content">
	<div class="fullscreenscrollbox">
	<form action="<?php print $formfile?>" method="POST">
	<input type="hidden" id="currentStep" name="currentStep" value="<?php print $currentStep ?>" />
	<input type="hidden" name="avc" id="avc" value="<?php print htmlspecialchars($avc) ?>" />
    <input type="hidden" name="uid" id="uid" value="<?php print htmlspecialchars($myappt->session_getuuid()) ?>" />
    <input type="hidden" name="slot_timestamp" value="<?php print $slot_timestamp ?>" />
	<input type="hidden" name="wk" value="<?php print ($wkstamp === false ? '' : urlencode($wkstamp)) ?>" />

	<!-- Numbered Circles -->
	<div id="circles" class="progressbar">
		<div class="step-container">
			<button type="submit" class="<?php echo getCircleClass(1, $currentStep, $_SESSION['max_step_reached']); ?>" 
					name="circle1" value="1">1</button>
			<div class="step-label">Select Activity</div>
		</div>
		<div class="<?php echo getBarClass(1, $currentStep, $_SESSION['max_step_reached']); ?>"></div>
		
		<div class="step-container">
			<button type="submit" class="<?php echo getCircleClass(2, $currentStep, $_SESSION['max_step_reached']); ?>" 
					name="circle2" value="2" 
					<?php echo ($_SESSION['max_step_reached'] < 2) ? 'disabled' : ''; ?>>2</button>
			<div class="step-label">Select Sponsoring Agency & Site</div>
		</div>
		<div class="<?php echo getBarClass(2, $currentStep, $_SESSION['max_step_reached']); ?>"></div>
		
		<div class="step-container">
			<button type="submit" class="<?php echo getCircleClass(3, $currentStep, $_SESSION['max_step_reached']); ?>" 
					name="circle3" value="3"
					<?php echo ($_SESSION['max_step_reached'] < 3) ? 'disabled' : ''; ?>>3</button>
			<div class="step-label">Select Date & Time</div>
		</div>
		<div class="<?php echo getBarClass(3, $currentStep, $_SESSION['max_step_reached']); ?>"></div>
		
		<div class="step-container">
			<button type="submit" class="<?php echo getCircleClass(4, $currentStep, $_SESSION['max_step_reached']); ?>" 
					name="circle4" value="4"
					<?php echo ($_SESSION['max_step_reached'] < 4) ? 'disabled' : ''; ?>>4</button>
			<div class="step-label">Enter Contact Information</div>
		</div>
		<div class="<?php echo getBarClass(4, $currentStep, $_SESSION['max_step_reached']); ?>"></div>
		
		<div class="step-container">
			<button type="submit" class="<?php echo getCircleClass(5, $currentStep, $_SESSION['max_step_reached']); ?>" 
					name="circle5" value="5"
					<?php echo ($_SESSION['max_step_reached'] < 5) ? 'disabled' : ''; ?>>5</button>
			<div class="step-label">Schedule Appointment</div>
		</div>
	</div>
	<?php
	if($currentStep == 1)
	{
	?>
		<!-- Step 1: Select Activity -->	
		<div class="titletextwhite">Select activity.</div>
		<p style="text-align:center">Select an <b>Activity</b> from the options below</p>
		<div class="cards-scope">
			<div style="padding-bottom: 10%;" class="cards-grid">
				<!-- Card 1 -->
				<div class="card">
					<div class="card-media">
						<img
						src="../appcore/images/enrollbutton.png"
						alt="Create a new appointment"
						loading="lazy"
						width="1200" height="675"
						style="--zoom:1.18; --pos:center;"
						>
					</div>
					<div class="card-body">
						<div class="card-copy">
							<h5 class="card-title">Enroll Credential</h5>
							<p class="card-text">
								Schedule an appointment to complete your credential enrollment.
							</p>
						</div>
						<button class="inputbtn darkblue" type="submit" name="action" value="enroll">Enroll</button>
					</div>
				</div>

				<!-- Card 2 -->
				<div class="card">
					<div class="card-media">
						<img
						src="../appcore/images/activatecardbutton.png"
						alt="Create a new appointment"
						loading="lazy"
						width="1200" height="675"
						style="--zoom:1.18; --pos:center;"
						>
					</div>
					<div class="card-body">
						<div class="card-copy">
							<h5 class="card-title">Activate Credential</h5>
							<p class="card-text">
							Schedule an appointment to activate and pick up your new credential.
							</p>
						</div>
						<button class="inputbtn darkblue" type="submit" name="action" value="activate">Activate</button>
					</div>
				</div>

				<!-- Card 3 -->
				<div class="card">
					<div class="card-media">
						<img
						src="../appcore/images/updatecardbutton.png"
						alt="Create a new appointment"
						loading="lazy"
						width="1200" height="675"
						style="--zoom:1.18; --pos:center;"
						>
					</div>
					<div class="card-body">
						<div class="card-copy">
							<h5 class="card-title">Update Credential</h5>
							<p class="card-text">
							Schedule an appointment to update an existing credential or reset PIN.
							</p>
						</div>
						<button class="inputbtn darkblue" type="submit" name="action" value="update">Update</button>
					</div>
				</div>
			</div>
			<input type="hidden" name="next_step" value="1">
		</div>
	<?php
	}	

	// Step 2: Select Sponsoring Agency & Site
	else if($currentStep == 2)
	{
	?>
		<!-- Step 2: Select Sponsoring Agency & Site -->
		
		<div class="titletextwhite">Select Sponsoring Agency and Site</div>
		
		<p style="text-align:center">Utilize the fields below to select your <b>State</b>, <b>City</b> and <b>Site</b>. Then click the <b>Select Site</b> button to see the search results.</p>

		<div style="display:flex;flex-direction:column;align-items:center;gap:28px;padding:10px 0;">
			<div class="inputtitlerow3 inputtitlespacer2" style="display:flex;justify-content:center;align-items:flex-end;gap:32px;flex-wrap:nowrap">
				<div class="inputtitle2 inputtitlespacer5" style="display:flex;flex-direction:column;gap:6px;min-width:16em">
				<label for="fstate" style="text-align:center">Filter: State</label>
				<select name="fstate" id="fstate" style="width:16em" onChange="filterstate()"></select>
				</div>
				<div class="inputtitle4 inputtitlespacer5" style="display:flex;flex-direction:column;gap:6px;min-width:16em">
				<label for="fcity" style="text-align:center">Filter: City</label>
				<select name="fcity" id="fcity" style="width:16em" onChange="filtercity()"></select>
				</div>
			</div>

			<div class="inputtitlerow3 inputtitlespacer3" style="display:flex;justify-content:center;align-items:flex-end;gap:24px;flex-wrap:nowrap; padding-bottom:20%">
				<div class="inputtitle1 inputtitlespacer5" style="display:flex;flex-direction:column;gap:6px">
				<label for="site" style="text-align:center">Site Selection</label>
				<select name="site" id="site" style="width:25em">
					<?php
					for ($i = 0; $i < $nas; $i++) {
						if ($siteid !== false) {
						if ($list_sites[$i][0] == $siteid)
							print "<option selected value=\"".(htmlentities($list_sites[$i][0]))."\">".(htmlentities($list_sites[$i][1]))."</option>\n";
						else
							print "<option value=\"".(htmlentities($list_sites[$i][0]))."\">".(htmlentities($list_sites[$i][1]))."</option>\n";
						} else {
						print "<option value=\"".(htmlentities($list_sites[$i][0]))."\">".(htmlentities($list_sites[$i][1]))."</option>\n";
						}
					}
					?>
				</select>
				</div>

				<div class="inputtitlerow2 inputtitlespacer3" style="display:contents">
					<div class="inputtitle3 inputtitlespacer5" style="display:flex">
						<button class="inputbtn darkblue" type="submit" name="next_step" value="2">Select Site</button>
					</div>
				</div>
			</div>
		</div>
	<?php
	}	

	// Step 3: Select Date & Time
	else if($currentStep == 3)
	{
	?>
		<!-- Step 3: Select Date & Time -->

		<div class="titletextwhite">Select Date and Time</div>
		<p style="text-align:center"><b>Select a Date</b> and <b>Select a Time</b> for your Appointment. All available Appointment times are represented in Site Local time.</p>

		<div style="display:flex;flex-direction:column;align-items:center;gap:28px;padding:10px 0;">
			

			<?php
			// Build the calendar INSIDE #content so it appears above the footer
			if ($siteid !== false) 
			{
				if ($slottime > 0) 
				{
					


					
					print "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\n";
					print "<tr class=\"apptweekrow\">\n";
					print "<td width=\"100%\" align=\"middle\" class=\"apptweek\">\n";
					print $sitename;
					print "<br><span class=\"apptsiteaddress\">".$siteaddress."</span>\n";
					print "<br><span class=\"apptsitetime\">".$currenttime."</span>\n";
					print "</td>\n";
					print "</tr>\n";
					print "<tr class=\"apptweekrow\">\n";
					print "<td width=\"100%\" class=\"apptweek\">\n";
					print "<a href=\"".htmlentities($formfile)."?wk=".urlencode($wkstamp_prev)."&site=".urlencode($siteid)."\" title=\"Previous week\">"
						. "<img src=\"../appcore/images/appt_arrow_left.jpg\" width=\"27\" height=\"19\" border=\"0\">"
						. "</a> ".htmlentities(date("jS M Y", $wkstamp))
						. " <a href=\"".htmlentities($formfile)."?wk=".urlencode($wkstamp_next)."&site=".urlencode($siteid)."\" title=\"Next week\">"
						. "<img src=\"../appcore/images/appt_arrow_right.jpg\" width=\"27\" height=\"19\" border=\"0\">"
						. "</a>\n";
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";

					print "<tr><td valign=\"top\" align=\"center\">\n";
                    print "<div class=\"legend\" role=\"list\" aria-label=\"Appointment legend\">\n";

                    print "<span class=\"legend-item\" role=\"listitem\">\n";
                    print "<span class=\"legend-swatch unavail\" aria-hidden=\"true\"></span>\n";
                    print "<span>Unavailable</span>\n";
                    print "</span>\n";

                    print "<span class=\"legend-item\" role=\"listitem\">\n";
                    print "<span class=\"legend-swatch vacant\" aria-hidden=\"true\"></span>\n";
                    print "<span>Available</span>\n";
                    print "</span>\n";

                    print "<span class=\"legend-item\" role=\"listitem\">\n";
                    print "<span class=\"legend-swatch booked\" aria-hidden=\"true\"></span>\n";
                    print "<span>Booked</span>\n";
                    print "</span>\n";

                    print "<span class=\"legend-item\" role=\"listitem\">\n";
                    print "<span class=\"legend-swatch mybooking\" aria-hidden=\"true\"></span>\n";
                    print "<span>My Booking</span>\n";
                    print "</span>\n";

                    print "</div>\n";
                    print "</td></tr>\n";

					$slotwidth = 13;
					$divwidth = intval($slotwidth/SLOTDIVISIONS);
					$timewidth = 100 - 7*$slotwidth;

					print "<table width=\"100%\" class=\"apptmaintable\" cellspacing=\"0\" cellpadding=\"1\" border=\"1\">\n";
					print "<tr class=\"apptrows\">\n";
					print "<td class=\"apptdayname\" width=\"".$timewidth."%\">&nbsp;</td>\n";
					for ($d = 0; $d < 7; $d++) {
					$wkdayslotstartstamp = ($daystart_stamp + ($d*24*60*60));
					if ($priv_apptsched) {
						$mac = $myappt->session_createmac($wkdayslotstartstamp.$siteid);
						$schedurl = "pop-dailysched.html?siteid=".urlencode($siteid)
									."&datestamp=".urlencode($wkdayslotstartstamp)
									."&avc=".urlencode($mac);
						$wkdate = date("D M jS", $wkdayslotstartstamp);
						print "<td class=\"apptdayname\" width=\"".$slotwidth."%\" onmouseover=\"javascript:this.style.cursor='pointer';\" onclick=\"javascript:popupOpener('".$schedurl."','appsched',500,900);\">".$wkdate."</td>\n";
					} else {
						$wkdate = date("D M jS", $wkdayslotstartstamp);
						print "<td class=\"apptdayname\" width=\"".$slotwidth."%\">".$wkdate."</td>\n";
					}
					}
					print "</tr>\n";

					for ($i = 0; $i < $n_timeslots; $i++) {
					$slottimestamp = ($daystart_stamp + ($i*$slottime*60));
					$timestring = date("H:i", $slottimestamp);
					print "<tr class=\"apptrows\">\n";
					print "<td class=\"apptslotname\">".$timestring."</td>\n";

					for ($d = 0; $d < 7; $d++) {
						$d_slottimestamp = $slottimestamp+($d*24*60*60);
						print "<td>\n";
						print "<table width=\"100%\" class=\"apptslottable\" cellspacing=\"1\" cellpadding=\"0\" border=\"1\">\n";
						print "<tr>\n";
						for ($s = 0; $s < SLOTDIVISIONS; $s++) {
						$stat = $slottable[$i][$d][$s]["stat"];
						switch ($stat) {
							case DIVSTAT_UNAVAIL:
							$celltitle = "unavailable";
							$stclass = "unavail";
							$clickable = false;
							break;
							case DIVSTAT_BOOKED:
							$uid = $slottable[$i][$d][$s]["uid"];
							$apptid = $slottable[$i][$d][$s]["apptid"];
							$apptref = $slottable[$i][$d][$s]["apptref"];
							if ($myappt->session_getuuid() == $uid) {
								$celltitle = "my booking: ".$apptref;
								$stclass = "mybooking";
								$clickable = true;
								$mac = $myappt->session_createmac($d_slottimestamp.$uid.$apptid);
								$clickurl = "pop-booking.html?st=".urlencode($d_slottimestamp)
											."&uid=".urlencode($uid)
											."&apptid=".urlencode($apptid)
											."&avc=".urlencode($mac);
							} else {
								if ($priv_apptedit === true) {
								$celltitle = "booked: ".$apptref;
								$stclass = "booked";
								$clickable = true;
								$mac = $myappt->session_createmac($d_slottimestamp.$uid.$apptid);
								$clickurl = "pop-booking.html?st=".urlencode($d_slottimestamp)
											."&uid=".urlencode($uid)
											."&apptid=".urlencode($apptid)
											."&avc=".urlencode($mac);
								} else {
								$celltitle = "booked: ".$apptref;
								$stclass = "booked";
								$clickable = false;
								}
							}
							break;
							case DIVSTAT_VACANT:
								$celltitle = "vacant";
								$stclass = "vacant";
								$clickable = true;
								$uid = $myappt->session_getuuid();
								$mac = $myappt->session_createmac($d_slottimestamp.$uid.$siteid);
								$clickurl = htmlentities($formfile)."?st=".urlencode($d_slottimestamp)
											."&uid=".urlencode($uid)
											."&site=".urlencode($siteid)
											."&step=4"
											."&wk=".urlencode($wkstamp)
											."&avc=".urlencode($mac);
								break;
							case DIVSTAT_CONFLICT:
							$uid = $slottable[$i][$d][$s]["uid"];
							$apptid = $slottable[$i][$d][$s]["apptid"];
							$apptref = $slottable[$i][$d][$s]["apptref"];
							$celltitle = "conflict: ".$apptref;
							if ($myappt->session_getuuid() == $uid) {
								$stclass = "conflict";
								$clickable = true;
								$mac = $myappt->session_createmac($d_slottimestamp.$uid.$apptid);
								$clickurl = "pop-booking.html?st=".urlencode($d_slottimestamp)
											."&uid=".urlencode($uid)
											."&apptid=".urlencode($apptid)
											."&avc=".urlencode($mac);
							} else {
								$stclass = "conflict";
								$clickable = false;
							}
							break;
							case DIVSTAT_PASTBOOKED:
							$celltitle = "unavailable";
							$stclass = "pastbooked";
							$clickable = false;
							break;
							case DIVSTAT_PASTVACANT:
							case DIVSTAT_BLOCKOUTVACANT:
							$celltitle = "unavailable";
							$stclass = "pastvacant";
							$clickable = false;
							break;
							case DIVSTAT_BLOCKOUTBOOKED:
							$uid = $slottable[$i][$d][$s]["uid"];
							$apptid = $slottable[$i][$d][$s]["apptid"];
							$apptref = $slottable[$i][$d][$s]["apptref"];
							if ($myappt->session_getuuid() == $uid) {
								$celltitle = "my booking: ".$apptref;
								$stclass = "mybooking";
								$clickable = true;
								$mac = $myappt->session_createmac($d_slottimestamp.$uid.$apptid);
								$clickurl = "pop-booking.html?st=".urlencode($d_slottimestamp)
											."&uid=".urlencode($uid)
											."&apptid=".urlencode($apptid)
											."&avc=".urlencode($mac);
							} else {
								if ($priv_apptedit === true) {
								$celltitle = "booked: ".$apptref;
								$stclass = "pastbooked";
								$clickable = true;
								$mac = $myappt->session_createmac($d_slottimestamp.$uid.$apptid);
								$clickurl = "pop-booking.html?st=".urlencode($d_slottimestamp)
											."&uid=".urlencode($uid)
											."&apptid=".urlencode($apptid)
											."&avc=".urlencode($mac);
								} else {
								$celltitle = "booked: ".$apptref;
								$stclass = "pastbooked";
								$clickable = false;
								}
							}
							break;
							default:
							$celltitle = "unavailable";
							$stclass = "unavail";
							$clickable = false;
							break;
						}

						print "<td width=\"".$divwidth."\" class=\"".$stclass."\" title=\"".$celltitle."\" "
								.($clickable ? "onmouseover=\"javascript:this.style.cursor='pointer';\"" : "")
								.($clickable ? "onclick=\"javascript:window.location.href='".$clickurl."'\"" : "")
								.">&nbsp;</td>\n";
						}
						print "</tr>\n";
						print "</table>\n";
						print "</td>\n";
					}
					print "</tr>\n";
					}

					print "</table>\n";
					print "<p/>\n";
				} 
				else 
				{
					print "<span class=\"lblblktext\">Slot time value is not set for this site.</span>\n";
				}
			}
			?>
		</div>


	<?php
	}	

// Step 4: Enter Contact information
	else if($currentStep == 4)
	{
		// Get slot timestamp from session if available
    $slot_timestamp = isset($_SESSION['wizard_data']['slot_timestamp']) ? $_SESSION['wizard_data']['slot_timestamp'] : false;
    $uid = isset($_SESSION['wizard_data']['uid']) ? $_SESSION['wizard_data']['uid'] : $myappt->session_getuuid();
    $avc = isset($_SESSION['wizard_data']['avc']) ? $_SESSION['wizard_data']['avc'] : '';
    
    // Format date and time from slot timestamp
    $appt_date = '';
    $appt_time = '';
    if ($slot_timestamp !== false) {
        $appt_date = date("D jS M Y", $slot_timestamp);
        $appt_time = date("H:i", $slot_timestamp);
    }
    
    // Get site details if available
    $appt_site = '';
    $appt_address = '';
    $appt_state = '';
    $appt_country = '';
    
    if ($siteid !== false && isset($r_ts)) {
        $appt_site = $r_ts["sitename"];
        $appt_address = trim($r_ts["siteaddress"]." ".$r_ts["siteaddrcity"]);
        $appt_state = $r_ts["siteaddrstate"];
        $appt_country = $r_ts["siteaddrcountry"];
    }
	?>
		 <!-- Step 4: Enter Contact information -->

    <div class="titletextwhite">Enter Contact Information</div>

    <p style="text-align:center">Enter your <b>Contact Information</b> in the fields below.</p>

    <div style="display:flex;flex-direction:column;align-items:center;gap:28px;padding:10px 0;">
    <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:24px;margin:20px 0;">
        <!-- Date Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="adate" style="font-weight:600;color:#24292f;font-size:14px;">Date *</label>
            <input id="adate" type="text" size="36" maxlength="120" tabindex="10" name="adate" value="<?php print htmlspecialchars($appt_date) ?>" style="padding:8px 12px;border:1px solid #000;background:#fff;border-radius:0;font-size:14px;outline:none;" readonly>
        </div>

        <!-- Time Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="atime" style="font-weight:600;color:#24292f;font-size:14px;">Time *</label>
            <input id="atime" type="text" size="36" maxlength="60" tabindex="20" name="atime" value="<?php print htmlspecialchars($appt_time) ?>" style="padding:8px 12px;border:1px solid #000;background:#fff;border-radius:0;font-size:14px;outline:none;" readonly>
        </div>

        <!-- Reference Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="apptref" style="font-weight:600;color:#24292f;font-size:14px;">Reference</label>
            <input id="apptref" type="text" size="36" maxlength="40" tabindex="30" name="apptref" value="" style="padding:8px 12px;border:1px solid #000;background:#e8e8e8;border-radius:0;font-size:14px;outline:none;" readonly>
        </div>

        <!-- Full Name Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="fullname" style="font-weight:600;color:#24292f;font-size:14px;">Full Name *</label>
            <input id="fullname" type="text" size="36" maxlength="120" tabindex="40" name="fullname" value="" style="padding:8px 12px;border:1px solid #000;background:#fff;border-radius:0;font-size:14px;outline:none;">
        </div>

        <!-- Email Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="email" style="font-weight:600;color:#24292f;font-size:14px;">Email *</label>
            <input id="email" type="email" size="36" maxlength="120" tabindex="50" name="email" value="" style="padding:8px 12px;border:1px solid #000;background:#fff;border-radius:0;font-size:14px;outline:none;">
        </div>

        <!-- Component Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="component" style="font-weight:600;color:#24292f;font-size:14px;">Component</label>
            <select id="component" name="component" tabindex="60" style="padding:8px 12px;border:1px solid #000;background:#fff;border-radius:0;font-size:14px;outline:none;cursor:pointer;">
                <option value="">-- Select Component --</option>
                <?php
                if (isset($listcomponent) && is_array($listcomponent)) {
                    $listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
                    $rc = count($listcomponent);
                    for ($i = 0; $i < $rc; $i++) {
                        echo '<option value="' . htmlspecialchars($listcomponent[$i][0]) . '">' . htmlspecialchars($listcomponent[$i][1]) . '</option>';
                    }
                }
                ?>
            </select>
        </div>

        <!-- Phone Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="phone" style="font-weight:600;color:#24292f;font-size:14px;">Phone</label>
            <input id="phone" type="text" size="36" maxlength="40" tabindex="70" name="phone" value="" style="padding:8px 12px;border:1px solid #000;background:#fff;border-radius:0;font-size:14px;outline:none;">
        </div>

        <!-- Reason Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="arsn" style="font-weight:600;color:#24292f;font-size:14px;">Reason</label>
            <select id="arsn" name="arsn" tabindex="80" style="padding:8px 12px;border:1px solid #000;background:#fff;border-radius:0;font-size:14px;outline:none;cursor:pointer;">
                <option value="">-- Select Reason --</option>
                <?php
                if (isset($listapptrsn) && is_array($listapptrsn)) {
                    $listapptrsn = $myappt->sortlistarray($listapptrsn, 1, SORT_ASC, SORT_REGULAR);
                    $rc = count($listapptrsn);
                    for ($i = 0; $i < $rc; $i++) {
                        echo '<option value="' . htmlspecialchars($listapptrsn[$i][0]) . '">' . htmlspecialchars($listapptrsn[$i][1]) . '</option>';
                    }
                }
                ?>
            </select>
        </div>

        <!-- Site Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="asite" style="font-weight:600;color:#24292f;font-size:14px;">Site</label>
            <input id="asite" type="text" size="36" maxlength="120" tabindex="90" name="asite" value="<?php print htmlspecialchars($appt_site) ?>" style="padding:8px 12px;border:1px solid #000;background:#e8e8e8;border-radius:0;font-size:14px;outline:none;" readonly>
        </div>

        <!-- Address Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="address" style="font-weight:600;color:#24292f;font-size:14px;">Address</label>
            <input id="address" type="text" size="36" maxlength="120" tabindex="100" name="address" value="<?php print htmlspecialchars($appt_address) ?>" style="padding:8px 12px;border:1px solid #000;background:#e8e8e8;border-radius:0;font-size:14px;outline:none;" readonly>
        </div>

        <!-- State Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="state" style="font-weight:600;color:#24292f;font-size:14px;">State</label>
            <input id="state" type="text" size="36" maxlength="40" tabindex="110" name="state" value="<?php print htmlspecialchars($appt_state) ?>" style="padding:8px 12px;border:1px solid #000;background:#e8e8e8;border-radius:0;font-size:14px;outline:none;" readonly>
        </div>

        <!-- Country Field -->
        <div style="display:flex;flex-direction:column;gap:8px;">
            <label for="country" style="font-weight:600;color:#24292f;font-size:14px;">Country</label>
            <input id="country" type="text" size="36" maxlength="40" tabindex="120" name="country" value="<?php print htmlspecialchars($appt_country) ?>" style="padding:8px 12px;border:1px solid #000;background:#e8e8e8;border-radius:0;font-size:14px;outline:none;" readonly>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display:flex;align-items:center;gap:12px;margin:30px 0 10px 0;padding-top:20px;">
        <button style="width:200px; padding:10px 16px;" type="submit" name="next_step" class="inputbtn darkblue" value="5" tabindex="130">Book Appointment</button>
        <input style="width:200px; padding:10px 16px;" type="button" name="btn_copymydata" class="inputbtn darkblue" value="My Appointment" onclick="javascript:copymydata()" title="Copy my data into this form" tabindex="140" />
		<input type="hidden" name="submit_site" value="1" />
    </div>
    
    <div style="font-size:13px;color:#656d76;margin-top:8px;">* Required items.</div>
</div>

		<script language="javascript">
		function copymydata()
		{
			document.getElementById("fullname").value = "<?php echo htmlspecialchars($r_current_user["uname"] ?? '', ENT_QUOTES); ?>";
			document.getElementById("email").value = "<?php echo htmlspecialchars($r_current_user["email"] ?? '', ENT_QUOTES); ?>";
			document.getElementById("phone").value = "<?php echo htmlspecialchars($r_current_user["phone"] ?? '', ENT_QUOTES); ?>";
			
			// Set component dropdown
			var componentSelect = document.getElementById("component");
			var adminComponent = "<?php echo htmlspecialchars($r_current_user["component"] ?? '', ENT_QUOTES); ?>";
			for (var i = 0; i < componentSelect.options.length; i++) {
				if (componentSelect.options[i].value == adminComponent) {
					componentSelect.options[i].selected = true;
					break;
				}
			}
		}
		</script>
	<?php
	}	
	

// Step 5: Confirmation
else if($currentStep == 5)
{
    $appt_data = $_SESSION['wizard_data'];
?>
     <!-- Step 5: Appointment Confirmation -->
    <div class="titletextwhite">Appointment Confirmed!</div>
    
    <p style="text-align:center">Your appointment has been successfully scheduled.</p>
    
    <div style="max-width:600px;margin:40px auto;background:#fff;padding:30px;border:1px solid #d0d7de;border-radius:6px;">
        <h3 style="color:#24292f;margin-top:0;">Appointment Details</h3>
        
        <div style="margin:20px 0;">
            <strong>Reference:</strong> <?php echo htmlspecialchars($appt_data['apptref'] ?? ''); ?><br>
            <strong>Date:</strong> <?php echo isset($appt_data['slot_timestamp']) ? date("D jS M Y", $appt_data['slot_timestamp']) : ''; ?><br>
            <strong>Time:</strong> <?php echo isset($appt_data['slot_timestamp']) ? date("H:i", $appt_data['slot_timestamp']) : ''; ?><br>
            <strong>Name:</strong> <?php echo htmlspecialchars($appt_data['fullname'] ?? ''); ?><br>
            <strong>Email:</strong> <?php echo htmlspecialchars($appt_data['email'] ?? ''); ?><br>
            <strong>Phone:</strong> <?php echo htmlspecialchars($appt_data['phone'] ?? ''); ?><br>
            <strong>Reason:</strong> <?php echo htmlspecialchars($appt_data['arsn'] ?? ''); ?><br>
        </div>
        
        <p style="color:#656d76;font-size:14px;">A confirmation email has been sent to your email address.</p>
        
        <div style="margin-top:30px;display:flex;gap:12px;">
            <a href="frm-appt.html" class="inputbtn darkblue" style="display:inline-block;padding:10px 20px;text-decoration:none;">View My Appointments</a>
            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="inputbtn darkblue" style="display:inline-block;padding:10px 20px;text-decoration:none;">Book Another</a>
        </div>
    </div>
<?php
}
// If we just finished step 5, clear the wizard data for next time
if ($currentStep == 5 && isset($_SESSION['wizard_data'])) {
    // Clear wizard session data after confirmation is shown
    unset($_SESSION['wizard_step']);
    unset($_SESSION['wizard_data']);
    unset($_SESSION['max_step_reached']);
}
	?>	

	</form>
		</div>
	</div>
	<?php
// }
		$myform->frmrender_side($bsideparams);
		print "</div>\n";// end of inner-flex class
	?>	
</div>
	<?php
		$myform->frmrender_footer_wlogo($footerparams);
		print "</div>\n"; // close .main
	?>	
</body>
</html>
