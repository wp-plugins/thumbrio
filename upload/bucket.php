<?php

class BucketException extends Exception {}

class Bucket {
    function __construct() {
        $this->domain      = get_option(OPTION_SUBDOMAIN);
        $this->webdir      = rtrim(get_option(OPTION_WEBDIR), '/') . '/';
        $this->private_key = get_option(OPTION_AMAZON_PRKEY);
        $this->public_key  = get_option(OPTION_AMAZON_PUKEY);
        $this->region      = get_option(OPTION_AMAZON_REGION);
        $this->bucket_name = ($this->webdir == '/' ? '' : $this->get_bucket());
        $this->prefix      = ($this->webdir == '/' ? '' : $this->get_prefix());
        $this->pagination  = array('content' => array(), 'next-marker' => '', 'end' => false);
    }

    public static function populate_construct($bucket_name, $region, $public_key, $private_key, $path) {
        $buckect_instance = new Bucket;
        $buckect_instance->webdir      = "https://$bucket_name.s3.amazonaws.com/$path";
        $buckect_instance->private_key = $private_key;
        $buckect_instance->public_key  = $public_key;
        $buckect_instance->bucket_name = $bucket_name;
        $buckect_instance->prefix      = $path;
        $buckect_instance->region      = $region;
        return $buckect_instance;
    }

    /**
     * Checks if the amazon_credentials are correct listing a first element in buckect.
     * By the way gets the good region
     * return a boolean
     */
    function check_amazon_credentials_and_get_region() {
        $region = 'us-east-1';
        $canonical_query   = "max-keys=1&prefix=$prefix";
        $canonical_headers = array('host' => "$this->bucket_name.s3.amazonaws.com");

        $headers = $this->authorization_headers('GET', '/', $canonical_query, $canonical_headers, '', gmdate("Ymd\THis\Z"), $region, 's3');
        $url = "https://". $canonical_headers['host'] ."/?". $canonical_query;
        $response = Bucket::make_http_request($url, 'GET', $headers, null);
        $OK = ((preg_match('<ListBucketResult>', $response) == 1) ? 1 : 0);
        if ($OK) {
            return array('OK' => $OK, 'region' => $region);
        } else {
            preg_match('/AuthorizationHeaderMalformed.+the region \'us-east-1\' is wrong; expecting.+<Region>([a-z-0-9]+)<\/Region>/',
                    $response, $matches);
            $new_region = $matches[1];
        }

        $headers = $this->authorization_headers('GET', '/', $canonical_query, $canonical_headers, '', gmdate("Ymd\THis\Z"), $new_region, 's3');
        $response = Bucket::make_http_request($url, 'GET', $headers, null);
        $OK = ((preg_match('<ListBucketResult>', $response) == 1) ? 1 : 0);
        if ($OK) {
            return array('OK' => $OK, 'region' => $new_region);
        } else {
            return array('OK' => 0,   'region' => '');
        }
    }

    /**
     * get_list_v4
     *
     * Connect to amazon and make a ls in order to get all the filenames of your bucket.
     *
     * @param (str) ($marker) The last filename we got in the last call to this function.
     * @throws (BucketException) Fail internet conexion or the information about the bucket is incorrect.
     * @return (array) All the images of the bucket
     */
    function get_list_v4($marker){

        $date_time         = gmdate("Ymd\THis\Z");
        $http_method       = 'GET';
        $canonical_uri     = '/';  //urlencode the URI doesn't work
        $canonical_query   = $this->build_canonical_query(array('marker' => $marker, 'prefix' => $this->prefix));
        $canonical_headers = array('host' => "$this->bucket_name.s3.amazonaws.com");
        $payload           = '';
        $service           = 's3';

        $headers = $this->authorization_headers(
            $http_method,
            $canonical_uri,
            $canonical_query,
            $canonical_headers,
            $payload,
            $date_time,
            $this->region,
            $service
        );

        $url = "https://$this->bucket_name.s3.amazonaws.com$canonical_uri?$canonical_query";
        $response = Bucket::make_http_request($url, 'GET', $headers, null);

        $xml_content = substr($response, strrpos($response, '<?xml version'));
        return $xml_content;
    }

