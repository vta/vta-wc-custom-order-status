<?php

/**
 * Extends the core WC_Email class. This email is meant to compliment VTA's Custom Order Status.
 * Reminder Emails are also configured here to allow order status to have 2 emails per order status.
 *
 * NOTE: These should only be used for order status notifications. This email class will not work with
 * forgotten passwords, new user, etc.
 */
class VTACustomEmail extends WC_Email {

    private WC_Order $order;
    protected bool   $is_reminder;

    /**
     * @param VTACustomOrderStatus $order_status custom order status attributes
     * @param bool $is_reminder denotes if class is reminder email
     */
    public function __construct( VTACustomOrderStatus $order_status, bool $is_reminder = false ) {
        $this->is_reminder = $is_reminder;

        // Add email ID, title, description, heading, subject
        $this->id             = "custom_email_{$order_status->get_cos_key()}" . ( $is_reminder ? '_reminder' : '' );
        $this->customer_email = true;
        $this->title          = "{$order_status->get_cos_name()} Email" . ( $is_reminder ? ' (Reminder)' : '' );
        $this->description    = $is_reminder
            ? "This is a reminder email for Order Status \"{$order_status->get_cos_name()}\""
            : "This email is received when an order status is changed to \"{$order_status->get_cos_name()}\".";

        $this->heading = "{$order_status->get_cos_name()}" . ( $is_reminder ? ' (Reminder)' : '' );
        $this->subject = "{$order_status->get_cos_name()} (Order #{order_number}) - {order_date}" . ( $is_reminder ? ' (Reminder)' : '' );

        // email template path
        $this->template_html  = $is_reminder ? 'templates/custom-reminder-email-html.php' : 'templates/custom-email-html.php';
        $this->template_plain = $is_reminder ? 'templates/custom-reminder-email-plain.php' : 'templates/custom-email-plain.php';

        // Triggers for this email
        if ( !$is_reminder )
            add_action($order_status->get_email_action(), [ $this, 'trigger' ], 10, 1);
        else
            add_action("{$order_status->get_email_action()}_reminder", [ $this, 'trigger' ], 10, 1);

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->template_base = plugin_dir_path(__DIR__ . 'templates');
        // default recipient to null. This field will be set when trigger pulls order information
        // and sets it to customer email
        $this->recipient = null;
        // placeholders for form fields
        $this->placeholders = [
            '{order_date}'              => '',
            '{order_number}'            => '',
            '{order_billing_full_name}' => '',
            '{site_title}'              => '',
            '{site_url}'                => '',
        ];

    }

