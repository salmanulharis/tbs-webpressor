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
        add_action('wp_ajax_tbsw_start_conversion', array($this, 'tbsw_start_conversion'));
        add_action('wp_ajax_nopriv_tbsw_start_conversion', array($this, 'tbsw_start_conversion'));

        
        
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('tbs-webpressor', false, TBSW_PLUGIN_DIR . '/languages/');

        // Enqueue scripts and styles if needed
        wp_enqueue_style('tbsw-style', TBSW_PLUGIN_URL . 'assets/css/style.css');
        // wp_enqueue_script('tbsw-script', TBSW_PLUGIN_URL . 'assets/js/script.js', array('jquery'), TBSW_VERSION, true);
        wp_enqueue_script('tbsw-backend-script', TBSW_PLUGIN_URL . 'assets/js/backend.js', array(), TBSW_VERSION, true);
        // Localize script with data for JavaScript
        wp_localize_script('tbsw-backend-script', 'tbswData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tbsw-nonce'),
            'plugin_url' => TBSW_PLUGIN_URL,
            'is_admin' => is_admin(),
            'max_upload_size' => wp_max_upload_size(),
            'translations' => array(
                'converting' => __('Converting images...', 'tbs-webpressor'),
                'success' => __('Conversion completed successfully!', 'tbs-webpressor'),
                'error' => __('Error during conversion', 'tbs-webpressor')
            )
        ));
    }

    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        add_options_page('WebP Converter', 'WebP Converter', 'manage_options', 'webp-converter', array($this, 'webp_converter_settings_page'));

        // Main menu item
        add_menu_page(
            'WebPressor Settings',        // Page title
            'WebPressor',                 // Menu title
            'manage_options',            // Capability
            'tbsw-dashboard',            // Menu slug
            array($this, 'tbsw_dashboard_page'),       // Callback function
            'dashicons-admin-generic',   // Icon
            25                           // Position
        );

        // Submenu item 1 (repeats main menu)
        add_submenu_page(
            'tbsw-dashboard',   // Parent slug - connects to the main menu item
            'Dashboard',        // Page title - shown in browser title bar
            'Dashboard',        // Menu title - text shown in the menu
            'manage_options',   // Capability required for access (admin level)
            'tbsw-dashboard',   // Menu slug - unique identifier for this page
            array($this, 'tbsw_dashboard_page') // Callback function that displays the page
        );

        // Submenu item 2
        add_submenu_page(
            'tbsw-dashboard', // Parent slug - connects to the main menu item
            'Settings', // Page title - shown in browser title bar
            'Settings', // Menu title - text shown in the menu
            'manage_options', // Capability required for access (admin level)
            'tbsw-settings', // Menu slug - unique identifier for this page
            array($this, 'tbsw_settings_page') // Callback function that displays the page
        );
    }

    public function tbsw_dashboard_page() {
        include TBSW_PLUGIN_DIR . 'admin/dashboard.php';
    }
    
    public function tbsw_settings_page() {
        include TBSW_PLUGIN_DIR . 'admin/settings.php';
    }

    public function tbsw_start_conversion() {
        write_log("AJAX request received for conversion.");
        $hasMorePages = true; // Default to true

        // Check nonce for security
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'tbsw-nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }

        // Get page parameter from the request
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

        $result = $this->tbsw_convert_attachements($page); // Call the conversion function

        $hasMorePages = $result['hasMorePages']; // Check if there are more pages to process
        write_log("Page: " . $page . ", Has more pages: " . ($hasMorePages ? 'true' : 'false'));

        // Handle the AJAX request for starting conversion
        $response = array('status' => 'success', 'message' => 'Conversion started!', 'hasMorePages' => $hasMorePages);
        wp_send_json($response);
    }

    public function tbsw_convert_attachements($page) {
        $hasMorePages = true;
        $args = array(
            'post_type'         => 'attachment',
            'posts_per_page'    => 10,
            'post_status'       => array(
                                        'publish', 
                                        'pending', 
                                        'draft', 
                                        'auto-draft', 
                                        'future', 
                                        'private', 
                                        'inherit', 
                                        'trash'
                                ),
            'paged'             => $page,
        );

        $attachments = new WP_Query($args);

        if ($attachments->have_posts()) {
            while ($attachments->have_posts()) {
                $attachments->the_post();
                $attachment_id = get_the_ID();
                write_log($attachment_id);
                // Call your conversion function here
                $created_data = $this->tbsw_create_webp($attachment_id);
            }
            wp_reset_postdata();
        }else {
            $hasMorePages = false; // No more attachments to process
            write_log("No more attachments to convert.");
        }

        return array('hasMorePages' => $hasMorePages); // Return true if there are more pages to process
    }

    public function tbsw_create_webp($attachment_id) {
        $count = 0;
        $file = get_attached_file($attachment_id);
    
        if (!$file || !file_exists($file)) {
            error_log("Invalid or missing file for attachment ID: $attachment_id");
            return false;
        }
    
        $mime = get_post_mime_type($attachment_id);
        if (strpos($mime, 'image/') !== 0 || $mime === 'image/webp') {
            return false;
        }
    
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $webp_path = preg_replace('/\.' . preg_quote($ext, '/') . '$/', '.webp', $file);
    
        $quality = intval(get_option('webp_quality', 80));
    
        if ($this->webp_converter_create_webp($file, $webp_path, $quality)) {
            error_log("WebP created: " . $webp_path);
            $count++;
        } else {
            error_log("WebP creation failed for: " . $file);
        }
    
        return array('count' => $count, 'webp_path' => $webp_path);
    }
    
    public function webp_converter_create_webp($source, $destination, $quality = 80) {
        if (!function_exists('imagewebp')) {
            error_log("imagewebp function not available.");
            return false;
        }
    
        if (!file_exists($source)) {
            error_log("Source file does not exist: $source");
            return false;
        }
    
        $info = getimagesize($source);
        if (!$info || !isset($info['mime'])) {
            error_log("Could not get image info from: $source");
            return false;
        }
    
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
                error_log("Unsupported image type: " . $info['mime']);
                return false;
        }
    
        $success = imagewebp($image, $destination, $quality);
        imagedestroy($image);
    
        return $success;
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

    // // 3. Image Conversion Logic (PHP GD)
    // function webp_converter_create_webp($source, $destination, $quality = 80) {
    //     if (!function_exists('imagewebp')) return false;

    //     $info = getimagesize($source);
    //     switch ($info['mime']) {
    //         case 'image/jpeg':
    //             $image = imagecreatefromjpeg($source);
    //             break;
    //         case 'image/png':
    //             $image = imagecreatefrompng($source);
    //             imagepalettetotruecolor($image);
    //             imagealphablending($image, true);
    //             imagesavealpha($image, true);
    //             break;
    //         default:
    //             return false;
    //     }

    //     $success = imagewebp($image, $destination, $quality);
    //     imagedestroy($image);
    //     return $success;
    // }
}