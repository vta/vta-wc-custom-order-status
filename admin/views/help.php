<?php
/**
 * @title VTA Custom Order Status Help
 * @description basic help page for plugin usage
 */

?>
<div class="help-page-container">

    <h1 class="page-title">Help</h1>

    <p class="intro-text">
        The VTA Custom Order Status plugin intends to replace static content that was hard coded in the theme. Admins
        of this site can now customize and update order statuses purely through content management. Order status changes
        no longer rely on developer changes.
    </p>

    <hr>

    <div class="usage-container">
        <h2>Usage</h2>

        <p>
            Initially, when we activate the plugin, the plugin will automatically create custom order statuses
            from the core WooCommerce plugin ("Processing", "Completed", "Cancelled", etc.).
        </p>

        <hr>

        <div class="new-edit-container">
            <h3>New/Edit</h3>

            <p class="new-edit-overview">
                The editor resembles WordPress's class blog/page editor. Each field represents is mapped to a portion of
                our custom order status. After you've completed each required field, publish the order status to add it
                to the workflow.
            </p>

            <figure>
                <img src="<?php echo plugin_dir_url(__DIR__) . '../assets/images/edit-ready-status.png'; ?>"
                     alt="Add/Edit Screen">
                <figcaption>Example edit page for "Ready for Pick Up".</figcaption>
            </figure>

            <table class="mapping-table">
                <colgroup>
                    <col class="field-col">
                    <col class="description-col">
                    <col class="example-col">
                </colgroup>
                <thead>
                <tr>
                    <th>Field</th>
                    <th>Description</th>
                    <th>Example</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Order Status Name</td>
                    <td>The name to appear in all admin areas and customer pages. This is the top input of the new/edit
                        page.
                    </td>
                    <td>Ex. <code>"Ready for Pick Up"</code></td>
                </tr>
                <tr>
                    <td>Order Status Key</td>
                    <td><strong>IMPORTANT!</strong> This value connects all WooCommerce functionalities regarding
                        order status updates. For existing order status, do not regenerate this value. For new order
                        statuses, you may generate your own key OR leave this blank to allow the plugin to auto-generate
                        one for you.
                    </td>
                    <td>Ex. <code>"ready"</code></td>
                </tr>
                <tr>
                    <td>Order Status Color</td>
                    <td>The color of the order status. This color is shown on the order status chips in the admin
                        dashboard. You can change this value with the color picker as seen in the example image. <em>NOTE:
                            This field does not affect any WooCommerce functionalities.</em>
                    </td>
                    <td>Ex. <code>#87ec13</code></td>
                </tr>
                <tr>
                    <td>Reorderable</td>
                    <td>If checked, customers will be able to re-order at this order status.</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Reminder Email</td>
                    <td>Allows auto-generated, daily emails of the specified order status. This will create an extra
                        custom reminder email on top of the custom email for the current order status. See the "Custom
                        Emails" section for more information.
                    </td>
                    <td></td>
                </tr>
                </tbody>
            </table>
        </div>

        <hr>

        <div class="custom-emails">
            <h3>Custom Emails</h3>

            <p class="custom-emails-overview">
                All custom order statuses will have its own Email notification <strong>except for existing for core
                    WooCommerce email templates</strong> such as "Processing", "Completed", "Cancelled", etc. You can
                customize these email's content by navigating to <strong>WooCommerce > Settings > Emails</strong>. These
                custom email templates can be found towards the bottom of the list.
            </p>

            <figure>
                <img src="<?php echo plugin_dir_url(__DIR__) . '../assets/images/custom-emails.png'; ?>"
                     alt="Custom Order Status Email Templates">
                <figcaption>Custom Email templates for Custom Order Statuses.</figcaption>
            </figure>

            <p class="custom-emails-fields">
                In addition to each order status having its own template, the custom templates will have a "Main
                Content" field that is not included in core WooCommerce email templates. This is the content that
                goes <strong>above</strong> the order items table.
            </p>

            <figure>
                <img src="<?php echo plugin_dir_url(__DIR__) . '../assets/images/email_main_content.png'; ?>"
                     alt="Main Content field for Custom Emails">
                <figcaption>Main content field for custom email templates.</figcaption>
            </figure>

            <hr>

            <h4>Reminder Emails</h4>

            <p class="reminder-emails">
                If the "Has Reminder Email?" field was checked (from the New/Edit page), the order status will have an
                additional email template for daily reminders. The reminder email contain additional form fields
                specific to reminder emails.
            </p>

            <figure>
                <img src="<?php echo plugin_dir_url(__DIR__) . '../assets/images/reminder-email.png'; ?>"
                     alt="Reminder Email Example">
                <figcaption>Example: Ready for Pick Up reminder email.</figcaption>
            </figure>

            <p class="reminder-emails-fields">
                Reminder emails also contain their own custom fields. These fields allow you to set the time of the
                daily reminders (excluding weekends), add a "Complete" order button, and button content.
            </p>

            <figure>
                <img src="<?php echo plugin_dir_url(__DIR__) . '../assets/images/reminder-email-fields.png'; ?>"
                     alt="Reminder Email Fields">
                <figcaption>Reminder Email custom form fields.</figcaption>
            </figure>
        </div>

    </div>

</div>
