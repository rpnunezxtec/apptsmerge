<?PHP
// $Id:$

if (!isset($_SESSION['authentx']))
	session_start();
header("Cache-control: private");
include("vec-incsession.php");
require_once("vec-clldap.php");
require_once("vec-clprocess.php");
require_once("vec-cllog.php");
require_once("vec-clxld.php");
require_once("vec-clforms.php");

$myldap = new authentxldap();
$myprocess = new authentxprocess();
$mylog = new authentxlog();
$myxld = new authentxxld();
$myform = new authentxforms();
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

// Locate the hspd12 object associated with this category and workflow
$host = $mysession->gethost();
$ucdn = $mysession->getucdn();
$dbh = $myxld->xld_cb2authentx($host);

// Get the adminname for logging
$acdn = $mysession->getmcdn();
$ologname = $myldap->getfullname($acdn, $host, $dbh);
if ($ologname === false)
	$ologname = $mysession->getmcid();

$uedn = $mysession->getuedn();
$dt = date("YmdHis");
$dt .= TIMEZONE;
$hdt = date("Y-m-d H:i:s");

$category = "identity";
$wflow = "pivi";

// If object is locked then dropdowns will not be successful.
// Need the chktype for clearing a locked object.
if (isset($_POST["chktype"]))
	$chktype = trim($_POST["chktype"]);
else
{
	$ctvals = $mysession->getformvalue($formname, "chktype");
	if ($ctvals !== false)
		$chktype = $ctvals[0];
}

$procdn = false;
$objlock = true;

// Need to have processmask to change the type
if ($chktype != "")
{
	if ($mysession->testmprocmask(PMASK_PRPFBICHK) === true)
	{
		// Make sure that the objects exist and are assigned to this workflow.
		// If a new chktype is submitted then this will switch the process object assigned to the workflow.
		$myprocess->checkcreateh12processobject($category, $chktype, $ucdn, $wflow, $host, $dbh, true);

		// Get the user's h12 pdn's. Returns rv[category]=pdn
		$hpdnvals = $myprocess->findhspd12pdn($ucdn, $host, true, $dbh);
		if (isset($hpdnvals[$category]))
		{
			$objpdn = $hpdnvals[$category];
			$procdn = "procid=".$chktype.",".$objpdn;

			// Check for a historical object that matched the chktype (proctype) and is still valid
			// If found then copy the contents of this object and GCO's to the proces object and lock it.
			$histobj = $myprocess->findhistobj($objpdn, $chktype, "Approved", $host, $dbh);
			if ($histobj !== false)
			{
				$histdn = $histobj[0]["dn"];
				$cprocdn = $myprocess->hist2process($objpdn, $histdn, $host, $dbh, true);
			}
		}
		else
		{
			$objpdn = false;
			$procdn = false;
		}

		if ($procdn !== false)
		{
			// Check whether the object is locked
			if ($myprocess->isobjunlocked($procdn, $host, $dbh) === true)
				$objlock = false;
			else
				$objlock = true;
		}
		else
			$objlock = true;
	}
}

// Without a process object we can't do anything else. It's also an error.
if ($procdn === false)
{
	$myxld->xld_close($dbh);
	print "<script type=\"text/javascript\">alert('Error: No process object found.')</script>\n";
	$uri = htmlentities($formfile);
	$mysession->vectormeto($uri);
	die();
}

