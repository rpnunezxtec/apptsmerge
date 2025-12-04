<?PHP
// $Id:$

session_start();
header("Cache-control: private");

include("config.php");
include("vec-clappointments.php");
$myappt = new authentxappointments();

$myappt->clearsession();

if (isset($page_logout))
	$myappt->vectormeto($page_logout);
else
	$myappt->vectormeto($page_denied);

?>