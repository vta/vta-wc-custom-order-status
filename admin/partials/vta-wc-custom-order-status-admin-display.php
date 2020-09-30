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
     * Front-end display for the main settings/list page
     */
    public function order_status_list() { ?>

        <h1>VTA's WooCommerce Custom Order Status</h1>

    <?php }

    /**
     * Front-end display for creating a new custom WooCommerce order status
     */
    public function new_order_status() { ?>

        <div class="wrap">

          <h2>New Order Status</h2>

          <form action="options.php" method="post">

  <!--          <input name="submit" class="button button-primary" type="submit" value="--><?php //esc_attr_e( 'Save' ); ?><!--" />-->

          </form>

        </div>

    <?php }

}
?>