    /**
     * Generate the policy and corresponding signature V4 used in the Post Object Request (function post_object_V4)
     */
    private function generate_policy_and_signature_v4($key, $content_type, $min_size, $max_size, $acl) {
        date_default_timezone_set('Europe/Berlin');
        $expiration_date = date('Y-m-d\TH:i:s\Z');
        $date_time   = gmdate("Ymd\THis\Z");
        $date        = substr($date_time, 0, 8);

        $x_amz_credential = "$this->public_key/$date/$this->region/s3/aws4_request";

        $policy_document = array(
            'expiration' => $expiration_date,
            'conditions' => array(
                array('bucket' => $this->bucket_name),
                array('starts-with', '$key', $key),
                array('starts-with', '$Content-Type', 'image/'),
                array('content-length-range', $min_size, $max_size),
                array('acl' => $acl),
                array('x-amz-credential'=> $x_amz_credential),
                array('x-amz-algorithm'=> 'AWS4-HMAC-SHA256'),
                array('x-amz-date'=> $date_time )
            )
        );

        $policy_json = json_encode($policy_document);
        $policy_b64  = base64_encode($policy_json);
        $date_time   = gmdate("Ymd\THis\Z");
        $date        = substr($date_time, 0, 8);
        $signing_key = $this->signing_key($this->private_key, $date, $this->region, 's3');
        $signature   = $this->signature($policy_b64, $signing_key);
        return array('policy' => $policy_b64, 'signature' => $signature, 'x-amz-credential' => $x_amz_credential);
    }

    /**
     * Post an image to amazon s3 bucket.
     *
     * @param (File) (file) The image file.
     * @param (str) (key) The name of the file.
     * @param (str) (content_type) The type of the file. For example: image/jpg, image/png or image/gif.
     * @param (int) (max_size) The size of the image.
     */
    function post_object_V4 ($filepath, $name, $size, $content_type, $date_time)  {
        $key = $this->prefix . $name;

        $acl = 'public-read'; // 'private' //////////////////////////////////// TESTING
        $policy_and_signature = $this->generate_policy_and_signature_v4($key, $content_type, 0, $size, $acl);

        $formdata = array(
            'acl'              => $acl,
            'Content-Type'     => $content_type,
            'key'              => $key,
            'Policy'           => $policy_and_signature['policy'],
            'x-amz-algorithm'  => 'AWS4-HMAC-SHA256',
            'x-amz-credential' => $policy_and_signature['x-amz-credential'],
            'x-amz-date'       => $date_time,
            'X-Amz-Signature'  => $policy_and_signature['signature'],
            'file'             => ("@$filepath"),
        );

        $headers = array("Content-Type:multipart/form-data");
        $response = Bucket::make_http_request("https://$this->bucket_name.s3.amazonaws.com", 'POST', $headers, $formdata);

        //Checks for a 2XX response (meaning OK)
        if (preg_match_all('/HTTP\/1.1 2[0-9][0-9]/', $response)) {
            return array('OK'=> true, 'response' => $response);
        } else {
            return array('OK'=> false, 'response' => 'There is an unknown error');
        }
    }


    /**
     * Put a copy of object in S3
     * @param (url) The url of file
     *
     */
    function copy_object_V4 ( $filename, $source_file ) {

        $acl           = "public-read";
        $host          = "$this->bucket_name.s3.amazonaws.com";
        $canonical_uri = "/$this->prefix$filename";
        $canonical_headers = array (
            'host'              => $host,
            'x-amz-copy-source' => "/$this->bucket_name/$this->prefix$source_file",
            'x-amz-acl'         => $acl,
        );

        $headers = $this->authorization_headers('PUT', $canonical_uri, '', $canonical_headers, '', gmdate("Ymd\THis\Z"), $this->region, 's3');
        $url = "https://$this->bucket_name.s3.amazonaws.com$canonical_uri";

        $response = Bucket::make_http_request($url, "PUT", $headers, null);

        //FIXME: Checks for a 2XX response (meaning OK)
        if (($response['http_code'] === 200) && isset($response['url'])) {
            // We need to ask again for the response
            // (see .http://docs.aws.amazon.com/AmazonS3/latest/API/RESTObjectCOPY.html in description)
            return array('OK'=> true, 'response' => $response);
        } else {
            return array('OK'=> false, 'response' => 'There is an unknown error');
        }
    }

