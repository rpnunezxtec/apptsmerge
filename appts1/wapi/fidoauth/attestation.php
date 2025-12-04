<?php

// $Id:$

// REST API : Authtests FIDO web-API class

include("binary/ByteBuffer.php");
include("attestation/AttestationObject.php");

class attestation
{
	// Private properties
	private $db_dbname;
	private $db_user;
	private $db_pw;
	private $db_host;
	
	private $ustat_active = 1;
	private $ustat_inactive = 2;
	private $ustat_pending = 3;

	// fido token possible status
	private $fstat_active = 1;
	private $fstat_inactive = 2;
	private $fstat_pending = 3;

	private $challenge;
	
	private $formats = array("packed", "android-safetynet", "apple");
	
	private $requireUserPresent = true;
	private $requireUserVerification = true;
	private $requireResidentKey = false;
	
	private $rpId;
	private $_siteid = "axappt";
	
	private $status;
	private $errorMessage;

	private $aaguid_mapping;

	private $attestation;

	public function __construct() {
		include "appconfig/config-app.php";

		// db config
		$this->db_dbname = $db_dbname;
		$this->db_host = $db_host;
		$this->db_user = $db_user;
		$this->db_pw = $db_pw;

		$this->rpId = $rpId;

		$this->aaguid_mapping = $aaguid_mapping;
		
		// set status to ok by default
		$this->status = "ok";
		
		// set status to empty string by default
		$this->errorMessage = "";
    }
	
	// Public properties
	
	
	// Private methods
	// Stores the base64-encoded challenge in the database for the user specified
	private function
	savechallenge($sdbh, $uuid, $challenge)
	{
		// Find user row and fido row
		$q = "select uid from user where uuid='".$sdbh->real_escape_string($uuid)."' ";
		$s = $sdbh->query($q);
		if ($s)
		{
			$r = $s->fetch_assoc();
			$s->free();
			if (isset($r["uid"]))
			{
				$uid = $r["uid"];

				// create FIDO entry
				$txnmsg = "Creating new FIDO row.";
				$qc = "insert into fido set "
					. "\n challenge='".$sdbh->real_escape_string($challenge)."', "
					. "\n uid='".$sdbh->real_escape_string($uid)."', "
					. "\n status='".$sdbh->real_escape_string($this->fstat_pending)."', "
					. "\n txnmsg='".$sdbh->real_escape_string($txnmsg)."' "
					;
				$sc = $sdbh->query($qc);
				$rv = $sc;
			}
			else
				$rv = false;
		}
		else
			$rv = false;
			
		return $rv;
	
	}

	// Update the base64-encoded challenge in the database for the user
	private function
	updatechallenge($sdbh, $uuid, $challenge)
	{
		// Find user row and fido row
		$q = "select uid from user where uuid='".$sdbh->real_escape_string($uuid)."' ";
		$s = $sdbh->query($q);
		if ($s)
		{
			$r = $s->fetch_assoc();
			$s->free();
			if (isset($r["uid"]))
			{
				$uid = $r["uid"];

				// Update FIDO entries with new challange for the user
				$txnmsg = "Updating challenge.";
				$qc = "update fido "
					. "\n set challenge='".$sdbh->real_escape_string($challenge)."', "
					. "\n txnmsg='".$sdbh->real_escape_string($txnmsg)."' "
					. "\n where uid=".$sdbh->real_escape_string($uid)
					. "\n and status=".$sdbh->real_escape_string($this->fstat_active) 
					;
				$sc = $sdbh->query($qc);
				$rv = $sc;
			}
			else
				$rv = false;
		}
		else
			$rv = false;
			
		return $rv;
	}
	
	// Private methods

	private function
	updateuser($sdbh, $f_uuid)
	{
		// get user count
		// Find user row and fido row
		$q = "select logincount from user where uuid='".$sdbh->real_escape_string($f_uuid)."' ";
		$s = $sdbh->query($q);
		
		if ($s)
		{	
			$r = $s->fetch_assoc();
			$s->free();
			if (isset($r["logincount"]))
			{
				$logincount = $r["logincount"];
			}
			else
			{
				$logincount = 0;
			}

			$logincount = $logincount + 1;
			$now = date('Y-m-d H:i:s');
			
			// update user status
			$qc = "update user set "
				. "\n lastlogin='".$now."', "
				. "\n logincount=".$logincount
				. "\n where uuid='".$sdbh->real_escape_string($f_uuid)."' "
				;
				
			$sc = $sdbh->query($qc);
			$rv = $sc;
		}
		else
			$rv = false;
		
		return $rv;
	}

	// Stores the base64-encoded challenge in the database for the user specified
	private function
	savepubkey($sdbh, $uuid, $pubkey_pem, $credentialId, $auth_name, $fid)
	{
		// Find user row and fido row
		$q = "select uid from user where uuid='".$sdbh->real_escape_string($uuid)."' ";
		$s = $sdbh->query($q);
		if ($s)
		{
			$r = $s->fetch_assoc();
			$s->free();
			if (isset($r["uid"]))
			{
				$uid = $r["uid"];
				
				// Locate fido row if it exists
				$txnmsg = "Saving public key pem and id.";
				$qc = "update fido set "
					. "\n pubkey='".$sdbh->real_escape_string($pubkey_pem)."', "
					. "\n devname='".$sdbh->real_escape_string($auth_name)."', "
					. "\n devid='".$sdbh->real_escape_string($credentialId)."', "
					. "\n txnmsg='".$sdbh->real_escape_string($txnmsg)."', "
					. "\n status='".$sdbh->real_escape_string($this->fstat_active)."' "
					. "\n where fid=".$sdbh->real_escape_string($fid)
					;
				$sc = $sdbh->query($qc);

				if($sc != false)
				{
					// update user status
					$qc = "update user set "
						. "\n status='".$this->ustat_active."' "
						. "\n where uuid='".$sdbh->real_escape_string($uuid)."' "
						;
						
					$sc = $sdbh->query($qc);
					$rv = $sc;
				} else
					$rv = false;
			}
			else
				$rv = false;
		}
		else
			$rv = false;
			
		return $rv;
	}
	
