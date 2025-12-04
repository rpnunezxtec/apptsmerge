<?php
// $Id: pop-providerconfig.php 288 2024-10-23 07:35:43Z atlas $

session_start();
header("Cache-control: private");
header('Content-Type: text/html; charset=UTF-8');

$formfile = "pop-providerconfig.php";

include("config.php");
require_once("replication/config/config-repl.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
require_once(_AUTHENTX5APPCORE."/cl-forms.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myform = new authentxforms();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

// validate the session
$sr = $myappt->validatesession($_siteid);
if ($sr !== true)
{
	print "<script type=\"text/javascript\">alert('".$sr."')</script>\n";
	$myappt->vectormeto($page_denied);
}

// Check privilege level to access this popup
// Validate access to this form - requires User tab permissions
if ($myappt->checktabmask(TAB_REPLDASH) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// Check admin privileges.
if ($myappt->checkprivilege(PRIV_REPLDASH) !== true)
{
	print "<script type=\"text/javascript\">alert('Insufficient privileges.')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// the geometry required for this popup
$windowx = 700;
$windowy = 500;

$providerid = false;
$urlargs = "";

$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if (!$sdbh->connect_error)
{
	// Posting of NEW provider
	if (isset($_POST["btn_add"]))
	{
		if (isset($_POST["providerid"]))
		{
			$providerid = trim($_POST["providerid"]);
			if ($providerid == "")
				$providerid = false;
			if ($providerid !== false)
			{
				// Check to see if this provider already exists
				// $rv[rsid], [providerid], [providerurl], [rstatus], [repxsyncmts], [lastreq]
				$providerset = $myappt->getprovider($sdbh, $providerid);
				if (isset($providerset["error"]))	// Error == provider not found
				{
					if (trim($_POST["syncdate"]) != "")
					{
						$syncdate = trim($_POST["syncdate"]);
						// Convert YYY-mm-dd HH:ii:ss to Unix timestamp
						$syncmts = strtotime($syncdate);
					}
					else 
						$syncmts = false;

					if (trim($_POST["providerurl"]) != "")
						$providerurl = trim($_POST["providerurl"]);
					else 
						$providerurl = false;

					if (trim($_POST["status"]) != "")
					{
						$rstatus = trim($_POST["status"]);
						if (($rstatus > 1) || ($rstatus < 0))
							$rstatus = 0;
					}
					else 
						$rstatus = false;

					// updateprovider($sdbh, $providerid, $providerurl = false, $rstatus = false, $syncmts = false, $add = false)
					$e = $myappt->updateprovider($sdbh, $providerid, $providerurl, $rstatus, $syncmts, true);
					if ($e !== true)
					{
						print "<script type=\"text/javascript\">alert('Error: ".htmlentities($e).".')</script>\n";
						$providerid = false;
					}
				}
				else 
				{
					print "<script type=\"text/javascript\">alert('Provider ID already exists.')</script>\n";
				}
				$urlargs = "?providerid=".urlencode($providerid);
			}
		}
	}

	// Posting of updates
	if (isset($_POST["btn_update"]))
	{
		$providerid = trim($_GET["providerid"]);
		if ($providerid == "")
			$providerid = false;
		if ($providerid !== false)
		{
			if (isset($_POST["syncdate"]))
			{
				if (trim($_POST["syncdate"]) != "")
				{
					$syncdate = trim($_POST["syncdate"]);
					// Convert YYY-mm-dd HH:ii:ss to Unix timestamp
					$syncmts = strtotime($syncdate);
				}
				else 
					$syncmts = false;
			}
			else
				$syncmts = false;

			if (trim($_POST["providerurl"]) != "")
				$providerurl = trim($_POST["providerurl"]);
			else 
				$providerurl = false;

			if (trim($_POST["status"]) != "")
				$rstatus = trim($_POST["status"]);
			else 
				$rstatus = false;
			
			// updateprovider($sdbh, $providerid, $providerurl = false, $rstatus = false, $syncmts = false, $add = false)
			$myappt->updateprovider($sdbh, $providerid, $providerurl, $rstatus, $syncmts, false);
		}
	}

	if (isset($_GET["providerid"]))
	{
		$providerid = trim($_GET["providerid"]);
		if ($providerid == "")
			$providerid = false;
		
		if ($providerid !== false)
			$urlargs = "?providerid=".urlencode($providerid);
	}

	// $rv[rsid], [providerid], [providerurl], [rstatus], [repxsyncmts], [lastreq]
	if ($providerid !== false)
		$providerset = $myappt->getprovider($sdbh, $providerid);

	$sdbh->close();
}
else
{
	print "<script type=\"text/javascript\">alert('Error connecting to appts DB.')</script>\n";
	print "<script type=\"text/javascript\">self.close()</script>\n";
	die();
}

// Configuration for form rendering
$cfg_stdmeta = array(
		"http-equiv=\"Default-style\" content=\"text/html;charset=UTF-8\"",
		"http-equiv=\"Pragma\" content=\"no-cache\"",
		"http-equiv=\"Cache-Control\" content=\"no-store,no-Cache\"",
);
$cfg_stdicon = "../appcore/images/icon-authentx.ico";
$cfg_stdcss = array(
		"../appcore/css/authentx.css",
		"../appcore/css/tabbar.css",
		"../appcore/css/formpanel.css",
);
$cfg_stdjscript = array(
		"../appcore/scripts/js-formstd.js",
		"../appcore/scripts/js-formext.js",
);

$cfg_logoimgurl = "../appcore/images/logo.png";
$cfg_authentxlogourl = "../appcore/images/AuthentX-logo-plain-gray6.png";



// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = "Provider Properties";
$headparams["icon"] = $cfg_stdicon;
$headparams["css"] = $cfg_stdcss;
$headparams["jscript_file"] = $cfg_stdjscript;
$headparams["jscript_local"][] = "window.resizeTo(".$windowx.",".$windowy.");";
$headparams["jscript_local"][] = "window.opener.location.href=window.opener.location.href;";
$headparams["jscript_local"][] = "if (top.frames.length != 0) {top.location = self.document.location;}";
$myform->frmrender_head($headparams);
$bodyparams = array();
$myform->frmrender_bodytag($bodyparams);
$myform->frmrender_popclose();

if ($providerid !== false)
{
	$syncdate = "";
	if (($providerset["repxsyncmts"] != NULL) && ($providerset["repxsyncmts"] != 0))
		$syncdate = date("m/d/Y H:i:s", $providerset["repxsyncmts"]);
	
?>
<section class="contentpanel_popup">
	<form name="mainform" method="post" action="<?php print $formfile.$urlargs ?>" autocomplete="off" >
	<table class="contentpanel_props">
		<tr class="contentrow_30">
		<td class="propscell_lt" width="25%"><label for="providerid" class="propscell">Server ID</label></td>
		<td class="propscell_lt" width="75%"><span class=" propstext"><?php print htmlentities($providerid) ?></span></td>
		</tr>
		
		<tr class="contentrow_30">
		<td class="propscell_lt"><label for="providerurl" class="propscell">Provider URL</label></td>
		<td class="propscell_lt">
		<input type="text" size="70" maxlength="120" tabindex="20" name="providerurl" value="<?php print htmlentities($providerset["providerurl"]) ?>" title="URL of provider to connect to as a consumer to request updates" />
		</td>
		</tr>
		
		<tr class="contentrow_30">
		<td class="propscell_lt"><label for="status" class="propscell">Provider Status</label></td>
		<td class="propscell_lt">
		<select name="status" style="width:22em;" tabindex="30" title="Status determines whether a consumer server can request updates from the provider" >
		<?php
		foreach ($listproviderstatus as $p)
		{
			if ($p[0] == $providerset["rstatus"])
				print "<option selected value=\"".$p[0]."\">".$p[1]."</option>";
			else
				print "<option value=\"".$p[0]."\">".$p[1]."</option>";
		}
		?>
		</select>
		</td>
		</tr>
		
		<tr class="contentrow_30">
		<td class="propscell_lt"><label for="syncdate" class="propscell">Sync DateTime</label></td>
		<td class="propscell_lt"><span class=" propstext"><?php print htmlentities($syncdate) ?></span></td>
		</tr>
	</table>
	<p/>
	
	<table class="contentpanel_popup">
		<tr class="contentrow_40">
		<td class="contentcell_lt">
		<input type="submit" name="btn_update" class="btntext" value="Update" tabindex="110" title="Save the changes to the database">
		</td>
		</tr>
	</table>
	</form>
</section>
<?php
}
else 
{
	// No providerID supplied, creating a new entry
?>
<section class="contentpanel_popup">
	<form name="mainform" method="post" action="<?php print $formfile ?>" autocomplete="off" >
	<table class="contentpanel_props">
		<tr class="contentrow_30">
		<td class="propscell_lt" width="40%"><label for="providerid" class="propscell">Server ID</label></td>
		<td class="propscell_lt" width="60%">
		<input type="text" size="50" maxlength="40" tabindex="10" name="providerid" /></td>
		</tr>
		
		<tr class="contentrow_30">
		<td class="propscell_lt"><label for="providerurl" class="propscell">Provider URL</label></td>
		<td class="propscell_lt">
		<input type="text" size="70" maxlength="120" tabindex="20" name="providerurl"  title="URL of provider to connect to as a consumer to request updates" />
		</td>
		</tr>
		
		<tr class="contentrow_30">
		<td class="propscell_lt"><label for="status" class="propscell">Provider Status</label></td>
		<td class="propscell_lt">
		<select name="status" style="width:22em;" tabindex="30" title="Status determines whether a consumer server can request updates from the provider">
		<?php
		foreach ($listproviderstatus as $p)
		{
			print "<option value=\"".$p[0]."\">".$p[1]."</option>";
		}
		?>
		</select>
		</td>
		</tr>
		
		<tr class="contentrow_30">
		<td class="propscell_lt"><label for="syncdate" class="propscell">Sync DateTime (YYYY-MM-DD hh:mm:ss)</label></td>
		<td class="propscell_lt">
		<input type="text" size="50" maxlength="20" tabindex="40" name="syncdate" title="Date and time from which object modifications will be replicated" /></td>
		</tr>
	</table>
	<p/>
	
	<table class="contentpanel_popup">
		<tr class="contentrow_40">
		<td class="contentcell_lt">
		<input type="submit" name="btn_add" class="btntext" value="Add">
		</td>
		</tr>
	</table>
	</form>
</section>
<?php
}
?>
</body></html>
