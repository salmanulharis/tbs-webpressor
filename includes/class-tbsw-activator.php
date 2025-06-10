<?php

class TBS_WebPressor_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function tbswebpressor_activate() {
        // Create necessary database tables if needed
        global $wpdb;
        
        // Add any activation logic here
        
        // Set default options
        add_option('tbswebpressor_compression_quality', 80);
        add_option('tbswebpressor_convert_to_webp', true);
    }
}