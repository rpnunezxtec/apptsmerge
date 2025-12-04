<?PHP

// $Id: fn-xcopymisc.php,v 1.1 2005/02/22 23:26:02 hjackson Exp $

// miscellaneous functions used in card credential create, delete, and update

function error_rtn ($logmsg, $loglevel)
{
        if ($loglevel & LOGLDAPERR)
                include("inc-log.php");
        if ($loglevel & LOGGENIO)
                print "$logmsg<br>";
        if ($loglevel & 0x800000)
                print "<script type=\"text/javascript\">alert('$logmsg. Please report this error to the system administrator.')</script>\n";

        // print "<script type=\"text/javascript\">top.location.href=\"$formfile\"</script>\n";
}

// This function takes a binary block as an argument and returns
// an array with indices 'dbyte for the sync byte, 'tag' for the tag
// and 'payload' for the payload.

function xblk_split($block)
{
        // preserve the first byte
        $dbyte = substr($block, 0, 1);
        // get the three tag bytes as hex strings
        $tagbyte[0] = bin2hex(substr($block, 1, 1));
        if (strlen($tagbyte[0]) == 1)
                $tagbyte[0] = "0".$tagbyte[0];
        $tagbyte[1] = bin2hex(substr($block, 2, 1));
                if (strlen($tagbyte[1]) == 1)
        $tagbyte[1] = "0".$tagbyte[1];
                $tagbyte[2] = bin2hex(substr($block, 3, 1));
        if (strlen($tagbyte[2]) == 1)
                $tagbyte[2] = "0".$tagbyte[2];
        // now create the tag from these three two-digit hex bytes
        $tag = $tagbyte[0].$tagbyte[1].$tagbyte[2];
        // get the payload, which is the rest of the block
        $payload = substr($block, 4);

        $result['dbyte'] = $dbyte;
        $result['tag'] = strtoupper($tag);
        $result['payload'] = $payload;

        return $result;
}

// This function converts a hex xblk-tag string to binary

function hex_tag($dbyte, $tag)
{
        // get three tag bytes as hex strings
        // the dbyte is a leading byte currently set to \x00
        // pad for any missing leading zeros

        $tag = str_pad($tag,6,"0",STR_PAD_LEFT);

        for ($b = 0, $i = 0; $b < 3;  $b++, $i+=2)
                $tagbyte[$b] = substr($tag, $i, 2);

        // now create the 4 byte binary string header
        $taghead = chr($dbyte).chr(hexdec($tagbyte[0])).chr(hexdec($tagbyte[1])).chr(hexdec($tagbyte[2]));

        return $taghead;
}

//This function converts a 32 character hex id number to 16 bytes binary

function xid2bin ($xid_string)
{
        $xid_string = str_replace(":","",$xid_string);

        if (strlen($xid_string) != 32) return FALSE;
        $bin_string = "";

        for ($i = 0; $i < 32; $i+=4) {
                $bin_string = $bin_string.(pack("n",hexdec(substr($xid_string,$i,4))));
                }
        return $bin_string;
}

//This function converts a hex text string to a binary string

/*
function hex2bin ($hex_string)
{
        $bin_string = "";
        $hex_string = str_replace(" ","",$hex_string);
        $EOS = strlen($hex_string);

        for ($i = 0; $i < $EOS; $i+=2) {
                $chunk = hexdec(substr($hex_string, $i, 2));
                $bin_string = $bin_string.substr((pack("n",$chunk)),1);
                }
        return $bin_string;
}
*/

// Function to perform AES encryption
function aes_encrypt($val,$ky)
{
   $mode=MCRYPT_MODE_CBC;
   $enc=MCRYPT_RIJNDAEL_128;
   //$val=str_pad($val, (16*(floor(strlen($val) / 16)+(strlen($val) % 16==0?2:1))), chr(16-(strlen($val) % 16)));
   //return mcrypt_encrypt($enc, $ky, $val, $mode, mcrypt_create_iv( mcrypt_get_iv_size($enc, $mode), MCRYPT_DEV_URANDOM));
   return @mcrypt_encrypt($enc, $ky, $val, $mode);
}

// Function to convert length to simple TLV encoding

function encode_len($length)
// length values for internal ICC data in the J8 format may be 1 or 3 bytes.
{
        if ($length < 255)
                $returnVal = substr(pack("n",$length),1);
        else
                $returnVal = "\xFF".pack("n",$length);

        return $returnVal;
}

