<?php

function thumbrio_wp_head() {
    wp_register_script('thumbrio-view', THUMBRIO_RESPONSIVE_JS);
    wp_enqueue_script('thumbrio-view');
    buffer_start();
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
 * *********************************************************
 * Thumbrioze all images with the thumbrio's class in html
 * Substitute the 'src' attribute by a 'data-src'
 * scr contains a local wordpress folder url
 * *********************************************************
*/
function src_to_data_src_th($html) {
    $webdir = get_webdir();
    $thurl = get_option(OPTION_SUBDOMAIN);
    if (strpos($html, "src=\"" . $webdir)) {
        $html = str_replace('src="'. $webdir . '/', 'src="' . LOADING_GIF . '" data-src="' . $thurl, $html);
        $html = preg_replace('/<img([^>]+)class="/', '<img\1class="' . THUMBRIO_CSS_CLASS . ' ', $html);
    };
    return $html;
}

function buffer_start() {
  ob_start('src_to_data_src_th');
}

function thumbrio_wp_footer() {
  ob_end_flush();
}

?>
