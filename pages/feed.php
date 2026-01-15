<?php


include '../../../include/db.php';

$feedname = array_key_exists('name',$_GET) ? $_GET['name'] : '';

$resources = get_resources();

$videoModel = new \model\VideoModel();

