<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://jamespham.io
 * @since      1.0.0
 *
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Vta_Wc_Custom_Order_Status
 * @subpackage Vta_Wc_Custom_Order_Status/includes
 * @author     James Pham <jamespham93@yahoo.com>
 * NOTE: Loader methods is mostly deprecated... Hooks are called directly in their own sub-classes...
 */
class Vta_Wc_Custom_Order_Status {

    /** @var Vta_Wc_Custom_Order_Status_Loader $loader Maintains and registers all hooks for the plugin. */
    protected Vta_Wc_Custom_Order_Status_Loader $loader;

    /** @var string $plugin_name The string used to uniquely identify this plugin. */
    protected string $plugin_name;

    /** @var  string $version The current version of the plugin. */
    protected string $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     */
    public function __construct() {
        if (defined('VTA_WC_CUSTOM_ORDER_STATUS_VERSION')) {
            $this->version = VTA_WC_CUSTOM_ORDER_STATUS_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = VTA_WC_COS_PLUGIN_NAME;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Vta_Wc_Custom_Order_Status_Loader. Orchestrates the hooks of the plugin.
     * - Vta_Wc_Custom_Order_Status_i18n. Defines internationalization functionality.
     * - Vta_Wc_Custom_Order_Status_Admin. Defines all hooks for the admin area.
     * - Vta_Wc_Custom_Order_Status_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @returns void
     */
    private function load_dependencies(): void {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vta-wc-custom-order-status-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vta-wc-custom-order-status-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-vta-wc-custom-order-status-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-vta-wc-custom-order-status-public.php';

        $this->loader = new Vta_Wc_Custom_Order_Status_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     * Uses the Vta_Wc_Custom_Order_Status_i18n class in order to set the domain and to register the hook
     * with WordPress.
     * @returns void
     */
    private function set_locale(): void {
        $plugin_i18n = new Vta_Wc_Custom_Order_Status_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     * @returns void
     */
    private function define_admin_hooks(): void {
        $plugin_admin = new Vta_Wc_Custom_Order_Status_Admin($this->get_plugin_name(), $this->get_version());
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     * @returns void
     */
    private function define_public_hooks(): void {
        $plugin_public = new Vta_Wc_Custom_Order_Status_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     * @returns void
     */
    public function run(): void {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name(): string {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     * @return    Vta_Wc_Custom_Order_Status_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader(): Vta_Wc_Custom_Order_Status_Loader {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     * @return    string    The version number of the plugin.
     */
    public function get_version(): string {
        return $this->version;
    }

}
