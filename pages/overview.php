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

// Falls ein konkreter Sync-Klick reinkommt
$action = getval("action", "");
$sync_ref = getval("sync_ref", 0, true); // umbenannt in sync_ref, um Konflikte mit dem Modal-ref zu vermeiden

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

// Hier holen wir wieder ALLLE Ressourcen mit Aktiv-Flag "ja"
$search_query = $vimp_aktiv_field['name'] . ':ja';
$results = do_search($search_query);

// Wir merken uns die ursprüngliche Ref aus der URL, um sie eventuell in der Liste hervorzuheben
$origin_ref = getval("ref", 0, true);
?>

    <div class="BasicsBox">
        <h1>Pressmatrix Status-Übersicht</h1>
        <div class="Listview">
            <table border="0" cellpadding="0" cellspacing="0" class="ListviewStyle">
                <tbody>
                <tr class="ListviewTitleStyle">
                    <td>Resource ID</td>
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

                        // Optionale optische Spielerei: Die Ressource, von der aus das Modal geöffnet wurde, fett markieren
                        $row_style = ($ref == $origin_ref) ? "style='background-color: #f0f8ff; font-weight: bold;'" : "";

                        print "<tr {$row_style}>";
                        print "<td>" . $ref . "</td>";
                        print "<td>" . htmlspecialchars(get_data_by_field($ref, $titel_id)) . "</td>";
                        print "<td>" . htmlspecialchars(get_data_by_field($ref, $config['pressmatrix_video_active'])) . "</td>";
                        print "<td>" . htmlspecialchars($field_value) . "</td>";
                        print "<td>" . ($pm_id ? htmlspecialchars($pm_id) : "<i>Noch nicht synchronisiert</i>") . "</td>";

                        // Wichtig: Wir übergeben die origin_ref als 'ref' weiter, damit das Modal weiß, woher es kam
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