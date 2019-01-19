<?php

namespace Abraia;

require_once('Client.php');

class Abraia extends Client {
    protected $path;
    protected $params;
    protected $userid;

    function __construct() {
        parent::__construct();
    }

    function setKey($key) {
        list($apiKey, $apiSecret) = explode(':', base64_decode($key));
        $this->setApiKeys($apiKey, $apiSecret);
        $this->userid = $this->check();
    }

    function files($path='') {
        return $this->listFiles($path);
    }

    function fromFile($path) {
        if (!$this->userid) $this->userid = $this->check();
        $resp = $this->uploadFile($path, $this->userid . '/');
        $this->path = $resp['source'];
        $this->params = array('q' => 'auto');
        return $this;
    }

    function fromUrl($url) {
        if (!$this->userid) $this->userid = $this->check();
        $resp = $this->uploadRemote($url, $this->userid . '/');
        $this->path = $resp['source'];
        $this->params = array('q' => 'auto');
        return $this;
    }

    function fromStore($path) {
        if (!$this->userid) $this->userid = $this->check();
        $this->path = $this->userid . '/' . $path;
        $this->params = array();
        return $this;
    }

    function toFile($path) {
        if ($this->params) {
          $ext = pathinfo($path, PATHINFO_EXTENSION);
          if ($ext) $this->params['fmt'] = strtolower($ext);
        }
        $data = $this->transformImage($this->path, $this->params);
        $fp = fopen($path, 'w');
        fwrite($fp, $data);
        fclose($fp);
        return $this;
    }

    function resize($width=null, $height=null, $mode='auto') {
        if ($width) $this->params['w'] = $width;
        if ($height) $this->params['h'] = $height;
        $this->params['m'] = $mode;
        return $this;
    }

    function remove() {
        return $this->removeFile($this->path);
    }
}
