<?php

function abraia_media_init()
{
  global $abraia;

  add_filter('manage_media_columns', 'abraia_media_columns');
  add_action('manage_media_custom_column', 'abraia_media_custom_column', 10, 2);

  add_action('admin_head', 'abraia_media_javascript');
  add_action('wp_ajax_compress_item', 'abraia_compress_item');
  add_action('wp_ajax_restore_item', 'abraia_restore_item');

  $settings = get_abraia_settings();
  set_abraia_settings($settings);
}

function abraia_media_columns($media_columns)
{
  $media_columns['abraia'] = __('Abraia Compression', 'abraia');
  return $media_columns;
}

function abraia_media_custom_column($column_name, $id)
{
  if ('abraia' !== $column_name) return;
  if (!wp_attachment_is_image($id) || !in_array(get_post_mime_type($id), ALLOWED_IMAGES)) {
    return;
  }
  $stats = get_post_meta($id, '_wpa_stats', true);
  echo abraia_media_custom_cell($id, $stats);
}

function abraia_media_custom_cell($id, $stats)
{
  if (!empty($stats)) {
    // print_r($stats);
    $size_diff = $stats['size_before'] - $stats['size_after'];
    $size_percent = 100 * $size_diff / ($stats['size_before'] + 0.000001);
    $html = '<p>Saved: <b>' . size_format($size_diff) . '</b> ( ' . round($size_percent) . '% )<br>';
    $html .= 'Size now: <i>' . size_format($stats['size_after'], 2) . '</i><br>';
    $html .= count($stats['sizes']) . ' images reduced<br></p>';
    $html .= '<button id="restore-' . $id . '" class="restore button" type="button" data-id="' . $id . '">Restore</button>';
  } else {
    $html = '<button id="compress-' . $id . '" class="compress button button-primary" type="button" data-id="' . $id . '" style="width:100%;">Optimize</button>';
  }
  $html .= '<img id="progress-' . $id . '" src="/wp-includes/js/thickbox/loadingAnimation.gif" style="width:100%; display:none;" alt=""/>';
  return $html;
}

function abraia_media_javascript()
{
  global $pagenow;
  if ($pagenow == 'upload.php') {
?>
    <script type="text/javascript">
      jQuery(document).ready(function($) {
        var nonce = '<?php echo wp_create_nonce('abraia-nonce') ?>';

        function sizeFormat(bytes, decimals = 0) {
          var units = ['B', 'KB', 'MB', 'GB', 'TB'];
          var value = 0;
          for (var u = 0; u < units.length; u++) {
            value = bytes;
            bytes /= 1024;
            if (bytes < 1) break;
          }
          return value.toFixed(decimals) + ' ' + units[u];
        }

        function renderCustomCell(id, stats) {
          var html = '';
          if (stats) {
            var size_diff = stats['size_before'] - stats['size_after'];
            var size_percent = 100 * size_diff / (stats['size_before'] + 0.000001);
            html = '<p>Saved: <b>' + sizeFormat(size_diff) + '</b> ( ' + Math.round(size_percent) + '% )<br>';
            html += 'Size now: <i>' + sizeFormat(stats['size_after'], 2) + '</i><br>';
            html += Object.keys(stats['sizes']).length + ' images reduced<br></p>';
            html += '<button id="restore-' + id + '" class="restore button" type="button" data-id="' + id + '">Restore</button>';
          } else {
            html = '<button id="compress-' + id + '" class="compress button button-primary" type="button" data-id="' + id + '" style="width:100%;">Optimize</button>';
          }
          html += '<img id="progress-' + id + '" src="/wp-includes/js/thickbox/loadingAnimation.gif" style="width:100%; display:none;" alt=""/>';
          return html;
        }

        function compressImage(id) {
          $('#progress-' + id).show();
          $('#compress-' + id).hide();
          return $.post(ajaxurl, {
            action: 'compress_item',
            id: id,
            nonce: nonce
          }, function(resp) {
            var stats = JSON.parse(resp);
            var html = renderCustomCell(id, stats);
            $('#compress-' + id).parent().html(html);
            $('#restore-' + id).click(function() {
              restoreImage(id);
            });
          });
        }

        function restoreImage(id) {
          $('#progress-' + id).show();
          $('#restore-' + id).hide();
          return $.post(ajaxurl, {
            action: 'restore_item',
            id: id,
            nonce: nonce
          }, function(resp) {
            var stats = JSON.parse(resp);
            var html = renderCustomCell(id, null);
            $('#restore-' + id).parent().html(html);
            $('#compress-' + id).click(function() {
              compressImage(id);
            });
          });
        }
        $('.compress').click(function() {
          compressImage($(this).data('id'));
        });
        $('.restore').click(function() {
          restoreImage($(this).data('id'));
        });
        var bulkSelector = $('#bulk-action-selector-top');
        var bulkAction = $('#doaction');
        bulkSelector.append($('<option>', {
          value: 'compress',
          text: 'Compress images'
        }));
        bulkSelector.change(function() {
          bulkAction.prop('type', (bulkSelector.val() === 'compress') ? 'button' : 'submit');
        });
        bulkAction.click(function() {
          if (bulkSelector.val() === 'compress') {
            var ids = [];
            $('tbody#the-list').find('input[name="media[]"]').each(function() {
              if ($(this).prop('checked')) ids.push($(this).val());
            });
            ids.reduce(function(pp, id) {
              return pp.then(function() {
                return compressImage(id)
              });
            }, $.when());
          }
        });
      });
    </script>
<?php
  }
}

