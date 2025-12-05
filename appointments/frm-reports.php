<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-reports.html";
$form_name = "reports";
$tab_name = ucfirst($form_name);

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

if (!$tab_reports)
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
	
// date values for today
$dt_y = date("Y");
$dt_m = date("m");
$dt_d = date("d");

// process the report execution request
if (isset($_POST["submit_report"]))
{
	// setup the dates
	if (isset($_POST["sd_d"]))
	{
		$sd_d = $_POST["sd_d"];
		if (($sd_d < 1) || ($sd_d > 31))
			$sd_d = false;
	}
	else
		$sd_d = false;
	
	if (isset($_POST["sd_m"]))
	{
		$sd_m = $_POST["sd_m"];
		if (($sd_m < 1) || ($sd_m > 12))
			$sd_m = false;
	}
	else
		$sd_m = false;
	
	if (isset($_POST["sd_y"]))
	{
		$sd_y = $_POST["sd_y"];
		if (is_nan($sd_y))
			$sd_y = false;
	}
	else
		$sd_y = false;
	
	if (($sd_d === false) || ($sd_m === false) || ($sd_y === false))
		$sdate = false;
	else
		$sdate = str_pad($sd_y, 4, "0", STR_PAD_LEFT)."-".str_pad($sd_m, 2, "0", STR_PAD_LEFT)."-".str_pad($sd_d, 2, "0", STR_PAD_LEFT);
	
	if (isset($_POST["ed_d"]))
	{
		$ed_d = $_POST["ed_d"];
		if (($ed_d < 1) || ($ed_d > 31))
			$ed_d = false;
	}
	else
		$ed_d = false;
	
	if (isset($_POST["ed_m"]))
	{
		$ed_m = $_POST["ed_m"];
		if (($ed_m < 1) || ($ed_m > 12))
			$ed_m = false;
	}
	else
		$ed_m = false;
	
	if (isset($_POST["ed_y"]))
	{
		$ed_y = $_POST["ed_y"];
		if (is_nan($ed_y))
			$ed_y = false;
	}
	else
		$ed_y = false;
	
	if (($ed_d === false) || ($ed_m === false) || ($ed_y === false))
		$edate = false;
	else
		$edate = str_pad($ed_y, 4, "0", STR_PAD_LEFT)."-".str_pad($ed_m, 2, "0", STR_PAD_LEFT)."-".str_pad($ed_d, 2, "0", STR_PAD_LEFT);
		
	// Call the report
	if ($sdate !== false)
	{
		$urlq = "?sd=".urlencode($sdate);
		if ($edate !== false)
			$urlq .= "&ed=".urlencode($edate);
	}
	elseif ($edate !== false)
		$urlq = "?ed=".urlencode($edate);
	else
		$urlq = "";
		
	if (isset($_POST["report"]))
	{
		$rpt = $_POST["report"];
		$reporturl = htmlentities($rpt).$urlq;	

		$myappt->popmeup($reporturl, "report", "toolbar=no,width=800,height=750,location=no,directories=no,status=yes,menubar=yes,scrollbars=yes,resizable=yes");
	}
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



// Only print this table if the privilege bits are present
if ($myappt->checkprivilege(PRIV_RPT))
{
?>
<div id="content">
	<div class="fullscreenscrollbox">
		<div style="max-width:1100px;margin:0 auto;display:flex;flex-direction:column;gap:16px;padding:8px 16px;">
			<div style="display:flex;flex-direction:column;align-items:stretch;gap:20px;padding:10px 0;">
			<div class="titletextwhite">Reports</div>
			<form name="mainform" method="post" action="<?php print $formfile ?>"  autocomplete="off" style="display:flex;flex-direction:column;align-items:stretch;gap:10px;width:100%;">
			<div style=" display:flex;align-items:flex-end; justify-content: start;gap:14px;flex-wrap:wrap;width:100%;">
				<!-- <div class="lblblktext" style="white-space:nowrap;align-self:center;">Filters:</div> -->
				<div style="display:flex;flex-direction:column;gap:4px;min-width:12rem;">
					<label class="lblblktext" for="report" style="text-align:left;font-weight:700;">Select Report</label>
					<select name="report" id="report" style="width:100%;">
						<?php
							$listreportset = $myappt->sortlistarray($listreportset, 1, SORT_ASC, SORT_REGULAR);
							$rc = count($listreportset);
							for ($i = 0; $i < $rc; $i++)
								print "<option value=\"".$listreportset[$i][0]."\">".$listreportset[$i][1]."</option>\n";
						?>
					</select>
				</div>
				<div style="display:flex; flex-direction:column; gap:6px; min-width:8rem;">
					<label class="lblblktext" for="sd_m" style="text-align:left;font-weight:700;">
						Start Date (mm-dd-yyyy)
					</label>

					<div style="display:flex; flex-direction:row; align-items:center; gap:4px;">
						<input type="text" size="2" maxlength="2" tabindex="2" id="sd_m" name="sd_m" value="<?php print $dt_m ?>" />
						-
						<input type="text" size="2" maxlength="2" tabindex="3" id="sd_d" name="sd_d" value="<?php print $dt_d ?>" />
						-
						<input type="text" size="6" maxlength="4" tabindex="4" id="sd_y" name="sd_y" value="<?php print $dt_y ?>" />
					</div>
				</div>
				<div style="display:flex; flex-direction:column; gap:6px; min-width:8rem;">
					<label class="lblblktext" for="sd_m" style="font-weight:700;">
						End Date (mm-dd-yyyy)
					</label>

					<div style="display:flex; flex-direction:row; align-items:center; gap:4px;">
						<input type="text" size="2" maxlength="2" tabindex="2" id="sd_m" name="sd_m" value="<?php print $dt_m ?>" />
						-
						<input type="text" size="2" maxlength="2" tabindex="3" id="sd_d" name="sd_d" value="<?php print $dt_d ?>" />
						-
						<input type="text" size="6" maxlength="4" tabindex="4" id="sd_y" name="sd_y" value="<?php print $dt_y ?>" />
					</div>
				</div>
			</div>
			<div style="display:flex; flex-direction:column;">
				<input type="submit" name="submit_report" class="inputbtn darkblue" value="Run Report">
			</div>
			</form>
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
}
echo "<script>";
echo "useridInp = \"".$userid."\"";
echo "</script>";
?>
</body></html>