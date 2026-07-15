<?php


// Sicherstellen, dass das Skript nur über die CLI aufgerufen wird
if (php_sapi_name() !== 'cli') {
    die("Dieses Skript darf nur über die CLI ausgeführt werden.\n");
}

use services\PressmatrixService;

// ResourceSpace-Umgebung laden (Pfad anpassen je nach Ordnertiefe!)
include "../../../../include/boot.php";
include "Cli.php";
include "../../services/PressmatrixService.php";

$config = get_plugin_config('pressmatrix');

$servicePressmatrix = new PressmatrixService();
$list = $servicePressmatrix->listArticles('jagen');
