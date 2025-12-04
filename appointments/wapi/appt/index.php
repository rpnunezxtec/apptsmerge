<?php
require '/authentx/core/authentx5/restler3/restler.php';

use Luracast\Restler\Restler;
use App\Controllers\Appointment;
use App\Support\Config;

$r = new Restler();

// Register endpoints
$r->addAPIClass(Appointment::class, Config::API_VERSION); // exposes /appointment under /appointment/v1

$r->handle();