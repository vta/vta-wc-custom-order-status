<?php

/**
 * Settings manager for plugin
 */
class VTACosSettingsManager {

    // PLUGIN vars
    private string $plugin_name, $plugin_version;

    // POST vars
    private string $post_type            = VTA_COS_CPT;
    private string $meta_color_key       = META_COLOR_KEY;
    private string $meta_reorderable_key = META_REORDERABLE_KEY;

    // SETTINGS var
    private VTACosSettings $settings;
    private string         $settings_name                = VTA_COS_SETTINGS_NAME;
    private string         $settings_page                = VTA_COS_SETTINGS_PAGE;
    private string         $settings_field               = VTA_COS_SETTINGS_FIELD;
    private string         $order_status_default_key     = ORDER_STATUS_DEFAULT_KEY;
    private string         $order_status_arrangement_key = ORDER_STATUS_ARRANGEMENT_KEY;

    /**
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
        add_action('admin_init', [ $this, 'settings_api_init' ]);
        add_action('admin_menu', [ $this, 'register_options_page' ]);
        add_action('admin_post_default_settings', [ $this, 'default_settings' ]);
    }

    /**
     * Enqueues Admin/JS scripts for Settings page.
     * @return void
     */
    public function enqueue_scripts(): void {
        [ 'query_params' => $query_params ] = get_query_params();

        // Settings page
        $is_settings_page = in_array($this->post_type, $query_params) && in_array($this->settings_page, $query_params);
        if ( is_admin() && $is_settings_page ) {
            wp_enqueue_style(
                "{$this->plugin_name}_settings_css",
                plugin_dir_url(__DIR__) . 'admin/css/settings.css',
                [],
                $this->plugin_version
            );

            // todo - missing jquery-ui-core
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_style('wp-jquery-ui-dialog');
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_script(
                "{$this->plugin_name}_settings_js",
                plugin_dir_url(__DIR__) . 'admin/js/settings.js',
                [ 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-dialog' ],
                $this->plugin_version,
                true
            );
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
                $this->order_status_default_key     => (int)$this->settings->get_default(),
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
                'label_for'                         => $this->order_status_default_key,
                $this->order_status_default_key     => (int)$this->settings->get_default(),
                $this->order_status_arrangement_key => $this->settings->get_arrangement()
            ]
        );
    }

    // SYNCING METHODS //

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

        if ( count($default_statuses) ) {
            // package arguments into array to convert to POST
            foreach ( $default_statuses as $order_status_key => $order_status_val ) {
                $arr = [
                    'name'                => $order_status_key,
                    'title'               => $order_status_val,
                    $this->meta_color_key => $default_colors[$order_status_key] ?? '#7D7D7D'
                ];
                $this->save_order_status($arr);
            }

            // We should sync settings after setting default Statuses
            $this->sync_settings();
        }
    }

    /**
     * Deletes current settings and creates fresh posts & settings.
     * @return void
     * @hooked admin_post_default_settings
     */
    public function default_settings(): void {
        $this->delete_posts_settings();
        $this->sync_default_statuses();

        status_header(200);
        wp_redirect("/wp-admin/edit.php?post_type={$this->post_type}&page={$this->settings_page}");
    }

    // RENDER METHODS //

    /**
     * Renders the entire
     * @return void
     */
    public function render_settings_page(): void {
        $reorderable_statuses = $this->settings->get_reorderable_statuses();
        $reminder_statuses    = $this->settings->get_reminder_statuses();
        include_once( plugin_dir_path(__DIR__) . '/admin/views/settings-page.php' );
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
     * Form field for settings arrangement (order) of Custom Order Statuses
     * NOTE: "order_status_arrangement" value should be a JSON encoded string
     * @param array $args
     * @return void
     */
    public function render_order_arrangement_field( array $args ): void {
        $label_for                = esc_attr($args['label_for']);
        $name                     = "$this->settings_name[$label_for]";
        $order_status_arrangement = $args[$this->order_status_arrangement_key];
        $default_post_id          = (int)$args[$this->order_status_default_key];
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

        $default_post_id          = $args[$this->order_status_default_key];
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

    // PRIVATE METHODS //

    /**
     * Assigns settings if empty or arrangement value is empty
     * @return void
     */
    private function sync_settings(): void {
        $default_post_id = null;

        $wp_query       = new WP_Query([
            'post_status' => 'publish',
            'post_type'   => $this->post_type,
        ]);
        $order_statuses = $wp_query->get_posts();
        $order_statuses = is_array($order_statuses) ? $order_statuses : [];

        $order_statuses = array_map(function ( WP_Post $post ) use ( &$default_post_id ) {
            if ( $post->post_type && preg_match('/(received)|(processing)/i', $post->post_title) ) {
                $default_post_id = $post->ID;
            }
            return $post->ID;
        }, $order_statuses);

        $options = [
            $this->order_status_arrangement_key => $order_statuses,
            $this->order_status_default_key     => $default_post_id ?? $order_statuses[0] ?? null,
        ];

        update_option($this->settings_name, $options);
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
            'name'               => '',
            'title'              => '',
            META_COLOR_KEY       => '#7D7D7D',
            META_REORDERABLE_KEY => false,
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
            update_post_meta($post_id, $this->meta_color_key, $arr[$this->meta_color_key] ?? '#7D7D7D');
            update_post_meta($post_id, $this->meta_reorderable_key, $arr[$this->meta_reorderable_key] ?? false);
        }

    }

    /**
     * Deletes all plugin settings and custom posts
     * @return void
     */
    private function delete_posts_settings(): void {
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

        delete_option($this->settings_name);
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

}
