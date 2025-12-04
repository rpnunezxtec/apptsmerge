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

$procdn = false;
$wflow = false;
$newentry = false;

if (isset($_GET["dn"]))
{
	$procdn = $_GET["dn"];
	if ($procdn == "")
		$procdn = false;
}

if (isset($_GET["wflow"]))
{
	$wflow = $_GET["wflow"];
	if ($wflow == "")
		$wflow = false;
}
	
if ($procdn !== false)
{
	if (isset($_GET["avc"]))
	{
		$avc = $_GET["avc"];
		if ($avc == "")
			$avc = false;
	}
	
	if ($avc === false)
	{
		print "<script type=\"text/javascript\">alert('Error: AVC not found.')</script>\n";
		print "<script type=\"text/javascript\">history.go(-1)</script>\n";
		die();
	}
	else 
	{
		if ($wflow === false)
		{
			$urlchk = $procdn;
			$avctest = $mysession->createmac($urlchk);
			if (strcasecmp($avc, $avctest) != 0)
			{
				print "<script type=\"text/javascript\">alert('Error: AVC incorrect.')</script>\n";
				print "<script type=\"text/javascript\">self.close()</script>\n";
				die();
			}
			$urlargs = "?dn=".urlencode($procdn)."&avc=".urlencode($avctest);
		}
		else 
		{
			$urlchk = $procdn.$wflow;
			$avctest = $mysession->createmac($urlchk);
			if (strcasecmp($avc, $avctest) != 0)
			{
				print "<script type=\"text/javascript\">alert('Error: AVC incorrect.')</script>\n";
				print "<script type=\"text/javascript\">self.close()</script>\n";
				die();
			}
			$urlargs = "?dn=".urlencode($procdn)."&wflow=".urlencode($wflow)."&avc=".urlencode($avctest);
		}
	}
}
else 
{
	$urlargs = "";
	$newentry = true;
}
	
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
			
$category = "background";

if (isset($_POST["chktype"]))
	$chktype = trim($_POST["chktype"]);
else 
{
	$ctvals = $mysession->getformvalue($formname, "chktype");
	if ($ctvals !== false)
		$chktype = $ctvals[0];
}

$objlock = true;
	
