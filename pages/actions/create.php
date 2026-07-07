<?php

use services\PressmatrixService;

include "../../../../include/db.php";

include "../../../../include/authenticate.php";

include "../../../../include/header.php";

require_once $_SERVER['DOCUMENT_ROOT'] .'/plugins/pressmatrix/services/PressmatrixService.php';

$ref=getval("ref","",true);
// Function to check if a specific preview size exists

$config = get_plugin_config('pressmatrix');

?>
<div class="BasicsBox">
    <p>Übertragung an Pressmatrix</p>
    <?php

    $field_pressmatrix_id = $config['pressmatrix_video_pressmatrix'];




    $pressmatrix_id = get_data_by_field($ref, $field_pressmatrix_id);




    $pressmatrixService = new PressmatrixService();
    print "<b>Status: </b>";
    if($pressmatrix_id != ''){
        print "Resource bei Pressmatrix vorhanden mit der ID: " . $pressmatrix_id;
        print "<br><b>Aktion: </b> Update<br>";
        $responseData = $pressmatrixService->update($ref,$pressmatrix_id);
        if($responseData === true){
            print "Erfolgreich für Resource: " . $ref;
            //$publish = $pressmatrixService->publish($pressmatrix_id);
            /*if($publish === true){
                print "LIVE";
            }else{
                print "Fehler";
            }*/
        }else{
            print "Fehlgeschlagen";
        }
    }else{
        print "Noch keine Resource bei Pressmatrix vorhanden.";
        print "<br><b>Aktion: </b> Anlegen<br>";

        $pressmatrixID = $pressmatrixService->create($ref);
        if($pressmatrixID !== null){
            print "Erfolgreich";
            update_field($ref, $config['pressmatrix_video_pressmatrix'], $pressmatrixID);
            //$publish = $pressmatrixService->publish($pressmatrixID);
            /*if($publish === true){
                print "LIVE";
            }else{
                print "Fehler";
            }*/
        }else{
            print "Fehlgeschlagen";
        }
    }


    ?>
</div>