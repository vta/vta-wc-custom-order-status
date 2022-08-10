<?php

/**
 * VTA Custom Order Status Class
 * Encapsulates "vta_order_status" post types for easier access
 */
class VTACustomOrderStatus {

    const POST_TYPE            = VTA_COS_CPT;
    const META_COLOR_KEY       = META_COLOR_KEY;
    const META_REORDERABLE_KEY = META_REORDERABLE_KEY;

    private WP_Post $post;

    /**
     * Encapsulates WP_Post in custom
     * @param WP_Post|int $post
     * @throws Exception
     */
    public function __construct( $post ) {
        $wp_post = get_post($post);

        // Invalid constructor parameter Exceptions...
        if ( !$wp_post instanceof WP_Post ) {
            $post_arg_json = json_encode(is_int($post) ? $post : (array)$post, JSON_PRETTY_PRINT);
            $err_msg       = "VTACustomOrderStatus::__construct() error - Invalid post. No post found for \"$post_arg_json\"";
            throw new Exception($err_msg);

        } elseif ( $wp_post->post_type !== self::POST_TYPE ) {
            $post_type = self::POST_TYPE;
            $err_msg   = "VTACustomOrderStatus::__construct() error - Post is not of type $post_type. Post #{$post->ID} is of type {$post->post_type}";
            throw new Exception($err_msg);
        }

        $this->post = $wp_post;
    }

    /**
     * Get Order Status key. Should be used with WooCommerce orders when setting statuses...
     * @param bool $with_prefix
     * @return string
     * @throws Exception
     */
    public function get_cos_key( bool $with_prefix = false ): string {
        $post_name = trim($this->post->post_name);

        if ( empty($post_name) ) {
            throw new Exception('VTACustomOrderStatus::get_cos_key() error. No order status key is set');
        }

        // add "wc_" for order status.
        if ( $with_prefix ) {
            $post_name = "wc_$post_name";
        }

        return $post_name;
    }

    /**
     * Gets color associated with Order Statuses
     * @return string
     */
    public function get_cos_color(): string {
        $color = get_post_meta($this->post->ID, self::META_COLOR_KEY, true);
        return is_string($color) ? $color : '#000';
    }

    /**
     * Returns if order status is reorder-able
     * @return bool
     */
    public function get_cos_reorderable(): bool {
        return get_post_meta($this->post->ID, self::META_REORDERABLE_KEY, true);
    }

    /**
     * Returns the Custom Order Status name
     * @return string
     */
    public function get_cos_name(): string {
        return $this->post->post_title;
    }

    /**
     * Returns Post ID of the custom order status
     * @return int
     */
    public function get_post_id(): int {
        return $this->post->ID;
    }

    /**
     * Returns core WP_Post of Custom Order Status
     * @return WP_Post
     */
    public function get_post(): WP_Post {
        return $this->post;
    }
}
