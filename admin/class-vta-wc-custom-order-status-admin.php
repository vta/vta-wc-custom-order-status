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
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/admin
 * @author     James Pham <jamespham93@yahoo.com>
 */
class Vta_Wc_Custom_Order_Status_Admin {

    private string $plugin_name;
    private string $version;

    private string                 $settings_name = VTA_COS_SETTINGS_NAME;
    private VTACustomOrderStatuses $order_statuses;
    private VTACosSettingsManager  $settings_manager;

    /**
     * Initialize the class and set its properties.
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct( string $plugin_name, string $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        $settings = get_option($this->settings_name) ?: [];
        $settings = new VTACosSettings($settings);

        $this->settings_manager = new VTACosSettingsManager($plugin_name, $version, $settings);
        $this->order_statuses   = new VTACustomOrderStatuses($plugin_name, $version, $settings);
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