	// create a uuid
	private function 
	getuuid_hex()
	{
		$cuuid = "";
		$command = "/authentx/core/http7/getuuid";

		$output = array();
		$uuid = exec($command, $output, $rtncode);
		
		if (strlen($uuid) != 36)
			$cuuid = false;
		else 
		{
			$t = explode('-', $uuid);
			$n = count($t);
			for ($i = 0; $i < $n; $i++)
				$cuuid .= $t[$i];
		}
		
		return $cuuid;
	}
	
	// generate random string
	function generateRandomString($length = 32) 
	{
		return bin2hex(random_bytes($length / 2));
	}
	
	// create user entry with status to pending
	private function 
	registeruser($sdbh, $userid, $uname = "XUser", $ustat = 1)
	{
		// create user uuid
		$uuid = $this->getuuid_hex();
		
		$qu = "insert into user set "
			. "\n userid='".$sdbh->real_escape_string($userid)."', "
			. "\n uname='".$sdbh->real_escape_string($uname)."', "
			. "\n status='".$sdbh->real_escape_string($ustat)."', "
			. "\n passwd='".$sdbh->real_escape_string($this->generateRandomString())."', "
			. "\n logincount=0, "
			. "\n uuid='".$sdbh->real_escape_string($uuid)."'"
			;
		
		$u = $sdbh->query($qu);
		
		return ($u)? $uuid : false;
	}
	
	// create user entry with status to pending
	private function 
	createsave_uuid($sdbh, $userid)
	{
		// create user uuid
		$uuid = $this->getuuid_hex();
		
		$qu = "update user "
			. "\n set uuid='".$sdbh->real_escape_string($uuid)."'"
			. "\n where userid='".$sdbh->real_escape_string($userid)."' "
			;
		
		$u = $sdbh->query($qu);
		
		return ($u)? $uuid : false;
	}
	
	/**
     * generates a new challange
     * @param int $length
     * @return string
     * @throws WebAuthnException
     */
    private function createChallenge($length = 32) 
	{
        if (!$this->challenge) {
            $this->challenge = ByteBuffer::randomBuffer($length);
        }
        return $this->challenge;
    }
	
	// get the challange fixed configuration 
	function 
	getdefaultchallange($user_uuid, $userid, $challenge, $display_name = "XUser")
	{
		$byteBuffer = new ByteBuffer(hex2bin($user_uuid));

		// Convert Base64 to Base64URL by replacing characters and removing padding
		$base64url = $byteBuffer->toBase64URL();

		$rv = [];

		$rv["status"] = $this->status;
		$rv["errorMessage"] = $this->errorMessage;

		$rv["rp"]["name"] = "Authentx PIV-I";
		$rv["rp"]["id"] = $this->rpId;
		
		// userVerification section
		$rv["authenticatorSelection"]["userVerification"] = "preferred";
		$rv["authenticatorSelection"]["residentKey"] = "discouraged";

		$rv["user"]["id"] = $base64url;
		$rv["user"]["displayName"] = $display_name;
		$rv["user"]["name"] = $userid;
		
		$rv["pubKeyCredParams"][0]["type"] = "public-key";
		$rv["pubKeyCredParams"][0]["alg"] = -7;				// ES256 (ECDSA w/ SHA-256)
		$rv["pubKeyCredParams"][1]["type"] = "public-key";
		$rv["pubKeyCredParams"][1]["alg"] = -257;			// RS256 (RSASSA-PKCS1-v1_5 using SHA-256)
		
		$rv["attestation"] = $this->attestation;
		
		$rv["timeout"] = 20000;
		
		// add suffix and prefix to parse on client side
		$rv["challenge"] = $challenge->toBase64URL();
		
		$rv["extensions"] = [
			"example.extension.bool" => true,
		];
		
		return $rv;
	}
	
	// get the challange fixed configuration 
	function 
	getdefaultchallangelogin($challange, $credids_array, $user_uuid)
	{
		$rv = [];
		
		$rv["timeout"] = "60000";
		$rv["challenge"] = $challange;
		$rv["userVerification"] = "required";
		
		$rv["rp"]["id"] = $this->rpId;
		
		$rv["user"]["id"] = new ByteBuffer(hex2bin($user_uuid));
		
		// add each credid for each token the user has
		foreach($credids_array as $credid)
		{
			$allowed_cred = array(
				"id" => $credid,
				"transports" => array(
					"usb", 
					"nfc",
					"ble",
					"hybrid",
					"internal"
				),
				"type" => "public-key"
			);

			$rv["allowCredentials"][] = $allowed_cred;
		}
		
		return $rv;
	}
	
	// return user session info
	private function
	getchallange($sdbh, $uuid, $status, $credential_id = false)
	{
		$rv = false;
		
		// Find user row and fido row
		$q = "select uid from user where uuid='".$sdbh->real_escape_string($uuid)."' ";
		$s = $sdbh->query($q);
		
		if ($s)
		{
			$r = $s->fetch_assoc();
			$s->free();
			if (isset($r["uid"]))
			{
				$uid = $r["uid"];
				
				// Locate the lastet fido row if it exists
				$qf = "select challenge, fid "
					. "\n from fido "
					. "\n where uid=" . $sdbh->real_escape_string($uid);

				// filter by cerd id if present
				if($credential_id != false)
					$qf .= "\n and devid='".$sdbh->real_escape_string($credential_id)."'";
					
				$qf .= "\n and status=".$sdbh->real_escape_string($status)
					. "\n order by fid desc"
					. "\n limit 1";
					
				$sf = $sdbh->query($qf);
				if ($sf)
				{
					$rf = $sf->fetch_assoc();
					$sf->free();
					
					if(isset($rf["challenge"]))
					{
						$rv = array();
						$challenge = $rf["challenge"];
						$fid = $rf["fid"];
						
						$rv['challenge'] = $challenge;
						$rv['fid'] = $fid;
					}
				}
			}
		}
		
		return $rv;
	}
	