    /**
     * Get the size of a file in the bucket.
     * This information is needed to delete the file (function delete_object_v4)
     */
    private function get_size($filename) {
        $response = $this->get_list_v4("$filename");
        $patron = '<Key>' . str_replace('/', '\/', $this->prefix . $filename) . '<\/Key><LastModified>[^><]+<\/LastModified><ETag>[^><]+<\/ETag><Size>([0-9]+)<\/Size>';
        preg_match('/'.$patron.'/', $response, $matches);
        if (count($matches) > 1) {
            $size = $matches[1];
        } else {
            $size = '0';
        };
        return $size;
    }

    // FIXME: Temporal desabled deletion
    /**
    * Remove a file from the bucket of amazon. Uses signature Version 4.
    *
    * @param  (str) ($image_url) The filename if we want to delete a file with this shape
    *                           http://<domain>/<path-to-filename>
    * @throws (BucketException) Fail if we cannot delete the filename
    */
    function delete_object_v4 ($image_url) {
        preg_match("/" . str_replace('/', '\/', $this->domain) . "\/(.+)/", $image_url, $matches);
        if (count($matches) > 1) {
            $filename = $matches[1];
        } else {
            throw new BucketException("We cannot delete this file $filename. Its format is not valid.", 1);
        }
        $host          = "$this->bucket_name.s3.amazonaws.com";
        $size          = $this->get_size($filename);
        $canonical_uri = "/$this->prefix$filename";
        $canonical_headers = array ( 'host' => $host, 'content-length' => $size );

        $headers = $this->authorization_headers('DELETE', $canonical_uri, '', $canonical_headers, '', gmdate("Ymd\THis\Z"), $this->region, 's3');
        $url = "https://$this->bucket_name.s3.amazonaws.com$canonical_uri";

        //FIXME TEMPORAL DISABLE DELETE FOR TESTING
        //$response = Bucket::make_http_request($url, "DELETE", $headers, null);
        $response = false;
        return $response;
    }

    function get_thumbrio_urls($urls, $size='x') {
        $result = array();
        $i = 0;
        foreach ($urls as $url) {
            $new_url = str_replace($this->webdir, '', $url);
            $thumbrio_url = $this->thumbrio_wo_signed($new_url, $size);
            array_push($result, $thumbrio_url);
            $i++;
        }
        return $result;
    }

    /**
     * synchronize
     *
     * Connect to amazon and make a ls in order to get all the filenames of your bucket.
     *
     * @throws (BucketException) Fail the function get_list_v4 and read_xml_file
     * @return (array) All the images of the bucket
     */
    function synchronize() {
        $db_images = WPFunctions::get_all_images_db();

        $xml_content = $this->get_list_v4('');
        $this->read_xml_file($xml_content, 'x', $db_images);

        if ($this->pagination['end'] && count($this->pagination['content']) > 0) {
            foreach ($this->pagination['content'] as $url) {
                $url = 'http://' . $url;
                //WPFunctions::insert_image( $url );
                thumbrio_insert_image( $url );
            }
        }
        return $this->pagination['content'];
    }

    private function thumbrio_wo_signed($url, $size) {
        $path = WPFunctions::thumbrio_urlencode($url);
        if ($size and $size != 'x') {
            $path .= "?size=$size";
        }
        $path = str_replace('//', '%2F%2F', $path);
        return "$this->domain/$path";
    }

    private function get_bucket() {
        preg_match('/^https\:\/\/(.+)\.s3\.amazonaws\.com/', "$this->webdir", $matches);
        if (count($matches) > 1) {
            return $matches[1];
        } else {
            throw new BucketException("Your webdir $this->webdir is not a valid webdir of amazon.");
        }
    }

    private function get_prefix() {
        preg_match('/^https\:\/\/.+\.s3\.amazonaws\.com\/(.*)/', "$this->webdir", $matches);
        if (count($matches) > 1) {
            return $matches[1];
        } else {
            throw new BucketException("Your webdir $this->webdir is not a valid webdir of amazon.");
        }
    }

    /**
     * read_xml_file
     *
     * Read a xml_file to get all the filenames.
     *
     * @param (str) ($xml_content) The xml file content
     * @param (str) ($size) The size of the images
     * @param (array) ($exceptions) The images we have to drop.
     * @throws (BucketException) Fail the function get_list_v4
     * @return (array) All the images of the bucket
     */
    private function read_xml_file($xml_content, $size, $exceptions) {
        if (!$xml_content)
            return 0;

        $this->get_all_urls($xml_content, $size, $exceptions);

        while (!$this->pagination['end']) {
            $xml_content = $this->get_list_v4($this->pagination['next-marker']);
            $this->get_all_urls($xml_content, $size, $exceptions);
        }
        return 1;
    }

