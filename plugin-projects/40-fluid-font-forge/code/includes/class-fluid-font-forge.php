<?php

/**
 * Fluid Font Forge Main Class
 * 
 * @package FluidFontForge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fluid Font Forge - Complete Unified Class
 */
class FluidFontForge
{
    // ========================================================================
    // CORE CONSTANTS SYSTEM
    // ========================================================================

    // Configuration Constants
    const PLUGIN_SLUG = 'fluid-font-forge';
    const NONCE_ACTION = 'fluid_font_nonce';

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

    // WordPress Options Keys - use global constants
    // Why constants: Prevents typos, ensures consistency across the codebase
    const OPTION_SETTINGS = FLUID_FONT_FORGE_OPTION_SETTINGS;
    // Why separate keys: Allows independent management of settings and size arrays
    const OPTION_CLASS_SIZES = FLUID_FONT_FORGE_OPTION_CLASS_SIZES;
    const OPTION_VARIABLE_SIZES = FLUID_FONT_FORGE_OPTION_VARIABLE_SIZES;
    const OPTION_TAG_SIZES = FLUID_FONT_FORGE_OPTION_TAG_SIZES;
    const OPTION_TAILWIND_SIZES = FLUID_FONT_FORGE_OPTION_TAILWIND_SIZES;

    // Valid Options
    // Why these units: px and rem are most common for font sizing, ensuring compatibility
    const VALID_UNITS = ['px', 'rem'];
    // Why these tabs: Covers all three size management methods provided by the plugin
    const VALID_TABS = ['class', 'vars', 'tag'];

    // Validation Ranges
    // Why 1-100px: Prevents unusably small (<1px) or absurdly large (>100px) root sizes
    const MIN_ROOT_SIZE_RANGE = [1, 100];
    // Why 200-5000px: Covers feature phones to ultra-wide displays safely
    const VIEWPORT_RANGE = [200, 5000];
    // Why 0.8-3.0: Below 0.8 is unreadable, above 3.0 creates excessive spacing
    const LINE_HEIGHT_RANGE = [0.8, 3.0];
    // Why 1.0-3.0: Below 1.0 shrinks text, above 3.0 creates extreme size jumps
    const SCALE_RANGE = [1.0, 3.0];

    // ========================================================================
    // CLASS PROPERTIES
    // ========================================================================

    //
    private $default_settings;
    private $default_class_sizes;
    private $default_variable_sizes;
    private $default_tag_sizes;
    private $default_tailwind_sizes;
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
     * Initialize default values using data factory
     */
    private function init_defaults()
    {
        $defaults = FluidFontForgeDefaultData::getAllDefaults();
        $this->default_settings = $defaults['settings'];
        $this->default_class_sizes = $defaults['classSizes'];
        $this->default_variable_sizes = $defaults['variableSizes'];
        $this->default_tag_sizes = $defaults['tagSizes'];
        $this->default_tailwind_sizes = $defaults['tailwindSizes'];
    }

