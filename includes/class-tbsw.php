<?php

class TBS_WebPressor {
    
    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Constructor code here
    }

    /**
     * Run the plugin.
     */
    public function run() {
        // Add your plugin's main functionality here
        add_action('init', array($this, 'init'));
        add_action('add_attachment', array($this, 'process_image'));
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_init', array($this, 'register_admin_settings'));
        add_filter('wp_generate_attachment_metadata', array($this, 'webp_converter_on_upload'), 10, 2);
        
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('tbs-webpressor', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Enqueue scripts and styles if needed
        wp_enqueue_style('tbs-webpressor-style', TBSW_PLUGIN_URL . 'assets/css/style.css');
        wp_enqueue_script('tbs-webpressor-script', TBSW_PLUGIN_URL . 'assets/js/script.js', array('jquery'), TBSW_VERSION, true);
    }

    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        add_options_page('WebP Converter', 'WebP Converter', 'manage_options', 'webp-converter', array($this, 'webp_converter_settings_page'));
    }

    /**
     * Register admin settings
     */
    public function register_admin_settings() {
        register_setting('webp_converter_settings', 'webp_quality');
        add_settings_section('webp_main', 'Main Settings', null, 'webp-converter');
        add_settings_field('webp_quality', 'Compression Quality (0-100)', array($this, 'webp_quality_field'), 'webp-converter', 'webp_main');
    }

    /**
     * Quality field callback
     */
    public function webp_quality_field() {
        $quality = get_option('webp_quality', 80);
        echo "<input type='number' name='webp_quality' value='$quality' min='0' max='100'>";
    }

    /**
     * Settings page callback
     */
    public function webp_converter_settings_page() {
        ?>
        <div class="wrap">
            <h2>WebP Converter Settings</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('webp_converter_settings');
                do_settings_sections('webp-converter');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Process image when uploaded
     */
    public function process_image($attachment_id) {
        // Add image processing code here
    }

    // 2. Convert to WebP on Upload
    function webp_converter_on_upload($metadata, $attachment_id) {
        $file = get_attached_file($attachment_id);
        $mime = get_post_mime_type($attachment_id);

        if (strpos($mime, 'image/') !== 0 || $mime === 'image/webp') return $metadata;

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $webp_path = preg_replace('/\.' . preg_quote($ext, '/') . '$/', '.webp', $file);

        $quality = intval(get_option('webp_quality', 80));

        if (webp_converter_create_webp($file, $webp_path, $quality)) {
            error_log("WebP created: " . $webp_path);
        } else {
            error_log("WebP creation failed for: " . $file);
        }

        return $metadata;
    }

    // 3. Image Conversion Logic (PHP GD)
    function webp_converter_create_webp($source, $destination, $quality = 80) {
        if (!function_exists('imagewebp')) return false;

        $info = getimagesize($source);
        switch ($info['mime']) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            default:
                return false;
        }

        $success = imagewebp($image, $destination, $quality);
        imagedestroy($image);
        return $success;
    }
}