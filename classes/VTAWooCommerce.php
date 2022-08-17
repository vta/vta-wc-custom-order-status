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

    /**
     * @param VTACosSettings $settings
     * @param string $plugin_name
     * @param string $plugin_version
     */
    public function __construct( string $plugin_name, string $plugin_version, VTACosSettings $settings ) {
        $this->plugin_name    = $plugin_name;
        $this->plugin_version = $plugin_version;

        $this->settings = $settings;

        /**
         * Need to run this hook first before other filter methods are ran in the Orders page
         */
        add_action('pre_get_posts', [ $this, 'query_include_deprecated' ], 11, 1);
        add_filter('woocommerce_register_shop_order_post_statuses', [ $this, 'append_vta_cos' ], 10, 1);
        add_filter('wc_order_statuses', [ $this, 'register_vta_cos' ], 10, 1);
    }

    /**
     * @param WP_Query $wp_query
     * @return void
     */
    public function query_include_deprecated( WP_Query $wp_query ): void {
        [ 'path' => $path, 'query_params' => $query_params ] = get_query_params();

        // Orders page for all account
        $is_my_account = preg_match('/my-account\/orders/', $path);

        // list table page for all WC orders
        $is_all_orders  = count($query_params) === 1;
        $is_edit_orders = $is_all_orders && preg_match('/edit\.php/', $path) && in_array($this->shop_post_type, $query_params);

        /** @var string| string[] $post_type "shop_order" or "shop_order_refund" */
        $post_type = $wp_query->get('post_type');

        /**
         * Conditions:
         * - My Account Orders Page OR WC Orders List Table (all)
         * - post type is "shop_order"
         */
        if (
            ($is_my_account || $is_edit_orders) &&
            ((is_array($post_type) && in_array($this->shop_post_type, $post_type)) || $post_type === $this->shop_post_type)
        ) {
            $wp_query->set('post_status', 'any');
        }
    }

    /**
     * Adds our custom order statuses to WC post status via WC filter.
     * @param array $post_statuses
     * @return array array of order statuses to be registered as post statuses
     * @see https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-post-types.html#source-view.560
     * @see https://developer.wordpress.org/reference/functions/register_post_type/
     */
    public function append_vta_cos( array $post_statuses ): array {
        $post_status_keys   = array_keys($post_statuses);
        $vta_order_statuses = $this->get_cos();

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
        $vta_order_statuses = $this->get_cos();

        foreach ( $vta_order_statuses as $vta_order_status ) {
            // if not defined by WC yet,
            if ( !in_array($vta_order_status->get_cos_key(true), $post_status_keys) ) {
                $order_statuses[$vta_order_status->get_cos_key(true)] = $vta_order_status->get_cos_name();
            }
        }

        return $this->sort_order_statuses($order_statuses);
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

}