	// return user session info
	private function
	getcredid($sdbh, $uid)
	{
		$rv = array();

		// Locate fido row if it exists
		$qf = "select devid "
			. "\n from fido "
			. "\n where uid='".$sdbh->real_escape_string($uid)."' "
			. "\n and status='".$sdbh->real_escape_string($this->fstat_active)."' ";
		
		$sf = $sdbh->query($qf);
		if ($sf)
		{
			while ($rf = $sf->fetch_assoc()) 
			{
				if (isset($rf["devid"])) 
				{
					$devid = $rf["devid"];

					$rv[] = $devid;
				}
			}

			$sf->free();
		}
		
		return $rv;
	}

	// return user session info
	private function
	getpubkey($sdbh, $fid)
	{
		$rv = false;
		
		// Locate fido row if it exists
		$qf = "select pubkey "
			. "\n from fido "
			. "\n where fid='".$sdbh->real_escape_string($fid)."' ";
		
			$sf = $sdbh->query($qf);
		if ($sf)
		{
			$rf = $sf->fetch_assoc();
			$sf->free();
			
			if(isset($rf["pubkey"]))
			{
				$pubkey = $rf["pubkey"];
				$rv = $pubkey;
			}
		}
		
		return $rv;
	}
	
	/**
     * checks if the origin matchs the RP ID
     * @param string $origin
     * @return boolean
     * @throws exception
     */
    private function 
	checkorigin($origin) 
	{

        // The origin's scheme must be https
        if (parse_url($origin, PHP_URL_SCHEME) !== 'https') 
		{
            return false;
        }

        // extract host from origin
        $host = parse_url($origin, PHP_URL_HOST);
        $host = trim($host, '.');

        // The RP ID must be equal to the origin's effective domain, or a registrable
        // domain suffix of the origin's effective domain.
        return preg_match('/' . preg_quote($this->rpId) . '$/i', $host) === 1;
    }
	
	private function
	formatbinary($uuid, $type = "UUID")
	{
		//$prefix = "=?BINARY?B?";
		//$suffix = "?=";

		$prefix = "";
		$suffix = "";
		
		$f_uuid = substr($uuid, strlen($prefix), strlen($uuid) - strlen($suffix) - strlen($prefix));
		$f_uuid = base64_decode($f_uuid);
		
		if($type == "UUID")
			$f_uuid = bin2hex($f_uuid);
		
		return $f_uuid;
	}
	
	// Public methods
	function
	index()
	{
		throw new RestException(400, "Malformed request");
	}

	// Method to request registration using a FIDO user-authenticated token/client.
	// This request comes from a form and will send the userid as the argument.
	// This methos must then determine if the userid is not already used and create a temporary 
	// registration record for it, along with a challenge. The challenge, userid, appid and appname
	// are returned so that the client can create a new credential for the userid for the device/application.
	function
	postoptions($request_data = NULL)
	{
		require_once("/authentx/core/authentx5/defs_core.php");
		date_default_timezone_set(DATE_TIMEZONE);
		
		$xmlrequest = file_get_contents("php://input");
		file_put_contents("/tmp/request_options.txt", $xmlrequest);

		// Simple method to:
		// 1. read the JSON request to register a userid
		// 2. check userid is already in the database (pre-register)
		// 3. return a registration challenge
				
		// Check the request for validity
		if ($request_data != NULL)
		{
			//$rd = json_decode($request_data);
			$rd = $request_data;

			// get attestation request
			$this->attestation = ($rd["attestation"]) ?? "none";

			if (isset($rd["username"]))
			{
				$userid = $rd["username"];
				
				$sdbh = new mysqli($this->db_host, $this->db_user, $this->db_pw, $this->db_dbname);
				if (!$sdbh->connect_errno)
				{
					// create challenge
					$challenge = $this->createChallenge();
							
					// Attempt to locate the user
					$q_u = "select * from user where userid='".$sdbh->real_escape_string($userid)."' ";
					$s_u = $sdbh->query($q_u);
					if ($s_u)
					{
						$r_u = $s_u->fetch_assoc();
						if (!empty($r_u))
						{
							// User status must be either PENDING or ACTIVE
							if (($r_u["status"] == $this->ustat_pending) || ($r_u["status"] == $this->ustat_active))
							{
								$uuid = $r_u["uuid"];
								
								// check if uuid is not null
								// create and save it
								if($uuid == null)
									$uuid = $this->createsave_uuid($sdbh, $userid);
								
								if($uuid != false)
								{
									// Got a user with this userid already, so attempt a login challenge.						
									$rv = $this->getdefaultchallange($uuid, $r_u["userid"], $challenge, $r_u["uname"]);
									
									$challenge_string = $challenge->toBase64URL();
									
									// Save the challenge so it can be checked later
									$this->savechallenge($sdbh, $uuid, $challenge_string);
									
									$s_u->free();
									$sdbh->close();
									return($rv);
								}
								else
								{
									// UUID
									$s_u->free();
									$sdbh->close();
									throw new RestException(403, "Unable to create UUID.");
								}
							}
							else
							{
								// INACTIVE status
								$s_u->free();
								$sdbh->close();
								throw new RestException(403, "Access denied.");
							}
						}
						else
						{
							// No userid
							//throw new RestException(403, "Unable to Find user: ".$userid);		
							
							// New user - create a registration user
							
							// get display name first
							$displayName = $rd["displayName"]?? "";
							
							// register user
							$user_uuid = $this->registeruser($sdbh, $rd["username"], $displayName, $this->ustat_pending);
							
							if($user_uuid == false)
							{
								// free up handlers
								$s_u->free();
								$sdbh->close();
								
								throw new RestException(403, "Unable to create user:".$userid);
							}
							
							$rv = $this->getdefaultchallange($user_uuid, $userid, $challenge, $displayName);
							
							$challenge_string = base64_encode($challenge->getBinaryString());
							
							// Save the challenge so it can be checked later
							$this->savechallenge($sdbh, $user_uuid, $challenge_string);
							
							// free up handlers
							$s_u->free();
							$sdbh->close();
							
							return($rv);
						}
					}
				}
			}
			else
			{
				// No userid
				throw new RestException(403, "Access denied.");
			}
		}
		else
		{
			// No post data
			throw new RestException(403, "Access denied.");
		}
	}			

