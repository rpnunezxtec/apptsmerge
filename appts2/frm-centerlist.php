<?php
// $Id:$
// Uses the appointments database to display and filter the sites

header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$form_name = "frm-centerlist";
$form_file = "frm-centerlist.php";

include("config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
require_once(_AUTHENTX5APPCORE."/cl-forms.php");
date_default_timezone_set(DATE_TIMEZONE);

$myform = new authentxforms();
$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

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
	
$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Read the site rows from the db
	$q = "select * "
		. "\n from site "
		. "\n where display='".CENTERDISPLAY_ON."' "	
		;

	// filters
	$f = "";
	if ($f_activity !== false)
		$f .= "\n and siteactivity='".$sdbh->real_escape_string($f_activity)."' ";
	if ($f_type !== false)
		$f .= "\n and sitetype='".$sdbh->real_escape_string($f_type)."' ";
	if ($f_region !== false)
		$f .= "\n and siteregion='".$sdbh->real_escape_string($f_region)."' ";
	if ($f_state !== false)
		$f .= "\n and siteaddrstate='".$sdbh->real_escape_string($f_state)."' ";
	if ($f_component !== false)
		$f .= "\n and sitecomponent='".$sdbh->real_escape_string($f_component)."' ";

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

	$ss = $sdbh->query($q);
	$dset = array();
	$n = 0;

	if ($ss)
	{
		while ($r = $ss->fetch_assoc())
		{
			$dset[$n]["centeruuid"] = $r["centeruuid"];
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

	$sdbh->close();
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Default-Style" content="text/html;charset=UTF-8" />
<title>Authentx Appointments Management System</title>
<link rel="shortcut icon" type="image/x-icon" href="../appcore/images/icon-authentx.ico" />
<link rel=stylesheet type="text/css" href="../appcore/css/authentx.css" />
<script language="javascript" src="../appcore/scripts/js-formstd.js"></script>
<script language="javascript" src="../appcore/scripts/js-formext.js"></script>
<script language="javascript" src="../appcore/scripts/js-centers.js"></script>
</head>
<body>
<p>
<table cellspacing="0" cellpadding="0" align="center" border="0" width="958">
<tr><td><img height="12" src="../appcore/images/logoslice_1.gif" width="200" border="0" /></td>
</tr>
<tr><td valign="top" background="../appcore/images/box_mtl_ctr.gif">
<table cellspacing="0" cellpadding="0" border="0" width="958">
<tr height="12"><td width="12" height="12"><img height="12" src="../appcore/images/box_mtl_topl.gif" width="12" /></td>
<td background="../appcore/images/box_mtl_top.gif"><img height="12" src="../appcore/images/logoslice_2.gif" width="188" border="0" /></td>
<td width="12"><img height="12" src="../appcore/images/box_mtl_topr.gif" width="12" /></td>
</tr>
<tr valign="top">
<td width="12" background="../appcore/images/box_mtl_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td align="middle" background="../appcore/images/bg_spacer.gif">
<table cellspacing="0" cellpadding="0" border="0" width="934">
<tr><td align="middle">
<table cellspacing="0" cellpadding="0" border="0" width="934">
<tr height="0"><td align="left" width="220"><img height="56" src="../appcore/images/logoslice_3.gif" width="188" border="0" /></td>
<td align="right">
<table cellspacing="0" cellpadding="0" border="0" width="610">
<tr>
<td align="left" width="450">
<table  cellspacing="0" cellpadding="0" border="0" width="450">
<tr height="28"><td valign="top"><span class="siteheading"></span></td>
</tr><tr height="28"><td valign="top"><span class="nameheading"></span></td></tr>
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
<td align="middle" colspan="2"></td>
</tr></table>
</td>
</tr></table>
</td></tr>
<tr height="8" valign="top">
<td></td>
<td></td>
</tr></table>
</td></tr>
<tr><td align="center" valign="bottom">
<table cellspacing="0" cellpadding="0" border="0" width="934">
<tr height="2"><td width="2" height="2"><img height="2" src="../appcore/images/bevel_topl.gif" width="2" /></td>
<td background="../appcore/images/bevel_top.gif" height="2"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="2" height="2"><img height="2" src="../appcore/images/bevel_topr.gif" width="2" /></td>
</tr>
<tr><td width="2" background="../appcore/images/bevel_l.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td valign="center" align="left">
<table border="0" cellspacing="0" cellpadding="10" width="930" bgcolor="#ffffff">
<tr><td align="left">
<table border="0" cellspacing="0" cellpadding="0" style='table-layout:fixed' width="900" bgcolor="#ffffff">
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />
<col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" /><col width="5%" />

<tr height="40"><td colspan="20" valign="top"><span class="siteheading">Centers</span></td></tr>

<tr>
<td colspan="20" valign="top">
<span class="blktext">
Below is a list of Card Issuance Facilities that are currently operational.<br/>  
To find a facility near your location, sort by using the drop column(s) (header descriptions included at the bottom of the site) 
then click the Find Centers button.  If the site is listed as Available, an appointment can be booked at that site via the 
Appointment Scheduling Tool. If a site is listed as unavailable, an appointment cannot currently be scheduled for this center.
<p/>
<strong>NOTE:</strong>&nbsp;Individual Light Activation Station (LAS) locations and hours of operation are not posted on this website. 
For LAS location availability within your agency, please contact your agency&rsquo;s point of contact or program office 
responsible for issuing employee and contractor credentials.
<p/>
</span>
</td>
</tr>

<tr><td>&nbsp;</td></tr>

<form name="filterform" action="<?php print $form_file ?>" method="post" >
<tr height="40">
<td colspan="6" valign="top">
<span class="lblblktext">Activity:</span><br/>
<select tabindex="10" name="f_activity" id="f_activity" style="width:15em;">
<?php $myform->frm_option($form_name, false, $listsiteactivity, true, $f_activity) ?>
<option value="">Any</option>
</select>
</td>
<td colspan="1"></td>
<td colspan="6" valign="top">
<span class="lblblktext">Type:</span><br/>
<select tabindex="20" name="f_type" id="f_type" style="width:15em;">
<?php $myform->frm_option($form_name, false, $listsitetype, true, $f_type) ?>
<option value="">Any</option>
</select>
</td>
<td colspan="1"></td>
<td colspan="6" valign="top">
<span class="lblblktext"></span><br/>
</td>
</tr>

<tr height="40">
<td colspan="6" valign="top">
<span class="lblblktext">Component:</span><br/>
<select tabindex="40" name="f_component" id="f_component" style="width:15em;">
<?php $myform->frm_option($form_name, false, $listcomponent, true, $f_component) ?>
</select>
</td>
<td colspan="1"></td>
<td colspan="6" valign="top">
<span class="lblblktext">State:</span><br/>
<select tabindex="30" name="f_state" id="f_state" style="width:8em;">
<?php $myform->frm_option($form_name, false, $liststates, true, $f_state) ?>
<option value="">Any</option>
</select>
</td>
<td colspan="1"></td>
<td colspan="6" valign="top">
<input type="submit" name="btn_searchfilter" class="btntext" value="Find Centers" />
</td>
</tr>

</form>

<tr height="20"><td colspan="20">&nbsp;</td></tr>

<tr>
<td colspan="20" valign="top">
<table width="100%" border="1" cellpadding="1" cellspacing="0" style="border-collapse:collapse;" >
<tr height="20">
<td width="13%" class="matrixheading" valign="top"><span class="tableheading"><a href="<?php print $form_file."?scol=0" ?>" style="color:blue;">Center</a></span></td>
<td width="14%" class="matrixheading" valign="top"><span class="tableheading"><a href="<?php print $form_file."?scol=1" ?>" style="color:blue;">Address</a></span></td>
<td width="10%" class="matrixheading" valign="top"><span class="tableheading"><a href="<?php print $form_file."?scol=2" ?>" style="color:blue;">City</a></span></td>
<td width="5%" class="matrixheading" valign="top"><span class="tableheading"><a href="<?php print $form_file."?scol=3" ?>" style="color:blue;">ST</a></span></td>
<td width="7%" class="matrixheading" valign="top"><span class="tableheading"><a href="<?php print $form_file."?scol=4" ?>" style="color:blue;">ZIP</a></span></td>
<td width="5%" class="matrixheading" valign="top"><span class="tableheading"><a href="<?php print $form_file."?scol=5" ?>" style="color:blue;">A</a></span></td>
<td width="5%" class="matrixheading" valign="top"><span class="tableheading"><a href="<?php print $form_file."?scol=6" ?>" style="color:blue;">T</a></span></td>
<td width="12%" class="matrixheading" valign="top"><span class="tableheading">Hours</span></td>
<td width="8%" class="matrixheading" valign="top"><span class="tableheading">Status</span></td>
<td width="6%" class="matrixheading" valign="top"><span class="tableheading">Map</span></td>
<td width="15%" class="matrixheading" valign="top"><span class="tableheading">Contact</span></td>
</tr>
	
<?php 
for ($i = 0; $i < $n; $i++)
{
	$r = $dset[$i];
	
	$rowline = $i%2;
	
	print "<tr>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print htmlentities($dset[$i]["name"]);
	print "</span></td>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print htmlentities($dset[$i]["addr"]);
	print "</span></td>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print htmlentities($dset[$i]["city"]);
	print "</span></td>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print htmlentities($dset[$i]["state"]);
	print "</span></td>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print htmlentities($dset[$i]["zip"]);
	print "</span></td>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print htmlentities($dset[$i]["activity"]);
	print "</span></td>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print htmlentities($dset[$i]["type"]);
	print "</span></td>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print $dset[$i]["hours"];
	print "</span></td>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print htmlentities($dset[$i]["status"]);
	print "</span></td>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print "<a href=\"javascript:popupOpener('".$dset[$i]["mapref"]."','centermap',800,800)\" >Map</a>";
	print "</span></td>";
	
	print "<td class=\"matrixline".$rowline."\" ><span class=\"tabletext\">";
	print $dset[$i]["contact"];
	print "</span></td>";
	
	print "</tr>\n";
	
}

?>
</table>
</td>
</tr>

<tr height="20"><td>&nbsp;</td></tr>

<tr>
<td colspan="20" valign="top">
<span class="smlblacktext">
<strong>Center Activity (A)</strong>: <br/>
The Enrollment and Issuance Workstation supports all of the following functions:<br/>
Enrollment and Issuance = Location capable of capturing fingerprints, documents, portrait, and card issuance; (EIWS) <br/>
Re-Issuance = Location capable of re-issuing expired credentials; (EIWS) <br/><br/>
The Light Activation Station and Enrollment and Issuance Workstation supports the following functions:<br/>
Certificate Updates = Location capable of updating the certificates on the card; (EIWS, LAS) <br/>
Card Activation = Credential was centrally printed and is in the cardholder's possession and ready for activation; (EIWS, LAS) <br/>
PIN Reset and Card Unlocks = Location capable of changing known and unknown credential PINs; (EIWS, LAS) <br/><br/>
Fingerprinting (FP) - The location supports the following functions:<br/>
Fingerprint Capture = Location capable of capturing fingerprints as part of the DHS onboarding process; (FP) <br/>
<br/>
<strong>Center Type (T)</strong>: S=Shared Center; D=Dedicated Center for use only by personnel of that Agency
<br/><br/>
<strong>Status: Available: </strong>This facility is currently operational and an appointment can be booked via the Appointment Scheduling Tool.<br/>
<strong>Unavailable: </strong>This facility is currently not operational or an appointment cannot currently be scheduled via the Appointment Scheduling Tool.<br/>
</span>
</td>
</tr>

</table>
</td></tr></table>
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
<table cellspacing="0" cellpadding="0" border="0" width="934">
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
<td width="12" height="14"><img height="14" src="../appcore/images/box_mtl_botl.gif" width="12"></td>
<td valign="top" background="../appcore/images/box_mtl_bot.gif"><img height="1" src="../appcore/images/spacer.gif" width="1" /></td>
<td width="12"><img height="14" src="../appcore/images/box_mtl_botr.gif" width="12" /></td>
</tr>
</table>
</td>
</tr>
</table>
</body></html>
