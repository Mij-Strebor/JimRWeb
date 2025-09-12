<?php

/**
 * Media scanner core functionality for Media Inventory Forge
 * 
 * This class handles the scanning of media files in batches, processing each file 
 * to extract metadata, categorize files, and manage errors. It is designed to work 
 * with the MIF_File_Utils and MIF_File_Processor classes to ensure efficient and 
 * organized media inventory management.
 *   
 * @package MediaInventoryForge
 * @subpackage Core
 * @since 2.0.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

/**
 * Class MIF_Scanner
 * Handles scanning and processing of media attachments in batches.
 * Relies on MIF_File_Processor for file processing.
 */
class MIF_Scanner
{

    private $batch_size;
    private $upload_dir;
    private $processed_count = 0;
    private $errors = [];
    private $file_processor;

    /**
     * Constructor
     */
    public function __construct($batch_size = 10)
    {
        $this->batch_size = max(1, min(50, intval($batch_size)));
        $this->upload_dir = wp_upload_dir();
        $this->file_processor = new MIF_File_Processor();
    }

    /**
     * Scan a batch of attachments
     */
    public function scan_batch($offset)
    {
        // Set time and memory limits for batch processing
        $this->prepare_environment();

        $attachments = $this->get_attachments($offset);
        $inventory_data = [];

        foreach ($attachments as $attachment_id) {
            try {
                $item_data = $this->process_attachment($attachment_id);
                if ($item_data) {
                    $inventory_data[] = $item_data;
                    $this->processed_count++;
                }
            } catch (Exception $e) {
                $this->log_error($attachment_id, $e->getMessage());
            }
        }

        $total_attachments = $this->get_total_attachments();
        $processed_total = min($offset + $this->batch_size, $total_attachments);

        return [
            'data' => $inventory_data,
            'offset' => $offset + $this->batch_size,
            'total' => $total_attachments,
            'complete' => $processed_total >= $total_attachments,
            'processed' => $processed_total,
            'errors' => $this->errors,
            'batch_size' => $this->batch_size,
            'current_batch_count' => count($inventory_data)
        ];
    }

    /**
     * Prepare environment for batch processing
     */
    private function prepare_environment()
    {
        // Set time limit (30 seconds)
        if (!ini_get('safe_mode')) {
            set_time_limit(30);
        }

        // Raise memory limit if possible
        wp_raise_memory_limit('admin');

        // Clear any existing errors for this batch
        $this->errors = [];
    }

    /**
     * Get attachments for current batch
     */
    private function get_attachments($offset)
    {
        $args = [
            'post_type' => 'attachment',
            'posts_per_page' => $this->batch_size,
            'offset' => $offset,
            'fields' => 'ids',
            'post_status' => 'inherit',
            'no_found_rows' => true, // Performance optimization
            'update_post_meta_cache' => false, // Performance optimization
            'update_post_term_cache' => false  // Performance optimization
        ];

        return get_posts($args);
    }

    /**
     * Process individual attachment
     */
    private function process_attachment($attachment_id)
    {
        $file_path = get_attached_file($attachment_id);
        $mime_type = get_post_mime_type($attachment_id);
        $title = get_the_title($attachment_id);

        // Validate attachment data
        if (!$this->file_processor->validate_attachment_data($attachment_id, $file_path, $mime_type)) {
            $this->log_error($attachment_id, "Invalid attachment data: missing file path or MIME type");
            return null;
        }

        // Process the file
        try {
            return $this->file_processor->process_file($attachment_id, $file_path, $mime_type, $title);
        } catch (Exception $e) {
            $this->log_error($attachment_id, "File processing failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get total number of attachments
     */
    private function get_total_attachments()
    {
        $counts = wp_count_posts('attachment');
        return isset($counts->inherit) ? $counts->inherit : 0;
    }

    /**
     * Log error for specific attachment
     */
    private function log_error($attachment_id, $message)
    {
        $error_msg = "Attachment ID {$attachment_id}: {$message}";
        $this->errors[] = $error_msg;

        // Log to WordPress error log if debug logging is enabled
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[Media Inventory Forge] ' . $error_msg);
        }
    }

    /**
     * Get current error list
     */
    public function get_errors()
    {
        return $this->errors;
    }
}
