# Media Inventory Forge - Plugin Conversion Implementation Guide

## Project Status
- 🔄 **Phase 1, Step 4**: Extract CSS/JS into Assets Directory

---

## Phase 1: Basic Structure Setup

### ✅ Step 1: Create Main Directories *(COMPLETED)*
```
media-inventory-forge/
├── includes/
├── assets/
└── templates/
```

### ✅ Step 2: Create Main Plugin File *(COMPLETED)*
File: `media-inventory-forge.php`

### ✅ Step 3: Split Large Function into Core Classes *(COMPLETED)*

**Current Goal**: Break apart the monolithic code into logical class files in `includes/core/`

#### 3a. Create Core Directory Structure
```
includes/
├── core/
│   ├── class-scanner.php
│   ├── class-analyzer.php
│   ├── class-categorizer.php
│   └── class-file-processor.php
├── utilities/
│   ├── class-file-utils.php
│   └── class-format-utils.php
└── admin/
    ├── class-admin.php
    └── class-ajax-handler.php
```

#### 3b. Extract Helper Functions First
**File**: `includes/utilities/class-file-utils.php`
```php
<?php
/**
 * File utilities for Media Inventory Forge
 */

// Prevent direct access
defined('ABSPATH') || exit;

class MIF_File_Utils {
    
    /**
     * Format file sizes in human-readable format
     */
    public static function format_bytes($bytes, $precision = 2) {
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
    public static function get_category($mime_type) {
        if ($mime_type === 'image/svg+xml') return 'SVG';
        if (strpos($mime_type, 'image/') === 0) return 'Images';
        if (strpos($mime_type, 'video/') === 0) return 'Videos';
        if (strpos($mime_type, 'audio/') === 0) return 'Audio';
        if (strpos($mime_type, 'application/pdf') === 0) return 'PDFs';
        if (strpos($mime_type, 'font/') === 0 || strpos($mime_type, 'application/font') === 0) return 'Fonts';
        if (in_array($mime_type, [
            'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation'
        ])) return 'Documents';
        if (strpos($mime_type, 'text/') === 0) return 'Text Files';
        if (strpos($mime_type, 'application/') === 0) return 'Other Documents';
        return 'Other';
    }

    /**
     * Extract font family name from filename or title
     */
    public static function get_font_family($title, $filename) {
        $name = !empty($title) ? $title : pathinfo($filename, PATHINFO_FILENAME);
        
        // Remove common font weight/style suffixes
        $name = preg_replace('/[-_\s]?(regular|bold|italic|light|medium|heavy|black|thin|extralight|semibold|extrabold)[-_\s]?/i', '', $name);
        
        // Remove file extensions and numbers
        $name = preg_replace('/\.(woff2?|ttf|otf|eot)$/i', '', $name);
        $name = preg_replace('/[-_\s]*\d+[-_\s]*/', '', $name);
        
        // Clean up and capitalize
        $name = trim($name, '-_ ');
        $name = ucwords(str_replace(['-', '_'], ' ', $name));
        
        return !empty($name) ? $name : 'Unknown Font';
    }

    /**
     * Validate file path is within uploads directory
     */
    public static function is_valid_upload_path($file_path) {
        $upload_dir = wp_upload_dir();
        $upload_basedir = $upload_dir['basedir'];
        
        // Get real paths to prevent directory traversal
        $real_file_path = realpath($file_path);
        $real_upload_path = realpath($upload_basedir);
        
        if (!$real_file_path || !$real_upload_path) {
            return false;
        }
        
        return strpos($real_file_path, $real_upload_path) === 0;
    }
}
```

