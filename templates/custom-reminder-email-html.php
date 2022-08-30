<?php
/**
 * @title VTA Custom Reminder Email (HTML)
 * @description Reminder email for order status (if applicable)
 */

// GLOBALS
$order_statuses = wc_get_order_statuses();
$order_status   = $order_statuses[$order->get_status()] ?? null;

$opening_paragraph = "An order, made by %s, has now been marked %s. The details of the item are as follows:";

$billing_first_name = '';
$billing_last_name  = '';

try {
    $customer           = new WC_Customer($order->get_customer_id());
    $billing_first_name = $customer->get_first_name() ?? null;
    $billing_last_name  = $customer->get_last_name();
} catch ( Exception $e ) {
    error_log("VTA Custom Reminder Email Error - $e");
}

/*****************
 * EMAIL CONTENT *
 *****************/
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php

if ( $order && $billing_first_name && $billing_last_name ) : ?>
    <p><?php printf($opening_paragraph, "$billing_first_name $billing_last_name", $order_status); ?></p>
<?php endif;

if ( $main_content ?? null ) {
    ?>
    <p><?php echo $main_content; ?></p>

    <table><!--CALL TO ACTION BUTTON-->
        <tr>
            <td style="padding: 10px 0 20px 0">
                <div>
                    <!--[if mso]>
            <v:roundrect
                xmlns:v="urn:schemas-microsoft-com:vml"
                xmlns:w="urn:schemas-microsoft-com:office:word" href="<?php echo site_url() . '/my-account/view-order/' . $order->get_id() . '?completed=1' ?>"
                style="height:50px;v-text-anchor:middle;width:300px;"
                arcsize="8%"
                strokecolor="#361b17"
                fillcolor="#e53935">
              <w:anchorlock/>
              <center
                style="color:#ffffff;
                font-family:sans-serif;
                font-size:14px;
                font-weight:bold;">
                I HAVE PICKED UP MY ORDER
              </center>
            </v:roundrect>
            <![endif]-->
                    <a href="<?php echo site_url() . '/my-account/view-order/' . $order->get_id() . '?completed=1' ?>"
                       style="background:#e53935;border:1px solid #361b17;border-radius:4px;color:#ffffff;
               display:inline-block;font-family:sans-serif;font-size:14px;font-weight:bold;line-height:50px;
               text-align:center;text-decoration:none;width:300px;-webkit-text-size-adjust:none;mso-hide:all;"
                    >
                        I HAVE PICKED UP MY ORDER
                    </a>
                </div>
            </td>
        </tr>
    </table>
    <?php
}
/*
* @hooked WC_Emails::order_details() Shows the order details table.
* @hooked WC_Structured_Data::generate_order_data() Generates structured data.
* @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
* @since 2.5.0
*/
do_action( 'woocommerce_email_order_details', $order );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>

<?php do_action( 'woocommerce_email_footer' ); ?>
