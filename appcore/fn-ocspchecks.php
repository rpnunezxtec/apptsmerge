<?php

// FUNCTION LIST:
// der2pem(der_data)
// pem2der(pem_data)
// xpki_verify(subject_cert_pem, issuing_cer_pem)  -- RSA decrypt -- deprecated
// cert_checksignature(theCert, theIssuer) --  deprecated
// cert_checksig(subject_cert, issuer_cert_file) -- new
// cert_ckeckskid(theCert)
// get_aia_urls(theCert)
// get_aki_from_cert(theCert)
// get_p7c_cert_from_uri(issuerurl, savetofileflag, pemlistfilename)
// cert_get_pemissuer(kpcscerts, getfromfileflag, pemlistfilename, aki)
// cert_checkexpire(pemcert)
// cert_checkocsp(theCert, theIssuer, ocspurl)

$hash_algos = array (
	"\x2a\x86\x48\x86\xf7\x0d\x01\x01\x0b" => 'sha256', // "sha256WithRSAEncryption"
	"\x2a\x86\x48\xce\x3d\x04\x03\x02" => 'sha256', 	// "ecdsa-with-SHA256"
	"\x2a\x86\x48\xce\x3d\x04\x03\x03" => 'sha384',  	// "ecdsa-with-SHA384"
	"\x2a\x86\x48\xce\x3d\x04\x03\x04" => 'sha512',		// "ecdsa-with-SHA512"
	"\x2a\x86\x48\x86\xf7\x0d\x01\x01\x0a" => 'sha256', // "RSASSA-PSS"
	"\x2b\x0e\x03\x02\x1a" => 'sha1',					// 'SHA-1
	"\x60\x86\x48\x01\x65\x03\x04\x02\x01" => 'sha256',	// "SHA-256"
	"\x60\x86\x48\x01\x65\x03\x04\x02\x02" => 'sha384', //  SHA-384"
	"\x60\x86\x48\x01\x65\x03\x04\x02\x03" => 'sha512', //  SHA-512"
	"\x2a\x86\x48\x86\xf7\x0d\x01\x01\x05" => 'sha1',	//  sha1WithRSAEncryption
	);

$algo_codes = array (
	"sha256" => '1',
	"sha384" => '2',
	"sha512" => '3',
	"sha1"	 => '4',
	);

$cmdpath_ecdsa_verify = '.';
$cmdpath_xscvp = '/authentx/core/xscvp';

require_once '/authentx/core/http7/cl-derparser.php';

// *************************************************
// * FUNCTION:  DER 2 PEM:						   *
// *************************************************
function der2pem($der_data)
{
    $pem = chunk_split(base64_encode($der_data), 64, "\n");
    if(strlen($pem) > 1)
    	$pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
    else return false;
    return $pem;
}

// *************************************************
// * FUNCTION:  PEM 2 DER:						   *
// *************************************************
function pem2der($pem_data) {
   $begin = "CERTIFICATE-----";
   $end   = "-----END";
   $pem_data = substr($pem_data, strpos($pem_data, $begin)+strlen($begin));
   $pem_data = substr($pem_data, 0, strpos($pem_data, $end));
   $der = base64_decode($pem_data);
   return $der;
}


// *************************************************
// * STEP 1:  GET THE CERT:			   *
// *************************************************
/*
$issuertmpfilename = "";
$issuerlistfilename = "";

$error = false;
$errormsg = array();
$errorcode = array();

// cert retrieved from app.  Get from tmpfile for tests.
$tmpcertfilename= "/tmp/certiPathCert9a.cer";

if(file_exists($tmpcertfilename))
	$tmpcert = file_get_contents($tmpcertfilename);
else
{
	$error = true;
	$errormsg[] = "Unable to retrieve cert for verification.";
	$errorcode[] = "0x100";
}
*/

// *************************************************
// * FUNCTION:  XSVCP  PVal (Path Validation)      *
// *************************************************
function checkpathvalidation($host,$port, $cardtype,$certtype, $cert)
{
	global $cmdpath_xscvp;
	global $_debug;

	$cmd = trim($cmdpath_xscvp."/xscvp -ip ".$host." -p ".$port." -ct ".$cardtype." -it ".$certtype." -i ".$cert);
	$result = array();
	$rtn = exec($cmd, $result, $rtncode);
	
	if($_debug) debug("checkpathvalidation cmd=".$cmd);
	if($_debug) debug("rtncode=".$rtncode);
	if($_debug) debug("rtn=".$rtn);
	
	if($rtncode != '0')
		return "Internal error ($rtncode)";
	else
		return $rtn;
}


