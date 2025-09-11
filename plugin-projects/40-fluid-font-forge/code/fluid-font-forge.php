<?php

/**
 * Plugin Name: Fluid Font Forge
 * Description: Advanced fluid typography calculator with CSS clamp() generation for responsive font scaling.
 * Version: 4.0.3
 * Author: Jim R (JimRWeb)
 * Author URI: https://jimrweb.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fluid-font-forge
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4/**
 * 
 * Fluid Font Forge Bootstrap File
 * 
 * This is the main plugin loader that defines constants, loads the main class, and initializes the plugin.
 * All functionality is contained in the FluidFontForge class.
 * 
 * @package FluidFontForge
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// ========================================================================
// PLUGIN CONSTANTS
// ========================================================================

define('FLUID_FONT_FORGE_VERSION', '4.0.3');
define('FLUID_FONT_FORGE_PATH', plugin_dir_path(__FILE__));
define('FLUID_FONT_FORGE_URL', plugin_dir_url(__FILE__));

// Version management constants
define('FLUID_FONT_FORGE_DB_VERSION', '1.0');
define('FLUID_FONT_FORGE_MIN_WP_VERSION', '5.0');
define('FLUID_FONT_FORGE_MIN_PHP_VERSION', '7.4');

// Option key constants
define('FLUID_FONT_FORGE_OPTION_SETTINGS', 'fluid_font_forge_settings');
define('FLUID_FONT_FORGE_OPTION_CLASS_SIZES', 'fluid_font_forge_class_sizes');
define('FLUID_FONT_FORGE_OPTION_VARIABLE_SIZES', 'fluid_font_forge_variable_sizes');
define('FLUID_FONT_FORGE_OPTION_TAG_SIZES', 'fluid_font_forge_tag_sizes');
define('FLUID_FONT_FORGE_OPTION_TAILWIND_SIZES', 'fluid_font_forge_tailwind_sizes');

// ========================================================================
// PLUGIN ACTIVATION AND DEACTIVATION
// ========================================================================

/**
 * Plugin activation function
 * Sets default options if they don't exist
 */
function fluid_font_forge_activate()
{
    // Set default settings if they don't exist
    if (!get_option(FLUID_FONT_FORGE_OPTION_SETTINGS)) {
        $default_settings = [
            'minRootSize' => 16,
            'maxRootSize' => 20,
            'minViewport' => 375,
            'maxViewport' => 1620,
            'unitType' => 'px',
            'activeTab' => 'class',
            'minScale' => 1.125,
            'maxScale' => 1.333,
            'autosaveEnabled' => true
        ];
        add_option(FLUID_FONT_FORGE_OPTION_SETTINGS, $default_settings);
    }

    // Set version number for tracking
    add_option('fluid_font_forge_version', FLUID_FONT_FORGE_VERSION);

    // Clear any cached data
    wp_cache_flush();
}

/**
 * Plugin deactivation function
 * Cleans up temporary data but preserves user settings
 */
function fluid_font_forge_deactivate()
{
    // Clear any cached data
    wp_cache_flush();

    // Remove plugin loaded flag
    if (defined('FLUID_FONT_FORGE_LOADED')) {
        // Note: Can't undefine constants, but flag is cleared on next load
    }
}

/**
 * Check if plugin needs to be upgraded
 */
function fluid_font_forge_check_version()
{
    $current_version = get_option('fluid_font_forge_version', '0');

    if (version_compare($current_version, FLUID_FONT_FORGE_VERSION, '<')) {
        // Run upgrade routine
        fluid_font_forge_upgrade($current_version);

        // Update version number
        update_option('fluid_font_forge_version', FLUID_FONT_FORGE_VERSION);
    }
}

/**
 * Handle plugin upgrades between versions
 */
function fluid_font_forge_upgrade($old_version)
{
    // Future upgrade routines will go here
    // For now, just clear cache
    wp_cache_flush();

    // Log the upgrade
    error_log("Fluid Font Forge upgraded from {$old_version} to " . FLUID_FONT_FORGE_VERSION);
}

/**
 * Add settings link to plugins page
 */
function fluid_font_forge_add_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=fluid-font-forge">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// ========================================================================
// PLUGIN INITIALIZATION
// ========================================================================

// Register activation hook
register_activation_hook(__FILE__, 'fluid_font_forge_activate');

// Register deactivation hook
register_deactivation_hook(__FILE__, 'fluid_font_forge_deactivate');

// Add settings link to plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'fluid_font_forge_add_settings_link');

// Check for plugin updates
add_action('plugins_loaded', 'fluid_font_forge_check_version');

// Load the data factory
require_once FLUID_FONT_FORGE_PATH . 'includes/class-default-data-factory.php';

// Debug: Check if class exists
if (class_exists('FluidFontForgeDefaultData')) {
    error_log('✅ FluidFontForgeDefaultData class loaded successfully');
} else {
    error_log('❌ FluidFontForgeDefaultData class NOT found');
}

// Load the main class
require_once FLUID_FONT_FORGE_PATH . 'includes/class-fluid-font-forge.php';

// Initialize the plugin
if (is_admin()) {
    global $fluidFontForge;

    // Instantiate the main plugin class
    $fluidFontForge = new FluidFontForge();

    // Verify plugin loaded successfully
    if (!$fluidFontForge) {
        error_log('Fluid Font Forge: Failed to initialize plugin class');
        return;
    }
}

// Plugin loaded successfully
if (!defined('FLUID_FONT_FORGE_LOADED')) {
    define('FLUID_FONT_FORGE_LOADED', true);
}
