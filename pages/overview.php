<?php

use services\PressmatrixService;

include_once __DIR__ . '/../../../include/boot.php';
include_once __DIR__ . '/../../../include/authenticate.php';

// Header für das Modal laden
include __DIR__ . '/../../../include/header.php';

require_once __DIR__ . '/../services/PressmatrixService.php';

$config = get_plugin_config('pressmatrix');

$vimp_aktiv_field    = get_field($config['pressmatrix_video_active']);
$video_file_field_id = $config['pressmatrix_video_file'];
$pressmatrix_id      = $config['pressmatrix_video_pressmatrix'];
$titel_id            = $config['pressmatrix_video_title'];
$evt_field_id        = $config['pressmatrix_video_evt'];
$object_field_id     = $config['pressmatrix_video_object_1'];
$object_2_id         = $config['pressmatrix_video_object_2'];

// Falls ein konkreter Sync-Klick reinkommt
$action = getval("action", "");
$sync_ref = getval("sync_ref", 0, true);

if ($action === "sync_now" && $sync_ref > 0) {
    $video_file_value = get_data_by_field($sync_ref, $video_file_field_id);
    $title_value      = get_data_by_field($sync_ref, $titel_id);

    $service = new PressmatrixService();
    $response = $service->syncVideo($sync_ref, $title_value, $video_file_value);

    if ($response && $response['success']) {
        update_field($sync_ref, $pressmatrix_id, $response['pressmatrix_id']);
        echo "<div class='PageInformative'>Ressource #{$sync_ref} erfolgreich an Pressmatrix übertragen!</div>";
    } else {
        echo "<div class='PageInformative' style='background-color:#f8d7da; color:#721c24;'>Übertragung für #{$sync_ref} fehlgeschlagen.</div>";
    }
}

// ALLLE Ressourcen mit Aktiv-Flag "ja" holen
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
// --- SORTIERUNG ENDE ---

// Wir merken uns die ursprüngliche Ref aus der URL
$origin_ref = getval("ref", 0, true);
?>

    <div class="BasicsBox">
        <h1>Pressmatrix Status-Übersicht</h1>
        <div class="Listview">
            <table border="0" cellpadding="0" cellspacing="0" class="ListviewStyle">
                <tbody>
                <tr class="ListviewTitleStyle">
                    <td>Resource ID</td>
                    <td>EVT</td>
                    <td>Objekt</td>
                    <td>Titel</td>
                    <td>Aktiv</td>
                    <td>1080p</td>
                    <td>Pressmatrix ID</td>
                    <td class="ListviewCentred">Aktion</td>
                </tr>
                <?php
                foreach($results as $resource){
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

                        $row_style = ($ref == $origin_ref) ? "style='background-color: #f0f8ff; font-weight: bold;'" : "";

                        print "<tr {$row_style}>";
                        print "<td>" . $ref . "</td>";
                        print "<td>" . ($evt_value ? htmlspecialchars($evt_value) : "<i>-</i>") . "</td>";
                        print "<td>" . ($object_combined ? htmlspecialchars($object_combined) : "<i>-</i>") . "</td>";
                        print "<td>" . htmlspecialchars(get_data_by_field($ref, $titel_id)) . "</td>";
                        print "<td>" . htmlspecialchars(get_data_by_field($ref, $config['pressmatrix_video_active'])) . "</td>";
                        print "<td>" . htmlspecialchars($field_value) . "</td>";
                        print "<td>" . ($pm_id ? htmlspecialchars($pm_id) : "<i>Noch nicht synchronisiert</i>") . "</td>";

                        $sync_url = $_SERVER['PHP_SELF'] . "?action=sync_now&sync_ref=" . $ref . "&ref=" . $origin_ref;
                        global $baseurl;
                        $urlparams = [
                                'ref' => $ref
                        ];
                        $overrideparams = [];
                        print "<td class='ListviewCentred'>";
                        print '<a href="' . generateURL($baseurl . "/plugins/pressmatrix/pages/actions/create.php", $urlparams, $overrideparams) . '" onclick="return ModalLoad(this, true);"><i class="fa fa-fw fa-history"></i>&nbsp;Übertragung Pressmatrix</a>';
                        print "</td>";
                        print "</tr>";
                    }
                }
                ?>
                </tbody>
            </table>
        </div>

    </div>

<?php
include __DIR__ . '/../../../include/footer.php';