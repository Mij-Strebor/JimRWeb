<?php

/**
 * Enhanced Cache Purging Functionality
 * 
 * This code implements a comprehensive admin bar button that allows administrators
 * to manually purge various WordPress caches with a single click.
 * 
 * Features:
 * - Adds "Purge Cache" button to the admin bar
 * - Restricts functionality to administrators only
 * - CSRF protection with WordPress nonces
 * - Supports multiple caching systems (WordPress, plugins, OPcache)
 * - Modern UI with non-intrusive notifications
 * - Comprehensive error handling and logging
 * - Optimized script loading
 * - Internationalization ready
 * 
 * @package Enhanced_Cache_Purge
 * @version 2.0.0
 * @author Your Name
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Cache Purge Class
 * 
 * Encapsulates all cache purging functionality in a clean class structure
 */
class Enhanced_Cache_Purge
{

    /**
     * Class constructor - Initialize hooks
     */
    public function __construct()
    {
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize the plugin functionality
     */
    public function init()
    {
        // Only load for administrators
        if (! current_user_can('manage_options')) {
            return;
        }

        add_action('admin_bar_menu', array($this, 'add_purge_cache_button'), 999);
        add_action('admin_footer', array($this, 'add_purge_cache_assets'));
        add_action('wp_footer', array($this, 'add_purge_cache_assets'));
        add_action('wp_ajax_purge_cache', array($this, 'handle_purge_cache'));
    }

    /**
     * Register the "Purge Cache" button in the WordPress admin bar
     * 
     * @param WP_Admin_Bar $wp_admin_bar The WordPress admin bar object
     */
    public function add_purge_cache_button($wp_admin_bar)
    {
        // Double-check permissions
        if (! current_user_can('manage_options')) {
            return;
        }

        $args = array(
            'id'    => 'purge-cache',
            'title' => __('Purge Cache', 'enhanced-cache-purge'),
            'href'  => '#',
            'meta'  => array(
                'class' => 'purge-cache-button',
                'title' => __('Clear all caches', 'enhanced-cache-purge')
            )
        );

        $wp_admin_bar->add_node($args);
    }

    /**
     * Add CSS, JavaScript, and HTML assets for the cache purging functionality
     * Only loads when admin bar is showing
     */
    public function add_purge_cache_assets()
    {
        // Only load if admin bar is showing and user has permissions
        if (! is_admin_bar_showing() || ! current_user_can('manage_options')) {
            return;
        }

        $this->add_cache_purge_styles();
        $this->add_cache_purge_scripts();
        $this->add_notification_container();
    }

    /**
     * Add CSS styles for the cache purge functionality
     */
    private function add_cache_purge_styles()
    {
?>
        <style id="cache-purge-styles">
            .cache-notification {
                position: fixed;
                top: 32px;
                right: 20px;
                background: #00a32a;
                color: white;
                padding: 12px 20px;
                border-radius: 4px;
                z-index: 100000;
                display: none;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                font-size: 14px;
                max-width: 300px;
                word-wrap: break-word;
            }

            .cache-notification.error {
                background: #d63638;
            }

            .cache-notification.info {
                background: #72aee6;
            }

            #wp-admin-bar-purge-cache.cache-loading a {
                opacity: 0.6;
                pointer-events: none;
                position: relative;
            }

            #wp-admin-bar-purge-cache.cache-loading a:after {
                content: ' ⟳';
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                from {
                    transform: rotate(0deg);
                }

                to {
                    transform: rotate(360deg);
                }
            }

