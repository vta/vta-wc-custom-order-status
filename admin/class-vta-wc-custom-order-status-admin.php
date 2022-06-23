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
//        $this->sync_default_statuses(); // TODO - move to activate()...

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
        $url_obj = parse_url($_SERVER['REQUEST_URI']);
        $path    = $url_obj['path'];
        $query   = $url_obj['query'] ?? null;

        // separate query parameters
        parse_str($query, $query_params);

        $is_settings_page = $path === '/wp-admin/edit.php' && !empty($query_params) &&
            isset($query_params['post_type']) &&
            $query_params['post_type'] === 'vta_order_status' &&
            isset($query_params['page']) &&
            $query_params['page'] === 'vta_order_status_settings';

        if ( $is_settings_page ) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url(__FILE__) . 'js/settings.js',
                [ 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable' ],
                $this->version,
                true
            );
        }
    }

    /**
     * Gets default OR current order statuses from WC and creates|update posts.
     * @return void
     * TODO - move into a different class in the future
     */
    public function sync_default_statuses(): void {

        error_log('running "sync_default_statuses"');

        $default_colors = [
            'wc-received'   => '#EE360F',
            'wc-processing' => '#E65100',
            'wc-finishing'  => '#EEEA13',
            'wc-proof'      => '#F5991B',
            'wc-ready'      => '#87EC13',
            'wc-pony'       => '#20EE13',
            'wc-completed'  => '#20EE13',
            'wc-on-hold'    => '#DD13EE',
            'wc-cancelled'  => '#A09FA0',
        ];

        $default_statuses = wc_get_order_statuses();

        // package arguments into array to convert to POST
        foreach ( $default_statuses as $order_status_key => $order_status_val ) {
            $arr = [
                'name'          => $order_status_key,
                'title'         => $order_status_val,
                'vta_cos_color' => $default_colors[$order_status_key] ?? '#7D7D7D'
            ];
            $this->save_order_status($arr);
        }
    }

    /**
     * Saves WC order status to Post Type
     * @param array $arr
     * @return void
     * @throws InvalidArgumentException
     */
    private function save_order_status(
        array $arr = [
            'name'                   => '',
            'title'                  => '',
            'vta_cos_color'          => '#7D7D7D',
            'vta_cos_is_reorderable' => false,
        ]
    ): void {

        require_once ABSPATH . '/wp-admin/includes/post.php';

        if ( empty($arr['name']) || empty($arr['title']) ) {
            $error_msg = 'Cannot have empty "name" or "title" field in $arr parameter.';
            throw new InvalidArgumentException($error_msg);
        }

        $post_id   = post_exists($arr['title']);
        $post_args = [
            'ID'          => $post_id,
            'post_title'  => $arr['title'],
            'name'        => $arr['name'],
            'post_type'   => 'vta_order_status',
            'post_status' => 'publish',
        ];
        $post_id   = wp_insert_post($post_args);

        if ( $post_id ) {
            update_post_meta($post_id, 'vta_cos_color', $arr['vta_cos_color'] ?? '#7D7D7D');
            update_post_meta($post_id, 'vta_cos_is_reorderable', $arr['vta_cos_is_reorderable'] ?? false);
        }

    }

    // TODO - CONSIDERING MOVING EVERYTHING BELOW HERE INTO ITS OWN CLASS

    /**
     * Sets up Custom Post Type for custom order status management.
     */
    static public function register_custom_order_statuses() {
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
                'hierarchical' => false,
                'menu_icon'    => 'dashicons-block-default'
            )
        );

//        self::customize_edit_screen();
    }

    // CUSTOM POST EDITOR FOR "CUSTOM ORDER STATUS" POST TYPES

    public function customize_edit_screen(): void {
        // remove certain post type elements from "Custom Order Status" post types
        // (we can set also, but we want to customize every input from post-new.php)
        remove_post_type_support('vta_order_status', 'editor');

//        add_action('add_meta_box', ['Vta_Wc_Custom_Order_Status_Admin', 'add_meta_boxes']);
        self::replace_title_placeholder();
        self::add_meta_boxes();
    }

    /**
     * Replaces "Add Title" with "Order Status Name"
     * @return void
     */
    static public function replace_title_placeholder(): void {
        add_filter('enter_title_here', fn() => 'Order Status Name');
    }

    /**
     * Adds meta boxes to CPT add/edit page
     * @return void
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'cos-color-picker',
            'Order Status Color Code',
            [ Vta_Wc_Custom_Order_Status_Admin::class, 'render_input_color_picker' ],
            'vta_order_status', // 'vta_custom_order'
            'normal',
            'high'
        );

        add_meta_box(
            'cos-reorderable-checkbox',
            'Reorder for this Status',
            [ Vta_Wc_Custom_Order_Status_Admin::class, 'render_reorderable_checkox' ],
            'vta_order_status', // 'vta_custom_order'
            'normal',
            'high'
        );
    }

    /**
     * UI for users to determine color code for order status
     * @return void
     */
    static public function render_input_color_picker(): void {
        include_once 'views/partials/color-picker.php';
    }

    /**
     * UI for users define if order is reorderable
     * @return void
     */
    static public function render_reorderable_checkox(): void {
        include_once 'views/partials/reorderable-checkbox.php';
    }

    // CUSTOM SETTINGS API

    /**
     * Add Settings page under "Custom Order Statuses" menu
     * @return void
     */
    public function register_options_page() {
        add_submenu_page(
            'edit.php?post_type=vta_order_status',
            'vta_order_status_settings',
            'Settings',
            'manage_options',
            'vta_order_status_settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Initialize Settings page for plugin & settings sections/fields
     * @return void
     */
    public function settings_api_init() {

        // Register new page in Custom Order Status Plugin
        register_setting(
            'vta_order_status_settings',
            'vta_order_status_options'
        );

        // Settings section
        add_settings_section(
            'vta_cos_order_status',
            'Settings',
            [ $this, 'render_settings_section' ],
            'vta_order_status_settings_fields',
        );

        // arrangement Field
        add_settings_field(
            'vta_cos_order_field',
            'Order of "Order Status"',
            [ $this, 'render_settings_order_field' ],
            'vta_order_status_settings_fields',
            'vta_cos_order_status',
            [
                'label_for' => '',
                'class'     => 'vta-cos-settings-row'
            ]
        );

        // first auto order status
        add_settings_field(
            'vta_cos_new_order_status_field',
            'New Order Statuses',
            [ $this, 'render_settings_order_field' ],
            'vta_order_status_settings_fields',
            'vta_cos_order_status',
            [
                'label_for' => '',
                'class'     => 'vta-cos-settings-row',
            ]
        );
    }

    public function render_settings_page() {
        include_once 'views/settings-page.php';
    }

    /**
     * Renders Setting Section for Custom Order Statuses.
     * NOTE: Section HTML precedes settings fields.
     * @return void
     */
    public function render_settings_section() {
        ?>
        <p>These settings apply to all "Custom Order Statuses" as a whole.</p>
        <?php
    }

    /**
     * Form field for settings arrangement (order) of Custom Order Statuses
     * @return void
     */
    public function render_settings_order_field() {
        include_once 'views/partials/settings/order-field.php';
    }

    /**
     * Form field for settings
     * @return void
     */
    public function render_new_order_status_field() {
        include_once 'views/partials/settings/new-order-field.php';
    }
}
