<?php
/**
 * Plugin Name: TBS WebPressor
 * Description: A WordPress plugin to convert images to WebP format and serve them to compatible browsers.
 * Version: 1.0.0
 * Author: TBS
 * Text Domain: tbs-webpressor
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define plugin constants
 */
define('TBSW_VERSION', '1.0.0');
define('TBSW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TBSW_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Include required files
 */
require_once TBSW_PLUGIN_DIR . 'includes/class-tbs-webpressor.php';
require_once TBSW_PLUGIN_DIR . 'includes/class-tbs-webpressor-converter.php';
require_once TBSW_PLUGIN_DIR . 'includes/class-tbs-webpressor-admin.php';
require_once TBSW_PLUGIN_DIR . 'includes/class-tbs-webpressor-public.php';
require_once TBSW_PLUGIN_DIR . 'includes/class-tbs-webpressor-ajax.php';

/**
 * Begins execution of the plugin.
 */
function run_tbs_webpressor() {
    $plugin = new TBS_WebPressor();
    $plugin->run();
}
run_tbs_webpressor();