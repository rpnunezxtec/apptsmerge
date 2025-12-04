<?php

// $Id:$

// REST API : Appointments Replication class

class apptrepl
{
	function
	index()
	{
		throw new RestException(400, "Malformed request");
	}

	/**
	* @desc REST GET method for list of modified rows in specified table. 
	* Returns a set of ID's for rows that have been modified since the specified timestamp (inclusive)
	* Multiple calls would be made to this, using a different table ID for each call according to
	* the consumer's configuration and requirements.
	* @param integer $mts: The timestamp in Unix timestamp format (integer).
	* @param string $table: The table name to perform the request on.
    * @param integer $mode: Set to 1 if the consumer is acting as a slave or seeding, and requesting all modified objects from a single provider.
	*/
	function
	getrowlist($mts, $table64, $mode = 0)
	{
		require_once("/authentx/core/authentx5appcore/defs_core.php");
		require_once("../config/config-repl.php");
		require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
		require_once(_AUTHENTX5APPCORE."/cl-auth.php");
		date_default_timezone_set(DATE_TIMEZONE);
		
		$myauth = new authentxauth();
		$myappt = new authentxappts2();
		$myappt->setreploghost(DB_DBHOST_APPTSREPLOG);
		$myappt->setreplogport(DB_DBPORT_APPTSREPLOG);
		$myappt->setreplogdbname(DB_DBNAME_APPTSREPLOG);
		$myappt->setreplogdbuser(DB_DBUSER_APPTSREPLOG);
		$myappt->setreplogdbpasswd(DB_DBPASSWD_APPTSREPLOG);
		
		$table = strtolower(trim(base64_decode(urldecode($table64))));
		
		// Authentication and identification
		if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{
			// decodes and decrypts the xticket stored in the Authorization header.
			// Returns true if present and decoded, or false if not.
			// Privilege determination set is in the authinfo member, containing the xticket elements
			if ($myauth->authwapi($_xt_schk) === true)
			{
				// In this case the app is replication, and the sourceid is the consumer host ID
				$appid = $myauth->authinfo['appid'];
				$sourceid = $myauth->authinfo['sourceid'];
				$txnid = substr($myappt->getuuid_hex(), 0, 8);
				
				// Only require the client cert to enable access and the xticket appid to be for replication
				if (strcasecmp($appid, APPID_APPTSYNC) == 0)
				{
				    // If this (local) machine is set to be a slave then we do not ever service other consumers
                    if ($cfg_mode_slave === true)
                    {
                        throw new RestException(401, 'This device is set to slave operation only.');
                    }
                    else
                    {
                    	if (in_array($table, $cfg_permit_tables))
                    	{
	    					$ts = time();
	                            
	                        // Connect to DB and get the set for the table and mts specified.
	                        $sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
							if (!$sdbh->connect_error)
							{
								// Find the key for this table in config
								if (isset($cfg_tableset[$table]))
								{
									$keyname = $cfg_tableset[$table];
									
									// Returns a set of $rv[n][key],[mts]
		                        	$replset = $myappt->provider_getdbreplset($sdbh, $table, $keyname, $mts, $cfg_mts_gap);
		    						$stime = time() - $ts;		// search time
		    						$nrepl = count($replset);	// number of results
		    						
			    					// Format the response array set
			    					$rv = array();
			    					$rv['providerid'] = $cfg_serverid;
			    					$rv['transactionid'] = $txnid;
			    					$rv['table'] = $table64;
			    						
			    					for ($i = 0; $i < $nrepl; $i++)
			    					{
			    						$rv['row'][$i]['id'] = base64_encode($replset[$i]["key"]);
			    						$rv['row'][$i]['modstamp'] = $replset[$i]["mts"];
			    					}
		    						
		    						// Add a response log entry
			    					$myappt->replog_addpsrspentry($_SERVER['REMOTE_ADDR'], $mts, $table, $nrepl, $txnid, $stime);
			    					
			    					$sdbh->close();
			                        return($rv);
								}
								else
								{
									$sdbh->close();
									// Table key not found
									throw new RestException(401, 'Table key not found.');
								}
	                    	}
	                    	else
	                    	{
								// Cannot connect to DB
								throw new RestException(401, 'DB connection fail.');
							}
						}
						else
                    	{
							// Invalid table requests
							throw new RestException(401, 'Invalid table request: '.$table.'.');
						}
                    }
				}
				else
				{
					// appid is not the correct one
					throw new RestException(401, 'Unrecognized application: '.$appid);
				}
			}
			else
			{
				// Failed to authenticate for some reason
				throw new RestException(401, "Auth error: ".$myauth->authinfo['error']);
			}
		}
		else
		{
			// No xticket
			throw new RestException(403, "Access denied.");
		}
	}
	
	
	/**
	* @desc REST method for multiple row fetch. Returns 1:n rows and all columns.
	* Requires a POST payload containing XML encoded row ID's for the table specified.
	* Operates on a single table only.
	*/
	function
	postfetchrows($request_data = NULL)
	{
		require_once("/authentx/core/authentx5appcore/defs_core.php");
		require_once("../config/config-repl.php");
		require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
		require_once(_AUTHENTX5APPCORE."/cl-auth.php");
		date_default_timezone_set(DATE_TIMEZONE);
	
		$myauth = new authentxauth();
		$myappt = new authentxappts2();
		$myappt->setreploghost(DB_DBHOST_APPTSREPLOG);
		$myappt->setreplogport(DB_DBPORT_APPTSREPLOG);
		$myappt->setreplogdbname(DB_DBNAME_APPTSREPLOG);
		$myappt->setreplogdbuser(DB_DBUSER_APPTSREPLOG);
		$myappt->setreplogdbpasswd(DB_DBPASSWD_APPTSREPLOG);
	
		// Authentication and identification
		if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{
			// decodes and decrypts the xticket stored in the Authorization header.
			// Returns true if present and decoded, or false if not.
			// Privilege determination set is in the authinfo member, containing the xticket elements
			if ($myauth->authwapi($_xt_schk) === true)
			{
				$appid = $myauth->authinfo['appid'];
				$sourceid = $myauth->authinfo['sourceid'];
	
				// Only require the client cert to enable access and the xticket appid to be for replication
				if (strcasecmp($appid, APPID_APPTSYNC) == 0)
				{
					if (empty($request_data))
						throw new RestException(400, "Short or missing XML input.");
					else
					{
						if (isset($request_data["transactionid"]))
						{
							$txnid = $request_data["transactionid"];
							$table64 = $request_data["table"];
							$table = trim(base64_decode($table64));
							
							if (isset($cfg_tableset[$table]))
							{
								$keyname = $cfg_tableset[$table];
								
								if (isset($request_data["row"]))
								{
									if (is_array($request_data["row"]))
										$rowidset = $request_data["row"];
									else
										$rowidset = array($request_data["row"]);
									
									$nrow = count($rowidset);
									if ($nrow > 0)
									{
										$rv = array();
										$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
										if (!$sdbh->connect_error)
										{
											$rv['providerid'] = $cfg_serverid;
											$rv['transactionid'] = $txnid;
		
											for ($i = 0; $i < $nrow; $i++)
											{
												$rowid64 = $rowidset[$i];
												$keyval = base64_decode($rowid64);
											
												$replrow = $myappt->provider_getdbreplrow($sdbh, $table, $keyname, $keyval);
												
												if (isset($replrow["error"]))
												{
													$errmsg = $replrow["error"];
													$myappt->replog_adderrorentry($txnid, $errmsg);
													$sdbh->close();
													throw new RestException(401, $errmsg);
												}
												else
												{
													// Format the response array set
													$rowmts = $myappt->provider_getrowmts($sdbh, $table, $keyname, $keyval);
													$rv['rowpack'][$i]['rowid'] = base64_encode($keyval);
													$rv['rowpack'][$i]['modstamp'] = $rowmts;
													$rv['rowpack'][$i]['row'] = array();
													$n = 0;
			
													// ['row']['colname']=b64 col value
													foreach ($replrow['row'] as $colname => $colval64)
													{
														$rv['rowpack'][$i]['row']['col'][$n]['name'] = $colname;
														$rv['rowpack'][$i]['row']['col'][$n]['value'] = $colval64;
														$n++;
													}
													// Increment the request row counter in the log DB
													$myappt->replog_updateprreqentry($txnid);
												}
											}
											
											$sdbh->close();
											return($rv);
										}
									}
									else
									{
										throw new RestException(400, "No row ID's found in request.");
									}
								}
								else
								{
									throw new RestException(400, "Row request section not found.");
								}
							}
							else
							{
								throw new RestException(400, "Table key not found.");
							}
						}
						else
						{
							throw new RestException(400, "Transaction ID not found in request.");
						}
					}
				}
				else
				{
					// unknown application ID
					throw new RestException(401, 'Unknown application.');
				}
			}
			else
			{
				// Failed to authenticate for some reason
				throw new RestException(401, $myauth->authinfo['error']);
			}
		}
		else
		{
			// No xticket
			throw new RestException(403, "Access denied.");
		}
	}
	
	
	/**
	* @desc REST GET method for retrieving the set of delete operations since $mts. 
	* Returns a set of table, keycol, keyval for rows that require deletion since the specified timestamp (inclusive)
	* @param integer $mts: The timestamp in Unix timestamp format (integer).
	*/
	function
	getdeleteset($mts)
	{
		require_once("/authentx/core/authentx5appcore/defs_core.php");
		require_once("../config/config-repl.php");
		require_once(_AUTHENTX5APPCORE."/cl-appts2.php");
		require_once(_AUTHENTX5APPCORE."/cl-auth.php");
		date_default_timezone_set(DATE_TIMEZONE);
		
		$myauth = new authentxauth();
		$myappt = new authentxappts2();
		$myappt->setreploghost(DB_DBHOST_APPTSREPLOG);
		$myappt->setreplogport(DB_DBPORT_APPTSREPLOG);
		$myappt->setreplogdbname(DB_DBNAME_APPTSREPLOG);
		$myappt->setreplogdbuser(DB_DBUSER_APPTSREPLOG);
		$myappt->setreplogdbpasswd(DB_DBPASSWD_APPTSREPLOG);
		
		// Authentication and identification
		if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{
			// decodes and decrypts the xticket stored in the Authorization header.
			// Returns true if present and decoded, or false if not.
			// Privilege determination set is in the authinfo member, containing the xticket elements
			if ($myauth->authwapi($_xt_schk) === true)
			{
				// In this case the app is replication, and the sourceid is the consumer host ID
				$appid = $myauth->authinfo['appid'];
				$sourceid = $myauth->authinfo['sourceid'];
				
				// Only require the client cert to enable access and the xticket appid to be for replication
				if (strcasecmp($appid, APPID_APPTSYNC) == 0)
				{
					// Connect to DB and get the set for the mts specified.
					$sdbh = new mysqli(DB_DBHOST_APPTS, DB_DBUSER_APPTS, DB_DBPASSWD_APPTS, DB_DBNAME_APPTS, DB_DBPORT_APPTS);
					if (!$sdbh->connect_error)
					{
						// [n][tablename], [keycol], [keyval]
						$dset = $myappt->provider_getdeleteset($sdbh, $mts);
						$nrepl = count($dset);

						$rv = array();
						$rv['providerid'] = $cfg_serverid;
						for ($i = 0; $i < $nrepl; $i++)
						{
							$rv['delete'][$i]['table'] = base64_encode($dset[$i]["tablename"]);
							$rv['delete'][$i]['keycol'] = base64_encode($dset[$i]["keycol"]);
							$rv['delete'][$i]['keyval'] = base64_encode($dset[$i]["keyval"]);
						}
						
						// Add a response log entry
						$myappt->replog_addpsrspentry($_SERVER['REMOTE_ADDR'], $mts, "delete", $nrepl, 0, 0);
						$sdbh->close();
						return($rv);
					}
					else
					{
						// Cannot connect to DB
						throw new RestException(401, 'DB connection fail.');
					}
				}
				else
				{
					// appid is not the correct one
					throw new RestException(401, 'Unrecognized application: '.$appid);
				}
			}
			else
			{
				// Failed to authenticate for some reason
				throw new RestException(401, "Auth error: ".$myauth->authinfo['error']);
			}
		}
		else
		{
			// No xticket
			throw new RestException(403, "Access denied.");
		}
	}
					

}
?>
