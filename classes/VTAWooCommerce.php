<?php

/**
 * @class VTA WooCommerce Integration
 * Integrates custom order statuses to core WooCommerce hooks to reflect admin settings & custom posts in this plugin.
 */
class VTAWooCommerce {

    // PLUGIN VARS
    private string $plugin_name;
    private string $plugin_version;

    /** @var array previous hardcoded order statuses defined in Child Theme. Use for fallback purposes. */
    private array $deprecated_order_statuses = [
        'wc-received'  => 'Order Received',
        'wc-proof'     => 'Proof Ready',
        'wc-special'   => 'Special',
        'wc-finishing' => 'Finishing',
        'wc-ready'     => 'Ready for Pick Up',
        'wc-pony'      => 'Pony'
    ];

    // WC VARS
    private string $shop_post_type = 'shop_order';

    // VTA COS VARS
    private string         $post_type = VTA_COS_CPT;
    private VTACosSettings $settings;

    /** @var VTACustomOrderStatus[] */
    private array $vta_cos;

    /** @var string[] */
    private array $pending_status_keys;

    /**
     * @param VTACosSettings $settings
     * @param string $plugin_name
     * @param string $plugin_version
     */
    public function __construct( string $plugin_name, string $plugin_version, VTACosSettings $settings ) {
        $this->plugin_name    = $plugin_name;
        $this->plugin_version = $plugin_version;

        $this->settings            = $settings;
        $this->vta_cos             = $this->get_cos();
        $this->pending_status_keys = $this->get_pending_cos_keys(true);

        /**
         * Need to run this hook first before other filter methods are ran in the Orders page
         */
        add_action('pre_get_posts', [ $this, 'query_include_deprecated' ], 10, 1);
        add_action('pre_get_posts', [ $this, 'query_pending_orders' ], 10, 1);

        // Register Custom Order Status to WC
        add_filter('woocommerce_register_shop_order_post_statuses', [ $this, 'append_vta_cos' ], 10, 1);
        add_filter('wc_order_statuses', [ $this, 'register_vta_cos' ], 10, 1);

        // WC List Table Customization
        add_filter("views_edit-{$this->shop_post_type}", [ $this, 'update_quicklinks' ], 10, 1);
        add_action('admin_head', [ $this, 'add_status_col_styles' ]);
        add_filter("bulk_actions-edit-{$this->shop_post_type}", [ $this, 'update_custom_bulk_actions' ], 11, 1);

        // Order Status for new order statuses
        add_action('woocommerce_checkout_order_processed', [ $this, 'use_default_order_status' ], 10, 3);
    }

    // POST STATUS / ORDER STATUS REGISTRATION CALLBACKS //

    /**
     * Adds our custom order statuses to WC post status via WC filter.
     * @param array $post_statuses
     * @return array array of order statuses to be registered as post statuses
     * @see https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-post-types.html#source-view.560
     * @see https://developer.wordpress.org/reference/functions/register_post_type/
     */
    public function append_vta_cos( array $post_statuses ): array {
        $post_status_keys   = array_keys($post_statuses);
        $vta_order_statuses = $this->vta_cos;

        foreach ( $vta_order_statuses as $vta_order_status ) {
            // if not defined by WC yet,
            if ( !in_array($vta_order_status->get_cos_key(true), $post_status_keys) ) {
                $post_statuses[$vta_order_status->get_cos_key(true)] = [
                    'label'                     => _x($vta_order_status->get_cos_name(), 'Order status', 'woocommerce'),
                    'public'                    => false,
                    'exclude_from_search'       => false,
                    'show_in_admin_all_list'    => true,
                    'show_in_admin_status_list' => true,
                    'label_count'               => _n_noop("{$vta_order_status->get_cos_name()} <span class='count'>(%s)</span>", "{$vta_order_status->get_cos_name()} <span class='count'>(%s)</span>", 'woocommerce'),
                ];
            }
        }

        return $post_statuses;
    }

    /**
     * Adds to order status to List of WC Order Statuses
     * @return void
     */
    public function register_vta_cos( array $order_statuses ): array {
        $post_status_keys   = array_keys($order_statuses);
        $vta_order_statuses = $this->vta_cos;

        foreach ( $vta_order_statuses as $vta_order_status ) {
            // if not defined by WC yet,
            if ( !in_array($vta_order_status->get_cos_key(true), $post_status_keys) ) {
                $order_statuses[$vta_order_status->get_cos_key(true)] = $vta_order_status->get_cos_name();
            }
        }

        return $this->sort_order_statuses($order_statuses);
    }

