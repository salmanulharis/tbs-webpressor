<?php
/**
 * Settings admin page template
 *
 * @package TBS_WebPressor
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Save settings if form is submitted
if (isset($_POST['tbsw_save_settings']) && check_admin_referer('tbsw_settings_nonce')) {
    $quality = isset($_POST['webp_quality']) ? intval($_POST['webp_quality']) : 80;
    $quality = max(0, min(100, $quality)); // Ensure quality is between 0-100
    
    update_option('tbsw_webp_quality', $quality);
    
    // Save convert_on_upload setting
    $convert_on_upload = isset($_POST['convert_on_upload']) ? 1 : 0;
    update_option('tbsw_convert_on_upload', $convert_on_upload);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'tbs-webpressor') . '</p></div>';
}

// Get current settings
$quality = get_option('tbsw_webp_quality', 80);
$convert_on_upload = get_option('tbsw_convert_on_upload', 1);
?>

<div class="wrap tbsw-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('tbsw_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="webp_quality"><?php _e('WebP Quality', 'tbs-webpressor'); ?></label>
                </th>
                <td>
                    <input type="number" name="webp_quality" id="webp_quality" 
                           value="<?php echo esc_attr($quality); ?>" 
                           min="0" max="100" step="1" class="small-text">
                    <p class="description">
                        <?php _e('Quality level between 0 (worst quality, smaller file) and 100 (best quality, larger file).', 'tbs-webpressor'); ?>
                        <br>
                        <?php _e('Recommended: 75-85 for a good balance between quality and file size.', 'tbs-webpressor'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="convert_on_upload"><?php _e('Convert on Upload', 'tbs-webpressor'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="convert_on_upload" id="convert_on_upload" 
                           value="1" <?php checked($convert_on_upload); ?>>
                    <p class="description">
                        <?php _e('Automatically convert images to WebP when they are uploaded to the media library.', 'tbs-webpressor'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="tbsw_save_settings" class="button button-primary" 
                   value="<?php _e('Save Settings', 'tbs-webpressor'); ?>">
        </p>
    </form>
    
    <div class="tbsw-card">
        <h2><?php _e('WebP Browser Support', 'tbs-webpressor'); ?></h2>
        <p>
            <?php _e('WebP is supported by all modern browsers including:', 'tbs-webpressor'); ?>
        </p>
        <ul>
            <li>Google Chrome (version 17+)</li>
            <li>Firefox (version 65+)</li>
            <li>Edge (version 18+)</li>
            <li>Safari (version 14+)</li>
            <li>Opera (version 11.10+)</li>
        </ul>
        <p>
            <?php _e('For older browsers, the plugin will automatically serve the original JPG/PNG images.', 'tbs-webpressor'); ?>
        </p>
    </div>
</div>