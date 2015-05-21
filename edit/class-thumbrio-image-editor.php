<?php
/**
 * A class to handle the image edition through Thumbr.io Web Service
 */
require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';

class Thumbrio_Image_Editor extends WP_Image_Editor_GD {
    public $file = null;
    public $size = null;
    public $mime_type = null;
    public $default_mime_type = 'image/jpeg';
    public $quality = false;
    public $default_quality = 100;
    // Thumbrio
    public $thumbrio_args   = array (
        'size' => array (
            'w'    => null,
            'h'    => null,
            'crop' => false,
            ),
        'rect' => array (
            'x' => null,
            'y' => null,
            'w' => null,
            'h' => null,
            ),
        'mirror' => false,
        'angle'  => 0,
        );
    public $file_origin = null;
    public $size_origin = null;

    /**
     * Each instance handles a single file.
     */
    public function __construct( $arg ) {
        if ( is_string( $arg ) ) {
            parent::__construct( $arg );
        } else {
            if ( $arg instanceof WP_Image_Editor_GD ) {
                $this->file      = $arg->file;
                $this->size      = $arg->size;
                $this->image     = $arg->image;
                $this->mime_type = $arg->mime_type;
            }
        }
        $this->get_thumbrio_args();
    }
    //TODO: Check for future versions
    public function __destruct() {
        if ( $this->image )
            @parent::__destruct();
        return true;
    }
    //TODO: Discuss how handle the local webdir.
    public static function test( $args = array() ) {
        // If origin is local the image are handled as usual.
        if ( thumbrio_is_webdir_local() )
            return false;
        return true;
    }
    // Put the thumbrio arguments in an array
    protected function thumbrio_query_args_to_array ( $queryArgs ) {
        $queryArgs = preg_replace('/^[?&|]/', '', $queryArgs);
        parse_str($queryArgs, $array_args);
        if (array_key_exists('size', $array_args)) {
            $values = explode('x', $array_args['size']);
            $crop = ( 'c' == substr($array_args['size'], -1) );
            $size = array(
                'w'    => (int) $values[0],
                'h'    => (int) $values[1],
                'crop' => $crop
            );
        } else {
            $size = array (
                'w'    => $this->size_origin['width'],
                'h'    => $this->size_origin['height'],
                'crop' => false
            );
        }
        $this->thumbrio_args['size'] = $size;
        if (array_key_exists('rect', $array_args)) {
            $values = explode(',', $array_args['rect']);
            $rect = array(
                'x' => (int) $values[0],
                'y' => (int) $values[1],
                'w' => (int) $values[2],
                'h' => (int) $values[3]
            );
        } else {
            if ( $this->size_origin['width'] )
                $w = $this->size_origin['width'];
            elseif ( $this->size['width'] )
                $w = $this->size['width'];
            else
                $w = $size['w'];
            if ( $this->size_origin['height'] )
                $h = $this->size_origin['height'];
            elseif ( $this->size['height'] )
                $h = $this->size['height'];
            else
                $h = $size['h'];
            $rect = array(
                'x' => 0,
                'y' => 0,
                'w' => $w,
                'h' => $h
            );
        }
        $this->thumbrio_args['rect'] = $rect;
        $this->thumbrio_args['mirror'] = array_key_exists('mirror', $array_args);
        if (array_key_exists('angle',  $array_args)) {
            $this->thumbrio_args['angle'] = (int) $array_args['angle'];
        } else {
            $this->thumbrio_args['angle'] = 0;
        }
    }
    public function load() {
        if ( !$this->image )
            return parent::load();
    }
    // Get from the file's url the original filename and the query arguments.
    protected function get_thumbrio_args () {

        $file_url = parse_url( $this->file );
        if ( isset( $file_url['query'] ) ) {
            $query =  $file_url['query'];
            $this->file_origin = substr( $this->file, 0, - (strlen( $query ) + 1));
            $long_size = @getimagesize( $this->file_origin );
            $this->size_origin = array (
                'width'  => $long_size[0],
                'height' => $long_size[1]
            );
        } else {
            $this->file_origin = $this->file;

            if ( $this->size ) {
                $this->size_origin = $this->size;
            } else {
                $long_size = @getimagesize( $this->file_origin );
                $this->size_origin = array (
                    'width'  => $long_size[0],
                    'height' => $long_size[1]
                );
            }
            $query = '';
        }
        $this->thumbrio_query_args_to_array( $query );
        return true;
    }
    // Compute the size of image from thumbr.io arguments
    protected function thumbrio_get_size ( $crop = false ) {
        if ( $crop ) {
            $width  = $this->thumbrio_args['size']['w'];
            $height = $this->thumbrio_args['size']['h'];
        } else {
            $width  = min($this->size_origin['width'],  $this->thumbrio_args['rect']['w']);
            $height = min($this->size_origin['height'], $this->thumbrio_args['rect']['h']);
            $factor = min(
                $this->thumbrio_args['size']['w'] / $width,
                $this->thumbrio_args['size']['h'] / $height
                );
            $width  = round($factor * $width);
            $height = round($factor * $height);
        }
        //TODO: Check carefully
        $this->thumbrio_args['size']['w'] = $width;
        $this->thumbrio_args['size']['h'] = $height;
        return $this->update_size( $width, $height );
    }
    protected function thumbrio_crop ( $x, $y, $w, $h, $dst_w = null, $dst_h = null ) {

        list($x, $y, $w, $h, $dst_w, $dst_h) = array( (int)$x, (int)$y, (int)$w, (int)$h, (int)$dst_w, (int)$dst_h);

        //// Interaction with angle
        $angle = $this->thumbrio_args['angle'];

        switch ( $angle ) {
            case 90:
                $aux = $x;
                $x = $this->size_origin['width'] - $y -$h;
                $y = $aux;
                $aux = $w;
                $w = $h;
                $h = $aux;
                break;
            case 180:
                $x = $this->size_origin['width']  - $x -$w;
                $y = $this->size_origin['height'] - $y -$h;
                break;
            case 270:
                $aux = $y;
                $y = $this->size_origin['height'] - $x -$w;
                $x = $aux;
                $aux = $w;
                $w = $h;
                $h = $aux;
        }
        ////  Interaction with mirror
        if ( $this->thumbrio_args['mirror'] ) {
            $x = $this->size_origin['width'] - $x - $w;
        }
        ////  Interaction with rectangle
        $x += $this->thumbrio_args['rect']['x'];
        $y += $this->thumbrio_args['rect']['y'];
        $this->thumbrio_args['rect'] = array (
            'x' => round($x), 'y' => round($y), 'w' => round($w), 'h' => round($h)
        );
        //// TODO: To check. IT COULD BE BAD SOLUTION
        //// This works fine if there is not previous a thumbrio size's argument that modifies
        //// the actual size
        $width  = $dst_w ? $dst_w : $w;
        $height = $dst_h ? $dst_h : $h;
        if ( $angle % 180 ){
            $aux = $width;
            $width = $height;
            $height = $aux;
        }
        $this->thumbrio_args['size']['w'] = $width;
        $this->thumbrio_args['size']['h'] = $height;
        if ( $this->size ) {
            $this->thumbrio_get_size();
        }
        return true;
    }
    // FIXME: It seems this function is deprecated
    protected function _thumbrio_rescale_crop( $src_x, $src_y, $src_w, $src_h ) {
        $w = $this->size['width'];
        $h = $this->size['height'];
        //if (! function_exists('_image_get_preview_ratio')) {
                $max   = max($w, $h);
                $scale = ($max > 400) ? (400 / $max) : 1;
        //} else {
        //    $scale = _image_get_preview_ratio( $w, $h );
        //}
        return array( $src_x * $scale,  $src_y * $scale, $src_w * $scale, $src_h * $scale);
    }
    public function crop( $src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false ) {
        // Discard preview scaling
        $this->thumbrio_crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h );
        if ( $this->image )
            return parent::crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs);
        return true;
    }
    protected function thumbrio_rotate ( $angle ) {
        //  To avoid a negative angle
        $angle += 360;
        $current_angle = $this->thumbrio_args['angle'];
        $this->thumbrio_args['angle'] = fmod($current_angle + $angle, 360);
        // Update size.
        if ( isset($this->size) && fmod($angle, 180) ) {
            if ( !$this->image ) //Otherwise the size was updated
                $this->update_size( $this->size['height'], $this->size['width'] );
        }
        return true;
    }
    public function rotate( $angle ) {
        if ( $this->image )
            parent::rotate( $angle );
        $this->thumbrio_rotate ( $angle );
        return true;
    }
    //TODO: Handler error from then GD Editor and the Image's destructor
    public function resize( $max_w, $max_h, $crop = false ) {
        // If there is a 'rotation' the dimensions must be swaped
        $do_swap = fmod( $this->thumbrio_args['angle'], 180 );

        $this->thumbrio_args['size']['w'] = $do_swap ? $max_h : $max_w;
        $this->thumbrio_args['size']['h'] = $do_swap ? $max_w : $max_h;
        $this->thumbrio_args['size']['crop'] = $crop;
        // Modify image for edition purposes. We follow parent::resize
        if ( ($this->image) && (($max_w < $this->size['width']) || ($max_h < $this->size['height'])) ) {
            $resized = parent::_resize( $max_w, $max_h, $crop );
            if ( is_resource( $resized ) ) {
                $this->image = $resized;
            } elseif ( is_wp_error( $resized ) )
                return $resized;
            //return new WP_Error( 'image_resize_error', __('Image resize failed.'), $this->file );
        } else
            $this->thumbrio_get_size( $crop );
        return true;
    }

    protected function thumbrio_flip ( $horz, $vert ) {
        if ( $horz && $vert ) {
            $this->thumbrio_rotate( 180 );
            return true;
        }
        if ( $vert ) {
            $this->thumbrio_args['angle']  = fmod( 3 * $this->thumbrio_args['angle'], 360);
            $this->thumbrio_args['mirror'] = !$this->thumbrio_args['mirror'];
        }
        if ( $horz ) {
            $this->thumbrio_rotate( 180 );
            $this->thumbrio_args['mirror'] = !$this->thumbrio_args['mirror'];
        }
        return true;
    }
    public function flip( $horz, $vert) {
        $this->thumbrio_flip ( $horz, $vert );
        if ( $this->image )
            return parent::flip( $horz, $vert );
        return true;
    }
    private function _filename_without_size_args () {
        $file = $this->generate_filename();
        return preg_replace('/size=[0-9]+x[0-9]+\|?/', '', $file);
    }
    public function multi_resize( $sizes ) {
        $metadata = array();
        $orig_size = $this->size_origin;
        foreach ( $sizes as $size => $size_data ) {
            if ( ! isset( $size_data['width'] ) && ! isset( $size_data['height'] ) ) {
                continue;
            }
            if ( ! isset( $size_data['width'] ) ) {
                $size_data['width'] = $this->size_origin['width'];
            }
            if ( ! isset( $size_data['height'] ) ) {
                $size_data['height'] = $this->size_origin['height'];
            }
            if ( ! isset( $size_data['crop'] ) ) {
                $size_data['crop'] = false;
            }
            // Remove the size thumbrio argument if it's present

            $filename = $this->_filename_without_size_args();

            //$this->_remove_size_args();
            //$image = new Thumbrio_Image_Editor ( $this );
            $image = new Thumbrio_Image_Editor ( $filename );
            // To avoid a crash in $this->resize the image is removed.
            $image->image = null;
            $image->resize( $size_data['width'], $size_data['height'], $size_data['crop'] );
            $filename  = $image->generate_short_filename();
            $metadata[$size] = array(
                'file'      => $filename,
                'width'     => $image->size['width'],
                'height'    => $image->size['height'],
                'crop'      => $size_data['crop'],
            );
            unset( $image );
        }
        return $metadata;
    }
    protected function _save ( $image, $filename = null, $mime_type = null ) {
        if ( ! $filename )
            $filename = $this->generate_filename( null, null, null );
        if ( ! $this->make_image( $filename, 'imagejpeg', array( $image, $filename ) ) )
                return new WP_Error( 'image_save_error', __('Image Editor Save Failed') );
        if (!$mime_type && !$this->mime_type) {
            $path_info = pathinfo($this->file_origin);
            $this->mime_type = $this->get_mime_type($path_info['extension']);
        }
        return array(
            'path'      => $filename,
            'file'      => wp_basename( apply_filters( 'image_make_intermediate_size', $filename ) ),
            'width'     => $this->size['width'],
            'height'    => $this->size['height'],
            'mime-type' => ($mime_type) ? $mime_type : $this->mime_type,
        );
    }
    public function save( $filename = null, $mime_type = null ) {
        $this->update_thumbrio_filename ();
        if (!$mime_type && !$this->mime_type) {
            $path_info = pathinfo( $this->file_origin );
            $this->mime_type = $this->get_mime_type($path_info['extension']);
        }
        $this->thumbrio_get_size();
        return array(
            'path'      => $this->file,
            'file'      => $this->file,
            'width'     => $this->size['width'],
            'height'    => $this->size['height'],
            'mime-type' => ($mime_type) ? $mime_type : $this->mime_type,
        );
    }
    protected function generate_short_filename ($use_pipe = true) {
        $file_url  = parse_url( $this->file_origin );
        $path_file = pathinfo($file_url['path']);
        $file      = $path_file['filename'] .'.'. $path_file['extension'];
        $str_args = $this->thumbrio_args_to_query( true, $use_pipe );
        $new_file = $file . (($str_args) ? '?' . $str_args : '');
        return $new_file;
    }
    public function _generate_filename ($use_pipe = true, $suffix = null, $dest_path = null, $extension = null) {
        $file     = $this->file_origin;
        $str_args = $this->thumbrio_args_to_query( true ); // USE PIPE to separate query arguments
        $new_file = $file . (($str_args) ? '?' . $str_args : '');
        return $new_file;
    }
    public function generate_filename( $suffix = null, $dest_path = null, $extension = null ) {
        return $this->_generate_filename( true, $suffix, $dest_path, $extension );
    }
    // Used to verify that there is an useful changement of size
    protected function propDiff ($a, $b) {
        if ($b != 0) {
            return abs(($a - $b) / $b);
        } elseif ($a = 0) {
            return 1;
        } else {
            return 1000; // artificial value. It isn't important
        }
    }
    // Get arguments of thumbrio and height and width and returns an array
    // with the args removing redundancy
    protected function thumbrio_simplify_thumbrio_args() {
        $array_args = $this->thumbrio_args;
        $width  = $this->size_origin['width'];
        $height = $this->size_origin['height'];
        $result = array();
        // Get rect if is not the trivial one
        if ( ($array_args['rect']['w']) && ($array_args['rect']['h']) ) {
            if (($array_args['rect']['x'] > 0) ||
                ($array_args['rect']['y'] > 0) ||
                ($this->propDiff($array_args['rect']['w'], $width) > 0.01) ||
                ($this->propDiff($array_args['rect']['h'], $height) > 0.01))   {
                    $result['rect'] = $array_args['rect'];
                    $width  = $array_args['rect']['w'];
                    $height = $array_args['rect']['h'];
                    foreach ( array_keys( $result['rect'] ) as $dim )
                        $result['rect'][$dim] = round( $result['rect'][$dim] );
            }
        }
        // Get size if is useful
        if (($array_args['size']['w']) && ($array_args['size']['h'])) {
            if (($this->propDiff($width,  $array_args['size']['w']) > 0.01) ||
                ($this->propDiff($height, $array_args['size']['h']) > 0.01)) {
                $result['size'] = $array_args['size'];
            }
        }
        if (array_key_exists('mirror', $array_args)) {
            $result['mirror'] = $array_args['mirror'];
        }
        if (array_key_exists('angle', $array_args)) {
            $result['angle'] = $array_args['angle'];
        }
        return $result;
    }
    public function thumbrio_args_to_query ( $simplify = false, $use_pipe = true ) {
        $array_args = ( $simplify )? $this->thumbrio_simplify_thumbrio_args() : $this->thumbrio_args;
        $separator  = $use_pipe ? '|' : '&';
        $args = '';
        if (isset($array_args['size']['w'])) {
            $args  = 'size=';
            $args .= $array_args['size']['w'];
            $args .= 'x' . $array_args['size']['h'];
            $args .= ($array_args['size']['crop']) ? 'c' : '';
        }
        if (isset($array_args['rect']['x'])) {
            $args .= ($args) ? $separator : '';
            $args .= 'rect=';
            $args .= $array_args['rect']['x']. ',';
            $args .= $array_args['rect']['y']. ',';
            $args .= $array_args['rect']['w']. ',';
            $args .= $array_args['rect']['h'];
        }
        if ($array_args['mirror']) {
            $args .= ($args) ? $separator : '';
            $args .= 'mirror=1';
        }
        if ($array_args['angle']) {
            $args .= ($args) ? $separator : '';
            $args .= 'angle=' . $array_args['angle'];
        }
        return $args;
    }
    public function update_thumbrio_filename () {
        $this->file = $this->generate_filename();
    }
    // This function is called by save method. It controls the 'saving part'
    protected function make_image( $filename, $function, $arguments ) {
        return true;
    }
    protected function update_size ( $width = false, $height = false ) {
        parent::update_size( $width, $height );
        if ( !$this->size_origin ) {
            $this->size_origin = $this->size;
        }
    }
}