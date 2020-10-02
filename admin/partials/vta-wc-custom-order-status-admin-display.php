<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://jamespham.io
 * @since      1.0.0
 *
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/admin/partials
 */
class Vta_Wc_Custom_Order_Status_Admin_Display {

    /**
     * Front-end display for the order status color list page
     */
    public function order_status_color_list() { ?>

        <h2>Order Status colors</h2>
        <ul>
          <li>Fee</li>
          <li>Fi</li>
          <li>Foh</li>
        </ul>

    <?php }

    /**
     *
     */
    public function order_status_create( $post ) {

      error_log(json_encode($post, JSON_PRETTY_PRINT));

      ?>

        <h2>Create a New Order Status</h2>
        <h1>Please Work</h1>

    <?php }

}
?>

