<?php

/**
 * ****************************************************
 * Add the links to Settings and About in plugins
 * ****************************************************
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

/**
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
 * ***************************
 * Menu about
 * ***************************
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

/*
 * ***************************
 * Menu help
 * ***************************
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

    // Determine in which page we are. 
    $results = get_info_from_query_arguments($_SERVER['QUERY_STRING']);
    $current_url = get_option('siteurl') . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
    if ($results['subdomain'] and $results['email']) {
        //settings_login_warning;
        $tab_keys = array('overview-validation');
    } else if (!get_option(OPTION_SUBDOMAIN)) {
        if ($results['signup']) {
            //settings_signup
            $tab_keys = array('overview-signup');
        } else { 
            //settings_signup_or_login
            $tab_keys = array('overview', 'thumbrio-signup', 'thumbrio-login');
        }
    } else {
        //settings_info
        $tab_keys = array('overview-info');
    }

    foreach($tab_keys as $tab) {
        $screen->add_help_tab($tabs[$tab]);
    }
    $screen->set_help_sidebar('Powered by <a href="http://www.thumbr.io">thumbr.io</a>');
}

function help_setting($concept) { 
    switch ($concept) {
        case 'overview':
            $message  = "<p> Here you set up the plugin to integrate your site with the Thumbr.io service. 
                            If you have not a Thumbr.io account you must Sign Up, else Log in. <p>";
            break;
        case 'overview_signup':
            $message  = "<p> You must provide an email and a (strong) password to sign up Thumbr.io service. <p>";
            break;
        case 'overview_validation':
            $message  = "<p> Here you must validate the settings shown. This will update your configuration of the Thumbr.io 
                            plugin to match your Thumbr.io Settings. <p>";
            break;
        case 'overview_info':
            $message  = "<p> Here we show you the configuration of your Thumbr.io plugin. As user of Thumbr.io you 
                            could change your settings there. In such case you must update the  settings in Thumbr.io's  
                            plugin pushing the button bellow. <p>";
            break;
        case 'thumbrio_login':
            $message  = "<p> If you have an account in Thumbr.io with a subdomain pointing to your local image folder, 
                            follows the link in order to update your wordpress configuration. If you have an account in Thumbr.io
                            but you have not configured any subdomain pointing to the local image folder  
                            a new subdomain name will be built on the fly. You can change this configuration later  
                            sign in Thumbr.io. </p>";
            break;
        case 'thumbrio_signup':
            $message  = "<p> We create an account in Thumbr.io under the email that you will provide. 
                            This is a free account that you could cancel at any time. </p>";
            break;
        default:
            $message = "";
            break;
    };
    return $message;
}

/*
 * ***************************
 * Menu Admin
 * ***************************
*/

function print_info_wordpress($subdomain, $email) {
    if (!$subdomain) {
        $subdomain = (get_option(OPTION_SUBDOMAIN)? get_option(OPTION_SUBDOMAIN): 
            "You need to set your thumbr.io account up");
    }
?>
    <div class="th-row">
        <div class="th-row-form">
            <div class="th-label-field"> Image local folder  </div>
            <div class="th-input-field"> <?= htmlentities(get_webdir()); ?></div>
        </div>
        <div class="th-row-form">    
            <div class="th-label-field"> Thumbr.io subdomain </div>
            <div class="th-input-field"> <?= htmlentities("$subdomain"); ?>
            </div>
        </div>
        <div class="th-row-form">    
            <div class="th-label-field"> Thumbr.io login email </div>
            <div class="th-input-field"> <?= htmlentities($email?: get_option(OPTION_EMAIL)); ?>
            </div>
        </div>
    </div>
<?php }

function print_head_logo(){ ?>
    <div class="logo-thumbrio">
        <a href="http://wwww.thumbr.io">
            <img src="http://www.thumbr.io/img/thumbrio-gray.png"/>
        </a>
    </div>
<?php } 

