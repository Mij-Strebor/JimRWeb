<?php

/**
 * Elementor Color Inventory Forge
 * Version: 1.0
 * Comprehensive tool for inventorying, analyzing, and managing Elementor Global Colors
 * Displays color swatches, HEX codes, CSS variables, and color mode conversions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Helper function for converting HEX to RGB
function elementor_color_hex_to_rgb($hex)
{
    $hex = ltrim($hex, '#');

    if (strlen($hex) == 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    ];
}

// Helper function for converting RGB to HSL
function elementor_color_rgb_to_hsl($r, $g, $b)
{
    $r /= 255;
    $g /= 255;
    $b /= 255;

    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l = ($max + $min) / 2;

    if ($max == $min) {
        $h = $s = 0; // achromatic
    } else {
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        switch ($max) {
            case $r:
                $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
                break;
            case $g:
                $h = ($b - $r) / $d + 2;
                break;
            case $b:
                $h = ($r - $g) / $d + 4;
                break;
        }
        $h /= 6;
    }

    return [
        'h' => round($h * 360),
        's' => round($s * 100),
        'l' => round($l * 100)
    ];
}

// Helper function for determining text color based on background
function elementor_color_get_contrast_text($hex)
{
    $rgb = elementor_color_hex_to_rgb($hex);
    $brightness = ($rgb['r'] * 299 + $rgb['g'] * 587 + $rgb['b'] * 114) / 1000;
    return $brightness > 128 ? '#000000' : '#FFFFFF';
}

// Helper function for finding color usage across Elementor content
function elementor_color_find_usage($color_id)
{
    global $wpdb;

    $usage_count = 0;
    $used_on = [];

    // Search for global color references in post meta - enhanced patterns
    $search_patterns = [
        "globals/colors?id={$color_id}",
        "globals/colors/{$color_id}",
        $color_id
    ];

    $results = [];
    foreach ($search_patterns as $search_pattern) {
        $pattern_results = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_type, pm.meta_key 
            FROM {$wpdb->postmeta} pm 
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE pm.meta_value LIKE %s 
            AND p.post_status IN ('publish', 'private')
            AND pm.meta_key IN ('_elementor_data', '_elementor_page_settings')
        ", '%' . $search_pattern . '%'));

        $results = array_merge($results, $pattern_results);
    }

    foreach ($results as $result) {
        $usage_count++;
        $used_on[] = [
            'id' => $result->ID,
            'title' => $result->post_title,
            'type' => $result->post_type,
            'meta_key' => $result->meta_key
        ];
    }

    return [
        'count' => $usage_count,
        'locations' => $used_on
    ];
}

// Get Elementor active kit ID
function elementor_color_get_active_kit_id()
{
    return get_option('elementor_active_kit', 0);
}

// Get all Elementor global colors
function elementor_color_get_global_colors()
{
    $kit_id = elementor_color_get_active_kit_id();

    if (!$kit_id) {
        error_log('Elementor Color: No active kit ID found');
        return false;
    }

    $kit_settings = get_post_meta($kit_id, '_elementor_page_settings', true);

    if (!$kit_settings) {
        error_log('Elementor Color: No kit settings found for kit ID: ' . $kit_id);
        return false;
    }

    error_log('Elementor Color: Kit settings structure: ' . print_r(array_keys($kit_settings), true));

    $colors = [];

    // System/Default colors
    if (isset($kit_settings['system_colors']) && is_array($kit_settings['system_colors'])) {
        error_log('Elementor Color: Found system_colors with ' . count($kit_settings['system_colors']) . ' entries');
        foreach ($kit_settings['system_colors'] as $color) {
            if (isset($color['_id']) && isset($color['color'])) {
                $colors[] = [
                    'id' => $color['_id'],
                    'title' => $color['title'] ?? ucfirst($color['_id']),
                    'color' => $color['color'],
                    'type' => 'system',
                    'css_var' => '--e-global-color-' . $color['_id']
                ];
            }
        }
    } else {
        error_log('Elementor Color: No system_colors found or not an array');
    }

    // Custom colors
    if (isset($kit_settings['custom_colors']) && is_array($kit_settings['custom_colors'])) {
        error_log('Elementor Color: Found custom_colors with ' . count($kit_settings['custom_colors']) . ' entries');
        foreach ($kit_settings['custom_colors'] as $color) {
            if (isset($color['_id']) && isset($color['color'])) {
                $colors[] = [
                    'id' => $color['_id'],
                    'title' => $color['title'] ?? 'Custom Color',
                    'color' => $color['color'],
                    'type' => 'custom',
                    'css_var' => '--e-global-color-' . $color['_id']
                ];
            }
        }
    } else {
        error_log('Elementor Color: No custom_colors found or not an array');
    }

    // Check for alternative structure (sometimes Elementor uses different keys)
    if (empty($colors)) {
        error_log('Elementor Color: No colors found, checking alternative structures...');

        // Check for 'colors' key
        if (isset($kit_settings['colors'])) {
            error_log('Elementor Color: Found alternative colors structure');
            // Handle alternative structure
        }

        // Check for direct color entries
        foreach ($kit_settings as $key => $value) {
            if (strpos($key, 'color') !== false) {
                error_log('Elementor Color: Found color-related key: ' . $key . ' = ' . print_r($value, true));
            }
        }
    }

    error_log('Elementor Color: Final colors array has ' . count($colors) . ' entries');

    return empty($colors) ? false : $colors;
}

// Add admin menu
add_action('admin_menu', 'elementor_color_inventory_add_admin_menu');

// ========================================================================
// ADMIN INTERFACE
// ========================================================================

/**
 * Add admin menu page
 */
function elementor_color_inventory_add_admin_menu()
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

    // Add Elementor Color Inventory as submenu item
    add_submenu_page(
        'j-forge',                          // Parent slug
        'Elementor Color Inventory Forge',  // Page title
        'Elementor Colors',                 // Menu title
        'manage_options',                   // Capability
        'elementor-color-inventory',        // Menu slug
        'elementor_color_inventory_admin_page'  // Function
    );
}

