<?php
// Puts the thumbrio arguments in an array
function query_args_to_array ($queryArgs) {
    $queryArgs = preg_replace('|^[?&]|', '', $queryArgs);
    parse_str($queryArgs, $array_args);
    $return = array();

    if (array_key_exists('size', $array_args)) {
        $values = explode('x', $array_args['size']);
        $size = array(
            'w' => (int) $values[0],
            'h' => (int) $values[1]
        );
        $return['size'] = $size;
    }
    if (array_key_exists('rect', $array_args)) {
        $values = explode(',', $array_args['rect']);
        $rect = array(
            'x' => (int) $values[0],
            'y' => (int) $values[1],
            'w' => (int) $values[2],
            'h' => (int) $values[3]
        );
        $return['rect'] = $rect;
    }
    if (array_key_exists('mirror', $array_args)) {
        $return['mirror'] = 1;
    }
    if (array_key_exists('angle',  $array_args)) {
        $return['angle'] = (int) $array_args['angle'];
    }
    return $return;
}

function thumbrio_get_post_from_query($query) {
    preg_match('/post=([0-9]+)/',$query, $matches);
    return $matches[1];
}

function thumbrio_metadata_as_array($post_id) {
    $metadata = get_post_meta($post_id);
    if (!isset($metadata['_wp_attachment_metadata']))
        return false;
    return unserialize($metadata['_wp_attachment_metadata'][0]);
}

// Used to remove the folder created during image edition
// if $always if false remove only empty folders
function thumbrio_unlink_recursive($dir, $always = true ) {
    if(!$dh = @opendir($dir)) {
        return;
    }
    while (false !== ($obj = readdir($dh))) {
        if($obj == '.' || $obj == '..') {
            continue;
        }
        if ((!@opendir($dir . '/' . $obj)) and (!$always)) {
            return;
        }
        if (!@unlink($dir . '/' . $obj)) {
            thumbrio_unlink_recursive($dir.'/'.$obj, $always);
        }
    }
    closedir($dh);
    @rmdir($dir);
    return;
}

// Filter to add our Class Image Editor
function thumbrio_image_editors ( $implementations ) {
    // Put our Editor as the first in list
    array_unshift( $implementations, 'Thumbrio_Image_Editor');
    return $implementations;
}
// Filter in update_attached_file in post.php
// Used by wp_save_image
function thumbrio_update_attached_file ( $file, $attachment_id )  {
    if ( thumbrio_is_webdir_local() )
        return $file;
    $new_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
    if ( $new_file ) {
        // We use used preffix 'th-' to be able to update now
        // Removing 'th-' we get a 'new' filename,  so 'wp updating' works.
        if ( substr($new_file, 0, 3) === 'th-' ) {
            return substr($new_file, 3);
        }
    }
    return thumbrio_remove_bad_added_path( $file );
}
// Filter in update_attached_file in post.php
// Used by wp_save_image
function thumbrio_update_attachment_metadata ( $data, $post_id ) {
    if ( thumbrio_is_webdir_local() )
        return $data;
    $action = thumbrio_which_action();
    if ( $action === 'after_edition') {
        $data = _thumbrio_update_attachment_metadata ( $data, $post_id );
    }
    return $data;
}
// Used by thumbrio_update_attachment_metadata
function _thumbrio_update_attachment_metadata ( $data, $post_id ) {
    if ( $file = get_post_meta( $post_id, '_wp_attached_file') )
        $data['file'] = $file[0];
    return $data;
}
// Filter in wp_save_image_file
function thumbrio_save_image_editor_file ( $saved, $filename, $image, $mime_type, $post_id ) {
    $file = $image->generate_filename();
    // We need to add a preffix to make able the update post meta later
    update_post_meta( $post_id, '_wp_attached_file', 'th-'. $file );
    return $image->save();
}
// Filter
function thumbrio_relative_upload_path ( $new_path, $path = null ) {
    if (thumbrio_is_webdir_local())
        return $new_path;
    $action = thumbrio_which_action();
    if ( $action === 'after_edition') {
        $new_path = _thumbrio_relative_upload_path ( $new_path );
    }
    return $new_path;
}
// Used by thumbrio_relative_upload_path
function _thumbrio_relative_upload_path ( $path ) {
    $url_parts  = parse_url($path);
    $path_parts = pathinfo($path);
    $new_filename = preg_replace('/-e[0-9]{13}$/', '', $path_parts['filename']);
    $new_path  = $path_parts['dirname'].'/'.$new_filename . '.'. $path_parts['extension'];
    $new_path .= isset($path_parts['query']) ? "?{$path_parts['query']}" : '';
    return $new_path;
}
//FIXME//////////////////////////////////////// TO REMOVE
// Filter in wp_save_image_file
// FIXME: Remove this filter. It is not used anymore
function thumbrio_editor_save_pre ($image, $post_id) {
    return $image;
}
// Filter
// It could be removed
function thumbrio_image_editor_before_change ($image, $changes) {
    return $image;
}
?>