// *************************************************
// * FUNCTION:  Verify Signature: (harry)		   *
// *************************************************
function cert_checksig($subject_cert_der, $issuer_cert_file)
{
	global $hash_algos;
	global $algo_codes;
	global $cmdpath_ecdsa_verify;
	global $_debug;

	// get the tbs from the subject cert
	// get the signature from the subject cert
	// get the digest algorithm
	// digest the tbs
	// submit parameters to rsa_ecdsa_verify

	$length_bytes = substr($subject_cert_der, 6, 2);
	$len_tbs = hexdec(bin2hex($length_bytes)) + 4;
	$subject_tbs = substr($subject_cert_der, 4, $len_tbs);
	
	// get the hash algorithm from the subject cert
	$siginfo_offset = $len_tbs + 4;
	$siginfo = substr($subject_cert_der, $siginfo_offset);
	
	if($_debug) debug("siginfo=".bin2hex($siginfo));
	
	$der_parser = new der_parser($siginfo);
	
	$der_parser->goto_node(0);
	$der_parser->goto_node_next(2, "OBJECT_IDENTIFIER");
	$nodeinfo = $der_parser->get_node_info();
	$algoid = $nodeinfo['val'];
	
	if($_debug) debug("algoid=".bin2hex($algoid));
	
	$der_parser->goto_node_next(1);
	$nodeinfo = $der_parser->get_node_info();
	$signature = substr($nodeinfo['val'], 1);
	
	if(!isset($hash_algos[$algoid]))
		return 'Unrecognized signature algorithm ('.bin2hex($algoid).')';
	
	$digest_type = $hash_algos[$algoid];
	$digest = hash($digest_type, $subject_tbs, true);


	$cmd = trim($cmdpath_ecdsa_verify."/rsa_ecdsa_verify $issuer_cert_file ".bin2hex($signature).' '.bin2hex($digest).' '.$algo_codes[$digest_type]);
	$result = array();				
	$testrtn = exec($cmd, $result, $rtncode);

	if($_debug) debug('checksig cmd:'.$cmd);
		
	if($rtncode != '0')
		return "Internal error ($rtncode)";
	else
		return $testrtn;
}

// **********************************************
// * FUNCTION:  Verify skid is correct on cert  *
// **********************************************
function
cert_checkskid($theCert_der)
{
	global $_debug;
	$rv = array();

	// X509 Certificate
	// (2)	tbsCertificate
	// (3)		version [0]
	// (3)		serialNumber
	// (3)		signature AlgorithmIdentifier
	// (3)		issuer
	// (3)		validity
	// (3)		subject
	// (3)		subjectPublicKeyInfo
	//	
	// (3)		extensions [3]
	// (2)	signatureAlgorithm
	// (2)	signatureValue

	if($_debug) debug("enter checkskid: ".bin2hex($theCert_der));

	//get the public key:
	$cert_parser = new der_parser($theCert_der);
	$cert_parser->goto_node(0);
	$cert_parser->goto_node_next(3);  // version
	$cert_parser->goto_node_next(3);  // serialNumber
	$cert_parser->goto_node_next(3);  // signature algoID
	$cert_parser->goto_node_next(3);  // issuer
	$cert_parser->goto_node_next(3);  // validity
	$cert_parser->goto_node_next(3);  // subject
	$cert_parser->goto_node_next(3);  // PKInfo
	$rtn = $cert_parser->goto_node_next(4, 'BIT_STRING');  // public key
	
	if($_debug && $rtn === false) debug('BIT_STRING not found...');
	
	$nodeinfo = $cert_parser->get_node_info();
	$bitstsring = $nodeinfo['val'];
		
	$publicKey = substr($bitstsring, 1);
	if($_debug) debug("publicKey=".bin2hex($publicKey));
	
	//if($publicKey == '') return false;
	
	$ski = $cert_parser->get_X509_subjectKeyIdentifier();
	if($_debug) debug("ski=".bin2hex($ski));

	//if($ski === false) 	return false;
		
	$my_ski = hash('sha1', $publicKey, true);
	if($_debug) debug("my_ski=".bin2hex($my_ski));

	if($ski == $my_ski)
		return true;
	else
		return false;
}


