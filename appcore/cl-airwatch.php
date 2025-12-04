<?php
//************************************************
// Copyright © 2014 XTec Inc. All Rights Reserved.
//************************************************

// $Id:$
// airwatch class: Standalone class to perform airwatch web-API interactions

interface iairwatch
{
	public function aw_getidsetforuser($cfg_params, $httpheaders, $username);
	public function aw_geruserinfo($cfg_params, $httpheaders, $username);
	public function aw_uploadp12cert($cfg_params, $httpheaders, $awuserid, $cert_b64, $cert_passwd);
	public function aw_pushdeviceprofile($cfg_params, $httpheaders, $awdeviceid, $awtagid);
	public function aw_removedeviceprofile($cfg_params, $httpheaders, $awdeviceid, $awtagid);
	public function aw_derivedcertuidcounter($ldbh, $ucdn, $ctype, $preexpirytime);
	public function aw_locate_device($devices, $awuserid, $awdevid);
	public function aw_getcsr_openssl($subjectname);	
	public function aw_request_certfromgw($cfg_params, $httpheaders, $caconnection, $certparams, $dbg);
	public function aw_create_p12cert($pemkey, $passphrase, $pemcert);
	public function aw_createderivedcerttable($ldbh, $tdnset, $cc, $ctype, $cfg_cert_container);
	public function aw_getcertdata($pemcert);
	public function aw_certformat($ct, $cert);
	public function aw_derivedcertsundertoken($ldbh, $tdn, $prefix, $suffix);
	public function aw_makepassword($len);
	public function aw_derivedcertcnchanged($ldbh, $ucdn, $ctype, $subjcn, $issby, $fn, $ln);
	
}

class airwatch implements iairwatch
{
	public function
	__construct()
	{
		return;
	}

	public function
	__destruct()
	{
		return;
	}

	// ******** PROPERTIES


	// ******** PRIVATE METHODS

	private function
	buildcabodyxml($certparams, $cfg_params, $caconnection, $add_sig = false)
	{		
		if ( isset($certparams["pemcsr"]))
		{
			$pemcsr = $certparams["pemcsr"];

 			// Strip out the PEM delimiters
			$pemcsr = str_ireplace("-----BEGIN CERTIFICATE REQUEST-----", "", $pemcsr);
			$pemcsr = str_ireplace("-----END CERTIFICATE REQUEST-----", "", $pemcsr);

			// remove extraneous white space
			$slen = strlen($pemcsr);
			$cleanstr = "";
			for ($i = 0; $i < $slen; $i++)
			{
				if (ord($pemcsr[$i]) == 32)
					continue;
				elseif ($pemcsr[$i] == "\n")
					continue;
				$cleanstr .= $pemcsr[$i];
			}
			$pemcsr = $cleanstr;
		}
		else
		{

			return "";
		}

		$ca_origin = $cfg_params[$caconnection]["origin"];
		$ca_req = $cfg_params[$caconnection]["careq"];
		// Wobbly interface with CA gateway system A5
		$ca_body = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>";
		$ca_body .= "<ServiceRequest>";
		$ca_body .= "<ServiceType>certificateManagement</ServiceType>";
                $ca_body .= "<version>2.3</version>";
		$ca_body .= "<ReqID>".$certparams["reqid"]."</ReqID>";
		$ca_body .= "<Timestamp>".gmdate("Y-m-d\TH:i:s\Z")."</Timestamp>";

		$PKIMessage = "";
		$PKIMessage .= "<PKIMessage>";
		$PKIMessage .= "<PKIMessageType>certificationRequest</PKIMessageType>";
		$PKIMessage .= "<messageID></messageID>";
		$PKIMessage .= "<PKIData>";
		$PKIMessage .= "<PKIHeader>";
		$PKIMessage .= "<pvno>3</pvno>";
		$PKIMessage .= "<sender>".$ca_origin."</sender>";
		$PKIMessage .= "<caRequest>".$ca_req."</caRequest>";
		$PKIMessage .= "<messageTime>".gmdate("Y-m-d\TH:i:s\Z")."</messageTime>";
		$PKIMessage .= "<protectionAlgo/>";
		$PKIMessage .= "<transactionID>".$certparams["reqid"]."</transactionID>";
		$PKIMessage .= "</PKIHeader>";
		$PKIMessage .= "<PKIAuth>";
		$PKIMessage .= "<sender/>";
		$PKIMessage .= "<authCode/>";
		$PKIMessage .= "<referenceNumber/>";
		$PKIMessage .= "<publicKeyMAC/>";
		$PKIMessage .= "<dnHash/>";
		$PKIMessage .= "<senderIDMAC/>";
		$PKIMessage .= "</PKIAuth>";
		$PKIMessage .= "<PKIBody>";
		$PKIMessage .= "<ControlInfo>";
		$PKIMessage .= "<OperatonControls>";
		if (isset($certparams["opcontrols"]))
		{
			foreach($certparams["opcontrols"] as $c)
			{
				$PKIMessage .= "<control>";
				$PKIMessage .= "<name>".$c["name"]."</name>";
				$PKIMessage .= "<value>".$c["val"]."</value>";
				$PKIMessage .= "</control>";
			}
		}
		$PKIMessage .= "</OperatonControls>";
//---------------
		$PKIMessage .= "<FunctionControls>";
		if (isset($certparams["fncontrols"]))
		{
			foreach($certparams["fncontrols"] as $c)
			{
				$PKIMessage .= "<control>";
				$PKIMessage .= "<name>".$c["name"]."</name>";
				$PKIMessage .= "<value>".$c["val"]."</value>";
				$PKIMessage .= "</control>";
			}
		}
//		$PKIMessage .= "<control>";
//		$PKIMessage .= "<name>certClass</name>";
//		$PKIMessage .= "<value>".$certparams["tokenclass"]."</value>";
//		$PKIMessage .= "</control>";
//		$PKIMessage .= "<control>";
//		$PKIMessage .= "<name>certType</name>";
//		$PKIMessage .= "<value>".$certparams["cert_type"]."</value>";
//		$PKIMessage .= "</control>";
		$PKIMessage .= "</FunctionControls>";
		$PKIMessage .= "</ControlInfo>";

		$PKIMessage .= '<registrationInfo>';

//------------------
		if (isset($certparams["reginfo"]))
		{
			foreach($certparams["reginfo"] as $rname => $rval)
			{
				if ($rval != "")
				{
					$PKIMessage .= "<regItem>";
					$PKIMessage .= "<name>".$rname."</name>";
					$PKIMessage .= "<value>".$rval."</value>";
					$PKIMessage .= "</regItem>";
				}
			}
		}

		$PKIMessage .= "<regItem>";
		$PKIMessage .= "<name>validity</name>";
		$PKIMessage .= "<valueset>";
		$PKIMessage .= "<notBefore>".$certparams["notBeforeDate"]."</notBefore>";
		$PKIMessage .= "<notAfter>".$certparams["notAfterDate"]."</notAfter>";
		$PKIMessage .= "</valueset>";
		$PKIMessage .= "</regItem>";

		$PKIMessage .= "<regItem>";
		$PKIMessage .= "<name>subjectAltName</name>";
		$PKIMessage .= "<valueset>";
		if (isset($certparams["subj_email"]))
		{
			if (!is_array($certparams["subj_email"]))
				$certparams["subj_email"] = array($certparams["subj_email"]);

			foreach($certparams["subj_email"] as $email)
			{
				if ($email != "")
					$PKIMessage .= "<email>".$email."</email>";
			}
		}

                if (isset($certparams["subj_upn"]))
                {
                        if (!is_array($certparams["subj_upn"]))
                                $certparams["subj_upn"] = array($certparams["subj_upn"]);

                        foreach($certparams["subj_upn"] as $upn)
                        {
                                if ($upn != "")
                                        $PKIMessage .= "<upn>".$upn."</upn>";
                        }
                }


		if (isset($certparams["subj_guid"]))
		{
			if ($certparams["subj_guid"] != "")
				$PKIMessage .= "<guid>urn:uuid:".$certparams["subj_guid"]."</guid>";
		}
		if (isset($certparams["regother"]))
		{
			foreach($certparams["regother"] as $oset)
			{
				if (is_array($oset))
				{
					$PKIMessage .= "<other>";
					$PKIMessage .= "<type>".$oset["type"]."</type>";
					$PKIMessage .= "<oid>".$oset["oid"]."</oid>";
					$PKIMessage .= "<value>".$oset["value"]."</value>";
					$PKIMessage .= "</other>";
				}
			}
		}
		$PKIMessage .= "</valueset>";
		$PKIMessage .= "</regItem>";
		$PKIMessage .= "</registrationInfo>";
//-----------------------------
		$PKIMessage .= "<certificationRequest>".$pemcsr."</certificationRequest>";
		$PKIMessage .= "</PKIBody>";
		$PKIMessage .= "<protection/>";
		$PKIMessage .= "</PKIData>";
		$PKIMessage .= "</PKIMessage>";
		
		$ca_body .= $PKIMessage;
		
		if($add_sig != false)
		{		
			$adminid = $cfg_params["adminid"];
			$adminpwd = $cfg_params["adminpwd"];
			$_ca_admin_pk_path = $cfg_params["caadminpk"];
			
			//get data to hash:
			$hashcontent = $PKIMessage;
			$PKIMessage_xml = simplexml_load_string($hashcontent, "SimpleXMLElement", LIBXML_NOBLANKS | LIBXML_NOCDATA | LIBXML_COMPACT);
			$PKIMessage_json = json_encode($PKIMessage_xml);
			$PKIMessage_array = json_decode($PKIMessage_json,TRUE);
			$hashcontent = http_build_query($PKIMessage_array);
			
			$digest_value = base64_encode(openssl_digest($hashcontent, "sha256", true));

			// sign data
			$adminpk = file_get_contents($_ca_admin_pk_path);
				
			// get private key to sign
			$returnpk = openssl_pkey_get_private($adminpk, $adminpwd);
			
			// sign content
			openssl_sign ($hashcontent , $signature, $returnpk, "sha256");
			
			openssl_free_key($returnpk);
			
			$signature_value = base64_encode($signature);

			// send digest value and signature if XTEC CA is used
			if(isset($digest_value) && isset($adminid))
			{
				$ca_body .= "<Signature>";
				$ca_body .= "<DigestValue>".$digest_value."</DigestValue>";
				$ca_body .= "<SignatureValue>".$signature_value."</SignatureValue>";
				$ca_body .= "</Signature>";
				$ca_body .= "<adminid>".$adminid."</adminid>";	

			}
		}
		
		$ca_body .= "</ServiceRequest>";
		
	return $ca_body;
}


