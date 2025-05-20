<?php

class TBS_WebPressor_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function tbsw_activate() {
        // Create necessary database tables if needed
        global $wpdb;
        
        // Add any activation logic here
        
        // Set default options
        add_option('tbsw_compression_quality', 80);
        add_option('tbsw_convert_to_webp', true);
    }
}