// Returns BER encoded length value

function berlen($declen)
{
        if($declen < 127)
                $ber_len = pack("C",$declen);
        elseif($declen < 256)
        {
                $first_byte = "\x81";
                $ber_len = pack("C",$declen);
                $ber_len = $first_byte.$ber_len;
        }
        elseif($declen < 65535)
        {
                $first_byte = "\x82";
                $ber_len = pack("n",$declen);
                $ber_len = $first_byte.$ber_len;
        }
        return $ber_len;
}

function pkisign($source_data, $parmstring=NULL)
{
        // source_data is stirng to be hashed and signed
        // parms = ca_cert_file_name, ca_cert_password, return-hash-flag

        $my_parms = explode(",", $parmstring);
        $my_ca = $my_parms[0];
        $my_pswd = $my_parms[1];
        $hash_rtnflag = $my_parms[2];
        $hash_algo = $my_parms[3];

        if($my_ca == "") $my_ca = LOCALCAPATH;
        if($my_pswd == "") $my_pswd = LOCALCAPSWD;
        if($hash_algo == "") $hash_algo = "SHA256";

        //$digest = datahash($source_data, $hash_algo);
        $digest = mhash(MHASH_SHA1,$source_data);
        $strlen_digest = strlen($digest);

        // compute signature
        $pkres = openssl_get_privatekey($my_ca, $my_pswd);
        $rtn_code = openssl_sign($source_data, $signature, $pkres);
        //$rtn_code = openssl_private_encrypt($digest, $signature, $pkres);


        if($rtn_code === TRUE)
                $print_rtn = "TRUE";
        elseif($rtn_code === FALSE)
                $print_rtn = "TRUE";
        else
                $print_rtn = "UNKNOWN";

        print "len-digest=($strlen_digest), rtn-code=($print_rtn)<br>";
        $print_digest = bin2hex($digest);
        $print_digest = chunk_split($print_digest,2," ");
        print "<span class=\"fixedfont\">";
        print $print_digest."<br><br>";
        print "</span>";

        // free the key from memory
        openssl_free_key($pkres);

        if($hash_rtnflag == "hashout")
        {
                $returnval[0] = $signature;
                $returnval[1] = $digest;
                return $returnval;
        }
        else
                return $signature;
}

function pkisign_digest($digest, $parmstring=NULL)
{
        // source_data is encoded digest
        // parms = ca_cert_file_name, ca_cert_password, return-hash-flag

        $my_parms = explode(",", $parmstring);
        $my_ca = $my_parms[0];
        $my_pswd = $my_parms[1];
        $hash_rtnflag = $my_parms[2];
        $hash_algo = $my_parms[3];

        if($my_ca == "") $my_ca = LOCALCAPATH;
        if($my_pswd == "") $my_pswd = LOCALCAPSWD;

        // compute signature
        $pkres = openssl_get_privatekey($my_ca, $my_pswd);
        //openssl_private_encrypt($digest, $signature, $pkres, OPENSSL_NO_PADDING);
        //$rtn_code = openssl_sign($source_data, $signature, $pkres);
        $rtn_code = openssl_private_encrypt($digest, $signature, $pkres, OPENSSL_PKCS1_PADDING);


        if($rtn_code === TRUE)
                $print_rtn = "TRUE";
        elseif($rtn_code === FALSE)
                $print_rtn = "TRUE";
        else
                $print_rtn = "UNKNOWN";

        print "len-digest=($strlen_digest), rtn-code=($print_rtn)<br>";
        $print_digest = bin2hex($digest);
        $print_digest = chunk_split($print_digest,2," ");
        print "<span class=\"fixedfont\">";
        print $print_digest."<br><br>";
        print "</span>";

        // free the key from memory
        openssl_free_key($pkres);

        if($hash_rtnflag == "hashout")
        {
                $returnval[0] = $signature;
                $returnval[1] = $digest;
                return $returnval;
        }
        else
                return $signature;
}

function datahash($source_data, $parmstring=NULL)
{
        $my_parms = explode(",", $parmstring);
        $my_alog = $my_parms[0];
        $my_mode = $my_parms[1];

        if($my_alog == "SHA256" OR $my_alog == "sha256") $algo = MHASH_SHA256;
        elseif($my_alog == "SHA1" OR $my_alog == "sha1") $algo = MHASH_SHA1;

        $hash_val = mhash($algo,$source_data);
        return $hash_val;
}




?>
