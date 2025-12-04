<?PHP

// $Id:$

// xsvc-appt.xas
// AJAX posting from appointments form to refresh the changed contents.
// GET: site (the siteid, also stored in the session)

// Returns simple HTML for the middle data table.

if (!isset($_SESSION["authentx"]))
	session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

$formfile = "frm-appt.php";

if (AJAX_APPT_ENABLE !== true)
	die();

// validate the session
$sr = $myappt->validatesession($_siteid);
if ($sr !== true)
{
	print "<script type=\"text/javascript\">alert('".$sr."')</script>\n";
	$myappt->vectormeto($page_denied);
}

// Initially the weekstamp should not be included - this gets calculated when we get a siteid
$wkstamp = false;
$centeruuid = false;
$sitename = "";
$siteaddress = "";
$currenttime = "";
$ns = 0;
$sitedetails = array();

$priv_apptsched = $myappt->checkprivilege(PRIV_APPTSCHED);
$priv_apptedit = $myappt->checkprivilege(PRIV_APPTEDIT);

// Find out what site (if any) has been selected
if (isset($_GET["center"]))
{
	$centeruuid = $_GET["center"];
	if (strlen($centeruuid) != 36)
		$centeruuid = false;
	else
		$myappt->session_setvar("center", $centeruuid);
}
else
	$centeruuid = false;

if ($myappt->session_getvar("center") !== false)
	$centeruuid = $myappt->session_getvar("center");

// get data from the database
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	$srep = array("\n", "\r", "\t", "\\", "\"");
	
	if ($centeruuid !== false)
	{
		// get the current site details (if selected)
		$sitedetails = $myappt->readsitedetail($sdbh, $centeruuid);
		if (count($sitedetails) > 0)
		{
			$sitename = $sitedetails["sitename"];
			$siteaddress = str_replace($srep, " ", trim($sitedetails["siteaddress"]." ".$sitedetails["siteaddrcity"]." ".$sitedetails["siteaddrstate"]));
			$sitezoneoffset = $sitedetails["tzoneoffset"];
			$currenttime = date("m/d/Y H:i:s", (time() + $sitezoneoffset));
	
			// Find out what week we are on - should be a timestamp for a Sunday
			if (isset($_GET["wk"]))
			{
				$wkstamp = $_GET["wk"];

				// check and sanitise it
				if ($wkstamp <= 0)
					$wkstamp = time() + $sitezoneoffset;
				if (is_nan($wkstamp))
					$wkstamp = time() + $sitezoneoffset;
							
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
			
			if ($sitedetails["startmon"] != NULL)
			{
				if ($sitedetails["startmon"] < $daystart)
					$daystart = $sitedetails["startmon"];
			}
			if ($sitedetails["starttue"] != NULL)
			{
				if ($sitedetails["starttue"] < $daystart)
					$daystart = $sitedetails["starttue"];
			}
			if ($sitedetails["startwed"] != NULL)
			{
				if ($sitedetails["startwed"] < $daystart)
					$daystart = $sitedetails["startwed"];
			}
			if ($sitedetails["startthu"] != NULL)
			{
				if ($sitedetails["startthu"] < $daystart)
					$daystart = $sitedetails["startthu"];
			}
			if ($sitedetails["startfri"] != NULL)
			{
				if ($sitedetails["startfri"] < $daystart)
					$daystart = $sitedetails["startfri"];
			}
			if ($sitedetails["startsat"] != NULL)
			{
				if ($sitedetails["startsat"] < $daystart)
					$daystart = $sitedetails["startsat"];
			}
			if ($sitedetails["starthol"] != NULL)
			{
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
			
			if ($sitedetails["endmon"] != NULL)
			{
				if ($sitedetails["endmon"] > $dayend)
					$dayend = $sitedetails["endmon"];
			}
			if ($sitedetails["endtue"] != NULL)
			{
				if ($sitedetails["endtue"] > $dayend)
					$dayend = $sitedetails["endtue"];
			}
			if ($sitedetails["endwed"] != NULL)
			{
				if ($sitedetails["endwed"] > $dayend)
					$dayend = $sitedetails["endwed"];
			}
			if ($sitedetails["endthu"] != NULL)
			{
				if ($sitedetails["endthu"] > $dayend)
					$dayend = $sitedetails["endthu"];
			}
			if ($sitedetails["endfri"] != NULL)
			{
				if ($sitedetails["endfri"] > $dayend)
					$dayend = $sitedetails["endfri"];
			}
			if ($sitedetails["endsat"] != NULL)
			{
				if ($sitedetails["endsat"] > $dayend)
					$dayend = $sitedetails["endsat"];
			}
			if ($sitedetails["endhol"] != NULL)
			{
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
			
			if ($slottime > 0)
			{
				// slottime is in minutes
				$n_timeslots = intval(($dayend_stamp - $daystart_stamp)/($slottime*60)) + 1;
			
				// For each timeslot between $daystart_stamp and $dayend_stamp for each day,
				// get the array of resource allocations for that slot. 
				// Build a table for the entire week.
				// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["stat"] = 0 (unavailable), 1 (booked), 2 (vacant), 3 (booked but now unavailable)
				// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["uid"] = uid of user if booked
				// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["apptid"] = apptid of booking
				// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["apptref"] = appt reference of booking
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
						$slottable[$i][$d] = $myappt->getslotallocation($sdbh, $centeruuid, $wkdate, $slottime, SLOTDIVISIONS, $sitezoneoffset);
					}
				}
			}
		}
	}
	$sdbh->close();
}

