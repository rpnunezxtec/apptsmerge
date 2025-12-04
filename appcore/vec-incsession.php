<?PHP
// $Id: vec-incsession.php 44 2008-10-29 06:06:24Z atlas $

include_once('vec-clsession.php');

$mysession = new authentxsession();
if (isset($_SESSION['authentx']))
{
	$mcdn = $mysession->getmcdn();
	$cdnparts = strpos($mcdn, 'usaccess') !== false ? true : false;			
}

include('/authentx/core/http7/inc-session.php');
?>