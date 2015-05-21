<?php
function thumbrio_upload_footer() {
    // Control update table in media-new page
    /*
    if (strpos($_SERVER['PHP_SELF'], 'wp-admin/media-new.php')) {
        ?>
            <script type='text/javascript' src='<?=THUMBRIO_UPLOAD_JS?>'></script>
        <?php
    }*/
    if (strpos($_SERVER['PHP_SELF'], 'wp-admin/options-general.php')) {
        ?>
            <script type='text/javascript' src='<?=THUMBRIO_WORDPRESS_JS?>'></script>
        <?php
    }
}

// Synchronization from external storage. Generate a final message
function thumbrio_admin_post_sync() {
    try {
        $bucket = new Bucket();
        $content = $bucket->synchronize();
        $added = count($content);
    } catch(BucketException $e) {
        header('HTTP/1.0 404 Not Found');
        $added = urlencode($e->getMessage());
    }
    if ($added === 'error') {
        $message_type = 'error';
        $message = 'There was an error';
    } else {
        $added = (int) $added;
        $message_type = 'updated';
        if ( $added > 1) {
            $message = "$added media attachments were added";
        } else if ( $added == 1) {
            $message = "A new media attachment was added";
        } else {
            $message = "No new media attachment was added";
        }
    }
    echo "<p>$message</p>";
    die();
};

// Return the URL to local upload folder
function thumbrio_get_local_upload_dir() {
    $upload = wp_upload_dir();
    return $upload['baseurl'];
}

// Return the webdir for images (origin)
function thumbrio_get_webdir () {
    return get_option(OPTION_WEBDIR);
}

// Change the data created by WP
function thumbrio_generate_uploaded_image_data( $post_id ) {
    $post      = get_post( $post_id );
    $image_url = $post->guid;

    $size = @getimagesize( $image_url );
    $metadata = array (
        'file'   => $image_url,
        'width'  => $size[0],
        'height' => $size[1]
    );

    $image = new Thumbrio_Image_Editor( $image_url );
    $sizes = thumbrio_get_image_sizes();
    $metadata['sizes'] = $image->multi_resize( $sizes );

    return  array (
            '_wp_attached_file'        => $image_url,
            '_wp_attachment_metadata'  => $metadata
        );
}

// Delete the files created by WP during uploading
function thumbrio_delete_local_files($raw_metadata) {
    $metadata   = $raw_metadata;
    $local_dir  = wp_upload_dir();
    $local_dir  = $local_dir['basedir'];
    $path       = $metadata['file'];
    $path_parts = explode('/', $path);
    $filename   = end($path_parts);
    $temp_dir   = '/'. substr($path, 0, strlen($path) - strlen($filename));
    $temp_root  = explode('/', $temp_dir);
    $folder     = $local_dir . $temp_dir;
    foreach ($metadata['sizes'] as $name_size => $value) {
        @unlink($folder . $value['file']);
    }
    @unlink($folder . $filename);
    return $local_dir . '/' .$temp_root[1];
}

// Return the post_id given the thumbnail's filename
function thumbrio_get_post_id_from_size($url, $size = 'thumbnail') {
    $query_images_args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        );
    $query_images = new WP_Query($query_images_args);
    $url_parts = explode('/', $url);
    $filename = end($url_parts);
    foreach ($query_images->posts as $image) {
        $metadata  = thumbrio_metadata_as_array($image->ID);
        $file = $metadata['sizes'][$size]['file'];
        if ($filename === $file) {
            return $image->ID;
        }
    }
}

/**
* Delete an image from Media Library and S3 bucket
*/
function thumbrio_delete_post($post_id) {
    $post = get_post($post_id);
    $url2remove = $post->guid;
    try {
        $s3 = new Bucket();
        $s3->delete_object_v4($url2remove);
    } catch (BucketException $e) {
    }
    return $post_id;
}

