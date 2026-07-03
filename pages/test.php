<?php

use services\PressmatrixService;

include '../../../include/db.php';
include_once '../../../include/search_functions.php';
include_once '../../../include/node_functions.php';

// Assuming the model files are in the same directory or you have an autoloader
require_once '../services/PressmatrixService.php';


/*
 *
 * curl --location 'https://pegasus.pressmatrix.com/api/v2/importer/organizations/{organization_id}/publications/{publication_id}/stories?search=Example&page=1&per=20' \
  --header 'Accept: application/json' \
  --header 'Authorization: Token {TOKEN_GENERATED_BY_PMX}'
 *
 *
 *
 */


$pressmatrixService = new PressmatrixService();
$ref = 80;
$pressmatrixService->create($ref);
