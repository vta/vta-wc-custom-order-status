<?php

/**
 * VTA Custom Order Status Class
 * Encapsulates "vta_order_status" post types for easier access
 */
class VTACustomOrderStatus {

    private string $post_type            = VTA_COS_CPT;
    private string $meta_color_key       = META_COLOR_KEY;
    private string $meta_reorderable_key = META_REORDERABLE_KEY;
    private string $meta_has_reminder = META_HAS_REMINDER_KEY;

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
     * Returns the order status key
     * @param bool $with_prefix prepend "wc_" if true
     * @return string i.e. "wc-processing"
     */
    public function get_cos_key( bool $with_prefix = false ): string {
        return $with_prefix ? "wc-{$this->post->post_name}" : $this->post->post_name;
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

    /**
     * Returns the hook name of for triggering its custom email
     * @return string
     */
    public function get_email_action(): string {
        return "custom_email_{$this->get_cos_key()}";
    }

    /**
     * Returns if order status has reminder email.
     * @return bool
     */
    public function get_has_reminder_email(): bool {
        return (bool)get_post_meta($this->post->ID, $this->meta_reorderable_key, true);
    }

    /**
     * Returns custom order status obj when searched by $key
     * @param string $cos_key ex. "processing" or "wc-processing"
     * @return VTACustomOrderStatus
     * @throws Exception
     */
    static public function get_cos_by_key( string $cos_key ): VTACustomOrderStatus {
        $wp_query = new WP_Query([
            'post_type'      => VTA_COS_CPT,
            'post_status'    => 'publish',
            'post_name__in'  => [ "wc-$cos_key", $cos_key ],
            'posts_per_page' => 1
        ]);

        return new VTACustomOrderStatus($wp_query->post);
    }
}
