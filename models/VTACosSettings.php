<?php

/**
 * VTA Custom Order Status Settings
 */
class VTACosSettings {

    /** @var array ex. [] */
    private array $order_status_default;

    /** @var null|int|string Post ID of default custom order status */
    private $order_status_arrangement;

    public function __construct( array $settings ) {
        foreach ( $settings as $key => $value ) {
            $this->{$key} = $value;
        }
    }
}