// AJAX handler for scanning colors
add_action('wp_ajax_elementor_color_scan', 'elementor_color_ajax_scan');
function elementor_color_ajax_scan()
{
    check_ajax_referer('elementor_color_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }

    // Check if Elementor is active
    if (!defined('ELEMENTOR_VERSION')) {
        wp_send_json_error('Elementor is not active on this site');
    }

    // Debug logging
    error_log('Elementor Color Scan: Starting scan...');

    $colors = elementor_color_get_global_colors();

    if (!$colors) {
        // Get more specific error information
        $kit_id = elementor_color_get_active_kit_id();
        error_log('Elementor Color Scan: Kit ID = ' . $kit_id);

        if (!$kit_id) {
            wp_send_json_error('No Elementor kit found. Please configure Elementor global settings first.');
        }

        $kit_settings = get_post_meta($kit_id, '_elementor_page_settings', true);
        error_log('Elementor Color Scan: Kit settings = ' . print_r($kit_settings, true));

        if (!$kit_settings) {
            wp_send_json_error('Elementor kit has no settings. Please configure global colors in Elementor.');
        }

        wp_send_json_error('No colors found in Elementor kit. Please add global colors in Elementor > Site Settings > Design System > Global Colors.');
    }

    error_log('Elementor Color Scan: Found ' . count($colors) . ' colors');

    $inventory_data = [];

    foreach ($colors as $color) {
        try {
            $hex_color = $color['color'];
            $rgb = elementor_color_hex_to_rgb($hex_color);
            $hsl = elementor_color_rgb_to_hsl($rgb['r'], $rgb['g'], $rgb['b']);
            $contrast_text = elementor_color_get_contrast_text($hex_color);
            $usage = elementor_color_find_usage($color['id']);

            $inventory_data[] = [
                'id' => $color['id'],
                'title' => $color['title'],
                'type' => $color['type'],
                'hex' => $hex_color,
                'css_var' => $color['css_var'],
                'rgb' => $rgb,
                'hsl' => $hsl,
                'contrast_text' => $contrast_text,
                'usage_count' => $usage['count'],
                'used_on' => $usage['locations']
            ];
        } catch (Exception $e) {
            error_log('Error processing color: ' . $e->getMessage());
        }
    }

    error_log('Elementor Color Scan: Processed ' . count($inventory_data) . ' colors successfully');

    wp_send_json_success([
        'colors' => $inventory_data,
        'total' => count($inventory_data),
        'kit_id' => elementor_color_get_active_kit_id()
    ]);
}

// AJAX handler for exporting CSV
add_action('wp_ajax_elementor_color_export', 'elementor_color_ajax_export');
function elementor_color_ajax_export()
{
    check_ajax_referer('elementor_color_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_die('Permission denied');
    }

    $color_data = json_decode(stripslashes($_POST['color_data']), true);

    if (empty($color_data)) {
        wp_die('No data to export');
    }

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="elementor-color-inventory-' . date('Y-m-d-H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, [
        'Color ID',
        'Color Name',
        'Type',
        'HEX',
        'CSS Variable',
        'RGB (R)',
        'RGB (G)',
        'RGB (B)',
        'RGB String',
        'HSL (H)',
        'HSL (S)',
        'HSL (L)',
        'HSL String',
        'Usage Count',
        'Used On'
    ]);

    foreach ($color_data as $color) {
        $used_on_list = [];
        if (!empty($color['used_on'])) {
            foreach ($color['used_on'] as $usage) {
                $used_on_list[] = $usage['title'] . ' (' . $usage['type'] . ')';
            }
        }

        fputcsv($output, [
            $color['id'],
            $color['title'],
            ucfirst($color['type']),
            $color['hex'],
            $color['css_var'],
            $color['rgb']['r'],
            $color['rgb']['g'],
            $color['rgb']['b'],
            'rgb(' . $color['rgb']['r'] . ', ' . $color['rgb']['g'] . ', ' . $color['rgb']['b'] . ')',
            $color['hsl']['h'],
            $color['hsl']['s'],
            $color['hsl']['l'],
            'hsl(' . $color['hsl']['h'] . ', ' . $color['hsl']['s'] . '%, ' . $color['hsl']['l'] . '%)',
            $color['usage_count'],
            implode(' | ', $used_on_list)
        ]);
    }

    fclose($output);
    exit;
}

