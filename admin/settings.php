<?php

add_action('admin_enqueue_scripts', 'abraia_admin_style');

function abraia_admin_style() {
    wp_register_style('abraia_admin_css', plugins_url('../assets/styles.css', __FILE__));
    wp_enqueue_style('abraia_admin_css');
}

add_action('admin_init', 'abraia_settings_init');

function abraia_settings_init() {
    add_filter('jpeg_quality', function($arg) { return 85; });
    register_setting('abraia', 'abraia_settings', 'validate_abraia_settings');
    $defaults = default_abraia_settings();
    $options = wp_parse_args(get_option('abraia_settings'), $defaults);
    update_option('abraia_settings', $options);
}

add_action('admin_menu', 'add_abraia_settings_page');

function add_abraia_settings_page() {
    add_options_page('Abraia settings', 'Abraia', 'manage_options', 'abraia', 'abraia_settings_page');
}

add_action('admin_notices', 'abraia_admin_notice');

function get_abraia_settings() {
    $options = get_option('abraia_settings');
    return $options;
}

function default_abraia_settings() {
    $defaults = array(
      'api_key' => '',
      'folder' => 'wordpress',
      'resize' => true,
      'max_width' => 2000,
      'max_height' => 2000,
      'thumbnails' => true,
      'min_size' => 15,
      'jpeg' => true,
      'png' => true,
      'gif' => true,
      'svg' => true,
      'webp' => true,
      'upload' => false
    );
    return $defaults;
}

function validate_abraia_settings($input) {
    $input['resize'] = ($input['resize'] == 1) ? 1 : 0;
    $input['thumbnails'] = ($input['thumbnails'] == 1) ? 1 : 0;
    $input['jpeg'] = ($input['jpeg'] == 1) ? 1 : 0;
    $input['png'] = ($input['png'] == 1) ? 1 : 0;
    $input['gif'] = ($input['gif'] == 1) ? 1 : 0;
    $input['svg'] = ($input['svg'] == 1) ? 1 : 0;
    $input['webp'] = ($input['webp'] == 1) ? 1 : 0;
    $input['upload'] = ($input['upload'] == 1) ? 1 : 0;
    return $input;
}

function get_abraia_user() {
    global $abraia_settings;
    global $abraia;
    try {
        $abraia->setKey($abraia_settings['api_key']);
        $user = $abraia->user();
    } catch (Exception $e) {
        // echo 'Exception catched: ' . $e->getMessage();
    }
    return $user;
}

