<?php

/**
 * Plugin Name: Media Inventory Forge
 * Plugin URI: https://jimrweb.com/plugins/media-inventory-forge
 * Description: Professional media library scanner and analyzer for WordPress developers
 * Version: 2.0.0
 * Author: Jim R. (JimRWeb)
 * Author URI: https://jimrweb.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: media-inventory-forge
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: true
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MIF_VERSION', '2.0.0');
define('MIF_PLUGIN_FILE', __FILE__);
define('MIF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MIF_PLUGIN_URL', plugin_dir_url(__FILE__));


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('MIF_VERSION')) {
    define('MIF_VERSION', '2.0.0');
}
if (!defined('MIF_PLUGIN_DIR')) {
    define('MIF_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Load utility classes
require_once MIF_PLUGIN_DIR . 'includes/utilities/class-file-utils.php';

// Load core classes
require_once MIF_PLUGIN_DIR . 'includes/core/class-file-processor.php';
require_once MIF_PLUGIN_DIR . 'includes/core/class-scanner.php';

// Load admin classes
require_once MIF_PLUGIN_DIR . 'includes/admin/class-admin.php';
require_once MIF_PLUGIN_DIR . 'includes/admin/class-admin-controller.php';

// Initialize admin functionality
if (is_admin()) {
    new MIF_Admin();
    new MIF_Admin_Controller();
}
