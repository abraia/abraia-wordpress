<?php

add_action('admin_enqueue_scripts', 'abraia_admin_style');

function abraia_admin_style() {
    wp_register_style('abraia_admin_css', plugins_url('../assets/styles.css', __FILE__));
    wp_enqueue_style('abraia_admin_css');
}

add_action('admin_init', 'abraia_settings_init');

function abraia_settings_init() {
    add_filter('jpeg_quality', function($arg){return 85;});

    register_setting('abraia', 'abraia_settings');
}

function get_abraia_settings() {
    // register_setting('media', 'abraia_backup');
    $defaults = array(
      'api_key' => '',
      'resize' => true,
      'max_width' => 2000,
      'max_height' => 2000,
      'upload' => false
    );
    $abraia_settings = wp_parse_args(get_option('abraia_settings'), $defaults);
    return $abraia_settings;
}

add_action('admin_menu', 'add_abraia_settings_page');

function add_abraia_settings_page() {
    add_options_page('Abraia settings', 'Abraia', 'manage_options', 'abraia', 'abraia_settings_page');
}

function abraia_settings_page() {
    $abraia_settings = get_abraia_settings();
    ?>
    <div class="abraia-panel">
      <div class="abraia-header">
        <h1>Settings <span style="color:#fc0">Abraia</span></h1>
        <p style="color:#fafafa">The smart web image optimization plugin</p>
      </div>
      <div class="abraia-content">
        <form method="post" action="options.php">
          <?php settings_fields('abraia'); ?>
          <table class="form-table">
            <tr>
              <th scope="row">API Key</th>
              <td><input type="text" name="abraia_settings[api_key]" value="<?php echo $abraia_settings['api_key']; ?>" /></td>
            </tr>
            <tr>
              <th scope="row">Resize larger images</th>
              <td>
                <label for="abraia_settings[max_width]">Max Width</label>
                <input name="abraia_settings[max_width]" step="1" min="0" type="number" class="small-text"
                    value="<?php echo $abraia_settings['max_width'] ?>" />
                <label for="abraia_settings[max_height]">Max Height</label>
                <input name="abraia_settings[max_height]" step="1" min="0" type="number" class="small-text"
                    value="<?php echo $abraia_settings['max_height'] ?>" />
                <p><input name="abraia_settings[resize]" type="checkbox" value="1"
                  <?php checked(1, $abraia_settings['resize'], true); ?> />
                  <label for="abraia_settings[resize]">Reduce unnecessarily large images to the specified maximum dimensions</label></p>
              </td>
            </tr>
            <tr>
              <th scope="row">Compress on upload</th>
              <td>
                <p><input name="abraia_settings[upload]" type="checkbox" value="1"
                  <?php checked(0, $abraia_settings['upload'], true); ?> />
                  <label for="abraia_settings[upload]">Compress new images on upload</label></p>
              </td>
            </tr>
          </table>
          <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
          </p>
        </form>
      </div>
    </div>
    <?php
}

add_action('admin_notices', 'abraia_admin_notice');

function abraia_admin_notice() {
    global $abraia;
    $current_user = wp_get_current_user();
    $abraia_settings = get_abraia_settings();
    if (!$abraia_settings['api_key']) {
        try {
            echo $abraia->loadUser()['user']['id'];
            $abraia_status = true;
        } catch (Exception $e) {
            echo 'Exception catched: ' . $e->getMessage();
            $abraia_status = false;
        }
        ?>
        <div class="abraia-panel">
          <div class="abraia-header">
            <h2>Welcome to <span style="color:#fc0">Abraia</span>!</h2>
            <span class="dashicons dashicons-dismiss" style="color: #fff;float: right;"></span>
            <p style="color:#fafafa">The smart web image optimization plugin</p>
          </div>
          <div class="abraia-content">
            <div class="abraia-row">
              <div class="abraia-column">
                <h3>1. Get your FREE API Key</h3>
                <p>Enter your email:</p>
                <input type="email" value="<?php echo $current_user->user_email ?>" placeholder="Enter your email" id="user_email" />
                <button class="button button-primary button-hero" onClick="fetch('https://api.abraia.me/users', { method: 'POST', body: JSON.stringify({ email: document.getElementById('user_email').value }), headers: { 'Content-Type': 'application/json' } }).then(res => res.json()).then(response => console.log('Success:', JSON.stringify(response))).catch(error => console.error('Error:', error));">Get API Key</button>
              </div>
              <div class="abraia-column">
                <h3>2. Enter your FREE API Key</h3>
                <p>Enter your API Key:</p>
                <form method="post" action="options.php">
                  <?php settings_fields('abraia'); ?>
                  <input type="text" name="abraia_settings[api_key]" value="<?php echo $abraia_settings['api_key']; ?>" />
                  <input type="submit" name="submit" id="submit" class="button button-primary button-hero" value="Save API Key" />
                </form>
              </div>
              <div class="abraia-column welcome-panel-last">
                <h3>3. Optimize your images</h3>
                <p>API Status:</p>
                <input type="text" style="background: #fc0;" value="<?php echo ($abraia_status) ? 'Everything OK' : 'Wrong API Key'; ?>" readonly />
                <a class="button button-primary button-hero" href="upload.php?page=abraia_bulk_page">Optimize images</a>
              </div>
            </div>
          </div>
        </div>
        <?php
    }
}
