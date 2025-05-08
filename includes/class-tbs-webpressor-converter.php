<?php
/**
 * WebP Converter Class.
 *
 * @since      1.0.0
 * @package    TBS_WebPressor
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

class TBS_WebPressor_Converter {
    
    /**
     * Initialize the converter.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Constructor code
    }
    
    /**
     * Convert an attachment to WebP format
     *
     * @since    1.0.0
     * @param    int    $attachment_id    The attachment ID to convert
     * @return   array|bool               Conversion stats or false on failure
     */
    public static function create_webp($attachment_id) {
        $count = 0;
        $file = get_attached_file($attachment_id);
    
        if (!$file || !file_exists($file)) {
            return false;
        }
    
        $mime = get_post_mime_type($attachment_id);
        if (strpos($mime, 'image/') !== 0 || $mime === 'image/webp') {
            return false;
        }
    
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $webp_path = preg_replace('/\.' . preg_quote($ext, '/') . '$/', '.webp', $file);
        $quality = intval(get_option('tbsw_webp_quality', 80));
    
        // Convert original image
        if (self::create_webp_file($file, $webp_path, $quality)) {
            $normalized_path = str_replace('\\', '/', $webp_path);
            update_post_meta($attachment_id, 'tbsw_webp_path', $normalized_path);
            $count++;
        } else {
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
    
                if (self::create_webp_file($thumb_path, $thumb_webp_path, $quality)) {
                    $count++;
                }
            }
        }
    
        return array('count' => $count, 'webp_path' => $webp_path);
    }
    
    /**
     * Create a WebP image from a source file
     *
     * @since    1.0.0
     * @param    string    $source        Source image path
     * @param    string    $destination   Destination WebP path
     * @param    int       $quality       WebP quality (0-100)
     * @return   bool                     Success or failure
     */
    public static function create_webp_file($source, $destination, $quality = 80) {
        if (!function_exists('imagewebp')) {
            return false;
        }
    
        if (!file_exists($source)) {
            return false;
        }
    
        $info = getimagesize($source);
        if (!$info || !isset($info['mime'])) {
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
                return false;
        }
    
        $success = imagewebp($image, $destination, $quality);
        imagedestroy($image);
    
        return $success;
    }
    
    /**
     * Convert attachments in batches
     *
     * @since    1.0.0
     * @param    int    $page    Current page for batch processing
     * @return   array           Result with hasMorePages status
     */
    public static function convert_attachements_batch($page) {
        $hasMorePages = true;
        $args = array(
            'post_type'      => 'attachment',
            'posts_per_page' => 10,
            'post_status'    => array(
                'publish', 
                'pending', 
                'draft', 
                'auto-draft', 
                'future', 
                'private', 
                'inherit', 
                'trash'
            ),
            'paged'          => $page,
        );

        $attachments = new WP_Query($args);

        if ($attachments->have_posts()) {
            while ($attachments->have_posts()) {
                $attachments->the_post();
                $attachment_id = get_the_ID();
                // Call conversion function
                $created_data = self::create_webp($attachment_id);
            }
            wp_reset_postdata();
        } else {
            $hasMorePages = false; // No more attachments to process
        }

        return array('hasMorePages' => $hasMorePages);
    }
}