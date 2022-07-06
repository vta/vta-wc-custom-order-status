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

    private string $plugin_name;
    private string $version;

    private string $post_type                    = 'vta_order_status';
    private string $settings_name                = 'vta_order_status_options';
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
    }

    /**
     * Register the CSS & JavaScript for the admin area.
     * @return void
     * @hooked admin_enqueue_scripts
     */
    public function enqueue_scripts(): void {
        list('query_params' => $query_params) = get_query_params();

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/vta-wc-custom-order-status-admin.css', array(), $this->version, 'all');

        // Plugin Settings Page only
        $is_settings_page = in_array($this->post_type, $query_params) && in_array($this->settings_page, $query_params);
        if ( is_admin() && $is_settings_page ) {
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
     * Used during plugin activation.
     * @return void
     */
    public function sync_default_statuses(): void {

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

        // We should sync settings after setting default Statuses
        $this->sync_settings();
    }

    /**
     * Saves WC Order Status to a Custom Post. Updates the Post if it already exists.
     * NOTE: Search is based on title (Order Status Name)
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
            'post_type'   => $this->post_type,
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
     * @hooked init
     */
    public function register_custom_order_statuses() {
        // labels for Custom Order Status (custom post)
        $labels = [
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
        ];

        // create custom post type of "Custom Order Status"
        register_post_type(
            $this->post_type,
            [
                'labels'       => $labels,
                'public'       => false,
                'show_ui'      => true,
                'show_in_menu' => true,
                'description'  => 'Customizable WooCommerce custom order statuses that re-purposed for VTA Document Services workflow.',
                'hierarchical' => false,
                'menu_icon'    => 'dashicons-block-default'
            ]
        );

//        self::customize_edit_screen();
    }

    // CUSTOM POST EDITOR FOR "CUSTOM ORDER STATUS" POST TYPES

    /**
     * Customizes Edit screen for Custom Order Status post page.
     * @return void
     * @hooked admin_init
     */
    public function customize_edit_screen(): void {
        // remove certain post type elements from "Custom Order Status" post types
        // (we can set also, but we want to customize every input from post-new.php)
        remove_post_type_support($this->post_type, 'editor');

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
            $this->post_type, // 'vta_custom_order'
            'normal',
            'high'
        );

        add_meta_box(
            'cos-reorderable-checkbox',
            'Reorder for this Status',
            [ Vta_Wc_Custom_Order_Status_Admin::class, 'render_reorderable_checkox' ],
            $this->post_type, // 'vta_custom_order'
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
     * @hooked admin_menu
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
     * @hooked admin_init
     */
    public function settings_api_init() {

        $options = get_option($this->settings_name);

        // Register new page in Custom Order Status Plugin
        register_setting(
            $this->settings_page,
            $this->settings_name
        );

        // Settings section
        add_settings_section(
            'vta_cos_order_status',
            'Settings',
            [ $this, 'render_settings_section' ],
            $this->settings_field,
        );

        // arrangement Field
        add_settings_field(
            'vta_cos_arrangement_field',
            'Order of "Order Status"',
            [ $this, 'render_order_arrangement_field' ],
            $this->settings_field,
            'vta_cos_order_status',
            [
                'label_for'                         => $this->order_status_arrangement_key,
                $this->order_status_arrangement_key => $options[$this->order_status_arrangement_key] ?? '[]'
            ]
        );

        // first auto order status
        add_settings_field(
            'vta_cos_default_status_field',
            'Default Order Status',
            [ $this, 'render_default_order_status_field' ],
            $this->settings_field,
            'vta_cos_order_status',
            [
                'label_for'                         => $this->default_order_status_key,
                $this->default_order_status_key     => $options[$this->default_order_status_key] ?? null,
                $this->order_status_arrangement_key => $options[$this->order_status_arrangement_key] ?? '[]'
            ]
        );
    }

    /**
     * Renders the entire
     * @return void
     */
    public function render_settings_page(): void {
        include_once 'views/settings-page.php';
    }

    /**
     * Renders Setting Section for Custom Order Statuses.
     * NOTE: Section HTML precedes settings fields.
     * @return void
     */
    public function render_settings_section(): void {
        $this->display_setting_msg();

        ?>
        <p>These settings apply to all "Custom Order Statuses" as a whole.</p>
        <?php
    }

    /**
     * Checks if error message is store in settings. Currently it only displays success message.
     * @return void
     */
    private function display_setting_msg(): void {
        if ( isset($_GET['settings-updated']) ) {
            add_settings_error(
                "{$this->settings_name}_messages",
                "{$this->settings_name}_message_success",
                'Settings Saved',
                'updated'
            );

            // show error/update messages
            settings_errors("{$this->settings_name}_messages");
        }
    }

    /**
     * Form field for settings arrangement (order) of Custom Order Statuses
     * NOTE: "order_status_arrangement" value should be a JSON encoded string
     * @param array $args
     * @return void
     */
    public function render_order_arrangement_field( array $args ): void {
        $label_for = esc_attr($args['label_for']);
        $name      = "$this->settings_name[$label_for]";
        $value     = $args[$this->order_status_arrangement_key];

        $order_status_arrangement = json_decode($value);
        ?>

        <input type="hidden"
               id="<?php echo $label_for; ?>"
               name="<?php echo $name; ?>"
               value="<?php echo $value; ?>"
        >
        <ul id="statuses-sortable">
            <?php foreach ( $order_status_arrangement as $order_status ): ?>

                <li class="ui-state-default vta-order-status" id="<?php echo $order_status->order_status_id; ?>">
                    <?php echo $order_status->order_status_name; ?>
                </li>

            <?php endforeach; ?>
        </ul>
        <p class="description">
            Determines the order of the order statuses to display in the dropdown menu.
        </p>

        <?php

    }

    /**
     * Form field setting for default order status.
     * @param array $args
     * @return void
     */
    public function render_default_order_status_field( array $args ): void {
        $order_statuses = wc_get_order_statuses();

        $label_for = esc_attr($args['label_for']);
        $name      = "$this->settings_name[$label_for]";
        $value     = $args[$this->default_order_status_key];

        $order_status_arrangement = $args[$this->order_status_arrangement_key];
        $order_status_arrangement = json_decode($order_status_arrangement);
        ?>

        <select id="<?php echo $label_for; ?>"
                name="<?php echo $name; ?>"
                value="<?php echo $value; ?>"
        >
            <?php foreach ( $order_status_arrangement as $order_status ): ?>

                <option id="<?php echo $order_status->order_status_id; ?>"
                        value="<?php echo $order_status->order_status_id; ?>"
                        <?php echo $value === $order_status->order_status_id ? 'selected' : ''; ?>
                >
                    <?php echo $order_status->order_status_name; ?>
                </option>

            <?php endforeach; ?>
        </select>

        <?php
    }

    /**
     * Assigns settings if empty or arrangement value is empty
     * @return void
     */
    private function sync_settings(): void {
        $options = get_option($this->settings_name) ?? null;

        if ( empty($options) || empty($options[$this->default_order_status_key] ?? null) ) {
            $args           = [
                'post_status' => 'publish',
                'post_type'   => $this->post_type,
            ];
            $wp_query       = new WP_Query($args);
            $order_statuses = $wp_query->get_posts();
            $order_statuses = is_array($order_statuses) ? $order_statuses : [];

            $order_statuses = array_map(fn( WP_Post $post ) => [
                'order_status_id'   => $post->post_name,
                'order_status_name' => $post->post_title
            ], $order_statuses);

            $options = [
                $this->order_status_arrangement_key => json_encode($order_statuses),
                $this->default_order_status_key     => $order_statuses[0]->order_status_id ?? null,
            ];

            update_option($this->settings_name, $options);
        }
    }

    // GETTERS //

    /**
     * @return string
     */
    public function get_post_type(): string {
        return $this->post_type;
    }
}
