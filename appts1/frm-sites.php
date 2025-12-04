<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-sites.html";
$form_name = "sites";
$tab_name = ucfirst($form_name);
$rpp= 25;

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

if (!$tab_sites)
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
	
$f_component = false;
$f_status = false;
$f_state = false;
$urlf = "";

// privilege to 'add' a site
$priv_screate = $myappt->checkprivilege(PRIV_SCREATE);

// find out what page we are on
if (isset($_GET["pg"]))
{
	$pg = $_GET["pg"];
	if (is_nan($pg))
		$pg = 1;
}
else
	$pg = 1;

// Filters for other pages
if (isset($_GET["f_component"]))
{
	$f_component = $_GET["f_component"];
	if (trim($f_component == ""))
		$f_component = false;
	else
		$urlf .= "&f_component=".urlencode($f_component);
}

if (isset($_GET["f_status"]))
{
	$f_status = $_GET["f_status"];
	if (!is_numeric($f_status))
		$f_status = false;
	else
		$urlf .= "&f_status=".urlencode($f_status);
}

if (isset($_GET["f_state"]))
{
	$f_state = $_GET["f_state"];
	if (trim($f_state == ""))
		$f_state = false;
	else
		$urlf .= "&f_state=".urlencode($f_state);
}

// Posted filters
if (isset($_POST["btn_filter"]))
{
	// component
	if (isset($_POST["f_component"]))
	{
		$f_component = trim($_POST["f_component"]);
		if ($f_component == "")
			$f_component = false;
		else
			$urlf .= "&f_component=".urlencode($f_component);
	}

	// status
	if (isset($_POST["f_status"]))
	{
		$f_status = $_POST["f_status"];
		if (!is_numeric($f_status))
			$f_status = false;
		else
			$urlf .= "&f_status=".urlencode($f_status);
	}
	
	// state
	if (isset($_POST["f_state"]))
	{
		$f_state = trim($_POST["f_state"]);
		if ($f_state == "")
			$f_state = false;
		else
			$urlf .= "&f_state=".urlencode($f_state);
	}
}

// Get the site data from the database
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	$q_site = "select siteid, "
		. "\n sitename, "
		. "\n sitecomponent, "
		. "\n sitetype, "
		. "\n siteactivity, "
		. "\n sitecontactphone, "
		. "\n sitecontactname, "
		. "\n siteaddrstate, "
		. "\n siteaddrcity, "
		. "\n timezone, "
		. "\n hmapid, "
		. "\n slottime, "
		. "\n siteblockout, "
		. "\n status "
		. "\n from site "
		. "\n where siteid>0 "
		;
		
	if ($f_state !== false)
		$q_site .= "\n and siteaddrstate='".$dbh->real_escape_string($f_state)."' ";
	if ($f_component !== false)
		$q_site .= "\n and sitecomponent='".$dbh->real_escape_string($f_component)."' ";
	if ($f_status !== false)
		$q_site .= "\n and status='".$dbh->real_escape_string($f_status)."' ";
	
	$q_site .= "\n order by siteaddrstate, siteaddrcity, sitename ";
	
	$s_site = $dbh->query($q_site);
	if ($s_site)
	{
		$rnum = 1;
		$dset = array();
		$nds = 0;
		$nr = $s_site->num_rows;
		
		while ($r_site = $s_site->fetch_assoc())
		{
			if ($rpp > 0)
				$rptest = ($rnum > ($rpp * ($pg-1))) && ($rnum <= ($rpp * $pg));
			else
				$rptest = 1;
			
			if ($rptest)
			{
				$dset[$nds]["sitename"] = $r_site["sitename"];
				$dset[$nds]["status"] = ($r_site["status"] == 0 ? "unavailable" : "available");
				$dset[$nds]["slottime"] = $r_site["slottime"];
				$dset[$nds]["siteid"] = $r_site["siteid"];
				$dset[$nds]["timezone"] = $r_site["timezone"];
				$dset[$nds]["sitecomponent"] = $r_site["sitecomponent"];
				$dset[$nds]["sitetype"] = $r_site["sitetype"];
				$dset[$nds]["siteactivity"] = $r_site["siteactivity"];
				$dset[$nds]["siteaddrstate"] = $r_site["siteaddrstate"];
				$dset[$nds]["siteaddrcity"] = $r_site["siteaddrcity"];
				$dset[$nds]["siteblockout"] = $r_site["siteblockout"];
				if ($dset[$nds]["siteblockout"] == NULL)
					$dset[$nds]["siteblockout"] = 0;
					
				$siteid = $r_site["siteid"];
				// Find the number of WS for this site
				$q_ws = "select wsid, siteid "
						. "\n from workstation "
						. "\n where siteid='".$siteid."'"
						;
				$s_ws = $dbh->query($q_ws);
				if ($s_ws)
				{
					$dset[$nds]["numws"] = $s_ws->num_rows;
					$s_ws->free();
				}
				else
					$dset[$nds]["numws"] = 0;
						
				// Find the holiday map name for this site
				$hmapid = $r_site["hmapid"];
				if ($hmapid != NULL)
				{
					$q_hol = "select hmapid, mapname "
							. "\n from holidaymap "
							. "\n where hmapid='".$hmapid."' "
							;
					$s_hol = $dbh->query($q_hol);
					if ($s_hol)
					{
						$r_hol = $s_hol->fetch_assoc();
						$dset[$nds]["hmapname"] = $r_hol["mapname"];
						$s_hol->free();
					}
					else
						$dset[$nds]["hmapname"] = "-";
				}
				else
					$dset[$nds]["hmapname"] = "-";
					
				$dset[$nds]["avc"] = $myappt->session_createmac($siteid);
				
				$rnum++;
				$nds++;
			}
		}
		$s_site->free();
	}
	
	$dbh->close();
}
else 
{
	$dset = array();
	$nds = 0;
	$nr = 0;
}

