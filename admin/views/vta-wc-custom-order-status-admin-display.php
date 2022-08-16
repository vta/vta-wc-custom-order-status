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
     * @param $post - post information of newly created order status (unused)
     */
    public function order_status_create( $post ) {

      ?>
      <p class="cos-form-field">
        <label for="order_status_name">New Custom Order Status</label>
        <input type="text" name="order_status_name" id="order_status_name" value>
      </p>

      <p class="cos-form-field">
        <label for="order_status_color">Order Status Chip Color</label>
        <input type="color" name="order_status_color" id="order_status_name" value>
      </p>

      <p class="cos-form-field">
        <span>Allow Reorder</span>

        <label for="order_status_reorder-yes">Yes</label>
        <input type="radio" name="order_status_reorder" id="order_status_reorder-yes" value="true">
        <label for="order_status_reorder-yes">No</label>
        <input type="radio" name="order_status_reorder" id="order_status_reorder-no" value="false" checked>
      </p>

      <p class="cos-form-field">
        <input type="submit" class="button button-primary button-large" value="Create">
      </p>

    <?php }

}
?>