            /* Mobile responsiveness */
            @media screen and (max-width: 782px) {
                .cache-notification {
                    top: 46px;
                    right: 10px;
                    max-width: calc(100% - 20px);
                    font-size: 13px;
                }
            }
        </style>
    <?php
    }

    /**
     * Add JavaScript for handling cache purge actions
     */
    private function add_cache_purge_scripts()
    {
        // Create nonce for security
        $nonce = wp_create_nonce('purge_cache_nonce');

        // Localize script data
        $script_data = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => $nonce,
            'strings' => array(
                'confirm'     => __('Are you sure you want to purge all caches?', 'enhanced-cache-purge'),
                'success'     => __('Cache cleared successfully!', 'enhanced-cache-purge'),
                'error'       => __('An error occurred while purging cache.', 'enhanced-cache-purge'),
                'network'     => __('Network error occurred. Please try again.', 'enhanced-cache-purge'),
                'loading'     => __('Purging cache...', 'enhanced-cache-purge'),
                'security'    => __('Security check failed. Please refresh and try again.', 'enhanced-cache-purge')
            )
        );
    ?>

        <script id="cache-purge-script">
            (function($) {
                'use strict';

                // Script data
                const cacheData = <?php echo wp_json_encode($script_data); ?>;

                $(document).ready(function() {
                    initCachePurge();
                });

                /**
                 * Initialize cache purge functionality
                 */
                function initCachePurge() {
                    $('#wp-admin-bar-purge-cache').on('click', handleCachePurgeClick);
                }

                /**
                 * Handle click on cache purge button
                 */
                function handleCachePurgeClick(e) {
                    e.preventDefault();

                    if (!confirm(cacheData.strings.confirm)) {
                        return;
                    }

                    const button = $(this);

                    // Show loading state
                    button.addClass('cache-loading');
                    showNotification(cacheData.strings.loading, 'info');

                    // Perform AJAX request
                    $.ajax({
                        url: cacheData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'purge_cache',
                            nonce: cacheData.nonce
                        },
                        timeout: 30000, // 30 second timeout
                        success: function(response) {
                            handleSuccess(response);
                        },
                        error: function(xhr, status, error) {
                            handleError(xhr, status, error);
                        },
                        complete: function() {
                            button.removeClass('cache-loading');
                        }
                    });
                }

                /**
                 * Handle successful AJAX response
                 */
                function handleSuccess(response) {
                    if (response.success) {
                        let message = response.data.message;

                        // Add detailed info if available
                        if (response.data.cleared && response.data.cleared.length > 0) {
                            message += ' (' + response.data.cleared.join(', ') + ')';
                        }

                        showNotification(message, 'success');

                        // Log to console for debugging
                        if (response.data.cleared) {
                            console.log('Cache types cleared:', response.data.cleared);
                        }
                    } else {
                        const errorMsg = response.data || cacheData.strings.error;
                        showNotification(errorMsg, 'error');
                    }
                }

                /**
                 * Handle AJAX errors
                 */
                function handleError(xhr, status, error) {
                    let message = cacheData.strings.network;

                    if (status === 'timeout') {
                        message = 'Request timed out. Cache may still be purging.';
                    } else if (xhr.status === 403) {
                        message = cacheData.strings.security;
                    }

                    showNotification(message, 'error');
                    console.error('Cache purge error:', status, error);
                }

                /**
                 * Show notification to user
                 */
                function showNotification(message, type = 'success') {
                    const notification = $('#cache-notification');

                    // Remove existing classes and add new type
                    notification.removeClass('error info success').addClass(type);
                    notification.text(message);

                    // Show notification
                    notification.fadeIn(300);

                    // Auto-hide after delay (longer for errors)
                    const delay = type === 'error' ? 5000 : 3000;
                    setTimeout(function() {
                        notification.fadeOut(300);
                    }, delay);
                }

            })(jQuery);
        </script>
