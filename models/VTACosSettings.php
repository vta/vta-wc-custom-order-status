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
            // special exception for arrangement. Stored as string in form save
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
     * Sets Post ID for default order status
     * @param int $post_id
     * @return void
     */
    public function set_default( int $post_id ) {
        $this->order_status_default = $post_id;
    }

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
     * @return int|null
     */
    public function get_default(): ?int {
        return (int)$this->order_status_default ?: null;
    }

    /**
     * Returns the order
     * @return int[] ex. Array<['post_id' => string|int, 'order_status_id' => string, 'order_status_name' => string]>
     */
    public function get_arrangement(): array {
        return array_map(fn( $post_id ) => (int)$post_id, $this->order_status_arrangement ?? []);
    }

    /**
     * Returns Reorderable statuses
     * @return VTACustomOrderStatus[]
     */
    public function get_reorderable_statuses(): array {
        try {
            return array_values(
                array_filter(
                    array_map(fn( int $order_status_id ) => new VTACustomOrderStatus($order_status_id), $this->get_arrangement()),
                    fn( VTACustomOrderStatus $order_status ) => $order_status->get_cos_reorderable()
                )
            );
        } catch ( Exception $e ) {
            error_log("VTACosSettings::get_reorderable_statuses() error - $e");
            return [];
        }
    }

    /**
     * Returns Reminder statuses
     * @return VTACustomOrderStatus[]
     */
    public function get_reminder_statuses(): array {
        try {
            return array_values(
                array_filter(
                    array_map(fn( int $order_status_id ) => new VTACustomOrderStatus($order_status_id), $this->get_arrangement()),
                    fn( VTACustomOrderStatus $order_status ) => $order_status->get_has_reminder_email()
                )
            );
        } catch ( Exception $e ) {
            error_log("VTACosSettings::get_reminder_statuses() error - $e");
            return [];
        }
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
