<?php

add_action('admin_menu', 'abraia_media_menu');

function abraia_media_menu() {
	add_media_page('Abraia Bulk Optimization', __('Bulk Abraia', 'abraia'), 'read', 'abraia_bulk_page', 'abraia_media_page');
}

function abraia_media_page() {
    $query_images_args = array(
        'post_type' => 'attachment',
        'post_mime_type' =>'image',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
    );
    $query_images = new WP_Query($query_images_args);
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
        // $images[]= wp_get_attachment_url( $image->ID );
    }
    $saved = $total_before - $total_after;
    $total = count($query_images->posts);
    $percent = $sum / ($total + 0.000001);
    $percent_saved = 100 * $saved / ($total_before + 0.000001);
    $abraia_user = get_abraia_user();
    ?>
      <div class="abraia-panel">
        <div class="abraia-header">
          <h1><?php esc_html_e('Bulk', 'abraia') ?> <span style="color:#fc0">Abraia</span></h1>
          <p><?php esc_html_e('Bulk image optimization', 'abraia') ?></p>
        </div>
        <div class="abraia-content">
          <div class="abraia-row">
            <div class="abraia-column">
              <h1><?php esc_html_e('Optimized', 'abraia') ?></h1>
              <div class="abraia-circular">
                <span class="progress-left">
                  <span class="progress-bar" style="transform: rotate(<?php echo ($sum > $total / 2) ? round($percent * 360 - 180) : 0 ?>deg);"></span>
                </span>
                <span class="progress-right">
                  <span class="progress-bar" style="transform: rotate(<?php echo ($sum > $total / 2) ? 180 : round($percent * 360) ?>deg);"></span>
                </span>
                <div class="progress-value"><span id="percent"><?php echo round(100 * $percent) ?></span>%</div>
              </div>
              <h2>(<span id="sum"><?php echo $sum ?></span> / <?php echo $total ?>)</h2>
            </div>
            <div class="abraia-column" style="margin: 0 10% 0 0;">
              <h1><?php esc_html_e('Saved', 'abraia') ?></h1>
              <br>
              <h2><b><span id="saved"><?php echo size_format($saved, 2) ?></span></b> ( <span id="percent-saved"><?php echo round($percent_saved) ?></span>% )</h2>
			  <p></p>
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
                  <div class="abraia-progress-bar" style="width:100%;background-color:#555">
                    <span id="original"><?php echo size_format($total_before, 2) ?></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="abraia-column" style="background-color:#fc0">
              <h1><?php esc_html_e('Your Account', 'abraia') ?></h1>
              <div style="flex:1;background-color:#fafafa;display:flex;flex-direction:column;align-items:center;justify-content:center">
                <h2>Free Trial</h2>
                <!-- <p>Credits: <?php echo $abraia_user['credits']; ?></p> -->
			    				<p style="text-align:center">Total optimized<br>
                  <b style="font-size:1.5em"><?php echo size_format($abraia_user['bandwidth'], 2); ?></b><br>
                  <b style="font-size:1.2em"><?php echo $abraia_user['transforms']; ?> files</b></p>
              </div>
            </div>
          </div>
        </div>
        <div class="abraia-footer">
          <div class="abraia-progress">
            <div id="progress-bar" class="abraia-progress-bar" style="width:0%">&nbsp;</div>
          </div>
          <button id="bulk" class="button button-primary button-hero" type="button" <?php echo ($sum == $total) ? 'disabled' : '' ?>>Bulk Optimization</button>
        </div>
      </div>
      <script type="text/javascript">
        // TODO: Merge common javascript code and as a new asset
        jQuery(document).ready(function($) {
          var sum = <?php echo $sum ?>;
          var total = <?php echo $total ?>;
          var original = <?php echo $total_before ?>;
          var optimized = <?php echo $total_after ?>;
          var images = <?php echo json_encode($images); ?>;
          function sizeFormat(bytes, decimals = 0) {
            var units = ['B', 'KB', 'MB', 'GB', 'TB'];
            var value = 0;
            var u = -1;
            do {
              value = bytes;
              bytes /= 1024;
              u += 1;
            } while (bytes >= 1 && u < units.length);
            return value.toFixed(decimals) + ' ' + units[u];
          }
          function progressBar(percent) {
            $('#progress-bar').css({'width': percent + '%'});
            if (percent === 0) $('#progress-bar').text('&nbsp;');
            else $('#progress-bar').text(percent + '%');
          }
          function compressImage(id, k) {
            return $.post(ajaxurl, { action: 'compress_item', id: id }, function(response) {
              $('#sum').text(sum + k + 1);
              $('#percent').text(Math.round(100 * (sum + k + 1) / total));
              progressBar(Math.round(100 * (k + 1) / images.length));
              var stats = JSON.parse(response);
              original += stats['size_before'];
              optimized += stats['size_after'];
              $('#original').text(sizeFormat(original, 2));
              $('#optimized').text(sizeFormat(optimized, 2));
              $('#saved').text(sizeFormat(original - optimized, 2));
              $('#percent-saved').text(Math.round(100 * (original - optimized) / original));
              $('#optimized-bar').css({'width': Math.round(100 * optimized / original) + '%'});
              $('.progress-right .progress-bar').css({'transform': 'rotate(' + (((sum + k + 1) > total / 2) ? 180 : Math.round(360 * (sum + k + 1) / total)) + 'deg)'});
              $('.progress-left .progress-bar').css({'transform': 'rotate(' + (((sum + k + 1) > total / 2) ? Math.round(360 * (sum + k + 1) / total - 180) : 0) + 'deg)'});
            });
          }
          var bulkButton = $('#bulk');
          bulkButton.click(function() {
            bulkButton.prop('disabled', true);
            images.reduce(function(pp, id, k) {
              return pp.then(function() { return compressImage(id, k) });
            }, $.when());
          });
        });
      </script>
    <?php
}
