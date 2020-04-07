<?php

add_action('admin_menu', 'abraia_media_menu');

function abraia_media_menu()
{
  add_media_page('Abraia Bulk Optimization', __('Bulk Abraia', 'abraia'), 'read', 'abraia_bulk_page', 'abraia_media_page');
}

function abraia_media_page()
{
  $query_images = new WP_Query(array(
    'post_type' => 'attachment',
    'post_mime_type' => 'image',
    'post_status' => 'inherit',
    'posts_per_page' => -1,
    'no_found_rows' => true,
    'fields' => 'ids',
  ));
  $sum = 0;
  $total = 0;
  $total_before = 0;
  $total_after = 0;
  $images = array();
  foreach ($query_images->posts as $id) {
    #        $stats = get_post_meta($id, '_wpa_stats', true);
    #        if (!empty($stats)) {
    #            $sum += 1;
    #            $total_before += $stats['size_before'];
    #            $total_after += $stats['size_after'];
    #        }
    #        else {
    $images[] = $id;
    #        }
    $total += 1;
  }
  $saved = $total_before - $total_after;
  $percent = $sum / ($total + 0.000001);
  $percent_saved = 100 * $saved / ($total_before + 0.000001);
  $user = get_abraia_user();
  ?>
  <div class="abraia-panel">
    <div class="abraia-header is-dark" style="display:block">
      <a href="https://abraia.me" target="_blank" style="float:right">
        <img src="<?php echo plugins_url('../assets/logo.png', __FILE__); ?>" style="height:40px">
      </a>
      <h1><?php esc_html_e('Bulk optimization', 'abraia') ?></h1>
    </div>
  </div>
  <div style="display:flex">
    <div style="width:75%">
      <div class="abraia-panel">
        <div class="abraia-content">
          <div class="abraia-row">
            <div class="abraia-column">
              <h2 class="is-centered"><?php esc_html_e('Optimized', 'abraia') ?></h2>
              <div class="abraia-circular">
                <span class="progress-left">
                  <span class="progress-bar" style="transform: rotate(<?php echo ($sum > $total / 2) ? round($percent * 360 - 180) : 0 ?>deg);"></span>
                </span>
                <span class="progress-right">
                  <span class="progress-bar" style="transform: rotate(<?php echo ($sum > $total / 2) ? 180 : round($percent * 360) ?>deg);"></span>
                </span>
                <div class="progress-value"><span id="percent"><?php echo round(100 * $percent) ?></span>%</div>
              </div>
              <p class="is-centered is-2">
                <span id="progress-spinner" class="spinner" style="float:unset;vertical-align:top"></span>
                <span id="sum"><?php echo $sum ?></span> <?php esc_html_e('images of', 'abraia') ?> <?php echo $total ?>
                <span class="spinner" style="float:unset;vertical-align:top"></span>
              </p>
            </div>
            <div class="abraia-column" style="margin: 0 10% 0 0;">
              <h2 class="is-centered"><?php esc_html_e('Saved', 'abraia') ?></h2>
              <p class="is-centered is-1"><b><span id="saved"><?php echo size_format($saved, 1) ?></span></b> ( <span id="percent-saved"><?php echo round($percent_saved) ?></span>% )</p>
              <div>
                <span><?php esc_html_e('Size now', 'abraia') ?></span>
                <div class="abraia-progress">
                  <div id="optimized-bar" class="abraia-progress-bar" style="width:<?php echo round(100 * $total_after / ($total_before + 0.000001)) ?>%">
                    <span id="optimized"><?php echo size_format($total_after, 2) ?></span>
                  </div>
                </div>
              </div>
              <p></p>
              <div>
                <span><?php esc_html_e('Size before', 'abraia') ?></span>
                <div class="abraia-progress">
                  <div class="abraia-progress-bar is-dark" style="width:100%">
                    <span id="original"><?php echo size_format($total_before, 2) ?></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div style="width:25%">
      <div class="abraia-panel">
        <div class="abraia-content is-light">
          <h2 class="is-centered"><?php esc_html_e('Your Account', 'abraia') ?></h2>
          <div class="is-light" style="display:flex;flex-direction:column;align-items:center;justify-content:center">
            <p class="is-centered is-2"><?php esc_html_e('Available', 'abraia'); ?><br>
              <span class="is-1"><b><?php echo size_format($user['credits'] * 104858, 1); ?></b></span><br></p>
            <a class="button button-hero is-yellow" style="font-size:16px;width:unset" href="https://abraia.me/payment/<?php echo ($user) ? '?email=' . $user['email'] : '' ?>" target="_blank"><?php esc_html_e('Buy More Credits', 'abraia'); ?></a>
            <p><?php esc_html_e('Total processed', 'abraia') ?> <?php echo $user['transforms']; ?> <?php esc_html_e('images and', 'abraia') ?> <?php echo size_format($user['bandwidth'], 1); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="abraia-panel">
    <div class="abraia-footer">
      <div class="abraia-progress">
        <div id="progress-bar" class="abraia-progress-bar" style="width:0%">&nbsp;</div>
      </div>
      <p></p>
      <button id="bulk" class="button button-hero is-blue" type="button" <?php echo ($sum == $total) ? 'disabled' : '' ?>>
        <?php esc_html_e('Bulk Optimization', 'abraia'); ?>
      </button>
    </div>
  </div>
  <script type="text/javascript">
    jQuery(document).ready(function($) {
      var sum = <?php echo $sum ?>;
      var total = <?php echo $total ?>;
      var original = <?php echo $total_before ?>;
      var optimized = <?php echo $total_after ?>;
      var images = <?php echo json_encode($images); ?>;
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

      function updateProgress(sum, total) {
        var percent = Math.round(100 * sum / total);
        $('#sum').text(sum);
        $('#percent').text(percent);
        $('.progress-right .progress-bar').css({
          'transform': 'rotate(' + ((sum > total / 2) ? 180 : Math.round(360 * sum / total)) + 'deg)'
        });
        $('.progress-left .progress-bar').css({
          'transform': 'rotate(' + ((sum > total / 2) ? Math.round(360 * sum / total - 180) : 0) + 'deg)'
        });
        $('#progress-bar').css({
          'width': percent + '%'
        });
        if (percent === 0) $('#progress-bar').text('&nbsp;');
        else $('#progress-bar').text(percent + '%');
      }

      function updateInfo(original, optimized) {
        $('#original').text(sizeFormat(original, 2));
        $('#optimized').text(sizeFormat(optimized, 2));
        $('#saved').text(sizeFormat(original - optimized, 2));
        $('#percent-saved').text(Math.round(100 * (original - optimized) / original));
        $('#optimized-bar').css({
          'width': Math.round(100 * optimized / original) + '%'
        });
      }

      function compressImage(id) {
        return $.post(ajaxurl, {
          action: 'compress_item',
          id: id,
          nonce: nonce
        }, function(resp) {
          sum += 1;
          var stats = JSON.parse(resp);
          if (stats) {
            original += stats['size_before'];
            optimized += stats['size_after'];
            updateInfo(original, optimized);
          }
          updateProgress(sum, total);
          if (sum == total) $('#progress-spinner').css('visibility', 'hidden');
        });
      }

      function nextTask() {
        if (images.length) {
          var id = images.shift();
          return compressImage(id);
        }
      }

      function startWorker() {
        return $.when().then(function next() {
          return nextTask().then(next);
        })
      }
      var bulkButton = $('#bulk');
      bulkButton.click(function() {
        bulkButton.prop('disabled', true);
        $('#progress-spinner').css('visibility', 'visible');
        $.when(startWorker(), startWorker(), startWorker()).then(function() {
          $('#progress-spinner').css('visibility', 'hidden');
        });
      });
    });
  </script>
  <?php
}
