<?PHP

// function to verify the PIV CHUID contents 

// parse s-ber chuid contents
// extract sdo
// hash remaining tlv elements string (with FE) = chuid-digest

// parse sdo = contentID + version + digestAlogrithms + encapContentInfo + certificate + signerInfo
// store certificate as PEM in savecert.pem

// extract chuid-digest from sdo
// compare to computed digest

// parse signerInfo = signerIdentifier + signedAttributes + signataureAlgo + signature
// prefix '31' + length-bytes (berlen) to signedAttributes
// digest signedAttributes string

// execute digest verify

// use Alberto's program: rsa_ecdsa_verify $cert_pem_file $signature $digest $digest_algo
// digest_algo ::
//		1 => SHA256
//		2 => SHA384
//		3 => SHA512
// result :: 0 = failed validation, 1 = verified OK
// Error:-2 – Wrong command line parameters
// Error:-3 – Error opening certificate file
// Error:-4 – Error reading X509 certificate
// Error:-5 – Error extracting public key from the X509 certificate
// Error:-6 – Unknown public key type (support only for RSA and ECDSA)
// Error:-7 – Wrong message type
// Error:#### where #### is a number returned when the OpenSSL verify() function encounters an error.

//	signed data
//		version
//		digestAlgoritm
//		encapsulatedContentInfo
//		certificate
//		signerInfo
//			version
//			signerDN
//			digestAlgorithm
//			signedAttributes
//				contentType
//					contentTypeOID
//				messageDigestInfo
//					digest
//				subjectname
//			signatureAlgorithm
//			signature

require_once '/authentx/core/http7/cl-derparser.php';

