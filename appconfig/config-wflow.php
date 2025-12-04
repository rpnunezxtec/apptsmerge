<?php

$wflow_template = array(
		"vetting" => array(
			"vetting" => array(
				"Investigation Start" => "bgistart",
				"Investigation Complete" => "bgiend",
				"Identity" => "id",
				"Impersonation Check" => "ichk",
			),
		),
		"arrow" => array(
			"start" => array(
				"Token" => "token",
				"Sponsor" => "sponsor",
				// "Custom Step" => "custom step",
				// "Authorize" => "authorize",
				// "Authorize #2" => "authorize #2",
			),
			"enrollment" => array(
				"Fingerprints" => "fpcap",
				"Document" => "doccap",
				"Portrait" => "photocap",
				"Signature" => "signature",
			),
			"issuance" => array(
				"Print" => "print",
				"Encode" => "encode",
				"Delivery" => "ship",
				"Activation" => "activate",
			),
		),
);
	
$wflow_procs = array(
"IAL1" => array(
	"dac" => array(
		"arrow" => array(
			"start" => array(
				"Token" => "token",
				"Sponsor" => "sponsor",
			),
			"issuance" => array(
				"Print" => "print",
				"Encode" => "encode",
				"Delivery" => "ship",
				"Activation" => "activate",
			),
		),
	),
),
"IAL3" => array(
	"pivi" => array(
		"vetting" => array(
			"vetting" => array(
				"Investigation Start" => "bgistart",
				"Investigation Complete" => "bgiend",
				"Identity" => "id",
				"Impersonation Check" => "ichk",
			),
		),
		"arrow" => array(
			"start" => array(
				"Token" => "token",
				"Sponsor" => "sponsor",
			),
			"enrollment" => array(
				"Fingerprints" => "fpcap",
				"Document" => "doccap",
				"Portrait" => "photocap",
				"Signature" => "signature",
			),
			"issuance" => array(
				"Print" => "print",
				"Encode" => "encode",
				"Delivery" => "ship",
				"Activation" => "activate",
			),
		),
	),
	"piv" => array(
		"vetting" => array(
			"vetting" => array(
				"Investigation Start" => "bgistart",
				"Investigation Complete" => "bgiend",
				"Identity" => "id",
				"Impersonation Check" => "ichk",
			),
		),
		"arrow" => array(
			"start" => array(
				"Token" => "token",
				"Sponsor" => "sponsor",
				// "Authorize" => "authorize",
			),
			"enrollment" => array(
				"Fingerprints" => "fpcap",
				"Document" => "doccap",
				"Portrait" => "photocap",
				"Signature" => "signature",
			),
			"issuance" => array(
				"Print" => "print",
				"Encode" => "encode",
				"Delivery" => "ship",
				"Activation" => "activate",
			),
		),
	),
),
);

$wflow_disabled = array(
);

// Workflow types defined for this application along with process names for each workflow.
// Each workflow type is a key to a list of process object ID's that are associated with it.
// All workflows contain HSPD12 common object info, so these do not have to be identified here.
$workflow_types = array(
	"dac" => array("token", "sponsor", "print", "encode", "ship", "activate",  ),
	"piv" => array("hspd12", "ichk", "token", "sponsor", "authorize", "fpcap", "doccap", "photocap", "signature", "print", "encode", "ship", "activate",  ),
	"pivi" => array("hspd12", "ichk", "token", "sponsor", "fpcap", "doccap", "photocap", "signature", "print", "encode", "ship", "activate",  ),
);

// The captions for each of the process objects for the processdiscovery service to use 
// when creating the XML output.
$workflow_captions = array(
	"dac" => array(
		"token" => "Token",
		"sponsor" => "Sponsor",
		"print" => "Print",
		"encode" => "Encode",
		"ship" => "Ship",
		"activate" => "Activate",
	),
	"piv" => array(
		"ichk" => "Impersonation Check",
		"token" => "Token",
		"sponsor" => "Sponsor",
		// "authorize" => "Authorize",
		"fpcap" => "Fingerprint Capture",
		"doccap" => "Document Capture",
		"photocap" => "Portrait Capture",
		"signature" => "Signature",
		"print" => "Print",
		"encode" => "Encode",
		"ship" => "Ship",
		"activate" => "Activate",
	),
	"pivi" => array(
		"ichk" => "Impersonation Check",
		"token" => "Token",
		"sponsor" => "Sponsor",
		"fpcap" => "Fingerprint Capture",
		"doccap" => "Document Capture",
		"photocap" => "Portrait Capture",
		"signature" => "Signature",
		"print" => "Print",
		"encode" => "Encode",
		"ship" => "Ship",
		"activate" => "Activate",
	),
);

$workflow_captions_template = array(
	"sponsor" => "Sponsor",
	"fpcap" => "Fingerprint Capture",
	"doccap" => "Document Capture",
	"photocap" => "Portrait Capture",
	"iriscap" => "Iris Capture",
	"ichk" => "Impersonation Check",
	"print" => "Print",
	"encode" => "Encode",
	"ship" => "Ship",
	"activate" => "Activate",
	"token" => "Token",
	"signature" => "Signature",
	"custom step" => "Custom Step",
	"authorize" => "Authorize",
);

?>
