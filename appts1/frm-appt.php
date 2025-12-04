<?PHP
// $Id: frm-tokens.html 214 2009-03-17 22:46:06Z atlas $

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-appt.html";
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

// get data from the database
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	if (isset($_POST["edit_appt"]))
    {
        if (isset($_POST["aref"]))
        {     
            $new_date = isset($_POST["adate"]) ? trim($_POST["adate"]) : "";
            $new_time = isset($_POST["atime"]) ? trim($_POST["atime"]) : "";
            $new_site = isset($_POST["asite"]) ? trim($_POST["asite"]) : "";
            $new_reason = isset($_POST["arsn"]) ? trim($_POST["arsn"]) : "";
			$aref = isset($_POST["aref"]) ? trim($_POST["aref"]) : "";
            $new_site_contact = isset($_POST["sitecontact"]) ? trim($_POST["sitecontact"]) : "";
            $new_contact_phone = isset($_POST["sitecontactphone"]) ? trim($_POST["sitecontactphone"]) : "";

            // reformat the date and time into MySQL format
            $formatted_date = date('Y-m-d', strtotime($new_date)); 
            $formatted_time = $new_time . ":00";
            $new_starttime = $formatted_date . ' ' . $formatted_time;

            // update the database record for the appointment 
            $q_appt = "update appointment a "
                    . "\n INNER JOIN site s ON s.siteid = a.siteid "
                    . "\n set a.starttime='".$new_starttime."', "
                    . "\n a.siteid='".$new_site."', "
                    . "\n apptrsn='".$dbh->real_escape_string($new_reason)."', "
                    . "\n sitecontactname='".$dbh->real_escape_string($new_site_contact)."', "
                    . "\n sitecontactphone='".$dbh->real_escape_string($new_contact_phone)."' "
                    . "\n where a.apptref='".$dbh->real_escape_string($aref)."' "
                    ;
                
            $s_appt = $dbh->query($q_appt); 

            // Check if the update was successful
            if ($s_appt)
            {
                $q_ua = "select * "
                . "\n from appointment "
                . "\n left join site on site.siteid=appointment.siteid "
                . "\n where apptref ='".$dbh->real_escape_string($aref)."' "
                ;
                $s_ua = $dbh->query($q_ua);
                    
                $r_ua = $s_ua->fetch_assoc();
                $s_ua->free();      
            }
        }
    }

	// Deleting an appointment
    // Confirm deletion first
    // Then delete the appointment
    else if(isset($_POST['delete_appt']) && ($_POST['confirm_delete'] ?? '') === 'yes')
    {
		$aref = isset($_POST["aref"]) ? trim($_POST["aref"]) : "";

        if (isset($_POST["aref"]))
        {     
            // delete the appointment
            $q_appt = "delete from appointment "
                . "\n where apptref='".$dbh->real_escape_string($aref)."' "
                . "\n limit 1 "
                ;
                
            $s_appt = $dbh->query($q_appt);
        }
    }

	// Query to get siteid and sitename from the site table
    $q_sites = "SELECT siteid, sitename FROM site";

    // Prepare and execute the query
    $result = $dbh->query($q_sites);

    // Initialize an array to hold the results
    $site_array = array();

    // Fetch the results and store them in the array
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $site_array[] = array('siteid' => $row['siteid'], 'sitename' => $row['sitename']);
        }
    }

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
	
	// Calculate the start and end datetimes for the search
	$uid = $myappt->session_getuuid();
	$avc = $myappt->session_createmac($uid);
	// Find each site that the user has appointments at.
	$q_ua = "select * "
		. "\n from appointment "
		. "\n left join site on site.siteid=appointment.siteid "
		. "\n where uid='".$dbh->real_escape_string($uid)."' "
		;
	$s_ua = $dbh->query($q_ua);
	$n_fa = 0;
	$appt_fa = array();

	while ($r_ua = $s_ua->fetch_assoc())
	{
		$sitetimezone = $r_ua["timezone"];
		if (($sitetimezone == "") || ($sitetimezone == NULL))
			$sitezoneoffset = 0;
		else
		{
			$mytzone = new DateTimeZone($sitetimezone);
			$mydatetime = new DateTime("now", $mytzone);
			$sitezoneoffset = $mytzone->getOffset($mydatetime);
		}
		// $currenttimestamp = $myappt->gmtime() + $sitezoneoffset;
		
		$apptdatetime = $r_ua["starttime"];
		$apptdatetimestamp = strtotime($apptdatetime);
		if ($apptdatetimestamp > $currenttimestamp)
		{
			// Record column data:
			// Date, Time, Site, Ref, Reason, Contact Name, Contact Phone
			$adt = $r_ua["starttime"];
			$ds_Y = substr($adt, 0, 4);
			$ds_M = substr($adt, 5, 2);
			$ds_D = substr($adt, 8, 2);
			$ds_h = substr($adt, 11, 2);
			$ds_m = substr($adt, 14, 2);
			$ds_s = substr($adt, 17, 2);
		 	$ats = mktime($ds_h, $ds_m, $ds_s, $ds_M, $ds_D, $ds_Y);
	
		 	$appt_fa[$n_fa]["atime"] = date("H:i", $ats); 
		 	$appt_fa[$n_fa]["adate"] = date("D M jS", $ats);
			$appt_fa[$n_fa]["aref"] = $r_ua["apptref"];
			$appt_fa[$n_fa]["asite"] = $r_ua["siteid"];
			$appt_fa[$n_fa]["arsn"] = $r_ua["apptrsn"];
			$appt_fa[$n_fa]["sitename"] = $r_ua["sitename"];
			$appt_fa[$n_fa]["sitecontact"] = $r_ua["sitecontactname"];
			$appt_fa[$n_fa]["sitecontactphone"] = $r_ua["sitecontactphone"];
			
			$n_fa++;
		}
	}
	$s_ua->free();
	$dbh->close();
}
else 
{
	$n_s = 0;
	$siteid = false;
	$sitename = "";
	$siteaddress = "";
	$currenttime = "";
}


// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
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

// print "<script>";
// print "function showEditSection() {";
// print "    var section = document.getElementById('edit-section');";
// print "    section.style.display = 'block';";
// print "    section.scrollIntoView({behavior: 'smooth'});";
// print "}";
// print "</script>";

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
	<!-- <div class="titletextwhite">Appointments</div> -->
    <div class="cards-scope">
      <div class="cards-grid">
        <!-- Card 1 -->
          <div class="card-media">
            <img
              src="../appcore/images/calendar_card.jpg"
              alt="Create a new appointment"
              loading="lazy"
              width="1200" height="675"
              style="--zoom:1.18; --pos:center;"
            >
          </div>
          <div class="card-body">
			<form action="frm-create.html" method="POST">
            <h5 class="card-title">Create Appointment</h5>
            <p class="card-text">Start a new appointment request and confirm details in a few steps.</p>
			<br>
			<input type="hidden" name="avc" class="inputbtn darkblue" value="<?php print htmlspecialchars($avc); ?>"/>
			<input type="submit" name="btn_filter" class="inputbtn darkblue" value="Create"/>
			</form>
          </div>
      </div>
    </div>
	
	<div style="max-width:1100px;margin:0 auto;display:flex;flex-direction:column;gap:16px;padding:5% 0;">
		<div style="display:flex;flex-direction:column;align-items:stretch;gap:20px;padding:10px 0;">
			<div style="padding-bottom: 20px" class="nameheading">Appointment Schedule for <?php print htmlentities($myappt->session_getuuname()) ?></div>
<?php
			print "<table id = \"appt_schedule\" class=\"striped\">";
			// print "<span class=\"proplabel\">Run Time: </span><span class=\"proptext\">" . date("D M jS H:i:s") . "</span><br/>";
			print "<thead>";
			print "<tr class=\"light-xtec-blue\">";
			print "<th class=\"tableheader\">Date</th>";
			print "<th class=\"tableheader\">Time</th>";
			print "<th class=\"tableheader tableheaderclick\" onclick=\"window.location.href='frm-sites.html'\">Site</th>";
			print "<th class=\"tableheader\">Appt Ref</th>";
			print "<th class=\"tableheader\">Reason</th>";
			print "<th class=\"tableheader\">Site Contact</th>";
			print "<th class=\"tableheader\">Contact Phone</th>";
			print "<th class=\"tableheader\">Action</th>";
			print "</tr>";
			print "</thead>";
			print "<tbody>";		
