<?php
/**
 * @title Custom HTML Email Template
 * @description HTML email for custom order statuses...
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
    error_log("VTA Custom Email Error - $e");
}

/*****************
 * EMAIL CONTENT *
 *****************/

do_action('woocommerce_email_header', $email_heading);

if ( $order && $billing_first_name && $billing_last_name ) : ?>
    <p><?php printf($opening_paragraph, "$billing_first_name $billing_last_name", $order_status); ?></p>
<?php endif;

if ( $main_content ?? null ) {
    ?>
    <p><?php echo $main_content; ?></p>
    <?php
}

/*
* @hooked WC_Emails::order_details() Shows the order details table.
* @hooked WC_Structured_Data::generate_order_data() Generates structured data.
* @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
* @since 2.5.0
*/
do_action('woocommerce_email_order_details', $order);

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo esc_html(wp_strip_all_tags(wptexturize($additional_content)));
}
?>

<?php do_action('woocommerce_email_footer'); ?>