// *************************************************
// * STEP 2:  GET THE AIA, FETCH ISSUER (p7c file) *
// * $mpcertfilename:  location of cert file.
// * Use openssl cmd from cert file		   *
// * Fetch issuer url. Sample: http://http.apl-test.cite.fpki-lab.gov/aia/certsIssuedToICAMTestCardSigningCA.p7c
// * Fetch ocsp url.  Sample:  http://ocsp.apl-test.cite.fpki-lab.gov
// * Returns array $rv w/ ocspurl and issuerurl
// *************************************************
function
get_aia_urls($theCert)
{
	global $_debug;
	
	$rv = array();
	$rv["error"] = false;
	$rv["errormsg"] = array();
	$rv["errorcode"] = array();
	$rv["ocspurl"] = "";
	$rv["issuerurl"] = "";

	if( strlen($theCert) < 1)
	{
		$rv["error"] = true;
		$rv["errormsg"] = "Unable to retrieve cert.";
		return $rv;
	}

	$tmpcertfile = tempnam('/tmp', 'thecert_'); 
	file_put_contents($tmpcertfile, $theCert);

	$cmd_x509 = "openssl x509 -inform pem -text -noout -in ";

	//ISSUER: CA Issuers - URI: http://http.apl-test.cite.fpki-lab.gov/aia/certsIssuedToICAMTestCardSigningCA.p7c
	$cmd_issuerurl = " | grep \"CA Issuers - URI:http\" | sed 's/CA Issuers = URI\://' ";
	$issuerurlcmd = $cmd_x509.$tmpcertfile.$cmd_issuerurl;
	$issuerurl = exec($issuerurlcmd);
	$issuerurl = str_ireplace("CA Issuers - URI:", "", $issuerurl);
	$issuerurl = trim($issuerurl);
	if( strlen($issuerurl) < 1)
	{
		$rv["error"] = true;
		$rv["errormsg"][] = "Unable to retrieve issuer from AIA";
		$rv["errorcode"][] = "0x101";
	}
	else
		$rv["issuerurl"] = $issuerurl;
	
	if($_debug) debug( "   CA Issuers - URI: ".$rv["issuerurl"]);

	//OCSP:  OCSP URL = http://ocsp.apl-test.cite.fpki-lab.gov
	$cmd_ocspurl = " | grep \"OCSP - URI:http\" | sed 's/OCSP - URI\://' ";
	$ocspcmd = $cmd_x509.$tmpcertfile.$cmd_ocspurl;
	$ocspurl = exec($ocspcmd);
	$ocspurl = str_ireplace("OCSP - URI:", "", $ocspurl);
	$ocspurl = trim($ocspurl);
	if( strlen($ocspurl) < 1)
	{
		$rv["error"] = true;
		$rv["errormsg"][] = "Unable to retrieve ocsp from AIA";
		$rv["errorcode"][] = "0x102";
	}
	else
		$rv["ocspurl"] = $ocspurl;
	
	if($_debug) debug("OCSP URL = ".$ocspurl);

	unlink($tmpcertfile);

	return $rv;
}

// *************************************************
// * FUNCTION:  GET THE AKI FROM CERT  		   *
// * AKI is compared w SKI to retrieve issuer      *
// * returns the aki or false if error.		   *
// *************************************************
function
get_aki_from_cert($theCert)
{
	// first parse out the cert, then grab the aki or the ski, depending on the cert.
	$certcontents = array();
	$certcontents = openssl_x509_parse($theCert);
	//print_r($certcontents);
	$aki = trim(str_ireplace("keyid:", "", $certcontents["extensions"]["authorityKeyIdentifier"]));
	//print "AKI: ".$aki."\n";
	if(strlen($aki) < 1)
		return false;
	else
		return $aki;
}

// *************************************************
// * FUNCTION:  GET THE SKI FROM CERT  		   *
// * AKI is compared w SKI to retrieve issuer      *
// * returns the aki or false if error.		   *
// *************************************************
function
get_ski_from_cert($theCert)
{
	// first parse out the cert, then grab the aki or the ski, depending on the cert.
	$certcontents = array();
	$certcontents = openssl_x509_parse($theCert);
	
	//print_r($certcontents);
	$ski = trim(str_ireplace("keyid:", "", $certcontents["extensions"]["subjectKeyIdentifier"]));
	//print "AKI: ".$aki."\n";
	if(strlen($ski) < 1)
		return false;
	else
		return $ski;
}


