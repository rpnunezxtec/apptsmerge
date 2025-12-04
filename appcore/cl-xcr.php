<?php
// $Id:$
// Class for XCR processing

interface ixcr
{
	public function readxcroutputset($sdbh, $xcrid);
	public function readxcrinputset($sdbh, $xcrid);
	public function evaluatexcrstimulus($sdbh, $xcrinputs);
	public function setoutput($sdbh, $xcroutput, $dostate);
	public function getoutput($dbh, $douttype, $id);
	public function getdoutlist($dbh, $fittedonly = true, $agencyid);
	public function getinput($dbh, $type, $id);
	public function getinputlist($dbh, $fittedonly = true, $agencyid);
	public function getxcrparams($dbh, $type, $id, $xcrinstate);
	public function getiopageset($dbh, $ptype, $agencyid, $f_devid = false, $f_isfitted = false);
	public function getdevicelist($dbh, $agencyid);
	public function agencyownspoint($dbh, $agencyid, $ptype, $pid);
	public function pointinuse($dbh, $ptype, $pid);
	public function lvpointbelongstodevice($dbh, $ptype, $pid);
	public function deletelvpoint($dbh, $ptype, $pid);
	
	public function readerrorstack();
}

class xcr implements ixcr
{
	function __construct ()
	{
		
	}

	function __destruct ()
	{
		
	}
	
	
	// ******** PROPERTIES

	private $errorstack = array();
	
	
	
	// ******** PRIVATE METHODS
	
	private function
	clearerrorstack()
	{
		$this->errorstack = array();
	}
	
	private function
	adderrortostack($errnum, $errmsg)
	{
		$this->errorstack[count($this->errorstack)] = array($errnum, $errmsg);
	}
	
	
	
	// ******** PUBLIC METHODS
	
	/**
	 * @return array containing the set of xcr outputs
	 * [n]["xcroutid"]=xcroutid: The index in the XCR outputs table for the point reference.
	 * [n]["id"]=xcrdoutid: The index of the dout point in the appropriate table
	 * [n]["xcrstate"]=xcrdoutstate: The state to set when XCR is triggered (1=active, 0=inactive)
	 * [n]["xcrdoutinsvc"]=xcrdoutinsvc: The dout point is in service for XCR processing when set to 1
	 * [n]["xcrdouttype"]=xcrdouttype: The table selection mechanism for the DOUT point
	 * [n]["doutname"]=doutname: Name of the DOUT point
	 * [n]["actstate"]=doutactstate: The active state of the DOUT point
	 * [n]["curstate"]=doutcurstate: The current state of the DOUT point
	 * [n]["actlbl"]=doutactlbl: The active state label (eg ON, OPEN, UP)
	 * [n]["inactlbl"]=doutinactlbl: The inactive label (eg OFF, CLOSED, DOWN)
	 * [n]["insvc"]=insvc: The in-service state of the DOUT point itself
	 * @param resource $sdbh. SQL xnodeio db connection
	 * @param int $xcrid. The index for the XCR row
	 * @desc Reads the DOUT set for the XCR supplied.
	 */
	public function
	readxcroutputset($sdbh, $xcrid)
	{
		$rv = array();
		$out = 0;
		
		$q_o = "select xcroutid, "
			. "\n xcroutput.doutid as xcrdoutid, "
			. "\n xcroutput.insvc as xcrdoutinsvc, "
			. "\n xcroutput.doutstate as xcrdoutstate, "
			. "\n xcroutput.douttype as xcrdouttype "
			. "\n from xcroutput "
			. "\n where xcrid='".$sdbh->real_escape_string($xcrid)."' "
			;
			
		$s_o = $sdbh->query($q_o);
		if (!$s_o)
			print "--E Error reading XCR outputs: ".$sdbh->error."\n";
		else 
		{
			while ($r_o = $s_o->fetch_assoc())
			{
				// Need to check the type (real or virtual) to determine the table to read the state data from
				$rv[$out]["xcroutid"] = $r_o["xcroutid"];			// Index in the XCR outputs table
				$rv[$out]["id"] = $r_o["xcrdoutid"];				// ID of dout point in whichever table it comes from
				$rv[$out]["xcrstate"] = $r_o["xcrdoutstate"];		// The state to set the output to when triggered (1=active, 0=inactive)
				$rv[$out]["xcrdoutinsvc"] = $r_o["xcrdoutinsvc"];	// The insvc state of the dout for XCR processing
				$rv[$out]["xcrdouttype"] = $r_o["xcrdouttype"];		// The dout type (ie table selection mechanism) for the point
				
				// Now read the rest of the dout info from the appropriate table
				switch ($rv[$out]["xcrdouttype"])
				{
					case DOTYPE_REAL:
							$q_do = "select xdoutid as doutid, "
								. "\n doutname, "
								. "\n doutactstate, "
								. "\n doutcurstate, "
								. "\n doutactlbl, "
								. "\n doutinactlbl, "
								. "\n insvc "
								. "\n from xdout "
								. "\n where xdoutid='".$sdbh->real_escape_string($rv[$out]["id"])."' "
								;
							break;
							
					case DOTYPE_LV:
							$q_do = "select lvdoutid as doutid, "
								. "\n doutname, "
								. "\n doutactstate, "
								. "\n doutcurstate, "
								. "\n doutactlbl, "
								. "\n doutinactlbl, "
								. "\n insvc "
								. "\n from lvdout "
								. "\n where lvdoutid='".$sdbh->real_escape_string($rv[$out]["id"])."' "
								;
							break;
					
					default:
							$q_do = false;
							print "--E Error: unrecognised dout type.\n";
							break;
				}
				
				if ($q_do !== false)
				{
					$s_do = $sdbh->query($q_do);
					if ($s_do)
					{
						$r_do = $s_do->fetch_assoc();
						if ($r_do)
						{
							$rv[$out]["doutname"] = $r_do["doutname"];
							$rv[$out]["actstate"] = $r_do["doutactstate"];
							$rv[$out]["curstate"] = $r_do["doutcurstate"];
							$rv[$out]["actlbl"] = $r_do["doutactlbl"];
							$rv[$out]["inactlbl"] = $r_do["doutinactlbl"];
							$rv[$out]["insvc"] = $r_do["insvc"];
				
							$out++;
						}
						$s_do->free();
					}
				}
			}
			$s_o->free();
		}
		
		return $rv;
	}
	
