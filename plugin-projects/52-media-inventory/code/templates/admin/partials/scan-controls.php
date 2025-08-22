<?php

/**
 * Scan controls template
 * @package MediaInventoryForge
 * @subpackage Templates
 * @since 2.0.0 
 * 
 * Provides the scan control buttons, progress bar, and summary stats section. 
 */

// Prevent direct access
defined('ABSPATH') || exit;
?>

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