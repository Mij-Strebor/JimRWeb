<?php

/**
 * Space Clamp Calculator
 * Version: 1.0
 * Generates responsive space using CSS clamp() functions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Space Clamp Calculator - Complete Unified Class
 */
class SpaceClampCalculator
{
    // ========================================================================
    // CORE CONSTANTS SYSTEM
    // ========================================================================

    // Configuration Constants
    const VERSION = '1.0';
    const PLUGIN_SLUG = 'space-clamp-calculator';
    const NONCE_ACTION = 'space_clamp_nonce';

    // Validation Ranges
    // Why 1-100px: Prevents unusably small (<1px) or absurdly large (>100px) base space
    const MIN_BASE_space_RANGE = [1, 100];
    // Why 200-5000px: Covers feature phones to ultra-wide displays safely
    const VIEWPORT_RANGE = [200, 5000];
    // Why 1.0-3.0: Below 1.0 shrinks space, above 3.0 creates extreme jumps
    const SCALE_RANGE = [1.0, 3.0];

    // Default Values - PRIMARY CONSTANTS
    // Why 8px: Common design system base unit - divisible by 2, 4, 8
    const DEFAULT_MIN_BASE_space = 8;
    // Why 12px: 50% larger than default - provides good scaling contrast
    const DEFAULT_MAX_BASE_space = 12;
    // Why 375px: iPhone SE width - covers smallest modern mobile devices
    const DEFAULT_MIN_VIEWPORT = 375;
    // Why 1620px: Laptop/desktop sweet spot - before ultra-wide displays
    const DEFAULT_MAX_VIEWPORT = 1620;
    // Why 1.125: Major Second ratio - subtle but noticeable space differences
    const DEFAULT_MIN_SCALE = 1.125;
    // Why 1.25: Major Third ratio - creates clear space hierarchy
    const DEFAULT_MAX_SCALE = 1.25;

    // Browser and system constants
    // Why 16px: Universal browser default - foundation for rem calculations
    const BROWSER_DEFAULT_FONT_SIZE = 16;
    // Why 16px base: 1rem = 16px by default - critical for rem/px conversions
    const CSS_UNIT_CONVERSION_BASE = 16;

    // Valid Options
    const VALID_UNITS = ['px', 'rem'];
    const VALID_TABS = ['class', 'vars', 'utils'];

    // WordPress Options Keys
    const OPTION_SETTINGS = 'space_clamp_settings';
    const OPTION_CLASS_SIZES = 'space_clamp_class_sizes';
    const OPTION_VARIABLE_SIZES = 'space_clamp_variable_sizes';
    const OPTION_UTILITY_SIZES = 'space_clamp_utility_sizes';

    // ========================================================================
    // CLASS PROPERTIES
    // ========================================================================

    private $default_settings;
    private $default_class_sizes;
    private $default_variable_sizes;
    private $default_utility_sizes;
    private $assets_loaded = false;

    // ========================================================================
    // CORE INITIALIZATION
    // ========================================================================

    /**
     * Constructor - Initialize the complete system
     */
    public function __construct()
    {
        $this->init_defaults();
        $this->init_hooks();
    }

    /**
     * Initialize default values using factory methods
     */
    private function init_defaults()
    {
        $this->default_settings = $this->create_default_settings();
        $this->default_class_sizes = $this->create_default_sizes('class');
        $this->default_variable_sizes = $this->create_default_sizes('vars');
        $this->default_utility_sizes = $this->create_default_sizes('utils');
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_save_space_clamp_settings', [$this, 'save_settings']);
        add_action('admin_footer', [$this, 'render_unified_assets'], 10);
    }

    // ========================================================================
    // DATA MANAGEMENT METHODS
    // ========================================================================

    /**
     * Create default settings array using constants
     */
    private function create_default_settings()
    {
        return [
            'minBasespace' => self::DEFAULT_MIN_BASE_space,
            'maxBasespace' => self::DEFAULT_MAX_BASE_space,
            'minViewport' => self::DEFAULT_MIN_VIEWPORT,
            'maxViewport' => self::DEFAULT_MAX_VIEWPORT,
            'unitType' => 'px',
            'selectedClassSizeId' => 3,
            'selectedVariableSizeId' => 3,
            'selectedUtilitySizeId' => 3,
            'activeTab' => 'class',
            'minScale' => self::DEFAULT_MIN_SCALE,
            'maxScale' => self::DEFAULT_MAX_SCALE,
            'autosaveEnabled' => true
        ];
    }

    /**
     * Create default sizes array for specified type using constants
     */
    private function create_default_sizes($type)
    {
        $configs = [
            'class' => [
                ['id' => 1, 'name' => 'space-xs'],
                ['id' => 2, 'name' => 'space-sm'],
                ['id' => 3, 'name' => 'space-md'],
                ['id' => 4, 'name' => 'space-lg'],
                ['id' => 5, 'name' => 'space-xl'],
                ['id' => 6, 'name' => 'space-xxl']
            ],
            'vars' => [
                ['id' => 1, 'name' => '--space-xs'],
                ['id' => 2, 'name' => '--space-sm'],
                ['id' => 3, 'name' => '--space-md'],
                ['id' => 4, 'name' => '--space-lg'],
                ['id' => 5, 'name' => '--space-xl'],
                ['id' => 6, 'name' => '--space-xxl']
            ],
            'utils' => [
                ['id' => 1, 'name' => 'xs', 'properties' => ['margin', 'padding']],
                ['id' => 2, 'name' => 'sm', 'properties' => ['margin', 'padding']],
                ['id' => 3, 'name' => 'md', 'properties' => ['margin', 'padding']],
                ['id' => 4, 'name' => 'lg', 'properties' => ['margin', 'padding']],
                ['id' => 5, 'name' => 'xl', 'properties' => ['margin', 'padding']],
                ['id' => 6, 'name' => 'xxl', 'properties' => ['margin', 'padding']]
            ]
        ];

        if (!isset($configs[$type])) {
            error_log("Space Clamp: Invalid size type: {$type}");
            return [];
        }

        $config = $configs[$type];
        $property_name = $this->get_size_property_name($type);

        return array_map(function ($item) use ($property_name, $type) {
            $size = [
                'id' => $item['id'],
                $property_name => $item['name']
            ];

            // Add utility-specific properties
            if ($type === 'utils' && isset($item['properties'])) {
                $size['properties'] = $item['properties'];
            }

            return $size;
        }, $config);
    }

    /**
     * Get the property name for a size type
     */
    private function get_size_property_name($type)
    {
        $property_map = [
            'class' => 'className',
            'vars' => 'variableName',
            'utils' => 'utilityName'
        ];

        return $property_map[$type] ?? 'className';
    }

    // ========================================================================
    // ADMIN INTERFACE
    // ========================================================================

    /**
     * Add admin menu page
     */
    public function add_admin_menu()
    {
        add_menu_page(
            'Space Clamp Calculator',
            'Space Clamp',
            'manage_options',
            self::PLUGIN_SLUG,
            [$this, 'render_admin_page'],
            'dashicons-editor-expand',
            13
        );
    }

