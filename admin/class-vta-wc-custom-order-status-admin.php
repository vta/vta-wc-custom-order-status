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

    const POST_TYPE            = VTA_COS_CPT;
    const META_COLOR_KEY       = META_COLOR_KEY;
    const META_REORDERABLE_KEY = META_REORDERABLE_KEY;

    private string         $settings_name = 'vta_order_status_options';
    private VTACosSettings $settings;

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

        $settings       = get_option($this->settings_name) ?: [];
        $this->settings = new VTACosSettings($settings);
    }

    /**
     * Register the CSS & JavaScript for the admin area.
     * @return void
     * @hooked admin_enqueue_scripts
     */
    public function enqueue_scripts(): void {
        global $post;
        list('query_params' => $query_params, 'path' => $path) = get_query_params();

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/vta-wc-custom-order-status-admin.css', array(), $this->version, 'all');

        // Plugin Settings Page only
        $is_settings_page = in_array(self::POST_TYPE, $query_params) && in_array($this->settings_page, $query_params);
        if ( is_admin() && $is_settings_page ) {
            wp_enqueue_style(
                "{$this->plugin_name}_settings_css",
                plugin_dir_url(__FILE__) . 'css/settings.css',
                [],
                $this->version
            );

            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script(
                "{$this->plugin_name}_settings_js",
                plugin_dir_url(__FILE__) . 'js/settings.js',
                [ 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable' ],
                $this->version,
                true
            );
        }

        // New/Edit Post page
        $is_new_post_page  = preg_match('/post-new\.php/', $path) || in_array(self::POST_TYPE, $query_params);
        $is_edit_post_page = preg_match('/post\.php/', $path) && $post instanceof WP_Post && $post->post_type === self::POST_TYPE;
        $is_post_page      = $is_new_post_page || $is_edit_post_page;
        if ( is_admin() && $is_post_page ) {
            wp_enqueue_style(
                "{$this->plugin_name}_post_css",
                plugin_dir_url(__FILE__) . 'css/post.css',
                [],
                $this->version
            );
            wp_enqueue_script(
                "{$this->plugin_name}_post_js",
                plugin_dir_url(__FILE__) . 'js/post.js',
                [ 'jquery' ],
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
                'name'               => $order_status_key,
                'title'              => $order_status_val,
                self::META_COLOR_KEY => $default_colors[$order_status_key] ?? '#7D7D7D'
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
            'name'                     => '',
            'title'                    => '',
            self::META_COLOR_KEY       => '#7D7D7D',
            self::META_REORDERABLE_KEY => false,
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
            'post_type'   => self::POST_TYPE,
            'post_status' => 'publish',
        ];
        $post_id   = wp_insert_post($post_args);

        if ( $post_id ) {
            update_post_meta($post_id, self::META_COLOR_KEY, $arr[self::META_COLOR_KEY] ?? '#7D7D7D');
            update_post_meta($post_id, self::META_REORDERABLE_KEY, $arr[self::META_REORDERABLE_KEY] ?? false);
        }

    }

    // TODO - CONSIDERING MOVING EVERYTHING BELOW HERE INTO ITS OWN CLASS

    /**
     * Deletes current settings and creates fresh posts & settings.
     * @return void
     * @hooked admin_post_default_settings
     */
    public function default_settings(): void {
        $this->delete_posts_settings();
        $this->sync_default_statuses();
        $post_type = $this->get_post_type();

        status_header(200);
        wp_redirect("/wp-admin/edit.php?post_type={$post_type}&page=$this->settings_page");
    }

    /**
     * Deletes all plugin settings and custom posts
     * @return void
     */
    public function delete_posts_settings(): void {
        $args     = [
            'post_status'    => 'any',
            'post_type'      => 'vta_order_status',
            'posts_per_page' => -1
        ];
        $wp_query = new WP_Query($args);
        $posts    = $wp_query->get_posts();

        foreach ( $posts as $post ) {
            wp_delete_post(is_int($post) ? $post : $post->ID);
        }

        delete_option('vta_order_status_options');
    }

    /**
     * Sets up Custom Post Type for custom order status management.
     * @hooked init
     */
    public function register_custom_order_statuses() {
        // labels for Custom Order Status (custom post)
        $labels = [
            'name'               => __('VTA Order Statuses', 'vta-wc-custom-order-status'),
            'singular_name'      => __('VTR Order Status', 'vta-wc-custom-order-status'),
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
            self::POST_TYPE,
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
        remove_post_type_support(self::POST_TYPE, 'editor');

        $this->replace_title_placeholder();
        $this->add_meta_boxes();
    }

    /**
     * Replaces "Add Title" with "Order Status Name"
     * @return void
     */
    public function replace_title_placeholder(): void {
        add_filter('enter_title_here', fn() => 'Order Status Name');
    }

    /**
     * Adds meta boxes to CPT add/edit page
     * @return void
     */
    public function add_meta_boxes(): void {
        add_meta_box(
            'cos-custom-attributes',
            'Order Status Custom Attributes',
            [ $this, 'render_edit_meta_fields' ],
            self::POST_TYPE, // 'vta_order_status'
            'normal',
            'high'
        );
    }

    /**
     * UI for users to determine custom Order Status attributes.
     * @return void
     */
    public function render_edit_meta_fields(): void {
        /** @var WP_Post | null $post */
        global $post;

        list('query_params' => $query_params) = get_query_params();

        $is_edit = in_array('edit', $query_params);

        $order_status_key = $is_edit && $post instanceof WP_Post ? $post->post_name : '';
        $post_id          = $is_edit && $post instanceof WP_Post ? $post->ID : null;
        $color            = $is_edit ? get_post_meta($post_id, self::META_COLOR_KEY, true) : '#000000';
        $reorderable      = $is_edit ? get_post_meta($post_id, self::META_REORDERABLE_KEY, true) : false;
        ?>

        <table id="edit-custom-attr">
            <tr>
                <td>
                    <label for="order-status-id">Order Status Key</label>
                </td>
                <td>
                    <input type="text" name="order_status_id" value="<?php echo $order_status_key; ?>">
                    <p class="description warning">
                        Do not update this if in doubt. This may cause a lot of downstream issues with current orders.
                    </p>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="color-picker">Color <span class="required">*</span></label>
                </td>
                <td>
                    <div>
                        <input type="color"
                               id="color-picker"
                               title="Custom Order Status Color Picker"
                               value="<?php echo $color; ?>"
                               name="vta_cos_color"
                               required
                        >
                        <button id="color-reset" class="button-small button-link-delete">
                            Reset
                        </button>
                    </div>
                    <p>
                        <strong id="color-val"><?php echo $color; ?></strong>
                    </p>
                    <p class="description">
                        Custom color designation for the following order status.
                    </p>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="reorderable-checkbox">Is Reordable?</label>
                </td>
                <td>
                    <input type="checkbox" id="reorderable-checkbox"
                           name="vta_cos_is_reorderable" <?php echo $reorderable ? 'checked' : '' ?>>
                    <label for="reorderable-checkbox"> Yes</label>
                    <p class="description">
                        Allow customs to re-order at this order status.
                    </p>
                </td>
            </tr>
        </table>

        <?php
    }

    // POST ACTIONS

    /**
     * Adds custom attribute to our Order Status for new/edited posts.
     * @param int $post_ID
     * @param WP_Post $post
     * @param bool $update
     * @return void
     * @hooked 'save_post_vta_order_status'
     */
    public function save_post( int $post_ID, ?WP_Post $post, ?bool $update ): void {
        // Order Status Color
        if ( array_key_exists(self::META_COLOR_KEY, $_POST) ) {
            update_post_meta($post_ID, self::META_COLOR_KEY, $_POST[self::META_COLOR_KEY]);
        }

        // Order Status "Is Reorderable"
        $is_reorderable = $_POST[self::META_REORDERABLE_KEY] ?? false;
        update_post_meta($post_ID, self::META_REORDERABLE_KEY, (bool)$is_reorderable);

        // Update in settings where needed
        if ( $post instanceof WP_Post && $post->post_status === 'publish' ) {

        }
    }

    // CUSTOM SETTINGS API

    /**
     * Add Settings page under "Custom Order Statuses" menu
     * @return void
     * @hooked admin_menu
     */
    public function register_options_page(): void {
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
    public function settings_api_init(): void {

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
                $this->default_order_status_key     => (int)$this->settings->get_default(),
                $this->order_status_arrangement_key => $this->settings->get_arrangement()
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
                $this->default_order_status_key     => (int)$this->settings->get_default(),
                $this->order_status_arrangement_key => $this->settings->get_arrangement()
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
        $label_for                = esc_attr($args['label_for']);
        $name                     = "$this->settings_name[$label_for]";
        $order_status_arrangement = $args[$this->order_status_arrangement_key];
        $default_post_id          = (int)$args[$this->default_order_status_key];
        ?>

        <input type="hidden"
               id="<?php echo $label_for; ?>"
               name="<?php echo $name; ?>"
               value="<?php echo json_encode($order_status_arrangement); ?>"
        >
        <ul id="statuses-sortable">
            <?php foreach ( $order_status_arrangement as $post_id ):
                try {
                    $order_status = new VTACustomOrderStatus((int)$post_id);
                    $is_default   = $default_post_id === $order_status->get_post_id();
                    ?>

                    <li class="ui-state-default vta-order-status draggable <?php echo $is_default ? 'default-status' : ''; ?>"
                        id="<?php echo $order_status->get_post_id(); ?>">
                        <?php echo $order_status->get_cos_name(); ?>
                        <?php echo $is_default ? "(Default Status)" : ''; ?>
                        <span class="dashicons dashicons-sort"></span>
                    </li>

                <?php } catch ( Exception $e ) {
                    error_log("Vta_Wc_Custom_Order_Status_Admin::render_order_arrangement_field() error - $e");
                }
            endforeach; ?>

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
        $label_for = esc_attr($args['label_for']);
        $name      = "$this->settings_name[$label_for]";

        $default_post_id          = $args[$this->default_order_status_key];
        $order_status_arrangement = $args[$this->order_status_arrangement_key];
        ?>

        <select id="<?php echo $label_for; ?>"
                name="<?php echo $name; ?>"
                value="<?php echo $default_post_id; ?>"
        >
            <?php foreach ( $order_status_arrangement as $post_id ):
                try {
                    $order_status = new VTACustomOrderStatus((int)$post_id);
                    ?>

                    <option id="<?php echo $order_status->get_post_id(); ?>"
                            value="<?php echo $order_status->get_post_id(); ?>"
                        <?php echo (int)$default_post_id === $order_status->get_post_id() ? 'selected' : ''; ?>
                    >
                        <?php echo $order_status->get_cos_name(); ?>
                    </option>

                <?php } catch ( Exception $e ) {
                    error_log("Vta_Wc_Custom_Order_Status_Admin::render_default_order_status_field() error - $e");
                }
            endforeach; ?>
        </select>

        <p class="description">
            This is the first order status to display when a customer requests a new order.
        </p>

        <?php
    }

    // GETTERS //

    /**
     * @return string
     */
    public function get_post_type(): string {
        return self::POST_TYPE;
    }

    /**
     * @return string
     */
    public function get_settings_name(): string {
        return $this->settings_name;
    }

    // PRIVATE METHODS //

    /**
     * Assigns settings if empty or arrangement value is empty
     * @return void
     */
    private function sync_settings(): void {
        $options = get_option($this->settings_name) ?? null;

        if ( empty($options) || empty($options[$this->default_order_status_key] ?? null) ) {
            $wp_query       = new WP_Query([
                'post_status' => 'publish',
                'post_type'   => self::POST_TYPE,
            ]);
            $order_statuses = $wp_query->get_posts();
            $order_statuses = is_array($order_statuses) ? $order_statuses : [];

            $order_statuses = array_map(fn( WP_Post $post ) => $post->ID, $order_statuses);

            $options = [
                $this->order_status_arrangement_key => $order_statuses,
                $this->default_order_status_key     => $order_statuses[0]['post_id'] ?? null,
            ];

            update_option($this->settings_name, $options);
        }
    }

    /**
     * Updates Settings array for new/updated Order Status
     * @param WP_Post $post
     * @return void
     */
    private function update_settings_post( WP_Post $post ): void {
        $options = get_option($this->settings_name);
    }
}
