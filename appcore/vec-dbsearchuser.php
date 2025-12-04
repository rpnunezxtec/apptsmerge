<?PHP
// $Id: vec-dbsearchuser.php 44 2008-10-29 06:06:24Z atlas $
if (isset($process_srchrtn))
{
	if ($process_srchrtn)
		return include_once('/authentx/core/http7/db-searchuser.php');
	else 
		include_once('/authentx/core/http7/db-searchuser.php');
}
else 
	include_once('/authentx/core/http7/db-searchuser.php');

?>