<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-mailtmpl.html";
$form_name = "templates";
$tab_name = ucfirst($form_name);

include("config.php");
include("vec-clappointments.php");
include('../appcore/vec-clforms.php');
$myform = new authentxforms();
$myappt = new authentxappointments();

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

if (!$tab_mailtmpl)
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

// Get the template data from the database
$dset = array();
$nds = 0;
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	$q_t = "select * from mailtemplate order by mtname";
	$s_t = $dbh->query($q_t);
	if ($s_t)
	{
		$nr = $s_t->num_rows;
	
		while ($r_t = $s_t->fetch_assoc())
		{
			// get the uid and create a MAC
			$dset[$nds]["mtid"] = $r_t["mtid"];
			$dset[$nds]["avc"] = $myappt->session_createmac($r_t["mtid"]);
			$dset[$nds]["mtname"] = $r_t["mtname"];
			$dset[$nds]["mtsubject"] = "";
			$dset[$nds]["mtfrom"] = "";
			if (!empty($r_t["mtfrom"]))
				$dset[$nds]["mtfrom"] = base64_decode($r_t["mtfrom"]);
			if (!empty($r_t["mtsubject"]))
				$dset[$nds]["mtsubject"] = base64_decode($r_t["mtsubject"]);
			
			$nds++;
		}
		$s_t->free();
	}
	$dbh->close();
}
else 
{
	$nr = 0;
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
?>
<div id="content">
	<div class="fullscreenscrollbox">
		<div style="max-width:1100px;margin:0 auto;display:flex;flex-direction:column;gap:16px;padding:8px 16px;">
			<div style="display:flex;flex-direction:column;align-items:stretch;gap:20px;padding:10px 0;">
				<div class="titletextwhite">Templates</div>

<?php
                print "<table class=\"striped\">";
		        print "<thead>";
		        print "<tr class=\"light-xtec-blue\">";
                print "<th class=\"tableheader\">Template</th>";
                print "<th class=\"tableheader\">From</th>";
                print "<th class=\"tableheader\">Subject</th>";
                print "</tr>";
                print "</thead>";
                print "<tbody>";
				for ($i = 0; $i < $nds; $i++)
				{
                    print "<tr>";
                        print "<td><p>";
                            print "<a href=\"javascript:popupOpener('pop-editmtdetail.html?mtid=". urlencode($dset[$i]['mtid']). "&avc=" . urlencode($dset[$i]['avc']). "','editmtdetail',350,500)\" style=\"color: blue;\" title=\" ". htmlentities($dset[$i]['wsname'], ENT_QUOTES, 'UTF-8')." (opens popup)\" aria-label='". htmlentities($dset[$i]['mtname'], ENT_QUOTES, 'UTF-8'). "'>". htmlentities($dset[$i]['mtname'], ENT_QUOTES, 'UTF-8'). "</a>";print "</p></td>";
                        print "<td><p>" . htmlentities($dset[$i]['mtfrom'], ENT_QUOTES, 'UTF-8') . "</p></td>";
                        print "<td><p>" . htmlentities($dset[$i]['mtsubject'], ENT_QUOTES, 'UTF-8') . "</p></td>";
                    print "</tr>";
				}
				print "</table>";
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