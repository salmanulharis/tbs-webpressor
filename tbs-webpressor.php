<?php
/**
 * Plugin Name: WebPressor - WebP Image Converter & Optimizer
 * Description: A WordPress plugin to convert images to WebP format and serve them to compatible browsers.
 * Version: 1.0.0
 * Author: Techbysh
 * Author URI: https://techbysh.com
 * Text Domain: webpressor-webp-image-converter-optimizer
 * Domain Path: /languages
 * License: GPL2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define plugin constants
 */
define('TBSW_VERSION', '1.0.0');
define('TBSW_PLUGIN_DIR', trailingslashit(dirname(__FILE__)));
define('TBSW_PLUGIN_URL', trailingslashit(plugins_url('', __FILE__)));
define('TBSW_PLUGIN_BASENAME', plugin_basename(__FILE__));

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
    $plugin->tbsw_run();
}
run_tbs_webpressor();