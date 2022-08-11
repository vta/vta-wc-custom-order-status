<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://jamespham.io
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/admin
 * @author     James Pham <jamespham93@yahoo.com>
 * TODO - (OPTIONAL) break down into smaller classes. Insert hooks into broken down classes. Too confusing to store hooks in class-vta-custom-order-status.php...
 */
class Vta_Wc_Custom_Order_Status_Admin {

    private string $plugin_name;
    private string $version;

    const POST_TYPE            = VTA_COS_CPT;
    const META_COLOR_KEY       = META_COLOR_KEY;
    const META_REORDERABLE_KEY = META_REORDERABLE_KEY;

    private string                 $settings_name = VTA_COS_SETTINGS_NAME;
    private VTACosSettings         $settings;
    private VTACustomOrderStatuses $order_statuses;
    private VTACosSettingsManager  $settings_manager;

    private string $default_order_status_key     = 'order_status_default';
    private string $order_status_arrangement_key = 'order_status_arrangement';
    private string $settings_page                = 'vta_order_status_settings';
    private string $settings_field               = 'vta_order_status_settings_fields';

    /**
     * Initialize the class and set its properties.
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct( string $plugin_name, string $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        $settings               = get_option($this->settings_name) ?: [];
        $this->settings         = new VTACosSettings($settings);
        $this->settings_manager = new VTACosSettingsManager($plugin_name, $version, $this->settings);
        $this->order_statuses   = new VTACustomOrderStatuses($plugin_name, $version);
    }

    /**
     * Gets default OR current order statuses from WC and creates|update posts.
     * Used during plugin activation.
     * @return void
     */
    public function sync_default_statuses(): void {
        $this->settings_manager->sync_default_statuses();
    }
}
