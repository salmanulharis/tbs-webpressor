<?php
/*
Plugin Name: WebPressor
Plugin URI: https://your-site.com/webpressor
Description: A powerful image compression and WebP conversion plugin for WordPress.
Version: 1.0.0
Author: Your Name
Author URI: http://techbysh.com/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: tbs-webpressor
Domain Path: /languages
*/

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('TBSW_VERSION', '1.0.0');
define('TBSW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TBSW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TBSW_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once TBSW_PLUGIN_DIR . 'includes/class-tbsw.php';

/**
 * Begins execution of the plugin.
 */
function run_tbs_webpressor() {
    $plugin = new TBS_WebPressor();
    $plugin->run();
}
run_tbs_webpressor();

/**
 * The code that runs during plugin activation.
 */
function activate_tbsw() {
    require_once TBSW_PLUGIN_DIR . 'includes/class-tbsw-activator.php';
    TBS_WebPressor_Activator::activate();
}
register_activation_hook(__FILE__, 'activate_tbsw');

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_tbsw() {
    require_once TBSW_PLUGIN_DIR . 'includes/class-tbsw-deactivator.php';
    TBS_WebPressor_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_tbsw');