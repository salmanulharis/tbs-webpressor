<?php
/**
 * Admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    TBS_WebPressor
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

class TBS_WebPressor_Admin {

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
     * Register the hooks for the admin area
     *
     * @since    1.0.0
     */
    public function tbswebpressor_admin_setup_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'tbswebpressor_enqueue_admin_styles'));
        add_action('admin_menu', array($this, 'tbswebpressor_register_admin_menu'));
        add_filter('wp_generate_attachment_metadata', array($this, 'tbswebpressor_convert_on_upload'), 99, 2);
    }

    /**
     * Enqueue admin-specific styles
     *
     * @since    1.0.0
     */
    public function tbswebpressor_enqueue_admin_styles() {
        wp_enqueue_style('tbswebpressor-admin-style', TBSWEBPRESSOR_PLUGIN_URL . 'assets/css/admin.css', array(), TBSWEBPRESSOR_VERSION);
    }

    /**
     * Register admin menu
     *
     * @since    1.0.0
     */
    public function tbswebpressor_register_admin_menu() {
        // Main menu item
        add_menu_page(
            'WebPressor Settings',        // Page title
            'WebPressor',                 // Menu title
            'manage_options',            // Capability
            'tbswebpressor-dashboard',            // Menu slug
            array($this, 'tbswebpressor_dashboard_page'),       // Callback function
            'dashicons-admin-generic',   // Icon
            25                           // Position
        );

        // Submenu item 1 (repeats main menu)
        add_submenu_page(
            'tbswebpressor-dashboard',   // Parent slug - connects to the main menu item
            'Dashboard',        // Page title - shown in browser title bar
            'Dashboard',        // Menu title - text shown in the menu
            'manage_options',   // Capability required for access (admin level)
            'tbswebpressor-dashboard',   // Menu slug - unique identifier for this page
            array($this, 'tbswebpressor_dashboard_page') // Callback function that displays the page
        );

        // Submenu item 2
        add_submenu_page(
            'tbswebpressor-dashboard', // Parent slug - connects to the main menu item
            'Settings', // Page title - shown in browser title bar
            'Settings', // Menu title - text shown in the menu
            'manage_options', // Capability required for access (admin level)
            'tbswebpressor-settings', // Menu slug - unique identifier for this page
            array($this, 'tbswebpressor_settings_page') // Callback function that displays the page
        );
    }

    /**
     * Display the dashboard page
     *
     * @since    1.0.0
     */
    public function tbswebpressor_dashboard_page() {
        include TBSWEBPRESSOR_PLUGIN_DIR . 'admin/dashboard.php';
    }
    
    /**
     * Display the settings page
     *
     * @since    1.0.0
     */
    public function tbswebpressor_settings_page() {
        include TBSWEBPRESSOR_PLUGIN_DIR . 'admin/settings.php';
    }

    /**
     * Convert image on upload
     *
     * @since    1.0.0
     * @param    int    $attachment_id    Attachment ID
     */
    public function tbswebpressor_convert_on_upload($metadata, $attachment_id) {
        // Get file information
        $file_type = get_post_mime_type($attachment_id);

        // Only process image attachments
        if (strpos($file_type, 'image/') === 0 && $file_type !== 'image/webp') {
            // Get plugin settings
            $option = get_option('tbswebpressor_convert_on_upload', array());
            
            // Check if auto-conversion on upload is enabled
            if ($option) {
                // Use the converter instance to create WebP version
                $converted = $this->converter->tbswebpressor_create_webp($attachment_id);
            }
        }
        return $metadata;
    }
    
}