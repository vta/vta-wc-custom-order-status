<?php
/**
 * @title Custom Plain Email Template
 * @description plain text email for custom order statuses...
 */

$order_statuses = wc_get_order_statuses();
$order_status   = $order_statuses[$order->get_status()] ?? null;

/** @var string|null $email_heading */
global $email_heading;

/** @var string|null $additional_content */
global $additional_content;

$opening_paragraph = "An order, made by %s, has now been marked \"%s\". The details of the order are as follows:";

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

echo "= " . $email_heading . " =\n\n";

if ( $order && $billing_first_name && $billing_last_name ) {
    echo sprintf($opening_paragraph, "$billing_first_name $billing_last_name", $order_status) . "\n\n";
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( $main_content ?? null ) {
    ?>
    <p><?php echo $main_content; ?></p>
    <?php
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

foreach ( $order->get_items() as $order_item ) {
    echo "{$order_item->get_name()} --- QTY: {$order_item->get_quantity()}\n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo "This is an email sent as the order status has been changed to \"{$order_status}\".\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo esc_html(wp_strip_all_tags(wptexturize($additional_content)));
}

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));
