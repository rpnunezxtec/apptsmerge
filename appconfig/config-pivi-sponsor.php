<?php

// This is a list defining the attributes required before sponsorship can be undertaken.
// Expects the variables $uedn to be defined prior to inclusion.

// dn: the DN to the object
// attr: the attribute within the object
// gcotag: a tag number of the object is a GCO. False for other objects
// val: false for ANY non-null contents, otherwise a specific value
// name: a name for the attribute to appear in the missing list alert popup

$mandatory_list = array
(
    array("dn" => "emplid=usaccess,".$uedn, "attr" => "email", "gcotag" => false, "val" => false, "name" => "Office Email"),
    array("dn" => $uedn, "attr" => "nationality", "gcotag" => false, "val" => false, "name" => "Citizenship"),
    array("dn" => "gcoid=general,".$uedn, "attr" => "xblk", "gcotag" => "002502", "val" => false, "name" => "Affiliation"),
    array("dn" => "gcoid=token,procid=token,ounit=piv,".$uedn, "attr" => "xblk", "gcotag" => "0020c6", "val" => false, "name" => "Card Topology"),
    array("dn" => "gcoid=token,procid=token,ounit=piv,".$uedn, "attr" => "xblk", "gcotag" => "002087", "val" => false, "name" => "Issuance Reason"),
);

?>
