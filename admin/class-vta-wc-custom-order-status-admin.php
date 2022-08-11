<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://jamespham.io
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
 * TODO - (OPTIONAL) break down into smaller classes. Insert hooks into broken down classes. Too confusing to store hooks in class-vta-custom-order-status.php...
 */
class Vta_Wc_Custom_Order_Status_Admin {

    private string $plugin_name;
    private string $version;

    const POST_TYPE            = VTA_COS_CPT;
    const META_COLOR_KEY       = META_COLOR_KEY;
    const META_REORDERABLE_KEY = META_REORDERABLE_KEY;

    private string         $settings_name = VTA_COS_SETTINGS_NAME;
    private VTACosSettings $settings;
    private VTACustomOrderStatuses $order_statuses;

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
        $this->order_statuses = new VTACustomOrderStatuses($plugin_name, $version);
    }

    /**
     * Register the CSS & JavaScript for the admin area.
     * @return void
     * @hooked admin_enqueue_scripts
     */
    public function enqueue_scripts(): void {
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
