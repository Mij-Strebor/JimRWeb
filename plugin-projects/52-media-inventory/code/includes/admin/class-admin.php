<?php

/**
 * Admin interface functionality for Media Inventory Forge
 * 
 * @package MediaInventoryForge
 * @subpackage Admin
 * @since 2.0.0
 * 
 * Handles enqueuing admin scripts/styles and localizing script data.
 */

// Prevent direct access
defined('ABSPATH') || exit;

/**
 * Class MIF_Admin
 * 
 * Handles admin-specific functionality such as enqueuing scripts and styles,
 * localizing script data, and managing admin page interactions.
 */
class MIF_Admin
{

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue admin assets (CSS and JavaScript)
     */
    public function enqueue_admin_assets($hook)
    {
        // Only load assets on our plugin pages
        if (strpos($hook, 'media-inventory') === false && strpos($hook, 'j-forge') === false) {
            return;
        }

        // Enqueue admin CSS
        wp_enqueue_style(
            'mif-admin-css',
            MIF_PLUGIN_URL . 'assets/css/admin.css',
            [],
            MIF_VERSION
        );

        // Enqueue admin JavaScript
        wp_enqueue_script(
            'mif-admin-js',
            MIF_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'], // Dependencies
            MIF_VERSION,
            true // Load in footer
        );

        // Localize script data (pass PHP data to JavaScript)
        wp_localize_script('mif-admin-js', 'mifData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('media_inventory_nonce'),
            'strings' => [
                'scanComplete' => __('Scan completed successfully!', 'media-inventory-forge'),
                'scanError' => __('An error occurred during scanning.', 'media-inventory-forge'),
                'confirmClear' => __('Are you sure you want to clear all results?', 'media-inventory-forge'),
            ]
        ]);
    }
}