function get_validate_url($current_url) {
    $username = (get_option(OPTION_EMAIL)?: get_option('admin_email'));
    $webdir = get_webdir();
    $wp_domain = $current_url;
    $qa = "username=" . urlencode($username) . "&webdir=" . urlencode($webdir) . "&wp_domain=" . urlencode($wp_domain);
    $url = THUMBRIO_VALIDATE_SUBDOMAIN . "?$qa";
    return $url;
}

function get_info_from_query_arguments($query_string) {
    preg_match('/subdomain=([^&]+)/', $query_string, $results_subdomain);
    preg_match('/email=([^&]+)/', $query_string, $results_email);
    preg_match('/signup=([^&]+)/', $query_string, $results_signup);
    preg_match('/error=([^&]+)/', $query_string, $results_error);

    $subdomain = null;
    $email = null;
    $signup = null;
    $error = null;
    if (count($results_subdomain) > 1) {
        $subdomain = urldecode($results_subdomain[1]);
    }
    if (count($results_email) > 1) {
        $email = urldecode($results_email[1]);
    }
    if (count($results_signup) > 1) {
        $signup = urldecode($results_signup[1]);
    }
    if (count($results_error) > 1) {
        $error = urldecode($results_error[1]);
    }
    return array('subdomain' => $subdomain, 'email' => $email, 'signup' => $signup, 'error' => $error);
}

function thumbrio_options() {
    wp_enqueue_script('cripto', THUMBRIO_HMAC_MD5_JS);
    wp_enqueue_script('storageInfo', THUMBRIO_WORDPRESS_JS);
    wp_enqueue_style('thumbrio-wordpress', THUMBRIO_WORDPRESS_CSS);
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $results = get_info_from_query_arguments($_SERVER['QUERY_STRING']);
    $current_url = get_option('siteurl') . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
    if ($results['subdomain'] and $results['email']) {
        print_settings_login_warning($results['subdomain'], $results['email']);
    } else if (!get_option(OPTION_SUBDOMAIN)) {
        if ($results['signup']) {
            print_settings_signup($current_url, $results['error']);
        } else { 
            print_settings_yes_or_no($current_url);
        }
    } else {
        print_settings_info($current_url);
    }
}

function print_settings_login_warning($subdomain, $email) {
?>  
    <div class="th-container">
        <h1>Validation of Thumbr.io settings<h1>
        <h3>Welcome  <?=htmlentities($email); ?></h3>
        <p>
        Your images in <strong><?=htmlentities(get_webdir()); ?></strong>
        will be served by Thumbr.io through <strong><?=htmlentities("http://$subdomain/");?></strong>.
        If you agree, save changes.
        </p>
        <form method="post" action="admin-post.php">
            <?php settings_fields('thumbrio-group');?>
            <input type="hidden" name="action" value="validate" />
            <input type="hidden" name="<?php echo OPTION_EMAIL; ?>" value="<?=htmlentities("$email"); ?>" />
            <input type="hidden" name="<?php echo OPTION_SUBDOMAIN; ?>" value="<?=htmlentities("$subdomain"); ?>" />
            <div class="th-row-form">
                <div class="th-label-field" style="margin-right:0;"> 
                    <?php submit_button ("Save", 'primary', 'submit-ok', false); ?></div>
                <div class="th-inter-button">or</div> 
                <div class="th-inter-button">
                <?php 
                $cancel_url = get_option('siteurl') . $_SERVER['PHP_SELF'] . '?page=thumbrio';
                echo "<a href='$cancel_url'>Discard</a>";
                ?>
                </div>
            
            </div>
        </form>
    </div>
<?php } 