// ******************************************************
// * STEP 4:  Get P7c CERT from URI, print PEMs to file *
// * $issuerurl = "http://http.apl-test.cite.fpki-lab.gov/aia/certsIssuedToICAMTestCardSigningCA.p7c";     // entrust 2 certs
// * $issuerurl = "http://http.apl-test.cite.fpki-lab.gov/aia/certsIssuedToICAMTestCardSigningCA.p7c";     // 1 cert golden card issuer.
// * $pemlistfilename = "issuerlist.pem";
// * MAKE SURE TO UNLINK THESE AT THE END.
// * $issuerlistfilename = "issuerlist.der";
// * return false if theres an error.
// ******************************************************

function
get_p7c_cert_from_uri($issuerurl, $saveToFile = false, $pemlistfilename = "")
{
	$rv = array();
	$rv["error"] = false;
	$rv["errormsg"] = "";
	$rv["errorcode"] = "";
	$rv["pkcscerts"] = "";

	$p7filename = tempnam('/tmp', 'p7file_'); // ../applists/p7certs.p7c";
	$status = false;

	if( strlen($issuerurl) < 1)
	{
		$rv["error"] = true;
		$rv["errormsg"] = "Error getting issuer url";
		$rv["errorcode"] = "0x201";
		return $rv;
	}

	$curl_conn = curl_init();

	curl_setopt($curl_conn, CURLOPT_URL, $issuerurl);
	curl_setopt($curl_conn, CURLOPT_RETURNTRANSFER, true);

	$pissuercerts = curl_exec($curl_conn);
	curl_close($curl_conn);	

	if(strlen($pissuercerts) > 0)
	{
		$file_extension = substr($issuerurl, -3);
		
		if(strcmp($file_extension, "p7c") == 0)
		{
			file_put_contents($p7filename, $pissuercerts);
			
			//check if output to file is needed.
			$cmd_pkcs = "openssl pkcs7 -inform DER -in $p7filename  -print_certs";
			if($saveToFile)
				$cmd_pkcs += " -out ".$pemlistfilename;
			$ans = array();
			$rexec = exec($cmd_pkcs, $ans, $res);
			
			// convert the array to string
			$pkcscerts = implode("\n", $ans);

			if( strlen($pkcscerts) < 1)
			{
				$rv["error"] = true;
				$rv["errormsg"] = "Error getting issuer cert";
			}
			else if( ($saveToFile == true) && (file_exists($pemlistfilename) == false) )
			{
				$rv["error"] = true;
				$rv["errormsg"] = "Error saving Issuer";
			}
			else
			{
				//no errors.  return pkcscerts:
				$rv["pkcscerts"] = $pkcscerts;
			}
		}
		else
		{
			
			
			// file is a certificate
			$rv["pkcscerts"] = der2pem($pissuercerts);
		}
	}
	else
	{
		$rv["error"] = true;
		$rv["errormsg"] = "Unable to retrieve issuer information";
	}

	unlink($p7filename);
	return $rv;

}


