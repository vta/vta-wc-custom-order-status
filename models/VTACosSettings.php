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
            if ( $key === 'order_status_arrangement' && is_string($value) ) {
                $arr          = json_decode($value) ?? [];
                $this->{$key} = $arr;
            } else {
                $this->{$key} = $value;
            }
        }
    }

    // SETTERS //

    /**
     * Sets new arrangement array for settings object. Meant to be used with to_array()
     * @param array $post_ids_arr
     * @return void
     */
    public function set_arrangement( array $post_ids_arr ) {
        $this->order_status_arrangement = $post_ids_arr;
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
     * @return int[] ex. Array<['post_id' => string|int, 'order_status_id' => string, 'order_status_name' => string]>
     */
    public function get_arrangement(): array {
        return array_map(fn( $post_id ) => (int)$post_id, $this->order_status_arrangement ?? []);
    }

    /**
     * Returns object in the format to be stored in update_options
     * @return array
     */
    public function to_array(): array {
        return [
            ORDER_STATUS_DEFAULT_KEY     => $this->get_default(),
            ORDER_STATUS_ARRANGEMENT_KEY => $this->get_arrangement()
        ];
    }

}
