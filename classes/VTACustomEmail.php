<?php

class VTACustomEmail extends WC_Email {

    private WC_Order $order;

    /**
     * @param VTACustomOrderStatus $order_status custom order status attributes
     */
    function __construct( VTACustomOrderStatus $order_status ) {
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
        add_action($order_status->get_email_action(), [ $this, 'trigger' ], 11, 1);

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

//    public function queue_notification( $order_id ) {
//
//        $order = new WC_order($order_id);
//        $items = $order->get_items();
//        // foreach item in the order
//        foreach ( $items as $item_key => $item_value ) {
//            // add an event for the item email, pass the item ID so other details can be collected as needed
//            wp_schedule_single_event(time(), 'custom_example_email_trigger', [ 'item_id' => $item_key ]);
//        }
//    }

    /**
     * Sends email for order status...
     * @param WC_Order | int $order
     * @return void
     */
    function trigger( $order ): void {

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
    function get_content_html() {
        ob_start();
        wc_get_template($this->template_html, [
            'email_heading'      => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
            'order'              => $this->order
        ], 'custom-templates', $this->template_base);
        return ob_get_clean();
    }

    /**
     * Returns plain HTML content
     * @return false|string
     */
    function get_content_plain() {
        ob_start();
        wc_get_template($this->template_plain, [
            'email_heading'      => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
            'order'              => $this->order
        ], 'custom-templates', $this->template_base);
        return ob_get_clean();
    }

    // return the subject
//    function get_subject() {
//        return apply_filters('woocommerce_email_subject_' . $this->id, $this->format_string($this->subject), $this->object);
//    }
//
//    // return the email heading
//    public function get_heading() {
//        return apply_filters('woocommerce_email_heading_' . $this->id, $this->format_string($this->heading), $this->object);
//    }

    // form fields that are displayed in WooCommerce->Settings->Emails
    function init_form_fields() {
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
}