function print_settings_signup($current_url, $error) {
?>
    <script type="text/javascript">
        window.onload = function () {
            var thFields = {
                webdir: {
                    name: 'webdir',
                },
                subdomain: {
                    name: 'subdomain',
                }, 
                email: {
                    name: 'adminemail', 
                },
            };
            controlAdmin (thFields);
        };
    </script>
    <div class="wrap">
        <?php
            if ($error) {
                echo "<div class='error'><p>$error</p></div>";
            }
        ?>
        <div class="th-container">
            <div id='th-configuration'>
                <h1>Sign Up in Thumbr.io</h1>
                <div class="font-small">
                    <p> You're signing up for a free account in <a href="https://www.thumbr.io">Thumbr.io</a>. You can serve up
                        to 1 GB / month. <a href="https://www.thumbr.io/profile/plans">Change your plan at any time</a> if you 
                        need more capacity.
                    </p> 
                </div>
                <div id="th-admin-loging">
                    <form id="signup-form" method="post" action="admin-post.php">
                        <?php settings_fields('thumbrio-group');?>
                        <input type="hidden" name="action" value="signup" />
                        <input type="hidden" name="webdir" value="<?php echo get_webdir(); ?>">
                        <div class="panel-option">
                            <div class="th-row">
                                <div class="th-row-form th-center-childs">
                                    <div class="th-label-field">Email</div>
                                    <div class="th-input-field">
                                        <div><input
                                            type="text"                                     
                                            name="adminemail"
                                            required="required"
                                            pattern="[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?"
                                            value="<?php echo (get_option(OPTION_EMAIL)?: get_option('admin_email')); ?>"
                                            size="35"/>
                                        </div>    
                                    </div>
                                </div>
                                <div class="th-row-form th-center-childs">
                                    <div class="th-label-field">Password</div>
                                    <div class="th-input-field">
                                        <div><input
                                            required="required"
                                            type="password"
                                            pattern=".{8,}"                                   
                                            name="adminpass" 
                                            value=""
                                            size="35"/>
                                        </div>    
                                    </div>
                                </div>
                                <div class="th-row-form th-center-childs">
                                    <div class="th-label-field">Repeat password</div>
                                    <div class="th-input-field">
                                        <div><input
                                            required="required"
                                            type="password"
                                            pattern=".{8,}"                                   
                                            name="adminpass-repeated" 
                                            value=""
                                            size="35"/>
                                        </div>    
                                    </div>
                                </div>

                                <div id="th-button-container">
                                <div class="th-row-form th-center-childs">
                                    <div class="th-label-field"></div>
                                    <div class="th-input-field">
                                        <div class="th-button-container">
                                        <?php submit_button ("Create", 'primary th-button', 'submit-new-user', false); ?>    
                                        </div>
                                        <div class="th-inter-button">or</div>
                                        <div class="th-button-container">
                                        <?php
                                           $back_url = add2url($current_url, null);
                                           echo "<a class=\"th-inter-button\" href=\"$back_url\">Cancel</a>";
                                        ?>
                                        </div> 
                                    </div>
                                </div>               
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php } 

function print_settings_yes_or_no($current_url) {
?>
    <div id="th-setup" class="th-container ">    
        <h1>Set up Thumbr.io plugin</h1>
        <p>This plugin makes the images in your blog responsive, adapting them to the 
            size and resolution of your users' devices. It depends on a service
            (<a href="http://wwww.thumbr.io">thumbr.io</a>) to resize dinamically
            your images.
        </p>
        <div id="th-button-container">
            <p>
            <?php
                $signup_url = "$current_url&signup=1";
                $login_url = get_validate_url($current_url);
                echo "<a class='button button-primary' href='$signup_url'>Sign up</a>";
                echo "  or  <a href='$login_url'>Log in</a>";
                echo " Thumbr.io to enable this plugin.";
            ?>
            </p>
        </div>
    </div>
<?php
}

function print_settings_info($current_url) {
?>
    <div class="th-container">
        <h1>Welcome  <?php echo get_option(OPTION_EMAIL);?> to Thumbr.io</h1>
        <p>
        Your images in <strong><?php echo get_webdir() ?></strong>
        are now served by Thumbr.io through <strong><?php echo get_option(OPTION_SUBDOMAIN); ?></strong>.
        </p>
        <?php $url = get_validate_url($current_url); ?>
        <p>
            If you changed your Thumbr.io configuration you must 
            <?php echo "<a class='button button-primary' href='$url'>update</a>"; ?>
            these settings.            
        </p>
    </div>
<?php } 


