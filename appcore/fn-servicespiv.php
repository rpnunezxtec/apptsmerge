<?php

function der2pem($der_data)
{
    $pem = chunk_split(base64_encode($der_data), 64, "\n");
    $pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
    return $pem;
}

/*
 function hex2bin($data)
{
         $bin    = "";
         $i      = 0;
         do {
             $bin    .= chr(hexdec($data{$i}.$data{($i + 1)}));
             $i      += 2;
         } while ($i < strlen($data));

        return $bin;
}
*/


function
finduser($dbh, $userid)
{

global $ldap_host;
global $myldap;
global $myxld;
global $_cred_basedn;
global $_token_basedn;
global $myservice;

$rv = array();
$error = false;
$errormsg = array();
$errorcode = array();

$ucdn = false;
$uedn = false;
$utdn = false;

        // check for crypto operations on SSN acid
        $plainuid = $userid;
        $userid = $myservice->checkcrypto_userid($userid);
	// search for the user
	$r_user = $myxld->xld_search($dbh, $_cred_basedn, "(&(objectclass=credential)(acid=".$userid."))", array("cid", "cdn", "edn"));
	$rlecode = $myxld->xld_errno($dbh);
	$c = $myxld->xld_count_entries($dbh, $r_user);
	if (($rlecode == 0) && ($c > 0))
	{
		$objset = $myxld->xld_first_entry($dbh, $r_user);
		if ($objset)
		{
			$ednvals = $myxld->xld_get_values($dbh, $objset, "edn");
			$cdnvals = $myxld->xld_get_values($dbh, $objset, "cdn");
			$ucdn = $myxld->xld_get_dn($dbh, $objset);
			if ($ednvals["count"] > 0)
				$uedn = $ednvals[0];
			else
				$uedn = false;
			// add all the token dn's as base dn's
			if ($cdnvals["count"] > 0)
			{
				$utdn = $cdnvals;
				unset($utdn["count"]);
			}
			else
				$utdn = false;

			// check to see whether the user has a token that matches this acid value
			$r_token = $myxld->xld_search($dbh, $_token_basedn, "(&(objectclass=credential)(acid=".$userid."))", array("cid", "cdn"));
			$rltcode = $myxld->xld_errno($dbh);
			$t = $myxld->xld_count_entries($dbh, $r_token);
			if (($rltcode == 0) && ($t > 0))
			{
				$tobjset = $myxld->xld_first_entry($dbh, $r_token);
				if ($tobjset)
				{
					$utdn = $myxld->xld_get_dn($dbh, $tobjset);
					// need to check that this token actually belongs to this user.
					$ucdnvals = $myxld->xld_get_values($dbh, $tobjset, "cdn");
					if ($ucdnvals["count"] > 0)
					{
						$tok_ucdn = $ucdnvals[0];
						if (strcasecmp($ucdn, $tok_ucdn) != 0)
							$utdn = false;
					}
					else
						$utdn = false;
				}
				else
					$utdn = false;
			}
			//else
				//$utdn = false;

		}
	}
	if ($rlecode > 0)
	{
		$error = true;
		$errormsg[] = "ldap error: ".$myxld->xld_err2str($rlecode);
		$errorcode[] = "0x".dechex($rlecode);
	}
	if ($c == 0)
	{
		// did not find the user using an acid value, try a scid now.
		$rs = $myldap->findednfromscid($plainuid, $ldap_host, $dbh);
		if ($rs !== false)
		{
			$nrs = count($rs);
			if ($nrs > 1)
			{
				$error = true;
				$errormsg[] = "Multiple users found with identifier: ".$plainuid;
				$errorcode[] = "0x00";
			}
			else
			{
				$uedn = $rs[0]["edn"];
				$ucdn = $rs[0]["cdn"];


				$r_u = $myxld->xld_search($dbh, $ucdn, "(objectclass=*)", array("cdn"));
				$rltcode = $myxld->xld_errno($dbh);
				$u = $myxld->xld_count_entries($dbh, $r_u);
				if (($rltcode == 0) && ($u > 0))
				{
					$uobjset = $myxld->xld_first_entry($dbh, $r_u);
					if($uobjset)
					{
						$cdnvals = $myxld->xld_get_values($dbh, $uobjset, "cdn");
						if($cdnvals["count"] > 0)
						{
							$utdn = $cdnvals;
							unset($utdn["count"]);
						}
						else
							$utdn = false;
					}
				}

			}
		}
		else
		{


			// search for the user via an acid in the token tree
			$r_token = $myxld->xld_search($dbh, $_token_basedn, "(&(objectclass=credential)(acid=".$userid."))", array("cid", "cdn"));
			$rltcode = $myxld->xld_errno($dbh);
			$t = $myxld->xld_count_entries($dbh, $r_token);
			if (($rltcode == 0) && ($t > 0))
			{
				$tobjset = $myxld->xld_first_entry($dbh, $r_token);
				if ($tobjset)
				{
					$utdn = $myxld->xld_get_dn($dbh, $tobjset);
					$ucdnvals = $myxld->xld_get_values($dbh, $tobjset, "cdn");
					if ($ucdnvals["count"] > 0)
					{
						$ucdn = $ucdnvals[0];
						$r_user = $myxld->xld_read($dbh, $ucdn, "objectclass=credential", array("cid", "cdn", "edn"));
						$rlecode = $myxld->xld_errno($dbh);
						if ($rlecode == 0)
						{
							$objset = $myxld->xld_first_entry($dbh, $r_user);
							if ($objset)
							{
								$ednvals = $myxld->xld_get_values($dbh, $objset, "edn");
								$cdnvals = $myxld->xld_get_values($dbh, $objset, "cdn");
								if ($ednvals["count"] > 0)
									$uedn = $ednvals[0];
								else
									$uedn = false;

								if ($cdnvals["count"] > 0)
								{
									$utdn = $cdnvals;
									unset($utdn["count"]);
								}
							}
						}
						else
						{
							$error = true;
							$errormsg[] = "user not found from token: ".$plainuid;
							$errorcode[] = "0x20";
						}
					}
					else
					{
						$error = true;
						$errormsg[] = "no credentials in token: ".$plainuid;
						$errorcode[] = "0x00";
					}
				}
			}
			else
			{
				$error = true;
				$errormsg[] = "user/token not found: ".$plainuid;
				$errorcode[] = "0x20";
			}
		}
	}

$rv["ucdn"] = $ucdn;
$rv["uedn"] = $uedn;
$rv["utdn"] = $utdn;
$rv["error"] = $error;
$rv["errorcode"] = $errorcode;
$rv["errormsg"] = $errormsg;
return $rv;

}


