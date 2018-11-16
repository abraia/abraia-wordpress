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
    ?>
      <style>
      .container {
        background-color: #fff;
        margin: 10px 20px 0 2px;
      }
      .section {
        padding: 10px 20px;
      }
      .is-dark {
        background-color: #333;
      }
      .is-dark h1 {
        color: #fafafa;
      }
      .row {
        display: flex;
        margin: 20px 0;
      }
      .row h1, .row h2 {
        text-align: center;
      }
      .row h2 {
        font-size: 2em;
        font-weight: 400;
      }
      .column {
        flex: 1;
        padding: 10px;
        display: flex;
        flex-direction: column;
      }
      .abraia-progress {
        color:#000!important;
        background-color: #f1f1f1!important
      }
      .abraia-progress-bar {
        color: #fff;
        background-color: #fc0;
        text-align: center;
      }
      .button.button-action {
        width: 100%;
        height: auto;
        padding: 11px 22px;
        font-size: 14px;
        font-weight: 600;
      }
      .progress {
        width: 150px;
        height: 150px;
        line-height: 150px;
        background: none;
        box-shadow: none;
        margin: 0 auto;
        position: relative;
        border-radius: 50%;
      }
      .progress > span{
        width: 50%;
        height: 100%;
        overflow: hidden;
        position: absolute;
        top: 0;
        z-index: 1;
      }
      .progress .progress-left{
        left: 0;
      }
      .progress .progress-bar{
        width: 100%;
        height: 100%;
        background: none;
        border-width: 12px;
        box-sizing: border-box;
        border-style: solid;
        position: absolute;
        top: 0;
      }
      .progress .progress-left .progress-bar{
        left: 100%;
        border-top-right-radius: 80px;
        border-bottom-right-radius: 80px;
        border-left: 0;
        -webkit-transform-origin: center left;
        transform-origin: center left;
      }
      .progress .progress-right{
        right: 0;
      }
      .progress .progress-right .progress-bar{
        left: -100%;
        border-top-left-radius: 80px;
        border-bottom-left-radius: 80px;
        border-right: 0;
        -webkit-transform-origin: center right;
        transform-origin: center right;
        animation: loading-1 1.8s linear forwards;
      }
      .progress .progress-value{
        width: 90%;
        height: 90%;
        border-radius: 50%;
        background: #555;
        font-size: 24px;
        color: #fff;
        line-height: 135px;
        text-align: center;
        position: absolute;
        top: 5%;
        left: 5%;
      }
      .progress.yellow .progress-bar{
        border-color: #ffcc00;
      }
      .progress.yellow .progress-left .progress-bar{
        animation: loading-3 1s linear forwards 1.8s;
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
      <div class="container">
        <div class="section is-dark">
          <h1>Bulk Image Optimization</h1>
        </div>
        <div class="section">
          <div class="row">
            <div class="column">
              <h1>Optimized</h1>
              <div class="progress yellow">
                <span class="progress-left">
                  <span class="progress-bar"></span>
                </span>
                <span class="progress-right">
                  <span class="progress-bar"></span>
                </span>
                <div class="progress-value"><?php echo round($percent * 100) ?>%</div>
              </div>
              <h2>(<?php echo $sum ?> / <?php echo $total ?>)</h2>
            </div>
            <div class="column" style="margin: 0 10% 0 0;">
              <h1>Saved</h1>
              <br>
              <h2><?php echo round($optimized / $total_before * 100) ?>% (<?php echo size_format($optimized, 2) ?>)</h2>
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
            <div class="column">
              <h1>Account</h1>
              <div style="flex:1;background-color:#eee;display:flex;align-items:center;justify-content:center;"><h2>Free Trial</h2></div>
            </div>
          </div>
        </div>
        <div class="section">
          <div class="abraia-progress">
            <div id="progress-bar" class="abraia-progress-bar" style="width:0%">&nbsp;</div>
          </div>
        </div>
        <div class="section">
          <button id="bulk" class="button button-primary button-action" type="button" <?php echo ($sum == $total) ? 'disabled' : '' ?>>Bulk Optimization</button>
        </div>
      </div>
      <script type="text/javascript">
        jQuery(document).ready(function($) {
          var images = <?php echo json_encode($images); ?>;
          var bulkButton = document.getElementById('bulk');
          function progressBar(percent) {
            const elem = document.getElementById("progress-bar")
            elem.style.width = percent + '%'
            if (percent === 0) elem.innerHTML = '&nbsp;'
            else elem.innerHTML = percent + '%'
          }
          function compressImage(id, k) {
            return $.post(ajaxurl, { action: 'compress_item', id: id }, function(response) {
              progressBar(Math.round((k + 1) / images.length * 100))
              console.log(response)
            });
          }
          $('#bulk').click(function() {
            bulkButton.disabled = true
            images.reduce(function(pp, id, k) {
              return pp.then(function() { return compressImage(id, k) })
            }, $.when())
          });
        });
      </script>
    <?php
}
