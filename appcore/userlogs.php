<?php
// $Id: userlogs.php 191 2009-03-12 02:24:17Z atlas $

$form_name = "userlogs";
$form_file = "userlogs.php";

$form_config = array
(
"entity:sysid=logs:objectlog:xsystem|0xfffffff1|0x00000000|userlogs|read|0x08",
);

session_start();
header("Cache-control: private");

include_once('config.php');
require('../appcore/vec-incsession.php');
require_once('../appcore/vec-clforms.php');

$myform = new authentxforms();
$host = $mysession->gethost();

// the geometry required for this popup
$windowx = 600;
$windowy = 600;

$rv = $myform->processformconfig($form_config, $form_name, $host, false);
if ($rv !== true)
	print "<script type=\"text/javascript\">alert('".$rv[0]."')</script>\n";

// Render the page
print "<!DOCTYPE html>\n<html>\n";
$headparams = array();
$headparams["meta"] = $cfg_stdmeta;
$headparams["title"] = "User Logs";
$headparams["icon"] = $cfg_stdicon;
$headparams["css"] = $cfg_stdcss;
$headparams["jscript_file"] = $cfg_stdjscript;
$headparams["jscript_local"][] = "window.resizeTo(".$windowx.",".$windowy.");";
$myform->frmrender_head($headparams);
$bodyparams = array();
$bodyparams["id"] = "popup";
$myform->frmrender_bodytag($bodyparams);
// The page container
print "<div class=\"main\">\n";

?>
	<div class="buttonrow">
		<div class="popupgrid" style="grid-template-columns: 100%;">
			<div class="popupcell center">User Logs</div>
		
<?php
$olvals = $mysession->getformvalue($form_name, "userlogs");
if ($olvals)
{
	if (count($olvals) > 0)
	{
		foreach ($olvals as $ol)
		{
			print "<div class=\"popupcell\" style=\"font-weight: 400; padding:0;\">".htmlentities($ol, ENT_QUOTES, "UTF-8")."</div>\n";
		}
	}
}
?>
		</div>
	</div>
</div>
</body>
</html>