?>

			<?php
			if ($n_fa == 0)
			{
			?>
			<?php
					print '<tr height="20"><td colspan="7" class="matrixline"><p>' . htmlentities('No Appointments', ENT_QUOTES, 'UTF-8') . '</p></td></tr>';

			}
			else 
			{
				for ($i = 0; $i < $n_fa; $i++)
				{
					print "<tr height=\"30\">";
					print "<td><p>" . htmlentities($appt_fa[$i]["adate"], ENT_QUOTES, 'UTF-8') . "</p></td>";
					print "<td><p>" . htmlentities($appt_fa[$i]["atime"], ENT_QUOTES, 'UTF-8') . "</p></td>";
					print "<td><p>" . htmlentities($appt_fa[$i]["sitename"], ENT_QUOTES, 'UTF-8') . "</p></td>";
					print "<td><p>" . htmlentities($appt_fa[$i]["aref"], ENT_QUOTES, 'UTF-8') . "</p></td>";
					print '<td><p>'. ($appt_fa[$i]['arsn'] === '' ? '&nbsp;' : htmlentities($appt_fa[$i]['arsn'], ENT_QUOTES, 'UTF-8')). '</p></td>';
					print '<td><p>'. ($appt_fa[$i]['sitecontact'] === '' ? '&nbsp;' : htmlentities($appt_fa[$i]['sitecontact'], ENT_QUOTES, 'UTF-8')). '</p></td>';
					print '<td><p>'. ($appt_fa[$i]['sitecontactphone'] === '' ? '&nbsp;' : htmlentities($appt_fa[$i]['sitecontactphone'], ENT_QUOTES, 'UTF-8')). '</p></td>';
					
					// Action column AKA Edit button
					print '<td style="text-align: center;">';
					$jsonData = htmlspecialchars(json_encode($appt_fa[$i]), ENT_QUOTES, 'UTF-8');
					print "<input type=\"button\" name=\"btn_filter\" class=\"inputbtn darkblue\" value=\"Edit\" onclick='showEditSection($jsonData)'/>";
					print '</td>';
				}
			}
			print "</table>";
			print "<div id=\"edit-section\" style=\"display: none;\">";
