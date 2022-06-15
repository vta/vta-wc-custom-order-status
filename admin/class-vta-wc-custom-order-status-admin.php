<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://jamespham.io
 * @since      1.0.0
 *
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/admin
 */

include 'partials/vta-wc-custom-order-status-admin-display.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/admin
 * @author     James Pham <jamespham93@yahoo.com>
 */
class Vta_Wc_Custom_Order_Status_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Vta_Wc_Custom_Order_Status_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Vta_Wc_Custom_Order_Status_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/vta-wc-custom-order-status-admin.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Vta_Wc_Custom_Order_Status_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Vta_Wc_Custom_Order_Status_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/vta-wc-custom-order-status-admin.js', array( 'jquery' ), $this->version, false);

    }

    /**
     * Add Color Settings subpage to Custom Order Status (custom posts) admin menu.
     * Hooked to admin_menu.
     */
    public function add_color_subpage() {

        $parent_slug = 'edit.php?post_type=custom_order_status';
        $page_title  = 'Color Settings';
        $menu_title  = 'Color Settings';
        $capability  = 'manage_options';
        $slug        = 'smashing_fields';
        $callback    = array( $this, 'plugin_settings_page_content' );
        $icon        = 'dashicons-admin-plugins';
        $position    = 100;

//        add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);

        $menu_slug = 'custom_order_status_color-settings';
        $callback  = array( Vta_Wc_Custom_Order_Status_Admin_Display::class, 'order_status_color_list' );

        add_submenu_page(
            $parent_slug,
            $page_title,
            $menu_title,
            $capability,
            $menu_slug,
            $callback,
            2
        );

    }

    /**
     * Inject Custom HTML into post-new.php for post type of "custom_order_status"
     */
    public function custom_order_add_inputs() {

        $id       = 'cos_add_input_wrapper';
        $title    = __('Create Custom Order Status', $this->plugin_name);
        $callback = array( Vta_Wc_Custom_Order_Status_Admin_Display::class, 'order_status_create' );
        $screen   = 'custom_order_status';

        add_meta_box($id, $title, $callback, $screen);
    }

}
