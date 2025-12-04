<?php

// $Id:$

$_email_notification_templates = array (
	"process_sponsor" => array (
		"subject" => "%workflow% Sponsorship",
		"from" => "authentx@xtec.com",
		"body" => "You have been sponsored for enrollment in the HSPD12 System.\n"
				. "Please contact the registrar to arrange an appointment for enrollment.\n"
				. "This message is automatically generated. Please do not reply.\n",
	),
	
	"process_reissue" => array (
		"subject" => "%workflow% Re-issuance",
		"from" => "authentx@xtec.com",
		"body" => "Your %workflow% has now been enabled for re-issuance.\n"
				. "Please contact the registrar to arrange an appointment for enrollment.\n"
				. "This message is automatically generated. Please do not reply.\n",
	),
	
	"process_printing" => array (
		"subject" => "%workflow% Approval",
		"from" => "authentx@xtec.com",
		"body" => "Your %workflow% has now been approved and is at the printing stage.\n"
				. "It will be shipped to the address designated by your sponsor.\n"
				. "%shiptoaddress%\n"
				. "You will receive further notification when it has been shipped.\n"
				. "\nThis message is automatically generated. Please do not reply.\n",
	),
	
	"process_printed" => array (
		"subject" => "%workflow% Printed",
		"from" => "authentx@xtec.com",
		"body" => "Your %workflow% has now been printed and is soon to be shipped.\n"
				. "It will be shipped to the address designated by your sponsor.\n"
				. "%shiptoaddress%\n"
				. "You will receive further notification when it has been shipped.\n"
				. "\nThis message is automatically generated. Please do not reply.\n",
	),
	
	"process_shipped" => array (
		"subject" => "%workflow% Shipped",
		"from" => "authentx@xtec.com",
		"body" => "This message serves to inform you that your %workflow% card issued for agency %agency% has been printed and shipped.\n"
				. "This %workflow% card will be delivered to the address selected by your sponsor.\n"
				. "%shiptoaddress%\n"
				. "The estimated date of delivery is %estdeliverydate%.\n"
				. "Pick up hours are 8AM-5PM, Monday-Friday, excluding Government holidays.\n"
				. "You will be notified when it has been received and is ready for activation.\n"
				. "Your card activation PIN is %upin%.\n"
				. "\nThis message is automatically generated. Please do not reply.\n",
	),
	
	"process_received" => array (
		"subject" => "%workflow% Received",
		"from" => "authentx@xtec.com",
		"body" => "Your %workflow% has now been received and is awaiting activation.\n"
				. "Please arrange an appointment with the activator to activate your %workflow%.\n"
				. "%shiptoaddress%\n"
				. "Your card activation PIN is %upin%.\n"
				. "\nThis message is automatically generated. Please do not reply.\n",
	),
	
	"emstat_status" => array (
		"subject" => "IDS Workstation State Change : %devicename%",
		"from" => "postmaster@authentx.com",
		"body" => "Workstation ID: %deviceid%\nWorkstation Name: %devicename%\nWorkstation Location: %devicelocn%\n"
				. "Status changed at %contime% from %cos_status% to %devicestatus%\n"
				. "\nThis message is automatically generated. Please do not reply.\n",
	),
	
	
);

?>