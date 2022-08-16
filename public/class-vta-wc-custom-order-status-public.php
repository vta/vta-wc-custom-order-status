<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://jamespham.io
 * @since      1.0.0
 *
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/public
 * @author     James Pham <jamespham93@yahoo.com>
 */
class Vta_Wc_Custom_Order_Status_Public {

    /** @var string $plugin_name The ID of this plugin. */
    private string $plugin_name;

    /** @var  string $version The current version of this plugin. */
    private string $version;

    private VTAWooCommerce $vta_woocommerce;

    /**
     * Initialize the class and set its properties.
     * @param string $plugin_name The name of the plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct( string $plugin_name, string $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;

        $this->vta_woocommerce = new VTAWooCommerce($plugin_name, $version);
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     * @returns void
     */
    public function enqueue_styles(): void {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/vta-wc-custom-order-status-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     * @returns void
     */
    public function enqueue_scripts(): void {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/vta-wc-custom-order-status-public.js', array( 'jquery' ), $this->version, false);
    }

}