	/**
	 * @return Array containing the input and-or logic set for the XCR to process
	 * rv: g=group (ie AND set)
	 * [g][n][id]: The index ID for the point in whatever table it is from.
	 * [g][n][type]: The type of point, ie which table it comes from.
	 * [g][n][state]: The state of the point for evaluation (1=active, 0=inactive)
	 * [g][n][xcrinid]: The index ID in the xcrinput table for the point reference.
	 * @param resource $sdbh. SQL db connection
	 * @param int $xcrid. The ID of the XCR row to process. 
	 * @desc Reads the input and-or array for the XCR and returns it.
	 */
	public function
	readxcrinputset($sdbh, $xcrid)
	{
		$rv = array();
		$count = array();
		
		$q_i = "select xcrinid, "
			. "\n ptype, "
			. "\n inid, "
			. "\n instate, "
			. "\n ingroup "
			. "\n from xcrinput "
			. "\n where xcrid='".$sdbh->real_escape_string($xcrid)."' "
			. "\n order by ingroup "
			;
			
		$s_i = $sdbh->query($q_i);
		if (!$s_i)
			print "--E Error reading XCR inputs: ".$sdbh->error."\n";
		else 
		{
			while ($r_i = $s_i->fetch_assoc())
			{
				$ingroup = $r_i["ingroup"];
				if (!isset($count[$ingroup]))
					$count[$ingroup] = 0;
					
				if (($ingroup > 0) && ($ingroup < 255))
				{
					$rv[$ingroup][$count[$ingroup]]["id"] = $r_i["inid"];
					$rv[$ingroup][$count[$ingroup]]["type"] = $r_i["ptype"];
					$rv[$ingroup][$count[$ingroup]]["state"] = $r_i["instate"];
					$rv[$ingroup][$count[$ingroup]]["xcrinid"] = $r_i["xcrinid"];
					$count[$ingroup]++;
				}
			}
			$s_i->free();
		}
		
		return $rv;
	}
	
	
	/**
	 * @return bool. True if triggered by stimulus, false if not
	 * @param resource $sdbh. SQL xnodeio db connection
	 * @param int $xcrinputs. The AND-OR array for the XCR being processed
	 * @desc Evaluates the stimulus to determine if a trigger condition is met.
	 */
	public function
	evaluatexcrstimulus($sdbh, $xcrinputs)
	{
		// Evaluate the stimulus, AND-OR array
		$rv = false;
		
		if (count($xcrinputs) > 0)
		{
			$groupstate = array();
			
			// Evaluate each AND group
			foreach ($xcrinputs as $groupid => $groupset)
			{
				// Starts as true, changes to false on the first false stimulus (logical AND)
				$groupstate[$groupid] = true;
				$ng = count($groupset);
				for ($g = 0; $g < $ng; $g++)
				{
					// Check only if it is still true
					if ($groupstate[$groupid] === true)
					{
						$inid = $groupset[$g]["id"];
						$ptype = $groupset[$g]["type"];
						$instate = $groupset[$g]["state"];
						
						switch ($ptype)
						{
							case XCR_PTYPE_REALDIN:
									$q_s = "select insvc, "
										. "\n dinactstate, "
										. "\n dincurstate "
										. "\n from xdin "
										. "\n where xdinid='".$sdbh->real_escape_string($inid)."' "
										;
									$s_s = $sdbh->query($q_s);
									if ($s_s)
									{
										$r_s = $s_s->fetch_assoc();
										if ($r_s)
										{
											if ($r_s["insvc"] == 1)
											{
												if ($r_s["dincurstate"] != $instate)
													$groupstate[$groupid] = false;
											}
										}
										$s_s->free();
									}
									break;
									
							case XCR_PTYPE_REALDIN3:
									$q_s = "select insvc, "
										. "\n dinacthstate, "
										. "\n dinactlstate, "
										. "\n dincurstate "
										. "\n from xdin3 "
										. "\n where xdin3id='".$sdbh->real_escape_string($inid)."' "
										;
									$s_s = $sdbh->query($q_s);
									if ($s_s)
									{
										$r_s = $s_s->fetch_assoc();
										if ($r_s)
										{
											if ($r_s["insvc"] == 1)
											{
												if ($r_s["dincurstate"] != $instate)
													$groupstate[$groupid] = false;
											}
										}
										$s_s->free();
									}
									break;
									
							case XCR_PTYPE_LVDIN:
									$q_s = "select insvc, "
										. "\n dinactstate, "
										. "\n dincurstate "
										. "\n from lvdin "
										. "\n where lvdinid='".$sdbh->real_escape_string($inid)."' "
										;
									$s_s = $sdbh->query($q_s);
									if ($s_s)
									{
										$r_s = $s_s->fetch_assoc();
										if ($r_s)
										{
											if ($r_s["insvc"] == 1)
											{
												if ($r_s["dincurstate"] != $instate)
													$groupstate[$groupid] = false;
											}
										}
										$s_s->free();
									}
									break;
									
							case XCR_PTYPE_REALDOUT:
									$q_s = "select insvc, "
										. "\n doutactstate, "
										. "\n doutcurstate "
										. "\n from xdout "
										. "\n where xdoutid='".$sdbh->real_escape_string($inid)."' "
										;
									$s_s = $sdbh->query($q_s);
									if ($s_s)
									{
										$r_s = $s_s->fetch_assoc();
										if ($r_s)
										{
											if ($r_s["insvc"] == 1)
											{
												if ($r_s["doutcurstate"] != $instate)
													$groupstate[$groupid] = false;
											}
										}
										$s_s->free();
									}
									break;
									
							case XCR_PTYPE_LVDOUT:
									$q_s = "select insvc, "
										. "\n doutactstate, "
										. "\n doutcurstate "
										. "\n from lvdout "
										. "\n where lvdoutid='".$sdbh->real_escape_string($inid)."' "
										;
									$s_s = $sdbh->query($q_s);
									if ($s_s)
									{
										$r_s = $s_s->fetch_assoc();
										if ($r_s)
										{
											if ($r_s["insvc"] == 1)
											{
												if ($r_s["doutcurstate"] != $instate)
													$groupstate[$groupid] = false;
											}
										}
										$s_s->free();
									}
									break;
									
							case XCR_PTYPE_AINCOMP:
									$q_s = "select insvc, "
										. "\n xcompstate "
										. "\n from xcomp "
										. "\n where xcompid='".$sdbh->real_escape_string($inid)."' "
										;
									$s_s = $sdbh->query($q_s);
									if ($s_s)
									{
										$r_s = $s_s->fetch_assoc();
										if ($r_s)
										{
											if ($r_s["insvc"] == 1)
											{
												if ($r_s["xcompstate"] != $instate)
													$groupstate[$groupid] = false;
											}
										}
										$s_s->free();
									}
									break;
									
							case XCR_PTYPE_TINT:
									$q_s = "select insvc, "
										. "\n tintstate "
										. "\n from intervals "
										. "\n where xtintid='".$sdbh->real_escape_string($inid)."' "
										;
									$s_s = $sdbh->query($q_s);
									if ($s_s)
									{
										$r_s = $s_s->fetch_assoc();
										if ($r_s)
										{
											if ($r_s["insvc"] == 1)
											{
												if ($r_s["tintstate"] != $instate)
													$groupstate[$groupid] = false;
											}
										}
										$s_s->free();
									}
									break;
									
							case XCR_PTYPE_TODTRIG:
									$q_s = "select insvc, "
										. "\n todstate "
										. "\n from todtrigger "
										. "\n where ttid='".$sdbh->real_escape_string($inid)."' "
										;
									$s_s = $sdbh->query($q_s);
									if ($s_s)
									{
										$r_s = $s_s->fetch_assoc();
										if ($r_s)
										{
											if ($r_s["insvc"] == 1)
											{
												if ($r_s["todstate"] != $instate)
													$groupstate[$groupid] = false;
											}
										}
										$s_s->free();
									}
									break;
									
							default:
									print "--E Unrecognised point type.\n";
									break;
						}
					}
				}
			}
			
			// Logical OR the group states to produce a current stimulus state
			foreach ($groupstate as $groupid => $gstate)
			{
				if ($gstate === true)
					$rv = true;
			}
		}
		
		return $rv;
	}
	
	
	/**
	 * @return bool. True if successful, False if failed
	 * @param resource $sdbh. SQL db connection
	 * @param array $xcroutput. A single xcroutput row to reset to an inactive state
	 * @param int $dostate. The state to set the output to.
	 * @desc Sets the dout point to the state requested and returns true is successful or false otherwise.
	 */
	public function
	setoutput($sdbh, $xcroutput, $dostate)
	{
		// $xcroutput:
		// ["id"]=xcrdoutid: The index of the dout point in the appropriate table
		// ["xcrstate"]=xcrdoutstate: The state to set when XCR is triggered (1=active, 0=inactive)
		// ["xcrdoutinsvc"]=xcrdoutinsvc: The dout point is in service for XCR processing when set to 1
		// ["xcrdouttype"]=xcrdouttype: The table selection mechanism for the DOUT point
		// ["doutname"]=doutname: Name of the DOUT point
		// ["actstate"]=doutactstate: The active state of the DOUT point
		// ["curstate"]=doutcurstate: The current state of the DOUT point
		// ["actlbl"]=doutactlbl: The active state label (eg ON, OPEN, UP)
		// ["inactlbl"]=doutinactlbl: The inactive label (eg OFF, CLOSED, DOWN)
		// ["insvc"]=insvc: The in-service state of the DOUT point itself
		$rv = false;
		
		switch ($xcroutput["xcrdouttype"])
		{
			case DOTYPE_REAL:
				$q_out = "update xdout "
					. "\n set doutcurstate='".$sdbh->real_escape_string($dostate)."', "
					. "\n doutlastcos='".gmdate("Y-m-d H:i:s")."' "
					. "\n where xdoutid='".$sdbh->real_escape_string($xcroutput["id"])."' "
					;
				break;
				
			case DOTYPE_LV:
				$q_out = "update lvdout "
					. "\n set doutcurstate='".$sdbh->real_escape_string($dostate)."', "
					. "\n doutlastcos='".gmdate("Y-m-d H:i:s")."' "
					. "\n where lvdoutid='".$sdbh->real_escape_string($xcroutput["id"])."' "
					;
				break;
				
			default:
				$q_out = false;
				break;
		}
		
		if ($q_out !== false)
		{
			$rv = $sdbh->query($q_out);
			if (!$rv)
				print "--E Error setting output: ".$sdbh->error."\n";
		}
		
		return $rv;
	}
	
	
	/**
	 * @return array of dout parameters [name],[insvc],[actlabel],[inactlabel],[actstate],[curstate]
	 * @param resource $dbh. SQL db connection
	 * @param int $douttype. The DOUT type indicator
	 * @param int $id. The index ID of the dout point
	 * @desc Reads the DOUT parameters from the appropriate table based on the douttype specified
	 */
	public function
	getoutput($dbh, $douttype, $id)
	{
		$rv = false;
		
		switch ($douttype)
		{
			case DOTYPE_REAL:
				$q_po = "select doutname, "
						. "\n doutactstate, "
						. "\n doutcurstate, "
						. "\n doutactlbl, "
						. "\n doutinactlbl, "
						. "\n insvc "
						. "\n from xdout "
						. "\n where xdoutid='".$dbh->real_escape_string($id)."' "
						;
				$s_po = $dbh->query($q_po);
				if ($s_po)
				{
					$r_po = $s_po->fetch_assoc();
					if ($r_po)
					{
						$rv["name"] = $r_po["doutname"];
						$rv["insvc"] = $r_po["insvc"];
						$rv["actlabel"] = $r_po["doutactlbl"];
						$rv["inactlabel"] = $r_po["doutinactlbl"];
						$rv["actstate"] = $r_po["doutactstate"];
							
						$actstate = $r_po["doutactstate"];
						$curstate = $r_po["doutcurstate"];
							
						if ($curstate == $actstate)
							$rv["curstate"] = $r_po["doutactlbl"];
						else 
							$rv["curstate"] = $r_po["doutinactlbl"];
					}
					$s_po->free();
				}
				break;
				
			case DOTYPE_LV:
				$q_po = "select doutname, "
						. "\n doutactstate, "
						. "\n doutcurstate, "
						. "\n doutactlbl, "
						. "\n doutinactlbl, "
						. "\n insvc "
						. "\n from lvdout "
						. "\n where lvdoutid='".$dbh->real_escape_string($id)."' "
						;
				$s_po = $dbh->query($q_po);
				if ($s_po)
				{
					$r_po = $s_po->fetch_assoc();
					if ($r_po)
					{
						$rv["name"] = $r_po["doutname"];
						$rv["insvc"] = $r_po["insvc"];
							
						$rv["actlabel"] = $r_po["doutactlbl"];
						$rv["inactlabel"] = $r_po["doutinactlbl"];
						$rv["actstate"] = $r_po["doutactstate"];
							
						$actstate = $r_po["doutactstate"];
						$curstate = $r_po["doutcurstate"];
							
						if ($curstate == $actstate)
							$rv["curstate"] = $r_po["doutactlbl"];
						else 
							$rv["curstate"] = $r_po["doutinactlbl"];
					}
					$s_po->free();
				}
				break;
		}
	
		return $rv;
	}
	
	
	/**
	 * @return array. DOUT list array. [0] element is composite containing "doutid|douttype". The name has a * appended for virtual DOUT points.
	 * @param resource $dbh. SQL db connection
	 * @param bool $fittedonly. Only return those points marked as 'fitted' when reading a physical device table
	 * @param string $agencyid. The agency that owns the points
	 * @desc Reads DOUT tables and returns a list array.
	 */
	public function
	getdoutlist($dbh, $fittedonly = true, $agencyid)
	{
		$rv = array();
		$n = 0;	
		
		// Real DOUT points
		$qd = "select "
			. "\n xdevname, "
			. "\n xdoutid, "
			. "\n doutname "
			. "\n from xdout "
			. "\n left join xdevice on xdevice.xdid=xdout.xdid "
			. "\n where xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
			;
		if ($fittedonly === true)
			$qd .= "\n and isfitted=1 ";
		$qd .= "\n order by doutname ";
			
		$sd = $dbh->query($qd);
		if ($sd)
		{
			while ($rd = $sd->fetch_assoc())
			{
				$rv[$n][0] = $rd["xdoutid"]."|".DOTYPE_REAL;
				$rv[$n][1] = $rd["doutname"]." (".$rd["xdevname"].")";
				$n++;
			}
			$sd->free();
		}
		
		// Virtual DOUT points
		$qd = "select "
			. "\n lvdoutid, "
			. "\n xdevname, "
			. "\n doutname "
			. "\n from lvdout "
			. "\n left join xdevice on xdevice.xdid=lvdout.xdid "
			. "\n where lvdout.agencyid='".$dbh->real_escape_string($agencyid)."' "
			. "\n or xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
			;

		$sd = $dbh->query($qd);
		if ($sd)
		{
			while ($rd = $sd->fetch_assoc())
			{
				$rv[$n][0] = $rd["lvdoutid"]."|".DOTYPE_LV;
				$rv[$n][1] = $rd["doutname"]."*";
				if (isset($rd["xdevname"]))
					$rv[$n][1] .= " (".$rd["xdevname"].")";
				$n++;
			}
			$sd->free();
		}
				
		return $rv;
	}
	
	
	/**
	 * @return array of stimulus point parameters [name],[insvc],[actlabel],[inactlabel],[actstate],[curstate]
	 * @param resource $dbh. SQL db connection
	 * @param int $type. The point type indicator
	 * @param int $id. The index ID of the point
	 * @desc Reads the stimulus input parameters from the appropriate table based on the type specified
	 */
	public function
	getinput($dbh, $type, $id)
	{
		$rv = false;
		
		switch ($type)
		{
			case XCR_PTYPE_REALDIN:
					$q_pi = "select dinname, "
						. "\n dinactstate, "
						. "\n dincurstate, "
						. "\n dinactlabel, "
						. "\n dininactlabel, "
						. "\n insvc "
						. "\n from xdin "
						. "\n where xdinid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["dinname"];
							$rv["insvc"] = $r_pi["insvc"];
							$rv["actstate"] = $r_pi["dinactstate"];
							$rv["curstate"] = $r_pi["dincurstate"];
							$rv["actlabel"] = $r_pi["dinactlabel"];
							$rv["inactlabel"] = $r_pi["dininactlabel"];
						}
						$s_pi->free();
					}
					break;
					