	// Method to request registration using a FIDO user-authenticated token/client.
	// This request comes from a form and will send the userid as the argument.
	// This methos must then determine if the userid is not already used and create a temporary 
	// registration record for it, along with a challenge. The challenge, userid, appid and appname
	// are returned so that the client can create a new credential for the userid for the device/application.
	function
	postrequestregister($request_data = NULL)
	{
		require_once("/authentx/core/authentx5/defs_core.php");
		date_default_timezone_set(DATE_TIMEZONE);

		// Simple method to:
		// 1. read the JSON request to register a userid
		// 2. check userid is already in the database (pre-register)
		// 3. return a registration challenge
				
		// Check the request for validity
		if ($request_data != NULL)
		{
			//$rd = json_decode($request_data);
			$rd = $request_data;
			if (isset($rd["userid"]))
			{
				$userid = $rd["userid"];
				
				$sdbh = new mysqli($this->db_host, $this->db_user, $this->db_pw, $this->db_dbname);
				if (!$sdbh->connect_errno)
				{
					// Attempt to locate the user
					$q_u = "select * from user where userid='".$sdbh->real_escape_string($userid)."' ";
					$s_u = $sdbh->query($q_u);
					if ($s_u)
					{
						$r_u = $s_u->fetch_assoc();
						if (!empty($r_u))
						{
							// create challenge
							$challenge = $this->createChallenge();

							// User status must be either PENDING or ACTIVE
							if (($r_u["status"] == $this->ustat_pending) || ($r_u["status"] == $this->ustat_active))
							{
								$uuid = $r_u["uuid"];
								
								// check if uuid is not null
								// create and save it
								if($uuid == null)
									$uuid = $this->createsave_uuid($sdbh, $userid);
								
								if($uuid != false)
								{
									// Got a user with this userid already, so attempt a login challenge.						
									$rv = $this->getdefaultchallange($uuid, $r_u["userid"], $challenge, $r_u["uname"]);
									
									$challenge_string = base64_encode($challenge->getBinaryString());
									
									// Save the challenge so it can be checked later
									$this->savechallenge($sdbh, $uuid, $challenge_string);
									
									$s_u->free();
									$sdbh->close();
									return($rv);
								}
								else
								{
									// UUID
									$s_u->free();
									$sdbh->close();
									throw new RestException(403, "Unable to create UUID.");
								}
							}
							else
							{
								// INACTIVE status
								$s_u->free();
								$sdbh->close();
								throw new RestException(403, "Access denied.");
							}
						}
						else
						{
							// No userid
							throw new RestException(403, "Unable to Find user: ".$userid);		
							
							// New user - create a registration user
							$user_uuid = $this->registeruser($sdbh, $rd["userid"], $this->ustat_pending);
							
							if($user_uuid == false)
							{
								// free up handlers
								$s_u->free();
								$sdbh->close();
								
								throw new RestException(403, "Unable to create user:".$userid);
							}
							
							$rv = $this->getdefaultchallange($user_uuid, $challenge, $rd["userid"]);
							
							$challenge_string = base64_encode($challenge->getBinaryString());
							
							// Save the challenge so it can be checked later
							$this->savechallenge($sdbh, $user_uuid, $challenge_string);
							
							// free up handlers
							$s_u->free();
							$sdbh->close();
							
							return($rv);
						}
					}
				}
			}
			else
			{
				// No userid
				throw new RestException(403, "Access denied.");
			}
		}
		else
		{
			// No post data
			throw new RestException(403, "Access denied.");
		}
	}	
	
