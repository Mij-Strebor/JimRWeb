<?php

/**
 * Fluid Button Forge - Inline Editing Version (Cleaned)
 * Version: 1
 * Generates responsive button designs using CSS clamp() functions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fluid Button Forge - Complete Unified Class
 */
class ButtonDesignCalculator
{
    // ========================================================================
    // CORE CONSTANTS SYSTEM
    // ========================================================================

    // Configuration Constants
    const VERSION = '1.0';
    const PLUGIN_SLUG = 'fluid-button-forge';
    const NONCE_ACTION = 'fluid_button_nonce';

    // Validation Ranges
    const MIN_BUTTON_SIZE_RANGE = [1, 200];
    const VIEWPORT_RANGE = [200, 5000];

    // Default Values - PRIMARY CONSTANTS
    const DEFAULT_MIN_BASE_SIZE = 16;
    const DEFAULT_MAX_BASE_SIZE = 20;
    const DEFAULT_MIN_VIEWPORT = 375;
    const DEFAULT_MAX_VIEWPORT = 1620;

    // Browser and system constants
    const BROWSER_DEFAULT_FONT_SIZE = 16;
    const CSS_UNIT_CONVERSION_BASE = 16;

    // Valid Options
    const VALID_UNITS = ['px', 'rem'];

    // WordPress Options Keys
    const OPTION_SETTINGS = 'button_design_settings';
    const OPTION_CLASS_SIZES = 'button_design_class_sizes';
    const OPTION_COLORS = 'button_design_colors';

    // ========================================================================
    // CLASS PROPERTIES
    // ========================================================================

    private $default_settings;
    private $default_class_sizes;
    private $default_colors;
    private $assets_loaded = false;

    // ========================================================================
    // CORE INITIALIZATION
    // ========================================================================

    public function __construct()
    {
        $this->init_defaults();
        $this->init_hooks();
    }

    private function init_defaults()
    {
        $this->default_settings = $this->create_default_settings();
        $this->default_class_sizes = $this->create_default_sizes('class');
        $this->default_colors = $this->create_default_colors();
    }

