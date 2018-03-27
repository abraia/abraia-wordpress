<?php

define('API_URL', 'https://abraia.me/api');

class Client {
    private static $api_key = NULL;
    private static $api_secret = NUll;

    var $url = '';
    var $params = array();

    function __construct() {
    }

    public static function set_keys($api_key, $api_secret) {
        self::$api_key = $api_key;
        self::$api_secret = $api_secret;
    }

    private function list_files($url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERPWD, self::$api_key.':'.self::$api_secret);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return array($statusCode, $resp);
    }

    private function remove_file($url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERPWD, self::$api_key.':'.self::$api_secret);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return array($statusCode, $resp);
    }

    private function upload_file($url, $filename) {
        $curl = curl_init($url);
        $postData = array('file' => curl_file_create($filename, '', basename($filename)));
        curl_setopt_array($curl, array(
            CURLOPT_USERPWD => self::$api_key.':'.self::$api_secret,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $postData,
        ));
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return array($statusCode, $resp);
    }

    private function download_file($url) {
        $curl = curl_init ($url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERPWD, self::$api_key.':'.self::$api_secret);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close ($curl);
        return array($statusCode, $resp);
    }

    function files() {
        $url = API_URL.'/images';
        list($status, $resp) = $this->list_files($url);
        if ($status != 200)
            throw new APIError('GET ' . $url . ' ' . $status);
        return $resp;
    }

    function from_file($filename) {
        $url = API_URL.'/images';
        list($status, $resp) = $this->upload_file($url, $filename);
        if ($status != 201)
            throw new APIError('POST ' . $url . ' ' . $status);
        $json = json_decode($resp, true);
        $this->url = $url.'/'.$json['filename'];
        $this->params['q'] = 'auto';
        return $this;
    }

    function from_url($url) {
        $this->url = API_URL.'/images';
        $this->params['url'] = $url;
        $this->params['q'] = 'auto';
        return $this;
    }

    function to_file($filename) {
        $url = $this->url.'?'.http_build_query($this->params);
        list($status, $resp) = $this->download_file($url);
        if ($status != 200)
            throw new APIError('GET ' . $url . ' ' . $status);
        $fp = fopen($filename, 'w');
        fwrite($fp, $resp);
        fclose($fp);
        return $this;
    }

    function resize($width=null, $height=null, $mode='auto') {
        if (!is_null($width)) $this->params['w'] = $width;
        if (!is_null($height)) $this->params['h'] = $height;
        $this->params['m'] = $mode;
        return $this;
    }

    function delete($filename) {
        $url = API_URL.'/images/'.$filename;
        list($status, $resp) = $this->remove_file($url);
        if ($status != 200)
            throw new APIError('DELETE ' . $url . ' ' . $status);
        return $resp;
    }
}

class APIError extends Exception {}

$abraia = new Client();

?>