	// Simple method to:
	// 1. read the registration credentials JSON message and decode
	// 2. check the challenge and save credentials in the database for the user
	// 3. log in the user if OK
	// 4. return a login status boolean	
	function
	postresult($request_data = NULL)
	{
		require_once("/authentx/core/authentx5/defs_core.php");
		date_default_timezone_set(DATE_TIMEZONE);

		$xmlrequest = file_get_contents("php://input");
		file_put_contents("/tmp/request_result.txt", $xmlrequest);
		// Simple method to:
		// 1. read the JSON request to register a userid
		// 2. check the posted data matches with the saved data for the user
		// 3. return a success iv evrything matches
				
		// Check the request for validity
		if ($request_data != NULL)
		{
			//$rd = json_decode($request_data);
			$rd = $request_data;
			$response = ($rd["response"]) ?? [];
			
			if (isset($response["clientDataJSON"]) && isset($response["attestationObject"]) && isset($response["id"]))
			{
				//$clientDataHash = hash('sha256', $clientDataJSON, true);
				$clientData_string = base64_decode($rd["clientDataJSON"]);
				$clientData = json_decode($clientData_string);
				$attestationObject = base64_decode($rd["attestationObject"]);
				$userUUID = $rd["id"];				
				$rpId = $rd["rpID"];				
				
				/* get challange info */			
				//connect to db first
				$sdbh = new mysqli($this->db_host, $this->db_user, $this->db_pw, $this->db_dbname);
				if (!$sdbh->connect_errno)
				{
					$uuid = $userUUID;
					
					// format user id
					$f_uuid = $this->formatbinary($uuid);
					
					// get challenge bit string to compare
					$session_challenge_bstring = $this->getchallange($sdbh, $f_uuid, $this->fstat_pending);
				
					
					if($session_challenge_bstring != false)
					{
						$challange = base64_decode($session_challenge_bstring["challenge"]);
						$fid = $session_challenge_bstring["fid"];

						// 1. Verify that the value of C.type is webauthn.create.
						if (!\property_exists($clientData, 'type') || $clientData->type !== 'webauthn.create')
							throw new RestException(403, 'Invalid WebAuthn type');
						
						// 2. Verify that the value of C.challenge matches the challenge that was sent to the authenticator in the create() call.
						if (!\property_exists($clientData, 'challenge') || ByteBuffer::fromBase64Url($clientData->challenge)->getBinaryString() !== $challange) 
						{
							throw new RestException(403,'Invalid Challenge');
						}
						
						// 3. Verify that the value of C.origin matches the Relying Party's origin.
						if (!\property_exists($clientData, 'origin') || !$this->checkorigin($clientData->origin)) 
						{
							throw new RestException(403, 'Invalid origin: '.$clientData->origin);
						}
						
						
						include_once("attestation/AttestationObject.php");
						
						// Attestation
						$attestationObject = new AttestationObject($attestationObject, $this->formats);
						
						// 4. Verify that the RP ID hash in authData is indeed the SHA-256 hash of the RP ID expected by the RP.
						if (!$attestationObject->validateRpIdHash(hash('sha256', $rpId, true))) 
						{
							throw new RestException(403,'Invalid rpId hash');
						}
						
						// 5. Verify that attStmt is a correct attestation statement, conveying a valid attestation signature
						if (!$attestationObject->validateAttestation(hash('sha256', $clientData_string, true))) {
							throw new RestException(403,'invalid certificate signature');
						}
						
						/*
						// Android-SafetyNet: if required, check for Compatibility Testing Suite (CTS).
						if ($requireCtsProfileMatch && $attestationObject->getAttestationFormat() instanceof Attestation\Format\AndroidSafetyNet) {
							if (!$attestationObject->getAttestationFormat()->ctsProfileMatch()) {
								 throw new WebAuthnException('invalid ctsProfileMatch: device is not approved as a Google-certified Android device.', WebAuthnException::ANDROID_NOT_TRUSTED);
							}
						}
						
						// 6. If validation is successful, obtain a list of acceptable trust anchors
						$rootValid = is_array($this->_caFiles) ? $attestationObject->validateRootCertificate($this->_caFiles) : null;
						if ($failIfRootMismatch && is_array($this->_caFiles) && !$rootValid) {
							throw new WebAuthnException('invalid root certificate', WebAuthnException::CERTIFICATE_NOT_TRUSTED);
						}
						*/
						
						// 6. Verify that the User Present bit of the flags in authData is set.
						$userPresent = $attestationObject->getAuthenticatorData()->getUserPresent();
						if ($this->requireUserPresent && !$userPresent) {
							throw new RestException(403,'User not present during authentication');
						}
						
						// 7. If user verification is required for this registration, verify that the User Verified bit of the flags in authData is set.
						$userVerified = $attestationObject->getAuthenticatorData()->getUserVerified();
						if ($this->requireUserVerification && !$userVerified) {
							throw new RestException(403,'user not verified during authentication');
						}
					
						// save registration data
						$pkey_pem = $attestationObject->getAuthenticatorData()->getPublicKeyPem();
						$credentialId = $attestationObject->getAuthenticatorData()->getCredentialId();
						$credentialAAGUID_bin = $attestationObject->getAuthenticatorData()->getAAGUID();
						$credentialAAGUID = (strlen(trim(bin2hex($credentialAAGUID_bin))) == 32)? trim(bin2hex($credentialAAGUID_bin)) : "default";

						if (isset($this->aaguid_mapping[$credentialAAGUID]))
							$auth_name = $this->aaguid_mapping[$credentialAAGUID];
						else
							$auth_name = $this->aaguid_mapping["default"];
						
						$r = $this->savepubkey($sdbh, $f_uuid, $pkey_pem, base64_encode($credentialId), $auth_name[0], $fid);
						
						if($r == false)
							throw new RestException(403,'Error saving public key.');
						
						$msg = 'Registration Success.';

						$rv = [];
						$rv["msg"] = $msg;
						$rv["success"] = true;
						
						// close db connection;
						$sdbh->close();
						
						return $rv;
					}
					else
					{
						// No userid
						throw new RestException(403, "Unable to fetch users session.");
					}
				}
				else
				{
					// No userid
					throw new RestException(403, "Unable to Connect to DB.");
				}
			}
			else
			{
				// No userid
				throw new RestException(403, "Missign required elements in Request.");
			}
		}
		else
		{
			// No post data
			throw new RestException(403, "Access denied.");
		}
	}