    /**
     * Initialize WordPress init_hooks (actions and filters)
     */
    private function init_hooks()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_save_font_clamp_sizes', [$this, 'save_sizes']);
        add_action('wp_ajax_save_font_clamp_settings', [$this, 'save_settings']);
    }

    // ========================================================================
    // ADMIN INTERFACE
    // ========================================================================

    /**
     * Add admin menu page
     * // Ensures "Fluid Font Forge" appears under "J Forge" parent menu
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
            'Fluid Font Forge',              // Page title
            'Fluid Font',                    // Menu title
            'manage_options',                // Capability
            self::PLUGIN_SLUG,               // Menu slug
            [$this, 'render_admin_page']     // Callback
        );
    }

    /**
     * Unified asset enqueuing (combines all segments)
     * // Ensures assets load only on the plugin's admin page
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
            FLUID_FONT_FORGE_VERSION
        );

        // Enqueue our admin styles
        wp_enqueue_style(
            'fluid-font-forge-admin',
            FLUID_FONT_FORGE_URL . 'assets/css/admin-styles.css',
            [],
            FLUID_FONT_FORGE_VERSION
        );

        // Enqueue WordPress utilities
        wp_enqueue_script('wp-util');

        // Enqueue tab utilities first
        wp_enqueue_script(
            'fluid-font-forge-tab-utils',
            FLUID_FONT_FORGE_URL . 'assets/js/tab-data-utilities.js',
            [],
            FLUID_FONT_FORGE_VERSION,
            true
        );

        // Enqueue our admin script
        wp_enqueue_script(
            'fluid-font-forge-admin',
            FLUID_FONT_FORGE_URL . 'assets/js/admin-script.js',
            ['wp-util', 'fluid-font-forge-tab-utils'],
            FLUID_FONT_FORGE_VERSION,
            true
        );

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
            'version' => FLUID_FONT_FORGE_VERSION,
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);
    }

    /**
     * Get all constants for JavaScript access
     * @return array Associative array of all defined constants 
     */
    public function get_all_constants()
    {
        return FluidFontForgeDefaultData::getConstants();
    }

    // ========================================================================
    // DATA GETTERS
    // ========================================================================

    /**
     * Get font clamp settings with caching
     * // Caches settings after first retrieval to optimize performance
     */
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

    /**
     * Get font clamp class sizes with caching
     * // Caches sizes after first retrieval to optimize performance
     * // Uses default sizes if none are saved in options
     */
    public function get_font_clamp_class_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_CLASS_SIZES, $this->default_class_sizes);
        }
        return $cached_sizes;
    }

    /**
     * Get font clamp variable sizes with caching
     * // Caches sizes after first retrieval to optimize performance
     * // Uses default sizes if none are saved in options
     */
    public function get_font_clamp_variable_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_VARIABLE_SIZES, $this->default_variable_sizes);
        }
        return $cached_sizes;
    }

    /**
     * Get font clamp tag sizes with caching
     * // Caches sizes after first retrieval to optimize performance
     * // Uses default sizes if none are saved in options
     */
    public function get_font_clamp_tag_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_TAG_SIZES, $this->default_tag_sizes);
        }
        return $cached_sizes;
    }

    /**
     * Get font clamp Tailwind sizes with caching
     * // Caches sizes after first retrieval to optimize performance
     * // Uses default sizes if none are saved in options
     */
    public function get_font_clamp_tailwind_sizes()
    {
        static $cached_sizes = null;
        if ($cached_sizes === null) {
            $cached_sizes = get_option(self::OPTION_TAILWIND_SIZES, $this->default_tailwind_sizes);
        }
        return $cached_sizes;
    }

    // ========================================================================
    // MAIN ADMIN PAGE RENDERER
    // ========================================================================

    /**
     * Main Admin Page Renderer - Complete Interface
     * // Combines all segments into a single cohesive interface
     * // Uses output buffering to capture and return the complete HTML
     * 
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
     * Complete interface HTML     * 
     * @param array $data Combined data for settings and sizes
     * @return string Complete HTML for the admin interface
     * Uses output buffering to capture and return the complete HTML
     * Includes sections for header, about, loading state, main panel, and how-to-use
     * Ensures consistent styling and structure throughout the interface
     */
    private function get_complete_interface($data)
    {
        $settings = $data['settings'];
        $class_sizes = $data['classSizes'];
        $variable_sizes = $data['variableSizes'];
        $tag_sizes = $data['tagSizes'];

        ob_start();
?>
        <div class="wrap" style="background: var(--clr-page-bg); padding: 20px; min-height: 100vh;">
            <div class="fcc-header-section">
                <h1 class="text-2xl font-bold mb-4">Fluid Font Forge (<?php echo FLUID_FONT_FORGE_VERSION; ?>)</h1><br>

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
         * Check if we're on the plugin page
         */
        private function is_font_clamp_page()
        {
            return isset($_GET['page']) && sanitize_text_field($_GET['page']) === self::PLUGIN_SLUG;
        }

        // ========================================================================
        // AJAX HANDLERS
        // ========================================================================

        /**
         * Save font sizes via AJAX
         */
        public function save_sizes()
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
                // Get the active tab to determine which sizes to save
                $active_tab = sanitize_text_field($_POST['activeTab'] ?? 'class');
                $sizes_json = stripslashes($_POST['sizes'] ?? '');
                $sizes = json_decode($sizes_json, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    wp_send_json_error(['message' => 'Invalid sizes data']);
                    return;
                }

                // Validate sizes array
                if (!is_array($sizes)) {
                    wp_send_json_error(['message' => 'Sizes must be an array']);
                    return;
                }

                // Save to appropriate option based on tab
                switch ($active_tab) {
                    case 'class':
                        $result = update_option(self::OPTION_CLASS_SIZES, $sizes);
                        break;
                    case 'vars':
                        $result = update_option(self::OPTION_VARIABLE_SIZES, $sizes);
                        break;
                    case 'tag':
                        $result = update_option(self::OPTION_TAG_SIZES, $sizes);
                        break;
                    case 'tailwind':
                        $result = update_option(FLUID_FONT_FORGE_OPTION_TAILWIND_SIZES, $sizes);
                        break;
                    default:
                        wp_send_json_error(['message' => 'Invalid tab type']);
                        return;
                }

                // Clear cached data
                wp_cache_delete(self::OPTION_CLASS_SIZES, 'options');
                wp_cache_delete(self::OPTION_VARIABLE_SIZES, 'options');
                wp_cache_delete(self::OPTION_TAG_SIZES, 'options');
                wp_cache_delete(FLUID_FONT_FORGE_OPTION_TAILWIND_SIZES, 'options');

                wp_send_json_success([
                    'message' => 'Sizes saved successfully',
                    'saved' => $result,
                    'count' => count($sizes),
                    'activeTab' => $active_tab
                ]);
            } catch (Exception $e) {
                error_log('Fluid Font Forge save_sizes error: ' . $e->getMessage());
                wp_send_json_error(['message' => 'Save failed: ' . $e->getMessage()]);
            }
        }

        /**
         * Save settings via AJAX
         */
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