/*
 * ***************************
 * Menu Admin Controller
 * ***************************
*/
function make_a_post($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);  
    curl_setopt($ch, CURLOPT_POST, count($data));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $content = curl_exec($ch);
    $error = curl_error($ch);

    if ($error != '') {
        return array('status' => False, 'message' => "$error");
    }
    $response = curl_getinfo($ch);
    curl_close ($ch);            
    if ($response['http_code'] !== 200) {
        return array('status' => False, 'message' => "$content");
    } else {
        return array('status' => True, 'message' => "$content");
    }
}
function add2url($url, $key_value) {
    preg_match('/(.+\?page=thumbrio)/', $url, $matches);
    if (count($matches) > 1) {
        $url = $matches[1];
    }
    $queue = "";
    if ($key_value) {
        $queue = "$key_value";
    }
    return "$url$queue";
}
function thumbrio_admin_post_signup() {
    if (!array_key_exists(OPTION_ADMIN_EMAIL, $_REQUEST)) {
        $next_url = add2url($_REQUEST['_wp_http_referer'], "&signup=1&error=" . urlencode('The email field is required'));
        wp_redirect($next_url, 302);
        exit;
    } else if (!array_key_exists(OPTION_ADMIN_PASSWORD, $_REQUEST)) {
        $next_url = add2url($_REQUEST['_wp_http_referer'], "&signup=1&error=" . urlencode('The password field is required'));
        wp_redirect($next_url, 302);
        exit;
    } else if (!array_key_exists(OPTION_ADMIN_PASSWORD . '-repeated', $_REQUEST)) {
        $next_url = add2url($_REQUEST['_wp_http_referer'], "&signup=1&error=" . urlencode('The password field is required'));
        wp_redirect($next_url, 302);
        exit;
    } else if ($_REQUEST[OPTION_ADMIN_PASSWORD . '-repeated'] != $_REQUEST[OPTION_ADMIN_PASSWORD]) {
        $next_url = add2url($_REQUEST['_wp_http_referer'], "&signup=1&error=" . urlencode('The passwords do not match'));
        wp_redirect($next_url, 302);
        exit;
    };

    $data = array(
        'username' => $_REQUEST[OPTION_ADMIN_EMAIL],
        'password' => $_REQUEST[OPTION_ADMIN_PASSWORD],
        OPTION_WEBDIR => get_webdir(),
    );
    $res = make_a_post(THUMBRIO_CREATE_ACCOUNT, $data);
    $subdomain = $res['message'];
    if ($res['status']) {
        update_option(OPTION_SUBDOMAIN, "http://$subdomain/");
        update_option(OPTION_EMAIL, $data['username']);
        $next_url = add2url($_REQUEST['_wp_http_referer'], null);
    } else {
        $next_url = add2url($_REQUEST['_wp_http_referer'], "&signup=1&error=" . urlencode($subdomain));
    }
    wp_redirect($next_url, 302);
    exit;
}
function thumbrio_admin_post_validate() {
    if (!array_key_exists(OPTION_SUBDOMAIN, $_REQUEST)) {
        return ('KO,The subdomain is required');
    } else if (!array_key_exists(OPTION_EMAIL, $_REQUEST)) {
        return ('KO,The email is required');
    }
    if (array_key_exists('submit-ok', $_REQUEST)) {
        update_option(OPTION_EMAIL, $_REQUEST[OPTION_EMAIL]);
        update_option(OPTION_SUBDOMAIN, 'http://' . $_REQUEST[OPTION_SUBDOMAIN] . '/');
    }
    $next_url = add2url($_REQUEST['_wp_http_referer'], null);
    wp_redirect($next_url, 302);
    exit;
}

?>
