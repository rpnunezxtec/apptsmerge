<?php
require_once("/authentx/core/authentx5/restler3/restler.php");
require_once('fido.php');
use Luracast\Restler\Restler;
use Luracast\Restler\Defaults;
Defaults::$smartAutoRouting = false;

$r = new Restler();
$r->addAPIClass('fido');
$r->setSupportedFormats('JsonFormat');
$r->handle();

?>