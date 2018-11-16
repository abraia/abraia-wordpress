<?php

$abraia_settings = array();

add_action('init', 'abraia_media_init');

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
            function compressImage(id) {
              $('#progress-'+id).show();
              var button = $('#compress-'+id).hide();
              return $.post(ajaxurl, { action: 'compress_item', id: id }, function(response) {
                button.parent().html(response);
              });
            }
            $('.compress').click(function(){
              var button = $(this);
              var id = button.data('id');
              compressImage(id);
            });
            var bulkSelector = $('#bulk-action-selector-top');
            var bulkAction = $('#doaction');
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
                    // echo $e;
                    // $stats = NULL;
                    continue;
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
        if (!is_null($stats)) update_post_meta($id, '_wpa_stats', $stats);
    }
    echo abraia_media_custom_cell($id, $stats);
    wp_die();
}
