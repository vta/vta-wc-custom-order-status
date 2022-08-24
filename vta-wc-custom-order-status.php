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
 * @since             0.6
 * @package           Vta_Wc_Custom_Order_Status
 *
 * @wordpress-plugin
 * Plugin Name:       VTA WooCommerce Custom Order Status
 * Plugin URI:        https://jamespham.io
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           0.5
 * Author:            James Pham
 * Author URI:        https://jamespham.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       vta-wc-custom-order-status
 * Domain Path:       /languages
 */

/** PLUGIN CONSTANTS **/
// Plugin
const VTA_WC_CUSTOM_ORDER_STATUS_VERSION = '0.6';
const VTA_WC_COS_PLUGIN_NAME             = 'vta-wc-custom-order-status';
// Post
const VTA_COS_CPT          = 'vta_order_status';
const META_COLOR_KEY       = 'vta_cos_color';
const META_REORDERABLE_KEY = 'vta_cos_is_reorderable';
// Settings/Options
const VTA_COS_SETTINGS_NAME        = 'vta_order_status_options';
const VTA_COS_SETTINGS_PAGE        = 'vta_order_status_settings';
const VTA_COS_SETTINGS_FIELD       = 'vta_order_status_settings_fields';
const ORDER_STATUS_DEFAULT_KEY     = 'order_status_default';
const ORDER_STATUS_ARRANGEMENT_KEY = 'order_status_arrangement';

/** Global files for plugin and/or theme usage **/
require_once 'admin/class-vta-wc-custom-order-status-admin.php';
require_once 'utils/common.php';
// WC Dependencies (before plugins are loaded)
include_once ABSPATH . '/wp-content/plugins/woocommerce/includes/abstracts/abstract-wc-settings-api.php';
include_once ABSPATH . '/wp-content/plugins/woocommerce/includes/emails/class-wc-email.php';
include_once ABSPATH . '/wp-content/plugins/woocommerce/includes/class-wc-emails.php';
include_once ABSPATH . '/wp-content/plugins/woocommerce/woocommerce.php';
// Models
require_once 'models/VTACustomOrderStatus.php';
require_once 'models/VTACosSettings.php';
// Classes
require_once 'classes/VTACustomOrderStatuses.php';
require_once 'classes/VTACosSettingsManager.php';
require_once 'classes/VTAWooCommerce.php';
require_once 'classes/VTACustomEmail.php';
require_once 'classes/VTACosEmailManager.php';

// If this file is called directly, abort.
if ( !defined('WPINC') ) {
    die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-vta-wc-custom-order-status-activator.php
 */
function activate_vta_wc_custom_order_status(): void {
    require_once plugin_dir_path(__FILE__) . 'includes/class-vta-wc-custom-order-status-activator.php';
    Vta_Wc_Custom_Order_Status_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-vta-wc-custom-order-status-deactivator.php
 */
function deactivate_vta_wc_custom_order_status(): void {
    require_once plugin_dir_path(__FILE__) . 'includes/class-vta-wc-custom-order-status-deactivator.php';
    Vta_Wc_Custom_Order_Status_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_vta_wc_custom_order_status');
register_deactivation_hook(__FILE__, 'deactivate_vta_wc_custom_order_status');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-vta-wc-custom-order-status.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_vta_wc_custom_order_status(): void {
    $plugin = new Vta_Wc_Custom_Order_Status();
    $plugin->run();
}

run_vta_wc_custom_order_status();
