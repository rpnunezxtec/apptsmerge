<?PHP
// $Id: proc-pivi-sponsor.php 386 2009-05-27 02:46:37Z  $

if (!isset($_SESSION['authentx']))
	session_start();
header("Cache-control: private");
include("vec-incsession.php");
include_once("vec-clldap.php");
include_once("vec-clprocess.php");
include_once("vec-cllog.php");
require_once("vec-clxld.php");
include_once("vec-clmail.php");
include_once("fn-sponsorcheck.php");

date_default_timezone_set(DATE_TIMEZONE);

$myldap = new authentxldap();
$myprocess = new authentxprocess();
$mylog = new authentxlog();
$myxld = new authentxxld();
$mymail = new authentxmail();
date_default_timezone_set(DATE_TIMEZONE);

// get the name of the form being posted
$formname = $_POST["formname"];
$formfile = $mysession->filterfileurl($_POST["formfile"]);

// define the $uedn before including the mandatory list
$uedn = $mysession->getuedn();
include("../appconfig/config-pivi-sponsor.php");

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
				print "<script type=\"text/javascript\">alert('Mandatory elements missing :\\n";
				foreach ($x as $e)
					print $e."\\n";
				print "')</script>\n";
				print "<script type=\"text/javascript\">history.go(-1)</script>\n";
				die();
			}

			$procdn = "procid=sponsor,ounit=pivi,".$uedn;
			$procentry = array();
			$myprocess->checkcreateprocessobject($uedn, "pivi", "sponsor", $dbh, $host);

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
			$ologentry = "[PIV-I SPONSOR] Sponsored by ".$ologname." on ".$dt;
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
				$plentry[7] = EAID_PIVI_SPONSOR;
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
			// sponsorship causes the enrolment process objects to be placed in the 'pending' state
			$enrolentry = array();
			$enrolentry["status"] = "Pending";
			$myprocess->checkcreateprocessobject($uedn, "pivi", "fpcap", $dbh, $host);
			$myprocess->checkcreateprocessobject($uedn, "pivi", "doccap", $dbh, $host);
			$myprocess->checkcreateprocessobject($uedn, "pivi", "photocap", $dbh, $host);
			$myxld->xld_modify($dbh, "procid=fpcap,ounit=pivi,".$uedn, $enrolentry);
			$myprocess->updateprocmodby("procid=fpcap,ounit=pivi,".$uedn, $mysession->getmcid(), $dbh, $host);

			$myxld->xld_modify($dbh, "procid=doccap,ounit=pivi,".$uedn, $enrolentry);
			$myprocess->updateprocmodby("procid=doccap,ounit=pivi,".$uedn, $mysession->getmcid(), $dbh, $host);

			$myxld->xld_modify($dbh, "procid=photocap,ounit=pivi,".$uedn, $enrolentry);
			$myprocess->updateprocmodby("procid=photocap,ounit=pivi,".$uedn, $mysession->getmcid(), $dbh, $host);

			// email to the applicant
			if ($useremail !== false)
			{
				$mailtemplate = $mymail->getemailtemplate($_email_notification_templates, ET_SPONSOR_PIVI);
				
				// Get info to be substituted into message body
				$mailtags = array();
				$mailtags["workflow"] = "PIV-I";
				$dnset = array();
				
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

			$procdn = "procid=sponsor,ounit=pivi,".$uedn;
			$procentry = array();
			$myprocess->checkcreateprocessobject($uedn, "pivi", "sponsor", $dbh, $host);

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
					$ologentry = "[PIV-I SPONSOR] Modified to ".$newstat." by ".$ologname." on ".$dt;
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
						$plentry[7] = EAID_PIVI_SPONSOR;
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

			// re-issue: resets all enrollment process objects to 'pending'
			// and clears the status for print, encode, ship and activate objects
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);

			$procdn = "procid=fpcap,ounit=pivi,".$uedn;
			$procentry["status"] = "Pending";
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[PIV-I FPCAP] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=doccap,ounit=pivi,".$uedn;
			$procentry["status"] = "Pending";
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[PIV-I DOCCAP] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=photocap,ounit=pivi,".$uedn;
			$procentry["status"] = "Pending";
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[PIV-I PHOTOCAP] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=ichk,ounit=pivi,".$uedn;
			$procentry["status"] = "Pending";
			$myxld->xld_modify($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[PIV-I ICHK] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=print,ounit=pivi,".$uedn;
			$procentry["status"] = array();
			$myxld->xld_mod_del($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[PIV-I PRINT] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=encode,ounit=pivi,".$uedn;
			$procentry["status"] = array();
			$myxld->xld_mod_del($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[PIV-I ENCODE] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=ship,ounit=pivi,".$uedn;
			$procentry["status"] = array();
			$myxld->xld_mod_del($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[PIV-I SHIP] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=activate,ounit=pivi,".$uedn;
			$procentry["status"] = array();
			$myxld->xld_mod_del($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[PIV-I ACTIVATE] Re-issue change by ".$ologname." on ".$dt;
			$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

			$procdn = "procid=hspd12,ounit=pivi,".$uedn;
			$procentry["status"] = array();
			$procentry["pdn"] = array();
			$myxld->xld_mod_del($dbh, $procdn, $procentry);
			$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

			$ologentry = "[PIV-I HSPD12] Re-issue change by ".$ologname." on ".$dt;
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
				$mailtemplate = $mymail->getemailtemplate($_email_notification_templates, ET_REISSUE_PIVI);
				
				// Get info to be substituted into message body
				$mailtags = array();
				$mailtags = array();
				$mailtags["workflow"] = "PIV-I";
				$dnset = array();
				
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
