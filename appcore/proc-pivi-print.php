<?PHP
// $Id: proc-pivi-print.php 202 2009-03-16 02:52:57Z atlas $

if (!isset($_SESSION['authentx']))
	session_start();
header("Cache-control: private");
include("vec-incsession.php");
include_once("vec-clldap.php");
include_once("vec-clprocess.php");
include_once("vec-cllog.php");
require_once("vec-clxld.php");
include_once("vec-clmail.php");

$myldap = new authentxldap();
$myprocess = new authentxprocess();
$mylog = new authentxlog();
$myxld = new authentxxld();
$mymail = new authentxmail();
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

			$procdn = "procid=print,ounit=pivi,".$uedn;
			$procentry = array();
			$myprocess->checkcreateprocessobject($uedn, "pivi", "print", $dbh, $host);

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
							$mailtemplate = $mymail->getemailtemplate($_email_notification_templates, ET_PRINTED_PIVI);
				
							// Get info to be substituted into message body
							$mailtags = array();
							$mailtags["workflow"] = "PIV-I";
							$dnset = array();
							$dnset["uedn"] = $uedn;
							$dnset["uwfdn"] = "ounit=pivi,".$uedn;
				
							$mailtemplate = $mymail->populateemailtemplate($mailtemplate, $mailtags, $dnset, $dbh, $_notification_tag_dictionary);
							$mymail->from = $mailtemplate["from"];
							$mymail->addaddress($useremail);
							$mymail->subject = $mailtemplate["subject"];
							$mymail->body = $mailtemplate["body"];
							
							if (strcasecmp(MAILER, "mail") == 0)
								$mymail->ismail();
							elseif (strcasecmp(MAILER, "qmail") == 0)
								$mymail->isqmail();
							elseif (strcasecmp(MAILER, "smtp") == 0)
							{
								$mymail->issmtp();
								$mymail->smtphost = SMTP_SERVER;
								$mymail->smtpport = SMTP_PORT;
								$mymail->smtpauth = SMTP_AUTH;
								$mymail->smtpuser = SMTP_AUTHUSER;
								$mymail->smtppassword = SMTP_AUTHPASSWD;
							}
								
							$mymail->send();
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
							$mailtemplate = $mymail->getemailtemplate($_email_notification_templates, ET_PRINTING_PIVI);
				
							// Get info to be substituted into message body
							$mailtags = array();
							$mailtags["workflow"] = "PIV-I";
							$dnset = array();
							$dnset["uedn"] = $uedn;
							$dnset["uwfdn"] = "ounit=pivi,".$uedn;
				
							$mailtemplate = $mymail->populateemailtemplate($mailtemplate, $mailtags, $dnset, $dbh, $_notification_tag_dictionary);
							$mymail->from = $mailtemplate["from"];
							$mymail->addaddress($useremail);
							$mymail->subject = $mailtemplate["subject"];
							$mymail->body = $mailtemplate["body"];
							
							if (strcasecmp(MAILER, "mail") == 0)
								$mymail->ismail();
							elseif (strcasecmp(MAILER, "qmail") == 0)
								$mymail->isqmail();
							elseif (strcasecmp(MAILER, "smtp") == 0)
							{
								$mymail->issmtp();
								$mymail->smtphost = SMTP_SERVER;
								$mymail->smtpport = SMTP_PORT;
								$mymail->smtpauth = SMTP_AUTH;
								$mymail->smtpuser = SMTP_AUTHUSER;
								$mymail->smtppassword = SMTP_AUTHPASSWD;
							}
								
							$mymail->send();
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
					$ologentry = "[PIV-I PRINT] Modified to ".$newstat." by ".$ologname." on ".$dt;
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
						$plentry[7] = EAID_PIVI_PRINT;
						$plentry[19] = "Applicant PIV-I print modified. Changed to ".$newstat;
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

			$procentry = array();
			// reprint:
			// sets the pivgen, pivdeliv and pivactivated process objects to null
			$procdn = "procid=print,ounit=pivi,".$uedn;
			$procentry["status"] = array();
			$procentry["startdate"] = array();
			$procentry["startby"] = array();
			$procentry["enddate"] = array();
			$procentry["endby"] = array();
			$procentry["duedate"] = array();
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			// Add object logs
			$ologentry = "[PIV-I PRINT] Re-print change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			// Add to common log object for user
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procentry = array();
			$procdn = "procid=encode,ounit=pivi,".$uedn;
			$procentry["status"] = array();
			$procentry["startdate"] = array();
			$procentry["startby"] = array();
			$procentry["enddate"] = array();
			$procentry["endby"] = array();
			$procentry["duedate"] = array();
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			// Add object logs
			$ologentry = "[PIV-I ENCODE] Re-print change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			// Add to common log object for user
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procentry = array();
			$procdn = "procid=ship,ounit=pivi,".$uedn;
			$procentry["status"] = array();
			$procentry["startdate"] = array();
			$procentry["startby"] = array();
			$procentry["enddate"] = array();
			$procentry["endby"] = array();
			$procentry["duedate"] = array();
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			// Add object logs
			$ologentry = "[PIV-I SHIP] Re-print change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			// Add to common log object for user
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procentry = array();
			$procdn = "procid=activate,ounit=pivi,".$uedn;
			$procentry["status"] = array();
			$procentry["startdate"] = array();
			$procentry["startby"] = array();
			$procentry["enddate"] = array();
			$procentry["endby"] = array();
			$procentry["duedate"] = array();
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			// Add object logs
			$ologentry = "[PIV-I ACTIVATE] Re-print change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			// Add to common log object for user
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
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