<?php
if ( !defined('ABSPATH') ) {
    exit;
}

/**
 * @class VTACosEmailManager
 * Custom class to provide email functionality to all custom classes without default WooCommerce email templates.
 */
class VTACosEmailManager {

    private string $reminder_emails_hook = 'vta_send_reminder_emails';

    private WC_Emails      $wc_emails;
    private VTACosSettings $settings;

    /** @var VTACustomOrderStatus[] */
    private array $no_email_statuses;

    public function __construct( VTACosSettings $settings ) {

        $this->wc_emails = WC_Emails::instance();
        $this->settings  = $settings;

        $existing_email_ids      = $this->get_existing_emails();
        $this->no_email_statuses = $this->filter_no_email_status($existing_email_ids);

        // override default WC emails templates
        add_filter('woocommerce_locate_template', [ $this, 'use_plugin_wc_overrides' ]);

        // add custom email classes
        add_filter('woocommerce_email_classes', [ $this, 'add_custom_emails' ], 10, 1);

        // Default Order Status for new orders
        add_action('woocommerce_checkout_order_created', [ $this, 'use_default_order_status' ], 10, 1);

        // custom hook to send reminder emails
        add_action($this->reminder_emails_hook, [ $this, 'send_reminder_emails' ]);

        // must re-initialize emails within Emails settings page to access Email settings
        $this->add_custom_emails_settings();
    }

    // CUSTOM EMAILS //

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

            $custom_email_key = "VTACustomEmail_$formatted_key";
            if ( !isset($emails[$custom_email_key]) ) {
                $emails[$custom_email_key] = $custom_email;
            }

            $custom_reminder_email_key = "{$custom_email_key}_Reminder";
            if ( $order_status->get_has_reminder_email() && !isset($emails[$custom_reminder_email_key]) ) {
                $reminder_email = new VTACustomEmail($order_status, true);

                $emails[$custom_reminder_email_key] = $reminder_email;
                $this->schedule_reminder_emails($order_status, $reminder_email->get_reminder_time());
            }
        }
        return $emails;
    }

    /**
     * Sends email for order status change
     * @param int $order_id
     * @param WC_Order $order
     * @param bool|null $is_reminder
     * @return void
     */
    public function send_email( int $order_id, WC_Order $order, bool $is_reminder = null ): void {
        try {
            $order_status    = VTACustomOrderStatus::get_cos_by_key($order->get_status());
            $this->wc_emails = WC_Emails::instance(); // must re-initiate class to trigger custom email classes
            $this->wc_emails->init(); // initiate only for custom emails...
            do_action($order_status->get_email_action() . ($is_reminder ? '_reminder' : ''), $order);

        } catch ( Exception $e ) {
            error_log("VTACosEmailManager::send_email() error. Could not send email for Order #$order_id - $e");
        }
    }

    /**
     * Executes all reminder emails notifications for given time.
     * @param VTACustomOrderStatus $order_status
     * @param string $time
     * @return void
     */
    public function schedule_reminder_emails( VTACustomOrderStatus $order_status, string $time = '08:00' ): void {
        $tomorrow_time = $this->getDateTimePacific('+1 day');

        preg_match('/(\d+):(\d+)/', $time, $matches);
        [ 1 => $hours, 2 => $minutes ] = $matches;

        $tomorrow_time->setTime((int)$hours ?? 0, (int)$minutes ?? 0);

        $tomorrow_day = $tomorrow_time->format('l');
        $is_weekend   = $tomorrow_day === 'Saturday' || $tomorrow_day === 'Sunday';

        if ( !wp_get_scheduled_event($this->reminder_emails_hook, [ $order_status ]) && !$is_weekend ) {
            wp_schedule_single_event($tomorrow_time->getTimestamp(), $this->reminder_emails_hook, [ $order_status ]);
        }
    }

    /**
     * Mass sends reminder emails for all order statuses
     * @param VTACustomOrderStatus $order_status
     * @return void
     */
    public function send_reminder_emails( VTACustomOrderStatus $order_status ): void {
        try {
            $wc_orders_query = new WC_Order_Query([
                'limit'  => -1,
                'status' => $order_status->get_cos_key()
            ]);

            /** @var WC_Order[] $wc_orders */
            $wc_orders = $wc_orders_query->get_orders();

            foreach ( $wc_orders as $order ) {
                $this->send_email($order->get_id(), $order, true);
            }
        } catch ( Exception $e ) {
            error_log("VTACosEmailManager::send_reminder_emails() error - $e");
        }
    }

    /**
     * Assigns the default order status for newly created orders
     * @param WC_Order $order
     * @return void
     */
    public function use_default_order_status( WC_Order $order ): void {
        $default_status_key = $this->get_default_status_key();
        $order->update_status($default_status_key);

        $new_order_email = new WC_Email_New_Order();
        $new_order_email->trigger($order->get_id());
    }

    // WOOCOMMERCE TEMPLATE OVERRIDES //

    /**
     * Locate WC email templates within this plugin to override default WC email templates.
     * NOTE: This does not override WC templates defined within a theme
     * @param string $template_name default template file path
     * @param string $template_path template file slug
     * @param string $default_path template file name
     * @return string
     */
    public function use_plugin_wc_overrides(
        string $template_name,
        string $template_path = '',
        string $default_path = ''
    ): string {
        $wc_templates_dir = ABSPATH . 'wp-content/plugins/woocommerce/templates/';

        // check if template file exists within plugin defined templates
        if ( preg_match("#$wc_templates_dir#", $template_name,) ) {
            $rel_template_path = str_replace($wc_templates_dir, '', $template_name);
            $plugin_path       = untrailingslashit(plugin_dir_path(__DIR__));

            $target_template = "{$plugin_path}/woocommerce/{$rel_template_path}";
            $template_name   = file_exists($target_template) ? $target_template : $template_name;
        }
        return $template_name;
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

    /**
     * Returns the current default order status key defined by plugin settings.
     * @param bool $has_prefix
     * @return string i.e. "wc-received"
     */
    private function get_default_status_key( bool $has_prefix = false ): ?string {
        $default_order_status_id = $this->settings->get_default();

        try {
            $order_status = new VTACustomOrderStatus($default_order_status_id);
            return $order_status->get_cos_key($has_prefix);
        } catch ( Exception $e ) {
            error_log("VTAWooCommerce::get_default_status() error - $e");
            return null;
        }
    }

    /**
     * Returns DateTime object in Pacific Time Zone.
     * @param string|null $date_str
     * @return DateTime
     */
    private function getDateTimePacific( ?string $date_str = null ): DateTime {
        $tz = new DateTimeZone('America/Los_Angeles');

        try {
            return new DateTime(empty($date_str) ? 'now' : $date_str, $tz);

        } catch ( Exception $e ) {
            error_log("Error at StoreHours::getDateTimePacific - $e");
            $datetime = new DateTime();
            $datetime->setTimezone($tz);
            return $datetime;
        }
    }

    /**
     * Checks if we are in WC Email Settings page. If so, reinitialize emails to include in custom options.
     * @return void
     */
    private function add_custom_emails_settings(): void {
        [ 'query_params' => $query_params ] = get_query_params();

        // re-initialize WC_Emails if in email settings page
        if ( is_admin() && in_array('wc-settings', $query_params) && in_array('email', $query_params) ) {
            $this->wc_emails->init();
        }
    }
}