function abraia_settings_page() {
    $settings = get_abraia_settings();
    $user = get_abraia_user();
    $credits = max($user['credits'], 0);
    ?>
    <div class="abraia-panel">
      <div class="abraia-header is-dark" style="display:block">
        <a href="https://abraia.me" target="_blank" style="float:right">
          <img src="<?php echo plugins_url('../assets/logo.png', __FILE__); ?>" style="height:40px">
        </a>  
        <h1><?php esc_html_e('Settings', 'abraia') ?></h1>
      </div>
    </div>
    <div style="display:flex">
      <div style="width:75%">
        <form method="post" action="options.php">
          <?php settings_fields('abraia'); ?>
          <div class="abraia-panel">
            <div class="abraia-content">
              <h2><?php esc_html_e('General settings', 'abraia'); ?></h2>
              <table class="form-table">
                <tr>
                  <th scope="row"><?php esc_html_e('Abraia key', 'abraia'); ?></th>
                  <td>
                    <input type="text" name="abraia_settings[api_key]" value="<?php echo $settings['api_key']; ?>" style="width:80%;" />
                    <img src="<?php echo ($user) ? plugins_url('../assets/checkmark.png', __FILE__) :  plugins_url('../assets/delete.png', __FILE__); ?>" style="vertical-align:middle;width:28px;margin-left:16px">
                  </td>
                </tr>
                <tr>
                  <th scope="row"><?php esc_html_e('Cloud folder', 'abraia'); ?></th>
                  <td>
                    <input type="text" name="abraia_settings[folder]" value="<?php echo $settings['folder']; ?>" />
                  </td>
                </tr>
              </table>
            </div>
          </div>
          <div class="abraia-panel">
            <div class="abraia-content">
              <h2><?php esc_html_e('Image optimization', 'abraia'); ?></h2>
              <table class="form-table">
                <tr>
                  <th scope="row"><?php esc_html_e('Resize larger images', 'abraia'); ?></th>
                  <td>
                    <label for="abraia_settings[max_width]"><?php esc_html_e('Max Width', 'abraia'); ?></label>
                    <input name="abraia_settings[max_width]" step="1" min="0" type="number" class="small-text" value="<?php echo $settings['max_width'] ?>" />
                    <label for="abraia_settings[max_height]"><?php esc_html_e('Max Height', 'abraia'); ?></label>
                    <input name="abraia_settings[max_height]" step="1" min="0" type="number" class="small-text" value="<?php echo $settings['max_height'] ?>" />
                    <p><input name="abraia_settings[resize]" type="checkbox" value="1" <?php checked($settings['resize'], 1); ?> />
                      <label for="abraia_settings[resize]"><?php esc_html_e('Reduce unnecessarily large images to the specified maximum dimensions', 'abraia'); ?></label></p>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><?php esc_html_e('Compress thumbnails', 'abraia'); ?></th>
                  <td>
                    <p><input name="abraia_settings[thumbnails]" type="checkbox" value="1" <?php checked($settings['thumbnails'], 1); ?> />
                      <label for="abraia_settings[thumbnails]"><?php esc_html_e('Compress generated thumbnails bigger than ', 'abraia'); ?></label>
                      <input name="abraia_settings[min_size]" step="1" min="0" type="number" class="small-text" value="<?php echo $settings['min_size'] ?>" /> KB</p>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><?php esc_html_e('Formats to be optimized', 'abraia'); ?></th>
                  <td>
                    <p><input name="abraia_settings[jpeg]" type="checkbox" value="1" <?php checked($settings['jpeg'], 1); ?> />
                      <label for="abraia_settings[jpeg]"><?php esc_html_e('Compress JPEG files', 'abraia'); ?></label></p>
                    <p><input name="abraia_settings[png]" type="checkbox" value="1" <?php checked($settings['png'], 1); ?> />
                      <label for="abraia_settings[png]"><?php esc_html_e('Compress PNG files', 'abraia'); ?></label></p>
                    <p><input name="abraia_settings[gif]" type="checkbox" value="1" <?php checked($settings['gif'], 1); ?> />
                      <label for="abraia_settings[gif]"><?php esc_html_e('Compress GIF files', 'abraia'); ?></label></p>
                    <p><input name="abraia_settings[svg]" type="checkbox" value="1" <?php checked($settings['svg'], 1); ?> />
                      <label for="abraia_settings[svg]"><?php esc_html_e('Compress SVG files', 'abraia'); ?></label></p>
                    <p><input name="abraia_settings[webp]" type="checkbox" value="1" <?php checked($settings['webp'], 1); ?> />
                      <label for="abraia_settings[webp]"><?php esc_html_e('Compress WebP files', 'abraia'); ?></label></p>
                  </td>
                </tr>
                <tr>
                  <th scope="row"><?php esc_html_e('Compress on upload', 'abraia'); ?></th>
                  <td>
                    <p><input name="abraia_settings[upload]" type="checkbox" value="1" <?php checked($settings['upload'], 1); ?> />
                      <label for="abraia_settings[upload]"><?php esc_html_e('Compress new images on upload', 'abraia'); ?></label></p>
                  </td>
                </tr>
              </table>
            </div>
          </div>
          <p class="submit">
            <input type="submit" name="submit" class="button button-hero is-blue" value="<?php esc_html_e('Save Changes', 'abraia') ?>" />
          </p>
        </form>
      </div>
      <div style="width:25%">
        <div class="abraia-panel">
          <div class="abraia-content is-light">
            <h2 class="is-centered"><?php esc_html_e('Your Account', 'abraia') ?></h2>
            <div class="is-light" style="display:flex;flex-direction:column;align-items:center;justify-content:center">
              <p class="is-centered is-2"><?php esc_html_e('Available', 'abraia'); ?><br>
              <span class="is-1"><b><?php echo size_format($credits * 104858, 1); ?></b></span><br></p>
              <a class="button button-hero is-yellow" style="font-size:16px;width:unset" href="https://abraia.me/payment/<?php echo ($user) ? '?email=' . $user['email'] : '' ?>" target="_blank"><?php esc_html_e('Buy More Megas', 'abraia'); ?></a>
              <p><?php esc_html_e('Total processed', 'abraia') ?> <?php echo $user['transforms']; ?> <?php esc_html_e('images and', 'abraia') ?> <?php echo size_format($user['bandwidth'], 1); ?>
            </div>
          </div>
        </div>
        <div class="abraia-panel">
          <div class="abraia-content is-dark">
            <p><b><?php esc_html_e('Support', 'abraia') ?></b><br><?php esc_html_e('If you have any question, doubt, or issue, just send us an email.', 'abraia') ?><br></p>
            <p><a class="button is-yellow" href="mailto:support@abraiasoftware.com?subject=Support Wordpress <?php echo ($user) ? $user['email'] : '' ?>"><?php esc_html_e('Get Support', 'abraia') ?></a></p>
          </div>
        </div>
      </div>
    </div>
    <?php
}

