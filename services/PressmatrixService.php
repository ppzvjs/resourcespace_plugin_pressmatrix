<?php

namespace services;

require_once $_SERVER['DOCUMENT_ROOT'] . '/plugins/pressmatrix/model/VideoModel.php';

use model\VideoModel;
use DateTime;
use CURLFile;

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
    }

    private function switchApi(int $ref)
    {
        $object_1_id = get_data_by_field($ref, $this->config['pressmatrix_video_object_1']);
        if(in_array(strtolower($object_1_id),['wuh','jww','djz'])){
            $api = 'jagen';
        }else{
            $api = 'angeln';
        }
        $this->url = $this->config['pressmatrix_api_url_' . $api];
        $this->organization = $this->config['pressmatrix_api_organization_' . $api];
        $this->publication = $this->config['pressmatrix_api_publication_' . $api];
        $this->token = $this->config['pressmatrix_api_token_' . $api];
        $this->filestore = $this->config['pressmatrix_api_filestore_' . $api];
        return $api;
    }

    /**
     * Erstellt eine neue Story (Multipart/Form-Data inkl. Bild-Upload)
     */
    public function create(int $ref): ?string
    {
        global $baseurl;

        // VideoModel bauen & Daten holen
        $video = $this->buildVideoModel($ref,true);
        if ($video === null) {
            return null;
        }

        // Bildpfade auflösen (Spezifisch für Erstellung)
        $img_url_web = get_resource_path($ref, true, 'pre', false);
        $local_img_path = '';

        if ($img_url_web !== false) {
            $urls = explode('/filestore', $img_url_web);
            $filestore_part = isset($urls[1]) ? $urls[1] : '';
            $clean_filestore_part = explode('?', $filestore_part)[0];

            $local_img_path = dirname(__DIR__, 3) . '/filestore' . $clean_filestore_part;
            $img_url_web = $baseurl . '/filestore' . $filestore_part;
        }

        $video->setImage($img_url_web);
        $data = $video->getPressmatrix();


        // Payload flach aufbauen für multipart/form-data
        /*$postFields = [];
        if (isset($data['story']) && is_array($data['story'])) {
            foreach ($data['story'] as $key => $value) {
                $postFields["story[$key]"] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
            }
        }*/
        $postFields = [];

        if (isset($data['story']) && is_array($data['story'])) {
            // Hilfsfunktion um verschachtelte Arrays flachzuklopfen
            $flatten = function ($data, $prefix = 'story') use (&$flatten, &$postFields) {
                foreach ($data as $key => $value) {
                    $currentKey = $prefix . '[' . $key . ']';
                    if (is_array($value)) {
                        $flatten($value, $currentKey);
                    } else {
                        $postFields[$currentKey] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                    }
                }
            };

            $flatten($data['story']);
        }

        // Lokales Bild anhängen falls vorhanden
        if (!empty($local_img_path) && file_exists($local_img_path)) {
            $mime_type = mime_content_type($local_img_path);
            $postFields['story[image]'] = new CURLFile($local_img_path, $mime_type, basename($local_img_path));
        }
        $this->switchApi($ref);
        $api = $endpoint = $this->getEndpointUrl();
        $response = $this->sendRequest($endpoint, 'POST', $postFields);

        print "<b>Api:</b> " . $api."<br>";

        if ($response) {
            $responseData = json_decode($response, true);
            if (isset($responseData['story']['id'])) {
                update_field($ref, $this->config['pressmatrix_video_external'], $video->getExternalId());
                update_field($ref, $this->config['pressmatrix_video_apple'], $video->getApple());
                update_field($ref, $this->config['pressmatrix_video_google'], $video->getGoogle());
                print "<b>External ID:</b> " . $video->getExternalId() . "<br>";
                return $responseData['story']['id'];

            }
        }

        return null;
    }

    /**
     * Aktualisiert eine bestehende Story via PATCH (JSON)
     */
    public function update(int $ref, string $story_id): bool
    {
        $video = $this->buildVideoModel($ref);
        if ($video === null) {
            return false;
        }
        $modelData = $video->getPressmatrix();

        file_put_contents('debug.txt',json_encode($modelData));

        $payload = ['story' => []];
        if (isset($modelData['story']) && is_array($modelData['story'])) {
            foreach ($modelData['story'] as $key => $value) {
                $payload['story'][$key] = $value;
            }
        }
        $api = $this->switchApi($ref);
        print "<b>Api:</b> " . $api."<br>";
        $endpoint = $this->getEndpointUrl($story_id);
        $headers = ['Content-Type: application/json'];

        $response = $this->sendRequest($endpoint, 'PATCH', json_encode($payload), $headers);


        update_field($ref, $this->config['pressmatrix_video_external'], $video->getExternalId());
        update_field($ref, $this->config['pressmatrix_video_apple'], $video->getApple());
        update_field($ref, $this->config['pressmatrix_video_google'], $video->getGoogle());

        print "<b>External ID:</b> " . $video->getExternalId() . "<br>";

        return $response !== null;
    }

    /**
     * Veröffentlicht eine Story
     */
    public function publish(string $story_id, int $ref): bool
    {
        $api = $this->switchApi($ref);
        $endpoint = $this->getEndpointUrl($story_id, '/publish');
        $response = $this->sendRequest($endpoint, 'POST', '');

        return $response !== null;
    }

    private function generateId(string $objekt){
        $counterFile = "counter_" . $objekt . ".txt";
        if (file_exists($counterFile)) {
            $currentNumber = (int)file_get_contents($counterFile);
        } else {
            $currentNumber = 1;
        }
        if ($currentNumber > 500) {
            die("Limit von 500 IDs für das Objekt '{$objekt}' wurde bereits erreicht!");
        }
        $nextId = "video." . $objekt . "." . str_pad($currentNumber, 3, "0", STR_PAD_LEFT);
        $nextNumber = $currentNumber + 1;
        file_put_contents($counterFile, $nextNumber);
        return $nextId;
    }


    ### Private Helper-Methoden zur Reduzierung von Code-Duplikaten ###

    /**
     * Zentralisiert das Laden der Felder und das Mapping auf das VideoModel
     */
    private function buildVideoModel(int $ref, bool $create = false): ?VideoModel
    {
        $object_1_id = get_data_by_field($ref, $this->config['pressmatrix_video_object_1']);
        $object_2_id = get_data_by_field($ref, $this->config['pressmatrix_video_object_2']);

        if ($object_1_id == '') {
            return null;
        }
        $externalID = get_data_by_field($ref,$this->config['pressmatrix_video_external']);
        if($externalID == ''){
            print "Noch keine Externe ID";
            $externalID = $this->generateId(strtolower($object_1_id));
            print " Neue Externe ID " . $externalID . "<br>";

        }
        $categories = $this->getCategories($ref);



        $date_field_id = $this->config['pressmatrix_video_evt'];
        $mediakey_field_id = $this->config['pressmatrix_video_mediakey'];
        $free_field_id = $this->config['pressmatrix_video_free'];

        $date_val = get_data_by_field($ref, $date_field_id);
        $mediakey_val = get_data_by_field($ref, $mediakey_field_id);
        $free_val = get_data_by_field($ref, $free_field_id);

        $hlsurl = $this->config['pressmatrix_video_hlsurl'] . $mediakey_val . '.m3u8';

        $video = new VideoModel($this->config);
        $video->setEvt(new DateTime(get_data_by_field($ref, $this->config['pressmatrix_video_evt'])));
        $video->setGuid($ref);
        $video->setCategories($categories);
        $video->setObject(strtolower($object_1_id));
        $video->setDuration($this->config['pressmatrix_video_duration']);
        $video->setTitle(get_data_by_field($ref, $this->config['pressmatrix_video_title']) ?: "Resource " . $ref);
        $video->setDescription(get_data_by_field($ref, $this->config['pressmatrix_video_description']));
        $video->setLink("https://paulparey.de/?r=" . $ref);
        $video->setFree($free_val);
        $video->setExternalId($externalID);
        $video->setApple($externalID);
        $video->setGoogle($externalID);
        if ($free_val !== 'frei') {
            $video->setPrice($this->config['pressmatrix_video_price']);

        }

        $video->setEvt(new DateTime($date_val));
        $video->setHls($hlsurl);

        return $video;
    }

    private function getCategories(int $ref)
    {

        $evt = get_data_by_field($ref,$this->config['pressmatrix_video_evt']);
        $year = explode("-",$evt)[0];

        $object_1_id = get_data_by_field($ref, $this->config['pressmatrix_video_object_1']);
        $object_2_id = get_data_by_field($ref, $this->config['pressmatrix_video_object_2']);

        $mainCats = [
            'DJZ' => $this->config['pressmatrix_article_djz_main'],
            'WUH' => $this->config['pressmatrix_article_wuh_main'],
            'JWW' => $this->config['pressmatrix_article_jww_main'],
            'FUF' => $this->config['pressmatrix_article_fuf_main'],
            'RF' => $this->config['pressmatrix_article_rf_main'],
            'NOR' => $this->config['pressmatrix_article_nor_main']

        ];


        if ($object_1_id != '') {
            $cats[] = intval($mainCats[$object_1_id]);
            $cats[] = intval($this->config['pressmatrix_article_' . strtolower($object_1_id) . "_" . $year]);

        }
        if ($object_2_id != '') {
            $cats[] = intval($mainCats[$object_2_id]);
            $cats[] = intval($this->config['pressmatrix_article_' . strtolower($object_1_id) . "_" . $year]);

        }
        return $cats;
    }

    /**
     * Generiert die dynamischen Endpoint-URLs für die API
     */
    private function getEndpointUrl(string $story_id = '', string $action = ''): string
    {
        $base = sprintf(
            "%s/api/v2/importer/organizations/%s/publications/%s/stories",
            $this->url,
            $this->organization,
            $this->publication
        );

        if (!empty($story_id)) {
            $base .= '/' . $story_id;
        }

        if (!empty($action)) {
            $base .= $action;
        }

        return $base;
    }

    /**
     * Führt den cURL-Request zentralisiert aus
     */
    private function sendRequest(string $endpoint, string $method, $payload, array $additionalHeaders = []): ?string
    {
        $ch = curl_init($endpoint);

        $defaultHeaders = [
            'Accept: application/json',
            'Authorization: Token ' . $this->token
        ];

        $headers = array_merge($defaultHeaders, $additionalHeaders);

        // Standard-Optionen
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);


        // HTTP-Methode & Payload Handling
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        print "<b>StatusCode: </b>" . $httpCode . "<br>";

        if (curl_errno($ch)) {
            $errorMessage = curl_error($ch);
            $errorCode = curl_errno($ch);
            print "<b>Fehlermeldung: </b>" . $errorMessage . "<br>";
            print "<b>Fehlercode: </b>" . $errorCode . "<br>";
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $response;
        }

        return null;
    }
}