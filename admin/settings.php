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
    
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'webpressor-webp-image-converter-optimizer') . '</p></div>';
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
                    <label for="webp_quality"><?php esc_html_e('WebP Quality', 'webpressor-webp-image-converter-optimizer'); ?></label>
                </th>
                <td>
                    <input type="number" name="webp_quality" id="webp_quality" 
                           value="<?php echo esc_attr($quality); ?>" 
                           min="0" max="100" step="1" class="small-text">
                    <p class="description">
                        <?php esc_html_e('Quality level between 0 (worst quality, smaller file) and 100 (best quality, larger file).', 'webpressor-webp-image-converter-optimizer'); ?>
                        <br>
                        <?php esc_html_e('Recommended: 75-85 for a good balance between quality and file size.', 'webpressor-webp-image-converter-optimizer'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="convert_on_upload"><?php esc_html_e('Convert on Upload', 'webpressor-webp-image-converter-optimizer'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="convert_on_upload" id="convert_on_upload" 
                           value="1" <?php checked($convert_on_upload); ?>>
                    <p class="description">
                        <?php esc_html_e('Automatically convert images to WebP when they are uploaded to the media library.', 'webpressor-webp-image-converter-optimizer'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="tbsw_save_settings" class="button button-primary" 
                   value="<?php echo esc_attr__('Save Settings', 'webpressor-webp-image-converter-optimizer'); ?>">
        </p>
    </form>
    
    <div class="tbsw-card">
        <h2><?php esc_html_e('WebP Browser Support', 'webpressor-webp-image-converter-optimizer'); ?></h2>
        <p>
            <?php esc_html_e('WebP is supported by all modern browsers including:', 'webpressor-webp-image-converter-optimizer'); ?>
        </p>
        <ul>
            <li>Google Chrome (version 17+)</li>
            <li>Firefox (version 65+)</li>
            <li>Edge (version 18+)</li>
            <li>Safari (version 14+)</li>
            <li>Opera (version 11.10+)</li>
        </ul>
        <p>
            <?php esc_html_e('For older browsers, the plugin will automatically serve the original JPG/PNG images.', 'webpressor-webp-image-converter-optimizer'); ?>
        </p>
    </div>
</div>