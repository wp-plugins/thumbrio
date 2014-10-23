<?php
/*
Plugin Name: Thumbrio Services
Plugin URL: http://thumbr.io
Description: A plugin to serve your wordpress images through thumbr.io
Version: 2.0
Author: Joaquin cuenca cuenca@thumbr.io and Thumbrio Development Team
*/

define("THUMBRIO_BACKEND", "http://cdn.api.thumbr.io");
define("THUMBRIO_FRONTEND", "http://www.thumbr.io");
define("THUMBRIO_FRONTEND_SECURE", "https://www.thumbr.io");
define("THUMBRIO_CHECK_SUBDOMAIN_INFO_URL", THUMBRIO_FRONTEND_SECURE . '/check/subdomain_info');

// Javascripts and styles
define("LOADING_GIF", plugins_url('static/img/loading.gif', __FILE__));
define("THUMBRIO_WORDPRESS_CSS", plugins_url('static/css/thumbrio.wordpress.css', __FILE__));
define("THUMBRIO_WORDPRESS_JS", plugins_url('static/js/thumbrio.wordpress.js', __FILE__));
define("THUMBRIO_RESPONSIVE_JS", plugins_url('static/js/thumbrio.responsive.js', __FILE__));
define("THUMBRIO_HMAC_MD5_JS", plugins_url('static/js/hmac_md5.js', __FILE__));

register_deactivation_hook(__FILE__, 'thumbrio_deactivate');
if (is_admin()) {
    add_action('admin_init', 'thumbrio_admin_init');
    add_action('admin_menu', 'thumbrio_menu');
    add_action('admin_post_update', 'thumbrio_admin_post_update');
    add_action('wp_get_attachment_url', 'thumbrio_wp_get_attachment_url');
    add_filter('plugin_action_links', 'thumbrio_plugin_action_links');
} else {
    add_action('wp_head', 'thumbrio_wp_head');
    add_action('wp_footer', 'thumbrio_wp_footer');
    function thumbrio_wp_head() {
        wp_register_script('thumbrio-view', THUMBRIO_RESPONSIVE_JS);
        wp_enqueue_script('thumbrio-view');
        buffer_start();
    }
}

function thumbrio_deactivate($network_id) {
    delete_option('thumbrio_subdomain');
}

/*
 ********************************************
 * Auxiliary functions
 ********************************************
*/
function get_webdir() {
    $upload = wp_upload_dir();
    return $upload['baseurl'];
}

/*
 * *******************************************
 * Substitute the 'src' attribute by a 'data-src'
 * *******************************************
*/
function src_to_data_src($html) {
    if (strpos($html, "src=\"" . get_option('thumbrio_subdomain')) !== false) {
        $html = preg_replace(
            array('/class="([^\"]*)"/',             '/src="([^"]+)"/'),
            array('class="\1 thumbrio-responsive"', 'data-src="\1" src="' . LOADING_GIF . '"'),
            $html
        );
    }
    return $html;
}

function buffer_start() {
  ob_start("src_to_data_src");
}

function thumbrio_wp_footer() {
  ob_end_flush();
}

/*
 * ******************************************************************
 * Remove the prefix http://domain/upload-path/ in the image src.
 * ******************************************************************
*/
function thumbrio_wp_get_attachment_url($url) {
    $webdir = get_webdir();
    if (strpos($url, $webdir) === 0) {
        $new_url = substr($url, strlen($webdir));
        if ($new_url[0] == '/') {
            $new_url = substr($new_url, 1);
        }
        $url = get_option('thumbrio_subdomain') . $new_url;
    }
    return $url;
}

/**
 * **************************
 * INIT VARIABLES
 * **************************
*/
function thumbrio_admin_init() {
    register_setting('thumbrio-group', 'thumbrio_subdomain');
}

/**
 * **********************
 * Settings Thumbr.io
 * **********************
 */
function thumbrio_plugin_action_links($links, $file) {
    $tag = 'plugin=wp-thumbrio-plugin';
    $new_links = array();
    if (strpos($links['activate'], $tag) > 0 or strpos($links['deactivate'], $tag) > 0) {
        $new_links['settings'] = (
            '<a href="' . add_query_arg(array('page' => 'thumbrio'), admin_url('options-general.php')) .
            '">' . esc_html__('Settings', 'thumbrio') . '</a>'
        );
    }
    return array_merge($links, $new_links);
}

function thumbrio_menu() {
    add_options_page('Thumbr.io Keys', 'Thumbr.io', 'manage_options', 'thumbrio', 'thumbrio_options');
}

function thumbrio_options() {
    wp_enqueue_script('cripto', THUMBRIO_HMAC_MD5_JS);
    wp_enqueue_script('storageInfo', THUMBRIO_WORDPRESS_JS);
    wp_enqueue_style('thumbrio-wordpress', THUMBRIO_WORDPRESS_CSS);
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
?>
    <div class="wrap">
        <form method="post" action="admin-post.php" target="hiddeniframe">
            <?php
                settings_fields('thumbrio-group');
            ?>
            <p class="logo-thumbrio">
                <a href="http://wwww.thumbr.io">
                    <img src="http://www.thumbr.io/img/thumbrio-white.svg" width="200" height="50" />
                </a>
            </p>
            <p>Are you a thumbr.io user?. If you are not, sign up <a href="https://www.thumbr.io/signup">here</a></p>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Subdomain:</th>
                    <td>
                        <span>http://</span>
                        <input id="subdomain-input" type="text" name="thumbrio_subdomain" value="<?php echo show_subdomain_in_settings(get_option('thumbrio_subdomain')); ?>" />
                        <span>.thumbr.io</span>
                    </td>
                    Go to <a href="https://www.thumbr.io/profile/hostname">thumbr.io hostnames</a> and create a
                    subdomain with a webfolder to your images folder.
                </tr>
            </table>
            <?php
                $webdir = get_webdir();
                echo "<input type=\"hidden\" name=\"thumbrio_webdir\" value=\"$webdir\" />";
                submit_button();
            ?>
        </form>
        <iframe name="hiddeniframe" id="hiddeniframe" style="display:none;"></iframe>
    </div>
    <script type="text/javascript">
        window.onload = function () {
            saveUserAndPassword(<?php echo "'" . THUMBRIO_CHECK_SUBDOMAIN_INFO_URL . "'"; ?>);
        };
    </script>
    <?php
}

function show_subdomain_in_settings($subdomain) {
    preg_match('/^http\:\/\/(.+)\.thumbr\.io\//', $subdomain, $result_preg);

    $result = $subdomain;
    if (count($result_preg) > 1) {
        $result = $result_preg[1];
    }
    return $result;
}

/**
* This function is executed when we push the button Acept in the option: "settings -> Thumbr.io".
*/
function thumbrio_admin_post_update($tabs) {
    function update_variables($dictionary, $vble, $fx=null) {
        if (array_key_exists($vble, $dictionary)) {
            $value = $dictionary[$vble];
            if ($fx) {
                $value = $fx($value);    
            }
            update_option($vble, $value);
        }
    }
    if (!array_key_exists('thumbrio_subdomain', $_REQUEST)) {
        wp_redirect("options-general.php?page=thumbrio&status=ko", 404);
        die();
    }
    update_variables($_REQUEST, 'thumbrio_subdomain', function($a) { return "http://$a.thumbr.io/"; });
    echo("Settings: ok;\n");
    die();
}
?>
