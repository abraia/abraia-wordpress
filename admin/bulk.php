<?php

add_action('admin_menu', 'abraia_media_menu');

function abraia_media_menu() {
	add_media_page('Abraia Bulk Optimization', __('Bulk Abraia', 'abraia'), 'read', 'abraia_bulk_page', 'abraia_media_page');
}

function abraia_media_page() {
    $query_images = new WP_Query(array(
        'post_type' => 'attachment',
        'post_mime_type' =>'image',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
    ));
    $sum = 0;
    $total_before = 0;
    $total_after = 0;
    $images = array();
    foreach ( $query_images->posts as $image) {
        $stats = get_post_meta($image->ID, '_wpa_stats', true);
        if (!empty($stats)) {
            $sum += 1;
            $total_before += $stats['size_before'];
            $total_after += $stats['size_after'];
        }
        else {
            $images[] = $image->ID;
        }
    }
    $saved = $total_before - $total_after;
    $total = count($query_images->posts);
    $percent = $sum / ($total + 0.000001);
    $percent_saved = 100 * $saved / ($total_before + 0.000001);
    $user = get_abraia_user();
    $credits = max($user['credits'], 0);
    ?>
      <div class="abraia-panel">
        <div class="abraia-header">
          <h1><span class="is-yellow">Abraia</span> <span style="color:#aaa">/</span> <?php esc_html_e('Bulk image optimization', 'abraia') ?></h1>
          <p class="is-gray"><?php esc_html_e('The smart web image optimization plugin', 'abraia'); ?></p>
        </div>
        <div class="abraia-content">
          <div class="abraia-row">
            <div class="abraia-column">
              <h2><?php esc_html_e('Optimized', 'abraia') ?></h2>
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
              <h2><?php esc_html_e('Saved', 'abraia') ?></h2>
              <p class="is-centered is-1"><b><span id="saved"><?php echo size_format($saved, 1) ?></span></b> ( <span id="percent-saved"><?php echo round($percent_saved) ?></span>% )</p>
              <div>
                <span><?php esc_html_e('Size now', 'abraia') ?></span>
                <div class="abraia-progress">
                  <div id="optimized-bar" class="abraia-progress-bar" style="width:<?php echo round(100 * $total_after / $total_before) ?>%">
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
            <div class="abraia-column is-dark">
              <h2 style="color:#fafafa"><?php esc_html_e('Your Account', 'abraia') ?></h2>
              <div class="is-light" style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center">
                <p class="is-centered is-2"><?php esc_html_e('Available', 'abraia'); ?><br>
                <span class="is-1"><b><?php echo size_format($credits * 100000, 1); ?></b></span><br></p>
                <a class="button button-hero is-yellow" style="font-size:16px;width:unset" href="https://abraia.me/payment/<?php echo ($user) ? '?email=' . $user['email'] : '' ?>" target="_blank"><?php esc_html_e('Buy More Megas', 'abraia'); ?></a>
                <p><?php esc_html_e('Total processed', 'abraia') ?> <?php echo $user['transforms']; ?> <?php esc_html_e('images and', 'abraia') ?> <?php echo size_format($user['bandwidth'], 1); ?>
              </div>
            </div>
          </div>
        </div>
        <div class="abraia-footer" <?php echo ($user['credits'] > 0) ? '' : 'style="display:none;"' ?>>
          <div class="abraia-progress">
            <div id="progress-bar" class="abraia-progress-bar" style="width:0%">&nbsp;</div>
          </div>
          <p></p>
          <button id="bulk" class="button button-primary button-hero" type="button" <?php echo ($sum == $total) ? 'disabled' : '' ?>>
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
          function progressBar(percent) {
            $('#progress-bar').css({'width': percent + '%'});
            if (percent === 0) $('#progress-bar').text('&nbsp;');
            else $('#progress-bar').text(percent + '%');
          }
          function updateProgress(curr, total) {
            $('#sum').text(curr);
            $('#percent').text(Math.round(100 * curr / total));
            $('.progress-right .progress-bar').css({'transform': 'rotate(' + ((curr > total / 2) ? 180 : Math.round(360 * curr / total)) + 'deg)'});
            $('.progress-left .progress-bar').css({'transform': 'rotate(' + ((curr > total / 2) ? Math.round(360 * curr / total - 180) : 0) + 'deg)'});
          }
		  function updateInfo(original, optimized) {
			$('#original').text(sizeFormat(original, 2));
			$('#optimized').text(sizeFormat(optimized, 2));
			$('#saved').text(sizeFormat(original - optimized, 2));
			$('#percent-saved').text(Math.round(100 * (original - optimized) / original));
			$('#optimized-bar').css({'width': Math.round(100 * optimized / original) + '%'});
		  }
          function compressImage(id, k) {
            return $.post(ajaxurl, { action: 'compress_item', id: id }, function(resp) {
              var curr = sum + k + 1;
              var stats = JSON.parse(resp);
              if (stats) {
                original += stats['size_before'];
                optimized += stats['size_after'];
                updateInfo(original, optimized);
              }
              updateProgress(curr, total);
              progressBar(Math.round(100 * (k + 1) / (total - sum)));
	          if (curr === total) location.reload(true);
            });
          }
          var bulkButton = $('#bulk');
          bulkButton.click(function() {
            bulkButton.prop('disabled', true);
            $('#progress-spinner').css({'visibility': 'visible'});
            images.reduce(function(pp, id, k) {
              return pp.then(function() { return compressImage(id, k) });
            }, $.when());
          });
        });
      </script>
    <?php
}
