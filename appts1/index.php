<?php
include("config.php");
require_once('../appcore/vec-clforms.php');
date_default_timezone_set(DATE_TIMEZONE);

$myform = new authentxforms();

// Render the page
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = $cfg_stdtitle;
$headparams["icon"] = $cfg_stdicon;
$headparams["css"] = $cfg_stdcss;
$headparams["jscript_file"] = $cfg_stdjscript;
$headparams["jscript_file"][] = "../appcore/scripts/js-formindex.js";
$headparams["jscript_file"][] = "../appcore/scripts/js-formlogin.js";
$headparams["jscript_local"][] = "if (top.frames.length != 0)\n"
			. "top.location=self.document.location;\n";
$myform->frmrender_head($headparams);

$bodyparams = array();
$bodyparams["onload"][] = "populateLoginForm()";
$myform->frmrender_bodytag($bodyparams);

// The page container
print "<div class=\"main\">\n";

$footerparams = array();
$footerparams["poweredbylogo"] = $cfg_authentxlogourl_white;
$footerparams["privacy"] = PRIVACY_INFO_FILE;

?>

	<div class="headingtextwhiteappt">
		<div class="logo_usaccess" style="text-align:center; width:50%;">
			<a href="<?php echo htmlspecialchars($page_certportal, ENT_QUOTES); ?>" aria-label="Go back to Portal">
			<img class="logo_usaccess" style="display:inline-block; width:50%;" src="../appcore/images/usaccess_banner_no_fill.png" alt="USAccess Logo">
			</a>
		</div>
	</div>

<!-- <div class="back-wrap">
  <button type="button"
          class="lrgbtn"
          onclick="location.href='../portal-cert/index.html'">
    Back
  </button>
</div> -->

<!-- <div class="back-wrap">
  <a href="../portal-cert/index.html" class="back-link" aria-label="Back">
    <img src="../appcore/images/back_icon_button_white.png"  alt="" class="back-img back-img--default" />
    <img src="../appcore/images/back_icon_button_white.png"  alt="" class="back-img back-img--hover" />
  </a>
</div> -->

	<div id="content" style="background-color: #00476B; width: 100%; height: auto; min-height: 68vh; ">
			<div class="buttonrow">
				<?php
				if ($_accessdisabled === false)
				{
				?>
				<form method="post" action="validate.php" id="loginform" autocomplete="off"  onsubmit="return checklogin(this)">	
					<div style="width:100%; height: auto; padding: 15px;">
					
						<div>
							<h3 title="IDENTITY MANAGEMENT SYSTEM" style="color: white; font-weight: bold; font-size: 1.25em;">APPOINTMENTS MANAGEMENT SYSTEM</h3>
						</div>
						<div>
							<span class="text-element txt-message" style="color: white; font-weight: bold; font-size: 0.9em;" id="txt_message">
							Please either register by clicking the Register button below,
							or login using your existing userid.<br>FIDO login by clicking the token image.
							</span>
						</div>
						
						<!-- Login Form Inputs -->
						<div id="loginFormInputs" style="display: block;">
							<div style="margin-top: 3%;">
								<input type="text" id="userid" name="userid" placeholder="User ID" autocomplete="off" class="lrginput" style="padding: 10px 0 10px 0;" maxlength="100"></input>
							</div>
							
							<div style="margin-top: 1%;">
								<input type="password" label for="password"  id="password" name="passwd" placeholder="Password" autocomplete="off" class="lrginput" style="padding: 10px 0 10px 0;"></input>
							</div>
							
							<div style="margin-top: 1%;">
								<input type="submit" id="login" name="submit" value="Login" class="lrgyellowbtn blkshadow"></input>
							</div>
							<div style="margin-top: 1.5%;">
								<span class="text-element txt-message" style="color: white; font-weight: bold; font-size: 0.9em;" id="txt_message">OR</span>
							</div>
						</div>
						<!-- End Login Form Inputs -->

						<!-- Last Logged User Form -->
						<div id="lastLoggedUserForm" style="display: block; margin-top: 5%;">
							<span class="text-element txt-message" style="color: white; font-weight: bold; font-size: 1.5em;" id="txt_message">Login as: </span><span class="text-element txt-message" style="color: #ffc823; font-size: 2em;" id="last_logged_userid">last_logged_userid</span>
							<br><span class="text-element txt-message" style="color: white; font-weight: bold; font-size: 0.9em;" id="txt_message">Or </span><span class="text-element txt-message" style="color: white; font-size: 0.9em; text-decoration: underline; cursor: pointer; " id="txt_message" onclick="useDifferentUserID()">Use different user</span>
						</div>
						<!-- End Last Logged User Form  -->

						<div>
							<img src="../appcore/images/yubikey.png" style="cursor: pointer;" name="btn_userid" id="btn_userid" width="70px" height="70px">
						</div>

						<div id="dontHaveAccount" style="margin-top: 2%;">
							<span class="text-element txt-message" style="color: white; font-weight: bold; font-size: 0.9em;" id="txt_message">Don't have an account?</span>
							 <span name="btn_register" class="registertext" onclick="javascript:popupOpener('pop-register.html','pop_register',400,600)">Register</span>
						</div>
						<div style="margin-top: 2%;">
							<a href="frm-centerlist.html" style="color: white;">Open Centers Page</a>
						</div>
					</div>
				</form>
				<?php
				}
				else 
				{
					?>
					<tr>
					<td class="contentcell_lt">
					<span class="siteheading"><?php print htmlentities($_accessdisabledmessage) ?></span>
					</td>
					</tr>
					<?php
				}
				?>
			</div>
		</div>
				<?php
					$myform->frmrender_footer_wlogo($footerparams);
				?>	
	</div>
</div>
<script src="../appcore/scripts/js-fido.js"></script>
<?php
print "<script>";
print "var uri_granted = \"".$page_granted."\"";
print "</script>";
?>
</body>
</html>