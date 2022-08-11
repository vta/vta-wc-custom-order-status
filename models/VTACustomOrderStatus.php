<?php

/**
 * VTA Custom Order Status Class
 * Encapsulates "vta_order_status" post types for easier access
 */
class VTACustomOrderStatus {

    private string $post_type            = VTA_COS_CPT;
    private string $meta_color_key       = META_COLOR_KEY;
    private string $meta_reorderable_key = META_REORDERABLE_KEY;

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

        } elseif ( $wp_post->post_type !== $this->post_type ) {
            $err_msg = "VTACustomOrderStatus::__construct() error - Post is not of type {$wp_post->post_type}. Post #{$post->ID} is of type {$post->post_type}";
            throw new Exception($err_msg);
        }

        $this->post = $wp_post;
    }


    /**
     * Gets color associated with Order Statuses
     * @return string
     */
    public function get_cos_color(): string {
        $color = get_post_meta($this->post->ID, $this->meta_color_key, true);
        return is_string($color) ? $color : '#000';
    }

    /**
     * Returns if order status is reorder-able
     * @return bool
     */
    public function get_cos_reorderable(): bool {
        return (bool)get_post_meta($this->post->ID, $this->meta_reorderable_key, true);
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