    // WC List Table //

    /**
     * Applies specific styles for custom order status colors.
     * @return void
     */
    public function add_status_col_styles(): void {
        [ 'query_params' => $query_params, 'path' => $path ] = get_query_params();

        if (
            is_admin() &&
            in_array($this->shop_post_type, $query_params) &&
            preg_match('/edit\.php/', $path)
        ) {
            ?>
            <style>
                <?php foreach ($this->vta_cos as $order_status) {
                    $status = $order_status->get_cos_key();
                    $color = $order_status->get_cos_color();

                    $css_rule = <<<CSS
                        mark.order-status.status-$status {
                            background: $color;
                            color: #fff;
                            font-weight: 500;
                            text-shadow: 1px 1px rgba(0,0,0,0.5);
                        }
                    CSS;

                    echo $css_rule;
                }?>
            </style>
            <?php
        }
    }

    /**
     * Adds "Pending Orders" view
     * @param array $views
     * @return array
     */
    public function update_quicklinks( array $views ): array {
        // save for later insertion after sorting
        $all_html = $views['all'];
        unset($views['all']);

        [ 'query_params' => $query_params ] = get_query_params();

        // pending orders
        $html = sprintf(
            '<a href="edit.php?post_status=pending-orders&#038;post_type=shop_order" %s>Pending Orders <span class="count">(%d)</span></a>',
            ($query_params['post_status'] ?? null) === 'pending-orders' ? 'class="current" aria-current="page"' : '',
            $this->get_pending_orders_count()
        );

        // add before all other default statuses
        $views = $this->sort_order_statuses($views);
        $views = [ 'pending-orders' => $html ] + $views;

        // re-insert all view at the beginning
        $views = [ 'all' => $all_html ] + $views;

        // remove all empty values
        return array_filter($views, fn( $link ) => is_string($link));
    }

    /**
     * Adds custom order statuses to bulk actions and sorts them accordingly
     * @param array $bulk_actions
     * @return array
     */
    public function update_custom_bulk_actions( array $bulk_actions ): array {
        [ 'query_params' => $query_params, 'path' => $path ] = get_query_params();

        if (
            is_admin() &&
            in_array($this->shop_post_type, $query_params) &&
            preg_match('/edit\.php/', $path)
        ) {
            // add to beginning of sorted menu
            $bulk_action_trash = [ 'trash' => $bulk_actions['trash'] ];
            $new_bulk_actions  = [];

            // custom sorting...
            $keyed_cos = [];
            array_walk($this->vta_cos, function ( VTACustomOrderStatus $order_status ) use ( &$keyed_cos ) {
                $keyed_cos[$order_status->get_cos_key(true)] = $order_status;
            });
            $sorted_keyed_cos   = $this->sort_order_statuses($keyed_cos);
            $filtered_keyed_cos = array_filter($sorted_keyed_cos, fn( $val ) => !is_int($val));

            foreach ( $filtered_keyed_cos as $order_status ) {
                $new_bulk_actions["mark_{$order_status->get_cos_key()}"] = "Change status to {$order_status->get_cos_name()}";
            }

            return $bulk_action_trash + $new_bulk_actions;
        }
        // else...
        return $bulk_actions;
    }

    // QUERY MODIFICATIONS //

    /**
     * Includes any deprecated order statuses to be queried on get all pages.
     * @param WP_Query $wp_query
     * @return void
     */
    public function query_include_deprecated( WP_Query $wp_query ): void {
        [ 'path' => $path, 'query_params' => $query_params ] = get_query_params();

        // Orders page for all account
        $is_my_account = (bool)preg_match('/my-account\/orders/', $path);

        // list table page for all WC orders
        $is_all_orders  = count($query_params) === 1;
        $is_edit_orders = $is_all_orders && preg_match('/edit\.php/', $path) && in_array($this->shop_post_type, $query_params);

        /**
         * Conditions:
         * - My Account Orders Page OR WC Orders List Table (all)
         * - post type is "shop_order"
         */
        if ( $is_my_account || $is_edit_orders ) {
            $wp_query->set('post_status', 'any');
        }
    }

