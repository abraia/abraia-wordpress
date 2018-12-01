<?php

add_action('admin_menu', 'abraia_media_menu');

function abraia_media_menu() {
	add_media_page('Abraia Bulk Optimization', 'Bulk Abraia', 'read', 'abraia_bulk_page', 'abraia_media_page');
}

function abraia_media_page() {
    $query_images_args = array(
        'post_type' => 'attachment',
        'post_mime_type' =>'image',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
    );
    $query_images = new WP_Query( $query_images_args );
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
    $optimized = $total_before - $total_after;
    $total = count($query_images->posts);
    $percent = $sum / ($total + 0.000001);
    $abraia_user = get_abraia_user();
    ?>
      <style>
      .abraia-progress {
        background-color: #eee;
        color: #333;
        height: 18px;
      }
      .abraia-progress-bar {
        background-color: #fc0;
        color: #fff;
        height: 18px;
        text-align: center;
      }
      .progress {
        width: 150px;
        height: 150px;
        margin: 0 auto;
        position: relative;
      }
      .progress > span {
        width: 50%;
        height: 100%;
        overflow: hidden;
        position: absolute;
        top: 0;
        z-index: 1;
      }
      .progress .progress-bar {
        width: 100%;
        height: 100%;
        background: none;
        border-width: 12px;
        border-color: #fc0;
        box-sizing: border-box;
        border-style: solid;
        position: absolute;
        top: 0;
      }
      .progress .progress-left {
        left: 0;
      }
      .progress .progress-left .progress-bar {
        left: 100%;
        border-top-right-radius: 80px;
        border-bottom-right-radius: 80px;
        border-left: 0;
        -webkit-transform-origin: center left;
        transform-origin: center left;
        animation: loading-3 1s linear forwards 1.8s;
      }
      .progress .progress-right {
        right: 0;
      }
      .progress .progress-right .progress-bar {
        left: -100%;
        border-top-left-radius: 80px;
        border-bottom-left-radius: 80px;
        border-right: 0;
        -webkit-transform-origin: center right;
        transform-origin: center right;
        animation: loading-1 1.8s linear forwards;
      }
      .progress .progress-value {
        width: 90%;
        height: 90%;
        border-radius: 50%;
        background-color: #eee;
        font-size: 24px;
        line-height: 135px;
        text-align: center;
        position: absolute;
        top: 5%;
        left: 5%;
      }
      @keyframes loading-1{
        0%{
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
        }
        100%{
            -webkit-transform: rotate(<?php echo ($sum > $total / 2) ? 180 : round($percent * 360) ?>deg);
            transform: rotate(<?php echo ($sum > $total / 2) ? 180 : round($percent * 360) ?>deg);
        }
      }
      @keyframes loading-3{
        0%{
            -webkit-transform: rotate(0deg);
            transform: rotate(0deg);
        }
        100%{
            -webkit-transform: rotate(<?php echo ($sum > $total / 2) ? round($percent * 360 - 180) : 0 ?>deg);
            transform: rotate(<?php echo ($sum > $total / 2) ? round($percent * 360 - 180) : 0 ?>deg);
        }
      }
      @media only screen and (max-width: 990px){
        .progress{ margin-bottom: 20px; }
      }
      </style>
      <div class="abraia-panel">
        <div class="abraia-header">
          <h1>Bulk <span style="color:#fc0">Abraia</span></h1>
          <p style="color:#fff">Bulk image optimization</p>
        </div>
        <div class="abraia-content">
          <div class="abraia-row">
            <div class="abraia-column">
              <h1>Optimized</h1>
              <div class="progress">
                <span class="progress-left">
                  <span class="progress-bar"></span>
                </span>
                <span class="progress-right">
                  <span class="progress-bar"></span>
                </span>
                <div class="progress-value"><span id="percent"><?php echo round($percent * 100) ?></span>%</div>
              </div>
              <h2>(<span id="sum"><?php echo $sum ?></span> / <?php echo $total ?>)</h2>
            </div>
            <div class="abraia-column" style="margin: 0 10% 0 0;">
              <h1>Saved</h1>
              <br>
              <h2><?php echo round($optimized / ($total_before + 0.000001) * 100) ?>% (<?php echo size_format($optimized, 2) ?>)</h2>
              <p></p>
              <div>
                <span>Original size</span><span style="float:right;"><?php echo size_format($total_before, 2) ?></span>
                <div class="abraia-progress">
                  <div class="abraia-progress-bar" style="width:100%;background-color:#555">&nbsp;</div>
                </div>
              </div>
              <p></p>
              <div>
                <span>Optimized size</span><span style="float:right;"><?php echo size_format($total_after, 2) ?></span>
                <div class="abraia-progress">
                  <div class="abraia-progress-bar" style="width:<?php echo round($total_after / $total_before * 100) ?>%">&nbsp;</div>
                </div>
              </div>
            </div>
            <div class="abraia-column">
              <h1>Account</h1>
              <div style="flex:1;background-color:#eee;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <h2>Free Trial</h2>
                <p>Credits: <?php echo $abraia_user['credits']; ?></p>
                <p>Total optimized: <?php echo $abraia_user['transforms']; ?></p>
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
        jQuery(document).ready(function($) {
          var sum = <?php echo $sum ?>;
          var total = <?php echo $total ?>;
          var images = <?php echo json_encode($images); ?>;
          var bulkButton = $('#bulk');
          function progressBar(percent) {
            const elem = document.getElementById("progress-bar");
            elem.style.width = percent + '%';
            if (percent === 0) elem.innerHTML = '&nbsp;';
            else elem.innerHTML = percent + '%';
          }
          function compressImage(id, k) {
            return $.post(ajaxurl, { action: 'compress_item', id: id }, function(response) {
              // TODO: Change to return response as json
              $("#sum").text(sum + k + 1);
              $('#percent').text(Math.round(100 * (sum + k + 1) / total));
              progressBar(Math.round((k + 1) / images.length * 100));
              console.log(response);
            });
          }
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
