<?php

/**
 * Fluid Font Forge  * Version: 3.7.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fluid Font Forge - Complete Unified Class
 */
class FontClampCalculator
{
    // ========================================================================
    // CORE CONSTANTS SYSTEM
    // ========================================================================

    // Configuration Constants
    const VERSION = '3.7.0';
    const PLUGIN_SLUG = 'fluid-font-forge';
    const NONCE_ACTION = 'fluid_font_nonce';

    // Validation Ranges
    // Why 1-100px: Prevents unusably small (<1px) or absurdly large (>100px) root sizes
    const MIN_ROOT_SIZE_RANGE = [1, 100];
    // Why 200-5000px: Covers feature phones to ultra-wide displays safely
    const VIEWPORT_RANGE = [200, 5000];
    // Why 0.8-3.0: Below 0.8 is unreadable, above 3.0 creates excessive spacing
    const LINE_HEIGHT_RANGE = [0.8, 3.0];
    // Why 1.0-3.0: Below 1.0 shrinks text, above 3.0 creates extreme size jumps
    const SCALE_RANGE = [1.0, 3.0];

    // Default Values - PRIMARY CONSTANTS 
    // Why 16px: Browser default font size - ensures accessibility baseline
    const DEFAULT_MIN_ROOT_SIZE = 16;
    // Why 20px: 25% larger than default - provides good contrast without being jarring
    const DEFAULT_MAX_ROOT_SIZE = 20;
    // Why 375px: iPhone SE width - covers smallest modern mobile devices
    const DEFAULT_MIN_VIEWPORT = 375;
    // Why 1620px: Laptop/desktop sweet spot - before ultra-wide displays
    const DEFAULT_MAX_VIEWPORT = 1620;
    // Why 1.125: Major Second ratio - subtle but noticeable size differences on mobile
    const DEFAULT_MIN_SCALE = 1.125;
    // Why 1.333: Perfect Fourth ratio - creates strong hierarchy on larger screens
    const DEFAULT_MAX_SCALE = 1.333;
    // Why 1.2: Headings need tighter spacing for visual impact and hierarchy
    const DEFAULT_HEADING_LINE_HEIGHT = 1.2;
    // Why 1.4: Body text needs comfortable reading spacing (WCAG accessibility)
    const DEFAULT_BODY_LINE_HEIGHT = 1.4;

    // Browser and system constants
    // Why 16px: Universal browser default - foundation for rem calculations and accessibility
    const BROWSER_DEFAULT_FONT_SIZE = 16;
    // Why 16px base: 1rem = 16px by default - critical for rem/px conversions
    const CSS_UNIT_CONVERSION_BASE = 16;

    // Valid Options
    const VALID_UNITS = ['px', 'rem'];
    const VALID_TABS = ['class', 'vars', 'tag'];

    // WordPress Options Keys
    const OPTION_SETTINGS = 'font_clamp_settings';
    const OPTION_CLASS_SIZES = 'font_clamp_class_sizes';
    const OPTION_VARIABLE_SIZES = 'font_clamp_variable_sizes';
    const OPTION_TAG_SIZES = 'font_clamp_tag_sizes';

    // ========================================================================
    // CLASS PROPERTIES
    // ========================================================================

    private $default_settings;
    private $default_class_sizes;
    private $default_variable_sizes;
    private $default_tag_sizes;
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
        $this->default_tag_sizes = $this->create_default_sizes('tags');
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_save_font_clamp_sizes', [$this, 'save_sizes']);
        add_action('wp_ajax_save_font_clamp_settings', [$this, 'save_settings']);