//for tests:
//$issuerlistfilename = "/tmp/CertsIssuedToNFIMediumSSPCA.p7c";
//$cmd_pkcstofile = "openssl pkcs7 -inform DER -in $issuerlistfilename  -print_certs -out ".$pemlistfilename;
function
cert_get_pemissuer($pkcscerts = "", $getFromFile = false, $pemlistfilename = "", $aki)
{
	$rv = array();
	$rv["error"] = false;
	$rv["errormsg"] = "";
	$rv["errorcode"] = "";
	$rv["found"] = false;
	$rv["pemcert"] = "";

	if( (strlen($pkcscerts) < 1) && ($getFromFile == false) )
	{
		$rv["error"] = true;
		$rv["errormsg"] = "Error retrieving issuing info.";
		return $rv;
	}

	if($getFromFile)
	{
		// if was out it to a file for easier parsing:
		$pkcscerts = file_get_contents($pemlistfilename);
	}

	$findmebegin = "-----BEGIN CERTIFICATE-----";
	$findmeend = "-----END CERTIFICATE-----";
	$findmecount = strlen($findmeend);

	$certcount = substr_count($pkcscerts, $findmebegin);
	$rv["count"] = $certcount;

	if($certcount > 0)
	{
		//print "There is ".$certcount." certificate in file\n";
		$s = 0;
		$e = 0;
		$found = false;
		while(( $pos = strpos($pkcscerts, $findmebegin, $s)) !== false)
		{
			$posend = strpos($pkcscerts, $findmeend, $e);
			if($found == false)
			{
				$issuerpem = substr($pkcscerts, $pos, $posend-$pos+$findmecount);

				// pem cert needs to be stored for ocsp checks.
				//file_put_contents($issuertmpfile, $issuserpem);
				$issuerdata = openssl_x509_parse($issuerpem);
				// *******************************************
				// * Check if AKI of cert === SKI of issuer. *
				// *******************************************
				// first parse out the cert, then grab the aki or the ski, depending on the cert.
				$ski = trim($issuerdata["extensions"]["subjectKeyIdentifier"]);
				if(strlen($ski) < 1)
				{
					$rv["error"] = true;
					$rv["errormsg"] = "Unable to retrieve SKI for verification.";
					$rv["errorcode"] = "0x107";
				}
				//else print "SKI: ".$ski."\n";

				if( strcasecmp($ski, $aki) === 0)
				{
					//$checkocsp = true; //print "same\n";
					$found = true;
					$rv["pemcert"] = $issuerpem;
					$rv["found"] = true;
				}
			} // ends if found.

			$s = $pos+1;
			$e = $posend+1;

		} // ends while each p7c (pem) cert.

	}
	else
	{
		$rv["error"] = true;
		$rv["errormsg"] = "Unable to retrieve issuer.";
		$rv["errorcode"] = "0x104";
	}

	return $rv;
}



// ******************************************
// * Check expiration date. 		        *
// ******************************************
function
cert_checkexpire($pemcert)
{
	$rv = array();
	$rv["error"] = false;
	$rv["errormsg"] = array();
	$rv["errorcode"] = array();

	$certdata = openssl_x509_parse($pemcert);
	
	if($certdata != false)
	{
		// EXP DATE CHECKS:
		$validFrom = date('Y-m-d H:i:s', $certdata['validFrom_time_t']);
		$validFromDate = date('Y-m-d', $certdata['validFrom_time_t']);
		$validTo = date('Y-m-d H:i:s', $certdata['validTo_time_t']);
		$validToDate = date('Y-m-d', $certdata['validTo_time_t']);
		$curr = date('Y-m-d H:i:s');
		$rv["date"] = $curr;
		
		if ($curr > $validTo || $curr < $validFrom)
		{
			$rv["error"] = true;
			$rv["errormsg"][] = "This certificate has expired or is not yet valid. Certificate date range from ".$validFromDate." to ".$validToDate.".";
			$rv["errorcode"][] = "0x105";
		}
	}
	else
	{
		$rv["error"] = true;
		$rv["errormsg"][] = "Invalid Certificate Data.";
		$rv["errorcode"][] = "0x107";
	}
	return $rv;

}


