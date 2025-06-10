<?php

class TBS_WebPressor_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function tbswebpressor_deactivate() {
        // Clean up any plugin data if needed
        
        // Optionally remove options
        delete_option('tbswebpressor_compression_quality');
        delete_option('tbswebpressor_convert_to_webp');
    }
}