<?php
/*
  Plugin name: Smart image optimization
  Plugin URI: https://github.com/abraia/abraia-wordpress
  Description: Automatically optimize your images with Abraia.
  Version: 0.4.0
  Author: Abraia Software
  Author URI: https://abraia.me
  Text Domain: wp-abraia
  License: GPLv2
*/

require_once('abraia.php');

$abraia = new Client();
$abraia_settings = array();
const ALLOWED_IMAGES = array('image/jpeg', 'image/png');

add_action('init', 'abraia_admin_init');
add_action('init', 'abraia_media_init');
add_action('admin_menu', 'abraia_media_menu');

function abraia_admin_init() {
    if (is_admin() && current_user_can('manage_options')) {
        add_action('admin_init', 'abraia_settings_init');
        add_action('admin_notices', 'abraia_admin_notice');
    }

    if (is_admin() && current_user_can('upload_files')) {

    }
}

include('ab-settings.php');
include('ab-media.php');
include('ab-bulk.php');
