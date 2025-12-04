<?PHP
// $Id: frm-tokens.html 214 2009-03-17 22:46:06Z atlas $

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "frm-fidotokens.html";
$form_name = "Passkeys";
$tab_name = ucfirst($form_name);

include("config.php");
include("vec-clappointments.php");
include('../appcore/vec-clforms.php');
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappointments();
$myform = new authentxforms();
$userinfo = array();

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

// Find out what site (if any) has been selected
if (isset($_GET["site"]))
{
	$siteid = $_GET["site"];
	$myappt->session_setvar("site", $siteid);
}
else
	$siteid = false;

if (isset($_POST["submit_site"]))
{
	if (isset($_POST["site"]))
		$siteid = $_POST["site"];
	$myappt->session_setvar("site", $siteid);
	if (isset($_POST["wk"]))
		$_GET["wk"] = $_POST["wk"];
}
	
if ($myappt->session_getvar("site") !== false)
	$siteid = $myappt->session_getvar("site");
	
$sdbh = new mysqli($_db_host, $_db_user, $_db_passwd, $_db_database);
if ($sdbh->connect_error)
{
	print "<script type=\"text/javascript\">alert('Could not open fido database.')</script>\n";
	print "<script type=\"text/javascript\">self.close()</script>\n";
	die();
}

// get user unique id to locate info in the db
$userid = $myappt->session_getuuserid();

if($userid == false || $userid == "")
{
	$userid = $myappt->session_getuemail();
}

// check if deactivate 
if(isset($_POST["submit"]))
{
	if(isset($_POST["status"]))
	{
		$t_fstatus = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
		
		// get token status and set to innactive: 2 or active: 1
		$t_status = ($t_fstatus == "Active")? 2 : 1;
		
		if(isset($_POST["fid"]))
		{
			$fid = filter_input(INPUT_POST, 'fid', FILTER_SANITIZE_NUMBER_INT);

			// build query
			$qfid = "update fido"
				  . " set status=".$t_status
				  . " where fid=".$fid;

			$sfid = $sdbh->query($qfid);

			if ($sfid)
				print "<script type=\"text/javascript\">alert('Token $fid successfully removed.')</script>\n";
			else
				print "<script type=\"text/javascript\">alert('Unable to remove token $fid.')</script>\n";
		}
		else
		{
			print "<script type=\"text/javascript\">alert('Unable to read fid value.')</script>\n";
		}
	}
	else
	{
		print "<script type=\"text/javascript\">alert('Unable to read token status value.')</script>\n";
	}
}

// get user info first
$grouped_tokenmatrix = array();
$tokenmatrix = array();
$status_map = array("2" => "Inactive", "1" => "Active");

$qf = "select status, lastlogin, uname, uid "
	. "\n from user" 
	. "\n where userid='".$sdbh->real_escape_string($userid)."' ";

$sf = $sdbh->query($qf);

if ($sf)
{
	$rf = $sf->fetch_assoc();
	$userinfo["username"] = $rf["uname"];
	
	if(isset($rf["uid"]))
	{
		$uid = $rf["uid"];
		
		// get fido tokens
		$qt = "select * "
			. "\n from fido" 
			. "\n where uid='".$sdbh->real_escape_string($uid)."' "
			. "\n and status=1";
	
		$st = $sdbh->query($qt);
		
		$i = 0;
		while ($rt = $st->fetch_assoc())
		{
			$status = $status_map[$rt["status"]];
			$devname = $rt["devname"];
			$fid = $rt["fid"];
			
			$grouped_tokenmatrix[$status][$i]["status"] = $status;
			$grouped_tokenmatrix[$status][$i]["devname"] = $devname;
			$grouped_tokenmatrix[$status][$i]["fid"] = $fid;
			
			$i++;
		}
		
		$st->free();
	}
	
	$sf->free();
}
else
{
	$sdbh->close();
	
	print "<script type=\"text/javascript\">alert('Could not connect fido database.')</script>\n";
	print "<script type=\"text/javascript\">self.close()</script>\n";
	die();
}

$sdbh->close();

// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams["css"] = $cfg_stdcss;
$headparams["jscript_file"] = $cfg_stdjscript;
$headparams["jscript_file"][] = "../appcore/scripts/js-tablesort.js";
$headparams["jscript_file"][] = "../appcore/scripts/js-checkall.js";
$headparams["jscript_file"][] = "../appcore/scripts/js-expandall.js";

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
$myform->frmrender_bodytag($bodyparams);

// The page container
print "<div class=\"main\">\n";

// The top heading section
$topparams = array();
$topparams["logoimgurl"] = $cfg_logoimgurl;
$topparams["logoalt"] = $cfg_logoalt;
$topparams["bannerheading1"] = BANNERHEADING1;
$topparams["bannerheading2"] = BANNERHEADING2;
$topparams["pageportal"] = $page_certportal;
$topparams["dropdown"] = $cfg_tabs;
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
$bsideparams["tabs"] = array(
);
$cfg_userdropdown["userinfo"] = $userinfo;
$bsideparams["userdropdown"] = $cfg_userdropdown;
$bsideparams["firstname"] = $firstname;
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
<?php
		//$myform->frmrender_userinfo($userinfoparams);