function chuid_verify($chuid_contents)
{
	global $hash_algos;
	global $algo_codes;
	global $cmdpath_ecdsa_verify;
	global $_debug;
	
	if($_debug) debug('Enter chuid verify...');
	
	$error = false;

	// remove NIST container tag bytes, if present
	if(substr($chuid_contents, 0, 1) == "\x5c")
	{
		$fileid = substr($chuid_contents, 1, 3);
		$lenbyte = substr($chuid_contents, 6, 1);
		if($lenbyte != "\x82")
			return "Contents too short";
		else
			$chuid_contents = substr($chuid_contents, 9);
	}
	
	// parse s-ber chuid contents
	// extract sdo
	// hash remaining tlv elements string (with FE) = chuid-digest

	$chuid_tlv = parse_sber($chuid_contents);

	$contents_str = '';
	foreach($chuid_tlv as $tagval) 
	{
		if($tagval['tag'] == "\x3e")
		{
			$sdo = $tagval['val'];  // signed data object
			continue;
		}
		$contents_str .= ($tagval['tag'].$tagval['lbytes'].$tagval['val']);
	}

	if($_debug) {debug('CHUID signed data object:'); debug(bin2hex($sdo));}

	// parse sdo = contentID + version + digestAlogrithms + encapContentInfo + certificate + signerInfo
	// store certificate as PEM in temp_cert_pem
	
	/*
	PKCS #7 signed data

		ContentInfo ::= SEQUENCE {
			contentType = OID (signedDataType)
			content [0] EXPLICIT (signedDataContent) }

		SignedData ::= 
			version
			digestAlgorithms
			encapContentInfo
			certificates [0] IMPLICIT OPTIONAL
			crls [1] IMPLICIT OPTIONAL  - omitted for chuid (FIPS 201)
			signerInfos
	*/

	$der_parser = new der_parser($sdo);
	
	$der_parser->goto_node(1);  // oid for signed data object
	$nodeinfo = $der_parser->get_node_info();
	
	if(strcmp(bin2hex($nodeinfo["val"]), '2a864886f70d010702') != 0)
		return 'Not signed data object';
		
	$der_parser->goto_node_next(4);  		// INTEGER version 
	$der_parser->goto_node_next(4, 'SET');  // set of digest algorithms
	$rtn = $der_parser->goto_node_next(6, 'OBJECT_IDENTIFIER');
	$nodeinfo = $der_parser->get_node_info();
	$digest_algo_oid = $nodeinfo['val'];
		
	if($_debug)
	{
		foreach($nodeinfo as $tag => $val)
		{
			if($tag == 'name')
				debug($tag.'=>'.$val);
			else
				debug($tag.'=>'.bin2hex($val));
		}
	}
	
	if(isset($hash_algos[$digest_algo_oid]))
		$sig_hash_algo = $hash_algos[$digest_algo_oid];
	else
	{
		if($_debug) debug('digest_algo_oid='.bin2hex($digest_algo_oid));
		return 'Unrecognized hash algorithm id ('.bin2hex($digest_algo_oid).')';
	}
			
	$der_parser->goto_node_next(4);
	$der_parser->goto_node_next(5, 'OBJECT_IDENTIFIER');  // encacsulated content id
	$nodeinfo = $der_parser->get_node_info();
	$content_oid = $nodeinfo['val'];
	
	if(strcmp($content_oid, "\x60\x86\x48\x01\x65\x03\x06\x01") != 0)
		return 'Content type not pivCHIUD or piviCHUID';
		
	// get certificate ***********

	$der_parser->goto_node_next(4);   // certificate
	$nodeinfo = $der_parser->get_node_info();
	$certificate = $der_parser->get_der_substr($nodeinfo["offset"], $nodeinfo["len"]);

	// save certificate to file in PEM format
	$temp_cert_der_file = tempnam('/tmp', 'chuid-cert-der-');
	$temp_cert_pem_file = tempnam('/tmp', 'chuid-cert-pem-');
	file_put_contents($temp_cert_der_file, $certificate);

	$cmd = "openssl "."x509 -inform DER -in $temp_cert_der_file > $temp_cert_pem_file";
	$output_array = array();
	$lastlineout = exec($cmd, $output_array, $rtn_val);
	unlink($temp_cert_der_file);
	
	// ***************************

	// get signerInfos
	$der_parser->goto_node_next(4);   // signerInfos
	$nodeinfo = $der_parser->get_node_info();
	$sigInfos = $der_parser->get_der_substr($nodeinfo["offset"], $nodeinfo["len"]);

	// get signed digest algorithm
	$sigInfo_parser = new der_parser($sigInfos);
	$sigInfo_parser->goto_node(0);
	$sigInfo_parser->goto_node_next(2); // version
	$sigInfo_parser->goto_node_next(2); // signer DN
	
	$sigInfo_parser->goto_node_next(2); // digest 
	$sigInfo_parser->goto_node_next(3, 'OBJECT_IDENTIFIER');
	$nodeinfo = $sigInfo_parser->get_node_info();
	$chuid_hash_algo_oid = $nodeinfo['val'];

	if($_debug)
	{
		debug('nodeinfo for chuid_hash_algo_id ('.bin2hex($chuid_hash_algo_oid).')');
		foreach($nodeinfo as $tag => $val)
		{
			if($tag == 'name')
				debug($tag.'=>'.$val);
			else
				debug($tag.'=>'.bin2hex($val));
		}
	}

	if(isset($hash_algos[$chuid_hash_algo_oid]))
		$chuid_hash_algo = $hash_algos[$chuid_hash_algo_oid];
	else
	{
		$error = true;
		$returnval = 'Unrecognized hash algorithm id ('.bin2hex($chuid_hash_algo_oid).')';
	}
	
	if(!$error)
	{
		// compute hash of chuid contents
		$my_chuid_digest = hash($chuid_hash_algo, $contents_str, true);

		// get signed attributes
		$sigInfo_parser->goto_node_next(2); // signed attributes
		$nodeinfo = $sigInfo_parser->get_node_info();
		$offset = $nodeinfo['offset'];
		$length = $nodeinfo['len'];
		$signedAttrs = "\x31".hex2bin($nodeinfo['lbytes']).$sigInfo_parser->get_der_substr($nodeinfo["offset"], $nodeinfo["len"]);

		// digest the signed attributes
		$digest = hash($sig_hash_algo, $signedAttrs, true);

		//get chuid digest
		$oid = '';
		while($oid != "\x2a\x86\x48\x86\xf7\x0d\x01\x09\x04")
		{
			$sigInfo_parser->goto_node_next(3);
			$rtn = $sigInfo_parser->goto_node_next(4, 'OBJECT_IDENTIFIER');
			if($rtn === false)
			{
				$error = true;
				$returnval = 'Signed digest not found';
				break;
			}
			$nodeinfo = $sigInfo_parser->get_node_info();
			$oid = $nodeinfo['val'];
		}
	}

	if(!$error)
	{
		$sigInfo_parser->goto_node_next(5, 'OCTET_STRING');
		$nodeinfo = $sigInfo_parser->get_node_info();
		$chuid_digest = $nodeinfo['val'];

		if($my_chuid_digest != $chuid_digest)
		{
			$error = true;
			$returnval = 'CHUID digest does not match';
		}
	}

	if(!$error)
	{
		//get signature 
		$sigInfo_parser->goto_node_next(2); 				// signatureAlgotrithm		
		$sigInfo_parser->goto_node_next(2, 'OCTET_STRING'); // signature
		$nodeinfo = $sigInfo_parser->get_node_info();
		$signature = $nodeinfo['val'];

		$algo_codes = array (
			"sha256" => '1',
			"sha384" => '2',
			"sha512" => '3',
			"sha1"	 => '4',
			);

		$array_out=array();
	//	$cmdpath = "/opt/src/rsa_ecdsa_verify";
		$cmdpath = ".";
		$cmd = $cmdpath."/rsa_ecdsa_verify $temp_cert_pem_file ".bin2hex($signature).' '.bin2hex($digest).' '.$algo_codes[$sig_hash_algo];
		$lastline = exec($cmd, $array_out, $rtn);

		if($rtn != 0)
			$returnval = "Internal system error ($rtn)";
		else
			$returnval = $lastline;
	}
	
	unlink($temp_cert_pem_file);
	return $returnval;
}


