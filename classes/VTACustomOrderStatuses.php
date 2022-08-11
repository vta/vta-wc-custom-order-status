<?php

/**
 * @class VTA Custom Order Statuses
 * Main class for Post Management for "vta_order_status" CPT.
 */
class VTACustomOrderStatuses {

    private string $plugin_name;
    private string $plugin_version;
    private string $post_type            = VTA_COS_CPT;
    private string $meta_color_key       = META_COLOR_KEY;
    private string $meta_reorderable_key = META_REORDERABLE_KEY;
    private string $settings_page        = VTA_COS_SETTINGS_PAGE;

    /**
     * Encapsulates hooks in class constructors. Ditches loader method set up by boilerplate.
     * @param string $plugin_name
     * @param string $plugin_version
     */
    public function __construct( string $plugin_name, string $plugin_version ) {
        $this->plugin_name    = $plugin_name;
        $this->plugin_version = $plugin_version;

        add_action('admin_enqueue_scripts', [ $this, 'enqueue_scripts' ]);
        add_action('init', [ $this, 'register_custom_order_statuses' ]);
        add_action('admin_post_default_settings', [ $this, 'default_settings' ]);
        add_action("save_post_{$this->post_type}", [ $this, 'save_post' ], 11, 3);
    }

    /**
     * Registers & enqueues specific hooks for Admin dashboard screens
     * @return void
     */
    public function enqueue_scripts(): void {
        global $post;
        list('query_params' => $query_params, 'path' => $path) = get_query_params();

        // Settings page
        $is_settings_page = in_array($this->post_type, $query_params) && in_array($this->settings_page, $query_params);
        if ( is_admin() && $is_settings_page ) {
            wp_enqueue_style(
                "{$this->plugin_name}_settings_css",
                plugin_dir_url(__FILE__) . 'css/settings.css',
                [],
                $this->plugin_version
            );

            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script(
                "{$this->plugin_name}_settings_js",
                plugin_dir_url(__FILE__) . 'js/settings.js',
                [ 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable' ],
                $this->plugin_version,
                true
            );
        }

        // New/Edit Post page
        // New/Edit Post page
        $is_new_post_page  = preg_match('/post-new\.php/', $path) || in_array($this->post_type, $query_params);
        $is_edit_post_page = preg_match('/post\.php/', $path) && $post instanceof WP_Post && $post->post_type === $this->post_type;
        $is_post_page      = $is_new_post_page || $is_edit_post_page;
        if ( is_admin() && $is_post_page ) {
            wp_enqueue_style(
                "{$this->plugin_name}_post_css",
                plugin_dir_url(__FILE__) . 'css/post.css',
                [],
                $this->plugin_version
            );
            wp_enqueue_script(
                "{$this->plugin_name}_post_js",
                plugin_dir_url(__FILE__) . 'js/post.js',
                [ 'jquery' ],
                $this->plugin_version,
                true
            );
        }
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
    }

    /**
     * Customizes Edit screen for Custom Order Status post page.
     * @return void
     * @hooked admin_init
     */
    public function customize_edit_screen(): void {
        // remove certain post type elements from "Custom Order Status" post types
        // (we can set also, but we want to customize every input from post-new.php)
        remove_post_type_support($this->post_type, 'editor');

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
            $this->post_type,
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
        $color            = $is_edit ? get_post_meta($post_id, $this->meta_color_key, true) : '#000000';
        $reorderable      = $is_edit ? get_post_meta($post_id, $this->meta_reorderable_key, true) : false;
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

    /**
     * Adds custom attribute to our Order Status for new/edited posts.
     * @param int $post_ID
     * @param WP_Post|null $post
     * @param bool $update
     * @return void
     * @hooked 'save_post_vta_order_status'
     */
    public function save_post( int $post_ID, ?WP_Post $post, ?bool $update ): void {
        // Order Status Color
        if ( array_key_exists($this->meta_color_key, $_POST) ) {
            update_post_meta($post_ID, $this->meta_color_key, $_POST[$this->meta_color_key]);
        }

        // Order Status "Is Reorderable"
        $is_reorderable = $_POST[$this->meta_reorderable_key] ?? false;
        update_post_meta($post_ID, $this->meta_reorderable_key, (bool)$is_reorderable);

        // Update in settings where needed
        if ( $post instanceof WP_Post && $post->post_status === 'publish' ) {

        }
    }

}
