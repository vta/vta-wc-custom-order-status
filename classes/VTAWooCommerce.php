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

    // POST VARS
    private string $shop_post_type = 'shop_order';

    /**
     * @param string $plugin_name
     * @param string $plugin_version
     */
    public function __construct( string $plugin_name, string $plugin_version ) {
        $this->plugin_name    = $plugin_name;
        $this->plugin_version = $plugin_version;

        /**
         * Need to run this hook first before other filter methods are ran in the Orders page
         */
        add_action('pre_get_posts', [ $this, 'query_include_deprecated' ], 11, 1);
    }

    /**
     * @param WP_Query $wp_query
     * @return void
     */
    public function query_include_deprecated( WP_Query $wp_query ): void {
        list('path' => $path, 'query_params' => $query_params) = get_query_params();

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

}
