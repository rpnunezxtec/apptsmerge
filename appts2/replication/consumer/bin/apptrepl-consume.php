<?php

// $Id:$

// Service that periodically contacts active providers for replication updates.
// This is present on the application server on a per-application basis. 
require_once("/authentx/core/authentx5appcore/defs_core.php");
require_once("../../config/config-repl.php");
require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
date_default_timezone_set(DATE_TIMEZONE);

$myappt = new authentxappts2();
$myappt->setreploghost(DB_DBHOST_APPTSREPLOG);
$myappt->setreplogport(DB_DBPORT_APPTSREPLOG);
$myappt->setreplogdbname(DB_DBNAME_APPTSREPLOG);
$myappt->setreplogdbuser(DB_DBUSER_APPTSREPLOG);
$myappt->setreplogdbpasswd(DB_DBPASSWD_APPTSREPLOG);
$myappt->setxticket($consumer_xticket);
$myappt->setproxyurl($consumer_proxyserver);
$myappt->setproxyport($consumer_proxyport);
$myappt->setcontimeout($consumertimeout_conn);
$myappt->setexectimeout($consumertimeout_exec);
$myappt->setclcert($clientcert);
$myappt->setclcertpasswd($clientcertpassphrase);
$myappt->setpostverb("POST");
$myappt->setsiteid($cfg_siteid);
$myappt->setagencyid($cfg_agencyid);
$myappt->setdbhost(DB_DBHOST_APPTS);
$myappt->setdbport(DB_DBPORT_APPTS);

$error = false;
$updaterr = false;
$lastconsumertxn = array();		// records the details of the last transaction for each consumer
$scerr = false;

$rts = time();
print "--- ".date("Y-m-d H:i:s T")." Replication start with multi-row of ".$cfg_repl_multilimit." rows ---\n";

if ($cfg_mode_slave === true)
    print "    *** Operating as a SLAVE consumer. ***\n";

$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
if ($sdbh->connect_error)
{
	print "Error connecting to SQL database to read provider set.\n";
	$scerr = true;
}
else 
{
	// Get the active providers to contact
	// [n][rsid], [providerid], [providerurl], [rstatus], [repxsyncmts], [lastreq]
	$providerset = $myappt->getproviders($sdbh, true);
}

