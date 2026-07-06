<?php

namespace services;

require_once $_SERVER['DOCUMENT_ROOT'] .'/plugins/pressmatrix/model/VideoModel.php';


use model\VideoModel;

class PressmatrixService
{
    private string $url;
    private string $organization;
    private string $publication;
    private string $token;
    private string $filestore;

    private array $config;

    public function __construct()
    {

        $this->config = get_plugin_config('pressmatrix');
        // Endpoint-Domain laut deinem cURL: editor.pressmatrix.com
        $this->url = $this->config['pressmatrix_api_url'];
        $this->organization = $this->config['pressmatrix_api_organization'];
        $this->publication = $this->config['pressmatrix_api_publication'];
        $this->token = $this->config['pressmatrix_api_token'];
        $this->filestore = $this->config['pressmatrix_api_filestore'];
    }

    public function create(int $ref)
    {
        global $baseurl;
        $feedname = 'wuh';

        $config = get_plugin_config('pressmatrix');
        $date_field_id = $config['pressmatrix_video_evt'];
        $ready_field_id = $config['pressmatrix_video_ready'];
        $mediakey_field_id = $config['pressmatrix_video_mediakey'];
        $free_field_id = $config['pressmatrix_video_free'];

        $ready_val = get_data_by_field($ref, $ready_field_id);
        $date_val = get_data_by_field($ref, $date_field_id);
        $mediakey_val = get_data_by_field($ref, $mediakey_field_id);
        $free_val = get_data_by_field($ref, $free_field_id);

        $hlsurl = $config['pressmatrix_video_hlsurl'] . $mediakey_val . '.m3u8';


        // 1. Map Resource to VideoModel
        $video = new VideoModel($config);
        $video->setGuid($ref);
        $video->setObject('FUF');
        $video->setDuration($config['pressmatrix_video_duration']);
        $video->setTitle(get_data_by_field($ref, $config['pressmatrix_video_title']) ?: "Resource " . $ref);
        $video->setDescription(get_data_by_field($ref, $config['pressmatrix_video_description']));
        $video->setLink("https://paulparey.de/?r=" . $ref);

        if ($free_val != 'frei') {
            $video->setPrice($config['pressmatrix_video_price']);
            $video->setExternalId(strtolower($feedname) . '.video.' . $ref);
        }

        $video->setEvt(new \DateTime($date_val));

        // Pfadauflösung für das lokale Bild (ResourceSpace-Spezifisch)
        $img_url_web = get_resource_path($ref, true, 'pre', false);

        if ($img_url_web === false) {
            $local_img_path = '';
            $img_url_web = '';
        } else {
            $urls = explode('/filestore', $img_url_web);
            $filestore_part = isset($urls[1]) ? $urls[1] : '';
            $clean_filestore_part = explode('?', $filestore_part)[0];

            $local_img_path = dirname(__DIR__, 3) . '/filestore' . $clean_filestore_part;
            $img_url_web = $baseurl . '/filestore' . $filestore_part;
        }

        $video->setImage($img_url_web);
        $video->setHls($hlsurl);

        // Daten aus dem Model holen
        $data = $video->getPressmatrix();

        // 2. Den Multipart-Payload flach aufbauen gemäß deinem cURL-Beispiel
        $postFields = [];
        if (isset($data['story']) && is_array($data['story'])) {
            foreach ($data['story'] as $key => $value) {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                $postFields["story[$key]"] = $value;
            }
        }

        // 3. WICHTIG: Das Bild MUSS als 'story[image]' angehängt werden (nicht nur 'image')
        if (!empty($local_img_path) && file_exists($local_img_path)) {
            $mime_type = mime_content_type($local_img_path);
            $postFields['story[image]'] = new \CURLFile($local_img_path, $mime_type, basename($local_img_path));
        }

        // 4. API-Endpoint dynamisch zusammenbauen
        $endpoint = sprintf(
            "%s/api/v2/importer/organizations/%s/publications/%s/stories",
            $this->url,
            $this->organization,
            $this->publication
        );

        // 5. cURL initialisieren
        $ch = curl_init($endpoint);

        // 6. Header definieren (Kein Content-Type manuell setzen!)
        $headers = [
            'Accept: application/json',
            'Authorization: Token ' . $this->token
        ];

        // 7. cURL Optionen setzen
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields); // Array triggert automatisch multipart/form-data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Erlaubt cURL dem --location Flag zu folgen

