<?php

/**
 * @link              http://localleadminer.com/
 * @since             1.0.0
 * @package           Woo_Paypal_Pro
 *
 * @wordpress-plugin
 * Plugin Name:       PayPal Pro for Woo
 * Plugin URI:        http://localleadminer.com/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            wpremiumdev
 * Author URI:        http://localleadminer.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-paypal-pro
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!defined('PREMIUM_WOO_PAYPAL_PRO_PLUGIN_DIR')) {
    define('PREMIUM_WOO_PAYPAL_PRO_PLUGIN_DIR', dirname(__FILE__));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-paypal-pro-activator.php
 */
function activate_woo_paypal_pro() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-woo-paypal-pro-activator.php';
    Woo_PayPal_Pro_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woo-paypal-pro-deactivator.php
 */
function deactivate_woo_paypal_pro() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-woo-paypal-pro-deactivator.php';
    Woo_PayPal_Pro_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_woo_paypal_pro');
register_deactivation_hook(__FILE__, 'deactivate_woo_paypal_pro');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-woo-paypal-pro.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woo_paypal_pro() {

    $plugin = new Woo_PayPal_Pro();
    $plugin->run();
}

add_action('plugins_loaded', 'load_woo_paypal_pro');

function load_woo_paypal_pro() {
    run_woo_paypal_pro();
}