    /**
     * Unified asset enqueuing
     */
    public function enqueue_assets()
    {
        $screen = get_current_screen();

        if (!$screen || !isset($_GET['page']) || $_GET['page'] !== self::PLUGIN_SLUG) {
            return;
        }

        wp_enqueue_style(
            'space-clamp-tailwind',
            'https://cdn.tailwindcss.com',
            [],
            self::VERSION
        );

        // Use wp-util but ensure it's loaded properly
        wp_enqueue_script('wp-util');

        // Add our data to wp-util
        wp_localize_script('wp-util', 'spaceClampAjax', [
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'defaults' => [
                'minBasespace' => self::DEFAULT_MIN_BASE_space,
                'maxBasespace' => self::DEFAULT_MAX_BASE_space,
                'minViewport' => self::DEFAULT_MIN_VIEWPORT,
                'maxViewport' => self::DEFAULT_MAX_VIEWPORT,
            ],
            'data' => [
                'settings' => $this->get_space_clamp_settings(),
                'classSizes' => $this->get_space_clamp_class_sizes(),
                'variableSizes' => $this->get_space_clamp_variable_sizes(),
                'utilitySizes' => $this->get_space_clamp_utility_sizes()
            ],
            'constants' => $this->get_all_constants(),
            'version' => self::VERSION,
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);
    }

    /**
     * Get all constants for JavaScript access
     */
    public function get_all_constants()
    {
        return [
            'DEFAULT_MIN_BASE_space' => self::DEFAULT_MIN_BASE_space,
            'DEFAULT_MAX_BASE_space' => self::DEFAULT_MAX_BASE_space,
            'DEFAULT_MIN_VIEWPORT' => self::DEFAULT_MIN_VIEWPORT,
            'DEFAULT_MAX_VIEWPORT' => self::DEFAULT_MAX_VIEWPORT,
            'DEFAULT_MIN_SCALE' => self::DEFAULT_MIN_SCALE,
            'DEFAULT_MAX_SCALE' => self::DEFAULT_MAX_SCALE,
            'BROWSER_DEFAULT_FONT_SIZE' => self::BROWSER_DEFAULT_FONT_SIZE,
            'CSS_UNIT_CONVERSION_BASE' => self::CSS_UNIT_CONVERSION_BASE,
            'MIN_BASE_space_RANGE' => self::MIN_BASE_space_RANGE,
            'VIEWPORT_RANGE' => self::VIEWPORT_RANGE,
            'SCALE_RANGE' => self::SCALE_RANGE,
            'VALID_UNITS' => self::VALID_UNITS,
            'VALID_TABS' => self::VALID_TABS
        ];
    }

    // ========================================================================
    // DATA GETTERS
    // ========================================================================

    public function get_space_clamp_settings()
    {
        static $cached_settings = null;

        if ($cached_settings === null) {
            $settings = wp_parse_args(
                get_option(self::OPTION_SETTINGS, []),
                $this->default_settings
            );
            if (!in_array($settings['activeTab'], self::VALID_TABS)) {
                $settings['activeTab'] = 'class';
                update_option(self::OPTION_SETTINGS, $settings);
            }

            $cached_settings = $settings;
        }

        return $cached_settings;
    }

    public function get_space_clamp_class_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_CLASS_SIZES, $this->default_class_sizes);
        }
        return $cached_sizes;
    }

    public function get_space_clamp_variable_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_VARIABLE_SIZES, $this->default_variable_sizes);
        }
        return $cached_sizes;
    }

    public function get_space_clamp_utility_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_UTILITY_SIZES, $this->default_utility_sizes);
        }
        return $cached_sizes;
    }

    // ========================================================================
    // MAIN ADMIN PAGE RENDERER
    // ========================================================================

    /**
     * Main Admin Page Renderer - Complete Interface
     */
    public function render_admin_page()
    {
        $data = [
            'settings' => $this->get_space_clamp_settings(),
            'class_sizes' => $this->get_space_clamp_class_sizes(),
            'variable_sizes' => $this->get_space_clamp_variable_sizes(),
            'utility_sizes' => $this->get_space_clamp_utility_sizes()
        ];

        echo $this->get_complete_interface($data);
    }

    /**
     * Complete interface HTML
     */
    private function get_complete_interface($data)
    {
        $settings = $data['settings'];

        ob_start();
?>
        <div class="wrap" style="background: var(--clr-page-bg); padding: 20px; min-height: 100vh;">
            <h1 class="text-2xl font-bold mb-4">Space Clamp Calculator (1.0)</h1>

            <!-- About Section -->
            <div class="fcc-info-toggle-section">
                <button class="fcc-info-toggle expanded" data-toggle-target="about-content">
                    <span style="color: #FAF9F6 !important;">📐 About Space Clamp Calculator</span>
                    <span class="fcc-toggle-icon" style="color: #FAF9F6 !important;">▼</span>
                </button>
                <div class="fcc-info-content expanded" id="about-content">
                    <div style="color: var(--clr-txt); font-size: 14px; line-height: 1.6;">
                        <p style="margin: 0 0 16px 0; color: var(--clr-txt);">
                            Perfect companion to the Font Clamp Calculator! While typography scales smoothly across devices, your space should too. This tool generates responsive margins, padding, and gaps using CSS clamp() functions. Create consistent space systems that adapt beautifully from mobile to desktop.
                        </p>
                        <div style="background: rgba(60, 32, 23, 0.1); padding: 12px 16px; border-radius: 6px; border-left: 4px solid var(--clr-accent); margin-top: 20px;">
                            <p style="margin: 0; font-size: 13px; opacity: 0.95; line-height: 1.5; color: var(--clr-txt);">
                                Space Clamp Calculator by Jim R. (<a href="https://jimrweb.com" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">JimRWeb</a>), part of the CSS Tools series developed with Claude AI (<a href="https://anthropic.com" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">Anthropic</a>).
                            </p>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Main Section -->
            <div class="space-clamp-container" id="scc-main-container">
                <div style="padding: 20px;">

                    <!-- How to Use Panel -->
                    <div class="fcc-info-toggle-section">
                        <button class="fcc-info-toggle expanded" data-toggle-target="info-content">
                            <span style="color: #FAF9F6 !important;">ℹ️ How to Use Space Clamp Calculator</span>
                            <span class="fcc-toggle-icon" style="color: #FAF9F6 !important;">▼</span>
                        </button>
                        <div class="fcc-info-content expanded" id="info-content">
                            <div style="color: var(--clr-txt); font-size: 14px; line-height: 1.6;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 20px;">
                                    <div>
                                        <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">1. Configure Settings</h4>
                                        <p style="margin: 0; font-size: 13px; line-height: 1.5;">Set your space units, viewport range, and scaling ratios. Choose a base value that represents your base space size.</p>
                                    </div>
                                    <div>
                                        <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">2. Manage Space Sizes</h4>
                                        <p style="margin: 0; font-size: 13px; line-height: 1.5;">Use the enhanced table to add, edit, delete, or reorder your space sizes. Drag rows to reorder them in the table.</p>
                                    </div>
                                    <div>
                                        <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">3. Preview Results</h4>
                                        <p style="margin: 0; font-size: 13px; line-height: 1.5;">Use the enhanced preview with controls to see how your space will look at different screen sizes. The displays show scaled results at your Min Width and Max Width.</p>
                                    </div>
                                    <div>
                                        <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">4. Copy CSS</h4>
                                        <p style="margin: 0; font-size: 13px; line-height: 1.5;">Generate clamp() CSS functions ready to use in your projects. Available as classes, variables, or utility styles with enhanced copy functionality.</p>
                                    </div>
                                </div>

                                <div style="background: #F0E6DA; padding: 12px 16px; border-radius: 8px; border: 1px solid #5C3324; margin: 16px 0 0 0; text-align: center;">
                                    <h4 style="color: #3C2017; font-size: 14px; font-weight: 600; margin: 0 0 6px 0;">💡 Pro Tip</h4>
                                    <p style="margin: 0; font-size: 13px; color: var(--clr-txt);">Use consistent space scales to create harmonious layouts that maintain their proportions across all device sizes.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced Header Section -->
                    <div style="margin-bottom: 16px;">
                        <!-- Top Row: Autosave Status -->
                        <div style="display: flex; justify-content: flex-end; align-items: center; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div class="fcc-autosave-flex">
                                    <label data-tooltip="Automatically save changes as you make them">
                                        <input type="checkbox" id="autosave-toggle" <?php echo $settings['autosaveEnabled'] ? 'checked' : ''; ?> data-tooltip="Toggle automatic saving of your space settings">
                                        <span>Autosave</span>
                                    </label>
                                    <button id="save-btn" class="fcc-btn" data-tooltip="Save all current settings and sizes to database">
                                        Save
                                    </button>
                                    <div id="autosave-status" class="autosave-status idle">
                                        <span id="autosave-icon">💾</span>
                                        <span id="autosave-text">Ready</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Row: Large Tabs -->
                        <div style="display: flex; justify-content: center;">
                            <div class="fcc-tabs" style="width: 100%; max-width: 600px;">
                                <button id="class-tab" class="tab-button <?php echo $settings['activeTab'] === 'class' ? 'active' : ''; ?>" style="flex: 1; padding: 12px 24px; border-radius: 6px; font-size: 16px; font-weight: 600;" data-tab="class" data-tooltip="Generate space classes like .space-lg, .space-md for use in HTML">Classes</button>
                                <button id="vars-tab" class="tab-button <?php echo $settings['activeTab'] === 'vars' ? 'active' : ''; ?>" style="flex: 1; padding: 12px 24px; border-radius: 6px; font-size: 16px; font-weight: 600;" data-tab="vars" data-tooltip="Generate CSS custom properties like --space-lg for use with var() in CSS">Variables</button>
                                <button id="utils-tab" class="tab-button <?php echo $settings['activeTab'] === 'utils' ? 'active' : ''; ?>" style="flex: 1; padding: 12px 24px; border-radius: 6px; font-size: 16px; font-weight: 600;" data-tab="utils" data-tooltip="Generate utility classes like .mt-lg, .p-md for margins and padding">Utilities</button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Content Areas -->

                    <!-- Settings and Data Table - Side by Side -->
                    <div class="fcc-main-grid">
                        <!-- Column 1: Settings Panel -->
                        <div>
                            <div class="fcc-panel" style="margin-bottom: 8px;">
                                <h2 class="settings-title">Settings</h2>

                                <!-- space Units Selector -->
                                <div class="font-units-section">
                                    <label class="font-units-label">Select Space Units to Use:</label>
                                    <div class="font-units-buttons">
                                        <button id="px-tab" class="unit-button <?php echo $settings['unitType'] === 'px' ? 'active' : ''; ?>" data-unit="px"
                                            aria-label="Use pixel units for space - more predictable but less accessible"
                                            aria-pressed="<?php echo $settings['unitType'] === 'px' ? 'true' : 'false'; ?>"
                                            data-tooltip="Use pixel units for space - more predictable but less accessible">PX</button>
                                        <button id="rem-tab" class="unit-button <?php echo $settings['unitType'] === 'rem' ? 'active' : ''; ?>" data-unit="rem"
                                            aria-label="Use rem units for space - scales with user's browser settings"
                                            aria-pressed="<?php echo $settings['unitType'] === 'rem' ? 'true' : 'false'; ?>"
                                            data-tooltip="Use rem units for space - scales with user's browser settings">REM</button>
                                    </div>
                                </div>

                                <!-- Row 1: Min Base and Min Width -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                                    <div class="grid-item">
                                        <label class="component-label" for="min-base-space">Min Viewport Space (px)</label>
                                        <input type="number" id="min-base-space" value="<?php echo esc_attr($settings['minBasespace'] ?? self::DEFAULT_MIN_BASE_space); ?>"
                                            class="component-input" style="width: 100%;"
                                            min="<?php echo self::MIN_BASE_space_RANGE[0]; ?>"
                                            max="<?php echo self::MIN_BASE_space_RANGE[1]; ?>"
                                            step="1"
                                            aria-label="Minimum base space in pixels - base space at minimum viewport width"
                                            data-tooltip="Base space size at minimum viewport width">
                                    </div>
                                    <div class="grid-item">
                                        <label class="component-label" for="min-viewport">Min Viewport Width (px)</label>
                                        <input type="number" id="min-viewport" value="<?php echo esc_attr($settings['minViewport']); ?>"
                                            class="component-input" style="width: 100%;"
                                            min="<?php echo self::VIEWPORT_RANGE[0]; ?>"
                                            max="<?php echo self::VIEWPORT_RANGE[1]; ?>"
                                            step="1"
                                            aria-label="Minimum viewport width in pixels - screen width where minimum space applies"
                                            data-tooltip="Screen width where minimum space applies">
                                    </div>
                                </div>

                                <!-- Row 2: Min Scale -->
                                <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px;">
                                    <div class="grid-item">
                                        <label class="component-label" for="min-scale">Min Viewport Space Scaling</label>
                                        <select id="min-scale" class="component-select" style="width: 100%;"
                                            aria-label="Minimum scale ratio for space on smaller screens - controls size differences between space levels"
                                            data-tooltip="space scale ratio for smaller screens - how much size difference between space levels">
                                            <option value="1.067" <?php selected($settings['minScale'], '1.067'); ?>>1.067 Minor Second</option>
                                            <option value="1.125" <?php selected($settings['minScale'], '1.125'); ?>>1.125 Major Second</option>
                                            <option value="1.200" <?php selected($settings['minScale'], '1.200'); ?>>1.200 Minor Third</option>
                                            <option value="1.250" <?php selected($settings['minScale'], '1.250'); ?>>1.250 Major Third</option>
                                            <option value="1.333" <?php selected($settings['minScale'], '1.333'); ?>>1.333 Perfect Fourth</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Row 3: Max Base and Max Width -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                                    <div class="grid-item">
                                        <label class="component-label" for="max-base-space">Max Viewport Space (px)</label>
                                        <input type="number" id="max-base-space" value="<?php echo esc_attr($settings['maxBasespace'] ?? self::DEFAULT_MAX_BASE_space); ?>"
                                            class="component-input" style="width: 100%;"
                                            min="<?php echo self::MIN_BASE_space_RANGE[0]; ?>"
                                            max="<?php echo self::MIN_BASE_space_RANGE[1]; ?>"
                                            step="1"
                                            aria-label="Maximum base space in pixels - base space at maximum viewport width"
                                            data-tooltip="Base space size at maximum viewport width">
                                    </div>
                                    <div class="grid-item">
                                        <label class="component-label" for="max-viewport">Max Viewport Width (px)</label>
                                        <input type="number" id="max-viewport" value="<?php echo esc_attr($settings['maxViewport']); ?>"
                                            class="component-input" style="width: 100%;"
                                            min="<?php echo self::VIEWPORT_RANGE[0]; ?>"
                                            max="<?php echo self::VIEWPORT_RANGE[1]; ?>"
                                            step="1"
                                            aria-label="Maximum viewport width in pixels - screen width where maximum space applies"
                                            data-tooltip="Screen width where maximum space applies">
                                    </div>
                                </div>

                                <!-- Row 4: Max Scale -->
                                <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px;">
                                    <div class="grid-item">
                                        <label class="component-label" for="max-scale">Max Viewport Space Scaling</label>
                                        <select id="max-scale" class="component-select" style="width: 100%;"
                                            aria-label="Maximum scale ratio for space on larger screens - controls how dramatic size differences are on big screens"
                                            data-tooltip="space scale ratio for larger screens - how dramatic the size differences should be on big screens">
                                            <option value="1.067" <?php selected($settings['maxScale'], '1.067'); ?>>1.067 Minor Second</option>
                                            <option value="1.125" <?php selected($settings['maxScale'], '1.125'); ?>>1.125 Major Second</option>
                                            <option value="1.200" <?php selected($settings['maxScale'], '1.200'); ?>>1.200 Minor Third</option>
                                            <option value="1.250" <?php selected($settings['maxScale'], '1.250'); ?>>1.250 Major Third</option>
                                            <option value="1.333" <?php selected($settings['maxScale'], '1.333'); ?>>1.333 Perfect Fourth</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Column 2: Space Size Classes (Data Table Panel) -->
                        <div>
                            <div class="fcc-panel" id="sizes-table-container">
                                <h2 style="margin-bottom: 12px;" id="table-title">Space Size Classes</h2>

                                <!-- Base Value and Action Buttons Row -->
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                    <!-- Left: Base Value Combo Box -->
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <label class="component-label" for="base-value" style="margin-bottom: 0; white-space: nowrap;">Base</label>
                                        <select id="base-value" class="component-select" style="width: 120px; height: 32px;"
                                            aria-label="Base reference size - used for calculating all other space sizes in the scale"
                                            data-tooltip="Reference size used for calculating other sizes - this will be your base space">
                                            <option value="3" selected>space-md</option>
                                        </select>
                                    </div>

                                    <!-- Right: Action Buttons -->
                                    <div class="fcc-table-buttons" id="table-action-buttons">
                                        <button id="add-size" class="fcc-btn">add size</button>
                                        <button id="reset-defaults" class="fcc-btn">reset</button>
                                        <button id="clear-sizes" class="fcc-btn fcc-btn-danger">clear all</button>
                                    </div>
                                </div>

                                <div id="sizes-table-wrapper">
                                    <table class="font-table">
                                        <thead>
                                            <tr id="table-header">
                                                <th style="width: 24px;">⋮</th>
                                                <th style="width: 75px;">Name</th>
                                                <th style="width: 70px;">Min Size</th>
                                                <th style="width: 70px;">Max Size</th>
                                                <th style="width: 20px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sizes-table">
                                            <tr>
                                                <td colspan="5" style="text-align: center; color: #6b7280; font-style: italic; padding: 40px 20px;">
                                                    Loading space data...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Full-Width Preview Section -->
                    <div class="fcc-preview-enhanced" style="clear: both; margin: 20px 0;">
                        <div class="fcc-preview-header-row">
                            <h2 style="color: var(--clr-primary); margin: 0;">Space Preview</h2>
                        </div>

                        <div class="fcc-preview-grid">
                            <div class="fcc-preview-column">
                                <div class="fcc-preview-column-header">
                                    <h3>Min Size (Small Screens)</h3>
                                    <div class="fcc-scale-indicator" id="min-viewport-display"><?php echo esc_html($settings['minViewport']); ?>px</div>
                                </div>
                                <div id="preview-min-container" style="background: white; border-radius: 8px; padding: 20px; border: 2px solid var(--clr-secondary); min-height: 320px; box-shadow: inset 0 2px 4px var(--clr-shadow);">
                                    <div style="text-align: center; color: var(--clr-txt); font-style: italic; padding: 60px 20px;">
                                        <div class="fcc-loading-spinner" style="width: 25px; height: 25px; margin: 0 auto 10px;"></div>
                                        <div>Generating space previews...</div>
                                    </div>
                                </div>
                            </div>

                            <div class="fcc-preview-column">
                                <div class="fcc-preview-column-header">
                                    <h3>Max Size (Large Screens)</h3>
                                    <div class="fcc-scale-indicator" id="max-viewport-display"><?php echo esc_html($settings['maxViewport']); ?>px</div>
                                </div>
                                <div id="preview-max-container" style="background: white; border-radius: 8px; padding: 20px; border: 2px solid var(--clr-secondary); min-height: 320px; box-shadow: inset 0 2px 4px var(--clr-shadow);">
                                    <div style="text-align: center; color: var(--clr-txt); font-style: italic; padding: 60px 20px;">
                                        <div class="fcc-loading-spinner" style="width: 25px; height: 25px; margin: 0 auto 10px;"></div>
                                        <div>Generating space previews...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enhanced CSS Output Containers -->
                    <div class="fcc-panel" style="margin-top: 8px;" id="selected-css-container">
                        <div class="fcc-css-header">
                            <h2 style="flex-grow: 1;" id="selected-code-title">Selected Class CSS</h2>
                            <div id="selected-copy-button">
                                <button id="copy-selected-btn" class="fcc-copy-btn"
                                    data-tooltip="Copy selected CSS to clipboard"
                                    aria-label="Copy selected CSS to clipboard"
                                    title="Copy CSS">
                                    <span class="copy-icon">📋</span> copy
                                </button>
                            </div>
                        </div>
                        <div style="background: white; border-radius: 6px; padding: 8px; border: 1px solid #d1d5db;">
                            <pre id="class-code" style="font-size: 12px; white-space: pre-wrap; color: #111827; margin: 0;">/* Loading CSS output... */</pre>
                        </div>
                    </div>

                    <div class="fcc-panel" style="margin-top: 8px;" id="generated-css-container">
                        <div class="fcc-css-header">
                            <h2 style="flex-grow: 1;" id="generated-code-title">Generated CSS (All Classes)</h2>
                            <div class="fcc-css-buttons" id="generated-copy-buttons">
                                <button id="copy-all-btn" class="fcc-copy-btn"
                                    data-tooltip="Copy all generated CSS to clipboard"
                                    aria-label="Copy all generated CSS to clipboard"
                                    title="Copy All CSS">
                                    <span class="copy-icon">📋</span> copy all
                                </button>
                            </div>
                        </div>
                        <div style="background: white; border-radius: 6px; padding: 8px; border: 1px solid #d1d5db; overflow: auto; max-height: 300px;">
                            <pre id="generated-code" style="font-size: 12px; white-space: pre-wrap; color: #111827; margin: 0;">/* Loading CSS output... */</pre>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Edit Modal -->
            <div id="edit-modal" class="fcc-modal">
                <div class="fcc-modal-dialog">
                    <div class="fcc-modal-header">
                        <span id="modal-title">Edit Size</span>
                        <button class="fcc-modal-close" id="modal-close-btn">×</button>
                    </div>
                    <div class="fcc-modal-content">
                        <div id="modal-form-content">
                            <!-- Form content will be inserted here -->
                        </div>
                        <div class="fcc-btn-group">
                            <button id="modal-cancel-btn" class="fcc-btn fcc-btn-ghost">Cancel</button>
                            <button id="modal-save-btn" class="fcc-btn">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    // ========================================================================
    // UNIFIED ASSET RENDERING
    // ========================================================================

    /**
     * Render all assets (CSS + JavaScript) in single method
     */
    public function render_unified_assets()
    {
        if (!$this->is_space_clamp_page() || $this->assets_loaded) {
            return;
        }

        $this->render_unified_css();
        $this->render_basic_javascript();

        $this->assets_loaded = true;
    }

    /**
     * Check if we're on the plugin page
     */
    private function is_space_clamp_page()
    {
        return isset($_GET['page']) && sanitize_text_field($_GET['page']) === self::PLUGIN_SLUG;
    }

    /**
     * Unified CSS (copied from Font Clamp Calculator and adapted)
     */
    private function render_unified_css()
    {
    ?>
        <style id="space-clamp-unified-styles">
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
                --jimr-success: #10b981;
                --jimr-success-dark: #059669;
                --jimr-danger: #ef4444;
                --jimr-danger-dark: #dc2626;
                --jimr-info: #3b82f6;
                --jimr-info-dark: #1d4ed8;
                --jimr-warning: #f59e0b;
                --jimr-warning-dark: #d97706;

                /* Badge Colors */
                --badge-active-bg: var(--jimr-success);
                --badge-active-text: white;
                --badge-inactive-bg: var(--jimr-gray-200);
                --badge-inactive-text: var(--jimr-gray-500);
                --badge-border: var(--jimr-gray-300);

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

            /* Layout styles for Base Value and Action Buttons */
            .fcc-table-buttons {
                display: flex;
                gap: var(--jimr-space-2);
                align-items: center;
                justify-content: flex-end;
            }

            /* Copy Button Specific Styles */
            .fcc-copy-btn {
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
                border: none;
                min-width: 80px;
                justify-content: center;
            }

            .fcc-copy-btn:hover {
                background: var(--clr-btn-hover);
                transform: translateY(-2px);
                box-shadow: var(--clr-shadow-lg);
            }

            .fcc-css-buttons {
                display: flex;
                gap: var(--jimr-space-2);
                align-items: center;
            }

            /* Input System */
            .fcc-input,
            .component-input,
            .component-select {
                height: 40px;
                padding: 10px 12px;
                border: 2px solid var(--clr-secondary);
                border-radius: var(--jimr-border-radius);
                font-size: var(--jimr-font-base);
                background: white;
                color: var(--clr-txt);
                transition: var(--jimr-transition-slow);
                box-sizing: border-box;
                margin: 0;
                width: 100%;
            }

            .fcc-input:focus,
            .component-input:focus,
            .component-select:focus {
                outline: none;
                border-color: var(--clr-accent);
                box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2), var(--clr-shadow);
                background: var(--clr-light);
                transform: translateY(-2px);
            }

            .component-select,
            select.fcc-input {
                cursor: pointer;
                appearance: none;
                background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235C3324' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right 8px center;
                background-size: 16px;
                padding-right: 32px;
            }

            /* Labels */
            label,
            .component-label,
            .fcc-label {
                color: var(--clr-txt);
                font-weight: 500;
                font-size: var(--jimr-font-sm);
                line-height: 1.3;
                margin-bottom: var(--jimr-space-2);
                display: block;
            }

            /* Drag & Drop Styles - Moderate Phase */
            .drag-handle {
                cursor: grab !important;
                user-select: none;
                padding: 4px;
                color: var(--jimr-gray-500);
                font-weight: bold;
                transition: color 0.2s ease;
            }

            .drag-handle:hover {
                color: var(--clr-secondary);
            }

            .drag-handle:active {
                cursor: grabbing !important;
            }

            .size-row.dragging {
                opacity: 0.5 !important;
                transform: rotate(2deg) !important;
                transition: all 0.2s ease !important;
            }

            .drag-placeholder {
                opacity: 0.3 !important;
                background-color: rgba(25, 118, 210, 0.1) !important;
                border: 2px dashed #1976d2 !important;
                transition: all 0.2s ease !important;
            }

            .drag-drop-zone {
                background: rgba(25, 118, 210, 0.05) !important;
                border-radius: var(--jimr-border-radius) !important;
            }

            /* Extension point: Enhanced animations for Complete phase */
            .drag-enhanced .size-row {
                transition: transform 0.3s ease, box-shadow 0.3s ease !important;
            }

            .drag-enhanced .size-row:hover {
                transform: translateY(-1px) !important;
                box-shadow: var(--clr-shadow-lg) !important;
            }

            /* Main Container */
            .space-clamp-container {
                margin: 0 auto;
                background: var(--clr-card-bg);
                border-radius: var(--jimr-border-radius-lg);
                box-shadow: var(--clr-shadow-xl);
                overflow: hidden;
                border: 2px solid var(--clr-primary);
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }

            .space-clamp-container.ready {
                opacity: 1;
                visibility: visible;
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

            /* Grid Layout */
            .fcc-main-grid {
                display: grid;
                grid-template-columns: 1fr 1.3fr;
                gap: var(--jimr-space-6);
                margin-bottom: var(--jimr-space-6);
            }


            .delete-size {
                background: none !important;
                border: none !important;
                cursor: pointer !important;
                font-size: 16px !important;
                padding: 4px !important;
                color: #dc3545 !important;
            }

            .delete-size:hover {
                background: rgba(220, 53, 69, 0.1) !important;
                border-radius: 4px !important;
            }

            @media (max-width: 1024px) {
                .fcc-main-grid {
                    grid-template-columns: 1fr;
                    gap: var(--jimr-space-4);
                }
            }

            .fcc-preview-enhanced {
                background: var(--clr-light);
                padding: var(--jimr-space-5);
                border-radius: var(--jimr-border-radius-lg);
                border: 2px solid var(--clr-secondary);
                box-shadow: var(--clr-shadow-lg);
                margin-top: var(--jimr-space-4);
                transition: var(--jimr-transition);
                clear: both;
                display: block;
            }

            .fcc-preview-header-row {
                text-align: left;
                margin-bottom: var(--jimr-space-5);
                border-bottom: 2px solid var(--clr-secondary);
                padding-bottom: var(--jimr-space-3);
            }

            .fcc-preview-header-row h2 {
                color: var(--clr-primary) !important;
                font-family: 'Georgia', serif !important;
                font-size: var(--jimr-font-2xl) !important;
                font-weight: 700 !important;
                margin: 0 !important;
                text-shadow: 1px 1px 2px var(--clr-shadow) !important;
                border: none !important;
            }

            .fcc-preview-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
                gap: var(--jimr-space-6);
                align-items: start;
            }

            .fcc-preview-column {
                display: flex;
                flex-direction: column;
                gap: var(--jimr-space-3);
            }

            .fcc-preview-column-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: var(--jimr-space-2);
                padding: var(--jimr-space-2) var(--jimr-space-3);
                background: rgba(92, 51, 36, 0.1);
                border-radius: var(--jimr-border-radius);
                border: 1px solid var(--clr-secondary);
            }

            .fcc-preview-column-header h3 {
                color: var(--clr-secondary) !important;
                font-size: var(--jimr-font-lg) !important;
                font-weight: 600 !important;
                margin: 0 !important;
                line-height: 1.2 !important;
            }

            .fcc-scale-indicator {
                background: var(--clr-secondary);
                color: #FAF9F6;
                border: 1px solid var(--clr-primary);
                padding: var(--jimr-space-1) var(--jimr-space-3);
                border-radius: var(--jimr-border-radius);
                font-size: var(--jimr-font-sm);
                font-weight: 600;
                font-style: normal;
                display: inline-block;
                box-shadow: var(--clr-shadow-sm);
                min-width: 60px;
                text-align: center;
                line-height: 1.2;
            }

            /* Tables */
            .font-table {
                border: none !important;
                border-collapse: collapse;
                table-layout: fixed;
                width: 100%;
                min-width: 520px;
                background: white;
                border-radius: var(--jimr-border-radius);
                overflow: hidden;
                box-shadow: var(--clr-shadow);
            }

            .font-table th,
            .font-table td {
                border: none !important;
                padding: var(--jimr-space-2) var(--jimr-space-2);
                overflow: hidden;
                text-overflow: ellipsis;
                font-size: var(--jimr-font-sm);
                line-height: 1.3;
            }

            .font-table th {
                font-size: var(--jimr-font-xs);
                font-weight: 600;
                background-color: var(--jimr-gray-100) !important;
                color: var(--jimr-gray-700);
                text-transform: uppercase;
                letter-spacing: 0.05em;
                vertical-align: top;
                border-bottom: 2px solid var(--jimr-gray-200) !important;
            }

            .font-table tbody tr:not(:last-child) td {
                border-bottom: 1px solid var(--jimr-gray-100) !important;
            }

            .size-name-input {
                border: none !important;
                background: transparent !important;
                width: 100% !important;
                padding: 4px 8px !important;
                color: var(--clr-txt) !important;
            }

            .font-table td:first-child {
                width: 30px !important;
                padding-right: 8px !important;
            }

            .font-table td:nth-child(2) {
                padding-left: 0 !important;
            }

            #sizes-table-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                width: 100%;
                max-width: 100%;
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

            /* Font Units */
            .font-units-section {
                margin-bottom: var(--jimr-space-5);
            }

            .font-units-label {
                display: block;
                font-size: var(--jimr-font-sm);
                font-weight: 500;
                color: var(--clr-txt);
                margin-bottom: var(--jimr-space-1);
            }

            .font-units-buttons {
                display: flex;
                border-radius: var(--jimr-border-radius-lg);
                overflow: hidden;
                border: 2px solid var(--clr-primary);
                box-shadow: var(--clr-shadow);
            }

            .unit-button {
                background: var(--clr-accent);
                color: #9C0202;
                border: none;
                width: 50%;
                padding: var(--jimr-space-2);
                text-align: center;
                font-size: var(--jimr-font-base);
                font-weight: 500;
                cursor: pointer;
                transition: var(--jimr-transition-slow);
            }

            .unit-button.active {
                background: var(--clr-secondary);
                color: #FAF9F6;
                font-weight: 700;
            }

            /* Tabs */
            .fcc-tabs {
                display: flex;
                gap: var(--jimr-space-1);
                background: var(--clr-primary);
                padding: 3px;
                border-radius: var(--jimr-border-radius-lg);
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .tab-button {
                background: var(--clr-accent);
                color: var(--clr-btn-txt);
                border: 2px solid var(--clr-btn-bdr);
                transition: var(--jimr-transition-slow);
                cursor: pointer;
                font-weight: 600;
                text-shadow: none;
                position: relative;
                overflow: hidden;
            }

            .tab-button:hover {
                background: var(--clr-btn-hover);
                color: var(--clr-btn-txt);
                border-color: var(--clr-btn-bdr);
                transform: translateY(-1px);
                box-shadow: var(--clr-shadow);
            }

            .tab-button.active {
                background: var(--clr-secondary) !important;
                color: #FAF9F6 !important;
                border-color: var(--clr-primary) !important;
                font-weight: 700 !important;
                box-shadow: 0 0 15px rgba(92, 51, 36, 0.4) !important;
            }

            /* Grid Items */
            .grid-item {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            /* Autosave */
            .fcc-autosave-flex {
                display: flex;
                align-items: center;
                gap: var(--jimr-space-4);
            }

            .fcc-autosave-flex label {
                display: flex;
                align-items: center;
                gap: var(--jimr-space-1);
                margin-bottom: 0;
                cursor: pointer;
            }

            .autosave-status {
                display: inline-flex;
                align-items: center;
                gap: var(--jimr-space-2);
                font-size: 0.8rem;
                padding: var(--jimr-space-1) var(--jimr-space-3);
                border-radius: var(--jimr-border-radius);
                transition: var(--jimr-transition-slow);
                font-weight: 500;
                border: 1px solid transparent;
            }

            .autosave-status.idle {
                background: var(--clr-card-bg);
                color: var(--clr-txt);
                border-color: var(--clr-secondary);
            }

            .autosave-status.saving {
                background: linear-gradient(135deg, var(--clr-accent), var(--clr-btn-hover));
                color: var(--clr-btn-txt);
                border-color: var(--clr-btn-bdr);
                animation: pulse 1.5s ease-in-out infinite;
            }

            .autosave-status.saved {
                background: linear-gradient(135deg, var(--jimr-success), var(--jimr-success-dark));
                color: white;
                border-color: #15803d;
            }

            .autosave-status.error {
                background: linear-gradient(135deg, var(--clr-btn-bdr), var(--jimr-danger));
                color: white;
                border-color: #991b1b;
            }

            @keyframes pulse {

                0%,
                100% {
                    opacity: 1;
                }

                50% {
                    opacity: 0.7;
                }
            }

            /* CSS Headers */
            .fcc-css-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: var(--jimr-space-3);
            }

            /* space Preview Styles */
            .space-preview-item {
                transition: all 0.2s ease !important;
                cursor: pointer !important;
            }

            .space-preview-item:hover {
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            }

            .space-preview-item.selected {
                background-color: rgba(59, 130, 246, 0.1) !important;
                border-color: rgba(59, 130, 246, 0.3) !important;
                box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
            }

            /* Modal Styles */
            .fcc-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            }

            .fcc-modal.show {
                display: flex;
            }

            .fcc-modal-dialog {
                background: var(--clr-card-bg);
                border-radius: var(--jimr-border-radius-lg);
                border: 2px solid var(--clr-primary);
                box-shadow: var(--clr-shadow-xl);
                width: 90%;
                max-width: 500px;
                max-height: 90vh;
                overflow: auto;
            }

            .fcc-modal-header {
                background: var(--clr-secondary);
                color: #FAF9F6;
                padding: var(--jimr-space-4);
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-weight: 600;
                font-size: var(--jimr-font-lg);
            }

            .fcc-modal-close {
                background: none;
                border: none;
                color: #FAF9F6;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: var(--jimr-transition);
            }

            .fcc-modal-close:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .fcc-modal-content {
                padding: var(--jimr-space-5);
            }

            .fcc-form-group {
                margin-bottom: var(--jimr-space-4);
            }

            .fcc-btn-group {
                display: flex;
                gap: var(--jimr-space-3);
                justify-content: flex-end;
                margin-top: var(--jimr-space-5);
            }

            /* Property Badge Styles */
            .property-badge {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: 600;
                text-align: center;
                border: 1px solid var(--badge-border);
                margin: 0 2px;
                min-width: 16px;
            }

            .property-badge.active {
                background: var(--badge-active-bg);
                color: var(--badge-active-text);
                border-color: var(--badge-active-bg);
            }

            .property-badge.inactive {
                background: var(--badge-inactive-bg);
                color: var(--badge-inactive-text);
            }

            /* Data Table Row Styles */

            /* Data Table Row Styles */
            .size-row {
                transition: all 0.2s ease !important;
                cursor: pointer !important;
                border: 1px solid transparent !important;
            }

            .size-row:hover {
                background-color: rgba(59, 130, 246, 0.05) !important;
                border-color: rgba(59, 130, 246, 0.2) !important;
                transform: translateX(2px) !important;
            }

            .size-row.selected {
                background-color: rgba(59, 130, 246, 0.25) !important;
                border-color: rgba(59, 130, 246, 0.5) !important;
                box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.4) !important;
            }

            .size-row.selected:hover {
                background-color: rgba(59, 130, 246, 0.15) !important;
            }

            /* Drag and Drop Styles */
            .size-row.dragging {
                opacity: 0.5 !important;
            }

            .drag-handle {
                cursor: grab !important;
            }

            .drag-handle:active {
                cursor: grabbing !important;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .fcc-main-grid {
                    grid-template-columns: 1fr;
                    gap: var(--jimr-space-4);
                }

                .fcc-tabs {
                    justify-content: center;
                }

                .fcc-info-content div[style*="grid-template-columns: 1fr 1fr"] {
                    grid-template-columns: 1fr !important;
                    gap: var(--jimr-space-4) !important;
                }

                .fcc-preview-grid {
                    grid-template-columns: 1fr;
                    gap: var(--jimr-space-4);
                }
            }

            #scc-main-container {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }

            .space-clamp-container {
                display: block !important;
            }
        </style>
    <?php
    }

    /**
     * Basic JavaScript (just toggle functionality for now)
     */ /**
     * Basic JavaScript (fixed version)
     */
    private function render_basic_javascript()
    {
    ?>
        <script id="space-clamp-basic-script">
            console.log('✅ JavaScript is loading');

            // Function to attach event listeners (can be called multiple times)
            function attachEventListeners() {
                // Settings input listeners
                const spaceInputs = ['min-base-space', 'max-base-space', 'min-viewport', 'max-viewport', 'min-scale', 'max-scale'];
                spaceInputs.forEach(inputId => {
                    const input = document.getElementById(inputId);
                    if (input) {
                        // Remove existing listener to prevent duplicates
                        input.removeEventListener('input', handleSettingsChange);
                        input.addEventListener('input', handleSettingsChange);
                    }
                });

                // Base combo box listener
                const baseSelect = document.getElementById('base-value');
                if (baseSelect) {
                    baseSelect.removeEventListener('change', handleBaseChange);
                    baseSelect.addEventListener('change', handleBaseChange);
                }

                // Unit button listeners (PX/REM)
                const unitButtons = document.querySelectorAll('.unit-button');
                unitButtons.forEach(button => {
                    button.removeEventListener('click', handleUnitChange);
                    button.addEventListener('click', handleUnitChange);
                });

                // Edit button listeners
                const editButtons = document.querySelectorAll('.edit-size');
                editButtons.forEach(button => {
                    button.removeEventListener('click', handleEditClick);
                    button.addEventListener('click', handleEditClick);
                });

                // Add Size button listener
                const addSizeBtn = document.getElementById('add-size');
                if (addSizeBtn) {
                    addSizeBtn.removeEventListener('click', handleAddSize);
                    addSizeBtn.addEventListener('click', handleAddSize);
                }

                // Add First Size button listener (for empty state)
                const addFirstSizeBtn = document.getElementById('add-first-size');
                if (addFirstSizeBtn) {
                    addFirstSizeBtn.removeEventListener('click', handleAddSize);
                    addFirstSizeBtn.addEventListener('click', handleAddSize);
                }

                // Reset button listener
                const resetBtn = document.getElementById('reset-defaults');
                if (resetBtn) {
                    resetBtn.removeEventListener('click', handleReset);
                    resetBtn.addEventListener('click', handleReset);
                }

                // Reset to Defaults button listener (for empty state)
                const resetToDefaultsBtn = document.getElementById('reset-to-defaults');
                if (resetToDefaultsBtn) {
                    resetToDefaultsBtn.removeEventListener('click', handleReset);
                    resetToDefaultsBtn.addEventListener('click', handleReset);
                }

                // Delete button listeners
                const deleteButtons = document.querySelectorAll('.delete-size');
                deleteButtons.forEach(button => {
                    button.removeEventListener('click', handleDelete);
                    button.addEventListener('click', handleDelete);
                });

                // Clear All button listener
                const clearAllBtn = document.getElementById('clear-sizes');
                if (clearAllBtn) {
                    clearAllBtn.removeEventListener('click', handleClearAll);
                    clearAllBtn.addEventListener('click', handleClearAll);
                }

                // Save button click handler (from FCC, adapted for SCC)
                const saveBtn = document.getElementById('save-btn');
                if (saveBtn) {
                    saveBtn.removeEventListener('click', handleSaveButton);
                    saveBtn.addEventListener('click', handleSaveButton);
                }

                // Autosave toggle listener (from FCC)
                const autosaveToggle = document.getElementById('autosave-toggle');
                if (autosaveToggle) {
                    autosaveToggle.removeEventListener('change', handleAutosaveToggle);
                    autosaveToggle.addEventListener('change', handleAutosaveToggle);

                    // Check initial state and start autosave if already enabled
                    if (autosaveToggle.checked) {
                        startAutosaveTimer();
                    }
                }

                // Modal close listeners (only attach if modal exists)
                const modalCloseBtn = document.getElementById('modal-close-btn');
                const modalCancelBtn = document.getElementById('modal-cancel-btn');
                const modalSaveBtn = document.getElementById('modal-save-btn');

                if (modalCloseBtn) {
                    modalCloseBtn.removeEventListener('click', closeEditModal);
                    modalCloseBtn.addEventListener('click', closeEditModal);
                }
                if (modalCancelBtn) {
                    modalCancelBtn.removeEventListener('click', closeEditModal);
                    modalCancelBtn.addEventListener('click', closeEditModal);
                }
                if (modalSaveBtn) {
                    modalSaveBtn.removeEventListener('click', handleSaveEdit);
                    modalSaveBtn.addEventListener('click', handleSaveEdit);
                }

                // Initialize drag & drop for current tab
                const currentTab = document.querySelector('.tab-button.active')?.getAttribute('data-tab') || 'class';
                if (window.dragDropManager) {
                    window.dragDropManager.initializeTable(currentTab);
                }
            }

            // Settings change handler
            function handleSettingsChange() {

                const settings = spaceClampAjax.data.settings;
                settings.minBasespace = parseInt(document.getElementById('min-base-space').value) || 8;
                settings.maxBasespace = parseInt(document.getElementById('max-base-space').value) || 12;
                settings.minViewport = parseInt(document.getElementById('min-viewport').value) || 375;
                settings.maxViewport = parseInt(document.getElementById('max-viewport').value) || 1620;
                settings.minScale = parseFloat(document.getElementById('min-scale').value) || 1.125;
                settings.maxScale = parseFloat(document.getElementById('max-scale').value) || 1.25;

                updateDataTableValues(getSelectedBaseId());
                updateCSSOutputs();
            }

            // Base selection change handler  
            function handleBaseChange() {
                updateDataTableValues(getSelectedBaseId());
                updateCSSOutputs();
            }

            // Unit change handler (PX/REM switch)
            function handleUnitChange(event) {
                const selectedUnit = event.target.getAttribute('data-unit');

                // Update settings
                spaceClampAjax.data.settings.unitType = selectedUnit;

                // Update button active states
                document.querySelectorAll('.unit-button').forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');

                // Recalculate table values and CSS
                updateDataTableValues(getSelectedBaseId());
                updateCSSOutputs();
            }

            // Edit button click handler
            function handleEditClick(event) {
                const sizeId = parseInt(event.target.getAttribute('data-id'));
                const currentTab = document.querySelector('.tab-button.active')?.getAttribute('data-tab') || 'class';
                openEditModal(currentTab, sizeId);
            }

            // Open edit modal with pre-filled data
            function openEditModal(tabType, sizeId) {
                const modal = document.getElementById('edit-modal');
                const title = document.getElementById('modal-title');
                const formContent = document.getElementById('modal-form-content');
                title.textContent = `Edit ${tabType === 'class' ? 'Class' : tabType === 'vars' ? 'Variable' : 'Utility'}`;

                // Get current data for this size
                let currentData = null;
                if (tabType === 'class') {
                    currentData = spaceClampAjax.data.classSizes.find(size => size.id === sizeId);
                } else if (tabType === 'vars') {
                    currentData = spaceClampAjax.data.variableSizes.find(size => size.id === sizeId);
                } else if (tabType === 'utils') {
                    currentData = spaceClampAjax.data.utilitySizes.find(size => size.id === sizeId);
                }

                // Generate form content
                if (tabType === 'class') {
                    formContent.innerHTML = `
            <div class="fcc-form-group">
                <label class="fcc-label" for="edit-class-name">Class Name</label>
                <input type="text" id="edit-class-name" class="fcc-input" 
                       value="${currentData?.className || ''}" 
                       placeholder="e.g., space-lg">
            </div>
        `;
                } else if (tabType === 'vars') {
                    formContent.innerHTML = `
            <div class="fcc-form-group">
                <label class="fcc-label" for="edit-variable-name">Variable Name</label>
                <input type="text" id="edit-variable-name" class="fcc-input" 
                       value="${currentData?.variableName || ''}" 
                       placeholder="e.g., --space-lg">
            </div>
        `;
                } else if (tabType === 'utils') {
                    const hasMargin = (currentData?.properties || []).includes('margin');
                    const hasPadding = (currentData?.properties || []).includes('padding');

                    formContent.innerHTML = `
            <div class="fcc-form-group">
                <label class="fcc-label" for="edit-utility-name">Utility Name</label>
                <input type="text" id="edit-utility-name" class="fcc-input" 
                       value="${currentData?.utilityName || ''}" 
                       placeholder="e.g., lg">
            </div>
            <div class="fcc-form-group">
                <label class="fcc-label">Properties</label>
                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 0;">
                        <input type="checkbox" id="edit-margin" ${hasMargin ? 'checked' : ''}>
                        <span id="edit-margin-badge" class="property-badge ${hasMargin ? 'active' : 'inactive'}">M</span> Margin
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 0;">
                        <input type="checkbox" id="edit-padding" ${hasPadding ? 'checked' : ''}>
                        <span id="edit-padding-badge" class="property-badge ${hasPadding ? 'active' : 'inactive'}">P</span> Padding
                    </label>
                </div>
            </div>
        `;

                    // Add checkbox event listeners to update badge appearance
                    setTimeout(() => {
                        const marginCheckbox = document.getElementById('edit-margin');
                        const paddingCheckbox = document.getElementById('edit-padding');
                        const marginBadge = document.getElementById('edit-margin-badge');
                        const paddingBadge = document.getElementById('edit-padding-badge');

                        if (marginCheckbox && marginBadge) {
                            marginCheckbox.addEventListener('change', () => {
                                marginBadge.className = `property-badge ${marginCheckbox.checked ? 'active' : 'inactive'}`;
                            });
                        }

                        if (paddingCheckbox && paddingBadge) {
                            paddingCheckbox.addEventListener('change', () => {
                                paddingBadge.className = `property-badge ${paddingCheckbox.checked ? 'active' : 'inactive'}`;
                            });
                        }
                    }, 50);
                }

                // Store current edit context for save function
                modal.setAttribute('data-tab-type', tabType);
                modal.setAttribute('data-size-id', sizeId);
                modal.setAttribute('data-action', 'edit');

                modal.classList.add('show');

                // Focus on the name input field and add Enter key handling
                setTimeout(() => {
                    const nameInput = tabType === 'class' ? document.getElementById('edit-class-name') :
                        tabType === 'vars' ? document.getElementById('edit-variable-name') :
                        document.getElementById('edit-utility-name');
                    if (nameInput) {
                        nameInput.focus();
                        nameInput.setSelectionRange(0, nameInput.value.length); // Force select all text

                        // Add Enter key listener for quick save
                        nameInput.addEventListener('keydown', (event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                handleSaveEdit();
                            }
                        });
                    }
                }, 100);
            }

            function closeEditModal() {
                const modal = document.getElementById('edit-modal');
                modal.classList.remove('show');
            }

            // Save edit/add handler
            function handleSaveEdit() {
                const modal = document.getElementById('edit-modal');
                const tabType = modal.getAttribute('data-tab-type');
                const sizeId = parseInt(modal.getAttribute('data-size-id'));
                const action = modal.getAttribute('data-action') || 'edit';

                // Get form values based on tab type and action
                let updateData = {};
                const prefix = action === 'add' ? 'add' : 'edit';

                if (tabType === 'class') {
                    const nameInput = document.getElementById(`${prefix}-class-name`);
                    updateData.className = nameInput.value.trim();
                } else if (tabType === 'vars') {
                    const nameInput = document.getElementById(`${prefix}-variable-name`);
                    updateData.variableName = nameInput.value.trim();
                } else if (tabType === 'utils') {
                    const nameInput = document.getElementById(`${prefix}-utility-name`);
                    const marginChecked = document.getElementById(`${prefix}-margin`).checked;
                    const paddingChecked = document.getElementById(`${prefix}-padding`).checked;

                    updateData.utilityName = nameInput.value.trim();
                    updateData.properties = [];
                    if (marginChecked) updateData.properties.push('margin');
                    if (paddingChecked) updateData.properties.push('padding');
                }
                // Validate input before saving
                const validationError = validateEntryData(updateData, tabType, action, sizeId);
                if (validationError) {
                    alert(validationError);
                    return;
                }

                // Update the appropriate data array
                let targetArray;
                if (tabType === 'class') {
                    targetArray = spaceClampAjax.data.classSizes;
                } else if (tabType === 'vars') {
                    targetArray = spaceClampAjax.data.variableSizes;
                } else if (tabType === 'utils') {
                    targetArray = spaceClampAjax.data.utilitySizes;
                }

                if (action === 'add') {
                    // Create new entry
                    const newEntry = {
                        id: sizeId,
                        ...updateData
                    };
                    targetArray.push(newEntry);
                } else {
                    // Find and update existing item
                    const itemIndex = targetArray.findIndex(item => item.id === sizeId);
                    if (itemIndex !== -1) {
                        Object.assign(targetArray[itemIndex], updateData);
                    }
                }

                // Regenerate table and CSS
                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    panelContainer.innerHTML = generatePanelContent(tabType);
                    attachEventListeners(); // Reattach listeners to new content
                }

                updateCSSOutputs();
                closeEditModal();
            }

            // Keyboard navigation for modal
            function handleModalKeydown(event) {
                if (!document.getElementById('edit-modal').classList.contains('show')) return;

                if (event.key === 'Enter') {
                    const activeElement = document.activeElement;

                    // If on an input, move to next focusable element
                    if (activeElement.tagName === 'INPUT') {
                        event.preventDefault();
                        const focusableElements = document.querySelectorAll('#edit-modal input, #edit-modal button');
                        const currentIndex = Array.from(focusableElements).indexOf(activeElement);
                        const nextElement = focusableElements[currentIndex + 1];
                        if (nextElement) {
                            nextElement.focus();
                        }
                    }
                    // If on Save button, trigger save
                    else if (activeElement.id === 'modal-save-btn') {
                        event.preventDefault();
                        handleSaveEdit();
                    }
                    // If on Cancel button, close modal
                    else if (activeElement.id === 'modal-cancel-btn') {
                        event.preventDefault();
                        closeEditModal();
                    }
                }

                if (event.key === 'Escape') {
                    attachEventListeners
                    closeEditModal();
                }
            }

            // Add modal keyboard listener during DOMContentLoaded
            document.addEventListener('keydown', handleModalKeydown);

            // Generate proper clamp() function with linear interpolation
            function generateClampFunction(minValue, maxValue, minViewport, maxViewport, unitType) {
                // Convert to pixels for calculation
                const minPx = unitType === 'rem' ? minValue * 16 : minValue;
                const maxPx = unitType === 'rem' ? maxValue * 16 : maxValue;

                // Calculate linear interpolation coefficients
                const coefficient = ((maxPx - minPx) / (maxViewport - minViewport) * 100);
                const constant = minPx - (coefficient * minViewport / 100);

                // Format values based on unit type
                const minUnit = unitType === 'rem' ? (minPx / 16).toFixed(3) + 'rem' : minPx + 'px';
                const maxUnit = unitType === 'rem' ? (maxPx / 16).toFixed(3) + 'rem' : maxPx + 'px';

                // Format the preferred value (constant + coefficient)
                const constantFormatted = unitType === 'rem' ?
                    (constant / 16).toFixed(4) + 'rem' :
                    constant.toFixed(2) + 'px';
                const coefficientFormatted = coefficient.toFixed(4) + 'vw';

                const preferredValue = constant === 0 ?
                    coefficientFormatted :
                    `calc(${constantFormatted} + ${coefficientFormatted})`;

                return `clamp(${minUnit}, ${preferredValue}, ${maxUnit})`;
            }

            // space calculation function
            function calculatespaceSize(sizeId, settings, selectedBaseId = 3) {
                // Step 1: Get the entry from the Base combo box
                const baseComboValue = document.getElementById('base-value')?.value;
                if (!baseComboValue) {
                    console.log("❌ No base value selected.");
                    return {
                        min: 8,
                        max: 12,
                        minUnit: '8px',
                        maxUnit: '12px'
                    }; // fallback
                }
                const baseId = parseInt(baseComboValue);

                // Step 2: Get current data array and find base position
                const currentTab = document.querySelector('.tab-button.active')?.getAttribute('data-tab') || 'class';
                let currentSizes;
                if (currentTab === 'class') {
                    currentSizes = spaceClampAjax.data.classSizes;
                } else if (currentTab === 'vars') {
                    currentSizes = spaceClampAjax.data.variableSizes;
                } else {
                    currentSizes = spaceClampAjax.data.utilitySizes;
                }

                const baseIndex = currentSizes.findIndex(item => item.id === baseId);
                const currentIndex = currentSizes.findIndex(item => item.id === sizeId);

                if (baseIndex === -1 || currentIndex === -1) {
                    console.log("❌ Base entry not found in table");
                    return {
                        min: 8,
                        max: 12,
                        minUnit: '8px',
                        maxUnit: '12px'
                    }; // fallback
                }

                // Step 3: Get the numeric scaling values
                const minScale = parseFloat(settings.minScale);
                const maxScale = parseFloat(settings.maxScale);
                if (isNaN(minScale) || isNaN(maxScale)) {
                    console.error("Invalid scaling values");
                    return {
                        min: 8,
                        max: 12,
                        minUnit: '8px',
                        maxUnit: '12px'
                    }; // fallback
                }

                // Step 4: Get the Min and Max Space from Settings
                const baseMinSpace = parseInt(settings.minBasespace);
                const baseMaxSpace = parseInt(settings.maxBasespace);

                // Step 5: Calculate how many steps away from base
                // Negative = smaller (above base in table), Positive = larger (below base in table)
                const steps = currentIndex - baseIndex;

                // Step 6: Use scaling as exponent
                // scale^steps as multiplier
                const minMultiplier = Math.pow(minScale, steps);
                const maxMultiplier = Math.pow(maxScale, steps);

                const minSize = Math.round(baseMinSpace * minMultiplier);
                const maxSize = Math.round(baseMaxSpace * maxMultiplier);

                return {
                    min: minSize,
                    max: maxSize,
                    minUnit: settings.unitType === 'rem' ? (minSize / 16).toFixed(3) + 'rem' : minSize + 'px',
                    maxUnit: settings.unitType === 'rem' ? (maxSize / 16).toFixed(3) + 'rem' : maxSize + 'px'
                };
            }

            // Generate CSS for Classes tab
            function generateClassesCSS(sizes, settings, selectedBaseId = 3) {
                const minVp = settings.minViewport;
                const maxVp = settings.maxViewport;
                const unitType = settings.unitType;

                return sizes.map(size => {
                    const calc = calculatespaceSize(size.id, settings, selectedBaseId);
                    const clampFunction = generateClampFunction(calc.min, calc.max, minVp, maxVp, unitType);
                    return `.${size.className} {\n  margin: ${clampFunction};\n}`;
                }).join('\n\n');
            }

            // Generate CSS for Variables tab
            function generateVariablesCSS(sizes, settings, selectedBaseId = 3) {
                const minVp = settings.minViewport;
                const maxVp = settings.maxViewport;
                const unitType = settings.unitType;

                const variablesList = sizes.map(size => {
                    const calc = calculatespaceSize(size.id, settings, selectedBaseId);
                    const clampFunction = generateClampFunction(calc.min, calc.max, minVp, maxVp, unitType);
                    return `  ${size.variableName}: ${clampFunction};`;
                }).join('\n');

                return `:root {\n${variablesList}\n}`;
            }

            // Generate CSS for Utilities tab
            function generateUtilitiesCSS(sizes, settings, selectedBaseId = 3) {
                const minVp = settings.minViewport;
                const maxVp = settings.maxViewport;
                const unitType = settings.unitType;

                let css = '';

                sizes.forEach(size => {
                    const calc = calculatespaceSize(size.id, settings, selectedBaseId);
                    const clampFunction = generateClampFunction(calc.min, calc.max, minVp, maxVp, unitType);

                    // Generate utilities for each property
                    (size.properties || ['margin', 'padding']).forEach(property => {
                        const prefix = property === 'margin' ? 'm' : 'p';
                        const sizeName = size.utilityName;

                        // Generate directional classes
                        css += `.${prefix}t-${sizeName} { ${property}-top: ${clampFunction}; }\n`;
                        css += `.${prefix}b-${sizeName} { ${property}-bottom: ${clampFunction}; }\n`;
                        css += `.${prefix}l-${sizeName} { ${property}-left: ${clampFunction}; }\n`;
                        css += `.${prefix}r-${sizeName} { ${property}-right: ${clampFunction}; }\n`;
                        css += `.${prefix}x-${sizeName} { ${property}-left: ${clampFunction}; ${property}-right: ${clampFunction}; }\n`;
                        css += `.${prefix}y-${sizeName} { ${property}-top: ${clampFunction}; ${property}-bottom: ${clampFunction}; }\n`;
                        css += `.${prefix}-${sizeName} { ${property}: ${clampFunction}; }\n\n`;
                    });
                });

                return css.trim();
            }

            // Helper function to get the currently selected base ID from the combo box
            function getSelectedBaseId() {
                const baseSelect = document.getElementById('base-value');
                return baseSelect ? parseInt(baseSelect.value) : 3;
            }

            function updateDataTableValues(selectedBaseId) {
                // Wait for DOM to update after content generation
                setTimeout(() => {
                    const settings = spaceClampAjax.data.settings;
                    const rows = document.querySelectorAll('.font-table tr[data-id]');

                    rows.forEach(row => {
                        const sizeId = parseInt(row.getAttribute('data-id'));
                        const calc = calculatespaceSize(sizeId, settings, selectedBaseId);

                        // For Classes/Variables: Min=column 3, Max=column 4
                        // For Utilities: Min=column 4, Max=column 5 (has Properties in column 3)
                        const hasPropertiesColumn = row.querySelector('td:nth-child(3) .utility-properties');

                        let minCell, maxCell;
                        if (hasPropertiesColumn) {
                            // Utilities tab
                            minCell = row.querySelector('td:nth-child(4)');
                            maxCell = row.querySelector('td:nth-child(5)');
                        } else {
                            // Classes/Variables tabs
                            minCell = row.querySelector('td:nth-child(3)');
                            maxCell = row.querySelector('td:nth-child(4)');
                        }

                        // Update the cells directly
                        if (minCell) {
                            minCell.textContent = calc.minUnit;
                        }
                        if (maxCell) {
                            maxCell.textContent = calc.maxUnit;
                        }
                    });
                }, 10);
            }

            // Updated function to regenerate CSS when settings change
            function updateCSSOutputs() {
                const currentTab = document.querySelector('.tab-button.active')?.getAttribute('data-tab') || 'class';
                const selectedBaseId = getSelectedBaseId();
                const settings = spaceClampAjax.data.settings;

                let currentSizes, css;

                if (currentTab === 'class') {
                    currentSizes = spaceClampAjax.data.classSizes;
                    css = generateClassesCSS(currentSizes, settings, selectedBaseId);
                } else if (currentTab === 'vars') {
                    currentSizes = spaceClampAjax.data.variableSizes;
                    css = generateVariablesCSS(currentSizes, settings, selectedBaseId);
                } else if (currentTab === 'utils') {
                    currentSizes = spaceClampAjax.data.utilitySizes;
                    css = generateUtilitiesCSS(currentSizes, settings, selectedBaseId);
                }

                // Update generated CSS output
                const generatedCode = document.getElementById('generated-code');
                if (generatedCode) {
                    generatedCode.textContent = css;
                }

                // Update preview
                generatespacePreview(currentTab, currentSizes, selectedBaseId);
            }

            // Generate space preview content
            function generatespacePreview(tabType, currentSizes, selectedBaseId) {
                const settings = spaceClampAjax.data.settings;

                // Update min container
                const minContainer = document.getElementById('preview-min-container');
                if (minContainer) {
                    minContainer.innerHTML = generatePreviewContent(currentSizes, settings, 'min', tabType, selectedBaseId);
                }

                // Update max container  
                const maxContainer = document.getElementById('preview-max-container');
                if (maxContainer) {
                    maxContainer.innerHTML = generatePreviewContent(currentSizes, settings, 'max', tabType, selectedBaseId);
                }
            }

            // Generate preview content for one container
            function generatePreviewContent(sizes, settings, sizeType, tabType, selectedBaseId = 3) {
                const titleText = sizeType === 'min' ? 'Small Screen Space' : 'Large Screen Space';

                return `
                    <div style="font-family: Arial, sans-serif;">
                        <h4 style="margin: 0 0 16px 0; color: var(--clr-txt); font-size: 14px; font-weight: 600;">${titleText}</h4>
                        ${sizes.map(size => {
                            const calc = calculatespaceSize(size.id, settings, selectedBaseId);
                            const space = sizeType === 'min' ? calc.min : calc.max;
                            const name = getDisplayName(size, tabType);
                            const barWidth = Math.min(space * 3, 180); // Scale for visual display
                            
                            return `
                                <div style="margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 4px; border-left: 3px solid #1976d2;">
                                    <div style="font-size: 11px; color: #666; margin-bottom: 4px; font-weight: 600;">${name}: ${space}px</div>
                                    <div style="background: linear-gradient(90deg, #e3f2fd, #1976d2); height: 16px; width: ${barWidth}px; border-radius: 2px; border: 1px solid #1976d2;"></div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            }

            // Add Size button handler
            function handleAddSize() {
                const currentTab = document.querySelector('.tab-button.active')?.getAttribute('data-tab') || 'class';

                // Generate next custom name and ID
                const {
                    nextId,
                    customName
                } = generateNextCustomEntry(currentTab);

                // Open add modal
                openAddModal(currentTab, nextId, customName);
            }

            // Validate entry data before saving
            function validateEntryData(updateData, tabType, action, sizeId) {
                let name, targetArray, propertyName;

                // Get the appropriate data and property name
                if (tabType === 'class') {
                    name = updateData.className;
                    targetArray = spaceClampAjax.data.classSizes;
                    propertyName = 'className';
                } else if (tabType === 'vars') {
                    name = updateData.variableName;
                    targetArray = spaceClampAjax.data.variableSizes;
                    propertyName = 'variableName';
                } else if (tabType === 'utils') {
                    name = updateData.utilityName;
                    targetArray = spaceClampAjax.data.utilitySizes;
                    propertyName = 'utilityName';
                }

                // Check for empty or whitespace-only names
                if (!name || name.trim() === '') {
                    return `Please enter a ${tabType === 'class' ? 'class' : tabType === 'vars' ? 'variable' : 'utility'} name.`;
                }

                // Check for placeholder text
                const placeholders = ['e.g., space-lg', 'e.g., --space-lg', 'e.g., lg'];
                if (placeholders.includes(name.trim())) {
                    return 'Please enter a real name, not the placeholder text.';
                }

                // Check for duplicates (exclude current item when editing)
                const isDuplicate = targetArray.some(item => {
                    const isSameEntry = action === 'edit' && item.id === sizeId;
                    return !isSameEntry && item[propertyName] === name.trim();
                });

                if (isDuplicate) {
                    return `A ${tabType === 'class' ? 'class' : tabType === 'vars' ? 'variable' : 'utility'} with the name "${name.trim()}" already exists.`;
                }

                // For utilities, validate that at least one property is selected
                if (tabType === 'utils' && (!updateData.properties || updateData.properties.length === 0)) {
                    return 'Please select at least one property (Margin or Padding) for the utility.';
                }

                return null; // No validation errors
            }

            // Generate next custom entry data
            function generateNextCustomEntry(tabType) {
                let currentData;
                if (tabType === 'class') {
                    currentData = spaceClampAjax.data.classSizes;
                } else if (tabType === 'vars') {
                    currentData = spaceClampAjax.data.variableSizes;
                } else if (tabType === 'utils') {
                    currentData = spaceClampAjax.data.utilitySizes;
                }

                // Find next available ID
                const maxId = currentData.length > 0 ? Math.max(...currentData.map(item => item.id)) : 0;
                const nextId = maxId + 1;

                // Generate custom name based on existing custom entries
                const customEntries = currentData.filter(item => {
                    const name = getDisplayName(item, tabType);
                    return name.includes('custom-');
                });

                const nextCustomNumber = customEntries.length + 1;
                let customName;

                if (tabType === 'class') {
                    customName = `custom-${nextCustomNumber}`;
                } else if (tabType === 'vars') {
                    customName = `--custom-${nextCustomNumber}`;
                } else if (tabType === 'utils') {
                    customName = `custom-${nextCustomNumber}`;
                }

                return {
                    nextId,
                    customName
                };
            }

            // Open add modal with pre-filled data
            function openAddModal(tabType, newId, defaultName) {
                const modal = document.getElementById('edit-modal');
                const title = document.getElementById('modal-title');
                const formContent = document.getElementById('modal-form-content');

                title.textContent = `Add ${tabType === 'class' ? 'Class' : tabType === 'vars' ? 'Variable' : 'Utility'}`;

                // Generate form content
                if (tabType === 'class') {
                    formContent.innerHTML = `
            <div class="fcc-form-group">
                <label class="fcc-label" for="add-class-name">Class Name</label>
                <input type="text" id="add-class-name" class="fcc-input" 
                       value="${defaultName}" 
                       placeholder="e.g., space-lg">
            </div>
        `;
                } else if (tabType === 'vars') {
                    formContent.innerHTML = `
            <div class="fcc-form-group">
                <label class="fcc-label" for="add-variable-name">Variable Name</label>
                <input type="text" id="add-variable-name" class="fcc-input" 
                       value="${defaultName}" 
                       placeholder="e.g., --space-lg">
            </div>
        `;
                } else if (tabType === 'utils') {
                    formContent.innerHTML = `
            <div class="fcc-form-group">
                <label class="fcc-label" for="add-utility-name">Utility Name</label>
                <input type="text" id="add-utility-name" class="fcc-input" 
                       value="${defaultName}" 
                       placeholder="e.g., lg">
            </div>
            <div class="fcc-form-group">
                <label class="fcc-label">Properties</label>
                <div style="display: flex; gap: 16px; margin-top: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 0;">
                        <input type="checkbox" id="add-margin" checked>
                        <span class="property-badge active">M</span> Margin
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 0;">
                        <input type="checkbox" id="add-padding" checked>
                        <span class="property-badge active">P</span> Padding
                    </label>
                </div>
            </div>
        `;
                }

                // Store add context for save function
                modal.setAttribute('data-tab-type', tabType);
                modal.setAttribute('data-size-id', newId);
                modal.setAttribute('data-action', 'add');

                modal.classList.add('show');

                // Focus on the name input field and add Enter key handling
                setTimeout(() => {
                    const nameInput = tabType === 'class' ? document.getElementById('add-class-name') :
                        tabType === 'vars' ? document.getElementById('add-variable-name') :
                        document.getElementById('add-utility-name');
                    if (nameInput) {
                        nameInput.select(); // Select the default text so user can replace it

                        // Add Enter key listener for quick save
                        nameInput.addEventListener('keydown', (event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                handleSaveEdit();
                            }
                        });
                    }
                }, 100);
            }

            // Reset button handler - restore original defaults
            function handleReset() {
                const currentTab = document.querySelector('.tab-button.active')?.getAttribute('data-tab') || 'class';
                const tabName = currentTab === 'class' ? 'Classes' : currentTab === 'vars' ? 'Variables' : 'Utilities';

                // Show confirmation dialog
                const confirmed = confirm(`Reset ${tabName} to defaults?\n\nThis will replace all current entries with the original 6 default sizes.\n\nAny custom entries will be lost.`);

                if (!confirmed) return;

                // Restore defaults based on tab type
                restoreDefaults(currentTab);

                // Regenerate the table
                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    panelContainer.innerHTML = generatePanelContent(currentTab);
                    attachEventListeners();
                }

                // Update table values and CSS outputs
                updateDataTableValues(getSelectedBaseId());
                updateCSSOutputs();

                // Show success notification
                showResetNotification(tabName);
            }

            // Restore default entries for specified tab type
            function restoreDefaults(tabType) {
                if (tabType === 'class') {
                    spaceClampAjax.data.classSizes = [{
                            id: 1,
                            className: 'space-xs'
                        },
                        {
                            id: 2,
                            className: 'space-sm'
                        },
                        {
                            id: 3,
                            className: 'space-md'
                        },
                        {
                            id: 4,
                            className: 'space-lg'
                        },
                        {
                            id: 5,
                            className: 'space-xl'
                        },
                        {
                            id: 6,
                            className: 'space-xxl'
                        }
                    ];
                } else if (tabType === 'vars') {
                    spaceClampAjax.data.variableSizes = [{
                            id: 1,
                            variableName: '--space-xs'
                        },
                        {
                            id: 2,
                            variableName: '--space-sm'
                        },
                        {
                            id: 3,
                            variableName: '--space-md'
                        },
                        {
                            id: 4,
                            variableName: '--space-lg'
                        },
                        {
                            id: 5,
                            variableName: '--space-xl'
                        },
                        {
                            id: 6,
                            variableName: '--space-xxl'
                        }
                    ];
                } else if (tabType === 'utils') {
                    spaceClampAjax.data.utilitySizes = [{
                            id: 1,
                            utilityName: 'xs',
                            properties: ['margin', 'padding']
                        },
                        {
                            id: 2,
                            utilityName: 'sm',
                            properties: ['margin', 'padding']
                        },
                        {
                            id: 3,
                            utilityName: 'md',
                            properties: ['margin', 'padding']
                        },
                        {
                            id: 4,
                            utilityName: 'lg',
                            properties: ['margin', 'padding']
                        },
                        {
                            id: 5,
                            utilityName: 'xl',
                            properties: ['margin', 'padding']
                        },
                        {
                            id: 6,
                            utilityName: 'xxl',
                            properties: ['margin', 'padding']
                        }
                    ];
                }
            }

            // Show reset success notification
            function showResetNotification(tabName) {
                // Create reset notification
                const notification = document.createElement('div');
                notification.id = 'reset-notification';
                notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--jimr-success);
        color: white;
        padding: 16px 20px;
        border-radius: var(--jimr-border-radius-lg);
        box-shadow: var(--clr-shadow-xl);
        z-index: 10001;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 2px solid var(--jimr-success-dark);
        animation: slideInUp 0.3s ease;
    `;

                notification.innerHTML = `
        <div style="font-size: 20px;">✅</div>
        <div>
            <div style="font-weight: 600; margin-bottom: 2px;">Reset Complete</div>
            <div style="font-size: 12px; opacity: 0.9;">Restored 6 default ${tabName.toLowerCase()}</div>
        </div>
    `;

                document.body.appendChild(notification);

                // Auto-dismiss after 3 seconds
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        notification.style.transition = 'transform 0.25s ease-out, opacity 0.25s ease-out';
                        notification.style.transform = 'translateY(100%)';
                        notification.style.opacity = '0';

                        setTimeout(() => {
                            if (document.body.contains(notification)) {
                                document.body.removeChild(notification);
                            }
                        }, 250);
                    }
                }, 3000);
            }

            // Delete button handler
            function handleDelete(event) {
                const sizeId = parseInt(event.target.getAttribute('data-id'));
                const currentTab = document.querySelector('.tab-button.active')?.getAttribute('data-tab') || 'class';

                // Get current data and find the item to delete
                let currentData, targetArray;
                if (currentTab === 'class') {
                    currentData = spaceClampAjax.data.classSizes;
                    targetArray = 'classSizes';
                } else if (currentTab === 'vars') {
                    currentData = spaceClampAjax.data.variableSizes;
                    targetArray = 'variableSizes';
                } else if (currentTab === 'utils') {
                    currentData = spaceClampAjax.data.utilitySizes;
                    targetArray = 'utilitySizes';
                }

                const itemToDelete = currentData.find(item => item.id === sizeId);
                if (!itemToDelete) return;

                const itemName = getDisplayName(itemToDelete, currentTab);

                // Show confirmation dialog
                const confirmed = confirm(`Delete "${itemName}"?\n\nThis action cannot be undone.`);

                if (!confirmed) return;

                // Remove item from data array
                const itemIndex = currentData.findIndex(item => item.id === sizeId);
                if (itemIndex !== -1) {
                    spaceClampAjax.data[targetArray].splice(itemIndex, 1);
                }

                // Regenerate the table
                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    panelContainer.innerHTML = generatePanelContent(currentTab);
                    attachEventListeners();
                }

                // Update table values and CSS outputs
                updateDataTableValues(getSelectedBaseId());
                updateCSSOutputs();
            }

            // Clear All button handler with confirmation and undo
            function handleClearAll() {
                const currentTab = document.querySelector('.tab-button.active')?.getAttribute('data-tab') || 'class';
                const tabName = currentTab === 'class' ? 'Classes' : currentTab === 'vars' ? 'Variables' : 'Utilities';

                // Get current data for backup
                let currentData, dataArrayRef;
                if (currentTab === 'class') {
                    currentData = [...spaceClampAjax.data.classSizes];
                    dataArrayRef = 'classSizes';
                } else if (currentTab === 'vars') {
                    currentData = [...spaceClampAjax.data.variableSizes];
                    dataArrayRef = 'variableSizes';
                } else if (currentTab === 'utils') {
                    currentData = [...spaceClampAjax.data.utilitySizes];
                    dataArrayRef = 'utilitySizes';
                }

                // Show confirmation dialog
                const confirmed = confirm(`Are you sure you want to clear all ${tabName}?\n\nThis will remove all ${currentData.length} entries from the current tab.\n\nYou can undo this action immediately after.`);

                if (!confirmed) return;

                // Clear the data array
                spaceClampAjax.data[dataArrayRef] = [];

                // Regenerate the table with empty state
                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    panelContainer.innerHTML = generatePanelContent(currentTab);
                    attachEventListeners();
                }

                // Update CSS outputs (will show empty)
                updateCSSOutputs();

                // Show undo notification
                showUndoNotification(tabName, currentData, dataArrayRef, currentTab);
            }

            // Show undo notification with restore functionality
            function showUndoNotification(tabName, backupData, dataArrayRef, tabType) {
                // Create undo notification
                const notification = document.createElement('div');
                notification.id = 'clear-undo-notification';
                notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--clr-secondary);
        color: #FAF9F6;
        padding: 16px 20px;
        border-radius: var(--jimr-border-radius-lg);
        box-shadow: var(--clr-shadow-xl);
        z-index: 10001;
        display: flex;
        align-items: center;
        gap: 16px;
        border: 2px solid var(--clr-primary);
        max-width: 400px;
        animation: slideInUp 0.3s ease;
    `;

                notification.innerHTML = `
        <div style="flex-grow: 1;">
            <div style="font-weight: 600; margin-bottom: 4px;">Cleared ${backupData.length} ${tabName}</div>
            <div style="font-size: 12px; opacity: 0.9;">This action can be undone</div>
        </div>
        <button id="undo-clear-btn" style="
            background: var(--clr-accent);
            color: var(--clr-btn-txt);
            border: 1px solid var(--clr-btn-bdr);
            padding: 8px 16px;
            border-radius: var(--jimr-border-radius);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--jimr-transition);
        ">UNDO</button>
        <button id="dismiss-clear-btn" style="
            background: none;
            border: none;
            color: #FAF9F6;
            font-size: 18px;
            cursor: pointer;
            padding: 4px;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        ">×</button>
    `;

                // Add CSS animation
                const style = document.createElement('style');
                style.textContent = `
        @keyframes slideInUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideOutDown {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(100%); opacity: 0; }
        }
    `;
                document.head.appendChild(style);

                document.body.appendChild(notification);

                // Undo button functionality
                document.getElementById('undo-clear-btn').addEventListener('click', () => {
                    // Restore the data
                    spaceClampAjax.data[dataArrayRef] = backupData;

                    // Regenerate the table
                    const panelContainer = document.getElementById('sizes-table-container');
                    if (panelContainer) {
                        panelContainer.innerHTML = generatePanelContent(tabType);
                        attachEventListeners();
                    }

                    // Update CSS outputs
                    updateDataTableValues(getSelectedBaseId());
                    updateCSSOutputs();

                    // Remove notification
                    removeNotification(notification);
                });

                // Dismiss button functionality
                document.getElementById('dismiss-clear-btn').addEventListener('click', () => {
                    removeNotification(notification);
                });

                // Enter key handling
                const handleUndoKeydown = (event) => {
                    if (event.key === 'Enter') {
                        removeNotification(notification);
                        document.removeEventListener('keydown', handleUndoKeydown);
                    }
                };
                document.addEventListener('keydown', handleUndoKeydown);

                // Auto-dismiss after 10 seconds
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        removeNotification(notification);
                        document.removeEventListener('keydown', handleUndoKeydown);
                    }
                }, 10000);
            }

            // Remove notification with smooth animation
            function removeNotification(notification) {
                // Prevent multiple calls
                if (notification.dataset.removing === 'true') return;
                notification.dataset.removing = 'true';

                // Use transform and opacity for smoother animation
                notification.style.transition = 'transform 0.25s ease-out, opacity 0.25s ease-out';
                notification.style.transform = 'translateY(100%)';
                notification.style.opacity = '0';

                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                        // Clean up any remaining event listeners
                        document.removeEventListener('keydown', handleUndoKeydown);
                    }
                }, 250);
            }

            // ========================================================================
            // SAVE AND AUTOSAVE SYSTEM (from FCC, adapted for SCC)
            // ========================================================================

            let autosaveTimer = null;

            function handleSaveButton() {
                const saveBtn = document.getElementById('save-btn');
                const autosaveStatus = document.getElementById('autosave-status');
                const autosaveIcon = document.getElementById('autosave-icon');
                const autosaveText = document.getElementById('autosave-text');

                // Update status to show saving
                if (autosaveStatus && autosaveIcon && autosaveText) {
                    autosaveStatus.className = 'autosave-status saving';
                    autosaveIcon.textContent = '⏳';
                    autosaveText.textContent = 'Saving...';
                }

                // Disable save button during save
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.textContent = 'Saving...';
                }

                // Collect current settings (adapted for SCC)
                const settings = {
                    minBasespace: document.getElementById('min-base-space')?.value,
                    maxBasespace: document.getElementById('max-base-space')?.value,
                    minViewport: document.getElementById('min-viewport')?.value,
                    maxViewport: document.getElementById('max-viewport')?.value,
                    minScale: document.getElementById('min-scale')?.value,
                    maxScale: document.getElementById('max-scale')?.value,
                    unitType: document.querySelector('.unit-button.active')?.getAttribute('data-unit'),
                    activeTab: document.querySelector('.tab-button.active')?.getAttribute('data-tab'),
                    autosaveEnabled: document.getElementById('autosave-toggle')?.checked,
                    selectedClassSizeId: document.getElementById('base-value')?.value || 3,
                    selectedVariableSizeId: document.getElementById('base-value')?.value || 3,
                    selectedUtilitySizeId: document.getElementById('base-value')?.value || 3
                };

                // Collect current sizes for all tabs (adapted for SCC)
                const allSizes = {
                    classSizes: spaceClampAjax?.data?.classSizes || [],
                    variableSizes: spaceClampAjax?.data?.variableSizes || [],
                    utilitySizes: spaceClampAjax?.data?.utilitySizes || []
                };

                const data = {
                    action: 'save_space_clamp_settings',
                    nonce: spaceClampAjax.nonce,
                    settings: JSON.stringify(settings),
                    sizes: JSON.stringify(allSizes)
                };

                fetch(spaceClampAjax.ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams(data)
                    })
                    .then(response => response.json())
                    .then(result => {
                        // Update status to show success
                        if (autosaveStatus && autosaveIcon && autosaveText) {
                            autosaveStatus.className = 'autosave-status saved';
                            autosaveIcon.textContent = '✅';
                            autosaveText.textContent = 'Saved!';

                            // Reset to ready after 2 seconds
                            setTimeout(() => {
                                autosaveStatus.className = 'autosave-status idle';
                                autosaveIcon.textContent = '💾';
                                autosaveText.textContent = 'Ready';
                            }, 2000);
                        }

                        // Re-enable save button
                        if (saveBtn) {
                            saveBtn.disabled = false;
                            saveBtn.textContent = 'Save';
                        }
                    })
                    .catch(error => {
                        console.error('Save error:', error);

                        // Update status to show error
                        if (autosaveStatus && autosaveIcon && autosaveText) {
                            autosaveStatus.className = 'autosave-status error';
                            autosaveIcon.textContent = '❌';
                            autosaveText.textContent = 'Error';

                            // Reset to ready after 3 seconds
                            setTimeout(() => {
                                autosaveStatus.className = 'autosave-status idle';
                                autosaveIcon.textContent = '💾';
                                autosaveText.textContent = 'Ready';
                            }, 3000);
                        }

                        // Re-enable save button
                        if (saveBtn) {
                            saveBtn.disabled = false;
                            saveBtn.textContent = 'Save';
                        }

                        alert('Error saving data');
                    });
            }

            function handleAutosaveToggle() {
                const isEnabled = document.getElementById('autosave-toggle')?.checked;

                if (isEnabled) {
                    startAutosaveTimer();
                } else {
                    stopAutosaveTimer();
                }
            }

            function startAutosaveTimer() {
                stopAutosaveTimer(); // Clear any existing timer

                autosaveTimer = setInterval(() => {
                    performSave(true); // true = isAutosave
                }, 30000); // 30 seconds
            }

            function stopAutosaveTimer() {
                if (autosaveTimer) {
                    clearInterval(autosaveTimer);
                    autosaveTimer = null;
                }
            }

            function performSave(isAutosave = false) {
                const saveBtn = document.getElementById('save-btn');
                if (saveBtn) {
                    handleSaveButton();
                }
            }

            // ========================================================================
            // DRAG & DROP MANAGER CLASS
            // ========================================================================

            /**
             * Modular Drag & Drop Manager
             * Designed for easy enhancement from Moderate → Complete
             */
            class DragDropManager {
                constructor() {
                    this.draggedElement = null;
                    this.draggedData = null;
                    this.currentTabType = null;
                    this.placeholder = null;

                    // Extension points for Complete phase
                    this.animationDuration = 200;
                    this.visualFeedbackEnabled = true;
                    this.dropZoneClass = 'drag-drop-zone';
                }

                /**
                 * Initialize drag & drop for a table
                 * @param {string} tabType - 'class', 'vars', or 'utils'
                 */
                initializeTable(tabType) {
                    this.currentTabType = tabType;
                    const tableBody = document.querySelector('#sizes-table');
                    if (!tableBody) {
                        console.log('❌ No table body found!');
                        return;
                    }

                    // Add drag listeners to all rows
                    const rows = tableBody.querySelectorAll('tr[data-id]');
                    rows.forEach(row => this.makeRowDraggable(row));

                    // Make table body a drop zone
                    this.makeDropZone(tableBody);
                }

                /**
                 * Make a table row draggable
                 * @param {HTMLElement} row 
                 */
                makeRowDraggable(row) {
                    const handle = row.querySelector('.drag-handle');
                    if (!handle) return;

                    // Make row draggable
                    row.draggable = true;

                    // Add drag event listeners
                    row.addEventListener('dragstart', (e) => this.handleDragStart(e, row));
                    row.addEventListener('dragend', (e) => this.handleDragEnd(e, row));

                    // Handle cursor changes
                    handle.addEventListener('mousedown', () => {
                        handle.style.cursor = 'grabbing';
                    });

                    handle.addEventListener('mouseup', () => {
                        handle.style.cursor = 'grab';
                    });
                }

                /**
                 * Make an element a drop zone
                 * @param {HTMLElement} element 
                 */
                makeDropZone(element) {
                    element.addEventListener('dragover', (e) => this.handleDragOver(e));
                    element.addEventListener('drop', (e) => this.handleDrop(e));
                    element.addEventListener('dragenter', (e) => this.handleDragEnter(e));
                    element.addEventListener('dragleave', (e) => this.handleDragLeave(e));
                }

                /**
                 * Handle drag start - Moderate phase
                 * Extension point: Add ghost image, enhanced feedback
                 */
                handleDragStart(event, row) {
                    this.draggedElement = row;

                    // Store the data being dragged
                    const sizeId = parseInt(row.getAttribute('data-id'));
                    this.draggedData = this.getSizeData(sizeId);

                    // Visual feedback - Moderate phase
                    if (this.visualFeedbackEnabled) {
                        row.classList.add('dragging');
                        row.style.opacity = '0.5';
                    }

                    // Create placeholder
                    this.createPlaceholder(row);

                    // Set drag data
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/html', row.outerHTML);
                }

                /**
                 * Handle drag end - cleanup
                 */
                handleDragEnd(event, row) {
                    // Remove visual feedback
                    row.classList.remove('dragging');
                    row.style.opacity = '';

                    // Remove placeholder
                    this.removePlaceholder();

                    // Clear drag data
                    this.draggedElement = null;
                    this.draggedData = null;
                }

                /**
                 * Handle drag over - allow drop
                 */
                handleDragOver(event) {
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';

                    // Find the row we're hovering over
                    const targetRow = this.findNearestRow(event.target);
                    if (targetRow && targetRow !== this.draggedElement) {
                        this.updatePlaceholderPosition(targetRow, event.clientY);
                    }
                }

                /**
                 * Handle drop - reorder the data
                 */
                handleDrop(event) {
                    event.preventDefault();

                    if (!this.draggedElement || !this.draggedData) return;

                    // Find drop target
                    const targetRow = this.findNearestRow(event.target);
                    if (!targetRow || targetRow === this.draggedElement) {
                        return;
                    }

                    // Get IDs for reordering
                    const draggedId = parseInt(this.draggedElement.getAttribute('data-id'));
                    const targetId = parseInt(targetRow.getAttribute('data-id'));

                    // Determine if dropping above or below target
                    const rect = targetRow.getBoundingClientRect();
                    const dropAbove = event.clientY < rect.top + rect.height / 2;

                    // Perform the reorder
                    this.reorderData(draggedId, targetId, dropAbove);

                    // Regenerate the table
                    this.regenerateTable();
                }

                /**
                 * Handle drag enter/leave for drop zone feedback
                 * Extension point: Enhanced visual indicators
                 */
                handleDragEnter(event) {
                    if (this.visualFeedbackEnabled) {
                        event.currentTarget.classList.add(this.dropZoneClass);
                    }
                }

                handleDragLeave(event) {
                    // Only remove if we're actually leaving the drop zone
                    if (!event.currentTarget.contains(event.relatedTarget)) {
                        event.currentTarget.classList.remove(this.dropZoneClass);
                    }
                }

                /**
                 * Create visual placeholder - Extension point for animations
                 */
                createPlaceholder(sourceRow) {
                    this.placeholder = sourceRow.cloneNode(true);
                    this.placeholder.classList.add('drag-placeholder');
                    this.placeholder.style.opacity = '0.3';
                    this.placeholder.style.backgroundColor = '#e3f2fd';
                    this.placeholder.style.border = '2px dashed #1976d2';

                    // Insert after source row initially
                    sourceRow.parentNode.insertBefore(this.placeholder, sourceRow.nextSibling);
                }

                /**
                 * Update placeholder position during drag
                 */
                updatePlaceholderPosition(targetRow, mouseY) {
                    if (!this.placeholder) return;

                    const rect = targetRow.getBoundingClientRect();
                    const dropAbove = mouseY < rect.top + rect.height / 2;

                    if (dropAbove) {
                        targetRow.parentNode.insertBefore(this.placeholder, targetRow);
                    } else {
                        targetRow.parentNode.insertBefore(this.placeholder, targetRow.nextSibling);
                    }
                }

                /**
                 * Remove placeholder
                 */
                removePlaceholder() {
                    if (this.placeholder) {
                        this.placeholder.remove();
                        this.placeholder = null;
                    }
                }

                /**
                 * Find the nearest table row to the given element
                 */
                findNearestRow(element) {
                    while (element && element.tagName !== 'TR') {
                        element = element.parentElement;
                    }

                    // Only exclude placeholder (the real problem)
                    if (element && element.hasAttribute('data-id')) {
                        // During drop, we need to find the nearest valid target
                        // Skip the placeholder, but look for siblings
                        if (element.classList.contains('drag-placeholder')) {
                            // Try to find a non-placeholder sibling
                            let sibling = element.nextElementSibling;
                            while (sibling && (!sibling.hasAttribute('data-id') || sibling.classList.contains('drag-placeholder'))) {
                                sibling = sibling.nextElementSibling;
                            }
                            if (!sibling) {
                                sibling = element.previousElementSibling;
                                while (sibling && (!sibling.hasAttribute('data-id') || sibling.classList.contains('drag-placeholder'))) {
                                    sibling = sibling.previousElementSibling;
                                }
                            }
                            if (sibling && sibling.hasAttribute('data-id')) {
                                return sibling;
                            }
                        } else {
                            return element;
                        }
                    }

                    console.log('❌ Returning null - invalid TR');
                    return null;
                }

                /**
                 * Get size data for the current tab type
                 */
                getSizeData(sizeId) {
                    let dataArray;
                    if (this.currentTabType === 'class') {
                        dataArray = spaceClampAjax.data.classSizes;
                    } else if (this.currentTabType === 'vars') {
                        dataArray = spaceClampAjax.data.variableSizes;
                    } else if (this.currentTabType === 'utils') {
                        dataArray = spaceClampAjax.data.utilitySizes;
                    }

                    return dataArray ? dataArray.find(item => item.id === sizeId) : null;
                }

                /**
                 * Reorder data arrays based on drag & drop
                 */
                reorderData(draggedId, targetId, dropAbove) {
                    let dataArray;
                    if (this.currentTabType === 'class') {
                        dataArray = spaceClampAjax.data.classSizes;
                    } else if (this.currentTabType === 'vars') {
                        dataArray = spaceClampAjax.data.variableSizes;
                    } else if (this.currentTabType === 'utils') {
                        dataArray = spaceClampAjax.data.utilitySizes;
                    }

                    if (!dataArray) return;

                    // Find current positions
                    const draggedIndex = dataArray.findIndex(item => item.id === draggedId);
                    const targetIndex = dataArray.findIndex(item => item.id === targetId);

                    if (draggedIndex === -1 || targetIndex === -1) return;

                    // Remove dragged item
                    const [draggedItem] = dataArray.splice(draggedIndex, 1);

                    // Calculate new position
                    let newIndex = targetIndex;
                    if (draggedIndex < targetIndex) {
                        newIndex--; // Adjust for removed item
                    }

                    if (!dropAbove) {
                        newIndex++; // Drop after target
                    }

                    // Insert at new position
                    dataArray.splice(newIndex, 0, draggedItem);
                }

                /**
                 * Regenerate table after reorder
                 */
                regenerateTable() {
                    const panelContainer = document.getElementById('sizes-table-container');
                    if (panelContainer) {
                        panelContainer.innerHTML = generatePanelContent(this.currentTabType);

                        // Reattach all event listeners
                        attachEventListeners();

                        // Reinitialize drag & drop for new table
                        this.initializeTable(this.currentTabType);
                        // Update table values and CSS outputs
                        updateDataTableValues(getSelectedBaseId());
                        updateCSSOutputs();
                    }
                }

                /**
                 * Extension point: Enable enhanced animations (Complete phase)
                 */
                enableEnhancedAnimations() {
                    this.animationDuration = 300;
                    // Future: Add smooth transitions, ghost images, etc.
                }

                /**
                 * Extension point: Add custom visual feedback (Complete phase)
                 */
                setCustomFeedback(options) {
                    Object.assign(this, options);
                    // Future: Custom drop zones, insertion indicators, etc.
                }
                /**
                 * Helper: Get current data array
                 */
                getCurrentDataArray() {
                    if (this.currentTabType === 'class') {
                        return spaceClampAjax.data.classSizes;
                    } else if (this.currentTabType === 'vars') {
                        return spaceClampAjax.data.variableSizes;
                    } else if (this.currentTabType === 'utils') {
                        return spaceClampAjax.data.utilitySizes;
                    }
                    return [];
                }

                /**
                 * Helper: Get item name for debugging
                 */
                getItemName(item) {
                    if (this.currentTabType === 'class') return item.className;
                    if (this.currentTabType === 'vars') return item.variableName;
                    if (this.currentTabType === 'utils') return item.utilityName;
                    return 'unknown';
                }
            }

            // Get display name based on tab type
            function getDisplayName(size, tabType) {
                if (tabType === 'class') return size.className;
                if (tabType === 'vars') return size.variableName;
                if (tabType === 'utils') return size.utilityName;
                return 'unknown';
            }

            // Initialize basic functionality  
            document.addEventListener('DOMContentLoaded', () => {

                // About toggle
                const aboutToggle = document.querySelector('[data-toggle-target="about-content"]');
                if (aboutToggle) {
                    aboutToggle.addEventListener('click', () => {
                        const content = document.getElementById('about-content');
                        const button = aboutToggle;
                        if (content && button) {
                            content.classList.toggle('expanded');
                            button.classList.toggle('expanded');
                        }
                    });
                }

                // Tab switching
                const tabButtons = document.querySelectorAll('.tab-button');
                tabButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        // Remove active class from all tabs
                        tabButtons.forEach(tab => tab.classList.remove('active'));
                        // Add active class to clicked tab
                        button.classList.add('active');

                        const tabName = button.getAttribute('data-tab');
                        const panelContainer = document.getElementById('sizes-table-container');

                        if (panelContainer) {
                            panelContainer.innerHTML = generatePanelContent(tabName);

                            const generatedCode = document.getElementById('generated-code');
                            if (generatedCode) {
                                let css = '';
                                const settings = spaceClampAjax.data.settings;
                                const currentSizes = tabName === 'class' ? spaceClampAjax.data.classSizes :
                                    tabName === 'vars' ? spaceClampAjax.data.variableSizes :
                                    spaceClampAjax.data.utilitySizes;

                                if (tabName === 'class') {
                                    css = generateClassesCSS(currentSizes, settings, getSelectedBaseId());
                                } else if (tabName === 'vars') {
                                    css = generateVariablesCSS(currentSizes, settings, getSelectedBaseId());
                                } else if (tabName === 'utils') {
                                    css = generateUtilitiesCSS(currentSizes, settings, getSelectedBaseId());
                                }

                                generatedCode.textContent = css;
                                generatespacePreview(tabName, currentSizes, getSelectedBaseId());
                                attachEventListeners();
                            }
                        }
                    });
                });

                // Generate initial content
                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    panelContainer.innerHTML = generatePanelContent('class');

                    const generatedCode = document.getElementById('generated-code');
                    if (generatedCode) {
                        const settings = spaceClampAjax.data.settings;
                        const currentSizes = spaceClampAjax.data.classSizes;
                        const css = generateClassesCSS(currentSizes, settings, getSelectedBaseId());
                        generatedCode.textContent = css;
                    }

                    generatespacePreview('class', spaceClampAjax.data.classSizes, getSelectedBaseId());

                    // Add event handlers for space input changes
                    const spaceInputs = ['min-base-space', 'max-base-space', 'min-viewport', 'max-viewport', 'min-scale', 'max-scale'];

                    // Initialize drag & drop manager
                    const dragDropManager = new DragDropManager();
                    window.dragDropManager = dragDropManager;

                    // Attach initial event listeners
                    attachEventListeners();
                }
            });

            // Generate reusable action buttons HTML
            function generateActionButtons() {
                return `
        <div class="fcc-table-buttons">
            <button id="add-size" class="fcc-btn">add size</button>
            <button id="reset-defaults" class="fcc-btn">reset</button>
            <button id="clear-sizes" class="fcc-btn fcc-btn-danger">clear all</button>
        </div>
    `;
            }

            // Panel content generation functions
            function generatePanelContent(tabType) {
                const data = spaceClampAjax.data;

                if (tabType === 'class') {
                    return generateClassesPanel(data.classSizes);
                } else if (tabType === 'vars') {
                    return generateVariablesPanel(data.variableSizes);
                } else if (tabType === 'utils') {
                    return generateUtilitiesPanel(data.utilitySizes);
                }

                return '<p>Unknown tab type</p>';
            }

            function generateClassesPanel(sizes) {
                // Handle empty state
                if (!sizes || sizes.length === 0) {
                    return `
            <h2 style="margin-bottom: 12px;" id="table-title">Space Size Classes</h2>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label class="component-label" style="margin-bottom: 0; white-space: nowrap; opacity: 0.5;">Base</label>
                    <select id="base-value" class="component-select" style="width: 120px; height: 32px;" disabled>
                        <option>No classes</option>
                    </select>
                </div>
                
                ${generateActionButtons()}
            </div>

            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: var(--jimr-border-radius); border: 2px dashed var(--jimr-gray-300);">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;">📭</div>
                <h3 style="color: var(--jimr-gray-600); margin: 0 0 8px 0; font-size: 18px;">No Space Classes</h3>
                <p style="color: var(--jimr-gray-500); margin: 0 0 20px 0; font-size: 14px;">Get started by adding your first space class or reset to defaults.</p>
                <button id="add-first-size" class="fcc-btn" style="margin-right: 12px;">add first class</button>
                <button id="reset-to-defaults" class="fcc-btn">reset to defaults</button>
            </div>
        `;
                }

                return `
        <h2 style="margin-bottom: 12px;" id="table-title">Space Size Classes</h2>
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <label class="component-label" for="base-value" style="margin-bottom: 0; white-space: nowrap;">Base</label>
                <select id="base-value" class="component-select" style="width: 120px; height: 32px;">
                    ${sizes.map(size => `
                        <option value="${size.id}" ${size.id === 3 ? 'selected' : ''}>${size.className}</option>
                    `).join('')}
                </select>
            </div>
            
            ${generateActionButtons()}
        </div>

        <div id="sizes-table-wrapper">
            <table class="font-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>NAME</th>
                        <th>MIN SIZE</th>
                        <th>MAX SIZE</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="sizes-table">
                    ${sizes.map(size => `
                        <tr data-id="${size.id}">
                            <td><div class="drag-handle">⋮⋮</div></td>
                            <td>
                                <input type="text" value="${size.className}" class="size-name-input" 
                                       placeholder="e.g., space-lg">
                            </td>
                            <td>
                                <span class="calculated-value">${(() => {
                                    const calc = calculatespaceSize(size.id, spaceClampAjax.data.settings, getSelectedBaseId());
                                    return calc.minUnit;
                                })()}</span>
                            </td>
                            <td>
                                <span class="calculated-value">${(() => {
                                    const calc = calculatespaceSize(size.id, spaceClampAjax.data.settings, getSelectedBaseId());
                                    return calc.maxUnit;
                                })()}</span>
                            </td>
                            <td>
                                <button class="edit-size" data-id="${size.id}">✎</button>
                                <button class="delete-size" data-id="${size.id}">🗑️</button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
            }

            function generateVariablesPanel(sizes) {
                // Handle empty state
                if (!sizes || sizes.length === 0) {
                    return `
            <h2 style="margin-bottom: 12px;" id="table-title">CSS Custom Properties</h2>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label class="component-label" style="margin-bottom: 0; white-space: nowrap; opacity: 0.5;">Base</label>
                    <select id="base-value" class="component-select" style="width: 120px; height: 32px;" disabled>
                        <option>No variables</option>
                    </select>
                </div>
                
                ${generateActionButtons()}
            </div>

 <div style="text-align: center; padding: 60px 20px; background: white; border-radius: var(--jimr-border-radius); border: 2px dashed var(--jimr-gray-300);">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;">📭</div>
                <h3 style="color: var(--jimr-gray-600); margin: 0 0 8px 0; font-size: 18px;">No CSS Variables</h3>
                <p style="color: var(--jimr-gray-500); margin: 0 0 20px 0; font-size: 14px;">Get started by adding your first CSS variable or reset to defaults.</p>
                <button id="add-first-size" class="fcc-btn" style="margin-right: 12px;">add first variable</button>
                <button id="reset-to-defaults" class="fcc-btn">reset to defaults</button>
            </div>
        `;
                }

                return `
                    <h2 style="margin-bottom: 12px;" id="table-title">CSS Custom Properties</h2>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label class="component-label" for="base-value" style="margin-bottom: 0; white-space: nowrap;">Base</label>
          <select id="base-value" class="component-select" style="width: 120px; height: 32px;">
    ${sizes.map(size => `
 <option value="${size.id}" ${size.id === 3 ? 'selected' : ''}>${size.variableName}</option>
    `).join('')}
</select>
                        </div>

                        ${generateActionButtons()}
                    </div>

                    <div id="sizes-table-wrapper">
                        <table class="font-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>VARIABLE NAME</th>
                                    <th>MIN SIZE</th>
                                    <th>MAX SIZE</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="sizes-table">
                                ${sizes.map(size => `
                                    <tr data-id="${size.id}">
                                        <td><div class="drag-handle">⋮⋮</div></td>
                                        <td>
                                            <input type="text" value="${size.variableName}" class="size-name-input" 
                                                   placeholder="e.g., --space-lg">
                                        </td>
                                        <td>
                                            <span class="calculated-value">${(() => {
const calc = calculatespaceSize(size.id, spaceClampAjax.data.settings, getSelectedBaseId());
return calc.minUnit;
                                            })()}</span>
                                        </td>
                                        <td>
                                            <span class="calculated-value">${(() => {
const calc = calculatespaceSize(size.id, spaceClampAjax.data.settings, getSelectedBaseId());
return calc.maxUnit;
                                            })()}</span>
                                        </td>
                                    <td>
    <button class="edit-size" data-id="${size.id}">✎</button>
    <button class="delete-size" data-id="${size.id}">🗑️</button>
</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            function generateUtilitiesPanel(sizes) {
                // Handle empty state
                if (!sizes || sizes.length === 0) {
                    return `
            <h2 style="margin-bottom: 12px;" id="table-title">Utility Classes</h2>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label class="component-label" style="margin-bottom: 0; white-space: nowrap; opacity: 0.5;">Base</label>
                    <select id="base-value" class="component-select" style="width: 120px; height: 32px;" disabled>
                        <option>No utilities</option>
                    </select>
                </div>
                
              ${generateActionButtons()}
            </div>

            <div style="text-align: center; padding: 60px 20px; background: white; border-radius: var(--jimr-border-radius); border: 2px dashed var(--jimr-gray-300);">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;">📭</div>
                <h3 style="color: var(--jimr-gray-600); margin: 0 0 8px 0; font-size: 18px;">No Utility Classes</h3>
                <p style="color: var(--jimr-gray-500); margin: 0 0 20px 0; font-size: 14px;">Get started by adding your first utility class or reset to defaults.</p>
                <button id="add-first-size" class="fcc-btn" style="margin-right: 12px;">add first utility</button>
                <button id="reset-to-defaults" class="fcc-btn">reset to defaults</button>
            </div>
        `;
                }

                return `
                    <h2 style="margin-bottom: 12px;" id="table-title">Utility Classes</h2>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label class="component-label" for="base-value" style="margin-bottom: 0; white-space: nowrap;">Base</label>
                       <select id="base-value" class="component-select" style="width: 120px; height: 32px;">
    ${sizes.map(size => `
 <option value="${size.id}" ${size.id === 3 ? 'selected' : ''}>${size.utilityName}</option>
   `).join('')}
</select>
                        </div>
                        
                    ${generateActionButtons()}
                    </div>

                    <div id="sizes-table-wrapper">
                        <table class="font-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>UTILITY NAME</th>
                                    <th>PROPERTIES</th>
                                    <th>MIN SIZE</th>
                                    <th>MAX SIZE</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody id="sizes-table">
                                ${sizes.map(size => `
                                    <tr data-id="${size.id}">
                                        <td><div class="drag-handle">⋮⋮</div></td>
                                        <td>
                                            <input type="text" value="${size.utilityName}" class="size-name-input" 
                                                   placeholder="e.g., lg">
                                        </td>
                                        <td>
                                         <div class="utility-properties">
    <span class="property-badge ${(size.properties || ['margin', 'padding']).includes('margin') ? 'active' : 'inactive'}">M</span>
    <span class="property-badge ${(size.properties || ['margin', 'padding']).includes('padding') ? 'active' : 'inactive'}">P</span>
</div>
                                        </td>
                                        <td>
                                            <span class="calculated-value">${(() => {
const calc = calculatespaceSize(size.id, spaceClampAjax.data.settings, getSelectedBaseId());
return calc.minUnit;
                                            })()}</span>
                                        </td>
                                        <td>
                                            <span class="calculated-value">${(() => {
const calc = calculatespaceSize(size.id, spaceClampAjax.data.settings, getSelectedBaseId());
return calc.maxUnit;
                                            })()}</span>
                                        </td>
                                   <td>
    <button class="edit-size" data-id="${size.id}">✎</button>
    <button class="delete-size" data-id="${size.id}">🗑️</button>
</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }
        </script>
<?php
    }

    // ========================================================================
    // AJAX HANDLERS
    // ========================================================================

    public function save_settings()
    {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        // Verify user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        try {
            // Decode and validate settings data
            $settings_json = stripslashes($_POST['settings'] ?? '');
            $settings = json_decode($settings_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => 'Invalid settings data']);
                return;
            }

            // Decode and validate sizes data
            $sizes_json = stripslashes($_POST['sizes'] ?? '');
            $sizes = json_decode($sizes_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => 'Invalid sizes data']);
                return;
            }

            // Save settings
            $result1 = update_option(self::OPTION_SETTINGS, $settings);
            $result2 = update_option(self::OPTION_CLASS_SIZES, $sizes['classSizes'] ?? []);
            $result3 = update_option(self::OPTION_VARIABLE_SIZES, $sizes['variableSizes'] ?? []);
            $result4 = update_option(self::OPTION_UTILITY_SIZES, $sizes['utilitySizes'] ?? []);

            // Clear cached data
            wp_cache_delete(self::OPTION_SETTINGS, 'options');
            wp_cache_delete(self::OPTION_CLASS_SIZES, 'options');
            wp_cache_delete(self::OPTION_VARIABLE_SIZES, 'options');
            wp_cache_delete(self::OPTION_UTILITY_SIZES, 'options');

            wp_send_json_success([
                'message' => 'All space data saved to database successfully',
                'saved_settings' => $result1,
                'saved_sizes' => $result2 && $result3 && $result4
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Save failed: ' . $e->getMessage()]);
        }
    }
}

// ========================================================================
// INITIALIZATION
// ========================================================================

// Initialize the Space Clamp Calculator
if (is_admin()) {
    global $spaceClampCalculator;
    $spaceClampCalculator = new SpaceClampCalculator();
}
