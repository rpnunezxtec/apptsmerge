<?PHP

//include("tbl-tag-defs.php");

/*************************************************************************************/
function parse_tlv($valstr, $tlv_type = "TLV", $numbytes = 0)
{
        global $tagNameTable;

$result = array();

        if($numbytes == 0)
                $numbytes = strlen($valstr);

        for ($i=0; $i < $numbytes;)
        {
                $tagbyte = substr($valstr, $i, 1);
                $i += 1;
                //print "tag="; print_hex($tagbyte, 1, "NOBREAK");


$taghex = bin2hex($tagbyte);

               /*
                if ($tagbyte == "\x31")  // exception for pin tags
                        $twobytetag = "\x20".$tagbyte;
                else
                */
                $twobytetag = "\x00".$tagbyte;

                $tag_ = unpack("nint",$twobytetag);
                $tag_int = $tag_["int"];
                $name = $tagNameTable[$tag_int];
                if ($name == "") $name = "unknown";
                //print " (". str_pad($name,12,".").")";

                //get length
                $lenbyte_0 = substr($valstr, $i, 1);
                $i += 1;
                if($tlv_type == "TLV")  // simple TLV
                {
                        if ($lenbyte_0 == "\xFF")
                        {
                                $lenbyte_12 = substr($valstr, $i, 2);
                                $i += 2;
                                $unpackarray = unpack("nint",$lenbyte_12);
                                $datalen = $unpackarray["int"];
                                $len = $lenbyte_0.$lenbyte_12;
                                $lenLen = 3;
                        }
                        else
                        {
                                $len = "\x00".$lenbyte_0;
                                $unpackarray = unpack("nint",$len);
                                $datalen = $unpackarray["int"];
                                $len = $lenbyte_0;
                                $lenLen = 1;
                        }
                }
                elseif ($tlv_type == "SBER")  // stupid-BER encoding
                {
                        if(hexdec(bin2hex($lenbyte_0)) > 127) // multiple length bytes
                        {
                                $lenLen = $lenbyte_0 & "\x7F";
                                $lenLen = hexdec(bin2hex($lenLen));

                                // length is in  base(256)
                                $len = substr($valstr, $i, $lenLen);
                                $length_hex = bin2hex(substr($valstr, $i, $lenLen));
                                $datalen = hexdec($length_hex);
                                $i += $lenLen;
                        }
                        else
                        {
                                $len = "\x00".$lenbyte_0;
                                $unpackarray = unpack("nint",$len);
                                $datalen = $unpackarray["int"];
                                $len = $lenbyte_0;
                                $lenLen = 1;
                        }
                }
                //print " len="; print_hex($len, $lenLen, "NOBREAK"); print "($datalen)";
                //print " value=";

                if (is_ascii(substr($valstr, $i, $datalen)))
                {
$valhex = substr($valstr, $i, $datalen);
                        //print substr($valstr, $i, $datalen)."<br>";
                }
                else
                {

$valhex = bin2hex(substr($valstr, $i, $datalen));
                        //print_hex(substr($valstr, $i, $datalen),$datalen);
                 }


$result[$taghex] = $valhex;


                $i += $datalen;
        }


        return $result;
}
/*************************************************************************************/
function parse_tlv_raw($valstr, $tlv_type = "TLV", $numbytes = 0)
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
                if($tlv_type == "TLV")  // simple TLV
                {
                        if ($lenbyte_0 == "\xFF")
                        {
                                $lenbyte_12 = substr($valstr, $i, 2);
                                $i += 2;
                                $unpackarray = unpack("nint",$lenbyte_12);
                                $datalen = $unpackarray["int"];
                                $len = $lenbyte_0.$lenbyte_12;
                                $lenLen = 3;
                                $lenBytes = $lenbyte_0.$lenbyte_12;
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
                }
               elseif ($tlv_type == "SBER")  // stupid-BER encoding
                {
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
                }

                $tlvTable[$tagCount]["len"] = $datalen;
                $tlvTable[$tagCount]['lbytes'] = $lenBytes;

                /*
                if (is_ascii(substr($valstr, $i, $datalen)))
                        $tlvTable[$tagCount]["val"] = substr($valstr, $i, $datalen);
                else
                        $tlvTable[$tagCount]["val"] =  bin2hex(substr($valstr, $i, $datalen));
                */

                $tlvTable[$tagCount]["val"] = substr($valstr, $i, $datalen);

                $tagCount++;
                $i += $datalen;
        }

        return $tlvTable;
}

function parse_tlv_tags($valstr, $tagstr, $numbytes = 0)
{
        global $tagNameTable;

        if($numbytes == 0)
                $numbytes = strlen($tagstr);

        for ($ti=0, $vi=0; $ti < $numbytes;)
        {
                $tagbyte = substr($tagstr, $ti, 1);
                $ti += 1;
                print "tag="; print_hex($tagbyte, 1, "NOBREAK");

                $twobytetag = "\x00".$tagbyte;

                $tag_ = unpack("nint",$twobytetag);
                $tag_int = $tag_["int"];
                $name = $tagNameTable[$tag_int];
                if ($name == "") $name = "unknown";
                print " (". str_pad($name,12,".").")";

                //get length
                $lenbyte_0 = substr($tagstr, $ti, 1);
                $ti += 1;

                if ($lenbyte_0 == "\xFF")
                {
                        $lenbyte_12 = substr($tagstr, $ti, 2);
                        $ti += 2;
                        $unpackarray = unpack("nint",$lenbyte_12);
                        $datalen = $unpackarray["int"];
                        $len = $lenbyte_0.$lenbyte_12;
                        $lenLen = 3;
                }
                else
                {
                        $len = "\x00".$lenbyte_0;
                        $unpackarray = unpack("nint",$len);
                        $datalen = $unpackarray["int"];
                        $len = $lenbyte_0;
                        $lenLen = 1;
                }

                print " len="; print_hex($len, $lenLen, "NOBREAK"); print "($datalen)";
                print " value=";

                if (is_ascii(substr($valstr, $vi, $datalen)))
                        print substr($valstr, $vi, $datalen)."<br>";
                else
                        print_hex(substr($valstr, $vi, $datalen),$datalen);
                $vi += $datalen;
        }
}
/*************************************************************************************/
function is_ascii ($string)
{
        for ($i = 0; $i < strlen($string); $i++)
        {
                $asciicode = ord($string[$i]);
                if (($asciicode >= 48 && $asciicode <= 57) || ($asciicode >= 65 && $asciicode <= 90) || ($asciicode >= 97 && $asciicode <= 122) || $asciicode == 32 || $asciicode == 64 || $asciicode == 46 || $asciicode == 47)
                        continue;
                else
                        return FALSE;
        }
        return TRUE;
}

/*************************************************************************************/
function print_hex($binstr, $numbytes, $break = "YES", $space = "YES")
{
        for ($i = 0; $i < $numbytes; $i++)
        {
                 print bin2hex($binstr[$i]);
                 if($space == "YES") print " ";
        }
        if ($break == "YES") print "<br>\n";
}


?>
