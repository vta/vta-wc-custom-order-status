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

              <?php

              settings_fields( 'vta-new-fields' );
              do_settings_sections( 'vta-new-fields' );

              submit_button( 'Create Order Status' );

              ?>

  <!--          <input name="submit" class="button button-primary" type="submit" value="--><?php //esc_attr_e( 'Save' ); ?><!--" />-->

          </form>

        </div>

    <?php }

    /**
     * Settings Section display. Nested within new_order_status
     * @param $args
     */
    public function settings_section_new( $args ) { ?>



    <?php }

    /**
     * @param $args
     */
    public function settings_field_new( $args ) { ?>

      <label for="<?php echo $args['id']; ?>">
          <?php echo $args['title']; ?>
      </label>

      <input type="text"
             name="<?php echo $args['id']; ?>"
             id="<?php echo $args['id']; ?>">

      <?php

      register_setting( 'vta-wc-new-order-status-field', $args['id'] );

    }

}
?>

