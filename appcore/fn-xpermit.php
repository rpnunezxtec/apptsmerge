<?PHP


	/**
	* @return array with 'pdn', 'pid' attributes or false.
	* @param string $permit_issued. The issued base for the issued permission location. (ex: ounit=issued)
	* @param string $permit_basedn. The base DN for the permission searches (not including the issued base).
	* @param string $default_accessgroupdn. The access group to assign the permission to.
	* @param array $token_basedn. The tree where token will be searched.

	* *** being switched to tokencdn @param array $tokenacid. The token acid to assign permission to (used as the acpid of permission).
	* @param string $tokencdn.  The token cdn to give permisisons to (pick up acids from here to use as acpid of permission)
	* @param string $adminname. The name to appear in the object creation log.  Also used as issby attribute.
	* @param string $defaultexpdate. The (optional) date of permission expiration, although required for access control.

	* @param string $defaultstatus. The (optional) status of permission, although required for access control.
	* @param string $_default_comment. The (optional) desc of permission.
	* @param resource $dbh. An (optional) database handle.
	* @param string $host. The (optional) ldap host.
	* @desc Attempts to create a new permission in the database and returns the identifiers and DN of the objects.
	*/
function
createnewpermission($permit_issueddn, $permit_basedn, $default_accessgroupdn, $token_basedn, $tokencdn, $adminname, $defaultexpdate = false, $defaultstatus = false, $_default_comment = false, $dbh = false, $host = false)
{

	require_once("/authentx/core/http7/config-base.php");
	require_once("/authentx/core/http7/cl-xld.php");
	require_once("/authentx/core/http7/cl-numbers.php");
//	include_once("../appconfig/config-xpermit.php");
//	include_once('../appconfig/config-app.php');

	$myxld = new authentxxld();
	$mynumbers = new authentxnumbers();


	if($permit_issueddn === false || $permit_basedn === false || $tokencdn === false)
		return "-4";

	if ($dbh === false)
	{
		if ($host == NULL)
		{
			require_once("/authentx/core/http7/cl-session.php");
//			require_once("cl-session.php");
			$mysession = new authentxsession();
			$host = $mysession->gethost();
		}
		// establish a connection
		$dbh = $myxld->xld_cb2authentx($host);
		if ($dbh === false)
			return "-2";
	}

	//first make sure the permission access group exists.
	$r_agroup = $myxld->xld_read($dbh, $default_accessgroupdn, "(objectclass=permission)", array("agpid", "pid"));
	$rlecode = $myxld->xld_errno($dbh);

	if ($rlecode != 0x00)
			return $rlecode;

	if ($r_agroup instanceof LDAP\Result)
		$c = $myxld->xld_count_entries($dbh, $r_agroup);
	else
		$c = 0;

	//default access group permission does not exist.
	//don't think we want to issue a permission without an accessgroup.
	if(($rlecode == 0) && ($c == 0))
			return -6;

	//permission exists.  Get mandatory elements:  agpid and the agdn.
	if(($rlecode == 0) && ($c > 0))
	{
		$objset = $myxld->xld_first_entry($dbh, $r_agroup);
		if($objset)
		{
			$agdn = $myxld->xld_get_dn($dbh, $objset);
			$agpidvals = $myxld->xld_get_values($dbh, $objset, "agpid");
			$pidvals = $myxld->xld_get_values($dbh, $objset, "pid");
			if ($agpidvals["count"] > 0)
				$agpid = $agpidvals[0];
			else
				$agpid = false;
			if($pidvals["count"] > 0)
				$pname = $pidvals[0];
			else
				$pname = false;
		}

	}
	else
		return "-8";

	//mandatory permission elements should be present.
	if($agpid === false || $agdn === false)
		return "-9";

	//now grab the token.
	$r_tokenset = $myxld->xld_read($dbh, $tokencdn, "(objectclass=credential)", array("cid", "cdn", "acid"));
	//$r_tokenset = $myxld->xld_search($dbh, $token_basedn, "(&(objectclass=credential)(cid=".$tokenacid."))", array("cid", "cdn", "acid"));
	$rltcode = $myxld->xld_errno($dbh);

	if($rltcode != 0x00)
		return $rlecode;

	$t = $myxld->xld_count_entries($dbh, $r_tokenset);
	if (($rltcode == 0) && ($t > 0))
	{
		//found it and there should only be one.
		$tset = $myxld->xld_first_entry($dbh, $r_tokenset);
		{
			if($tset)
			{
				//the token we are looking at
				$utdn = $myxld->xld_get_dn($dbh, $tset);
				$cdnvals = $myxld->xld_get_values($dbh, $tset, "cdn");
				if ($cdnvals["count"] > 0)
					$ucdn = $cdnvals[0];
				else
					$ucdn = false;

				//and the acids:
				$acidvals = $myxld->xld_get_values($dbh, $tset, "acid");
				if($acidvals["count"] > 0)
				{
					$tacids = $acidvals;
					unset($tacids["count"]);
				}
				else
					$tacids = false;



			}
		}

	}
	else
		return "-40";

	if($ucdn === false || $utdn === false)
		return "-12";


	//get the list of acids:
	$tac = count($tacids);

	//WE ONLY WANT TO ASSIGN DEFAULT PERMISSIONS TO FAX ACIDS
	if($tac > 0)
	{		
		$found = false;
		$tokenacid = false;
		for($i=0; $i<$tac; $i++)
		{
			if(!$found)
			{	
				$tmp = $tacids[$i];
				if(strcasecmp(substr($tmp,0,3), "FAX") == 0)
				{
					$found = true;
					$tokenacid = $tmp;					
				}
			}
		}
	}
	$tacids = array();
	$tacids[0] = $tokenacid;
	$tac = count($tacids);

	if($tac > 0)
	{

		//for each acid, check if a default permission already exits.  if so, unset from list.
		for($i=0; $i<$tac; $i++)
		{
			$tokenacid = $tacids[$i];

			//check if permission already exists for this token acid.  if not we can create. if so, take out from list
			$r_permit = $myxld->xld_search($dbh, $permit_basedn, "(&(objectclass=permission)(acpid=".$tokenacid."))", array("pdn", "acpid"));
			$rltcode = $myxld->xld_errno($dbh);
			$p = $myxld->xld_count_entries($dbh, $r_permit);
			$found = 0;
			if (($rltcode == 0) && ($p > 0))
			{
				//found some permissions for this acid.  grab them to see if any are defaults.
				for($pset = $myxld->xld_first_entry($dbh, $r_permit); $pset != false; $pset = $myxld->xld_next_entry($dbh, $pset))
				{
					if(!$found)
					{
						//the permission we are looking at
						$updn = $myxld->xld_get_dn($dbh, $pset);
						$updnvals = $myxld->xld_get_values($dbh, $pset, "pdn");
						if ($updnvals["count"] > 0)
						{
							//the group we are comparing to:
							$tempagdn = $updnvals[0];
							if(strcasecmp($tempagdn, $default_accessgroupdn) == 0)
							{
								$found = 1;
								//permission already exists.
								//****************TODO:  ADD THE REST OF THE ACPIDS TO THAT PERMISISON.
								$permit["acpid"] = array();

								//check to see which acpids already exist for this default permission:
								$acpidvals = $myxld->xld_get_values($dbh, $pset, "acpid");
								if($acpidvals["count"] > 0)
								{
									$acpids = $acpidvals;
									unset($acpids["count"]);
									$tpc = count($acpids);
									if($tpc > 0)
									{
										$l = 0;
										
//print "tac = ".$tac."\n";
										for($j=0; $j<$tac; $j++)
										{
//print "tacid count = ".$tpc."\n";
											$found = false;
											for($k=0; $k<$tpc;$k++)
											{
//print "token = ".$tacids[$j]."\n";
//print "acpid = ".$acpids[$k]."\n";
												if(strcasecmp($tacids[$j], $acpids[$k]) == 0)
												{

													$found = 1;
												}
											}
											if(!$found)
											{
//print "not found: ".$tacids[$j]."\n";
												$permit["acpid"][] = $tacids[$j];
											}

										}
										
									}

								}
								
								if(count($permit["acpid"]) > 0)
								{
//var_dump($permit["acpid"]);
									$r_permit = $myxld->xld_mod_add($dbh, $updn, $permit);
									$rlecode = $myxld->xld_errno($dbh);
									// log the general ldap error if we need to
									if ($rlecode != 0x00)
										return "-blah";
								}
								else
								{
									//default permission exists with all the acids.  nothing to do.
									return true;
								}

								return true;

							}
						}
					}
				}
			}  //ends if found permission

		}//ends for each token acid

	}//ends if count > 0


	//here if no default permission exists.  create one.
	$permit = array();
	$permit["objectClass"] = "permission";
	$permit["issdate"] = date("YmdHis");
	$permit["issdate"] .= TIMEZONE;
	$permit["issby"] = $adminname;
	$permit["cdn"] = $ucdn;
	$permit["typecode"] = @pack('n', PTYPE_PERMIT);
	$permit["agpid"] = $agpid;

	$permit["acpid"] = array();
	for($i=0; $i<$tac; $i++)
	{
		$permit["acpid"][$i] = $tacids[$i];
	}


	//$permit["acpid"] = $tokenacid;
	if($defaultstatus !== false)
		$permit["status"] = $defaultstatus;
	if($defaultexpdate !== false)
		$permit["expdate"] = $defaultexpdate;
	if($pname !== false)
		$permit["pname"] = $pname;
	if($_default_comment !== false)
		$permit["desc"] = $_default_comment;

	$permitpid = $mynumbers->getnewid(ACCESSPID, PASSWD_PHP, USERDN_PHP);
	if ($permitpid === false)
		return "-28";


	// build the pdn
	$permitpdn = "pid=".$permitpid.",".$permit_issueddn.",".$permit_basedn;
	$permit["pid"] = $permitpid;
	// add the pdn reference back to the originating master permission
	$permit["pdn"] = $agdn;
//print "permitpdn = ".$permitpdn;
//var_dump($permit);	

	$r_permit = $myxld->xld_add($dbh, $permitpdn, $permit);
	$rlecode = $myxld->xld_errno($dbh);
	// log the general ldap error if we need to
	if ($rlecode != 0x00)
		return "-31";

	// ldap success, so continue and add the pdn to the user's credential
	// and the alias to the master group permission object -- NOT IMPLEMENTED.
	// Add an event log entry if an application ID is set.
	//NOT IMPLEMENTED.
	/*
	if ($_xemenable)
	{
		$edomain = $mylog->hextobin("00000000000000000000000000000000");
		$plentry = array();
		//$plentry[7] = substr(str_pad($_POST["_logappid"], 32, "0", STR_PAD_LEFT), 0, 32);
		$plentry[19] = $permitpdn." issued";
		$plentry[20] = $adminname;
		//$plentry[21] = $mysession->getucid();
		if (isset($_xemagencyid))
			$plentry[22] = $_xemagencyid;
		$epayload = $mylog->buildelogpayload($plentry);
		$esourceid = $mylog->hextobin($plentry[20]);
		$mylog->queue_event(ECLA_LOG, ECOM_NONE, $edomain, $esourceid, $epayload);
	}
	*/


	// create the entry to the user's credential object
	$credmod["pdn"] = $permitpdn;
	$r_modcr = $myxld->xld_mod_add($dbh, $ucdn, $credmod);
	$rlecode = $myxld->xld_errno($dbh);
	// log the general ldap error if we need to
	if ($rlecode != 0x00)
	{
		//SHOULD CLEAN UP IF THERE'S AN ERROR AT THIS POINT SINCE PERMISSION WAS ALREADY CREATED.
		return "-33";
	}


	//looks good.  send results.
	$rv = array();
	$rv["pid"] = $permitpid;
	$rv["pdn"] = $permitpdn;
	return $rv;

}

?>