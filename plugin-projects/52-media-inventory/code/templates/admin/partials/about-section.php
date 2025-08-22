<?php

/**
 * About section template
 * @package MediaInventoryForge
 * @subpackage Templates
 * @since 2.0.0
 * 
 * Provides an expandable "About" section with plugin details and features.
 */

// Prevent direct access
defined('ABSPATH') || exit;
?>

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