// calculate the number of pages to show
$np = intval($nr/$rpp);
if (($nr % $rpp) > 0)
	$np++;

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
			<div class="titletextwhite">Sites</div>
			<form name="usearch" method="post" action="<?php print $formfile ?>" autocomplete="off" style="display:flex;flex-direction:column;align-items:stretch;gap:10px;width:100%;">
			<div style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;width:100%;">
				<div class="lblblktext" style="white-space:nowrap;align-self:center;font-weight:700;">Filters:</div>
			</div>
			<div style=" display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;width:100%;">
				<!-- <div class="lblblktext" style="white-space:nowrap;align-self:center;">Filters:</div> -->
				<div style="display:flex;flex-direction:column;gap:4px;min-width:12rem;">
					<label class="lblblktext" for="f_component" style="text-align:left;font-weight:700;">Component</label>
					<select name="f_component" id="f_component" style="width:100%;">
						<?php
						$listcomponent = $myappt->sortlistarray($listcomponent, 1, SORT_ASC, SORT_REGULAR);
						$rc = count($listcomponent);
						for ($i = 0; $i < $rc; $i++) {
							if (strcasecmp($listcomponent[$i][0], $f_component) == 0)
							print "<option selected value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
							else
							print "<option value=\"".$listcomponent[$i][0]."\">".$listcomponent[$i][1]."</option>\n";
						}
						?>
					</select>
</div>
				<div style="display:flex;flex-direction:column;gap:4px;min-width:10rem;">
					<label class="lblblktext" for="f_status" style="text-align:left;font-weight:700;">Status</label>
					<select name="f_status" id="f_status" style="width:100%;">
						<?php
						$rc = count($listuserstatus);
						$selected = false;
						for ($i = 0; $i < $rc; $i++) {
							if ($f_status == $listuserstatus[$i][0]) {
							print "<option selected value=\"".$listuserstatus[$i][0]."\">".$listuserstatus[$i][1]."</option>\n";
							$selected = true;
							} else {
							print "<option value=\"".$listuserstatus[$i][0]."\">".$listuserstatus[$i][1]."</option>\n";
							}
						}
						if ($selected === true)
							print "<option value=\"\">Any</option>\n";
						else
							print "<option selected value=\"\">Any</option>\n";
						?>
					</select>
				</div>
				<div style="display:flex;flex-direction:column;gap:4px;min-width:8rem;">
					<label class="lblblktext" for="f_adminonly" style="text-align:left;font-weight:700;">Admin Only</label>
					<select name="f_adminonly" id="f_adminonly" style="width:100%;">
						<?php
						$rc = count($listyesnoint);
						for ($i = 0; $i < $rc; $i++) {
							if ($f_adminonly == $listyesnoint[$i][0])
							print "<option selected value=\"".$listyesnoint[$i][0]."\">".$listyesnoint[$i][1]."</option>\n";
							else
							print "<option value=\"".$listyesnoint[$i][0]."\">".$listyesnoint[$i][1]."</option>\n";
						}
						?>
					</select>
				</div>
				<div style="display:flex;align-items:end;">
					<input type="submit" name="btn_filter" class="inputbtn darkblue" value="Apply" />
				</div>
				<div style="display:flex; align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto;">
					<input type="text" name="namesearch" id="namesearch" value="<?php print htmlentities($usearch) ?>" size="30" maxlength="30" placeholder="Name search.." />
					<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto;">
						<input type="submit" name="btn_usearch" class="inputbtn darkblue" value="Search" />
					</div>
				</div>
			</div>
			</form>

