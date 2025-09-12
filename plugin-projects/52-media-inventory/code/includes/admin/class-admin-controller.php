<?php

/**
 * Admin controller for Media Inventory Forge
 * 
 * @package MediaInventoryForge
 * @subpackage Admin
 * @since 2.0.0
 */

defined('ABSPATH') || exit;

/**
 * Class MIF_Admin_Controller
 * Handles admin menu, AJAX requests for scanning and exporting media inventory.
 * Relies on MIF_Scanner and MIF_File_Processor for core functionality.
 */
class MIF_Admin_Controller
{
    // Constructor
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_media_inventory_scan', [$this, 'ajax_scan']);
        add_action('wp_ajax_media_inventory_export', [$this, 'ajax_export']);
    }

    // Add admin menu and submenu
    public function add_admin_menu()
    {
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

        if (!$j_forge_exists) {
            add_menu_page(
                'J Forge',
                'J Forge',
                'manage_options',
                'j-forge',
                '__return_null',
                'dashicons-admin-tools',
                12
            );
        }

        add_submenu_page(
            'j-forge',
            'Media Inventory Forge',
            'Media Inventory',
            'manage_options',
            'media-inventory',
            [$this, 'admin_page']
        );
    }

    // AJAX handler for scanning media
    public function ajax_scan()
    {
        check_ajax_referer('media_inventory_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }

        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 10;

        if ($offset < 0) {
            wp_send_json_error('Invalid offset parameter');
            return;
        }

        if ($batch_size < 1 || $batch_size > 50) {
            wp_send_json_error('Invalid batch size parameter');
            return;
        }

        try {
            $scanner = new MIF_Scanner($batch_size);
            $result = $scanner->scan_batch($offset);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                $result['debug'] = [
                    'memory_usage' => memory_get_usage(true),
                    'memory_peak' => memory_get_peak_usage(true)
                ];
            }

            wp_send_json_success($result);
        } catch (Exception $e) {
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('[Media Inventory Forge] Scan failed: ' . $e->getMessage());
            }

            wp_send_json_error('Scan failed: ' . $e->getMessage());
        }
    }

    //
    public function ajax_export()
    {
        check_ajax_referer('media_inventory_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }

        $inventory_data = json_decode(stripslashes($_POST['inventory_data']), true);

        if (empty($inventory_data)) {
            wp_die('No data to export');
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="media-inventory-' . date('Y-m-d-H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

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
                $details .= ' - ' . MIF_File_Utils::format_bytes($file['size']);
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
                MIF_File_Utils::format_bytes($item['total_size']),
                implode(' | ', $file_details)
            ]);
        }

        fclose($output);
        exit;
    }

    // Render admin page
    public function admin_page()
    {
        include MIF_PLUGIN_DIR . 'templates/admin/main-page.php';
    }
}
