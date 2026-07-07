<?php

namespace services;

require_once $_SERVER['DOCUMENT_ROOT'] .'/plugins/pressmatrix/model/VideoModel.php';

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
        $this->url = $this->config['pressmatrix_api_url'];
        $this->organization = $this->config['pressmatrix_api_organization'];
        $this->publication = $this->config['pressmatrix_api_publication'];
        $this->token = $this->config['pressmatrix_api_token'];
        $this->filestore = $this->config['pressmatrix_api_filestore'];
    }

    /**
     * Erstellt eine neue Story (Multipart/Form-Data inkl. Bild-Upload)
     */
    public function create(int $ref): ?string
    {
        global $baseurl;

        // VideoModel bauen & Daten holen
        $video = $this->buildVideoModel($ref);
        if($video === null){
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
            $flatten = function($data, $prefix = 'story') use (&$flatten, &$postFields) {
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

        $endpoint = $this->getEndpointUrl();
        $response = $this->sendRequest($endpoint, 'POST', $postFields);

        if ($response) {
            $responseData = json_decode($response, true);
            if (isset($responseData['story']['id'])) {
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
        if($video === null){
            return false;
        }
        $modelData = $video->getPressmatrix();

        $payload = ['story' => []];
        if (isset($modelData['story']) && is_array($modelData['story'])) {
            foreach ($modelData['story'] as $key => $value) {
                $payload['story'][$key] = $value;
            }
        }

        $endpoint = $this->getEndpointUrl($story_id);
        $headers = ['Content-Type: application/json'];

        $response = $this->sendRequest($endpoint, 'PATCH', json_encode($payload), $headers);

        return $response !== null;
    }

    /**
     * Veröffentlicht eine Story
     */
    public function publish(string $story_id): bool
    {
        $endpoint = $this->getEndpointUrl($story_id, '/publish');
        $response = $this->sendRequest($endpoint, 'POST', '');

        return $response !== null;
    }

    ### Private Helper-Methoden zur Reduzierung von Code-Duplikaten ###

    /**
     * Zentralisiert das Laden der Felder und das Mapping auf das VideoModel
     */
    private function buildVideoModel(int $ref): ?VideoModel
    {
        $object_1_id = get_data_by_field($ref,$this->config['pressmatrix_video_object_1']);
        $object_2_id = get_data_by_field($ref,$this->config['pressmatrix_video_object_2']);

        if($object_1_id == ''){
            return null;
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
        $video->setEvt(new DateTime(get_data_by_field($ref,$this->config['pressmatrix_video_evt'])));
        $video->setGuid($ref);
        $video->setCategories($categories);
        $video->setObject('FUF');
        $video->setDuration($this->config['pressmatrix_video_duration']);
        $video->setTitle(get_data_by_field($ref, $this->config['pressmatrix_video_title']) ?: "Resource " . $ref);
        $video->setDescription(get_data_by_field($ref, $this->config['pressmatrix_video_description']));
        $video->setLink("https://paulparey.de/?r=" . $ref);

        if ($free_val !== 'frei') {
            $video->setPrice($this->config['pressmatrix_video_price']);
            $video->setExternalId(strtolower($object_1_id) . '.video.' . $ref);
        }

        $video->setEvt(new DateTime($date_val));
        $video->setHls($hlsurl);

        return $video;
    }

    private function getCategories(int $ref){
        $object_1_id = get_data_by_field($ref,$this->config['pressmatrix_video_object_1']);
        $object_2_id = get_data_by_field($ref,$this->config['pressmatrix_video_object_2']);

        $mainCats = [
            'DJZ' => $this->config['pressmatrix_article_djz_main'],
            'WUH' => $this->config['pressmatrix_article_wuh_main'],
            'JWW' => $this->config['pressmatrix_article_jww_main'],
            'FUF' => $this->config['pressmatrix_article_fuf_main'],
            'RF' => $this->config['pressmatrix_article_rf_main'],
            'NOR' => $this->config['pressmatrix_article_nor_main']

        ];


        if($object_1_id != ''){
            $cats[] = $mainCats[$object_1_id];
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

        if (curl_errno($ch)) {
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