function get_chuid_cert($chuid_sdo)
{
	global $hash_algos;
	global $algo_codes;
	global $cmdpath_ecdsa_verify;
	global $_debug;
	
	// parse sdo = contentID + version + digestAlogrithms + encapContentInfo + certificate + signerInfo
	// store certificate as PEM in temp_cert_pem
	
	$der_parser = new der_parser($chuid_sdo);
	
	$der_parser->goto_node(1);  			// oid for signed data object
	$der_parser->goto_node_next(4); 		// version
	$der_parser->goto_node_next(4, 'SET');  // set of digest algorithms
	$der_parser->goto_node_next(4); 		// ecapsulated Content Info

	$inode = $der_parser->goto_node_next(4, 'CONTEXT');   // certificate
	if($inode === false)
		//return 'Certificate not found';
		return false;
	
	$nodeinfo = $der_parser->get_node_info();
	$certificate = $der_parser->get_der_substr($nodeinfo["offset"], $nodeinfo["len"]);
	
	return $certificate;

	/*
	// save certificate to file in PEM format
	$temp_cert_der_file = tempnam('/tmp', 'chuid-cert-der-');
	$temp_cert_pem_file = tempnam('/tmp', 'chuid-cert-pem-');

	file_put_contents($temp_cert_der_file, $certificate);
	$cmd = "openssl "."x509 -inform DER -in $temp_cert_der_file > $temp_cert_pem_file";
	$output_array = array();
	$lastlineout = exec($cmd, $output_array, $rtn_val);
	unlink($temp_cert_der_file);

	if($rtn_val == 0)
		return $temp_cert_pem_file;
	else
		return false;
	*/
}