	// Simple method to:
	// 1. read the registration credentials JSON message and decode
	// 2. check the challenge and save credentials in the database for the user
	// 3. log in the user if OK
	// 4. return a login status boolean	
	function
	postregister($request_data = NULL)
	{
		require_once("/authentx/core/authentx5/defs_core.php");
		date_default_timezone_set(DATE_TIMEZONE);

	
		// Simple method to:
		// 1. read the JSON request to register a userid
		// 2. check the posted data matches with the saved data for the user
		// 3. return a success iv evrything matches
				
		// Check the request for validity
		if ($request_data != NULL)
		{
			//$rd = json_decode($request_data);
			$rd = $request_data;
			
			if (isset($rd["clientDataJSON"]) && isset($rd["attestationObject"]) && isset($rd["userUUID"]))
			{
				//$clientDataHash = hash('sha256', $clientDataJSON, true);
				$clientData_string = base64_decode($rd["clientDataJSON"]);
				$clientData = json_decode($clientData_string);
				$attestationObject = base64_decode($rd["attestationObject"]);
				$userUUID = $rd["userUUID"];				
				$rpId = $rd["rpID"];				
				
				/* get challange info */			
				//connect to db first
				$sdbh = new mysqli($this->db_host, $this->db_user, $this->db_pw, $this->db_dbname);
				if (!$sdbh->connect_errno)
				{
					$uuid = $userUUID;
					
					// format user id
					$f_uuid = $this->formatbinary($uuid);
					
					// get challenge bit string to compare
					$session_challenge_bstring = $this->getchallange($sdbh, $f_uuid, $this->fstat_pending);
				
					
					if($session_challenge_bstring != false)
					{
						$challange = base64_decode($session_challenge_bstring["challenge"]);
						$fid = $session_challenge_bstring["fid"];

						// 1. Verify that the value of C.type is webauthn.create.
						if (!\property_exists($clientData, 'type') || $clientData->type !== 'webauthn.create')
							throw new RestException(403, 'Invalid WebAuthn type');
						
						// 2. Verify that the value of C.challenge matches the challenge that was sent to the authenticator in the create() call.
						if (!\property_exists($clientData, 'challenge') || ByteBuffer::fromBase64Url($clientData->challenge)->getBinaryString() !== $challange) 
						{
							throw new RestException(403,'Invalid Challenge');
						}
						
						// 3. Verify that the value of C.origin matches the Relying Party's origin.
						if (!\property_exists($clientData, 'origin') || !$this->checkorigin($clientData->origin)) 
						{
							throw new RestException(403, 'Invalid origin: '.$clientData->origin);
						}
						
						
						include_once("attestation/AttestationObject.php");
						
						// Attestation
						$attestationObject = new AttestationObject($attestationObject, $this->formats);
						
						// 4. Verify that the RP ID hash in authData is indeed the SHA-256 hash of the RP ID expected by the RP.
						if (!$attestationObject->validateRpIdHash(hash('sha256', $rpId, true))) 
						{
							throw new RestException(403,'Invalid rpId hash');
						}
						
						// 5. Verify that attStmt is a correct attestation statement, conveying a valid attestation signature
						if (!$attestationObject->validateAttestation(hash('sha256', $clientData_string, true))) {
							throw new RestException(403,'invalid certificate signature');
						}
						
						/*
						// Android-SafetyNet: if required, check for Compatibility Testing Suite (CTS).
						if ($requireCtsProfileMatch && $attestationObject->getAttestationFormat() instanceof Attestation\Format\AndroidSafetyNet) {
							if (!$attestationObject->getAttestationFormat()->ctsProfileMatch()) {
								 throw new WebAuthnException('invalid ctsProfileMatch: device is not approved as a Google-certified Android device.', WebAuthnException::ANDROID_NOT_TRUSTED);
							}
						}
						
						// 6. If validation is successful, obtain a list of acceptable trust anchors
						$rootValid = is_array($this->_caFiles) ? $attestationObject->validateRootCertificate($this->_caFiles) : null;
						if ($failIfRootMismatch && is_array($this->_caFiles) && !$rootValid) {
							throw new WebAuthnException('invalid root certificate', WebAuthnException::CERTIFICATE_NOT_TRUSTED);
						}
						*/
						
						// 6. Verify that the User Present bit of the flags in authData is set.
						$userPresent = $attestationObject->getAuthenticatorData()->getUserPresent();
						if ($this->requireUserPresent && !$userPresent) {
							throw new RestException(403,'User not present during authentication');
						}
						
						// 7. If user verification is required for this registration, verify that the User Verified bit of the flags in authData is set.
						$userVerified = $attestationObject->getAuthenticatorData()->getUserVerified();
						if ($this->requireUserVerification && !$userVerified) {
							throw new RestException(403,'user not verified during authentication');
						}
					
						// save registration data
						$pkey_pem = $attestationObject->getAuthenticatorData()->getPublicKeyPem();
						$credentialId = $attestationObject->getAuthenticatorData()->getCredentialId();
						$credentialAAGUID_bin = $attestationObject->getAuthenticatorData()->getAAGUID();
						$credentialAAGUID = (strlen(trim(bin2hex($credentialAAGUID_bin))) == 32)? trim(bin2hex($credentialAAGUID_bin)) : "default";

						if (isset($this->aaguid_mapping[$credentialAAGUID]))
							$auth_name = $this->aaguid_mapping[$credentialAAGUID];
						else
							$auth_name = $this->aaguid_mapping["default"];
						
						$r = $this->savepubkey($sdbh, $f_uuid, $pkey_pem, base64_encode($credentialId), $auth_name[0], $fid);
						
						if($r == false)
							throw new RestException(403,'Error saving public key.');
						
						$msg = 'Registration Success.';

						$rv = array();
						$rv["msg"] = $msg;
						$rv["success"] = true;
						
						// close db connection;
						$sdbh->close();
						
						return $rv;
					}
					else
					{
						// No userid
						throw new RestException(403, "Unable to fetch users session.");
					}
				}
				else
				{
					// No userid
					throw new RestException(403, "Unable to Connect to DB.");
				}
			}
			else
			{
				// No userid
				throw new RestException(403, "Missign required elements in Request.");
			}
		}
		else
		{
			// No post data
			throw new RestException(403, "Access denied.");
		}
	}
		

