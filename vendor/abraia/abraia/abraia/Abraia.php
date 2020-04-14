<?php

namespace Abraia;

require_once('Client.php');

class Abraia extends Client {
    protected $path;
    protected $params;
    protected $userid;

    function __construct($folder='') {
        parent::__construct();
        $this->userid = $this->getUserId();
        $this->setFolder($folder);
    }


    private function getUserId() {
        try {
            return $this->user()['id'];
        } catch (\Exception $e) {
            return NULL;
        }
    }

    function setId($userid) {
        $this->userid = $userid;
    }

    function setKey($key) {
        list($apiKey, $apiSecret) = array_pad(explode(':', base64_decode($key)), 2, null);
        $this->setApiKeys($apiKey, $apiSecret);
        // $this->userid = $this->getUserId();
    }

    function setFolder($folder) {
        $this->folder = $folder ? $folder . '/' : '';
    }

    function user() {
      return $this->loadUser()['user'];
    }

    function files($path='') {
        return $this->listFiles($this->userid . '/' . $this->folder . $path);
    }

    function fromFile($path) {
        $resp = $this->uploadFile($path, $this->userid . '/' . $this->folder);
        $this->path = $resp['source'];
        $this->params = array('q' => 'auto');
        return $this;
    }

    function fromUrl($url) {
        $resp = $this->uploadRemote($url, $this->userid . '/' . $this->folder);
        $this->path = $resp['source'];
        $this->params = array('q' => 'auto');
        return $this;
    }

    function fromStore($path) {
        $this->path = $this->userid . '/' . $this->folder . $path;
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
