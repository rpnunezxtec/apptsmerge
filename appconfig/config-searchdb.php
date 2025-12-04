<?php
// $Id:$

// Configuration for SQL search database population and update.

// The number of seconds to sleep between running a db update operation
$_searchdb_sleep = 3600;

// Database connection parameters.
$_db_host = "127.0.0.1";
$_db_database = "authentxsearch_usaccess";
$_db_user = "usaccesssearchdbuser";
$_db_passwd = "78MicrOcameL_usaccess";

// The searchtext column is filled with composite results, which can be enabled or disabled here
$_searchdb_fields = array (
"fullname" => true,
"entemail" => true,
"emplemail" => true,
);

// The emplid for the application, used to get data from the employment object if specified
$emplid = "usaccess";

// The client use of the searchdb
// Make use of the searchdb (enable/disable)
$_searchdb_enable = true;

// Number of rows to limit in the return set
$_searchdb_limitrows = 40;

?>