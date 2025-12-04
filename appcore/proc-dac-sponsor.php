<?PHP
// $Id: proc-dac-sponsor.php

if (!isset($_SESSION['authentx']))
	session_start();
header("Cache-control: private");
include("vec-incsession.php");
include_once("vec-clldap.php");
include_once("vec-clprocess.php");
include_once("vec-cllog.php");
require_once("vec-clxld.php");
include_once("fn-sponsorcheck.php");

date_default_timezone_set(DATE_TIMEZONE);

$myldap = new authentxldap();
$myprocess = new authentxprocess();
$mylog = new authentxlog();
$myxld = new authentxxld();

// get the name of the form being posted
$formname = $_POST["formname"];
$formfile = $mysession->filterfileurl($_POST["formfile"]);

// define the $uedn before including the mandatory list
$uedn = $mysession->getuedn();
include("../appconfig/config-dac-sponsor.php");

$useremailvals = $mysession->getformvalue($formname, "useremail");
$useremail = false;
if ($useremailvals !== false)
{
	if (count($useremailvals) > 0)
	{
		if ($useremailvals[0] != "")
			$useremail = $useremailvals[0];
	}
}

// do not send emails.
$useremail = false;

// Functionality:
// Record a new remark if entered by an adjudicator.
// Record a status change if entered by an adjudicator.
// Any status change creates an objectlog entry and
// an event log entry
// 'Sponsor Applicant' button is processed if pressed by a sponsor.
// The applicant's process object is filled with the sponsor's details
// and the status changed (and objectlog and event log entries recorded).

