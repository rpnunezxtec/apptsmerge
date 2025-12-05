<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-emws.html";
$form_name = "emws";
$tab_name = ucfirst($form_name);
$rpp= 25;

include("config.php");
include("vec-clappointments.php");
include('../appcore/vec-clforms.php');
$myform = new authentxforms();
$myappt = new authentxappointments();
$searchvar = "emsearch";

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

if (!$tab_ws)
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
	
$f_site = false;
$urlf = "";

// find out what page we are on
if (isset($_GET["pg"]))
{
	$pg = $_GET["pg"];
	if (!is_numeric($pg))
		$pg = 1;
}
else
	$pg = 1;

// Filters for other pages
if (isset($_GET["f_site"]))
{
	$f_site = $_GET["f_site"];
	if (!is_numeric($f_site))
		$f_site = false;
	else
		$urlf .= "&f_site=".urlencode($f_site);
}

// Search seeding
if (isset($_POST["btn_emsearch"]))
{
	if (isset($_POST["emsearch"]))
	{
		$emsearch = trim($_POST["emsearch"]);
		if (!empty($emsearch))
			$myappt->session_setvar($searchvar, $emsearch);
		else
			$myappt->session_setvar($searchvar, NULL);
	}
}
$emsearch = $myappt->session_getvar($searchvar);
if (empty($emsearch))
	$emsearch = "";

// Filter selection
if (isset($_POST["btn_filter"]))
{
	// site
	if (isset($_POST["f_site"]))
	{
		$f_site = trim($_POST["f_site"]);
		if (!is_numeric($f_site))
			$f_site = false;
		else
			$urlf .= "&f_site=".urlencode($f_site);
	}
}

// Get the ws data from the database
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{
	$q_ws = "select ws.wsid, "
		. "\n ws.wsname, "
		. "\n ws.status, "
		. "\n ws.siteid, "
		. "\n s.sitename, "
		. "\n s.siteid "
		. "\n from workstation as ws "
		. "\n left join site as s on ws.siteid=s.siteid "
		. "\n where ws.wsid>0"
		;
		
	if ($f_site !== false)
		$q_ws .= "\n and ws.siteid='".$dbh->real_escape_string($f_site)."' ";
	
	if (!empty($emsearch))
		$q_ws .= "\n and ws.wsname like '%".$dbh->real_escape_string($emsearch)."%' ";
	
	$q_ws .= "\n order by s.sitename, ws.wsname ";
	
	$s_ws = $dbh->query($q_ws);
	if ($s_ws)
	{
		$nr = $s_ws->num_rows;
		$dset = array();
		$nds = 0;
		$rnum = 1;

		while ($r_ws = $s_ws->fetch_assoc())
		{
			if ($rpp > 0)
				$rptest = ($rnum > ($rpp * ($pg-1))) && ($rnum <= ($rpp * $pg));
			else
				$rptest = 1;
			
			if ($rptest)
			{
				$dset[$nds]["wsname"] = $r_ws["wsname"];
				$dset[$nds]["sitename"] = $r_ws["sitename"];
				$dset[$nds]["status"] = ($r_ws["status"] == 0 ? "unavailable" : "available");
				$dset[$nds]["wsid"] = $r_ws["wsid"];
				$dset[$nds]["avc"] = $myappt->session_createmac($r_ws["wsid"]);
				
				$rnum++;
				$nds++;
			}
		}
		$s_ws->free();
	}
}
else 
{
	$nr = 0;
	$nds = 0;
	$dset = array();
}

// calculate the number of pages to show
$np = intval($nr/$rpp);
if (($nr % $rpp) > 0)
	$np++;

