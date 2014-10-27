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
define("THUMBRIO_CSS_CLASS", "thumbrio-responsive");

// Javascripts and styles
define("LOADING_GIF", plugins_url('static/img/loading.gif', __FILE__));
define("THUMBRIO_WORDPRESS_CSS", plugins_url('static/css/thumbrio.wordpress.css', __FILE__));
define("THUMBRIO_WORDPRESS_JS", plugins_url('static/js/thumbrio.wordpress.js', __FILE__));
define("THUMBRIO_RESPONSIVE_JS", plugins_url('static/js/thumbrio.responsive.js', __FILE__));
define("THUMBRIO_HMAC_MD5_JS", plugins_url('static/js/hmac_md5.js', __FILE__));

if (is_admin()) {
    add_action('admin_init', 'thumbrio_admin_init');
    add_action('admin_menu', 'thumbrio_menu');
    add_action('admin_head', 'thumbrio_admin_head');
    add_action('admin_post_update', 'thumbrio_admin_post_update');
    //add_action('wp_get_attachment_url', 'thumbrio_wp_get_attachment_url');
    add_filter('plugin_action_links', 'thumbrio_plugin_action_links');
} else {
    add_action('wp_footer', 'thumbrio_wp_footer');
    add_action('wp_head', 'thumbrio_wp_head');
}

function thumbrio_wp_head() {
    wp_register_script('thumbrio-view', THUMBRIO_RESPONSIVE_JS);
    wp_enqueue_script('thumbrio-view');
    buffer_start();
}

function thumbrio_admin_head() {
    remove_submenu_page( 'index.php', 'thumbrio_about');
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


function src_to_data_src_th($html) {
    $webdir = get_webdir();
    $thurl = get_option('thumbrio_subdomain');
    if (strpos($html, "src=\"" . $webdir)) {
        error_log("\n\n1>>>$thurl, $webdir\n");
        $html = str_replace('src="'. $webdir . '/', 'src="' . LOADING_GIF . '" data-src="' . $thurl, $html);
        $html = preg_replace('/<img([^>]+)class="/', '<img\1class="' . THUMBRIO_CSS_CLASS . ' ', $html);
    };
    return $html;
}


function buffer_start() {
  ob_start("src_to_data_src_th");
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
function thumbrio_plugin_action_links($links) {
    $tag = 'plugin=wp-thumbrio-plugin';
    $new_links = array();
    if (strpos($links['activate'], $tag) > 0 or strpos($links['deactivate'], $tag) > 0) {
        $new_links['settings'] = (
            '<a href="' . add_query_arg(array('page' => 'thumbrio'), admin_url('options-general.php')) .
            '">' . esc_html__('Settings', 'thumbrio') . '</a>'
        );
        $new_links['about'] = ('<a href="' . add_query_arg(array('page' => 'thumbrio_about'), admin_url('index.php')) .
            '">' . esc_html__('About', 'thumbrio_about') . '</a>');
    }
    return array_merge($links, $new_links);
}

function thumbrio_menu() {
    $page = add_options_page('Thumbr.io Keys', 'Thumbr.io', 'manage_options', 'thumbrio', 'thumbrio_options');
    add_action("load-$page", 'add_help_tabs');
    add_dashboard_page('Thumbrio about', null, 'read', "thumbrio_about", 'thumbrio_about');
}

function thumbrio_about() {
    wp_enqueue_style('thumbrio-wordpress', THUMBRIO_WORDPRESS_CSS);
?>
    <div class="logo-thumbrio">
        <a href="http://wwww.thumbr.io">
            <img src="http://www.thumbr.io/img/thumbrio-white.svg" />
        </a>
    </div>
    <div class="th-container">
        <h1><?php esc_html_e('Welcome', 'thumbrio'); ?></h1>
        <div>
            <h3><?php esc_html_e('General Description', 'thumbrio'); ?></h3>
            <p>Provide that you have a <a href="http://wwww.thumbr.io">thumbr.io</a> account you can serve your images through it. Using the
            thumbr.io technology permets you to improve the responsiveness of your pages. Thumbrio handles your images to decrease your bandwidth consumption. 
            It serves the optimal images that guarantee the best visual experience at minimum image size.</p>

            <h3><?php esc_html_e('Under Paid Plan', 'thumbrio'); ?></h3>
            <p><?php esc_html_e('You deliver your images and static content through a dedicated CDN', 'thumbrio'); ?>
                implying a lower latency and, of course,  a better user experience.</p> 
        </div>        
        <div>
            <a href="/wp-admin/options-general.php?page=thumbrio">Go to Settings</a>
        </div>
    </div>

<?php
}

function add_help_tabs() {
    $screen = get_current_screen();
    $tabs = array(
        array(
            'title'    => 'Overview',
            'id'       => 'thumbrio-overview',
            'callback'  => 'help_setting_overview'
        ),
        array(
            'title'    => 'Subdomain',
            'id'       => 'thumbrio-subdomain',
            'callback' => 'help_setting_subdomain'
        ),
        array(
            'title'    => 'Signing up',
            'id'       => 'thumbrio-signup',
            'callback' => 'help_setting_signup'
        )

    );
    foreach($tabs as $tab) {
        $screen->add_help_tab($tab);
    }
    $screen->set_help_sidebar('Powered by <a href="http://www.thumbr.io">thumbr.io</a>');
}

function help_setting_overview() { 
?>
    <p>Here you set the plugin up to integrate your site with the Thumbr.io service.</p>
    <p>All information required here come from your account's settings in Thumbrio.io.</p>
<?php
}


function help_setting_subdomain() { 
?>
    <p>You have to define a thumbr.io subdomain in your account fetching to your wordpress image folder. 
    Then,  you must fill out the corresponding field in this page's form.</p>
<?php
}

function help_setting_signup() {
?>
    <p>The regular signup process in Thumbr.io have been simplified for people using this plugin. 
       It take about 3 min to be completed.</p>
<?php
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
        <div class="logo-thumbrio">
            <a href="http://wwww.thumbr.io">
                <img src="http://www.thumbr.io/img/thumbrio-white.svg"/>
            </a>
        </div>
        <form method="post" action="admin-post.php" target="hiddeniframe">
            <?php
                settings_fields('thumbrio-group');
            ?>
            <div class="th-container">
                <p>Please fill out the following information from your thumbr.io settings.</p>
                <div class="th-row">
                    <div class="th-row-form">
                        <div class="th-label-field">Subdomain</div>
                        <div class="th-input-field">
                            <div><span > http://</span></div><div><input id="subdomain-input" type="text" 
                            name="thumbrio_subdomain" 
                            value="<?php echo show_subdomain_in_settings(get_option('thumbrio_subdomain')); ?>" /></div><div><span> .thumbr.io</span></div>
                        </div>
                    </div>
                </div>
                <div class="th-row">
                    <?php
                        $webdir = get_webdir();
                        echo "<input type=\"hidden\" name=\"thumbrio_webdir\" value=\"$webdir\" />";
                        submit_button('Acept');
                    ?>
                </div>
                <hr>
                <p><span class="remark">Important:</span> In order to use this plugin you must have an account in 
                    <a href="http://wwww.thumbr.io">thumbr.io</a>. If you are not a thumbrio's user, please 
                    <a href="https://www.thumbr.io/signup">sign up</a> and follow the instructions.
                </p>
            </div>    
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
