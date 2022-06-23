<?php
/**
 * @title VTA Custom Order Status Settings Page
 * @author James Pham
 * @description top level settings for custom Plugin
 */
?>
<h1>Test settings page</h1>

<?php
// Settings Form Fields
settings_fields('vta_order_status_settings');
do_settings_sections('vta_order_status_settings_fields');
submit_button('Save');
?>
