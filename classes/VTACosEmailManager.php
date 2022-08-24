<?php
if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * @class VTACosEmailManager
 * Custom class to provide email functionality to all custom classes without default WooCommerce email templates.
 */
class VTACosEmailManager {

    private WC_Emails      $wc_emails;
    private VTACosSettings $settings;

    /** @var VTACustomOrderStatus[] */
    private array $no_email_statuses;

    public function __construct( VTACosSettings $settings ) {
        $this->wc_emails = new WC_Emails();
        $this->settings  = $settings;

        $existing_email_ids      = $this->get_existing_emails();
        $this->no_email_statuses = $this->filter_no_email_status($existing_email_ids);

        // add custom email classes
        add_filter('woocommerce_email_classes', [ $this, 'add_custom_emails' ], 11, 1);
    }

    /**
     * Registers our custom email classes
     * @param array $emails existing email objects from WC core plugin.
     * @return array
     */
    public function add_custom_emails( array $emails ): array {
        foreach ( $this->no_email_statuses as $order_status ) {
            $order_status_key = $order_status->get_cos_key();
            $formatted_key    = str_replace('-', '_', ucwords($order_status_key, '-'));
            $custom_email     = new VTACustomEmail($order_status);

            $emails["VTACustomEmail_$formatted_key"] = $custom_email;
        }
        return $emails;
    }

    /**
     * Sends email for order status change
     * @param int $order_id
     * @param WC_Order $order
     * @return void
     */
    public function send_email( int $order_id, WC_Order $order ): void {
        try {
            $order_status = VTACustomOrderStatus::get_cos_by_key($order->get_status());
            WC_Emails::instance(); // must re-initiate class to add custom email classes
            do_action($order_status->get_email_action(), $order);

        } catch ( Exception $e ) {
            error_log("VTACosEmailManager::send_email() error. Could not send email for Order #$order_id - $e");
        }
    }

    // PRIVATE METHODS //

    /**
     * Retrieves existing emails from WC core email class.
     * @return string[]
     */
    private function get_existing_emails(): array {
        $existing_email_ids = [];

        if ( class_exists('WC_Emails') ) {
            $wc_email_list = $this->wc_emails->get_emails();

            foreach ( $wc_email_list as $wc_email ) {
                if ( $wc_email->id ?? null )
                    $existing_email_ids[] = $wc_email->id;
            }
        }
        return $existing_email_ids;
    }

    /**
     * Returns list of order statuses that do not have corresponding core WC email.
     * @return VTACustomOrderStatus[]
     */
    private function filter_no_email_status( array $existing_email_ids ): array {
        $no_email_statuses = [];

        try {
            $order_statuses = array_map(fn( int $post_id ) => new VTACustomOrderStatus($post_id), $this->settings->get_arrangement());

            foreach ( $order_statuses as $order_status ) {
                $has_template = current(array_filter($existing_email_ids, function ( string $id /** i.e. "customer_completed_order" */ ) use ( $order_status ) {
                    return preg_match("/{$order_status->get_cos_key(false)}/", $id);
                }));

                // add order status without email template
                // also create action to send email template for that particular order status change
                if ( !$has_template ) {
                    $no_email_statuses[] = $order_status;
                    add_action("woocommerce_order_status_{$order_status->get_cos_key(false)}", [ $this, 'send_email' ], 10, 2);
                }
            }

        } catch ( Exception $e ) {
            error_log("VTACosEmailManager::register_email_triggers() error - $e");
        }

        return $no_email_statuses;
    }
}
