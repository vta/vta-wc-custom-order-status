<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://jamespham.io
 * @since             1.0.0
 * @package           Vta_Wc_Custom_Order_Status
 *
 * @wordpress-plugin
 * Plugin Name:       VTA WooCommerce Custom Order Status
 * Plugin URI:        https://jamespham.io
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            James Pham
 * Author URI:        https://jamespham.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       vta-wc-custom-order-status
 * Domain Path:       /languages
 */

require_once 'admin/class-vta-wc-custom-order-status-admin.php';
require_once 'utils/common.php'

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'VTA_WC_CUSTOM_ORDER_STATUS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-vta-wc-custom-order-status-activator.php
 */
function activate_vta_wc_custom_order_status() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vta-wc-custom-order-status-activator.php';
	Vta_Wc_Custom_Order_Status_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-vta-wc-custom-order-status-deactivator.php
 */
function deactivate_vta_wc_custom_order_status() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-vta-wc-custom-order-status-deactivator.php';
	Vta_Wc_Custom_Order_Status_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_vta_wc_custom_order_status' );
register_deactivation_hook( __FILE__, 'deactivate_vta_wc_custom_order_status' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-vta-wc-custom-order-status.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_vta_wc_custom_order_status() {
	$plugin = new Vta_Wc_Custom_Order_Status();
	$plugin->run();

}

run_vta_wc_custom_order_status();

/**
 * 1. init - register CPT separately from plugin execution
 * 2. plugins_loaded - allow WC & WP core to load first (dependencies)
 * 3. TODO - redo WC dependencies
 */
//add_action('init', ['Vta_Wc_Custom_Order_Status_Admin', 'register_custom_order_statuses'], 10);
//add_action('admin_init', ['Vta_Wc_Custom_Order_Status_Admin', 'customize_edit_screen'], 10);
//add_action('wp', 'run_vta_wc_custom_order_status', 99);