    /* FIXME: name function is misleading
     * Update the attribute 'pagination' after reading the $xml_content (xml file)
     *
     * It only takes images droping the repeated ones
     */
    private function get_all_urls($xml_content, $size, $exceptions){
        function remove_prefix($url, $prefix) {
            $patron = str_replace('/', '\/', $prefix);
            return preg_replace('/^'. $patron . '/', '', $url);
        }
        function ends_with($str, $sub) {
            return (substr($str, strlen($str) - strlen($sub)) === $sub);
        }
        function content_type_correct($filename) {
            $ok = false;
            foreach (array('.jpg', '.jpeg', '.gif', '.png', '.tiff', '.bmp') as $content_type) {
                if (ends_with(strtolower($filename), $content_type)) {
                    $ok = true;
                    break;
                }
            }
            return $ok;
        }
        function is_repeated($url, $values) {
            $ok = false;
            if (count($values) > 0) {
                foreach ($values as $value) {
                    if (strpos($value, $url) === 0) {
                        $ok = true;
                        break;
                    }
                }
            }
            return $ok;
        }
        function code_url($url) {
            preg_match('/^(.*\/){0,1}([^\/]+)/', $url, $matches);
            $code_url = $matches[1] . rawurlencode($matches[2]);
            return $code_url;
        }

        $i = 0;
        $xml_obj = new SimpleXMLElement($xml_content);
        foreach ($xml_obj->Contents as $obj) {
            $object = array("Key" => $obj->Key[0], "date" => $obj->LastModified[0], "Size" => $obj->Size[0]);

            $this->pagination['next_marker'] = (string)$object['Key'];

            if ($object['Size'] > 0 and content_type_correct($object['Key'])){

                $url = remove_prefix($object['Key'], $this->prefix);

                if (! is_repeated("http://$this->domain/" . code_url($url), $exceptions)) {

                    $thumbrio_url = $this->thumbrio_wo_signed($url, $size);
                    array_push($this->pagination['content'], $thumbrio_url);
                }
                $i ++;
            }
        }
        if ($i < $xml_obj->MaxKeys) {
            $this->pagination['end'] = true;
        }
    }

