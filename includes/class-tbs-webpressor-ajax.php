<?php
/**
 * AJAX functionality of the plugin.
 *
 * @since      1.0.0
 * @package    TBS_WebPressor
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

class TBS_WebPressor_Ajax {

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
     * Register all AJAX handlers
     *
     * @since    1.0.0
     */
    public function setup_hooks() {
        // Ajax actions - mapped for both logged-in and non-logged in users
        add_action('wp_ajax_tbsw_start_conversion', array($this, 'start_conversion'));
        add_action('wp_ajax_nopriv_tbsw_start_conversion', array($this, 'start_conversion'));
        
        add_action('wp_ajax_tbsw_get_media_count', array($this, 'get_media_count'));
        add_action('wp_ajax_nopriv_tbsw_get_media_count', array($this, 'get_media_count'));
        
        add_action('wp_ajax_tbsw_get_pending_media_count', array($this, 'get_pending_media_count'));
        add_action('wp_ajax_nopriv_tbsw_get_pending_media_count', array($this, 'get_pending_media_count'));
        
        add_action('wp_ajax_tbsw_reset_conversion', array($this, 'reset_conversion'));
        add_action('wp_ajax_nopriv_tbsw_reset_conversion', array($this, 'reset_conversion'));
    }

    /**
     * Verify nonce for AJAX requests
     *
     * @since    1.0.0
     */
    private function verify_nonce() {
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'tbsw-nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
    }

    /**
     * Start the conversion process
     *
     * @since    1.0.0
     */
    public function start_conversion() {
        $this->verify_nonce();

        // Get page parameter from the request
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;

        $result = $this->converter->convert_attachements_batch($page);

        $hasMorePages = $result['hasMorePages'];
        
        // Handle the AJAX request for starting conversion
        $response = array('status' => 'success', 'message' => 'Conversion started!', 'hasMorePages' => $hasMorePages);
        wp_send_json($response);
    }

    /**
     * Get total media count
     *
     * @since    1.0.0
     */
    public function get_media_count() {
        $this->verify_nonce();

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

    /**
     * Get pending media count (not yet converted)
     *
     * @since    1.0.0
     */
    public function get_pending_media_count() {
        $this->verify_nonce();

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

    /**
     * Reset all conversions
     *
     * @since    1.0.0
     */
    public function reset_conversion() {
        $this->verify_nonce();
    
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