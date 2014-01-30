<?php
/*
Plugin Name: Thumbrio amazon s3 services
Plugin URL: http://thumbr.io
Description: A plugin to serve your images saved in amazon through thumbr.io
Version: 1.0
Author: Joaquin cuenca cuenca@thumbr.io.
*/
require_once (dirname(__FILE__) . '/library/amazon-s3-thumbrio.php');
require_once (dirname(__FILE__) . '/library/auxiliary.php');

define("THUMBRIO_BACKEND", "http://cdn.thumbr.io");
define("THUMBRIO_FRONTEND", "http://thumbr.io");


if (is_admin()) {
    // create global variables.
    add_action('admin_init', 'register_thumbrio_settings');
    
    // Create the menus "SETTINGS" and "MEDIA" -> "THUMBRIO UPLOADER".
    add_action('admin_menu', 'thumbrio_menu');
    add_action('admin_menu', 'thumbrio_uploader_s3_menu');

    // Thumbrio Gallery Option
    add_action('media_upload_InsertMediaThumbrio','user_media_upload_InsertMediaThumbrio'); 
    add_filter('media_upload_tabs', 'user_media_upload_tabs');
    add_action('admin_post_Save_images', 'user_admin_post');
    add_action('admin_post_Save_images_media', 'user_admin_post');

    // Edit Image
    add_action('admin_enqueue_scripts', 'user_admin_enqueue_scripts');
    add_filter('get_image_tag', 'user_get_image_tag');
} else {
    add_action('wp_head', 'user_wp_head', 0);
    function user_wp_head() {
        wp_register_style('thumbrio-active', plugins_url('static/css/thumbrio.responsive.css', __FILE__)); 
        wp_enqueue_style('thumbrio-active');
    }    
}


/*
 * ********************
 * Thumbrio Gallery Option
 * ********************
*/
function user_media_upload_InsertMediaThumbrio() {
    require('library/thumbrio-media.php');
}
function user_media_upload_tabs($tabs) {
    $tabs['InsertMediaThumbrio'] = 'Insert media Thumbr.io';
    $tabs['gallery'] = 'Insert media Thumbr.io';
    return $tabs;
}
function user_admin_post() {
    require_once (dirname(__FILE__) . '/library/auxiliary.php');
    Auxiliary::insert_images($_REQUEST['thumbrio_file_names']);
    if ($_REQUEST['storage'] == "s3") {
        update_option('thumbrio_amazon_buffer_marks', json_encode(array()));
        update_option('thumbrio_amazon_s3_urls', '');
    }
    if (array_key_exists('redirect_url', $_REQUEST)) {
        wp_redirect($_REQUEST['redirect_url'], 200);
    } else {
        // require('library/thumbrio-media.php');
    }
    die();
}

/*
 * *******************************************
 * edit image
 * *******************************************
*/
function user_admin_enqueue_scripts($hook) {
    if('post.php' != $hook)
        return;
    
    echo "<script type='text/javascript'>\n";
    echo "\tvar API_KEY_THUMBRIO = '" . get_option('thumbrio_api_key') . "';\n";
    echo "\tvar SECRET_KEY_THUMBRIO = '" . get_option('thumbrio_secret_key') . "';\n";
    echo "\tvar BASE_URL_THUMBRIO = '" . get_option('thumbrio_base_url') . "';\n";
    echo "</script>\n";
    echo "<script src='" . plugins_url('static/js/hmac_md5.js', __FILE__) . "'></script>\n";
    echo "<script src='" . plugins_url('static/js/hmac.js', __FILE__) . "'></script>\n";
    echo "<script src='" . plugins_url('static/js/thumbrio.wordpress.js', __FILE__). "'></script>\n";
    echo "<script src='" . THUMBRIO_BACKEND . "/js/plugin.js" . "'></script>\n";
    echo "<script type='text/javascript'>window.onload = function() { initializeEditorImages(); };</script>\n";
}
function user_get_image_tag($html) {
    // This function is called when we attach an image to a post.
    // $html is somthing like this: "<img src="xxx" class="xxx" width="xxx" height="xxx" />"
    preg_match('/src=\"https?\:\/\/cdn.thumbr.io/', $html, $result);
    if ($result[0]) {
        $html = preg_replace(array('/class="([^\"]*)"/'), array('class="\1 thumbrio-responsive"'), $html);
    }
    return $html;
}

