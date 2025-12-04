<?PHP
// $Id: proc-dac-ship.php 206 2009-03-17 02:01:45Z atlas $

if (!isset($_SESSION['authentx']))
	session_start();
header("Cache-control: private");
include("vec-incsession.php");
include_once("vec-clldap.php");
include_once("vec-clprocess.php");
include_once("vec-cllog.php");
require_once("vec-clxld.php");
require_once("vec-clforms.php");
include_once("vec-clmail.php");

$myldap = new authentxldap();
$myprocess = new authentxprocess();
$mylog = new authentxlog();
$myxld = new authentxxld();
$myform = new authentxforms();
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
	if ($mysession->testmprocmask(PMASK_PRPSHIP) === true)
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

			$procdn = "procid=ship,ounit=dac,".$uedn;
			$procentry = array();
			$myprocess->checkcreateprocessobject($uedn, "dac", "ship", $dbh, $host);

			// check write permission for due date
			$wauth = $mysession->getformewm($formname, "duedate");
			if ($wauth)
			{
				if (isset($_POST["yy_duedate"]))
					$yy = $_POST["yy_duedate"];
				else
					$yy = "";
				if (isset($_POST["mm_duedate"]))
					$mm = $_POST["mm_duedate"];
				else
					$mm = "";
				if (isset($_POST["dd_duedate"]))
					$dd = $_POST["dd_duedate"];
				else
					$dd = "";
				$drv = $myform->convert_datefields($yy, $mm, $dd);
				$baddate = $drv["bad"];
				$deldate = $drv["empty"];
				$mydate = $drv["ldapdate"];

				if (!$baddate && !$deldate)
				{
					$mydate .= "000000";
					$mydate .= TIMEZONE;
				}

				// only update if the date is not bad
				if (!$baddate)
				{
					// get the old value
					$oldvalues = $mysession->getformvalue($formname, "duedate");
					$oldval = $oldvalues[0];
					// is the posted value different from the old value?
					if (strcmp($mydate, $oldval) != 0)
					{
						if (!$deldate)
							$dateentry["duedate"] = $mydate;
						else
							$dateentry["duedate"] = array();
						$myxld->xld_modify($dbh, $procdn, $dateentry);
						$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
					}
				}
			}

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
						// manual approval or rejection fills out the end attributes
						$procentry["endby"] = $acdn;
						$procentry["enddate"] = $dt;

						// if approved send an email
						if (strcasecmp($newstat, "approved") == 0)
						{
							if ($useremail !== false)
							{
								$mailto = $useremail;
								$mailsubject = "DAC Received";
								$mailbody = "Your DAC has now been received and is awaiting activation.\n";
								$mailbody .= "Please arrange an appointment with the activator to activate your DAC.\n";
								$shiptovals = $myldap->getldapattr($dbh, "gcoid=token,procid=token,ounit=dac,".$uedn, "xblk", false, "002055", "gco");
								if ($shiptovals !== false)
								{
									$shipto = $shiptovals[0];
									if ($shipto != "")
										$mailbody .= $shipto."\n";
								}
								$upinvals = $myldap->getldapattr($dbh, "gcoid=token,procid=token,ounit=dac,".$uedn, "upin", false, false, "gco");
								if ($upinvals !== false)
								{
									$upin = $upinvals[0];
									if ($upin != "")
										$mailbody .= "Your card activation PIN is ".$upin."\n";
								}
								$mailbody .= "\nThis message is automatically generated. Please do not reply.\n";
								$mailheaders = "From: Authentx System Server <postmaster@authentx.com>\r\n";
								mail($mailto, $mailsubject, $mailbody, $mailheaders);
							}
						}
					}
					if (strcasecmp($newstat, "pending") == 0)
					{
						// manual pending initiates the start attributes
						$procentry["startby"] = $acdn;
						$procentry["startdate"] = $dt;
						$procentry["endby"] = array();
						$procentry["enddate"] = array();

						// send an email
						if ($useremail !== false)
						{
							$mailto = $useremail;
							$mailsubject = "DAC Card Shipped";
							$mailbody = "This message serves to inform you that your DAC card ";
							$agencyvals = $myldap->getldapattr($dbh, "sysid=entitybranchname,".$uedn, "sysval", false, false, "xsystem");
							if ($agencyvals !== false)
							{
								$agency = $agencyvals[0];
								if ($agency != "")
									$mailbody .= "issued for agency ".$agency;
							}
							$mailbody .= " has been printed and shipped.\n";
							$mailbody .= "This DAC card will be delivered to the address selected by your sponsor.\n";
							$shiptovals = $myldap->getldapattr($dbh, "gcoid=token,procid=token,ounit=dac,".$uedn, "xblk", false, "002055", "gco");
							if ($shiptovals !== false)
							{
								$shipto = $shiptovals[0];
								if ($shipto != "")
									$mailbody .= $shipto."\n";
							}
							//$mailbody .= "The following is your tracking number: 123456789012\n";
							$duedatevals = $myldap->getldapattr($dbh, "procid=ship,ounit=dac,".$uedn, "duedate", false, false, "process");
							if ($duedatevals !== false)
							{
								$duedate = $duedatevals[0];
								if ($duedate != "")
								{
									$dd_y = substr($duedate, 0, 4);
									$dd_m = substr($duedate, 4, 2);
									$dd_d = substr($duedate, 6, 2);
									$dd = $dd_y."-".$dd_m."-".$dd_d;
									$ddstamp = strtotime($dd);
									$mailbody .= "The estimated date of delivery is ".date("D jS F, Y", $ddstamp).".\n";
								}
							}
							$mailbody .= "Pick up hours are 8AM-5PM, Monday-Friday, excluding Government holidays.\n";
							$mailbody .= "You will be notified when it has been received and is ready for activation.\n";
							$upinvals = $myldap->getldapattr($dbh, "gcoid=token,procid=token,ounit=dac,".$uedn, "upin", false, false, "gco");
							if ($upinvals !== false)
							{
								$upin = $upinvals[0];
								if ($upin != "")
									$mailbody .= "Your card activation PIN is ".$upin."\n";
							}
							$mailbody .= "\nThis message is automatically generated. Please do not reply.\n";
							$mailheaders = "From: Authentx System Server <postmaster@authentx.com>\r\n";
							mail($mailto, $mailsubject, $mailbody, $mailheaders);
						}
					}

					// modify the attributes to the new values
					$myxld->xld_modify($dbh, $procdn, $procentry);
					$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);

					// Add object logs
					$ologentry = "[DAC SHIP] Modified to ".$newstat." by ".$ologname." on ".$dt;
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
						$plentry[7] = EAID_DAC_SHIP;
						$plentry[19] = "Applicant DAC shipping modified. Changed to ".$newstat;
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