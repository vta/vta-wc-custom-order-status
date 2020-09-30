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
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/vta-wc-custom-order-status-admin.css', array(), $this->version, 'all' );

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/vta-wc-custom-order-status-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function create_admin_menu() {

        // Add the menu item and page
        $page_title = 'VTA WooCommerce Custom Order Status Settings';
        $menu_title = 'Custom Order Status';
        $capability = 'manage_options';
        $menu_slug = 'vta-wc-custom-order-status';
        $callback = array( Vta_Wc_Custom_Order_Status_Admin_Display::class, 'order_status_list' );
        $icon = 'dashicons-clipboard';
        $position = 26; // place right before WooCommerce in admin menu

        add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback, $icon, $position );

        $this->add_order_status_subpage();

    }

    public function add_order_status_subpage() {

        $parent_slug = 'vta-wc-custom-order-status';
        $page_title = 'New Order Status';
        $menu_title = 'New';
        $capability = 'manage_options';
        $menu_slug = 'vta-wc-custom-order-status-new';
        $callback = array( Vta_Wc_Custom_Order_Status_Admin_Display::class, 'new_order_status' );

        add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback, null );

    }

}
