<?php

/*
 * ****************************************************
 * Add the links to Settings and About in plugins
 * ****************************************************
 */
function thumbrio_plugin_action_links($links) {
    $tag = 'plugin=wp-thumbrio-plugin';
    $new_links = array();
    $link_state = (isset($links['activate']))? 'activate' : 'deactivate';
    if (strpos($links[$link_state], $tag) > 0) {
        $new_links['settings'] = (
            '<a href="' . add_query_arg(array('page' => 'thumbrio'), admin_url('options-general.php')) .
            '">' . esc_html__('Settings', 'thumbrio') . '</a>'
        );
        $new_links['about'] = ('<a href="' . add_query_arg(array('page' => 'thumbrio_about'), admin_url('index.php')) .
            '">' . esc_html__('About', 'thumbrio_about') . '</a>');
    }
    return array_merge($links, $new_links);
}
/*
 * ****************************************************
 * Add the menu of Thumbr.io
 * ****************************************************
 */
function thumbrio_menu() {
    $page = add_options_page('Thumbr.io Menu', 'Thumbr.io', 'manage_options', 'thumbrio', 'thumbrio_options');
    add_action("load-$page", 'add_help_tabs');
    add_dashboard_page('Thumbr.io about', null, 'read', "thumbrio_about", 'thumbrio_about');
}

function thumbrio_admin_head() {
    remove_submenu_page('index.php', 'thumbrio_about');
}

/*
 * *********************************************
 * About page. General Description of the Plugin
 * *********************************************
 */
function thumbrio_about() {
    wp_enqueue_style('thumbrio-wordpress', THUMBRIO_WORDPRESS_CSS);
?>
    <div class="th-container">
        <h1>Thumbr.io plugin</h1>
        <div>
            <h3>General Description</h3>

            <p>Thumbr.io is a web application created to serve images optimally. Meaning, deliver
            images at the correct resolution and size for any device.
            This translates in a more efficient bandwidth consumption of your visitors.
            It is, particularly, important for them that access your website through slow connections.</p>

            <h4>Under Paid Plan</h4>
            <p> You benefit from delivering your images and static content through a dedicated worldwide CDN,
                a bigger bandwidth capacity and personalized image URLs.</p>

            <h4>New Thumbr.io's User</h4>
            <p>By using this plugin you accept the <a href="http://www.thumbr.io/tos"> Terms of Use</a>.</p>
        </div>
        <div>
            <a href="/wp-admin/options-general.php?page=thumbrio">Go to Settings</a>
        </div>
    </div>
<?php
}

/* FIXME: Put in class version
 * ***********************************
 * Define the menu help for every page
 * ***********************************s
 */
function add_help_tabs() {
    $screen = get_current_screen();

    $tabs = array(
        'overview' => array(
            'title'    => 'Overview',
            'id'       => 'overview',
            'content'  => help_setting('overview')
        ),
        'overview-signup' => array(
            'title'    => 'Overview',
            'id'       => 'overview-signup',
            'content'  => help_setting('overview_signup')
        ),
        'signup-email' => array(
            'title'    => 'Email',
            'id'       => 'signup-email',
            'content'  => help_setting('signup_email')
        ),
        'signup-password' => array(
            'title'    => 'Password',
            'id'       => 'signup-password',
            'content'  => help_setting('signup_password')
        ),
        'overview-validation' => array(
            'title'    => 'Validation',
            'id'       => 'overview-validation',
            'content'  => help_setting('overview_validation')
        ),
        'overview-info' => array(
            'title'    => 'Overview',
            'id'       => 'overview-info',
            'content'  => help_setting('overview_info')
        ),
        'thumbrio-signup' => array(
            'title'    => 'Sign Up',
            'id'       => 'thumbrio-signup',
            'content'  => help_setting('thumbrio_signup')
        ),
        'thumbrio-login' => array(
            'title'    => 'Log In',
            'id'       => 'thumbrio-login',
            'content'  => help_setting('thumbrio_login')
        ),
    );
}

