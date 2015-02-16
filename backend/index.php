<?php

function thumbrio_wp_head() {
    wp_register_script('thumbrio-view', THUMBRIO_RESPONSIVE_JS);
    wp_enqueue_script('thumbrio-view');
    thumbrio_buffer_start();
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

function thumbrio_starts_with($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

function thumbrio_replace_img_attributes($img_matches) {
    $img_src = '';
    $img_class = '';
    $webdir = get_webdir();
    $attributes_str = isset($img_matches[1]) ? $img_matches[1] : '';
    $thurl = get_option(OPTION_SUBDOMAIN);
    $attributes = array();
    preg_match_all('/([a-z0-9_-]+)\s*(?:=\s*[\"\']([^\"\']*)[\"\'])?/is', $attributes_str, $attributes, PREG_SET_ORDER);

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
            case 'data-original-src':
                // we drop a previous data-src or data-original-src on this img
                break;

            default:
                $filtered_attributes[] = "$attr[1]=\"$attr[2]\"";
        }
    }

    // change src, class and add a new data-src to $filtered_attributes
    $img_src_wo_protocol = preg_replace('|^https?:|', '', $img_src);
    $webdir_wo_protocol = preg_replace('|^https?:|', '', $webdir);
    if (thumbrio_starts_with($img_src_wo_protocol, $webdir_wo_protocol . '/')) {
        $new_img_data_src = $thurl . substr($img_src_wo_protocol, strlen($webdir_wo_protocol) + 1);
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

function thumbrio_buffer_start() {
    ob_start('thumbrio_src_to_data_src');
}

function thumbrio_wp_footer() {
    ob_end_flush();
}
?>