#### 3c. Create Scanner Class
**File**: `includes/core/class-scanner.php`
```php
<?php
/**
 * Media scanner core functionality
 */

// Prevent direct access
defined('ABSPATH') || exit;

class MIF_Scanner {
    
    private $batch_size;
    private $upload_dir;
    private $processed_count = 0;
    private $errors = [];

    public function __construct($batch_size = 10) {
        $this->batch_size = $batch_size;
        $this->upload_dir = wp_upload_dir();
    }

    /**
     * Scan a batch of attachments
     */
    public function scan_batch($offset) {
        // Set time and memory limits
        set_time_limit(30);
        wp_raise_memory_limit('admin');

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
                $this->errors[] = "Error processing attachment ID $attachment_id: " . $e->getMessage();
            }
        }

        return [
            'data' => $inventory_data,
            'offset' => $offset + $this->batch_size,
            'total' => $this->get_total_attachments(),
            'complete' => ($offset + $this->batch_size) >= $this->get_total_attachments(),
            'processed' => min($offset + $this->batch_size, $this->get_total_attachments()),
            'errors' => $this->errors
        ];
    }

    /**
     * Get attachments for current batch
     */
    private function get_attachments($offset) {
        return get_posts([
            'post_type' => 'attachment',
            'posts_per_page' => $this->batch_size,
            'offset' => $offset,
            'fields' => 'ids',
            'post_status' => 'inherit'
        ]);
    }

    /**
     * Process individual attachment
     */
    private function process_attachment($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        $mime_type = get_post_mime_type($attachment_id);
        $title = get_the_title($attachment_id);

        if (!$file_path || !$mime_type) {
            $this->errors[] = "Skipped attachment ID $attachment_id: missing file path or MIME type";
            return null;
        }

        // Use file processor to handle the actual processing
        $processor = new MIF_File_Processor();
        return $processor->process_file($attachment_id, $file_path, $mime_type, $title);
    }

    /**
     * Get total number of attachments
     */
    private function get_total_attachments() {
        return wp_count_posts('attachment')->inherit;
    }

    /**
     * Get current error list
     */
    public function get_errors() {
        return $this->errors;
    }
}
```

#### 3d. Create File Processor Class
**File**: `includes/core/class-file-processor.php`
```php
<?php
/**
 * Individual file processing logic
 */

// Prevent direct access
defined('ABSPATH') || exit;

class MIF_File_Processor {

    private $upload_basedir;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->upload_basedir = $upload_dir['basedir'];
    }

    /**
     * Process a single file and return its data
     */
    public function process_file($attachment_id, $file_path, $mime_type, $title) {
        $category = MIF_File_Utils::get_category($mime_type);
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        $item_data = [
            'id' => $attachment_id,
            'title' => $title,
            'mime_type' => $mime_type,
            'category' => $category,
            'extension' => $extension,
            'files' => [],
            'file_count' => 0,
            'total_size' => 0,
            'dimensions' => '',
            'font_family' => ''
        ];

        // Process main file
        $this->process_main_file($item_data, $file_path, $mime_type);

        // Handle category-specific processing
        switch ($category) {
            case 'Fonts':
                $item_data['font_family'] = MIF_File_Utils::get_font_family($title, $file_path);
                break;
            case 'Images':
                $this->process_image_variations($item_data, $attachment_id, $file_path);
                break;
        }

        return $item_data;
    }

    /**
     * Process the main file
     */
    private function process_main_file(&$item_data, $file_path, $mime_type) {
        if (!file_exists($file_path)) {
            return;
        }

        $file_size = filesize($file_path);
        $file_info = [
            'path' => str_replace($this->upload_basedir, '', $file_path),
            'filename' => basename($file_path),
            'size' => $file_size,
            'type' => 'original',
            'dimensions' => ''
        ];

        // Get image dimensions
        if (strpos($mime_type, 'image/') === 0) {
            $image_info = @getimagesize($file_path);
            if ($image_info) {
                $file_info['dimensions'] = $image_info[0] . ' × ' . $image_info[1] . 'px';
                $item_data['dimensions'] = $file_info['dimensions'];
            }
        }

        $item_data['files'][] = $file_info;
        $item_data['file_count']++;
        $item_data['total_size'] += $file_size;
    }

    /**
     * Process image variations and thumbnails
     */
    private function process_image_variations(&$item_data, $attachment_id, $file_path) {
        // Get thumbnail URL for display
        $thumbnail_url = wp_get_attachment_image_src($attachment_id, 'thumbnail');
        if ($thumbnail_url) {
            $item_data['thumbnail_url'] = $thumbnail_url[0];
            $item_data['thumbnail_width'] = $thumbnail_url[1];
            $item_data['thumbnail_height'] = $thumbnail_url[2];
        } else {
            $item_data['thumbnail_url'] = wp_get_attachment_url($attachment_id);
        }

        // Process image size variations
        $metadata = wp_get_attachment_metadata($attachment_id);
        if ($metadata && isset($metadata['sizes'])) {
            $dirname = dirname($file_path);
            $processed_files = [];

            foreach ($metadata['sizes'] as $size_name => $size_data) {
                $size_file = $dirname . '/' . $size_data['file'];
                $size_file_key = basename($size_file);

                if (file_exists($size_file) && !isset($processed_files[$size_file_key])) {
                    $file_size = filesize($size_file);
                    $file_info = [
                        'path' => str_replace($this->upload_basedir, '', $size_file),
                        'filename' => basename($size_file),
                        'size' => $file_size,
                        'type' => 'size: ' . $size_name,
                        'dimensions' => ''
                    ];

                    // Get dimensions for this size
                    $image_info = @getimagesize($size_file);
                    if ($image_info) {
                        $file_info['dimensions'] = $image_info[0] . ' × ' . $image_info[1] . 'px';
                    }

                    $item_data['files'][] = $file_info;
                    $item_data['file_count']++;
                    $item_data['total_size'] += $file_size;
                    $processed_files[$size_file_key] = true;
                }
            }
        }
    }
}
```

