<?php
class Auxiliary {
    public static function update_url($filename, $history) {
        $info = Auxiliary::parser_url($filename);
        if (! $info)
            return $filename;

        $history = Auxiliary::update_arguments(
            $history, $info['query_arguments']
        );
        return Auxiliary::thumbrio(
            urldecode($info['url']),
            urldecode($info['size']),
            urldecode($info['seo_name']),
            Auxiliary::stringify_query_arguments($history)
        );
    }
    public static function get_base_image_url($filename) {
        $info = Auxiliary::parser_url($filename);
        return Auxiliary::thumbrio(
            urldecode($info['size']),
            urldecode('x'),
            urldecode('foo.png')
        );
    }
    public static function insert_images($filenames) {
        if ($filenames && count($filenames) > 0) {
            $count = Auxiliary::get_count_posts();
            $offset = 0;
            foreach($filenames as $filename) {
                preg_match('/[^\.\/]+\.[^\.\/]+$/', $filename, $names);
                $thumbnail = Auxiliary::thumbrio(
                    $filename, 'x', $names[0], 'thumbrit-apply=wp-' . ($count + $offset)
                );
                Auxiliary::insert_image($thumbnail);
                $offset ++;
            }
        }
    }
    public static function thumbrio($url, $size, $thumb_name='thumb.png',
                                    $query_arguments=NULL, $base_url=NULL) {
        if (!$base_url) {
            $base_url = get_option('thumbrio_base_url');
        }
        if (substr($url, 0, 7) === 'http://') {
            $url = substr($url, 7);
        }
        $encoded_url = Auxiliary::_thumbrio_urlencode($url);
        $encoded_size = Auxiliary::_thumbrio_urlencode($size);
        $encoded_thumb_name = Auxiliary::_thumbrio_urlencode($thumb_name);
        $path = "$encoded_url/$encoded_size/$encoded_thumb_name";
        if ($query_arguments) {
            $path .= "?$query_arguments";
        }
        // We should add the API to the URL when we use the non customized
        // thumbr.io domains
        if ($base_url == get_option('thumbrio_base_url')) {
            $path = get_option('thumbrio_api_key') . "/$path";
        }
        // some bots (msnbot-media) "fix" the url changing // by /, so even if
        // it's legal it's troublesome to use // in a URL.
        $path = str_replace('//', '%2F%2F', $path);
        $token = hash_hmac('md5', $base_url . $path, get_option('thumbrio_secret_key'));
        return "$base_url$token/$path";
    }
    public static function parser_url($filename) {
        // Remove Query Arguments
        preg_match('/\?.+$/', $filename, $result);
        $query_arguments = array();
        if ($result) {
            $query_arguments = Auxiliary::parse_query_arguments($result[0]);
            $filename = substr($filename, 0, strlen($filename) - strlen($result[0]));
        }
        preg_match('/^https?\:\/\/[^\/]+/', $filename, $result);
        $base_url = $result[0];
        $filename = substr($filename, strlen($result[0]) + 1);

        preg_match('/^[^\/]+/', $filename, $result);
        $hmac_token = $result[0];
        $filename = substr($filename, strlen($result[0]) + 1);

        preg_match('/^[^\/]+/', $filename, $result);
        $api_key = $result[0];
        $filename = substr($filename, strlen($result[0]) + 1);

        preg_match('/[^\/\.]+\.[^\.\/]+$/', $filename, $result);
        $seo_name = $result[0];
        $filename = substr($filename, 0, strlen($filename) - strlen($result[0]) - 1);

        preg_match('/[^\/]+$/', $filename, $result);
        $size = $result[0];
        $url = substr($filename, 0, strlen($filename) - strlen($result[0]) - 1);
        $url = str_replace('%3A', ':', $url);
        $url = str_replace('%2F', '/', $url);

        return array(
            'base_url' => $base_url,
            'api_key' => $api_key,
            'url' => $url,
            'size' => $size,
            'seo_name' => $seo_name,
            'query_arguments' => $query_arguments
        );
    }
    public static function get_new_rect($history, $size) {
        $angle = 0;
        $mirror = false;
        if (array_key_exists('rect', $history)) {
            $rect = $history['rect']; 
        } else {
            return $history;
        }
        if (array_key_exists('angle', $history)) {
            $angle = $history['angle'] % 360;    
        }
        if (array_key_exists('mirror', $history)) {
            $mirror = $history['mirror'];    
        }
        if ($angle == 90) {
            $history['rect'] = array(
                'left' => $size['width'] - $rect['height'] - $rect['top'],
                'top' => $rect['left'],
                'width' => $rect['height'],
                'height' => $rect['width']
            );
        } else if ($angle == 180) {
            $history['rect'] = array(
                'left' => $size['width'] - $rect['width'] - $rect['left'],
                'top' => $size['height'] - $rect['height'] - $rect['top'],
                'width' => $rect['width'],
                'height' => $rect['height']
            );
        } else if ($angle == 270) {
            $history['rect'] = array(
                'left' => $rect['top'],
                'top' => $size['width'] - $rect['width'] - $rect['left'],
                'width' => $rect['height'],
                'height' => $rect['width']
            );
        }
        $history['rect'] = array(
            'left' => max(0, $history['rect']['left']),
            'top' => max(0, $history['rect']['top']),
            'width' => max(0, $rect['height']),
            'height' => max(0, $rect['width'])
        );
        if ($mirror){
            $history['rect']['left'] = max(0, $size['width'] - $history['rect']['left']);
        }
        return $history;
    }
    private static function stringify_query_arguments($dict) {
        $x = '';
        $keys = array();
        foreach ($dict as $key => $value) {
            if (in_array($key, $keys) ||
                ($key == 'size' && $value == 'x') ||
                ($key == 'angle' && $value == 0) ||
                ($key == 'mirror' && $value == 0))
                continue;
            $x = $x . '&' . $key . '=';
            if ($key == 'rect') { 
                foreach ($value as $key_rect => $value_rect) {
                    if ($value_rect == 0)
                        $value_rect = '0';
                    $x = $x . $value_rect . ',' ;
                }
                $x = substr($x, 0, strlen($x) - 1);
            } else {
                $x = $x . $value;   
            }
            array_push($keys, $key);
        }
        if (strlen($x) > 0) {
            $x = substr($x, 1);
        }
        return $x;
    }
    private static function update_arguments($dict, $args) {
        if (!$args) {
            return $dict;
        }
        foreach ($args as $key => $value) {
            if ($key == 'mirror') {
                if (!array_key_exists($key, $dict)) {
                    $dict['mirror'] = $value ? 0 : 1;
                } else {
                    $dict['mirror'] = ($value == $dict['mirror'])? 0 : 1;    
                }
            } else if ($key == 'angle') {
                if (!array_key_exists($key, $dict)) {
                    $dict['angle'] = intval($value, 10) % 360;
                } else {
                    $dict['angle'] = (intval($dict['angle'], 10) + $value) % 360;
                }
            } else if ($key == 'rect') {
                preg_match('/([^,]+),([^,]+),([^,]+),([^,]+)/' , $value, $values);
                if (!array_key_exists($key, $dict)) {
                    $dict['rect'] = array(
                        'left' => $values[1],
                        'top' => $values[2],
                        'width' => $values[3],
                        'height' => $values[4]
                    );
                } else {
                    $dict['rect'] = array(
                        'left' => $values[1] + $dict['rect']['left'],
                        'top' => $values[2] + $dict['rect']['top'],
                        'width' => $dict['rect']['width'],
                        'height' => $dict['rect']['height']
                    );
                }
            } else {
                $dict[$key] = $value;
            }
        }
        return $dict;
    }
    private static function parse_query_arguments($str) {
        if (strlen($str) <= 0 || $str[0] != '?') {
            return array();
        }
        $str = substr($str, 1);
        $query_arguments = array();
        while (strlen($str) > 0) {
            preg_match('/^[^\=]+/', $str, $result);
            if (!$result)
                return $query_arguments;
            $key = $result[0];
            $str = substr($str, strlen($result[0]) + 1);

            preg_match('/^[^\&]+/', $str, $result);
            if (!$result)
                return $query_arguments;
            $value = $result[0];
            $str = substr($str, strlen($result[0]) + 1);

            if ($key && $value) {
                $query_arguments[$key] = $value;
            }
        }
        return $query_arguments;
    }
    private static function insert_image($filename) {
        preg_match('/\?[^\?]+$/', $filename, $result);
        $new_filename = $filename;
        if ($result)
            $new_filename = substr($filename, 0, strlen($filename) - strlen($result[0]));

        $wp_filetype = wp_check_filetype(basename($new_filename), null);
        $attachment = array(
           'guid' => $filename, 
           'post_mime_type' => $wp_filetype['type'],
           'post_title' => preg_replace('/\.[^.]+$/', '', basename($new_filename)),
           'post_content' => '',
           'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $filename);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
        wp_update_attachment_metadata($attach_id, $attach_data);
    }
    private static function _thumbrio_urlencode($str) {
        $length = strlen($str);
        $encoded = '';
        for ($i = 0; $i < $length; $i++) {
            $c = $str[$i];
            if (($c >= 'a' && $c <= 'z') ||
                ($c >= 'A' && $c <= 'Z') ||
                ($c >= '0' && $c <= '9') ||
                $c == '/' || $c == '-' || $c == '_' || $c == '.')
                $encoded .= $c;
            else
                $encoded .= '%' . strtoupper(bin2hex($c));
        }
        return $encoded;
    }
    private static function get_count_posts() {
        $data = wp_count_attachments();
        settype($data, "array");
        $number = 0;
        foreach ($data as $key => $value)
            $number += $value;
        return $number;
    }
}

?>