if($tokensorting == false)		
	$myform->frmrender_table($tableparams);
else
{
?>
		<div class="titletextwhite">User's Credential Objects</div>
			<div class="tablescrollbox">
				<div class="row" style="width: 90%;">
			<?php 
			if(count($grouped_tokenmatrix) > 0)
			{
			?>
				<div class="popupbutton">
					<div class="popupclose">
						<input type="button" id="expandcollapse" name="expandcollapse" class="popupbuttontxt" value="Expand All" onclick="expandAll()">
					</div>
				</div>
			<?php
				foreach ($grouped_tokenmatrix as $grp => $tset)
				{
				   print "<ul class=\"col s12 collapsible expandable\">";
				   print "<li class=\"\">";
					 print "<h6 class=\"collapsible-header center\" style=\"color: #212121;font-weight: bold;\" onclick=\"rotate(this)\"><div style=\"width: 90%; display: inline-block;\">".$grp." Tokens</div><img src=\"../appcore/images/expand_more.png\" style=\"height: 50%;\"></img></h6>";
					 print "<div class=\"collapsible-body\">";
					  print "<table class=\"striped\" id=\"".$grp."\">";
						print "<thead>";
						  print "<tr class=\"light-xtec-blue\">";
							  print "<th class=\"sorttableheader\" onclick=\"sortTable('".$grp."', 1, this)\">Token ID<img src=\"../appcore/images/arrow_upward.png\" style=\"display: none; padding: 0% 2%;\"></img></th>";
							  print "<th class=\"sorttableheader\" onclick=\"sortTable('".$grp."', 2, this)\">Device Name<img src=\"../appcore/images/arrow_upward.png\" style=\"display: none; padding: 0% 2%;\"></img></th>";
							  print "<th class=\"sorttableheader\" onclick=\"sortTable('".$grp."', 3, this)\">Status<img src=\"../appcore/images/arrow_upward.png\" style=\"display: none; padding: 0% 2%;\"></img></th>";
							  print "<th class=\"sorttableheader\" onclick=\"sortTable('".$grp."', 4, this)\">Action<img src=\"../appcore/images/arrow_upward.png\" style=\"display: none; padding: 0% 2%;\"></img></th>";
						print "</tr>";
						print "</thead>";
						print "<tbody>";

						$ngr = count($tset);
						for ($i = 0; $i < $ngr; $i++)
						{
							//$dn = $tset[$i]["dn"];
							//$avc = $mysession->createmac(strtolower($dn));
					
							print "<tr>";
								print "</p></td>";
								print "<td><p>";
									print htmlentities($tset[$i]["fid"]);
								print "</p></td>";
								print "<td><p>";
									print htmlentities($tset[$i]["devname"]);
								print "</p></td>";
								print "<td><p>";
									print htmlentities($tset[$i]["status"]);
								print "</p></td>";
								print "<td style=\"padding: 20px;\">";
									print "<form name=\"siteform\" method=\"post\" action=\"".$formfile."\"  autocomplete=\"off\" >";
									print "<input type=\"hidden\" name=\"fid\" id=\"fid\" value=\"".$tset[$i]["fid"]."\" />";
									print "<input type=\"hidden\" name=\"status\" id=\"status\" value=\"".$tset[$i]["status"]."\" />";
										print "<input type=\"submit\" name=\"submit\" style=\"background-color: #4CAF50; color: white; padding: 14px 20px; margin: 8px 0; border: none; cursor: pointer; border-radius: 4px;
									font-size: 16px; transition: background-color 0.3s ease; width: 100%\" value=\"".(($tset[$i]["status"] == "Active")? "Deactivate" : "Activate")."\"\>";
									print "</form>";
								print "</p></td>";
							print "</tr>";												
						}				
						print "</tbody>";
					  print "</table>";
					  print "</div>";
					  print "</li>";
				  print "</ul>";
				}
			}
			else
			{
		?>				
				<div class="inputtitlerow1">
					<div class="inputtitle1 inputtitlespacer15" style="text-align:center;">
						<span class="text-element txt-message" id="txt_message" id="txt_message">
							No FIDO Tokens to Display
						</span>
					</div>
				</div>
		<?php
			}
		?>
				<button type="button" style="width: auto; margin-top: 50px;" class="lrgyellowbtn" name="btn_register" value="register" id="btn_register">Register Fido Token</button>
			  </div>
			</div>
<?php
}
?>
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
	</div>
		<script src="../appcore/scripts/js-fidoregister.js"></script>
		<?php

			echo "<script>";
			echo "useridInp = \"".$userid."\"";
			echo "</script>";

		?>
	</body>
</html>