#### 3e. Update Main Plugin File
**File**: `media-inventory-forge.php` *(Update to include new classes)*
```php
// Add after the constants, before the existing code:

// Load utility classes
require_once MIF_PLUGIN_DIR . 'includes/utilities/class-file-utils.php';

// Load core classes
require_once MIF_PLUGIN_DIR . 'includes/core/class-scanner.php';
require_once MIF_PLUGIN_DIR . 'includes/core/class-file-processor.php';
```

#### 3f. Update AJAX Handler
**In your existing AJAX function**, replace the main scanning logic with:
```php
function media_inventory_ajax_scan() {
    check_ajax_referer('media_inventory_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }

    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $batch_size = 10;

    // Use the new scanner class
    $scanner = new MIF_Scanner($batch_size);
    $result = $scanner->scan_batch($offset);

    wp_send_json_success($result);
}
```

### ✅ Step 3 Completion Checklist
- [ ] Created `includes/core/` directory
- [ ] Created `includes/utilities/` directory
- [ ] Created `MIF_File_Utils` class with helper functions
- [ ] Created `MIF_Scanner` class with batch processing
- [ ] Created `MIF_File_Processor` class for individual files
- [ ] Updated main plugin file to load new classes
- [ ] Updated AJAX handler to use new scanner
- [ ] Tested that scanning still works

---

## Phase 1: Remaining Steps

### Step 4: Extract CSS/JS into Assets Directory

#### 4a. Create Asset Files
**File**: `assets/css/admin.css`
```css
/* Move all the CSS from the inline <style> block */
:root {
    /* Core JimRWeb Brand Colors */
    --clr-primary: #3C2017;
    --clr-secondary: #5C3324;
    /* ... rest of CSS variables and styles */
}
```

**File**: `assets/js/admin.js`
```javascript
/* Move all JavaScript from the inline <script> block */
class MediaInventoryForge {
    constructor() {
        this.inventoryData = [];
        this.isScanning = false;
        this.init();
    }
    
    init() {
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.bindEvents());
        } else {
            this.bindEvents();
        }
    }
    
    // ... rest of JavaScript functionality
}

// Initialize the app
new MediaInventoryForge();
```

#### 4b. Enqueue Assets Properly
**File**: `includes/admin/class-admin.php`
```php
<?php
class MIF_Admin {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'media-inventory') === false) {
            return;
        }
        
        wp_enqueue_style(
            'mif-admin-css',
            MIF_PLUGIN_URL . 'assets/css/admin.css',
            [],
            MIF_VERSION
        );
        
        wp_enqueue_script(
            'mif-admin-js',
            MIF_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            MIF_VERSION,
            true
        );
        
        // Localize script data
        wp_localize_script('mif-admin-js', 'mifData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('media_inventory_nonce'),
            'strings' => [
                'scanComplete' => __('Scan completed successfully!', 'media-inventory-forge'),
                'scanError' => __('An error occurred during scanning.', 'media-inventory-forge'),
            ]
        ]);
    }
}
```

