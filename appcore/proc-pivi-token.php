<?PHP
// $Id: proc-pivi-token.php 206 2009-03-17 02:01:45Z atlas $

if (!isset($_SESSION['authentx']))
	session_start();
header("Cache-control: private");
include("vec-incsession.php");
include_once("vec-clldap.php");
include_once("vec-clprocess.php");
include_once("vec-cllog.php");
require_once("vec-clxld.php");
require_once("vec-clforms.php");

$myldap = new authentxldap();
$myprocess = new authentxprocess();
$mylog = new authentxlog();
$myxld = new authentxxld();
$myform = new authentxforms();
date_default_timezone_set(DATE_TIMEZONE);

$wflow = "pivi";

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
// Update objects and record log entries.
if (isset($_POST["btn_adjudicate"]))
{
	if ($mysession->testmprocmask(PMASK_PRPTOKEN) === true)
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

			$procdn = "procid=token,ounit=pivi,".$uedn;
			$procentry = array();
			$myprocess->checkcreateprocessobject($uedn, "pivi", "token", $dbh, $host);

			$rootdn = array();
			$rootdn["credential"] = $mysession->getucdn();
			$rootdn["entity"] = $uedn;

			foreach($_POST as $formtag => $formval)
			{
				$formval = html_entity_decode($formval, ENT_QUOTES, "UTF-8");
				// process only if this is a data element
				if ($mysession->formitemexists($formname, $formtag))
				{
					// are we allowed to write to this element?
					$wauth = $mysession->getformewm($formname, $formtag);
					if ($wauth)
					{
						// get the flags for this data element
						$flags = $mysession->getformflags($formname, $formtag);
						// only process if the nosave flag is not set
						if (!($flags & FFLAG_NOSAVE))
						{
							// erase the previous array of attribute values
							unset($srcattr);

							// get the source for the element - decoded array from form config
							$itemsrc = $mysession->getformsource($formname, $formtag);

							// add comments cumulatively
							if (strcasecmp($formtag, "comment") == 0)
							{
								$rementry = array();
								$rementry["rem"] = trim($_POST["comment"]);
								if ($rementry["rem"] != "")
								{
									$myxld->xld_mod_add($dbh, $procdn, $rementry);
								}
							}
							else
							{
								$oldvalues = $mysession->getformvalue($formname, $formtag);
								if($oldvalues !== false)
									$oldval = $oldvalues[0];
								else
									$oldval = "";
								if (strcmp($formval, $oldval) != 0)
								{
									$oldattr = array();
									$numspec = count($itemsrc);
									$srcroot = $itemsrc[0];
									if ($srcroot == "credential" || $srcroot == "entity")
										$objectdn = $rootdn[$srcroot];
									else
										$objectdn = $srcroot;

									// the items can only come from the process object or the objects underneath it
									if ($numspec == 4)
									{
										$subtreedn = $itemsrc[1];
										$objectdn = $subtreedn.",".$objectdn;
										$attrname = $itemsrc[2];
										$srcattr[$attrname] = $formval;
										$oldattr[$attrname] = $oldval;
										$srcobject = $itemsrc[3];
									}
									elseif ($numspec == 5)
									{
										$subtreedn = $itemsrc[1];
										$objectdn = $subtreedn.",".$objectdn;
										$attrname = $itemsrc[2];
										$srctag = strtolower($itemsrc[3]);
										$srcobject = $itemsrc[4];
									}

									// submitted empty => delete the attribute
									if (($formval == "") && ($numspec == 4))
									{
										$rslt = $myxld->xld_mod_del($dbh, $objectdn, $oldattr);
										$rlecode = $myxld->xld_errno($dbh);
										// if we get 0x12 (inappropriate matching) then the attribute must have
										// been binary. We will have to delete them all (there should only be one anyway).
										if ($rlecode == 0x12)
										{
											$srcattr[$attrname] = array();
											$rslt = $myxld->xld_modify($dbh, $objectdn, $srcattr);
										}
									}
									// otherwise create/modify the object/attribute
									else
									{
										if ($numspec == 4)
										{
											$rslt = $myxld->xld_modify($dbh, $objectdn, $srcattr);
											$rlecode = $myxld->xld_errno($dbh);
											if ($rlecode == 0x20)
											{
												$myldap->ldap_checkandcreate($objectdn, $srcobject, $srcattr, $host, $dbh);
												$rslt = $myxld->xld_modify($dbh, $objectdn, $srcattr);
												$rlecode = $myxld->xld_errno($dbh);
											}
										}
										elseif ($numspec == 5)
										{
											$s_gco = $myxld->xld_read($dbh, $objectdn, "objectclass=".$srcobject, array($attrname));
											$rlecode = $myxld->xld_errno($dbh);
											if ($rlecode == 0x20)
											{
												$myldap->ldap_checkandcreate($objectdn, $srcobject, array(), $host, $dbh);
												$s_gco = $myxld->xld_read($dbh, $objectdn, "objectclass=".$srcobject, array($attrname));
												$rlecode = $myxld->xld_errno($dbh);
											}
											unset($gcotagarray);
											if ($s_gco !== false)
											{
												$gcoentry = $myxld->xld_first_entry($dbh, $s_gco);
												if ($gcoentry !== false)
												{
													$xblkarray = $myxld->xld_get_values_len($dbh, $gcoentry, $attrname);
													if ($xblkarray !== false)
													{
														$numxblk = $xblkarray['count'];
														for ($i = 0; $i < $numxblk; $i++)
														{
															$gcoxblk = $xblkarray[$i];
															$r = $myldap->gco_split($gcoxblk);
															$gcotagarray[$r['tag']] = $r['payload'];
														}
													}
												}
												$gcotagarray[$srctag] = $formval;
												$i = 0;
												unset($gco_update);
												foreach ($gcotagarray as $tag => $payload)
													$gco_update['xblk'][$i++] = $myldap->gco_combine(0, $tag, $payload);

												$r_gco = $myxld->xld_modify($dbh, $objectdn, $gco_update);
												$rlecode = $myxld->xld_errno($dbh);
											}

											if ($rlecode == 0)
											{
												if ($_xemenable)
												{
													$eparts = $myldap->dntoparts($uedn);
													$edomain = $mylog->hextobin("00000000000000000000000000000000");
													$plentry = array();
													$plentry[7] = EAID_PIVI_TOKEN;
													$plentry[19] = "Applicant PIV-I token modified. Changed ".$attrname.($attrname == "xblk" ? " tag ".$srctag : "")." from ".$oldval." to ".$formval;
													$plentry[20] = $mysession->getmcid();
													$plentry[21] = $mysession->getucid();
													$plentry[33] = $formval;
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
							}
						}
					}
				}
			}

			$elements = $mysession->getformelements($formname);
			foreach($elements as $formtag => $formval)
			{
				$wauth = $mysession->getformewm($formname, $formtag);
				if ($wauth)
				{
					$flags = $mysession->getformflags($formname, $formtag);
					if (!($flags & FFLAG_NOSAVE))
					{
						if ($flags & FFLAG_DATE)
						{
							unset($srcattr);
							$partidx = "yy_".$formtag;
							if (isset($_POST[$partidx]))
								$yy = $_POST[$partidx];
							else
								$yy = "";
							$partidx = "mm_".$formtag;
							if (isset($_POST[$partidx]))
								$mm = $_POST[$partidx];
							else
								$mm = "";
							$partidx = "dd_".$formtag;
							if (isset($_POST[$partidx]))
								$dd = $_POST[$partidx];
							else
								$dd = "";
							$drv = $myform->convert_datefields($yy, $mm, $dd);
							$baddate = $drv["bad"];
							$deldate = $drv["empty"];
							$mydate = $drv["ldapdate"];

							if($baddate)
							{
								print "<script type=\"text/javascript\">alert('Not a valid date.')</script>\n";
							}
							
							if(($formtag == "crdexpdate") && !$baddate && !$deldate)
							{
								// Get today's date
								$month = date('m');
								$day = date('d');
								$year = date('Y');
								
								// Date cannot be in the past
								if((($yy == $year) && ($mm == $month) && ($dd < $day)) || ($yy < $year) || (($yy == $year) && ($mm < $month)))
								{
									$baddate = true;
									print "<script type=\"text/javascript\">alert('Card expiration date cannot be in the past.')</script>\n";
								}
								
								// Check if crdexpdate is greater than allowed date
								if(isset($card_exp[$wflow]))
								{
									$maxdays = $card_exp[$wflow];
									
									$maxdate = strtotime("+".$maxdays." days");
									$crdexpdate = strtotime($mm."/".$dd."/".$yy);
									
									if($crdexpdate > $maxdate)
									{
										$baddate = 1;
										print "<script type=\"text/javascript\">alert('Card expiration date cannot be more than ".$maxdays." days in the future.')</script>\n";
									}
								}
							}
					
							if (!$baddate && !$deldate)
							{
								$mydate .= "000000";
								$mydate .= TIMEZONE;
							}

							if (!$baddate)
							{
								$oldvalues = $mysession->getformvalue($formname, $formtag);
								if($oldvalues !== false)
									$oldval = $oldvalues[0];
								else
									$oldval = "";
								if (strcmp($mydate, $oldval) != 0)
								{
									$itemsrc = $mysession->getformsource($formname, $formtag);
									$numspec = count($itemsrc);
									$srcroot = $itemsrc[0];
									if ($srcroot == "credential" || $srcroot == "entity")
										$objectdn = $rootdn[$srcroot];
									else
										$objectdn = $srcroot;

									if ($numspec == 4)
									{
										$attrname = $itemsrc[2];
										$subtreedn = $itemsrc[1];
										$objectdn = $subtreedn.",".$objectdn;
										$srcobject = $itemsrc[3];
									}
									elseif ($numspec == 5)
									{
										$attrname = $itemsrc[2];
										$subtreedn = $itemsrc[1];
										$objectdn = $subtreedn.",".$objectdn;
										$srctag = strtolower($itemsrc[3]);
										$srcobject = $itemsrc[4];
										if ($deldate)
										{
											$mydate = "";
											$deldate = 0;
										}
									}

									// Modify the attribute
									if (!$deldate)
									{
										if ($numspec == 4)
										{
											$srcattr[$attrname] = $mydate;
											$r_date = $myxld->xld_modify($dbh, $objectdn, $srcattr);
											$rlecode = $myxld->xld_errno($dbh);
											if ($rlecode == 0x20)
											{
												$myldap->ldap_checkandcreate($objectdn, $srcobject, $srcattr, $host, $dbh);
												$rslt = $myxld->xld_modify($dbh, $objectdn, $srcattr);
												$rlecode = $myxld->xld_errno($dbh);
											}
										}
										elseif ($numspec == 5)
										{
											$s_gco = $myxld->xld_read($dbh, $objectdn, "objectclass=".$srcobject, array($attrname));
											$rlecode = $myxld->xld_errno($dbh);
											if ($rlecode == 0x20)
											{
												$myldap->ldap_checkandcreate($objectdn, $srcobject, array(), $host, $dbh);
												$s_gco = $myxld->xld_read($dbh, $objectdn, "objectclass=".$srcobject, array($attrname));
												$rlecode = $myxld->xld_errno($dbh);
											}
											unset($gcotagarray);

											if ($s_gco !== false)
											{
												$gcoentry = $myxld->xld_first_entry($dbh, $s_gco);
												if ($gcoentry !== false)
												{
													$xblkarray = $myxld->xld_get_values_len($dbh, $gcoentry, $attrname);
													if ($xblkarray !== false)
													{
														$numxblk = $xblkarray['count'];
														for ($i = 0; $i < $numxblk; $i++)
														{
															$gcoxblk = $xblkarray[$i];
															$r = $myldap->gco_split($gcoxblk);
															$gcotagarray[$r['tag']] = $r['payload'];
														}
													}
												}
												$gcotagarray[$srctag] = $mydate;
												$i = 0;
												unset($gco_update);
												foreach ($gcotagarray as $tag => $payload)
													$gco_update['xblk'][$i++] = $myldap->gco_combine(0, $tag, $payload);
												$r_gco = $myxld->xld_modify($dbh, $objectdn, $gco_update);
												$rlecode = $myxld->xld_errno($dbh);
											}
										}

										if ($rlecode == 0)
										{
											if ($_xemenable)
											{
												$eparts = $myldap->dntoparts($uedn);
												$edomain = $mylog->hextobin("00000000000000000000000000000000");
												$plentry = array();
												$plentry[7] = EAID_PIVI_TOKEN;
												$plentry[19] = "Applicant PIV-I token modified. Changed ".$attrname.($attrname == "xblk" ? " tag ".$srctag : "")." from ".$oldval." to ".$mydate;
												$plentry[20] = $mysession->getmcid();
												$plentry[21] = $mysession->getucid();
												$plentry[33] = $mydate;
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
									// we need to delete the existing date from the directory
									else
									{
										if ($numspec == 4)
										{
											$srcattr[$attrname] = $oldval;
											$rslt = $myxld->xld_mod_del($dbh, $objectdn, $srcattr);
											$rlecode = $myxld->xld_errno($dbh);
											if ($rlecode == 0)
											{
												if ($_xemenable)
												{
													$eparts = $myldap->dntoparts($uedn);
													$edomain = $mylog->hextobin("00000000000000000000000000000000");
													$plentry = array();
													$plentry[7] = EAID_PIV_TOKEN;
													$plentry[16] = $tokencid;
													$plentry[19] = "Applicant PIV-I token modified. Deleted ".$attrname.($attrname == "xblk" ? " tag ".$srctag : "");
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
							}
						}
					}
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