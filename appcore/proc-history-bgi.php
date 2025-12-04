<?php
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
$objpdn = false;
$newentry = false;

if (isset($_GET["dn"]))
{
	$procdn = $_GET["dn"];
	if ($procdn == "")
		$procdn = false;
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
		// Test the integrity of the arguments
		$urlchk = $procdn;
		$avctest = $mysession->createmac($urlchk);
		if (strcasecmp($avc, $avctest) != 0)
		{
			print "<script type=\"text/javascript\">alert('Error: AVC incorrect.')</script>\n";
			print "<script type=\"text/javascript\">history.go(-1)</script>\n";
			die();
		}
		$urlargs = "?dn=".urlencode($procdn)."&avc=".urlencode($avctest);
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

if ($chktype != "")
{
	// Get the user's hspd12 parent
	$h12pdnset = $myprocess->findhspd12pdn($ucdn, $host, true, $dbh);
	if (isset($h12pdnset[$category]))
		$objpdn = $h12pdnset[$category];
}

// If a new object is requested, create it and assign the procdn
// Must have the appropriate procmask to do this
if ($newentry === true)
{
	if (isset($_POST["btn_new"]))
	{
		if ($objpdn !== false)
		{
			// create a new historical object object
			$procdn = $myprocess->createnewhistoryobject($objpdn, AUTHENTXPID, $host, $dbh);
			if ($procdn !== false)
			{
				// create the urlargs now, for the return vector
				$avc = $mysession->createmac($procdn);
				$urlargs = "?dn=".urlencode($procdn)."&avc=".urlencode($avc);
				
				$hldn = "sysid=logs,".$objpdn;
				$cldn = "sysid=logs,".$uedn;
				$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
				
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
										$ologentry = "[".strtoupper($chktype)."] ".$en." set to ".$elementval." by ".$ologname." on ".$dt;
				
										$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
										// Add to common log object for user
										$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
										$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
			
										// Add to category historical log object for user
										$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$elementval." by ".$ologname;
										$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
										$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
										
										// queue event log entry
										if ($_xemenable)
										{
											$eparts = $myldap->dntoparts($uedn);
											$edomain = $mylog->hextobin("00000000000000000000000000000000");
											$plentry = array();
											$plentry[7] = EAID_BGIADJ;
											$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$elementval." by ".$ologname;
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
										$ologentry = "[".strtoupper($chktype)."] ".$en." set to ".$nv." by ".$ologname." on ".$dt;
										$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
										// Add to common log object for user
										$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
										$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
				
										// Add to category historical log object for user
										$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$nv." by ".$ologname;
										$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
										$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
										
										// queue event log entry
										if ($_xemenable)
										{
											$eparts = $myldap->dntoparts($uedn);
											$edomain = $mylog->hextobin("00000000000000000000000000000000");
											$plentry = array();
											$plentry[7] = EAID_BGIADJ;
											$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." ".$en." set to ".$nv." by ".$ologname;
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
			else 
			{
				$myxld->xld_close($dbh);
				print "<script type=\"text/javascript\">alert('Error: Could not create new historical object.')</script>\n";
			}
		}
		else 
		{
			$myxld->xld_close($dbh);
			print "<script type=\"text/javascript\">alert('Error: Category not found or Investigation type not selected.')</script>\n";
		}
	}
}
elseif ($newentry === false)
{
	if ($objpdn !== false)
	{
		if (isset($_POST["btn_adjudicate"]) || isset($_POST["btn_chkreopen"]))
		{
			$hldn = "sysid=logs,".$objpdn;
			$cldn = "sysid=logs,".$uedn;
			$copyhistory = false;
			
			// Copy the historical object to the workflow area when re-opening the investigation
			if (isset($_POST["btn_chkreopen"]))
				$copyhistory = true;
			
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
										$ologentry = "[".strtoupper($chktype)."] ".$en." changed from ".($ov == "" ? "NULL" : $ov)." to ".$elementval." by ".$ologname." on ".$dt;
										$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
										// Add to common log object for user
										$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
										$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
			
										// Add to category historical log object for user
										$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$elementval." by ".$ologname;
										$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
										$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
										
										// queue event log entry
										if ($_xemenable)
										{
											$eparts = $myldap->dntoparts($uedn);
											$edomain = $mylog->hextobin("00000000000000000000000000000000");
											$plentry = array();
											$plentry[7] = EAID_BGIADJ;
											$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$elementval." by ".$ologname;
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
										$ologentry = "[".strtoupper($chktype)."] ".$en." added: ".$elementval." by ".$ologname." on ".$dt;
										$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
										// Add to common log object for user
										$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
										$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
			
										// Add to category historical log object for user
										$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." ".$en." added: ".$elementval." by ".$ologname;
										$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
										$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
										
										// queue event log entry
										if ($_xemenable)
										{
											$eparts = $myldap->dntoparts($uedn);
											$edomain = $mylog->hextobin("00000000000000000000000000000000");
											$plentry = array();
											$plentry[7] = EAID_BGIADJ;
											$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." ".$en." added: ".$elementval." by ".$ologname;
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
									$ologentry = "[".strtoupper($chktype)."] ".$en." changed from ".($ov == "" ? "NULL" : $ov)." to ".$nv." by ".$ologname." on ".$dt;
									$mylog->addobjlog($procdn, $ologentry, $host, $dbh);
									// Add to common log object for user
									$myldap->ldap_checkandcreate($cldn, "xsystem", array(), $host, $dbh);
									$mylog->addobjlog($cldn, $ologentry, $host, $dbh);
			
									// Add to category historical log object for user
									$hlogentry = $hdt.": ".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$nv." by ".$ologname;
									$myldap->ldap_checkandcreate($hldn, "xsystem", array(), $host, $dbh);
									$mylog->addobjlog($hldn, $hlogentry, $host, $dbh);
									
									// queue event log entry
									if ($_xemenable)
									{
										$eparts = $myldap->dntoparts($uedn);
										$edomain = $mylog->hextobin("00000000000000000000000000000000");
										$plentry = array();
										$plentry[7] = EAID_BGIADJ;
										$plentry[19] = "Applicant ".strtoupper($category).":".strtoupper($chktype)." ".$en." change from ".($ov == "" ? "NULL" : $ov)." to ".$nv." by ".$ologname;
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
	
			// Copy the historical object to workflow applicable
			if ($copyhistory === true)
			{
				$hobj = $myprocess->hist2process($objpdn, $procdn, $host, $dbh, $lock = false);
				if ($hobj === false)
					print "<script type=\"text/javascript\">alert('Error re-opening object.')</script>\n";
				else 
				{
					print "<script type=\"text/javascript\">alert('Investigation re-opened.')</script>\n";
					print "<script type=\"text/javascript\">self.close()</script>\n";
				}
			}
		}
	}
	else 
	{
		$myxld->xld_close($dbh);
		print "<script type=\"text/javascript\">alert('Error: Category not found or Investigation type not selected.')</script>\n";
	}
}

$myxld->xld_close($dbh);
$uri = htmlentities($formfile).$urlargs;
$mysession->vectormeto($uri);

?>