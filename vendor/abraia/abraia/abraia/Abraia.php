<?php

namespace Abraia;

require_once('Client.php');

class Abraia extends Client {
    protected $path;
    protected $params;
    protected $userid;

    function __construct() {
        parent::__construct();
        $this->userid = $this->check();
    }

    function files($path='') {
        return $this->listFiles($path);
    }

    function fromFile($path) {
        $resp = $this->uploadFile($path, $this->userid . '/');
        $this->path = $resp['source'];
        $this->params = array('q' => 'auto');
        return $this;
    }

    function fromUrl($url) {
        $this->path = '';
        $this->params = array(
            'url' => $url,
            'q' => 'auto',
        );
        return $this;
    }

    function fromStore($path) {
        $this->path = $path;
        $this->params = array('q' => 'auto');
        return $this;
    }

    function toFile($path) {
        $data = $this->transformImage($this->path, $this->params);
        $fp = fopen($path, 'w');
        fwrite($fp, $data);
        fclose($fp);
        return $this;
    }

    function resize($width=null, $height=null, $mode='auto') {
        if (!is_null($width)) $this->params['w'] = $width;
        if (!is_null($height)) $this->params['h'] = $height;
        $this->params['m'] = $mode;
        return $this;
    }

    function remove($path) {
        return $this->removeFile($path);
    }
}
