<?php

include '../../../include/db.php';


include "../../../include/authenticate.php"; if (!checkperm("a")) {exit ("Permission denied.");}
$plugin_page_heading = $lang['pressmatrix_configuration'];
$plugin_name = 'pressmatrix';
if(!in_array($plugin_name, $plugins))
{plugin_activate_for_setup($plugin_name);}

$page_def = [];


$page_def[] = config_add_section_header($lang['pressmatrix_header_baseconfig'],$lang['pressmatrix_header_baseconfig_description']);
$page_def[] = config_add_text_input(
    'pressmatrix_baseconfig_1080p',
    $lang['pressmatrix_baseconfig_1080p']
);

// Do the page generation ritual -- don't change this section.
config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, null, $plugin_page_heading);

include '../../../include/footer.php';