if (isset($_POST["btn_sponsor"]))
{
	if ($mysession->testmprocmask(PMASK_SPONSOR) === true)
	{
		$host = $mysession->gethost();
		$acdn = $mysession->getmcdn();
		$ucdn = $mysession->getucdn();

		$dbh = $myxld->xld_cb2authentx($host);
		if ($dbh !== false)
		{
			$ologname = $myldap->getfullname($acdn, $host, $dbh);
			if ($ologname === false)
				$ologname = $mysession->getmcid();

			$dt = date("YmdHis");
			$dt .= TIMEZONE;

			$cldn = "sysid=logs,".$uedn;

			// first test to see that everything required is non-null to allow sponsorship
			$x = checksponsor($dbh, $mandatory_list);
			if ($x !== true)
			{
				print "<script type=\"text/javascript\">alert('Mandatory elements missing:\\n";
				foreach ($x as $e)
					print $e."\\n";
				print "')</script>\n";
				print "<script type=\"text/javascript\">history.go(-1)</script>\n";
				die();
			}
			
			$procdn = "procid=sponsor,ounit=dac,".$uedn;
			$procentry = array();
			$myprocess->checkcreateprocessobject($uedn, "dac", "sponsor", $dbh, $host);
	
			// startby and endby fields filled with sponsor's cdn
			$scdn = $mysession->getmcdn();
			$procentry["startby"] = $scdn;
			$procentry["endby"] = $scdn;

			// startdate and enddate filled with now
			$procentry["startdate"] = $dt;
			$procentry["enddate"] = $dt;
	
			// change the status to 'approved'
			$newstat = "Approved";
			$procentry["status"] = $newstat;

			// modify the attributes to the new values
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
			
			// Add object logs
			$ologentry = "[DAC SPONSOR] Sponsored by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			// Add to common log object for user
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
			
			// queue an event log entry
			if ($_xemenable)
			{
				$eparts = $myldap->dntoparts($uedn);
				$edomain = $mylog->hextobin("00000000000000000000000000000000");
				$plentry = array();
				$plentry[7] = EAID_DAC_SPONSOR;
				$plentry[19] = "Applicant sponsored.";
				$plentry[20] = $mysession->getmcid();
				$plentry[21] = $mysession->getucid();
				$plentry[33] = $newstat;
				$plentry[34] = $ologname;
				$plentry[36] = $eparts[1];
				if (isset($_xemagencyid))
					$plentry[22] = $_xemagencyid;
				$epayload = $mylog->buildelogpayload($plentry);
				$esourceid = $mylog->hextobin($plentry[20]);
				$mylog->queue_event(ECLA_LOG, ECOM_NONE, $edomain, $esourceid, $epayload);
			}

			// email to the applicant
			if ($useremail !== false)
			{
				$mailto = $useremail;
				$mailsubject = "DAC Sponsorship";
				$mailbody = "You have been sponsored for enrollment in the HSPD12 System.\n";
				$mailbody .= "Please contact the registrar to arrange an appointment for enrollment.\n";
				$mailbody .= "\nThis message is automatically generated. Please do not reply.\n";
				$mailheaders = "From: Authentx System Server <postmaster@authentx.com>\r\n";
				mail($mailto, $mailsubject, $mailbody, $mailheaders);
			}

			$myxld->xld_close($dbh);
		}
	}
	else
	{
		print "<script type=\"text/javascript\">alert('You do not have a sponsorship role.')</script>\n";
		print "<script type=\"text/javascript\">history.go(-1)</script>\n";
		die();
	}
}
elseif (isset($_POST["btn_adjudicate"]))
{
	if ($mysession->testmprocmask(PMASK_PRPSPONSOR) === true)
	{
		$host = $mysession->gethost();
		$acdn = $mysession->getmcdn();
		$ucdn = $mysession->getucdn();

		$dbh = $myxld->xld_cb2authentx($host);
		if ($dbh !== false)
		{
			$ologname = $myldap->getfullname($acdn, $host, $dbh);
			if ($ologname === false)
				$ologname = $mysession->getmcid();

			$dt = date("YmdHis");
			$dt .= TIMEZONE;

			$cldn = "sysid=logs,".$uedn;

			$procdn = "procid=sponsor,ounit=dac,".$uedn;
			$procentry = array();
			$myprocess->checkcreateprocessobject($uedn, "dac", "sponsor", $dbh, $host);
	
			// check write permission for status
			$wauth = $mysession->getformewm($formname, "status");
			if ($wauth)
			{
				// adjudby and adjuddate should be filled
				$procentry["adjudby"] = $acdn;
				$procentry["adjuddate"] = $dt;
		
				// status change
				$newstat = trim($_POST["status"]);
				$oldstatvals = $mysession->getformvalue($formname, "status");
				if ($oldstatvals !== false)
					$oldstat = $oldstatvals[0];
				else
					$oldstat = "";
				if (strcasecmp($newstat, $oldstat) !== 0)
				{
					$procentry["status"] = $newstat;
					if ((strcasecmp($newstat, "approved") == 0) || (strcasecmp($newstat, "rejected") == 0))
					{
						// manual approval or rejection fills out the endby and enddate attributes
						$procentry["endby"] = $acdn;
						$procentry["enddate"] = $dt;
					}
					if (strcasecmp($newstat, "pending") == 0)
					{
						// manual pending fills out the startby and startdate attributes
						$procentry["startby"] = $acdn;
						$procentry["startdate"] = $dt;
					}
					
					// modify the attributes to the new values
					$myxld->xld_modify($dbh, $procdn, $procentry);
					$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
					
					// Add object logs
					$ologentry = "[DAC SPONSOR] Modified to ".$newstat." by ".$ologname." on ".$dt;
					$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
					// Add to common log object for user
					$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
					$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
					
					// queue event log entry
					if ($_xemenable)
					{
						$eparts = $myldap->dntoparts($uedn);
						$edomain = $mylog->hextobin("00000000000000000000000000000000");
						$plentry = array();
						$plentry[7] = EAID_DAC_SPONSOR;
						$plentry[19] = "Applicant sponsorship modified. Changed to ".$newstat;
						$plentry[20] = $mysession->getmcid();
						$plentry[21] = $mysession->getucid();
						$plentry[33] = $newstat;
						$plentry[34] = $ologname;
						$plentry[36] = $eparts[1];
						if (isset($_xemagencyid))
							$plentry[22] = $_xemagencyid;
						$epayload = $mylog->buildelogpayload($plentry);
						$esourceid = $mylog->hextobin($plentry[20]);
						$mylog->queue_event(ECLA_LOG, ECOM_NONE, $edomain, $esourceid, $epayload);
					}
				}
			}

			// check permission for the remark
			$wauth = $mysession->getformewm($formname, "comment");
			if ($wauth)
			{
				$rementry["rem"] = trim($_POST["comment"]);
				if ($rementry["rem"] != "")
				{
					$myxld->xld_mod_add($dbh, $procdn, $rementry);
					$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
				}
			}
			$myxld->xld_close($dbh);
		}
	}
	else 
	{
		print "<script type=\"text/javascript\">alert('You do not have sufficient role privileges.')</script>\n";
		print "<script type=\"text/javascript\">history.go(-1)</script>\n";
		die();
	}
}
elseif (isset($_POST["btn_reissue"]))
{
	if ($mysession->testmprocmask(PMASK_REISSUE) === true)
	{
		$host = $mysession->gethost();
		$acdn = $mysession->getmcdn();
		$ucdn = $mysession->getucdn();

		$dbh = $myxld->xld_cb2authentx($host);
		if ($dbh !== false)
		{
			$ologname = $myldap->getfullname($acdn, $host, $dbh);
			if ($ologname === false)
				$ologname = $mysession->getmcid();

			$dt = date("YmdHis");
			$dt .= TIMEZONE;

			$cldn = "sysid=logs,".$uedn;
			$procentry = array();

			// re-issue: clears the status for print, encode, ship and activate objects
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);

			$procdn = "procid=print,ounit=dac,".$uedn;
			$procentry["status"] = array();
			$myxld->xld_mod_del($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[DAC PRINT] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=encode,ounit=dac,".$uedn;
			$procentry["status"] = array();
			$myxld->xld_mod_del($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[DAC ENCODE] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=ship,ounit=dac,".$uedn;
			$procentry["status"] = array();
			$myxld->xld_mod_del($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[DAC SHIP] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=activate,ounit=dac,".$uedn;
			$procentry["status"] = array();
			$myxld->xld_mod_del($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
			
			$ologentry = "[DAC ACTIVATE] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
			
			// queue event log entry
			if ($_xemenable)
			{
				$eparts = $myldap->dntoparts($uedn);
				$edomain = $mylog->hextobin("00000000000000000000000000000000");
				$plentry = array();
				$plentry[7] = EAID_SPONSORREISSUE;
				$plentry[19] = "Applicant token re-issue: ".$mysession->getucid();
				$plentry[20] = $mysession->getmcid();
				$plentry[21] = $mysession->getucid();
				$plentry[34] = $ologname;
				$plentry[36] = $eparts[1];
				if (isset($_xemagencyid))
					$plentry[22] = $_xemagencyid;
				$epayload = $mylog->buildelogpayload($plentry);
				$esourceid = $mylog->hextobin($plentry[20]);
				$mylog->queue_event(ECLA_LOG, ECOM_NONE, $edomain, $esourceid, $epayload);
			}
			
			// email to the applicant
			if ($useremail !== false)
			{
				$mailto = $useremail;
				$mailsubject = "DAC re-issuance";
				$mailbody = "Your DAC has now been enabled for re-issuance.\n";
				$mailbody .= "Please contact the registrar to arrange an appointment for enrollment.\n";
				$mailbody .= "\nThis message is automatically generated. Please do not reply.\n";
				$mailheaders = "From: Authentx System Server <postmaster@authentx.com>\r\n";
				mail($mailto, $mailsubject, $mailbody, $mailheaders);
			}
		}
	}
	else 
	{
		print "<script type=\"text/javascript\">alert('You do not have sufficient role privileges.')</script>\n";
		print "<script type=\"text/javascript\">history.go(-1)</script>\n";
		die();
	}
}

$uri = htmlentities($formfile);
$mysession->vectormeto($uri);

?>
