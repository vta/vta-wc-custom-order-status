<?php

/**
 * VTA Custom Order Status Class
 * Encapsulates "vta_order_status" post types for easier access
 */
class VTACustomOrderStatus {

    const POST_TYPE = VTA_COS_CPT;

    private $post;

    /**
     * Encapsulates WP_Post in custom
     * @param WP_Post|int $post
     */
    public function __construct( $post ) {
    }
}