if (!($dbh->connect_error))
{
	// Create a list array of sites with workstations
	// [0] = siteid, [1] = sitename
	$listsites = array();
	$q_s = "select distinct ws.siteid, "
		. "\n s.sitename "
		. "\n from workstation as ws "
		. "\n left join site as s on s.siteid=ws.siteid "
		. "\n where s.sitename is not NULL "
		. "\n order by s.sitename "
		;
		
	$s_s = $dbh->query($q_s);
	$ns = 0;
	if ($s_s)
	{
		while ($r_s = $s_s->fetch_assoc())
		{
			$listsites[$ns] = array($r_s["siteid"], $r_s["sitename"]);
			$ns++;
		}
		$s_s->free();
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
			<div class="titletextwhite">Workstations</div>
				<form name="wsearch" method="post" action="<?php print $formfile ?>" autocomplete="off" style="display:flex;flex-direction:column;align-items:stretch;gap:10px;width:100%;">
				<!-- <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto;">
					<input type="text" name="emsearch" id="emsearch" value="<?php print htmlentities($emsearch) ?>" size="30" maxlength="30" placeholder="Name search..">
				</div>
				<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto;">
					<input type="submit" name="btn_emsearch" class="inputbtn darkblue" value="Search">
				</div> -->
				<div style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;width:100%;">
					<div class="lblblktext" style="white-space:nowrap;align-self:center;font-weight:700;">Filters:</div>
				</div>

				<div style="display: flex;">
					<div style="display:flex;flex-direction:column;gap:4px;min-width:12rem;">
						<label class="lblblktext" for="f_site" style="text-align:left;font-weight:700;">Site</label>
						<select name="f_site" style="width: 15em; margin-bottom: 4px;">
						<?php
						$rc = count($listsites);
						$selected = false;
						for ($i = 0; $i < $rc; $i++)
						{
							if (strcasecmp($listsites[$i][0], $f_site) == 0)
							{
								print "<option selected value=\"".$listsites[$i][0]."\">".$listsites[$i][1]."</option>\n";
								$selected = true;
							}
							else
								print "<option value=\"".$listsites[$i][0]."\">".$listsites[$i][1]."</option>\n";
						}

						if ($selected === true)
							print "<option value=\"\">Any</option>\n";
						else
							print "<option selected value=\"\">Any</option>\n";
							
						?>
						</select>
						<div style="display:flex;align-items:end;">
							<input type="submit" name="btn_filter" class="inputbtn darkblue" value="Apply" />
						</div>
					</div>

					<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto;">
						<input type="text" name="emsearch" id="emsearch" value="<?php print htmlentities($emsearch) ?>" size="30" maxlength="30" placeholder="Name search..">
						<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:auto;">
							<input type="submit" name="btn_emsearch" class="inputbtn darkblue" value="Search">
						</div>
					</div>
				</div>

				
			</div>
			<!-- <div style="display:flex;align-items:end;">
				<input type="submit" name="btn_filter" class="inputbtn darkblue" value="Apply" />
			</div> -->
			</form>

<?php
			// Only print this table if the privilege bits are present
			if ($myappt->checkprivilege(PRIV_WSASGN) || $myappt->checkprivilege(PRIV_WSCREATE) || $myappt->checkprivilege(PRIV_WSSTAT))
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
				print "<th class=\"tableheader\">WS Name</th>";
				print "<th class=\"tableheader\">Site</th>";					
				print "<th class=\"tableheader\">Status</th>";					
				print "</tr>";
				print "</thead>";
				print "<tbody>";

				if ($_axsitesync_enable === false)
				{
					print "<tr>";
					print "<td><p>";
					print "<a href=\"javascript:popupOpener('pop-editwsdetail.html','editwsdetail',350,500)\" style=\"color: blue;\" aria-label=\"Add (opens popup)\" title=\"Add (opens popup)\">Add...</a>";
					print "</p></td>";
					print "<td><p>&nbsp;</p></td>";
					print "<td><p>&nbsp;</p></td>";
					print "</tr>";
				}
			
				for ($i = 0; $i < $nds; $i++)
				{	
					// output each row for this page
					print "<tr>";
					print "<td><p>";
					print "<a href=\"javascript:popupOpener('pop-editwsdetail.html?wsid=" .urlencode($dset[$i]['wsid']). "&avc=" . urlencode($dset[$i]['avc']). "','editwsdetail',350,500)\" style=\"color: blue;\" title=\" ". htmlentities($dset[$i]['wsname'], ENT_QUOTES, 'UTF-8')." (opens popup)\" aria-label=\" ". htmlentities($dset[$i]['wsname'], ENT_QUOTES, 'UTF-8')." (opens popup)\">" . htmlentities($dset[$i]['wsname'], ENT_QUOTES, 'UTF-8'). "</a>";print "</p></td>";
					print "<td><p>" . htmlentities($dset[$i]['sitename'], ENT_QUOTES, 'UTF-8') . "</p></td>";
					print "<td><p>" . htmlentities($dset[$i]['status'], ENT_QUOTES, 'UTF-8') . "</p></td>";
                    print "</tr>";
				}
				print "</table>";
			}
			?>
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