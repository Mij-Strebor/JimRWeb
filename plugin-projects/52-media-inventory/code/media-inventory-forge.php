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

// YOUR EXISTING CODE WILL GO BELOW THIS LINE
// (We'll move it from the snippet in the next step)

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

// Initialize admin functionality (add this at the very end of the file)
if (is_admin()) {
    new MIF_Admin();
}

// Helper function for formatting file sizes
function media_inventory_format_bytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = ($bytes > 0) ? floor(log($bytes) / log(1024)) : 0;
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Get file category based on MIME type
function media_inventory_get_category($mime_type)
{
    if ($mime_type === 'image/svg+xml') return 'SVG';
    if (strpos($mime_type, 'image/') === 0) return 'Images';
    if (strpos($mime_type, 'video/') === 0) return 'Videos';
    if (strpos($mime_type, 'audio/') === 0) return 'Audio';
    if (strpos($mime_type, 'application/pdf') === 0) return 'PDFs';
    if (strpos($mime_type, 'font/') === 0 || strpos($mime_type, 'application/font') === 0) return 'Fonts';
    if (in_array($mime_type, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])) return 'Documents';
    if (strpos($mime_type, 'text/') === 0) return 'Text Files';
    if (strpos($mime_type, 'application/') === 0) return 'Other Documents';
    return 'Other';
}

// Extract font family name from filename or title
function media_inventory_get_font_family($title, $filename)
{
    // Try to extract font family from title or filename
    $name = !empty($title) ? $title : pathinfo($filename, PATHINFO_FILENAME);

    // Remove common font weight/style suffixes
    $name = preg_replace('/[-_\s]?(regular|bold|italic|light|medium|heavy|black|thin|extralight|semibold|extrabold)[-_\s]?/i', '', $name);

    // Remove file extensions and numbers
    $name = preg_replace('/\.(woff2?|ttf|otf|eot)$/i', '', $name);
    $name = preg_replace('/[-_\s]*\d+[-_\s]*/', '', $name);

    // Clean up and capitalize
    $name = trim($name, '-_ ');
    $name = ucwords(str_replace(['-', '_'], ' ', $name));

    return !empty($name) ? $name : 'Unknown Font';
}

// Add admin menu
add_action('admin_menu', 'media_inventory_add_admin_menu');

// ========================================================================
// ADMIN INTERFACE
// ========================================================================

/**
 * Add admin menu page
 */
function media_inventory_add_admin_menu()
{
    // Check if J Forge parent menu exists, create if needed
    global $menu;
    $j_forge_exists = false;

    if (is_array($menu)) {
        foreach ($menu as $menu_item) {
            if (isset($menu_item[0]) && $menu_item[0] === 'J Forge') {
                $j_forge_exists = true;
                break;
            }
        }
    }

    // Create J Forge parent menu if it doesn't exist
    if (!$j_forge_exists) {
        add_menu_page(
            'J Forge',                    // Page title
            'J Forge',                    // Menu title
            'manage_options',             // Capability
            'j-forge',                    // Menu slug
            '__return_null',              // No callback (parent only)
            'dashicons-admin-tools',      // Icon
            12                            // Position
        );
    }

    // Add Media Inventory as submenu item
    add_submenu_page(
        'j-forge',                      // Parent slug
        'Media Inventory Forge',        // Page title
        'Media Inventory',              // Menu title
        'manage_options',               // Capability
        'media-inventory',              // Different slug
        'media_inventory_admin_page'    // Function
    );
}

// AJAX handler for scanning media
add_action('wp_ajax_media_inventory_scan', 'media_inventory_ajax_scan');
function media_inventory_ajax_scan()
{
    // Security checks
    check_ajax_referer('media_inventory_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
        return;
    }

    // Get and validate parameters
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 10;

    // Validate parameters
    if ($offset < 0) {
        wp_send_json_error('Invalid offset parameter');
        return;
    }

    if ($batch_size < 1 || $batch_size > 50) {
        wp_send_json_error('Invalid batch size parameter');
        return;
    }

    try {
        // Create scanner instance
        $scanner = new MIF_Scanner($batch_size);

        // Perform the scan
        $result = $scanner->scan_batch($offset);

        // Add debug information if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $result['debug'] = [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ];
        }

        wp_send_json_success($result);
    } catch (Exception $e) {
        // Log the error
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[Media Inventory Forge] Scan failed: ' . $e->getMessage());
        }

        wp_send_json_error('Scan failed: ' . $e->getMessage());
    }
}

// AJAX handler for exporting CSV
add_action('wp_ajax_media_inventory_export', 'media_inventory_ajax_export');
function media_inventory_ajax_export()
{
    check_ajax_referer('media_inventory_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }

    $inventory_data = json_decode(stripslashes($_POST['inventory_data']), true);

    if (empty($inventory_data)) {
        wp_die('No data to export');
    }

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="media-inventory-' . date('Y-m-d-H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, [
        'ID',
        'Title',
        'Category',
        'Extension',
        'MIME Type',
        'Dimensions',
        'Thumbnail URL',
        'Font Family',
        'File Count',
        'Total Size',
        'Total Size (Formatted)',
        'File Details'
    ]);

    foreach ($inventory_data as $item) {
        $file_details = [];
        foreach ($item['files'] as $file) {
            $details = $file['filename'] . ' (' . $file['type'] . ')';
            if (!empty($file['dimensions'])) {
                $details .= ' - ' . $file['dimensions'];
            }
            $details .= ' - ' . media_inventory_format_bytes($file['size']);
            $file_details[] = $details;
        }

        fputcsv($output, [
            $item['id'],
            $item['title'],
            $item['category'],
            $item['extension'],
            $item['mime_type'],
            $item['dimensions'] ?? '',
            $item['thumbnail_url'] ?? '',
            $item['font_family'] ?? '',
            $item['file_count'],
            $item['total_size'],
            media_inventory_format_bytes($item['total_size']),
            implode(' | ', $file_details)
        ]);
    }

    fclose($output);
    exit;
}

// Admin page
function media_inventory_admin_page()
{
    include MIF_PLUGIN_DIR . 'templates/admin/main-page.php';
}
