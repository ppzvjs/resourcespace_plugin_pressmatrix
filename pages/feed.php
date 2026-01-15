<?php
include '../../../include/db.php';
include_once '../../../include/search_functions.php';
include_once '../../../include/node_functions.php';

if(!array_key_exists('name', $_GET)){
    die('parameter name missing');
}

$config = get_plugin_config('pressmatrix');
$feedname = $_GET['name'];

$today_ts = strtotime(date('Y-m-d')); // Get today's timestamp at midnight

// 1. Get Node IDs for the Checkbox and Object
$active_node = get_node_id("Ja", $config['pressmatrix_video_active']);
$object_node = get_node_id(strtoupper($feedname), $config['pressmatrix_video_object']);

// 2. Search ONLY for the nodes (This part is 100% working)
$search_query = "@@" . $active_node . " @@" . $object_node;
$results = do_search($search_query, "3", "resourceid", 0, -1, "DESC");
global $last_search_sql; // This variable holds the final SQL query
echo "<strong>Generated SQL:</strong> " . htmlspecialchars($last_search_sql) . "<br>";
echo "<strong>PHP Now Variable:</strong> " . $today_ts . "<br>";
echo "<strong>Full Search Query:</strong> " . htmlspecialchars($search_query) . "<br>";

$final_results = [];

$date_field_id = $config['pressmatrix_video_evt'];
$ready_field_id = $config['pressmatrix_video_ready'];

if (is_array($results)) {
    foreach ($results as $resource) {
        // A. Verify the 'Ready' field is not empty
        $ready_val = get_data_by_field($resource['ref'], $ready_field_id);
        if (trim($ready_val) === "") {
            continue;
        }

        // B. Verify the 'Date' field is in the past
        $date_val = get_data_by_field($resource['ref'], $date_field_id);
        if (!$date_val) {
            continue; // Skip if no date is set
        }

        $resource_ts = strtotime($date_val);
        if ($resource_ts > $today_ts) {
            continue; // Skip if the date is in the future
        }

        // If it passed both checks, add it to our list
        $final_results[] = $resource;

        // Stop once we hit your limit
        if (count($final_results) >= 5) {
            break;
        }
    }
}

// 3. Final Output
if (!empty($final_results)) {
    foreach ($final_results as $res) {
        echo "✅ Found Resource: " . $res['ref'] . "<br>";
        echo "Date: " . get_data_by_field($res['ref'], $date_field_id) . "<br><hr>";
    }
} else {
    echo "No resources found that are active, have data, and a date in the past.";
}