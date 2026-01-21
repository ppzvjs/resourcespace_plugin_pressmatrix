<?php
use model\FeedModel;
use model\VideoModel;

include '../../../include/db.php';
include_once '../../../include/search_functions.php';
include_once '../../../include/node_functions.php';

// Assuming the model files are in the same directory or you have an autoloader
require_once '../model/FeedModel.php';
require_once '../model/VideoModel.php';

if(!array_key_exists('name', $_GET)){
    die('parameter name missing');
}

$config = get_plugin_config('pressmatrix');
$feedname = $_GET['name'];
$today_ts = strtotime(date('Y-m-d 23:59:59')); // End of today

// 1. Setup Feed Metadata
$feed = new FeedModel();
$feed->setTitle("Pressmatrix Feed: " . strtoupper($feedname));
$feed->setLink("https://paulparey.de");
$feed->setFeedlink((isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

$feed->setBuildDate(new \DateTime());
$feed->setImageUrl("https://www.paulparey.de/wp-content/uploads/2018/07/header-logo.jpg"); // Set your logo
$feed->setImageTitle("Paul Parey");
$feed->setImageWidth(144);
$feed->setImageHeight(144);

// 2. Fetch Resources
$active_node = get_node_id("ja", $config['pressmatrix_video_active']);
$object_node = get_node_id(strtoupper($feedname), $config['pressmatrix_video_object']);

// Fetch more than 50 to ensure we have enough after filtering
$results = do_search("@@$active_node @@$object_node", "3", "resourceid", 0, intval($config['pressmatrix_video_articles']), "DESC");

$feed->setDescription("Video feed for " . $feedname . ' count ' . count($results));
$valid_items = [];
$date_field_id = $config['pressmatrix_video_evt'];
$ready_field_id = $config['pressmatrix_video_ready'];
$mediakey_field_id = $config['pressmatrix_video_mediakey'];
$free_field_id = $config['pressmatrix_video_free'];

if (is_array($results)) {
    foreach ($results as $resource) {
        $ref = is_array($resource) ? intval($resource['ref']) : intval($resource);

        if ($ref <= 0) continue;

        // Filter A: Ready Check
        $ready_val = get_data_by_field($ref, $ready_field_id);
        if (trim($ready_val) === "") continue;


        // Filter B: Date Check
        $date_val = get_data_by_field($ref, $date_field_id);
        if (!$date_val) continue;

        $mediakey_val = get_data_by_field($ref,$mediakey_field_id);
        if(!$mediakey_val) continue;

        $free_val = get_data_by_field($ref,$free_field_id);



        $resource_ts = strtotime($date_val);

        if ($resource_ts > $today_ts) continue;

        $img_url = get_resource_path($ref, true, 'pre', false);

        //"hls": "https://hls-master.video.pareygo.de/566eab426f3ab82e742ba996e6f5718d.m3u8",
        $hlsurl = $config['pressmatrix_video_hlsurl'] . get_data_by_field($ref,$mediakey_field_id) . '.m3u8';

        // 3. Map Resource to VideoModel
        $video = new VideoModel();
        $video->setGuid($ref);
        $video->setTitle(get_data_by_field($resource['ref'], $config['pressmatrix_video_title']) ?: "Resource " . $resource['ref']);
        $video->setDescription(get_data_by_field($resource['ref'], $config['pressmatrix_video_description']));
        $video->setLink("https://paulparey.de/?r=" . $resource['ref']);

        if($free_val != 'frei'){
            //$video->setPrice($config['pressmatrix_video_price']);
            //$video->setExternalId(strtolower($feedname). '.video.' . $ref);

        }
        // Convert stored string to DateTime object
        $video->setEvt(new \DateTime($date_val));

        // Image & HLS (Using your config mapping)
        global $baseurl;
        $urls = explode('/filestore',$img_url);
        $img_url = $baseurl . '/filestore' . $urls[1];
        $video->setImage($img_url);
        $video->setHls($hlsurl);

        $valid_items[] = $video;
        if (count($valid_items) >= 50) break;
    }
}

// 4. Sort Valid Items by Date DESC
usort($valid_items, function($a, $b) {
    return $b->getEvt()->getTimestamp() <=> $a->getEvt()->getTimestamp();
});

// 5. Build and Output Feed
foreach ($valid_items as $item) {
    $feed->addItem($item);
}

header('Content-Type: application/rss+xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');
echo $feed->getFeed();