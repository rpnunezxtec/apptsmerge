<?PHP

// $Id: local-certrevrequest.php 350 2008-05-31 06:05:54Z jackson $

// This program is intended to send revoke certificate requests to the remote CA for the 
// token specified by the card DN and the following types of certificates:
//		PIV authentication certificate : keyid = 9A
//		Key management certificate : keyid = 9D
//  	Digital signature certificate : keyid = 9C
//  	PIV card authentication certificate : keyid = 9E
// 

if (!isset($_SESSION["authentx"]))
	session_start();

header("Cache-control: private");
include("/authentx/core/http7/inc-session.php");

require_once("/authentx/core/http7/config-base.php");
require_once("../appconfig/config-app.php");	
require_once("../appconfig/config-carevrequest.php");

require_once("/authentx/core/http7/cl-log.php");
require_once("/authentx/core/http7/cl-xld.php");
require_once("/authentx/core/http7/cl-ldap.php");
date_default_timezone_set(DATE_TIMEZONE);

$mylog = new authentxlog();
$myldap = new authentxldap();
$myxld = new authentxxld();
$mysession = new authentxsession();

$error = false;
$errormsg = array();
$errorcode = array();

if(!isset($carddn))
{
	if (isset($_POST["dn"]))
		$carddn = trim($_POST["dn"]);
	elseif (isset($_GET["dn"]))
		$carddn = $_GET["dn"];
	else
	{
		$error = true;
		$errormsg[] = "Token DN was not specified\n";
		$errorcode[] = "0x31";
	}
}
		
if(!isset($keyid))
{
	if (isset($_POST["keyid"]))
		$keyid = trim($_POST["keyid"]);
	elseif (isset($_GET["keyid"]))
		$keyid= trim($_GET["keyid"]);
	else
	{
		$error = true;
		$errormsg[] = "Key ID was not specified\n";
		$errorcode[] = "0x31";
	}
}

// verify MAC
if (!isset($avc))
	if (isset($_GET["avc"]))
		$avc = $_GET["avc"];

if (isset($avc))
{
	$myavc = $mysession->createmac(strtolower($carddn));
	if (strcasecmp($avc, $myavc) == 0)
		$avctest = true;
	else
		$avctest = false;
}
else
	$avctest = false;
	
if ($avctest !== true)
{
	print "<script type=\"text/javascript\">alert('Certificate auth failed avc(".$avc.") myavc(".$myavc.").')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	die();
}

// the user cdn
$ucdn = $mysession->getucdn();
$mcdn = $mysession->getmcdn();

$host = $mysession->gethost();
$dbh = $myxld->xld_cb2authentx($host);

if ($dbh !== false and !$error)
{
	// get revocation reason
	if(!isset($revReason))
	{
		$remrslt = $myxld->xld_read($dbh, "gcoid=card,".$carddn, "objectclass=gco", array("rem"));
		$remobjset = $myxld->xld_first_entry($dbh, $remrslt);
		$remarray = $myxld->xld_get_values_len($dbh, $remobjset, "rem");
		$revReason = $remarray[0];
	}
	
	// get token type
	$tkntyperslt = $myxld->xld_read($dbh, $carddn, "objectclass=credential", array("tokenclass"));
	$tkntypeobjset = $myxld->xld_first_entry($dbh, $tkntyperslt);
	$tkntypearray = $myxld->xld_get_values_len($dbh, $tkntypeobjset, "tokenclass");
	$tkntype = $tkntypearray[0];

	$mcid = $mysession->getmcid();
	$ucid = $mysession->getucid();

	//first check certificate status
	$certstatus = true;
	$pendingrev = false;
	$keycontainer = $_key_map[$_appletid][$keyid][0];
	$certdn = "gcoid=".$_appletid.".".$keycontainer.",".$carddn;
	$certrslt = $myxld->xld_read($dbh, $certdn, "objectclass=gco", array("status"));
	$rlecode = $myxld->xld_errno($dbh);
	
	if ($rlecode == 0x00)
	{
		$objset = $myxld->xld_first_entry($dbh, $certrslt);
		if ($objset)
		{
			$valarray = $myxld->xld_get_values_len($dbh, $objset, "status");
			$status = $valarray[0];

			if (strcasecmp($status, "pendingrev") == 0)
			{
				$pendingrev = true;
			}
			else if (strcasecmp($status, "active") == 0)
			{
				chdir($_execdir);			
				$call = $_process." ".$carddn." ".$_appletid." ".$keyid." ".$revReason." ".$tkntype." ".$mcid." ".$ucid;	
				pclose(popen($call.' &', 'r'));

				//update token history log entry
				$tldn = "sysid=logs,".$carddn;
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
					$plentry[19] = "Revocation attempt: ".$_appletid." Key: ".$keyid." Token: ".$carddn;
					$plentry[20] = $mcid;
					$plentry[21] = $ucid;
					if (isset($_xemagencyid))
						$plentry[22] = $_xemagencyid;
					$epayload = $mylog->buildelogpayload($plentry);
					$esourceid = $mylog->hextobin($plentry[20]);
					$mylog->queue_event(ECLA_LOG, ECOM_NONE, $edomain, $esourceid, $epayload);
				}

				$myxld->xld_close($dbh);

				print "<script type=\"text/javascript\">alert('Submitting Revocation Request for key: ".$keyid."')</script>\n";
				print "<script type=\"text/javascript\">window.close()</script>\n";
			}
			else
				$certstatus = false;
		}
		else
			$certstatus = false;
	}
	else 
		$certstatus = false;

	$myxld->xld_close($dbh);
	
	if ($pendingrev == true)
	{
		print "<script type=\"text/javascript\">alert('Revocation request still processing for cert: ".$keyid.". Please check again later.')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
	}

	if ($certstatus == false)
	{
		print "<script type=\"text/javascript\">alert('Certificate status is not \"Active\"')</script>\n";
		print "<script type=\"text/javascript\">window.close()</script>\n";
	}
}
else
{
	$displaymsg = "";
	foreach($errormsg as $msg)
		$displaymsg = $displaymsg.$msg."; ";
	
	print "<script type=\"text/javascript\">alert('ERROR: ".$displaymsg.")')</script>\n";
	print "<script type=\"text/javascript\">window.close()</script>\n";
	
}
?>
