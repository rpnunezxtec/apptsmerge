<?PHP
// $Id:

if (!isset($_SESSION['authentx']))
	session_start();
header("Cache-control: private");
include("vec-incsession.php");
include_once("vec-clldap.php");
include_once("vec-clprocess.php");
include_once("vec-cllog.php");
require_once("vec-clxld.php");
include_once("fn-sponsorcheck.php");
include_once($listbase."/list-daccardtopology.php");
date_default_timezone_set(DATE_TIMEZONE);

$myldap = new authentxldap();
$myprocess = new authentxprocess();
$mylog = new authentxlog();
$myxld = new authentxxld();

// get the name of the form being posted
$formname = $_POST["formname"];
$formfile = $mysession->filterfileurl($_POST["formfile"]);

// define the $uedn before including the mandatory list
$uedn = $_POST["uedn"];
include("../appconfig/config-dac-sponsor.php");

$useremail = $_POST["email"];

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
	$host = $mysession->gethost();
	$acdn = $mysession->getmcdn();
	$aedn = $mysession->getmedn();

	$dbh = $myxld->xld_cb2authentx($host);
	if ($dbh !== false)
	{
		$ologname = $myldap->getfullname($acdn, $host, $dbh);
		if ($ologname === false)
			$ologname = $mysession->getmcid();

		$dt = date("YmdHis");
		$dt .= TIMEZONE;

		$cldn = "sysid=logs,".$uedn;

		$procdn = "procid=token,ounit=dac,".$uedn;
		$procentry = array();
		$myprocess->checkcreateprocessobject($uedn, "dac", "token", $dbh, $host);
		
		// Add card topology to DAC wflow before sponsoring
		if(isset($listdaccardtopology[0][0]))
		{
			$gcoentry = array();
			$gcoentry["0020c6"] = $listdaccardtopology[0][0];
			$myldap->updategco("gcoid=token,procid=token,ounit=dac,".$uedn, $gcoentry, $host, $dbh);
		}
		
		// Add device type to DAC wflow before sponsoring
		if (isset($_POST["cardtype"]) && (trim($_POST["cardtype"]) != ""))
		{
			$devicetype = $_POST["cardtype"];
			$gcoentry = array();
			$gcoentry["000054"] = $devicetype;
			$myldap->updategco("gcoid=token,procid=token,ounit=dac,".$uedn, $gcoentry, $host, $dbh);
		}

		// first test to see that everything required is non-null to allow sponsorship
		$x = checksponsor($dbh, $mandatory_list);
		if ($x !== true)
		{
			print "<script type=\"text/javascript\">alert('Mandatory elements missing:\\n";
			foreach ($x as $e)
				print $e."\\n";
			print "')</script>\n";
			$uri = htmlentities($formfile);
			$mysession->vectormeto($uri);
		}
		else
		{
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
			
			if($biocopyenable == true)
			{
				// Copy over biometric objects from logged in recorded
				// Entire object copied if not already there, otherwise skipped.
				$xlist = $myldap->getobjects($aedn, "biometric", "bioid", $host, $dbh);
				$nx = count($xlist);
				for ($i = 0; $i < $nx; $i++)
				{
					$xdn = $xlist[$i][0];
					$rid = $xlist[$i][1];
					$newdn = "bioid=".$rid.",".$uedn;
					// check whether the target object exists
					$xr = $myxld->xld_read($dbh, $newdn, "objectclass=biometric", array("bioid"));
					$rcode = $myxld->xld_errno($dbh);
					if ($rcode == 0x20)
						$ecr = $myldap->myldap_copy($dbh, $xdn, $newdn, true);
				}
				
				if ($_xemenable)
				{
					$edomain = $mylog->hextobin("00000000000000000000000000000000");
					$plentry = array();
					$plentry[7] = EAID_COPYENTITY;
					$plentry[19] = "Copied user biometrics: ".$uedn." to ".$region;
					$plentry[20] = $mysession->getmcid();
					$plentry[21] = $mysession->getucid();
					if (isset($_xemagencyid))
						$plentry[22] = $_xemagencyid;
					$epayload = $mylog->buildelogpayload($plentry);
					$esourceid = $mylog->hextobin($plentry[20]);
					$mylog->queue_event(ECLA_LOG, ECOM_NONE, $edomain, $esourceid, $epayload);
				}
			}
		}

		$myxld->xld_close($dbh);
	}
}

print "<script type=\"text/javascript\">alert('Successfully sponsored for DAC.')</script>\n";
$uri = htmlentities($formfile);
$mysession->vectormeto($uri);

?>