	/**
	 * @return array of the form: $rv["error"][n] contains errors encountered. $rv["result"] contains the XML response from the remote server.
	 * @param string $remoteurl. The URL of the remote server to request.
	 * @param mixed $clientcert. An optional client certificate for connection authentication.
	 * @param string $certpasswd. An optional client certificate pass phrase.
	 * @param integer timeout. An optional connection timeout in seconds. Default is 30.
	 * @param string post fields. An optional POST content for the request.
	 * @param string proxy URL. An optional proxy server URL to use.
	 * @param string proxy port. An optional proxy port (defaults to 8080).
	 * @param string $verb. The http verb to use (PUT or POST). False for default (POST) or (GET).
	 * @param array $httpheaders. An array (numerically indexed) of http headers to be set (eg Content-Type: application/json)
	 * @desc Handles a request to a remote server using https (ssl connection) at the specified URL.
	 */
	private function
	curlrequest_ssl($remoteurl, $clientcert = false, $certpasswd = false, $timeout = 30, $postfields = false, $proxyurl = false, $proxyport = 8080, $verb, $httpheaders = array())
	{
		$rv = array();

		$ch = curl_init($remoteurl);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSLVERSION, 1);	// 1 = TLSv1, 3 = SSLv3
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		if ($clientcert !== false)
		{
			curl_setopt($ch, CURLOPT_SSLCERT, $clientcert);
			if ($certpasswd !== false)
				curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certpasswd);
		}

		if ($postfields !== false)
		{
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			if (strcasecmp($verb, "POST") == 0)
				curl_setopt($ch, CURLOPT_POST, true);
			elseif (strcasecmp($verb, "PUT") == 0)
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		}
		else
			curl_setopt($ch, CURLOPT_HTTPGET, true);

		if ($proxyurl !== false)
		{
			curl_setopt($ch, CURLOPT_PROXY, $proxyurl);
			curl_setopt($ch, CURLOPT_PROXYPORT, $proxyport);
		}

		if (count($httpheaders) > 0)
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheaders);
		}
	
        $response = curl_exec($ch);

		$crspcode = curl_errno($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$rv["httpcode"] = $httpcode;
		
		if ($crspcode != 0)
			$rv["error"] = "Curl error: 0x".dechex($crspcode)." for ".$remoteurl;
		else
			$rv["result"] = $response;
		return $rv;
	}

	
	
	// ******** PUBLIC METHODS
	/**
	 * @return array. Array containing userID and set of user information including user UID for each
	 * @param array $cfg_params. An array of connection parameters.
	 * ["awhost"] = airwatch host address
	 * ["clcertfile"] = client certificate filename
	 * ["clcertpasswd"] = client cert passphrase
	 * ["proxyurl"] = proxy URL to use
	 * ["proxyport"] = proxy port to use
	 * @param string $username. The username to provide the query for.
	 * @desc Returns an array of identifiers and info after making a connection to the airwatch server and
	 * requesting the data for the username specified.
	 * ["device"][n]["awuserid"] = airwatch userID value
	 * ["device"][n]["awid"] = airwatch deviceID value
	 * ["device"][n]["platformname"] = Device platform name
	 * ["device"][n]["modelname"] = Device model name
	 * ["device"][n]["udid"] = UDID number
	 * ["device"][n]["phonenumber"] = Device phone number
	 * ["device"][n]["friendlyname"] = Device friendly name
	 * ["device"][n]["email"] = User email address
	 * ["jsonmessage"] = Entire json message object string
	 *
	 */
	public function
	aw_geruserinfo($cfg_params, $httpheaders, $username)
	{
		date_default_timezone_set("US/Eastern");

		$rv = array();
		$clientcert = false;
		$certpasswd = false;
		$proxyurl = false;
		$proxyport = 8080;
		$verb = "GET";
		$n = 0;

		if (isset($cfg_params["awhost"]))
		{
			$remoteurl = $cfg_params["awhost"]."API/system/users/search?email=".urlencode($username);
			if (isset($cfg_params["clcertfile"]))
				$clientcert = $cfg_params["clcertfile"];
			if (isset($cfg_params["clcertpasswd"]))
				$certpasswd = $cfg_params["clcertpasswd"];
			if (isset($cfg_params["proxyurl"]))
				$proxyurl = $cfg_params["proxyurl"];
			if (isset($cfg_params["proxyport"]))
				$proxyport = $cfg_params["proxyport"];
		
			// This should connect and get a JSON response
			$jsonrsp = $this->curlrequest_ssl($remoteurl, $clientcert, $certpasswd, 30, false, $proxyurl, $proxyport, $verb, $httpheaders);
			
			if ($jsonrsp !== false)
			{
				$rv["httpcode"] = $jsonrsp["httpcode"];

				if (isset($jsonrsp["error"]))
					$rv["error"] = $jsonrsp["error"];
				elseif (isset($jsonrsp["result"]))
				{
					$rv["jsonmessage"] = $jsonrsp["result"];
					
					$prsp = json_decode($jsonrsp["result"], true);
					if ($prsp !== false)
					{
						if(isset($prsp["errorCode"]))
						{
							// show error
							$rv["error"] = true;
							$rv["errorcode"] = $prsp["errorCode"];
							$rv["errormsg"] = $prsp["message"];
						}
						else
						{							
							// Get the fields of interest from the parsed response
							if (isset($prsp["Users"]))
							{
								// The devices array contains all we need
								$d = $prsp["Users"];
								$n = count($d);
								
								if($n == 0)
								{
									// show error
									$rv["error"] = true;
									$rv["errorcode"] = 0x01;
									$rv["errormsg"] = "No records found with email: ".$username;
								}
								else
								{
									$rv["count"] = $n;
									
									// user found flag
									$user_found = false;
									
									// Process each device
									for ($i = 0; $i < $n; $i++)
									{
										//check if returend email matches the search email
										if (isset($d[$i]["Email"]) && (trim($d[$i]["Email"]) != ""))
										{
											$awemail = $d[$i]["Email"];
											
											// compare 2 emails
											if(strcasecmp($awemail, $username) == 0)
											{
												// user found
												$user_found = true;
												
												// assign returned email
												$rv["user"][$i]["awemail"] = $d[$i]["Email"];
											
												if (isset($d[$i]["UserName"]))
													$rv["user"][$i]["username"] = $d[$i]["UserName"];
												else
													$rv["user"][$i]["username"] = false;
													
												if (isset($d[$i]["Password"]))
													$rv["user"][$i]["password"] = $d[$i]["Password"];
												else
													$rv["user"][$i]["password"] = false;
													
												if (isset($d[$i]["FirstName"]))
													$rv["user"][$i]["fname"] = $d[$i]["FirstName"];
												else
													$rv["user"][$i]["fname"] = false;
													
												if (isset($d[$i]["LastName"]))
													$rv["user"][$i]["lname"] = $d[$i]["LastName"];
												else
													$rv["user"][$i]["lname"] = false;
													
												if (isset($d[$i]["Status"]))
													$rv["user"][$i]["status"] = $d[$i]["Status"];
												else
													$rv["user"][$i]["status"] = false;
													
												if (isset($d[$i]["Uuid"]))
													$rv["user"][$i]["uuid"] = $d[$i]["Uuid"];
												else
													$rv["user"][$i]["uuid"] = false;
												
												// dont look further
												break;
											}
										}
										else
										{
											// show error
											$rv["error"] = true;
											$rv["errorcode"] = 0x02;
											$rv["errormsg"] = "No valid email returned for userid: ".$username;
										}
									}
									
									if(!$user_found)
									{
										// show error
										$rv["error"] = true;
										$rv["errorcode"] = 0x01;
										$rv["errormsg"] = "No records found with email: ".$username;
									}
								}
								
							}
						}
					}
				}
			}
		}

		return $rv;
	}

	// ******** PUBLIC METHODS
	/**
	 * @return array. Array containing userID and set of device information including deviceID for each
	 * @param array $cfg_params. An array of connection parameters.
	 * ["awhost"] = airwatch host address
	 * ["clcertfile"] = client certificate filename
	 * ["clcertpasswd"] = client cert passphrase
	 * ["proxyurl"] = proxy URL to use
	 * ["proxyport"] = proxy port to use
	 * @param string $username. The username to provide the query for.
	 * @desc Returns an array of identifiers and info after making a connection to the airwatch server and
	 * requesting the data for the username specified.
	 * ["device"][n]["awuserid"] = airwatch userID value
	 * ["device"][n]["awid"] = airwatch deviceID value
	 * ["device"][n]["platformname"] = Device platform name
	 * ["device"][n]["modelname"] = Device model name
	 * ["device"][n]["udid"] = UDID number
	 * ["device"][n]["phonenumber"] = Device phone number
	 * ["device"][n]["friendlyname"] = Device friendly name
	 * ["device"][n]["email"] = User email address
	 * ["jsonmessage"] = Entire json message object string
	 *
	 */
	public function
	aw_getidsetforuser($cfg_params, $httpheaders, $username)
	{
		date_default_timezone_set("US/Eastern");

		$rv = array();
		$clientcert = false;
		$certpasswd = false;
		$proxyurl = false;
		$proxyport = 8080;
		$verb = "GET";
		$n = 0;

		if (isset($cfg_params["awhost"]))
		{
			$remoteurl = $cfg_params["awhost"]."API/v1/mdm/devices/search?user=".urlencode($username);
			if (isset($cfg_params["clcertfile"]))
				$clientcert = $cfg_params["clcertfile"];
			if (isset($cfg_params["clcertpasswd"]))
				$certpasswd = $cfg_params["clcertpasswd"];
			if (isset($cfg_params["proxyurl"]))
				$proxyurl = $cfg_params["proxyurl"];
			if (isset($cfg_params["proxyport"]))
				$proxyport = $cfg_params["proxyport"];
		
			// This should connect and get a JSON response
			$jsonrsp = $this->curlrequest_ssl($remoteurl, $clientcert, $certpasswd, 30, false, $proxyurl, $proxyport, $verb, $httpheaders);
			
			if ($jsonrsp !== false)
			{
				$rv["httpcode"] = $jsonrsp["httpcode"];

				if (isset($jsonrsp["error"]))
					$rv["error"] = $jsonrsp["error"];
				elseif (isset($jsonrsp["result"]))
				{
					$rv["jsonmessage"] = $jsonrsp["result"];

					$prsp = json_decode($jsonrsp["result"], true);
					if ($prsp !== false)
					{
						// Get the fields of interest from the parsed response
						if (isset($prsp["Devices"]))
						{
							// user found flag
							$user_device_found = false;
									
							// The devices array contains all we need
							$d = $prsp["Devices"];
							$n = count($d);

							// Process each device
							for ($i = 0; $i < $n; $i++)
							{
								if (isset($d[$i]["UserName"]) && (trim($d[$i]["UserName"]) != ""))
								{
									$uname = $d[$i]["UserName"];
									
									// compare 2 user unique id
									if(strcasecmp($uname, $username) == 0)
									{
										// user found
										$user_device_found = true;
									
										if (isset($d[$i]["Platform"]))
											$rv["devices"][$i]["platformname"] = $d[$i]["Platform"];
										else
											$rv["devices"][$i]["platformname"] = false;

										if (isset($d[$i]["ModelId"]["Name"]))
											$rv["devices"][$i]["modelname"] = $d[$i]["ModelId"]["Name"];
										else
											$rv["devices"][$i]["modelname"] = false;

										if (isset($d[$i]["Udid"]))
											$rv["devices"][$i]["udid"] = $d[$i]["Udid"];
										else
											$rv["devices"][$i]["udid"] = false;

										if (isset($d[$i]["PhoneNumber"]))
											$rv["devices"][$i]["phonenumber"] = $d[$i]["PhoneNumber"];
										else
											$rv["devices"][$i]["phonenumber"] = false;

										if (isset($d[$i]["DeviceFriendlyName"]))
											$rv["devices"][$i]["friendlyname"] = $d[$i]["DeviceFriendlyName"];
										else
											$rv["devices"][$i]["friendlyname"] = false;

										if (isset($d[$i]["UserId"]["Id"]["Value"]))
											$rv["devices"][$i]["awuserid"] = $d[$i]["UserId"]["Id"]["Value"];
										else
											$rv["devices"][$i]["awuserid"] = false;

										if (isset($d[$i]["Id"]["Value"]))
											$rv["devices"][$i]["awid"] = $d[$i]["Id"]["Value"];
										else
											$rv["devices"][$i]["awid"] = false;

										if (isset($d[$i]["UserEmailAddress"]))
											$rv["devices"][$i]["email"] = $d[$i]["UserEmailAddress"];
										else
											$rv["devices"][$i]["email"] = false;
									}
								}
							}
							
							if(!$user_device_found)
							{
								// show error
								$rv["error"] = true;
								$rv["errorcode"] = 0x01;
								$rv["errormsg"] = "No records found with email: ".$username;
							}
						}
					}
				}
			}
		}

		return $rv;
	}
	
	/**
	 * @return array. Array containing [httpcode] for the http result, and [error] if there is an error string.
	 * @param array $cfg_params. An array of connection parameters.
	 * ["awhost"] = airwatch host address
	 * ["clcertfile"] = client certificate filename
	 * ["clcertpasswd"] = client cert passphrase
	 * ["proxyurl"] = proxy URL to use
	 * ["proxyport"] = proxy port to use
	 * @param array $httpheaders. A set of http headers to set for the message.
	 * [Content-Type], [Authorization], [aw-tenant-code]
	 * @param int $awuserid. The AirWatch userID for the selected user/device.
	 * @param string $cert_b64. The base64 encoded P12 certificate.
	 * @param string $cert_passwd. The certificate passphrase entered by the user.
	 * @desc Attempts to send the P12 certificate to AirWatch for the userID specified.
	 */
	public function
	aw_uploadp12cert($cfg_params, $httpheaders, $awuserid, $cert_b64, $cert_passwd)
	{
		date_default_timezone_set("US/Eastern");

		$rv = false;
		$clientcert = false;
		$certpasswd = false;
		$proxyurl = false;
		$proxyport = 8080;
		$verb = "POST";

		if (isset($cfg_params["awhost"]))
		{
			$remoteurl = $cfg_params["awhost"]."API/v1/system/users/".$awuserid."/uploadsmimecerts";
			if (isset($cfg_params["clcertfile"]))
				$clientcert = $cfg_params["clcertfile"];
			if (isset($cfg_params["clcertpasswd"]))
				$certpasswd = $cfg_params["clcertpasswd"];
			if (isset($cfg_params["proxyurl"]))
				$proxyurl = $cfg_params["proxyurl"];
			if (isset($cfg_params["proxyport"]))
				$proxyport = $cfg_params["proxyport"];

			// Create the POST payload
			$pl = array();
			$pl["Signing"] = array();
			$pl["Signing"]["CertificatePayload"] = $cert_b64;
			$pl["Signing"]["Password"] = $cert_passwd;
			$poststring = json_encode($pl);

			// This should connect and will not get a response
			$jsonrsp = $this->curlrequest_ssl($remoteurl, $clientcert, $certpasswd, 30, $poststring, $proxyurl, $proxyport, $verb, $httpheaders);

			$rv["httpcode"] = $jsonrsp["httpcode"];
			if (isset($jsonrsp["error"]))
				$rv["error"] = $jsonrsp["error"];
		}

		return $rv;
	}
	
	/**
	 * @return array. Array containing [httpcode] for the http result, and [error] if there is an error string.
	 * @param array $cfg_params. An array of connection parameters.
	 * ["awhost"] = airwatch host address
	 * ["clcertfile"] = client certificate filename
	 * ["clcertpasswd"] = client cert passphrase
	 * ["proxyurl"] = proxy URL to use
	 * ["proxyport"] = proxy port to use
	 * @param array $httpheaders. A set of http headers to set for the message.
	 * [Content-Type], [Authorization], [aw-tenant-code]
	 * @param int $awdeviceid. The AirWatch device ID for the selected device.
	 * @param int $awtagid. The Airwatch tag ID (static code)
	 * @desc Attempts to send the a request to Airwatch to push the previously uploaded certificate to the device specified.
	 */
	public function
	aw_pushdeviceprofile($cfg_params, $httpheaders, $awdeviceid, $awtagid)
	{
		date_default_timezone_set("US/Eastern");
		$rv = array();
		$rv["error"] = false;
		$rv["errorcode"] = false;
		$clientcert = false;
		$certpasswd = false;
		$proxyurl = false;
		$proxyport = 8080;
		$verb = "POST";
		if (isset($cfg_params["awhost"]))
		{
			$remoteurl = $cfg_params["awhost"]."API/mdm/tags/".$awtagid."/adddevices";
			if (isset($cfg_params["clcertfile"]))
				$clientcert = $cfg_params["clcertfile"];
			if (isset($cfg_params["clcertpasswd"]))
				$certpasswd = $cfg_params["clcertpasswd"];
			if (isset($cfg_params["proxyurl"]))
				$proxyurl = $cfg_params["proxyurl"];
			if (isset($cfg_params["proxyport"]))
				$proxyport = $cfg_params["proxyport"];
				
			// Create the POST payload
			$pl = array();
			$pl["BulkValues"]["Value"][0] = $awdeviceid;
			$poststring = json_encode($pl);
			// This should connect and WILL get a response
			$jsonrsp = $this->curlrequest_ssl($remoteurl, $clientcert, $certpasswd, 30, $poststring, $proxyurl, $proxyport, $verb, $httpheaders);
			if ($jsonrsp !== false)
			{
				$rv["httpcode"] = $jsonrsp["httpcode"];
				if (isset($jsonrsp["error"]))
					$rv["error"] = $jsonrsp["error"];
				elseif (isset($jsonrsp["result"]))
				{
					$rv["jsonmessage"] = $jsonrsp["result"];
					$prsp = json_decode($jsonrsp["result"], true);
					if ($prsp !== false)
					{
						if(isset($prsp["AcceptedItems"]) && $prsp["AcceptedItems"] > 0  )
							return $rv;
						if (isset($prsp["Faults"]["Fault"][0]["ErrorCode"]))
						{
							$rv["errorcode"] = $prsp["Faults"]["Fault"][0]["ErrorCode"];
							$rv["error"] = $prsp["Faults"]["Fault"][0]["Message"];
						}
					}
					else
						$rv["error"] = "Error parsing response, 400";
				}
			}
			else
				$rv["error"] = "Error getting response.";
		}
		else
			$rv["error"] = "Error getting host.";
		return $rv;
	}

	/**
	 * @return array. Array containing [httpcode] for the http result, and [error] if there is an error string.
	 * @param array $cfg_params. An array of connection parameters.
	 * ["awhost"] = airwatch host address
	 * ["clcertfile"] = client certificate filename
	 * ["clcertpasswd"] = client cert passphrase
	 * ["proxyurl"] = proxy URL to use
	 * ["proxyport"] = proxy port to use
	 * @param array $httpheaders. A set of http headers to set for the message.
	 * [Content-Type], [Authorization], [aw-tenant-code]
	 * @param int $awdeviceid. The AirWatch device ID for the selected device.
	 * @param int $awtagid. The Airwatch tag ID (static code)
	 * @desc Attempts to send the a request to Airwatch to push the previously uploaded certificate to the device specified.
	 */
	public function
	aw_removedeviceprofile($cfg_params, $httpheaders, $awdeviceid, $awtagid)
	{
		date_default_timezone_set("US/Eastern");

		$rv = array();
		$rv["error"] = false;
		$rv["errorcode"] = false;
		$clientcert = false;
		$certpasswd = false;
		$proxyurl = false;
		$proxyport = 8080;
		$verb = "POST";

		if (isset($cfg_params["awhost"]))
		{
			$remoteurl = $cfg_params["awhost"]."API/mdm/tags/".$awtagid."/removedevices";
			if (isset($cfg_params["clcertfile"]))
				$clientcert = $cfg_params["clcertfile"];
			if (isset($cfg_params["clcertpasswd"]))
				$certpasswd = $cfg_params["clcertpasswd"];
			if (isset($cfg_params["proxyurl"]))
				$proxyurl = $cfg_params["proxyurl"];
			if (isset($cfg_params["proxyport"]))
				$proxyport = $cfg_params["proxyport"];
				
			// Create the POST payload
			$pl = array();
			$pl["BulkValues"]["Value"][0] = $awdeviceid;
			
			$poststring = json_encode($pl);

			// This should connect and WILL get a response
			$jsonrsp = $this->curlrequest_ssl($remoteurl, $clientcert, $certpasswd, 30, $poststring, $proxyurl, $proxyport, $verb, $httpheaders);
			if ($jsonrsp !== false)
			{
				$rv["httpcode"] = $jsonrsp["httpcode"];

				if (isset($jsonrsp["error"]))
						$rv["error"] = $jsonrsp["error"];
				elseif (isset($jsonrsp["result"]))
				{
					$rv["jsonmessage"] = $jsonrsp["result"];

					$prsp = json_decode($jsonrsp["result"], true);
					if ($prsp !== false)
					{
						if(isset($prsp["AcceptedItems"]) && $prsp["AcceptedItems"] > 0  )
							return $rv;

						if (isset($prsp["Faults"]["Fault"][0]["ErrorCode"]))
						{
							$rv["errorcode"] = $prsp["Faults"]["Fault"][0]["ErrorCode"];
							$rv["error"] = $prsp["Faults"]["Fault"][0]["Message"];
						}
					}
					else
						$rv["error"] = "Error parsing response, 400";
				}
			}
			else
				$rv["error"] = "Error getting response.";
		}
		else
			$rv["error"] = "Error getting host.";

		return $rv;
	}
	
	/**
	 * @desc: Locates the single specified device for the userid/devid and returns the array of device data
	 * @param: array $devices. Array of device parameters: 
	 * [n]["awuserid"] = airwatch userID value
	 * [n]["awid"] = airwatch deviceID value
	 * [n]["platformname"] = Device platform name
	 * [n]["modelname"] = Device model name
	 * [n]["udid"] = UDID number
	 * [n]["phonenumber"] = Device phone number
	 * [n]["friendlyname"] = Device friendly name
	 * [n]["email"] = User email address
	 * @param: string $awuserid. The userID to search for in devices ["awuserid"]
	 * @param: string $awdevid. The deviceID to search for in devices ["awid"].
	 * @return: array of device data for the specific device located or false if not found
	 */
	public function
	aw_locate_device($devices, $awuserid, $awdevid)
	{
		date_default_timezone_set("US/Eastern");
		
		$rv = false;
		$nd = count($devices);
		for ($i = 0; $i < $nd; $i++)
		{
			if ((strcasecmp($devices[$i]["awuserid"], $awuserid) == 0) && (strcasecmp($devices[$i]["awid"], $awdevid) == 0))
				$rv = $devices[$i];
		}
		
		return $rv;
	}
	
	
	/**
	 * @desc Uses openSSL to generate a PKI key pair and a certificate signing request (csr).
	 * @param array $subjectname. An ordered array of array(attribute=>value) for the subject DN.
	 * Order from top level down. Example:
	 * [0]['C'] = 'US'
	 * [1]['O'] = 'U.S. Government'
	 * [2]['OU'] = $agency_name
	 * [3]['OU'] = $component_name
	 * [4]['CN'] = $common_name
	 * [5]['UID'] = $userid
	 * @return array containing ["privatekey"], ["csr"]
	 * or ["error"] containing errot message
	 */

	public function
	aw_getcsr_openssl($subjectname)
	{
		date_default_timezone_set("US/Eastern");

		$rv = array();
		$subjdnstring = "";

		foreach ($subjectname as $aset)
		{
			foreach ($aset as $a => $v)
				$subjdnstring .= $a."=".$v."\n";
		}

		// openSSL configuration to be used with req command
		$config_string = <<<CONFSTR
oid_section=new_oids

[ new_oids ]
pivcerttype=1.3.6.1.4.6702.1.1.1

[ req ]
default_bits=2048
default_md=sha256
distinguished_name=req_distinguished_name
prompt=no
oid_section=new_oids
attributes=req_attributes
req_extensions=v3_req
string_mask=nombstr

[ req_distinguished_name ]
$subjdnstring

[ req_attributes ]
pivcerttype=9A

[ usr_cert ]

[ v3_req ]
basicConstraints=CA:FALSE
keyUsage=nonRepudiation, digitalSignature, keyEncipherment
extendedKeyUsage=clientAuth

CONFSTR;

		// temp files to be used with openSSL req command
		$tempdir = "/tmp";
		$temp_configfile = tempnam($tempdir, 'awconf_');
		$temp_csrfile = tempnam($tempdir, 'awcsr_');
		$temp_keyfile = tempnam($tempdir, 'awkey_');

		// output the config file
		file_put_contents($temp_configfile, $config_string);

		// openSSL command to create new key pair and csr; key is not encrypted
		$cmd_csr = "openssl req -config ".$temp_configfile." -new  -out ".$temp_csrfile." -keyout ".$temp_keyfile." -outform PEM -nodes -batch";

		// exec the openSSL command
		$cmdrtn = exec($cmd_csr, $output, $rtnstatus);

		if ($rtnstatus != 0)
			$rv["error"] = $output;
		else
		{
			$rv["csr"] = file_get_contents($temp_csrfile);
			$rv["privatekey"] = file_get_contents($temp_keyfile);
		}

		unlink($temp_keyfile);
		unlink($temp_csrfile);
		unlink($temp_configfile);
	
		return $rv;	
	}
	
	
	/**
	 * A5 GW
	 * @desc Uses the CA or gateway specified in the params to request the certificate.
	 * This is for use with the new AUthentx GW system
	 * @param array $cfg_params. Array containing connection parameters for various CA hosts.
	 * @param array $httpheaders. A set of http headers to set for the message.
	 * @param array $caconnection. The CA connection parameters to use from the configuration.
	 * @param array $certparams. Cert request parameters. CSR is PEM encoded.
	 * @return array: ["pemcert"] = PEM encoded certificate, ["serial"] = cert serial, ["expdate"] = cet expdate
	 * or ["error"] = error message
	 */
	public function
	aw_request_certfromgw($cfg_params, $httpheaders, $caconnection, $certparams, $dbg = false, $add_signature = false)
	{
		require_once("../appcore/vec-clxxml.php");
		date_default_timezone_set("US/Eastern");

		$myxml = new authentxxml();
		$rv = array();
		$err = false;

		if (isset($cfg_params[$caconnection]))
		{
			
			$ca_gwurl = $cfg_params[$caconnection]["gateway_url"];

			$capost = $this->buildcabodyxml($certparams, $cfg_params, $caconnection, $add_signature);

			// Create the connection
			$gw_resp = $this->curlrequest_ssl($ca_gwurl, false, false, 60, $capost, false, false, "post", $httpheaders);

			if (isset($gw_resp["error"]))
			{
				$err = true;
				$rv["error"] = $gw_resp["error"];
			}
			else
			{
				$httpresp = $gw_resp["httpcode"];
				$xmlresponse = $gw_resp["result"];

				// Set the envelope tags to parse out
				$myxml->xmlclearblocktags();
				$myxml->xmladdblocktag("responseinfo");
				$myxml->xmladdblocktag("pkibody");

				// Parse the XML response
				$r = $myxml->parseresponse($xmlresponse, false);
				if (isset($r["error"]))
				{
					$err = true;
					$rv["error"] = $r["error"];
				}
				else
				{
					// Check the status first
					$rstat = $r["result"]["responseinfo"][0]["status"][0];
					if (strcasecmp($rstat, "success") != 0)
					{
						$rv["error"] = $r["result"]["responseinfo"][0]["statusmessage"][0];
						//$rv["error"] = $r["result"]["status"][0]["responseinfo"][0];
					}
					else
					{
						// Read the attributes and populate the return result
						if (isset($r["result"]["pkibody"][0]["certificate"][0]))
						{
							$x_b64cert = $r["result"]["pkibody"][0]["certificate"][0];
							if (strlen($x_b64cert) > 256)
							{
								$x_dercert = base64_decode($x_b64cert);
								// Convert to PEM cert
								$x_pemcert = $this->aw_certformat("dertopem", $x_dercert);

								$rv["dercert"] = $x_dercert;
								$rv["b64cert"] = $x_b64cert;
								$rv["pemcert"] = $x_pemcert;

								// extract the serial number and the expdate
								$certdata = $this->aw_getcertdata($x_pemcert);

								if (isset($certdata["000064"]))
									$rv["serial"] = $certdata["000064"];
								if (isset($certdata["000061"]))
									$rv["expdate"] = $certdata["000061"];
							}
							else
								$rv["error"] = "Error: No certificate in response.";
						}
						else
						{
							$rv["error"] = "Error: Could not find CertResponse.";
						}
					}
				}
			}
		}
		else
		{
			$rv["error"] = "Error: CA connection (".$caconnection.") unknown.";
			$err = true;
		}

		return $rv;
	}

	/**
	 * @desc Encrypts the private key with the passphrase and creates a pkcs12 container for the cert and key.
	 * @param string $pemkey. PEM format private key.
	 * @param string $passphrase. Passphrase to use to encrypt the secret key.
	 * @param string $pemcert. The PEM format certificate.
	 * @return array: ["cert_p12"] = pkcs12 certificate, ["cert_b64"] = base64 encoded pkcs12 certificate
	 * or ["error"] = error message
	 */
	public function
	aw_create_p12cert($pemkey, $passphrase, $pemcert)
	{
		date_default_timezone_set("US/Eastern");

		$rv = array();

		$certp12file = "/tmp/derived-".gmdate("YmdHis")."-".rand(100, 999).".p12";
		$certpemfile = "/tmp/derived-".gmdate("YmdHis")."-".rand(100, 999).".pem";
		$certkeyfile = "/tmp/derived-".gmdate("YmdHis")."-".rand(100, 999).".key";

		file_put_contents($certpemfile, $pemcert);
		file_put_contents($certkeyfile, $pemkey);

		$cmd_encode = "openssl pkcs12 -export -inkey ".$certkeyfile." -in ".$certpemfile." -out ".$certp12file." -password pass:'".$passphrase."'";
		if (file_exists($certpemfile) && file_exists($certkeyfile))
		{
			$r = exec($cmd_encode, $rslt, $rtncode);
			if (file_exists($certp12file))
			{
				$rv["cert_p12"] = file_get_contents($certp12file);
				$rv["cert_b64"] = base64_encode($rv["cert_p12"]);

				unlink($certp12file);
			}
			else
				$rv["error"] = "Error creating pkcs12 cert.";

			unlink($certkeyfile);
			unlink($certpemfile);
		}
		else
			$rv["error"] = "Error writing pem files.";

		return $rv;
	}

	/**
	 * @desc Traverses a set of token DN's to extract a table of info about the certs on the tokens with ctype of 'derived'.
	 * @param resource $ldbh. LDAP connection.
	 * @param array $tdnset. An array of token DN's to examine.
	 * @param string $cc. The cert container (eg "derivedcert"). Prefix, followed by SERIAL (eg derivedcert.serialnum.cert)
	 * @param string $ctype. The token type (eg 'PIV', 'gadget'...)
	 * @return array: ["tdn"] = token DN,
	 * ["phonenumber"] = phone number for token if present
	 * ["deviceid"] = device UDID value
	 * ["awstatus"] = certificate status through airwatch
	 * For [n] certs on the device:
	 * ["certstatus"][n] = certificate status (active, revoked etc)
	 * ["expdate"][n] = certificate expiration date.
	 * ["certserial"][n] = certificate serial number.
	 * ["revreason"][n] = cert revocation reason (if present)
	 * ["revdate"][n] = cert revocation date (if present)
	 * ["ufname"][n] = user-friendly cert name
	 */
	public function
	aw_createderivedcerttable($ldbh, $tdnset, $cc, $ctype, $cfg_cert_container)
	{
		require_once('../appcore/vec-clldap.php');

		$myldap = new authentxldap();

		$nt = count($tdnset);
		$rv = array();
		$nc = 0;
		// Locate the tokens with derived certs
		for ($i = 0; $i < $nt; $i++)
		{
			$tdn = $tdnset[$i];
			// Read the ctype, keep only if matched $ctype
			$x = $myldap->getldapattr($ldbh, $tdn, "ctype", false, false, "credential");
			if ($x !== false)
			{
				foreach($ctype as $type)
				{
					if (strcasecmp($x[0], $type) == 0)
					{
						// Type of cert
						$rv[$nc]['certtype'] = $x[0];
						
						// DN for device
						$rv[$nc]['tdn'] = $tdn;

						// Issue Date
						$x = $myldap->getldapattr($ldbh, $tdn, "issdate", false, false, "credential");
						if ($x !== false)
						{							
							// convert the original if it is not empty
							$internaldate = $x[0];

							if ($internaldate != "")
							{
								$date_yy = substr($internaldate, 0, 4);
								$date_mm = substr($internaldate, 4, 2);
								$date_dd = substr($internaldate, 6, 2);
							
								$nicedate = $date_mm."-".$date_dd."-".$date_yy;
								$rv[$nc]['issdate'] = $nicedate;
							}
							else
								$rv[$nc]['issdate'] = "";
						}
						else
							$rv[$nc]['issdate'] = "";
						
						// Issby
						$x = $myldap->getldapattr($ldbh, $tdn, "issby", false, false, "credential");
						if ($x !== false)
							$rv[$nc]['issby'] = $x[0];
						else
							$rv[$nc]['issby'] = "";

						//$awdn = "gcoid=airwatch,".$tdn;
						$awdn = "gcoid=".$cfg_cert_container.".cert,".$tdn;

						// Phone number for device
						$g = $myldap->getldapattr($ldbh, $awdn, "xblk", false, "000005", "gco");
						if ($g !== false)
							$rv[$nc]['phonenumber'] = $g[0];
						else
							$rv[$nc]['phonenumber'] = "";

						// Device ID for device
						$g = $myldap->getldapattr($ldbh, $awdn, "xblk", false, "000004", "gco");
						if ($g !== false)
							$rv[$nc]['deviceid'] = $g[0];
						else
							$rv[$nc]['deviceid'] = "";

						// AW device ID for device
						$g = $myldap->getldapattr($ldbh, $awdn, "xblk", false, "000002", "gco");
						if ($g !== false)
							$rv[$nc]['awdeviceid'] = $g[0];
						else
							$rv[$nc]['awdeviceid'] = "";

						// AW user ID for device
						$g = $myldap->getldapattr($ldbh, $awdn, "xblk", false, "000003", "gco");
						if ($g !== false)
							$rv[$nc]['awuserid'] = $g[0];
						else
							$rv[$nc]['awuserid'] = "";

						// Handle multiple certs on a device - Read all the cert objects starting with the $cc prefix.
						// returns an array of DN's to GCO's that match the prefix and suffix
						$cset = $this->aw_derivedcertsundertoken($ldbh, $tdn, $cc, ".cert");
						$nk = count($cset);
						for ($k = 0; $k < $nk; $k++)
						{
							$certdn = $cset[$k];

							// awstatus for the cert gcoid=derived.piv.cert:xblk:009001
							$g = $myldap->getldapattr($ldbh, $certdn, "xblk", false, "009001", "gco");
							if ($g !== false)
								$rv[$nc]['awstatus'][$k] = $g[0];
							else
								$rv[$nc]['awstatus'][$k] = "";

							// cert status gcoid=derived.piv.cert:status
							$g = $myldap->getldapattr($ldbh, $certdn, "status", false, false, "gco");
							if ($g !== false)
								$rv[$nc]['certstatus'][$k] = $g[0];
							else
								$rv[$nc]['certstatus'][$k] = "";

							// cert expdate gcoid=derived.piv.cert:expdate
							$g = $myldap->getldapattr($ldbh, $certdn, "expdate", false, false, "gco");
							if ($g !== false)
							{
								// convert the original if it is not empty
								$internaldate = $g[0];

								if ($internaldate != "")
								{
									$date_yy = substr($internaldate, 0, 4);
									$date_mm = substr($internaldate, 4, 2);
									$date_dd = substr($internaldate, 6, 2);
								
									$nicedate = $date_mm."-".$date_dd."-".$date_yy;
									$rv[$nc]['certexpdate'][$k] = $nicedate;
								}
								else
									$rv[$nc]['certexpdate'][$k] = "";
							}
							else
								$rv[$nc]['certexpdate'][$k] = "";

							// cert serial
							$g = $myldap->getldapattr($ldbh, $certdn, "xblk", false, "000064", "gco");
							if ($g !== false)
								$rv[$nc]['certserial'][$k] = $g[0];
							else
								$rv[$nc]['certserial'][$k] = "";

							// cert revocation date
							$g = $myldap->getldapattr($ldbh, $certdn, "xblk", false, "007015", "gco");
							if ($g !== false)
							{
								// convert the original if it is not empty
								$internaldate = $g[0];

								if ($internaldate != "")
								{
									$date_yy = substr($internaldate, 0, 4);
									$date_mm = substr($internaldate, 4, 2);
									$date_dd = substr($internaldate, 6, 2);
								
									$nicedate = $date_mm."-".$date_dd."-".$date_yy;
									$rv[$nc]['revdate'][$k] = $nicedate;
								}
								else
									$rv[$nc]['revdate'][$k] = "";
							}
							else
								$rv[$nc]['revdate'][$k] = "";

							// cert revocation reason
							$g = $myldap->getldapattr($ldbh, $certdn, "xblk", false, "007014", "gco");
							if ($g !== false)
								$rv[$nc]['revreason'][$k] = $g[0];
							else
								$rv[$nc]['revreason'][$k] = "";

							// cert user-friendly name
							$g = $myldap->getldapattr($ldbh, $certdn, "xblk", false, "007016", "gco");
							if ($g !== false)
								$rv[$nc]['ufname'][$k] = $g[0];
							else
								$rv[$nc]['ufname'][$k] = "";
						}
						$nc++;
					}
				}
			}
		}

		return $rv;
	}