        // Unified asset loading (all segments combined)
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
            'minRootSize' => self::DEFAULT_MIN_ROOT_SIZE,
            'maxRootSize' => self::DEFAULT_MAX_ROOT_SIZE,
            'minViewport' => self::DEFAULT_MIN_VIEWPORT,
            'maxViewport' => self::DEFAULT_MAX_VIEWPORT,
            'unitType' => 'px',
            'selectedClassSizeId' => 5,
            'selectedVariableSizeId' => 5,
            'selectedTagSizeId' => 1,
            'activeTab' => 'class',
            'previewFontUrl' => '',
            'minScale' => self::DEFAULT_MIN_SCALE,
            'maxScale' => self::DEFAULT_MAX_SCALE,
            'autosaveEnabled' => true,
            'classBaseValue' => 'medium',
            'varsBaseValue' => '--fs-md',
            'tagBaseValue' => 'p'
        ];
    }

    /**
     * Create default sizes array for specified type using constants
     */
    private function create_default_sizes($type)
    {
        $configs = [
            'class' => [
                ['id' => 1, 'name' => 'xxxlarge', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 2, 'name' => 'xxlarge', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 3, 'name' => 'xlarge', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 4, 'name' => 'large', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 5, 'name' => 'medium', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 6, 'name' => 'small', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 7, 'name' => 'xsmall', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 8, 'name' => 'xxsmall', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT]
            ],
            'vars' => [
                ['id' => 1, 'name' => '--fs-xxxl', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 2, 'name' => '--fs-xxl', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 3, 'name' => '--fs-xl', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 4, 'name' => '--fs-lg', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 5, 'name' => '--fs-md', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 6, 'name' => '--fs-sm', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 7, 'name' => '--fs-xs', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 8, 'name' => '--fs-xxs', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT]
            ],
            'tailwind' => [
                ['id' => 1, 'name' => '4xl', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 2, 'name' => '3xl', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 3, 'name' => '2xl', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 4, 'name' => 'xl', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 5, 'name' => 'base', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 6, 'name' => 'lg', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 7, 'name' => 'sm', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 8, 'name' => 'xs', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT]
            ],
            'tags' => [
                ['id' => 1, 'name' => 'h1', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 2, 'name' => 'h2', 'lineHeight' => self::DEFAULT_HEADING_LINE_HEIGHT],
                ['id' => 3, 'name' => 'h3', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 4, 'name' => 'h4', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 5, 'name' => 'h5', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 6, 'name' => 'h6', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT],
                ['id' => 7, 'name' => 'p', 'lineHeight' => self::DEFAULT_BODY_LINE_HEIGHT]
            ]
        ];

        if (!isset($configs[$type])) {
            error_log("Fluid Font: Invalid size type: {$type}");
            return [];
        }

        $config = $configs[$type];
        $property_name = $this->get_size_property_name($type);

        return array_map(function ($item) use ($property_name) {
            return [
                'id' => $item['id'],
                $property_name => $item['name'],
                'lineHeight' => $item['lineHeight']
            ];
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
            'tailwind' => 'tailwindName',
            'tags' => 'tagName'
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
            'Fluid Font Forge',
            'Fluid Font',
            'manage_options',
            self::PLUGIN_SLUG,
            [$this, 'render_admin_page'],
            'dashicons-editor-textcolor',
            12
        );
    }

    /**
     * Unified asset enqueuing (combines all segments)
     */
    public function enqueue_assets()
    {
        $screen = get_current_screen();

        if (!$screen || !isset($_GET['page']) || $_GET['page'] !== self::PLUGIN_SLUG) {
            return;
        }

        // Enqueue Tailwind CSS
        wp_enqueue_style(
            'font-clamp-tailwind',
            'https://cdn.tailwindcss.com',
            [],
            self::VERSION
        );

        // Enqueue WordPress utilities
        wp_enqueue_script('wp-util');

        // Complete data with constants access
        wp_localize_script('wp-util', 'fontClampAjax', [
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'defaults' => [
                'minRootSize' => self::DEFAULT_MIN_ROOT_SIZE,
                'maxRootSize' => self::DEFAULT_MAX_ROOT_SIZE,
                'minViewport' => self::DEFAULT_MIN_VIEWPORT,
                'maxViewport' => self::DEFAULT_MAX_VIEWPORT,
            ],
            'data' => [
                'settings' => $this->get_font_clamp_settings(),
                'classSizes' => $this->get_font_clamp_class_sizes(),
                'variableSizes' => $this->get_font_clamp_variable_sizes(),
                'tagSizes' => $this->get_font_clamp_tag_sizes()
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
            'DEFAULT_MIN_ROOT_SIZE' => self::DEFAULT_MIN_ROOT_SIZE,
            'DEFAULT_MAX_ROOT_SIZE' => self::DEFAULT_MAX_ROOT_SIZE,
            'DEFAULT_MIN_VIEWPORT' => self::DEFAULT_MIN_VIEWPORT,
            'DEFAULT_MAX_VIEWPORT' => self::DEFAULT_MAX_VIEWPORT,
            'DEFAULT_MIN_SCALE' => self::DEFAULT_MIN_SCALE,
            'DEFAULT_MAX_SCALE' => self::DEFAULT_MAX_SCALE,
            'DEFAULT_HEADING_LINE_HEIGHT' => self::DEFAULT_HEADING_LINE_HEIGHT,
            'DEFAULT_BODY_LINE_HEIGHT' => self::DEFAULT_BODY_LINE_HEIGHT,
            'BROWSER_DEFAULT_FONT_SIZE' => self::BROWSER_DEFAULT_FONT_SIZE,
            'CSS_UNIT_CONVERSION_BASE' => self::CSS_UNIT_CONVERSION_BASE,
            'MIN_ROOT_SIZE_RANGE' => self::MIN_ROOT_SIZE_RANGE,
            'VIEWPORT_RANGE' => self::VIEWPORT_RANGE,
            'LINE_HEIGHT_RANGE' => self::LINE_HEIGHT_RANGE,
            'SCALE_RANGE' => self::SCALE_RANGE,
            'VALID_UNITS' => self::VALID_UNITS,
            'VALID_TABS' => self::VALID_TABS
        ];
    }

    // ========================================================================
    // DATA GETTERS
    // ========================================================================

    public function get_font_clamp_settings()
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

    public function get_font_clamp_class_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_CLASS_SIZES, $this->default_class_sizes);
        }
        return $cached_sizes;
    }

    public function get_font_clamp_variable_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_VARIABLE_SIZES, $this->default_variable_sizes);
        }
        return $cached_sizes;
    }

    public function get_font_clamp_tag_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_TAG_SIZES, $this->default_tag_sizes);
        }
        return $cached_sizes;
    }

    public function get_font_clamp_tailwind_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option('font_clamp_tailwind_sizes', $this->default_tailwind_sizes);
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
            'settings' => $this->get_font_clamp_settings(),
            'classSizes' => $this->get_font_clamp_class_sizes(),
            'variableSizes' => $this->get_font_clamp_variable_sizes(),
            'tagSizes' => $this->get_font_clamp_tag_sizes(),
            'tailwindSizes' => $this->get_font_clamp_tailwind_sizes()
        ];

        echo $this->get_complete_interface($data);
    }

    /**
     * Complete interface HTML
     */
    private function get_complete_interface($data)
    {
        $settings = $data['settings'];
        $class_sizes = $data['class_sizes'];
        $variable_sizes = $data['variable_sizes'];
        $tag_sizes = $data['tag_sizes'];

        ob_start();
?>
        <div class="wrap" style="background: var(--clr-page-bg); padding: 20px; min-height: 100vh;">
            <div class="fcc-header-section">
                <h1 class="text-2xl font-bold mb-4">Fluid Font Forge (<?php echo self::VERSION; ?>)</h1><br>

                <!-- About Section -->
                <div class="fcc-info-toggle-section">
                    <button class="fcc-info-toggle expanded" data-toggle-target="about-content">
                        <span style="color: #FAF9F6 !important;">ℹ️ About Fluid Font Forge</span>
                        <span class="fcc-toggle-icon" style="color: #FAF9F6 !important;">▼</span>
                    </button>
                    <div class="fcc-info-content expanded" id="about-content">
                        <div style="color: var(--clr-txt); font-size: 14px; line-height: 1.6;">
                            <p style="margin: 0 0 16px 0; color: var(--clr-txt);">
                                I've been a font nerd for a while. I enjoy seeing designers presenting an attraction in a striking way that doesn't attract attention itself. It's exactly like a director, or cinematographer moves you into the emotional depth of a movie without you knowing it. When CSS clamp() came along, there was an explosion of responsive font management. That gave the font the ability to stop drawing attention to itself and present the message effectively. I recently visited a YouTube presentation by a favorite WordPress guru. There was a sophisticated calculator for font clamping demo development. This was better than many of the websites out there. But it wasn't enough! Here's my attempt. Enjoy.</p>
                            <div style="background: rgba(60, 32, 23, 0.1); padding: 12px 16px; border-radius: 6px; border-left: 4px solid var(--clr-accent); margin-top: 20px;">
                                <p style="margin: 0; font-size: 13px; opacity: 0.95; line-height: 1.5; color: var(--clr-txt);">
                                    Fluid Font Forge by Jim R. (<a href="https://jimrweb.com" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">JimRWeb</a>), developed with tremendous help from Claude AI (<a href="https://anthropic.com" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">Anthropic</a>), based on an original snippet by Imran Siddiq (<a href="https://websquadron.co.uk" target="_blank" style="color: #CD5C5C; text-decoration: underline; font-weight: 600;">WebSquadron</a>), in his Font Clamp Calculator (2.2).</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="fcc-loading-screen" class="fcc-loading-screen">
                    <div class="fcc-loading-content">
                        <div class="fcc-loading-spinner"></div>
                        <h2>Fluid Font Forge<br><br>Loading...</h2>
                        <p>Initializing enhanced interface and advanced features...</p>
                        <div class="fcc-loading-progress">
                            <div class="fcc-progress-bar"></div>
                        </div>
                    </div>
                </div>

                <!-- Main Section -->
                <div class="font-clamp-container" id="fcc-main-container">
                    <div style="padding: 20px;">

                        <!-- How to Use Panel -->
                        <div class="fcc-info-toggle-section">
                            <button class="fcc-info-toggle expanded" data-toggle-target="info-content">
                                <span style="color: #FAF9F6 !important;">ℹ️ How to Use Fluid Font Forge</span>
                                <span class="fcc-toggle-icon" style="color: #FAF9F6 !important;">▼</span>
                            </button>
                            <div class="fcc-info-content expanded" id="info-content">
                                <div style="color: var(--clr-txt); font-size: 14px; line-height: 1.6;">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 20px;">
                                        <div>
                                            <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">1. Configure Settings</h4>
                                            <p style="margin: 0; font-size: 13px; line-height: 1.5;">Set your font units, viewport range, and scaling ratios. Choose a base value that represents your base root size font size.</p>
                                        </div>
                                        <div>
                                            <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">2. Manage Font Sizes</h4>
                                            <p style="margin: 0; font-size: 13px; line-height: 1.5;">Use the enhanced table to add, edit, delete, or reorder your font sizes. Drag rows to reorder them in the table.</p>
                                        </div>
                                        <div>
                                            <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">3. Preview Results</h4>
                                            <p style="margin: 0; font-size: 13px; line-height: 1.5;">Use the enhanced preview with controls to see how your fonts will look at different screen sizes. The displays are at scale at the ends of your entered Min Width and Max Width.</p>
                                        </div>
                                        <div>
                                            <h4 style="color: var(--clr-secondary); font-size: 15px; font-weight: 600; margin: 0 0 8px 0;">4. Copy CSS</h4>
                                            <p style="margin: 0; font-size: 13px; line-height: 1.5;">Generate clamp() CSS functions ready to use in your projects. Available as classes, variables, or tag styles with enhanced copy functionality.</p>
                                        </div>
                                    </div>

                                    <div style="background: #F0E6DA; padding: 12px 16px; border-radius: 8px; border: 1px solid #5C3324; margin: 16px 0 0 0; text-align: center;">
                                        <h4 style="color: #3C2017; font-size: 14px; font-weight: 600; margin: 0 0 6px 0;">💡 Pro Tip</h4>
                                        <p style="margin: 0; font-size: 13px; color: var(--clr-txt);">Use Preview Font to test with your actual web fonts and enjoy the enhanced interactive experience with smooth animations and professional styling.</p>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Enhanced Header Section -->
                        <div style="margin: 2rem 0;">
                            <!-- Top Row: Preview Font Input and Autosave Status -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <div class="fcc-font-input">
                                    <label for="preview-font-url" data-tooltip="Load a custom WOFF2 font file to preview your font sizes">Preview Font:</label>
                                    <input type="text" id="preview-font-url" class="fcc-input" placeholder="Paste WOFF2 font URL" value="<?php echo esc_attr($settings['previewFontUrl']); ?>" style="width: 200px; margin-bottom: 0;" data-tooltip="Enter a WOFF2 font URL to see how your sizes look with that font">
                                    <span id="font-filename">Default</span>
                                </div>

                                <div style="display: flex; align-items: center; gap: 20px;">
                                    <div class="fcc-autosave-flex">
                                        <label data-tooltip="Automatically save changes as you make them">
                                            <input type="checkbox" id="autosave-toggle" <?php echo $settings['autosaveEnabled'] ? 'checked' : ''; ?> data-tooltip="Toggle automatic saving of your font settings">
                                            <span>Autosave</span>
                                        </label>
                                        <button id="save-btn" class="fcc-btn" data-tooltip="Save all current settings and sizes to database">
                                            Save
                                        </button>
                                        <div id="autosave-status" class="autosave-status idle">
                                            <span id="autosave-icon">⚡</span>
                                            <span id="autosave-text">Ready</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Row: Large Tabs -->
                            <div style="display: flex; justify-content: center;">
                                <div class="fcc-tabs" style="width: 100%; max-width: 600px;">
                                    <button id="class-tab" class="tab-button <?php echo $settings['activeTab'] === 'class' ? 'active' : ''; ?>" style="flex: 1; padding: 12px 24px; border-radius: 6px; font-size: 16px; font-weight: 600;" data-tab="class" data-tooltip="Generate CSS classes like .large, .medium, .small for use in HTML">Class</button>
                                    <button id="vars-tab" class="tab-button <?php echo $settings['activeTab'] === 'vars' ? 'active' : ''; ?>" style="flex: 1; padding: 12px 24px; border-radius: 6px; font-size: 16px; font-weight: 600;" data-tab="vars" data-tooltip="Generate CSS custom properties like --fs-lg for use with var() in CSS">Variables</button>
                                    <button id="tailwind-tab" class="tab-button <?php echo $settings['activeTab'] === 'tailwind' ? 'active' : ''; ?>" style="flex: 1; padding: 12px 24px; border-radius: 6px; font-size: 16px; font-weight: 600;" data-tab="tailwind" data-tooltip="Generate Tailwind config fontSize object for direct integration with tailwind.config.js">Tailwind Config</button>
                                    <button id="tag-tab" class="tab-button <?php echo $settings['activeTab'] === 'tag' ? 'active' : ''; ?>" style="flex: 1; padding: 12px 24px; border-radius: 6px; font-size: 16px; font-weight: 600;" data-tab="tag" data-tooltip="Generate CSS that directly styles HTML tags like h1, h2, p automatically">Tags</button>
                                </div>
                            </div>
                        </div>

                        <!-- Settings and Data Table - Side by Side -->
                        <div class="fcc-main-grid">
                            <!-- Column 1: Settings Panel -->
                            <div>
                                <div class="fcc-panel" style="margin-bottom: 8px;">
                                    <h2 class="settings-title">Settings</h2>

                                    <!-- Font Units Selector -->
                                    <div class="font-units-section">
                                        <label class="font-units-label">Select Font Units to Use:</label>
                                        <div class="font-units-buttons">
                                            <button id="px-tab" class="unit-button <?php echo $settings['unitType'] === 'px' ? 'active' : ''; ?>" data-unit="px"
                                                aria-label="Use pixel units for font sizes - more predictable but less accessible"
                                                aria-pressed="<?php echo $settings['unitType'] === 'px' ? 'true' : 'false'; ?>"
                                                data-tooltip="Use pixel units for font sizes - more predictable but less accessible">PX</button>
                                            <button id="rem-tab" class="unit-button <?php echo $settings['unitType'] === 'rem' ? 'active' : ''; ?>" data-unit="rem"
                                                aria-label="Use rem units for font sizes - scales with user's browser settings"
                                                aria-pressed="<?php echo $settings['unitType'] === 'rem' ? 'true' : 'false'; ?>"
                                                data-tooltip="Use rem units for font sizes - scales with user's browser settings">REM</button>
                                        </div>
                                    </div>
                                    <p class="divider">What is the base font size at the viewport limits and the viewport range?</p>
                                    <!-- Row 1: Min Root and Min Width -->
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                                        <div class="grid-item">
                                            <label class="component-label" for="min-root-size">Min Viewport Font Size (px)</label>
                                            <input type="number" id="min-root-size" value="<?php echo esc_attr($settings['minRootSize'] ?? self::DEFAULT_MIN_ROOT_SIZE); ?>"
                                                class="component-input" style="width: 100%;"
                                                min="<?php echo self::MIN_ROOT_SIZE_RANGE[0]; ?>"
                                                max="<?php echo self::MIN_ROOT_SIZE_RANGE[1]; ?>"
                                                step="1"
                                                aria-label="Minimum root font size in pixels - base font size at minimum viewport width"
                                                data-tooltip="Base font size at minimum viewport width">
                                        </div>
                                        <div class="grid-item">
                                            <label class="component-label" for="min-viewport">Min Viewport Width (px)</label>
                                            <input type="number" id="min-viewport" value="<?php echo esc_attr($settings['minViewport']); ?>"
                                                class="component-input" style="width: 100%;"
                                                min="<?php echo self::VIEWPORT_RANGE[0]; ?>"
                                                max="<?php echo self::VIEWPORT_RANGE[1]; ?>"
                                                step="1"
                                                aria-label="Minimum viewport width in pixels - screen width where minimum font size applies"
                                                data-tooltip="Screen width where minimum font size applies">
                                        </div>
                                    </div>

                                    <!-- Row 2: Max Root and Max Width -->
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                                        <div class="grid-item">
                                            <label class="component-label" for="max-root-size">Max Viewport Font Size (px)</label>
                                            <input type="number" id="max-root-size" value="<?php echo esc_attr($settings['maxRootSize'] ?? self::DEFAULT_MAX_ROOT_SIZE); ?>"
                                                class="component-input" style="width: 100%;"
                                                min="<?php echo self::MIN_ROOT_SIZE_RANGE[0]; ?>"
                                                max="<?php echo self::MIN_ROOT_SIZE_RANGE[1]; ?>"
                                                step="1"
                                                aria-label="Maximum root font size in pixels - base font size at maximum viewport width"
                                                data-tooltip="Base font size at maximum viewport width">
                                        </div>
                                        <div class="grid-item">
                                            <label class="component-label" for="max-viewport">Max Viewport Width (px)</label>
                                            <input type="number" id="max-viewport" value="<?php echo esc_attr($settings['maxViewport']); ?>"
                                                class="component-input" style="width: 100%;"
                                                min="<?php echo self::VIEWPORT_RANGE[0]; ?>"
                                                max="<?php echo self::VIEWPORT_RANGE[1]; ?>"
                                                step="1"
                                                aria-label="Maximum viewport width in pixels - screen width where maximum font size applies"
                                                data-tooltip="Screen width where maximum font size applies">
                                        </div>
                                    </div>
                                    <p class="divider">How should your font scale? Set the ratio at both viewport limits.</p>

                                    <!-- Row 3: Min Scale -->
                                    <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px;">
                                        <div class="grid-item">
                                            <label class="component-label" for="min-scale">Min Viewport Font Scaling</label>
                                            <select id="min-scale" class="component-select" style="width: 100%;"
                                                aria-label="Minimum scale ratio for typography on smaller screens - controls size differences between font levels"
                                                data-tooltip="Typography scale ratio for smaller screens - how much size difference between font levels">
                                                <option value="1.067" <?php selected($settings['minScale'], '1.067'); ?>>1.067 Minor Second</option>
                                                <option value="1.125" <?php selected($settings['minScale'], '1.125'); ?>>1.125 Major Second</option>
                                                <option value="1.200" <?php selected($settings['minScale'], '1.200'); ?>>1.200 Minor Third</option>
                                                <option value="1.250" <?php selected($settings['minScale'], '1.250'); ?>>1.250 Major Third</option>
                                                <option value="1.333" <?php selected($settings['minScale'], '1.333'); ?>>1.333 Perfect Fourth</option>
                                                <option value="1.414" <?php selected($settings['minScale'], '1.414'); ?>>1.414 Augmented Fourth</option>
                                                <option value="1.500" <?php selected($settings['minScale'], '1.500'); ?>>1.500 Perfect Fifth</option>
                                                <option value="1.618" <?php selected($settings['minScale'], '1.618'); ?>>1.618 Golden Ratio</option>
                                                <option value="1.667" <?php selected($settings['minScale'], '1.667'); ?>>1.667 Major Sixth</option>
                                                <option value="1.778" <?php selected($settings['minScale'], '1.778'); ?>>1.778 Minor Seventh</option>
                                                <option value="1.875" <?php selected($settings['minScale'], '1.875'); ?>>1.875 Major Seventh</option>
                                                <option value="2.000" <?php selected($settings['minScale'], '2.000'); ?>>2.000 Octave</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Row 4: Max Scale -->
                                    <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px;">
                                        <div class="grid-item">
                                            <label class="component-label" for="max-scale">Max Viewport Font Scaling</label>
                                            <select id="max-scale" class="component-select" style="width: 100%;"
                                                aria-label="Maximum scale ratio for typography on larger screens - controls how dramatic size differences are on big screens"
                                                data-tooltip="Typography scale ratio for larger screens - how dramatic the size differences should be on big screens">
                                                <option value="1.067" <?php selected($settings['maxScale'], '1.067'); ?>>1.067 Minor Second</option>
                                                <option value="1.125" <?php selected($settings['maxScale'], '1.125'); ?>>1.125 Major Second</option>
                                                <option value="1.200" <?php selected($settings['maxScale'], '1.200'); ?>>1.200 Minor Third</option>
                                                <option value="1.250" <?php selected($settings['maxScale'], '1.250'); ?>>1.250 Major Third</option>
                                                <option value="1.333" <?php selected($settings['maxScale'], '1.333'); ?>>1.333 Perfect Fourth</option>
                                                <option value="1.414" <?php selected($settings['maxScale'], '1.414'); ?>>1.414 Augmented Fourth</option>
                                                <option value="1.500" <?php selected($settings['maxScale'], '1.500'); ?>>1.500 Perfect Fifth</option>
                                                <option value="1.618" <?php selected($settings['maxScale'], '1.618'); ?>>1.618 Golden Ratio</option>
                                                <option value="1.667" <?php selected($settings['maxScale'], '1.667'); ?>>1.667 Major Sixth</option>
                                                <option value="1.778" <?php selected($settings['maxScale'], '1.778'); ?>>1.778 Minor Seventh</option>
                                                <option value="1.875" <?php selected($settings['maxScale'], '1.875'); ?>>1.875 Major Seventh</option>
                                                <option value="2.000" <?php selected($settings['maxScale'], '2.000'); ?>>2.000 Octave</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Column 2: Font Size Classes (Data Table Panel) -->
                            <div>
                                <div class="fcc-panel" id="sizes-table-container">
                                    <h2 style="margin-bottom: 12px;" id="table-title">Font Size Classes</h2>

                                    <!-- Base Value and Action Buttons Row -->
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                                        <!-- Left: Base Value Combo Box -->
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <label class="component-label" for="base-value" style="margin-bottom: 0; white-space: nowrap;">Base</label>
                                            <select id="base-value" class="component-select" style="width: 120px; height: 32px;"
                                                aria-label="Base reference size - used for calculating all other font sizes in the scale, typically your body text size"
                                                data-tooltip="Reference size used for calculating other sizes - this will be your body text size">
                                                <?php
                                                // Populate based on current tab
                                                if ($settings['activeTab'] === 'class') {
                                                    $current_sizes = $class_sizes;
                                                    $property_name = 'className';
                                                    $selected_id = $settings['selectedClassSizeId'];
                                                } elseif ($settings['activeTab'] === 'vars') {
                                                    $current_sizes = $variable_sizes;
                                                    $property_name = 'variableName';
                                                    $selected_id = $settings['selectedVariableSizeId'];
                                                } elseif ($settings['activeTab'] === 'tailwind') {
                                                    $current_sizes = $this->get_font_clamp_tailwind_sizes();
                                                    $property_name = 'tailwindName';
                                                    $selected_id = 5; // default to 'base'
                                                } else {
                                                    $current_sizes = $tag_sizes;
                                                    $property_name = 'tagName';
                                                    $selected_id = $settings['selectedTagSizeId'];
                                                }
                                                foreach ($current_sizes as $size) {
                                                    $selected = $size['id'] == $selected_id ? 'selected' : '';
                                                    echo "<option value='{$size['id']}' {$selected}>{$size[$property_name]}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>

                                        <!-- Right: Action Buttons -->
                                        <div class="fcc-table-buttons" id="table-action-buttons">
                                            <div style="color: var(--clr-secondary); font-size: 12px; font-style: italic;">Loading...</div>
                                        </div>
                                    </div>

                                    <div id="sizes-table-wrapper">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Full-Width Preview Section -->
                        <div class="fcc-preview-enhanced" style="clear: both; margin: 20px 0;">
                            <div class="fcc-preview-header-row">
                                <h2 style="color: var(--clr-primary); margin: 0;">Font Preview</h2>
                            </div>

                            <div class="fcc-preview-grid">
                                <div class="fcc-preview-column">
                                    <div class="fcc-preview-column-header">
                                        <h3>Min Size (Small Screens)</h3>
                                        <div class="fcc-scale-indicator" id="min-viewport-display"><?php echo esc_html($settings['minViewport']); ?>px</div>
                                    </div>
                                    <div id="preview-min-container" style="background: white; border-radius: 8px; padding: 20px; border: 2px solid var(--clr-secondary); min-height: 320px; box-shadow: inset 0 2px 4px var(--clr-shadow); overflow: hidden;">
                                        <div style="text-align: center; color: var(--clr-txt); font-style: italic; padding: 60px 20px;">
                                            <div class="fcc-loading-spinner" style="width: 25px; height: 25px; margin: 0 auto 10px;"></div>
                                            <div>Loading preview...</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="fcc-preview-column">
                                    <div class="fcc-preview-column-header">
                                        <h3>Max Size (Large Screens)</h3>
                                        <div class="fcc-scale-indicator" id="max-viewport-display"><?php echo esc_html($settings['maxViewport']); ?>px</div>
                                    </div>
                                    <div id="preview-max-container" style="background: white; border-radius: 8px; padding: 20px; border: 2px solid var(--clr-secondary); min-height: 320px; box-shadow: inset 0 2px 4px var(--clr-shadow); overflow: hidden;">
                                        <div style="text-align: center; color: var(--clr-txt); font-style: italic; padding: 60px 20px;">
                                            <div class="fcc-loading-spinner" style="width: 25px; height: 25px; margin: 0 auto 10px;"></div>
                                            <div>Loading preview...</div>
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
                                </div>
                            </div>
                            <div style="background: white; border-radius: 6px; padding: 8px; border: 1px solid #d1d5db; overflow: auto; max-height: 300px;">
                                <pre id="generated-code" style="font-size: 12px; white-space: pre-wrap; color: #111827; margin: 0;">/* Loading CSS output... */</pre>
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
                if (!$this->is_font_clamp_page() || $this->assets_loaded) {
                    return;
                }

                $this->render_unified_css();
                $this->render_unified_javascript();

                $this->assets_loaded = true;
            }

            /**
             * Check if we're on the plugin page
             */
            private function is_font_clamp_page()
            {
                return isset($_GET['page']) && sanitize_text_field($_GET['page']) === self::PLUGIN_SLUG;
            }

            /**
             * Unified CSS (all styles)
             */
            private function render_unified_css()
            {
                ?>
                    <style id="font-clamp-unified-styles">
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

                        /* Layout styles for Base Value and Action Buttons */
                        .fcc-table-buttons {
                            display: flex;
                            gap: var(--jimr-space-2);
                            align-items: center;
                            justify-content: flex-end;
                            /* Right-justify the buttons */
                        }

                        /* Ensure the Base Value combo box fits nicely */
                        #base-value {
                            min-width: 120px;
                            height: 32px !important;
                            font-size: 13px;
                        }

                        /* Style the Base label to match other component labels */
                        .component-label[for="base-value"] {
                            font-size: var(--jimr-font-sm);
                            font-weight: 500;
                            color: var(--clr-txt);
                        }

                        /* Responsive adjustment for smaller screens */
                        @media (max-width: 768px) {
                            .fcc-table-buttons {
                                flex-direction: column;
                                gap: 8px;
                                align-items: stretch;
                            }

                            #base-value {
                                width: 100px;
                                font-size: 12px;
                            }
                        }

                        /* Copy Button Specific Styles */
                        .fcc-copy-btn {
                            background: var(--clr-accent);
                            color: var(--clr-btn-txt);
                            border: 2px solid var(--clr-btn-bdr);
                            padding: var(--jimr-space-2) var(--jimr-space-4);
                            border-radius: var(--jimr-border-radius);
                            border-color: var(--clr-btn-bdr);
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

                        .fcc-copy-btn::before {
                            content: '';
                            position: absolute;
                            top: 50%;
                            left: 50%;
                            width: 0;
                            height: 0;
                            background: rgba(255, 255, 255, 0.3);
                            border-radius: 50%;
                            transform: translate(-50%, -50%);
                            transition: width 0.6s, height 0.6s;
                        }

                        .fcc-copy-btn:active::before {
                            width: 300px;
                            height: 300px;
                        }

                        .fcc-copy-btn:hover {
                            background: var(--clr-btn-hover);
                            transform: translateY(-2px);
                            box-shadow: var(--clr-shadow-lg);
                        }

                        .fcc-copy-btn:active {
                            transform: translateY(0) scale(0.98);
                            box-shadow: var(--clr-shadow);
                        }

                        .fcc-copy-btn:focus {
                            outline: none;
                            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.3), var(--clr-shadow-lg);
                        }

                        .fcc-copy-btn.success {
                            background: var(--clr-success);
                            border-color: var(--clr-success-dark);
                            color: white;
                            font-style: normal;
                            text-transform: none;
                            letter-spacing: normal;
                        }

                        .copy-icon {
                            font-size: var(--jimr-font-sm);
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
                        label {
                            color: var(--clr-txt);
                            font-weight: 500;
                            font-size: var(--jimr-font-sm);
                            line-height: 1.3;
                            margin-bottom: var(--jimr-space-2);
                            display: block;
                        }

                        /* Header Section and Main Container */
                        .fcc-header-section,
                        .space-clamp-container {
                            width: 1280px;
                            margin: 0 auto;
                        }

                        /* Main Container */
                        .font-clamp-container {
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

                        .font-clamp-container.ready {
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

                        /* Mobile table scroll fix */
                        #sizes-table-wrapper {
                            overflow-x: auto;
                            -webkit-overflow-scrolling: touch;
                            width: 100%;
                            max-width: 100%;
                        }

                        @media (max-width: 768px) {
                            #sizes-table-container {
                                padding: 12px;
                            }

                            .font-table th,
                            .font-table td {
                                padding: 6px 4px;
                                font-size: 11px;
                                white-space: nowrap;
                            }
                        }

                        /* Loading Screen */
                        .fcc-loading-screen {
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: var(--clr-overlay);
                            backdrop-filter: blur(5px);
                            z-index: 9999;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: opacity 0.5s ease, visibility 0.5s ease;
                        }

                        .fcc-loading-screen.hidden {
                            opacity: 0;
                            visibility: hidden;
                            pointer-events: none;
                        }

                        .fcc-loading-content {
                            text-align: center;
                            max-width: 420px;
                            padding: 50px;
                            background: var(--clr-card-bg);
                            border-radius: var(--jimr-border-radius-lg);
                            box-shadow: var(--clr-shadow-xl);
                            border: 3px solid var(--clr-primary);
                            position: relative;
                        }

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

                        .fcc-loading-content h2 {
                            color: var(--clr-primary);
                            margin: 0 0 12px 0;
                            font-size: 26px;
                            font-weight: 700;
                            font-family: 'Georgia', serif;
                            text-shadow: 1px 1px 2px var(--clr-shadow);
                        }

                        .fcc-loading-content p {
                            color: var(--clr-txt);
                            margin: 0 0 25px 0;
                            font-size: 15px;
                            font-weight: 500;
                        }

                        .fcc-loading-progress {
                            width: 100%;
                            height: 6px;
                            background: var(--clr-light);
                            border-radius: 3px;
                            overflow: hidden;
                            border: 1px solid var(--clr-primary);
                        }

                        .fcc-progress-bar {
                            height: 100%;
                            background: linear-gradient(90deg, var(--clr-accent), var(--clr-btn-hover), var(--clr-accent));
                            width: 0%;
                            border-radius: 2px;
                            animation: fcc-progress 3s ease-in-out infinite;
                            box-shadow: 0 0 10px rgba(255, 215, 0, 0.4);
                        }

                        @keyframes fcc-progress {
                            0% {
                                width: 0%;
                            }

                            50% {
                                width: 70%;
                            }

                            100% {
                                width: 100%;
                            }
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

                        /* Dividers */
                        .divider {
                            border-top: 1px solid var(--clr-secondary);
                            margin-top: var(--jimr-space-4);
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

                        .autosave-status.saving {
                            background: linear-gradient(135deg, var(--clr-accent), var(--clr-btn-hover));
                            color: var(--clr-btn-txt);
                            border-color: var(--clr-btn-bdr);
                            animation: pulse 1.5s ease-in-out infinite;
                        }

                        .autosave-status.saved {
                            background: linear-gradient(135deg, var(--clr-success), var(--clr-success-dark));
                            color: white;
                            border-color: #15803d;
                        }

                        .autosave-status.error {
                            background: linear-gradient(135deg, var(--clr-btn-bdr), var(--jimr-danger));
                            color: white;
                            border-color: #991b1b;
                        }

                        .autosave-status.idle {
                            background: var(--clr-card-bg);
                            color: var(--clr-txt);
                            border-color: var(--clr-secondary);
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

                        /* Font Input */
                        .fcc-font-input {
                            display: flex;
                            align-items: center;
                            gap: var(--jimr-space-3);
                        }

                        .fcc-font-input label {
                            white-space: nowrap;
                            margin-bottom: 0;
                            font-size: var(--jimr-font-sm);
                            font-weight: 500;
                            color: var(--clr-txt);
                        }

                        .fcc-font-input input {
                            width: 200px;
                            margin-bottom: 0;
                        }

                        .fcc-font-input span {
                            font-size: var(--jimr-font-sm);
                            color: var(--jimr-gray-500);
                            font-style: italic;
                        }

                        /* Preview Row Styles */
                        .preview-row {
                            margin-bottom: var(--jimr-space-1) !important;
                            padding: var(--jimr-space-2) var(--jimr-space-3) !important;
                            border-radius: var(--jimr-border-radius) !important;
                            transition: var(--jimr-transition) !important;
                            cursor: pointer !important;
                            border: 1px solid transparent !important;
                        }

                        /* Data Table Row Styles */
                        .size-row {
                            transition: all 0.2s ease !important;
                            cursor: pointer !important;
                            border: 1px solid transparent !important;
                        }

                        /* Combined Preview Row and Data Table Row Hover Styles */
                        .preview-row:hover,
                        .size-row:hover {
                            background-color: rgba(59, 130, 246, 0.05) !important;
                            border-color: rgba(59, 130, 246, 0.2) !important;
                            transform: translateX(2px) !important;
                        }

                        .preview-row.selected,
                        .size-row.selected {
                            background-color: rgba(59, 130, 246, 0.25) !important;
                            border-color: rgba(59, 130, 246, 0.5) !important;
                            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.4) !important;
                        }

                        .preview-row.selected:hover,
                        .size-row.selected:hover {
                            background-color: rgba(59, 130, 246, 0.15) !important;
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

                        /* Accessibility */
                        @media (prefers-reduced-motion: reduce) {
                            * {
                                animation-duration: 0.01ms !important;
                                animation-iteration-count: 1 !important;
                                transition-duration: 0.01ms !important;
                            }
                        }

                        .fcc-btn:focus-visible,
                        .fcc-input:focus-visible {
                            outline: 2px solid var(--clr-accent) !important;
                            outline-offset: 2px !important;
                        }

                        /* Drag and Drop Styles */
                        .size-row.dragging {
                            opacity: 0.5 !important;
                        }

                        .drag-insertion-line {
                            height: 4px !important;
                            background: #3b82f6 !important;
                            margin: 0 !important;
                            border-radius: 2px !important;
                            box-shadow: 0 0 4px rgba(59, 130, 246, 0.5) !important;
                        }

                        .drag-handle {
                            cursor: grab !important;
                        }

                        .drag-handle:active {
                            cursor: grabbing !important;
                        }
                    </style>
                <?php
            }

            /**
             * Unified JavaScript with Advanced Features Integration
             */
            private function render_unified_javascript()
            {
                ?>
                    <script id="font-clamp-unified-script">
                        // Simple JavaScript Tooltips
                        class SimpleTooltips {
                            constructor() {
                                this.tooltip = null;
                                this.init();
                            }

                            init() {
                                document.addEventListener('mouseover', (e) => {
                                    if (e.target.dataset.tooltip) {
                                        this.showTooltip(e.target, e.target.dataset.tooltip);
                                    }
                                });

                                document.addEventListener('mouseout', (e) => {
                                    if (e.target.dataset.tooltip) {
                                        this.hideTooltip();
                                    }
                                });
                            }

                            showTooltip(element, text) {
                                this.hideTooltip();

                                this.tooltip = document.createElement('div');
                                this.tooltip.style.cssText = `
                        position: absolute;
                        background: #8B4513;
                        color: white;
                        padding: 8px 12px;
                        border-radius: 4px;
                        font-size: 12px;
                        white-space: nowrap;
                        z-index: 99999;
                        pointer-events: none;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        border: 1px solid #654321;
                    `;
                                this.tooltip.textContent = text;
                                document.body.appendChild(this.tooltip);

                                const rect = element.getBoundingClientRect();
                                const tooltipRect = this.tooltip.getBoundingClientRect();

                                let left = rect.left + window.scrollX + rect.width / 2 - tooltipRect.width / 2;
                                let top = rect.top + window.scrollY - tooltipRect.height - 8;

                                if (left < 5) left = 5;
                                if (left + tooltipRect.width > window.innerWidth - 5) {
                                    left = window.innerWidth - tooltipRect.width - 5;
                                }
                                if (top < window.scrollY + 5) {
                                    top = rect.bottom + window.scrollY + 8;
                                }

                                this.tooltip.style.left = left + 'px';
                                this.tooltip.style.top = top + 'px';
                            }

                            hideTooltip() {
                                if (this.tooltip) {
                                    this.tooltip.remove();
                                    this.tooltip = null;
                                }
                            }
                        }

                        // ========================================================================
                        // SHARED UTILITIES
                        // ========================================================================

                        const FontClampUtils = {

                            getCurrentSizes(activeTab = null, fontClampAdvanced = null) {
                                const tab = activeTab || window.fontClampCore?.activeTab || 'class';

                                let sizes = [];
                                switch (tab) {
                                    case 'class':
                                        sizes = window.fontClampAjax?.data?.classSizes || [];
                                        break;
                                    case 'vars':
                                        sizes = window.fontClampAjax?.data?.variableSizes || [];
                                        break;
                                    case 'tailwind':
                                        sizes = window.fontClampAjax?.data?.tailwindSizes || [];
                                        break;
                                    case 'tag':
                                        sizes = window.fontClampAjax?.data?.tagSizes || [];
                                        break;
                                    default:
                                        sizes = [];
                                }

                                // Handle defaults if needed and fontClampAdvanced instance is available
                                if (sizes.length === 0 && fontClampAdvanced) {
                                    switch (tab) {
                                        case 'class':
                                            sizes = fontClampAdvanced.getDefaultClassSizes();
                                            if (window.fontClampAjax?.data) {
                                                window.fontClampAjax.data.classSizes = sizes;
                                            }
                                            break;
                                        case 'vars':
                                            sizes = fontClampAdvanced.getDefaultVariableSizes();
                                            if (window.fontClampAjax?.data) {
                                                window.fontClampAjax.data.variableSizes = sizes;
                                            }
                                            break;
                                        case 'tailwind':
                                            sizes = fontClampAdvanced.getDefaultTailwindSizes();
                                            if (window.fontClampAjax?.data) {
                                                window.fontClampAjax.data.tailwindSizes = sizes;
                                            }
                                            break;
                                        case 'tag':
                                            sizes = fontClampAdvanced.getDefaultTagSizes();
                                            if (window.fontClampAjax?.data) {
                                                window.fontClampAjax.data.tagSizes = sizes;
                                            }
                                            break;
                                    }
                                }

                                return sizes;
                            }

                        }

                        // Enhanced Core Interface Controller
                        class FontClampEnhancedCoreInterface {
                            constructor() {
                                console.log('🚀 Fluid Font Forge');

                                this.initializeData();
                                this.cacheElements();
                                this.bindBasicEvents();
                                this.bindEnhancedEvents();
                                this.bindToggleEvents();
                                this.triggerSegmentHooks();
                                this.syncVisualState();

                                setTimeout(() => {
                                    this.updateBaseValueDropdown(this.activeTab);
                                }, 100);

                                this.initLoadingSequence();
                            }

                            bindToggleEvents() {
                                setTimeout(() => {
                                    const infoToggle = document.querySelector('[data-toggle-target="info-content"]');
                                    if (infoToggle) {
                                        infoToggle.addEventListener('click', () => this.toggleInfo());
                                    }

                                    const aboutToggle = document.querySelector('[data-toggle-target="about-content"]');
                                    if (aboutToggle) {
                                        aboutToggle.addEventListener('click', () => this.toggleAbout());
                                    }
                                }, 100);
                            }

                            toggleInfo() {
                                const button = document.querySelector('[data-toggle-target="info-content"]');
                                const content = document.getElementById('info-content');

                                if (content && button) {
                                    if (content.classList.contains('expanded')) {
                                        content.classList.remove('expanded');
                                        button.classList.remove('expanded');
                                    } else {
                                        content.classList.add('expanded');
                                        button.classList.add('expanded');
                                    }
                                }
                            }

                            toggleAbout() {
                                const button = document.querySelector('[data-toggle-target="about-content"]');
                                const content = document.getElementById('about-content');

                                if (content && button) {
                                    if (content.classList.contains('expanded')) {
                                        content.classList.remove('expanded');
                                        button.classList.remove('expanded');
                                    } else {
                                        content.classList.add('expanded');
                                        button.classList.add('expanded');
                                    }
                                }
                            }

                            initLoadingSequence() {
                                // Why loading steps: Users need visual feedback during complex initialization
                                // Prevents flash of unstyled content and confusing intermediate states
                                this.loadingSteps = {
                                    coreReady: false, // Why: Core interface must be ready first
                                    advancedReady: false, // Why: Advanced features needed for full functionality  
                                    contentPopulated: false // Why: Data must be loaded before revealing interface
                                };

                                // Why event listeners: Components signal readiness asynchronously
                                // Can't predict which loads first in WordPress admin environment
                                window.addEventListener('fontClampAdvancedReady', () => {
                                    this.loadingSteps.advancedReady = true;
                                    this.checkAndRevealInterface();
                                });

                                window.addEventListener('fontClamp_dataUpdated', () => {
                                    this.loadingSteps.contentPopulated = true;
                                    this.checkAndRevealInterface();
                                });

                                setTimeout(() => {
                                    if (!this.isInterfaceRevealed()) {
                                        this.revealInterface();
                                    }
                                }, 5000);
                            }

                            checkAndRevealInterface() {
                                // Why wait for both: Revealing interface too early shows broken/empty state
                                // Advanced features needed for interactions, content needed for display
                                if (this.loadingSteps.advancedReady && this.loadingSteps.contentPopulated) {
                                    // Why 300ms delay: Allows final calculations to complete before reveal
                                    setTimeout(() => this.revealInterface(), 300);
                                }
                            }

                            revealInterface() {
                                if (this.isInterfaceRevealed()) return;

                                const loadingScreen = document.getElementById('fcc-loading-screen');
                                if (loadingScreen) {
                                    loadingScreen.classList.add('hidden');
                                }

                                const mainContainer = document.getElementById('fcc-main-container');
                                if (mainContainer) {
                                    mainContainer.classList.add('ready');
                                }

                                const autosaveIcon = document.getElementById('autosave-icon');
                                const autosaveText = document.getElementById('autosave-text');
                                if (autosaveIcon && autosaveText) {
                                    autosaveIcon.textContent = '💾';
                                    autosaveText.textContent = 'Ready';
                                }

                                // Create ARIA live region for screen reader announcements
                                const ariaRegion = document.createElement('div');
                                ariaRegion.id = 'fcc-announcements';
                                ariaRegion.setAttribute('aria-live', 'polite');
                                ariaRegion.setAttribute('aria-atomic', 'false');
                                ariaRegion.style.cssText = 'position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;';
                                document.body.appendChild(ariaRegion);
                            }

                            isInterfaceRevealed() {
                                const mainContainer = document.getElementById('fcc-main-container');
                                return mainContainer && mainContainer.classList.contains('ready');
                            }

                            syncVisualState() {
                                document.querySelectorAll('[data-tab]').forEach(tab => {
                                    tab.classList.remove('active');
                                });
                                document.querySelector(`[data-tab="${this.activeTab}"]`)?.classList.add('active');
                            }

                            initializeData() {
                                // Get data from wp_localize_script
                                const data = window.fontClampAjax?.data || {};

                                this.settings = data.settings || {};
                                this.classSizes = data.classSizes || [];
                                this.variableSizes = data.variableSizes || [];
                                this.tagSizes = data.tagSizes || [];
                                this.tailwindSizes = data.tailwindSizes || [];

                                this.activeTab = this.settings.activeTab || 'class';
                                this.unitType = this.settings.unitType || 'px';
                            }

                            // ========================================================================
                            // EVENT BINDING & SETUP METHODS  
                            // ========================================================================

                            cacheElements() {
                                this.elements = {
                                    classTab: document.getElementById('class-tab'),
                                    varsTab: document.getElementById('vars-tab'),
                                    tagTab: document.getElementById('tag-tab'),
                                    pxTab: document.getElementById('px-tab'),
                                    remTab: document.getElementById('rem-tab'),
                                    tableTitle: document.getElementById('table-title'),
                                    selectedCodeTitle: document.getElementById('selected-code-title'),
                                    generatedCodeTitle: document.getElementById('generated-code-title'),
                                    previewMinContainer: document.getElementById('preview-min-container'),
                                    previewMaxContainer: document.getElementById('preview-max-container'),
                                    sizesTableWrapper: document.getElementById('sizes-table-wrapper'),
                                    classCode: document.getElementById('class-code'),
                                    generatedCode: document.getElementById('generated-code'),
                                    minViewportDisplay: document.getElementById('min-viewport-display'),
                                    maxViewportDisplay: document.getElementById('max-viewport-display')
                                };
                            }

                            bindBasicEvents() {
                                this.elements.classTab?.addEventListener('click', () => this.switchTab('class'));
                                this.elements.varsTab?.addEventListener('click', () => this.switchTab('vars'));

                                // Force bind tailwind tab with direct query
                                const tailwindTab = document.getElementById('tailwind-tab');
                                if (tailwindTab) {
                                    tailwindTab.addEventListener('click', () => this.switchTab('tailwind'));
                                } else {
                                    console.log('❌ Tailwind tab not found!');
                                }

                                this.elements.tagTab?.addEventListener('click', () => this.switchTab('tag'));

                                this.elements.pxTab?.addEventListener('click', () => this.switchUnitType('px'));
                                this.elements.remTab?.addEventListener('click', () => this.switchUnitType('rem'));

                                document.getElementById('min-root-size')?.addEventListener('input', () => this.triggerCalculation());
                                document.getElementById('max-root-size')?.addEventListener('input', () => this.triggerCalculation());
                                document.getElementById('min-viewport')?.addEventListener('input', () => this.triggerCalculation());
                                document.getElementById('max-viewport')?.addEventListener('input', () => this.triggerCalculation());
                                document.getElementById('min-scale')?.addEventListener('change', () => this.triggerCalculation());
                                document.getElementById('max-scale')?.addEventListener('change', () => this.triggerCalculation());
                            }

                            triggerCalculation() {
                                window.dispatchEvent(new CustomEvent('fontClamp_settingsChanged'));
                                if (window.fontClampAdvanced && window.fontClampAdvanced.calculateSizes) {
                                    window.fontClampAdvanced.calculateSizes();
                                }
                            }

                            bindEnhancedEvents() {
                                document.getElementById('min-viewport')?.addEventListener('input', (e) => {
                                    if (this.elements.minViewportDisplay) {
                                        this.elements.minViewportDisplay.textContent = e.target.value + 'px';
                                    }
                                });

                                document.getElementById('max-viewport')?.addEventListener('input', (e) => {
                                    if (this.elements.maxViewportDisplay) {
                                        this.elements.maxViewportDisplay.textContent = e.target.value + 'px';
                                    }
                                });
                            }

                            switchTab(tabName) {
                                this.activeTab = tabName;

                                document.querySelectorAll('[data-tab]').forEach(tab => {
                                    tab.classList.remove('active');
                                });
                                document.querySelector(`[data-tab="${tabName}"]`)?.classList.add('active');

                                if (tabName === 'class') {
                                    this.elements.tableTitle.textContent = 'Font Size Classes';
                                    this.elements.selectedCodeTitle.textContent = 'Selected Class CSS';
                                    this.elements.generatedCodeTitle.textContent = 'Generated CSS (All Classes)';
                                } else if (tabName === 'vars') {
                                    this.elements.tableTitle.textContent = 'CSS Variables';
                                    this.elements.selectedCodeTitle.textContent = 'Selected Variable CSS';
                                    this.elements.generatedCodeTitle.textContent = 'Generated CSS (All Variables)';
                                } else if (tabName === 'tailwind') {
                                    this.elements.tableTitle.textContent = 'Tailwind Font Sizes';
                                    this.elements.selectedCodeTitle.textContent = 'Selected Size Config';
                                    this.elements.generatedCodeTitle.textContent = 'Tailwind Config (fontSize Object)';
                                } else if (tabName === 'tag') {
                                    this.elements.tableTitle.textContent = 'HTML Tag Styles';
                                    this.elements.selectedCodeTitle.textContent = 'Selected Tag CSS';
                                    this.elements.generatedCodeTitle.textContent = 'Generated CSS (All Tags)';
                                }

                                if (typeof this.updateBaseValueDropdown === 'function') {
                                    this.updateBaseValueDropdown(tabName);
                                }

                                this.triggerHook('tabChanged', {
                                    activeTab: tabName
                                });
                            }

                            updateBaseValueDropdown(tabName) {
                                const baseValueSelect = document.getElementById('base-value');
                                if (!baseValueSelect) {
                                    return;
                                }

                                baseValueSelect.innerHTML = '';

                                let currentSizes, propertyName, defaultValue;

                                if (tabName === 'class') {
                                    currentSizes = this.classSizes.filter(size =>
                                        !size.className || !size.className.startsWith('custom-')
                                    );
                                    propertyName = 'className';
                                    defaultValue = 'medium';
                                } else if (tabName === 'vars') {
                                    currentSizes = this.variableSizes.filter(size =>
                                        !size.variableName || !size.variableName.startsWith('custom-')
                                    );
                                    propertyName = 'variableName';
                                    defaultValue = '--fs-md';
                                } else if (tabName === 'tailwind') {
                                    currentSizes = FontClampUtils.getCurrentSizes('tailwind', window.fontClampAdvanced);
                                    propertyName = 'tailwindName';
                                    defaultValue = 'base';
                                    console.log('🔍 Fixed tailwind sizes:', currentSizes);
                                } else if (tabName === 'tag') {
                                    currentSizes = this.tagSizes.filter(size =>
                                        !size.tagName || !size.tagName.startsWith('custom-')
                                    );
                                    propertyName = 'tagName';
                                    defaultValue = 'p';
                                }

                                if (!currentSizes || currentSizes.length === 0) {
                                    return;
                                }

                                let defaultFound = false;
                                currentSizes.forEach((size, index) => {
                                    const option = document.createElement('option');
                                    option.value = size.id;
                                    option.textContent = size[propertyName];

                                    if (size[propertyName] === defaultValue) {
                                        option.selected = true;
                                        defaultFound = true;
                                    }

                                    baseValueSelect.appendChild(option);
                                });

                                if (!defaultFound && baseValueSelect.options.length > 0) {
                                    baseValueSelect.options[0].selected = true;
                                }
                            }

                            switchUnitType(unitType) {
                                this.unitType = unitType;

                                document.querySelectorAll('[data-unit]').forEach(btn => {
                                    btn.classList.remove('active');
                                });
                                document.querySelector(`[data-unit="${unitType}"]`)?.classList.add('active');

                                this.triggerHook('unitTypeChanged', {
                                    unitType: unitType
                                });
                            }

                            triggerSegmentHooks() {
                                window.dispatchEvent(new CustomEvent('fontClampCoreReady', {
                                    detail: {
                                        coreInterface: this,
                                        data: {
                                            settings: this.settings,
                                            classSizes: this.classSizes,
                                            variableSizes: this.variableSizes,
                                            tagSizes: this.tagSizes
                                        },
                                        elements: this.elements
                                    }
                                }));
                            }

                            triggerHook(hookName, data) {
                                window.dispatchEvent(new CustomEvent(`fontClamp_${hookName}`, {
                                    detail: {
                                        ...data,
                                        coreInterface: this
                                    }
                                }));
                            }

                            getCurrentSizes() {
                                return FontClampUtils.getCurrentSizes(this.activeTab);
                            }

                            updateData(newData) {
                                Object.assign(this, newData);
                                this.triggerHook('dataUpdated', newData);
                            }
                        }

                        /**
                         * Fluid Font Advanced Features Controller
                         */
                        class FontClampAdvanced {
                            // ========================================================================
                            // CORE INITIALIZATION
                            // ========================================================================

                            constructor() {

                                this.version = '3.6.0';
                                this.DEBUG_MODE = true;
                                this.initialized = false;
                                this.dragState = this.initDragState();
                                this.editingId = null;
                                this.lastFontStyle = null;
                                this.dataChanged = false;
                                this.selectedRowId = null;
                                this.autosaveTimer = null; // Add this for autosave functionality

                                // Initialize constants from backend
                                this.constants = this.initializeConstants();

                                // Why complex initialization: WordPress admin loads assets asynchronously
                                // DOM, AJAX data, and other components may load in any order - we need all three
                                this.initState = {
                                    domReady: false, // Why: Can't bind events until DOM elements exist
                                    dataReady: false, // Why: Can't calculate sizes without fluid font data  
                                    segmentBReady: false // Why: Can't function without core interface ready
                                };

                                this.updatePreview = this.debounce(this.updatePreview.bind(this), 150);
                                this.calculateSizes = this.debounce(this.calculateSizes.bind(this), 300);

                                this.initializeWhenReady();
                            }

                            /**
                             * Initialize constants from backend 
                             */
                            initializeConstants() {
                                // Priority 1: Use constants from Segment A
                                if (window.fontClampAjax && window.fontClampAjax.constants) {
                                    return window.fontClampAjax.constants;
                                }

                                // Priority 2: Use defaults
                                if (window.fontClampAjax && window.fontClampAjax.defaults) {
                                    return {
                                        DEFAULT_MIN_ROOT_SIZE: window.fontClampAjax.defaults.minRootSize || 16,
                                        DEFAULT_MAX_ROOT_SIZE: window.fontClampAjax.defaults.maxRootSize || 20,
                                        DEFAULT_MIN_VIEWPORT: window.fontClampAjax.defaults.minViewport || 375,
                                        DEFAULT_MAX_VIEWPORT: window.fontClampAjax.defaults.maxViewport || 1620,
                                        DEFAULT_BODY_LINE_HEIGHT: 1.4,
                                        DEFAULT_HEADING_LINE_HEIGHT: 1.2,
                                        BROWSER_DEFAULT_FONT_SIZE: 16,
                                        CSS_UNIT_CONVERSION_BASE: 16
                                    };
                                }

                                // Ultimate fallback (should never be reached)
                                return {
                                    DEFAULT_MIN_ROOT_SIZE: 16,
                                    DEFAULT_MAX_ROOT_SIZE: 20,
                                    DEFAULT_MIN_VIEWPORT: 375,
                                    DEFAULT_MAX_VIEWPORT: 1620,
                                    DEFAULT_BODY_LINE_HEIGHT: 1.4,
                                    DEFAULT_HEADING_LINE_HEIGHT: 1.2,
                                    BROWSER_DEFAULT_FONT_SIZE: 16,
                                    CSS_UNIT_CONVERSION_BASE: 16
                                };
                            }

                            initializeWhenReady() {

                                if (document.readyState === 'loading') {
                                    document.addEventListener('DOMContentLoaded', () => {
                                        this.initState.domReady = true;
                                        this.checkReadinessAndInit();
                                    });
                                } else {
                                    this.initState.domReady = true;
                                }

                                if (window.fontClampAjax && window.fontClampAjax.data) {
                                    this.initState.dataReady = true;
                                } else {
                                    window.addEventListener('fontClampDataReady', () => {
                                        this.initState.dataReady = true;
                                        this.checkReadinessAndInit();
                                    });
                                }

                                if (window.fontClampCore) {
                                    this.initState.segmentBReady = true;
                                } else {
                                    window.addEventListener('fontClampCoreReady', () => {
                                        this.initState.segmentBReady = true;
                                        this.checkReadinessAndInit();
                                    });
                                }

                                this.checkReadinessAndInit();

                                // Why timeout fallback: WordPress admin can have loading delays/failures
                                // If something doesn't load properly, we still need a working interface
                                setTimeout(() => {
                                    if (!this.initialized) {
                                        // Why force init: Better to have partial functionality than broken interface
                                        this.forceInit();
                                    }
                                }, 2000); // Why 2 seconds: Long enough for normal loading, short enough for user patience
                            }

                            checkReadinessAndInit() {
                                const {
                                    domReady,
                                    dataReady,
                                    segmentBReady
                                } = this.initState;

                                // Why all three required: Missing any piece causes runtime errors
                                // DOM needed for element binding, data for calculations, core for coordination
                                if (domReady && dataReady && segmentBReady && !this.initialized) {
                                    // Why setTimeout: Ensures final DOM render before binding events
                                    setTimeout(() => this.init(), 50);
                                }
                            }

                            forceInit() {
                                if (!this.initialized) {
                                    this.init();
                                }
                            }

                            log(message, ...args) {
                                if (this.DEBUG_MODE) {
                                    console.log(`[FontClamp] ${message}`, ...args);
                                }
                            }

                            initDragState() {
                                return {
                                    isDragging: false,
                                    draggedRow: null,
                                    startY: 0,
                                    currentY: 0,
                                    offset: 0
                                };
                            }

                            init() {
                                try {

                                    this.cacheElements();
                                    this.bindEvents();
                                    this.setupTableActions();
                                    this.setupModal();
                                    this.initializeDisplay();

                                    this.initialized = true;

                                    window.dispatchEvent(new CustomEvent('fontClampAdvancedReady', {
                                        detail: {
                                            advancedFeatures: this,
                                            version: this.version
                                        }
                                    }));

                                } catch (error) {
                                    console.error('❌ Failed to initialize Fluid Font Advanced Features:', error);
                                    this.showError('Failed to initialize advanced features');
                                }
                            }

                            // ========================================================================
                            // ELEMENT & EVENT MANAGEMENT
                            // ========================================================================

                            cacheElements() {
                                this.elements = {
                                    minRootSizeInput: document.getElementById('min-root-size'),
                                    maxRootSizeInput: document.getElementById('max-root-size'),
                                    baseValueSelect: document.getElementById('base-value'),
                                    minViewportInput: document.getElementById('min-viewport'),
                                    maxViewportInput: document.getElementById('max-viewport'),
                                    minScaleSelect: document.getElementById('min-scale'),
                                    maxScaleSelect: document.getElementById('max-scale'),
                                    previewFontUrlInput: document.getElementById('preview-font-url'),
                                    fontFilenameSpan: document.getElementById('font-filename'),
                                    autosaveStatus: document.getElementById('autosave-status'),
                                    autosaveIcon: document.getElementById('autosave-icon'),
                                    autosaveText: document.getElementById('autosave-text'),
                                    autosaveToggle: document.getElementById('autosave-toggle'),
                                    sizesTableWrapper: document.getElementById('sizes-table-wrapper'),
                                    previewMinContainer: document.getElementById('preview-min-container'),
                                    previewMaxContainer: document.getElementById('preview-max-container'),
                                    tableHeader: document.getElementById('table-header'),
                                    tableActionButtons: document.getElementById('table-action-buttons'),
                                    minViewportDisplay: document.getElementById('min-viewport-display'),
                                    maxViewportDisplay: document.getElementById('max-viewport-display')
                                };
                            }

                            bindEvents() {
                                const settingsInputs = [
                                    'minRootSizeInput', 'maxRootSizeInput', 'minViewportInput', 'maxViewportInput'
                                ];

                                settingsInputs.forEach(elementKey => {
                                    const element = this.elements[elementKey];
                                    if (element) {
                                        element.addEventListener('input', () => this.calculateSizes());
                                    }
                                });

                                const settingsSelects = [
                                    'baseValueSelect', 'minScaleSelect', 'maxScaleSelect'
                                ];

                                settingsSelects.forEach(elementKey => {
                                    const element = this.elements[elementKey];
                                    if (element) {
                                        element.addEventListener('change', () => this.calculateSizes());
                                    }
                                });

                                if (this.elements.previewFontUrlInput) {
                                    this.elements.previewFontUrlInput.addEventListener('input',
                                        this.debounce(() => this.updatePreviewFont(), 500)
                                    );
                                }

                                if (this.elements.autosaveToggle) {
                                    this.elements.autosaveToggle.addEventListener('change', () => {
                                        this.handleAutosaveToggle();
                                    });

                                    // Check initial state and start autosave if already enabled
                                    if (this.elements.autosaveToggle.checked) {
                                        this.startAutosaveTimer();
                                    }
                                }

                                // Save button click handler
                                const saveBtn = document.getElementById('save-btn');
                                if (saveBtn) {
                                    saveBtn.addEventListener('click', () => {

                                        // Update status to show saving
                                        const autosaveStatus = document.getElementById('autosave-status');
                                        const autosaveIcon = document.getElementById('autosave-icon');
                                        const autosaveText = document.getElementById('autosave-text');

                                        if (autosaveStatus && autosaveIcon && autosaveText) {
                                            autosaveStatus.className = 'autosave-status saving';
                                            autosaveIcon.textContent = '⏳';
                                            autosaveText.textContent = 'Saving...';
                                        }

                                        // Disable save button during save
                                        saveBtn.disabled = true;
                                        saveBtn.textContent = 'Saving...';

                                        // Collect current settings
                                        const settings = {
                                            minRootSize: this.elements.minRootSizeInput?.value,
                                            maxRootSize: this.elements.maxRootSizeInput?.value,
                                            minViewport: this.elements.minViewportInput?.value,
                                            maxViewport: this.elements.maxViewportInput?.value,
                                            minScale: this.elements.minScaleSelect?.value,
                                            maxScale: this.elements.maxScaleSelect?.value,
                                            unitType: window.fontClampCore?.unitType,
                                            activeTab: window.fontClampCore?.activeTab,
                                            previewFontUrl: this.elements.previewFontUrlInput?.value,
                                            autosaveEnabled: this.elements.autosaveToggle?.checked
                                        };

                                        // Collect current sizes for all tabs
                                        const allSizes = {
                                            classSizes: window.fontClampAjax?.data?.classSizes || [],
                                            variableSizes: window.fontClampAjax?.data?.variableSizes || [],
                                            tagSizes: window.fontClampAjax?.data?.tagSizes || []
                                        };

                                        const data = {
                                            action: 'save_font_clamp_settings',
                                            nonce: window.fontClampAjax.nonce,
                                            settings: JSON.stringify(settings),
                                            sizes: JSON.stringify(allSizes)
                                        };
                                        fetch(window.fontClampAjax.ajaxurl, {
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
                                                saveBtn.disabled = false;
                                                saveBtn.textContent = 'Save';
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
                                                saveBtn.disabled = false;
                                                saveBtn.textContent = 'Save';

                                                alert('Error saving data');
                                            });
                                    });
                                }

                                window.addEventListener('fontClamp_tabChanged', (e) => {
                                    this.handleTabChange(e.detail);
                                });

                                window.addEventListener('fontClamp_unitTypeChanged', () => {
                                    this.calculateSizes();
                                });
                            }

                            handleAutosaveToggle() {
                                const isEnabled = this.elements.autosaveToggle?.checked;

                                if (isEnabled) {
                                    this.startAutosaveTimer();
                                } else {
                                    this.stopAutosaveTimer();
                                }

                                this.updateSettings();
                            }

                            startAutosaveTimer() {
                                this.stopAutosaveTimer(); // Clear any existing timer

                                this.autosaveTimer = setInterval(() => {
                                    this.performSave(true); // true = isAutosave
                                }, 30000); // 30 seconds
                            }

                            stopAutosaveTimer() {
                                if (this.autosaveTimer) {
                                    clearInterval(this.autosaveTimer);
                                    this.autosaveTimer = null;
                                }
                            }

                            performSave(isAutosave = false) {
                                const saveBtn = document.getElementById('save-btn');
                                if (saveBtn) {
                                    saveBtn.click();
                                }
                            }

                            setupTableActions() {
                                const tableButtons = this.elements.tableActionButtons;
                                if (!tableButtons) return;

                                tableButtons.innerHTML = `
        <button id="add-size" class="fcc-btn">Add Size</button>
        <button id="reset-defaults" class="fcc-btn">Reset</button>
        <button id="clear-sizes" class="fcc-btn fcc-btn-danger">clear all</button>
    `;

                                // Event delegation for header buttons
                                tableButtons.addEventListener('click', (e) => {
                                    const button = e.target.closest('button');
                                    if (!button) return;

                                    e.preventDefault();

                                    switch (button.id) {
                                        case 'add-size':
                                            this.addNewSize();
                                            break;
                                        case 'reset-defaults':
                                            this.resetDefaults();
                                            break;
                                        case 'clear-sizes':
                                            this.clearSizes();
                                            break;
                                    }
                                });

                                // Event delegation for empty state buttons in table wrapper
                                const tableWrapper = this.elements.sizesTableWrapper;
                                if (tableWrapper) {
                                    tableWrapper.addEventListener('click', (e) => {
                                        const button = e.target.closest('button');
                                        if (!button) return;

                                        e.preventDefault();

                                        switch (button.id) {
                                            case 'add-size':
                                                this.addNewSize();
                                                break;
                                            case 'reset-defaults':
                                                this.resetDefaults();
                                                break;
                                        }
                                    });
                                }
                            }

                            /**
                             * Initialize display with proper constants instead of magic numbers
                             */
                            initializeDisplay() {
                                this.populateSettings();
                                this.updateBaseValueOptions();

                                // Use constants instead of magic numbers
                                const minViewportSize = this.elements.minViewportInput?.value || this.constants.DEFAULT_MIN_VIEWPORT;
                                const maxViewportSize = this.elements.maxViewportInput?.value || this.constants.DEFAULT_MAX_VIEWPORT;

                                if (this.elements.minViewportDisplay) {
                                    this.elements.minViewportDisplay.textContent = minViewportSize + 'px';
                                }
                                if (this.elements.maxViewportDisplay) {
                                    this.elements.maxViewportDisplay.textContent = maxViewportSize + 'px';
                                }

                                this.calculateSizes();
                                this.renderSizes();
                                this.updatePreviewFont();
                                this.createCopyButtons();
                                this.setupKeyboardShortcuts();
                                this.updatePreview();

                                this.log('Display initialization complete with constants');
                            }

                            /**
                             * Populate settings using constants instead of magic numbers
                             */
                            populateSettings() {

                                const data = window.fontClampAjax?.data;

                                if (!data) {
                                    console.error('❌ No fluid font data available!');
                                    return;
                                }

                                // Use constants instead of magic numbers for validation
                                const minRootSize = data.settings?.minRootSize || this.constants.DEFAULT_MIN_ROOT_SIZE;
                                const maxRootSize = data.settings?.maxRootSize || this.constants.DEFAULT_MAX_ROOT_SIZE;
                                const minViewport = data.settings?.minViewport || this.constants.DEFAULT_MIN_VIEWPORT;
                                const maxViewport = data.settings?.maxViewport || this.constants.DEFAULT_MAX_VIEWPORT;

                                if (this.elements.minRootSizeInput) {
                                    this.elements.minRootSizeInput.value = minRootSize;
                                }
                                if (this.elements.maxRootSizeInput) {
                                    this.elements.maxRootSizeInput.value = maxRootSize;
                                }
                                if (this.elements.minViewportInput) {
                                    this.elements.minViewportInput.value = minViewport;
                                }
                                if (this.elements.maxViewportInput) {
                                    this.elements.maxViewportInput.value = maxViewport;
                                }

                                if (this.elements.autosaveToggle) {
                                    this.elements.autosaveToggle.checked = data.settings?.autosaveEnabled !== false;
                                }
                            }

                            handleTabChange(detail) {
                                this.log('Tab changed to:', detail.activeTab);

                                this.updateTableHeaders();
                                this.updateBaseValueOptions();

                                setTimeout(() => {
                                    this.calculateSizes();
                                    this.renderSizes();
                                    this.updatePreview();
                                }, 50);
                            }

                            updateTableHeaders() {
                                const headerRow = this.elements.tableHeader;
                                if (!headerRow) return;

                                const activeTab = window.fontClampCore?.activeTab || 'class';
                                const nameHeader = headerRow.children[1];

                                if (nameHeader) {
                                    switch (activeTab) {
                                        case 'class':
                                            nameHeader.innerHTML = 'Class';
                                            break;
                                        case 'vars':
                                            nameHeader.innerHTML = 'Variable';
                                            break;
                                        case 'tag':
                                            nameHeader.innerHTML = 'Tag';
                                            break;
                                    }
                                }
                            }

                            updateBaseValueOptions() {
                                const select = this.elements.baseValueSelect;
                                if (!select) return;

                                const activeTab = window.fontClampCore?.activeTab || 'class';
                                const sizes = this.getCurrentSizes();

                                // Store current selection to preserve it
                                const currentSelection = select.value;

                                select.innerHTML = '';
                                select.disabled = false; // Re-enable when we have data

                                let selectionFound = false;
                                sizes.forEach(size => {
                                    const option = document.createElement('option');
                                    switch (activeTab) {
                                        case 'class':
                                            option.value = size.id;
                                            option.textContent = size.className;
                                            // Preserve current selection or use default
                                            if ((currentSelection && size.id == currentSelection) || (!currentSelection && size.className === 'medium')) {
                                                option.selected = true;
                                                selectionFound = true;
                                            }
                                            break;
                                        case 'vars':
                                            option.value = size.id;
                                            option.textContent = size.variableName;
                                            if ((currentSelection && size.id == currentSelection) || (!currentSelection && size.variableName === '--fs-md')) {
                                                option.selected = true;
                                                selectionFound = true;
                                            }
                                            break;
                                        case 'tailwind':
                                            option.value = size.id;
                                            option.textContent = size.tailwindName;
                                            if ((currentSelection && size.id == currentSelection) || (!currentSelection && size.tailwindName === 'base')) {
                                                option.selected = true;
                                                selectionFound = true;
                                            }
                                            break;
                                        case 'tag':
                                            option.value = size.id;
                                            option.textContent = size.tagName;
                                            if ((currentSelection && size.id == currentSelection) || (!currentSelection && size.tagName === 'p')) {
                                                option.selected = true;
                                                selectionFound = true;
                                            }
                                            break;
                                    }
                                    select.appendChild(option);
                                });

                                // If current selection wasn't found, select first option
                                if (!selectionFound && select.options.length > 0) {
                                    select.options[0].selected = true;
                                }
                            }

                            // ========================================================================
                            // DATA MANAGEMENT & CALCULATION METHODS
                            // ========================================================================

                            calculateSizes() {
                                const baseValue = this.elements.baseValueSelect?.value;
                                if (!baseValue) {
                                    this.log('❌ No base value selected');
                                    return;
                                }

                                const sizes = this.getCurrentSizes();
                                const baseSize = sizes.find(size => {
                                    return size.id == baseValue; // Compare ID to ID (use == to handle string/number conversion)
                                });
                                if (!baseSize) {
                                    this.log('❌ Base size not found for:', baseValue);
                                    return;
                                }
                                const baseIndex = sizes.indexOf(baseSize);
                                const minScale = parseFloat(this.elements.minScaleSelect?.value);
                                const maxScale = parseFloat(this.elements.maxScaleSelect?.value);
                                const minRootSize = parseFloat(this.elements.minRootSizeInput?.value);
                                const maxRootSize = parseFloat(this.elements.maxRootSizeInput?.value);
                                const unitType = window.fontClampCore?.unitType || 'rem';

                                if (isNaN(minScale) || isNaN(maxScale) || isNaN(minRootSize) || isNaN(maxRootSize)) {
                                    this.log('❌ Invalid form values');
                                    return;
                                }

                                let minBaseSize, maxBaseSize;
                                if (unitType === 'rem') {
                                    // Why divide by defalut font size: Convert px to rem units 
                                    // (1rem = 16px by browser default, user may have changed this)
                                    // Mathematical relationship: rem = pixels ÷ browser_default_font_size
                                    minBaseSize = minRootSize / this.constants.BROWSER_DEFAULT_FONT_SIZE;
                                    maxBaseSize = maxRootSize / this.constants.BROWSER_DEFAULT_FONT_SIZE;
                                } else {
                                    // Why direct assignment: px units don't need conversion (already absolute)
                                    minBaseSize = minRootSize;
                                    maxBaseSize = maxRootSize;
                                }

                                if (sizes.length === 1) {
                                    sizes[0].min = parseFloat(minBaseSize.toFixed(3));
                                    sizes[0].max = parseFloat(maxBaseSize.toFixed(3));
                                } else {
                                    sizes.forEach((size, index) => {
                                        // Why steps calculation: Distance from base determines scaling power
                                        // Negative steps = larger sizes (headings), positive = smaller (captions)
                                        const steps = baseIndex - index;

                                        // Why Math.pow: Typography scales exponentially, not linearly
                                        // Each step up/down multiplies by the scale ratio (musical harmony theory)
                                        const minMultiplier = Math.pow(minScale, steps);
                                        const maxMultiplier = Math.pow(maxScale, steps);

                                        // Why separate min/max: Different scales for mobile vs desktop creates better hierarchy
                                        const calculatedMin = minBaseSize * minMultiplier;
                                        const calculatedMax = maxBaseSize * maxMultiplier;

                                        size.min = parseFloat(calculatedMin.toFixed(3));
                                        size.max = parseFloat(calculatedMax.toFixed(3));
                                    });
                                }

                                this.dataChanged = true;
                                this.renderSizes();
                                this.updatePreview();
                                this.updateCSS();
                            }

                            // ========================================================================
                            // UI RENDERING & PREVIEW METHODS
                            // ========================================================================

                            updatePreview() {

                                try {
                                    const sizes = this.getCurrentSizes();
                                    const previewMin = this.elements.previewMinContainer;
                                    const previewMax = this.elements.previewMaxContainer;

                                    if (!previewMin || !previewMax) {
                                        return;
                                    }

                                    previewMin.innerHTML = '';
                                    previewMax.innerHTML = '';

                                    if (sizes.length === 0) {
                                        previewMin.innerHTML = '<div style="text-align: center; color: #6b7280; font-style: italic; padding: 60px 20px;">No sizes to preview</div>';
                                        previewMax.innerHTML = '<div style="text-align: center; color: #6b7280; font-style: italic; padding: 60px 20px;">No sizes to preview</div>';
                                        return;
                                    }

                                    const minRootSize = parseFloat(this.elements.minRootSizeInput?.value);
                                    const maxRootSize = parseFloat(this.elements.maxRootSizeInput?.value);
                                    const unitType = window.fontClampCore?.unitType || 'rem';

                                    if (isNaN(minRootSize) || isNaN(maxRootSize)) {
                                        console.error('❌ Invalid root size values in updatePreview');
                                        return;
                                    }

                                    const activeTab = window.fontClampCore?.activeTab || 'class';

                                    sizes.forEach((size, index) => {
                                        const displayName = this.getSizeDisplayName(size, activeTab);
                                        const minSize = size.min || this.constants.DEFAULT_MIN_ROOT_SIZE;
                                        const maxSize = size.max || this.constants.DEFAULT_MAX_ROOT_SIZE;

                                        let minSizePx, maxSizePx;
                                        if (unitType === 'rem') {
                                            // Why multiply: Convert rem back to pixels for preview display
                                            // Formula: pixels = rem_value × current_root_font_size
                                            minSizePx = minSize * minRootSize;
                                            maxSizePx = maxSize * maxRootSize;
                                        } else {
                                            // Why direct use: px values are already in display units
                                            minSizePx = minSize;
                                            maxSizePx = maxSize;
                                        }

                                        const lineHeight = size.lineHeight || this.constants.DEFAULT_LINE_HEIGHT;

                                        // Why multiply by line height: Total text height includes line spacing
                                        // Typography formula: rendered_height = font_size × line_height
                                        const minTextHeight = minSizePx * lineHeight;
                                        const maxTextHeight = maxSizePx * lineHeight;

                                        // Why Math.max + 16: Use tallest text height + padding for consistent alignment
                                        // Prevents smaller text from floating, creates visual rhythm
                                        const unifiedRowHeight = Math.max(minTextHeight, maxTextHeight) + 16;

                                        // Why abs(): Get positive difference regardless of which size is larger
                                        const paddingDiff = Math.abs(maxSizePx - minSizePx);

                                        // Why conditional padding: Align smaller text to same baseline as larger text
                                        // Bottom-alignment math: smaller_size + padding = larger_size
                                        const minPadding = minSizePx < maxSizePx ? paddingDiff : 0;
                                        const maxPadding = maxSizePx < minSizePx ? paddingDiff : 0;

                                        const minRow = this.createPreviewRow(displayName, minSizePx, 'px', lineHeight, unifiedRowHeight, size.id, index, minPadding);
                                        const maxRow = this.createPreviewRow(displayName, maxSizePx, 'px', lineHeight, unifiedRowHeight, size.id, index, maxPadding);
                                        this.addSynchronizedHover(minRow, maxRow);

                                        previewMin.appendChild(minRow);
                                        previewMax.appendChild(maxRow);
                                    });

                                } catch (error) {
                                    console.error('❌ Preview update error:', error);
                                }
                            }
                            createPreviewRow(displayName, fontSize, unitType, lineHeight, rowHeight, sizeId, index, topPadding = 0) {

                                const row = document.createElement('div');
                                row.className = 'preview-row';
                                row.dataset.sizeId = sizeId;
                                row.dataset.index = index;

                                row.style.cssText = `
            height: ${rowHeight}px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            margin-bottom: 4px;
            overflow: hidden;
            position: relative;
            box-sizing: border-box;
            padding: 8px 8px 12px 8px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            `;

                                const text = document.createElement('div');
                                text.textContent = displayName;

                                const fontSizeValue = `${fontSize}px`;

                                text.style.cssText = `
            font-family: var(--preview-font, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif);
            font-size: ${fontSizeValue};
            line-height: ${lineHeight};
            font-weight: 500;
            color: #1f2937;
            text-align: center;
            white-space: nowrap;
            overflow: visible;
            max-width: 100%;
            box-sizing: border-box;
            margin: 0;
            width: 100%;
            padding-top:  ${4 + topPadding}px;
            `;
                                row.addEventListener('click', () => {
                                    this.selectedRowId = sizeId;
                                    this.highlightDataTableRow(sizeId, index);
                                    this.highlightPreviewRows(sizeId);
                                    this.updateCSS();
                                });

                                row.appendChild(text);
                                return row;
                            }

                            highlightDataTableRow(sizeId, index) {
                                document.querySelectorAll('.size-row.selected').forEach(row => {
                                    row.classList.remove('selected');
                                });

                                const dataTableRow = document.querySelector(`.size-row[data-id="${sizeId}"]`);
                                if (dataTableRow) {
                                    dataTableRow.classList.add('selected');
                                    dataTableRow.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'nearest'
                                    });
                                }
                            }

                            highlightPreviewRows(sizeId) {
                                document.querySelectorAll('.preview-row.selected').forEach(row => {
                                    row.classList.remove('selected');
                                });

                                document.querySelectorAll(`.preview-row[data-size-id="${sizeId}"]`).forEach(row => {
                                    row.classList.add('selected');
                                });
                            }

                            addSynchronizedHover(element1, element2) {
                                const hoverIn = () => {
                                    element1.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
                                    element2.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
                                };

                                const hoverOut = () => {
                                    element1.style.backgroundColor = 'transparent';
                                    element2.style.backgroundColor = 'transparent';
                                };

                                element1.addEventListener('mouseenter', hoverIn);
                                element1.addEventListener('mouseleave', hoverOut);
                                element2.addEventListener('mouseenter', hoverIn);
                                element2.addEventListener('mouseleave', hoverOut);
                            }

                            renderSizes() {
                                const wrapper = this.elements.sizesTableWrapper;
                                if (!wrapper) return;

                                const sizes = this.getCurrentSizes();
                                const activeTab = window.fontClampCore?.activeTab || 'class';
                                const unitType = window.fontClampCore?.unitType || 'rem';

                                wrapper.innerHTML = `
                        <table class="font-table">
                            <thead>
                                <tr id="table-header">
                                    <th style="width: 24px;">⋮</th>
                                    <th style="width: 90px;">Name</th>
                                    <th style="width: 70px;">Min Size</th>
                                    <th style="width: 70px;">Max Size</th>
                                    <th style="width: 40px;">Line Height</th>
                                    <th style="width: 30px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="sizes-table"></tbody>
                        </table>
                    `;

                                const tbody = document.getElementById('sizes-table');

                                sizes.forEach((size, index) => {
                                    const row = document.createElement('tr');
                                    row.className = 'size-row';
                                    row.draggable = true;
                                    row.dataset.id = size.id;
                                    row.dataset.index = index;

                                    const displayName = this.getSizeDisplayName(size, activeTab);

                                    row.innerHTML = `
                            <td class="drag-handle" style="text-align: center; color: #9ca3af; cursor: grab; user-select: none;" 
            data-tooltip="Drag to reorder" data-tooltip-position="right">⋮⋮</td>
                            <td style="font-weight: 500; overflow: hidden; text-overflow: ellipsis;" title="${displayName}">${displayName}</td>
                            <td style="text-align: center; font-family: monospace; font-size: 10px;">${this.formatSize(size.min, unitType)}</td>
                            <td style="text-align: center; font-family: monospace; font-size: 10px;">${this.formatSize(size.max, unitType)}</td>
                            <td style="text-align: center; font-size: 11px;">${size.lineHeight}</td>
                            <td style="text-align: center; padding: 2px;">
                                <button class="edit-btn" style="color: #3b82f6; background: none; border: none; cursor: pointer; margin-right: 6px; font-size: 13px; padding: 2px;" title="Edit">✎</button>
                                <button class="delete-btn" style="color: #ef4444; background: none; border: none; cursor: pointer; font-size: 12px; padding: 2px;" title="Delete">🗑️</button>
                            </td>
                        `;

                                    this.bindRowEvents(row);
                                    tbody.appendChild(row);
                                });

                                this.updateTableHeaders();
                                this.updateCSS();
                            }

                            bindRowEvents(row) {
                                const editBtn = row.querySelector('.edit-btn');
                                if (editBtn) {
                                    editBtn.addEventListener('click', (e) => {
                                        e.stopPropagation();
                                        this.editSize(parseInt(row.dataset.id));
                                    });
                                }

                                const deleteBtn = row.querySelector('.delete-btn');
                                if (deleteBtn) {
                                    deleteBtn.addEventListener('click', (e) => {
                                        e.stopPropagation();
                                        this.deleteSize(parseInt(row.dataset.id));
                                    });
                                }
                                row.addEventListener('click', (e) => {
                                    if (e.target.closest('button')) return;

                                    const sizeId = parseInt(row.dataset.id);
                                    const index = row.dataset.index;

                                    document.querySelectorAll('.size-row.selected').forEach(r => {
                                        r.classList.remove('selected');
                                    });

                                    row.classList.add('selected');
                                    this.selectedRowId = sizeId;
                                    this.highlightPreviewRows(sizeId);
                                    this.updateCSS();
                                });

                                // Only allow dragging from the drag handle
                                const dragHandle = row.querySelector('.drag-handle');
                                if (dragHandle) {
                                    // Make only the handle initiate drag, but set data on the row
                                    dragHandle.addEventListener('mousedown', (e) => {
                                        row.draggable = true;
                                    });

                                    row.addEventListener('dragstart', (e) => {
                                        this.dragState.draggedRow = row;
                                        e.dataTransfer.effectAllowed = 'move';
                                        e.dataTransfer.setData('text/plain', row.dataset.id);
                                        row.style.opacity = '0.5';
                                        row.classList.add('dragging');
                                    });

                                    row.addEventListener('dragenter', (e) => {
                                        if (this.dragState.draggedRow && this.dragState.draggedRow !== row) {

                                            // Remove existing insertion indicators
                                            document.querySelectorAll('.size-row').forEach(r => {
                                                r.style.borderTop = '';
                                                r.style.boxShadow = '';
                                            });

                                            // Add border insertion line
                                            row.style.borderTop = '4px solid #3b82f6';
                                            row.style.boxShadow = '0 -2px 8px rgba(59, 130, 246, 0.5)';
                                        }
                                    });

                                    row.addEventListener('dragend', (e) => {
                                        row.style.opacity = '1';
                                        row.classList.remove('dragging');
                                        row.draggable = false;
                                        this.dragState.draggedRow = null;

                                        // Clean up visual feedback
                                        document.querySelectorAll('.size-row').forEach(r => {
                                            r.classList.remove('drag-over');
                                        });

                                        // Remove insertion line
                                        const insertionLine = document.getElementById('drag-insertion-line');
                                        if (insertionLine) insertionLine.remove();
                                    });

                                    row.addEventListener('dragover', (e) => {
                                        e.preventDefault(); // Always prevent default to allow drop
                                        e.dataTransfer.dropEffect = 'move';
                                    });

                                    row.addEventListener('drop', (e) => {
                                        e.preventDefault();

                                        if (this.dragState.draggedRow && this.dragState.draggedRow !== row) {

                                            // Remove insertion line
                                            const insertionLine = document.getElementById('drag-insertion-line');
                                            if (insertionLine) insertionLine.remove();

                                            // Get the current sizes array
                                            const sizes = this.getCurrentSizes();
                                            const draggedId = parseInt(this.dragState.draggedRow.dataset.id);
                                            const targetId = parseInt(row.dataset.id);

                                            // Find the items to reorder
                                            const draggedIndex = sizes.findIndex(s => s.id === draggedId);
                                            const targetIndex = sizes.findIndex(s => s.id === targetId);


                                            if (draggedIndex !== -1 && targetIndex !== -1) {
                                                // Remove the dragged item and insert it at the target position
                                                const [draggedItem] = sizes.splice(draggedIndex, 1);
                                                sizes.splice(targetIndex, 0, draggedItem);

                                                // Re-render the table with new order
                                                this.renderSizes();
                                                this.updatePreview();
                                                this.markDataChanged();
                                            } else {
                                                console.log('Could not find indices for reordering');
                                            }
                                        } else {
                                            console.log('Drop conditions not met');
                                        }
                                    });

                                    row.addEventListener('dragleave', (e) => {
                                        // Add small delay to prevent immediate removal during cursor movement
                                        setTimeout(() => {
                                            // Only remove if we're not actively dragging over another row
                                            if (this.dragState.draggedRow && !document.querySelector('.size-row:hover')) {
                                                document.querySelectorAll('.size-row').forEach(r => {
                                                    r.style.borderTop = '';
                                                    r.style.boxShadow = '';
                                                });
                                            }
                                        }, 100);
                                    });

                                }

                            }

                            updateCSS() {
                                try {
                                    const selectedElement = document.getElementById('class-code');
                                    const generatedElement = document.getElementById('generated-code');

                                    if (selectedElement && generatedElement) {
                                        this.generateAndUpdateCSS(selectedElement, generatedElement);
                                    }

                                    const currentData = {
                                        classSizes: window.fontClampAjax?.data?.classSizes || [],
                                        variableSizes: window.fontClampAjax?.data?.variableSizes || [],
                                        tagSizes: window.fontClampAjax?.data?.tagSizes || [],
                                        coreInterface: window.fontClampCore
                                    };

                                    window.dispatchEvent(new CustomEvent('fontClamp_dataUpdated', {
                                        detail: currentData
                                    }));

                                } catch (error) {
                                    console.error('❌ CSS update error:', error);
                                }
                            }

                            // ========================================================================
                            // COPY & CSS GENERATION
                            // ========================================================================

                            generateAndUpdateCSS(selectedElement, generatedElement) {
                                try {
                                    const sizes = this.getCurrentSizes();
                                    const activeTab = window.fontClampCore?.activeTab || 'class';
                                    const unitType = window.fontClampCore?.unitType || 'rem';

                                    const generateClampCSS = (minSize, maxSize) => {
                                        const minViewport = parseFloat(this.elements?.minViewportInput?.value ||
                                            document.getElementById('min-viewport')?.value || this.constants.DEFAULT_MIN_VIEWPORT);
                                        const maxViewport = parseFloat(this.elements?.maxViewportInput?.value ||
                                            document.getElementById('max-viewport')?.value || this.constants.DEFAULT_MAX_VIEWPORT);

                                        const minRootSize = parseFloat(this.elements.minRootSizeInput?.value);
                                        const maxRootSize = parseFloat(this.elements.maxRootSizeInput?.value);

                                        if (isNaN(minRootSize) || isNaN(maxRootSize)) {
                                            this.log('❌ Invalid root size value from Settings inputs');
                                            return 'clamp(1rem, 1rem, 1rem)';
                                        }

                                        let minValue, maxValue;
                                        if (unitType === 'rem') {
                                            minValue = `${minSize}rem`;
                                            maxValue = `${maxSize}rem`;
                                        } else {
                                            minValue = `${minSize}px`;
                                            maxValue = `${maxSize}px`;
                                        }

                                        // Why slope calculation: Creates linear interpolation between viewport sizes
                                        // Formula: rise over run - how much font size changes per pixel of viewport
                                        const slope = (maxSize - minSize) / (maxViewport - minViewport);

                                        // Why intersection: Y-intercept where the scaling line crosses viewport=0
                                        // Mathematical formula: y = mx + b, solving for b when x=minViewport, y=minSize
                                        const intersection = -minViewport * slope + minSize;

                                        // Why multiply by 100: Convert decimal slope to vw units (1vw = 1% of viewport width)
                                        const slopeInViewportWidth = (slope * 100).toFixed(4);

                                        const intersectionValue = unitType === 'rem' ?
                                            `${intersection.toFixed(4)}rem` : `${intersection.toFixed(4)}px`;

                                        // Why clamp() structure: min(floor), preferred(linear scaling), max(ceiling)
                                        // Creates smooth font scaling between viewports with safe boundaries
                                        return `clamp(${minValue}, ${intersectionValue} + ${slopeInViewportWidth}vw, ${maxValue})`;
                                    };

                                    const selectedId = this.getSelectedSizeId();
                                    const selectedSize = sizes.find(s => s.id === selectedId);
                                    if (selectedSize && selectedSize.min && selectedSize.max) {
                                        const clampValue = generateClampCSS(selectedSize.min, selectedSize.max);
                                        const displayName = this.getSizeDisplayName(selectedSize, activeTab);

                                        let selectedCSS = '';
                                        if (activeTab === 'class') {
                                            selectedCSS = `.${displayName} {\n  font-size: ${clampValue};\n  line-height: ${selectedSize.lineHeight};\n}`;
                                        } else if (activeTab === 'vars') {
                                            selectedCSS = `:root {\n  ${displayName}: ${clampValue};\n}`;
                                        } else if (activeTab === 'tailwind') {
                                            selectedCSS = `'${displayName}': '${clampValue}'`;
                                        } else {
                                            selectedCSS = `${displayName} {\n  font-size: ${clampValue};\n  line-height: ${selectedSize.lineHeight};\n}`;
                                        }

                                        selectedElement.textContent = selectedCSS;
                                    } else {
                                        selectedElement.textContent = '/* No size selected or calculated */';
                                    }

                                    let allCSS = '';

                                    if (activeTab === 'class') {
                                        sizes.forEach(size => {
                                            if (size.min && size.max) {
                                                const clampValue = generateClampCSS(size.min, size.max);
                                                allCSS += `.${size.className} {\n  font-size: ${clampValue};\n  line-height: ${size.lineHeight};\n}\n\n`;
                                            }
                                        });
                                    } else if (activeTab === 'vars') {
                                        allCSS = ':root {\n';
                                        sizes.forEach(size => {
                                            if (size.min && size.max) {
                                                const clampValue = generateClampCSS(size.min, size.max);
                                                allCSS += `  ${size.variableName}: ${clampValue};\n`;
                                            }
                                        });
                                        allCSS += '}';
                                    } else if (activeTab === 'tailwind') {
                                        allCSS = 'module.exports = {\n  theme: {\n    extend: {\n      fontSize: {\n';
                                        sizes.forEach((size, index) => {
                                            if (size.min && size.max) {
                                                const clampValue = generateClampCSS(size.min, size.max);
                                                const comma = index < sizes.length - 1 ? ',' : '';
                                                allCSS += `        '${size.tailwindName}': '${clampValue}'${comma}\n`;
                                            }
                                        });
                                        allCSS += '      }\n    }\n  }\n}';
                                    } else {
                                        sizes.forEach(size => {
                                            if (size.min && size.max) {
                                                const clampValue = generateClampCSS(size.min, size.max);
                                                allCSS += `${size.tagName} {\n  font-size: ${clampValue};\n  line-height: ${size.lineHeight};\n}\n\n`;
                                            }
                                        });
                                    }

                                    generatedElement.textContent = allCSS || '/* No sizes calculated */';

                                } catch (error) {
                                    console.error('❌ CSS generation error:', error);
                                }
                            }

                            createCopyButtons() {
                                // Create selected CSS copy button
                                const selectedCopyContainer = document.getElementById('selected-copy-button');
                                if (selectedCopyContainer) {
                                    const activeTab = window.fontClampCore?.activeTab || 'class';
                                    const tooltipText = this.getSelectedCSSTooltip(activeTab);

                                    selectedCopyContainer.innerHTML = `
            <button id="copy-selected-btn" class="fcc-copy-btn" 
                    data-tooltip="${tooltipText}" 
                    aria-label="Copy selected CSS to clipboard"
                    title="Copy CSS">
                <span class="copy-icon">📋</span> copy
            </button>
        `;
                                }

                                // Create generated CSS copy button  
                                const generatedCopyContainer = document.getElementById('generated-copy-buttons');
                                if (generatedCopyContainer) {
                                    const activeTab = window.fontClampCore?.activeTab || 'class';
                                    const tooltipText = this.getGeneratedCSSTooltip(activeTab);

                                    generatedCopyContainer.innerHTML = `
            <button id="copy-all-btn" class="fcc-copy-btn" 
                    data-tooltip="${tooltipText}" 
                    aria-label="Copy all generated CSS to clipboard"
                    title="Copy All CSS">
                <span class="copy-icon">📋</span> copy all
            </button>
        `;
                                }

                                // Setup click handlers after creating buttons
                                setTimeout(() => {
                                    const copySelectedBtn = document.getElementById('copy-selected-btn');
                                    const copyAllBtn = document.getElementById('copy-all-btn');

                                    if (copySelectedBtn) {
                                        copySelectedBtn.addEventListener('click', () => this.copySelectedCSS());
                                    }

                                    if (copyAllBtn) {
                                        copyAllBtn.addEventListener('click', () => this.copyGeneratedCSS());
                                    }
                                }, 100);
                            }

                            /**
                             * Copy selected CSS to clipboard
                             */
                            copySelectedCSS() {
                                const cssElement = document.getElementById('class-code');
                                if (!cssElement) {
                                    console.log('❌ No CSS element found');
                                    return;
                                }

                                const cssText = cssElement.textContent || cssElement.innerText;

                                if (!cssText || cssText.includes('Loading CSS') || cssText.includes('No CSS')) {
                                    console.log('⚠️ No CSS available to copy');
                                    return;
                                }
                                const button = document.getElementById('copy-selected-btn');
                                this.copyToClipboard(cssText, button);
                            }

                            /**
                             * Copy generated CSS to clipboard  
                             */
                            copyGeneratedCSS() {
                                const cssElement = document.getElementById('generated-code');
                                if (!cssElement) {
                                    console.log('❌ No CSS element found');
                                    return;
                                }

                                const cssText = cssElement.textContent || cssElement.innerText;

                                if (!cssText || cssText.includes('Loading CSS') || cssText.includes('No CSS')) {
                                    console.log('⚠️ No CSS available to copy');
                                    return;
                                }
                                const button = document.getElementById('copy-all-btn');
                                this.copyToClipboard(cssText, button);
                            }

                            /**
                             * Copy text to clipboard with visual feedback
                             */
                            copyToClipboard(text, button) {
                                if (navigator.clipboard && navigator.clipboard.writeText) {
                                    navigator.clipboard.writeText(text).then(() => {
                                        this.showButtonSuccess(button);
                                    }).catch(err => {
                                        console.error('Copy failed:', err);
                                        this.fallbackCopy(text);
                                        this.showButtonSuccess(button);
                                    });
                                } else {
                                    this.fallbackCopy(text);
                                    this.showButtonSuccess(button);
                                }
                            }

                            /**
                             * Fallback copy method for older browsers
                             */
                            fallbackCopy(text) {
                                const textarea = document.createElement('textarea');
                                textarea.value = text;
                                textarea.style.position = 'fixed';
                                textarea.style.opacity = '0';
                                textarea.style.top = '-9999px';
                                textarea.style.left = '-9999px';
                                document.body.appendChild(textarea);
                                textarea.select();
                                textarea.setSelectionRange(0, 99999);

                                try {
                                    document.execCommand('copy');
                                } catch (err) {
                                    console.error('Fallback copy failed:', err);
                                }

                                document.body.removeChild(textarea);
                            }



                            /**
                             * Get tooltip text for selected CSS based on active tab
                             */
                            getSelectedCSSTooltip(activeTab) {
                                switch (activeTab) {
                                    case 'class':
                                        return 'Copy the CSS for your selected class. Paste this into your stylesheet.';
                                    case 'vars':
                                        return 'Copy the CSS custom property for your selected variable.';
                                    case 'tag':
                                        return 'Copy the CSS for your selected HTML tag.';
                                    default:
                                        return 'Copy the selected CSS to your clipboard.';
                                }
                            }

                            /**
                             * Get tooltip text for generated CSS based on active tab
                             */
                            getGeneratedCSSTooltip(activeTab) {
                                switch (activeTab) {
                                    case 'class':
                                        return 'Copy all CSS classes with responsive font sizes.';
                                    case 'vars':
                                        return 'Copy all CSS custom properties for your :root selector.';
                                    case 'tag':
                                        return 'Copy all HTML tag styles for automatic responsive typography.';
                                    default:
                                        return 'Copy all generated CSS for your project.';
                                }
                            }

                            /**
                             * Show button success state
                             */
                            showButtonSuccess(button) {
                                if (!button) return;

                                button.classList.add('success');

                                setTimeout(() => {
                                    button.classList.remove('success');
                                }, 1500);
                            }

                            /**
                             * Setup keyboard shortcuts for copy operations
                             */
                            setupKeyboardShortcuts() {
                                document.addEventListener('keydown', (e) => {
                                    // Ctrl/Cmd + Shift + C for selected CSS
                                    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
                                        e.preventDefault();
                                        this.copySelectedCSS(

                                        );
                                    }

                                    // Ctrl/Cmd + Shift + A for all CSS
                                    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'A') {
                                        e.preventDefault();
                                        this.copyGeneratedCSS();
                                    }
                                });
                            }

                            getSelectedSizeId() {
                                // If a row is specifically selected, use that
                                if (this.selectedRowId) {
                                    return this.selectedRowId;
                                }

                                // Otherwise fall back to base value dropdown
                                const activeTab = window.fontClampCore?.activeTab || 'class';
                                const baseValue = document.getElementById('base-value')?.value;

                                if (!baseValue) return null;

                                const sizes = this.getCurrentSizes();
                                const selectedSize = sizes.find(size => {
                                    switch (activeTab) {
                                        case 'class':
                                            return size.className === baseValue;
                                        case 'vars':
                                            return size.variableName === baseValue;
                                        case 'tag':
                                            return size.tagName === baseValue;
                                    }
                                });

                                return selectedSize ? selectedSize.id : null;
                            }
                            updatePreviewFont() {
                                const fontUrl = this.elements.previewFontUrlInput?.value;
                                const filenameSpan = this.elements.fontFilenameSpan;

                                if (fontUrl && fontUrl.trim()) {
                                    const filename = fontUrl.split('/').pop().split('?')[0] || 'Custom Font';
                                    if (filenameSpan) {
                                        filenameSpan.textContent = filename;
                                    }

                                    if (this.lastFontStyle) {
                                        this.lastFontStyle.remove();
                                    }

                                    const fontStyle = document.createElement('style');
                                    fontStyle.textContent = `
                            @font-face {
                                font-family: 'PreviewFont';
                                src: url('${fontUrl}') format('woff2');
                                font-display: swap;
                            }
                            :root {
                                --preview-font: 'PreviewFont', sans-serif;
                            }
                        `;
                                    document.head.appendChild(fontStyle);
                                    this.lastFontStyle = fontStyle;
                                } else {
                                    if (filenameSpan) {
                                        filenameSpan.textContent = 'Default';
                                    }
                                    if (this.lastFontStyle) {
                                        this.lastFontStyle.remove();
                                        this.lastFontStyle = null;
                                    }
                                }
                            }

                            // ========================================================================
                            // MODAL & EDITING METHODS
                            // ========================================================================

                            setupModal() {
                                const existing = document.getElementById('edit-modal');
                                if (existing) existing.remove();

                                const modal = document.createElement('div');
                                modal.id = 'edit-modal';
                                modal.className = 'fcc-modal';
                                modal.innerHTML = `
                        <div class="fcc-modal-dialog">
                            <div class="fcc-modal-header">
                                Edit Size
                                <button type="button" class="fcc-modal-close" aria-label="Close">&times;</button>
                            </div>
                            <div class="fcc-modal-content">
                                <div class="fcc-form-group" id="name-field">
                                    <label class="fcc-label" for="edit-name">Name</label>
                                    <input type="text" id="edit-name" class="fcc-input" required>
                                </div>
                                <div class="fcc-form-group">
                                    <label class="fcc-label" for="edit-line-height">Line Height</label>
                                    <input type="number" id="edit-line-height" class="fcc-input" 
                                           step="0.1" min="0.8" max="3.0" required>
                                </div>
<div class="fcc-btn-group">
    <button type="button" class="fcc-btn fcc-btn-ghost" id="modal-cancel">cancel</button>
    <button type="button" class="fcc-btn" id="modal-save">save</button>
</div>
                            </div>
                        </div>
                    `;

                                document.body.appendChild(modal);
                                this.bindModalEvents(modal);
                            }

                            bindModalEvents(modal) {
                                const closeBtn = modal.querySelector('.fcc-modal-close');
                                const cancelBtn = modal.querySelector('#modal-cancel');
                                const saveBtn = modal.querySelector('#modal-save');

                                closeBtn.addEventListener('click', () => this.closeModal());
                                cancelBtn.addEventListener('click', () => this.closeModal());

                                modal.addEventListener('click', (e) => {
                                    if (e.target === modal) this.closeModal();
                                });

                                saveBtn.addEventListener('click', () => {
                                    this.saveEdit();
                                });
                                modal.addEventListener('keydown', (e) => {
                                    if (e.key === 'Enter') {
                                        e.preventDefault();
                                        this.saveEdit();
                                    } else if (e.key === 'Escape') {
                                        this.closeModal();
                                    }
                                });
                            }

                            editSize(id) {
                                const sizes = this.getCurrentSizes();
                                const size = sizes.find(s => s.id == id);
                                if (!size) return;

                                this.editingId = id;

                                const modal = document.getElementById('edit-modal');
                                const nameInput = document.getElementById('edit-name');
                                const nameField = document.getElementById('name-field');
                                const lineHeightInput = document.getElementById('edit-line-height');

                                if (!modal || !nameInput || !lineHeightInput) return;

                                const activeTab = window.fontClampCore?.activeTab || 'class';
                                const displayName = this.getSizeDisplayName(size, activeTab);

                                nameInput.value = displayName;
                                lineHeightInput.value = size.lineHeight;

                                // For tags, show the name field but disable it
                                if (activeTab === 'tag') {
                                    nameField.style.display = 'block';
                                    nameInput.disabled = true;
                                    nameInput.style.opacity = '0.6';
                                    nameInput.style.cursor = 'not-allowed';
                                } else {
                                    nameField.style.display = 'block';
                                    nameInput.disabled = false;
                                    nameInput.style.opacity = '1';
                                    nameInput.style.cursor = 'text';
                                }

                                const header = modal.querySelector('.fcc-modal-header');
                                if (header) {
                                    header.firstChild.textContent = `Edit ${displayName}`;
                                }

                                modal.classList.add('show');

                                setTimeout(() => {
                                    (activeTab === 'tag' ? lineHeightInput : nameInput).focus();
                                }, 100);
                            }

                            saveEdit() {
                                if (!this.editingId) return;

                                const sizes = this.getCurrentSizes();
                                const activeTab = window.fontClampCore?.activeTab || 'class';
                                let size;

                                if (this.isAddingNew) {
                                    // Create new size entry
                                    size = {
                                        id: this.editingId,
                                        min: this.constants.DEFAULT_MIN_ROOT_SIZE,
                                        max: this.constants.DEFAULT_MAX_ROOT_SIZE,
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    };

                                    // Add type-specific properties
                                    if (activeTab === 'class') {
                                        size.className = '';
                                    } else if (activeTab === 'vars') {
                                        size.variableName = '';
                                    } else if (activeTab === 'tag') {
                                        size.tagName = '';
                                    }

                                } else {
                                    // Edit existing size
                                    size = sizes.find(s => s.id == this.editingId);
                                    if (!size) return;
                                }

                                const nameInput = document.getElementById('edit-name');
                                const lineHeightInput = document.getElementById('edit-line-height')

                                const newName = nameInput?.value.trim();
                                const newLineHeight = parseFloat(lineHeightInput?.value);

                                if (!newName && activeTab !== 'tag') {
                                    this.showFieldError(nameInput, 'Name cannot be empty');
                                    return;
                                }

                                if (isNaN(newLineHeight) || newLineHeight < 0.8 || newLineHeight > 3.0) {
                                    this.showFieldError(lineHeightInput, 'Line height must be between 0.8 and 3.0');
                                    return;
                                }

                                if (activeTab !== 'tag') {
                                    const isDuplicate = sizes.some(s => {
                                        if (s.id === this.editingId) return false;
                                        const existingName = this.getSizeDisplayName(s, activeTab);
                                        return existingName === newName;
                                    });

                                    if (isDuplicate) {
                                        this.showFieldError(nameInput, 'A size with this name already exists');
                                        return;
                                    }
                                }

                                if (activeTab === 'class') {
                                    size.className = newName;
                                } else if (activeTab === 'vars') {
                                    size.variableName = newName;
                                } else if (activeTab === 'tag') {
                                    size.tagName = newName;
                                }
                                size.lineHeight = newLineHeight;

                                // Add new size to array if we're adding
                                if (this.isAddingNew) {
                                    sizes.push(size);
                                }

                                this.updateBaseValueOptions();
                                this.calculateSizes();
                                this.renderSizes();
                                this.updatePreview();
                                this.markDataChanged();
                                this.closeModal();
                            }

                            closeModal() {
                                const modal = document.getElementById('edit-modal');
                                if (modal) {
                                    modal.classList.remove('show');
                                }
                                this.editingId = null;
                            }

                            showFieldError(field, message) {
                                field.classList.add('error');
                                field.focus();
                                alert(message);
                                setTimeout(() => field.classList.remove('error'), 3000);
                            }

                            deleteSize(id) {
                                if (confirm('Delete this size?')) {
                                    const sizes = this.getCurrentSizes();
                                    const index = sizes.findIndex(s => s.id == id);
                                    if (index !== -1) {
                                        sizes.splice(index, 1);
                                        this.renderSizes();
                                        this.updatePreview();
                                        this.markDataChanged();
                                    }
                                }
                            }

                            // ========================================================================
                            // TABLE ACTIONS & CONTROLS
                            // ========================================================================

                            addNewSize() {
                                const activeTab = window.fontClampCore?.activeTab || 'class';

                                // Generate next custom name and ID
                                const {
                                    nextId,
                                    customName
                                } = this.generateNextCustomEntry(activeTab);

                                // Open add modal
                                this.openAddModal(activeTab, nextId, customName);
                            }

                            // Generate next custom entry data
                            generateNextCustomEntry(activeTab) {
                                let currentData;
                                if (activeTab === 'class') {
                                    currentData = window.fontClampAjax?.data?.classSizes || [];
                                } else if (activeTab === 'vars') {
                                    currentData = window.fontClampAjax?.data?.variableSizes || [];
                                } else if (activeTab === 'tag') {
                                    currentData = window.fontClampAjax?.data?.tagSizes || [];
                                }

                                // Find next available ID
                                const maxId = currentData.length > 0 ? Math.max(...currentData.map(item => item.id)) : 0;
                                const nextId = maxId + 1;

                                // Generate custom name based on existing custom entries
                                const customEntries = currentData.filter(item => {
                                    const name = this.getSizeDisplayName(item, activeTab);
                                    return name.includes('custom-') || name.includes('--fs-custom-');
                                });

                                const nextCustomNumber = customEntries.length + 1;
                                let customName;

                                if (activeTab === 'class') {
                                    customName = `custom-${nextCustomNumber}`;
                                } else if (activeTab === 'vars') {
                                    customName = `--fs-custom-${nextCustomNumber}`;
                                } else if (activeTab === 'tag') {
                                    customName = 'span';
                                }

                                return {
                                    nextId,
                                    customName
                                };
                            }

                            // Open add modal with pre-filled data
                            openAddModal(activeTab, newId, defaultName) {
                                const modal = document.getElementById('edit-modal');
                                const header = modal.querySelector('.fcc-modal-header');
                                const nameInput = document.getElementById('edit-name');
                                const nameField = document.getElementById('name-field');
                                const lineHeightInput = document.getElementById('edit-line-height');

                                header.firstChild.textContent = `Add ${activeTab === 'class' ? 'Class' : activeTab === 'vars' ? 'Variable' : 'Tag'}`;

                                nameInput.value = defaultName;
                                lineHeightInput.value = this.constants.DEFAULT_BODY_LINE_HEIGHT;

                                // For tags, show the name field but disable it
                                if (activeTab === 'tag') {
                                    nameField.style.display = 'block';
                                    nameInput.disabled = true;
                                    nameInput.style.opacity = '0.6';
                                    nameInput.style.cursor = 'not-allowed';
                                } else {
                                    nameField.style.display = 'block';
                                    nameInput.disabled = false;
                                    nameInput.style.opacity = '1';
                                    nameInput.style.cursor = 'text';
                                }

                                // Store add context for save function
                                this.editingId = newId;
                                this.isAddingNew = true;

                                modal.classList.add('show');

                                // Focus on the name input field and add Enter key handling
                                setTimeout(() => {
                                    const focusInput = activeTab === 'tag' ? lineHeightInput : nameInput;
                                    if (focusInput) {
                                        focusInput.focus();
                                        if (focusInput === nameInput) {
                                            focusInput.setSelectionRange(0, focusInput.value.length); // Select all text
                                        }
                                    }
                                }, 100);
                            }

                            resetDefaults() {
                                const activeTab = window.fontClampCore?.activeTab || 'class';
                                const tabName = activeTab === 'class' ? 'Classes' : activeTab === 'vars' ? 'Variables' : 'Tags';

                                if (confirm(`Reset ${tabName} to defaults?\n\nThis will replace all current entries with the original default sizes.\n\nAny custom entries will be lost.`)) {
                                    switch (activeTab) {
                                        case 'class':
                                            window.fontClampAjax.data.classSizes = this.getDefaultClassSizes();
                                            break;
                                        case 'vars':
                                            window.fontClampAjax.data.variableSizes = this.getDefaultVariableSizes();
                                            break;
                                        case 'tag':
                                            window.fontClampAjax.data.tagSizes = this.getDefaultTagSizes();
                                            break;
                                    }

                                    this.calculateSizes();
                                    this.renderSizes();
                                    this.updatePreview();
                                    this.markDataChanged();

                                    // Update only the current tab's data in core interface
                                    setTimeout(() => {
                                        if (window.fontClampCore) {
                                            const activeTab = window.fontClampCore.activeTab;
                                            // Update only the specific tab's data
                                            switch (activeTab) {
                                                case 'class':
                                                    window.fontClampCore.classSizes = window.fontClampAjax.data.classSizes;
                                                    break;
                                                case 'vars':
                                                    window.fontClampCore.variableSizes = window.fontClampAjax.data.variableSizes;
                                                    break;
                                                case 'tailwind':
                                                    window.fontClampCore.tailwindSizes = window.fontClampAjax.data.tailwindSizes;
                                                    break;
                                                case 'tag':
                                                    window.fontClampCore.tagSizes = window.fontClampAjax.data.tagSizes;
                                                    break;
                                            }
                                            // Then update dropdown with fresh data for current tab only
                                            window.fontClampCore.updateBaseValueDropdown(activeTab);
                                        }
                                    }, 200);

                                    // Show success notification
                                    this.showResetNotification(tabName);
                                }
                            }

                            // Show reset success notification
                            showResetNotification(tabName) {
                                // Create reset notification
                                const notification = document.createElement('div');
                                notification.id = 'reset-notification';
                                notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: var(--clr-success);
        color: white;
        padding: 16px 20px;
        border-radius: var(--jimr-border-radius-lg);
        box-shadow: var(--clr-shadow-xl);
        z-index: 10001;
        display: flex;
        align-items: center;
        gap: 12px;
        border: 2px solid var(--clr-success-dark);
        animation: slideInUp 0.3s ease;
    `;

                                notification.innerHTML = `
        <div style="font-size: 20px;">✅</div>
        <div>
            <div style="font-weight: 600; margin-bottom: 2px;">Reset Complete</div>
            <div style="font-size: 12px; opacity: 0.9;">Restored default ${tabName.toLowerCase()}</div>
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

                            clearSizes() {
                                const activeTab = window.fontClampCore?.activeTab || 'class';
                                const tabName = activeTab === 'class' ? 'Classes' : activeTab === 'vars' ? 'Variables' : 'Tags';

                                // Get current data for backup
                                let currentData, dataArrayRef;
                                if (activeTab === 'class') {
                                    currentData = [...(window.fontClampAjax?.data?.classSizes || [])];
                                    dataArrayRef = 'classSizes';
                                } else if (activeTab === 'vars') {
                                    currentData = [...(window.fontClampAjax?.data?.variableSizes || [])];
                                    dataArrayRef = 'variableSizes';
                                } else if (activeTab === 'tag') {
                                    currentData = [...(window.fontClampAjax?.data?.tagSizes || [])];
                                    dataArrayRef = 'tagSizes';
                                }

                                // Show confirmation dialog
                                const confirmed = confirm(`Are you sure you want to clear all ${tabName}?\n\nThis will remove all ${currentData.length} entries from the current tab.\n\nYou can undo this action immediately after.`);

                                if (!confirmed) return;

                                // Clear the data source
                                if (window.fontClampAjax?.data) {
                                    window.fontClampAjax.data[dataArrayRef] = [];
                                }

                                // Directly render empty table instead of calling renderSizes()
                                this.renderEmptyTable();

                                // Handle empty base dropdown directly
                                const baseSelect = document.getElementById('base-value');
                                if (baseSelect) {
                                    const emptyOptionText = activeTab === 'class' ? 'No classes' :
                                        activeTab === 'vars' ? 'No variables' : 'No tags';
                                    baseSelect.innerHTML = `<option>${emptyOptionText}</option>`;
                                    baseSelect.disabled = true;
                                }

                                this.updatePreview();
                                this.updateCSS();
                                this.markDataChanged();

                                // Show undo notification
                                this.showUndoNotification(tabName, currentData, dataArrayRef, activeTab);
                            }

                            showUndoNotification(tabName, backupData, dataArrayRef, tabType) {
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

                                // Add CSS animation if not already added
                                if (!document.getElementById('notification-animations')) {
                                    const style = document.createElement('style');
                                    style.id = 'notification-animations';
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
                                }

                                document.body.appendChild(notification);

                                // Undo button functionality
                                document.getElementById('undo-clear-btn').addEventListener('click', () => {
                                    // Restore the data
                                    if (window.fontClampAjax?.data) {
                                        window.fontClampAjax.data[dataArrayRef] = backupData;
                                    }

                                    // Regenerate the table
                                    this.renderSizes();
                                    this.updateBaseValueOptions();
                                    this.updatePreview();
                                    this.updateCSS();

                                    // Remove notification
                                    this.removeNotification(notification);
                                });

                                // Dismiss button functionality
                                document.getElementById('dismiss-clear-btn').addEventListener('click', () => {
                                    this.removeNotification(notification);
                                });

                                // Enter key handling
                                const handleUndoKeydown = (event) => {
                                    if (event.key === 'Enter' && document.body.contains(notification)) {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        this.removeNotification(notification);
                                        document.removeEventListener('keydown', handleUndoKeydown);
                                    }
                                };
                                document.addEventListener('keydown', handleUndoKeydown);

                                // Auto-dismiss after 10 seconds
                                setTimeout(() => {
                                    if (document.body.contains(notification)) {
                                        this.removeNotification(notification);
                                        document.removeEventListener('keydown', handleUndoKeydown);
                                    }
                                }, 10000);
                            }

                            removeNotification(notification) {
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
                                    }
                                }, 250);
                            }

                            renderEmptyTable() {
                                const wrapper = this.elements.sizesTableWrapper;
                                if (!wrapper) return;

                                const activeTab = window.fontClampCore?.activeTab || 'class';
                                const tabDisplayName = activeTab === 'class' ? 'Font Size Classes' :
                                    activeTab === 'vars' ? 'CSS Variables' : 'HTML Tag Styles';
                                const addButtonText = activeTab === 'class' ? 'add first class' :
                                    activeTab === 'vars' ? 'add first variable' : 'add first tag';

                                wrapper.innerHTML = `
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: var(--jimr-border-radius-lg); border: 2px dashed var(--jimr-gray-300); margin-top: 20px;">
            <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;">📭</div>
            <h3 style="color: var(--jimr-gray-600); margin: 0 0 8px 0; font-size: 18px;">No ${tabDisplayName}</h3>
            <p style="color: var(--jimr-gray-500); margin: 0 0 20px 0; font-size: 14px;">Get started by adding your first size or reset to defaults.</p>
<button id="add-size" class="fcc-btn" style="margin-right: 12px;">${addButtonText}</button>
            <button id="reset-defaults" class="fcc-btn">reset to defaults</button>
        </div>
    `;

                                // Note: The existing event delegation in setupTableActions() will handle these buttons
                                // since they use the same IDs as the main action buttons
                            }
                            updateSettings() {
                                this.calculateSizes();
                                this.renderSizes();
                                this.updatePreview();
                                this.markDataChanged();
                            }

                            markDataChanged() {
                                this.dataChanged = true;
                            }

                            // ========================================================================
                            // DATA MANAGEMENT & UTILITY METHODS
                            // ========================================================================

                            getCurrentSizes() {
                                const activeTab = window.fontClampCore?.activeTab || 'class';
                                const sizes = FontClampUtils.getCurrentSizes(activeTab, this);
                                console.log(`🔍 getCurrentSizes for ${activeTab}:`, sizes);
                                return sizes;
                            }

                            getSizeDisplayName(size, activeTab) {
                                switch (activeTab) {
                                    case 'class':
                                        return size.className || '';
                                    case 'vars':
                                        return size.variableName || '';
                                    case 'tailwind':
                                        return size.tailwindName || '';
                                    case 'tag':
                                        return size.tagName || '';
                                    default:
                                        return '';
                                }
                            }

                            formatSize(value, unitType) {
                                if (!value) return '—';
                                if (unitType === 'px') {
                                    return `${Math.round(value)} ${unitType}`;
                                }
                                return `${value.toFixed(3)} ${unitType}`;
                            }

                            debounce(func, wait) {
                                let timeout;
                                return function executedFunction(...args) {
                                    const later = () => {
                                        clearTimeout(timeout);
                                        func.apply(this, args);
                                    };
                                    clearTimeout(timeout);
                                    timeout = setTimeout(later, wait);
                                };
                            }

                            showError(message) {
                                console.error(message);
                            }

                            // ========================================================================
                            // DEFAULT DATA FACTORIES
                            // ========================================================================

                            getDefaultClassSizes() {
                                return [{
                                        id: 1,
                                        className: 'xxxlarge',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 2,
                                        className: 'xxlarge',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 3,
                                        className: 'xlarge',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 4,
                                        className: 'large',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 5,
                                        className: 'medium',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 6,
                                        className: 'small',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 7,
                                        className: 'xsmall',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 8,
                                        className: 'xxsmall',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    }
                                ];
                            }

                            getDefaultVariableSizes() {
                                return [{
                                        id: 1,
                                        variableName: '--fs-xxxl',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 2,
                                        variableName: '--fs-xxl',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 3,
                                        variableName: '--fs-xl',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 4,
                                        variableName: '--fs-lg',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 5,
                                        variableName: '--fs-md',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 6,
                                        variableName: '--fs-sm',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 7,
                                        variableName: '--fs-xs',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 8,
                                        variableName: '--fs-xxs',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    }
                                ];
                            }

                            getDefaultTagSizes() {
                                return [{
                                        id: 1,
                                        tagName: 'h1',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 2,
                                        tagName: 'h2',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 3,
                                        tagName: 'h3',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 4,
                                        tagName: 'h4',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 5,
                                        tagName: 'h5',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 6,
                                        tagName: 'h6',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 7,
                                        tagName: 'p',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    }
                                ];
                            }

                            getDefaultTailwindSizes() {
                                return [{
                                        id: 1,
                                        tailwindName: '4xl',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 2,
                                        tailwindName: '3xl',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 3,
                                        tailwindName: '2xl',
                                        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT
                                    },
                                    {
                                        id: 4,
                                        tailwindName: 'xl',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 5,
                                        tailwindName: 'base',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 6,
                                        tailwindName: 'lg',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 7,
                                        tailwindName: 'sm',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    },
                                    {
                                        id: 8,
                                        tailwindName: 'xs',
                                        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT
                                    }
                                ];
                            }
                        }

                        // Initialize Advanced Features
                        document.addEventListener('DOMContentLoaded', () => {
                            window.fontClampAdvanced = new FontClampAdvanced();
                        });

                        new SimpleTooltips();

                        document.addEventListener('DOMContentLoaded', () => {
                            window.fontClampCore = new FontClampEnhancedCoreInterface();
                        });
                    </script>
            <?php
            }

            // ========================================================================
            // AJAX HANDLERS
            // ========================================================================

            public function save_sizes()
            {
                // Enhanced save functionality - placeholder
                wp_send_json_success(['message' => 'Sizes saved successfully']);
            }


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
                    $result4 = update_option(self::OPTION_TAG_SIZES, $sizes['tagSizes'] ?? []);

                    // Clear cached data
                    wp_cache_delete(self::OPTION_SETTINGS, 'options');
                    wp_cache_delete(self::OPTION_CLASS_SIZES, 'options');
                    wp_cache_delete(self::OPTION_VARIABLE_SIZES, 'options');
                    wp_cache_delete(self::OPTION_TAG_SIZES, 'options');

                    wp_send_json_success([
                        'message' => 'All data saved to database successfully',
                        'saved_settings' => $result1,
                        'saved_sizes' => $result2 && $result3 && $result4
                    ]);
                } catch (Exception $e) {
                    wp_send_json_error(['message' => 'Save failed: ' . $e->getMessage()]);
                }
            }
        }

        // ========================================================================
        // INITIALIZATION - CRITICAL FOR SNIPPETS MANAGER
        // ========================================================================

        // Initialize the unified Fluid Font Forge
        if (is_admin()) {
            global $fontClampCalculator;
            $fontClampCalculator = new FontClampCalculator();
        }
