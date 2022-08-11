<?php

/**
 * @class VTA Custom Order Statuses
 * Main class for Post Management for "vta_order_status" CPT.
 */
class VTACustomOrderStatuses {

    // PLUGIN vars
    private string $plugin_name, $plugin_version;

    // POST vars
    private string $post_type            = VTA_COS_CPT;
    private string $meta_color_key       = META_COLOR_KEY;
    private string $meta_reorderable_key = META_REORDERABLE_KEY;

    // SETTINGS var
    private VTACosSettings $settings;
    private string         $settings_name                = VTA_COS_SETTINGS_NAME;
    private string         $order_status_default_key     = ORDER_STATUS_DEFAULT_KEY;
    private string         $order_status_arrangement_key = ORDER_STATUS_ARRANGEMENT_KEY;

    /**
     * Encapsulates hooks in class constructors. Ditches loader method set up by boilerplate.
     * @param string $plugin_name
     * @param string $plugin_version
     * @param VTACosSettings $settings
     */
    public function __construct(
        string         $plugin_name,
        string         $plugin_version,
        VTACosSettings $settings
    ) {
        $this->plugin_name    = $plugin_name;
        $this->plugin_version = $plugin_version;
        $this->settings       = $settings;

        add_action('admin_enqueue_scripts', [ $this, 'enqueue_scripts' ]);
        add_action('init', [ $this, 'register_custom_order_statuses' ]);
        add_action('admin_init', [ $this, 'customize_edit_screen' ]);
        add_action("save_post_{$this->post_type}", [ $this, 'save_post' ], 11, 3);

        // list table hooks
        add_filter("manage_{$this->post_type}_posts_columns", [ $this, 'add_custom_col' ], 10, 1);
        add_action("manage_{$this->post_type}_posts_custom_column", [ $this, 'inject_custom_col_data' ], 10, 2);
        add_filter("manage_edit-{$this->post_type}_sortable_columns", [ $this, 'add_custom_col_sorting' ], 10, 1);
        add_action('pre_get_posts', [ $this, 'define_custom_col_sorting' ], 10, 1);
    }

