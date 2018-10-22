<?php
/*
  Plugin name: Smart image and video optimization
  Plugin URI: https://github.com/abraia/abraia-wordpress
  Description: Automatically optimize your images and videos with Abraia.
  Version: 0.3.0
  Author: Abraia Software
  Author URI: https://abraia.me
  Text Domain: wp-abraia
  License: GPLv2
*/

require_once('abraia.php');

const ALLOWED_IMAGES = array('image/jpeg', 'image/png');


add_action('init', 'abraia_admin_init');

function abraia_admin_init() {
    if (is_admin() && current_user_can('manage_options')) {
        add_action('admin_init', 'abraia_settings_init');
        add_action('admin_notices', 'abraia_admin_notice');
    }
}

function abraia_settings_init() {
    register_setting('media', 'abraia_api_key');
    register_setting('media', 'abraia_api_secret');
    register_setting('media', 'abraia_uploads');
    register_setting('media', 'abraia_backup');
    register_setting('media', 'abraia_resize');
    register_setting('media', 'abraia_max_width');
    register_setting('media', 'abraia_max_height');

    add_settings_section('abraia_api_section', 'Smart image compression', 'abraia_settings_section', 'media');
}

function abraia_settings_section() {
    ?>
    <table id="abraia_settings" class="form-table">
      <tbody>
        <tr>
          <th scope="row"><label for="abraia_api_key">API Key</label></th>
          <td><input name="abraia_api_key" id="abraia_api_key" type="text" class="regular-text"
              value="<?php echo get_option('abraia_api_key') ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="abraia_api_secret">API Secret</label></th>
          <td><input name="abraia_api_secret" id="abraia_api_secret" type="text" class="regular-text"
              value="<?php echo get_option('abraia_api_secret') ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="abraia_api_status">API Status</label></th>
          <td><a class="button" href="https://abraia.me/auth/login" target="_blank"
              style="background: #fc0;">Get your Abraia API Keys</a></td>
        </tr>

        <tr>
          <th scope="row"><label for="abraia_resize">Resize larger images</label></th>
          <td>
            <label for="abraia_max_width">Max Width</label>
            <input name="abraia_max_width" step="1" min="0" id="abraia_max_width" type="number" class="small-text"
                value="<?php echo get_option('abraia_max_width', 2000) ?>" />
            <label for="abraia_max_height">Max Height</label>
            <input name="abraia_max_height" step="1" min="0" id="abraia_max_height" type="number" class="small-text"
                value="<?php echo get_option('abraia_max_height', 2000) ?>" />
            <p><input name="abraia_resize" id="abraia_resize" type="checkbox" value="1"
                <?php checked(1, get_option('abraia_resize'), true); ?> />
                <label for="abraia_resize">Reduce unnecessarily large images to the specified maximum dimensions</label></p>
          </td>
        </tr>
      </tbody>
    </table>
    <?php
}

function abraia_admin_notice() {
    if (!get_option('abraia_api_key') or !get_option('abraia_api_secret')) {
        ?>
        <div class="notice notice-error">
          <p><a class="button" href="options-media.php#abraia_settings" style="background: #fc0;">
            Configure your Abraia API Keys</a> to start compressing images</p>
        </div>
        <?php
    }
}


add_action('init', 'abraia_media_init');

$abraia_settings = array();

function abraia_media_init() {
    global $abraia;
    global $abraia_settings;
    if (is_admin() && current_user_can('upload_files')) {
        add_filter('manage_media_columns', 'abraia_media_columns');
        add_action('manage_media_custom_column', 'abraia_media_custom_column', 10, 2);

        add_action('admin_head', 'abraia_media_javascript');
        add_action('wp_ajax_compress_item', 'abraia_compress_item');

        $abraia->set_keys(get_option('abraia_api_key'), get_option('abraia_api_secret'));
        $abraia_settings = array(
          'uploads' => get_option('abraia_uploads'),
          'backup' => get_option('abraia_backup'),
          'resize' => get_option('abraia_resize'),
          'max_width' => get_option('abraia_max_width'),
          'max_height' => get_option('abraia_max_height'),
        );
    }
}

function abraia_media_columns( $media_columns ) {
	$media_columns['abraia'] = 'Abraia Compression';
	return $media_columns;
}

