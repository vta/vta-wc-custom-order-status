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

        // Sync WC Order Statuses to Custom Posts if they exist.
        $admin = new Vta_Wc_Custom_Order_Status_Admin(
            VTA_WC_COS_PLUGIN_NAME,
            VTA_WC_CUSTOM_ORDER_STATUS_VERSION
        );

        $has_cos_posts = self::cos_posts_exists();
        if ( !$has_cos_posts ) {
            $admin->sync_default_statuses();
        }
    }

    /**
     * Checks if Custom Order Status post exists yet.
     * @return bool
     */
    private static function cos_posts_exists(): bool {
        require_once ABSPATH . '/wp-admin/includes/post.php';

        $args     = [
            'post_type'   => VTA_COS_CPT,
            'post_status' => 'any',
            'limit'       => -1
        ];
        $wp_query = new WP_Query($args);
        $posts    = $wp_query->get_posts();

        return count($posts) > 0;
    }
}
