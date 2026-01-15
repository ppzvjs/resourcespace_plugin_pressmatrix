<?php


include '../../../include/db.php';


if(!array_key_exists('name',$_GET)){
    return 'parameter name missing';
}

$feedname = $_GET['name'];
// Get the Node ID for "Ja" in Field 120
$ja_node = get_node_id("Ja", 120);
// Get the Node ID for "DJZ" in Field 108
$djz_node = get_node_id(strtoupper($feedname), 108);

$search_query = "@@" . $ja_node . " @@" . $djz_node . " !field130:*";
$resource_types = "3"; // Photography only
$order_by = "resourceid";
$archive_state = 0; // Only active resources
$limit = 5;
$sort_direction = "DESC";

// Execute the search
$results = do_search($search_query, $resource_types, $order_by, $archive_state, $limit, $sort_direction);

// Check if we have results
if (is_array($results) && count($results) > 0) {
    foreach ($results as $resource) {
        echo "Found Resource ID: " . $resource['ref'] . "<br>";
        echo "Title: " . $resource['field8'] . "<br>"; // field8 is usually Title
    }
} else {
    echo "No resources found matching the criteria.";
}