if (!$scerr)
{
	$np = count($providerset);
    if (($np > 1) && ($cfg_repl_slave === true))
        print "    !!! WARNING: Operating in slave mode and more than one provider configured !!!\n";
	
	// Perform requests on each provider
	for ($i = 0; $i < $np; $i++)
	{
		$updaterr = false;
		
		$provider = $providerset[$i];
		$providerid = $providerset[$i]["providerid"];
		$providerurl = $providerset[$i]["providerurl"];
		$providerstatus = $providerset[$i]["rstatus"];
		$providermodstamp = $providerset[$i]["repxsyncmts"];
		$originalprovidermodstamp = $providermodstamp;
		
		$sts = $providermodstamp;

		if ($providerstatus == 1)
		{
			// A new starting sync date for each round
			$ssyncdate = date("Y-m-d H:i:s T", $sts);
			
			// Request the update set for each table in $cfg_repl_tables
			foreach ($cfg_repl_tables as $table)
			{
				$t_s = time();
				$failcount = 0;
				print $providerid.": Requesting [".$table."] update set from ".$providerurl." with sync date ".$ssyncdate." and exec timeout of ".$consumertimeout_exec."s\n";
				/* Returns an array of row identifiers and error indication.
				* $rv["providerid"]=provider ID
				* $rv["table"]=table name
				* $rv["txnid"]=set identifier
				* $rv["row"][n]["rowid"]=row ID, ["row"][n]["mts"]=xsyncmts value (unix timestamp)
				* $rv["error"]=error message if any.
				*/
				$updateset = $myappt->consumer_requestupdateset($provider, $table, $cfg_mode_slave);
				$txnresultstatus = $updateset["httpcode"].".".$updateset["curlcode"].".000";
				unset($updateset["httpcode"]);	// Remove them so they don't get counted later for processing
				unset($updateset["curlcode"]);
				$t_r = time() - $t_s;
				
				if (isset($updateset["error"]))
				{
					$updaterr = true;
					print $updateset["error"]."\n";
					$myappt->replog_adderrorentry(0, $updateset["error"]);
					$txnresultmessage = "Error requesting update set for table ".$table;
				}
				else 
				{
					$txnresultmessage = "OK";
					$txnid = $updateset["txnid"];
					$table = $updateset["table"];
					$nr = count($updateset["row"]);
					print $providerid.": ".$nr." rows found for table ".$table." and txn ".$txnid." in ".$t_r." seconds \n";
					$myappt->replog_addcsreqentry($providerurl, $providermodstamp, $table, $nr, $txnid);
					
					if ($cfg_repl_multilimit < 1)
					{
						$cfg_repl_multilimit = 10;
						print "  Insane multilimit value, setting to ".$cfg_repl_multilimit."\n";
					}
					
					if (isset($cfg_tableset[$table]))
					{
						$keyname = $cfg_tableset[$table];
						
						$j = 0;
						$mq = 0;
						$rowset = array();
							
						while ($j < $nr)	// scerr can be used to abort the loop, otherwise process the rows returned
						{
							// Create request with multiple <row> tags with encoded rowid per row
							$keyval = $updateset["row"][$j]["rowid"];
							$ms = $updateset["row"][$j]["mts"];
								
							// Check the row (if it exists) locally to see if it has been locally
							// modified after the replication row, in which case we don't overwrite it.
							$isolder = $myappt->rowmodstampisolder($sdbh, $table, $keyname, $keyval, $ms);
							
							if ($isolder === true)
							{
								// Add this rowid to the set to process
								$rowset[$mq]["rowid"] = $keyval;
								$rowset[$mq]["ms"] = $ms;
								print $providerid.": Table ".$table." Row added to process set: ".$keyval." mts: ".date("Y-m-d H:i:s T", $ms)."\n";
								$mq++;
							}
							else
								print $providerid.": Table ".$table." Row Locally Modified: ".$keyval." mts: ".date("Y-m-d H:i:s T", $ms)."\n";
								
							// Ready to perform the request?
							// Either at the limit, or less than the limit and at the end of the set with some in the set to process
							if (($mq == $cfg_repl_multilimit) || (($j == ($nr - 1)) && ($mq > 0)))
							{
								print $providerid.": Table ".$table." Row set request for ".$mq." objects.\n";
								// [curlcode], [httpcode], [error], [providerid], [txnid], [table], [row][n][rowid], [row][n][mts], [row][n][row][colname]=colval
								$rowresult = $myappt->consumer_requestupdatemultirowset($provider, $table, $txnid, $rowset);
								$txnresultstatus = $rowresult["httpcode"].".".$rowresult["curlcode"].".000";
								unset($rowresult["httpcode"]);	// Remove them so they don't get counted later for processing
								unset($rowresult["curlcode"]);
											
								if (isset($rowresult["error"]))
								{
									print $providerid.": Table ".$table." Row set request FAIL: ".$rowresult["error"]."\n";
									$myappt->replog_adderrorentry($txnid, $rowresult["error"]);
									$txnresultmessage = "Error requesting update rows for table ".$table;
									$failcount++;
								}
								else
								{
									print $providerid.": Table".$table." Row set request PASS\n";
									$txnresultmessage = "OK";
								
									// Process the multiple-row result set
									// $rowresult["row"][$n]["rowid"] = decoded rowid for table
									// $rowresult["row"][$n]["mts"] = xsyncmts for the row
									// $rowresult["row"][$n]["col"][$colname] = decoded col value
									$nrow = count($rowresult["row"]);
									print $providerid.": Table ".$table." ".$nrow." rows in result set\n";
									for ($irow = 0; $irow < $nrow; $irow++)
									{
										$row = $rowresult["row"][$irow];
										$keyval = $row["rowid"];
										$rowms = $row["mts"];
										print "   Row: ".$keyval." : mts: ".$rowms."\n";
								
										// Setup the entry set.
										// Does the row already exist? Use UUID values to test.
										$keyname = $cfg_tableset[$table];
										$rowexists = $myappt->rowexists($sdbh, $table, $keyname, $keyval);
																																					
										if (isset($row["col"]))
										{
											if (count($row["col"]) == 0)
											{
												print $providerid.": Table ".$table.", Row ".$keyval." .. PROBLEM: No columns returned.\n";
												$failcount++;
											}
											else
											{
												// Create the update/insert set first
												$rowentry = $row["col"];
												$myappt->printcols($providerid, $table, $rowentry);
												$rue = false;
												
												if ($rowexists)
												{
													print "   Table ".$table." row exists: ".$keyval."\n";
													
													// Ignore some columns that are not to be replicated, create a set of column values to commit
													$ignorelist = array($cfg_aicols[$table], $keyname, "xsyncmts", "xreplmts");
													$commitset = array();
													foreach ($rowentry as $cn => $cv)
													{
														$cn = strtolower($cn);
														if (!in_array($cn, $ignorelist))
														{
															// Add to the set to save	
															$commitset[$cn] = $cv;
														}
													}
													$ncs = count($commitset);
												
													// Perform the DB update. 
													if ($ncs > 0)
													{
														print "   Table ".$table." performing updates.\n";
														$q = "update ".$table." set ";
														foreach ($commitset as $cn => $cv)
														{
															if ($cv == '')
																$q .= "\n ".$cn."=NULL, ";
															else
																$q .= "\n ".$cn."='".$sdbh->real_escape_string($cv)."', ";
														}
														$q .= "xsyncmts='".$rowms."', ";
														$q .= "xreplmts='".$rts."' ";
														$q .= "where ".$keyname."='".$sdbh->real_escape_string($keyval)."' ";
														
														$s = $sdbh->query($q);
														if (!$s)
														{
															print $providerid.": Table ".$table.", Row ".$keyval." .. PROBLEM: Error saving row: ".$sdbh->error."\n";
															$failcount++;
															$rue = true;
														}
														else
															print $providerid.": Table ".$table.", Row ".$keyval." .. Updated.\n";
													}
													else
														print "   Table ".$table." no columns to be updated.\n";
												}
												else	// Row does not exist, so insert it
												{
													print "   Table ".$table." row is new: ".$keyval."\n";
													
													// Ignore some columns that are not to be replicated, create a set of column values to commit
													// For addition we need to include the key column
													$ignorelist = array($cfg_aicols[$table], "xsyncmts", "xreplmts");
													$commitset = array();
													foreach ($rowentry as $cn => $cv)
													{
														$cn = strtolower($cn);
														if (!in_array($cn, $ignorelist))
														{
															// Add to the set to save	
															$commitset[$cn] = $cv;
														}
													}
													$ncs = count($commitset);
													
													// Perform the DB new row insertion. 
													if ($ncs > 0)
													{
														print "   Table ".$table." performing addition.\n";
														$q = "insert into ".$table." set ";
														foreach ($commitset as $cn => $cv)
														{
															if ($cv == '')
																$q .= "\n ".$cn."=NULL, ";
															else
																$q .= "\n ".$cn."='".$sdbh->real_escape_string($cv)."', ";
														}
														$q .= "xsyncmts='".$rowms."', ";
														$q .= "xreplmts='".$rts."' ";
													
														$s = $sdbh->query($q);
														if (!$s)
														{
															print $providerid.": Table ".$table.", Row ".$keyval." .. PROBLEM: Error saving row: ".$sdbh->error."\n";
															$failcount++;
															$rue = true;
														}
														else
															print $providerid.": Table ".$table.", Row ".$keyval." .. Inserted.\n";
													}
													else
														print "   Table ".$table." no columns to be inserted.\n";
												}
												
												// Add log entry for successful entry
												if (!$rue)
													$myappt->replog_updatecrreqentry($txnid);
												else
													$rue = false;
											}
										}
										else
										{
											print $providerid.": Table ".$table.", Row ".$keyval." .. PROBLEM: No row returned.\n";
											$failcount++;
										}
									}	// Row loop
								}
								
								$mq = 0;
								print $providerid.": Table ".$table." Row set request completed.\n";
							}	// Enough rows in the set to process for table
							
							$j++;	// result row counter
						}	// End main row set processing loop for table
					}	// Only if the table is known
					else
					{
						print $providerid.": Table ".$table." .. PROBLEM: No index configured.\n";
						$failcount++;
					}
				}
			}	// table set loop
			
			// Now perform the deletedrows operation
			// This will request the delete set from the provider using the mts for the provider
			// and delete any rows returned by the provider in the consumer database.
			// returns an array containing [error][] and [delete][] for service logging.
			$xd = $myappt->consumer_requestdeleteset($sdbh, $provider);
			if (isset($xd["error"]))
			{
				$ne = count($xd["error"]);
				for ($ce = 0; $ce < $ne; $ce++)
					print $providerid.": ERROR, deleterows: ".$xd["error"][$ce]."\n";
			}
			if (isset($xd["delete"]))
			{
				$ne = count($xd["delete"]);
				for ($ce = 0; $ce < $ne; $ce++)
					print $providerid.": ".$xd["delete"][$ce]."\n";
			}
			else
				print $providerid.": No rows to delete.\n";

			// Do not update the timestamps if we got an error at either end of the operation
			if ($updaterr === false)
			{
				// Completed provider, update the provider record with new timestamp.
				// Next search will include rows with this timestamp, and further rows with this timestamp
				// that were not present on the previous search.
				// $rts is the timestamp for the process start, $sts/$providermodstamp is the timestamp read for this provider.
				// Need to update the provider with $rts less 5 seconds for overlap.
				if ($failcount > 0)
					print $providerid.": Failcount for provider: ".$failcount." rows. Replication sync stamp not changed.\n";
				else
				{
					$providermodstamp = $rts - $cfg_repl_overlap;	// Move it up to just before starting the replication request
					print $providerid.": Modstamp set to ".$providermodstamp." : ".date("Y-m-d H:i:s T", $providermodstamp)."\n";
					$myappt->updateprovider($sdbh, $providerid, false, false, $providermodstamp, false);
				}
			}
			else
			{
				print $providerid.": Replication sync stamp not changed.\n";
			}
		}
		else
		{
			print $providerid.":  Status is not active.\n";
		}
	} // end providers looop
}
	
$rte = time();
$rtt = $rte - $rts;
$cst = $repl_consumersleep - $rtt;
print "--- ".date("Y-m-d H:i:s T")." Replication Complete in ".$rtt." seconds (".$cst."s) ---\n";

if ($repl_consumersleep > 0)
{
	if ($cst > 0)
		sleep($cst);
}
print "=== (XXX) ===\n";

exit(0);

?>