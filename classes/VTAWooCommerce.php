<?php

/**
 * @class VTA WooCommerce Integration
 * Integrates custom order statuses to core WooCommerce hooks to reflect admin settings & custom posts in this plugin.
 */
class VTAWooCommerce {

    // PLUGIN VARS
    private string $plugin_name;
    private string $plugin_version;

    /**
     * @param string $plugin_name
     * @param string $plugin_version
     */
    public function __construct( string $plugin_name, string $plugin_version ) {
        $this->plugin_name    = $plugin_name;
        $this->plugin_version = $plugin_version;
    }

}