function abraia_compress_item()
{
  if (check_ajax_referer('abraia-nonce', 'nonce')) {
    $id = intval($_POST['id']);
    $meta = wp_get_attachment_metadata($id);
    $stats = abraia_compress_image($id, $meta);
    echo json_encode($stats);
  }
  wp_die();
}

function abraia_compress_image($id, $meta)
{
  global $abraia;
  $settings = get_abraia_settings();
  $min_size = $settings['min_size'] * 1000;
  $max_width = $settings['resize'] ? $settings['max_width'] : 0;
  $max_height = $settings['resize'] ? $settings['max_height'] : 0;
  $stats = get_post_meta($id, '_wpa_stats', true);
  if (empty($stats) && in_array(wp_check_filetype($meta['file'])['type'], ALLOWED_IMAGES)) {
    $path = pathinfo(get_attached_file($id));
    $meta['sizes']['original'] = array('file' => $path['basename']);
    $stats = array('size_before' => 0, 'size_after' => 0, 'sizes' => array());
    foreach ($meta['sizes'] as $size => $values) {
      if (!$settings['thumbnails'] && ($size != 'original')) continue;
      $file = $values['file'];
      if (!empty($file)) {
        $stats['sizes'][$size] = array();
        $image = path_join($path['dirname'], $file);
        $temp = path_join($path['dirname'], 'temp');
        $size_before = filesize($image);
        $size_after = 0;
        if ($size_before > $min_size) {
          try {
            $abraia->fromFile($image)->resize($max_width, $max_height, 'thumb')->toFile($temp);
            $size_after = filesize($temp);
            if ($size_after < $size_before) rename($temp, $image);
            else unlink($temp);
          } catch (Exception $e) {
            // print_r($e->getCode() . $e->getMessage());
            if ($e->getCode() === 402) {
              $stats = NULL;
              break;
            }
          }
        }
        if (!($size_after > 0 && $size_after < $size_before)) $size_after = $size_before;
        $stats['sizes'][$size]['size_before'] = $size_before;
        $stats['sizes'][$size]['size_after'] = $size_after;
        $stats['size_before'] += $size_before;
        $stats['size_after'] += $size_after;
      }
    }
    if (!is_null($stats)) update_post_meta($id, '_wpa_stats', $stats);
  }
  return $stats;
}

function abraia_restore_item()
{
  if (check_ajax_referer('abraia-nonce', 'nonce')) {
    $id = intval($_POST['id']);
    $meta = wp_get_attachment_metadata($id);
    $stats = abraia_restore_image($id, $meta);
    echo json_encode($stats);
    wp_die();
  }
}

function abraia_restore_image($id, $meta)
{
  global $abraia;
  $stats = get_post_meta($id, '_wpa_stats', true);
  if ($stats) {
    $path = pathinfo(get_attached_file($id));
    $meta['sizes']['original'] = array('file' => $path['basename']);
    foreach ($meta['sizes'] as $size => $values) {
      $sizes = $stats['sizes'];
      $file = $values['file'];
      if ($file && ($sizes[$size]['size_before'] > $sizes[$size]['size_after'])) {
        $image = path_join($path['dirname'], $file);
        try {
          $abraia->fromStore($file)->toFile($image);
        } catch (Exception $e) {
          // $stats = NULL;
        }
      }
    }
    delete_post_meta($id, '_wpa_stats');
  }
  return NULL; //json_decode('{}');
}

add_filter('wp_generate_attachment_metadata', 'abraia_upload_filter', 10, 2);

function abraia_upload_filter($meta, $id)
{
  $settings = get_abraia_settings();
  if ($settings['upload']) abraia_compress_image($id, $meta);
  return $meta;
}
