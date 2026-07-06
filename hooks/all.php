<?php
// Datei: /plugins/pressmatrix/hooks/view.php (oder all.php)


function HookPressmatrixAllAfterResourceActions(){
    global $baseurl, $urlparams, $overrideparams, $lang;
    print '<li><a href="' . generateURL($baseurl . "/plugins/pressmatrix/pages/actions/create.php", $urlparams, $overrideparams) . '" onclick="return ModalLoad(this, true);"><i class="fa fa-fw fa-history"></i>&nbsp;Übertragung Pressmatrix</a></li>';

}


//HookTranscodeAllAfterResourceActions()
function HookPressmatrixViewRenderAfterResourceDetails($ref, $resource)
{

    print "moin";
    die();
    // 1. Hole die bestehende Pressmatrix-ID aus den Metadaten der Ressource
    // (Passe die ID des Metadatenfeldes '123' an das Feld an, in dem du die ID speicherst)
    $config = get_plugin_config('pressmatrix');
    $pm_id_field = isset($config['pressmatrix_story_id_field']) ? $config['pressmatrix_story_id_field'] : 100;

    $pressmatrix_id = get_data_by_field($ref, $pm_id_field);

    // 2. HTML-Block rendert das Panel im ResourceSpace Design
    ?>
    <div class="RecordBox">
        <div class="RecordPanel">
            <div class="Title"><?php echo i18n_get("pressmatrix_integration_title") ?: "Pressmatrix Integration"; ?></div>

            <table class="InfoTable">
                <tr>
                    <td class="Title">Status:</td>
                    <td>
                        <?php if (!empty($pressmatrix_id)): ?>
                            <span style="color: #2ecc71; font-weight: bold;">✓ Verbunden</span> (Story-ID: <?php echo htmlspecialchars($pressmatrix_id); ?>)
                        <?php else: ?>
                            <span style="color: #e74c3c; font-weight: bold;">✗ Nicht exportiert</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <div class="Question" style="padding-top: 15px;">
                <?php if (empty($pressmatrix_id)): ?>
                    <button class="HorizontalAction" onclick="triggerPressmatrixAction('create', <?php echo $ref; ?>)">
                        🚀 Story anlegen
                    </button>
                <?php else: ?>
                    <button class="HorizontalAction" onclick="triggerPressmatrixAction('update', <?php echo $ref; ?>)">
                        🔄 Aktualisieren
                    </button>

                    <button class="HorizontalAction" style="background-color: #d63031; color: white;" onclick="if(confirm('Möchtest du diese Story wirklich aus Pressmatrix löschen?')) triggerPressmatrixAction('delete', <?php echo $ref; ?>)">
                        🗑️ Aus Pressmatrix löschen
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function triggerPressmatrixAction(action, ref) {
            if (!action || !ref) return;

            // Loader/Feedback anzeigen
            jQuery('#pressmatrix_status').html('Verarbeite Anfrage...');

            jQuery.ajax({
                url: window.baseurl + '/plugins/pressmatrix/pages/action_handler.php',
                type: 'POST',
                data: {
                    action: action,
                    ref: ref
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Erfolgreich ausgeführt: ' + response.message);
                        location.reload(); // Seite neu laden, um geänderte IDs/Status zu sehen
                    } else {
                        alert('Fehler: ' + response.message);
                    }
                },
                error: function() {
                    alert('Ein serverseitiger Fehler ist aufgetreten.');
                }
            });
        }
    </script>
    <?php
}