			case XCR_PTYPE_REALDIN3:
					$q_pi = "select dinname, "
						. "\n dinacthstate, "
						. "\n dinactlstate, "
						. "\n dincurstate, "
						. "\n dininactlabel, "
						. "\n dinactllabel, "
						. "\n dinacthlabel, "
						. "\n insvc "
						. "\n from xdin3 "
						. "\n where xdin3id='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["dinname"];
							$rv["insvc"] = $r_pi["insvc"];
							$rv["acthstate"] = $r_pi["dinacthstate"];
							$rv["actlstate"] = $r_pi["dinactlstate"];
							$rv["curstate"] = $r_pi["dincurstate"];
							$rv["inactlabel"] = $r_pi["dininactlabel"];
							$rv["acthlabel"] = $r_pi["dinacthlabel"];
							$rv["actllabel"] = $r_pi["dinactllabel"];
						}
						$s_pi->free();
					}
					break;
				
			case XCR_PTYPE_LVDIN:
					$q_pi = "select dinname, "
						. "\n dinactstate, "
						. "\n dincurstate, "
						. "\n dinactlabel, "
						. "\n dininactlabel, "
						. "\n insvc "
						. "\n from lvdin "
						. "\n where lvdinid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["dinname"];
							$rv["insvc"] = $r_pi["insvc"];
							$rv["actstate"] = $r_pi["dinactstate"];
							$rv["curstate"] = $r_pi["dincurstate"];
							$rv["actlabel"] = $r_pi["dinactlabel"];
							$rv["inactlabel"] = $r_pi["dininactlabel"];
						}
						$s_pi->free();
					}
					break;
							
			case XCR_PTYPE_REALDOUT:
					$q_pi = "select doutname, "
						. "\n doutactstate, "
						. "\n doutcurstate, "
						. "\n doutactlbl, "
						. "\n doutinactlbl, "
						. "\n insvc "
						. "\n from xdout "
						. "\n where xdoutid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["doutname"];
							$rv["insvc"] = $r_pi["insvc"];
							$rv["actstate"] = $r_pi["doutactstate"];
							$rv["curstate"] = $r_pi["doutcurstate"];
							$rv["actlabel"] = $r_pi["doutactlbl"];
							$rv["inactlabel"] = $r_pi["doutinactlbl"];
						}
						$s_pi->free();
					}
					break;
					
			case XCR_PTYPE_LVDOUT:
					$q_pi = "select doutname, "
						. "\n doutactstate, "
						. "\n doutcurstate, "
						. "\n doutactlbl, "
						. "\n doutinactlbl, "
						. "\n insvc "
						. "\n from lvdout "
						. "\n where lvdoutid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["doutname"];
							$rv["insvc"] = $r_pi["insvc"];
							$rv["actstate"] = $r_pi["doutactstate"];
							$rv["curstate"] = $r_pi["doutcurstate"];
							$rv["actlabel"] = $r_pi["doutactlbl"];
							$rv["inactlabel"] = $r_pi["doutinactlbl"];
						}
						$s_pi->free();
					}
					break;
					
			case XCR_PTYPE_AINCOMP:
					$q_pi = "select pointname, "
						. "\n xcompstate, "
						. "\n insvc "
						. "\n from xcomp "
						. "\n where xcompid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["pointname"];
							$rv["insvc"] = $r_pi["insvc"];
							$rv["actstate"] = 1;
							$rv["curstate"] = $r_pi["xcompstate"];
							$rv["actlabel"] = "on";
							$rv["inactlabel"] = "off";
						}
						$s_pi->free();
					}
					break;
					
			case XCR_PTYPE_TODTRIG:
					$q_pi = "select pointname, "
						. "\n todstate, "
						. "\n insvc "
						. "\n from todtrigger "
						. "\n where ttid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["pointname"];
							$rv["insvc"] = $r_pi["insvc"];
							$rv["actstate"] = 1;
							$rv["curstate"] = $r_pi["todstate"];
							$rv["actlabel"] = "on";
							$rv["inactlabel"] = "off";
						}
						$s_pi->free();
					}
					break;
					
			case XCR_PTYPE_TINT:
					$q_pi = "select pointname, "
						. "\n tintstate, "
						. "\n insvc "
						. "\n from xtint "
						. "\n where tintid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["pointname"];
							$rv["insvc"] = $r_pi["insvc"];
							$rv["actstate"] = 1;
							$rv["curstate"] = $r_pi["tintstate"];
							$rv["actlabel"] = "on";
							$rv["inactlabel"] = "off";
						}
						$s_pi->free();
					}
					break;
		}
	
		return $rv;
	}
	
	
	/**
	 * @return array. INPUT list array. [0] element is composite containing "id|ptype".
	 * @param resource $dbh. SQL db connection
	 * @param bool $fittedonly. Only return those points marked as 'fitted' when reading a physical device table
	 * @param string $agancyid. The agency that owns the points
	 * @desc Reads tables and returns a list array for input stimulus selection.
	 */
	public function
	getinputlist($dbh, $fittedonly = true, $agencyid)
	{
		$rv = array();
		$n = 0;

		// DIN points
		$qi = "select "
			. "\n xdevname, "
			. "\n xdinid, "
			. "\n dinname "
			. "\n from xdin "
			. "\n left join xdevice on xdevice.xdid=xdin.xdid "
			. "\n where xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
			;
		if ($fittedonly === true)
			$qi .= "\n and isfitted=1 ";
		$qi .= "\n order by dinname ";

		$si = $dbh->query($qi);
		if ($si)
		{
			while ($ri = $si->fetch_assoc())
			{
				$rv[$n][0] = $ri["xdinid"]."|".XCR_PTYPE_REALDIN;
				$rv[$n][1] = "[din] : ".$ri["dinname"]." (".$ri["xdevname"].")";
				$n++;
			}
			$si->free();
		}
		
		// DIN3 points
		$qi = "select "
			. "\n xdevname, "
			. "\n xdin3id, "
			. "\n dinname "
			. "\n from xdin3 "
			. "\n left join xdevice on xdevice.xdid=xdin3.xdid "
			. "\n where xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
			;
		if ($fittedonly === true)
			$qi .= "\n and isfitted=1 ";
		$qi .= "\n order by dinname ";

		$si = $dbh->query($qi);
		if ($si)
		{
			while ($ri = $si->fetch_assoc())
			{
				$rv[$n][0] = $ri["xdin3id"]."|".XCR_PTYPE_REALDIN3;
				$rv[$n][1] = "[din3] : ".$ri["dinname"]." (".$ri["xdevname"].")";
				$n++;
			}
			$si->free();
		}
		
		// LVDIN points
		$qi = "select "
			. "\n lvdinid, "
			. "\n xdevname, "
			. "\n dinname "
			. "\n from lvdin "
			. "\n left join xdevice on xdevice.xdid=lvdin.xdid "
			. "\n where lvdin.agencyid='".$dbh->real_escape_string($agencyid)."' "
			. "\n or xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
			. "\n order by dinname "
			;

		$si = $dbh->query($qi);
		if ($si)
		{
			while ($ri = $si->fetch_assoc())
			{
				$rv[$n][0] = $ri["lvdinid"]."|".XCR_PTYPE_LVDIN;
				$rv[$n][1] = "[lvdin] : ".$ri["dinname"];
				if (isset($ri["xdevname"]))
					$rv[$n][1] .= " (".$ri["xdevname"].")";
				$n++;
			}
			$si->free();
		}

		// DOUT points
		$qi = "select "
			. "\n xdevname, "
			. "\n xdoutid, "
			. "\n doutname "
			. "\n from xdout "
			. "\n left join xdevice on xdevice.xdid=xdout.xdid "
			. "\n where xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
			;
		if ($fittedonly === true)
			$qi .= "\n and isfitted=1 ";
		$qi .= "\n order by doutname ";

		$si = $dbh->query($qi);
		if ($si)
		{
			while ($ri = $si->fetch_assoc())
			{
				$rv[$n][0] = $ri["xdoutid"]."|".XCR_PTYPE_REALDOUT;
				$rv[$n][1] = "[dout] : ".$ri["doutname"]." (".$ri["xdevname"].")";
				$n++;
			}
			$si->free();
		}
		
		// LVDOUT points
		$qi = "select lvdoutid, "
			. "\n xdevname, "
			. "\n doutname "
			. "\n from lvdout "
			. "\n left join xdevice on xdevice.xdid=lvdout.xdid "
			. "\n where lvdout.agencyid='".$dbh->real_escape_string($agencyid)."' "
			. "\n or xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
			. "\n order by doutname "
			;

		$si = $dbh->query($qi);
		if ($si)
		{
			while ($ri = $si->fetch_assoc())
			{
				$rv[$n][0] = $ri["lvdoutid"]."|".XCR_PTYPE_LVDOUT;
				$rv[$n][1] = "[lvdout] : ".$ri["doutname"];
				if (isset($ri["xdevname"]))
					$rv[$n][1] .= " (".$ri["xdevname"].")";
				$n++;
			}
			$si->free();
		}
		
		// AINCOMP points
		$qi = "select xcompid, "
			. "\n pointname "
			. "\n from xcomp "
			. "\n where agencyid='".$dbh->real_escape_string($agencyid)."' "
			. "\n order by pointname "
			;

		$si = $dbh->query($qi);
		if ($si)
		{
			while ($ri = $si->fetch_assoc())
			{
				$rv[$n][0] = $ri["xcompid"]."|".XCR_PTYPE_AINCOMP;
				$rv[$n][1] = "[xcomp] : ".$ri["pointname"];
				$n++;
			}
			$si->free();
		}
		
		// TODTRIGGER points
		$qi = "select ttid, "
			. "\n pointname "
			. "\n from todtrigger "
			. "\n where agencyid='".$dbh->real_escape_string($agencyid)."' "
			. "\n order by pointname "
			;

		$si = $dbh->query($qi);
		if ($si)
		{
			while ($ri = $si->fetch_assoc())
			{
				$rv[$n][0] = $ri["ttid"]."|".XCR_PTYPE_TODTRIG;
				$rv[$n][1] = "[todtrig] : ".$ri["pointname"];
				$n++;
			}
			$si->free();
		}
		
		// TINT points
		$qi = "select xtintid, "
			. "\n pointname "
			. "\n from xtint "
			. "\n where agencyid='".$dbh->real_escape_string($agencyid)."' "
			. "\n order by pointname "
			;

		$si = $dbh->query($qi);
		if ($si)
		{
			while ($ri = $si->fetch_assoc())
			{
				$rv[$n][0] = $ri["xtintid"]."|".XCR_PTYPE_TINT;
				$rv[$n][1] = "[tint] : ".$ri["pointname"];
				$n++;
			}
			$si->free();
		}
		
		return $rv;
	}

	
	/**
	 * @return array. Set of parameters for XCR input display
	 * $rv[name], [insvc], [curstate], [instate]
	 * @param resource $dbh. SQL db connection
	 * @param array $type. The XCR input type. Selects the table to ghet data from
	 * @param int $id. ID of point in type table to get data from
	 * @paranm int $xcrinstate. The state the XCR requires this input point to be for a trigger.
	 * @desc Reads input state parameters accrding to the type specified.
	 */
	public function
	getxcrparams($dbh, $type, $id, $xcrinstate)
	{
		$rv = array();
		
		switch ($type)
		{
			case XCR_PTYPE_REALDIN:
					$q_pi = "select dinname, "
						. "\n dinactstate, "
						. "\n dincurstate, "
						. "\n dinactlabel, "
						. "\n dininactlabel, "
						. "\n insvc "
						. "\n from xdin "
						. "\n where xdinid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["dinname"];
							$rv["insvc"] = $r_pi["insvc"];
							
							$actstate = $r_pi["dinactstate"];
							$curstate = $r_pi["dincurstate"];
							
							if ($curstate == $actstate)
								$rv["curstate"] = $r_pi["dinactlabel"];
							else 
								$rv["curstate"] = $r_pi["dininactlabel"];
							
							if ($xcrinstate == $actstate)
								$rv["instate"] = $r_pi["dinactlabel"];
							else 
								$rv["instate"] = $r_pi["dininactlabel"];
						}
						$s_pi->free();
					}
					break;
					
			case XCR_PTYPE_REALDIN3:
					$q_pi = "select dinname, "
						. "\n dinacthstate, "
						. "\n dinactlstate, "
						. "\n dincurstate, "
						. "\n dininactlabel, "
						. "\n dinacthlabel, "
						. "\n dinactllabel, "
						. "\n insvc "
						. "\n from xdin3 "
						. "\n where xdin3id='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["dinname"];
							$rv["insvc"] = $r_pi["insvc"];
							
							$acthstate = $r_pi["dinacthstate"];
							$actlstate = $r_pi["dinactlstate"];
							$curstate = $r_pi["dincurstate"];
							
							if ($curstate == $acthstate)
								$rv["curstate"] = $r_pi["dinacthlabel"];
							else if ($curstate == $actlstate)
								$rv["curstate"] = $r_pi["dinactllabel"];
							else
								$rv["curstate"] = $r_pi["dininactlabel"];
							
							if ($xcrinstate == $acthstate)
								$rv["instate"] = $r_pi["dinacthlabel"];
							else if ($xcrinstate == $actlstate)
								$rv["instate"] = $r_pi["dinactllabel"];
							else
								$rv["instate"] = $r_pi["dininactlabel"];
						}
						$s_pi->free();
					}
					break;
			
			case XCR_PTYPE_LVDIN:
					$q_pi = "select dinname, "
						. "\n dinactstate, "
						. "\n dincurstate, "
						. "\n dinactlabel, "
						. "\n dininactlabel, "
						. "\n insvc "
						. "\n from lvdin "
						. "\n where lvdinid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["dinname"];
							$rv["insvc"] = $r_pi["insvc"];
							
							$actstate = $r_pi["dinactstate"];
							$curstate = $r_pi["dincurstate"];
							
							if ($curstate == $actstate)
								$rv["curstate"] = $r_pi["dinactlabel"];
							else 
								$rv["curstate"] = $r_pi["dininactlabel"];
							
							if ($xcrinstate == $actstate)
								$rv["instate"] = $r_pi["dinactlabel"];
							else 
								$rv["instate"] = $r_pi["dininactlabel"];
						}
						$s_pi->free();
					}
					break;
							
			case XCR_PTYPE_REALDOUT:
					$q_pi = "select doutname, "
						. "\n doutactstate, "
						. "\n doutcurstate, "
						. "\n doutactlbl, "
						. "\n doutinactlbl, "
						. "\n insvc "
						. "\n from xdout "
						. "\n where xdoutid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["doutname"];
							$rv["insvc"] = $r_pi["insvc"];
							
							$actstate = $r_pi["doutactstate"];
							$curstate = $r_pi["doutcurstate"];
							
							if ($curstate == $actstate)
								$rv["curstate"] = $r_pi["doutactlbl"];
							else 
								$rv["curstate"] = $r_pi["doutinactlbl"];
							
							if ($xcrinstate == $actstate)
								$rv["instate"] = $r_pi["doutactlbl"];
							else 
								$rv["instate"] = $r_pi["doutinactlbl"];
						}
						$s_pi->free();
					}
					break;
					
			case XCR_PTYPE_LVDOUT:
					$q_pi = "select doutname, "
						. "\n doutactstate, "
						. "\n doutcurstate, "
						. "\n doutactlbl, "
						. "\n doutinactlbl, "
						. "\n insvc "
						. "\n from lvdout "
						. "\n where lvdoutid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["doutname"];
							$rv["insvc"] = $r_pi["insvc"];
							
							$actstate = $r_pi["doutactstate"];
							$curstate = $r_pi["doutcurstate"];
							
							if ($curstate == $actstate)
								$rv["curstate"] = $r_pi["doutactlbl"];
							else 
								$rv["curstate"] = $r_pi["doutinactlbl"];
							
							if ($xcrinstate == $actstate)
								$rv["instate"] = $r_pi["doutactlbl"];
							else 
								$rv["instate"] = $r_pi["doutinactlbl"];
						}
						$s_pi->free();
					}
					break;
					
			case XCR_PTYPE_AINCOMP:
					$q_pi = "select pointname, "
						. "\n xcompstate, "
						. "\n insvc "
						. "\n from xcomp "
						. "\n where xcompid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["pointname"];
							$rv["insvc"] = $r_pi["insvc"];
							$state = $r_pi["xcompstate"];
							
							if ($state > 0)
								$rv["curstate"] = "on";
							else 
								$rv["curstate"] = "off";
							
							if ($xcrinstate > 0)
								$rv["instate"] = "on";
							else 
								$rv["instate"] = "off";
						}
						$s_pi->free();
					}
					break;
					
			case XCR_PTYPE_TODTRIG:
					$q_pi = "select pointname, "
						. "\n todstate, "
						. "\n insvc "
						. "\n from todtrigger "
						. "\n where ttid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["pointname"];
							$rv["insvc"] = $r_pi["insvc"];
							$state = $r_pi["todstate"];
	
							if ($state > 0)
								$rv["curstate"] = "on";
							else 
								$rv["curstate"] = "off";
							
							if ($xcrinstate > 0)
								$rv["instate"] = "on";
							else 
								$rv["instate"] = "off";
						}
						$s_pi->free();
					}
					break;
					
			case XCR_PTYPE_TINT:
					$q_pi = "select pointname, "
						. "\n tintstate, "
						. "\n insvc "
						. "\n from xtint "
						. "\n where tintid='".$dbh->real_escape_string($id)."' "
						;
					$s_pi = $dbh->query($q_pi);
					if ($s_pi)
					{
						$r_pi = $s_pi->fetch_assoc();
						if ($r_pi)
						{
							$rv["name"] = $r_pi["pointname"];
							$rv["insvc"] = $r_pi["insvc"];
							$state = $r_pi["tintstate"];
	
							if ($state > 0)
								$rv["curstate"] = "on";
							else 
								$rv["curstate"] = "off";
							
							if ($xcrinstate > 0)
								$rv["instate"] = "on";
							else 
								$rv["instate"] = "off";
						}
						$s_pi->free();
					}
					break;
		}
		
		return $rv;	
	}
	
	
	/**
	 * @return array. Set of points for specified point type and optional devid and fitted filters.
	 * @param resource $dbh. SQL db connection
	 * @param int $ptype. The point type. Selects the table and data to fetch
	 * @param int $f_devid. Filter using the device ID given
	 * @paranm int $f_isfitted. Filter to return just the fitted points
	 * @desc Reads point data for the ptype specified.
	 */
	public function
	getiopageset($dbh, $ptype, $agencyid, $f_devid = false, $f_isfitted = false)
	{
		$rv = array();
		$nd = 0;
		
		switch($ptype)
		{
			case XIO_PTYPE_REALDIN:
					$q_xio = "select "
						. "\n xdevid, "
						. "\n xdevname, "
						. "\n xdevice.insvc as xdevinsvc, "
						. "\n xdinid, "
						. "\n xdin.insvc as xdininsvc, "
						. "\n dinchan, "
						. "\n dinname, "
						. "\n dindesc, "
						. "\n dinactlabel, "
						. "\n dininactlabel, "
						. "\n dinactstate, "
						. "\n dincurstate "
						. "\n from xdin "
						. "\n left join xdevice on xdevice.xdid=xdin.xdid "
						. "\n where xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
						;
					if ($f_devid !== false)
						$q_xio .= "\n and xdevid='".$dbh->real_escape_string($f_devid)."' ";
					if ($f_isfitted == 1)
						$q_xio .= "\n and isfitted=1 ";
					$s_xio = $dbh->query($q_xio);
					if ($s_xio)
					{
						while ($r_xio = $s_xio->fetch_assoc())
						{
							$rv[$nd]["xdevid"] = $r_xio["xdevid"];
							$rv[$nd]["devname"] = $r_xio["xdevname"];
							$rv[$nd]["devinsvc"] = $r_xio["xdevinsvc"];
							$rv[$nd]["pointid"] = $r_xio["xdinid"];
							$rv[$nd]["ptype"] = $ptype;
							$rv[$nd]["pinsvc"] = $r_xio["xdininsvc"];
							$rv[$nd]["pchan"] = $r_xio["dinchan"];
							$rv[$nd]["pname"] = $r_xio["dinname"];
							$rv[$nd]["pdesc"] = $r_xio["dindesc"];
							$rv[$nd]["actlabel"] = $r_xio["dinactlabel"];
							$rv[$nd]["inactlabel"] = $r_xio["dininactlabel"];
							$rv[$nd]["actstate"] = $r_xio["dinactstate"];
							$rv[$nd]["curstate"] = $r_xio["dincurstate"];
							
							$nd++;
						}
						$s_xio->free();		
					}
					break;
					
			case XIO_PTYPE_REALDIN3:
					$q_xio = "select "
						. "\n xdevid, "
						. "\n xdevname, "
						. "\n xdevice.insvc as xdevinsvc, "
						. "\n xdin3id, "
						. "\n xdin3.insvc as xdininsvc, "
						. "\n dinchan, "
						. "\n dinname, "
						. "\n dindesc, "
						. "\n dinacthlabel, "
						. "\n dinactllabel, "
						. "\n dininactlabel, "
						. "\n dinacthstate, "
						. "\n dinactlstate, "
						. "\n dincurstate "
						. "\n from xdin3 "
						. "\n left join xdevice on xdevice.xdid=xdin3.xdid "
						. "\n where xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
						;
					if ($f_devid !== false)
						$q_xio .= "\n and xdevid='".$dbh->real_escape_string($f_devid)."' ";
					if ($f_isfitted == 1)
						$q_xio .= "\n and isfitted=1 ";
					$s_xio = $dbh->query($q_xio);
					if ($s_xio)
					{
						while ($r_xio = $s_xio->fetch_assoc())
						{
							$rv[$nd]["xdevid"] = $r_xio["xdevid"];
							$rv[$nd]["devname"] = $r_xio["xdevname"];
							$rv[$nd]["devinsvc"] = $r_xio["xdevinsvc"];
							$rv[$nd]["pointid"] = $r_xio["xdin3id"];
							$rv[$nd]["ptype"] = $ptype;
							$rv[$nd]["pinsvc"] = $r_xio["xdininsvc"];
							$rv[$nd]["pchan"] = $r_xio["dinchan"];
							$rv[$nd]["pname"] = $r_xio["dinname"];
							$rv[$nd]["pdesc"] = $r_xio["dindesc"];
							$rv[$nd]["acthlabel"] = $r_xio["dinacthlabel"];
							$rv[$nd]["actllabel"] = $r_xio["dinactllabel"];
							$rv[$nd]["inactlabel"] = $r_xio["dininactlabel"];
							$rv[$nd]["acthstate"] = $r_xio["dinacthstate"];
							$rv[$nd]["actlstate"] = $r_xio["dinactlstate"];
							$rv[$nd]["curstate"] = $r_xio["dincurstate"];
							
							$nd++;
						}
						$s_xio->free();		
					}
					break;
					
			case XIO_PTYPE_LVDIN:
					$q_xio = "select "
						. "\n xdevid, "
						. "\n xdevname, "
						. "\n xdevice.insvc as xdevinsvc, "
						. "\n lvdinid, "
						. "\n lvdin.insvc as lvdininsvc, "
						. "\n dinchan, "
						. "\n dinname, "
						. "\n dindesc, "
						. "\n dinactlabel, "
						. "\n dininactlabel, "
						. "\n dinactstate, "
						. "\n dincurstate "
						. "\n from lvdin "
						. "\n left join xdevice on xdevice.xdid=lvdin.xdid "
						. "\n where lvdin.agencyid='".$dbh->real_escape_string($agencyid)."' "
						;
					if ($f_devid !== false)
						$q_xio .= "\n and xdevid='".$dbh->real_escape_string($f_devid)."' ";
					$s_xio = $dbh->query($q_xio);
					if ($s_xio)
					{
						while ($r_xio = $s_xio->fetch_assoc())
						{
							$rv[$nd]["xdevid"] = $r_xio["xdevid"];
							$rv[$nd]["devname"] = $r_xio["xdevname"];
							$rv[$nd]["devinsvc"] = $r_xio["xdevinsvc"];
							$rv[$nd]["pointid"] = $r_xio["lvdinid"];
							$rv[$nd]["ptype"] = $ptype;
							$rv[$nd]["pinsvc"] = $r_xio["lvdininsvc"];
							$rv[$nd]["pchan"] = $r_xio["dinchan"];
							$rv[$nd]["pname"] = $r_xio["dinname"];
							$rv[$nd]["pdesc"] = $r_xio["dindesc"];
							$rv[$nd]["actlabel"] = $r_xio["dinactlabel"];
							$rv[$nd]["inactlabel"] = $r_xio["dininactlabel"];
							$rv[$nd]["actstate"] = $r_xio["dinactstate"];
							$rv[$nd]["curstate"] = $r_xio["dincurstate"];
							
							$nd++;
						}
						$s_xio->free();		
					}
					break;
					
			case XIO_PTYPE_REALDOUT:
					$q_xio = "select "
						. "\n xdevid, "
						. "\n xdevname, "
						. "\n xdevice.insvc as xdevinsvc, "
						. "\n xdoutid, "
						. "\n xdout.insvc as xdoutinsvc, "
						. "\n doutchan, "
						. "\n doutname, "
						. "\n doutdesc, "
						. "\n doutactlbl, "
						. "\n doutinactlbl, "
						. "\n doutactstate, "
						. "\n doutcurstate "
						. "\n from xdout "
						. "\n left join xdevice on xdevice.xdid=xdout.xdid "
						. "\n where xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
						;
					if ($f_devid !== false)
						$q_xio .= "\n and xdevid='".$dbh->real_escape_string($f_devid)."' ";
					if ($f_isfitted == 1)
						$q_xio .= "\n and isfitted=1 ";
					$s_xio = $dbh->query($q_xio);
					if ($s_xio)
					{
						while ($r_xio = $s_xio->fetch_assoc())
						{
							$rv[$nd]["xdevid"] = $r_xio["xdevid"];
							$rv[$nd]["devname"] = $r_xio["xdevname"];
							$rv[$nd]["devinsvc"] = $r_xio["xdevinsvc"];
							$rv[$nd]["pointid"] = $r_xio["xdoutid"];
							$rv[$nd]["ptype"] = $ptype;
							$rv[$nd]["pinsvc"] = $r_xio["xdoutinsvc"];
							$rv[$nd]["pchan"] = $r_xio["doutchan"];
							$rv[$nd]["pname"] = $r_xio["doutname"];
							$rv[$nd]["pdesc"] = $r_xio["doutdesc"];
							$rv[$nd]["actlabel"] = $r_xio["doutactlbl"];
							$rv[$nd]["inactlabel"] = $r_xio["doutinactlbl"];
							$rv[$nd]["actstate"] = $r_xio["doutactstate"];
							$rv[$nd]["curstate"] = $r_xio["doutcurstate"];
							
							$nd++;
						}
						$s_xio->free();		
					}
					break;
					
			case XIO_PTYPE_LVDOUT:
					$q_xio = "select "
						. "\n xdevid, "
						. "\n xdevname, "
						. "\n xdevice.insvc as xdevinsvc, "
						. "\n lvdoutid, "
						. "\n lvdout.insvc as lvdoutinsvc, "
						. "\n doutchan, "
						. "\n doutname, "
						. "\n doutdesc, "
						. "\n doutactlbl, "
						. "\n doutinactlbl, "
						. "\n doutactstate, "
						. "\n doutcurstate "
						. "\n from lvdout "
						. "\n left join xdevice on xdevice.xdid=lvdout.xdid "
						. "\n where lvdout.agencyid='".$dbh->real_escape_string($agencyid)."' "
						;
					if ($f_devid !== false)
						$q_xio .= "\n and xdevid='".$dbh->real_escape_string($f_devid)."' ";
					$s_xio = $dbh->query($q_xio);
					if ($s_xio)
					{
						while ($r_xio = $s_xio->fetch_assoc())
						{
							$rv[$nd]["xdevid"] = $r_xio["xdevid"];
							$rv[$nd]["devname"] = $r_xio["xdevname"];
							$rv[$nd]["devinsvc"] = $r_xio["xdevinsvc"];
							$rv[$nd]["pointid"] = $r_xio["lvdoutid"];
							$rv[$nd]["ptype"] = $ptype;
							$rv[$nd]["pinsvc"] = $r_xio["lvdoutinsvc"];
							$rv[$nd]["pchan"] = $r_xio["doutchan"];
							$rv[$nd]["pname"] = $r_xio["doutname"];
							$rv[$nd]["pdesc"] = $r_xio["doutdesc"];
							$rv[$nd]["actlabel"] = $r_xio["doutactlbl"];
							$rv[$nd]["inactlabel"] = $r_xio["doutinactlbl"];
							$rv[$nd]["actstate"] = $r_xio["doutactstate"];
							$rv[$nd]["curstate"] = $r_xio["doutcurstate"];
							
							$nd++;
						}
						$s_xio->free();		
					}
					break;
					
			case XIO_PTYPE_AINCOMP:
					$q_xio = "select "
						. "\n xcompid, "
						. "\n insvc, "
						. "\n pointname, "
						. "\n xcompref, "
						. "\n hysteresis, "
						. "\n threshhighname, "
						. "\n threshlowname, "
						. "\n xcompstate "
						. "\n from xcomp "
						. "\n where agencyid='".$dbh->real_escape_string($agencyid)."' "
						;
					$s_xio = $dbh->query($q_xio);
					if ($s_xio)
					{
						while ($r_xio = $s_xio->fetch_assoc())
						{
							$rv[$nd]["pointid"] = $r_xio["xcompid"];
							$rv[$nd]["ptype"] = $ptype;
							$rv[$nd]["pinsvc"] = $r_xio["insvc"];
							$rv[$nd]["reference"] = $r_xio["xcompref"];
							$rv[$nd]["hysteresis"] = $r_xio["hysteresis"];
							$rv[$nd]["pname"] = $r_xio["pointname"];
							$rv[$nd]["actlabel"] = $r_xio["threshhighname"];
							$rv[$nd]["inactlabel"] = $r_xio["threshlowname"];
							$rv[$nd]["actstate"] = 1;
							$rv[$nd]["curstate"] = $r_xio["xcompstate"];
							
							$nd++;
						}
						$s_xio->free();		
					}
					break;
					
			case XIO_PTYPE_TODTRIG:
					$q_xio = "select "
						. "\n ttid, "
						. "\n insvc, "
						. "\n pointname, "
						. "\n trigdate_y, "
						. "\n trigdate_m, "
						. "\n trigdate_d, "
						. "\n trigdate_h, "
						. "\n trigdate_i, "
						. "\n trigdow, "
						. "\n todstate "
						. "\n from todtrigger "
						. "\n where agencyid='".$dbh->real_escape_string($agencyid)."' "
						;
					$s_xio = $dbh->query($q_xio);
					if ($s_xio)
					{
						while ($r_xio = $s_xio->fetch_assoc())
						{
							$rv[$nd]["pointid"] = $r_xio["ttid"];
							$rv[$nd]["ptype"] = $ptype;
							$rv[$nd]["pinsvc"] = $r_xio["insvc"];
							$rv[$nd]["pname"] = $r_xio["pointname"];
							$rv[$nd]["trigdate"] = $r_xio["trigdate_y"]."-".$r_xio["trigdate_m"]."-".$r_xio["trigdate_d"];
							$rv[$nd]["trigtime"] = $r_xio["trigdate_h"].":".$r_xio["trigdate_i"];
							$rv[$nd]["trigdow"] = $r_xio["trigdow"];
							$rv[$nd]["actstate"] = 1;
							$rv[$nd]["curstate"] = $r_xio["todstate"];
							
							$nd++;
						}
						$s_xio->free();		
					}
					break;
					
			case XIO_PTYPE_TINT:
					$q_xio = "select "
						. "\n xtintid, "
						. "\n insvc, "
						. "\n pointname, "
						. "\n timestart, "
						. "\n duration, "
						. "\n remaining, "
						. "\n tintstate "
						. "\n from xtint "
						. "\n where agencyid='".$dbh->real_escape_string($agencyid)."' "
						;
					$s_xio = $dbh->query($q_xio);
					if ($s_xio)
					{
						while ($r_xio = $s_xio->fetch_assoc())
						{
							$rv[$nd]["pointid"] = $r_xio["ttid"];
							$rv[$nd]["ptype"] = $ptype;
							$rv[$nd]["pinsvc"] = $r_xio["insvc"];
							$rv[$nd]["pname"] = $r_xio["pointname"];
							$rv[$nd]["timestart"] = $r_xio["timestart"];
							$rv[$nd]["duration"] = $r_xio["duration"];
							$rv[$nd]["remaining"] = $r_xio["remaining"];
							$rv[$nd]["actstate"] = 1;
							$rv[$nd]["curstate"] = $r_xio["tintstate"];
							
							$nd++;
						}
						$s_xio->free();		
					}
					break;
					
			case XIO_PTYPE_AIN:
					$q_xio = "select "
						. "\n xdevid, "
						. "\n xdevname, "
						. "\n xdevice.insvc as xdevinsvc, "
						. "\n xainid, "
						. "\n xain.insvc as xaininsvc, "
						. "\n ainchan, "
						. "\n ainname, "
						. "\n aindesc, "
						. "\n ainrawval, "
						. "\n ainavgval "
						. "\n from xain "
						. "\n left join xdevice on xdevice.xdid=xain.xdid "
						. "\n where xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' "
						;
					if ($f_devid !== false)
						$q_xio .= "\n and xdevid='".$dbh->real_escape_string($f_devid)."' ";
					if ($f_isfitted == 1)
						$q_xio .= "\n and isfitted=1 ";
					$s_xio = $dbh->query($q_xio);
					if ($s_xio)
					{
						while ($r_xio = $s_xio->fetch_assoc())
						{
							$rv[$nd]["xdevid"] = $r_xio["xdevid"];
							$rv[$nd]["devname"] = $r_xio["xdevname"];
							$rv[$nd]["devinsvc"] = $r_xio["xdevinsvc"];
							$rv[$nd]["pointid"] = $r_xio["xainid"];
							$rv[$nd]["ptype"] = $ptype;
							$rv[$nd]["pinsvc"] = $r_xio["xaininsvc"];
							$rv[$nd]["pchan"] = $r_xio["ainchan"];
							$rv[$nd]["pname"] = $r_xio["ainname"];
							$rv[$nd]["pdesc"] = $r_xio["aindesc"];
							$rv[$nd]["ainrawval"] = $r_xio["ainrawval"];
							$rv[$nd]["ainavgval"] = $r_xio["ainavgval"];
							
							$nd++;
						}
						$s_xio->free();		
					}
					break;
		}
		
		return $rv;
	}
	
	
	/**
	 * @return array. Device list array. [n][0]=devid, [n][1]=devname
	 * @param resource $dbh. SQL db connection
	 * @param string $agancyid. The agency that owns the device
	 * @desc Reads a list of agency devices and returns the list array
	 */
	public function
	getdevicelist($dbh, $agencyid)
	{
		$rv = array();
		$n = 0;
		
		$qd = "select "
			. "\n xdevid, "
			. "\n xdevname "
			. "\n from xdevice "
			. "\n where agencyid='".$dbh->real_escape_string($agencyid)."' "
			;
		$sd = $dbh->query($qd);
		if ($sd)
		{
			while ($rd = $sd->fetch_assoc())
			{
				$rv[$n][0] = $rd["xdevid"];
				$rv[$n][1] = $rd["xdevname"];
				$n++;
			}
			$sd->free();
		}
		
		return $rv;
	}
	
	
	/**
	 * @return bool. True if the specified agency own the point, false otherwise
	 * @param resource $dbh. SQL db connection
	 * @param string $agancyid. The agency that owns the point
	 * @param int $ptype. The point type
	 * @param int $pid. The point row ID
	 * @desc Checks that the agency owns the point
	 */
	public function
	agencyownspoint($dbh, $agencyid, $ptype, $pid)
	{
		$rv = false;
		
		switch($ptype)
		{
			case XIO_PTYPE_REALDIN:
					$q = "select count(*) as pc from xdin left join xdevice on xdevice.xdid=xdin.xdid where xdinid='".$dbh->real_escape_string($pid)."' and xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r["pc"] > 0)
							$rv = true;
						$s->free();
					}
					break;
					
			case XIO_PTYPE_REALDIN3:
					$q = "select count(*) as pc from xdin3 left join xdevice on xdevice.xdid=xdin3.xdid where xdin3id='".$dbh->real_escape_string($pid)."' and xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r["pc"] > 0)
							$rv = true;
						$s->free();
					}
					break;
			
			case XIO_PTYPE_LVDIN:
					$q = "select count(*) as pc from lvdin where lvdinid='".$dbh->real_escape_string($pid)."' and agencyid='".$dbh->real_escape_string($agencyid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r["pc"] > 0)
							$rv = true;
						$s->free();
					}
					break;
			
			case XIO_PTYPE_REALDOUT:
					$q = "select count(*) as pc from xdout left join xdevice on xdevice.xdid=xdout.xdid where xdoutid='".$dbh->real_escape_string($pid)."' and xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r["pc"] > 0)
							$rv = true;
						$s->free();
					}
					break;
			
			case XIO_PTYPE_LVDOUT:
					$q = "select count(*) as pc from lvdout left join xdevice on xdevice.xdid=lvdout.xdid where lvdoutid='".$dbh->real_escape_string($pid)."' and xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r["pc"] > 0)
							$rv = true;
						$s->free();
					}
					break;
			
			case XIO_PTYPE_AIN:
					$q = "select count(*) as pc from xain left join xdevice on xdevice.xdid=xain.xdid where xainid='".$dbh->real_escape_string($pid)."' and xdevice.agencyid='".$dbh->real_escape_string($agencyid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r["pc"] > 0)
							$rv = true;
						$s->free();
					}
					break;
			
			case XIO_PTYPE_AINCOMP:
					$q = "select count(*) as pc from xcomp where xcompid='".$dbh->real_escape_string($pid)."' and agencyid='".$dbh->real_escape_string($agencyid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r["pc"] > 0)
							$rv = true;
						$s->free();
					}
					break;
			
			case XIO_PTYPE_TODTRIG:
					$q = "select count(*) as pc from todtrigger where ttid='".$dbh->real_escape_string($pid)."' and agencyid='".$dbh->real_escape_string($agencyid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r["pc"] > 0)
							$rv = true;
						$s->free();
					}
					break;
			
			case XIO_PTYPE_TINT:
					$q = "select count(*) as pc from xtint where xtintid='".$dbh->real_escape_string($pid)."' and agencyid='".$dbh->real_escape_string($agencyid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r["pc"] > 0)
							$rv = true;
						$s->free();
					}
					break;
		}
		
		return $rv;
	}
	
	
	/**
	 * @return bool. True if the specified point is in use in any XCR, false otherwise
	 * @param resource $dbh. SQL db connection
	 * @param int $ptype. The point type
	 * @param int $pid. The point row ID
	 * @desc Checks whether the point specified is still being used in any XCR
	 */
	public function
	pointinuse($dbh, $ptype, $pid)
	{
		$rv = false;
		
		// Check XCR input tables first
		$q = "select count(*) as pc from xcrinput where ptype='".$dbh->real_escape_string($ptype)."' and inid='".$dbh->real_escape_string($pid)."' ";
		$s = $dbh->query($q);
		if ($s)
		{
			$r = $s->fetch_assoc();
			if ($r)
			{
				if ($r["pc"] > 0)
					$rv = true;
			}
			$s->free();
		}

		// Check XCR output tables for a couple of point types if not used yet
		if ($rv === false)
		{
			if ($ptype == XIO_PTYPE_REALDOUT || $ptype == XIO_PTYPE_LVDOUT)
			{
				$q = "select count(*) as pc from xcroutput where douttype='".$dbh->real_escape_string($ptype)."' and doutid='".$dbh->real_escape_string($pid)."' ";
				$s = $dbh->query($q);
				if ($s)
				{
					$r = $s->fetch_assoc();
					if ($r)
					{
						if ($r["pc"] > 0)
							$rv = true;
					}
					$s->free();
				}
			}
		}
		
		return $rv;
	}
	
	
	/**
	 * @return bool. True if the specified LV point is owned by a device, false otherwise
	 * @param resource $dbh. SQL db connection
	 * @param int $ptype. The point type
	 * @param int $pid. The point row ID
	 * @desc Checks whether the LV point specified is owned by a device
	 */
	public function
	lvpointbelongstodevice($dbh, $ptype, $pid)
	{
		$rv = false;
		
		switch ($ptype)
		{
			case XIO_PTYPE_LVDIN:
					$q = "select xdid from lvdin where lvdinid='".$dbh->real_escape_string($pid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r)
						{
							if ($r["xdid"] > 0)
								$rv = true;
						}
						$s->free();
					}
					break;
		
			case XIO_PTYPE_LVDOUT:
					$q = "select xdid from lvdout where lvdoutid='".$dbh->real_escape_string($pid)."' ";
					$s = $dbh->query($q);
					if ($s)
					{
						$r = $s->fetch_assoc();
						if ($r)
						{
							if ($r["xdid"] > 0)
								$rv = true;
						}
						$s->free();
					}
					break;
		}
		
		return $rv;
	}
	
	
	/**
	 * @return void
	 * @param resource $dbh. SQL db connection
	 * @param int $ptype. The point type
	 * @param int $pid. The point row ID
	 * @desc Deletes the point from the DB if it is a virtual type.
	 */
	public function
	deletelvpoint($dbh, $ptype, $pid)
	{
		if ($pid < 1 || $pid == "")
			return;
			
		switch ($ptype)
		{
			case XIO_PTYPE_LVDIN:
					$q = "delete from lvdin where lvdinid='".$dbh->real_escape_string($pid)."' limit 1";
					$s = $dbh->query($q);
					break;
					
			case XIO_PTYPE_LVDOUT:
					$q = "delete from lvdout where lvdoutid='".$dbh->real_escape_string($pid)."' limit 1";
					$s = $dbh->query($q);
					break;
		}
	}
	
	
	/**
	 * @return array of errorstack ([0]=errnum, [1]=errmsg) or false if empty
	 * @desc Reads the errorstack array and returns it if it contains anything
	 */
	public function 
	readerrorstack()
	{
		if (count($this->errorstack) > 0)
			return $this->errorstack;
		else
			return false;
	}
	
	
}	
	
?>