/**
* Upload an image to amazon s3 and insert the GUID of post into the WP DB.
*/
function thumbrio_handle_upload ( $results ) {
    if (thumbrio_is_webdir_local()) {
        return $results;
    }
    $domain = get_option(OPTION_SUBDOMAIN);
    try {
        $bucket = new Bucket();
        $file = $_FILES['async-upload'];

        $response = $bucket->post_object_v4($results['file'], $file['name'], $file['size'], $file['type'], gmdate("Ymd\THis\Z"));

        // TODO: this get_thumbrio_urls seems buggy
        $url = $bucket->get_thumbrio_urls(array($file['name']));

        $url = 'http://' . $url[0];
        $results['url'] = $url;
    } catch (BucketException $e) {
        $results['error'] = $e->getMessage();
    }
    return $results;
}

function thumbrio_get_image_sizes () {
    $intermediate_sizes = get_intermediate_image_sizes();
    global $_wp_additional_image_sizes;

    $sizes = array_merge( $intermediate_sizes, array_keys( $_wp_additional_image_sizes ));

    foreach ( $sizes as $size ) {
        if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
            $width  = intval( $_wp_additional_image_sizes[ $size ]['width'] );
            $height = intval( $_wp_additional_image_sizes[ $size ]['height'] );
            $crop   = $_wp_additional_image_sizes[ $size ]['crop'];
        } else {
            $height = get_option( "{$size}_size_h" );
            $width  = get_option( "{$size}_size_w" );
            $crop   = get_option( "{$size}_crop" );
        }

        $_sizes[ $size ] = array( 'width' => $width, 'height' => $height, 'crop' => $crop );
    }
    return $_sizes;
}

/*
 * Return a metadata corresponding to Thumbr.io options
 * given the usual metadata
 */
function thumbrio_generate_attachment_metadata($metadata, $parent_id = null) {
    $action = thumbrio_which_action($_SERVER, $_REQUEST);

    // It doesn't change anything if we are in local origin.
    // Don't change metadata if we are in media-new page to avoid an bug.
    if (thumbrio_is_webdir_local()) {
        return $metadata;
    }

    if ($action === 'async-upload') {
        $folder    = thumbrio_delete_local_files($metadata);
        $post_meta = thumbrio_generate_uploaded_image_data($parent_id);
        update_post_meta($parent_id, '_wp_attached_file', $post_meta['_wp_attached_file']);
        thumbrio_unlink_recursive($folder, true);
        return $post_meta['_wp_attachment_metadata'];
    }

    if ($action == 'custom-header-crop') {
        $crop    = $_REQUEST['cropDetails'];
        $post_id = $_REQUEST['id'];
        return thumbrio_generate_cropped_header_metadata($metadata, $post_id, $crop, $parent_id);
    }
    return $metadata;
}

// Filter to change attachment permalink.
// Used to the view attachment in Media Library
function thumbrio_attachment_link ( $link, $post_id ) {
    $link = get_post_meta ($post_id, '_wp_attached_file');
    return $link[0];
}

// Filter the attachment attributes of an attachment
// However it is not called when we publish the attachment page
function thumbrio_get_attachment_image_attributes ( $attributes, $attachment, $size) {
    return $attributes;
}

// Insert in DB the information from a new image given by thumbrio url
function thumbrio_insert_image ( $th_url ) {
    $image = new Thumbrio_Image_Editor ( $th_url );
    $info = $image->save();

    $subdomain    = get_option(OPTION_SUBDOMAIN);
    $th_url_short = preg_replace('/^https?:\/\//', '', $info['file']);         // remove protocol
    $post_title   = substr( $th_url_short, strlen($subdomain) + 1 );           // remove subdomain
    $post_title   = preg_replace('/\.?[jpegiffbmpn]{0,5}$/', '', $post_title); // remove extension

    $attachment = array(
       'guid'           => $info['file'],
       'post_mime_type' => $info['mime-type'],
       'post_title'     => urldecode( $post_title ),
       'post_content'   => '',
       'post_status'    => 'inherit'
    );
    $attachement_id = wp_insert_attachment( $attachment );

    update_post_meta( $attachement_id, '_wp_attached_file', $info['file'] );

    $sizes = thumbrio_get_image_sizes();
    $metadata = array(
        'file'   => $info['file'],
        'width'  => $info['width'],
        'height' => $info['height'],
        'sizes'  => $image->multi_resize( $sizes )
    );
    wp_update_attachment_metadata($attachement_id, $metadata);
}

?>
