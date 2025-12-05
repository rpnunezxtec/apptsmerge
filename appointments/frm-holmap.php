<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-holmap.html";
$form_name = "holidays";
$tab_name = ucfirst($form_name);
$rpp= 25;

include("config.php");
include("vec-clappointments.php");
include('../appcore/vec-clforms.php');
$myform = new authentxforms();
$myappt = new authentxappointments();
date_default_timezone_set(DATE_TIMEZONE);

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

if (!$tab_holmaps)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	$myappt->vectormeto($page_denied);
}

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
	
if (isset($_GET["calyear"]))
	$calyear = $_GET["calyear"];
else
	$calyear = date('Y');
if ((!is_numeric($calyear)) || ($calyear < 1970))
	$calyear = date('Y');

// Get the holmap data from the database
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	// process the form submission
	if (isset($_POST["submit_holmap_save"]))
	{
		// can't save unless privileges allow
		if ($myappt->checkprivilege(PRIV_HOLMAP) !== true)
		{
			print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
		}
		else 
		{
			// saves the current set of holidays into the named map in the database
			$badpost = false;
			// get the submitted holiday map name and the value.
			if (isset($_POST["hmapname"]))
				$hmapname = $_POST["hmapname"];
			else
				$badpost = true;
			if ($hmapname == '')
				$badpost = true;
			if (isset($_POST["holidaymap"]))
				$hmapvalue = $_POST["holidaymap"];
			else
				$badpost = true;
			if (strlen($hmapvalue) != 96)
				$badpost = true;
	
			if ($badpost)
				print "<script type=\"text/javascript\">alert('Bad map name or value.')</script>\n";
			else
			{
				// See if the mapname already exists (ie update or insert)
				$q_map = "select * "
					. "\n from holidaymap "
					. "\n where mapname='".$dbh->real_escape_string($hmapname)."' "
					;
				$s_map = $dbh->query($q_map);
				if ($s_map)
				{
					$n_map = $s_map->num_rows;
					if ($n_map > 0)
					{
						$r_map = $s_map->fetch_assoc();
						$hmapid = $r_map["hmapid"];
						
						// perform the update
						$q_umap = "update holidaymap "
							. "\n set holmap='".$dbh->real_escape_string($hmapvalue)."' "
							. "\n where hmapid='".$hmapid."' "
							. "\n limit 1 "
							;
						$s_umap = $dbh->query($q_umap);
						// log success
						if ($s_umap)
						{
							$logstring = "Holiday map ".$hmapname." (".$hmapid.") updated";
							$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_HOLMAP);
						}
					}
					else
					{
						// perform the insert
						$q_umap = "insert into holidaymap set "
							. "\n mapname='".$dbh->real_escape_string($hmapname)."', "
							. "\n holmap='".$dbh->real_escape_string($hmapvalue)."' "
							;
						$s_umap = $dbh->query($q_umap);
						
						if ($s_umap)
						{
							$hmapid = $dbh->insert_id;
							$logstring = "Holiday map ".$hmapname." (".$hmapid.") created";
							$myappt->createlogentry($dbh, $logstring, $myappt->session_getuuid(), ALOG_HOLMAP);
						}
						else
							print "<script type=\"text/javascript\">alert('Error: ".htmlentities($dbh->error).".')</script>\n";
					}
					$s_map->free();
				}
			}
		}
	}
	
	// read the maps for the selection dropdown
	$q_hmaps = "select * "
		. "\n from holidaymap "
		;
	$s_hmaps = $dbh->query($q_hmaps);
	// create a list array with the map data and names
	$listholidaymap = array();
	$n_hol = 0;
	if ($s_hmaps)
	{
		while ($r_hmaps = $s_hmaps->fetch_assoc())
		{
			$listholidaymap[$n_hol][0] = $r_hmaps["holmap"];
			$listholidaymap[$n_hol][1] = $r_hmaps["mapname"];
			$n_hol++;
		}
		$s_hmaps->free();
	}
	
	$dbh->close();
}
else 
{
	$listholidaymap = array();
	$n_hol = 0;
}

// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams['css'] = array_merge($cfg_stdcss, ['../appcore/css/authentxcalendar.css']);
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

$myform->frmrender_bodytag($bodyparams);

// The page container
print "<div class=\"main\">\n";

// Top banner
$topparams = array();
$topparams["logoimgurl"]   = $cfg_logoimgurl;
$topparams["logoalt"]      = $cfg_logoalt;
$topparams["bannerheading1"]= BANNERHEADING1;
$topparams["bannerheading2"]= BANNERHEADING2;
$topparams["pageportal"] = $page_certportal;
$topparams["dropdown"]     = array_merge($cfg_userdropdown, $cfg_tabs);
$myform->frmrender_topbanner($topparams);

// Left side
$asideparams = array();
$asideparams["tabs"] = $cfg_tabs;
$asideparams["tabon"] = $tab_name;
$asideparams["side"] = "aSide";
$myform->frmrender_side($asideparams);

