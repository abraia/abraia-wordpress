<?php

namespace Abraia;

define('ABRAIA_API_URL', 'https://api.abraia.me');

function endsWith( $str, $sub ) {
    return ( substr( $str, strlen( $str ) - strlen( $sub ) ) == $sub );
}

class APIError extends \Exception {}

class Client {
    protected $apiKey;
    protected $apiSecret;

    function __construct() {
        $apiKey = getenv('ABRAIA_API_KEY');
        $apiSecret = getenv('ABRAIA_API_SECRET');
        $this->apiKey = ($apiKey === false) ? '' : $apiKey;
        $this->apiSecret = ($apiSecret === false) ? '' : $apiSecret;
    }

    public function setApiKeys($apiKey, $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function check() {
        return $this->listFiles()['folders'][0]['name'];
    }

    public function listFiles($path='') {
        $curl = curl_init(ABRAIA_API_URL . '/files/' . $path);
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($statusCode != 200)
            throw new APIError('GET ' . $statusCode);
        return json_decode($resp, true);
    }

    public function uploadFile($filename, $path='') {
        $source = endsWith($path, '/') ? $path . basename($filename) : $path;
        $name = basename($source);
        $curl = curl_init(ABRAIA_API_URL . '/files/' . $source);
        $data = json_encode(array(
            "name" => $name,
            "type" => ''
        ));
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length ' . strlen($data)
        ));
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($statusCode != 201)
            throw new APIError('POST ' . $statusCode);
        $resp = json_decode($resp, true);
        $uploadURL = $resp['uploadURL'];
        $file = fopen($filename, 'r');
        $curl = curl_init($uploadURL);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_PUT, 1);
        curl_setopt($curl, CURLOPT_INFILE, $file);
        curl_setopt($curl, CURLOPT_INFILESIZE, filesize($filename));
        $resp = curl_exec($curl);
        fclose($file);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($statusCode != 200)
            throw new APIError('POST ' . $statusCode);
        return array(
            "name" => $name,
            "source" => $source
        );
    }

    public function downloadFile($path) {
        $curl = curl_init(ABRAIA_API_URL . '/files/' . $path);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER,1);
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close ($curl);
        if ($statusCode != 200)
            throw new APIError('GET ' . $statusCode);
        return $resp;
    }

    public function removeFile($path) {
        $curl = curl_init(ABRAIA_API_URL . '/files/' . $path);
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($statusCode != 200)
            throw new APIError('DELETE ' . $statusCode);
        return json_decode($resp, true);
    }

    public function transformImage($path, $params=array()) {
        $url = ABRAIA_API_URL . '/images/' . $path;
        $url = $url.'?'.http_build_query($params);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER,1);
        $resp = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close ($curl);
        if ($statusCode != 200)
            throw new APIError('GET ' . $statusCode);
        return $resp;
    }
}
