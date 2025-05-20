<?php
/**
 * Public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    TBS_WebPressor
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

class TBS_WebPressor_Public {

    /**
     * The converter instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      TBS_WebPressor_Converter    $converter    Converter instance.
     */
    protected $converter;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    TBS_WebPressor_Converter    $converter    Converter instance.
     */
    public function __construct($converter) {
        $this->converter = $converter;
    }

    /**
     * Register the hooks for the public-facing functionality
     *
     * @since    1.0.0
     */
    public function tbsw_public_setup_hooks() {
        add_filter('wp_get_attachment_url', array($this, 'tbsw_maybe_serve_webp_version'), 9999);
        add_filter('the_content', array($this, 'tbsw_replace_images_with_webp'));
        add_filter('widget_text', array($this, 'tbsw_replace_images_with_webp'));
        add_filter('widget_custom_html_content', array($this, 'tbsw_replace_images_with_webp'));
    }

    /**
     * Check if WebP version exists and use it if browser supports it
     *
     * @since    1.0.0
     * @param    string    $url    Original attachment URL
     * @return   string            Original or WebP URL
     */
    public function tbsw_maybe_serve_webp_version($url) {
        // Only run for front-end (not admin or REST)
        if (is_admin() || defined('REST_REQUEST')) {
            return $url;
        }
    
        // Check if browser supports WebP
        $http_accept = isset($_SERVER['HTTP_ACCEPT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT'])) : '';
        if (strpos($http_accept, 'image/webp') === false) {
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

    /**
     * Replace image URLs with WebP versions in content
     *
     * @since    1.0.0
     * @param    string    $content    Content to process
     * @return   string                Processed content
     */
    public function tbsw_replace_images_with_webp($content) {
        // Skip admin and feed
        if (is_admin() || is_feed()) {
            return $content;
        }
        // Check if browser supports WebP
        $http_accept = isset($_SERVER['HTTP_ACCEPT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT'])) : '';
        if (strpos($http_accept, 'image/webp') === false) {
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
}