function cbeff_verify($cbeff, $certfile_pem)
{
	global $hash_algos;
	global $algo_codes;
	global $cmdpath_ecdsa_verify;
	global $_debug;
	
	if($_debug) debug("Enter cbeff_verify...");
	if($_debug) debug(bin2hex($cbeff));
	
	// spearate into bio block and signature block
	// compute hash of bio block
	// parse signed data object
	// extract cbeff-digest
	// compare extracted digest with computed digest
	// compute hash of signed attributes
	// execute Alberto's program using certificate from chuid
	
	// CBEFF Structure:
	// 	cbeff_header (88 bytes)
	//		lenBD (offset=2 len=4)
	//		lenSB (offset=6,len=2)
	// 	bio_data
	// 	signature_block
	//	  signed data
	//		version
	//		digestAlgoritm (used by signer)
	//		encapsulatedContentInfo
	//		certificate
	//		signerInfo
	//			version
	//			signerDN
	//			digestAlgorithm (of signed attrs and/or eContent)
	//			signedAttributes
	//				contentType
	//					contentTypeOID
	//				messageDigestInfo
	//					digest
	//				subjectname
	//			signatureAlogrithm
	//			signature
		
	
	$lenBD = hexdec(bin2hex(substr($cbeff, 2, 4)));  // bio data block
	$lenSB = hexdec(bin2hex(substr($cbeff, 6, 2)));  // signature block
	$len_cbeff = $lenBD + 88;
	$offsetSB = $lenBD + 88;

	$cbeff_biodat = substr($cbeff, 0, $len_cbeff);
	$sdo = substr($cbeff, $offsetSB);

	if(strlen($sdo) != $lenSB)
		return 'Wrong length signature block';
		
	$der_parser = new der_parser($sdo);

	$der_parser->goto_node(1);  // oid for signed data object
	$nodeinfo = $der_parser->get_node_info();

	if(strcmp(bin2hex($nodeinfo["val"]), '2a864886f70d010702') != 0)
		return 'Not signed data object';

	$der_parser->goto_node_next(4);  		// INTEGER version 
	$der_parser->goto_node_next(4, 'SET');  // set of digest algorithms
	$der_parser->goto_node_next(6, 'OBJECT_IDENTIFIER');
	$nodeinfo = $der_parser->get_node_info();
	$digest_algo_oid = $nodeinfo['val'];

	if(isset($hash_algos[$digest_algo_oid]))
		$sig_hash_algo = $hash_algos[$digest_algo_oid];
	else
		return 'Unrecognized hash algorithm id ('.bin2hex($digest_algo_oid).')';

	$der_parser->goto_node_next(4);
	$der_parser->goto_node_next(5, 'OBJECT_IDENTIFIER');  // encacsulated content id
	$nodeinfo = $der_parser->get_node_info();
	$content_oid = $nodeinfo['val'];

	if(strcmp($content_oid, "\x60\x86\x48\x01\x65\x03\x06\x02") != 0)
		return 'Content type not pivBiometricObj';

	$der_parser->goto_node_next(4);   // certificate or signerInfo
	$nodeinfo = $der_parser->get_node_info();

	if($nodeinfo['name'] == 'CONTEXT') // certificate
	{
		$certificate = $der_parser->get_der_substr($nodeinfo["offset"], $nodeinfo["len"]);

		// save certificate to file in PEM format
		$temp_cert_der_file = tempnam('/tmp', 'fp-cert-der-');
		$temp_cert_pem_file = tempnam('/tmp', 'fp-cert-pem-');
		file_put_contents($temp_cert_der_file, $certificate);

		$cmd = "openssl "."x509 -inform DER -in $temp_cert_der_file > $temp_cert_pem_file";
		$output_array = array();
		$lastlineout = exec($cmd, $output_array, $rtn_val);

		$der_parser->goto_node_next(4);   // signerInfos
		$nodeinfo = $der_parser->get_node_info();
	}
	else // use cerfificate from cert file
	{
		$temp_cert_pem_file = $certfile_pem;
	}

	// get signerInfos
	$sigInfos = $der_parser->get_der_substr($nodeinfo["offset"], $nodeinfo["len"]);
	
	if($_debug) debug('sigInfos='.bin2hex($sigInfos));

	$sigInfo_parser = new der_parser($sigInfos);

	// get content digest algorithm
	$sigInfo_parser->goto_node(0);
	$sigInfo_parser->goto_node_next(2); // version
	$sigInfo_parser->goto_node_next(2); // signer DN

	$sigInfo_parser->goto_node_next(2); // digest algorithm
	$sigInfo_parser->goto_node_next(3, 'OBJECT_IDENTIFIER');
	$nodeinfo = $sigInfo_parser->get_node_info();
	$eCont_hash_algo_oid = $nodeinfo['val'];

	if(isset($hash_algos[$eCont_hash_algo_oid]))
		$eCont_hash_algo = $hash_algos[$eCont_hash_algo_oid];
	else
		return 'Unrecognized hash algorithm id ('.bin2hex($eCont_hash_algo_oid).')';

	// compute hash of chuid contents
	$my_cbeff_digest = hash($eCont_hash_algo, $cbeff_biodat, true);

	// get signed attributes
	$sigInfo_parser->goto_node_next(2); // signed attributes
	$nodeinfo = $sigInfo_parser->get_node_info();
	$offset = $nodeinfo['offset'];
	$length = $nodeinfo['len'];
	$signedAttrs = "\x31".hex2bin($nodeinfo['lbytes']).$sigInfo_parser->get_der_substr($nodeinfo["offset"], $nodeinfo["len"]);

	// digest the signed attributes
	$digest = hash($sig_hash_algo, $signedAttrs, true);

	//get cbeff digest
	$oid = '';
	while($oid != "\x2a\x86\x48\x86\xf7\x0d\x01\x09\x04")
	{
		$sigInfo_parser->goto_node_next(3);
		$rtn = $sigInfo_parser->goto_node_next(4, 'OBJECT_IDENTIFIER');
		if($rtn === false)
		{
			return 'Signed digest not found';
			break;
		}
		$nodeinfo = $sigInfo_parser->get_node_info();
		$oid = $nodeinfo['val'];
	}
		
	$sigInfo_parser->goto_node_next(5, 'OCTET_STRING');
	$nodeinfo = $sigInfo_parser->get_node_info();
	$cbeff_digest = $nodeinfo['val'];

	if($my_cbeff_digest != $cbeff_digest)
		return 'CBEFF digest does not match';

	//get signature 
	$sigInfo_parser->goto_node_next(2);					// signatureAlgorithm
	$sigInfo_parser->goto_node_next(2, 'OCTET_STRING'); // signature
	$nodeinfo = $sigInfo_parser->get_node_info();
	$signature = $nodeinfo['val'];

	$array_out=array();
//	$cmdpath = "/opt/src/rsa_ecdsa_verify";
	$cmdpath = ".";
	$cmd = $cmdpath."/rsa_ecdsa_verify $temp_cert_pem_file ".bin2hex($signature).' '.bin2hex($digest).' '.$algo_codes[$sig_hash_algo];
	$lastline = exec($cmd, $array_out, $rtn);

	if($_debug) debug("cmd=$cmd");
	if($_debug) debug("rtn=$rtn");
	if($_debug) debug("lastline=$lastline");
	if($_debug) array_dump($array_out);

	if($rtn != 0)
		return "Internal system error ($rtn)";
	else
		return $lastline;
}


