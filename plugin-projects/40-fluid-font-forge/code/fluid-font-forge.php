<?php

/**
 * Plugin Name: Fluid Font Forge
 * Plugin URI: https://jimrweb.com
 * Description: Advanced fluid typography calculator with CSS clamp() generation for responsive font scaling.
 * Version: 4.0.0
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

// Define plugin constants
define('FLUID_FONT_FORGE_VERSION', '3.7.0');
define('FLUID_FONT_FORGE_PATH', plugin_dir_path(__FILE__));
define('FLUID_FONT_FORGE_URL', plugin_dir_url(__FILE__));

// ========================================================================
// PLUGIN INITIALIZATION
// ========================================================================

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