?>
			
			

		<form name="editappt" method="post" action="<?php print $formfile ?>"  autocomplete="off">
		<input type="hidden" name="avc" id="avc" value="<?php print $avc ?>" />
		<input type="hidden" name="uid" id="uid" value="<?php print $uid ?>" />
		<input type="hidden" name="aref" id="aref" value="<?php print $aref ?>" />
		<input type="hidden" name="confirm_delete" value="no">
			<div style=" border:1px solid #d0d7de; border-radius:8px;">
				<div class="titletextblack" style="font-weight: bold; font-size: 18px; background-color: #8097b5; padding:12px; border:1px solid #6e8bb1ff ; border-radius:8px 8px 0 0;">
					Edit Appointment
				</div>
					<div class="smlblacktext" style="display:flex; align-items:center; /* background-color: #fcfbdaff; */ gap:12px; padding:12px; border:1px solid #d0d7de; border-radius:8px;">
					Enter the information you would like to update, then save your changes and refresh the browser. To remove an appointment from the schedule, click the delete button.
					</div>
		
				<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 10px 0;">
					
					<!-- Date -->
					<div style="padding:12px;">
						<label for="adate" class="proplabel" style="font-weight: bold; display:block; margin-bottom:8px;">Date</label>
						<input id="adate" type="text" size="36" maxlength="120" tabindex="10" name="adate" value="<?php print $appt_fa["adate"]?>" style="width:60%;">
					</div>
					
					<!-- Time -->
					<div style="padding:12px;">
						<label for="atime" class="proplabel" style="font-weight: bold; display:block; margin-bottom:8px;">Time</label>
						<input id="atime" type="text" size="36" maxlength="60" tabindex="20" name="atime" value="<?php print $appt_fa["atime"] ?>" style="width:60%;">
					</div>
					
					<!-- Site -->
					<div style="padding:12px;">
						<label for="asite" class="proplabel" style="font-weight: bold; display:block; margin-bottom:8px;">Site</label>
						<select name="asite" id="asite" tabindex="40" style="width:60%;">
							<option value="">Select Site</option>
							<?php
							foreach ($site_array as $site) {
								echo '<option value="' . $site['siteid'] . '">' . htmlspecialchars($site['sitename']) . '</option>';
							}
							?>
						</select>
					</div>
					
					<!-- Appointment Reference -->
					<div style="padding:12px;">
						<label for="apptref" class="proplabel" style="font-weight: bold; display:block; margin-bottom:8px;">Appointment Reference</label>
						<input id="apptref" type="text" size="36" maxlength="40" tabindex="30" name="apptref" value="<?php print $appt_fa["aref"] ?>" style="width:60%;" readonly>
					</div>
					
					<!-- Reason -->
					<div style="padding:12px;">
						<label for="arsn" class="proplabel" style="font-weight: bold; display:block; margin-bottom:8px;">Reason</label>
						<select id="arsn" name="arsn" tabindex="40" style="width:60%;">
						<?php
						$rc = count($listapptrsn);
						for ($i = 0; $i < $rc; $i++) {
							if (strcasecmp($appt_fa["arsn"], $listapptrsn[$i][0]) == 0)
							print "<option selected value=\"".$listapptrsn[$i][0]."\">".$listapptrsn[$i][1]."</option>\n";
							else
							print "<option value=\"".$listapptrsn[$i][0]."\">".$listapptrsn[$i][1]."</option>\n";
						}
						?>
						</select>
					</div>
					
					<!-- Site Contact -->
					<div style="padding:12px;">
						<label for="sitecontact" class="proplabel" style="font-weight: bold; display:block; margin-bottom:8px;" >Site Contact</label>
						<input id="sitecontact" type="text" size="36" maxlength="40" tabindex="30" name="sitecontact" value="<?php print $appt_fa["sitecontact"] ?>" style="width:60%;" readonly>
					</div>
					
					<!-- Site Contact Phone -->
					<div style="padding:12px;">
						<label for="sitecontactphone" class="proplabel" style="font-weight: bold; display:block; margin-bottom:8px;" >Site Contact Phone</label>
						<input id="sitecontactphone" type="text" size="36" maxlength="40" tabindex="30" name="sitecontactphone" value="<?php print $appt_fa["sitecontactphone"] ?>" style="width:60%;" readonly>
					</div>
					
				</div>

				<div style="display: flex; justify-content: center; gap: 8px; padding-top: 16px; border-top: 1px solid #d0d7de;">
					<input type="submit" name="edit_appt" class="inputbtn darkblue" value="Save" title="Edit Appointment" style="margin:0px; width: 120px;">
					<input type="submit" name="delete_appt" class="inputbtn darkblue" value="Delete" title="Delete Appointment" style="margin:0; width: 120px;" onclick="if(!confirm('Are you sure you want to delete this appointment? This cannot be undone.')){return false;} this.form.confirm_delete.value='yes';">
				</div>
				<!-- <div class="proplabel">* Required items.</div> -->
				<br>
			</div>
		</form>
	<script>
	function showEditSection(rowData) {
		// Populate the form fields with row data
		document.getElementById('adate').value = rowData.adate || '';
		document.getElementById('atime').value = rowData.atime || '';
		document.getElementById('asite').value = rowData.asite || rowData.siteid || '';
		document.getElementById('apptref').value = rowData.aref || '';
		document.getElementById('aref').value = rowData.aref || '';
		document.getElementById('arsn').value = rowData.arsn || '';
		document.getElementById('sitecontact').value = rowData.sitecontact || '';
		document.getElementById('sitecontactphone').value = rowData.sitecontactphone || '';
		
		// Show the edit section
		document.getElementById('edit-section').style.display = 'block';
		
		// Scroll to the edit section
		document.getElementById('edit-section').scrollIntoView({ behavior: 'smooth' });
	}
	</script>
<?php
	print "</div>";
?>
		</div>
	</div>
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
