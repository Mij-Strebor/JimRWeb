<?php

/**
 * File utilities for Media Inventory Forge
 * 
 * This class provides methods for handling file operations such as formatting sizes,
 * determining file categories, extracting font family names, and validating file paths.
 * It is designed to work with WordPress uploads and ensure files are accessible and properly categorized.
 * 
 * @package MediaInventoryForge
 * @subpackage Utilities
 * @since 2.0.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

class MIF_File_Utils
{

    /**
     * Format file sizes in human-readable format
     */
    public static function format_bytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = ($bytes > 0) ? floor(log($bytes) / log(1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get file category based on MIME type
     */
    public static function get_category($mime_type)
    {
        if ($mime_type === 'image/svg+xml') return 'SVG';
        if (strpos($mime_type, 'image/') === 0) return 'Images';
        if (strpos($mime_type, 'video/') === 0) return 'Videos';
        if (strpos($mime_type, 'audio/') === 0) return 'Audio';
        if (strpos($mime_type, 'application/pdf') === 0) return 'PDFs';
        if (strpos($mime_type, 'font/') === 0 || strpos($mime_type, 'application/font') === 0) return 'Fonts';

        // Office documents
        if (in_array($mime_type, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ])) {
            return 'Documents';
        }

        if (strpos($mime_type, 'text/') === 0) return 'Text Files';
        if (strpos($mime_type, 'application/') === 0) return 'Other Documents';
        return 'Other';
    }

    /**
     * Extract font family name from filename or title
     */
    public static function get_font_family($title, $filename)
    {
        $name = !empty($title) ? $title : pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/[-_\s]?(regular|bold|italic|light|medium|heavy|black|thin|extralight|semibold|extrabold)[-_\s]?/i', '', $name);
        $name = preg_replace('/\.(woff2?|ttf|otf|eot)$/i', '', $name);
        $name = preg_replace('/[-_\s]*\d+[-_\s]*/', '', $name);
        $name = trim($name, '-_ ');
        $name = ucwords(str_replace(['-', '_'], ' ', $name));
        return !empty($name) ? $name : 'Unknown Font';
    }

    /**
     * Validate file path is within uploads directory
     */
    public static function is_valid_upload_path($file_path)
    {
        $upload_dir = wp_upload_dir();
        $upload_basedir = $upload_dir['basedir'];
        $real_file_path = realpath($file_path);
        $real_upload_path = realpath($upload_basedir);

        if (!$real_file_path || !$real_upload_path) {
            return false;
        }

        return strpos($real_file_path, $real_upload_path) === 0;
    }

    /**
     * Sanitize file path for display
     */
    public static function sanitize_file_path($file_path, $upload_basedir)
    {
        return str_replace($upload_basedir, '', $file_path);
    }

    /**
     * Check if file exists and is readable
     */
    public static function is_file_accessible($file_path)
    {
        return file_exists($file_path) && is_readable($file_path);
    }

    /**
     * Get safe file size (handles errors gracefully)
     */
    public static function get_safe_file_size($file_path)
    {
        if (!self::is_file_accessible($file_path)) {
            return 0;
        }

        $size = filesize($file_path);
        return $size !== false ? $size : 0;
    }
}
