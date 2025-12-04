<?PHP
// $Id: fn-sponsorcheck.php 44 2008-10-29 06:06:24Z atlas $
// Checks that required fields are filled for sponsorship.
// Arguments: $dbh - connection to LDAP database. $list - list of mandatory attributes.
// Returns: true if OK, otherwise an array of strings for missing attributes

function 
checksponsor($dbh, $list)
{
	global $myldap;
	global $mysession;
	
	$missing = false;
	$missingattr = array();
	if (count($list) > 0)
	{
		foreach ($list as $entryset)
		{
			$dn = $entryset["dn"];
			$attr = $entryset["attr"];
			$gcotag = $entryset["gcotag"];
			$val = $entryset["val"];
			$name = $entryset["name"];
			
			$rv = $myldap->getldapattr($dbh, $dn, $attr, false, $gcotag);
			if ($rv === false)
			{
				$missing = true;
				$missingattr[] = $name;
			}
			else
			{
				if (count($rv) > 0)
				{
					if ($rv[0] == "")
					{
						$missing = true;
						$missingattr[] = $name;
					}
					if ($val !== false)
					{
						if (strcasecmp($val, $rv[0]) != 0)
						{
							$missing = true;
							$missingattr[] = $name;
						}
					}
				}
				else
				{
					$missing = true;
					$missingattr[] = $name;
				}
			}
		}
		if ($missing === true)
			return $missingattr;
		else
			return true;
	}
	else
		return true;

}

?>