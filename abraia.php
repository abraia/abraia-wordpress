<?php

define('API_URL', 'https://abraia.me/api');

class Client {
    private static $api_key = NULL;
    private static $api_secret = NUll;

    var $url = '';
    var $params = array();

    function __construct() {
    }

    public static function setKeys($api_key, $api_secret) {
        self::$api_key = $api_key;
        self::$api_secret = $api_secret;
    }

    private function listFiles($url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERPWD, self::$api_key.':'.self::$api_secret);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return array($statusCode, $resp);
    }

    private function removeFile($url) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERPWD, self::$api_key.':'.self::$api_secret);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return array($statusCode, $resp);
    }

    private function uploadFile($url, $filename) {
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

    private function downloadFile($url) {
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
        list($status, $resp) = $this->listFiles($url);
        if ($status != 200)
            throw new APIError('GET ' . $url . ' ' . $status);
        return $resp;
    }

    function fromFile($filename) {
        $url = API_URL.'/images';
        list($status, $resp) = $this->uploadFile($url, $filename);
        if ($status != 201)
            throw new APIError('POST ' . $url . ' ' . $status);
        $json = json_decode($resp, true);
        $this->url = $url.'/'.$json['filename'];
        $this->params['q'] = 'auto';
        return $this;
    }

    function fromUrl($url) {
        $this->url = API_URL.'/images';
        $this->params['url'] = $url;
        $this->params['q'] = 'auto';
        return $this;
    }

    function toFile($filename) {
        $url = $this->url.'?'.http_build_query($this->params);
        list($status, $resp) = $this->downloadFile($url);
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
        list($status, $resp) = $this->removeFile($url);
        if ($status != 200)
            throw new APIError('DELETE ' . $url . ' ' . $status);
        return $resp;
    }
}

class APIError extends Exception {}

?>
