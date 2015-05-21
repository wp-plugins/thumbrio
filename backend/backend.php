<?php
function thumbrio_wp_head() {
    thumbrio_buffer_start();
}

//FIXME: Changed for testing. To check
function thumbrio_process_post_meta_file ($file, $post_id) {
    $metadata_attach = thumbrio_metadata_as_array($post_id);
    $width    = $metadata_attach['width'];
    $height   = $metadata_attach['height'];
    $thb_size = "?size=$width"."x$height";
    $thb_file = $file;
    $metadata_attach['file'] = $thb_file;
    update_post_meta($post_id, '_wp_attached_file', $thb_file);
    update_post_meta($post_id, '_wp_attachment_metadata', $metadata_attach);
}

function thumbrio_which_action ($server = null, $request = null) {

    $server  = ($server)  ? $server : $_SERVER;
    $request = ($request) ? $request : $_REQUEST;

    $php_self = $server['REQUEST_URI'];

    switch ($php_self) {
        case '/wp-admin/async-upload.php':
            $action = 'async-upload';
            break;
        case '/wp-admin/admin-ajax.php':
            if (isset($request['action']) && $request['action'] == 'custom-header-crop' ) {
                $action = 'custom-header-crop';
            } else {
                $action = 'after_edition';
            }
            break;
        case '/wp-admin/admin-post.php':
            $action = $request['action'];
            break;
        case '/wp-admin/post.php':
            if (strpos($server['QUERY_STRING'], 'action=edit')){
                $action = 'edition';
            } else {
                $action = 'other';
            }
            break;
        case '/wp-admin/media-new.php':
            $action = 'upload';
            break;
        case '':
            $action = 'customize-crop-header';
            break;
        case '/':
            if ($request['wp_customize'] == 'on') {
                $action = 'customize';
            } else {
                $action = 'other';
            }
            break;
        default:
            $action = 'other';
            break;
    }
    return $action;
}

/*
 *******************************************
 * Transform the url
 *******************************************
 */
// Remove the local path added to url if it is not correct.
function thumbrio_remove_bad_added_path($url) {
    $reg_prot = '/https?:\/\//';
    $ans      = preg_split($reg_prot, $url);
    $path     = end($ans);
    preg_match_all($reg_prot, $url, $matches);
    $protocol = end($matches[0]);

    return $protocol . $path;
}

function thumbrio_remove_edition_tag ($url) {
    if (preg_match('/-e[0-9]*(\.[jpegtifbmpn]{3,4})$/', $url)) {
        return preg_replace('/-e[0-9]*(\.[jpegtifbmpn]{3,4})$/', '\1', $url);
    }
    return $url;
}

// Get the good url when there are thumbrio arguments
// Filter of wp_get_attachment_url
function thumbrio_get_attachment_url($url, $post_id) {
    $temporal = get_post_meta( $post_id);
    $temporal = $temporal['_wp_attached_file'];
    $new_url = $temporal[0];
    return $new_url;
}

/*
 ********************************************
 * Auxiliary functions
 ********************************************
*/
// Return true if the origin is the local one
function thumbrio_is_webdir_local() {
    return (thumbrio_get_local_webdir() === get_option(OPTION_WEBDIR));
}

function thumbrio_get_local_webdir() {
    $upload = wp_upload_dir();
    return $upload['baseurl'];
}

function thumbrio_starts_with($haystack, $needle) {
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function thumbrio_replace_img_attributes($img_matches) {

    $thumbrio_url = 'https://' . get_option(OPTION_SUBDOMAIN);
    if ( thumbrio_is_webdir_local() ) {
        $webdir = thumbrio_get_local_webdir();
    } else {
        $webdir = $thumbrio_url;
    }

    $img_src = '';
    $img_class = '';
    $attributes = array();
    $attributes_str = $img_matches[1];

    preg_match_all("/([a-z0-9_-]+)\s*(?:=\s*[\"\']([^\"\']*)[\"\'])?/is", $attributes_str, $attributes, PREG_SET_ORDER);

    $filtered_attributes = array();

    // extract into $filtered_attributes src, class and drop data-src attribute (if it's there)
    foreach ($attributes as $attr) {
        $attr_name = strtolower($attr[1]);
        $attr_value = $attr[2] ? $attr[2] : '';
        switch ($attr_name) {
            case 'src':
                $img_src = $attr_value;
                break;

            case 'class':
                $img_class = $attr_value;
                break;

            case 'data-src':
                $img_data_src = $attr_value;
                break;

            default:
                $filtered_attributes[] = "$attr[1]=\"$attr[2]\"";
        }

    }

    // change src, class and add a new data-src to $filtered_attributes
    $img_src_wo_protocol = preg_replace('|^https?:|', '', $img_src);
    $webdir_wo_protocol = preg_replace('|^https?:|', '', $webdir);

    if (thumbrio_starts_with($img_src_wo_protocol, $webdir_wo_protocol)) {
        $new_img_data_src = $thumbrio_url . substr($img_src_wo_protocol, strlen($webdir_wo_protocol));
        $new_img_src = LOADING_GIF;
        $new_img_class = $img_class . ' ' . THUMBRIO_CSS_CLASS;

        $filtered_attributes[] = "src=\"$new_img_src\"";
        $filtered_attributes[] = "class=\"$new_img_class\"";
        $filtered_attributes[] = "data-src=\"$new_img_data_src\"";
        $filtered_attributes[] = "data-original-src=\"$img_src\"";

        return '<img ' . join(' ', $filtered_attributes) . '>';
    }
    else {
        // return original attributes if src doesn't contain the expected domain / path.
        return $img_matches[0];
    }
}

/*
 * *********************************************************
 * Thumbrioze all images with the thumbrio's class in html
 * Substitute the 'src' attribute by a 'data-src'
 * scr contains a local wordpress folder url
 * *********************************************************
*/
function thumbrio_src_to_data_src($html) {
    if (!get_option(OPTION_SUBDOMAIN)) {
        return $html;
    }
    else {
        return preg_replace_callback('/<img\s+([^>]*)>/', 'thumbrio_replace_img_attributes', $html);
    }
}

function thumbrio_wp_enqueue_scripts() {
    wp_register_script('thumbrio-view'  , THUMBRIO_RESPONSIVE_JS);
    wp_enqueue_script('thumbrio-view');
}

function thumbrio_buffer_start() {
    ob_start('thumbrio_src_to_data_src');
}

function thumbrio_wp_footer() {
    ob_end_flush();
}

?>
