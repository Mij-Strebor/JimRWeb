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
?>
    <div class="wrap" style="background: var(--clr-page-bg); padding: 20px; min-height: 100vh;">
        <div class="fcc-header-section" style="width: 1280px; margin: 0 auto;">
            <h1 class="text-2xl font-bold mb-4">Media Inventory Forge (2.0)</h1><br>

            <!-- About Section -->
            <div class="fcc-info-toggle-section">
                <button class="fcc-info-toggle expanded" data-toggle-target="about-content">
                    <span style="color: #FAF9F6 !important;">ℹ️ About Media Inventory Forge</span>
                    <span class="fcc-toggle-icon" style="color: #FAF9F6 !important;">▼</span>
                </button>
                <div class="fcc-info-content expanded" id="about-content">
                    <div style="color: var(--clr-txt); font-size: 14px; line-height: 1.6;">
                        <p style="margin: 0 0 16px 0; color: var(--clr-txt);">
                            Media Inventory Forge is a comprehensive scanning tool that analyzes all media files in your WordPress uploads directory. It provides detailed information about file sizes, categories, dimensions, and storage usage. Perfect for site optimization, cleanup projects, and understanding your media library's footprint. Scan progressively through thousands of files with detailed categorization and export capabilities.
                        </p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 20px;">
                            <div>
                                <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">🔍 Comprehensive Analysis</h4>
                                <p style="margin: 0; font-size: 13px; line-height: 1.5;">Scans all media types including images, videos, audio, fonts, documents, and SVGs with detailed file information.</p>
                            </div>
                            <div>
                                <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">📊 Storage Insights</h4>
                                <p style="margin: 0; font-size: 13px; line-height: 1.5;">Get precise storage usage by category, file counts, and detailed breakdowns of image sizes and variations.</p>
                            </div>
                            <div>
                                <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">⚡ Progressive Scanning</h4>
                                <p style="margin: 0; font-size: 13px; line-height: 1.5;">Handles large media libraries efficiently with batch processing and real-time progress tracking.</p>
                            </div>
                            <div>
                                <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">📈 Export & Reporting</h4>
                                <p style="margin: 0; font-size: 13px; line-height: 1.5;">Generate detailed CSV reports for analysis, auditing, or planning cleanup and optimization strategies.</p>
                            </div>
                        </div>
                        <div style="background: rgba(60, 32, 23, 0.1); padding: 12px 16px; border-radius: 6px; border-left: 4px solid var(--clr-accent); margin-top: 20px;">
                            <p style="margin: 0; font-size: 13px; opacity: 0.95; line-height: 1.5; color: var(--clr-txt);">
                                Media Inventory Forge by Jim R. (<a href="https://jimrweb.com" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">JimRWeb</a>), developed with tremendous help from Claude AI (<a href="https://anthropic.com" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">Anthropic</a>). Part of the professional WordPress development toolkit.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Container -->
            <div class="media-inventory-container" style="margin: 0 auto; background: var(--clr-card-bg); border-radius: var(--jimr-border-radius-lg); box-shadow: var(--clr-shadow-xl); overflow: hidden; border: 2px solid var(--clr-primary);">
                <div style="padding: 20px;">

                    <!-- Controls Section -->
                    <div class="fcc-panel" style="margin-bottom: 20px;">
                        <h2 style="margin-bottom: 16px;">Scan Controls</h2>

                        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 16px;">
                            <button id="start-scan" class="fcc-btn">🔍 start scan</button>
                            <button id="stop-scan" class="fcc-btn fcc-btn-danger" style="display: none;">⏹️ stop scan</button>
                            <button id="export-csv" class="fcc-btn" style="display: none;">📊 export csv</button>
                            <button id="clear-results" class="fcc-btn fcc-btn-ghost" style="display: none;">🗑️ clear results</button>
                        </div>

                        <div id="scan-progress" style="display: none;">
                            <div style="margin-bottom: 12px;">
                                <strong style="color: var(--clr-primary);">Scanning Progress:</strong>
                            </div>
                            <div style="background: var(--clr-light); height: 24px; border-radius: 12px; overflow: hidden; border: 2px solid var(--clr-secondary); margin-bottom: 8px;">
                                <div id="progress-bar" style="background: linear-gradient(90deg, var(--clr-accent), var(--clr-btn-hover)); height: 100%; width: 0%; transition: width 0.3s ease; position: relative; overflow: hidden;">
                                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); animation: shimmer 2s infinite;"></div>
                                </div>
                            </div>
                            <p id="progress-text" style="margin: 0; color: var(--clr-txt); font-weight: 500;">0 / 0 processed</p>
                        </div>

                        <div id="summary-stats" style="margin-top: 20px; display: none;">
                            <h3 style="color: var(--clr-primary); margin: 0 0 12px 0;">Summary</h3>
                            <div id="summary-content" style="background: var(--clr-light); padding: 16px; border-radius: var(--jimr-border-radius); border: 1px solid var(--clr-secondary);"></div>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div class="fcc-panel">
                        <h2 style="margin-bottom: 16px;">Inventory Results</h2>
                        <div id="results-container" style="min-height: 120px;">
                            <div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">
                                Click "start scan" to begin inventory scanning.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        :root {
            /* Core JimRWeb Brand Colors */
            --clr-primary: #3C2017;
            --clr-secondary: #5C3324;
            --clr-txt: #86400E;
            --clr-accent: #FFD700;
            --clr-page-bg: #E8D8C3;
            --clr-card-bg: #F0E6DA;
            --clr-btn-hover: #E5B929;
            --clr-btn-txt: #9C0202;
            --clr-btn-bdr: #DE0B0B;
            --clr-light: #F5F1EC;
            --clr-shadow: rgba(60, 32, 23, 0.15);
            --clr-overlay: rgba(232, 216, 195, 0.95);
            --clr-secondary-hover: #dcc7a8;

            /* Extended Utility Colors */
            --clr-success: #10b981;
            --clr-success-dark: #059669;
            --jimr-danger: #ef4444;
            --jimr-danger-dark: #dc2626;
            --jimr-info: #3b82f6;
            --jimr-info-dark: #1d4ed8;
            --jimr-warning: #f59e0b;
            --jimr-warning-dark: #d97706;

            /* Gray Scale */
            --jimr-gray-50: #f8fafc;
            --jimr-gray-100: #f1f5f9;
            --jimr-gray-200: #e2e8f0;
            --jimr-gray-300: #cbd5e1;
            --jimr-gray-400: #94a3b8;
            --jimr-gray-500: #64748b;
            --jimr-gray-600: #475569;
            --jimr-gray-700: #334155;
            --jimr-gray-800: #1e293b;
            --jimr-gray-900: #0f172a;

            /* Design System */
            --jimr-border-radius: 3px;
            --jimr-border-radius-lg: 5px;
            --jimr-transition: all 0.2s ease;
            --jimr-transition-slow: all 0.3s ease;
            --clr-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --clr-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --clr-shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.2);
            --clr-shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);

            --jimr-font-xs: 10px;
            --jimr-font-sm: 12px;
            --jimr-font-base: 14px;
            --jimr-font-lg: 16px;
            --jimr-font-xl: 18px;
            --jimr-font-2xl: 20px;

            --jimr-space-1: 4px;
            --jimr-space-2: 8px;
            --jimr-space-3: 12px;
            --jimr-space-4: 16px;
            --jimr-space-5: 20px;
            --jimr-space-6: 24px;
        }

        /* Global Styling */
        body {
            background: var(--clr-page-bg) !important;
            color: var(--clr-txt) !important;
        }

        .wp-admin {
            background: var(--clr-page-bg) !important;
        }

        #wpbody-content {
            background: var(--clr-page-bg) !important;
        }

        .wrap {
            background: var(--clr-page-bg) !important;
        }

        /* Button System */
        .fcc-btn {
            background: var(--clr-accent);
            color: var(--clr-btn-txt);
            border: 2px solid var(--clr-btn-bdr);
            padding: var(--jimr-space-2) var(--jimr-space-4);
            border-radius: var(--jimr-border-radius);
            font-size: var(--jimr-font-sm);
            font-weight: 600;
            font-style: italic;
            text-transform: lowercase;
            letter-spacing: 0.5px;
            transition: var(--jimr-transition-slow);
            cursor: pointer;
            box-shadow: var(--clr-shadow);
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: var(--jimr-space-2);
            text-decoration: none;
        }

        .fcc-btn:hover {
            background: var(--clr-btn-hover);
            transform: translateY(-2px);
            box-shadow: var(--clr-shadow-lg);
        }

        .fcc-btn-primary {
            background: var(--jimr-info);
            color: white;
            border-color: var(--jimr-info);
            font-style: normal;
            text-transform: none;
            letter-spacing: normal;
        }

        .fcc-btn-danger {
            background: var(--jimr-danger);
            color: white;
            border-color: var(--jimr-danger-dark);
            font-style: normal;
            text-transform: none;
            letter-spacing: normal;
        }

        .fcc-btn-ghost {
            background: var(--jimr-gray-500);
            color: white;
            border-color: var(--jimr-gray-600);
            font-style: normal;
            text-transform: none;
            letter-spacing: normal;
        }

        /* Panels */
        .fcc-panel {
            background: var(--clr-light);
            padding: var(--jimr-space-5);
            border-radius: var(--jimr-border-radius-lg);
            border: 2px solid var(--clr-secondary);
            box-shadow: var(--clr-shadow-lg);
            transition: var(--jimr-transition);
        }

        /* Typography */
        h1 {
            color: var(--clr-primary) !important;
            font-family: 'Georgia', serif;
            text-shadow: 1px 1px 2px var(--clr-shadow);
        }

        h2 {
            color: var(--clr-primary) !important;
            font-family: 'Georgia', serif;
            border-bottom-color: var(--clr-secondary) !important;
            text-shadow: 1px 1px 2px var(--clr-shadow);
            font-size: var(--jimr-font-2xl);
            margin: 0 0 var(--jimr-space-5) 0;
            border-bottom: 2px solid var(--clr-secondary);
            padding-bottom: var(--jimr-space-2);
        }

        /* Info Toggles */
        .fcc-info-toggle-section {
            margin-bottom: var(--jimr-space-6);
            border: 2px solid var(--clr-secondary);
            border-radius: var(--jimr-border-radius-lg);
            overflow: hidden;
            background: linear-gradient(135deg, var(--clr-light), var(--clr-card-bg));
            box-shadow: var(--clr-shadow);
        }

        .fcc-info-toggle {
            width: 100%;
            background: var(--clr-secondary);
            color: #FAF9F6 !important;
            border: none;
            padding: var(--jimr-space-4) var(--jimr-space-5);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: var(--jimr-font-lg);
            font-weight: 600;
            transition: var(--jimr-transition-slow);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .fcc-info-toggle:hover {
            background: var(--clr-primary);
            color: #FAF9F6 !important;
            transform: translateY(-1px);
        }

        .fcc-toggle-icon {
            transition: transform 0.3s ease;
            font-size: var(--jimr-font-base);
            color: #FAF9F6 !important;
        }

        .fcc-info-toggle.expanded .fcc-toggle-icon {
            transform: rotate(180deg);
        }

        .fcc-info-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, padding 0.4s ease;
            background: var(--clr-light);
            color: var(--clr-txt);
        }

        .fcc-info-content.expanded {
            max-height: 500px;
            padding: var(--jimr-space-5);
        }

        /* Progress Animation */
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        /* Tables */
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            background: white;
            border-radius: var(--jimr-border-radius);
            overflow: hidden;
            box-shadow: var(--clr-shadow);
        }

        .inventory-table th,
        .inventory-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--jimr-gray-200);
            font-size: var(--jimr-font-sm);
        }

        .inventory-table th {
            background-color: var(--jimr-gray-100) !important;
            color: var(--jimr-gray-700);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: var(--jimr-font-xs);
        }

        .inventory-table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }

        .category-section {
            margin-bottom: 32px;
            border-radius: var(--jimr-border-radius-lg);
            overflow: hidden;
            box-shadow: var(--clr-shadow);
        }

        .category-header {
            background: var(--clr-secondary);
            color: #FAF9F6;
            padding: 16px 20px;
            margin: 0;
            font-size: var(--jimr-font-lg);
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        .file-details {
            font-size: var(--jimr-font-xs);
            color: var(--jimr-gray-600);
            max-width: 300px;
            line-height: 1.4;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--jimr-gray-200);
            font-size: var(--jimr-font-sm);
        }

        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
            border-top: 2px solid var(--clr-secondary);
            margin-top: 12px;
            padding-top: 12px;
            color: var(--clr-primary);
        }

        /* Sample thumbnails in tables */
        .sample-images {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .sample-thumbnail {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: var(--jimr-border-radius);
            border: 1px solid var(--clr-secondary);
        }

        /* Image Display Styles */
        .images-list {
            margin-top: 16px;
        }

        .image-item {
            margin-bottom: 20px;
            border: 2px solid var(--clr-secondary);
            border-radius: var(--jimr-border-radius-lg);
            overflow: hidden;
            background: white;
            box-shadow: var(--clr-shadow);
        }

        .image-header {
            background: var(--clr-light);
            padding: 12px 16px;
            border-bottom: 1px solid var(--clr-secondary);
            font-size: var(--jimr-font-base);
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .image-thumbnail {
            flex-shrink: 0;
            width: 80px;
            height: 80px;
            border-radius: var(--jimr-border-radius);
            overflow: hidden;
            border: 2px solid var(--clr-secondary);
            background: var(--clr-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.2s ease;
            background: var(--clr-light);
        }

        .image-thumbnail:hover img {
            transform: scale(1.05);
        }

        .image-thumbnail.loading {
            background: var(--clr-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--clr-txt);
            font-size: var(--jimr-font-xs);
        }

        .image-thumbnail.error {
            background: var(--jimr-gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--jimr-gray-500);
            font-size: var(--jimr-font-xs);
        }

        .image-info {
            flex-grow: 1;
            min-width: 0;
            /* Allows text to wrap properly */
        }

        .main-dimensions {
            color: var(--jimr-gray-600);
            font-size: var(--jimr-font-xs);
            font-style: italic;
        }

        .image-stats {
            color: var(--clr-txt);
            font-weight: normal;
            font-size: var(--jimr-font-sm);
        }

        .image-files {
            padding: 12px 16px;
        }

        .file-item {
            padding: 6px 0;
            border-bottom: 1px solid var(--jimr-gray-100);
            font-family: monospace;
            font-size: var(--jimr-font-sm);
            line-height: 1.4;
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .filename {
            font-weight: bold;
            color: var(--clr-secondary);
        }

        .file-type {
            color: var(--jimr-gray-600);
            font-style: italic;
        }

        .file-dimensions {
            color: var(--clr-txt);
        }

        .file-size {
            color: var(--jimr-danger);
            font-weight: bold;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .fcc-header-section {
                width: 100% !important;
                padding: 0 16px;
            }

            .media-inventory-container {
                margin: 0 !important;
            }

            .inventory-table th,
            .inventory-table td {
                padding: 8px 6px;
                font-size: var(--jimr-font-xs);
            }

            .image-header {
                flex-direction: column;
                gap: 12px;
                align-items: center;
                text-align: center;
            }

            .image-thumbnail {
                width: 100px;
                height: 100px;
            }

            .image-info {
                text-align: center;
            }
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            let inventoryData = [];
            let isScanning = false;

            // Toggle functionality for about section
            $('.fcc-info-toggle').on('click', function() {
                const target = $(this).data('toggle-target');
                const content = $('#' + target);

                if (content.hasClass('expanded')) {
                    content.removeClass('expanded');
                    $(this).removeClass('expanded');
                } else {
                    content.addClass('expanded');
                    $(this).addClass('expanded');
                }
            });

            $('#start-scan').on('click', function() {
                if (isScanning) return;

                isScanning = true;
                inventoryData = [];

                $('#start-scan').prop('disabled', true).text('scanning...').hide();
                $('#stop-scan').show();
                $('#scan-progress').show();
                $('#summary-stats').hide();
                $('#export-csv, #clear-results').hide();
                $('#results-container').html('<div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">Scanning in progress...</div>');

                scanBatch(0);
            });

            $('#stop-scan').on('click', function() {
                isScanning = false;
                $('#start-scan').prop('disabled', false).text('🔍 start scan').show();
                $('#stop-scan').hide();
                $('#scan-progress').hide();
                $('#export-csv, #clear-results').show();
                $('#results-container').html('<div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">Scan stopped. Click "start scan" to resume or "clear results" to start over.</div>');
            });

            $('#export-csv').on('click', function() {
                if (inventoryData.length === 0) {
                    alert('No data to export');
                    return;
                }

                // Create form and submit
                const form = $('<form>', {
                    method: 'POST',
                    action: ajaxurl
                });

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'action',
                    value: 'media_inventory_export'
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'nonce',
                    value: '<?php echo wp_create_nonce('media_inventory_nonce'); ?>'
                }));

                form.append($('<input>', {
                    type: 'hidden',
                    name: 'inventory_data',
                    value: JSON.stringify(inventoryData)
                }));

                $('body').append(form);
                form.submit();
                form.remove();
            });

            $('#clear-results').on('click', function() {
                inventoryData = [];
                $('#results-container').html('<div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">Click "start scan" to begin inventory scanning.</div>');
                $('#summary-stats').hide();
                $('#export-csv, #clear-results').hide();
            });

            function scanBatch(offset) {
                if (!isScanning) return; // Check if user stopped the scan

                $.post({
                    url: ajaxurl,
                    data: {
                        action: 'media_inventory_scan',
                        nonce: '<?php echo wp_create_nonce('media_inventory_nonce'); ?>',
                        offset: offset
                    },
                    timeout: 30000 // 30 second timeout
                }).done(function(response) {
                    if (!isScanning) return; // Check again in case user stopped during request

                    if (response.success) {
                        inventoryData = inventoryData.concat(response.data.data);

                        // Show any errors
                        if (response.data.errors && response.data.errors.length > 0) {
                            console.warn('Scan warnings:', response.data.errors);
                        }

                        // Update progress
                        const progress = Math.round((response.data.processed / response.data.total) * 100);
                        $('#progress-bar').css('width', progress + '%');
                        $('#progress-text').text(response.data.processed + ' / ' + response.data.total + ' processed');

                        if (response.data.complete) {
                            // Scanning complete
                            isScanning = false;
                            $('#start-scan').prop('disabled', false).text('🔍 start scan').show();
                            $('#stop-scan').hide();
                            $('#scan-progress').hide();
                            $('#export-csv, #clear-results').show();

                            displayResults();
                        } else {
                            // Continue scanning
                            setTimeout(function() {
                                scanBatch(response.data.offset);
                            }, 500); // Small delay between batches
                        }
                    } else {
                        alert('Error: ' + response.data);
                        isScanning = false;
                        $('#start-scan').prop('disabled', false).text('🔍 start scan').show();
                        $('#stop-scan').hide();
                        $('#scan-progress').hide();
                    }
                }).fail(function(xhr, status, error) {
                    if (!isScanning) return; // User already stopped

                    let errorMsg = 'AJAX request failed';
                    if (status === 'timeout') {
                        errorMsg = 'Request timed out - try reducing batch size or check server resources';
                    } else if (xhr.responseText) {
                        errorMsg = 'Server error: ' + xhr.responseText.substring(0, 200);
                    }

                    alert(errorMsg);
                    isScanning = false;
                    $('#start-scan').prop('disabled', false).text('🔍 start scan').show();
                    $('#stop-scan').hide();
                    $('#scan-progress').hide();
                });
            }

            function displayResults() {
                if (inventoryData.length === 0) {
                    $('#results-container').html('<div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">No media files found.</div>');
                    return;
                }

                // Group by category
                const categories = {};
                const totals = {
                    files: 0,
                    size: 0,
                    items: 0
                };

                inventoryData.forEach(item => {
                    if (!categories[item.category]) {
                        categories[item.category] = {
                            items: [],
                            totalSize: 0,
                            totalFiles: 0,
                            itemCount: 0
                        };
                    }

                    categories[item.category].items.push(item);
                    categories[item.category].totalSize += item.total_size;
                    categories[item.category].totalFiles += item.file_count;
                    categories[item.category].itemCount++;

                    totals.files += item.file_count;
                    totals.size += item.total_size;
                    totals.items++;
                });

                // Build results HTML
                let html = '';

                // Use custom ordering for main display
                const mainCategoryOrder = ['Fonts', 'SVG', 'Images', 'Videos', 'Audio', 'PDFs', 'Documents', 'Text Files', 'Other Documents', 'Other'];

                // Get categories in the desired order
                const mainOrderedCategories = [];

                // First add categories in our preferred order
                mainCategoryOrder.forEach(function(catName) {
                    if (categories[catName]) {
                        mainOrderedCategories.push(catName);
                    }
                });

                // Then add any remaining categories alphabetically
                Object.keys(categories).sort().forEach(function(catName) {
                    if (!mainOrderedCategories.includes(catName)) {
                        mainOrderedCategories.push(catName);
                    }
                });

                mainOrderedCategories.forEach(function(catName) {
                    const category = categories[catName];

                    html += '<div class="category-section">';
                    html += '<h3 class="category-header">' + catName + ' (' + category.itemCount + ' items, ' + category.totalFiles + ' files, ' + formatBytes(category.totalSize) + ')</h3>';

                    // Debug: Track HTML building order
                    console.log('Building HTML for:', catName);
                    if (catName === 'Fonts') {
                        // Group fonts by family
                        const fontFamilies = {};
                        category.items.forEach(item => {
                            const family = item.font_family || 'Unknown Font';
                            if (!fontFamilies[family]) {
                                fontFamilies[family] = {
                                    items: [],
                                    totalSize: 0,
                                    totalFiles: 0
                                };
                            }
                            fontFamilies[family].items.push(item);
                            fontFamilies[family].totalSize += item.total_size;
                            fontFamilies[family].totalFiles += item.file_count;
                        });

                        html += '<table class="inventory-table">';
                        html += '<thead><tr><th>Font Family</th><th>Variants</th><th>Files</th><th>Total Size</th><th>Details</th></tr></thead>';
                        html += '<tbody>';

                        Object.keys(fontFamilies).sort().forEach(familyName => {
                            const family = fontFamilies[familyName];
                            const variants = family.items.map(item => item.extension.toUpperCase()).join(', ');
                            const details = family.items.map(item => escapeHtml(item.title) + ' (' + formatBytes(item.total_size) + ')').join('<br>');

                            html += '<tr>';
                            html += '<td><strong>' + escapeHtml(familyName) + '</strong></td>';
                            html += '<td>' + variants + '</td>';
                            html += '<td>' + family.totalFiles + '</td>';
                            html += '<td>' + formatBytes(family.totalSize) + '</td>';
                            html += '<td class="file-details">' + details + '</td>';
                            html += '</tr>';
                        });

                        html += '</tbody></table>';

                    } else if (catName === 'SVG') {
                        // Regular display for SVG
                        html += '<table class="inventory-table">';
                        html += '<thead><tr><th>Title</th><th>Extension</th><th>Dimensions</th><th>Files</th><th>Size</th><th>File Details</th></tr></thead>';
                        html += '<tbody>';

                        category.items.forEach(item => {
                            const fileDetails = item.files.map(f => {
                                let detail = f.type + ': ' + formatBytes(f.size);
                                if (f.dimensions) {
                                    detail += ' (' + f.dimensions + ')';
                                }
                                return detail;
                            }).join('<br>');

                            html += '<tr>';
                            html += '<td>' + escapeHtml(item.title) + '</td>';
                            html += '<td>' + item.extension.toUpperCase() + '</td>';
                            html += '<td>' + (item.dimensions || 'Unknown') + '</td>';
                            html += '<td>' + item.file_count + '</td>';
                            html += '<td>' + formatBytes(item.total_size) + '</td>';
                            html += '<td class="file-details">' + fileDetails + '</td>';
                            html += '</tr>';
                        });

                        html += '</tbody></table>';

                    } else if (catName === 'Images') {
                        // Group images by WordPress size categories
                        const wpSizeCategories = {};

                        category.items.forEach(item => {
                            item.files.forEach(file => {
                                const filename = file.filename || '';
                                let sizeCategory = 'Original Files';
                                let sizeSuffix = 'original';

                                // Extract size suffix from filename (e.g., "-150", "-300x200", "-768")
                                const sizeMatch = filename.match(/-(\d+)(?:x\d+)?(?:\.[^.]+)?$/);
                                if (sizeMatch) {
                                    const width = parseInt(sizeMatch[1]);
                                    sizeSuffix = '-' + width;

                                    // Categorize by WordPress standard sizes
                                    if (width <= 150) {
                                        sizeCategory = 'Thumbnails (≤150px)';
                                    } else if (width <= 300) {
                                        sizeCategory = 'Small (151-300px)';
                                    } else if (width <= 768) {
                                        sizeCategory = 'Medium (301-768px)';
                                    } else if (width <= 1024) {
                                        sizeCategory = 'Large (769-1024px)';
                                    } else if (width <= 1536) {
                                        sizeCategory = 'Extra Large (1025-1536px)';
                                    } else {
                                        sizeCategory = 'Super Large (>1536px)';
                                    }
                                }

                                if (!wpSizeCategories[sizeCategory]) {
                                    wpSizeCategories[sizeCategory] = {
                                        items: [],
                                        totalSize: 0,
                                        totalFiles: 0,
                                        sizeSuffixes: new Set()
                                    };
                                }

                                wpSizeCategories[sizeCategory].items.push({
                                    ...item,
                                    currentFile: file
                                });
                                wpSizeCategories[sizeCategory].totalSize += file.size;
                                wpSizeCategories[sizeCategory].totalFiles++;
                                wpSizeCategories[sizeCategory].sizeSuffixes.add(sizeSuffix);
                            });
                        });

                        // WordPress Image Sizes Summary title
                        html += '<h2 style="color: var(--clr-primary); margin: 20px 0 12px 0; text-align: center;">WordPress Image Sizes Summary</h2>';

                        // Summary by WordPress size categories (three separate white columns)
                        html += '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 20px;">';

                        const wpCategoryOrder = [
                            'Original Files',
                            'Thumbnails (≤150px)',
                            'Small (151-300px)',
                            'Medium (301-768px)',
                            'Large (769-1024px)',
                            'Extra Large (1025-1536px)',
                            'Super Large (>1536px)'
                        ];

                        const sortedWpCategories = wpCategoryOrder.filter(cat => wpSizeCategories[cat]);

                        // Split into three columns more evenly
                        const leftColumn = [];
                        const middleColumn = [];
                        const rightColumn = [];

                        sortedWpCategories.forEach((cat, index) => {
                            if (index % 3 === 0) {
                                leftColumn.push(cat);
                            } else if (index % 3 === 1) {
                                middleColumn.push(cat);
                            } else {
                                rightColumn.push(cat);
                            }
                        });
                        // Left column
                        html += '<div style="background: white; border-radius: var(--jimr-border-radius); padding: 12px; box-shadow: var(--clr-shadow); border: 1px solid var(--jimr-gray-200);">';
                        leftColumn.forEach(categoryName => {
                            const wpCategory = wpSizeCategories[categoryName];
                            const leftSizeSuffixList = Array.from(wpCategory.sizeSuffixes).join(', ');

                            html += '<div style="padding: 8px 0; border-bottom: 1px solid var(--jimr-gray-200);">';
                            html += '<div><strong style="color: var(--clr-secondary);">' + escapeHtml(categoryName) + '</strong><br>';
                            html += '<small style="color: var(--clr-txt);">Suffixes: ' + leftSizeSuffixList + '</small><br>';
                            html += '<small style="color: var(--clr-txt);">' + wpCategory.totalFiles + ' files, ' + formatBytes(wpCategory.totalSize) + '</small></div>';
                            html += '</div>';
                        });
                        html += '</div>';

                        // Middle column
                        html += '<div style="background: white; border-radius: var(--jimr-border-radius); padding: 12px; box-shadow: var(--clr-shadow); border: 1px solid var(--jimr-gray-200);">';
                        middleColumn.forEach(categoryName => {
                            const wpCategory = wpSizeCategories[categoryName];
                            const middleSizeSuffixList = Array.from(wpCategory.sizeSuffixes).join(', ');

                            html += '<div style="padding: 8px 0; border-bottom: 1px solid var(--jimr-gray-200);">';
                            html += '<div><strong style="color: var(--clr-secondary);">' + escapeHtml(categoryName) + '</strong><br>';
                            html += '<small style="color: var(--clr-txt);">Suffixes: ' + middleSizeSuffixList + '</small><br>';
                            html += '<small style="color: var(--clr-txt);">' + wpCategory.totalFiles + ' files, ' + formatBytes(wpCategory.totalSize) + '</small></div>';
                            html += '</div>';
                        });
                        html += '</div>';

                        // Right column
                        html += '<div style="background: white; border-radius: var(--jimr-border-radius); padding: 12px; box-shadow: var(--clr-shadow); border: 1px solid var(--jimr-gray-200);">';
                        rightColumn.forEach(categoryName => {
                            const wpCategory = wpSizeCategories[categoryName];
                            const rightSizeSuffixList = Array.from(wpCategory.sizeSuffixes).join(', ');

                            html += '<div style="padding: 8px 0; border-bottom: 1px solid var(--jimr-gray-200);">';
                            html += '<div><strong style="color: var(--clr-secondary);">' + escapeHtml(categoryName) + '</strong><br>';
                            html += '<small style="color: var(--clr-txt);">Suffixes: ' + rightSizeSuffixList + '</small><br>';
                            html += '<small style="color: var(--clr-txt);">' + wpCategory.totalFiles + ' files, ' + formatBytes(wpCategory.totalSize) + '</small></div>';
                            html += '</div>';
                        });
                        html += '</div>';
                        html += '</div>';

                        // Image cards title
                        html += '<h2 style="color: var(--clr-primary); margin: 20px 0 12px 0; text-align: center;">Image Cards</h2>';

                        // Individual image cards (detailed view) - Two column layout
                        html += '<div class="images-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">';

                        category.items.forEach(item => {
                            html += '<div class="image-item">';
                            html += '<div class="image-header">';

                            // Add thumbnail if available
                            if (item.thumbnail_url) {
                                html += '<div class="image-thumbnail">';
                                html += '<img src="' + escapeHtml(item.thumbnail_url) + '" ';
                                html += 'alt="' + escapeHtml(item.title) + '" ';
                                html += 'title="' + escapeHtml(item.title) + '" ';
                                html += 'loading="lazy" ';
                                html += 'onerror="this.parentElement.innerHTML=\'📷<br><small>Preview unavailable</small>\'; this.parentElement.classList.add(\'error\');" />';
                                html += '</div>';
                            } else {
                                html += '<div class="image-thumbnail error">📷<br><small>No preview</small></div>';
                            }

                            html += '<div class="image-info">';
                            html += '<strong>' + escapeHtml(item.title) + '</strong><br>';
                            html += '<span class="image-stats">(' + item.file_count + ' files, ' + formatBytes(item.total_size) + ')</span>';
                            if (item.dimensions) {
                                html += '<br><span class="main-dimensions">Original: ' + item.dimensions + '</span>';
                            }
                            html += '</div>';
                            html += '</div>';

                            html += '<div class="image-files">';
                            item.files.forEach(file => {
                                html += '<div class="file-item">';
                                html += '<span class="filename">' + escapeHtml(file.filename || 'Unknown file') + '</span> ';
                                html += '<span class="file-type">(' + file.type + ')</span> - ';
                                html += '<span class="file-dimensions">' + (file.dimensions || 'Unknown size') + '</span> - ';
                                html += '<span class="file-size">' + formatBytes(file.size) + '</span>';
                                html += '</div>';
                            });
                            html += '</div>';

                            html += '</div>';
                        });

                        html += '</div>';


                    }

                    html += '</div>';
                });

                $('#results-container').html(html);

                // Update summary
                let summaryHtml = '';
                // Explicit category order
                const categoryOrder = ['Fonts', 'SVG', 'Images', 'Videos', 'Audio', 'PDFs', 'Documents', 'Text Files', 'Other Documents', 'Other'];

                // Get categories in the desired order
                const orderedCategories = [];

                // First add categories in our preferred order
                categoryOrder.forEach(function(catName) {
                    if (categories[catName]) {
                        orderedCategories.push(catName);
                    }
                });

                // Then add any remaining categories alphabetically
                Object.keys(categories).sort().forEach(function(catName) {
                    if (!orderedCategories.includes(catName)) {
                        orderedCategories.push(catName);
                    }
                });

                orderedCategories.forEach(function(catName) {
                    const category = categories[catName];

                    // Debug: Show processing order
                    console.log('Processing category:', catName);
                    summaryHtml += '<div class="summary-item"><span>' + catName + ':</span><span>' + formatBytes(category.totalSize) + '</span></div>';
                });
                summaryHtml += '<div class="summary-item"><span>Total:</span><span>' + formatBytes(totals.size) + '</span></div>';

                $('#summary-content').html(summaryHtml);
                $('#summary-stats').show();
            }

            function formatBytes(bytes) {
                const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                let size = Math.max(bytes, 0);
                let pow = Math.floor(Math.log(size) / Math.log(1024));
                pow = Math.min(pow, units.length - 1);
                size /= Math.pow(1024, pow);
                return Math.round(size * 100) / 100 + ' ' + units[pow];
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    </script>
<?php
}
