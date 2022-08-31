<?php
/**
 * @title VTA Custom Order Status Settings Page
 * @author James Pham
 * @description top level settings for custom Plugin
 */
?>
<form action="options.php" method="post">
    <?php
    // Settings Form Fields
    settings_fields('vta_order_status_settings');
    do_settings_sections('vta_order_status_settings_fields');
    submit_button('Save');
    ?>
</form>
<hr>
<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" id="reset-settings-form">
    <input type="hidden" name="action" value="default_settings">

    <h2>Reset to default settings</h2>
    <input type="hidden" id="confirm-value">
    <?php submit_button('Reset Settings', 'button-hero', 'submit', true, [
        'id' => 'reset-settings-btn'
    ]); ?>
    <p class="description">
        <strong class="reset-warning">WARNING:</strong> Use this button to restore ALL custom order statuses and plugin
        settings to the default settings.
    </p>

    <div id="reset-confirm-dialog" title="Reset Order Statuses">
        <p>
            <span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span> Default Order
            Statuses
            will be reset to its original settings & all custom Order Statuses will be deleted. Are you sure?
        </p>
    </div>
</form>
<hr>
<h2>Re-orderable Statuses</h2>
<table class="meta-table">
    <?php
    /** @var VTACustomOrderStatus $order_status */
    foreach ( $reorderable_statuses ?? [] as $order_status ): ?>
        <tr>
            <td><?php echo $order_status->get_cos_name(); ?></td>
        </tr>
    <?php endforeach; ?>
</table>
<hr>
<h2>Reminder Statuses</h2>
<table class="meta-table">
    <?php
    /** @var VTACustomOrderStatus $order_status */
    foreach ( $reminder_statuses ?? [] as $order_status ): ?>
        <tr>
            <td><?php echo $order_status->get_cos_name(); ?></td>
        </tr>
    <?php endforeach; ?>
</table>