// Change the type only - does not require objectlock, however does require processmask
if (isset($_POST["btn_changetype"]))
{
	if ($mysession->testmprocmask(PMASK_PRPFBICHK) === true)
	{
		$hldn = "sysid=logs,".$objpdn;
		$cldn = "sysid=logs,".$uedn;

		// check permission for the Check type
		$wauth = $mysession->getformewm($formname, "chktype");
		if ($wauth)
		{
			$newval = trim($_POST["chktype"]);
			$oldvals = $mysession->getformvalue($formname, "chktype");
			if ($oldvals !== false)
				$oldval = $oldvals[0];
			else
				$oldval = "";
			if (strcasecmp($newval, $oldval) !== 0)
			{
				$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category)." Type change from ".$oldval." to ".$newval." by ".$ologname;
				$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
				$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
				if ($_xemenable)
				{
					$eparts = $myldap->dntoparts($uedn);
					$edomain = $mylog->hextobin("00000000000000000000000000000000");
					$plentry = array();
					$plentry[7] = EAID_FBI;
					$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category)." type changed from ".$oldval." to ".$newval;
					$plentry[20] = $mysession->getmcid();
					$plentry[21] = $mysession->getucid();
					$plentry[33] = $newval;
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
		$uri = htmlentities($formfile);
		$mysession->vectormeto($uri);
		die();
	}
}

if ($objlock === true)
{
	if (isset($_POST["btn_chkclear"]))
	{
		if (($mysession->testmprocmask(PMASK_PRPBGIEND) === true) || ($mysession->testmprocmask(PMASK_PRPBGISTART) === true))
		{
			$hldn = "sysid=logs,".$objpdn;
			$cldn = "sysid=logs,".$uedn;

			// process the clearing of the workflow object
			$hobj = $myprocess->clearprocessobject($procdn, false, $ologname, $host, $dbh);
			if ($hobj === false)
				print "<script type=\"text/javascript\">alert('Error renewing object.')</script>\n";
			else
			{
				$ologentry = "[".strtoupper($chktype)."] Initialized by ".$ologname." on ".$dt;
				$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
				$mylog->addobjlog($cldn, $ologentry, $host, $dbh);

				$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." Initialized by ".$ologname;
				$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
				$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);

				// queue event log entry
				if ($_xemenable)
				{
					$eparts = $myldap->dntoparts($uedn);
					$edomain = $mylog->hextobin("00000000000000000000000000000000");
					$plentry = array();
					$plentry[7] = EAID_FBIADJ;
					$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." Initialized by ".$ologname;
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
			}
		}
	}
}

if ($objlock === false)
{
	if (isset($_POST["btn_adjudicate"]))
	{
		if ($mysession->testmprocmask(PMASK_PRPFBICHK) === true)
		{
	    	$hldn = "sysid=logs,".$objpdn;
			$cldn = "sysid=logs,".$uedn;
			$createsnapshot = false;

			// check write permission for status
			$wauth = $mysession->getformewm($formname, "status");
			if ($wauth)
			{
				$procentry = array();

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

					// adjudby and adjuddate should be filled
					$procentry["adjudby"] = $ologname;
					$procentry["adjuddate"] = $dt;

					if (strcasecmp($newstat, "pending") == 0)
					{
						$procentry["startby"] = array();
						$procentry["startdate"] = array();
						$procentry["endby"] = array();
						$procentry["enddate"] = array();
					}
					if (strcasecmp($newstat, "submitted") == 0)
					{
						// manual initiation fills out the start attributes
						$procentry["startby"] = $ologname;
						$procentry["startdate"] = $dt;
						$procentry["endby"] = array();
						$procentry["enddate"] = array();
					}
					if ((strcasecmp($newstat, "approved") == 0) || (strcasecmp($newstat, "rejected") == 0))
					{
						// manual approval or rejection fills out the end attributes
						$procentry["endby"] = $ologname;
						$procentry["enddate"] = $dt;
					}

					// modify the attributes to the new values
					$myxld->xld_modify($dbh, $procdn, $procentry);
					$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

					// Add object logs
					$ologentry = "[".strtoupper($wflow).":".strtoupper($chktype)."] Adjudicated to ".$newstat." by ".$ologname." on ".$dt;
					$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
					// Add to common log object for user
					$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
					$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
					// Add to ID historical log object for user
					$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." Status change from ".$oldstat." to ".$newstat." by ".$ologname;
					$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
					$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);

					// queue event log entry
					if ($_xemenable)
					{
						$eparts = $myldap->dntoparts($uedn);
						$edomain = $mylog->hextobin("00000000000000000000000000000000");
						$plentry = array();
						$plentry[7] = EAID_FBIADJ;
						$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." check adjudicated. Changed to ".$newstat;
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

			// check permission for the expdate attribute
			$wauth = $mysession->getformewm($formname, "expdate");
			if ($wauth)
			{
				// Get date in ldap format
				$drv = $myform->fsp_getdatefrompost("expdate");
				
				$newval = $drv["value"];
				$oldvals = $mysession->getformvalue($formname, "expdate");
				if ($oldvals !== false)
					$oldval = $oldvals[0];
				else
					$oldval = "";
				if (strcasecmp($newval, $oldval) !== 0)
				{
					$procentry = array();
					$procentry["expdate"] = $newval;
					// modify the attributes to the new values
					$myxld->xld_modify($dbh, $procdn, $procentry);
					$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
					// Add to ID historical log object for user
					$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." expiration date change from ".$oldval." to ".$newval." by ".$ologname;
					$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
					$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
					if ($_xemenable)
					{
						$eparts = $myldap->dntoparts($uedn);
						$edomain = $mylog->hextobin("00000000000000000000000000000000");
						$plentry = array();
						$plentry[7] = EAID_FBI;
						$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." expiration date changed from ".$oldval." to ".$newval;
						$plentry[20] = $mysession->getmcid();
						$plentry[21] = $mysession->getucid();
						$plentry[33] = $newval;
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

			// check permission for the classification GCO item
			$wauth = $mysession->getformewm($formname, "class");
			if ($wauth)
			{
				$newval = trim($_POST["class"]);
				$oldvals = $mysession->getformvalue($formname, "class");
				if ($oldvals !== false)
					$oldval = $oldvals[0];
				else
					$oldval = "";
				if (strcasecmp($newval, $oldval) !== 0)
				{
					$gcodn = "gcoid=properties,".$procdn;
					$gcoentry = array();
					$gcoentry["000003"] = $newval;
					$myldap->updategco($gcodn, $gcoentry, $host, $dbh);
					$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
					// Add to FBI historical log object for user
					$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." Classification change from ".$oldval." to ".$newval." by ".$ologname;
					$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
					$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
					if ($_xemenable)
					{
						$ologname = $myldap->getfullname($acdn);
						if ($ologname === false)
							$ologname = $mysession->getmcid();
						$eparts = $myldap->dntoparts($uedn);
						$edomain = $mylog->hextobin("00000000000000000000000000000000");
						$plentry = array();
						$plentry[7] = EAID_FBI;
						$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." results changed from ".$oldval." to ".$newval;
						$plentry[20] = $mysession->getmcid();
						$plentry[21] = $mysession->getucid();
						$plentry[33] = $newval;
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

			// check permission for the chktype item
			$wauth = $mysession->getformewm($formname, "chktype");
			if ($wauth)
			{
				$newval = trim($_POST["chktype"]);
				$oldvals = $mysession->getformvalue($formname, "chktype");
				if ($oldvals !== false)
					$oldval = $oldvals[0];
				else
					$oldval = "";
				if (strcasecmp($newval, $oldval) !== 0)
				{
					$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category)." Type change from ".$oldval." to ".$newval." by ".$ologname;
					$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
					$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
					if ($_xemenable)
					{
						$eparts = $myldap->dntoparts($uedn);
						$edomain = $mylog->hextobin("00000000000000000000000000000000");
						$plentry = array();
						$plentry[7] = EAID_FBI;
						$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category)." type changed from ".$oldval." to ".$newval;
						$plentry[20] = $mysession->getmcid();
						$plentry[21] = $mysession->getucid();
						$plentry[33] = $newval;
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

			// And the refto, refdate GCO items
			$wauth = $mysession->getformewm($formname, "refto");
			if ($wauth)
			{
				$newval = trim($_POST["refto"]);
				$oldvals = $mysession->getformvalue($formname, "refto");
				if ($oldvals !== false)
					$oldval = $oldvals[0];
				else
					$oldval = "";
				if (strcasecmp($newval, $oldval) !== 0)
				{
					$gcodn = "gcoid=properties,".$procdn;
					$gcoentry = array();
					$gcoentry["000001"] = trim($_POST["refto"]);
					$gcoentry["000002"] = $dt;
					$myldap->updategco($gcodn, $gcoentry, $host, $dbh);
					$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
					// Add to ID historical log object for user
					$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." Referral change from ".$oldval." to ".$newval." by ".$ologname;
					$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
					$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
					if ($_xemenable)
					{
						$eparts = $myldap->dntoparts($uedn);
						$edomain = $mylog->hextobin("00000000000000000000000000000000");
						$plentry = array();
						$plentry[7] = EAID_FBI;
						$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ref changed from ".$oldval." to ".$newval;
						$plentry[20] = $mysession->getmcid();
						$plentry[21] = $mysession->getucid();
						$plentry[33] = $newval;
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

			// And the rejection reason GCO items
			$wauth = $mysession->getformewm($formname, "rejrsn");
			if ($wauth)
			{
				$newval = trim($_POST["rejrsn"]);
				$oldvals = $mysession->getformvalue($formname, "rejrsn");
				if ($oldvals !== false)
					$oldval = $oldvals[0];
				else
					$oldval = "";
				if (strcasecmp($newval, $oldval) !== 0)
				{
					$gcodn = "gcoid=properties,".$procdn;
					$gcoentry = array();
					$gcoentry["000004"] = trim($_POST["rejrsn"]);
					$myldap->updategco($gcodn, $gcoentry, $host, $dbh);
					$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
					// Add to ID historical log object for user
					$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." Rejection reason change from ".$oldval." to ".$newval." by ".$ologname;
					$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
					$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
					if ($_xemenable)
					{
						$eparts = $myldap->dntoparts($uedn);
						$edomain = $mylog->hextobin("00000000000000000000000000000000");
						$plentry = array();
						$plentry[7] = EAID_FBI;
						$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." rejection reason changed from ".$oldval." to ".$newval;
						$plentry[20] = $mysession->getmcid();
						$plentry[21] = $mysession->getucid();
						$plentry[33] = $newval;
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

			// And the FP return date GCO item
			$wauth = $mysession->getformewm($formname, "fprtndate");
			if ($wauth)
			{
				// dateparts
				$dd = $_POST["dd_fprtndate"];
				$mm = $_POST["mm_fprtndate"];
				$yy = $_POST["yy_fprtndate"];

				$drv = $myform->convert_datefields($yy, $mm, $dd);

				$newval = $drv["ldapdate"];
				$oldvals = $mysession->getformvalue($formname, "fprtndate");
				if ($oldvals !== false)
					$oldval = $oldvals[0];
				else
					$oldval = "";
				if (strcasecmp($newval, $oldval) !== 0)
				{
					$gcodn = "gcoid=properties,".$procdn;
					$gcoentry = array();
					$gcoentry["000005"] = $newval;
					$myldap->updategco($gcodn, $gcoentry, $host, $dbh);
					$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
					// Add to ID historical log object for user
					$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." FP return date change from ".$oldval." to ".$newval." by ".$ologname;
					$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
					$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
					if ($_xemenable)
					{
						$eparts = $myldap->dntoparts($uedn);
						$edomain = $mylog->hextobin("00000000000000000000000000000000");
						$plentry = array();
						$plentry[7] = EAID_FBI;
						$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." FP return date changed from ".$oldval." to ".$newval;
						$plentry[20] = $mysession->getmcid();
						$plentry[21] = $mysession->getucid();
						$plentry[33] = $newval;
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
					// Add to ID historical log object for user
					$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." Comment: ".$rementry["rem"]." added by ".$ologname;
					$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
					$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
				}
			}
		}
		else
		{
			$myxld->xld_close($dbh);
			print "<script type=\"text/javascript\">alert('You do not have sufficient role privileges.')</script>\n";
			print "<script type=\"text/javascript\">history.go(-1)</script>\n";
			die();
		}
	}

	$myxld->xld_close($dbh);
	$uri = htmlentities($formfile);
	$mysession->vectormeto($uri);
}
else
{
	$myxld->xld_close($dbh);
	$uri = htmlentities($formfile);
	$mysession->vectormeto($uri);
}

?>