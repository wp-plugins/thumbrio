<?php

require_once (dirname(__FILE__) . '/../upload/bucket.php');
require_once (dirname(__FILE__) . '/../upload/upload.php');

require_once (dirname(__FILE__) . '/../edit/edit.php');
require_once (dirname(__FILE__) . '/../edit/class-thumbrio-image-editor.php');
require_once (dirname(__FILE__) . '/../custom/custom.php');


if (is_admin()) {
    add_action('admin_post_sync',         'thumbrio_admin_post_sync');
    //add_action('admin_post_get_origin',   'thumbrio_admin_post_get_origin');
    add_action('admin_post_clean_config', 'thumbrio_admin_post_clean_config');
    add_action('admin_post_upload_image', 'thumbrio_admin_post_upload_image');
    add_action('admin_post_header_url',   'thumbrio_admin_post_header_url');

    // Change the url of a image
    add_action('wp_get_attachment_url',       'thumbrio_get_attachment_url', 10, 2);
    add_action('wp_get_attachment_thumb_url', 'thumbrio_get_attachment_url');

    // Insert the javascripts for upload
    add_action('admin_footer',     'thumbrio_upload_footer');
    //add_filter('wp_insert_attachment_data' , 'thumbrio_insert_attachment_data');

    // Upload
    add_action('wp_handle_upload',                  'thumbrio_handle_upload');
    add_filter('wp_generate_attachment_metadata',   'thumbrio_generate_attachment_metadata', 10, 2);
    add_filter('attachment_link',                   'thumbrio_attachment_link', 10, 2);
    add_filter('wp_get_attachment_image_attributes','thumbrio_get_attachment_image_attributes', 10, 3);

    // Delete a file
    add_action('delete_post', 'thumbrio_delete_post');

    // Custom header
    add_filter('wp_create_file_in_uploads', 'thumbrio_create_file_in_uploads', 10, 2);
    add_filter('file_is_displayable_image', 'thumbrio_file_is_displayable_image', 10, 2);
    add_action('wp_custom_header_crop',     'thumbrio_custom_header_crop');
    add_filter('wp_crop_image',             'thumbrio_crop_image');
    //add_action('wp_ajax_custom-header-crop', 'thumbrio_custom_header_crop');

    // Edit tool
    add_filter('_wp_relative_upload_path',      'thumbrio_relative_upload_path', 10, 2);
    add_filter('update_attached_file',          'thumbrio_update_attached_file', 10, 2);
    add_filter('wp_update_attachment_metadata', 'thumbrio_update_attachment_metadata', 10, 2);
    add_filter('wp_image_editors',              'thumbrio_image_editors');
    add_filter('image_editor_save_pre',         'thumbrio_editor_save_pre', 10, 2);
    add_filter('wp_image_editor_before_change', 'thumbrio_image_editor_before_change', 10, 2);
    add_filter('wp_save_image_editor_file',     'thumbrio_save_image_editor_file', 10, 5);
}


