<?php

/**
 * VTA Custom Order Status Settings
 */
class VTACosSettings {

    /** @var null|array post IDs */
    private ?array $order_status_arrangement;

    /** @var null|int|string Post ID of default custom order status */
    private $order_status_default;

    public function __construct( array $settings ) {
        foreach ( $settings as $key => $value ) {
            // special exception for arrangment. Stored as string in form save
            if ($key === 'order_status_arrangement' && is_string($value)) {
                $arr = json_decode($value) ?? [];
                $this->{$key} = $arr;
            } else {
                $this->{$key} = $value;
            }
        }
    }

    // GETTERS //

    /**
     * Returns Post ID of the default order status
     * @return int|string|null
     */
    public function get_default() {
        return $this->order_status_default;
    }

    /**
     * Returns the order
     * @return array ex. Array<['post_id' => string|int, 'order_status_id' => string, 'order_status_name' => string]>
     */
    public function get_arrangement(): array {
        return $this->order_status_arrangement ?? [];
    }

}