// Admin page
function elementor_color_inventory_admin_page()
{
?>
    <div class="wrap" style="background: var(--clr-page-bg); padding: 20px; min-height: 100vh;">
        <div class="fcc-header-section" style="width: 1280px; margin: 0 auto;">
            <h1 class="text-2xl font-bold mb-4">Elementor Color Inventory Forge (1.0)</h1><br>

            <!-- About Section -->
            <div class="fcc-info-toggle-section">
                <button class="fcc-info-toggle expanded" data-toggle-target="about-content">
                    <span style="color: #FAF9F6 !important;">🎨 About Elementor Color Inventory Forge</span>
                    <span class="fcc-toggle-icon" style="color: #FAF9F6 !important;">▼</span>
                </button>
                <div class="fcc-info-content expanded" id="about-content">
                    <div style="color: var(--clr-txt); font-size: 14px; line-height: 1.6;">
                        <p style="margin: 0 0 16px 0; color: var(--clr-txt);">
                            Elementor Color Inventory Forge is a comprehensive tool for analyzing and managing your Elementor Global Colors. It provides detailed color information including HEX codes, RGB/HSL values, CSS variables, and usage tracking across your website. Perfect for design system management, brand consistency checks, and color palette optimization.
                        </p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 20px;">
                            <div>
                                <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">🎯 Complete Color Analysis</h4>
                                <p style="margin: 0; font-size: 13px; line-height: 1.5;">Scans all Elementor Global Colors with detailed conversion to RGB, HSL, and automatic CSS variable detection.</p>
                            </div>
                            <div>
                                <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">📊 Usage Tracking</h4>
                                <p style="margin: 0; font-size: 13px; line-height: 1.5;">Track where each color is used across your website content, helping identify unused or overused colors.</p>
                            </div>
                            <div>
                                <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">🔧 Developer Tools</h4>
                                <p style="margin: 0; font-size: 13px; line-height: 1.5;">Export CSS variables, copy color codes, and generate design system documentation with precise Elementor variable names.</p>
                            </div>
                            <div>
                                <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">📈 Export & Documentation</h4>
                                <p style="margin: 0; font-size: 13px; line-height: 1.5;">Generate detailed CSV reports and CSS files for design documentation, brand guides, and development workflows.</p>
                            </div>
                        </div>
                        <div style="background: rgba(60, 32, 23, 0.1); padding: 12px 16px; border-radius: 6px; border-left: 4px solid var(--clr-accent); margin-top: 20px;">
                            <p style="margin: 0; font-size: 13px; opacity: 0.95; line-height: 1.5; color: var(--clr-txt);">
                                Elementor Color Inventory Forge by Jim R. (<a href="https://jimrweb.com" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">JimRWeb</a>), developed with tremendous help from Claude AI (<a href="https://anthropic.com" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">Anthropic</a>). Part of the professional WordPress development toolkit.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Container -->
            <div class="elementor-color-inventory-container" style="margin: 0 auto; background: var(--clr-card-bg); border-radius: var(--jimr-border-radius-lg); box-shadow: var(--clr-shadow-xl); overflow: hidden; border: 2px solid var(--clr-primary);">
                <div style="padding: 20px;">

                    <!-- Summary Stats Section -->
                    <div class="fcc-panel" style="margin-bottom: 20px;">
                        <h2 style="margin-bottom: 16px;">Color Palette Summary</h2>

                        <div id="summary-stats">
                            <div id="summary-content" style="background: var(--clr-light); padding: 16px; border-radius: var(--jimr-border-radius); border: 1px solid var(--clr-secondary);">
                                <div style="text-align: center; color: var(--clr-txt); font-style: italic;">
                                    <div class="fcc-loading-spinner" style="width: 25px; height: 25px; margin: 0 auto 10px;"></div>
                                    <div>Analyzing Elementor colors...</div>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; align-items: center; margin-top: 16px; justify-content: space-between;">
                            <button id="manual-scan" class="fcc-btn" style="display: none;">🔄 retry scan</button>
                            <button id="export-csv" class="fcc-btn">📊 export csv</button>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <div class="fcc-panel">
                        <h2 style="margin-bottom: 16px;">Color Palette Results</h2>
                        <div id="results-container" style="min-height: 120px;">
                            <div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">
                                <div class="fcc-loading-spinner" style="width: 25px; height: 25px; margin: 0 auto 10px;"></div>
                                <div>Loading Elementor colors...</div>
                            </div>
                        </div>
                    </div>

                    <!-- CSS Output Section -->
                    <div style="margin-top: 20px;">
                        <!-- CSS Section Header -->
                        <h2 style="color: var(--clr-primary); margin: 0 0 20px 0; text-align: center; font-family: 'Georgia', serif; font-size: var(--jimr-font-2xl); font-weight: 700; text-shadow: 1px 1px 2px var(--clr-shadow);">Generated Color CSS</h2>

                        <!-- CSS Output Tabs -->
                        <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                            <div class="css-output-tabs">
                                <button id="css-vars-tab" class="css-tab-button active" data-css-tab="vars" data-tooltip="Generate CSS custom properties for use with var() in stylesheets">variables</button>
                                <button id="css-classes-tab" class="css-tab-button" data-css-tab="classes" data-tooltip="Generate utility classes for text, background, and border colors">classes</button>
                                <button id="css-scss-tab" class="css-tab-button" data-css-tab="scss" data-tooltip="Generate SCSS variables for build processes and compilation">scss</button>
                                <button id="css-fallback-tab" class="css-tab-button" data-css-tab="fallback" data-tooltip="Generate CSS with fallback values for better browser compatibility">fallbacks</button>
                            </div>
                        </div>

                        <!-- CSS Output Panel -->
                        <div class="fcc-panel" style="margin-top: 8px;" id="css-output-container">
                            <div class="fcc-css-header">
                                <h2 style="flex-grow: 1;" id="css-output-title">CSS Variables</h2>
                                <div class="fcc-css-buttons" id="css-copy-buttons">
                                    <button id="copy-css-btn" class="fcc-copy-btn" data-tooltip="Copy CSS to clipboard" aria-label="Copy CSS to clipboard" title="Copy CSS">
                                        <span class="copy-icon">📋</span> copy
                                    </button>
                                </div>
                            </div>
                            <div style="background: white; border-radius: 6px; padding: 8px; border: 1px solid #d1d5db; overflow: auto; max-height: 400px;">
                                <pre id="css-output" style="font-size: 12px; white-space: pre-wrap; color: #111827; margin: 0;">/* Loading CSS output... */</pre>
                            </div>
                        </div>

                        <!-- Contrast Analysis Section -->
                        <div style="margin-top: 20px;">
                            <h2 style="color: var(--clr-primary); margin: 0 0 20px 0; text-align: center; font-family: 'Georgia', serif; font-size: var(--jimr-font-2xl); font-weight: 700; text-shadow: 1px 1px 2px var(--clr-shadow);">Color Contrast Analysis</h2>

                            <div class="fcc-panel" id="contrast-analysis-container">
                                <h2 style="margin-bottom: 16px;">WCAG Contrast Checker</h2>

                                <!-- Background and Foreground Controls -->
                                <div class="contrast-controls" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">

                                    <!-- Background Color Control -->
                                    <div class="contrast-color-control">
                                        <h3 style="color: var(--clr-secondary); margin: 0 0 12px 0; font-size: var(--jimr-font-lg);">Background Color</h3>

                                        <!-- Swatch, Button and Dropdown Panel -->
                                        <div style="margin-bottom: 12px;">
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div style="display: inline-flex; border: 2px solid var(--clr-secondary); border-radius: var(--jimr-border-radius); overflow: hidden;">
                                                    <input type="color" id="bg-color-picker" value="#ffffff" style="width: 120px; height: 40px; border: none; margin: 0; cursor: pointer;" data-color="#ffffff" data-name="White">
                                                    <button id="bg-color-button" onclick="document.getElementById('bg-color-picker').click()" style="background: var(--clr-accent); color: var(--clr-btn-txt); border: none; padding: 0 12px; font-size: 11px; font-weight: 600; cursor: pointer; height: 40px; border-left: 1px solid var(--clr-secondary); display: flex; align-items: center;">#FFFFFF</button>
                                                </div>
                                                <select id="bg-color-dropdown" class="contrast-dropdown" style="width: 300px; padding: 8px 12px; border: 2px solid var(--clr-secondary); border-radius: var(--jimr-border-radius); background: white; color: var(--clr-txt); font-size: var(--jimr-font-sm); height: 44px; box-sizing: border-box;">
                                                    <option value="">Choose from Global Colors...</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Foreground Color Control -->
                                    <div class="contrast-color-control">
                                        <h3 style="color: var(--clr-secondary); margin: 0 0 12px 0; font-size: var(--jimr-font-lg);">Foreground Color</h3>

                                        <!-- Swatch, Button and Dropdown Panel -->
                                        <div style="margin-bottom: 12px;">
                                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <div style="display: inline-flex; border: 2px solid var(--clr-secondary); border-radius: var(--jimr-border-radius); overflow: hidden;">
                                                        <input type="color" id="fg-color-picker" value="#000000" style="width: 120px; height: 40px; border: none; margin: 0; cursor: pointer;" data-color="#000000" data-name="Black">
                                                        <button id="fg-color-button" onclick="document.getElementById('fg-color-picker').click()" style="background: var(--clr-accent); color: var(--clr-btn-txt); border: none; padding: 0 12px; font-size: 11px; font-weight: 600; cursor: pointer; height: 40px; border-left: 1px solid var(--clr-secondary); display: flex; align-items: center;">#000000</button>
                                                    </div>
                                                    <select id="fg-color-dropdown" class="contrast-dropdown" style="width: 300px; padding: 8px 12px; border: 2px solid var(--clr-secondary); border-radius: var(--jimr-border-radius); background: white; color: var(--clr-txt); font-size: var(--jimr-font-sm); height: 44px; box-sizing: border-box;">
                                                        <option value="">Choose from Global Colors...</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Results Section: Sample Text and WCAG Compliance -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

                                    <!-- Left: Contrast Results -->
                                    <div class="contrast-results" style="background: var(--clr-light); padding: 20px; border-radius: var(--jimr-border-radius); border: 2px solid var(--clr-secondary);">

                                        <!-- Single Text Preview -->
                                        <div id="contrast-preview" style="padding: 20px; border-radius: var(--jimr-border-radius); background: #ffffff; color: #000000; border: 1px solid var(--clr-secondary); margin-bottom: 20px;">
                                            <h3 style="font-size: 24px; font-weight: bold; margin: 0 0 12px 0;">Sample Heading Text</h3>
                                            <p style="font-size: 16px; font-weight: normal; margin: 0 0 12px 0; line-height: 1.5;">This is a sample paragraph to demonstrate how your selected foreground and background colors will look together in real content. The text should be easily readable with sufficient contrast for accessibility.</p>
                                            <p style="font-size: 14px; font-weight: normal; margin: 0; line-height: 1.4;">Smaller text example: This demonstrates how smaller text will appear with your color combination. WCAG guidelines have different requirements for smaller text versus larger text.</p>
                                        </div>

                                        <!-- Contrast Ratio -->
                                        <div style="margin-bottom: 16px;">
                                            <span style="font-size: var(--jimr-font-lg); color: var(--clr-txt);">Contrast Ratio: </span>
                                            <span id="contrast-ratio-value" style="font-size: var(--jimr-font-lg); font-weight: 600; color: var(--clr-primary);">21:1</span>
                                        </div>

                                        <!-- WCAG Compliance with Suggested Colors -->
                                        <div style="display: flex; gap: 24px;">

                                            <!-- Left: Compliance Indicators -->
                                            <div style="flex: 1; color: var(--clr-txt); font-size: var(--jimr-font-base); line-height: 1.6;">
                                                <div style="margin-bottom: 8px;">
                                                    <span>WCAG AA Normal Text (4.5:1 required): </span>
                                                    <span id="wcag-aa-normal-text" style="font-weight: 600; padding: 2px 8px; border-radius: 3px; background: var(--clr-success); color: white;">Yes</span>
                                                </div>
                                                <div style="margin-bottom: 8px;">
                                                    <span>WCAG AA Large Text (3:1 required): </span>
                                                    <span id="wcag-aa-large-text" style="font-weight: 600; padding: 2px 8px; border-radius: 3px; background: var(--clr-success); color: white;">Yes</span>
                                                </div>
                                                <div style="margin-bottom: 8px;">
                                                    <span>WCAG AAA Normal Text (7:1 required): </span>
                                                    <span id="wcag-aaa-normal-text" style="font-weight: 600; padding: 2px 8px; border-radius: 3px; background: var(--clr-success); color: white;">Yes</span>
                                                </div>
                                                <div>
                                                    <span>WCAG AAA Large Text (4.5:1 required): </span>
                                                    <span id="wcag-aaa-large-text" style="font-weight: 600; padding: 2px 8px; border-radius: 3px; background: var(--clr-success); color: white;">Yes</span>
                                                </div>
                                            </div>

                                            <!-- Right: Suggested Colors -->
                                            <div style="flex: 0 0 140px;">
                                                <h4 style="margin: 0 0 12px 0; font-size: var(--jimr-font-sm); color: var(--clr-secondary); font-weight: 600;">Suggested Colors:</h4>

                                                <!-- AA Suggestion -->
                                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                                    <div id="aa-color-swatch" style="width: 30px; height: 30px; border-radius: 4px; border: 2px solid var(--clr-secondary); background: #000000; cursor: pointer;" title="Double-click to apply AA compliant color"></div>
                                                    <div style="font-size: var(--jimr-font-xs); color: var(--clr-txt);">
                                                        <div style="font-weight: 600;">AA: <span id="aa-color-hex">#000000</span></div>
                                                        <div style="opacity: 0.8;">Ratio: <span id="aa-color-ratio">21:1</span></div>
                                                    </div>
                                                </div>

                                                <!-- AAA Suggestion -->
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <div id="aaa-color-swatch" style="width: 30px; height: 30px; border-radius: 4px; border: 2px solid var(--clr-secondary); background: #000000; cursor: pointer;" title="Double-click to apply AAA compliant color"></div>
                                                    <div style="font-size: var(--jimr-font-xs); color: var(--clr-txt);">
                                                        <div style="font-weight: 600;">AAA: <span id="aaa-color-hex">#000000</span></div>
                                                        <div style="opacity: 0.8;">Ratio: <span id="aaa-color-ratio">21:1</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right: WCAG Explanation Panel -->
                                    <div style="background: var(--clr-light); padding: 20px; border-radius: var(--jimr-border-radius); border: 2px solid var(--clr-secondary);">
                                        <h3 style="color: var(--clr-secondary); margin: 0 0 16px 0; font-size: var(--jimr-font-lg);">Understanding WCAG Compliance</h3>

                                        <div style="color: var(--clr-txt); font-size: var(--jimr-font-sm); line-height: 1.6;">
                                            <div style="margin-bottom: 16px;">
                                                <h4 style="color: var(--clr-primary); margin: 0 0 8px 0; font-size: var(--jimr-font-base);">WCAG AA (Minimum)</h4>
                                                <p style="margin: 0 0 4px 0;"><strong>Normal Text:</strong> 4.5:1 contrast ratio required</p>
                                                <p style="margin: 0;"><strong>Large Text:</strong> 3:1 contrast ratio required</p>
                                            </div>

                                            <div style="margin-bottom: 16px;">
                                                <h4 style="color: var(--clr-primary); margin: 0 0 8px 0; font-size: var(--jimr-font-base);">WCAG AAA (Enhanced)</h4>
                                                <p style="margin: 0 0 4px 0;"><strong>Normal Text:</strong> 7:1 contrast ratio required</p>
                                                <p style="margin: 0;"><strong>Large Text:</strong> 4.5:1 contrast ratio required</p>
                                            </div>

                                            <div style="background: rgba(60, 32, 23, 0.1); padding: 12px; border-radius: 4px; border-left: 3px solid var(--clr-accent);">
                                                <p style="margin: 0 0 8px 0; font-weight: 600; color: var(--clr-primary);">Text Size Guidelines:</p>
                                                <p style="margin: 0 0 4px 0;"><strong>Large Text:</strong> 18pt+ (24px+) normal weight, or 14pt+ (18.5px+) bold weight</p>
                                                <p style="margin: 0;"><strong>Normal Text:</strong> Smaller than large text thresholds</p>
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

                        /* Color Cards */
                        .color-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                            gap: 20px;
                            margin-top: 20px;
                        }

                        .color-card {
                            background: white;
                            border-radius: var(--jimr-border-radius-lg);
                            overflow: hidden;
                            box-shadow: var(--clr-shadow);
                            border: 2px solid var(--clr-secondary);
                            transition: var(--jimr-transition);
                        }

                        .color-card:hover {
                            transform: translateY(-3px);
                            box-shadow: var(--clr-shadow-lg);
                        }

                        .color-swatch {
                            height: 80px;
                            position: relative;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-weight: bold;
                            font-size: var(--jimr-font-lg);
                            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
                            cursor: pointer;
                            transition: var(--jimr-transition);
                        }

                        .color-swatch:hover {
                            filter: brightness(1.1);
                        }

                        .color-info {
                            padding: 16px;
                        }

                        .color-title {
                            font-weight: 600;
                            color: var(--clr-primary);
                            margin-bottom: 8px;
                            font-size: var(--jimr-font-lg);
                        }

                        .color-details {
                            display: flex;
                            justify-content: space-between;
                            gap: 16px;
                            margin-bottom: 12px;
                        }

                        .color-detail {
                            font-size: var(--jimr-font-sm);
                            color: var(--clr-txt);
                            display: flex;
                            align-items: center;
                            gap: 6px;
                        }

                        .color-detail-label {
                            font-weight: 600;
                            color: var(--clr-secondary);
                        }

                        .color-value {
                            font-family: monospace;
                            background: var(--jimr-gray-100);
                            padding: 2px 6px;
                            border-radius: var(--jimr-border-radius);
                            cursor: pointer;
                            transition: var(--jimr-transition);
                        }

                        .color-value:hover {
                            background: var(--clr-accent);
                            color: var(--clr-btn-txt);
                        }

                        .color-usage {
                            margin-top: 12px;
                            padding-top: 12px;
                            border-top: 1px solid var(--jimr-gray-200);
                            font-size: var(--jimr-font-sm);
                        }

                        .usage-count {
                            font-weight: 600;
                            color: var(--clr-secondary);
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

                        /* CSS Output Tabs - Custom Styling (Override default tabs) */
                        .css-output-tabs {
                            display: flex;
                            gap: var(--jimr-space-1);
                            background: var(--clr-primary);
                            padding: 3px;
                            border-radius: var(--jimr-border-radius-lg);
                            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
                            justify-content: center;
                            max-width: 800px;
                            margin: 0 auto;
                        }

                        .css-tab-button {
                            background: var(--clr-accent) !important;
                            color: var(--clr-btn-txt) !important;
                            border: 2px solid var(--clr-btn-bdr) !important;
                            padding: 16px 12px !important;
                            border-radius: var(--jimr-border-radius) !important;
                            font-size: 14px !important;
                            font-weight: 600 !important;
                            font-style: italic !important;
                            text-transform: lowercase !important;
                            letter-spacing: 0.5px !important;
                            transition: var(--jimr-transition-slow) !important;
                            cursor: pointer !important;
                            position: relative !important;
                            overflow: hidden !important;
                            flex: 0 0 160px !important;
                            width: 160px !important;
                            height: 64px !important;
                            text-align: center !important;
                            text-decoration: none !important;
                            display: flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                        }

                        .css-tab-button:hover {
                            background: var(--clr-btn-hover) !important;
                            transform: translateY(-1px) !important;
                            box-shadow: var(--clr-shadow) !important;
                            color: var(--clr-btn-txt) !important;
                            border-color: var(--clr-btn-bdr) !important;
                        }

                        .css-tab-button.active {
                            background: var(--clr-secondary) !important;
                            color: #FAF9F6 !important;
                            border-color: var(--clr-primary) !important;
                            font-weight: 700 !important;
                            font-style: normal !important;
                            text-transform: none !important;
                            letter-spacing: normal !important;
                            box-shadow: 0 0 15px rgba(92, 51, 36, 0.4) !important;
                            transform: none !important;
                        }

                        .css-tab-button.active:hover {
                            background: var(--clr-secondary) !important;
                            color: #FAF9F6 !important;
                            transform: none !important;
                        }

                        /* Responsive adjustments */
                        @media (max-width: 768px) {
                            .fcc-header-section {
                                width: 100% !important;
                                padding: 0 16px;
                            }

                            .elementor-color-inventory-container {
                                margin: 0 !important;
                            }

                            .color-grid {
                                grid-template-columns: 1fr;
                            }

                            .color-details {
                                flex-direction: column;
                                gap: 8px;
                            }

                            .color-details>div {
                                display: flex;
                                flex-direction: column;
                                gap: 6px;
                            }

                            .css-output-tabs {
                                max-width: 100% !important;
                            }

                            .css-tab-button {
                                font-size: 12px !important;
                                padding: 12px 8px !important;
                                width: 120px !important;
                                height: 48px !important;
                                flex: 0 0 120px !important;
                            }
                        }

                        @media (max-width: 768px) {
                            .fcc-header-section {
                                width: 100% !important;
                                padding: 0 16px;
                            }

                            .elementor-color-inventory-container {
                                margin: 0 !important;
                            }

                            .color-grid {
                                grid-template-columns: 1fr;
                            }

                            .color-details {
                                flex-direction: column;
                                gap: 8px;
                            }

                            .color-details>div {
                                display: flex;
                                flex-direction: column;
                                gap: 6px;
                            }

                            .fcc-tabs {
                                max-width: 100% !important;
                            }

                            .tab-button {
                                font-size: 12px !important;
                                padding: 8px 12px !important;
                            }
                        }

                        /* Copy notification */
                        .copy-notification {
                            position: fixed;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            background: var(--clr-success);
                            color: white;
                            padding: 12px 24px;
                            border-radius: var(--jimr-border-radius);
                            box-shadow: var(--clr-shadow-lg);
                            z-index: 9999;
                            font-weight: 600;
                            opacity: 0;
                            transition: opacity 0.3s ease;
                        }

                        .copy-notification.show {
                            opacity: 1;
                        }
                    </style>

                    <script>
                        jQuery(document).ready(function($) {
                            let colorData = [];
                            let activeCSSTab = 'vars';

                            // Debug AJAX setup
                            console.log('🔧 Elementor Color Inventory initializing...');
                            console.log('📡 AJAX URL:', ajaxurl);
                            console.log('🔑 Nonce available:', '<?php echo wp_create_nonce('elementor_color_nonce'); ?>');

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

                            // Auto-scan colors on page load with delay to ensure everything is ready
                            setTimeout(function() {
                                initializeButtonText(); // Initialize button text first
                                scanColors();
                            }, 500);
                            // Manual scan button (backup)
                            $('#manual-scan').on('click', function() {
                                $('#manual-scan').prop('disabled', true).text('scanning...');
                                setTimeout(function() {
                                    scanColors();
                                    $('#manual-scan').prop('disabled', false).text('🔄 retry scan');
                                }, 100);
                            });

                            // CSS Tab switching
                            $('.css-tab-button').on('click', function() {
                                const newTab = $(this).data('css-tab');
                                switchCSSTab(newTab);
                            });

                            // Copy CSS functionality
                            $('#copy-css-btn').on('click', function() {
                                copyCSS();
                            });

                            $('#export-csv').on('click', function() {
                                exportData('csv');
                            });

                            function scanColors() {
                                console.log('🎨 Starting Elementor color scan...');
                                updateSummary('Analyzing Elementor Global Colors...');

                                $.post({
                                    url: ajaxurl,
                                    data: {
                                        action: 'elementor_color_scan',
                                        nonce: '<?php echo wp_create_nonce('elementor_color_nonce'); ?>'
                                    },
                                    timeout: 10000
                                }).done(function(response) {
                                    console.log('📡 AJAX Response received:', response);

                                    if (response && response.success) {
                                        console.log('✅ Scan successful, colors found:', response.data.colors);
                                        colorData = response.data.colors;
                                        displayColors();
                                        generateCSS();
                                    } else {
                                        console.error('❌ Scan failed:', response);
                                        const errorMsg = response && response.data ? response.data : 'Unknown error occurred';

                                        // Show helpful message for common issues
                                        let helpfulMessage = '';
                                        if (errorMsg.includes('No colors found')) {
                                            helpfulMessage = '<br><br><strong>How to add Elementor colors:</strong><br>1. Go to Elementor → Site Settings<br>2. Click "Design System" → "Global Colors"<br>3. Add your brand colors<br>4. Return here to see them analyzed';
                                        }

                                        $('#results-container').html('<div style="text-align: center; padding: 40px; color: var(--jimr-danger); font-weight: 600;">Error: ' + escapeHtml(errorMsg) + '<div style="font-weight: normal; color: var(--clr-txt); margin-top: 12px; line-height: 1.4;">' + helpfulMessage + '</div></div>');
                                        updateSummary('Error: ' + errorMsg);
                                        $('#manual-scan').show(); // Show retry button on error
                                    }
                                }).fail(function(xhr, status, error) {
                                    console.error('❌ AJAX request failed:', status, error, xhr);
                                    let errorMessage = 'Connection failed';

                                    if (status === 'timeout') {
                                        errorMessage = 'Request timed out - server may be slow';
                                    } else if (xhr.status === 0) {
                                        errorMessage = 'Network error - check internet connection';
                                    } else if (xhr.status === 403) {
                                        errorMessage = 'Permission denied - check user privileges';
                                    } else if (xhr.status === 404) {
                                        errorMessage = 'Endpoint not found - plugin may not be active';
                                    } else if (xhr.responseText) {
                                        errorMessage = 'Server error: ' + xhr.responseText.substring(0, 100);
                                    }

                                    $('#results-container').html('<div style="text-align: center; padding: 40px; color: var(--jimr-danger); font-weight: 600;">Failed to load Elementor colors.<br><small style="font-weight: normal; color: var(--clr-txt);">' + errorMessage + '</small></div>');
                                    updateSummary('Connection failed');
                                    $('#manual-scan').show(); // Show retry button on failure
                                });
                            }

                            function switchCSSTab(tabName) {
                                activeCSSTab = tabName;

                                // Update tab visual state using new CSS classes
                                $('.css-tab-button').removeClass('active');
                                $(`[data-css-tab="${tabName}"]`).addClass('active');

                                // Update title
                                const titles = {
                                    'vars': 'CSS Variables',
                                    'classes': 'CSS Classes',
                                    'scss': 'SCSS Variables',
                                    'fallback': 'CSS with Fallbacks'
                                };
                                $('#css-output-title').text(titles[tabName] || 'CSS Output');

                                // Regenerate CSS for new tab
                                generateCSS();
                            }

                            function generateCSS() {
                                if (colorData.length === 0) {
                                    $('#css-output').text('/* No colors available */');
                                    return;
                                }

                                let css = '';

                                switch (activeCSSTab) {
                                    case 'vars':
                                        css = generateCSSVariables();
                                        break;
                                    case 'classes':
                                        css = generateCSSClasses();
                                        break;
                                    case 'scss':
                                        css = generateSCSSVariables();
                                        break;
                                    case 'fallback':
                                        css = generateCSSWithFallbacks();
                                        break;
                                }

                                $('#css-output').text(css);
                            }

                            function generateCSSVariables() {
                                let css = '/* Elementor Global Colors as CSS Variables */\n';
                                css += '/* Add to your theme\'s style.css or child theme stylesheet */\n\n';
                                css += ':root {\n';

                                colorData.forEach(color => {
                                    css += `  ${color.css_var}: ${color.hex}; /* ${color.title} */\n`;
                                });

                                css += '}\n\n';
                                css += '/* Usage Examples:\n';
                                css += ' * color: var(--e-global-color-primary);\n';
                                css += ' * background-color: var(--e-global-color-secondary);\n';
                                css += ' * border-color: var(--e-global-color-accent);\n';
                                css += ' */';

                                return css;
                            }

                            function generateCSSClasses() {
                                let css = '/* Elementor Color Utility Classes */\n';
                                css += '/* Add to your theme\'s style.css or use in Elementor Custom CSS */\n\n';

                                // Text colors
                                css += '/* Text Colors */\n';
                                colorData.forEach(color => {
                                    const className = color.id.replace(/[^a-zA-Z0-9]/g, '-');
                                    css += `.text-${className} {\n  color: ${color.hex} !important;\n}\n\n`;
                                });

                                // Background colors
                                css += '/* Background Colors */\n';
                                colorData.forEach(color => {
                                    const className = color.id.replace(/[^a-zA-Z0-9]/g, '-');
                                    css += `.bg-${className} {\n  background-color: ${color.hex} !important;\n}\n\n`;
                                });

                                // Border colors
                                css += '/* Border Colors */\n';
                                colorData.forEach(color => {
                                    const className = color.id.replace(/[^a-zA-Z0-9]/g, '-');
                                    css += `.border-${className} {\n  border-color: ${color.hex} !important;\n}\n\n`;
                                });

                                return css;
                            }

                            function generateSCSSVariables() {
                                let css = '// Elementor Global Colors as SCSS Variables\n';
                                css += '// Add to your SCSS build process\n\n';

                                colorData.forEach(color => {
                                    const variableName = color.id.replace(/[^a-zA-Z0-9]/g, '-');
                                    css += `$color-${variableName}: ${color.hex}; // ${color.title}\n`;
                                });

                                css += '\n// Usage Examples:\n';
                                css += '// color: $color-primary;\n';
                                css += '// background-color: $color-secondary;\n';
                                css += '// border-color: $color-accent;\n';

                                return css;
                            }

                            function generateCSSWithFallbacks() {
                                let css = '/* Elementor Colors with Fallbacks */\n';
                                css += '/* Provides fallback values for better browser compatibility */\n\n';

                                css += ':root {\n';
                                colorData.forEach(color => {
                                    css += `  ${color.css_var}: ${color.hex};\n`;
                                });
                                css += '}\n\n';

                                css += '/* Utility Classes with Fallbacks */\n';
                                colorData.forEach(color => {
                                    const className = color.id.replace(/[^a-zA-Z0-9]/g, '-');
                                    css += `.elementor-${className} {\n`;
                                    css += `  color: ${color.hex};\n`;
                                    css += `  color: var(${color.css_var});\n`;
                                    css += '}\n\n';
                                });

                                return css;
                            }

                            function copyCSS() {
                                const cssText = $('#css-output').text();

                                if (!cssText || cssText.includes('Loading CSS') || cssText.includes('No colors')) {
                                    alert('No CSS available to copy');
                                    return;
                                }

                                copyToClipboard(cssText, $('#copy-css-btn')[0]);
                            }

                            function exportData(type) {
                                if (colorData.length === 0) {
                                    alert('No data to export');
                                    return;
                                }

                                const form = $('<form>', {
                                    method: 'POST',
                                    action: ajaxurl
                                });

                                form.append($('<input>', {
                                    type: 'hidden',
                                    name: 'action',
                                    value: 'elementor_color_export'
                                }));

                                form.append($('<input>', {
                                    type: 'hidden',
                                    name: 'nonce',
                                    value: '<?php echo wp_create_nonce('elementor_color_nonce'); ?>'
                                }));

                                form.append($('<input>', {
                                    type: 'hidden',
                                    name: 'color_data',
                                    value: JSON.stringify(colorData)
                                }));

                                $('body').append(form);
                                form.submit();
                                form.remove();
                            }

                            function updateSummary(message) {
                                $('#summary-content').html(`<div style="text-align: center; color: var(--clr-txt); font-style: italic;">${message}</div>`);
                            }

                            function displayColors() {
                                if (colorData.length === 0) {
                                    $('#results-container').html('<div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">No Elementor colors found.</div>');
                                    updateSummary('No colors found');
                                    return;
                                }

                                // Group colors by type
                                const systemColors = colorData.filter(color => color.type === 'system');
                                const customColors = colorData.filter(color => color.type === 'custom');

                                let html = '';

                                // System Colors Section
                                if (systemColors.length > 0) {
                                    html += '<div style="margin-bottom: 32px;">';
                                    html += '<div style="background: var(--clr-secondary); color: #FAF9F6; padding: 16px 20px; margin: 0 0 20px 0; font-size: var(--jimr-font-lg); font-weight: 600; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3); border-radius: var(--jimr-border-radius-lg) var(--jimr-border-radius-lg) 0 0;">System Colors (' + systemColors.length + ')</div>';
                                    html += '<div class="color-grid">';
                                    systemColors.forEach(color => {
                                        html += generateColorCard(color);
                                    });
                                    html += '</div></div>';
                                }

                                // Custom Colors Section
                                if (customColors.length > 0) {
                                    html += '<div style="margin-bottom: 32px;">';
                                    html += '<div style="background: var(--clr-secondary); color: #FAF9F6; padding: 16px 20px; margin: 0 0 20px 0; font-size: var(--jimr-font-lg); font-weight: 600; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3); border-radius: var(--jimr-border-radius-lg) var(--jimr-border-radius-lg) 0 0;">Custom Colors (' + customColors.length + ')</div>';
                                    html += '<div class="color-grid">';
                                    customColors.forEach(color => {
                                        html += generateColorCard(color);
                                    });
                                    html += '</div></div>';
                                }

                                $('#results-container').html(html);

                                // Update summary
                                let summaryHtml = '';
                                summaryHtml += '<div class="summary-item"><span>System Colors:</span><span>' + systemColors.length + '</span></div>';
                                summaryHtml += '<div class="summary-item"><span>Custom Colors:</span><span>' + customColors.length + '</span></div>';

                                const totalUsage = colorData.reduce((sum, color) => sum + color.usage_count, 0);
                                summaryHtml += '<div class="summary-item"><span>Total Usage:</span><span>' + totalUsage + ' instances</span></div>';
                                summaryHtml += '<div class="summary-item"><span>Total Colors:</span><span>' + colorData.length + '</span></div>';

                                $('#summary-content').html(summaryHtml);

                                // Add click handlers for copying values
                                $('.color-value').on('click', function() {
                                    copyToClipboard($(this).text(), this);
                                });

                                $('.color-swatch').on('click', function() {
                                    const hex = $(this).data('hex');
                                    copyToClipboard(hex, this);
                                });
                            }

                            function generateColorCard(color) {
                                const rgbString = 'rgb(' + color.rgb.r + ', ' + color.rgb.g + ', ' + color.rgb.b + ')';
                                const hslString = 'hsl(' + color.hsl.h + ', ' + color.hsl.s + '%, ' + color.hsl.l + '%)';

                                let html = '<div class="color-card">';

                                // Color swatch with name
                                html += '<div class="color-swatch" style="background-color: ' + color.hex + '; color: ' + color.contrast_text + ';" data-hex="' + color.hex + '" title="Click to copy HEX">';
                                html += escapeHtml(color.title);
                                html += '</div>';

                                // Color info
                                html += '<div class="color-info">';

                                html += '<div class="color-details">';

                                // Left column: Color coding
                                html += '<div style="display: flex; flex-direction: column; gap: 8px;">';
                                html += '<div class="color-detail"><span class="color-detail-label">HEX:</span> <span class="color-value">' + color.hex + '</span></div>';
                                html += '<div class="color-detail"><span class="color-detail-label">RGB:</span> <span class="color-value">' + rgbString + '</span></div>';
                                html += '<div class="color-detail"><span class="color-detail-label">HSL:</span> <span class="color-value">' + hslString + '</span></div>';
                                html += '</div>';

                                // Right column: ID and CSS Variable
                                html += '<div style="display: flex; flex-direction: column; gap: 8px;">';
                                html += '<div class="color-detail"><span class="color-detail-label">ID:</span> <span class="color-value">' + color.id + '</span></div>';
                                html += '<div class="color-detail"><span class="color-detail-label">Ele CSS Var:</span> <span class="color-value">' + color.css_var + '</span></div>';
                                html += '</div>';

                                html += '</div>';

                                // Usage info
                                html += '<div class="color-usage">';
                                html += '<span class="usage-count">Used ' + color.usage_count + ' times</span>';
                                if (color.used_on.length > 0) {
                                    html += '<div style="margin-top: 6px; font-size: var(--jimr-font-xs); color: var(--jimr-gray-600);">';
                                    const limitedUsage = color.used_on.slice(0, 3);
                                    limitedUsage.forEach(usage => {
                                        html += '<div>' + escapeHtml(usage.title) + ' (' + usage.type + ')</div>';
                                    });
                                    if (color.used_on.length > 3) {
                                        html += '<div>... and ' + (color.used_on.length - 3) + ' more</div>';
                                    }
                                    html += '</div>';
                                }
                                html += '</div>';

                                html += '</div>';
                                html += '</div>';

                                return html;
                            }

                            function copyToClipboard(text, element) {
                                navigator.clipboard.writeText(text).then(function() {
                                    showCopyNotification('Copied: ' + text);
                                    if (element) {
                                        showButtonSuccess(element);
                                    }
                                }).catch(function() {
                                    // Fallback for older browsers
                                    const textArea = document.createElement('textarea');
                                    textArea.value = text;
                                    document.body.appendChild(textArea);
                                    textArea.select();
                                    document.execCommand('copy');
                                    document.body.removeChild(textArea);
                                    showCopyNotification('Copied: ' + text);
                                    if (element) {
                                        showButtonSuccess(element);
                                    }
                                });
                            }

                            function showButtonSuccess(button) {
                                if (!button) return;

                                const $button = $(button);
                                $button.addClass('success');

                                setTimeout(() => {
                                    $button.removeClass('success');
                                }, 1500);
                            }

                            function showCopyNotification(message) {
                                const notification = $('<div class="copy-notification">' + escapeHtml(message) + '</div>');
                                $('body').append(notification);

                                setTimeout(function() {
                                    notification.addClass('show');
                                }, 10);

                                setTimeout(function() {
                                    notification.removeClass('show');
                                    setTimeout(function() {
                                        notification.remove();
                                    }, 300);
                                }, 2000);
                            }

                            function escapeHtml(text) {
                                const div = document.createElement('div');
                                div.textContent = text;
                                return div.innerHTML;
                            }
                            // ========================================================================
                            // CONTRAST ANALYSIS FUNCTIONS
                            // ========================================================================

                            // Populate contrast dropdowns with available colors
                            function populateContrastDropdowns() {
                                const bgDropdown = $('#bg-color-dropdown');
                                const fgDropdown = $('#fg-color-dropdown');

                                // Clear existing options except first
                                bgDropdown.find('option:not(:first)').remove();
                                fgDropdown.find('option:not(:first)').remove();

                                // Add colors from inventory
                                colorData.forEach(color => {
                                    const option = `<option value="${color.hex}" data-name="${color.title}">${color.title}</option>`;
                                    bgDropdown.append(option);
                                    fgDropdown.append(option);
                                });
                            }

                            // Calculate contrast ratio between two colors
                            function calculateContrastRatio(color1, color2) {
                                const rgb1 = elementor_color_hex_to_rgb_js(color1);
                                const rgb2 = elementor_color_hex_to_rgb_js(color2);

                                const l1 = getRelativeLuminance(rgb1.r, rgb1.g, rgb1.b);
                                const l2 = getRelativeLuminance(rgb2.r, rgb2.g, rgb2.b);

                                const lighter = Math.max(l1, l2);
                                const darker = Math.min(l1, l2);

                                return (lighter + 0.05) / (darker + 0.05);
                            }

                            // Convert hex to RGB for JavaScript
                            function elementor_color_hex_to_rgb_js(hex) {
                                hex = hex.replace('#', '');
                                if (hex.length === 3) {
                                    hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
                                }
                                return {
                                    r: parseInt(hex.substr(0, 2), 16),
                                    g: parseInt(hex.substr(2, 2), 16),
                                    b: parseInt(hex.substr(4, 2), 16)
                                };
                            }

                            // Calculate relative luminance
                            function getRelativeLuminance(r, g, b) {
                                const [rs, gs, bs] = [r, g, b].map(c => {
                                    c = c / 255;
                                    return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
                                });
                                return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
                            }

                            // Get contrast text color (black or white)
                            function elementor_color_get_contrast_text_js(hex) {
                                const rgb = elementor_color_hex_to_rgb_js(hex);
                                const brightness = (rgb.r * 299 + rgb.g * 587 + rgb.b * 114) / 1000;
                                return brightness > 128 ? '#000000' : '#FFFFFF';
                            }

                            function updateContrastAnalysis() {
                                const bgColor = $('#bg-color-picker').val();
                                const fgColor = $('#fg-color-picker').val();

                                console.log('Getting colors:', {
                                    bgColor,
                                    fgColor
                                });

                                // Validate colors
                                if (!bgColor || !fgColor) {
                                    console.log('Missing colors, using defaults');
                                    return;
                                }

                                // Calculate contrast ratio
                                const ratio = calculateContrastRatio(bgColor, fgColor);
                                console.log('Calculated ratio:', ratio);
                                // Update preview
                                $('#contrast-preview').css({
                                    'background-color': bgColor,
                                    'color': fgColor
                                });

                                // Also apply foreground color to text elements inside preview
                                $('#contrast-preview h3, #contrast-preview p').css({
                                    'color': fgColor
                                });

                                // Update ratio display
                                $('#contrast-ratio-value').text(ratio.toFixed(2) + ':1');

                                // Update compliance indicators
                                updateComplianceIndicators(ratio);

                                // Update suggested colors
                                updateSuggestedColors();
                            }

                            // Calculate and update suggested compliant colors based on current foreground
                            function updateSuggestedColors() {
                                const bgColor = $('#bg-color-picker').val();
                                const fgColor = $('#fg-color-picker').val();

                                if (!bgColor || !fgColor) return;

                                console.log('Background:', bgColor, 'Foreground:', fgColor);

                                // Check current foreground color compliance
                                const currentRatio = calculateContrastRatio(bgColor, fgColor);
                                console.log('Current ratio:', currentRatio.toFixed(2));

                                // AA Suggestion (4.5:1 required)
                                let aaColor = fgColor;
                                let aaRatio = currentRatio;

                                if (currentRatio < 4.5) {
                                    // Need to adjust current color to meet AA compliance
                                    aaColor = adjustColorForCompliance(bgColor, fgColor, 4.5);
                                    aaRatio = calculateContrastRatio(bgColor, aaColor);
                                }

                                // AAA Suggestion (7:1 required)
                                let aaaColor = fgColor;
                                let aaaRatio = currentRatio;

                                if (currentRatio < 7) {
                                    // Need to adjust current color to meet AAA compliance
                                    aaaColor = adjustColorForCompliance(bgColor, fgColor, 7);
                                    aaaRatio = calculateContrastRatio(bgColor, aaaColor);
                                }

                                console.log('AA suggestion:', aaColor, 'ratio:', aaRatio.toFixed(2));
                                console.log('AAA suggestion:', aaaColor, 'ratio:', aaaRatio.toFixed(2));

                                // Update AA suggestion
                                $('#aa-color-swatch').css('background-color', aaColor);
                                $('#aa-color-hex').text(aaColor.toUpperCase());
                                $('#aa-color-ratio').text(aaRatio.toFixed(1) + ':1');

                                // Update AAA suggestion
                                $('#aaa-color-swatch').css('background-color', aaaColor);
                                $('#aaa-color-hex').text(aaaColor.toUpperCase());
                                $('#aaa-color-ratio').text(aaaRatio.toFixed(1) + ':1');

                                // Color the swatch borders based on whether adjustment was needed
                                const aaBorderColor = (aaColor === fgColor) ? 'var(--clr-success)' : 'var(--clr-accent)';
                                const aaaBorderColor = (aaaColor === fgColor) ? 'var(--clr-success)' : 'var(--clr-accent)';

                                $('#aa-color-swatch').css('border-color', aaBorderColor);
                                $('#aaa-color-swatch').css('border-color', aaaBorderColor);
                            }

                            // Adjust a color to meet specific contrast ratio requirement
                            function adjustColorForCompliance(bgColor, fgColor, targetRatio) {
                                const bgRgb = elementor_color_hex_to_rgb_js(bgColor);
                                const fgRgb = elementor_color_hex_to_rgb_js(fgColor);

                                // Determine if we need to go darker or lighter
                                const bgLuminance = getRelativeLuminance(bgRgb.r, bgRgb.g, bgRgb.b);

                                // If background is light, we need darker foreground
                                // If background is dark, we need lighter foreground
                                const shouldGoDarker = bgLuminance > 0.5;

                                let adjustedColor = fgColor;
                                let bestRatio = calculateContrastRatio(bgColor, fgColor);

                                // Try adjusting in steps until we meet the target ratio
                                for (let step = 0; step < 100; step++) {
                                    let testRgb;

                                    if (shouldGoDarker) {
                                        // Make darker by reducing RGB values
                                        const factor = 1 - (step * 0.05);
                                        testRgb = {
                                            r: Math.max(0, Math.round(fgRgb.r * factor)),
                                            g: Math.max(0, Math.round(fgRgb.g * factor)),
                                            b: Math.max(0, Math.round(fgRgb.b * factor))
                                        };
                                    } else {
                                        // Make lighter by increasing RGB values
                                        const factor = 1 + (step * 0.05);
                                        testRgb = {
                                            r: Math.min(255, Math.round(fgRgb.r * factor)),
                                            g: Math.min(255, Math.round(fgRgb.g * factor)),
                                            b: Math.min(255, Math.round(fgRgb.b * factor))
                                        };
                                    }

                                    const testHex = rgbToHex(testRgb.r, testRgb.g, testRgb.b);
                                    const testRatio = calculateContrastRatio(bgColor, testHex);

                                    console.log(`Step ${step}: ${testHex} ratio: ${testRatio.toFixed(2)}`);

                                    if (testRatio >= targetRatio) {
                                        adjustedColor = testHex;
                                        bestRatio = testRatio;
                                        break;
                                    }

                                    // If we've hit pure black or white, stop
                                    if ((shouldGoDarker && testRgb.r === 0 && testRgb.g === 0 && testRgb.b === 0) ||
                                        (!shouldGoDarker && testRgb.r === 255 && testRgb.g === 255 && testRgb.b === 255)) {
                                        adjustedColor = testHex;
                                        break;
                                    }
                                }

                                console.log(`Final adjusted color: ${adjustedColor} with ratio: ${bestRatio.toFixed(2)} (target: ${targetRatio})`);
                                return adjustedColor;
                            }

                            // Helper function to convert RGB to hex
                            function rgbToHex(r, g, b) {
                                return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1).toUpperCase();
                            }

                            // Apply suggested AA color
                            function applyBestAAColor() {
                                const aaColor = $('#aa-color-hex').text();
                                $('#fg-color-picker').val(aaColor);
                                $('#fg-color-button').text(aaColor);
                                $('#fg-color-dropdown').val(''); // Reset dropdown to "Choose from Global Colors..."
                                updateContrastAnalysis();
                                showCopyNotification('Applied AA compliant color: ' + aaColor);
                            }

                            // Apply suggested AAA color  
                            function applyBestAAAColor() {
                                const aaaColor = $('#aaa-color-hex').text();
                                $('#fg-color-picker').val(aaaColor);
                                $('#fg-color-button').text(aaaColor);
                                $('#fg-color-dropdown').val(''); // Reset dropdown to "Choose from Global Colors..."
                                updateContrastAnalysis();
                                showCopyNotification('Applied AAA compliant color: ' + aaaColor);
                            }

                            function updateComplianceIndicators(ratio) {
                                console.log('Updating compliance with ratio:', ratio);

                                const indicators = [{
                                        id: 'wcag-aa-normal-text',
                                        threshold: 4.5,
                                        name: 'AA Normal'
                                    },
                                    {
                                        id: 'wcag-aa-large-text',
                                        threshold: 3,
                                        name: 'AA Large'
                                    },
                                    {
                                        id: 'wcag-aaa-normal-text',
                                        threshold: 7,
                                        name: 'AAA Normal'
                                    },
                                    {
                                        id: 'wcag-aaa-large-text',
                                        threshold: 4.5,
                                        name: 'AAA Large'
                                    }
                                ];

                                indicators.forEach(indicator => {
                                    const element = $('#' + indicator.id);
                                    const passes = ratio >= indicator.threshold;

                                    console.log(`${indicator.name}: ${ratio} >= ${indicator.threshold} = ${passes}`);

                                    if (passes) {
                                        element.css('background', 'var(--clr-success)').css('color', 'white').text('Yes');
                                    } else {
                                        element.css('background', 'var(--jimr-danger)').css('color', 'white').text('No');
                                    }
                                });
                            }

                            // Color picker change handlers
                            $('#bg-color-picker, #fg-color-picker').on('change input', function() {
                                const isBackground = $(this).attr('id') === 'bg-color-picker';
                                const color = $(this).val().toUpperCase();
                                const buttonId = isBackground ? '#bg-color-button' : '#fg-color-button';

                                console.log('Color changed:', color, 'Button ID:', buttonId);

                                // Update button text
                                $(buttonId).text(color);
                                updateContrastAnalysis();
                            });

                            // Double-click handlers for suggested colors
                            $('#aa-color-swatch').on('dblclick', function() {
                                console.log('AA swatch double-clicked');
                                applyBestAAColor();
                            });

                            $('#aaa-color-swatch').on('dblclick', function() {
                                console.log('AAA swatch double-clicked');
                                applyBestAAAColor();
                            });

                            // Initialize button text on page load
                            function initializeButtonText() {
                                const bgColor = $('#bg-color-picker').val().toUpperCase();
                                const fgColor = $('#fg-color-picker').val().toUpperCase();

                                $('#bg-color-button').text(bgColor);
                                $('#fg-color-button').text(fgColor);

                                console.log('Initialized button text - BG:', bgColor, 'FG:', fgColor);
                            }
                            // Dropdown change handlers (combo box functionality)
                            $('#bg-color-dropdown, #fg-color-dropdown').on('change', function() {
                                const isBackground = $(this).attr('id') === 'bg-color-dropdown';
                                const selectedOption = $(this).find(':selected');
                                const color = selectedOption.val();
                                const name = selectedOption.data('name');

                                if (color && color !== '') {
                                    const pickerId = isBackground ? '#bg-color-picker' : '#fg-color-picker';
                                    const buttonId = isBackground ? '#bg-color-button' : '#fg-color-button';

                                    // Apply the selected color
                                    $(pickerId).val(color);
                                    $(buttonId).text(color.toUpperCase());
                                    updateContrastAnalysis();
                                }
                            });

                            // Swatch click handlers
                            $('.contrast-swatch').on('click', function() {
                                const isBackground = $(this).attr('id') === 'bg-color-swatch';
                                const pickerId = isBackground ? '#bg-color-picker' : '#fg-color-picker';
                                $(pickerId).trigger('click');
                            });

                            // Update contrast functionality when colors are loaded
                            const originalDisplayColors = displayColors;
                            displayColors = function() {
                                originalDisplayColors();
                                populateContrastDropdowns();
                                updateContrastAnalysis();
                            };

                        });
                    </script>
                <?php
            }
                ?>