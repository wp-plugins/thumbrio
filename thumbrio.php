<?php
/*
Plugin Name: Thumbr.io Services
Plugin URL: http://thumbr.io
Description: A plugin to serve your wordpress images through thumbr.io
Version: 2.01
Author: Joaquin cuenca cuenca@thumbr.io and Thumbr.io Development Team
*/
require_once (dirname(__FILE__) . '/admin/index.php');
require_once (dirname(__FILE__) . '/backend/index.php');


define("THUMBRIO_BACKEND", "http://cdn.api.thumbr.io");
define("THUMBRIO_FRONTEND", "http://www.thumbr.io");
define("THUMBRIO_FRONTEND_SECURE", "https://www.thumbr.io");
define("THUMBRIO_CREATE_ACCOUNT", THUMBRIO_FRONTEND_SECURE . '/wordpress/signup');
define("THUMBRIO_VALIDATE_SUBDOMAIN",  THUMBRIO_FRONTEND_SECURE . '/wordpress/landing_validate');

define("THUMBRIO_CSS_CLASS", "thumbrio-responsive");
define("OPTION_SUBDOMAIN", "thumbrio-subdomain");
define("OPTION_EMAIL", 'thumbrio-email');

define("OPTION_WEBDIR", "webdir");
define("OPTION_ADMIN_EMAIL", "adminemail");
define("OPTION_ADMIN_PASSWORD", "adminpass");

// Javascripts and styles
define("LOADING_GIF", plugins_url('static/img/loading.gif', __FILE__));
define("THUMBRIO_WORDPRESS_CSS", plugins_url('static/css/thumbrio.wordpress.css', __FILE__));
define("THUMBRIO_WORDPRESS_JS", plugins_url('static/js/thumbrio.wordpress.min.js', __FILE__));
define("THUMBRIO_RESPONSIVE_JS", plugins_url('static/js/thumbrio.responsive.min.js', __FILE__));


if (is_admin()) {
    register_deactivation_hook(__FILE__, 'deactivate_plugin');
    add_action('admin_init', 'thumbrio_admin_init');
    add_action('admin_menu', 'thumbrio_menu');
    add_action('admin_head', 'thumbrio_admin_head');
    add_action('admin_post_signup', 'thumbrio_admin_post_signup');
    add_action('admin_post_validate', 'thumbrio_admin_post_validate');
    add_filter('plugin_action_links', 'thumbrio_plugin_action_links');
} else {
    add_action('wp_footer', 'thumbrio_wp_footer');
    add_action('wp_head', 'thumbrio_wp_head');
}

function deactivate_plugin() {
    delete_option(OPTION_EMAIL);
    delete_option(OPTION_SUBDOMAIN);
}

/**
 * **************************
 * INIT VARIABLES
 * **************************
*/
function thumbrio_admin_init() {
    register_setting('thumbrio-group', OPTION_SUBDOMAIN);
    register_setting('thumbrio-group', OPTION_EMAIL);
}