function abraia_admin_notice() {
    $current_user = wp_get_current_user();
    $settings = get_abraia_settings();
    $user = get_abraia_user();
    if (!$user) {
        ?>
        <div class="abraia-panel">
          <div class="abraia-header is-dark" style="display:block">
            <a href="https://abraia.me" target="_blank" style="float:right">
              <img src="<?php echo plugins_url('../assets/logo.png', __FILE__); ?>" style="height:40px">
            </a>
            <h2><?php esc_html_e('Welcome!', 'abraia') ?></h2>
          </div>
        </div>
        <div class="abraia-row">
          <div class="abraia-panel">
            <div class="abraia-content">
              <h3>1. <?php esc_html_e('Get your FREE API Key', 'abraia') ?></h3>
              <p><?php esc_html_e('Enter your email', 'abraia') ?>:</p>
              <input type="email" class="is-fullwidth" value="<?php echo $current_user->user_email ?>" placeholder="Enter your email" id="user_email" />
              <button class="button button-hero is-blue" style="margin-top:18px" onClick="fetch('https://api.abraia.me/users', { method: 'POST', body: JSON.stringify({ email: document.getElementById('user_email').value }), headers: { 'Content-Type': 'application/json' } }).then(res => res.json()).then(response => console.log('Success:', JSON.stringify(response))).catch(error => console.error('Error:', error));"><?php esc_html_e('Get API Key', 'abraia') ?></button>
            </div>
          </div>
          <div class="abraia-panel">
            <div class="abraia-content">
              <h3>2. <?php esc_html_e('Enter your FREE API Key', 'abraia') ?></h3>
              <p><?php esc_html_e('Enter your API Key', 'abraia') ?>:</p>
              <form method="post" action="options.php">
                <?php settings_fields('abraia'); ?>
                <input type="text" class="is-fullwidth" name="abraia_settings[api_key]" value="<?php echo $settings['api_key']; ?>" />
                <input type="hidden" name="abraia_settings[folder]" value="<?php echo $settings['folder']; ?>" />
                <input type="hidden" name="abraia_settings[resize]" value="<?php echo $settings['resize']; ?>">
                <input type="hidden" name="abraia_settings[max_width]" value="<?php echo $settings['max_width']; ?>">
                <input type="hidden" name="abraia_settings[max_height]" value="<?php echo $settings['max_height']; ?>">
                <input type="hidden" name="abraia_settings[thumbnails]" value="<?php echo $settings['thumbnails']; ?>">
                <input type="hidden" name="abraia_settings[min_size]" value="<?php echo $settings['min_size']; ?>">
                <input type="hidden" name="abraia_settings[jpeg]" value="<?php echo $settings['jpeg']; ?>">
                <input type="hidden" name="abraia_settings[png]" value="<?php echo $settings['png']; ?>">
                <input type="hidden" name="abraia_settings[gif]" value="<?php echo $settings['gif']; ?>">
                <input type="hidden" name="abraia_settings[svg]" value="<?php echo $settings['svg']; ?>">
                <input type="hidden" name="abraia_settings[webp]" value="<?php echo $settings['webp']; ?>">
                <input type="hidden" name="abraia_settings[upload]" value="<?php echo $settings['upload']; ?>">
                <input type="submit" name="submit" class="button button-hero is-blue" style="margin-top:18px" value="<?php esc_html_e('Save API Key', 'abraia') ?>" />
              </form>
            </div>
          </div>
          <div class="abraia-panel">
            <div class="abraia-content">
              <h3>3. <?php esc_html_e('Optimize your images', 'abraia') ?></h3>
              <p><?php esc_html_e('API Status', 'abraia') ?>:</p>
              <input type="text" class="is-fullwidth<?php echo ($user) ? ' is-green' : ' is-red'?>;" value="<?php echo ($user) ? __('Everything OK', 'abraia') : __('Wrong API Key', 'abraia'); ?>" readonly />
              <a class="button button-hero is-blue" style="margin-top:18px" href="upload.php?page=abraia_bulk_page"><?php esc_html_e('Optimize images', 'abraia') ?></a>
            </div>
          </div>
        </div>
        <?php
    } elseif ($user['credits'] < 1000) {
        ?>
        <div class="abraia-panel">
          <div class="abraia-message">
            <a class="button is-dark" href="https://abraia.me/payment/<?php echo ($user) ? '?email=' . $user['email'] : '' ?>" target="_blank"><?php esc_html_e('Buy More Megas', 'abraia'); ?></a>
            <h2><?php esc_html_e('Your Abraia optimization quote is expiring!', 'abraia') ?></h2>
          </div>
        </div>
        <?php
    }
}
