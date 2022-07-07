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
<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post" id="reset-settings-form">
    <input type="hidden" name="action" value="default_settings">

    <h2>Reset to default settings</h2>
    <?php submit_button('Reset Settings', 'button-hero', 'submit', true, [
        'id' => 'reset-settings-btn'
    ]); ?>
    <p class="description">
        Use this button to restore ALL custom order statuses and plugin settings to the default settings.
    </p>
</form>