//****
// checks that user is not the ssame as admin.
// also checks user status.
//** ucdn = user cdn
//** admincid = admin cid
function
checkuser($dbh, $ucdn, $uedn, $admincid)
{

global $ldap_host;
global $myldap;

$rv = array();
$error = false;
$errormsg = array();
$errorcode = array();


		$spos = stripos($ucdn, "=");
		$epos = stripos($ucdn, ",");
		$mucid = substr($ucdn, $spos+1, $epos-$spos-1);

		if( strcasecmp($mucid, $admincid) == 0 )
		{
				$error = true;
				$errormsg[] = "You cannot edit your own record.";
				$errorcode[] = "0x77";
		}
		else
		{

			if ($myldap->ldap_checkstatus($uedn, array("active"), false, $ldap_host, $dbh) === false)
			{
				$error = true;
				$errormsg[] = "Not an active user.".$sv;
				$errorcode[] = "0x74";
			}

		}

$rv["error"] = $error;
$rv["errorcode"] = $errorcode;
$rv["errormsg"] = $errormsg;
	return $rv;

}

function
finduserfromtoken($dbh, $tokenid)
{
	return false;
}




// function searches for admin from token branch
// checks token status
// gets admin edn status
// gets admin cid for later use -- to make sure it is not the same as user cid
// checks roles based on configured list.
function
checkadmin($dbh, $adminid, $rolecheck = false, $sigcheck = false, $hash = false, $digest = false, $sig = false)
{

global $ldap_host;
global $myldap;
global $myxld;
global $allowedroles;
global $_token_basedn;

$rv = array();
$error = false;
$errormsg = array();
$errorcode = array();
$allowaccess = false;
$admincid = false;
$atdn = false;
$cert = false;

$rv["error"] = false;
$rv["errorcode"] = array();
$rv["errormsg"] = array();
$rv["allowaccess"] = false;
$rv["atdn"] = false;
$rv["admincid"] = false;
$rv["cert"] = false;


//KIOSK sends operator id as 32-ZEROS. (000...00..000)
//CARDUTIL sends operator id as 16-ZEROS (000...00)
if ( ( strcasecmp($adminid, "0000000000000000") == false ) || (strcasecmp($adminid, "00000000000000000000000000000000") == false) )
{
	$rv["allowaccess"] = true;
	return $rv;
}


//search for admin account.
$r_admintoken = $myxld->xld_search($dbh, $_token_basedn, "(&(objectclass=credential)(acid=".$adminid."))", array("cid", "cdn"));
$rladmintcode = $myxld->xld_errno($dbh);

$t = $myxld->xld_count_entries($dbh, $r_admintoken);
if (($rladmintcode == 0) && ($t > 0))
{
	$admintobjset = $myxld->xld_first_entry($dbh, $r_admintoken);
	if ($admintobjset)
	{

		//admin token dn
		$atdn = $myxld->xld_get_dn($dbh, $admintobjset);

		//check admin token status:
		if($myldap->ldap_checkstatus($atdn, array("active"), false, $ldap_host, $dbh) === false)
		{
			$error = true;
			$errormsg[] = "Not an active credential for administrator.";
			$errorcode[] = "0x47";
		}

		// certificate of administrator
                $certdn = "gcoid=A000000308.5FC105,".$atdn;
                $certset = $myldap->getldapattr($dbh, $certdn, "xblk", false, "000070", "gco");
		$cert = $certset[0];

		// NEW.
		if($error == false)
		{
			// Verify certificate is not revoked.
                        if ($myldap->ldap_checkstatus($certdn, array("active"), false, $ldap_host, $dbh) === false)
                        {
                                $error = true;
                                $errormsg[] = "Not an active certificate.".$sv;
                                $errorcode[] = "0x79";
                        }
		}

		if (!$error)
		{
			// get admin authentx cdn from token.
			$acdnvals = $myxld->xld_get_values($dbh, $admintobjset, "cdn");
			if ($acdnvals["count"] > 0)
			{
				$tok_acdn = $acdnvals[0];
				//now grab the user roles.

				$r_adminuser = $myxld->xld_read($dbh, $tok_acdn, "(objectclass=credential)", array("role", "cid", "edn"));
				$r_aecode = @ldap_error($dbh);
				if ($r_aecode == 0)
				{
					$aobjset = $myxld->xld_first_entry($dbh, $r_adminuser);
					if ($aobjset)
					{


						//get CID to make sure you are not trying to retrieve same record -- done later.
						$admincidvals = $myxld->xld_get_values($dbh, $aobjset, "cid");
						if ($admincidvals["count"] > 0)
						{
							$admincid = $admincidvals[0];
						}
						else
						{
							$error = true;
							$errorcode[] = "0x43";
							$errormsg[] = "Admin account not found.";
						}


						if (!$error)
						{

							// check admin status to make sure user and token are not revoked.
							$adminednvals = $myxld->xld_get_values($dbh, $aobjset, "edn");
							if ($adminednvals["count"] > 0)
							{
								$adminedn = $adminednvals[0];
								if($myldap->ldap_checkstatus($adminedn, array("active"), false, $ldap_host, $dbh) === false)
								{
									$error = true;
									$errormsg[] = "Not an active administrator.";
									$errorcode[] = "0x46";
								}
							}
							else
							{
								$adminedn = false;
								$error = true;
								$errormsg[] = "Administrator not active.";
								$errorcode[] = "0x45";
							}

						}

						if (!$error && $rolecheck == true)
						{

							//get roles.
							$adminrolevals = $myxld->xld_get_values($dbh, $aobjset, "role");
							if($adminrolevals["count"] > 0)
							{

								//print_r($adminrolevals);
								unset($adminrolevals["count"]);
								$iroles = array_intersect($allowedroles, $adminrolevals);
								if (count($iroles) > 0)
								{
									$allowaccess = true;
								}
								else
								{
									$error = true;
									$errorcode[] = "0x39";
									$errormsg[] = "Insufficient roles/privileges.";
								}
							}
							else
							{
								$error = true;
								$errorcode[] = "0x38";
								$errormsg[] = "No roles found.";
							}
						}//ends signature checks.


						if (!$error && $sigcheck == true)
						{
							$allowaccess = false;
							$calchash = openssl_digest($hash, "sha256", true);			

							if ( strcasecmp($digest, $calchash) == 0)
							{
								//if hashes match, continue to check signature.
								//$cert = $rv["cert"];
								$pemcert = der2pem($cert);
								$pubkeyid = openssl_pkey_get_public($pemcert);

								$sigok = openssl_verify($hash, $sig, $pubkeyid, "sha256");
								if( $sigok == 1)
								{
									//print "<!-- Verified. -->\n";
									$allowaccess = true;
								}
								elseif ($sigok == 0)
								{
									//print "<-- Not verified. -->\n";									
									$error = true;
									$errormsg[] = "Signature not verified.";
									$errorcode[] = "0x81";
								}
								else
								{
									//print "<-- Ugly. -->\n";
									$error = true;
									$errormsg[] = "Unable to verify Signature.";
									$errorcode[] = "0x83";
								}

								// free the key from memory
								openssl_free_key($pubkeyid);


							}
							else
							{

									$error = true;
									$errormsg[] = "Signature unable to be verified.";
									$errorcode[] = "0x82";
							}

						}// ends signature checks.
					}
					else
					{
						//aobjectset false.
						$error = true;
						$errorcode[] = "0x37";
						$errormsg[] = "Administrator record not found from token.";
					}
				}
				else
				{
					//Error retrieving administrator account.
					$error = true;
					$errorcode[] = "0x36";
					$errormsg[] = "Admin user not found from token";
				}
			}
			else
			{
				$atdn = false;
				$error = true;
				$errorcode[] = "0x40";
				$errormsg[] = "Could not locate admin record";
			}

		} //ends if no eror.
	}
	else
	{
		$atdn = false;
		$error = true;
		$errorcode[] = "0x42";
		$errormsg[] = "Administrator object not found.";
	}
}
else
{
	$atdn = false;
	$error = true;
	$errorcode[] = "0x41";
	$errormsg[] = "Administrator token not found.";
}

$rv["error"] = $error;
$rv["errorcode"] = $errorcode;
$rv["errormsg"] = $errormsg;
$rv["allowaccess"] = $allowaccess;
$rv["admincid"] = $admincid;
$rv["atdn"] = $atdn;
$rv["cert"] = $cert;
return $rv;

}


?>