        // 8. Request ausführen
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // 9. Fehlerprüfung & Auswertung
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            if(array_key_exists('id',$responseData['story'])){
                return $responseData['story']['id'];
            }else{
                return null;
            }
        } else {
            return null;
        }
    }

    public function update(int $ref, string $story_id)
    {
        $config = get_plugin_config('pressmatrix');
        $feedname = 'wuh';

        $date_field_id = $config['pressmatrix_video_evt'];
        $ready_field_id = $config['pressmatrix_video_ready'];
        $mediakey_field_id = $config['pressmatrix_video_mediakey'];
        $free_field_id = $config['pressmatrix_video_free'];

        $date_val = get_data_by_field($ref, $date_field_id);
        $mediakey_val = get_data_by_field($ref, $mediakey_field_id);
        $free_val = get_data_by_field($ref, $free_field_id);

        $hlsurl = $config['pressmatrix_video_hlsurl'] . $mediakey_val . '.m3u8';

        // 1. Map Resource to VideoModel
        $video = new VideoModel($config);
        $video->setGuid($ref);
        $video->setObject('FUF');
        $video->setDuration($config['pressmatrix_video_duration']);
        $video->setTitle(get_data_by_field($ref, $config['pressmatrix_video_title']) ?: "Resource " . $ref);
        $video->setDescription(get_data_by_field($ref, $config['pressmatrix_video_description']));
        $video->setLink("https://paulparey.de/?r=" . $ref);

        if ($free_val != 'frei') {
            $video->setPrice($config['pressmatrix_video_price']);
            $video->setExternalId(strtolower($feedname) . '.video.' . $ref);
        }

        $video->setEvt(new \DateTime($date_val));
        $video->setHls($hlsurl);

        // Daten aus dem Model holen
        $modelData = $video->getPressmatrix();

        // 2. JSON-Payload für PATCH aufbauen
        // Wichtig: Laut Doku wird ein verschachteltes JSON unter dem Key 'story' erwartet
        $payload = [
            'story' => []
        ];

        if (isset($modelData['story']) && is_array($modelData['story'])) {
            foreach ($modelData['story'] as $key => $value) {
                // Bei JSON können (und sollten) Booleans als echte Booleans übergeben werden
                if (is_bool($value)) {
                    $payload['story'][$key] = $value;
                } else {
                    $payload['story'][$key] = $value;
                }
            }
        }

        // Hinweis: Falls das Bild beim PATCH ebenfalls aktualisiert werden soll,
        // müsste geprüft werden, ob die API Binärdaten/Multipart im PATCH-Request erlaubt.
        // Laut deiner Doku-Vorlage ist es hier reines JSON (daher bleibt das lokale Bild hier außen vor).

        // 3. API-Endpoint für PATCH zusammenbauen
        $endpoint = sprintf(
            "%s/api/v2/importer/organizations/%s/publications/%s/stories/%s",
            $this->url,
            $this->organization,
            $this->publication,
            $story_id
        );

        // 4. cURL initialisieren
        $ch = curl_init($endpoint);

        // 5. Header definieren (Diesmal MIT Content-Type: application/json)
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Token ' . $this->token
        ];

        // 6. cURL Optionen setzen
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // 7. Request ausführen
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        // 8. Auswertung (Erfolgreiche HTTP-Statuscodes für Updates sind meist 200 oder 204)
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            return false;
        }
    }

    public function publish(string $story_id): bool
    {
        // 1. API-Endpoint für das Publishing zusammenbauen
        $endpoint = sprintf(
            "%s/api/v2/importer/organizations/%s/publications/%s/stories/%s/publish",
            $this->url,
            $this->organization,
            $this->publication,
            $story_id
        );

        // 2. cURL initialisieren
        $ch = curl_init($endpoint);

        // 3. Header definieren
        $headers = [
            'Accept: application/json',
            'Authorization: Token ' . $this->token
        ];

        // 4. cURL Optionen setzen
        curl_setopt($ch, CURLOPT_POST, true);
        // Einige APIs erwarten bei leeren POST-Requests explizit die Content-Length: 0
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // 5. Request ausführen
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        // 6. Auswertung (200 OK oder andere 2xx Erfolgsstatuscodes)
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            return false;
        }
    }
}