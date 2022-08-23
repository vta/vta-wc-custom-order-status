<?php
if ( !defined('ABSPATH') ) {
    exit;
}

$wc_plugin_path = trailingslashit(WP_PLUGIN_DIR) . 'woocommerce/woocommerce.php';

/**
 * @class VTACosEmailManager
 * Custom class to provide email functionality to all custom classes without default WooCommerce email templates.
 */
class VTACosEmailManager {

    /** @var string[] */
    private array          $wc_list_items_id = [];
    private WC_Emails      $wc_emails;
    private VTACosSettings $settings;

    public function __construct( VTACosSettings $settings ) {
        $this->settings = $settings;

        // access WC core classes after plugins are loaded
        add_action('plugins_loaded', [ $this, 'get_existing_emails' ]);
        add_action('plugins_loaded', [ $this, 'register_email_triggers' ]);

        // include the email class files
        add_filter('woocommerce_email_classes', [ $this, 'add_custom_emails' ]);
    }

    /**
     * Associates custom order status to VTACustomEmail and creates email triggers
     * @return void
     */
    public function register_email_triggers(): void {
        try {
            $order_statuses = array_map(fn( int $post_id ) => new VTACustomOrderStatus($post_id), $this->settings->get_arrangement());

            foreach ( $order_statuses as $order_status ) {
                $has_template = current(array_filter($this->wc_list_items_id, function ( string $id /** i.e. "customer_completed_order" */ ) use ( $order_status ) {
                    return preg_match("/{$order_status->get_cos_key(false)}/", $id);
                }));

                // only assign custom emails to Order Statuses without core WC Email class
//                if ( $has_template )
//                    add_action("woocommerce_order_status_{$order_status->get_cos_key(false)}");
            }

        } catch ( Exception $e ) {
            error_log("VTACosEmailManager::register_email_triggers() error - $e");
        }
    }

    /**
     * Registers our custom email classes
     * @param array $emails
     * @return array
     */
    public function add_custom_emails( array $emails ): array {
        if ( !isset($emails['VTACustomEmail']) ) {
            $emails['VTACustomEmail'] = include_once(plugin_dir_path(__DIR__) . 'emails/class-finishing-email.php');
        }
        $emails['Finishing_Email'] = include_once(plugin_dir_path(__DIR__) . 'emails/class-finishing-email.php');
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
                $this->wc_list_items_id[] = $wc_email->id;
            }
        }
    }
}