/**
	 * @desc Traverses a set of token DN's to extract a table of info about the certs on the tokens with ctype of 'derived'.
	 * @param resource $ldbh. LDAP connection.
	 * @param array $tdnset. An array of token DN's to examine.
	 * @param string $cc. The cert container (eg "derivedcert"). Prefix, followed by SERIAL (eg derivedcert.serialnum.cert)
	 * @param string $ctype. The token type (eg 'PIV', 'gadget'...)
	 * @return array: ["tdn"] = token DN,
	 * ["phonenumber"] = phone number for token if present
	 * ["deviceid"] = device UDID value
	 * For [n] certs on the device:
	 * ["certstatus"][n] = certificate status (active, revoked etc)
	 * ["expdate"][n] = certificate expiration date.
	 * ["certserial"][n] = certificate serial number.
	 * ["revreason"][n] = cert revocation reason (if present)
	 * ["revdate"][n] = cert revocation date (if present)
	 * ["ufname"][n] = user-friendly cert name
	 */
	public function
	createderivedcerttable($ldbh, $tdnset, $cc, $ctypearr, $cfg_cert_container)
	{
		require_once('../appcore/vec-clldap.php');

		$myldap = new authentxldap();

		$nt = count($tdnset);
		$rv = array();
		$nc = 0;
		// Locate the tokens with derived certs
		for ($i = 0; $i < $nt; $i++)
		{
			$tdn = $tdnset[$i];
			// Read the ctype, keep only if matched $ctype
			$x = $myldap->getldapattr($ldbh, $tdn, "ctype", false, false, "credential");
			if ($x !== false)
			{
				if (in_array($x[0], $ctypearr))
				{
					$rv[$nc]['certtype'] = $x[0];
					
					// DN for device
					$rv[$nc]['dn'] = $tdn;
					
					$issdatevals = $myldap->getldapattr($ldbh, $tdn, "issdate", false, false, "credential");
					if ($issdatevals !== false)
					{
						$issdate = substr($issdatevals[0], 0, 4)."-". substr($issdatevals[0], 4, 2)."-". substr($issdatevals[0], 6, 2);
						$rv[$nc]['issdate'] = $issdate;
					}
					else
						$rv[$nc]['issdate'] = "";

					// Handle multiple certs on a device - Read all the cert objects starting with the $cc prefix.
					// returns an array of DN's to GCO's that match the prefix and suffix
					$cset = $this->aw_derivedcertsundertoken($ldbh, $tdn, $cc, ".cert");
					$certdn = $cset[0];
					
					// cert status gcoid=derived.cert:status
					$g = $myldap->getldapattr($ldbh, $certdn, "status", false, false, "gco");
					
					if ($g !== false)
						$rv[$nc]['certstatus'] = $g[0];
					else
						$rv[$nc]['certstatus'] = "";

					// cert expdate gcoid=derived.cert:expdate
					$g = $myldap->getldapattr($ldbh, $certdn, "expdate", false, false, "gco");
					
					if ($g !== false)
					{
						$expdate = substr($g[0], 0, 4)."-". substr($g[0], 4, 2)."-". substr($g[0], 6, 2);
						$rv[$nc]['certexpdate'] = $expdate;
					}
					else
						$rv[$nc]['certexpdate'] = "";

					// cert serial
					$g = $myldap->getldapattr($ldbh, $certdn, "xblk", false, "000064", "gco");
					
					if ($g !== false)
					{
						$rv[$nc]['certserial'] = $g[0];
						$rv[$nc]['id'] = $g[0];
					}
					else
						$rv[$nc]['certserial'] = "";

					// cert revocation date
					$g = $myldap->getldapattr($ldbh, $certdn, "xblk", false, "007015", "gco");
					
					if ($g !== false)
						$rv[$nc]['revdate'] = $g[0];
					else
						$rv[$nc]['revdate'] = "";

					// cert revocation reason
					$g = $myldap->getldapattr($ldbh, $certdn, "xblk", false, "007014", "gco");
					
					if ($g !== false)
						$rv[$nc]['revreason'] = $g[0];
					else
						$rv[$nc]['revreason'] = "";

					// cert user-friendly name
					$g = $myldap->getldapattr($ldbh, $certdn, "xblk", false, "007016", "gco");
					
					if ($g !== false)
						$rv[$nc]['ufname'] = $g[0];
					else
						$rv[$nc]['ufname'] = "";
					
					$nc++;
				}
			}
		}

		return $rv;
	}

	public function
	aw_getcertdata($pemcert)
	{
		require_once('../appcore/vec-clnumbers.php');
		$mynumbers = new authentxnumbers();

		date_default_timezone_set("US/Eastern");

		$rv = array();

		// Cert gets written to a temp file for processing
		$tempdir = "/tmp/";

		// The various GCO tags
		$gcotag_dercert = "000070";
		$gcotag_certexpdate = "000061";
		$gcotag_certissdate = "000062";
		$gcotag_certaiaurl = "000063";
		$gcotag_certserial = "000064";
		$gcotag_certsubjdn = "000065";
		$gcotag_certissdn = "000066";

		// **** Commands
		// Command to convert a PEM cert to DER format
		// openssl x509 -inform pem -in CERT.pem -outform der -out CERT.der
		$cmd_cert_pem2der_1 = "openssl x509 -inform pem -in ";
		$cmd_cert_pem2der_2 = " -outform der -out ";

		// Commands to extract information from the certificate
		// Common first part for getting cert detail
		$cmd_x509 = "openssl x509 -inform pem -text -noout -in ";
		// Serial Number : 1252577091 (0x4aa8cf43)
		$cmd_serial = " | grep \"Serial Number\" | sed 's/^.*\: //' ";
		// expdate : Mar 25 04:00:00 2016 GMT
		$cmd_expdate = " | grep \"Not After\" | sed 's/Not After \://' ";
		// issdate : Mar 25 04:00:00 2013 GMT
		$cmd_issdate = " | grep \"Not Before\" | sed 's/Not Before\://' ";
		// issuer URL : nfimediumsspweb.managed.entrust.com/AIA/CertsIssuedToNFIMediumSSPCA.p7c
		$cmd_issuerurl = " | grep \"CA Issuers - URI:http\" | sed 's/CA Issuers - URI\://' ";
		// Subject DN
		$cmd_subjdn = " | grep \"Subject:\" | sed 's/Subject\://' ";
		// Issuer DN
		$cmd_issdn = " | grep \"Issuer:\" | sed 's/Issuer\://' ";

		$filerandom = "derived_".$mynumbers->getuuid_hex()."_";

		// Convert the PEM cert to DER
		$pemfile = $tempdir.$filerandom."cert.pem";
		file_put_contents($pemfile, $pemcert);
		$derfile = $tempdir.$filerandom."cert.der";

		$dercmd = $cmd_cert_pem2der_1.$pemfile.$cmd_cert_pem2der_2.$derfile;
		$execrslt = array();
		$execrtn = exec($dercmd, $execrslt, $rtncode);

		if (file_exists($derfile))
		{
			$rv[$gcotag_dercert] = file_get_contents($derfile);
			unlink($derfile);
		}

		$expdatecmd = $cmd_x509.$pemfile.$cmd_expdate;
		$issdatecmd = $cmd_x509.$pemfile.$cmd_issdate;
		$serialcmd = $cmd_x509.$pemfile.$cmd_serial;
		$issuerurlcmd = $cmd_x509.$pemfile.$cmd_issuerurl;
		$subjdncmd = $cmd_x509.$pemfile.$cmd_subjdn;
		$issdncmd = $cmd_x509.$pemfile.$cmd_issdn;

		// expdate: Mar 25 04:00:00 2016 GMT
		$expdate = exec($expdatecmd);
		$expdate = trim($expdate);
		$expdate = preg_replace('/[\s]+/', ' ', $expdate);
		$edparts = explode(" ", $expdate);
		if (count($edparts) > 3)
		{
			$edraw = $edparts[0]." ".$edparts[1]." ".$edparts[3]." ".$edparts[2];
			$edstamp = strtotime($edraw);
			$expdateldap = gmdate("YmdHis", $edstamp)."Z";
		}

		// issdate: Mar 25 04:00:00 2013 GMT
		$issdate = exec($issdatecmd);
		$issdate = trim($issdate);
		$issdate = preg_replace('/[\s]+/', ' ', $issdate);
		$idparts = explode(" ", $issdate);
		if (count($idparts) > 3)
		{
			$idraw = $idparts[0]." ".$idparts[1]." ".$idparts[3]." ".$idparts[2];
			$idstamp = strtotime($idraw);
			$issdateldap = gmdate("YmdHis", $idstamp)."Z";
		}

		// Issuer URL: nfimediumsspweb.managed.entrust.com/AIA/CertsIssuedToNFIMediumSSPCA.p7c
		$issuerurl = exec($issuerurlcmd);
		$issuerurl = trim($issuerurl);

		// subject DN
		$subjdn = exec($subjdncmd);
		$subjdn = trim($subjdn);

		// issuer DN
		$issdn = exec($issdncmd);
		$issdn = trim($issdn);

		// Serial Number Hex string
		$serial = exec($serialcmd);
		$serial = trim($serial);
		$sp = explode("x", $serial);
		if (isset($sp[1]))
			$serial = substr($sp[1], 0, -1);

		$rv[$gcotag_certexpdate] = $expdateldap;
		$rv[$gcotag_certissdate] = $issdateldap;
		$rv[$gcotag_certaiaurl] = $issuerurl;
		$rv[$gcotag_certsubjdn] = $subjdn;
		$rv[$gcotag_certissdn] = $issdn;
		$rv[$gcotag_certserial] = $serial;

		unlink($pemfile);

		return $rv;
	}


	/**
	 * @desc: Converts a cert from pem to der or der to pem.
	 * @param string $ct. Conversion type. Can be 'pem2der' or 'dertopem'
	 * @param string $cert. The cert to convert from
	 * @return string. The converted certificate (ie PEM or DER cert), or false on error.
	 */
	public function
	aw_certformat($ct, $cert)
	{
		require_once('../appcore/vec-clnumbers.php');
		$mynumbers = new authentxnumbers();

		date_default_timezone_set("US/Eastern");

		$rv = false;

		// Cert gets written to a temp file for processing
		$tempdir = "/tmp/";

		// **** Commands
		$cmd_cert_pem2der_1 = "openssl x509 -inform pem -in ";
		$cmd_cert_pem2der_2 = " -outform der -out ";
		$cmd_cert_der2pem_1 = "openssl x509 -inform der -in ";
		$cmd_cert_der2pem_2 = " -outform pem -out ";
		$filerandom = "derived_".$mynumbers->getuuid_hex()."_";

		if (strcasecmp($ct, "pemtoder") == 0)
		{
			// Convert the PEM cert to DER
			$pemfile = $tempdir.$filerandom."cert.pem";
			file_put_contents($pemfile, $cert);
			$derfile = $tempdir.$filerandom."cert.der";

			$dercmd = $cmd_cert_pem2der_1.$pemfile.$cmd_cert_pem2der_2.$derfile;
			$execrslt = array();
			$execrtn = exec($dercmd, $execrslt, $rtncode);

			if (file_exists($derfile))
			{
				$rv = file_get_contents($derfile);
				unlink($derfile);
				unlink($pemfile);
			}
		}
		elseif (strcasecmp($ct, "dertopem") == 0)
		{
			// Convert the DER cert to PEM
			$derfile = $tempdir.$filerandom."cert.der";
			file_put_contents($derfile, $cert);
			$pemfile = $tempdir.$filerandom."cert.pem";

			$pemcmd = $cmd_cert_der2pem_1.$derfile.$cmd_cert_der2pem_2.$pemfile;
			$execrslt = array();
			$execrtn = exec($pemcmd, $execrslt, $rtncode);

			if (file_exists($pemfile))
			{
				$rv = file_get_contents($pemfile);
				unlink($pemfile);
				unlink($derfile);
			}
		}

		return $rv;
	}

	/*
	 * @desc: Reads gco object ID's under token and performs a partial match with
	 * derived cert (cc) prefix and the suffix.
	 * suffix MUST be the end of the gcoID string
	 */
	public function
	aw_derivedcertsundertoken($ldbh, $tdn, $prefix, $suffix)
	{
		require_once('../appcore/vec-clxld.php');

		$myxld = new authentxxld();

		$rv = array();

		$f = "objectclass=gco";
		$a = array("gcoid");

		$objset = $myxld->xld_search($ldbh, $tdn, $f, $a);
		if ($objset)
		{
			$objentry = $myxld->xld_first_entry($ldbh, $objset);
			for ($n = 0; $objentry != false; $objentry = $myxld->xld_next_entry($ldbh, $objentry))
			{
				$objdn = $myxld->xld_get_dn($ldbh, $objentry);
				$gid = $myxld->xld_get_values_len($ldbh, $objentry, "gcoid");
				$r = $gid['count'];
				for ($i = 0; $i < $r; $i++)
				{
					if (stripos($gid[$i], $prefix) !== false)
					{
						$gsfx = substr($gid[$i], (-1 * strlen($suffix)));
						if (strcasecmp($gsfx, $suffix) == 0)
						{
							$rv[$n] = $objdn;
							$n++;
						}
					}
				}
			}
		}

		return $rv;
	}

	/*
	 * @desc: Returns a randomly generated password of length $len
	*/
	public function
	aw_makepassword($len)
	{
		srand();
		$rv = "";
		for ($i = 0; $i < $len; $i++)
		{
			do
			{
				$c = rand(40, 126);
			}
			while (($c == 60) || ($c == 62) || ($c == 64) || ($c == 34) || ($c == 96)); // eliminate the following characters for safety: < > @ " '
			$rv .= chr($c);
		}

		return $rv;
	}


	/**
	 * @desc: Looks for derived credentials held by the user that match the ctype and returns the
	 * current count value or false if no credential exists. The current counter will be the highest
	 * value from all the revoked and expired derived certs. The preexpiry time is required because it
	 * deems a cert expired if it is still active within this time before expiration.
	 * @param resource $ldbh. Authentx ldap DB connection
	 * @param string $ucdn. User's authentx CDN.
	 * @param string $ctype. The credential type ('userdevice' set in config)
	 * @param string $issby.  In case you want to filter futher, by issby ('different MDMs may have different CN names);
	 * @return int. The counter current value or false if no revoked or expired token exists
	 */
	public function
	aw_derivedcertuidcounter($ldbh, $ucdn, $ctype, $issby)
	{
		require_once("siteconfig/config-site.php");
		date_default_timezone_set("US/Eastern");

		global $cfg_cert_container;

		$myldap = new authentxldap();
		$rv = false;

		// Get users token list
		$tdnset = $myldap->getldapattr($ldbh, $ucdn, "cdn", false, false, "credential");
		if ($tdnset !== false)
		{
			// Read the ctype and status for each token and find any active derived certs
			foreach ($tdnset as $utdn)
			{
				$ctset = $myldap->getldapattr($ldbh, $utdn, "ctype", false, false, "credential");
				if ($ctset !== false)
				{
					if (strcasecmp($ctset[0], $ctype) == 0)
					{
						// do 1 extra filter.  check for issby.
						$isbset = $myldap->getldapattr($ldbh, $utdn, "issby", false, false, "credential");
						
						if($isbset !== false)
						{													
							if (strcasecmp($isbset[0], $issby) == 0)
							{
								// Found a derived credential. from same issby.  so we can keep checking.
								$certdngcodn = "gcoid=certinfo,".$utdn;
								$ctrgcotag = "000092";

								// Check for the counter value in the [000092] gco xblk in the token certdn GCO
								$ctrvalset = $myldap->getldapattr($ldbh, $certdngcodn, "xblk", false, $ctrgcotag, "gco");
								if ($ctrvalset !== false)
								{
									if ($ctrvalset[0] != "")
										$ctrvalue = $ctrvalset[0];
									else
										$ctrvalue = false;
								}
								else
									$ctrvalue = false;

								if ($rv === false)
									$rv = $ctrvalue;
								else
								{
									if ($rv < $ctrvalue)
										$rv = $ctrvalue;
								}
							}
						}
					}
				}
			}
		}

		return $rv;
	}

	/**
	 * @param resource $ldbh. LDAP DB connection
	 * @param string $ucdn. Authentx cred DN
	 * @param string $ctype. Cred type to look for
	 * @param string $subjcn. The CN from the subject DN to search for.
	 * @param string $issby.  In case you want to filter futher, by issby ('different MDMs may have different CN names); 
	 * @return array $rv["newcn"] = true/false. true if no matching CN found.
	 * $rv["prevcount"] = count value (or false) if a matching CN is located in a previous cert.
	 */
	public function
	aw_derivedcertcnchanged($ldbh, $ucdn, $ctype, $subjcn, $issby, $fn, $ln)
	{
		require_once("siteconfig/config-site.php");
		require_once('../appcore/vec-clldap.php');
		date_default_timezone_set("US/Eastern");
		
		global $cfg_cert_container;

		$myldap = new authentxldap();
		$rv = array();
		$rv["newcn"] = true;
		$rv["certspresent"] = false;
		$rv["prevcount"] = false;

		// Get users token list
		$tdnset = $myldap->getldapattr($ldbh, $ucdn, "cdn", false, false, "credential");
		if ($tdnset !== false)
		{
			// Read the ctype and status for each token and find any active derived certs
			foreach ($tdnset as $utdn)
			{
				if ($rv["newcn"] === true)
				{
					$ctset = $myldap->getldapattr($ldbh, $utdn, "ctype", false, false, "credential");
					if ($ctset !== false)
					{
						if (strcasecmp($ctset[0], $ctype) == 0)
						{	
							// do 1 extra filter.  check for issby.
							$isbset = $myldap->getldapattr($ldbh, $utdn, "issby", false, false, "credential");
							if($isbset !== false)
							{
								if (strcasecmp($isbset[0], $issby) == 0)
								{
									// Found a derived credential. from same issby.  so we can keep checking.
									$rv["certspresent"] = true;
									
									// Need to check the CN value from the certdn GCO
									$certdngcodn = "gcoid=".$cfg_cert_container.".certdn,".$utdn;
									
									$cngcotag = "000001";

									$certcnvalue = false;
									$certcnvalueset = $myldap->getldapattr($ldbh, $certdngcodn, "xblk", false, $cngcotag, "gco");
									if ($certcnvalueset !== false)
									{
										if ($certcnvalueset[0] != "")
											$certcnvalue = $certcnvalueset[0];
									}

									$fngcotag = "000008";

									$certfnvalue = false;
									$certfnvalueset = $myldap->getldapattr($ldbh, $certdngcodn, "xblk", false, $fngcotag, "gco");
									if ($certfnvalueset !== false)
									{
										if ($certfnvalueset[0] != "")
											$certfnvalue = $certfnvalueset[0];
									}
									
									$lngcotag = "000009";

									$certlnvalue = false;
									$certlnvalueset = $myldap->getldapattr($ldbh, $certdngcodn, "xblk", false, $lngcotag, "gco");
									if ($certlnvalueset !== false)
									{
										if ($certlnvalueset[0] != "")
											$certlnvalue = $certlnvalueset[0];
									}
									
									if ((strcasecmp($subjcn, $certcnvalue) == 0) && (strcasecmp($fn, $certfnvalue) == 0) && (strcasecmp($ln, $certlnvalue) == 0))
									{								
										// CN matches, so get the count value to return and re-use							
										$rv["newcn"] = false;
										
										// since it matches, get the count.  if it doesn't exist default to false
										$gcodn = "gcoid=certinfo,".$utdn;
										$gcotag = "000092";

										$ctrvalue = false;
										$ctrvalueset = $myldap->getldapattr($ldbh, $gcodn, "xblk", false, $gcotag, "gco");
										if ($ctrvalueset !== false)
										{
											if ($ctrvalueset[0] != "")
												$ctrvalue = $ctrvalueset[0];
										}
										
										// previous count or false.
										$rv["prevcount"] = $ctrvalue;
									}
								}
							}												
						}
					}
				}
			}  // checked all applicable tokens.
		}

		return $rv;
	}
}

?>
