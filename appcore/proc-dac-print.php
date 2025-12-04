<?PHP
// $Id:$

if (!isset($_SESSION['authentx']))
	session_start();
header("Cache-control: private");
include("vec-incsession.php");
include_once("vec-clldap.php");
include_once("vec-clprocess.php");
include_once("vec-cllog.php");
require_once("vec-clxld.php");

$myldap = new authentxldap();
$myprocess = new authentxprocess();
$mylog = new authentxlog();
$myxld = new authentxxld();
date_default_timezone_set(DATE_TIMEZONE);

// get the name of the form being posted
$formname = $_POST["formname"];
$formfile = $mysession->filterfileurl($_POST["formfile"]);

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
// Any status change creates an objectlog entry and an event log entry.

if (isset($_POST["btn_adjudicate"]))
{
	if ($mysession->testmprocmask(PMASK_PRPPRINT) === true)
	{
		$host = $mysession->gethost();
		$acdn = $mysession->getmcdn();
		$uedn = $mysession->getuedn();
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

			$procdn = "procid=print,ounit=dac,".$uedn;
			$procentry = array();
			$myprocess->checkcreateprocessobject($uedn, "dac", "print", $dbh, $host);
	
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
					if (strcasecmp($newstat, "rejected") == 0)
					{
						// manual approval or rejection fills out the start end end attributes
						$procentry["endby"] = $acdn;
						$procentry["enddate"] = $dt;
						$procentry["startby"] = $acdn;
						$procentry["startdate"] = $dt;
					}
					if (strcasecmp($newstat, "approved") == 0)
					{
						// manual approval or rejection fills out the end attributes
						$procentry["endby"] = $acdn;
						$procentry["enddate"] = $dt;
					
						// email to the applicant
						if ($useremail !== false)
						{
							$mailto = $useremail;
							$mailsubject = "DAC Approval";
							$mailbody = "Your DAC has now been printed and is soon to be shipped.\n";
							$mailbody .= "It will be shipped to the address designated by your sponsor.\n";
							$shiptovals = $myldap->getldapattr($dbh, "gcoid=token,procid=token,ounit=dac,".$uedn, "xblk", false, "002055", "gco");
							if ($shiptovals !== false)
							{
								$shipto = $shiptovals[0];
								if ($shipto != "")
									$mailbody .= $shipto."\n";
							}
							$mailbody .= "You will receive further notification when it has been shipped.\n";
							$mailbody .= "\nThis message is automatically generated. Please do not reply.\n";
							$mailheaders = "From: Authentx System Server <postmaster@authentx.com>\r\n";
							mail($mailto, $mailsubject, $mailbody, $mailheaders);
						}
					}
					if (strcasecmp($newstat, "pending") == 0)
					{
						// manual pending removes the start and end attributes
						$procentry["endby"] = array();
						$procentry["enddate"] = array();
						$procentry["startby"] = $acdn;
						$procentry["startdate"] = $dt;
						
						// email to the applicant
						if ($useremail !== false)
						{
							$mailto = $useremail;
							$mailsubject = "DAC Approval";
							$mailbody = "Your DAC has now been approved and is at the printing stage.\n";
							$mailbody .= "It will be shipped to the address designated by your sponsor.\n";
							$shiptovals = $myldap->getldapattr($dbh, "gcoid=token,procid=token,ounit=dac,".$uedn, "xblk", false, "002055", "gco");
							if ($shiptovals !== false)
							{
								$shipto = $shiptovals[0];
								if ($shipto != "")
									$mailbody .= $shipto."\n";
							}
							$mailbody .= "You will receive further notification when it is ready to be shipped.\n";
							$mailbody .= "\nThis message is automatically generated. Please do not reply.\n";
							$mailheaders = "From: Authentx System Server <postmaster@authentx.com>\r\n";
							mail($mailto, $mailsubject, $mailbody, $mailheaders);
						}
					}
					if (strcasecmp($newstat, "uninitiated") == 0)
					{
						$procentry["endby"] = array();
						$procentry["enddate"] = array();
						$procentry["startby"] = array();
						$procentry["startdate"] = array();
						$procentry["status"] = array();
					}
		
					// modify the attributes to the new values
					$myxld->xld_modify($dbh, $procdn, $procentry);
					$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
				
					// Add object logs
					$ologentry = "[DAC PRINT] Modified to ".$newstat." by ".$ologname." on ".$dt;
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
						$plentry[7] = EAID_DAC_PRINT;
						$plentry[19] = "Applicant DAC print modified. Changed to ".$newstat;
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
elseif (isset($_POST["btn_reprint"]))
{
	if ($mysession->testmprocmask(PMASK_REPRINT) === true)
	{
		$host = $mysession->gethost();
		$acdn = $mysession->getmcdn();
		$uedn = $mysession->getuedn();
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

			// reprint:
			// sets the print, ship and activate process objects to null
			$procentry = array();
			$procdn = "procid=print,ounit=dac,".$uedn;
			$procentry["status"] = array();
			$procentry["startdate"] = array();
			$procentry["startby"] = array();
			$procentry["enddate"] = array();
			$procentry["endby"] = array();
			$procentry["duedate"] = array();
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
				
			// Add object logs
			$ologentry = "[DAC PRINT] Re-print change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			// Add to common log object for user
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
				
			$procentry = array();
			$procdn = "procid=encode,ounit=dac,".$uedn;
			$procentry["status"] = array();
			$procentry["startdate"] = array();
			$procentry["startby"] = array();
			$procentry["enddate"] = array();
			$procentry["endby"] = array();
			$procentry["duedate"] = array();
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
			
			// Add object logs
			$ologentry = "[DAC ENCODE] Re-print change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			// Add to common log object for user
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
			
			$procentry = array();
			$procdn = "procid=ship,ounit=dac,".$uedn;
			$procentry["status"] = array();
			$procentry["startdate"] = array();
			$procentry["startby"] = array();
			$procentry["enddate"] = array();
			$procentry["endby"] = array();
			$procentry["duedate"] = array();
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
			
			// Add object logs
			$ologentry = "[DAC SHIP] Re-print change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			// Add to common log object for user
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
			
			$procentry = array();
			$procdn = "procid=activate,ounit=dac,".$uedn;
			$procentry["status"] = array();
			$procentry["startdate"] = array();
			$procentry["startby"] = array();
			$procentry["enddate"] = array();
			$procentry["endby"] = array();
			$procentry["duedate"] = array();
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
			
			// Add object logs
			$ologentry = "[DAC ACTIVATE] Re-print change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			// Add to common log object for user
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
			
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

$uri = htmlentities($formfile);
$mysession->vectormeto($uri);

?>
