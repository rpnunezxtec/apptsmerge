<?php

// $Id:$
// Service that periodically requests the site and emws details from the authentx system

require_once("../../config.php");
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setsiteid($_siteid);
$myappt->setagencyid($_xemagencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

$ts = time();

if ($_axsitesync_enable === true)
{
	$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
	if ($sdbh->connect_error)
	{
		print "ERROR: Could not connect to appointments database.\n";
	}
	else
	{
		// Request the update set from the configured db server URL
		// [n][centerid], [n][dn]
		print "Requesting site list from ".AX_SITESYNCURL."\n";
		$sitelist = $myappt->apptsync_consumer_getsitelist(AX_SITESYNCURL);
		if (isset($sitelist["error"]))
			print "ERROR: ".$sitelist["error"]."\n";
		else
		{
			$ns = count($sitelist);
			print $ns." sites returned in list.\n";
		
			for ($s = 0; $s < $ns; $s++)
			{
				if (isset($sitelist[$s]["centerid"]))
				{
					$centerid = $sitelist[$s]["centerid"];
					$sitedn = $sitelist[$s]["dn"];
				
					// Request each center and emws detail
					// [site][param]=value, [em][n][param]=value
					print ($s+1)."/".$ns.": Requesting site: ".$centerid." details.\n";
					$siteset = $myappt->apptsync_consumer_getsite(AX_SITESYNCURL, $sitedn);
					if (isset($siteset["error"]))
						print "   ERROR: ".$siteset["error"]."\n";
					else
					{
						// Make ready for db commit
						// 1. Check if site already exists. Returns centeruuid of match.
						$rs = $myappt->findsitebycenterid($sdbh, $centerid);
						
						// Create new empty site if not
						if ($rs === false)
						{
							// Create a uuid for the site record
							$centeruuid = $myappt->makeuniqueuuid($sdbh, "site", "centeruuid");

							$qs = "insert into site set "
								. "\n centerid='".$sdbh->real_escape_string($centerid)."', "
								. "\n centeruuid='".$centeruuid."', "
								. "\n xsyncmts='".time()."', "
								. "\n status=0 "
								;
								
							$ss = $sdbh->query($qs);
							if ($ss)
								print "   New site entry created: centerid: ".$centerid.", centeruuid: ".$centeruuid."\n";
							else
							{
								$centeruuid = false;
								print "   ERROR: Could not create new empty site ".$centerid.": ".$sdbh->error.".\n";
							}
						}
						else
							$centeruuid = $rs;
						
						if ($centeruuid !== false)
						{
							// 2. Check whether necessary settings are defined if site already exists (slottime > 0)
							$siteready = false;
							$qs = "select slottime "
								. "\n from site "
								. "\n where centeruuid='".$centeruuid."' "
								;
							$ss = $sdbh->query($qs);
							if ($ss)
							{
								$rs = $ss->fetch_assoc();
								if ($rs)
								{
									if ($rs["slottime"] > 0)
										$siteready = true;
								}
								$ss->free();
							}
							
							// Status will be set to unavailable until all settings are in place
							$sitestatus = 0;	
							if ($siteready !== false)
							{
								// Map the status string to the int value
								if (isset($siteset["site"]["status"]))
								{
									$stat = strtolower($siteset["site"]["status"]);
									foreach ($_apptsitestatus as $sv => $sset)
									{
										if (in_array($stat, $sset))
											$sitestatus = $sv;
									}
								}
								else
									$sitestatus = 0;
							}
							
							// 3. Create update query for site in appt db
							$qs = "update site set ";
							
							if (isset($siteset["site"]["sitename"]) && ($siteset["site"]["sitename"] != ''))
								$qs .= "\n sitename='".$sdbh->real_escape_string($siteset["site"]["sitename"])."', ";
							else
								$qs .= "\n sitename='".$sdbh->real_escape_string($centerid)."', ";
							
							if (isset($siteset["site"]["siteaddress"]))
								$qs .= "\n siteaddress='".$sdbh->real_escape_string($siteset["site"]["siteaddress"])."', ";
							else
								$qs .= "\n siteaddress=NULL, ";
							
							if (isset($siteset["site"]["siteaddrcity"]))
								$qs .= "\n siteaddrcity='".$sdbh->real_escape_string($siteset["site"]["siteaddrcity"])."', ";
							else
								$qs .= "\n siteaddrcity=NULL, ";
							
							if (isset($siteset["site"]["siteaddrstate"]))
								$qs .= "\n siteaddrstate='".$sdbh->real_escape_string($siteset["site"]["siteaddrstate"])."', ";
							else
								$qs .= "\n siteaddrstate=NULL, ";
							
							if (isset($siteset["site"]["siteaddrzip"]))
								$qs .= "\n siteaddrzip='".$sdbh->real_escape_string($siteset["site"]["siteaddrzip"])."', ";
							else
								$qs .= "\n siteaddrzip=NULL, ";
							
							if (isset($siteset["site"]["siteaddrcountry"]))
								$qs .= "\n siteaddrcountry='".$sdbh->real_escape_string($siteset["site"]["siteaddrcountry"])."', ";
							else
								$qs .= "\n siteaddrcountry=NULL, ";
							
							if (isset($siteset["site"]["siteregion"]))
								$qs .= "\n siteregion='".$sdbh->real_escape_string($siteset["site"]["siteregion"])."', ";
							else
								$qs .= "\n siteregion=NULL, ";
							
							if (isset($siteset["site"]["siteactivity"]))
								$qs .= "\n siteactivity='".$sdbh->real_escape_string($siteset["site"]["siteactivity"])."', ";
							else
								$qs .= "\n siteactivity=NULL, ";
							
							if (isset($siteset["site"]["sitetype"]))
								$qs .= "\n sitetype='".$sdbh->real_escape_string($siteset["site"]["sitetype"])."', ";
							else
								$qs .= "\n sitetype=NULL, ";
							
							if (isset($siteset["site"]["timezone"]))
								$qs .= "\n timezone='".$sdbh->real_escape_string($siteset["site"]["timezone"])."', ";
							else
								$qs .= "\n timezone=NULL, ";
							
							if (isset($siteset["site"]["sitecontactname"]))
								$qs .= "\n sitecontactname='".$sdbh->real_escape_string($siteset["site"]["sitecontactname"])."', ";
							else
								$qs .= "\n sitecontactname=NULL, ";
							
							if (isset($siteset["site"]["sitecontactphone"]))
								$qs .= "\n sitecontactphone='".$sdbh->real_escape_string($siteset["site"]["sitecontactphone"])."', ";
							else
								$qs .= "\n sitecontactphone=NULL, ";
							
							if (isset($siteset["site"]["sitecontactemail"]))
								$qs .= "\n sitenotifyemail='".$sdbh->real_escape_string($siteset["site"]["sitecontactemail"])."', ";
							else
								$qs .= "\n sitenotifyemail=NULL, ";
							
							if (isset($_xemagencyid))
								$qs .= "\n appid='".$_xemagencyid."', ";
							
							$qs .= "\n status='".$sitestatus."', ";
							$qs .= "\n xsyncmts='".time()."' ";
							$qs .= "\n where centeruuid='".$centeruuid."' limit 1";
							
							$ss = $sdbh->query($qs);
							if (!$ss)
								print "   ERROR: Could not update site (centeruuid: ".$centeruuid."), centerid: ".$centerid.": ".$sdbh->error.".\n";
							else
								print "   Site updated.\n";
							
							
							// 4. Check if EM are included with site
							if (isset($siteset["em"]))
							{
								$nem = count($siteset["em"]);
								for ($em = 0; $em < $nem; $em++)
								{
									if (isset($siteset["em"][$em]["wsname"]))
									{
										// wsname and deviceid are mandatory
										$wsname = $siteset["em"][$em]["wsname"];
											
										// Does the workstation exist under this site
										$rs = $myappt->findsiteemwsbyname($sdbh, $centeruuid, $wsname);
											
										// Create new empty site if not
										if ($rs === false)
										{
											$wsuuid = $myappt->makeuniqueuuid($sdbh, "workstation", "wsuuid");

											$qs = "insert into workstation "
												. "\n set "
												. "\n centeruuid='".$centeruuid."', "
												. "\n wsuuid='".$wsuuid."', "
												. "\n xsyncmts='".time()."', "
												. "\n wsname='".$sdbh->real_escape_string($wsname)."' "
												;
										
											$ss = $sdbh->query($qs);
											if ($ss)
												print "   New workstation wsname: ".$wsname.", wsuuid: ".$wsuuid." created.\n";
											else
											{
												$wsuuid = false;
												print "   ERROR: Could not create new empty workstation ".$wsname.": ".$sdbh->error.".\n";
											}
										}
										else
											$wsuuid = $rs;
											
										if ($wsuuid !== false)
										{
											$qs = "update workstation set ";
											
											// Map the status values
											// 'status' = em status set on emconfig form
											$emstatus = 0;
											if (isset($siteset["em"][$em]["status"]))
											{
												$stat = strtolower($siteset["em"][$em]["status"]);
												foreach ($_apptemwsstatus as $sv => $sset)
												{
													if (in_array($stat, $sset))
														$emstatus = $sv;
												}
											}
											
											if (isset($siteset["em"][$em]["deviceid"]))
											{
												$deviceid = $siteset["em"][$em]["deviceid"];
												$qs .= "\n deviceid='".$sdbh->real_escape_string($deviceid)."', ";
											}
											
											$qs .= "\n status='".$emstatus."', ";
											$qs .= "\n xsyncmts='".time()."', ";
											$qs .= "\n appid='".$_xemagencyid."' ";
											$qs .= "\n where wsuuid='".$wsuuid."' limit 1";
												
											$ss = $sdbh->query($qs);
											if (!$ss)
												print "   ERROR: Could not update workstation (wsuuid: ".$wsuuid.") ".$wsname.": ".$sdbh->error.".\n";
											else
												print "   Workstation ".$wsname.", wsuuid: ".$wsuuid." updated.\n";
										}
									}
									else 
										print "   ERROR: Workstation name not found.\n";
								}
							}
						}
					}
				}
				else
					print "   ".$s."/".$ns.": centerID not found, skipping.\n";
			}
		}
		$sdbh->close();
	}	
}

$tf = time();
$te = $tf - $ts;
$tr = $_sleeptime_apptsitesync - $te;
if ($tr > 0)
	sleep($tr);

?>