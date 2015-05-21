<?php

function thumbrio_remove_image_extension ($image) {
    $name_parts = explode('.', $image);
    return implode('.', array_slice($name_parts, 0, count($name_parts) - 1));
}

function thumbrio_update_header_post($post_id, $metadata) {
    $url_file_array = parse_url($metadata['file']);
    $long_name      = substr($url_file_array['path'], 1);
    $post_title     = urldecode(thumbrio_remove_image_extension($long_name));
    $post_content   = '';
    $post_name      = sanitize_title($post_title);

    return array(
        'ID'           => $post_id,
        'post_title'   => $post_title,
        'post_content' => $post_content,
        'post_name'    => $post_name
    );
}
// Compute the dimensions of header.
// TODO: Use the "flex" conditions
function thumbrio_get_header_dimensions( $dimensions = null ) {
    $theme_height = get_theme_support( 'custom-header', 'height' );
    $theme_width  = get_theme_support( 'custom-header', 'width' );
    //$has_flex_width  = current_theme_supports( 'custom-header', 'flex-width' );
    //$has_flex_height = current_theme_supports( 'custom-header', 'flex-height' );
    //$has_max_width   = current_theme_supports( 'custom-header', 'max-width' ) ;
    $dst = array( 'height' => $theme_height, 'width' => $theme_width );
    return $dst;
}


// Create the metadata asscociated to header image
function thumbrio_generate_cropped_header_metadata($metadata, $post_id, $crop, $parent_id) {
    $dst = thumbrio_get_header_dimensions();
    // Update width to keep the aspect ratio
    $dst['height'] = round ($crop['height'] * $dst['width'] / $crop['width']);

    $file = get_post_meta($post_id, '_wp_attached_file');
    $file = $file[0];
    $image = new Thumbrio_Image_Editor ( $file );
    $image->crop( $crop['x1'], $crop['y1'], $crop['width'], $crop['height'], $dst['width'], $dst['height'] );

    $metadata['file'] = $image->generate_filename(); //Use pipes for query arguments
    $metadata['width']  = $dst['width'];
    $metadata['height'] = $dst['height'];

    $sizes = thumbrio_get_image_sizes();
    $metadata['sizes'] = $image->multi_resize( $sizes );

    // Update the info of the header
    $object = thumbrio_update_header_post($parent_id, $metadata);
    wp_update_post( $object );

    return $metadata;
}

// Displayable image. Used in 'wp_generate_attachment_metadata'
// Return false to avoid WP to try to open the file. Otherwise, it'll crash
function thumbrio_file_is_displayable_image ($result, $path) {
    if (thumbrio_which_action($_SERVER, $_REQUEST) === 'custom-header-crop')
        return false;
    return $result;
};

// Change the default name given to the cropped header.
// Return a 'filename' with thumbrio's query arguments.
// The generated header file and folders are removed from local directory.
function thumbrio_create_file_in_uploads ($cropped, $post_id) {
    $action = thumbrio_which_action($_SERVER, $_REQUEST);
    if ($action === 'custom-header-crop') {
        // Remove file and folders
        @unlink($cropped);
        $upload_dir = wp_upload_dir();
        $upload_dir = $upload_dir['basedir'];
        thumbrio_unlink_recursive($upload_dir . '/http:', false);
        thumbrio_unlink_recursive($upload_dir . '/https:', false);
        // Generate the new filename
        $file  = get_post_meta($post_id, '_wp_attached_file');
        $file  = $file[0];
        $crop  = $_POST['cropDetails'];

        $image = new Thumbrio_Image_Editor ( $file );
        $dst   = thumbrio_get_header_dimensions();
        $image->crop( $crop['x1'], $crop['y1'], $crop['width'], $crop['height'], $dst['width'], $dst['height'] );
        $cropped = $image->_generate_filename( true );
    }
    return $cropped;
}
?>