// ******************************************
// * Check ocsp status of cert.             *
// ******************************************
function
cert_checkocsp($theCert, $theIssuer, $ocspurl)
{
	global $_debug;

	$rv = array();
	$rv["error"] = false;
	$rv["errormsg"] = array();
	$rv["errorcode"] = array();
	$rv["ocspstatus"] = "";

	if( (strlen($theCert) < 1) || (strlen($theIssuer) < 1) || (strlen($ocspurl) < 1) )
	{
		$rv["error"] = true;
		$rv["errormsg"] = "Missing parameters for ocsp checks.";
		return $rv;
	}

	$issuertmpfile = tempnam('/tmp', 'issuer_pem_'); 	// "../applists/isser.pem";
	$tmpcertfile = tempnam('/tmp', 'cert_pem_'); 		// ../applists/cert.pem";

	//file_put_contents($issuertmpfile, $theIssuer);
	//file_put_contents($tmpcertfile, $theCert);

	$fsf = fopen($issuertmpfile, "w");
	if ($fsf !== false)
	{
			fwrite($fsf, $theIssuer);
			fclose($fsf);
	}
	$fsf = fopen($tmpcertfile, "w");
	if ($fsf !== false)
	{
			fwrite($fsf, $theCert);
			fclose($fsf);
	}

	// OCSP PER ISSUER:
	$cmd = "openssl ocsp -issuer ".$issuertmpfile." -cert ".$tmpcertfile." -url ".$ocspurl." -noverify -no_nonce -resp_text";

	// ADD OCSP HOST TO REMOVE METHOD NOT ALLOWED ERROR:
	$findmehost = "http://";
	$findmehostlen = strlen($findmehost);
	$ocspurllen = strlen($ocspurl);
	$hostbegin = stripos($ocspurl, $findmehost);

	$ocsphost = substr($ocspurl, $hostbegin+$findmehostlen, $ocspurllen-$hostbegin+$findmehostlen);
	
	//remove extra path if any from the host
	$ocsphost = explode("/", trim($ocsphost))[0];
	if(strlen($ocsphost) > 0)
	{
		$cmd = $cmd." -header Host=".$ocsphost;	
	}

	if($_debug) {
		debug("ocsphost=".$ocsphost);
		debug("cmd=".$cmd);
	}
	
	$cmd = $cmd." | grep ".$tmpcertfile;
	$ans = array();
	$rexec = exec($cmd, $ans, $res);
	
	if($_debug) debug("cert_checkocsp cmd = ".$cmd);
	if($_debug) debug("cert_checkocsp ans = ".var_export($ans, true));

	$ocspres = (isset($ans[0]))? explode(":", $ans[0]) : array();
	if (count($ocspres) == 2)
	{
		$certstatus = trim($ocspres[1]);
		$rv["ocspstatus"] = $certstatus;
	}
	
	else
	{
		$rv["error"] = true;
		$rv["errormsg"][] = "Certificate status could not be verified.";
		$rv["errorcode"][] = "0x106";
	}

	// unlink the temp files;
	unlink($issuertmpfile);
	unlink($tmpcertfile);

	return $rv;
}


//*****************************
//* harry's parse sber function
//*****************************
/*  not used ? conflicts with other scritps
function parse_sber($valstr, $numbytes = 0)
{
	$tlvTable = array();
	$tagCount = 0;
	
	if($numbytes == 0)
		$numbytes = strlen($valstr);
	
	for ($i=0; $i < $numbytes;) 
	{
		$tagbyte = substr($valstr, $i, 1);
		$i += 1;
		
		//$tlvTable[$tagCount]["tag"] = strtoupper(bin2hex($tagbyte));
		$tlvTable[$tagCount]["tag"] = $tagbyte;

		//get length
		$lenbyte_0 = substr($valstr, $i, 1);
		$i += 1;

		if(hexdec(bin2hex($lenbyte_0)) > 127) // multiple length bytes
		{
			$lenLen = $lenbyte_0 & "\x7F";
			$lenLen = hexdec(bin2hex($lenLen));

			// length is in  base(256)
			$len = substr($valstr, $i, $lenLen);
			$length_hex = bin2hex(substr($valstr, $i, $lenLen));
			$datalen = hexdec($length_hex);
			$i += $lenLen;
			$lenBytes = $lenbyte_0.$len;
		}
		else
		{
			$len = "\x00".$lenbyte_0;	
			$unpackarray = unpack("nint",$len);
			$datalen = $unpackarray["int"];	
			$len = $lenbyte_0;
			$lenLen = 1;
			$lenBytes = $lenbyte_0;
		}

		$tlvTable[$tagCount]["len"] = $datalen;
		$tlvTable[$tagCount]['lbytes'] = $lenBytes;	
		$tlvTable[$tagCount]["val"] = substr($valstr, $i, $datalen);
			
		$tagCount++;
		$i += $datalen;
	}
	
	return $tlvTable;
}
*/

// ******************************************************
// * FINAL STEP:  unlink all temp files                 *
// ******************************************************
//if(file_exists($tmpcertfilename))
//        unlink($tmpcertfilename);       // the tmpcert
//if(file_exists($issuerlistfilename))
//        unlink($issuerlistfilename);    // the issuer list p7c file -- der format
//if(file_exists($pemlistfilename))
//        unlink($pemlistfilename);       // the pem issuer list. -- remove from use if possible.

?>