function secobj_verify($secobjsdo, $temp_chuidcert_pem_file, &$lds_hashtbl, &$lds_hashAlgo)
{
	global $hash_algos;
	global $algo_codes;
	global $cmdpath_ecdsa_verify;
	global $_debug;

	if($_debug) debug('Enter secobj_verify...');
	if($_debug) debug("secobjsdo:\n".bin2hex($secobjsdo));
	
	// parse security sdo
	$secobj_parser = new der_parser($secobjsdo);
	
//	signed data
//	(4)	version
//	(4)	digestAlgorithm
//	(4)	encapsulatedContentInfo
//  (5)		LDSsecurityObj
//	(6)			OCTECT_STRING
//	(4)	signerInfo
//	(6)		version
//	(6)		signerDN
//	(6)		digestAlgorithm
//	(6)		signedAttributes
//	(7)			contentType				2a 86 48 86 f7 0d 01 09 03
//	(9)				contentTypeOID   	2b 1b 01 01 01 (LDSSecurityObject)
//	(7)			signingTime				2a 86 48 86 f7 0d 01 09 05
//	(9)				UTCTime					
//	(7)			messageDigestInfo  		2a 86 48 86 f7 0d 01 09 04 
//	(9)				digest
//				subjectname
//	(6)		signatureAlgo
//	(6)		signature

//	(7)	SEQUENCE
//	(8)		OBJECT_IDENTIFIER
//	(8)		SET
//	(9)			ANY (OCTECT_STRING, UTCTime, ...)

//	LDSsecurityObj
//	(2)	version
//	(2)	hashAlgo
//	(2)	hashTable
//	(3)		hashGrp
//	(4)			grpNumber
//	(4)			hashValue
		
	$secobj_parser->goto_node(0);
	$secobj_parser->goto_node_next(4);	// version
	$secobj_parser->goto_node_next(4);	// algorithm
	$secobj_parser->goto_node_next(4);	// eContentInfo

	$secobj_parser->goto_node_next(6, 'OCTET_STRING');	// LDSsecurityObj
	$nodeinfo = $secobj_parser->get_node_info();
	$ldssecobj = $nodeinfo['val'];
		
	if($_debug) debug("LDSsecurity obj:\n".bin2hex($ldssecobj));

	$secobj_parser->goto_node_next(4);	// singerInfo
	$secobj_parser->goto_node_next(6);  // version
	$secobj_parser->goto_node_next(6);  // signerDN
	$secobj_parser->goto_node_next(6);  // algorithm
		
	$secobj_parser->goto_node_next(7, 'OBJECT_IDENTIFIER');
	$nodeinfo = $secobj_parser->get_node_info();
	$digest_hash_algo = $hash_algos[$nodeinfo['val']];

	$secobj_parser->goto_node_next(6);  // signedAttrs
	
	$nodeinfo = $secobj_parser->get_node_info();
	$signedAttrs = "\x31".hex2bin($nodeinfo['lbytes']).$secobj_parser->get_der_substr($nodeinfo["offset"], $nodeinfo["len"]);

	// digest the signed attributes
	$digest = hash($digest_hash_algo, $signedAttrs, true);
	
	//get security object digest
	$oid = '';
	while($oid != "\x2a\x86\x48\x86\xf7\x0d\x01\x09\x04")
	{
		$secobj_parser->goto_node_next(7);
		$rtn = $secobj_parser->goto_node_next(8, 'OBJECT_IDENTIFIER');
		if($rtn === false)
		{
			$error = true;
			$returnval = 'Signed digest not found';
			if($_debug) debug('Signed digest not found');
			break;
		}
		$nodeinfo = $secobj_parser->get_node_info();
		$oid = $nodeinfo['val'];
	}

	$rtn = $secobj_parser->goto_node_next(9, 'OCTET_STRING');
	$nodeinfo = $secobj_parser->get_node_info();
	$secobj_digest = $nodeinfo['val'];
	
	if($_debug && $rtn === false) debug('digest not found in security object');
	if($_debug) debug('digest='.bin2hex($secobj_digest));

	// parse LDSsecurity obj to get hash values
	// compare each hash to computed hashes
	// compute hash of hashes
		
	$lds_parser = new der_parser($ldssecobj);
	$lds_parser->goto_node(0);
	$lds_parser->goto_node_next(2); 	// version

	$lds_parser->goto_node_next(2);		// hash algorithm
	$lds_parser->goto_node_next(3, 'OBJECT_IDENTIFIER');
	$nodeinfo = $lds_parser->get_node_info();
	$lds_hashAlgo = $nodeinfo['val'];

	$lds_parser->goto_node_next(2);		// hash table
	$rtn = $lds_parser->goto_node_next(4);
	while($rtn !== false)
	{
		$nodeinfo = $lds_parser->get_node_info();
		$grpnum = $nodeinfo['val'];
		$lds_parser->goto_node_next(4);
		$nodeinfo = $lds_parser->get_node_info();
		$hashval = $nodeinfo['val'];
		$lds_hashtbl[$grpnum] = $hashval;
		$rtn = $lds_parser->goto_node_next(4);
	}

	if($_debug) 
	{
		debug('dump of LDS hash table...');
		unset($debugtbl);
		foreach($lds_hashtbl as $grpnum => $hashval)
			$debugtbl[bin2hex($grpnum)] = bin2hex($hashval); 
		array_dump($debugtbl);
	}
	
	$hashAlgorithm = $hash_algos[$lds_hashAlgo]; 
	$hashofhashes = hash($hashAlgorithm,  $ldssecobj, true);

	if($_debug) debug("hashAlgorithm=".bin2hex($hashAlgorithm));
	if($_debug) debug("hashofhashes=".bin2hex($hashofhashes));

	if($hashofhashes != $secobj_digest)
		return 'Security object hash value does not match';

	// get the signature	
	$secobj_parser->goto_node_next(6);		// algorithm
	$secobj_parser->goto_node_next(6);		// signature
	$nodeinfo = $secobj_parser->get_node_info();
	$signature = $nodeinfo['val'];
	
	if($_debug) debug('signature='.bin2hex($signature));

	$array_out=array();
	$cmdpath = "."; //	"/opt/src/rsa_ecdsa_verify";
	$cmd = $cmdpath."/rsa_ecdsa_verify $temp_chuidcert_pem_file ".bin2hex($signature).' '.bin2hex($digest).' '.$algo_codes[$hashAlgorithm];
	$lastline = exec($cmd, $array_out, $rtn);

	if($_debug) debug("cmd=$cmd");
	if($_debug) debug("rtn=$rtn");
	if($_debug) debug("lastline=$lastline");
	if($_debug) array_dump($array_out);

	if($rtn != 0)
		return "Internal system error ($rtn)";
	else
		return $lastline;
}


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


?>


