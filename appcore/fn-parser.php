<?php

//include fn-fascn.php
function  bindec_unparity ($binstr)
{
        switch ($binstr) {
                case "00001": $digit = "\x30";  break;
                case "10000": $digit = "\x31";  break;
                case "01000": $digit = "\x32";  break;
                case "11001": $digit = "\x33";  break;
                case "00100": $digit = "\x34";  break;
                case "10101": $digit = "\x35";  break;
                case "01101": $digit = "\x36";  break;
                case "11100": $digit = "\x37";  break;
                case "00010": $digit = "\x38";  break;
                case "10011": $digit = "\x39";  break;
                case "01011": $digit = "\x3A";  break;
                case "11010": $digit = "\x3B";  break;
                case "00111": $digit = "\x3C";  break;
                case "10110": $digit = "\x3D";  break;
                case "01110": $digit = "\x3E";  break;
                case "11111": $digit = "\x3F";  break;
                default: $digit = "\x30";               break;
                }
        return $digit;
}


function BinString2BitSequence($mystring) {
   $mybitseq = "";
   $end = strlen($mystring);
   for($i = 0 ; $i < $end; $i++){
       $mybyte = decbin(ord($mystring[$i])); // convert char to bit string
       $mybitseq .= substr("00000000",0,8 - strlen($mybyte)) . $mybyte; // 8 bit packed
   }
   return $mybitseq;
}


function unpak_seiwg($seiwg)  // Tested OK
{
        $ascii_str = "";
        $seiwg_len = strlen($seiwg);

        if($seiwg_len > 25) {
                print "SEIWG  wrong length (".strlen($seiwg).") = ".bin2hex($seiwg)."<br>\n";
                return FALSE;
                }

       $bin_str = BinString2BitSequence($seiwg);

        for($i = 0; $i < strlen($bin_str) - 5; $i += 5) {
                $substring_binstr = substr($bin_str, $i, 5);
                $no_parity_byte = bindec_unparity($substring_binstr);
                //print "$no_parity_byte";
                $ascii_str = $ascii_str.$no_parity_byte;
                }

        return $ascii_str;
}


function decodefascn($fascn_hex)
{
$bin_string = "";
        $string_len = strlen($fascn_hex);
        for($i = 0;$i < $string_len; $i+=2)
        {
                $hexbyte = substr($fascn_hex,$i,2);
                $binbyte = pack('C', hexdec($hexbyte));
                $bin_string = $bin_string.$binbyte;
        }
$string_len = strlen($bin_string);
        $fascn_str = unpak_seiwg($bin_string);
        return $fascn_str;

}


?>

