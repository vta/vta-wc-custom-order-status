<?php

/**
 * @title VTACustomEmail
 * Extends the core WC_Email class. This email is meant to compliment VTA's Custom Order Status.
 * Reminder Emails are also configured here to allow order status to have 2 emails per order status.
 */
class VTACustomEmail extends WC_Email {

    private WC_Order $order;

    /**
     * @param VTACustomOrderStatus $order_status custom order status attributes
     */
    public function __construct( VTACustomOrderStatus $order_status ) {
        // Add email ID, title, description, heading, subject
        $this->id             = "custom_email_{$order_status->get_cos_key()}";
        $this->customer_email = true;
        $this->title          = "{$order_status->get_cos_name()} Email";
        $this->description    = "This email is received when an order status is changed to \"{$order_status->get_cos_name()}\".";

        $this->heading = "{$order_status->get_cos_name()}";
        $this->subject = "{$order_status->get_cos_name()} (Order #{order_number}) - {order_date}";

        // email template path
        $this->template_html  = 'templates/custom-email-html.php';
        $this->template_plain = 'templates/custom-email-plain.php';

        // Triggers for this email
        add_action($order_status->get_email_action(), [ $this, 'trigger' ], 10, 1);

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

            $this->placeholders['{order_date}']              = wc_format_datetime( $order->get_date_created() );
            $this->placeholders['{order_number}']            = $order->get_order_number();
            $this->placeholders['{order_billing_full_name}'] = $order->get_formatted_billing_full_name();

            // send the email
            $this->send($recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), []);
        }
    }

    /**
     * Returns HTML Email content
     * @return false|string
     */
    public function get_content_html() {
        ob_start();
        wc_get_template($this->template_html, [
            'email_heading'      => $this->get_heading(),
            'main_content'       => $this->get_main_content(),
            'additional_content' => $this->get_additional_content(),
            'order'              => $this->order
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

    // form fields that are displayed in WooCommerce->Settings->Emails
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
            'main_content' => [
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
    }

    /**
     * Custom Dynamic field for main content above the Order Detaisl
     * @return string
     */
    public function get_main_content(): string {
        return $this->get_option('main_content', '');
    }
}
