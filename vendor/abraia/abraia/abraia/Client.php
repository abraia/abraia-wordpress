<?php

namespace Abraia;

define('ABRAIA_API_URL', 'https://api.abraia.me');

function endsWith( $str, $sub ) {
    return ( substr( $str, strlen( $str ) - strlen( $sub ) ) == $sub );
}


class Client {
    protected $apiKey;
    protected $apiSecret;

    function __construct() {
        $key = getenv('ABRAIA_KEY');
        if ($key) {
          list($apiKey, $apiSecret) = explode(':', base64_decode($key));
          $this->apiKey = ($apiKey === false) ? '' : $apiKey;
          $this->apiSecret = ($apiSecret === false) ? '' : $apiSecret;
        }
    }

    public function setApiKeys($apiKey, $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function loadUser() {
        $curl = curl_init(ABRAIA_API_URL . '/users');
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($code != 200)
            throw new \Exception($resp, $code);
        return json_decode($resp, true);
    }

    public function listFiles($path='') {
        $curl = curl_init(ABRAIA_API_URL . '/files/' . $path);
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($code != 200)
            throw new \Exception($resp, $code);
        return json_decode($resp, true);
    }

    public function uploadRemote($url, $path) {
        $curl = curl_init(ABRAIA_API_URL . '/files/' . $path);
        $data = json_encode(array("url" => $url));
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length ' . strlen($data)
        ));
        $resp = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($code != 201)
            throw new \Exception($resp, $code);
        $resp = json_decode($resp, true);
        return $resp['file'];
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
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($code != 201)
            throw new \Exception($resp, $code);
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
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($code != 200)
            throw new \Exception($resp, $code);
        return array(
            "name" => $name,
            "source" => $source
        );
    }

    public function moveFile($old_path, $new_path) {
        $curl = curl_init(ABRAIA_API_URL . '/files/' . $new_path);
        $data = json_encode(array("store" => $old_path));
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length ' . strlen($data)
        ));
        $resp = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($code != 201)
            throw new \Exception($resp, $code);
        $resp = json_decode($resp, true);
        return $resp['file'];
    }

    public function downloadFile($path) {
        $curl = curl_init(ABRAIA_API_URL . '/files/' . $path);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
        $resp = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close ($curl);
        if ($code != 200)
            throw new \Exception($resp, $code);
        return $resp;
    }

    public function removeFile($path) {
        $curl = curl_init(ABRAIA_API_URL . '/files/' . $path);
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $resp = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($code != 200)
            throw new \Exception($resp, $code);
        return json_decode($resp, true);
    }

    public function transformImage($path, $params=array()) {
        $url = ABRAIA_API_URL . '/images/' . $path;
        if ($params) $url = $url.'?'.http_build_query($params);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiKey.':'.$this->apiSecret);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
        $resp = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close ($curl);
        if ($code != 200)
            throw new \Exception($resp, $code);
        return $resp;
    }
}