	// Simple method to:
	// 1. get the JSON login request and decode it
	// 2. check for user registered in the database
	// 3. if registered then return a challenge
	// 4. return a response to the caller	
	function
	postlogin($request_data = NULL)
	{
		require_once("/authentx/core/authentx5/defs_core.php");
		date_default_timezone_set(DATE_TIMEZONE);
		
		// Check the request for validity
		if ($request_data != NULL)
		{
			//$rd = json_decode($request_data);
			$rd = $request_data;
			if (isset($rd["userid"]))
			{
				$sdbh = new mysqli($this->db_host, $this->db_user, $this->db_pw, $this->db_dbname);
				if (!$sdbh->connect_errno)
				{
					// Attempt to locate the user
					$q_u = "select * from user where userid='".$sdbh->real_escape_string($rd["userid"])."' ";
					$s_u = $sdbh->query($q_u);
					if ($s_u)
					{
						$r_u = $s_u->fetch_assoc();
						if (!empty($r_u))
						{
							// User status must be ACTIVE
							if ($r_u["status"] == $this->ustat_active)
							{
								$uuid = $r_u["uuid"];
								$uid = $r_u["uid"];
								
								// create new challange for user
								$challenge = $this->createChallenge();
								
								// update users fido tokens with new challange
								$rv = $this->updatechallenge($sdbh, $uuid, $challenge);
								
								if($rv == false)
								{
									$s_u->free();
									$sdbh->close();
									throw new RestException(403, "Error fetching challenge.");
								}
								
								// get credential id	
								$devid_array = $this->getcredid($sdbh, $uid);
								
								if(count($devid_array) <= 0)
								{
									// Unable to fetch device id
									$s_u->free();
									$sdbh->close();
									throw new RestException(403, "Access denied. Unable to fetch device id.");
								}

								//format each credid
								$cred_id = array();

								foreach($devid_array as $devid)
								{
									$devid_b64 = base64_decode($devid);
									$cred_id[] = $devid instanceof ByteBuffer ? $devid : new ByteBuffer($devid_b64);  // binary
								}
								
								$rv = $this->getdefaultchallangelogin(new ByteBuffer(base64_decode($challenge)), $cred_id, $uuid);
								
								$s_u->free();
								$sdbh->close();
								return $rv;
							}
							else
							{
								// INACTIVE status
								$s_u->free();
								$sdbh->close();
								throw new RestException(403, "Access denied-ustatus.");
							}
						}
						else
						{
							// User not pre-registered
							$s_u->free();
							$sdbh->close();
							throw new RestException(403, "Access denied-unregistered.");
						}
					}
				}
			}
			else
			{
				// No userid
				throw new RestException(403, "Access denied.");
			}
		}
		else
		{
			// No post data
			throw new RestException(403, "Access denied.");
		}
	}
	
	
	// Simple method to:
	// 1. read the JSON encrypted challenge (encrypted with token private key)
	// 2. authenticate the challenge by decrypting with public key
	// 3. if auth OK then return login OK
	function
	postloginchallenge($request_data = NULL)
	{
		require_once("/authentx/core/authentx5/defs_core.php");
		require_once("/authentx/core/http7/cl-appointments5_8.php");
		date_default_timezone_set(DATE_TIMEZONE);
		define("SESS_NAME", "authentxappts");
		
		$myappt = new authentxappointments();
		
		// clear the session
		$myappt->clearsession();
	
		// Simple method to:
		// 1. read the JSON request to register a userid
		// 2. check the posted data matches with the saved data for the user
		// 3. return a success iv evrything matches
				
		// Check the request for validity
		if ($request_data != NULL)
		{
			//$rd = json_decode($request_data);
			$rd = $request_data;
			
			if (isset($rd["clientDataJSON"]) && isset($rd["authenticatorData"]) && isset($rd["signature"]) && isset($rd["userUUID"]) && isset($rd["id"]) && isset($rd["rpID"]))
			{
				$clientData_string = base64_decode($rd["clientDataJSON"]);
				$clientDataHash = \hash('sha256', $clientData_string, true);
				$clientData = json_decode($clientData_string);
				$authenticatorData = base64_decode($rd["authenticatorData"]);
				$authenticatorObj = new AuthenticatorData($authenticatorData);
				$signature = base64_decode($rd["signature"]);
				//$userHandle = base64_decode($rd["userHandle"]) ?? false;
				$uuid = $rd["userUUID"];
				$cred_id = $rd["id"];
				$rpID = $rd["rpID"];
				
				$credentialPublicKey = null;			
				
				/* get challange info */			
				//connect to db first
				$sdbh = new mysqli($this->db_host, $this->db_user, $this->db_pw, $this->db_dbname);
				if (!$sdbh->connect_errno)
				{
					// format user id
					$f_uuid = $this->formatbinary($uuid);
								
					// Attempt to locate the user
					$q_u = "select * from user where uuid='".$sdbh->real_escape_string($f_uuid)."' ";
					$s_u = $sdbh->query($q_u);
					
					if ($s_u)
					{
						$r_u = $s_u->fetch_assoc();
						$s_u->free();
						if (!empty($r_u))
						{
							// User status must be either PENDING or ACTIVE
							if ($r_u["status"] == $this->ustat_active)
							{
								// get user data
								$u_uname = $r_u["uname"];
								$u_email = $r_u["email"];
								$u_status = $r_u["status"];
								$u_id = $r_u["userid"];
								//$u_priv = $r_u["privilege"];
								//$u_tabmask = $r_u["tabmask"];
								
								// get challenge bit string to compare
								$session_challenge_bstring = $this->getchallange($sdbh, $f_uuid, $this->fstat_active, $cred_id);
								
								if($session_challenge_bstring != false)
								{
									$challange = base64_decode($session_challenge_bstring["challenge"]);
									$fid = $session_challenge_bstring["fid"];

									// 1. Verify that the RP ID is indeed the intended.
									if ($rpID != $this->rpId)
										throw new RestException(403,'Invalid rpId');
									
									
									// 2. If the allowCredentials option was given when this authentication ceremony was initiated,
									//    verify that credential.id identifies one of the public key credentials that were listed in allowCredentials.
									//    -> verified when we search the DB for the userUUID given in the POST. The userUUID sent will be the id given in the allowCredentials 

									// 2. If credential.response.userHandle is present, verify that the user identified
									//    by this value is the owner of the public key credential identified by credential.id.
									//    -> TO BE VERIFIED BY IMPLEMENTATION

									// 3. Using credential’s id attribute (or the corresponding rawId, if base64url encoding is
									//    inappropriate for your use case), look up the corresponding credential public key.
									//    -> TO BE LOOKED UP BY IMPLEMENTATION

									// 4. Let JSONtext be the result of running UTF-8 decode on the value of cData.
									if (!\is_object($clientData))
										throw new RestException(403,'Invalid client data');								

									// 7. Verify that the value of C.type is the string webauthn.get.
									if (!\property_exists($clientData, 'type') || $clientData->type !== 'webauthn.get') {
										throw new RestException(403, 'Invalid type');
									}
									
									// 8. Verify that the value of C.challenge matches the challenge that was sent to the
									//    authenticator in the PublicKeyCredentialRequestOptions passed to the get() call.
									if (!\property_exists($clientData, 'challenge') || ByteBuffer::fromBase64Url($clientData->challenge)->getBinaryString() !== $challange) {
										throw new RestException(403, 'Invalid challenge');
									}

									// 9. Verify that the value of C.origin matches the Relying Party's origin.
									if (!\property_exists($clientData, 'origin') || !$this->checkOrigin($clientData->origin)) {
										throw new RestException(403, 'Invalid origin');
									}

									// 11. Verify that the rpIdHash in authData is the SHA-256 hash of the RP ID expected by the Relying Party.
									if ($authenticatorObj->getRpIdHash() !== hash('sha256', $this->rpId, true)) {
										throw new RestException(403, 'invalid rpId hash');
									}

									// 12. Verify that the User Present bit of the flags in authData is set
									if ($this->requireUserPresent && !$authenticatorObj->getUserPresent()) {
										throw new RestException(403, 'user not present during authentication', WebAuthnException::USER_PRESENT);
									}

									// 13. If user verification is required for this assertion, verify that the User Verified bit of the flags in authData is set.
									if ($this->requireUserVerification && !$authenticatorObj->getUserVerified()) {
										throw new RestException(403, 'user not verificated during authentication', WebAuthnException::USER_VERIFICATED);
									}

									// 14. Verify the values of the client extension outputs
									//     (extensions not implemented)

									// 15. Using the credential public key looked up in step 3, verify that sig is a valid signature
									//     over the binary concatenation of authData and hash.
									$dataToVerify = '';
									$dataToVerify .= $authenticatorData;
									$dataToVerify .= $clientDataHash;
									
									// get public key saved
									$pubkey_pem = $this->getpubkey($sdbh, $fid);
									
									$publicKey = \openssl_pkey_get_public($pubkey_pem);
									if ($publicKey === false) {
										throw new RestException(403, 'public key invalid');
									}

									if (\openssl_verify($dataToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
										throw new RestException(403, 'invalid signature', WebAuthnException::INVALID_SIGNATURE);
									}

									$signatureCounter = $authenticatorObj->getSignCount();
									if ($signatureCounter !== 0) {
										$this->_signatureCounter = $signatureCounter;
									}

									// 17. If either of the signature counter value authData.signCount or
									//     previous signature count is nonzero, and if authData.signCount
									//     less than or equal to previous signature count, it's a signal
									//     that the authenticator may be cloned
									if (isset($prevSignatureCnt) && ($prevSignatureCnt !== null)) {
										if ($signatureCounter !== 0 || $prevSignatureCnt !== 0) {
											if ($prevSignatureCnt >= $signatureCounter) {
												throw new RestException(403, 'signature counter not valid');
											}
										}
									}
									
									// update user login info
									$r = $this->updateuser($sdbh, $f_uuid);
									
									$sdbh->close();
									
									if($r == false)
									{
										throw new RestException(403, 'Unable to update user login info.');
									}
									else
									{
										// start the session
										session_start();
										
										// setup a session for this user and return the uid
										$myappt->session_setsiteid($this->_siteid);
										$myappt->session_sethost($this->db_host);
										$myappt->restampsession();
										$myappt->session_setuuid($f_uuid);
										$myappt->session_setuuname($u_uname);
										$myappt->session_setuemail($u_email);
										$myappt->session_setuuserid($u_id);
										//$myappt->session_setupriv($u_priv);
										//$myappt->session_setutabmask($u_tabmask);

										$msg = 'Login Success.';

										$rv = array();
										$rv["msg"] = $msg;
										$rv["success"] = true;
										
										return $rv;
									}
								}
								else
								{
									$s_u->free();
									$sdbh->close();
								
									// No userid
									throw new RestException(403, "Unable to fetch users session.");
								}
							}
							else
							{
								// INACTIVE status
								$s_u->free();
								$sdbh->close();
								
								throw new RestException(403, "User status not active.");
							}
						}
						else
						{
							$sdbh->close();
							
							// No userid
							throw new RestException(403, "Unable to fetch user: ".$userid);
						}
					}
					else
					{
						$sdbh->close();
						
						// No userid
						throw new RestException(403, "Unable to fetch user: ".$userid);
					}
				}
				else
				{
					// No userid
					throw new RestException(403, "Unable to connect to db.");
				}
			}
			else
			{
				// No userid
				throw new RestException(403, "Missing Post Data.");
			}
		}
		else
		{
			// No post data
			throw new RestException(403, "Access denied.");
		}
	}

	
}
?>
