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

define("THUMBRIO_BACKEND", "http://cdn.api.thumbr.io");
define("THUMBRIO_FRONTEND", "http://thumbr.io");


if (is_admin()) {
    // Create the menus
    add_action('admin_init', 'register_thumbrio_settings');
    add_action('admin_menu', 'thumbrio_menu');
    add_action('admin_menu', 'thumbrio_uploader_s3_menu');

    // Thumbrio Gallery Option
    add_action('media_upload_InsertMediaThumbrio','user_media_upload_InsertMediaThumbrio');
    add_filter('media_upload_tabs', 'user_media_upload_tabs');

    // Save Images
    add_action('admin_post_Save_images', 'Save_images');
    add_action('admin_post_Save_images1', 'Save_images1');
    add_action('admin_post_Save_images2', 'user_media_upload_InsertMediaThumbrio');

    // Edit Image
    add_action('admin_enqueue_scripts', 'user_admin_enqueue_scripts');
    add_filter('wp_get_attachment_url', 'user_wp_get_attachment_url');
    add_filter('wp_image_editors', 'user_wp_image_editors');
    add_filter('wp_update_attachment_metadata', 'user_wp_update_attachment_metadata');
    add_filter('update_attached_file', 'user_update_attached_file');
}
/*
 * ********************
 * SAVE IMAGES
 * ********************
*/
function Save_images() {
    require_once (dirname(__FILE__) . '/library/auxiliary.php');
    Auxiliary::insert_images($_REQUEST['thumbrio_file_names']);
    wp_redirect($_REQUEST['redirect_url'], 200);
    exit;
}
function Save_images1() {
    require_once (dirname(__FILE__) . '/library/auxiliary.php');
    Auxiliary::insert_images($_REQUEST['thumbrio_file_names']);
    require('library/thumbrio-media.php');
    exit;
}

/**
 * **********************
 * Gallery amazon s3
 * **********************
*/
function user_media_upload_tabs($tabs) {
    $tabs['InsertMediaThumbrio'] = 'Insert media Thumbr.io';
    $tabs['gallery'] = 'Insert media Thumbr.io';
    return $tabs;
}
function user_media_upload_InsertMediaThumbrio() {
    require('library/thumbrio-media.php');
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
function user_wp_get_attachment_url($path) {
    preg_match('/wp\-content\/uploads\/https?\:\/\/.+$/', $path, $result);
    if ($result) {
        $path = substr($result[0], strlen('wp-content/uploads/'));
    }
    return $path;
}
function user_wp_update_attachment_metadata($post, $data=null){
    $base_url = get_option('thumbrio_base_url');
    if (substr($post['file'], 0, strlen($base_url)) == $base_url) {
        $aux = get_option('thumbrio_aux');
        if ($aux) {
            $post['file'] = $aux;
        }
    } else {
        update_option('thumbrio_aux', null);
    }
    return $post;
}
function user_update_attached_file($attachment_id, $file=null){
    $res = $attachment_id;
    $aux = get_option('thumbrio_aux');
    if ($aux) {
        $res = $aux; 
    }
    return $res;
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
    register_setting('thumbrio-group', 'amazon_s3_bucket_name');
    register_setting('thumbrio-group', 'amazon_s3_access_key');
    register_setting('thumbrio-group', 'amazon_s3_secret_key');
    register_setting('thumbrio-group', 'thumbrio_edit_image_url');
    register_setting('thumbrio-group', 'amazon_buffer_marks');
    register_setting('thumbrio-group', 'amazon-s3-urls');

    $base_url = defined('THUMBRIO_BASE_URL');
    if ($base_url) {
        $base_url = THUMBRIO_BACKEND . '/';  
    }
    update_option('thumbrio_base_url', $base_url);

    $buffer_marks = get_option('amazon_buffer_marks');
    if (!$buffer_marks) {
        update_option('amazon_buffer_marks', json_encode(array()));
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
            <input type="hidden" name="action" value="Save_images" />
        </form>
    </div>
    <div id="thumbrio-gallery-menu">
        <form method="POST" action="admin-post.php">
            <h2>The latest images in your bucket</h2>
            <?php $s3->print_all_files('thumbrio-gallery', "100x100c", false); ?>
            <input name="submit2" type="submit" value="Use images" class="button button-primary"/>
            <input type="hidden" name="redirect_url" value="<?php echo admin_url('upload.php?page=thumbrio_uploader'); ?>" />
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