### Step 5: Create Template Files

#### 5a. Move HTML to Templates
**File**: `templates/admin/main-page.php`
```php
<?php
// Move the HTML content from the admin page function
// Use proper template structure with header/footer includes
?>
<div class="wrap mif-admin-wrap">
    <div class="fcc-header-section">
        <h1 class="text-2xl font-bold mb-4">
            <?php echo esc_html(get_admin_page_title()); ?> (<?php echo MIF_VERSION; ?>)
        </h1>
        
        <?php include MIF_PLUGIN_DIR . 'templates/admin/partials/about-section.php'; ?>
        <?php include MIF_PLUGIN_DIR . 'templates/admin/partials/scan-controls.php'; ?>
        <?php include MIF_PLUGIN_DIR . 'templates/admin/partials/results-section.php'; ?>
    </div>
</div>
```

---

## Phase 2: Organize by Feature

### Step 1: Create Admin Interface Classes
- [ ] Move admin menu creation to `includes/admin/class-admin-menu.php`
- [ ] Move AJAX handlers to `includes/admin/class-ajax-handler.php`
- [ ] Create settings page in `includes/admin/class-settings.php`

### Step 2: Organize Core Logic
- [ ] Create `includes/core/class-analyzer.php` for data analysis
- [ ] Create `includes/core/class-categorizer.php` for file categorization
- [ ] Add performance monitoring to `includes/utilities/class-performance-monitor.php`

### Step 3: Setup Export System
- [ ] Create `includes/export/class-csv-exporter.php`
- [ ] Create `includes/export/class-export-factory.php`
- [ ] Add JSON export capability

---

## Phase 3: Professional Polish

### Step 1: Add Testing Framework
- [ ] Setup PHPUnit in `tests/` directory
- [ ] Create unit tests for core classes
- [ ] Add integration tests for admin functionality

### Step 2: Build Tools
- [ ] Setup `package.json` with build scripts
- [ ] Configure asset compilation (Webpack/Gulp)
- [ ] Add code linting and formatting

### Step 3: Documentation
- [ ] Create user documentation in `docs/`
- [ ] Add inline code documentation
- [ ] Create developer hooks reference

### Step 4: Distribution Preparation
- [ ] Create proper `readme.txt` for WordPress.org
- [ ] Add plugin screenshots
- [ ] Setup release packaging

---

## Testing Checklist

After each major step, verify:
- [ ] Plugin activates without errors
- [ ] Admin menu appears correctly
- [ ] Scanning functionality works
- [ ] Export features function properly
- [ ] No PHP warnings/notices in debug mode
- [ ] Assets load correctly (CSS/JS)

---

## Notes & Context

### Current File Structure
```
media-inventory-forge/
├── media-inventory-forge.php      # Main plugin file
├── includes/
│   ├── core/                      # Core business logic
│   ├── utilities/                 # Helper classes
│   └── admin/                     # Admin interface
├── assets/
│   ├── css/                       # Stylesheets
│   └── js/                        # JavaScript files
└── templates/
    └── admin/                     # Admin page templates
```

### Key Classes Created
- `MIF_File_Utils`: File system and formatting utilities
- `MIF_Scanner`: Batch processing and scanning coordination
- `MIF_File_Processor`: Individual file analysis and processing

### Development Principles
- **Separation of Concerns**: Logic, presentation, and data separate
- **WordPress Standards**: Following official coding standards and best practices
- **Performance First**: Batch processing, memory management, timeout handling
- **Extensibility**: Hook system for future enhancements
- **Security**: Proper nonce verification, capability checks, input sanitization

---

## Next Session Handoff

**Current Status**: Completed Phase 1, Steps 1-2. Working on Phase 1, Step 3 (class extraction).

**Immediate Next Task**: Complete the class extraction by creating the utility and core classes, then test that the scanning functionality still works with the new structure.

**Files to Focus On**:
1. `includes/utilities/class-file-utils.php` - Create first
2. `includes/core/class-scanner.php` - Main scanning logic
3. `includes/core/class-file-processor.php` - Individual file processing
4. Update main plugin file to load new classes
5. Update AJAX handler to use new scanner class

**Testing Priority**: Ensure scanning still works after refactoring before moving to asset extraction.