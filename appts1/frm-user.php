<?php 
// $Id:$

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-user.html";
$form_name = "users";
$tab_name = ucfirst($form_name);
$rpp= 25;


include("config.php");
include("vec-clappointments.php");
include('../appcore/vec-clforms.php');
$myform = new authentxforms();
$myappt = new authentxappointments();
$searchvar = "usearch";

$fullname = $myappt->session_getuuname();
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

if (!$tab_users)
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
$f_adminonly = 0;
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

if (isset($_GET["f_adminonly"]))
{
	$f_adminonly = $_GET["f_adminonly"];
	if (!is_numeric($f_adminonly))
		$f_adminonly = 0;
	else
	{
		if ($f_adminonly != 0)
			$f_adminonly = 1;
	}
	$urlf .= "&f_adminonly=".urlencode($f_adminonly);
}

// Search seeding
if (isset($_POST["btn_usearch"]))
{
	if (isset($_POST["namesearch"]))
	{
		$usearch = trim($_POST["namesearch"]);
		if (!empty($usearch))
			$myappt->session_setvar($searchvar, $usearch);
		else
			$myappt->session_setvar($searchvar, NULL);
	}
}
$usearch = $myappt->session_getvar($searchvar);
if (empty($usearch))
	$usearch = "";

// Filter selection
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
	
	// admin only
	if (isset($_POST["f_adminonly"]))
	{
		$f_adminonly = $_POST["f_adminonly"];
		if (!is_numeric($f_adminonly))
			$f_adminonly = 0;
		else
		{
			if ($f_adminonly != 0)
				$f_adminonly = 1;
		}
		$urlf .= "&f_adminonly=".urlencode($f_adminonly);
	}
}

