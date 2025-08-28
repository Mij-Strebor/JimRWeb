<?php

/**
 * Fluid Font Forge Uninstall Script
 * 
 * Fired when the plugin is uninstalled.
 * Removes all plugin data from the database.
 * 
 * @package FluidFontForge
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load constants for option keys
require_once plugin_dir_path(__FILE__) . 'fluid-font-forge.php';

// Delete plugin options
delete_option(FLUID_FONT_FORGE_OPTION_SETTINGS);
delete_option(FLUID_FONT_FORGE_OPTION_CLASS_SIZES);
delete_option(FLUID_FONT_FORGE_OPTION_VARIABLE_SIZES);
delete_option(FLUID_FONT_FORGE_OPTION_TAG_SIZES);
delete_option(FLUID_FONT_FORGE_OPTION_TAILWIND_SIZES);

// Clear any cached data
wp_cache_flush();

// Clean up any transients (if any were used)
delete_transient('fluid_font_forge_cache');
