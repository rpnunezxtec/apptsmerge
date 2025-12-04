<?PHP

include_once("fn-tlv-parse.php");
include_once("fn-xcopymisc.php");
include_once("inc-oid-list.php");

define("NOBREAK", 0x01);

// if class = universal
$type_table = array
(
         0 => "EOC",
         1 => "BOOLEAN",
         2 => "INTEGER",
         3 => "BIT_STRING",
         4 => "OCTET_STRING",
         5 => "NULL",
         6 => "OBJECT_IDENTIFIER",
         7 => "ObjectDescriptor",
         8 => "INSTANCE_OF",
         9 => "REAL",
        10 => "ENUMERATED",
        11 => "EMBEDDED_PDV",
        12 => "UTF8String",
        13 => "RELATIVE_OID",
        14 => "T61String",
        15 => "notdefined",
        16 => "SEQUENCE",
        17 => "SET",
        18 => "NumericString",
        19 => "PrintableString",
        20 => "T61String",
        21 => "VideotexString",
        22 => "IA5String",
        23 => "UTCTime",
        24 => "GeneralizedTime",
        25 => "GraphicString",
        26 => "ISO646String",
        27 => "GeneralString",
        28 => "UniversalString",
        29 => "CHARACTER_STRING",
        30 => "BMPString",
);

$pritable_types = array
(
                12 => "UTF8String",
                13 => "RELATIVE_OID",
                18 => "NumericString",
                19 => "PrintableString",
                22 => "IA5String",
                23 => "UTCTime",
                24 => "GeneralizedTime",
                26 => "ISO646String",
                27 => "GeneralString",
                28 => "UniversalString",
                29 => "CHARACTER_STRING",
);


$class_table = array (0 =>"UNIVERSAL", 1 => "APPLICATION", 2 => "CONTEXT", 3 => "PRIVATE");

/*
function print_indent($level, $flag=NULL)
{
        if($flag == NULL)  // 0x01 = NOBREAK
                print "<br>";

        for($i = 1; $i < $level; $i++)
        {
                print "&nbsp&nbsp&nbsp&nbsp";

        }
}
*/

function der_decode($string_in, $max_len, $tally = 0, $level = 0)
{
        $level++;
        //print "<br><br>($level) ENTER der-decode... maxlen=($max_len) stringin_len=(".strlen($string_in).")<br>";
        global $type_table;
        global $class_table;
        global $oid_table;
        $my_length = $max_len;
        $str_pos = 0;

        while ($str_pos < $max_len)
        {

                // first byte = tag
                $tag = substr($string_in, $str_pos, 1);

                $str_pos += 1;
                $tally += 1;

                // decompose class and constructed bit (bit 6)
                // constructed? If bit 6 = 1, then is constructed
                $tag_int = hexdec(bin2hex($tag));
                $tag_class = $tag_int >> 6;
//              $test = (hexdec(bin2hex($tag))  & 0x20);
                $test = $tag_int & 0x20;

                // multi-byte tag? if low 5 bits = '11111'
                $multi_byte = $tag_int & 0x1f;
                if($multi_byte == 0x1f)
                {
                        // get next tag byte
                        $tag2 = substr($string_in, $str_pos, 1);
                        $str_pos += 1;
                        $tally += 1;
                        // test more bit (bit 8) -- should do this, but do not expect more than two bytes
                        $tag = $tag.$tag2;
                        $tag_num = hexdec(bin2hex($tag));
                        $tag_int = hexdec(bin2hex($tag2));
                }
                else
                {
                        $tag_num = $tag_int & 0x1f;
                }

//              print "tag=".bin2hex($tag)." ";
//              print "tagclass($tag_class) tagnum($tag_num) ";


                if($test)
                {
                        $CONSTRUCTED = TRUE;
                        $CONSTEST = "CONSTRUCTED";
                }
                else
                {
                        $CONSTRUCTED = FALSE;
                        $CONSTEST = "SIMPLE";
                }

                //decode length
                $length_byte = substr($string_in,$str_pos,1);

                $str_pos++; $tally++;

                if(hexdec(bin2hex($length_byte)) > 127) // multiple length bytes
                {
                        $num_len_bytes = $length_byte & "\x7F";

                        $num_len_bytes = hexdec(bin2hex($num_len_bytes));


                        // length is in  base(256)
                        $length_hex = bin2hex(substr($string_in, $str_pos, $num_len_bytes));
                        //print "length = ".$length_hex."<br>";
//$s = bin2hex($string_in);
//print "value = ".$s."\n";


                        $length = hexdec($length_hex);

//print "the length = ".$length."\n\n";

                        $str_pos += $num_len_bytes;
                        $tally += $num_len_bytes;
                }
                else
                {
//                      print "num_len_bytes=(1) ";
                        $length = hexdec(bin2hex($length_byte));
                        $length_hex = bin2hex($length_byte);
                        //print "length = ".$length_hex."<br>";
                }

               $substring = substr($string_in, $str_pos, $length);
                if($tag_class == 0) // universal class
                        $tag_type = $type_table[$tag_num];
                elseif($tag_class == 1)
                        $tag_type = $class_table[$tag_class]."(".$type_table[$tag_num].")";
                else
                        $tag_type = $class_table[$tag_class];

                $TAG = bin2hex($tag);
//              print_indent($level);
//              $print_level = $level -1;
//              print "LEVEL=$print_level TAG=".$TAG." TYPE=".$tag_type."($CONSTEST)";
//              print " LENGTH=$length_hex($length) ";

                if($CONSTRUCTED)
                {
                        $substring_len = strlen($substring);
                        //print "<br><br>call der_dercode, str_pos=$str_pos, max_len=$max_len<br>";
                        // if constructed and length == 0, infer constructed length
                        if($substring_len == 0)
                        {
                                $substring_len = $max_len - $str_pos;
                                $substring = substr($string_in, $str_pos, $substring_len);
                        }
                        $done_str_len = der_decode($substring, $substring_len, $tally, $level);
                        //print "<br>return from level($rtn_level) maxlen=$max_len<br>";
                        if($done_str_len < $substring_len)
                        {
                                $str_pos += $done_str_len;
                                $tally += $done_str_len;
                        }
                        else
                        {
                                $str_pos += $substring_len;
                                $tally += $substring_len;
                        }
                }
               else
                {
//                      print "VALUE= ";
                        if(is_ascii($substring))
                        {
                                //print $substring;

                        }
                        else
                        {
                                $hex_string = bin2hex($substring);
                                $chunk_hex_string = chunk_split($hex_string,2," ");

//*** this is what is being printed.

                                //print $chunk_hex_string;
                                //print "This is the hex string: ".$hex_string."\n\n";
/*
                                if($tag_class == 0 && $tag_num == 6)
                                {
                                        print "(".$oid_table[$substring].")";
                                        print " {"; oid_decode($hex_string); print "}";
                                }
*/
                        //      print "<br>";
                        }
                        $str_pos += $length;
                        $tally += $length;
                }

                //print "str_pos($str_pos); max_len($max_len); tally($tally);<br>";

                // Tag 00 is reserved for End of Content
                if($str_pos >= $my_length or $TAG == "00" )
                {
                        //print "($level) END<br>";

//print "return hext string:  ".$hex_string."\n\n\n";
return $hex_string;
                        //return ($str_pos);
                }
        }
        //print "<br>returning...<br>";
//print "return hext string:  ".$hex_string."\n\n\n";
return $hex_string;

//      return ($str_pos);
}

?>
