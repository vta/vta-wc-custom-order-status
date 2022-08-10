<?php

/**
 * VTA Custom Order Status Class
 * Encapsulates "vta_order_status" post types for easier access
 */
class VTACustomOrderStatus {

    const POST_TYPE = VTA_COS_CPT;

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

        } elseif ( $post->post_type !== self::POST_TYPE ) {
            $post_type = self::POST_TYPE;
            $err_msg   = "VTACustomOrderStatus::__construct() error - Post is not of type $post_type. Post #{$post->ID} is of type {$post->post_type}";
            throw new Exception($err_msg);
        }
    }

    public function get_post(): WP_Post {
        return $this->post;
    }
}