    /**
     * Registers & enqueues specific hooks for Admin dashboard screens
     * @return void
     */
    public function enqueue_scripts(): void {
        global $post;
        list('query_params' => $query_params, 'path' => $path) = get_query_params();

        // New/Edit Post page
        $is_new_post_page  = preg_match('/post-new\.php/', $path) || in_array($this->post_type, $query_params);
        $is_edit_post_page = preg_match('/post\.php/', $path) && $post instanceof WP_Post && $post->post_type === $this->post_type;
        $is_post_page      = $is_new_post_page || $is_edit_post_page;
        if ( is_admin() && $is_post_page ) {
            wp_enqueue_style(
                "{$this->plugin_name}_post_css",
                plugin_dir_url(__DIR__) . 'admin/css/post.css',
                [],
                $this->plugin_version
            );
            wp_enqueue_script(
                "{$this->plugin_name}_post_js",
                plugin_dir_url(__DIR__) . 'admin/js/post.js',
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

    // NEW/EDIT POST SCREEN (post.php) //

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
        if ( $post instanceof WP_Post && $post->post_status === 'publish' )
            $this->update_to_settings($post_ID);
        else
            $this->remove_from_settings($post_ID);
    }

    /**
     * Adds to arrangement array if applicable
     * @param int $post_id
     * @return void
     */
    public function update_to_settings( int $post_id ): void {
        $arrangement = $this->settings->get_arrangement();

        if ( !in_array($post_id, $arrangement) ) {
            $arrangement[] = $post_id;
            $this->settings->set_arrangement($arrangement);

            $updated_settings = $this->settings->to_array();
            update_option($this->settings_name, $updated_settings);
        }
    }

    /**
     * Removes from arrangement array if applicable.
     * Also removes from default order status ID and replaces it with the first item in arrangement array.
     * @param int $post_id
     * @return void
     */
    public function remove_from_settings( int $post_id ): void {
        $arrangement = $this->settings->get_arrangement();

        // remove from arrangement
        if ( in_array($post_id, $arrangement) ) {
            $arrangement = array_values(array_filter($arrangement, fn( $id ) => $id !== $post_id));
            $this->settings->set_arrangement($arrangement);
        }

        // remove & replace if current is default order status
        if ( $this->settings->get_default() === $post_id ) {
            $new_default_id = $arrangement[0] ?? null;
            $this->settings->set_default($new_default_id);
        }

        $updated_settings = $this->settings->to_array();
        update_option($this->settings_name, $updated_settings);
    }

    // LIST TABLE (edit.php) //

    /**
     * Adds custom columns to List table
     * @param array $post_columns
     * @return array
     */
    public function add_custom_col( array $post_columns ): array {
        // change title to "Order Status Name"
        $post_columns['title'] = 'Order Status Name';

        $date = $post_columns['date']; // inset at the end...
        unset($post_columns['date']);

        // add custom columns
        $post_columns[$this->meta_color_key]               = 'Color';
        $post_columns[$this->meta_reorderable_key]         = 'Is Re-Orderable';
        $post_columns[$this->order_status_arrangement_key] = 'Arrangement Number';

        $post_columns['date'] = $date;

        return $post_columns;
    }

    /**
     * Adds data to custom column
     * @param string $col_name
     * @param int $post_id
     * @return void
     */
    public function inject_custom_col_data( string $col_name, int $post_id ): void {
        $content = '';
        $class   = '';

        try {
            $order_status = new VTACustomOrderStatus($post_id);
        } catch ( Exception $e ) {
            error_log("VTAHolidayPosts::inject_custom_col_data() Error - $e");
            $order_status = null;
        }

        if ( !$order_status instanceof VTACustomOrderStatus ) {
            return;
        }

        switch ( $col_name ) {
            case $this->meta_color_key:
                $color   = $order_status->get_cos_color() ?? '#000';
                $content = "<span class='cos-color-chip' style='background: $color;'>$color</span>";
                $class   = 'cos-color-col';
                break;
            case $this->meta_reorderable_key:
                $text    = $order_status->get_cos_reorderable() ? '<span class="dashicons dashicons-yes"></span>' : '';
                $content = "<p class='cos-reorderable-check'>$text</p>";
                $class   = 'cos-reorderable-col';
                break;
            case $this->order_status_arrangement_key:
                $arrangement_num = $this->get_arrangement_num($post_id);
                $content         = "<p class='cos-arrangement-num'>$arrangement_num</p>";
                $class           = 'cos-arrangement-col';
                break;
        }

        printf('<p class="%s">%s</p>', $class, $content);
    }

    /**
     * Enables sorting for custom columns
     * @param array $sortable_columns
     * @return array
     */
    public function add_custom_col_sorting( array $sortable_columns ): array {
        $sortable_columns[$this->meta_color_key]               = $this->meta_color_key;
        $sortable_columns[$this->meta_reorderable_key]         = $this->meta_reorderable_key;
        $sortable_columns[$this->order_status_arrangement_key] = $this->order_status_arrangement_key;

        return $sortable_columns;
    }

    /**
     * Defines sorting query for custom columns
     * @param WP_Query $wp_query
     * @return void
     */
    public function define_custom_col_sorting( WP_Query $wp_query ): void {
        $post_type = $wp_query->get('post_type');

        // only run in admin Table List for VTA Holiday Posts
        if ( is_admin() && $post_type === $this->post_type ) {
            switch ( $wp_query->get('orderby') ) {
                case $this->meta_color_key:
                    $wp_query->set('meta_key', $this->meta_color_key);
                    $wp_query->set('orderby', 'meta_value');
                    break;
                case $this->meta_reorderable_key:
                    $wp_query->set('meta_key', $this->meta_reorderable_key);
                    $wp_query->set('orderby', 'meta_value');
                    break;
                case $this->order_status_arrangement_key:
                    $order       = $wp_query->get('order');
                    $arrangement = $this->settings->get_arrangement();
                    $arrangement_sorted = $order === 'asc' ? $arrangement : array_reverse($arrangement);
                    $wp_query->set('orderby', false);
                    $wp_query->set('order', false);
                    $wp_query->set('post__in', $arrangement_sorted);
                    break;
            }
        }
    }

    // PRIVATE METHODS

    /**
     * Returns index of Order Status arrangement placement..
     * @param int $post_id
     * @return int|null placement in arrangement OR null if not in settings (i.e drafts, trash, etc.)
     */
    private function get_arrangement_num( int $post_id ): ?int {
        $arrangement = $this->settings->get_arrangement();
        $index       = array_search($post_id, $arrangement);

        return is_int($index) ? $index + 1 : null;
    }

}