<?php
    }

    /**
     * Add notification container HTML
     */
    private function add_notification_container()
    {
        echo '<div id="cache-notification" class="cache-notification"></div>';
    }

    /**
     * Handle the AJAX cache purge request
     * Server-side handler with comprehensive cache clearing
     */
    public function handle_purge_cache()
    {
        // Verify nonce for security
        if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'purge_cache_nonce')) {
            $this->log_cache_action('Security check failed: Invalid nonce');
            wp_send_json_error(__('Security check failed', 'enhanced-cache-purge'));
        }

        // Verify user permissions
        if (! current_user_can('manage_options')) {
            $this->log_cache_action('Unauthorized cache purge attempt');
            wp_send_json_error(__('Insufficient permissions', 'enhanced-cache-purge'));
        }

        try {
            $cleared_caches = $this->purge_all_caches();

            $this->log_cache_action('Cache successfully purged. Types: ' . implode(', ', $cleared_caches));

            wp_send_json_success(array(
                'message' => __('Cache cleared successfully!', 'enhanced-cache-purge'),
                'cleared' => $cleared_caches,
                'timestamp' => current_time('mysql')
            ));
        } catch (Exception $e) {
            $this->log_cache_action('Cache purge error: ' . $e->getMessage());
            wp_send_json_error(__('Cache purge failed', 'enhanced-cache-purge'));
        }
    }

    /**
     * Purge all available cache types
     * 
     * @return array List of successfully cleared cache types
     */
    private function purge_all_caches()
    {
        $cleared = array();

        // WordPress object cache
        if (wp_cache_flush()) {
            $cleared[] = __('WordPress Object Cache', 'enhanced-cache-purge');
        }

        // Clear popular caching plugins
        $cleared = array_merge($cleared, $this->clear_plugin_caches());

        // Clear OPcache if available
        if (function_exists('opcache_reset') && opcache_reset()) {
            $cleared[] = __('OPcache', 'enhanced-cache-purge');
        }

        // Clear file-based cache if exists
        if ($this->clear_file_cache()) {
            $cleared[] = __('File Cache', 'enhanced-cache-purge');
        }

        // Clear rewrite rules cache
        flush_rewrite_rules(false);
        $cleared[] = __('Rewrite Rules', 'enhanced-cache-purge');

        // Allow other plugins to hook in
        do_action('enhanced_cache_purge_complete', $cleared);

        return $cleared;
    }

    /**
     * Clear caches from popular caching plugins
     * 
     * @return array List of cleared plugin caches
     */
    private function clear_plugin_caches()
    {
        $cleared = array();

        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
            $cleared[] = __('WP Super Cache', 'enhanced-cache-purge');
        }

        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
            $cleared[] = __('W3 Total Cache', 'enhanced-cache-purge');
        }

        // WP Rocket
        if (function_exists('wp_rocket_clean_domain')) {
            wp_rocket_clean_domain();
            $cleared[] = __('WP Rocket', 'enhanced-cache-purge');
        }

        // LiteSpeed Cache
        if (class_exists('LiteSpeed\Purge')) {
            do_action('litespeed_purge_all');
            $cleared[] = __('LiteSpeed Cache', 'enhanced-cache-purge');
        }

        // WP Fastest Cache
        if (class_exists('WpFastestCache')) {
            do_action('wpfc_clear_all_cache');
            $cleared[] = __('WP Fastest Cache', 'enhanced-cache-purge');
        }

        // Autoptimize
        if (class_exists('autoptimizeCache')) {
            autoptimizeCache::clearall();
            $cleared[] = __('Autoptimize Cache', 'enhanced-cache-purge');
        }

        return $cleared;
    }

    /**
     * Clear file-based cache directories
     * 
     * @return bool True if file cache was cleared
     */
    private function clear_file_cache()
    {
        $cache_paths = array(
            WP_CONTENT_DIR . '/cache/',
            WP_CONTENT_DIR . '/wp-rocket-config/',
            ABSPATH . 'wp-content/cache/'
        );

        $cleared = false;
        foreach ($cache_paths as $path) {
            if (is_dir($path) && is_writable($path)) {
                $this->recursive_delete($path, false);
                $cleared = true;
            }
        }

        return $cleared;
    }

    /**
     * Recursively delete files in a directory
     * 
     * @param string $dir Directory path
     * @param bool $delete_dir Whether to delete the directory itself
     */
    private function recursive_delete($dir, $delete_dir = true)
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->recursive_delete($path);
            } else {
                unlink($path);
            }
        }

        if ($delete_dir) {
            rmdir($dir);
        }
    }

    /**
     * Log cache-related actions for debugging
     * 
     * @param string $message Log message
     */
    private function log_cache_action($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $user_id = get_current_user_id();
            $user_info = $user_id ? " (User ID: {$user_id})" : " (No user)";

            error_log("[Enhanced Cache Purge] {$message}{$user_info}");
        }
    }
}

// Initialize the enhanced cache purge system
new Enhanced_Cache_Purge();

/**
 * Utility function to manually trigger cache purge from other code
 * 
 * @return array List of cleared cache types
 */
function enhanced_cache_purge_all()
{
    if (class_exists('Enhanced_Cache_Purge')) {
        $purge_instance = new Enhanced_Cache_Purge();
        return $purge_instance->purge_all_caches();
    }

    return array();
}

/**
 * Check if current user can purge cache
 * 
 * @return bool
 */
function can_user_purge_cache()
{
    return current_user_can('manage_options');
}