    private function init_hooks()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_save_button_design_settings', [$this, 'save_settings']);
        add_action('admin_footer', [$this, 'render_unified_assets'], 10);
    }

    // ========================================================================
    // DATA MANAGEMENT METHODS
    // ========================================================================

    private function create_default_settings()
    {
        return [
            'minBaseSize' => self::DEFAULT_MIN_BASE_SIZE,
            'maxBaseSize' => self::DEFAULT_MAX_BASE_SIZE,
            'minViewport' => self::DEFAULT_MIN_VIEWPORT,
            'maxViewport' => self::DEFAULT_MAX_VIEWPORT,
            'unitType' => 'px',
            'autosaveEnabled' => true
        ];
    }

    private function create_default_sizes()
    {
        $config = [
            ['id' => 1, 'name' => 'btn-sm', 'width' => 120, 'height' => 32, 'paddingX' => 12, 'paddingY' => 6, 'fontSize' => 14, 'borderRadius' => 4, 'borderWidth' => 1],
            ['id' => 2, 'name' => 'btn-md', 'width' => 160, 'height' => 40, 'paddingX' => 16, 'paddingY' => 8, 'fontSize' => 16, 'borderRadius' => 6, 'borderWidth' => 2],
            ['id' => 3, 'name' => 'btn-lg', 'width' => 200, 'height' => 48, 'paddingX' => 20, 'paddingY' => 10, 'fontSize' => 18, 'borderRadius' => 8, 'borderWidth' => 2]
        ];

        return array_map(function ($item) {
            return [
                'id' => $item['id'],
                'className' => $item['name'],
                'width' => $item['width'],
                'height' => $item['height'],
                'paddingX' => $item['paddingX'],
                'paddingY' => $item['paddingY'],
                'fontSize' => $item['fontSize'],
                'borderRadius' => $item['borderRadius'],
                'borderWidth' => $item['borderWidth']
            ];
        }, $config);
    }

    // Create default colors for button states
    // This includes Normal, Hover, Active, and Disabled states   
    private function create_default_colors()
    {
        return [
            'normal' => [
                'background' => 'var(--clr-accent)',
                'text' => 'var(--clr-btn-txt)',
                'border' => 'var(--clr-btn-bdr)',
                'useBorder' => true
            ],
            'hover' => [
                'background' => 'var(--clr-btn-hover)',
                'text' => 'var(--clr-btn-txt)',
                'border' => 'var(--clr-btn-bdr)',
                'useBorder' => true
            ],
            'active' => [
                'background' => [
                    'type' => 'solid',
                    'solid' => '#DAA520',
                    'gradient' => [
                        'type' => 'linear',
                        'angle' => 135,
                        'stops' => [
                            ['color' => '#DAA520', 'position' => 0],
                            ['color' => '#FF7F00', 'position' => 100]
                        ]
                    ]
                ],
                'text' => 'var(--clr-btn-txt)',
                'border' => 'var(--clr-btn-bdr)',
                'useBorder' => true
            ],
            'disabled' => [
                'background' => [
                    'type' => 'solid',
                    'solid' => 'var(--jimr-gray-300)',
                    'gradient' => [
                        'type' => 'linear',
                        'angle' => 135,
                        'stops' => [
                            ['color' => 'var(--jimr-gray-300)', 'position' => 0],
                            ['color' => 'var(--jimr-gray-400)', 'position' => 100]
                        ]
                    ]
                ],
                'text' => 'var(--jimr-gray-600)',
                'border' => 'var(--jimr-gray-400)',
                'useBorder' => true
            ]
        ];
    }

    // ========================================================================
    // ADMIN INTERFACE
    // ========================================================================

    /**
     * Add admin menu page
     */
    public function add_admin_menu()
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

        // Add Fluid Font Forge as submenu under J Forge
        add_submenu_page(
            'j-forge',                       // Parent slug
            'Fluid Button Forge',              // Page title
            'Fluid Button',                    // Menu title
            'manage_options',                // Capability
            self::PLUGIN_SLUG,               // Menu slug
            [$this, 'render_admin_page']     // Callback
        );
    }


    public function enqueue_assets()
    {
        $screen = get_current_screen();

        if (!$screen || !isset($_GET['page']) || $_GET['page'] !== self::PLUGIN_SLUG) {
            return;
        }

        wp_enqueue_style(
            'button-design-tailwind',
            'https://cdn.tailwindcss.com',
            [],
            self::VERSION
        );

        wp_enqueue_script('wp-util');

        wp_localize_script('wp-util', 'buttonDesignAjax', [
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'defaults' => [
                'minBaseSize' => self::DEFAULT_MIN_BASE_SIZE,
                'maxBaseSize' => self::DEFAULT_MAX_BASE_SIZE,
                'minViewport' => self::DEFAULT_MIN_VIEWPORT,
                'maxViewport' => self::DEFAULT_MAX_VIEWPORT,
            ],
            'data' => [
                'settings' => $this->get_button_design_settings(),
                'classSizes' => $this->get_button_design_class_sizes(),
                'colors' => $this->get_button_design_colors()
            ],
            'constants' => $this->get_all_constants(),
            'version' => self::VERSION,
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);
    }

    public function get_all_constants()
    {
        return [
            'DEFAULT_MIN_BASE_SIZE' => self::DEFAULT_MIN_BASE_SIZE,
            'DEFAULT_MAX_BASE_SIZE' => self::DEFAULT_MAX_BASE_SIZE,
            'DEFAULT_MIN_VIEWPORT' => self::DEFAULT_MIN_VIEWPORT,
            'DEFAULT_MAX_VIEWPORT' => self::DEFAULT_MAX_VIEWPORT,
            'BROWSER_DEFAULT_FONT_SIZE' => self::BROWSER_DEFAULT_FONT_SIZE,
            'CSS_UNIT_CONVERSION_BASE' => self::CSS_UNIT_CONVERSION_BASE,
            'MIN_BUTTON_SIZE_RANGE' => self::MIN_BUTTON_SIZE_RANGE,
            'VIEWPORT_RANGE' => self::VIEWPORT_RANGE,
            'VALID_UNITS' => self::VALID_UNITS
        ];
    }

    // ========================================================================
    // DATA GETTERS
    // ========================================================================

    public function get_button_design_settings()
    {
        static $cached_settings = null;

        if ($cached_settings === null) {
            $settings = wp_parse_args(
                get_option(self::OPTION_SETTINGS, []),
                $this->default_settings
            );

            $cached_settings = $settings;
        }

        return $cached_settings;
    }

    public function get_button_design_class_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_CLASS_SIZES, $this->default_class_sizes);
        }
        return $cached_sizes;
    }

    public function get_button_design_colors()
    {
        static $cached_colors = null;
        if ($cached_colors === null) {
            $cached_colors = get_option(self::OPTION_COLORS, $this->default_colors);
        }
        return $cached_colors;
    }

    // ========================================================================
    // MAIN ADMIN PAGE RENDERER
    // ========================================================================

    public function render_admin_page()
    {
        $data = [
            'settings' => $this->get_button_design_settings(),
            'class_sizes' => $this->get_button_design_class_sizes(),
            'colors' => $this->get_button_design_colors()
        ];

        echo $this->get_complete_interface($data);
    }

    private function get_complete_interface($data)
    {
        $settings = $data['settings'];
        $colors = $data['colors'];

        ob_start();
?>
        <div class="wrap" style="background: var(--clr-page-bg); padding: 20px; min-height: 100vh;">
            <div class="header-section">
                <h1 class="text-2xl font-bold mb-4">Fluid Button Forge (1.0)</h1><br>

                <!-- About Section -->
                <div class="about-panel-container">
                    <div>
                        <button class="fcc-info-toggle expanded" data-toggle-target="about-content">
                            <span style="color: #FAF9F6 !important;">🎨 About Fluid Button Forge</span>
                            <span class="fcc-toggle-icon" style="color: #FAF9F6 !important;">▼</span>
                        </button>
                    </div>
                    <div class="collapsible-text expanded" id="about-content">
                        <div style="color: var(--clr-txt); font-size: 14px; line-height: 1.6;">
                            <p style="margin: 0 0 16px 0; color: var(--clr-txt);">
                                Create professional button systems for your website! Design responsive Call-to-Action buttons, primary navigation buttons, secondary actions, and form submit buttons that scale perfectly across all devices. This tool generates CSS clamp() functions for consistent button hierarchies that maintain their proportions from mobile to desktop, ensuring your CTAs and interactive elements look perfect everywhere.
                            </p>
                            <div style="background: rgba(60, 32, 23, 0.1); padding: 12px 16px; border-radius: 6px; border-left: 4px solid var(--clr-accent); margin-top: 20px;">
                                <p style="margin: 0; font-size: 13px; opacity: 0.95; line-height: 1.5; color: var(--clr-txt);">
                                    Fluid Button Forge by Jim R. (<a href="https://jimrweb.com" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">JimRWeb</a>), part of the CSS Tools series developed with Claude AI (<a href="https://anthropic.com" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">Anthropic</a>).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Section -->
            <div class="main-panel-container" id="bdc-main-container">
                <!-- How to Use Panel -->
                <div class="full-width-styling">
                    <div class="major-panel-header">
                        <button class="fcc-info-toggle expanded" data-toggle-target="info-content">
                            <span style="color: #FAF9F6 !important;">ℹ️ How to Use Fluid Button Forge</span>
                            <span class="fcc-toggle-icon" style="color: #FAF9F6 !important;">▼</span>
                        </button>
                    </div>
                    <div class="collapsible-text expanded" id="info-content">
                        <div style="color: var(--clr-txt); font-size: 14px; line-height: 1.6;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 20px;">
                                <div>
                                    <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">1. Configure Settings</h4>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.5;">Set your base size, viewport range, and scaling ratios. Choose units and configure colors for different button states.</p>
                                </div>
                                <div>
                                    <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">2. Design Button Sizes</h4>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.5;">Edit button properties directly in each card - click any value to modify it. Colors and states can be changed per button.</p>
                                </div>
                                <div>
                                    <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">3. Preview Buttons</h4>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.5;">See live previews showing how your buttons will look at different screen sizes and in all four states: Normal, Hover, Active, and Disabled.</p>
                                </div>
                                <div>
                                    <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">4. Generate CSS</h4>
                                    <p style="margin: 0; font-size: 13px; line-height: 1.5;">Copy responsive CSS with clamp() functions ready to use in your projects. Available as classes or CSS custom properties.</p>
                                </div>
                            </div>

                            <div style="background: #F0E6DA; padding: 12px 16px; border-radius: 8px; border: 1px solid #5C3324; margin: 1rem 10rem; text-align: center;">
                                <h4 style="color: #3C2017; font-size: 14px; font-weight: 600; margin: 0 0 6px 0;">💡 Pro Tip</h4>
                                <p style="margin: 0; font-size: 13px; color: var(--clr-txt);">All editing is now inline - just click on any value to change it. Button names can be edited by clicking the name in the header.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Full Width Settings Panel -->
                <div class="full-width-styling" style="margin-bottom: 24px 0;">
                    <div class="major-panel-content">
                        <div style="margin-bottom: 20px;">
                            <h2 style="color: var(--clr-primary); margin: 0;">Settings</h2>
                        </div>

                        <!-- Settings in horizontal layout -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px;">
                            <div class="grid-item">
                                <label class="component-label" for="min-base-size">Min Viewport Font Size (px)</label>
                                <input type="number" id="min-base-size" value="<?php echo esc_attr($settings['minBaseSize'] ?? self::DEFAULT_MIN_BASE_SIZE); ?>"
                                    class="component-input" style="width: 100%;"
                                    min="<?php echo self::MIN_BUTTON_SIZE_RANGE[0]; ?>"
                                    max="<?php echo self::MIN_BUTTON_SIZE_RANGE[1]; ?>"
                                    step="1"
                                    data-tooltip="Starting button size at the smallest screen width. This is the minimum size your buttons will ever be.">
                            </div>
                            <div class="grid-item">
                                <label class="component-label" for="min-viewport">Min Viewport Width (px)</label>
                                <input type="number" id="min-viewport" value="<?php echo esc_attr($settings['minViewport']); ?>"
                                    class="component-input" style="width: 100%;"
                                    min="<?php echo self::VIEWPORT_RANGE[0]; ?>"
                                    max="<?php echo self::VIEWPORT_RANGE[1]; ?>"
                                    step="1"
                                    data-tooltip="Screen width where minimum button sizes apply. Typically mobile device width (375px).">
                            </div>
                            <div class="grid-item">
                                <label class="component-label" for="max-base-size">Max Viewport Font Size (px)</label>
                                <input type="number" id="max-base-size" value="<?php echo esc_attr($settings['maxBaseSize'] ?? self::DEFAULT_MAX_BASE_SIZE); ?>"
                                    class="component-input" style="width: 100%;"
                                    min="<?php echo self::MIN_BUTTON_SIZE_RANGE[0]; ?>"
                                    max="<?php echo self::MIN_BUTTON_SIZE_RANGE[1]; ?>"
                                    step="1"
                                    data-tooltip="Target button size at the largest screen width. This is the maximum size your buttons will reach.">
                            </div>
                            <div class="grid-item">
                                <label class="component-label" for="max-viewport">Max Viewport Width (px)</label>
                                <input type="number" id="max-viewport" value="<?php echo esc_attr($settings['maxViewport']); ?>"
                                    class="component-input" style="width: 100%;"
                                    min="<?php echo self::VIEWPORT_RANGE[0]; ?>"
                                    max="<?php echo self::VIEWPORT_RANGE[1]; ?>"
                                    step="1"
                                    data-tooltip="Screen width where maximum button sizes apply. Typically large desktop width (1620px).">
                            </div>
                            <div class="grid-item">
                                <label class="component-label">Button Units</label>
                                <div class="font-units-buttons" data-tooltip="Choose pixel units for precise control or rem units for accessibility and user preference scaling.">
                                    <button id="px-tab" class="unit-button <?php echo $settings['unitType'] === 'px' ? 'active' : ''; ?>" data-unit="px"
                                        data-tooltip="Pixel units provide exact, predictable sizing but don't scale with user browser settings">PX</button>
                                    <button id="rem-tab" class="unit-button <?php echo $settings['unitType'] === 'rem' ? 'active' : ''; ?>" data-unit="rem"
                                        data-tooltip="Rem units scale with user's browser font size settings for better accessibility">REM</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Button Classes Panel -->
                <div class="fcc-main-grid" style="grid-template-columns: 1fr;">
                    <div>
                        <div class="fcc-panel" id="sizes-table-container">
                            <div id="sizes-table-wrapper">
                                <div style="text-align: center; color: #6b7280; font-style: italic; padding: 40px 20px;">
                                    <div class="fcc-loading-spinner" style="width: 25px; height: 25px; margin: 0 auto 10px;"></div>
                                    <div>Loading button classes...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Full-Width Preview Section -->
            <div class="full-width-styling">
                <div class="major-panel-content">
                    <div class="fcc-preview-header-row">
                        <h2 style="color: var(--clr-primary); margin: 0;">Button Preview</h2>
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
                                    <div>Generating button previews...</div>
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
                                    <div>Generating button previews...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Button CSS Panel -->
            <div class="full-width-styling" style="margin-top: 20px;">
                <div class="major-panel-content" id="selected-css-container">
                    <div class="fcc-css-header">
                        <h2 style="flex-grow: 1;" id="selected-code-title">Selected Button CSS</h2>
                        <div class="fcc-css-buttons" id="selected-copy-buttons">
                            <button id="copy-selected-btn" class="fcc-copy-btn"
                                data-tooltip="Copy selected button CSS to clipboard"
                                title="Copy Selected CSS">
                                <span class="copy-icon">📋</span> copy selected
                            </button>
                        </div>
                    </div>
                    <div style="background: white; border-radius: 6px; padding: 8px; border: 1px solid #d1d5db; overflow: auto; max-height: 300px;">
                        <pre id="selected-code" style="font-size: 12px; white-space: pre-wrap; color: #111827; margin: 0;">/* Click a button card to select it and view its CSS */</pre>
                    </div>
                </div>
            </div>

            <!-- Full Width CSS Output Containers -->
            <div class="full-width-styling" style="margin-top: 20px;">
                <div class="major-panel-content" id="generated-css-container">
                    <div class="fcc-css-header">
                        <h2 style="flex-grow: 1;" id="generated-code-title">Generated CSS (All Button Classes)</h2>
                        <div class="fcc-css-buttons" id="generated-copy-buttons">
                            <button id="copy-all-btn" class="fcc-copy-btn"
                                data-tooltip="Copy all generated CSS to clipboard"
                                title="Copy All CSS">
                                <span class="copy-icon">📋</span> copy all
                            </button>
                        </div>
                    </div>
                    <div style="background: white; border-radius: 6px; padding: 8px; border: 1px solid #d1d5db; overflow: auto; max-height: 400px;">
                        <pre id="generated-code" style="font-size: 12px; white-space: pre-wrap; color: #111827; margin: 0;">/* Loading CSS output... */</pre>
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

    public function render_unified_assets()
    {
        if (!$this->is_button_design_page() || $this->assets_loaded) {
            return;
        }

        $this->render_unified_css();
        $this->render_basic_javascript();

        $this->assets_loaded = true;
    }

    private function is_button_design_page()
    {
        return isset($_GET['page']) && sanitize_text_field($_GET['page']) === self::PLUGIN_SLUG;
    }

    private function render_unified_css()
    {
    ?>
        <style id="button-design-unified-styles">
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
                --clr-header-text: #FAF9F6;

                /* Extended Utility Colors */
                --jimr-success: #10b981;
                --jimr-success-dark: #059669;
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

            /* Hide WordPress admin footer on this page */
            #wpfooter {
                display: none !important;
            }

            .wp-footer {
                display: none !important;
            }

            #footer-thankyou {
                display: none !important;
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

            /* Color Input Styles */
            .color-input {
                height: 40px;
                padding: 4px;
                border: 2px solid var(--clr-secondary);
                border-radius: var(--jimr-border-radius);
                background: white;
                cursor: pointer;
                transition: var(--jimr-transition-slow);
            }

            .color-input:focus {
                outline: none;
                border-color: var(--clr-accent);
                box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
            }

            .color-input:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            /* Validation Feedback Styles */
            .validation-error {
                border-color: var(--jimr-danger) !important;
                box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
                animation: shake 0.5s ease-in-out;
            }

            .validation-corrected {
                border-color: var(--jimr-warning) !important;
                box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2) !important;
                animation: pulse-warning 1s ease-in-out;
            }

            @keyframes shake {

                0%,
                100% {
                    transform: translateX(0);
                }

                25% {
                    transform: translateX(-4px);
                }

                75% {
                    transform: translateX(4px);
                }
            }

            @keyframes pulse-warning {

                0%,
                100% {
                    opacity: 1;
                }

                50% {
                    opacity: 0.7;
                }
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
                grid-template-columns: 1fr;
                gap: var(--jimr-space-6);
                margin-bottom: var(--jimr-space-6);
            }

            /* Preview Section */
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
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: var(--jimr-space-6) !important;
                align-items: start !important;
                width: 100%;
            }

            .fcc-preview-column {
                display: flex;
                flex-direction: column;
                gap: var(--jimr-space-3);
                min-width: 0;
                /* Prevents overflow issues */
            }

            @media (max-width: 1024px) {
                .fcc-preview-grid {
                    grid-template-columns: 1fr !important;
                    gap: var(--jimr-space-4) !important;
                }
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

            /* Header Section and Main Container */
            .header-section,
            .main-panel-container {
                width: 1280px;
                margin: 0 auto;
            }

            .about-panel-container,
            .main-panel-container,
            .full-width-styling {
                overflow: hidden;
                background: linear-gradient(135deg, var(--clr-light), var(--clr-card-bg));
                border: 2px solid var(--clr-primary);
                border-radius: var(--jimr-border-radius-lg);
                box-shadow: var(--clr-shadow-xl);
            }

            .about-panel-container {
                margin: var(--jimr-space-6) 0;
            }

            .full-width-styling {
                margin-bottom: var(--jimr-space-6);
            }

            /* Standardized Panel Content System */
            .major-panel-content {
                padding: var(--jimr-space-5);
                background: var(--clr-light);
            }

            .collapsible-text {
                margin: var(--jimr-space-3)
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

            /* Collapsible Content Animation */
            .collapsible-text {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.4s ease-out, padding 0.3s ease;
                padding: 0 var(--jimr-space-5);
            }

            .collapsible-text.expanded {
                max-height: 1000px;
                transition: max-height 0.5s ease-in, padding 0.3s ease;
                padding: var(--jimr-space-3) var(--jimr-space-5);
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

            /* Loading Screen */
            .fcc-loading-spinner {
                width: 60px;
                height: 60px;
                border: 5px solid var(--clr-light);
                border-top: 5px solid var(--clr-accent);
                border-right: 5px solid var(--clr-btn-hover);
                border-radius: 50%;
                animation: fcc-spin 1.2s linear infinite;
                margin: 0 auto 25px;
                box-shadow: var(--clr-shadow);
            }

            @keyframes fcc-spin {
                to {
                    transform: rotate(360deg);
                }
            }

            /* Button Preview Styles */
            .preview-button {
                border: 2px solid;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                text-decoration: none;
                transition: all 0.2s ease;
                margin: 8px;
                font-family: inherit;
            }

            .preview-button:focus {
                outline: 2px solid #3b82f6;
                outline-offset: 2px;
            }

            /* Button Card Layout Styles */
            .button-card {
                border: 2px solid var(--clr-secondary);
                border-radius: var(--jimr-border-radius-lg);
                overflow: hidden;
                box-shadow: var(--clr-shadow);
                background: white;
                display: block;
                position: static;
                width: 100%;
                box-sizing: border-box;
                cursor: pointer;
                transition: var(--jimr-transition);
            }

            .button-card:hover {
                transform: translateY(-2px);
                box-shadow: var(--clr-shadow-lg);
            }

            .button-card.selected {
                border-color: var(--clr-accent);
                box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.3), var(--clr-shadow-lg);
                transform: translateY(-2px);
            }

            @media (min-width: 1024px) {
                .button-card {
                    width: calc(50% - 12px);
                }
            }

            /* Ensure flex gap works properly */
            .fcc-panel [style*="gap: 24px"]>.button-card {
                margin: 0 !important;
            }

            .button-card-header {
                background: var(--clr-secondary);
                color: #FAF9F6;
                padding: 12px 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .button-card-content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0;
                background: var(--clr-card-bg);
                padding-bottom: 12px;
            }

            .button-properties-panel {
                background: var(--clr-light);
                padding: 16px;
                position: relative;
                border-radius: 6px;
                border-right: 2px solid var(--clr-secondary);
            }

            .button-states-panel {
                background: var(--clr-light);
                padding: 16px;
                border-radius: 6px;
            }

            .card-panel-title {
                color: var(--clr-primary);
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 12px;
                border-bottom: 1px solid var(--clr-secondary);
                padding-bottom: 6px;
                margin-top: 0;
            }

            .button-card-content .card-panel-title:first-child {
                margin-top: 0;
            }

            .card-property-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
                font-size: 13px;
            }

            .card-property-label {
                color: var(--clr-txt);
                font-weight: 500;
            }

            .card-property-input {
                width: 60px;
                padding: 2px 4px;
                border: 1px solid #ccc;
                border-radius: 3px;
                text-align: right;
                font-size: 12px;
            }

            .card-state-buttons {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px;
                margin-bottom: 12px;
            }

            .card-state-button {
                background: var(--jimr-gray-200);
                color: var(--jimr-gray-700);
                border: 1px solid var(--jimr-gray-300);
                padding: 6px 8px;
                border-radius: 4px;
                font-size: 11px;
                font-weight: 600;
                cursor: pointer;
                text-align: center;
                transition: var(--jimr-transition);
            }

            .card-state-button:hover {
                background: var(--jimr-gray-300);
            }

            .card-state-button.active {
                background: var(--clr-secondary);
                color: #FAF9F6;
                border-color: var(--clr-primary);
            }

            .card-checkbox-row {
                display: flex;
                align-items: center;
                gap: 6px;
                margin-bottom: 6px;
                font-size: 11px;
                flex-wrap: wrap;
            }

            .card-color-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .card-color-section {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .card-color-label {
                font-size: 11px;
                color: var(--clr-txt);
                font-weight: 500;
            }

            .card-color-input {
                height: 32px;
                border: 1px solid var(--clr-secondary);
                border-radius: 4px;
                cursor: pointer;
                transition: var(--jimr-transition);
            }

            .card-color-input:focus {
                outline: none;
                border-color: var(--clr-accent);
                box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
            }

            .card-color-input:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .header-preview-container {
                background: rgba(240, 230, 218, 0.95);
                border-radius: 6px;
                border: 2px solid rgba(60, 32, 23, 0.3);
                box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 80px;
            }

            .header-preview-btn {
                background: var(--clr-accent);
                color: var(--clr-btn-txt);
                border: 2px solid var(--clr-btn-bdr);
                font-weight: 600;
                cursor: pointer;
                transition: all 0.2s ease;
                font-family: inherit;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .card-action-buttons {
                display: flex;
                gap: 8px;
            }

            .card-action-btn {
                background: rgba(255, 255, 255, 0.2);
                border: 1px solid rgba(255, 255, 255, 0.3);
                color: var(--clr-header-text);
                padding: 8px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.2s ease;
                font-weight: 500;
            }

            .card-action-btn:hover {
                background: rgba(255, 255, 255, 0.3);
                transform: translateY(-1px);
            }

            .card-action-btn.card-delete-btn {
                background: rgba(220, 53, 69, 0.8);
                border: 1px solid rgba(220, 53, 69, 1);
            }

            .card-action-btn.card-delete-btn:hover {
                background: rgba(220, 53, 69, 0.9);
            }

            /* Inline Name Editing */
            .editable-name {
                background: rgba(255, 255, 255, 0.15);
                border: 1px solid rgba(255, 255, 255, 0.4);
                color: #FAF9F6;
                font-weight: 600;
                font-size: 16px;
                padding: 2px 4px;
                border-radius: 3px;
                cursor: pointer;
                transition: all 0.2s ease;
                min-width: 120px;
            }

            .editable-name:hover {
                background: var(--clr-light);
                border-color: var(--clr-secondary);
            }

            .editable-name:focus {
                outline: none;
                background: white;
                color: var(--clr-txt);
                border-color: var(--clr-accent);
                box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.3);
            }

            @media (max-width: 768px) {
                .button-card-content {
                    grid-template-columns: 1fr;
                }

                .button-properties-panel {
                    border-right: none;
                    border-bottom: 2px solid var(--clr-secondary);
                    margin: 0 12px 12px 12px;
                }
            }

            /* Responsive */
            @media (max-width: 768px) {
                .fcc-main-grid {
                    grid-template-columns: 1fr;
                    gap: var(--jimr-space-4);
                }

                .fcc-info-content div[style*="grid-template-columns: 1fr 1fr"] {
                    grid-template-columns: 1fr !important;
                    gap: var(--jimr-space-4) !important;
                }
            }

            #bdc-main-container {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }

            /* Drag Handle Styles */
            .drag-handle {
                cursor: grab !important;
                user-select: none;
                padding: 4px;
                color: var(--clr-header-text);
                font-weight: bold;
                transition: color 0.2s ease;
            }

            .drag-handle:hover {
                color: var(--clr-secondary);
            }

            .drag-handle:active {
                cursor: grabbing !important;
            }
        </style>
    <?php
    }

    private function render_basic_javascript()
    {
    ?>
        <script id="button-design-basic-script">
            // ========================================================================
            // GLOBAL STATE AND DATA
            // ========================================================================

            let autosaveTimer = null;
            let selectedButtonId = null;

            // ========================================================================
            // EVENT LISTENER ATTACHMENT
            // ========================================================================

            function attachEventListeners() {
                // Settings input listeners
                const buttonInputs = ['min-base-size', 'max-base-size', 'min-viewport', 'max-viewport'];
                buttonInputs.forEach(inputId => {
                    const input = document.getElementById(inputId);
                    if (input) {
                        input.removeEventListener('input', handleSettingsChange);
                        input.addEventListener('input', handleSettingsChange);
                    }
                });

                // Unit button listeners (PX/REM)
                const unitButtons = document.querySelectorAll('.unit-button');
                unitButtons.forEach(button => {
                    button.removeEventListener('click', handleUnitChange);
                    button.addEventListener('click', handleUnitChange);
                });

                // Property input listeners (inline editing)
                const propertyInputs = document.querySelectorAll('.card-property-input');
                propertyInputs.forEach(input => {
                    input.removeEventListener('input', handlePropertyChange);
                    input.addEventListener('input', handlePropertyChange);
                });

                // Editable name listeners
                const editableNames = document.querySelectorAll('.editable-name');
                editableNames.forEach(input => {
                    input.removeEventListener('blur', handleNameChange);
                    input.removeEventListener('keydown', handleNameKeydown);
                    input.addEventListener('blur', handleNameChange);
                    input.addEventListener('keydown', handleNameKeydown);
                });

                // Button-specific color input listeners
                const buttonColorInputs = document.querySelectorAll('.card-color-input');
                buttonColorInputs.forEach(input => {
                    input.removeEventListener('input', handleButtonColorChange);
                    input.addEventListener('input', handleButtonColorChange);
                });

                // Button card state button listeners  
                const cardStateButtons = document.querySelectorAll('.card-state-button');
                cardStateButtons.forEach(button => {
                    button.removeEventListener('click', handleCardStateChange);
                    button.addEventListener('click', handleCardStateChange);
                });

                // Button card checkbox listeners
                const borderCheckboxes = document.querySelectorAll('.use-border-checkbox');
                borderCheckboxes.forEach(checkbox => {
                    checkbox.removeEventListener('change', handleCardBorderChange);
                    checkbox.addEventListener('change', handleCardBorderChange);
                });

                // Save and autosave listeners
                const saveBtn = document.getElementById('save-btn');
                if (saveBtn) {
                    saveBtn.removeEventListener('click', handleSaveButton);
                    saveBtn.addEventListener('click', handleSaveButton);
                }

                const autosaveToggle = document.getElementById('autosave-toggle');
                if (autosaveToggle) {
                    autosaveToggle.removeEventListener('change', handleAutosaveToggle);
                    autosaveToggle.addEventListener('change', handleAutosaveToggle);

                    if (autosaveToggle.checked) {
                        startAutosaveTimer();
                    }
                }

                // Copy button listeners
                const copyAllBtn = document.getElementById('copy-all-btn');
                if (copyAllBtn) {
                    copyAllBtn.removeEventListener('click', handleCopyAll);
                    copyAllBtn.addEventListener('click', handleCopyAll);
                }

                const copySelectedBtn = document.getElementById('copy-selected-btn');
                if (copySelectedBtn) {
                    copySelectedBtn.removeEventListener('click', handleCopySelected);
                    copySelectedBtn.addEventListener('click', handleCopySelected);
                }

                // Button card selection listeners
                const buttonCards = document.querySelectorAll('.button-card');
                buttonCards.forEach(card => {
                    card.removeEventListener('click', handleCardSelection);
                    card.addEventListener('click', handleCardSelection);
                });

                // Action button listeners
                const createBtn = document.getElementById('create-button');
                if (createBtn) {
                    createBtn.removeEventListener('click', handleCreateButton);
                    createBtn.addEventListener('click', handleCreateButton);
                }

                // Reset listener
                const resetBtn = document.getElementById('reset-defaults');
                if (resetBtn) {
                    resetBtn.removeEventListener('click', handleReset);
                    resetBtn.addEventListener('click', handleReset);
                }

                // Clear all buttons listener
                const clearBtn = document.getElementById('clear-sizes');
                if (clearBtn) {
                    clearBtn.removeEventListener('click', handleClearAll);
                    clearBtn.addEventListener('click', handleClearAll);
                }

                // Action button listeners
                const duplicateButtons = document.querySelectorAll('.card-action-btn:not(.card-delete-btn)');
                duplicateButtons.forEach(button => {
                    button.removeEventListener('click', handleDuplicate);
                    button.addEventListener('click', handleDuplicate);
                });

                const deleteButtons = document.querySelectorAll('.card-action-btn.card-delete-btn');
                deleteButtons.forEach(button => {
                    button.removeEventListener('click', handleDelete);
                    button.addEventListener('click', handleDelete);
                });

                // New button name input Enter key
                const newButtonName = document.getElementById('new-button-name');
                if (newButtonName) {
                    newButtonName.removeEventListener('keydown', handleNewButtonNameKeydown);
                    newButtonName.addEventListener('keydown', handleNewButtonNameKeydown);
                }
            }

            // ========================================================================
            // EVENT HANDLERS
            // ========================================================================

            function handleSettingsChange() {
                const settings = buttonDesignAjax.data.settings;
                settings.minBaseSize = parseInt(document.getElementById('min-base-size').value) || 16;
                settings.maxBaseSize = parseInt(document.getElementById('max-base-size').value) || 20;
                settings.minViewport = parseInt(document.getElementById('min-viewport').value) || 375;
                settings.maxViewport = parseInt(document.getElementById('max-viewport').value) || 1620;
                updateCSSOutputs();
                updatePreview();
            }

            function handleUnitChange(event) {
                const selectedUnit = event.target.getAttribute('data-unit');
                const previousUnit = buttonDesignAjax.data.settings.unitType;

                // Only proceed if unit actually changed
                if (previousUnit === selectedUnit) return;

                buttonDesignAjax.data.settings.unitType = selectedUnit;

                document.querySelectorAll('.unit-button').forEach(btn => btn.classList.remove('active'));
                event.target.classList.add('active');

                // Update all unit labels in property cards
                document.querySelectorAll('.unit-label').forEach(label => {
                    label.textContent = selectedUnit;
                });

                // Update input limits and step values for new unit
                updateInputLimitsForUnit(selectedUnit);

                // Convert all property input values
                document.querySelectorAll('.card-property-input').forEach(input => {
                    const currentValue = parseFloat(input.value);
                    const property = input.getAttribute('data-property');

                    // Skip fontSize, borderRadius, borderWidth - they stay in pixels
                    if (['fontSize', 'borderRadius', 'borderWidth'].includes(property)) {
                        return;
                    }

                    let newValue;
                    if (previousUnit === 'px' && selectedUnit === 'rem') {
                        // Convert px to rem (divide by 16, remove trailing zeros)
                        newValue = parseFloat((currentValue / 16).toFixed(4));
                    } else if (previousUnit === 'rem' && selectedUnit === 'px') {
                        // Convert rem to px (multiply by 16, round to integer)
                        newValue = Math.round(currentValue * 16);
                    } else {
                        return; // No conversion needed
                    }

                    input.value = newValue;

                    // Update the underlying data
                    const sizeId = parseInt(input.getAttribute('data-size-id'));
                    const currentSizes = buttonDesignAjax.data.classSizes;
                    const sizeItem = currentSizes.find(item => item.id === sizeId);
                    if (sizeItem && sizeItem[property] !== undefined) {
                        sizeItem[property] = newValue;
                    }
                });

                updateCSSOutputs();
                updatePreview();
            }

            function handlePropertyChange(event) {
                const input = event.target;
                let value = parseFloat(input.value);
                const sizeId = parseInt(input.getAttribute('data-size-id'));
                const property = input.getAttribute('data-property');

                if (!sizeId || !property || isNaN(value)) {
                    return;
                }

                // Validate and auto-correct the value
                const correctedValue = validateAndCorrectValue(input, value, property);
                if (correctedValue !== value) {
                    input.value = correctedValue;
                    value = correctedValue;
                    showValidationFeedback(input, 'corrected');
                }

                // Find and update the button in data
                const currentSizes = buttonDesignAjax.data.classSizes;
                const button = currentSizes.find(item => item.id === sizeId);

                if (button) {
                    button[property] = value;
                    updateCSSOutputs();
                    updatePreview();
                    updateButtonCardPreview(sizeId, true); // true = update dimensions
                }
            }

            // Handle inline name editing
            function handleNameChange(event) {
                const input = event.target;
                const newName = input.value.trim();
                const sizeId = parseInt(input.getAttribute('data-size-id'));

                if (!newName || !sizeId) {
                    // Revert to original name if empty
                    const currentSizes = buttonDesignAjax.data.classSizes;
                    const button = currentSizes.find(item => item.id === sizeId);
                    if (button) {
                        input.value = button.className;
                    }
                    return;
                }

                // Check if name already exists
                const currentSizes = buttonDesignAjax.data.classSizes;
                const nameExists = currentSizes.some(item => item.className === newName && item.id !== sizeId);

                if (nameExists) {
                    alert(`Button class "${newName}" already exists. Please choose a different name.`);
                    // Revert to original name
                    const button = currentSizes.find(item => item.id === sizeId);
                    if (button) {
                        input.value = button.className;
                    }
                    return;
                }

                // Update the button name
                const button = currentSizes.find(item => item.id === sizeId);
                if (button) {
                    button.className = newName;
                    updateCSSOutputs();
                    showNameUpdateSuccess(newName);
                }
            }

            function handleNameKeydown(event) {
                if (event.key === 'Enter') {
                    event.target.blur(); // Trigger the blur event to save
                } else if (event.key === 'Escape') {
                    // Revert to original name
                    const sizeId = parseInt(event.target.getAttribute('data-size-id'));
                    const currentSizes = buttonDesignAjax.data.classSizes;
                    const button = currentSizes.find(item => item.id === sizeId);
                    if (button) {
                        event.target.value = button.className;
                        event.target.blur();
                    }
                }
            }

            function handleNewButtonNameKeydown(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    handleCreateButton();
                }
            }

            // Handle create button
            function handleCreateButton() {
                const nameInput = document.getElementById('new-button-name');
                const name = nameInput.value.trim();

                if (!name) {
                    alert('Please enter a button name');
                    nameInput.focus();
                    return;
                }

                // Check if name already exists
                const currentData = buttonDesignAjax.data.classSizes;
                const nameExists = currentData.some(item => item.className === name);
                if (nameExists) {
                    alert(`Button class "${name}" already exists. Please choose a different name.`);
                    nameInput.focus();
                    return;
                }

                // Generate new ID
                const maxId = currentData.length > 0 ? Math.max(...currentData.map(item => item.id)) : 0;
                const newId = maxId + 1;

                // Get current unit type for defaults
                const unitType = buttonDesignAjax.data.settings.unitType;
                const isRem = unitType === 'rem';

                // Create new button with default values
                const newButton = {
                    id: newId,
                    className: name,
                    width: isRem ? 10 : 160,
                    height: isRem ? 2.5 : 40,
                    paddingX: isRem ? 1 : 16,
                    paddingY: isRem ? 0.5 : 8,
                    fontSize: 16,
                    borderRadius: 6,
                    borderWidth: 2,
                    colors: {
                        normal: {
                            background: 'var(--clr-accent)',
                            text: 'var(--clr-btn-txt)',
                            border: 'var(--clr-btn-bdr)',
                            useBorder: true
                        },
                        hover: {
                            background: 'var(--clr-btn-hover)',
                            text: 'var(--clr-btn-txt)',
                            border: 'var(--clr-btn-bdr)',
                            useBorder: true
                        },
                        active: {
                            background: 'var(--clr-secondary)',
                            text: 'var(--clr-btn-txt)',
                            border: 'var(--clr-btn-bdr)',
                            useBorder: true
                        },
                        disabled: {
                            background: 'var(--jimr-gray-300)',
                            text: 'var(--jimr-gray-600)',
                            border: 'var(--jimr-gray-500)',
                            useBorder: true
                        }
                    }
                };

                // Add to data array
                buttonDesignAjax.data.classSizes.push(newButton);

                // Clear the input
                nameInput.value = '';

                // Regenerate the UI
                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    panelContainer.innerHTML = generatePanelContent();
                    attachEventListeners();
                }

                // Update CSS and preview
                updateCSSOutputs();
                updatePreview();

                // Show success message
                showCreateSuccess(name);
            }

            // Handle button-specific color changes
            function handleButtonColorChange(event) {
                const input = event.target;
                const value = input.value;
                const sizeId = parseInt(input.getAttribute('data-size-id'));

                // Extract color type from class name (e.g., 'background-input' -> 'background')
                const colorType = input.classList.toString().match(/(background|text|border)-input/)?.[1];

                if (!sizeId || !colorType) {
                    console.error('No size ID or color type found for color input', {
                        sizeId,
                        colorType,
                        classList: input.classList.toString()
                    });
                    return;
                }

                // Find the button in the data
                const currentSizes = buttonDesignAjax.data.classSizes;
                const button = currentSizes.find(item => item.id === sizeId);

                if (!button || !button.colors) {
                    console.error('Button or button colors not found');
                    return;
                }

                // Get the current state for this button (default to normal)
                const buttonState = getButtonCurrentState(sizeId) || 'normal';

                // Ensure the state exists
                if (!button.colors[buttonState]) {
                    button.colors[buttonState] = {};
                }

                // Update the specific color property using simplified structure
                switch (colorType) {
                    case 'background':
                        // Update both old structure (for compatibility) and add new structure
                        button.colors[buttonState].background1 = value;
                        button.colors[buttonState].background = value;
                        break;
                    case 'text':
                        button.colors[buttonState].text = value;
                        break;
                    case 'border':
                        button.colors[buttonState].border = value;
                        break;
                }
                updateCSSOutputs();
                updatePreview();
                updateButtonCardPreview(sizeId);
            }

            // Get the current active state for a specific button
            function getButtonCurrentState(sizeId) {
                const activeStateButton = document.querySelector(`[data-state][data-size-id="${sizeId}"].active`);
                return activeStateButton ? activeStateButton.getAttribute('data-state') : 'normal';
            }

            // Update individual button card preview
            function updateButtonCardPreview(sizeId, updateDimensions = false) {
                const previewButton = document.querySelector(`[data-size-id="${sizeId}"].header-preview-btn`);
                if (!previewButton) return;

                const currentSizes = buttonDesignAjax.data.classSizes;
                const button = currentSizes.find(item => item.id === sizeId);
                if (!button) return;

                // Get current state and colors
                const currentState = getButtonCurrentState(sizeId) || 'normal';
                const buttonColors = normalizeColorData(button.colors || buttonDesignAjax.data.colors);
                const stateColors = buttonColors[currentState] || buttonColors.normal;

                // Update dimensions only when explicitly requested (property changes)
                if (updateDimensions) {
                    previewButton.style.width = Math.max(button.width * 0.5, 60) + 'px';
                    previewButton.style.height = Math.max(button.height * 0.8, 28) + 'px';
                    previewButton.style.paddingLeft = Math.max(button.paddingX * 0.7, 8) + 'px';
                    previewButton.style.paddingRight = Math.max(button.paddingX * 0.7, 8) + 'px';
                    previewButton.style.paddingTop = Math.max(button.paddingY * 0.7, 4) + 'px';
                    previewButton.style.paddingBottom = Math.max(button.paddingY * 0.7, 4) + 'px';
                    previewButton.style.fontSize = Math.max(button.fontSize * 0.8, 12) + 'px';
                    previewButton.style.borderRadius = Math.max(button.borderRadius * 0.9, 0) + 'px';
                    previewButton.style.borderWidth = button.borderWidth > 0 ? Math.max(button.borderWidth, 1) + 'px' : '0px';
                }

                // Always update colors
                previewButton.style.background = stateColors.background;

                previewButton.style.color = stateColors.text;

                if (stateColors.useBorder !== false && button.borderWidth > 0) {
                    previewButton.style.borderColor = stateColors.border;
                    previewButton.style.borderStyle = 'solid';
                } else {
                    previewButton.style.border = 'none';
                }

                // Update the button text to match the class name
                previewButton.textContent = button.className.replace('btn-', '');
            }

            function generateButtonPreviewStyle(button, stateColors, context = 'main') {
                const settings = buttonDesignAjax.data.settings;

                // Calculate responsive values based on context
                let scaleFactor;
                let sizeType;

                if (context === 'card') {
                    // Card previews are smaller, use min viewport scaling
                    scaleFactor = 0.6;
                    sizeType = 'min';
                } else if (context === 'min') {
                    scaleFactor = 0.7;
                    sizeType = 'min';
                } else {
                    scaleFactor = 1.0;
                    sizeType = 'max';
                }

                // Calculate dimensions - use raw values for preview, not responsive calculations
                const properties = ['width', 'height', 'paddingX', 'paddingY', 'fontSize', 'borderRadius', 'borderWidth'];
                const style = {};

                properties.forEach(prop => {
                    // For preview panels, use raw button values with simple scaling
                    const rawValue = button[prop] || 0;
                    let scaledValue = Math.max(rawValue * scaleFactor, getMinValue(prop));

                    switch (prop) {
                        case 'width':
                            style.width = scaledValue + 'px';
                            style.minWidth = scaledValue + 'px';
                            break;
                        case 'height':
                            style.height = scaledValue + 'px';
                            style.minHeight = scaledValue + 'px';
                            break;
                        case 'paddingX':
                            style.paddingLeft = scaledValue + 'px';
                            style.paddingRight = scaledValue + 'px';
                            break;
                        case 'paddingY':
                            style.paddingTop = scaledValue + 'px';
                            style.paddingBottom = scaledValue + 'px';
                            break;
                        case 'fontSize':
                            style.fontSize = scaledValue + 'px';
                            break;
                        case 'borderRadius':
                            if (button[prop] === 0) {
                                style.borderRadius = '0';
                            } else {
                                style.borderRadius = scaledValue + 'px';
                            }
                            break;
                        case 'borderWidth':
                            if (stateColors.useBorder !== false && scaledValue > 0) {
                                style.borderWidth = scaledValue + 'px';
                                style.borderStyle = 'solid';
                            } else if (scaledValue === 0) {
                                style.border = 'none';
                            }
                            break;
                    }
                });

                // Apply color styles  
                if (stateColors) {
                    style.background = stateColors.background;
                    style.color = stateColors.text;

                    // Get the actual border width from the button data
                    const currentSizes = buttonDesignAjax.data.classSizes;
                    const buttonItem = currentSizes.find(item => item.id === button.id);
                    const borderWidth = buttonItem ? buttonItem.borderWidth : 0;

                    if (stateColors.useBorder !== false && borderWidth > 0) {
                        style.borderColor = stateColors.border;
                    } else {
                        style.border = 'none';
                    }
                }

                // Common button styles
                style.fontFamily = 'inherit';
                style.fontWeight = '600';
                style.cursor = 'pointer';
                style.transition = 'all 0.2s ease';
                style.textTransform = 'capitalize';
                style.display = 'inline-flex';
                style.alignItems = 'center';
                style.justifyContent = 'center';
                style.boxSizing = 'border-box';

                return style;
            }

            function getMinValue(property) {
                // Minimum values to ensure buttons remain readable
                switch (property) {
                    case 'width':
                        return 40;
                    case 'height':
                        return 20;
                    case 'paddingX':
                        return 4;
                    case 'paddingY':
                        return 2;
                    case 'fontSize':
                        return 10;
                    case 'borderRadius':
                        return 0; // Allow perfectly square corners
                    case 'borderWidth':
                        return 0; // Allow no border
                    default:
                        return 1;
                }
            }

            // Handle button card state changes
            function handleCardStateChange(event) {
                const button = event.target;
                const sizeId = parseInt(button.getAttribute('data-size-id'));
                const newState = button.getAttribute('data-state');

                // Update active state for this button's state buttons
                const stateButtons = document.querySelectorAll(`[data-size-id="${sizeId}"].card-state-button`);
                stateButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                // Update color inputs to show this state's colors
                updateCardColorInputs(sizeId, newState);

                // Update the card preview to show this state's colors
                updateButtonCardPreview(sizeId);
            }

            // Handle border checkbox changes for specific buttons  
            function handleCardBorderChange(event) {
                const checkbox = event.target;
                const sizeId = parseInt(checkbox.getAttribute('data-size-id'));
                const isChecked = checkbox.checked;

                const currentSizes = buttonDesignAjax.data.classSizes;
                const button = currentSizes.find(item => item.id === sizeId);
                if (!button || !button.colors) return;

                const currentState = getButtonCurrentState(sizeId) || 'normal';
                if (!button.colors[currentState]) button.colors[currentState] = {};

                button.colors[currentState].useBorder = isChecked;

                // Enable/disable border color input
                const borderInput = document.querySelector(`[data-size-id="${sizeId}"].border-input`);
                if (borderInput) {
                    borderInput.disabled = !isChecked;
                }

                updateCSSOutputs();
                updatePreview();
                updateButtonCardPreview(sizeId);
            }

            // Update color inputs for a specific button and state
            function updateCardColorInputs(sizeId, state) {
                const currentSizes = buttonDesignAjax.data.classSizes;
                const button = currentSizes.find(item => item.id === sizeId);
                if (!button || !button.colors) return;

                // Get state colors, fallback to normal if state doesn't exist
                const stateColors = button.colors[state] || button.colors.normal;

                // Normalize the colors first
                const normalizedColors = normalizeColorData({
                    [state]: stateColors
                });
                const normalizedStateColors = normalizedColors[state];

                // Update color inputs
                const backgroundInput = document.querySelector(`[data-size-id="${sizeId}"].background-input`);
                const textInput = document.querySelector(`[data-size-id="${sizeId}"].text-input`);
                const borderInput = document.querySelector(`[data-size-id="${sizeId}"].border-input`);

                if (backgroundInput) backgroundInput.value = normalizedStateColors.background || '#FFD700';
                if (textInput) textInput.value = normalizedStateColors.text || '#9C0202';
                if (borderInput) {
                    borderInput.value = normalizedStateColors.border || '#DE0B0B';
                    borderInput.disabled = !normalizedStateColors.useBorder;
                }

                // Update checkboxes
                const borderCheckbox = document.querySelector(`[data-size-id="${sizeId}"].use-border-checkbox`);

                if (borderCheckbox) borderCheckbox.checked = normalizedStateColors.useBorder !== false;

                // Update preview immediately
                updateButtonCardPreview(sizeId);
            }

            // Handle duplicate button click
            function handleDuplicate(event) {
                const sizeId = parseInt(event.target.getAttribute('data-id'));
                const currentData = buttonDesignAjax.data.classSizes;
                const originalItem = currentData.find(item => item.id === sizeId);
                if (!originalItem) return;

                // Generate new ID
                const maxId = currentData.length > 0 ? Math.max(...currentData.map(item => item.id)) : 0;
                const newId = maxId + 1;

                const originalName = originalItem.className;
                const duplicateName = generateDuplicateName(originalName, currentData);

                // Create complete duplicate item
                const duplicateItem = {
                    id: newId,
                    className: duplicateName,
                    width: originalItem.width,
                    height: originalItem.height,
                    paddingX: originalItem.paddingX,
                    paddingY: originalItem.paddingY,
                    fontSize: originalItem.fontSize,
                    borderRadius: originalItem.borderRadius,
                    borderWidth: originalItem.borderWidth,
                    colors: JSON.parse(JSON.stringify(originalItem.colors || {}))
                };

                // Add to data array
                buttonDesignAjax.data.classSizes.push(duplicateItem);

                // Refresh UI completely
                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    panelContainer.innerHTML = generatePanelContent();
                    attachEventListeners();
                }

                updateCSSOutputs();
                updatePreview();

                // Show success feedback
                showDuplicateSuccess(originalName, duplicateName);
            }

            // Generate a unique duplicate name
            function generateDuplicateName(originalName, currentData) {
                let baseName = originalName.replace(/-copy(-\d+)?$/, '');
                let counter = 1;
                let newName = `${baseName}-copy`;

                while (currentData.some(item => item.className === newName)) {
                    counter++;
                    newName = `${baseName}-copy-${counter}`;
                }

                return newName;
            }

            function handleReset() {
                const confirmed = confirm(`Reset to defaults?\n\nThis will replace all current entries with the original 3 default sizes.\n\nAny custom entries will be lost.`);

                if (!confirmed) return;

                restoreDefaults();

                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    panelContainer.innerHTML = generatePanelContent();
                    attachEventListeners();
                }

                updateCSSOutputs();
                updatePreview();
            }

            // Handle clear all button click
            function handleClearAll() {
                const currentData = [...buttonDesignAjax.data.classSizes];

                const confirmed = confirm(`Are you sure you want to clear all Button Classes?\n\nThis will remove all ${currentData.length} entries.`);

                if (!confirmed) return;

                buttonDesignAjax.data.classSizes = [];

                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    panelContainer.innerHTML = generatePanelContent();
                    attachEventListeners();
                }

                updateCSSOutputs();
                updatePreview();
            }

            // Handle delete button click
            function handleDelete(event) {
                const sizeId = parseInt(event.target.getAttribute('data-id'));
                const currentData = buttonDesignAjax.data.classSizes;

                const itemToDelete = currentData.find(item => item.id === sizeId);
                if (!itemToDelete) return;

                const itemName = itemToDelete.className;
                const confirmed = confirm(`Delete "${itemName}"?\n\nThis action cannot be undone.`);

                if (!confirmed) return;

                const itemIndex = currentData.findIndex(item => item.id === sizeId);
                if (itemIndex !== -1) {
                    buttonDesignAjax.data.classSizes.splice(itemIndex, 1);
                }

                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    panelContainer.innerHTML = generatePanelContent();
                    attachEventListeners();
                }

                updateCSSOutputs();
                updatePreview();
            }

            // Handle copy all button click
            function handleCopyAll() {
                const generatedCode = document.getElementById('generated-code');
                if (generatedCode) {
                    navigator.clipboard.writeText(generatedCode.textContent).then(() => {
                        // Show success feedback
                        const btn = document.getElementById('copy-all-btn');
                        const originalText = btn.innerHTML;
                        btn.innerHTML = '<span class="copy-icon">✅</span> copied!';
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                        }, 2000);
                    });
                }
            }

            function handleCopySelected() {
                const selectedCode = document.getElementById('selected-code');
                if (selectedCode && selectedCode.textContent !== '/* Click a button card to select it and view its CSS */') {
                    navigator.clipboard.writeText(selectedCode.textContent).then(() => {
                        // Show success feedback
                        const btn = document.getElementById('copy-selected-btn');
                        const originalText = btn.innerHTML;
                        btn.innerHTML = '<span class="copy-icon">✅</span> copied!';
                        setTimeout(() => {
                            btn.innerHTML = originalText;
                        }, 2000);
                    });
                }
            }

            function handleCardSelection(event) {
                // Prevent selection when clicking on inputs or buttons within the card
                if (event.target.tagName === 'INPUT' || event.target.tagName === 'BUTTON') {
                    return;
                }

                const card = event.currentTarget;
                const sizeId = parseInt(card.getAttribute('data-id'));

                if (!sizeId) return;

                // Update selection
                selectedButtonId = sizeId;

                // Update visual selection state
                updateCardSelectionVisual();

                // Update selected CSS panel
                updateSelectedButtonCSS();
            }

            function updateCardSelectionVisual() {
                // Remove selection from all cards
                document.querySelectorAll('.button-card').forEach(card => {
                    card.classList.remove('selected');
                });

                // Add selection to current card
                if (selectedButtonId) {
                    const selectedCard = document.querySelector(`[data-id="${selectedButtonId}"].button-card`);
                    if (selectedCard) {
                        selectedCard.classList.add('selected');
                    }
                }
            }

            function updateSelectedButtonCSS() {
                const selectedCode = document.getElementById('selected-code');
                const selectedTitle = document.getElementById('selected-code-title');

                if (!selectedCode) return;

                if (!selectedButtonId) {
                    selectedCode.textContent = '/* Click a button card to select it and view its CSS */';
                    if (selectedTitle) {
                        selectedTitle.textContent = 'Selected Button CSS';
                    }
                    return;
                }

                const currentSizes = buttonDesignAjax.data.classSizes;
                const selectedButton = currentSizes.find(item => item.id === selectedButtonId);

                if (!selectedButton) {
                    selectedCode.textContent = '/* Selected button not found */';
                    return;
                }

                // Generate CSS for just this button
                const settings = buttonDesignAjax.data.settings;
                const colors = buttonDesignAjax.data.colors;
                const css = generateSingleButtonCSS(selectedButton, settings, colors);

                selectedCode.textContent = css;

                if (selectedTitle) {
                    selectedTitle.textContent = `Selected Button CSS (${selectedButton.className})`;
                }
            }

            function generateSingleButtonCSS(button, settings, globalColors) {
                const minVp = settings.minViewport;
                const maxVp = settings.maxViewport;
                const unitType = settings.unitType;

                let css = '';

                // Generate main class CSS
                const properties = ['width', 'height', 'paddingX', 'paddingY', 'fontSize', 'borderRadius', 'borderWidth'];
                let classCSS = `.${button.className} {\n`;

                properties.forEach(prop => {
                    const calc = calculateButtonProperty(button.id, prop, settings);
                    const clampFunction = generateClampFunction(calc.min, calc.max, minVp, maxVp, unitType);

                    let cssProp;
                    switch (prop) {
                        case 'paddingX':
                            cssProp = 'padding-left';
                            classCSS += `  ${cssProp}: ${clampFunction};\n`;
                            cssProp = 'padding-right';
                            classCSS += `  ${cssProp}: ${clampFunction};\n`;
                            break;
                        case 'paddingY':
                            cssProp = 'padding-top';
                            classCSS += `  ${cssProp}: ${clampFunction};\n`;
                            cssProp = 'padding-bottom';
                            classCSS += `  ${cssProp}: ${clampFunction};\n`;
                            break;
                        case 'fontSize':
                            cssProp = 'font-size';
                            classCSS += `  ${cssProp}: ${clampFunction};\n`;
                            break;
                        case 'borderRadius':
                            cssProp = 'border-radius';
                            classCSS += `  ${cssProp}: ${clampFunction};\n`;
                            break;
                        case 'borderWidth':
                            if (button[prop] > 0) {
                                cssProp = 'border-width';
                                classCSS += `  ${cssProp}: ${clampFunction};\n`;
                            }
                            break;
                        default:
                            classCSS += `  ${prop}: ${clampFunction};\n`;
                    }
                });

                classCSS += '}\n\n';
                css += classCSS;

                // Use button-specific colors if available, fallback to global colors
                const buttonColors = normalizeColorData(button.colors || globalColors);

                // Add state variations
                Object.keys(buttonColors).forEach(state => {
                    const stateColors = buttonColors[state];
                    const stateClass = state === 'normal' ? `.${button.className}` : `.${button.className}:${state}`;

                    let stateCSS = `${stateClass} {\n`;

                    // Background (simplified - no gradients)
                    stateCSS += `  background: ${stateColors.background};\n`;

                    // Text color
                    stateCSS += `  color: ${stateColors.text};\n`;

                    // Border
                    if (stateColors.useBorder && button.borderWidth > 0) {
                        stateCSS += `  border-color: ${stateColors.border};\n`;
                        stateCSS += `  border-style: solid;\n`;
                    } else {
                        stateCSS += `  border: none;\n`;
                    }

                    stateCSS += '}\n\n';
                    css += stateCSS;
                });

                return css.trim();
            }

            // ========================================================================
            // COLOR DATA NORMALIZATION  
            // ========================================================================

            function normalizeColorData(colors) {
                if (!colors) return {};

                const normalized = {};

                Object.keys(colors).forEach(state => {
                    const stateColors = colors[state];
                    let backgroundColor;

                    // Handle newer background object structure (priority)
                    if (stateColors.background && typeof stateColors.background === 'object') {
                        backgroundColor = stateColors.background.solid || stateColors.background.gradient?.stops?.[0]?.color || '#FFD700';
                    }
                    // Handle old background1/background2 structure
                    else if (stateColors.background1) {
                        backgroundColor = stateColors.background1;
                    }
                    // Handle simple background string
                    else if (stateColors.background) {
                        backgroundColor = stateColors.background;
                    }
                    // Fallback
                    else {
                        backgroundColor = '#FFD700';
                    }

                    normalized[state] = {
                        background: backgroundColor,
                        text: stateColors.text || '#9C0202',
                        border: stateColors.border || '#DE0B0B',
                        useBorder: stateColors.useBorder !== false
                    };
                });

                return normalized;
            }

            // ========================================================================
            // HELPER FUNCTIONS
            // ========================================================================

            function restoreDefaults() {
                buttonDesignAjax.data.classSizes = [{
                        id: 1,
                        className: 'btn-sm',
                        width: 120,
                        height: 32,
                        paddingX: 12,
                        paddingY: 6,
                        fontSize: 14,
                        borderRadius: 4,
                        borderWidth: 1
                    },
                    {
                        id: 2,
                        className: 'btn-md',
                        width: 160,
                        height: 40,
                        paddingX: 16,
                        paddingY: 8,
                        fontSize: 16,
                        borderRadius: 6,
                        borderWidth: 2
                    },
                    {
                        id: 3,
                        className: 'btn-lg',
                        width: 200,
                        height: 48,
                        paddingX: 20,
                        paddingY: 10,
                        fontSize: 18,
                        borderRadius: 8,
                        borderWidth: 2
                    }
                ];
            }

            // Success message functions
            function showDuplicateSuccess(originalName, duplicateName) {
                showSuccessMessage(`✅ Duplicated "${originalName}" as "${duplicateName}"`);
            }

            function showNameUpdateSuccess(buttonName) {
                showSuccessMessage(`✅ Renamed to "${buttonName}"`);
            }

            function showCreateSuccess(buttonName) {
                showSuccessMessage(`✅ Created button "${buttonName}"`);
            }

            function showSuccessMessage(text) {
                const message = document.createElement('div');
                message.style.cssText = `
        position: fixed;
        top: 50px;
        right: 20px;
        background: var(--jimr-success);
        color: white;
        padding: 12px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: var(--clr-shadow-lg);
        z-index: 10000;
        transition: all 0.3s ease;
    `;
                message.textContent = text;

                document.body.appendChild(message);

                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (message.parentNode) {
                            message.parentNode.removeChild(message);
                        }
                    }, 300);
                }, 3000);
            }

            // ========================================================================
            // VALIDATION SYSTEM
            // ========================================================================

            function getPropertyLimits(property) {
                const limits = {
                    // Dimensional properties (support both px and rem)
                    width: {
                        minPx: 30,
                        maxPx: 800,
                        minRem: 1.875,
                        maxRem: 50
                    },
                    height: {
                        minPx: 20,
                        maxPx: 150,
                        minRem: 1.25,
                        maxRem: 9.375
                    },
                    paddingX: {
                        minPx: 0,
                        maxPx: 50,
                        minRem: 0,
                        maxRem: 3.125
                    },
                    paddingY: {
                        minPx: 0,
                        maxPx: 30,
                        minRem: 0,
                        maxRem: 1.875
                    },

                    // Fixed pixel properties
                    fontSize: {
                        min: 10,
                        max: 32
                    },
                    borderRadius: {
                        min: 0,
                        max: 100
                    },
                    borderWidth: {
                        min: 0,
                        max: 8
                    }
                };

                return limits[property] || {
                    min: 0,
                    max: 1000
                };
            }

            function validateAndCorrectValue(input, value, property) {
                const limits = getPropertyLimits(property);
                const unitType = buttonDesignAjax.data.settings.unitType;

                let min, max;

                // Determine limits based on unit type and property
                if (['fontSize', 'borderRadius', 'borderWidth'].includes(property)) {
                    // Fixed pixel properties
                    min = limits.min;
                    max = limits.max;
                } else {
                    // Dimensional properties that support px/rem
                    if (unitType === 'rem') {
                        min = limits.minRem;
                        max = limits.maxRem;
                    } else {
                        min = limits.minPx;
                        max = limits.maxPx;
                    }
                }

                // Validate and correct
                if (value < min) {
                    return min;
                } else if (value > max) {
                    return max;
                }

                return value;
            }

            function updateInputLimitsForUnit(unitType) {
                document.querySelectorAll('.card-property-input').forEach(input => {
                    const property = input.getAttribute('data-property');

                    if (['fontSize', 'borderRadius', 'borderWidth'].includes(property)) {
                        // Fixed pixel properties don't change
                        return;
                    }

                    // Update dimensional property limits
                    const limits = getPropertyLimits(property);

                    if (unitType === 'rem') {
                        input.setAttribute('min', limits.minRem);
                        input.setAttribute('max', limits.maxRem);
                        input.setAttribute('step', '0.1');
                    } else {
                        input.setAttribute('min', limits.minPx);
                        input.setAttribute('max', limits.maxPx);
                        input.setAttribute('step', '1');
                    }
                });
            }

            function showValidationFeedback(input, type) {
                // Remove existing feedback
                input.classList.remove('validation-error', 'validation-corrected');

                if (type === 'error') {
                    input.classList.add('validation-error');
                    setTimeout(() => input.classList.remove('validation-error'), 2000);
                } else if (type === 'corrected') {
                    input.classList.add('validation-corrected');
                    setTimeout(() => input.classList.remove('validation-corrected'), 2000);
                }
            }

            function initializeInputLimits() {
                const unitType = buttonDesignAjax.data.settings.unitType;
                updateInputLimitsForUnit(unitType);
            }

            // ========================================================================
            // CALCULATION FUNCTIONS
            // ========================================================================

            function generateClampFunction(minValue, maxValue, minViewport, maxViewport, unitType) {
                // If min and max values are the same, just return the constant value
                if (minValue === maxValue) {
                    if (minValue === 0) {
                        return '0';
                    }
                    return unitType === 'rem' ?
                        (minValue / 16).toFixed(3).replace(/\.?0+$/, '') + 'rem' :
                        minValue + 'px';
                }

                const minPx = unitType === 'rem' ? minValue * 16 : minValue;
                const maxPx = unitType === 'rem' ? maxValue * 16 : maxValue;

                const coefficient = ((maxPx - minPx) / (maxViewport - minViewport) * 100);
                const constant = minPx - (coefficient * minViewport / 100);

                const minUnit = unitType === 'rem' ? (minPx / 16).toFixed(3) + 'rem' : minPx + 'px';
                const maxUnit = unitType === 'rem' ? (maxPx / 16).toFixed(3) + 'rem' : maxPx + 'px';

                const constantFormatted = unitType === 'rem' ?
                    (constant / 16).toFixed(4) + 'rem' :
                    constant.toFixed(2) + 'px';
                const coefficientFormatted = coefficient.toFixed(4) + 'vw';

                const preferredValue = constant === 0 ?
                    coefficientFormatted :
                    `calc(${constantFormatted} + ${coefficientFormatted})`;

                return `clamp(${minUnit}, ${preferredValue}, ${maxUnit})`;
            }

            // Calculate button property based on size ID and settings
            function calculateButtonProperty(sizeId, property, settings) {
                const currentSizes = buttonDesignAjax.data.classSizes;
                const buttonItem = currentSizes.find(item => item.id === sizeId);

                if (!buttonItem || !buttonItem[property]) {
                    return {
                        min: settings.minBaseSize || 16,
                        max: settings.maxBaseSize || 20
                    };
                }

                // Get the button's defined property value
                let buttonValue = buttonItem[property];

                // Convert stored value to pixels if needed
                // If unit is REM and stored value looks like REM (< 20), convert to pixels
                if (settings.unitType === 'rem' && buttonValue < 20 && ['width', 'height', 'paddingX', 'paddingY'].includes(property)) {
                    buttonValue = buttonValue * 16; // Convert REM to pixels for calculation
                }

                // Get the scaling ratios from settings
                const minRatio = settings.minBaseSize / settings.maxBaseSize; // e.g., 16/20 = 0.8
                const maxRatio = 1.0; // Always 100% at max viewport

                // Scale the button property proportionally
                const minSize = Math.round(buttonValue * minRatio);
                const maxSize = buttonValue;

                return {
                    min: minSize,
                    max: maxSize
                };
            }

            // Update CSS outputs
            function updateCSSOutputs() {
                const settings = buttonDesignAjax.data.settings;
                const colors = buttonDesignAjax.data.colors;
                const currentSizes = buttonDesignAjax.data.classSizes;

                const css = generateClassesCSS(currentSizes, settings, colors, 2);
                const generatedCode = document.getElementById('generated-code');
                if (generatedCode) {
                    generatedCode.textContent = css;
                }

                // Also update selected button CSS
                updateSelectedButtonCSS();
            }

            // Generate CSS for all button classes
            function generateClassesCSS(sizes, settings, globalColors) {
                const minVp = settings.minViewport;
                const maxVp = settings.maxViewport;
                const unitType = settings.unitType;

                let css = '';

                sizes.forEach(size => {
                    const properties = ['width', 'height', 'paddingX', 'paddingY', 'fontSize', 'borderRadius', 'borderWidth'];
                    let classCSS = `.${size.className} {\n`;

                    properties.forEach(prop => {
                        const calc = calculateButtonProperty(size.id, prop, settings);
                        const clampFunction = generateClampFunction(calc.min, calc.max, minVp, maxVp, unitType);

                        let cssProp;
                        switch (prop) {
                            case 'paddingX':
                                cssProp = 'padding-left';
                                classCSS += `  ${cssProp}: ${clampFunction};\n`;
                                cssProp = 'padding-right';
                                classCSS += `  ${cssProp}: ${clampFunction};\n`;
                                break;
                            case 'paddingY':
                                cssProp = 'padding-top';
                                classCSS += `  ${cssProp}: ${clampFunction};\n`;
                                cssProp = 'padding-bottom';
                                classCSS += `  ${cssProp}: ${clampFunction};\n`;
                                break;
                            case 'fontSize':
                                cssProp = 'font-size';
                                classCSS += `  ${cssProp}: ${clampFunction};\n`;
                                break;
                            case 'borderRadius':
                                cssProp = 'border-radius';
                                classCSS += `  ${cssProp}: ${clampFunction};\n`;
                                break;
                            case 'borderWidth':
                                cssProp = 'border-width';
                                classCSS += `  ${cssProp}: ${clampFunction};\n`;
                                break;
                            default:
                                classCSS += `  ${prop}: ${clampFunction};\n`;
                        }
                    });

                    classCSS += '}\n\n';

                    // Use button-specific colors if available, fallback to global colors
                    const buttonColors = normalizeColorData(size.colors || globalColors);

                    // Add state variations using button's individual colors
                    Object.keys(buttonColors).forEach(state => {
                        const stateColors = buttonColors[state];
                        const stateClass = state === 'normal' ? `.${size.className}` : `.${size.className}:${state}`;

                        let stateCSS = `${stateClass} {\n`;

                        // Background (simplified - no gradients)
                        stateCSS += `  background: ${stateColors.background};\n`;

                        // Text color
                        stateCSS += `  color: ${stateColors.text};\n`;

                        // Border  
                        const buttonItem = sizes.find(s => s.className === size.className);
                        const hasBorderWidth = buttonItem && buttonItem.borderWidth > 0;

                        if (stateColors.useBorder && hasBorderWidth) {
                            stateCSS += `  border-color: ${stateColors.border};\n`;
                            stateCSS += `  border-style: solid;\n`;
                        } else {
                            stateCSS += `  border: none;\n`;
                        }

                        stateCSS += '}\n\n';
                        css += stateCSS;
                    });

                    css += classCSS;
                });

                return css.trim();
            }

            function updatePreview() {
                const currentSizes = buttonDesignAjax.data.classSizes;
                generateButtonPreview(currentSizes);
            }

            function updateAllCardPreviews() {
                const currentSizes = buttonDesignAjax.data.classSizes;
                currentSizes.forEach(size => {
                    updateButtonCardPreview(size.id, true); // true = update dimensions too
                });
            }

            function generateButtonPreview(currentSizes) {
                const settings = buttonDesignAjax.data.settings;
                const colors = buttonDesignAjax.data.colors;

                const minContainer = document.getElementById('preview-min-container');
                if (minContainer) {
                    minContainer.innerHTML = generatePreviewContent(currentSizes, settings, colors, 'min');
                }

                const maxContainer = document.getElementById('preview-max-container');
                if (maxContainer) {
                    maxContainer.innerHTML = generatePreviewContent(currentSizes, settings, colors, 'max');
                }
            }

            function generatePreviewContent(sizes, settings, globalColors, sizeType) {
                const titleText = sizeType === 'min' ? 'Small Screen Buttons' : 'Large Screen Buttons';

                return `
    <div style="font-family: Arial, sans-serif;">
        <h4 style="margin: 0 0 16px 0; color: var(--clr-txt); font-size: 14px; font-weight: 600;">${titleText}</h4>
        ${sizes.map(size => {
            const name = size.className;
            const buttonColors = normalizeColorData(size.colors || globalColors);
            
            return `
                <div style="margin-bottom: 20px; padding: 12px; background: #f8f9fa; border-radius: 6px; border-left: 3px solid #1976d2; display: block; position: relative;">
                    <div style="font-size: 11px; color: #666; margin-bottom: 8px; font-weight: 600;">${name}</div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        ${Object.keys(buttonColors).map(state => {
                            const stateColors = buttonColors[state];
                            const previewStyle = generateButtonPreviewStyle(size, stateColors, sizeType);
                            
                            // Convert style object to inline CSS string
                            const styleString = Object.entries(previewStyle)
                                .map(([key, value]) => {
                                    // Convert camelCase to kebab-case
                                    const cssKey = key.replace(/([A-Z])/g, '-$1').toLowerCase();
                                    return `${cssKey}: ${value}`;
                                })
                                .join('; ');

                            return `
                                <button class="preview-button" style="${styleString}">
                                    ${state}
                                </button>
                            `;
            }).join('')
            } <
            /div> < /
            div >
                `;
        }).join('')}
    </div>
`;
            }

            // ========================================================================
            // TABLE GENERATION
            // ========================================================================

            function generatePanelContent() {
                if (!buttonDesignAjax || !buttonDesignAjax.data || !buttonDesignAjax.data.classSizes) {
                    console.error('Panel content generation failed - missing data');
                    return '<div style="text-align: center; padding: 40px;">Error: Button data not available</div>';
                }

                const data = buttonDesignAjax.data;
                const result = generateClassesPanel(data.classSizes);
                return result;
            }

            function convertValueForDisplay(value, property) {
                const unitType = buttonDesignAjax.data.settings.unitType;
                const isRem = unitType === 'rem';

                // fontSize, borderRadius, borderWidth always stay in pixels
                if (['fontSize', 'borderRadius', 'borderWidth'].includes(property)) {
                    return value;
                }

                // Convert width, height, padding to current unit for display
                if (isRem) {
                    // For default buttons (sm, md, lg), assume stored values are pixels and convert to rem
                    // For width/height: assume pixels if > 10, for padding: assume pixels if > 2
                    const isLikelyPixels = (['width', 'height'].includes(property) && value > 10) ||
                        (['paddingX', 'paddingY'].includes(property) && value > 2);

                    if (isLikelyPixels) {
                        return parseFloat((value / 16).toFixed(3));
                    }
                }

                return value;
            }

            function generateClassesPanel(sizes) {
                if (!sizes || sizes.length === 0) {
                    return `

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 style="margin: 0; flex: 0 0 auto;">Button Classes</h2>
                            
                            <div class="fcc-autosave-flex" style="flex: 0 0 auto;">
                                <label data-tooltip="Automatically save changes as you make them">
                                    <input type="checkbox" id="autosave-toggle" checked data-tooltip="Toggle automatic saving of your button settings">
                                    <span>Autosave</span>
                                </label>
                                <button id="save-btn" class="fcc-btn" data-tooltip="Save all current settings and designs to database">
                                    Save
                                </button>
                                <div id="autosave-status" class="autosave-status idle">
                                    <span id="autosave-icon">💾</span>
                                    <span id="autosave-text">Ready</span>
                                </div>
                            </div>
                            
                            <div class="fcc-table-buttons" style="flex: 0 0 auto;">
                                <button id="reset-defaults" class="fcc-btn">reset</button>
                                <button id="clear-sizes" class="fcc-btn fcc-btn-danger">clear all</button>
                            </div>
                        </div>

                        <div style="text-align: center; color: #6b7280; font-style: italic; padding: 40px 20px;">
                            No button classes created yet. Use the form above to create your first button.
                        </div>
                    `;
                }

                // Initialize button colors if missing - using design system colors
                sizes.forEach(size => {
                    if (!size.colors) {
                        size.colors = {
                            normal: {
                                background: 'var(--clr-accent)',
                                text: 'var(--clr-btn-txt)',
                                border: 'var(--clr-btn-bdr)',
                                useBorder: true
                            },
                            hover: {
                                background: 'var(--clr-btn-hover)',
                                text: 'var(--clr-btn-txt)',
                                border: 'var(--clr-btn-bdr)',
                                useBorder: true
                            },
                            active: {
                                background: 'var(--clr-secondary)',
                                text: 'var(--clr-btn-txt)',
                                border: 'var(--clr-btn-bdr)',
                                useBorder: true
                            },
                            disabled: {
                                background: 'var(--jimr-gray-300)',
                                text: 'var(--jimr-gray-600)',
                                border: 'var(--jimr-gray-500)',
                                useBorder: true
                            }
                        };
                    }
                });

                return `
                
    <!-- Add New Button Form -->

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0; flex: 0 0 auto;">Button Classes</h2>
                        
                        <div class="fcc-autosave-flex" style="flex: 0 0 auto;">
                            <label data-tooltip="Automatically save changes as you make them">
                                <input type="checkbox" id="autosave-toggle" checked data-tooltip="Toggle automatic saving of your button settings">
                                <span>Autosave</span>
                            </label>
                            <button id="save-btn" class="fcc-btn" data-tooltip="Save all current settings and designs to database">
                                Save
                            </button>
                            <div id="autosave-status" class="autosave-status idle">
                                <span id="autosave-icon">💾</span>
                                <span id="autosave-text">Ready</span>
                            </div>
                        </div>
                        
                        <div class="fcc-table-buttons" style="flex: 0 0 auto;">
                            <button id="reset-defaults" class="fcc-btn">reset</button>
                            <button id="clear-sizes" class="fcc-btn fcc-btn-danger">clear all</button>
                        </div>
                    </div>

                    <div>
                        <div style="display: flex; flex-direction: row; flex-wrap: wrap; gap: 24px;">
                            ${sizes.map(size => `
                                <div class="button-card" data-id="${size.id}">
                                    <!-- Button Card Header -->
                                    <div class="button-card-header">
                                        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                                            <div class="drag-handle">⋮⋮</div>
                                            <input type="text" class="editable-name" data-size-id="${size.id}" value="${size.className}">
                                        </div>
                                        
                                    <div class="card-action-buttons">
                                            <button class="card-action-btn" data-id="${size.id}">📋 duplicate</button>
                                            <button class="card-action-btn card-delete-btn" data-id="${size.id}">🗑️ delete</button>
                                        </div>
                                    </div>
                                    
<!-- Button Preview Section -->
<div style="background: var(--clr-light); padding: 16px; margin: 12px; border-radius: 6px; border-bottom: 2px solid var(--clr-secondary);">
    <div class="header-preview-container" style="height: 80px; display: flex; align-items: center; justify-content: center;">
<button class="header-preview-btn" data-size-id="${size.id}" 
    style="width: ${Math.max(size.width * 0.5, 60)}px; height: ${Math.max(size.height * 0.8, 28)}px; 
    padding: ${Math.max(size.paddingY * 0.7, 4)}px ${Math.max(size.paddingX * 0.7, 8)}px; 
    font-size: ${Math.max(size.fontSize * 0.8, 12)}px; 
    border-radius: ${Math.max(size.borderRadius * 0.9, 0)}px; 
    border-width: ${size.borderWidth > 0 ? Math.max(size.borderWidth, 1) : 0}px; 
    ${size.borderWidth > 0 ? 'border-style: solid;' : 'border: none;'}">
    ${size.className.replace('btn-', '')}
</button>
    </div>
</div>
                                    
                                    <!-- Button Card Content -->
                                    <div class="button-card-content">
                                        <!-- Left Panel: Properties -->
<div class="button-properties-panel" style="margin: 12px;">
                                            <div class="card-panel-title">Properties</div>
      <div class="card-property-row">
    <span class="card-property-label">Width</span>
    <div>
        <input type="number" class="card-property-input" data-size-id="${size.id}" data-property="width" 
               value="${convertValueForDisplay(size.width, 'width')}" 
               data-min-px="30" data-max-px="800" data-min-rem="1.875" data-max-rem="50"
               style="width: 65px; text-align: right;">
        <span style="font-size: 11px; margin-left: 6px; display: inline-block; width: 30px; text-align: left;" class="unit-label">${buttonDesignAjax.data.settings.unitType}</span>
    </div>
</div>
<div class="card-property-row">
    <span class="card-property-label">Height</span>
    <div>
        <input type="number" class="card-property-input" data-size-id="${size.id}" data-property="height" 
               value="${convertValueForDisplay(size.height, 'height')}" 
               data-min-px="20" data-max-px="150" data-min-rem="1.25" data-max-rem="9.375"
               style="width: 65px; text-align: right;">
        <span style="font-size: 11px; margin-left: 6px; display: inline-block; width: 30px; text-align: left;" class="unit-label">${buttonDesignAjax.data.settings.unitType}</span>
    </div>
</div>
<div class="card-property-row">
    <span class="card-property-label">Padding X</span>
    <div>
        <input type="number" class="card-property-input" data-size-id="${size.id}" data-property="paddingX" 
               value="${convertValueForDisplay(size.paddingX, 'paddingX')}" 
               data-min-px="0" data-max-px="50" data-min-rem="0" data-max-rem="3.125"
               style="width: 65px; text-align: right;">
        <span style="font-size: 11px; margin-left: 6px; display: inline-block; width: 30px; text-align: left;" class="unit-label">${buttonDesignAjax.data.settings.unitType}</span>
    </div>
</div>
<div class="card-property-row">
    <span class="card-property-label">Padding Y</span>
    <div>
        <input type="number" class="card-property-input" data-size-id="${size.id}" data-property="paddingY" 
               value="${convertValueForDisplay(size.paddingY, 'paddingY')}" 
               data-min-px="0" data-max-px="30" data-min-rem="0" data-max-rem="1.875"
               style="width: 65px; text-align: right;">
        <span style="font-size: 11px; margin-left: 6px; display: inline-block; width: 30px; text-align: left;" class="unit-label">${buttonDesignAjax.data.settings.unitType}</span>
    </div>
</div>
<div class="card-property-row">
    <span class="card-property-label">Font Size</span>
    <div>
        <input type="number" class="card-property-input" data-size-id="${size.id}" data-property="fontSize" 
               value="${size.fontSize}" min="10" max="32" step="1"
               style="width: 65px; text-align: right;">
        <span style="font-size: 11px; margin-left: 6px; display: inline-block; width: 30px; text-align: left;">px</span>
    </div>
</div>
<div class="card-property-row">
    <span class="card-property-label">Border Radius</span>
    <div>
        <input type="number" class="card-property-input" data-size-id="${size.id}" data-property="borderRadius" 
               value="${size.borderRadius}" min="0" max="100" step="1"
               style="width: 65px; text-align: right;">
        <span style="font-size: 11px; margin-left: 6px; display: inline-block; width: 30px; text-align: left;">px</span>
    </div>
</div>
<div class="card-property-row">
    <span class="card-property-label">Border Width</span>
    <div>
        <input type="number" class="card-property-input" data-size-id="${size.id}" data-property="borderWidth" 
               value="${size.borderWidth}" min="0" max="8" step="1"
               style="width: 65px; text-align: right;">
        <span style="font-size: 11px; margin-left: 6px; display: inline-block; width: 30px; text-align: left;">px</span>
    </div>
</div>
                                        </div>
                                        
                                 <!-- Right Panel: States & Colors -->
<div class="button-states-panel" style="margin: 12px;">
                                            <div class="card-panel-title">States</div>
                                            
                                            <div class="card-state-buttons">
                                                <button class="card-state-button active" data-state="normal" data-size-id="${size.id}">Normal</button>
                                                <button class="card-state-button" data-state="hover" data-size-id="${size.id}">Hover</button>
                                                <button class="card-state-button" data-state="active" data-size-id="${size.id}">Active</button>
                                                <button class="card-state-button" data-state="disabled" data-size-id="${size.id}">Disabled</button>
                                            </div>
                                            
                                            <div style="margin-top: 20px;">
                                                <div class="card-panel-title">Colors</div>
                                                
<div class="card-checkbox-row">
    <input type="checkbox" class="use-border-checkbox" data-size-id="${size.id}" ${size.colors?.normal?.useBorder !== false ? 'checked' : ''}>
    <span>Show Border</span>
</div>

<div style="display: flex; gap: 12px; align-items: end;">
    <div class="card-color-section" style="flex: 1;">
        <span class="card-color-label">Background</span>
        <input type="color" class="card-color-input background-input" data-size-id="${size.id}" value="${normalizeColorData(size.colors || buttonDesignAjax.data.colors).normal.background}">
    </div>
    <div class="card-color-section" style="flex: 1;">
        <span class="card-color-label">Text</span>
        <input type="color" class="card-color-input text-input" data-size-id="${size.id}" value="${size.colors?.normal?.text || '#9C0202'}">
    </div>
    <div class="card-color-section" style="flex: 1;">
        <span class="card-color-label">Border</span>
        <input type="color" class="card-color-input border-input" data-size-id="${size.id}" value="${size.colors?.normal?.border || '#DE0B0B'}" ${size.colors?.normal?.useBorder === false ? 'disabled' : ''}>
    </div>
</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            // ========================================================================
            // SAVE FUNCTIONALITY
            // ========================================================================

            function handleSaveButton() {
                const saveBtn = document.getElementById('save-btn');
                const autosaveStatus = document.getElementById('autosave-status');
                const autosaveIcon = document.getElementById('autosave-icon');
                const autosaveText = document.getElementById('autosave-text');

                if (autosaveStatus && autosaveIcon && autosaveText) {
                    autosaveStatus.className = 'autosave-status saving';
                    autosaveIcon.textContent = '⏳';
                    autosaveText.textContent = 'Saving...';
                }

                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.textContent = 'Saving...';
                }

                const settings = {
                    minBaseSize: document.getElementById('min-base-size')?.value,
                    maxBaseSize: document.getElementById('max-base-size')?.value,
                    minViewport: document.getElementById('min-viewport')?.value,
                    maxViewport: document.getElementById('max-viewport')?.value,
                    unitType: document.querySelector('.unit-button.active')?.getAttribute('data-unit'),
                    autosaveEnabled: document.getElementById('autosave-toggle')?.checked,
                };

                const allSizes = {
                    classSizes: buttonDesignAjax?.data?.classSizes || [],
                    variableSizes: buttonDesignAjax?.data?.variableSizes || []
                };

                const allColors = buttonDesignAjax?.data?.colors || {};

                const data = {
                    action: 'save_button_design_settings',
                    nonce: buttonDesignAjax.nonce,
                    settings: JSON.stringify(settings),
                    sizes: JSON.stringify(allSizes),
                    colors: JSON.stringify(allColors)
                };

                fetch(buttonDesignAjax.ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams(data)
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (autosaveStatus && autosaveIcon && autosaveText) {
                            autosaveStatus.className = 'autosave-status saved';
                            autosaveIcon.textContent = '✅';
                            autosaveText.textContent = 'Saved!';

                            setTimeout(() => {
                                autosaveStatus.className = 'autosave-status idle';
                                autosaveIcon.textContent = '💾';
                                autosaveText.textContent = 'Ready';
                            }, 2000);
                        }

                        if (saveBtn) {
                            saveBtn.disabled = false;
                            saveBtn.textContent = 'Save';
                        }
                    })
                    .catch(error => {
                        console.error('Save error:', error);

                        if (autosaveStatus && autosaveIcon && autosaveText) {
                            autosaveStatus.className = 'autosave-status error';
                            autosaveIcon.textContent = '❌';
                            autosaveText.textContent = 'Error';

                            setTimeout(() => {
                                autosaveStatus.className = 'autosave-status idle';
                                autosaveIcon.textContent = '💾';
                                autosaveText.textContent = 'Ready';
                            }, 3000);
                        }

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
                stopAutosaveTimer();
                autosaveTimer = setInterval(() => {
                    handleSaveButton();
                }, 30000);
            }

            function stopAutosaveTimer() {
                if (autosaveTimer) {
                    clearInterval(autosaveTimer);
                    autosaveTimer = null;
                }
            }

            // ========================================================================
            // INITIALIZATION
            // ========================================================================

            document.addEventListener('DOMContentLoaded', () => {
                // Standardized toggle functionality for all collapsible panels
                document.querySelectorAll('[data-toggle-target]').forEach(toggle => {
                    toggle.addEventListener('click', () => {
                        const targetId = toggle.getAttribute('data-toggle-target');
                        const content = document.getElementById(targetId);
                        if (content && content.classList.contains('collapsible-text')) {
                            content.classList.toggle('expanded');
                            toggle.classList.toggle('expanded');
                        }
                    });
                });

                // Initialize the interface
                const panelContainer = document.getElementById('sizes-table-container');
                if (panelContainer) {
                    if (buttonDesignAjax && buttonDesignAjax.data && buttonDesignAjax.data.classSizes) {
                        // Replace panel content
                        panelContainer.innerHTML = generatePanelContent();

                        // Force update preview containers immediately
                        const minContainer = document.getElementById('preview-min-container');
                        const maxContainer = document.getElementById('preview-max-container');

                        if (minContainer) {
                            minContainer.innerHTML = '<div style="text-align: center; padding: 20px;">Loading previews...</div>';
                        }
                        if (maxContainer) {
                            maxContainer.innerHTML = '<div style="text-align: center; padding: 20px;">Loading previews...</div>';
                        }

                        attachEventListeners();
                        updateCSSOutputs();
                        updatePreview(); // This should replace the "Loading previews..." content
                        updateAllCardPreviews();

                        // Auto-select first button if available
                        const currentSizes = buttonDesignAjax.data.classSizes;
                        if (currentSizes && currentSizes.length > 0) {
                            selectedButtonId = currentSizes[0].id;
                            updateCardSelectionVisual();
                            updateSelectedButtonCSS();
                        }

                        // Initialize input validation limits
                        initializeInputLimits();
                    } else {
                        console.error('Button data not loaded:', buttonDesignAjax);
                        panelContainer.innerHTML = '<div style="padding: 40px; text-align: center;">Data loading error</div>';
                    }
                } else {
                    console.error('Panel container not found');
                }

                // Show the container
                const container = document.getElementById('bdc-main-container');
                if (container) {
                    container.classList.add('ready');
                }
            });
        </script>
<?php
    }

    // ========================================================================
    // AJAX HANDLERS
    // ========================================================================
    public function save_settings()
    {
        if (!wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        try {
            $settings_json = stripslashes($_POST['settings'] ?? '');
            $settings = json_decode($settings_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => 'Invalid settings data']);
                return;
            }

            $sizes_json = stripslashes($_POST['sizes'] ?? '');
            $sizes = json_decode($sizes_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => 'Invalid sizes data']);
                return;
            }

            $colors_json = stripslashes($_POST['colors'] ?? '');
            $colors = json_decode($colors_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => 'Invalid colors data']);
                return;
            }

            $result1 = update_option(self::OPTION_SETTINGS, $settings);
            $result2 = update_option(self::OPTION_CLASS_SIZES, $sizes['classSizes'] ?? []);
            $result3 = update_option(self::OPTION_COLORS, $colors);

            wp_cache_delete(self::OPTION_SETTINGS, 'options');
            wp_cache_delete(self::OPTION_CLASS_SIZES, 'options');
            wp_cache_delete(self::OPTION_COLORS, 'options');

            wp_send_json_success([
                'message' => 'All button design data saved successfully',
                'saved_settings' => $result1,
                'saved_sizes' => $result2,
                'saved_colors' => $result3
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Save failed: ' . $e->getMessage()]);
        }
    }
}

// ========================================================================
// INITIALIZATION
// ========================================================================

if (is_admin()) {
    global $buttonDesignCalculator;
    $buttonDesignCalculator = new ButtonDesignCalculator();
}