// For the new entry submission, create the new object with the selected type.
if ($chktype != "")
{
	// Make sure that the objects exist and are assigned to this workflow.
	// If a new chktype is submitted then this will switch the process object assigned to the workflow.
	if ($wflow === false)
		$myprocess->checkcreateh12processobject($category, $chktype, $ucdn, false, $host, $dbh, true);
	else
		$myprocess->checkcreateh12processobject($category, $chktype, $ucdn, $wflow, $host, $dbh, true);
	
	// Get the user's h12 pdn's. Returns rv[category]=pdn
	$hpdnvals = $myprocess->findhspd12pdn($ucdn, $host, true, $dbh);
	if (isset($hpdnvals[$category]))
	{
		$objpdn = $hpdnvals[$category];
		$procdn = "procid=".$chktype.",".$objpdn;
		
		if ($wflow === false)
		{
			$urlchk = $procdn;
			$avctest = $mysession->createmac($urlchk);
			$urlargs = "?dn=".urlencode($procdn)."&avc=".urlencode($avctest);
		}
		else 
		{
			$urlchk = $procdn.$wflow;
			$avctest = $mysession->createmac($urlchk);
			$urlargs = "?dn=".urlencode($procdn)."&wflow=".urlencode($wflow)."&avc=".urlencode($avctest);
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

// Without a process object we can't do anything else. It's also an error.
if ($procdn === false)
{
	$myxld->xld_close($dbh);
	print "<script type=\"text/javascript\">alert('Error: No process object found or Investigation type not selected.')</script>\n";
	$uri = htmlentities($formfile).$urlargs;
	$mysession->vectormeto($uri);
	die();
}

// Process the form submission and log the changes
if (($objlock === false) && ($newentry === false))
{
	if (isset($_POST["btn_adjudicate"]) || isset($_POST["btn_chkclose"]))
	{
		$hldn = "sysid=logs,".$objpdn;
		$cldn = "sysid=logs,".$uedn;
		$createsnapshot = false;
		
		// Close the investigation object once everything is saved and snapshot it
		if (isset($_POST["btn_chkclose"]))
			$createsnapshot = true;
		
		// Check the posted items against the items in session from the form config
		foreach($_POST as $elementname => $elementval)
		{
			$elementval = html_entity_decode($elementval, ENT_QUOTES, "UTF-8");
			if ($mysession->formitemexists($formname, $elementname))
			{
				$wauth = $mysession->getformewm($formname, $elementname);
				if ($wauth)
				{
					$flags = $mysession->getformflags($formname, $elementname);
					if (!($flags & FFLAG_NOSAVE))
					{
						if ($flags & FFLAG_ADDATTR)
							$r = $myform->fsp_addattr($mysession, $myldap, $myxld, $dbh, $formname, $elementname, $elementval);
						else
							$r = $myform->fsp_singleattr($mysession, $myldap, $myxld, $dbh, $formname, $elementname, $elementval);
						
						if ($r === true)
						{
							if (!($flags & FFLAG_ADDATTR))
							{
								// logs etc for changed elements
								$ovvals = $mysession->getformvalue($formname, $elementname);
								if ($ovvals !== false)
									$ov = $ovvals[0];
								else 
									$ov = "";
								$en = $elementname;
								// Log only if change occurred
								if (strcasecmp($ov, $elementval) != 0)
								{
									if ($wflow === false)
										$ologentry = "[".strtoupper($chktype)."] ".$en." changed from ".($ov == "" ? "NULL" : $ov)." to ".$elementval." by ".$ologname." on ".$dt;
									else
										$ologentry = "[".strtoupper($wflow)." : ".strtoupper($chktype)."] ".$en." changed from ".($ov == "" ? "NULL" : $ov)." to ".$elementval." by ".$ologname." on ".$dt;
			
									$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
									// Add to common log object for user
									$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
									$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
		
									// Add to category historical log object for user
									if ($wflow === false)
										$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$elementval." by ".$ologname;
									else
										$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$elementval." by ".$ologname;
									$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
									$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
									
									// queue event log entry
									if ($_xemenable)
									{
										$eparts = $myldap->dntoparts($uedn);
										$edomain = $mylog->hextobin("00000000000000000000000000000000");
										$plentry = array();
										$plentry[7] = EAID_BGIADJ;
										if ($wflow === false)
											$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$elementval." by ".$ologname;
										else
											$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$elementval." by ".$ologname;
										$plentry[20] = $mysession->getmcid();
										$plentry[21] = $mysession->getucid();
										$plentry[33] = $elementval;
										$plentry[34] = $ologname;
										$plentry[36] = $eparts[1];
										if (isset($_xemagencyid))
											$plentry[22] = $_xemagencyid;
										$epayload = $mylog->buildelogpayload($plentry);
										$esourceid = $mylog->hextobin($plentry[20]);
										$mylog->queue_event(ECLA_LOG, ECOM_NONE, $edomain, $esourceid, $epayload);
									}
									$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
								}
							}
							else 
							{
								if ($elementval != "")
								{
									// Log the addition, not the change
									$en = $elementname;
									if ($wflow === false)
										$ologentry = "[".strtoupper($chktype)."] ".$en." added: ".$elementval." by ".$ologname." on ".$dt;
									else
										$ologentry = "[".strtoupper($wflow)." : ".strtoupper($chktype)."] ".$en." added: ".$elementval." by ".$ologname." on ".$dt;
			
									$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
									// Add to common log object for user
									$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
									$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
		
									// Add to category historical log object for user
									if ($wflow === false)
										$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." ".$en." added: ".$elementval." by ".$ologname;
									else
										$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ".$en." added: ".$elementval." by ".$ologname;
									$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
									$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
									
									// queue event log entry
									if ($_xemenable)
									{
										$eparts = $myldap->dntoparts($uedn);
										$edomain = $mylog->hextobin("00000000000000000000000000000000");
										$plentry = array();
										$plentry[7] = EAID_BGIADJ;
										if ($wflow === false)
											$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." ".$en." added: ".$elementval." by ".$ologname;
										else
											$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ".$en." added: ".$elementval." by ".$ologname;
										$plentry[20] = $mysession->getmcid();
										$plentry[21] = $mysession->getucid();
										$plentry[33] = $elementval;
										$plentry[34] = $ologname;
										$plentry[36] = $eparts[1];
										if (isset($_xemagencyid))
											$plentry[22] = $_xemagencyid;
										$epayload = $mylog->buildelogpayload($plentry);
										$esourceid = $mylog->hextobin($plentry[20]);
										$mylog->queue_event(ECLA_LOG, ECOM_NONE, $edomain, $esourceid, $epayload);
									}
									$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
								}
							}
						}
					}
				}
			}
		}
		
		// now process each of the session elements in case the data is hidden as a set of date fields.
		$elements = $mysession->getformelements($formname);
		foreach($elements as $elementname => $elementval)
		{
			$wauth = $mysession->getformewm($formname, $elementname);
			if ($wauth)
			{
				$flags = $mysession->getformflags($formname, $elementname);
				if (!($flags & FFLAG_NOSAVE))
				{
					// Combine date parts processing
					if ($flags & FFLAG_DATE)
					{
						$r = $myform->fsp_dateprocess($mysession, $myldap, $myxld, $dbh, $formname, $elementname);

						if ($r === true)
						{
							// logs etc
							$ovvals = $mysession->getformvalue($formname, $elementname);
							if ($ovvals !== false)
								$ov = $ovvals[0];
							else 
								$ov = "";
							$en = $elementname;
	
							$nva = $myform->fsp_getdatefrompost($elementname);
							$nv = $nva["value"];

							// Only log if changed
							if (strcasecmp($ov, $nv) != 0)
							{
								if ($wflow === false)
									$ologentry = "[".strtoupper($chktype)."] ".$en." changed from ".($ov == "" ? "NULL" : $ov)." to ".$nv." by ".$ologname." on ".$dt;
								else
									$ologentry = "[".strtoupper($wflow)." : ".strtoupper($chktype)."] ".$en." changed from ".($ov == "" ? "NULL" : $ov)." to ".$nv." by ".$ologname." on ".$dt;
		
								$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
								// Add to common log object for user
								$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
								$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
		
								// Add to category historical log object for user
								if ($wflow === false)
									$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$nv." by ".$ologname;
								else
									$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$nv." by ".$ologname;
								$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
								$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
								
								// queue event log entry
								if ($_xemenable)
								{
									$eparts = $myldap->dntoparts($uedn);
									$edomain = $mylog->hextobin("00000000000000000000000000000000");
									$plentry = array();
									$plentry[7] = EAID_BGIADJ;
									if ($wflow === false)
										$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$nv." by ".$ologname;
									else
										$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$nv." by ".$ologname;
									$plentry[20] = $mysession->getmcid();
									$plentry[21] = $mysession->getucid();
									$plentry[33] = $nv;
									$plentry[34] = $ologname;
									$plentry[36] = $eparts[1];
									if (isset($_xemagencyid))
										$plentry[22] = $_xemagencyid;
									$epayload = $mylog->buildelogpayload($plentry);
									$esourceid = $mylog->hextobin($plentry[20]);
									$mylog->queue_event(ECLA_LOG, ECOM_NONE, $edomain, $esourceid, $epayload);
								}
								
								$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
							}
						}
					}
				}
			}
		}	

		// Snapshot if applicable
		if ($createsnapshot === true)
		{
			$hobj = $myprocess->createh12snapshot($objpdn, $chktype, true, AUTHENTXPID, $host, $dbh);
			if ($hobj === false)
				print "<script type=\"text/javascript\">alert('Error archiving object.')</script>\n";
		}
	}
	
	$myxld->xld_close($dbh);
	$uri = htmlentities($formfile).$urlargs;
	$mysession->vectormeto($uri);
}
elseif (($objlock === true) && ($newentry === false))
{
	if (isset($_POST["btn_chkclear"]))
	{
		$hldn = "sysid=logs,".$objpdn;
		$cldn = "sysid=logs,".$uedn;
		
		// process the clearing of the workflow object
		$hobj = $myprocess->clearprocessobject($procdn, false, $ologname, $host, $dbh);
		if ($hobj === false)
			print "<script type=\"text/javascript\">alert('Error clearing object.')</script>\n";
		else 
		{
			$ologentry = "[".strtoupper($chktype)."] Initialized by ".$ologname." on ".$dt;
			$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
			
			$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." Initialized by ".$ologname;
			$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
			$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
			
			// queue event log entry
			if ($_xemenable)
			{
				$eparts = $myldap->dntoparts($uedn);
				$edomain = $mylog->hextobin("00000000000000000000000000000000");
				$plentry = array();
				$plentry[7] = EAID_BGIADJ;
				$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." Initialized by ".$ologname;
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
	$myxld->xld_close($dbh);
	$uri = htmlentities($formfile).$urlargs;
	$mysession->vectormeto($uri);
}
elseif ($newentry === true)
{
	if (isset($_POST["btn_new"]))
	{
		$hldn = "sysid=logs,".$objpdn;
		$cldn = "sysid=logs,".$uedn;
		
		// creating a new entry, use a slightly different mechanism to ensure the object is created and the bound data is saved
		$dnset["procdn"] = $procdn;
		// Re-build the data binding
		$rv = $myform->fsp_builddataspec($dnset, $formname);
		// Get the submitted data into the database
		// Check the posted items against the items in session from the form config
		foreach($_POST as $elementname => $elementval)
		{
			$elementval = html_entity_decode($elementval, ENT_QUOTES, "UTF-8");
			if ($mysession->formitemexists($formname, $elementname))
			{
				$wauth = $mysession->getformewm($formname, $elementname);
				if ($wauth)
				{
					$flags = $mysession->getformflags($formname, $elementname);
					if (!($flags & FFLAG_NOSAVE))
					{
						if ($flags & FFLAG_ADDATTR)
							$r = $myform->fsp_addattr($mysession, $myldap, $myxld, $dbh, $formname, $elementname, $elementval);
						else
							$r = $myform->fsp_singleattr($mysession, $myldap, $myxld, $dbh, $formname, $elementname, $elementval);
						
						if ($r === true)
						{
							// logs etc
							$en = $elementname;

							if ($elementval != "")
							{
								if ($wflow === false)
									$ologentry = "[".strtoupper($chktype)."] ".$en." set to ".$elementval." by ".$ologname." on ".$dt;
								else
									$ologentry = "[".strtoupper($wflow)." : ".strtoupper($chktype)."] ".$en." set to ".$elementval." by ".$ologname." on ".$dt;
		
								$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
								// Add to common log object for user
								$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
								$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
	
								// Add to category historical log object for user
								if ($wflow === false)
									$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$elementval." by ".$ologname;
								else
									$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$elementval." by ".$ologname;
								$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
								$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
								
								// queue event log entry
								if ($_xemenable)
								{
									$eparts = $myldap->dntoparts($uedn);
									$edomain = $mylog->hextobin("00000000000000000000000000000000");
									$plentry = array();
									$plentry[7] = EAID_BGIADJ;
									if ($wflow === false)
										$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$elementval." by ".$ologname;
									else
										$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$elementval." by ".$ologname;
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
								
								$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
							}
						}
					}
				}
			}
		}
		
		// now process each of the session elements in case the data is hidden as a set of date fields.
		$elements = $mysession->getformelements($formname);
		foreach($elements as $elementname => $elementval)
		{
			$wauth = $mysession->getformewm($formname, $elementname);
			if ($wauth)
			{
				$flags = $mysession->getformflags($formname, $elementname);
				if (!($flags & FFLAG_NOSAVE))
				{
					// Combine date parts processing
					if ($flags & FFLAG_DATE)
					{
						$r = $myform->fsp_dateprocess($mysession, $myldap, $myxld, $dbh, $formname, $elementname);

						if ($r === true)
						{
							// logs etc
							$en = $elementname;
	
							$nva = $myform->fsp_getdatefrompost($elementname);
							$nv = $nva["value"];

							if ($nv != "")
							{
								if ($wflow === false)
									$ologentry = "[".strtoupper($chktype)."] ".$en." set to ".$nv." by ".$ologname." on ".$dt;
								else
									$ologentry = "[".strtoupper($wflow)." : ".strtoupper($chktype)."] ".$en." set to ".$nv." by ".$ologname." on ".$dt;
		
								$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
								// Add to common log object for user
								$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
								$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
		
								// Add to category historical log object for user
								if ($wflow === false)
									$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$nv." by ".$ologname;
								else
									$hlogentry = $hdt.": ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$nv." by ".$ologname;
								$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
								$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
								
								// queue event log entry
								if ($_xemenable)
								{
									$eparts = $myldap->dntoparts($uedn);
									$edomain = $mylog->hextobin("00000000000000000000000000000000");
									$plentry = array();
									$plentry[7] = EAID_BGIADJ;
									if ($wflow === false)
										$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$nv." by ".$ologname;
									else
										$plentry[19] = "Applicant ".strtoupper($wflow).":".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$nv." by ".$ologname;
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
								
								$myprocess->updateprocmodby($procdn, $mysession->getmcid(), $dbh, $host);
							}
						}
					}
				}
			}
		}
	}
	
	$myxld->xld_close($dbh);
	$uri = htmlentities($formfile).$urlargs;
	$mysession->vectormeto($uri);
}
else 
{
	$myxld->xld_close($dbh);
	$uri = htmlentities($formfile).$urlargs;
	$mysession->vectormeto($uri);
}

?>