    private function build_canonical_query($canonical_query_array) {
        //Build canonical query
        ksort($canonical_query_array);
        $canonical_query = '';
        foreach ($canonical_query_array as $key => $value) {
            $canonical_query .= urlencode($key) . '=' . urlencode($value) . '&';
        }
        return substr($canonical_query, 0, -1);
    }
    /**
     * The following functions are used to build the Amazon S3 signature V4
     */
    private function str2Hex($str) {
        $res = '';
        $str_long = str_split($str);
        foreach ($str_long as $value) {
            $res .= str_pad( dechex(ord($value)), 2, "0" , STR_PAD_LEFT);
        }
        return $res;
    }
    private function canonical_request (
        $http_method,
        $canonical_uri,
        $canonical_query,
        $canonical_headers,
        $payload,
        $date_time) {

        if ($payload) {
            $payload_signature = hash_file('sha256', $payload);
        } else {
            $payload_signature = hash('sha256', '');
        }

        $x_amz_array = array(
            'x-amz-content-sha256' => $payload_signature,
            'x-amz-date' => $date_time
        );
        $key_headers = array_merge(array_keys($canonical_headers), array_keys($x_amz_array));
        sort($key_headers);
        $canonical_headers = array_merge($canonical_headers, $x_amz_array);

        //BUILD CANONICAL REQUEST
        $canonical_request = "$http_method\n" .
                             "$canonical_uri\n".
                             "$canonical_query\n";

        $not_used_keys = array('content-type');

        $signed_headers = '';
        foreach ($key_headers as $key) {
            $canonical_request .= "$key:$canonical_headers[$key]\n";
            if ($key != 'content-type') {
                $signed_headers .= "$key;";
            }
        }


        $signed_headers = substr($signed_headers, 0, -1);

        $canonical_request .= "\n$signed_headers\n";
        $canonical_request .= $payload_signature;

        return array(
            'request'           => $canonical_request,
            'headers'           => $signed_headers,
            'payload_signature' => $payload_signature);
    }
    private function string_to_sign ($time_stamp, $region, $service, $canonical_request) {
        $date_time = (isset($time_stamp) ? $time_stamp : gmdate("Ymd\THis\Z"));
        $date      = substr($time_stamp, 0, 8);
        $signed_canonical_request = hash('sha256', utf8_encode($canonical_request));
        $stringToSign = "AWS4-HMAC-SHA256\n" .
                        "$date_time\n" .
                        "$date/$region/$service/aws4_request\n" .
                        "$signed_canonical_request";
        return $stringToSign;
    }
    private function signing_key($private_key, $date, $region, $service) {
        $DateKey              = hash_hmac('sha256', $date,          "AWS4$private_key", true);
        $DateRegionKey        = hash_hmac('sha256', $region,        $DateKey, true);
        $DateRegionServiceKey = hash_hmac('sha256', $service,       $DateRegionKey, true);
        $SigningKey           = hash_hmac('sha256', "aws4_request", $DateRegionServiceKey, true);
        return $SigningKey;
    }
    private function signature($stringToSign, $SigningKey) {
        return $this->str2Hex(hash_hmac('sha256', utf8_encode($stringToSign), $SigningKey, true));
    }
    private function authorization($public_key, $date, $region, $service, $headers, $signature) {
        return "AWS4-HMAC-SHA256 " .
             "Credential=$public_key/$date/$region/$service/aws4_request," .
             "SignedHeaders=" . $headers . "," .
             "Signature=$signature";
    }
    private function authorization_headers($http_method, $canonical_uri, $canonical_query, $canonical_headers, $payload, $date_time, $region, $service) {

        $date = substr($date_time, 0, 8);

        $response = $this->canonical_request ($http_method, $canonical_uri, $canonical_query, $canonical_headers,
            $payload, $date_time);

        $string_to_sign = $this->string_to_sign($date_time, $region, $service, $response['request']);
        $signing_key    = $this->signing_key($this->private_key, $date, $region, $service);
        $signature      = $this->signature($string_to_sign, $signing_key);

        $authorization  = $this->authorization($this->public_key, $date, $region, $service,
            $response['headers'], $signature);

        $headers = array(
            "Host: ". $canonical_headers['host'],
            "Authorization: ". $authorization,
            "x-amz-date: " . $date_time,
            "x-amz-content-sha256:". $response['payload_signature']
            );
        foreach ($canonical_headers as $key => $value) {
            if (strtolower($key) != 'host') {
                array_push($headers, "$key:$value");
            }
        }
        return $headers;
    }

    /**
     * Make the http request
     */
    static function make_http_request($url, $method, $headers, $formdata) {

        //$formdata = http_build_query($formdata);
        $ch = curl_init();
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } else if ($method != 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "$method");
        }

        if ($formdata && ($method == 'POST' || $method == 'PUT')) {
            // TODO: Use CURLFile to upload this file. The @filename API is deprecated.
            @curl_setopt($ch, CURLOPT_POSTFIELDS, $formdata);
        }

        if ($headers) {
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);

        $content  = curl_exec($ch);
        $error    = curl_error($ch);
        $response = curl_getinfo($ch);
        curl_close ($ch);

        if ($error != '') {
            throw new BucketException("$error");
        }

        //Detecting a particular error. This error permits to obtain the good region
        $region_error = preg_match('/AuthorizationHeaderMalformed.+the region \'us-east-1\' is wrong; expecting.+<Region>([a-z-0-9]+)<\/Region>/',
            $content);

        if ($response['http_code'] >= 400 && !$region_error) {
            throw new BucketException("There was an error when we tried to $method to $url");
        }
        if ($method == 'PUT') {
            return $response;
        }
        return $content;
    }
}

class WPFunctions {
    public static function get_all_images_db() {
        $query_images_args = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            );
        $query_images = new WP_Query($query_images_args);

        $images = array();
        foreach ($query_images->posts as $attach_post) {
                $mime_type = get_post_mime_type($attach_post->ID);
                    if (preg_match('/^image\//', $mime_type)) {
                        $images[] = wp_get_attachment_url($attach_post->ID);
                    }
                }
        return $images;
    }

    public static function thumbrio_urlencode($str) {
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
}



?>
