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

    // GETTERS //

}
