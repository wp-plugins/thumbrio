<?php
/*
Plugin Name: Thumbr.io
Plugin URL: http://thumbr.io
Description: Serve your images responsively through <a href="https://www.thumbr.io/">Thumbr.io</a>. Combine remote storage using <strong>Amazon S3</strong> (optional), responsive resizing of images, and super fast serving of images through a <strong>CDN</strong>.
Version: 2.2
Author: Thumbr.io
Author URI: https://www.thumbr.io/
*/

define("THUMBRIO_BACKEND",         "http://cdn.api.thumbr.io");
define("THUMBRIO_FRONTEND",        "http://www.thumbr.io");
define("THUMBRIO_FRONTEND_SECURE", "https://www.thumbr.io");

require_once (dirname(__FILE__) . '/admin/index.php');
require_once (dirname(__FILE__) . '/backend/backend.php');
require_once (dirname(__FILE__) . '/admin/class-thumbrio.php');

// OPTIONS
define("OPTION_SUBDOMAIN",     "thumbrio-subdomain");
define("OPTION_EMAIL",         "thumbrio-email");
define("OPTION_WEBDIR",        "thumbrio-webdir");
define("OPTION_PRIVATE_KEY",   "thumbrio-private-key");
define("OPTION_PUBLIC_KEY",    "thumbrio-public-key");
define("OPTION_AMAZON_PRKEY",  "thumbrio-amazon-prk");
define("OPTION_AMAZON_PUKEY",  "thumbrio-amazon-puk");
define("OPTION_AMAZON_REGION", "thumbrio-amazon-region");

// Javascripts and styles
define("LOADING_GIF",     plugins_url('static/img/loading.gif', __FILE__));
define("ATTENTION_PNG",   plugins_url('static/img/attention.png', __FILE__));
define("SYNCHRONIZE_PNG", THUMBRIO_FRONTEND . '/static/img/cloud-thumbr.png');

define("THUMBRIO_WORDPRESS_CSS", plugins_url('static/css/thumbrio.wordpress.css', __FILE__));
define("THUMBRIO_WORDPRESS_JS",  plugins_url('static/js/thumbrio.wordpress.js', __FILE__));
define("THUMBRIO_EDITION_JS",    plugins_url('static/js/thumbrio.edition.js', __FILE__));
define("THUMBRIO_RESPONSIVE_JS", plugins_url('static/js/thumbrio.responsive.js', __FILE__));
define("THUMBRIO_SYNCHRO_JS",    plugins_url('static/js/thumbrio.synchronization.js', __FILE__));
define("THUMBRIO_ORIGIN_JS",     plugins_url('static/js/thumbrio.origin.js', __FILE__));
define("THUMBRIO_UPLOAD_JS",     plugins_url('static/js/thumbrio.upload.js', __FILE__));
define("THUMBRIO_CUSTOMIZE_JS",  plugins_url('static/js/thumbrio.customize.js', __FILE__));
// OTHERS
define("THUMBRIO_CSS_CLASS", "thumbrio-responsive");
define("QA_ADMIN_EMAIL",     "adminemail");
define("QA_ADMIN_PASSWORD",  "adminpass");

if (is_admin()) {
    add_action('admin_enqueue_scripts', 'thumbrio_admin_enqueue_scripts');
    // Action that it will be triggered when the user deactive the plugin.
    register_deactivation_hook(__FILE__, 'deactivate_plugin');

    // Define the options
    add_action('admin_init', 'thumbrio_admin_init');

    // Insert the option thumbrio in settings menu
    add_action('admin_menu', 'thumbrio_menu');

    // Add in the head part of the html
    add_action('admin_head', 'thumbrio_admin_head');

    // Control a form
    add_action('admin_post_sign',         'thumbrio_admin_post_sign');
    add_action('admin_post_subtype',      'thumbrio_admin_post_subtype');
    add_action('admin_post_amazon',       'thumbrio_admin_post_amazon');
    add_action('admin_post_thumbrio',     'thumbrio_admin_post_thumbrio');
    add_action('admin_post_get_thumbrio', 'thumbrio_admin_post_get_thumbrio');
    add_action('admin_post_activate',     'thumbrio_admin_post_activate');
    //add_action('admin_post_sync',         'thumbrio_admin_post_sync');
    add_action('admin_post_get_origin',   'thumbrio_admin_post_get_origin');
    add_action('admin_post_clean_config', 'thumbrio_admin_post_clean_config');
    add_action('admin_post_upload_image', 'thumbrio_admin_post_upload_image');
    add_action('admin_post_header_url',   'thumbrio_admin_post_header_url');

    // Insert Settings and About links in the plugins page
    add_filter('plugin_action_links', 'thumbrio_plugin_action_links');

} else {
    add_action('wp_enqueue_scripts', 'thumbrio_wp_enqueue_scripts');
    add_action('wp_footer',          'thumbrio_wp_footer');
    add_action('wp_head',            'thumbrio_wp_head');
}

$upload_dir = wp_upload_dir();
if ( get_option(OPTION_WEBDIR) !== $upload_dir['baseurl'] )
     require_once (dirname(__FILE__) . '/admin/thumbrio-plus.php');

/**
 * **************************
 * REMOVE VARIABLES
 * **************************
*/
function deactivate_plugin() {
    delete_option(OPTION_SUBDOMAIN);
    delete_option(OPTION_EMAIL);
    delete_option(OPTION_WEBDIR);
    delete_option(OPTION_PRIVATE_KEY);
    delete_option(OPTION_PUBLIC_KEY);
    delete_option(OPTION_AMAZON_PRKEY);
    delete_option(OPTION_AMAZON_PUKEY);
    delete_option(OPTION_AMAZON_REGION);
}

/**
 * **************************
 * INIT VARIABLES
 * **************************
*/
function thumbrio_admin_init() {
    register_setting('thumbrio-group', OPTION_SUBDOMAIN);
    register_setting('thumbrio-group', OPTION_EMAIL);
    register_setting('thumbrio-group', OPTION_WEBDIR);
    register_setting('thumbrio-group', OPTION_PRIVATE_KEY);
    register_setting('thumbrio-group', OPTION_PUBLIC_KEY);
    register_setting('thumbrio-group', OPTION_AMAZON_PRKEY);
    register_setting('thumbrio-group', OPTION_AMAZON_PUKEY);
    register_setting('thumbrio-group', OPTION_AMAZON_REGION);
}