$urlparams = "";
if ($centeruuid !== false)
{
	$urlparams = "?center=".urlencode($centeruuid);
	if ($wkstamp !== false)
		$urlparams .= "&wk=".urlencode($wkstamp);
}


// Build the calendar for appointments if there's a siteid
if ($centeruuid !== false)
{
	if ($slottime > 0)
	{
		// Put the key at the top
		$resultstring = "<table width=\"800\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">";
		$resultstring .= "<tr><td valign=\"top\" align=\"center\" width=\"800\">";
		$resultstring .= "<table width=\"480\" cellspacing=\"1\" cellpadding=\"2\" border=\"1\">";
		$resultstring .= "<tr height=\"20\">";
		$resultstring .= "<td width=\"20\" class=\"unavail\"><span class=\"matrixline\">&nbsp;</span></td>";
		$resultstring .= "<td width=\"100\"><span class=\"matrixline\">Unavailable</span></td>";
		$resultstring .= "<td width=\"20\" class=\"vacant\"><span class=\"matrixline\">&nbsp;</span></td>";
		$resultstring .= "<td width=\"100\"><span class=\"matrixline\">Available</span></td>";
		$resultstring .= "<td width=\"20\" class=\"booked\"><span class=\"matrixline\">&nbsp;</span></td>";
		$resultstring .= "<td width=\"100\"><span class=\"matrixline\">Booked</span></td>";
		$resultstring .= "<td width=\"20\" class=\"mybooking\"><span class=\"matrixline\">&nbsp;</span></td>";
		$resultstring .= "<td width=\"100\"><span class=\"matrixline\">My Booking</span></td>";
		$resultstring .= "</tr>";
		$resultstring .= "</table>";
		$resultstring .= "</td></tr></table>";
		$resultstring .= "<p/>";
		
	 	// setup the week navigation
	 	$resultstring .= "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">";
	 	$resultstring .= "<tr class=\"apptweekrow\">";
	 	$resultstring .= "<td width=\"100%\" align=\"middle\" class=\"apptweek\">";
		$resultstring .= $sitename;
		$resultstring .= "<br><span class=\"apptsiteaddress\">".$siteaddress."</span>";
		$resultstring .= "<br><span class=\"apptsitetime\">".$currenttime."</span>";
		$resultstring .= "</td>";
		$resultstring .= "</tr>";
	 	$resultstring .= "<tr class=\"apptweekrow\">";
	 	$resultstring .= "<td width=\"100%\" class=\"apptweek\">";
		$resultstring .= "<a href=\"".htmlentities($formfile)."?wk=".urlencode($wkstamp_prev)."&center=".urlencode($centeruuid)."\" title=\"Previous week\">"
					. "<img src=\"../appcore/images/appt_arrow_left.jpg\" width=\"27\" height=\"19\" border=\"0\">"
					. "</a> ".htmlentities(date("jS M Y", $wkstamp))
					. " <a href=\"".htmlentities($formfile)."?wk=".urlencode($wkstamp_next)."&center=".urlencode($centeruuid)."\" title=\"Next week\">"
					. "<img src=\"../appcore/images/appt_arrow_right.jpg\" width=\"27\" height=\"19\" border=\"0\">"
					. "</a>";
		$resultstring .= "</td>";
		$resultstring .= "</tr>";
		$resultstring .= "</table>";

		$slotwidth = 13;
		$divwidth = intval($slotwidth/SLOTDIVISIONS);
		$timewidth = 100 - 7*$slotwidth;

	 	// weekday table headings
	 	// column for timeslots (16%) and 7 weekday columns (12% ea)
	 	$resultstring .= "<table width=\"100%\" class=\"apptmaintable\" cellspacing=\"0\" cellpadding=\"1\" border=\"1\">";
	 	$resultstring .= "<tr class=\"apptrows\">";
	 	$resultstring .= "<td class=\"apptdayname\" width=\"".$timewidth."%\">&nbsp;</td>";
	 	for ($d = 0; $d < 7; $d++)
		{
			if ($priv_apptsched)
			{
				// can click on the day heading to get the daily appt schedule popup for this site
				$wkdayslotstartstamp = ($daystart_stamp + ($d*24*60*60));
				$mac = $myappt->session_createmac($wkdayslotstartstamp.$centeruuid);
				$schedurl = "pop-dailysched.php?center=".urlencode($centeruuid)
							."&datestamp=".urlencode($wkdayslotstartstamp)
							."&avc=".urlencode($mac)
							;
				$wkdate = date("D M jS", $wkdayslotstartstamp);
				$resultstring .= "<td class=\"apptdayname\" "
					. "width=\"".$slotwidth."%\" "
					. "onmouseover=\"javascript:this.style.cursor='pointer';\""
					. "onclick=\"javascript:popupOpener('".$schedurl."','appsched',500,900);\" "
					. " >".$wkdate."</td>";
			}
			else
			{
				$wkdayslotstartstamp = ($daystart_stamp + ($d*24*60*60));
				$wkdate = date("D M jS", $wkdayslotstartstamp);
				$resultstring .= "<td class=\"apptdayname\" width=\"".$slotwidth."%\">".$wkdate."</td>";
			}
		}
		$resultstring .= "</tr>";
	 	
	 	// render the slottable
	 	// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["stat"] = 0 (unavailable), 1 (booked), 2 (vacant), 3 (booked but now unavailable)
		// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["uid"] = uid of user if booked
		// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["apptid"] = apptid of booking
		// $table[n(slot 0-n)][m(day 0-6)][k(division 0-k)]["apptref"] = appt reference of booking
		
		for ($i = 0; $i < $n_timeslots; $i++)
		{
			// setup the row and print the start time
			$slottimestamp = ($daystart_stamp + ($i*$slottime*60));
			$timestring = date("H:i", $slottimestamp);
			$resultstring .= "<tr class=\"apptrows\">";
			$resultstring .= "<td class=\"apptslotname\">".$timestring."</td>";
			
			for ($d = 0; $d < 7; $d++)
			{
				// Setup the cell for the appt divisions
				// Make a slot timestamp for the day (slottimestamp is for wd=0, Sunday)
				$d_slottimestamp = $slottimestamp+($d*24*60*60);
				$resultstring .= "<td>";
				$resultstring .= "<table width=\"100%\" class=\"apptslottable\" cellspacing=\"1\" cellpadding=\"0\" border=\"1\">";
				$resultstring .= "<tr>";
				for ($s = 0; $s < SLOTDIVISIONS; $s++)
				{
					$stat = $slottable[$i][$d][$s]["stat"];
					switch ($stat)
					{
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
								if ($myappt->session_getuuid() == $uuid)
								{
									// can click on booking to view/cancel
									$celltitle = "my booking: ".$apptref;
									$stclass = "mybooking";
									$clickable = true;
									$mac = $myappt->session_createmac($d_slottimestamp.$uuid.$apptuuid);
									$clickurl = "pop-booking.php?st=".urlencode($d_slottimestamp)
												."&uuid=".urlencode($uuid)
												."&apptuuid=".urlencode($apptuuid)
												."&avc=".urlencode($mac)
												;
								}
								else
								{
									// if an admin is permitted to edit any appointments
									if ($priv_apptedit === true)
									{
										$celltitle = "booked: ".$apptref;
										$stclass = "booked";
										$clickable = true;
										$mac = $myappt->session_createmac($d_slottimestamp.$uuid.$apptuuid);
										$clickurl = "pop-booking.php?st=".urlencode($d_slottimestamp)
													."&uuid=".urlencode($uuid)
													."&apptuuid=".urlencode($apptuuid)
													."&avc=".urlencode($mac)
													;
									}
									else
									{
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
								$uuid = $myappt->session_getuuid();
								$mac = $myappt->session_createmac($d_slottimestamp.$uuid.$centeruuid);
								$clickurl = "pop-booking.php?st=".urlencode($d_slottimestamp)
											."&uuid=".urlencode($uuid)
											."&center=".urlencode($centeruuid)
											."&avc=".urlencode($mac)
											;
								break;

						case DIVSTAT_CONFLICT:
								$uuid = $slottable[$i][$d][$s]["uuid"];
								$apptuuid = $slottable[$i][$d][$s]["apptuuid"];
								$apptref = $slottable[$i][$d][$s]["apptref"];
								$celltitle = "conflict: ".$apptref;
								if ($myappt->session_getuuid() == $uuid)
								{
									$stclass = "conflict";
									$clickable = true;
									$mac = $myappt->session_createmac($d_slottimestamp.$uuid.$apptuuid);
									$clickurl = "pop-booking.php?st=".urlencode($d_slottimestamp)
												."&uuid=".urlencode($uuid)
												."&apptuuid=".urlencode($apptuuid)
												."&avc=".urlencode($mac)
												;
								}
								else
								{
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
								if ($myappt->session_getuuid() == $uuid)
								{
									// can click on booking to view/cancel
									$celltitle = "my booking: ".$apptref;
									$stclass = "mybooking";
									$clickable = true;
									$mac = $myappt->session_createmac($d_slottimestamp.$uuid.$apptuuid);
									$clickurl = "pop-booking.php?st=".urlencode($d_slottimestamp)
									."&uuid=".urlencode($uuid)
									."&apptuuid=".urlencode($apptuuid)
									."&avc=".urlencode($mac)
									;
								}
								else
								{
									// if an admin is permitted to edit any appointments
									if ($priv_apptedit === true)
									{
										$celltitle = "booked: ".$apptref;
										$stclass = "pastbooked";
										$clickable = true;
										$mac = $myappt->session_createmac($d_slottimestamp.$uuid.$apptuuid);
										$clickurl = "pop-booking.php?st=".urlencode($d_slottimestamp)
										."&uuid=".urlencode($uuid)
										."&apptuuid=".urlencode($apptuuid)
										."&avc=".urlencode($mac)
										;
									}
									else
									{
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

					// display the colour-coded divisions
					$resultstring .= "<td width=\"".$divwidth."\" class=\"".$stclass."\" "
								." title=\"".$celltitle."\" "
								.($clickable ? "onmouseover=\"javascript:this.style.cursor='pointer';\"" : "")
								.($clickable ? "onclick=\"javascript:popupOpener('".$clickurl."','booking',400,450)\"" : "")
								.">&nbsp;</td>";
				}
				$resultstring .= "</tr>";
				$resultstring .= "</table>";
				$resultstring .= "</td>";
			}
			$resultstring .= "</tr>";
		}

		$resultstring .= "</table>";
		$resultstring .= "<p/>";
	}
	else 
		$resultstring = "<span class=\"lblblktext\">Slot time value is not set for this site.</span>";
}
else
	$resultstring = "<span class=\"lblblktext\">Please select a site.</span>";

header('Content-type: text/html; charset=utf-8');
print $resultstring;
	
?>