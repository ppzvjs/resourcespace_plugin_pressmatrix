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
$ref = $argv[1] ?? null;
CLI::log("Pressmatrix CLI-Tool Single für " . $ref, "cyan", true);

if (!$ref) {
    CLI::log("[ERROR] Bitte gib eine Ressourcen-ID an (z.B. php cli_command.php 123)!", "red", true);
    return;
}
$data = get_resource_data($ref);
if(!$data){
    CLI::log("[ERROR] Resource " . $ref . " nicht gefunden", "red", true);
    return;
}
$field_pressmatrix_id = $config['pressmatrix_video_pressmatrix'];
$pressmatrix_id = get_data_by_field($ref, $field_pressmatrix_id);
$servicePressmatrix = new PressmatrixService();
if($pressmatrix_id != ''){
    CLI::log("Updaten", "green", false);
    $response = $servicePressmatrix->update($ref,$pressmatrix_id,false);
    if($response){
        CLI::log('Erfolgreich',"green",true);
    }else{
        CLI::log('Fehler','red',true);
    }
}else{
    CLI::log("Anlegen", "green", false);
    $id = $servicePressmatrix->create($ref,false);
    if($id !== null){
        CLI::log('Erfolgreich',"green",true);
    }else{
        CLI::log('Fehler','red',true);
    }
}