// Get the user data from the database
$dbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if (!($dbh->connect_error))
{	
	// Start row number
	$rowstart = ($pg - 1) * $rpp;
	
	$q_u = "";
	$q_up = "select * from user ";
	$q_uc = "select count(*) as ucount from user ";
	if (!empty($usearch))
	{
		$q_u .= "\n where uname like '%".$dbh->real_escape_string($usearch)."%' ";
		if ($f_component !== false)
			$q_u .= "\n and component='".$dbh->real_escape_string($f_component)."' ";
		if ($f_status !== false)
			$q_u .= "\n and status='".$dbh->real_escape_string($f_status)."' ";
		if ($f_adminonly == 1)
			$q_u .= "\n and (privilege & 0x7ffe) ";
	}
	else
	{
		if ($f_component !== false)
		{
			$q_u .= "\n where component='".$dbh->real_escape_string($f_component)."' ";
			
			if ($f_status !== false)
				$q_u .= "\n and status='".$dbh->real_escape_string($f_status)."' ";
			
			if ($f_adminonly == 1)
				$q_u .= "\n and (privilege & 0x7ffe) ";
		}
		else
		{
			if ($f_status !== false)
			{
				$q_u .= "\n where status='".$dbh->real_escape_string($f_status)."' ";
				
				if ($f_adminonly == 1)
					$q_u .= "\n and (privilege & 0x7ffe) ";
			}
			else 
			{
				if ($f_adminonly == 1)
					$q_u .= "\n where (privilege & 0x7ffe) ";
			}
		}
	}
		
	$q_u .= "\n order by userid ";
	$q_limit = "\n limit ".$rowstart.", ".$rpp;

	$dset= array();
	$nds = 0;
	$nr = 0;
	
	// First get a total row count for pagination
	$qq = $q_uc.$q_u;
	$sq = $dbh->query($qq);
	if ($sq)
	{
		$rq = $sq->fetch_assoc();
		$nr = $rq["ucount"];
		$sq->free();
	}
	
	// Now get the data for the page
	$q_usr = $q_up.$q_u.$q_limit;
	
	$s_u = $dbh->query($q_usr);
	if ($s_u)
	{
		while ($r_u = $s_u->fetch_assoc())
		{
			// is the user an admin user
			$adm = $r_u["privilege"] & 0x7FFE;
			if ($adm == 0)
				$dset[$nds]["is_admin"] = false;
			else
				$dset[$nds]["is_admin"] = true;
					
			// get the uid and create a MAC
			$dset[$nds]["uid"] = $r_u["uid"];
			$dset[$nds]["avc"] = $myappt->session_createmac($r_u["uid"]);
			$dset[$nds]["userid"] = $r_u["userid"];
			$dset[$nds]["uname"] = $r_u["uname"];
			$dset[$nds]["component"] = $r_u["component"];
			$dset[$nds]["email"] = $r_u["email"];
			$dset[$nds]["lastlogin"] = $r_u["lastlogin"];

			$nds++;
		}
		$s_u->free();		
	}
	
	$dbh->close();
}
else 
{
	$dset= array();
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
			<div class="titletextwhite">Users</div>
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
			if ($myappt->checkprivilege(PRIV_UCREATE) || $myappt->checkprivilege(PRIV_UROLES))
			{
				// ✅ Flex row under the filters
				print "<div style='display:flex;align-items:center;gap:8px;margin:6px 0 4px;'>";
				print "<span class='pageon' style='font-weight:bold;'>Page:</span>";
				for ($i = 0; $i < $np; $i++) {
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
				print "<th class=\"tableheader\">UserID</th>";
				print "<th class=\"tableheader\">Full Name</th>";					
				print "<th class=\"tableheader\">Component</th>";					
				print "<th class=\"tableheader\">Email</th>";					
				print "<th class=\"tableheader\">Last Login</th>";					
				print "<th class=\"tableheader\">Admin</th>";					
				print "</tr>";
				print "</thead>";
				print "<tbody>";

				print "<tr>";
				print "<td><p><a href=\"javascript:popupOpener('pop-edituserdetail.html','edituserdetail',350,500)\" style=\"color: blue;\" aria-label=\"Add (opens popup)\" title=\"Add (opens popup)\">Add...</a></p></td>";
				print "<td><p>&nbsp;</p></td>";
				print "<td><p>&nbsp;</p></td>";
				print "<td><p>&nbsp;</p></td>";
				print "<td><p>&nbsp;</p></td>";
				print "<td><p>&nbsp;</p></td>";
				print "</tr>";

				for ($i = 0; $i < $nds; $i++)
				{	
					// output each row for this page
					print "<tr>";
					print "<td><p>";
					print "<a href=\"javascript:popupOpener('pop-edituserdetail.html?uid=".urlencode($dset[$i]["uid"])."&avc=".urlencode($dset[$i]["avc"])."','edituserdetail',350,500)\" style=\"color: blue;\" title=\" ". htmlentities($dset[$i]['sitename'], ENT_QUOTES, 'UTF-8')." (opens popup)\" aria-label=\" ". htmlentities($dset[$i]['sitename'], ENT_QUOTES, 'UTF-8')." (opens popup)\">". htmlentities($dset[$i]['userid'], ENT_QUOTES, 'UTF-8'). "</a>";
					print "</p></td>";
					
					print "<td><p>" . htmlentities($dset[$i]['uname'], ENT_QUOTES, 'UTF-8') . "</p></td>";
					print "<td><p>" . htmlentities($dset[$i]['component'], ENT_QUOTES, 'UTF-8') . "</p></td>";
					print "<td><p>" . htmlentities($dset[$i]['email'], ENT_QUOTES, 'UTF-8') . "</p></td>";
					print "<td><p>" . htmlentities($dset[$i]['lastlogin'], ENT_QUOTES, 'UTF-8') . "</p></td>";
					print '<td><p class="tabletext" style="color:' . ($dset[$i]['is_admin'] ? '#008f3f' : '#ff0f00') . ';">' . ($dset[$i]['is_admin'] ? 'Y' : 'N') . '</p></td>';


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