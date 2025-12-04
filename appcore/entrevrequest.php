<?PHP

// $Id:$

// This script is called to revoke a user and all tokens and certificates associated
// with that user.  It is meant to be called in the on-line mode, i.e. through the 
// web forms, although it should reside in authentx core.

if (!isset($_SESSION["authentx"]))
	session_start();

header("Cache-control: private");
$corebase = "/authentx/core/http7";

include("$corebase/inc-session.php");
include_once("$corebase/cl-log.php");
require_once("$corebase/cl-xld.php");
require_once("$corebase/cl-ldap.php");
require_once("../appconfig/config-carevrequest.php");
require_once("$corebase/fn-gco.php");
require_once("$corebase/cl-derparser.php");
date_default_timezone_set(DATE_TIMEZONE);

$mylog = new authentxlog();
$myxld = new authentxxld();
$myldap = new authentxldap();

// get the name of the form being posted
$formname = trim($_POST["formname"]);

// get the filename for the form
$formfile = $mysession->filterfileurl(trim($_POST["formfile"]));

// the user cdn
$ucdn = $mysession->getucdn();
$mcdn = $mysession->getmcdn();

$host = $mysession->gethost();
$dbh = $myxld->xld_cb2authentx($host);

if ($dbh !== false)
{
	$tcdnlist = array();
	$uedn = $mysession->getuedn();
	$tcdnlist = $myldap->getldapattr($dbh, $ucdn, "cdn", false, false, "credential");

	// get revocation reason
	$remrslt = $myxld->xld_read($dbh, "sysid=statusreason,".$uedn, "objectclass=xsystem", array("sysval"));
	$rlecode = $myxld->xld_errno($dbh);		// check the resulting ldap error code
	if ($rlecode != 0 or $remrslt == false)
	{
		$ldaperrmsg = $myxld->xld_error($dbh);
	}
	$remobjset = $myxld->xld_first_entry($dbh, $remrslt);
	$remarray = $myxld->xld_get_values_len($dbh, $remobjset, "sysval");
	$revReason = $remarray[0];

	// spawn revocation request for each token certificate
	foreach($tcdnlist as $tcdn)
	{
		$trslt = $myxld->xld_read($dbh, $tcdn, "objectclass=credential", array("status"));
		$objset = $myxld->xld_first_entry($dbh, $trslt);
		$valarray = $myxld->xld_get_values_len($dbh, $objset, "status");
		$tstatus = $valarray[0];
		
		// get token type
		$tkntyperslt = $myxld->xld_read($dbh, $tcdn, "objectclass=credential", array("tokenclass"));
		$tkntypeobjset = $myxld->xld_first_entry($dbh, $tkntyperslt);
		$tkntypearray = $myxld->xld_get_values_len($dbh, $tkntypeobjset, "tokenclass");
		$tkntype = $tkntypearray[0];
		
		$mcid = $mysession->getmcid();
		$ucid = $mysession->getucid();

		$keylist = "";
		$keys = "";
		$keylist_pending = "";
		$pendingrev = false;
		
		foreach($_cert_types as $keyid => $cert_type_desc)
		{
			//first check certificate status
			$keycontainer = $_key_map[$_appletid][$keyid][0];
			$certdn = "gcoid=".$_appletid.".".$keycontainer.",".$tcdn;
			$certrslt = $myxld->xld_read($dbh, $certdn, "objectclass=gco", array("status"));
			$rlecode = $myxld->xld_errno($dbh);
			if ($rlecode == 0x00)
			{
				$objset = $myxld->xld_first_entry($dbh, $certrslt);
				if ($objset)
				{
					$valarray = $myxld->xld_get_values_len($dbh, $objset, "status");
					$status = $valarray[0];
					
					//check if cert status is active 
					if ((strcasecmp($status, "active") == 0))
					{
						$keylist .= $keyid;
						$keylist .= " ";
						
						$keys .= $keyid.",";

						//update token history log entry
						$tldn = "sysid=logs,".$tcdn;
						$myldap->ldap_checkandcreate($tldn, "xsystem", array(), $host);
						$ologname = $myldap->getfullname($mcdn);
						if ($ologname === false)
							$ologname = $mysession->getmcid();
						$dt = date("YmdHis");
						$dt .= TIMEZONE;
						$ologentry = "[REVOKE CERT] Submitted Revocation Request for key: ".$keyid." by ".$ologname." at ".$dt;
						$mylog->addobjlog($tldn, $ologentry, $host, $dbh);
						
						$gco_update = array();
						$gco_update["status"] = "pendingrev"; 
						$rslt = $myxld->xld_modify($dbh, $certdn, $gco_update);

						if ($_xemenable)
						{
							$edomain = $mylog->hextobin("00000000000000000000000000000000");
							$plentry = array();
							$plentry[7] = EAID_CERTREVREQ;
							$plentry[19] = "Revocation attempt: ".$_appletid." Key: ".$keyid." Token: ".$tcdn;
							$plentry[20] = $mcid;
							$plentry[21] = $ucid;
							if (isset($_xemagencyid))
								$plentry[22] = $_xemagencyid;
							$epayload = $mylog->buildelogpayload($plentry);
							$esourceid = $mylog->hextobin($plentry[20]);
							$mylog->queue_event(ECLA_LOG, ECOM_NONE, $edomain, $esourceid, $epayload);
						}
					}
					else if (strcasecmp($status, "pendingrev") == 0)
					{
						$pendingrev = true;
						$keylist_pending .= $keyid;
						$keylist_pending .= " ";
					}
					else
					{
						$error = true;
						$errormsg[] = "Certificate status is not active.";
						$errorcode[] = "0x00";
					}
				}
			}
		}
		
		if($keys != "")
		{
			//start background process
			chdir($_execdir);
			$call = $_process." ".$tcdn." ".$_appletid." ".$keys." ".$revReason." ".$tkntype." ".$mcid." ".$ucid;
			pclose(popen($call.' &', 'r'));
		}
		
		if($pendingrev == true)
			print "<script type=\"text/javascript\">alert('Revocation request still processing for cert(s): ".$keylist_pending.". Please check again later.')</script>\n";
			
		// update token status to revoked
		if(stristr($tstatus, "revoked") === false)
		{
			$rslt = $myxld->xld_mod_replace($dbh, $tcdn, array("status" => "Revoked"));
			if (!$rslt)
			{
				$error = true;
				$errormsg[] = "Could not update the status in the LDAP directory.";
				$errorcode[] = "0x20";				
			}
			else
			{
				//update token history log entry
				$tldn = "sysid=logs,".$tcdn;
				$myldap->ldap_checkandcreate($tldn, "xsystem", array(), $host);
				$ologname = $myldap->getfullname($mcdn);
				if ($ologname === false)
					  $ologname = $mysession->getmcid();
				$dt = date("YmdHis");
				$dt .= TIMEZONE;
				$ologentry = "[REVOKE CRED] Credential was revoked by ".$ologname." at ".$dt;
				$mylog->addobjlog($tldn, $ologentry, $host, $dbh);
			}
		}
	}
		
	print "<script type=\"text/javascript\">alert('Revocation Request Submitted')</script>\n";
	print "<script type=\"text/javascript\">history.go(-1)</script>\n";
}
else
{
	print "<script type=\"text/javascript\">alert('Error: could not connect/bind to server')</script>\n";
	print "<script type=\"text/javascript\">history.go(-1)</script>\n";
}

?>
