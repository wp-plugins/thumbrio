<?php
require_once (dirname(__FILE__) . '/auxiliary.php');

class Amazon_s3_thumbrio {
    public $path, $user_data, $pagination;
    const THUMBRIO_BUCKET_NAME = 'thumbrio';
    const AMAZON_URL = 'https://s3.amazonaws.com/';
    const MAX_KEYS = 20;

    function __construct($path, $page) {
        function set_path($path, $self) {
            if (!$self->user_data)
                return null;

            $new_path = '';
            if (!$path)
                $path = '';
            if ($self->user_data['amazon_bucket_name'] == Amazon_s3_thumbrio::THUMBRIO_BUCKET_NAME) {
                $new_path = 'uploads/' . $self->user_data['thumbrio_api_key'] . '/' . $path; 
            }
            return $new_path;
        }
        $this->path = null;
        if (!$page)
            $page = 0;

        $marker = json_decode(get_option('amazon_buffer_marks'));
        if (!count($marker)) {
            $marker = array('');
        }
        $this->pagination = array('page' => $page, 'markers' => $marker, 'end' => false);

        $this->user_data = array(
            'amazon_bucket_name' => get_option('amazon_s3_bucket_name'),
            'amazon_access_key' => get_option('amazon_s3_access_key'),
            'amazon_secret_key' => get_option('amazon_s3_secret_key'),
            'thumbrio_api_key' => get_option('thumbrio_api_key'),
            'thumbrio_secret_key' => get_option('thumbrio_secret_key'),
            'thumbrio_base_url' => get_option('thumbrio_base_url')
        );
        if ($this->user_data['amazon_bucket_name'] || $this->user_data['amazon_access_key'] ||
            $this->user_data['amazon_secret_key']) {
            $this->path = set_path($path, $this);
        } else {
            echo ('<h3><strong>Error:</strong> You must initialize a value for api_key, secret_key and ' .
                 'domain in Settings/Thumbr.io<h3>');
        }
    }
    function get_user_data ($data) {
        if ($data)
            return $this->user_data[$data];
        return $this->user_data;
    }
    function print_all_files($class_name, $size="100x100s", $pagination=false) {
        function set_pagination($pagination, $page, $next) {
            if ($pagination) {
                echo "<h3>Page " . ($page + 1) . "</h3>";
                $url = 'admin-post.php?action=Save_images2';
                if ($page > 0) {
                    echo "<a class='thumbrio-button' href=\"$url&page=" . ($page - 1) . "\">&larr;</a>\n";
                } else {
                    echo "<a class='thumbrio-button disabled' disabled=\"disabled\" href=\"#\">&larr;</a>\n";
                }
                if ($next) {
                    echo "<a class='thumbrio-button' href=\"$url&page=" . ($page + 1) . "\">&rarr;</a>\n";
                } else {
                    echo "<a class='thumbrio-button disabled' disabled=\"disabled\" href=\"#\">&rarr;</a>\n";
                }
            }
        }
        $urls = $this->get_all_urls($size);
        if ($urls == -1) {
            echo "<div class=\"$class_name\">\n
                 \t<p><strong>Warning</strong>: It is likely that your access/secret key is not valid or it could
                 be that you haven't got the necessary privileges.</p>\n
                 </div>";
            $urls = null;
        } else if (!$urls) {
            set_pagination($pagination, $this->pagination['page'], $this->pagination['end']);
            echo "<div class=\"$class_name\"><p>There is not any images</p></div>";
        } else {
            set_pagination($pagination, $this->pagination['page'], $this->pagination['end']);
            echo "<div class='$class_name'>\n";
            foreach ($urls as $url) {
                echo '<img src="' . $url['thumbrio'] . '" alt="' . $url['url'] . '" />' . "\n";
            }
            echo "</div>\n";
        }
    }
    function get_all_urls($size='x') {
        $obj = Amazon_s3_thumbrio::get_urls_and_save_them(
            $this->user_data['amazon_bucket_name'],
            $this->user_data['amazon_secret_key'],
            $this->user_data['amazon_access_key'],
            $this->path,
            $this->pagination['markers'][$this->pagination['page']],
            Amazon_s3_thumbrio::MAX_KEYS,
            $size);

        $this->pagination['end'] = $obj['end'];
        if ($obj['content'] <= 0 or !$obj['content'])
            return $obj['content'];
        if ($this->pagination['page'] == count($this->pagination['markers']) - 1) {
            if (!count($this->pagination['markers'])) {
                $this->pagination['markers'] = array("", $obj['next-marker']);    
            } else {
                array_push($this->pagination['markers'], $obj['next-marker']);    
            }
            update_option('amazon_buffer_marks', json_encode($this->pagination['markers']));
        }
        return $obj['content'];
    }
    static function get_urls_and_save_them($bucket_name, $amazon_secret_key, $amazon_access_key, $path, $marker, $max_keys, $size){
        $content = get_option('amazon-s3-urls');
        $make_http_get = true;
        $objs = array('contents' => array());

        if (strlen($content) > 0) {
            $objs = json_decode($content, true);
            $now = date('Y-m-d H:i:s');
            $diff = abs(strtotime((string)$now) - strtotime($objs['date']));
            if ($diff > (5 * 60)) {
                $make_http_get = true;
                $objs = array('contents' => array());
            } else {
                $make_http_get = false;
                $obj = null;
                for ($i = 0; $i < count($objs['contents']); $i++) {
                    if (strlen($marker) > 0 and $marker == $objs['contents'][$i]['next-marker']) {
                        if (count($objs['contents']) > ($i + 1)) {
                            $obj = $objs['contents'][$i + 1];    
                        } else {
                            $make_http_get = true;  
                        }
                        break;
                    } else if (strlen($marker) == 0) {
                        $obj = $objs['contents'][$i];
                        break;
                    }
                }
                if (!$obj) {
                    $make_http_get = true;      
                }
            }
        }
        if ($make_http_get) {
            $obj = Amazon_s3_thumbrio::get_urls_from_page(
                $bucket_name, $amazon_secret_key, $amazon_access_key, $path, $marker, $max_keys, $size
            );
            $objs['contents'][] = $obj;
        }
        $objs['date'] = (string)date('Y-m-d H:i:s');
        update_option('amazon-s3-urls', json_encode($objs));
        return $obj;
    }
    static function get_urls_from_page($bucket_name, $secret_key, $access_key, $prefix, $marker, $max_keys, $size) {
        function ends_with($str, $sub) {
            return (substr($str, strlen($str) - strlen($sub)) === $sub);
        }
        function gs_encodeSignature($s, $key) {
            $s = utf8_encode($s);
            $s = hash_hmac('sha1', $s, $key, true);
            $s = base64_encode($s);
            return $s;
        }
        function get_url_request($bucket_name, $secret_key, $access_key, $prefix, $marker){
            $today = gmdate("D, d M Y H:i:s") . ' +0000';
            $stringToSign = "GET\n\n\n\nx-amz-date:$today\n/$bucket_name/";
            $data = array(
                "url" => "https://$bucket_name.s3.amazonaws.com/?prefix=$prefix&marker=$marker",
                "data" => array(
                    "Authorization: AWS $access_key:" . gs_encodeSignature($stringToSign, $secret_key),
                    "x-amz-date: $today"
                )
            );
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $data['url']);  
            curl_setopt($ch, CURLOPT_HTTPHEADER, $data['data']); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $content = curl_exec($ch);
            $response = curl_getinfo($ch);
            curl_close ($ch);
            if ($response['http_code'] != 200)
                return null;
            return $content;
        }
        function get_thumbrio_url($url, $size) {
            preg_match('/[^\.\/]+\.[^\.\/]+$/', $url, $names);
            return Auxiliary::thumbrio($url, $size, $thumb_name=$names[0]);
        }