/* TODO: write as an array
 * *******************************************
 * Help texts
 * *******************************************
 */
function help_setting($concept) {
    switch ($concept) {
        case 'overview':
            $message  = "<p>A thumbr.io account is required to use this plugin. If you have one follow the 'Log in' link
                            to configure the plugin, otherwise Sign Up. You will be registered in one step.
                            The plugin will be properly configured.</p>";
            break;
        case 'overview_signup':
            $message  = "<p>You must provide an email and a (strong) password to sign up Thumbr.io service. <p>";
            break;
        case 'signup_email':
            $message  = "<p>The email must not be registered in Thumbr.io.</p>";
            break;
        case 'signup_password':
            $message  = "<p>The password must be at least 8 characters in length.</p>";
            break;
        case 'overview_validation':
            $message  = "<p>Here you must validate the settings shown. This will update your configuration of the Thumbr.io
                            plugin to match your Thumbr.io Settings. <p>";
            break;
        case 'overview_info':
            $message  = "<p>Here we show you the configuration of your Thumbr.io plugin. As user of Thumbr.io you
                            could change your settings there. In such case you must update the  settings in Thumbr.io's
                            plugin pushing the button bellow. <p>";
            break;
        case 'thumbrio_login':
            $message  = "<p>If you have an account in Thumbr.io with a subdomain pointing to your local image folder,
                            follows the link in order to update your WordPress configuration. If you have an account in Thumbr.io
                            but you have not configured any subdomain pointing to the local image folder
                            a new subdomain name will be built on the fly. You can change this configuration later
                            sign in Thumbr.io. </p>";
            break;
        case 'thumbrio_signup':
            $message  = "<p>We create an account in Thumbr.io under the email that you will provide.
                            This is a free account that you could cancel at any time. </p>";
            break;
        default:
            $message = "";
            break;
    };
    return $message;
}

/*
 * **********************************
 *  Setting page handle
 * **********************************
 */
function thumbrio_options() {
    $thumbrio = new Thumbrio;
    $thumbrio->show_panel();
}

function thumbrio_admin_enqueue_scripts() {
    wp_register_script('thumbrio-wordpress-js', THUMBRIO_WORDPRESS_JS);
    wp_register_style('thumbrio-wordpress',     THUMBRIO_WORDPRESS_CSS);
    wp_enqueue_script('thumbrio-wordpress-js');
    wp_enqueue_style('thumbrio-wordpress');
    wp_register_script('thumbrio-custom', THUMBRIO_CUSTOMIZE_JS);
    wp_enqueue_script('thumbrio-custom');
}

/*
 * ***********************
 * Form Post Handlers
 * ***********************
 */
function thumbrio_admin_post_sign() {
    $thumbrio = new Thumbrio;
    $thumbrio->thumbrio_post_form_sign();
}

function thumbrio_admin_post_subtype() {
    $thumbrio = new Thumbrio;
    $thumbrio->thumbrio_post_form_type();
}

function thumbrio_admin_post_amazon() {
    $thumbrio = new Thumbrio;
    $thumbrio->thumbrio_post_form_amazon();
}

function thumbrio_admin_post_thumbrio() {
    $thumbrio = new Thumbrio;
    $thumbrio->thumbrio_post_list_thumbrio();
}

function thumbrio_admin_post_get_thumbrio() {
    $thumbrio = new Thumbrio;
    $thumbrio->thumbrio_post_get_thumbrio();
}

function thumbrio_admin_post_activate() {
    $thumbrio = new Thumbrio;
    $thumbrio->activation_subdomain($_REQUEST);
}

function thumbrio_admin_post_get_origin() {
    $thumbrio = new Thumbrio;
    $thumbrio->thumbrio_post_set_origin($_REQUEST);
}

function thumbrio_admin_post_clean_config() {
    $thumbrio = new Thumbrio;
    $thumbrio->thumbrio_clean_config();
}

function thumbrio_admin_post_upload_image() {
    if (!thumbrio_is_webdir_local()) {
        thumbrio_upload_new_image($_REQUEST);
    }
}

?>
