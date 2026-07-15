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
$vimp_aktiv_field    = get_field($config['pressmatrix_video_active']);
$video_file_field_id = $config['pressmatrix_video_file'];
$pressmatrix_id      = $config['pressmatrix_video_pressmatrix'];
$titel_id            = $config['pressmatrix_video_title'];
$evt_field_id        = $config['pressmatrix_video_evt'];
$object_field_id     = $config['pressmatrix_video_object_1'];
$object_2_id         = $config['pressmatrix_video_object_2'];
$ref = $argv[1] ?? null;
CLI::log("Pressmatrix CLI-Tool Single für " . $ref, "cyan", true);

$search_query = $vimp_aktiv_field['name'] . ':ja';
$results = do_search($search_query);

// --- SORTIERUNG START ---
if (is_array($results) && !empty($results)) {
    // 1. Daten für die Sortierung vorbereiten (verhindert mehrfache DB-Abfragen in der Schleife)
    foreach ($results as $key => $resource) {
        $ref = $resource['ref'];
        $results[$key]['_sort_evt']    = get_data_by_field($ref, $evt_field_id);
        $results[$key]['_sort_object'] = get_data_by_field($ref, $object_field_id);
    }

    // 2. usort anwenden: Erst nach EVT, bei Gleichheit nach OBJECT
    usort($results, function ($a, $b) {
        // Erstes Kriterium: EVT (Absteigend - neueste oben)
        $cmp = strcmp($b['_sort_evt'], $a['_sort_evt']);

        // Wenn EVT identisch ist, nach OBJECT sortieren (Alphabetisch aufsteigend)
        if ($cmp === 0) {
            $cmp = strcmp($a['_sort_object'], $b['_sort_object']);
        }

        return $cmp;
    });
}
foreach($results as $resource) {
    $ref = $resource['ref'];
    $field_value = get_data_by_field($ref, $video_file_field_id);

    if ($field_value !== null && trim($field_value) !== '') {
        $pm_id = get_data_by_field($ref, $pressmatrix_id);

        // Zusätzliche Felder für die Anzeige auslesen
        $evt_value = get_data_by_field($ref, $evt_field_id);
        $object_1  = get_data_by_field($ref, $object_field_id);
        $object_2  = get_data_by_field($ref, $object_2_id);

        // Objekte sauber zusammenführen falls beide existieren
        $object_combined = trim($object_1 . ' ' . $object_2);
        CLI::log("Ref: " . $ref,'reset',false);
        CLI::log("Titel: " . htmlspecialchars(get_data_by_field($ref, $titel_id)),'reset',false);
        CLI::log("EVT: " . htmlspecialchars($evt_value),'reset',false);
        CLI::log("Pressmatrix: " . ($pm_id ? htmlspecialchars($pm_id) : "<i>Noch nicht synchronisiert</i>"),'reset',false);
        CLI::log("-------------",'reset',false);

/*

        print "<tr {$row_style}>";
        print "<td>" . $ref . "</td>";
        print "<td>" . ($evt_value ? htmlspecialchars($evt_value) : "<i>-</i>") . "</td>";
        print "<td>" . ($object_combined ? htmlspecialchars($object_combined) : "<i>-</i>") . "</td>";
        print "<td>" . htmlspecialchars(get_data_by_field($ref, $titel_id)) . "</td>";
        print "<td>" . htmlspecialchars(get_data_by_field($ref, $config['pressmatrix_video_active'])) . "</td>";
        print "<td>" . htmlspecialchars($field_value) . "</td>";
        print "<td>" . ($pm_id ? htmlspecialchars($pm_id) : "<i>Noch nicht synchronisiert</i>") . "</td>";

        print $ref;*/
    }
}


