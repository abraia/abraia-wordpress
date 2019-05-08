<?php
/*
  Plugin name: Abraia
  Plugin URI: https://github.com/abraia/abraia-wordpress
  Description: Automatically optimize your images with Abraia.
  Version: 0.6.1
  Author: Abraia Software
  Author URI: https://abraia.me
  Text Domain: abraia
  License: GPLv2
*/

require_once 'vendor/autoload.php';

$abraia = new Abraia\Abraia();
$abraia_settings = array();

const ALLOWED_IMAGES = array('image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp');

add_action('init', 'abraia_admin_init');

function abraia_admin_init() {
    if (is_admin()) {
        load_plugin_textdomain('abraia', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        include('admin/settings.php');
        include('admin/media.php');
        include('admin/bulk.php');
        abraia_media_init();
    }
}
