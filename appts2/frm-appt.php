<?php
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-appt.php";
$form_name = "Appts";
$tab_name = ucfirst($form_name);

include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE . "/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);
$fullname = $_SESSION["authentxappts"]["user"]["uname"];
$namearray = explode(" ", $fullname);
$firstname = $namearray[0];

// validate the session
$sr = $myappt->validatesession($_siteid);
if ($sr !== true) {
	print "<script type=\"text/javascript\">alert('" . $sr . "')</script>\n";
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
$tab_repldash = $myappt->checktabmask(TAB_REPLDASH);

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
if ($tab_repldash)
	$ntabs++;


$priv_apptsched = $myappt->checkprivilege(PRIV_APPTSCHED);
$priv_apptedit = $myappt->checkprivilege(PRIV_APPTEDIT);

// Initially the weekstamp should not be included - this gets calculated when we get a siteid
$wkstamp = false;
$centeruuid = false;
$sitename = "";
$siteaddress = "";
$currenttime = "";
$ns = 0;
$sitedetails = array();

// Find out what center (if any) has been selected
if (isset($_GET["center"])) {
	$centeruuid = $_GET["center"];
	$myappt->session_setvar("center", $centeruuid);
} else
	$centeruuid = false;

if (isset($_POST["submit_center"])) {
	if (isset($_POST["center"]))
		$centeruuid = trim($_POST["center"]);

	$myappt->session_setvar("center", $centeruuid);

	if (isset($_POST["wk"]))
		$_GET["wk"] = trim($_POST["wk"]);
}

if ($myappt->session_getvar("center") !== false)
	$centeruuid = $myappt->session_getvar("center");

// get data from the database
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error) {
	// site list - only those with > 0 emws and available should be included
	$q_s = "select * from site "
		. "\n where status>'0' "
		. "\n order by siteaddrstate, siteaddrcity, sitename ";

	$s_s = $sdbh->query($q_s);
	if ($s_s) {
		$n_s = $s_s->num_rows;
		$list_sites = array();
		$jsite = array();
		$nas = 0;
		$srep = array("\n", "\r", "\t", "\\", "\"");

		while ($r_s = $s_s->fetch_assoc()) {
			$cntruuid = $r_s["centeruuid"];
			$sname = $r_s["sitename"];
			$s_region = trim($r_s["siteregion"]);
			$s_addr = trim(str_replace($srep, " ", trim($r_s["siteaddress"])));
			$s_city = trim($r_s["siteaddrcity"]);
			$s_state = trim($r_s["siteaddrstate"]);
			$s_activity = $r_s["siteactivity"];

			$q_aws = "select count(*) as wscount "
				. "\n from workstation "
				. "\n where centeruuid='" . $cntruuid . "' "
				. "\n and status>'0' ";
			$s_aws = $sdbh->query($q_aws);
			if ($s_aws) {
				$r_aws = $s_aws->fetch_assoc();
				if ($r_aws) {
					if ($r_aws["wscount"] > 0) {
						// Create a list of everything, for the initial case
						$list_centers[$nas][0] = $cntruuid;
						$list_centers[$nas][1] = $s_state . " : " . $s_city . " : " . $sname . " (" . $s_addr . ") - " . $s_activity;

						// Create the javascript population sets
						// Collated sets of centers by state and city
						$jsite[$s_state][$s_city][] = array($cntruuid, $s_state . " : " . $s_city . " : " . $sname . " (" . $s_addr . ") - " . $s_activity);

						$nas++;
					}
				}
				$s_aws->free();
			}
		}
		$s_s->free();
	}

	if ($centeruuid !== false) {
		// get the current site details (if selected)
		$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
		if (count($sitedetails) > 0) {
			$sitename = $sitedetails["sitename"];
			$siteaddress = str_replace($srep, " ", trim($sitedetails["siteaddress"] . " " . $sitedetails["siteaddrcity"] . " " . $sitedetails["siteaddrstate"]));
			$sitezoneoffset = $sitedetails["tzoneoffset"];
			$currenttime = date("m/d/Y H:i:s", (time() + $sitezoneoffset));

			// Find out what week we are on - should be a timestamp for a Sunday
			if (isset($_GET["wk"])) {
				$wkstamp = $_GET["wk"];

				// check and sanitise it
				if ($wkstamp <= 0)
					$wkstamp = time() + $sitezoneoffset;
				if (is_nan($wkstamp))
					$wkstamp = time() + $sitezoneoffset;

				$wdate = getdate($wkstamp);
			} else {
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
				$wkstamp_next = $wkstamp + (7 * 25 * 60 * 60); // allow for DST crossover
			else
				$wkstamp_next = $wkstamp;
			if ($sundate["year"] > 1970)
				$wkstamp_prev = $wkstamp - (7 * 24 * 60 * 60);
			else
				$wkstamp_prev = $wkstamp;

			// slot time is in minutes
			$slottime = $sitedetails["slottime"];

			// Number of timeslots for the site (based on the day with the longest operational time)
			// Ignore NULL settings, which mean that the day is not in operation.
			if ($sitedetails["startsun"] != NULL)
				$daystart = $sitedetails["startsun"];
			elseif ($sitedetails["startmon"] != NULL)
				$daystart = $sitedetails["startmon"];
			elseif ($sitedetails["starttue"] != NULL)
				$daystart = $sitedetails["starttue"];
			elseif ($sitedetails["startwed"] != NULL)
				$daystart = $sitedetails["startwed"];
			elseif ($sitedetails["startthu"] != NULL)
				$daystart = $sitedetails["startthu"];
			elseif ($sitedetails["startfri"] != NULL)
				$daystart = $sitedetails["startfri"];
			elseif ($sitedetails["startsat"] != NULL)
				$daystart = $sitedetails["startsat"];
			else
				$daystart = "09:00:00";

			if ($sitedetails["startmon"] != NULL) {
				if ($sitedetails["startmon"] < $daystart)
					$daystart = $sitedetails["startmon"];
			}
			if ($sitedetails["starttue"] != NULL) {
				if ($sitedetails["starttue"] < $daystart)
					$daystart = $sitedetails["starttue"];
			}
			if ($sitedetails["startwed"] != NULL) {
				if ($sitedetails["startwed"] < $daystart)
					$daystart = $sitedetails["startwed"];
			}
			if ($sitedetails["startthu"] != NULL) {
				if ($sitedetails["startthu"] < $daystart)
					$daystart = $sitedetails["startthu"];
			}
			if ($sitedetails["startfri"] != NULL) {
				if ($sitedetails["startfri"] < $daystart)
					$daystart = $sitedetails["startfri"];
			}
			if ($sitedetails["startsat"] != NULL) {
				if ($sitedetails["startsat"] < $daystart)
					$daystart = $sitedetails["startsat"];
			}
			if ($sitedetails["starthol"] != NULL) {
				if ($sitedetails["starthol"] < $daystart)
					$daystart = $sitedetails["starthol"];
			}

			// Day's end
			if ($sitedetails["endsun"] != NULL)
				$dayend = $sitedetails["endsun"];
			elseif ($sitedetails["endmon"] != NULL)
				$dayend = $sitedetails["endmon"];
			elseif ($sitedetails["endtue"] != NULL)
				$dayend = $sitedetails["endtue"];
			elseif ($sitedetails["endwed"] != NULL)
				$dayend = $sitedetails["endwed"];
			elseif ($sitedetails["endthu"] != NULL)
				$dayend = $sitedetails["endthu"];
			elseif ($sitedetails["endfri"] != NULL)
				$dayend = $sitedetails["endfri"];
			elseif ($sitedetails["endsat"] != NULL)
				$dayend = $sitedetails["endsat"];
			else
				$dayend = "17:00:00";

			if ($sitedetails["endmon"] != NULL) {
				if ($sitedetails["endmon"] > $dayend)
					$dayend = $sitedetails["endmon"];
			}
			if ($sitedetails["endtue"] != NULL) {
				if ($sitedetails["endtue"] > $dayend)
					$dayend = $sitedetails["endtue"];
			}
			if ($sitedetails["endwed"] != NULL) {
				if ($sitedetails["endwed"] > $dayend)
					$dayend = $sitedetails["endwed"];
			}
			if ($sitedetails["endthu"] != NULL) {
				if ($sitedetails["endthu"] > $dayend)
					$dayend = $sitedetails["endthu"];
			}
			if ($sitedetails["endfri"] != NULL) {
				if ($sitedetails["endfri"] > $dayend)
					$dayend = $sitedetails["endfri"];
			}
			if ($sitedetails["endsat"] != NULL) {
				if ($sitedetails["endsat"] > $dayend)
					$dayend = $sitedetails["endsat"];
			}
			if ($sitedetails["endhol"] != NULL) {
				if ($sitedetails["endhol"] > $dayend)
					$dayend = $sitedetails["endhol"];
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

			if ($slottime > 0) {
				// slottime is in minutes
				$n_timeslots = intval(($dayend_stamp - $daystart_stamp) / ($slottime * 60)) + 1;

				// For each timeslot between $daystart_stamp and $dayend_stamp for each day,
				// get the array of resource allocations for that slot. 
				// Build a table for the entire week.
				// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["stat"] = 0 (unavailable), 1 (booked), 2 (vacant), 3 (booked but now unavailable)
				// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["uid"] = uid of user if booked
				// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["apptid"] = apptid of booking
				// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["apptref"] = appt reference of booking
				$slottable = array();
				for ($i = 0; $i < $n_timeslots; $i++) {
					for ($d = 0; $d < 7; $d++) {
						// Timeslot start stamp: start on Sun start time for the week, 
						// add a day offset and a slot offset to get the start of the timeslot
						$wkdayslotstartstamp = ($daystart_stamp + ($d * 24 * 60 * 60) + ($i * $slottime * 60));
						$wkdate = date("Y-m-d H:i:s", $wkdayslotstartstamp);
						// get the timeslot availability map
						$slottable[$i][$d] = $myappt->getslotallocation($sdbh, $centeruuid, $wkdate, $slottime, SLOTDIVISIONS, $sitezoneoffset);
					}
				}
			}
		}
	}
	$sdbh->close();
}

print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams["css"] = $cfg_stdcss;
$headparams["jscript_file"] = $cfg_stdjscript;
// $headparams["jscript_file"][] = "../appcore/scripts/js-tablesort.js";
// $headparams["jscript_file"][] = "../appcore/scripts/js-checkall.js";
// $headparams["jscript_file"][] = "../appcore/scripts/js-expandall.js";

$myform->frmrender_head($headparams);
?>
<!--<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-store,no-Cache" />
<title>Authentx Appointments Management System</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentxappointment.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>-->
<?php

$bodyparams = array();
$bodyparams["onload"][] = "startSessionTimer()";

if (AJAX_SESSIONREFRESH_ENABLE === true) {
	$bodyparams["onload"][] = "startRefresh()";
}

if (AJAX_APPT_ENABLE === true) {
	print "<script language=\"javascript\" src=\"../appcore/scripts/js-ajax_appt.js\"></script>\n";
	print "<script language=\"javascript\">\n";
	print "xhrservice = '" . AJAX_APPTSERVICE . "'\n";
	if ($centeruuid !== false)
		print "center='" . urlencode($centeruuid) . "'\n";
	if ($wkstamp !== false)
		print "wk='" . urlencode($wkstamp) . "'\n";
	print "refreshinterval='" . $refresh_appt . "'\n";
	print "</script>\n";
}

if (count($jsite) > 0) {
	// Script for the filter dropdowns
	print "<script language=\"javascript\">\n";
	print "var states=[];\n";
	print "var cities=[];\n";
	print "var centers=[];\n";

	$n = 0;
	foreach ($jsite as $state => $stateset) {
		print "states[" . $n . "]=\"" . $state . "\";\n";
		print "cities[" . $n . "]=[];\n";
		print "centers[" . $n . "]=[];\n";
		$m = 0;
		foreach ($stateset as $city => $cityset) {
			print "cities[" . $n . "][" . $m . "]=\"" . $city . "\";\n";
			print "centers[" . $n . "][" . $m . "]=[];\n";
			$j = 0;
			foreach ($cityset as $cntrset) {
				print "centers[" . $n . "][" . $m . "][" . $j . "]=[\"" . $cntrset[0] . "\",\"" . $cntrset[1] . "\"];\n";
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
	var sel_s = document.getElementById("center");
	var ns = 0;
	var x = 0;
	
	sel_c.length = 0;
	sel_s.length = 0;
	for (i = 0; i < nc; i++)
	{
		sel_c.options[i] = new Option(cities[sel_st_idx][i], cities[sel_st_idx][i]);
		
		ns = centers[sel_st_idx][i].length;
		for (j = 0; j < ns; j++)
			sel_s.options[x++] = new Option(centers[sel_st_idx][i][j][1], centers[sel_st_idx][i][j][0]);
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
	var ns = centers[sel_st_idx][sel_c_idx].length;
	
	sel_s.length = 0;
	for (i = 0; i < ns; i++)
		sel_s.options[i] = new Option(centers[sel_st_idx][sel_c_idx][i][1], centers[sel_st_idx][sel_c_idx][i][0]);
}

<?php
	print "</script>\n";
}


$myform->frmrender_bodytag($bodyparams);

// The page container
print "<div class=\"main\">\n";

// The top heading section
$topparams = array();
$topparams["logoimgurl"] = $cfg_logoimgurl;
$topparams["logoalt"] = $cfg_logoalt;
$topparams["bannerheading1"] = BANNERHEADING1;
$topparams["bannerheading2"] = BANNERHEADING2;
$topparams["pageportal"] = $page_certportal;
$topparams["dropdown"] = array_merge($cfg_userdropdown, $cfg_tabs);

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

if (AJAX_APPT_ENABLE === true) {
	if ($refresh_appt !== false) {
		$bodyparams["onload"][] = "startRefresh()";
	}

	$bodyparams["onload"][] = "f_init()";
}

?>
<div id="content">
	<div class="fullscreenscrollbox">

		<!-- Create Appointment Card -->
		<div class="cards-scope">
			<div class="cards-grid">
				<div class="card-media">
					<img
						src="../appcore/images/calendar_card.jpg"
						alt="Create a new appointment"
						loading="lazy"
						width="1200" height="675"
						style="--zoom:1.18; --pos:center;">
				</div>
				<div class="card-body">
					<form action="frm-create.html" method="POST">
						<h5 class="card-title">Create Appointment</h5>
						<p class="card-text">Start a new appointment request and confirm details in a few steps.</p>
						<br>
						<input type="hidden" name="avc" class="inputbtn darkblue" value="<?php print htmlspecialchars($avc); ?>" />
						<input type="submit" name="btn_filter" class="inputbtn darkblue" value="Create" />
					</form>
				</div>
			</div>
		</div>
		<!-- / Create Appointment Card -->

		<div style="max-width:1100px;margin:0 auto;display:flex;flex-direction:column;gap:16px;padding:5% 0;">
			<div style="display:flex;flex-direction:column;align-items:stretch;gap:20px;padding:10px 0;">
				<div style="padding-bottom: 20px" class="nameheading">Appointment Schedule for <?php print htmlentities($myappt->session_getuuname()) ?></div>

				<?php
				// print "<span class=\"proplabel\">Run Time: </span><span class=\"proptext\">" . date("D M jS H:i:s") . "</span><br/>";
				?>
				<table id="appt_schedule" class="striped">
					<thead>
						<tr class="light-xtec-blue">
							<th class="tableheader">Date</th>
							<th class="tableheader">Time</th>
							<th class="tableheader tableheaderclick" onclick="window.location.href='frm-sites.html'">Site</th>
							<th class="tableheader">Appt Ref</th>
							<th class="tableheader">Reason</th>
							<th class="tableheader">Site Contact</th>
							<th class="tableheader">Contact Phone</th>
							<th class="tableheader">Action</th>
						</tr>
					</thead>
				<tbody>

				<table width="100%" cellspacing="0" cellpadding="0" border="0" nowrap="nowrap" scope="row" class="tabtable">
					<tr height="33" valign="center" align="center">
						<td>
							<?php
							// Determine user's tab display
							?>
							<table width="<?php echo $ntabs * 105; ?>" cellspacing="0" cellpadding="0" border="0" nowrap="nowrap" scope="row">
								<tr height="33" valign="center">
									<td width="105" class="tabcell_on"><span class="tabtext">Appts</span></td>
									<?php if ($tab_users): ?>
										<td width="105" class="tabcell_off"><a href="frm-user.php"><span class="tabtext">Users</span></a></td>
									<?php endif; ?>
									<?php if ($tab_sites): ?>
										<td width="105" class="tabcell_off"><a href="frm-sites.php"><span class="tabtext">Sites</span></a></td>
									<?php endif; ?>
									<?php if ($tab_ws): ?>
										<td width="105" class="tabcell_off"><a href="frm-emws.php"><span class="tabtext">EMWS</span></a></td>
									<?php endif; ?>
									<?php if ($tab_holmaps): ?>
										<td width="105" class="tabcell_off"><a href="frm-holmap.php"><span class="tabtext">Holidays</span></a></td>
									<?php endif; ?>
									<?php if ($tab_reports): ?>
										<td width="105" class="tabcell_off"><a href="frm-reports.php"><span class="tabtext">Reports</span></a></td>
									<?php endif; ?>
									<?php if ($tab_invite): ?>
										<td width="105" class="tabcell_off"><a href="frm-userinvite.php"><span class="tabtext">Invite</span></a></td>
									<?php endif; ?>
									<?php if ($tab_mailtmpl): ?>
										<td width="105" class="tabcell_off"><a href="frm-mailtmpl.php"><span class="tabtext">Templates</span></a></td>
									<?php endif; ?>
									<?php if ($tab_repldash): ?>
										<td width="105" class="tabcell_off"><a href="frm-repldash.php"><span class="tabtext">Replication</span></a></td>
									<?php endif; ?>
								</tr>
							</table>
						</td>
					</tr>
					<tr height="20">
						<td valign="top" colspan="9">
							<table width="100%" cellspacing="0" cellpadding="0" border="0" id="bartable">
								<tr height="18" valign="center">
									<td>&nbsp;</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>

				<p />

				<table cellspacing="0" cellpadding="0" align="center" border="0" width="858">
					<tr>
						<td><img height="12" src="../appcore/images/logoslice_1.gif" width="200" border="0" /></td>
					</tr>
					<tr>
						<td valign="top" background="../appcore/images/box_mtl_ctr.gif">
							<table cellspacing="0" cellpadding="0" border="0" width="858">
								<tr height="12">
									<td width="12" height="12"><img height="12" src="../appcore/images/box_mtl_topl.gif" width="12" /></td>
									<td background="../appcore/images/box_mtl_top.gif"><img height="12" src="../appcore/images/logoslice_2.gif" width="188" border="0" /></td>
									<td width="12"><img height="12" src="../appcore/images/box_mtl_topr.gif" width="12" /></td>
								</tr>
								<tr valign="top">
									<td width="12" background="../appcore/images/box_mtl_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
									<td align="middle" background="../appcore/images/bg_spacer.gif">
										<table cellspacing="0" cellpadding="0" border="0" width="834">
											<tr>
												<td align="middle">
													<table cellspacing="0" cellpadding="0" border="0" width="834">
														<tr height="0">
															<td align="left" width="220"><img height="56" src="../appcore/images/logoslice_3.gif" width="188" border="0" /></td>
															<td align="right">
																<table cellspacing="0" cellpadding="0" border="0" width="610">
																	<tr>
																		<td align="left" width="450">
																			<table cellspacing="0" cellpadding="0" border="0" width="450">
																				<tr height="28">
																					<td valign="top"><span class="siteheading"><?php echo SITEHEADING; ?></span></td>
																				</tr>
																				<tr height="28">
																					<td valign="top"><span class="nameheading"></span></td>
																				</tr>
																			</table>
																		</td>
																		<td align="right" width="160">
																			<table cellspacing="0" cellpadding="0" border="0" width="160">
																				<tr height="28" valign="middle">
																					<td align="middle" width="80"></td>
																					<td align="middle" width="40"></td>
																					<td align="middle" width="40"></td>
																				</tr>
																				<tr height="28" valign="middle">
																					<td align="middle"></td>
																					<td align="middle" colspan="2"><a href="vec-logout.php" title="Log off the system"><img src="../appcore/images/icon-btnlogoff.gif" width="75" height="24" border="0" onclick='return frmCheckDirty()' /></a></td>
																				</tr>
																			</table>
																		</td>
																	</tr>
																</table>
															</td>
														</tr>
														<tr height="8" valign="top">
															<td></td>
															<td></td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>
												<td align="center" valign="bottom">
													<table cellspacing="0" cellpadding="0" border="0" width="834">
														<tr height="2">
															<td width="2" height="2"><img height="2" src="../appcore/images/bevel_topl.gif" width="2" /></td>
															<td background="../appcore/images/bevel_top.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
															<td width="2" height="2"><img height="2" src="../appcore/images/bevel_topr.gif" width="2" /></td>
														</tr>
														<tr>
															<td width="2" background="../appcore/images/bevel_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
															<td valign="center" align="left">
																<table border="0" cellspacing="0" cellpadding="10" width="830" bgcolor="#ffffff">
																	<tr>
																		<td align="left" valign="top">
																			<table border="0" cellspacing="0" cellpadding="0" STYLE='table-layout:fixed' width="800" bgcolor="#ffffff">
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />
																				<col width="5%" />

																				<tr height="1">
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																					<td></td>
																				</tr>

																				<!-- Site Selection Form -->
																				<form name="siteform" method="post" action="<?php print $formfile ?>" autocomplete="off">
																					<tr height="40">
																						<td colspan="6" valign="top"><span class="lblblktext">Filter: State</span><br />
																							<select name="fstate" id="fstate" style="width:16em;" onChange="filterstate()">
																							</select>
																						</td>
																						<td colspan="6" valign="top"><span class="lblblktext">Filter: City</span><br />
																							<select name="fcity" id="fcity" style="width:16em;" onChange="filtercity()">
																							</select>
																						</td>
																						<td colspan="8" valign="top"></td>
																					</tr>

																					<tr height="40">
																						<td colspan="12" valign="top"><span class="lblblktext">Site Selection</span><br />
																							<input type="hidden" name="wk" value="<?php print($wkstamp === false ? "" : urlencode($wkstamp)) ?>" />
																							<select name="center" id="center" style="width:36em;">
																								<?php
																								// output the list of centers in a selection dropdown
																								for ($i = 0; $i < $nas; $i++) {
																									if ($centeruuid !== false) {
																										if ($list_centers[$i][0] == $centeruuid)
																											print "<option selected value=\"" . (htmlentities($list_centers[$i][0])) . "\">" . (htmlentities($list_centers[$i][1])) . "</option>\n";
																										else
																											print "<option value=\"" . (htmlentities($list_centers[$i][0])) . "\">" . (htmlentities($list_centers[$i][1])) . "</option>\n";
																									} else
																										print "<option value=\"" . (htmlentities($list_centers[$i][0])) . "\">" . (htmlentities($list_centers[$i][1])) . "</option>\n";
																								}
																								?>
																							</select>
																						</td>
																						<td colspan="4" valign="top"><span class="lblblktext">&nbsp;</span><br />
																							<input type="submit" name="submit_center" class="btntext" value="Select Site" />
																						</td>
																						<td colspan="4" valign="top"><span class="lblblktext">&nbsp;</span><br />
																							<input type="button" class="btntext" value="My Appointments" onclick="javascript:popupOpener('pop-showmyappts.php','myappts',350,500)" />
																						</td>
																					</tr>
																				</form>
																				<!-- / Site Selection Form -->

																				<tr height="20">
																					<td colspan="20" valign="top">
																						<hr />
																					</td>
																				</tr>

																				<tr>
																					<td colspan="20" valign="top" id="pagedata">
																						<?php
																						// Build the calendar for appointments if there's a centeruuid
																						if ($centeruuid !== false) {
																							if ($slottime > 0) {
																								// Put the key at the top
																								?>
																								<table width="800" cellspacing="0" cellpadding="0" border="0">
																									<tr><td valign="top" align="center" width="800">
																									<table width="480" cellspacing="1" cellpadding="2" border="1">
																									<tr height="20">
																									<td width="20" class="unavail"><span class="matrixline">&nbsp;</span></td>
																									<td width="100"><span class="matrixline">Unavailable</span></td>
																									<td width="20" class="vacant"><span class="matrixline">&nbsp;</span></td>
																									<td width="100"><span class="matrixline">Available</span></td>
																									<td width="20" class="booked"><span class="matrixline">&nbsp;</span></td>
																									<td width="100"><span class="matrixline">Booked</span></td>
																									<td width="20" class="mybooking"><span class="matrixline">&nbsp;</span></td>
																									<td width="100"><span class="matrixline">My Booking</span></td>
																									</tr>
																									</table>
																									</td>
																									</tr>
																								</table>

																								<p/>

																								<?php
																								// setup the week navigation
																								?>
																								<table width="100%" border="0" cellspacing="0" cellpadding="0">
																								<tr class="apptweekrow">
																								<td width="100%" align="middle" class="apptweek">
																								<?php echo $sitename; ?>
																								<br><span class="apptsiteaddress"><?php echo $siteaddress; ?></span>
																								<br><span class="apptsitetime"><?php echo $currenttime; ?></span>
																								</td>
																								</tr>
																								<tr class="apptweekrow">
																								<td width="100%" class="apptweek">
																								<a href="<?php echo htmlentities($formfile); ?>?wk=<?php echo urlencode($wkstamp_prev); ?>&center=<?php echo urlencode($centeruuid); ?>" title="Previous week">
																								<img src="../appcore/images/appt_arrow_left.jpg" width="27" height="19" border="0">
																								</a>
																								<?php echo htmlentities(date("jS M Y", $wkstamp)); ?>
																								<a href="<?php echo htmlentities($formfile); ?>?wk=<?php echo urlencode($wkstamp_next); ?>&center=<?php echo urlencode($centeruuid); ?>" title="Next week">
																								<img src="../appcore/images/appt_arrow_right.jpg" width="27" height="19" border="0">
																								</a>
																								</td>
																								</tr>
																								</table>

																								<?php

																								$slotwidth = 13;
																								$divwidth = intval($slotwidth / SLOTDIVISIONS);
																								$timewidth = 100 - 7 * $slotwidth;

																								// weekday table headings
																								// column for timeslots (16%) and 7 weekday columns (12% ea)
																								?>
																								<table width="100%" class="apptmaintable" cellspacing="0" cellpadding="1" border="1">
																								<tr class="apptrows">
																								<td class="apptdayname" width="<?php echo $timewidth; ?>%">&nbsp;</td>

																								<?php

																								// weekday headings
																								for ($d = 0; $d < 7; $d++) {
																									$wkdayslotstartstamp = ($daystart_stamp + ($d * 24 * 60 * 60));
																									if ($priv_apptsched) {
																										// can click on the day heading to get the daily appt schedule popup for this site
																										$mac = $myappt->session_createmac($wkdayslotstartstamp . $centeruuid);
																										$schedurl = "pop-dailysched.php?center=" . urlencode($centeruuid)
																											. "&datestamp=" . urlencode($wkdayslotstartstamp)
																											. "&avc=" . urlencode($mac);
																										$wkdate = date("D M jS", $wkdayslotstartstamp);
																										print "<td class=\"apptdayname\" "
																											. "width=\"" . $slotwidth . "%\" "
																											. "onmouseover=\"javascript:this.style.cursor='pointer';\""
																											. "onclick=\"javascript:popupOpener('" . $schedurl . "','appsched',500,900);\" "
																											. " >" . $wkdate . "</td>\n";
																									} else {
																										$wkdate = date("D M jS", $wkdayslotstartstamp);
																										print "<td class=\"apptdayname\" width=\"" . $slotwidth . "%\">" . $wkdate . "</td>\n";
																									}
																								}
																								print "</tr>\n";

																								/**
																								 * // render the slottable
																								 * 
																								 * $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["stat"] = 0 (unavailable), 1 (booked), 2 (vacant), 3 (booked but now unavailable)
																								 * $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["uid"] = uid of user if booked
																								 * $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["apptid"] = apptid of booking
																								 * $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["apptref"] = appt reference of booking
																								 */

																								for ($i = 0; $i < $n_timeslots; $i++) {
																									// setup the row and print the start time
																									$slottimestamp = ($daystart_stamp + ($i * $slottime * 60));
																									$timestring = date("H:i", $slottimestamp);

																									print "<tr class=\"apptrows\">\n";
																									print "<td class=\"apptslotname\">" . $timestring . "</td>\n";

																									for ($d = 0; $d < 7; $d++) {
																										// Setup the cell for the appt divisions
																										// Make a slot timestamp for the day (slottimestamp is for wd=0, Sunday)
																										$d_slottimestamp = $slottimestamp + ($d * 24 * 60 * 60);
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
																													$apptref = false;
																													break;

																												case DIVSTAT_BOOKED:
																													$uuid = $slottable[$i][$d][$s]["uuid"];
																													$apptuuid = $slottable[$i][$d][$s]["apptuuid"];
																													$apptref = $slottable[$i][$d][$s]["apptref"];
																													if ($myappt->session_getuuid() == $uuid) {
																														// can click on booking to view/cancel
																														$celltitle = "my booking: " . $apptref;
																														$stclass = "mybooking";
																														$clickable = true;
																														$mac = $myappt->session_createmac($d_slottimestamp . $uuid . $apptuuid);
																														$clickurl = "pop-booking.php?st=" . urlencode($d_slottimestamp)
																															. "&uuid=" . urlencode($uuid)
																															. "&apptuuid=" . urlencode($apptuuid)
																															. "&avc=" . urlencode($mac);
																													} else {
																														// if an admin is permitted to edit any appointments
																														if ($priv_apptedit === true) {
																															$celltitle = "booked: " . $apptref;
																															$stclass = "booked";
																															$clickable = true;
																															$mac = $myappt->session_createmac($d_slottimestamp . $uuid . $apptuuid);
																															$clickurl = "pop-booking.php?st=" . urlencode($d_slottimestamp)
																																. "&uuid=" . urlencode($uuid)
																																. "&apptuuid=" . urlencode($apptuuid)
																																. "&avc=" . urlencode($mac);
																														} else {
																															$celltitle = "booked: " . $apptref;
																															$stclass = "booked";
																															$clickable = false;
																														}
																													}
																													break;

																												case DIVSTAT_VACANT:
																													$celltitle = "vacant";
																													$stclass = "vacant";
																													$clickable = true;
																													$uuid = $myappt->session_getuuid();
																													$mac = $myappt->session_createmac($d_slottimestamp . $uuid . $centeruuid);
																													$clickurl = "pop-booking.php?st=" . urlencode($d_slottimestamp)
																														. "&uuid=" . urlencode($uuid)
																														. "&center=" . urlencode($centeruuid)
																														. "&avc=" . urlencode($mac);
																													break;

																												case DIVSTAT_CONFLICT:
																													$uuid = $slottable[$i][$d][$s]["uuid"];
																													$apptuuid = $slottable[$i][$d][$s]["apptuuid"];
																													$apptref = $slottable[$i][$d][$s]["apptref"];
																													$celltitle = "conflict: " . $apptref;
																													if ($myappt->session_getuuid() == $uuid) {
																														$stclass = "conflict";
																														$clickable = true;
																														$mac = $myappt->session_createmac($d_slottimestamp . $uuid . $apptuuid);
																														$clickurl = "pop-booking.php?st=" . urlencode($d_slottimestamp)
																															. "&uuid=" . urlencode($uuid)
																															. "&apptuuid=" . urlencode($apptuuid)
																															. "&avc=" . urlencode($mac);
																													} else {
																														$stclass = "conflict";
																														$clickable = false;
																													}
																													break;

																												case DIVSTAT_PASTBOOKED:
																													$celltitle = "unavailable";
																													$stclass = "pastbooked";
																													$clickable = false;
																													$apptref = false;
																													break;

																												case DIVSTAT_PASTVACANT:
																												case DIVSTAT_BLOCKOUTVACANT:
																													$celltitle = "unavailable";
																													$stclass = "pastvacant";
																													$clickable = false;
																													$apptref = false;
																													break;

																												case DIVSTAT_BLOCKOUTBOOKED:
																													$uuid = $slottable[$i][$d][$s]["uuid"];
																													$apptuuid = $slottable[$i][$d][$s]["apptuuid"];
																													$apptref = $slottable[$i][$d][$s]["apptref"];
																													if ($myappt->session_getuuid() == $uuid) {
																														// can click on booking to view/cancel
																														$celltitle = "my booking: " . $apptref;
																														$stclass = "mybooking";
																														$clickable = true;
																														$mac = $myappt->session_createmac($d_slottimestamp . $uuid . $apptuuid);
																														$clickurl = "pop-booking.php?st=" . urlencode($d_slottimestamp)
																															. "&uuid=" . urlencode($uuid)
																															. "&apptuuid=" . urlencode($apptuuid)
																															. "&avc=" . urlencode($mac);
																													} else {
																														// if an admin is permitted to edit any appointments
																														if ($priv_apptedit === true) {
																															$celltitle = "booked: " . $apptref;
																															$stclass = "pastbooked";
																															$clickable = true;
																															$mac = $myappt->session_createmac($d_slottimestamp . $uuid . $apptuuid);
																															$clickurl = "pop-booking.php?st=" . urlencode($d_slottimestamp)
																																. "&uuid=" . urlencode($uuid)
																																. "&apptuuid=" . urlencode($apptuuid)
																																. "&avc=" . urlencode($mac);
																														} else {
																															$celltitle = "booked: " . $apptref;
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

																											// display the colour-coded divisions
																											print "<td width=\"" . $divwidth . "\" class=\"" . $stclass . "\" "
																												. " title=\"" . $celltitle . "\" "
																												. ($clickable ? "onmouseover=\"javascript:this.style.cursor='pointer';\"" : "")
																												. ($clickable ? "onclick=\"javascript:popupOpener('" . $clickurl . "','booking',400,450)\"" : "")
																												. ">&nbsp;</td>\n";
																										}

																										print "</tr>\n";
																										print "</table>\n";
																										print "</td>\n";
																									}

																									print "</tr>\n";
																								}

																								print "</table>\n";
																								print "<p/>\n";
																							} else {
																								print "<span class=\"lblblktext\">Slot time value is not set for this site.</span>\n";
																							}
																						} else {
																							print "<span class=\"lblblktext\">Please select a site.</span>\n";
																						}
																						?>
																					</td>
																				</tr>
																			</table>
																		</td>
																	</tr>
																</table>
															</td>
															<td width="2" background="../appcore/images/bevel_r.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
														</tr>
														<tr height="2">
															<td width="2" height="2"><img height="2" src="../appcore/images/bevel_botl.gif" width="2" /></td>
															<td background="../appcore/images/bevel_bot.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
															<td width="2" height="2"><img height="2" src="../appcore/images/bevel_botr.gif" width="2" /></td>
														</tr>
													</table>
											</tr>
											<tr>
												<td align="center" valign="bottom">
													<table cellspacing="0" cellpadding="0" border="0" width="834">
														<tr>
															<td width="30%" align="left">
																&nbsp;
															</td>
															<td width="40%" align="center">
																<span class="smlgrytext">&nbsp;</span>
															</td>
															<td width="30%" align="right">
																<span align="right"><img height="25" src="../appcore/images/AuthentX-logo-plain-gray6.gif" width="94" /></span>
															</td>
														</tr>
													</table>
											</tr>
										</table>
									</td>
									<td width="12" background="../appcore/images/box_mtl_r.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
								</tr>
								<tr height="14">
									<td width="12" height="14"><img height="14" src="../appcore/images/box_mtl_botl.gif" width="12" /></td>
									<td valign="top" background="../appcore/images/box_mtl_bot.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
									<td width="12"><img height="14" src="../appcore/images/box_mtl_botr.gif" width="12" /></td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
</body>

</html>