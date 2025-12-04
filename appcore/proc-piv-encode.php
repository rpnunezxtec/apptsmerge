<?PHP
// $Id: proc-piv-encode.php 202 2009-03-16 02:52:57Z atlas $

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

// Functionality:
// Record a new remark if entered by an adjudicator.
// Record a status change if entered by an adjudicator.
// Any status change creates an objectlog entry and
// an event log entry

if (isset($_POST["btn_adjudicate"]))
{
	if ($mysession->testmprocmask(PMASK_PRPENCODE) === true)
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
			
			$procdn = "procid=encode,ounit=piv,".$uedn;
			$procentry = array();
			$myprocess->checkcreateprocessobject($uedn, "piv", "encode", $dbh, $host);

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
					}
					if (strcasecmp($newstat, "pending") == 0)
					{
						// manual pending removes the start and end attributes
						$procentry["endby"] = array();
						$procentry["enddate"] = array();
						$procentry["startby"] = $acdn;
						$procentry["startdate"] = $dt;
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
					$ologentry = "[PIV ENCODE] Modified to ".$newstat." by ".$ologname." on ".$dt;
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
						$plentry[7] = EAID_PIVENCODE;
						$plentry[19] = "Applicant PIV encoding modified. Changed to ".$newstat;
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

$uri = htmlentities($formfile);
$mysession->vectormeto($uri);

?>