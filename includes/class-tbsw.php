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
        add_action('admin_menu', array($this, 'register_admin_menu'));
        // add_filter('wp_generate_attachment_metadata', array($this, 'webp_converter_on_upload'), 10, 2);
        add_filter('wp_get_attachment_url', array($this, 'tbsw_maybe_serve_webp_version'), 9999);
        // add_filter('wp_get_attachment_metadata', array($this, 'tbsw_maybe_serve_webp_version'), 9999);
        add_filter('the_content', array($this, 'tbsw_replace_images_with_webp'));
        add_filter('widget_text', array($this, 'tbsw_replace_images_with_webp'));
        add_filter('widget_custom_html_content', array($this, 'tbsw_replace_images_with_webp'));

        // Ajax actions
        add_action('wp_ajax_tbsw_start_conversion', array($this, 'tbsw_start_conversion'));
        add_action('wp_ajax_nopriv_tbsw_start_conversion', array($this, 'tbsw_start_conversion'));
        add_action('wp_ajax_tbsw_get_media_count', array($this, 'tbsw_get_media_count'));
        add_action('wp_ajax_nopriv_tbsw_get_media_count', array($this, 'tbsw_get_media_count'));
        add_action('wp_ajax_tbsw_get_pending_media_count', array($this, 'tbsw_get_pending_media_count'));
        add_action('wp_ajax_nopriv_tbsw_get_pending_media_count', array($this, 'tbsw_get_pending_media_count'));
        add_action('wp_ajax_tbsw_reset_conversion', array($this, 'tbsw_reset_conversion'));
        add_action('wp_ajax_nopriv_tbsw_reset_conversion', array($this, 'tbsw_reset_conversion'));

        
        
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

    function tbsw_maybe_serve_webp_version($url) {
        // Only run for front-end (not admin or REST)
        if (is_admin() || defined('REST_REQUEST')) {
            return $url;
        }
    
        // Check if browser supports WebP
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'image/webp') === false) {
            return $url;
        }
    
        // Check if it's an image
        $ext = pathinfo($url, PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png'])) {
            return $url;
        }
    
        // Construct WebP URL
        $webp_url = preg_replace('/\.' . preg_quote($ext, '/') . '$/i', '.webp', $url);
    
        // Convert URL to path to check file exists
        $upload_dir = wp_upload_dir();
        $relative_path = str_replace($upload_dir['baseurl'], '', $webp_url);
        $webp_path = $upload_dir['basedir'] . $relative_path;
    
        // If WebP version exists, return it
        if (file_exists($webp_path)) {
            return $webp_url;
        }
    
        return $url;
    }

    function tbsw_replace_images_with_webp($content) {
        // Skip admin and feed
        if (is_admin() || is_feed()) {
            return $content;
        }
    
        // Check if browser supports WebP
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'image/webp') === false) {
            return $content;
        }
    
        // Process all <img> tags with .jpg, .jpeg, .png
        return preg_replace_callback(
            '#<img[^>]+src=["\']([^"\']+\.(jpg|jpeg|png))["\'][^>]*>#i',
            function ($matches) {
                $original_tag = $matches[0];
                $original_url = $matches[1];
    
                // Generate webp URL
                $webp_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $original_url);
    
                // Check if .webp file exists
                $upload_dir = wp_upload_dir();
                $relative_path = str_replace($upload_dir['baseurl'], '', $webp_url);
                $webp_path = $upload_dir['basedir'] . $relative_path;
    
                if (file_exists($webp_path)) {
                    // Replace src with .webp version
                    return str_replace($original_url, $webp_url, $original_tag);
                }
    
                // If no webp version, return original
                return $original_tag;
            },
            $content
        );
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
    
        // Convert original image
        if ($this->webp_converter_create_webp($file, $webp_path, $quality)) {
            error_log("WebP created: " . $webp_path);
            $normalized_path = str_replace('\\', '/', $webp_path);
            update_post_meta($attachment_id, 'tbsw_webp_path', $normalized_path);
            $count++;
        } else {
            error_log("WebP creation failed for: " . $file);
        }
    
        // Convert thumbnails
        $metadata = wp_get_attachment_metadata($attachment_id);
        if (!empty($metadata['sizes'])) {
            $upload_dir = wp_upload_dir();
            $base_dir = trailingslashit($upload_dir['basedir']);
            $subdir = trailingslashit(dirname($metadata['file']));
    
            foreach ($metadata['sizes'] as $size_name => $size_info) {
                $thumb_path = $base_dir . $subdir . $size_info['file'];
    
                if (!file_exists($thumb_path)) continue;
    
                $thumb_ext = pathinfo($thumb_path, PATHINFO_EXTENSION);
                $thumb_webp_path = preg_replace('/\.' . preg_quote($thumb_ext, '/') . '$/', '.webp', $thumb_path);
    
                if ($this->webp_converter_create_webp($thumb_path, $thumb_webp_path, $quality)) {
                    error_log("WebP thumbnail created: $thumb_webp_path");
                    $count++;
                } else {
                    error_log("WebP thumbnail creation failed: $thumb_path");
                }
            }
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


    public function tbsw_get_media_count() {
        // Check nonce for security
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'tbsw-nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }

        $args = array(
            'post_type' => 'attachment',
            'post_status' => array(
                'publish', 
                'pending', 
                'draft', 
                'auto-draft', 
                'future', 
                'private', 
                'inherit', 
                'trash'
            ),
            'posts_per_page' => -1,
        );

        $attachments = new WP_Query($args);
        $count = $attachments->found_posts;

        wp_send_json_success(array('count' => $count));
    }

    public function tbsw_get_pending_media_count() {
        // Check nonce for security
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'tbsw-nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }

        $args = array(
            'post_type' => 'attachment',
            'post_status' => array(
                'publish', 
                'pending', 
                'draft', 
                'auto-draft', 
                'future', 
                'private', 
                'inherit', 
                'trash'
            ),
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'tbsw_webp_path',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => 'tbsw_webp_path',
                    'value' => '',
                    'compare' => '='
                )
            ),
        );

        $attachments = new WP_Query($args);
        $count = $attachments->found_posts;

        wp_send_json_success(array('count' => $count));
    }

    public function tbsw_reset_conversion() {
        // Check nonce for security
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'tbsw-nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
    
        $args = array(
            'post_type'      => 'attachment',
            'post_status'    => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
            'posts_per_page' => -1,
            'post_mime_type' => 'image',
        );
    
        $attachments = new WP_Query($args);
    
        if ($attachments->have_posts()) {
            while ($attachments->have_posts()) {
                $attachments->the_post();
                $attachment_id = get_the_ID();
    
                $file = get_attached_file($attachment_id);
                if (!$file || !file_exists($file)) {
                    continue;
                }
    
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $webp_main = preg_replace('/\.' . preg_quote($ext, '/') . '$/', '.webp', $file);
    
                // Delete main WebP if exists
                if (file_exists($webp_main)) {
                    unlink($webp_main);
                }
    
                // Delete thumbnail WebPs
                $metadata = wp_get_attachment_metadata($attachment_id);
                $upload_dir = wp_upload_dir();
                $base_dir   = trailingslashit($upload_dir['basedir']);
                $subdir     = isset($metadata['file']) ? dirname($metadata['file']) . '/' : '';
    
                if (!empty($metadata['sizes'])) {
                    foreach ($metadata['sizes'] as $size) {
                        if (!empty($size['file'])) {
                            $thumb_file = $base_dir . $subdir . $size['file'];
                            $ext_thumb  = pathinfo($thumb_file, PATHINFO_EXTENSION);
                            $webp_thumb = preg_replace('/\.' . preg_quote($ext_thumb, '/') . '$/', '.webp', $thumb_file);
                            if (file_exists($webp_thumb)) {
                                unlink($webp_thumb);
                            }
                        }
                    }
                }
    
                // Remove meta for original webp path
                delete_post_meta($attachment_id, 'tbsw_webp_path');
            }
    
            wp_reset_postdata();
        }
    
        wp_send_json_success(array('message' => 'Conversion reset successfully!'));
    }
    
}