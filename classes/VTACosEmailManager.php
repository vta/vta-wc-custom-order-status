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

    /** @var string[] */
    private array $wc_list_items_id = [];

    /** @var VTACustomOrderStatus[] */
    private array $no_email_statuses = [];

    public function __construct( VTACosSettings $settings ) {
        $this->settings = $settings;

        // access WC core classes after plugins are loaded
        add_action('plugins_loaded', [ $this, 'get_existing_emails' ]);
        add_action('plugins_loaded', [ $this, 'filter_no_email_status' ]);
//        add_action('plugins_loaded', [ $this, 'register_email_triggers' ]);

        // add custom email classes
        add_filter('woocommerce_email_classes', [ $this, 'add_custom_emails' ], 10, 1);
    }

    /**
     * Returns list of order statuses that do not have corresponding core WC email.
     * @return void
     */
    public function filter_no_email_status(): void {
        try {
            $order_statuses = array_map(fn( int $post_id ) => new VTACustomOrderStatus($post_id), $this->settings->get_arrangement());

            foreach ( $order_statuses as $order_status ) {
                $has_template = current(array_filter($this->wc_list_items_id, function ( string $id /** i.e. "customer_completed_order" */ ) use ( $order_status ) {
                    return preg_match("/{$order_status->get_cos_key(false)}/", $id);
                }));

                // add order status without email template
                if ( !$has_template ) {
                    $this->no_email_statuses[] = $order_status;
                    // add_action("woocommerce_order_status_{$order_status->get_cos_key(false)}");
                }
            }

        } catch ( Exception $e ) {
            error_log("VTACosEmailManager::register_email_triggers() error - $e");
        }
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
     * Retrieves existing emails from WC core email class.
     * @return void
     */
    public function get_existing_emails(): void {
        if ( class_exists('WC_Emails') ) {
            $this->wc_emails = new WC_Emails();
            $wc_email_list   = $this->wc_emails->get_emails();

            foreach ( $wc_email_list as $wc_email ) {
                if ( $wc_email->id ?? null )
                    $this->wc_list_items_id[] = $wc_email->id;
            }
        }
    }
}
