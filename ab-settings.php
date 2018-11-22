<?php

function abraia_settings_init() {
    register_setting('media', 'abraia_api_key');
    register_setting('media', 'abraia_api_secret');
    register_setting('media', 'abraia_upload');
    // register_setting('media', 'abraia_backup');
    register_setting('media', 'abraia_resize');
    register_setting('media', 'abraia_max_width');
    register_setting('media', 'abraia_max_height');

    add_settings_section('abraia_api_section', 'Smart image compression', 'abraia_settings_section', 'media');
}

function abraia_settings_section() {
    ?>
    <table id="abraia_settings" class="form-table">
      <tbody>
        <tr>
          <th scope="row"><label for="abraia_api_key">API Key</label></th>
          <td><input name="abraia_api_key" id="abraia_api_key" type="text" class="regular-text"
              value="<?php echo get_option('abraia_api_key') ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="abraia_api_secret">API Secret</label></th>
          <td><input name="abraia_api_secret" id="abraia_api_secret" type="text" class="regular-text"
              value="<?php echo get_option('abraia_api_secret') ?>" /></td>
        </tr>
        <tr>
          <th scope="row"><label for="abraia_api_status">API Status</label></th>
          <td><a class="button" href="https://abraia.me/auth/login" target="_blank"
              style="background: #fc0;">Get your Abraia API Keys</a></td>
        </tr>
        <tr>
          <th scope="row"><label for="abraia_resize">Resize larger images</label></th>
          <td>
            <label for="abraia_max_width">Max Width</label>
            <input name="abraia_max_width" step="1" min="0" id="abraia_max_width" type="number" class="small-text"
                value="<?php echo get_option('abraia_max_width', 2000) ?>" />
            <label for="abraia_max_height">Max Height</label>
            <input name="abraia_max_height" step="1" min="0" id="abraia_max_height" type="number" class="small-text"
                value="<?php echo get_option('abraia_max_height', 2000) ?>" />
            <p><input name="abraia_resize" id="abraia_resize" type="checkbox" value="1"
                <?php checked(1, get_option('abraia_resize'), true); ?> />
                <label for="abraia_resize">Reduce unnecessarily large images to the specified maximum dimensions</label></p>
          </td>
        </tr>
        <tr>
          <th scope="row"><label for="abraia_upload">Compress on upload</label></th>
          <td>
            <p><input name="abraia_upload" id="abraia_upload" type="checkbox" value="1"
                <?php checked(0, get_option('abraia_upload'), true); ?> />
                <label for="abraia_upload">Compress new images on upload</label></p>
          </td>
        </tr>
      </tbody>
    </table>
    <?php
}

function abraia_admin_notice() {
    if (!get_option('abraia_api_key') or !get_option('abraia_api_secret')) {
        ?>
        <div class="notice notice-error">
          <p><a class="button" href="options-media.php#abraia_settings" style="background: #fc0;">
            Configure your Abraia API Keys</a> to start compressing images</p>
        </div>
        <?php
    }
}
