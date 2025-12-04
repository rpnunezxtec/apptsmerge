<?php
// $Id:$

// email template for booking appointments on behalf of another person.

$et_booking = array(
"subject" => "Your appointment.",
"from" => "authentx@xtec.com",
"body" => "Your appointment has been added by %adminname% to the Authentx appointments system.\n"
		. "Please click on the link below or copy and paste it into your browser to access the appointments system to manage your appointment.\n"
		. "%vurl%\n"
		. "This message is automatically generated. Please do not reply.\n",

);


?>