$tableparams = array();
$tableparams["title"]   = $cfg_forms[$form_name]["table_title"];
$tableparams["data"]    = $tokenmatrix;
$tableparams["columns"] = $cfg_forms[$form_name]["table_columns"];

$bsideparams = array();
$bsideparams["side"] = "bSide";
$bsideparams["firstname"] = $firstname;
$bsideparams["userdropdown"] = $cfg_userdropdown;
$bsideparams["authentxlogoimgurl"] = $cfg_authentxlogourl;

$footerparams = array();
$footerparams["poweredbylogo"] = $cfg_authentxlogourl_white;
$footerparams["serverid"] = SERVERID;

?>
<div id="content">
	<div class="buttonrow">
		<div class="fullscreenscrollbox">
		<div class="titletextwhite">Holidays</div>
			<form name="mapform" method="POST" autocomplete="off" id="mapform" action="<?php print $form_file ?>?calyear=<?php print $calyear ?>" >
			<input type="hidden" name="avc" id="avc" value="<?php print $avc ?>" />
				<div class="inputtitlerow3">
					<div class="inputtitle3 inputtitlespacer2">
						<label for="holidaymap">View/Edit Template</label>
						<select name="holidaymap" id="holidaymap" style="width:80%;" onchange="mapholdata()">
							<?php $myform->frm_option($form_name, false, $listholidaymap, true, false) ?>
						</select>
					</div>
					<div class="inputtitle3 inputtitlespacer2">
						<label for="hmapname">Template Name</label>
						<input type="text" style="width:80%;" maxlength="30" class="" name="hmapname" id="hmapname" value="">
					</div>
					<div class="inputtitle3 inputtitlespacer2">
						<input type="submit" name="submit" value="Save Template" class="inputbtn darkblue">
					</div>
				</div>
			</form>
			<div class="inputtitlerow1" style="margin-top:0;">
				<div class="titletextwhite center">
					<?php
					if ($calyear == 1970)
						print " ".$calyear." <a href=\"".$form_file."?calyear=".($calyear+1)."\">-&gt;</a>\n";
					else
						print "<a href=\"".$form_file."?calyear=".($calyear-1)."\" title=\"Previous year\">&lt;-</a> ".$calyear." <a href=\"".$form_file."?calyear=".($calyear+1)."\" title=\"Next year\">-&gt;</a>\n";
					?>
				</div>
			</div>
<div class="inputtitlerow4" style="margin-top:0;">
			<?php
			for ($calrow = 0; $calrow < 3; $calrow++)
			{
				for ($calcol = 0; $calcol < 4; $calcol++)
				{
					$calmonthnum = ($calrow * 4) + $calcol + 1;
					// the day of week of the month start: 0=sun  .. 6=sat
					$utime = strtotime($calyear.'/'.$calmonthnum.'/01');
					$calwd = date('w', $utime);
					$calndays = date('t', $utime);
					$calmonthname = date('F', $utime);
			?>
					<table cellspacing="0" cellpadding="0" border="0" align="center" style="padding-top:2%;">
					<tr><th colspan="7" class="calmonthname"><?php print $calmonthname ?></th></tr>
					<tr class="caldays">
						<th class="caldays">S</th>
						<th class="caldays">M</th>
						<th class="caldays">T</th>
						<th class="caldays">W</th>
						<th class="caldays">T</th>
						<th class="caldays">F</th>
						<th class="caldays">S</th>
					</tr>
			<?php
					// output the month up to 6 rows of 7 columns
					$daynum = 1;
					$tdd = 1;
				
					for ($r = 0; $r < 6; $r++)
					{
						print "<tr>\n";
						for ($d = 0; $d < 7; $d++)
						{
							// the bit number in the holiday map. Each cell needs to be identified by its bit number
							$bitnum = 32 * ($calmonthnum - 1) + ($daynum -1);
							if (($daynum == 1) && ($d < $calwd))
								$strt = 0;
							else
								$strt = 1;
						
							// print the trailing squares
							if ($daynum > $calndays)
							{
								if (($d == 0) || ($d == 6))
									print "<td class=\"calweekend\"><div>";
								else
									print "<td class=\"calweekday\"><div>";
								print "&nbsp;";
							}
							else
							{
								if ($strt)
								{
									// print the squares with dates in them. These need an id for js style mods.
									if (($d == 0) || ($d == 6))
										print "<td class=\"calweekend\" id=\"calbit".$bitnum."\" onclick=\"cellprocess('calbit".$bitnum."')\"><div>";
									else
										print "<td class=\"calweekday\" id=\"calbit".$bitnum."\" onclick=\"cellprocess('calbit".$bitnum."')\"><div>";
									print "<div class=\"caldate\">".$daynum."</div>";
								}
								else
								{
									// print the leading squares
									if (($d == 0) || ($d == 6))
										print "<td class=\"calweekend\"><div>";
									else
										print "<td class=\"calweekday\"><div>";
									print "&nbsp;";
								}
							}
							print "</div>";
							if ($strt)
								$daynum++;
							print "</td>\n";
						}
						print "</tr>\n";
					}
					// end of month table
					print "</table>\n";
				}
			}
			?>
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