function abraia_media_custom_column( $column_name, $id ) {
    if ( 'abraia' !== $column_name ) return;
    if (!wp_attachment_is_image($id) || !in_array(get_post_mime_type($id), ALLOWED_IMAGES)) {
        return;
    }
    $stats = get_post_meta($id, '_wpa_stats', true);
    echo abraia_media_custom_cell($id, $stats);
}

function abraia_media_custom_cell($id, $stats) {
    if (!empty($stats)) {
        $size_diff = $stats['size_before'] - $stats['size_after'];
        $size_percent = 100 * $size_diff / $stats['size_before'];
        $html = '<p>' . count($stats['sizes']) . ' images reduced by ' .
             size_format($size_diff) . ' ( ' . number_format($size_percent, 2) . '% )<br>';
        $html .= 'Size before: ' . size_format($stats['size_before'], 2) . '<br>';
        $html .= 'Size after: ' . size_format($stats['size_after'], 2) . '<br></p>';
    }
    else {
        $html = '<button id="compress-'.$id.'" class="compress button button-primary"
            type="button" data-id="'.$id.'" style="width:100%;">Compress</button>';
        $html .= '<img id="progress-'.$id.'" src="/wp-includes/js/thickbox/loadingAnimation.gif"
            style="width:100%; display:none;" alt=""/>';
    }
    return $html;
}

function abraia_media_javascript() {
    global $pagenow;
    if ($pagenow == 'upload.php') {
      ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var bulkSelector = $('#bulk-action-selector-top');
            var bulkAction = $('#doaction');
            function compressImage(id) {
                $('#progress-'+id).show();
                var button = $('#compress-'+id).hide();
                return $.post(ajaxurl, { action: 'compress_item', id: id }, function(response) {
                    button.parent().html(response);
                });
            }
            bulkSelector.append(
                $('<option>', { value: 'compress', text: 'Compress images' }));
            bulkSelector.change(function() {
                if (bulkSelector.val() === 'compress')
                    bulkAction.prop('type', 'button');
                else
                    bulkAction.prop('type', 'submit');
            });
            bulkAction.click(function() {
                if (bulkSelector.val() === 'compress') {
                    var ids = [];
                    $('tbody#the-list').find('input[name="media[]"]').each(function () {
                        if ($(this).prop('checked'))
                            ids.push($(this).val());
                    });
                    ids.reduce(function(pp, id) {
                        return pp.then(function() { return compressImage(id) });
                    }, $.when());
                }
            });
            $('.compress').click(function(){
                var button = $(this);
                var id = button.data('id');
                compressImage(id);
            });
        });
        </script>
      <?php
    }
}

function abraia_compress_item() {
    global $abraia;
    global $abraia_settings;
    $id = $_POST['id'];
    $stats = get_post_meta($id, '_wpa_stats', true);
    if (empty($stats) && in_array(get_post_mime_type($id), ALLOWED_IMAGES)) {
        $path = pathinfo(get_attached_file($id));
        $meta = wp_get_attachment_metadata($id);
        $meta['sizes']['original'] = array('file' => $path['basename']);
        $stats = array('size_before' => 0, 'size_after' => 0, 'sizes' => array());
        foreach ($meta['sizes'] as $size => $values) {
            $file = $values['file'];
            if (!empty($file)) {
                $stats['sizes'][$size] = array();
                $image = path_join($path['dirname'], $file);
                $temp = path_join($path['dirname'], 'temp');
                try {
                    if ($abraia_settings['resize']) {
                        $abraia->from_file($image)->resize($abraia_settings['max_width'], $abraia_settings['max_height'], 'thumb')->to_file($temp);
                    } else {
                        $abraia->from_file($image)->to_file($temp);
                    }
                }
                catch (APIError $e) {
                    echo abraia_media_custom_cell($id, NULL);
                    wp_die();
                }
                $size_before = filesize($image);
                $size_after = filesize($temp);
                if ($size_after < $size_before) rename($temp, $image);
                else $size_after = $size_before;
                $stats['sizes'][$size]['size_before'] = $size_before;
                $stats['sizes'][$size]['size_after'] = $size_after;
                $stats['size_before'] += $size_before;
                $stats['size_after'] += $size_after;
            }
        }
        update_post_meta($id, '_wpa_stats', $stats);
    }
    echo abraia_media_custom_cell($id, $stats);
    wp_die();
}

include('abraia-media.php');
