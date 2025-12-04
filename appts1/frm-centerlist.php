<?php
// $Id:$
// Uses the appointments database to display and filter the sites

$form_name = "frm-centerlist";
$form_file = "frm-centerlist.html";

header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

include("config.php");
include("vec-clappointments.php");
include("../appcore/vec-clforms.php");
$myappt = new authentxappointments();
$myform = new authentxforms();
date_default_timezone_set(DATE_TIMEZONE);

$apptlink = "<a href=\"index.html\">See Appointments System</a>";
$hours_unavailable = "Contact location to book an appointment";
$addrchars = array("\n", "\r", "\t");

// Process any submitted search terms
if (isset($_POST['btn_searchfilter']))
{
	// Set the submitted filters
	if (isset($_POST["f_activity"]))
	{
		$f_activity = trim($_POST["f_activity"]);
		if ($f_activity == "")
			$f_activity = false;
	}
	else
		$f_activity = false;
	
	if (isset($_POST["f_type"]))
	{
		$f_type = trim($_POST["f_type"]);
		if ($f_type == "")
			$f_type = false;
	}
	else
		$f_type = false;
	
	if (isset($_POST["f_region"]))
	{
		$f_region = trim($_POST["f_region"]);
		if ($f_region == "")
			$f_region = false;
	}
	else
		$f_region = false;
	
	if (isset($_POST["f_component"]))
	{
		$f_component = trim($_POST["f_component"]);
		if ($f_component == "")
			$f_component = false;
	}
	else
		$f_component = false;
	
	if (isset($_POST["f_state"]))
	{
		$f_state = trim($_POST["f_state"]);
		if ($f_state == "")
			$f_state = false;
	}
	else
		$f_state = false;
}
else
{
	// Turn the filters off
	$f_activity = false;
	$f_type = false;
	$f_region = false;
	$f_component = false;
	$f_state = false;
}

// Sorting column
if (isset($_GET["scol"]))
{
	$scol = trim($_GET["scol"]);
	if (is_nan($scol))
		$scol = 0;
}
else
	$scol = 0;
	
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if(!($dbh->connect_error))
{
	// Read the site rows from the db
	$q = "select * "
		. "\n from site "
		. "\n where display='".CENTERDISPLAY_ON."' "	
		;

	// filters
	$f = "";
	if ($f_activity !== false)
		$f .= "\n and siteactivity='".$dbh->real_escape_string($f_activity)."' ";
	if ($f_type !== false)
		$f .= "\n and sitetype='".$dbh->real_escape_string($f_type)."' ";
	if ($f_region !== false)
		$f .= "\n and siteregion='".$dbh->real_escape_string($f_region)."' ";
	if ($f_state !== false)
		$f .= "\n and siteaddrstate='".$dbh->real_escape_string($f_state)."' ";
	if ($f_component !== false)
		$f .= "\n and sitecomponent='".$dbh->real_escape_string($f_component)."' ";

	$q .= $f;

	// Sorting
	switch ($scol)
	{
		case 0:
		default:
			// Sort on col 0 (Center name)
			$q .= "\n order by sitename ";
			break;
				
		case 1:
			// Sort on col 1 (street address)
			$q .= "\n order by siteaddress, siteaddrcity ";
			break;
				
		case 2:
			// Sort on col 2 (City)
			$q .= "\n order by siteaddrcity, siteaddrstate ";
			break;
				
		case 3:
			// Sort on col 3 (state)
			$q .= "\n order by siteaddrstate, siteaddrcity ";
			break;
				
		case 4:
			// Sort on col 4 (zip)
			$q .= "\n order by siteaddrzip, siteaddrcity ";
			break;
				
		case 5:
			// Sort on col 5 (activity)
			$q .= "\n order by siteactivity, siteaddrstate, siteaddrcity ";
			break;
				
		case 6:
			// Sort on col 6 (type)
			$q .= "\n order by sitetype, siteaddrstate, siteaddrcity ";
			break;
	}

	$ss = $dbh->query($q);
	$dset = array();
	$n = 0;

	if ($ss)
	{
		while ($r = $ss->fetch_assoc())
		{
			$dset[$n]["name"] = $r["sitename"];
			$dset[$n]["addr"] = str_replace($addrchars, " ", $r["siteaddress"]);
			$dset[$n]["city"] = $r["siteaddrcity"];
			$dset[$n]["state"] = $r["siteaddrstate"];
			$dset[$n]["zip"] = $r["siteaddrzip"];
			$dset[$n]["region"] = $r["siteregion"];
			$dset[$n]["component"] = $r["sitecomponent"];
			$dset[$n]["activity"] = $r["siteactivity"];
			$dset[$n]["type"] = $r["sitetype"];
			if ($r["status"] == 0)
				$dset[$n]["hours"] = $hours_unavailable;
			else
				$dset[$n]["hours"] = $apptlink;
			
			$mapurl = "http://maps.google.com/maps?f=q&h1=en&q=";
			$s = str_replace(' ', '+', $dset[$n]["addr"]);
			$mapurl .= $s.",";
			$s = str_replace(' ', '+', $dset[$n]["city"]);
			$mapurl .= $s.",";
			$s = str_replace(' ', '+', $dset[$n]["state"]);
			$mapurl .= $s.",";
			$s = str_replace(' ', '+', $dset[$n]["zip"]);
			$mapurl .= $s.",";
			$s = str_replace(' ', '+', $r["siteaddrcountry"]);
			$mapurl .= $s;
			$dset[$n]["mapref"] = $mapurl;
			
			$contact = "";
			if (trim($r["sitecontactname"]) != "")
				$contact .= "Name: ".$r["sitecontactname"]."<br/>\n";
			if (trim($r["sitecontactphone"]) != "")
				$contact .= "Phone: ".$r["sitecontactphone"]."<br/>\n";
			if (trim($r["sitenotifyemail"]) != "")
				$contact .= "Email: ".$r["sitenotifyemail"]."<br/>\n";
			$dset[$n]["contact"] = $contact;

			if ($r["status"] == 0)
				$dset[$n]["status"] = "Unavailable";
			else
				$dset[$n]["status"] = "Available";
			
			$n++;
		}
		$ss->free();
	}

	$dbh->close();
}

// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams['css'] = $cfg_stdcss;
$headparams["jscript_file"] = $cfg_stdjscript;
// $headparams["jscript_file"][] = "../appcore/scripts/js-tablesort.js";
// $headparams["jscript_file"][] = "../appcore/scripts/js-checkall.js";
// $headparams["jscript_file"][] = "../appcore/scripts/js-expandall.js";
$myform->frmrender_head($headparams);

// The page container
print "<div class=\"main\">\n";

// Top banner
$topparams = array();
$topparams["logoimgurl"]   = $cfg_logoimgurl;
$topparams["logoalt"]      = $cfg_logoalt;
$topparams["bannerheading1"]= BANNERHEADING1;
$topparams["bannerheading2"]= BANNERHEADING2;
$topparams["pageportal"] = $page_certportal;
// $topparams["dropdown"]     = array_merge($cfg_userdropdown, $cfg_tabs);
$myform->frmrender_topbanner($topparams);

$asideparams = array();
$asideparams["side"] = "aSide";
$myform->frmrender_side($asideparams);

$bsideparams = array();
$bsideparams["side"] = "bSide";
$bsideparams["authentxlogoimgurl"] = $cfg_authentxlogourl;

$footerparams = array();
$footerparams["poweredbylogo"] = $cfg_authentxlogourl_white;
$footerparams["serverid"] = SERVERID;
?>

<div id="content">
	<div class="fullscreenscrollbox">
		<div style="display:flex;flex-direction:column;align-items:center;gap:28px;padding:10px 0;">
			<div class="titletextblue">FRB Centers</div>
			<div class="blktext">
				Below is a list of FRB PIV Card Issuance Facilities (PCIF) that are currently operational.
				To find a PCIF near your location, sort by using the drop column(s) (header descriptions included at the bottom of the site) 
				then click the Find Centers button.  If the site is listed as Available, an appointment can be booked at that site via the 
				Appointment Scheduling Tool. If a site is listed as unavailable, an appointment cannot currently be scheduled for this PCIF.
				<br>
				<br>
				<strong>NOTE:</strong>&nbsp;Individual Light Activation Station (LAS) locations and hours of operation are not posted on this website. 
				For LAS location availability within your agency, please contact your agency&rsquo;s HSPD-12 point of contact or program office 
				<br>
				&nbsp;
			</div>

			<form name="filterform" action="<?php print $form_file ?>" method="post" style="display:flex; flex-direction:column; align-items:center; gap:24px; width:100%">
				<div style="display:flex; flex-wrap:wrap; align-items:flex-end;justify-content:space-between;gap:0 60px;width:70%;">
					<div style="flex:0 0 18em; display:flex; flex-direction:column; gap:12px;">
						<div style="display:flex; flex-direction:column;">
							<label for="f_activity" style="margin-bottom:4px;">Activity:</label>
							<select tabindex="10" name="f_activity" id="f_activity" style="width:100%;">
							<?php $myform->frm_option($form_name, false, $listsiteactivity, true, $f_activity) ?>
							<option value="">Any</option>
							</select>
						</div>
						<div style="display:flex; flex-direction:column;">
							<label for="f_component" style="margin-bottom:4px;">Component:</label>
							<select tabindex="40" name="f_component" id="f_component" style="width:100%;">
							<?php $myform->frm_option($form_name, false, $listcomponent, true, $f_component) ?>
							</select>
						</div>
					</div>
					<div style="flex:0 0 18em; display:flex; flex-direction:column; gap:12px;">
						<div style="display:flex; flex-direction:column;">
							<label for="f_type" style="margin-bottom:4px;">Type:</label>
							<select tabindex="20" name="f_type" id="f_type" style="width:100%;">
							<?php $myform->frm_option($form_name, false, $listsitetype, true, $f_type) ?>
							<option value="">Any</option>
							</select>
						</div>
						<div style="display:flex; flex-direction:column;">
							<label for="f_state" style="margin-bottom:4px;">State:</label>
							<select tabindex="30" name="f_state" id="f_state" style="width:100%;">
							<option value="">Any</option>
							<?php $myform->frm_option($form_name, false, $liststates, true, $f_state) ?>
							</select>
						</div>
					</div>

					<div style="flex:0 0 12em; display:flex; justify-content:flex-end; padding-top: 20px;">
						<input type="submit" name="btn_searchfilter" class="inputbtn darkblue" value="Find Centers"/>
					</div>
				</div>
			</form>