<?php
// Only print this table if the privilege bits are present
if ($myappt->checkprivilege(PRIV_SCREATE) || $myappt->checkprivilege(PRIV_SITEEDIT) || $myappt->checkprivilege(PRIV_SHOURS) || $myappt->checkprivilege(PRIV_SSTAT))
{
	// print page numbers for records
	print "<div style='display:flex;align-items:center;gap:8px;margin:6px 0 4px;'>";
	print "<span class='pageon' style='font-weight:bold;'>Page:</span>";
	for ($i = 0; $i < $np; $i++)
	{
		if ($pg == $i+1)
			print "<span class='pageon' style='padding:2px 6px;'>".($i+1)."</span>";
		else
			print "<a href=\"".htmlentities($formfile)."?pg=".($i+1).$urlf."\" ".
						"style='text-decoration:none;'><span class='pageoff' style='padding:2px 6px;'>".($i+1)."</span></a>";
	}
	print "</div>";	

	print "<table class=\"striped\">";
	print "<thead>";
	print "<tr class=\"light-xtec-blue\">";
	print "<th class=\"tableheader\">Site Name</th>";
	print "<th class=\"tableheader\">State</th>";					
	print "<th class=\"tableheader\">City</th>";					
	print "<th class=\"tableheader\"># WS</th>";					
	print "<th class=\"tableheader\">Status</th>";					
	print "<th class=\"tableheader\">Slot</th>";					
	print "<th class=\"tableheader\">Blockout</th>";					
	print "<th class=\"tableheader\">Holiday Map</th>";					
	print "</tr>";
	print "</thead>";
	print "<tbody>";

if ($priv_screate && $_axsitesync_enable === false)
{
	print "<tr>";
	print "<td><p>";
	print "<a href=\"javascript:popupOpener('pop-editwsdetail.html','editwsdetail',350,500)\" style=\"color: blue;\" aria-label=\"Add (opens popup)\" title=\"Add (opens popup)\">Add...</a>";
	print "</p></td>";
	print "<td><p>&nbsp;</p></td>";
	print "<td><p>&nbsp;</p></td>";
	print "<td><p>&nbsp;</p></td>";
	print "<td><p>&nbsp;</p></td>";
	print "<td><p>&nbsp;</p></td>";
	print "<td><p>&nbsp;</p></td>";
	print "<td><p>&nbsp;</p></td>";
	print "</tr>";
}
for ($i = 0; $i < $nds; $i++)
{
	// output each row for this page
	print "<tr>";
	print "<td><p>";
	print "<a href=\"javascript:popupOpener('pop-editsitedetail.html?siteid=" . urlencode($dset[$i]['siteid']) . "&avc=" . urlencode($dset[$i]['avc'])." ','editsitedetail',350,500)\" style=\"color: blue;\" title=\" ". htmlentities($dset[$i]['sitename'], ENT_QUOTES, 'UTF-8')." (opens popup)\" aria-label=\" ". htmlentities($dset[$i]['sitename'], ENT_QUOTES, 'UTF-8')." (opens popup)\">" . htmlentities($dset[$i]['sitename'], ENT_QUOTES, 'UTF-8'). "</a>";print "</p></td>";
	print "<td><p>" . htmlentities($dset[$i]['siteaddrstate'], ENT_QUOTES, 'UTF-8') . "</p></td>";
	print "<td><p>" . htmlentities($dset[$i]['siteaddrcity'], ENT_QUOTES, 'UTF-8') . "</p></td>";
	print "<td><p>" . htmlentities($dset[$i]['numws'], ENT_QUOTES, 'UTF-8') . "</p></td>";
	print "<td><p>" . htmlentities($dset[$i]['status'], ENT_QUOTES, 'UTF-8') . "</p></td>";
	print "<td><p>" . htmlentities($dset[$i]['slottime'], ENT_QUOTES, 'UTF-8') . "</p></td>";
	print "<td><p>" . htmlentities($dset[$i]['siteblockout'], ENT_QUOTES, 'UTF-8') . "</p></td>";
	print "<td><p>" . htmlentities($dset[$i]['hmapname'], ENT_QUOTES, 'UTF-8') . "</p></td>";
	print "</tr>";
}
	print "</table>";

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
		print "</div>\n"; // close .main
	?>	
</body></html>