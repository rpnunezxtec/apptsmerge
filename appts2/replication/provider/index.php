<?php
require_once("/authentx/core/authentx5appcore/restler3/restler.php");
require_once('apptrepl.php');
use Luracast\Restler\Restler;
use Luracast\Restler\Defaults;
Defaults::$smartAutoRouting = false;

$r = new Restler(true);
$r->addAPIClass('apptrepl');
$r->setSupportedFormats('JsonFormat', 'XmlFormat');
$r->handle();

?>