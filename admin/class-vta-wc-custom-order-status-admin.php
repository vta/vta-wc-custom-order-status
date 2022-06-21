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
     * Sets up Custom Post Type for custom order status management.
     */
    public function register_custom_order_statuses() {

        // labels for Custom Order Status (custom post)
        $labels = array(
            'name'               => __('Custom Order Statuses', 'vta-wc-custom-order-status'),
            'singular_name'      => __('Custom Order Status', 'vta-wc-custom-order-status'),
            'add_new'            => __('New Order Status', 'vta-wc-custom-order-status'),
            'add_new_item'       => __('Add New Order Status', 'vta-wc-custom-order-status'),
            'edit_item'          => __('Edit Order Status', 'vta-wc-custom-order-status'),
            'new_item'           => __('New Order Status', 'vta-wc-custom-order-status'),
            'view_item'          => __('View Order Status', 'vta-wc-custom-order-status'),
            'search_items'       => __('Search Statuses', 'vta-wc-custom-order-status'),
            'not_found'          => __('No Order Statuses Found', 'vta-wc-custom-order-status'),
            'not_found_in_trash' => __('No Order Statuses found in Trash', 'vta-wc-custom-order-status')
        );

        // create custom post type of "Custom Order Status"
        register_post_type(
            'vta_order_status',
            array(
                'labels'       => $labels,
                'public'       => false,
                'show_ui'      => true,
                'show_in_menu' => true,
                'description'  => 'Customizable WooCommerce custom order statuses that re-purposed for VTA Document Services workflow.',
                'hierarchical' => false
            )
        );

        // remove certain post type elements from "Custom Order Status" post types
        // (we can set also, but we want to customize every input from post-new.php)
//        remove_post_type_support( 'vta_order_status', 'title' );
        remove_post_type_support('vta_order_status', 'editor');
        remove_post_type_support('vta_order_status', 'thumbnail');
        remove_post_type_support('vta_order_status', 'post-formats');
        remove_post_type_support('vta_order_status', 'page-attributes');
        remove_post_type_support('vta_order_status', 'post-format');

    }

//    /**
//     * Calls all required methods to create Plugin's dashboard menu & submenus
//     * @return void
//     */
//    public function register_menu(): void {
//        $this->register_main_menu();
//    }
//
//    /**
//     * Create the main menu for the plugin
//     * @return void
//     */
//    private function register_main_menu() {
//        add_menu_page(
//            'VTA Custom Order Statuses',
//            'VTA Custom Order Statuses',
//            'manage_options',
//            'vta-cos-settings',
//            [ $this, 'main_menu_page' ],
//            'dashicons-edit',
//            25  // before WooCommerce
//        );
//    }

//    /**
//     * Add Color Settings subpage to Custom Order Status (custom posts) admin menu.
//     * Hooked to admin_menu.
//     */
//    public function add_color_subpage() {
//
//        $parent_slug = 'edit.php?post_type=vta_order_status';
//        $page_title  = 'Color Settings';
//        $menu_title  = 'Color Settings';
//        $capability  = 'manage_options';
//        $slug        = 'smashing_fields';
//        $callback    = array( $this, 'plugin_settings_page_content' );
//        $icon        = 'dashicons-admin-plugins';
//        $position    = 100;
//
////        add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);
//
//        $menu_slug = 'custom_order_status_color-settings';
//        $callback  = array( Vta_Wc_Custom_Order_Status_Admin_Display::class, 'order_status_color_list' );
//
//        add_submenu_page(
//            $parent_slug,
//            $page_title,
//            $menu_title,
//            $capability,
//            $menu_slug,
//            $callback,
//            2
//        );
//
//    }

//    /**
//     * Inject Custom HTML into post-new.php for post type of "vta_order_status"
//     */
//    public function custom_order_add_inputs() {
//
//        $id       = 'cos_add_input_wrapper';
//        $title    = __('Create Custom Order Status', $this->plugin_name);
//        $callback = array( Vta_Wc_Custom_Order_Status_Admin_Display::class, 'order_status_create' );
//        $screen   = 'vta_order_status';
//
//        add_meta_box($id, $title, $callback, $screen);
//    }

//    /**
//     * Main Menu Page view
//     * @return void
//     */
//    public function main_menu_page(): void {
//        require_once 'views/main-menu.php';
//    }
//
//    /**
//     * Custom form fields specifically for
//     * @return void
//     */
//    public function new_custom_status_fields(): void {
//
//    }

}
