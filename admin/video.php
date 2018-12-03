<?php

// add the new media tab
add_filter('media_upload_tabs', 'my_upload_tab');
function my_upload_tab($tabs) {
    $tabs['mytabname'] = "Abraia Video Store";
    return $tabs;
}

// call the new tab with wp_iframe
add_action('media_upload_mytabname', 'add_my_new_form');
function add_my_new_form() {
    wp_iframe( 'my_new_form' );
}

function my_scripts_method() {
    wp_register_style('videojs', 'https://vjs.zencdn.net/7.0/video-js.min.css');
    wp_enqueue_style('videojs');
    wp_register_script('videojs', 'https://vjs.zencdn.net/7.0/video.min.js');
    wp_enqueue_script('videojs');
}
add_action('wp_enqueue_scripts', 'my_scripts_method');

// the new tab content
function my_new_form() {
    echo media_upload_header(); // This function is used for print media uploader headers etc.
    // echo '<iframe name="iframe" id="iframe_id" src="' . plugins_url('upload.html', __FILE__) . '" style="width: 100%; height: 100%;" ></iframe><script language="javascript">function hello(string){ alert(string); }</script>';
    ?>
      <style>
        h2 {
          text-align: center;
          font-size: 24px;
          font-weight: 400;
        }
        .media-frame {
          background-color: #fcfcfc;
        }
        .media-frame-content {
          display: flex;
          height: calc(100% - 60px);
        }
        .media-embed {
          display: flex;
          background-color: #fff;
          flex-direction: column;
        }
        .media-gallery {
          padding: 8px;
          display: flex;
          flex-wrap: wrap;
          overflow-y: scroll;
          justify-content: center;
          font-size: 12px;
        }
        .media-sidebar {
          background: #f3f3f3;
          width: 267px;
          padding: 0 16px 24px;
          z-index: 75;
          border-left: 1px solid #ddd;
          overflow: auto;
          -webkit-overflow-scrolling: touch;
        }
        .media-toolbar-primary {
          float: right;
          padding: 14px;
        }
        .uploader {
          display: flex;
          align-items: center;
          justify-content: center;
          flex-direction: column;
        }
        #drop {
          width: 450px;
          height: 300px;
          color: #fff;
          background-color: #fc0;
          border-radius: 50px;
          font-size: 20px;
          font-weight: 400;
          line-height: 1.5;
          display: flex;
          text-align: center;
          align-items: center;
          justify-content: center;
          margin: 2rem;
        }
        .progress {
          color:#000!important;
          background-color: #f1f1f1!important
        }
        .progress-bar {
          color: #fff;
          background-color: #fc0;
          text-align: center;
        }
        .thumbnail {
          display: flex;
          max-width: 150px;
          padding: 1em;
        }
        .thumbnail a {
          text-align: center;
        }
        .thumbnail img {
          width: 100%;
        }
        .selected {
          box-shadow: inset 0 0 0 3px #fff,inset 0 0 0 7px #0073aa;
        }
        .is-hidden {
          display: none;
        }
      </style>
        <div class="media-frame-content">
          <div class="media-embed">
            <div class="progress">
              <div id="progress-bar" class="progress-bar" style="width:0%">&nbsp;</div>
            </div>
            <div class="uploader">
              <input id="file-selector" class="is-hidden" type="file" accept="video/mp4" onChange="uploadFiles(event.target.files)" name="file[]" multiple />
              <div id="drop" onClick="document.getElementById('file-selector').click();">Drop files here to upload<br>or<br>Click to select</div>
            </div>
            <h2>Video Gallery</h2>
            <div id="gallery" class="media-gallery"></div>
          </div>
          <div class="media-sidebar">
          </div>
        </div>
        <div class="media-toolbar">
          <div class="media-toolbar-primary search-form">
            <button id="insertButton" type="button" class="button media-button button-primary button-large media-button-select" onClick="insertVideo();" disabled="disabled">Insert into post</button>
          </div>
        </div>
        <script src="<?php echo plugins_url('../assets/client.js', __FILE__); ?>"></script>
        <script type="text/javascript">
          const apiKey = "<?php echo get_option('abraia_api_key') ?>";
          const apiSecret = "<?php echo get_option('abraia_api_secret') ?>";

          const gallery = document.getElementById('gallery');
          const drop = document.getElementById('drop');

          const client = new Client(apiKey, apiSecret);
          let selected = {};
          let folder = '';

          function progressBar(percent) {
            const elem = document.getElementById("progress-bar")
            elem.style.width = percent + '%'
            if (percent === 0) elem.innerHTML = '&nbsp;'
            else elem.innerHTML = percent + '%'
          }

          function progress(evt) {
            const percent = Math.round((evt.loaded * 100) / evt.total)
            progressBar(percent)
          }

          function cancel(e) {
            e.preventDefault();
            return false;
          }

          function uploadFiles(files) {
            for (var i=0; i<files.length; i++) {
              var file = files[i];
              client.uploadFile(file, folder, progress)
                .then(file => {
                  console.log('uploaded', file);
                  client.processVideo(file.path, { fmt: 'hls' })
                    .then(resp => {
                      console.log(resp);
                      listFiles(folder);
                    });
                });
            }
          }

          function listFiles(folder) {
            client.listFiles(folder).then(data => {
              gallery.innerHTML = data.files.map(file => `<div class="thumbnail"><a href="#" onclick="selectVideo(this, '${file.source}')"><img src="${file.thumbnail}" />${file.name}</a></div>`).join('')
              progressBar(0)
            });
          }

          function handleDrop(e) {
            e.preventDefault();
            var dt = e.dataTransfer
            uploadFiles(dt.files)
          }

          drop.addEventListener('dragenter', cancel);
          drop.addEventListener('dragover', cancel);
          drop.addEventListener('drop', handleDrop);

          function selectVideo(ele, src) {
            const ext = src.split('.').pop()
            const path = src.slice(0, -(ext.length+1)) + '/'
            selected.poster = path + 'poster.jpg';
            selected.playlist = path + 'playlist.m3u8';
            insertButton.disabled = false;
            const thumbs = document.getElementsByClassName('thumbnail');
            for(let i = 0; i < thumbs.length; i++) {
              const thumb = thumbs.item(i);
              thumb.classList.remove('selected');
              console.log(thumb.firstChild);
            }
            console.log(ele);
            ele.parentNode.classList.add('selected');
          }

          const insertButton = document.getElementById('insertButton');

          function insertVideo() {
            const text = `<video class="video-js vjs-default-skin" poster="${selected.poster}" autoplay loop preload="auto" data-setup=\'{"fluid": true}\'><source src="${selected.playlist}" type="application/x-mpegURL"></video>`;
            parent.send_to_editor(text);
          }

          function send_to_editor(string) {
            window.parent.send_to_editor(string);
            window.parent.tb_remove();
          }

          client.check().then((userid) => {
            folder = userid + '/videos/';
            listFiles(folder);
          });
        </script>
    <?php
}
