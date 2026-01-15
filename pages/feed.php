<?php
include '../../../include/db.php';
include_once '../../../include/search_functions.php';
include_once '../../../include/node_functions.php';

if(!array_key_exists('name', $_GET)){
    die('parameter name missing');
}

$config = get_plugin_config('pressmatrix');
$feedname = $_GET['name'];
$today_ts = strtotime(date('Y-m-d'));

// 1. Get Node IDs
$active_node = get_node_id("ja", $config['pressmatrix_video_active']);
$object_node = get_node_id(strtoupper($feedname), $config['pressmatrix_video_object']);

// 2. Initial Search
// We fetch a larger amount (e.g., 200) to make sure we find 50 that pass our filters
$search_query = "@@" . $active_node . " @@" . $object_node;
$results = do_search($search_query, "3", "resourceid", 0, 200, "DESC");

$final_results = [];
$date_field_id = $config['pressmatrix_video_evt'];
$ready_field_id = $config['pressmatrix_video_ready'];

if (is_array($results)) {
    foreach ($results as $resource) {
        // A. Verify the 'Ready' field is not empty
        $ready_val = get_data_by_field($resource['ref'], $ready_field_id);
        if (trim($ready_val) === "") { continue; }

        // B. Get Date and Verify
        $date_val = get_data_by_field($resource['ref'], $date_field_id);
        if (!$date_val) { continue; }

        $resource_ts = strtotime($date_val);
        if ($resource_ts > $today_ts) { continue; }

        // Store the date inside the object so we can sort by it easily
        $resource['sort_date'] = $resource_ts;
        $resource['display_date'] = $date_val;

        $final_results[] = $resource;

        // Stop once we have 50 valid resources
        if (count($final_results) >= 50) {
            break;
        }
    }
}

// 3. Sort the final list by date DESC (Newest first)
usort($final_results, function($a, $b) {
    return $b['sort_date'] <=> $a['sort_date'];
});

// 4. Final Output
if (!empty($final_results)) {
    echo "<h2>Showing " . count($final_results) . " Resources (Sorted by Date DESC)</h2>";
    foreach ($final_results as $res) {
        echo "✅ Found Resource: " . $res['ref'] . "<br>";
        echo "Date: " . $res['display_date'] . "<br><hr>";
    }
} else {
    echo "No resources found matching the criteria.";
}