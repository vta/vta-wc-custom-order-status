<?php

/**
 * Fired during plugin activation
 *
 * @link       https://jamespham.io
 * @since      1.0.0
 *
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/includes
 * @author     James Pham <jamespham93@yahoo.com>
 */
class Vta_Wc_Custom_Order_Status_Activator {

    /**
     * Checks for WooCommerce before activating plugin
     * @return void
     */
    public static function activate(): void {
        $wc_is_active = is_plugin_active('woocommerce/woocommerce.php');

        if ( !$wc_is_active ) {
            $error_msg = "WooCommerce must be installed and activated! Plugin activation stopped";
            die($error_msg);
        }
    }

}