    /**
     * Sends email for order status...
     * @param WC_Order | int $order
     * @return void
     */
    public function trigger( $order ): void {
        // convert to WC_Order if id is given
        if ( is_int($order) ) {
            $order = wc_get_order($order);
        }

        // validation
        if ( $order instanceof WC_Order ) {
            $this->order = $order;
            $recipient   = $order->get_billing_email();

            $this->placeholders['{order_date}']              = wc_format_datetime($order->get_date_created());
            $this->placeholders['{order_number}']            = $order->get_order_number();
            $this->placeholders['{order_billing_full_name}'] = $order->get_formatted_billing_full_name();
            $this->placeholders['{site_title}']              = $this->get_blogname();
            $this->placeholders['{site_url}']                = site_url();

            // send the email only if use enabled this notification
            if ( $this->is_enabled() && !empty($recipient) ) {
                $this->send($recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), []);
            }
        }
    }

    /**
     * Returns HTML Email content
     * @return false|string
     */
    public function get_content_html() {
        ob_start();
        wc_get_template($this->template_html, [
            'email_heading'         => $this->get_heading(),
            'main_content'          => $this->get_main_content(),
            'additional_content'    => $this->get_additional_content(),
            'order'                 => $this->order,
            'site_url'              => site_url(),
            'has_complete_action'   => $this->has_complete_action(),
            'complete_action_text'  => $this->get_complete_action_text(),
            'complete_action_color' => $this->get_complete_action_color(),
        ], 'custom-templates', $this->template_base);
        return ob_get_clean();
    }

    /**
     * Returns plain HTML content
     * @return false|string
     */
    public function get_content_plain() {
        ob_start();
        wc_get_template($this->template_plain, [
            'email_heading'      => $this->get_heading(),
            'main_content'       => $this->get_main_content(),
            'additional_content' => $this->get_additional_content(),
            'order'              => $this->order
        ], 'custom-templates', $this->template_base);
        return ob_get_clean();
    }

    /**
     * Form fields that are displayed in WooCommerce->Settings->Emails
     * @return void
     */
    public function init_form_fields() {
        $placeholder_text  = sprintf(__('Available placeholders: %s', 'woocommerce'), '<code>' . esc_html(implode('</code>, <code>', array_keys($this->placeholders))) . '</code>');
        $this->form_fields = [
            'enabled'            => [
                'title'   => 'Enable/Disable',
                'type'    => 'checkbox',
                'label'   => 'Enable this email notification',
                'default' => 'yes'
            ],
            'subject'            => [
                'title'       => 'Subject',
                'type'        => 'text',
                'description' => sprintf(__('This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'custom-email'), $this->subject),
                'placeholder' => '',
                'default'     => ''
            ],
            'heading'            => [
                'title'       => 'Email Heading',
                'type'        => 'text',
                'description' => sprintf(__('This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'custom-email'), $this->heading),
                'placeholder' => '',
                'default'     => ''
            ],
            'main_content'       => [
                'title'       => 'Main content',
                'description' => 'Text to appear above order details.' . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => 'N/A', 'custom-email',
                'type'        => 'textarea',
                'default'     => $this->get_main_content(),
                'desc_tip'    => true,
            ],
            'additional_content' => [
                'title'       => 'Additional content',
                'description' => 'Text to appear below the main email content.' . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => 'N/A', 'custom-email',
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ],
            'email_type'         => [
                'title'       => 'Email type', 'custom-email',
                'type'        => 'select',
                'description' => 'Choose which format of email to send.', 'custom-email',
                'default'     => 'html',
                'class'       => 'email_type',
                'options'     => [
                    'plain' => 'Plain text',
                    'html'  => 'HTML', 'custom-email'
                ]
            ]
        ];

        // Reminder Options
        if ( $this->is_reminder ) {
            $this->form_fields['reminder_time']         = [
                'title'       => 'Scheduled Reminder Time',
                'description' => 'Scheduled time to send reminder emails on weekdays.',
                'type'        => 'time',
                'default'     => '08:00',
                'desc_tip'    => true,
            ];
            $this->form_fields['has_complete_action']   = [
                'title'       => 'Has Complete Action',
                'description' => 'Provides button for the user to complete their order.',
                'type'        => 'checkbox',
                'default'     => false,
                'desc_tip'    => true,
            ];
            $this->form_fields['complete_action_text']  = [
                'title'       => 'Complete Action Button Text',
                'description' => 'Button Text for complete action (if applicable).',
                'type'        => 'text',
                'default'     => 'Complete',
                'desc_tip'    => true,
            ];
            $this->form_fields['complete_action_color'] = [
                'title'       => 'Complete Action Button Color',
                'description' => 'Button Color for complete action (if applicable).',
                'type'        => 'color',
                'default'     => '#e53935',
                'desc_tip'    => true,
            ];
        }
    }

    /**
     * Custom Dynamic field for main content above the Order Details.
     * Add wrap around format string to update merge tags.
     * @return string
     */
    public function get_main_content(): string {
        return $this->format_string($this->get_option('main_content', ''));
    }

    /**
     * Scheduled time for reminder emails to send out
     * @return string
     */
    public function get_reminder_time(): string {
        return $this->get_option('reminder_time', '08:00');
    }

    /**
     * Action button condition for reminder emails
     * @return bool
     */
    public function has_complete_action(): bool {
        return $this->get_option('has_complete_action', false);
    }

    /**
     * Text for action button (if applicable).
     * @return string
     */
    public function get_complete_action_text(): string {
        return $this->get_option('complete_action_text', 'Complete');
    }

    public function get_complete_action_color(): string {
        return $this->get_option('complete_action_color', '#e53935');
    }
}