<br>
<table width="100%" border="1" class="table" cellspacing="0" cellpadding="1"  style="border-collapse:collapse;" >
	<tr class="tableheading">
		<th class="tablehead" style="width: 10%; text-align: center;"><a href="<?php print $form_file."?scol=0" ?>" style="color:blue;">Center</a></th>
		<th class="tablehead" style="width: 14%; text-align: center;"><a href="<?php print $form_file."?scol=1" ?>" style="color:blue;">Address</a></th>
		<th class="tablehead" style="width: 10%; text-align: center;"><a href="<?php print $form_file."?scol=2" ?>" style="color:blue;">City</a></th>
		<th class="tablehead" style="width: 5%; text-align: center;"><a href="<?php print $form_file."?scol=3" ?>" style="color:blue;">ST</a></th>
		<th class="tablehead" style="width: 7%; text-align: center;"><a href="<?php print $form_file."?scol=4" ?>" style="color:blue;">ZIP</a></th>
		<th class="tablehead" style="width: 5%; text-align: center;"><a href="<?php print $form_file."?scol=5" ?>" style="color:blue;">A</a></th>
		<th class="tablehead" style="width: 5%; text-align: center;"><a href="<?php print $form_file."?scol=6" ?>" style="color:blue;">T</a></th>
		<th class="tablehead" style="width: 12%; text-align: center;">Hours</th>
		<th class="tablehead" style="width: 8%; text-align: center;">Status</th>
		<th class="tablehead" style="width: 6%; text-align: center;">Map</th>
		<th class="tablehead" style="width: 15%; text-align: center;">Contact</th>
	</tr>
		
	<?php 
	for ($i = 0; $i < $n; $i++)
	{
		$r = $dset[$i];
		
		$rowline = $i%2;
		
		print "<tr>";
		
		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print htmlentities($dset[$i]["name"]);
		print "</span></td>";
		
		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print htmlentities($dset[$i]["addr"]);
		print "</span></td>";
		
		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print htmlentities($dset[$i]["city"]);
		print "</span></td>";
		
		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print htmlentities($dset[$i]["state"]);
		print "</span></td>";
		
		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print htmlentities($dset[$i]["zip"]);
		print "</span></td>";
		
		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print htmlentities($dset[$i]["activity"]);
		print "</span></td>";
		
		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print htmlentities($dset[$i]["type"]);
		print "</span></td>";
		
		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print $dset[$i]["hours"];
		print "</span></td>";
		
		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print htmlentities($dset[$i]["status"]);
		print "</span></td>";

		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print "<a href=\"javascript:popupOpener('".$dset[$i]["mapref"]."','centermap',800,800)\" >Map</a>";
		print "</span></td>";
		
		print "<td style=\" text-align:center;\" class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
		print $dset[$i]["contact"];
		print "</span></td>";
		
		print "</tr>\n";
		
	}

?>
</table>

		<div class="titletextblue">Center Activity (A):</div>
		<div class="blktext">
			The Enrollment and Issuance Workstation supports all of the following functions:<br/>
			Enrollment and Issuance = Location capable of capturing fingerprints, documents, portrait, and card issuance; (EIWS) <br/>
			Re-Issuance = Location capable of re-issuing expired credentials; (EIWS) <br/><br/>
			The Light Activation Station and Enrollment and Issuance Workstation supports the following functions:<br/>
			Certificate Updates = Location capable of updating the certificates on the card; (EIWS, LAS) <br/>
			Card Activation = Credential was centrally printed and is in the cardholder's possession and ready for activation; (EIWS, LAS) <br/>
			PIN Reset and Card Unlocks = Location capable of changing known and unknown credential PINs; (EIWS, LAS) <br/><br/>
			Fingerprinting (FP): The location supports the following functions:<br/>
			Fingerprint Capture = Location capable of capturing fingerprints as part of the FRB onboarding process; (FP) <br/>
			<br/>
			<strong>Center Type (T)</strong>: S=Shared Center; D=Dedicated Center for use only by personnel of that Agency
			<br/><br/>
			<strong>Status: Available: </strong>This PCIF is currently operational and an appointment can be booked via the Appointment Scheduling Tool.<br/>
			<strong>Unavailable: </strong>This PCIF is currently not operational or an appointment cannot currently be scheduled via the Appointment Scheduling Tool.<br/>
		</div>
		</div>
	</div>
</div>
	<?php
		$myform->frmrender_side($bsideparams);
		print "</div>\n";// end of inner-flex class
	?>	
</div>
	<?php
		$myform->frmrender_footer_wlogo($footerparams);
	?>	
</body></html>
