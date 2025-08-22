<?php

/**
 * Main admin page template for Media Inventory Forge
 * 
 * @package MediaInventoryForge
 * @subpackage Templates
 * @since 2.0.0
 * 
 * Combines scan controls, results display, and about section.
 */

// Prevent direct access
defined('ABSPATH') || exit;
?>

/* Main Admin Page Template */
<div class="wrap" style="background: var(--clr-page-bg); padding: 20px; min-height: 100vh;">
    <div class="fcc-header-section" style="width: 1280px; margin: 0 auto;">
        <h1 class="text-2xl font-bold mb-4">
            <?php echo esc_html(get_admin_page_title()); ?> (<?php echo MIF_VERSION; ?>)
        </h1><br>

        <?php include MIF_PLUGIN_DIR . 'templates/admin/partials/about-section.php'; ?>

        <!-- Main Container -->
        <div class="media-inventory-container" style="margin: 0 auto; background: var(--clr-card-bg); border-radius: var(--jimr-border-radius-lg); box-shadow: var(--clr-shadow-xl); overflow: hidden; border: 2px solid var(--clr-primary);">
            <div style="padding: 20px;">

                <?php include MIF_PLUGIN_DIR . 'templates/admin/partials/scan-controls.php'; ?>
                <?php include MIF_PLUGIN_DIR . 'templates/admin/partials/results-section.php'; ?>

            </div>
        </div>
    </div>
</div>