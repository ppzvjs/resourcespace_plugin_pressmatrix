<?php

include '../../../include/db.php';


include "../../../include/authenticate.php"; if (!checkperm("a")) {exit ("Permission denied.");}
$plugin_page_heading = $lang['pressmatrix_configuration'];
$plugin_name = 'pressmatrix';
if(!in_array($plugin_name, $plugins))
{plugin_activate_for_setup($plugin_name);}

$metafield_options = [];
$metafield_options[''] = '-- Select a metadata field --';

$fields = ps_query("
    SELECT ref, title
      FROM resource_type_field
     WHERE active = 1
  ORDER BY title
");

foreach ($fields as $field)
{
    $metafield_options[$field['ref']] =
        i18n_get_translated($field['title']) . " (ID {$field['ref']})";
}

$page_def = [];




$page_def[] = config_add_section_header($lang['pressmatrix_header_baseconfig'],$lang['pressmatrix_header_baseconfig_description']);
$page_def[] = config_add_single_select(
    'pressmatrix_video_title',
    $lang['pressmatrix_video_title'],
    $metafield_options,
    true // required
);
$page_def[] = config_add_single_select(
    'pressmatrix_video_description',
    $lang['pressmatrix_video_description'],
    $metafield_options,
    true // required
);
$page_def[] = config_add_single_select(
    'pressmatrix_video_file',
    $lang['pressmatrix_video_file'],
    $metafield_options,
    true // required
);
$page_def[] = config_add_single_select(
    'pressmatrix_video_evt',
    $lang['pressmatrix_video_evt'],
    $metafield_options,
    true // required
);
$page_def[] = config_add_single_select(
    'pressmatrix_video_duration',
    $lang['pressmatrix_video_duration'],
    $metafield_options,
    true // required
);
$page_def[] = config_add_single_select(
    'pressmatrix_video_object',
    $lang['pressmatrix_video_object'],
    $metafield_options,
    true // required
);
$page_def[] = config_add_single_select(
    'pressmatrix_video_active',
    $lang['pressmatrix_video_active'],
    $metafield_options,
    true // required
);
$page_def[] = config_add_single_select(
    'pressmatrix_video_ready',
    $lang['pressmatrix_video_ready'],
    $metafield_options,
    true // required
);
$page_def[] = config_add_single_select(
    'pressmatrix_video_mediakey',
    $lang['pressmatrix_video_mediakey'],
    $metafield_options,
    true // required
);
$page_def[] = config_add_text_input(
    'pressmatrix_video_hlsurl',
    $lang['pressmatrix_video_hlsurl']
);


// Do the page generation ritual -- don't change this section.
config_gen_setup_post($page_def, $plugin_name);
include '../../../include/header.php';
config_gen_setup_html($page_def, $plugin_name, null, $plugin_page_heading);

include '../../../include/footer.php';