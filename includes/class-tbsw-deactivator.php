<?php

class TBS_WebPressor_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function tbsw_deactivate() {
        // Clean up any plugin data if needed
        
        // Optionally remove options
        delete_option('tbsw_compression_quality');
        delete_option('tbsw_convert_to_webp');
    }
}