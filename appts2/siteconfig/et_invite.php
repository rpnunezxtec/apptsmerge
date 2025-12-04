<?php
// $Id:$

// email template for invitations.

$et_invite = array(
"subject" => "Invitation to appointments system.",
"from" => "authentx@xtec.com",
"body" => "Invitee,\n"
		. "You are receiving this email so that you may schedule an appointment to get your PIV Card. "
		. "Please READ and FOLLOW the instructions below to schedule a PIV Card appointment.\n"
		. " - Click the following link to access the Appointment Management System: \n"
		. " %vurl%\n"
		. " - You will see a screen that says \"There is a problem with this website's security "
		. " certificate\", but please click \"Continue to this website\" so that you can schedule "
		. " your appointment.\n"
		. " - Schedule an Appointment by clicking on an available time block "
		. " (the available blocks are green and the unavailable blocks are red).\n"
		. " - Enter your information in the appointment screen that pops up and click "
		. " \"Book Appointment\".\n\n"
		. "This message is automatically generated. Please do not reply.\n",

);


?>