    /**
     * Intercepts Query for Pending Orders view & returns the correct query for post statuses
     * @param WP_Query $wp_query
     * @return void
     */
    public function query_pending_orders( WP_Query $wp_query ): void {
        [ 'path' => $path, 'query_params' => $query_params ] = get_query_params();

        $is_edit_orders = preg_match('/edit\.php/', $path) && in_array($this->shop_post_type, $query_params);

        if (
            is_admin() && $is_edit_orders &&
            in_array('pending-orders', $query_params) &&
            $wp_query->get('post_type') === $this->shop_post_type
        ) {
            remove_action('pre_get_posts', [ $this, 'query_pending_orders' ], 11);
            $wp_query->set('post_status', $this->pending_status_keys);
            add_action('pre_get_posts', [ $this, 'query_pending_orders' ], 11, 1);
        }
    }

    // WC AUTOMATIONS //

    /**
     * Assigns the default order status for newly created orders
     * @param int $order_id
     * @param array $posted_data
     * @param WC_Order $order
     * @return void
     * TODO - move to email manager...
     */
    public function use_default_order_status( int $order_id, array $posted_data, WC_Order $order ): void {
        $default_status_key = $this->get_default_status_key();
        $order->update_status($default_status_key);

        // need to trigger customer "Processing" email here because we do not go through "Processing" status initially
        if ( preg_match('/processing/', $order->get_status()) ) {
            WC_Emails::instance();
            $processing_email = new WC_Email_Customer_Processing_Order();
            $processing_email->trigger($order_id);
            // need to send admin "New Order" email as well
            $new_order_email = new WC_Email_New_Order();
            $new_order_email->trigger($order_id);
        }
    }

    // PRIVATE METHODS //

    /**
     * Returns all available Order Statuses
     * @return VTACustomOrderStatus[]
     */
    private function get_cos(): array {
        try {
            $wp_query       = new WP_Query([
                'post_type'      => $this->post_type,
                'post_status'    => 'publish',
                'posts_per_page' => -1
            ]);
            $order_statuses = $wp_query->posts;
            return array_map(fn( $post ) => new VTACustomOrderStatus($post), $order_statuses);

        } catch ( Exception $e ) {
            error_log("VTAWooCommerce::get_cos() error. Could not convert post status VTA Custom Order Statuses. - $e");
            return [];
        }
    }

    /**
     * Sorts order statuses based on plugin settings arrangement field.
     * NOTE: Only sorts arrays with keys => values i.e. [ 'wc-processing' => any value ...]
     * @param array $order_statuses
     * @return array
     */
    private function sort_order_statuses( array $order_statuses ): array {
        $arrangement_ids = $this->settings->get_arrangement();

        try {
            $arrangement_cos_keys = array_map(fn( int $post_id ) => (new VTACustomOrderStatus($post_id))->get_cos_key(true), $arrangement_ids);
            return array_replace(array_flip($arrangement_cos_keys), $order_statuses);
        } catch ( Exception $e ) {
            error_log("VTAWooCommerce::sort_order_status error. Could not sort custom order statuses - $e");
            return [];
        }
    }

    /**
     * Gets count of custom order statuses
     * @return int
     */
    private function get_pending_orders_count(): int {
        $non_reorderable_cos = $this->get_pending_cos_keys();
        $orders              = wc_get_orders([ 'status' => $non_reorderable_cos ]);

        // manually filter orders since pre_get_posts interferes with count
        $filtered_orders = array_filter(
            $orders,
            fn( WC_Order $wc_order ) => in_array($wc_order->get_status(), $non_reorderable_cos)
        );

        return count($filtered_orders);
    }

    /**
     * Returns all Custom Order Status without "Reoderable" abilities.
     * @param bool $with_prefix adds 'wc-' to order status keys
     * @return array
     */
    private function get_pending_cos_keys( bool $with_prefix = false ): array {
        // hard-coded from WC default statuses...
        $other_non_pending_statuses = [
            'cancelled',
            'pending',
            'refunded',
            'failed'
        ];

        $vta_cos      = $this->vta_cos;
        $filtered_cos = array_filter(
            $vta_cos,
            fn( VTACustomOrderStatus $order_status ) => !$order_status->get_cos_reorderable() && !in_array($order_status->get_cos_key(), $other_non_pending_statuses)
        );

        return array_values(array_map(fn( VTACustomOrderStatus $order_status ) => $order_status->get_cos_key($with_prefix), $filtered_cos));
    }

    /**
     * Returns the current default order status key defined by plugin settings.
     * @return string i.e. "wc-received"
     */
    private function get_default_status_key(): ?string {
        $default_order_status_id = $this->settings->get_default();

        try {
            $order_status = new VTACustomOrderStatus($default_order_status_id);
            return $order_status->get_cos_key(true);
        } catch ( Exception $e ) {
            error_log("VTAWooCommerce::get_default_status() error - $e");
            return null;
        }
    }

}