        $xml_content = get_url_request($bucket_name, $secret_key, $access_key, $prefix, $marker);
        if (!$xml_content)
            return array('content' => -1, 'mark' => null);

        $results = array();
        $i = 0;
        $xml_obj = new SimpleXMLElement($xml_content);
        $next_marker = '';
        $end = false;
        foreach ($xml_obj->Contents as $obj) {
            $object = array("Key" => $obj->Key[0], "date" => $obj->LastModified[0], "Size" => $obj->Size[0]);
            $next_marker = (string)$object['Key'];
            if ($object['Size'] > 0 and (
                ends_with($object['Key'], '.jpg') or ends_with($object['Key'], '.jpeg') or
                ends_with($object['Key'], '.png') or ends_with($object['Key'], '.gif') or
                ends_with($object['Key'], '.tiff') or ends_with($object['Key'], '.bmp'))) {
                if ($i >= $max_keys) {
                    $end = 'true';
                    break;
                }
                $url = Amazon_s3_thumbrio::AMAZON_URL . $bucket_name . '/' . $object['Key'];
                array_push($results, array('thumbrio' => get_thumbrio_url($url, $size), 'url' => $url));
                $i ++;
            }
        }
        return array('content' => $results, 'next-marker' => $next_marker, 'end' => $end);
    }
}
?>