/**
 * **************************
 * INIT VARIABLES
 * **************************
*/
function register_thumbrio_settings() {
    register_setting('thumbrio-group', 'thumbrio_api_key');
    register_setting('thumbrio-group', 'thumbrio_secret_key');
    register_setting('thumbrio-group', 'thumbrio_base_url');
    register_setting('thumbrio-group', 'thumbrio_storage_settings');
    register_setting('thumbrio-group', 'thumbrio_amazon_s3_bucket_name');
    register_setting('thumbrio-group', 'thumbrio_amazon_s3_access_key');
    register_setting('thumbrio-group', 'thumbrio_amazon_s3_secret_key');
    register_setting('thumbrio-group', 'thumbrio_amazon_s3_urls');
    register_setting('thumbrio-group', 'thumbrio_amazon_buffer_marks');
    $base_url = defined('THUMBRIO_BASE_URL');
    if ($base_url) {
        $base_url = THUMBRIO_BACKEND . '/';  
    }
    update_option('thumbrio_base_url', $base_url);

    $buffer_marks = get_option('thumbrio_amazon_buffer_marks');
    if (!$buffer_marks) {
        update_option('thumbrio_amazon_buffer_marks', json_encode(array()));
    }
}
/**
 * **********************
 * Uploader amazon s3
 * **********************
*/
function thumbrio_uploader_s3_menu() {
    add_media_page('Thumbrio Uploader', 'Thumbrio Uploader', 'read', 'thumbrio_uploader', 'thumbrio_uploader');
}
function thumbrio_uploader() {
    wp_enqueue_style('dropzone-css', THUMBRIO_FRONTEND . "/static/css/dropzone.css");
    wp_enqueue_script('dropzone-js', THUMBRIO_FRONTEND . "/static/js/thirdparty/dropzone.min.js");
    wp_enqueue_style('thumbrio-gallery-css', plugins_url('static/css/thumbrio.wordpress.css', __FILE__));

    wp_enqueue_script('thumbrio-uploader-js', THUMBRIO_FRONTEND . "/js/thumbrio.uploader.js");
    wp_enqueue_script('cripto', plugins_url('static/js/hmac_md5.js', __FILE__));
    wp_enqueue_script('hmac', plugins_url('static/js/hmac.js', __FILE__));
    wp_enqueue_script('thumbrio', plugins_url('static/js/thumbrio.wordpress.js', __FILE__));

    if (!current_user_can('read')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    $s3 = new Amazon_s3_thumbrio('', 0);
?>

<div class="wrap">
    <?php screen_icon(); ?>
    <div id="thumbrio-menu">
        <h2>Thumbrio Uploader</h2>
    </div>
    <div id="thumbrio-uploader-menu">
        <h2>Upload Files</h2>
        <form method="POST" action="admin-post.php">
            <div id="thumbrio-uploader" class="dropzone"></div>
            <input name="submit" type="submit" value="Use images" class="button button-primary"/>
            <input type="hidden" name="redirect_url" value="<?php echo admin_url('upload.php?page=thumbrio_uploader'); ?>" />
            <input type="hidden" name="storage" value="s3" />
            <input type="hidden" name="action" value="Save_images" />
        </form>
    </div>
    <div id="thumbrio-gallery-menu">
        <form method="POST" action="admin-post.php">
            <h2>The latest images in your bucket</h2>
            <?php $s3->print_all_files('thumbrio-gallery', "100x100c", false); ?>
            <input name="submit2" type="submit" value="Use images" class="button button-primary"/>
            <input type="hidden" name="redirect_url" value="<?php echo admin_url('upload.php?page=thumbrio_uploader'); ?>" />
            <input type="hidden" name="storage" value="local" />
            <input type="hidden" name="action" value="Save_images" />
        </form>
    </div>
    <script type="application/javascript">
    window.onload = function () {
        var AMAZON_S3_BUCKET_NAME = "<?php echo $s3->get_user_data('amazon_bucket_name'); ?>";
        var AMAZON_S3_SECRET_KEY = "<?php echo $s3->get_user_data('amazon_secret_key'); ?>";
        var AMAZON_S3_ACCESS_KEY = "<?php echo $s3->get_user_data('amazon_access_key'); ?>";
        var THUMBRIO_API_KEY = "<?php echo $s3->get_user_data('thumbrio_api_key'); ?>";
        var SETTINGS_UPLOADER_DEFAULT = JSON.parse('<?php echo get_option('thumbrio_storage_settings') ?>');

        initializeDropzone(THUMBRIO_API_KEY, AMAZON_S3_SECRET_KEY, AMAZON_S3_BUCKET_NAME, AMAZON_S3_ACCESS_KEY, SETTINGS_UPLOADER_DEFAULT);
        insertImageIntoDatabase(document.getElementsByClassName('button')[0], document.getElementsByTagName('form')[0]);
        insertImageIntoDatabase(document.getElementsByClassName('button')[1], document.getElementsByTagName('form')[1]);
        selectAnImage();
    };
    </script>
</div>
<?php
}
/**
 * **********************
 * Settings Thumbr.io
 * **********************
*/
function thumbrio_menu() {
    add_options_page('Thumbr.io Keys', 'Thumbr.io', 'manage_options', 'thumbrio', 'thumbrio_options');
}
function thumbrio_options() {
    wp_enqueue_script('cripto', plugins_url('static/js/hmac_md5.js', __FILE__));
    wp_enqueue_script('storageInfo', plugins_url('static/js/thumbrio.wordpress.js', __FILE__));
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
?>
    <div class="wrap">
    <?php
        screen_icon();
    ?>
    <h2>Thumbr.io settings</h2>
    <form method="post" action="options.php">
    <?php
        settings_fields('thumbrio-group');
    ?>
    <p>You can consult your api key and secret key in <a href="http://www.thumbr.io/profile/">thumbrio profile</a>.<p>
    <p>Before submitting your keys, assure you have got your <strong>Amazon S3</strong> or <strong>Thumbr.io S3</strong> backend
        configured in <a href="http://www.thumbr.io/profile/storage">thumbrio storage</a>.</p>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">Thumbr.io API Key</th>
            <td><input type="text" name="thumbrio_api_key" value="<?php echo get_option('thumbrio_api_key'); ?>" /></td>
        </tr>
        <tr valign="top">
            <th scope="row">Thumbr.io Secret Key</th>
            <td><input type="text" name="thumbrio_secret_key" value="<?php echo get_option('thumbrio_secret_key'); ?>" /></td>
        </tr>
    </table>
    <?php
        submit_button();
    ?>
    </form>
    </div>
    <script type="text/javascript">
        window.onload = function () {
            saveUserAndPassword();
        };
    </script>
    <?php
}
?>
