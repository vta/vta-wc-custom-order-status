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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/vta-wc-custom-order-status-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/vta-wc-custom-order-status-public.js', array( 'jquery' ), $this->version, false );

	}

    /**
     *
     */
	public function register_custom_order_statuses() {

	    // labels for Custom Order Status (custom post)
        $labels = array(
            'name' => __( 'Custom Order Statuses', 'vta-wc-custom-order-status' ),
            'singular_name' => __( 'Custom Order Status', 'vta-wc-custom-order-status' ),
            'add_new' => __( 'New Order Status', 'vta-wc-custom-order-status' ),
            'add_new_item' => __( 'Add New Order Status', 'vta-wc-custom-order-status' ),
            'edit_item' => __( 'Edit Book', 'vta-wc-custom-order-status' ),
            'new_item' => __( 'New Book', 'vta-wc-custom-order-status' ),
            'view_item' => __( 'View Books', 'vta-wc-custom-order-status' ),
            'search_items' => __( 'Search Books', 'vta-wc-custom-order-status' ),
            'not_found' =>  __( 'No Books Found', 'vta-wc-custom-order-status' ),
            'not_found_in_trash' => __( 'No Books found in Trash', 'vta-wc-custom-order-status' )
        );

	    // create custom post type of "Custom Order Status"
	    register_post_type(
	        'custom_order_status',
            array(
                'labels' => $labels,
                'public' => true,
                'description' => 'Customizable WooCommerce custom order statuses that re-purposed for VTA Document Services workflow.',
                'hierarchical' => false
            )
        );

	    // remove certain post type elements from "Custom Order Status" post types
        // (we can set also, but we want to customize every input from post-new.php)
        remove_post_type_support( 'custom_order_status', 'title' );
        remove_post_type_support( 'custom_order_status', 'editor' );
        remove_post_type_support( 'custom_order_status', 'thumbnail' );
        remove_post_type_support( 'custom_order_status', 'post-format' );

    }
}
