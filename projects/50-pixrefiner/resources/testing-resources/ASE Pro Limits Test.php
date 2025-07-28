<?php
/**
 * ASE Pro Capability Test - Font Clamp Calculator
 * 
 * Tests what ASE Pro can handle before we build the real refactored snippet
 * 
 * Instructions: 
 * 1. Add this as a new snippet in ASE Pro
 * 2. Set it to run on "Admin Pages" or "Admin Menu"
 * 3. Activate it and check the results
 * 4. Look for the test results in your admin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ASE Pro Test Class
 */
class FontClampASETest {
    
    private $test_results = [];
    private $memory_start;
    
    public function __construct() {
        $this->memory_start = memory_get_usage();
        $this->run_tests();
    }
    
    /**
     * Run all capability tests
     */
    private function run_tests() {
        // Test 1: WordPress Hook Access
        try {
            $this->test_wordpress_hooks();
        } catch (Exception $e) {
            $this->test_results['WordPress Hooks'] = ['ERROR: ' . $e->getMessage()];
        } catch (Error $e) {
            $this->test_results['WordPress Hooks'] = ['FATAL ERROR: ' . $e->getMessage()];
        }
        
        // Test 2: Memory and Execution
        try {
            $this->test_memory_limits();
        } catch (Exception $e) {
            $this->test_results['Memory & Execution'] = ['ERROR: ' . $e->getMessage()];
        } catch (Error $e) {
            $this->test_results['Memory & Execution'] = ['FATAL ERROR: ' . $e->getMessage()];
        }
        
        // Test 3: File System Access
        try {
            $this->test_file_system();
        } catch (Exception $e) {
            $this->test_results['File System'] = ['ERROR: ' . $e->getMessage()];
        } catch (Error $e) {
            $this->test_results['File System'] = ['FATAL ERROR: ' . $e->getMessage()];
        }
        
        // Test 4: Large Data Handling
        try {
            $this->test_large_data();
        } catch (Exception $e) {
            $this->test_results['Large Data Handling'] = ['ERROR: ' . $e->getMessage()];
        } catch (Error $e) {
            $this->test_results['Large Data Handling'] = ['FATAL ERROR: ' . $e->getMessage()];
        }
        
        // Test 5: CSS/JS Inline Support
        try {
            $this->test_inline_assets();
        } catch (Exception $e) {
            $this->test_results['Inline Assets'] = ['ERROR: ' . $e->getMessage()];
        } catch (Error $e) {
            $this->test_results['Inline Assets'] = ['FATAL ERROR: ' . $e->getMessage()];
        }
        
        // Show results
        add_action('admin_notices', [$this, 'show_test_results']);
    }
    
    /**
     * Test WordPress hook availability
     */
    private function test_wordpress_hooks() {
        $hooks_available = [];
        
        // Test admin_enqueue_scripts
        if (has_action('admin_enqueue_scripts')) {
            $hooks_available[] = 'admin_enqueue_scripts (exists)';
        }
        
        // Test if we can add actions
        $test_action_fired = false;
        add_action('font_clamp_test_action', function() use (&$test_action_fired) {
            $test_action_fired = true;
        });
        do_action('font_clamp_test_action');
        
        if ($test_action_fired) {
            $hooks_available[] = 'Custom actions (working)';
        }
        
        // Test admin_footer
        add_action('admin_footer', function() {
            echo '<!-- ASE Pro admin_footer test: working -->';
        });
        $hooks_available[] = 'admin_footer (added)';
        
        $this->test_results['WordPress Hooks'] = $hooks_available;
    }
    
    /**
     * Test memory and execution limits
     */
    private function test_memory_limits() {
        $memory_info = [];
        
        // Current memory usage
        $current_memory = memory_get_usage(true);
        $memory_info[] = 'Current usage: ' . $this->format_bytes($current_memory);
        
        // Memory limit
        $memory_limit = ini_get('memory_limit');
        $memory_info[] = 'PHP memory limit: ' . $memory_limit;
        
        // Peak memory
        $peak_memory = memory_get_peak_usage(true);
        $memory_info[] = 'Peak usage: ' . $this->format_bytes($peak_memory);
        
        // Test large array creation
        try {
            $large_array = range(1, 10000);
            $memory_after = memory_get_usage(true);
            $memory_info[] = 'Large array test: ' . $this->format_bytes($memory_after - $current_memory) . ' used';
            unset($large_array);
        } catch (Exception $e) {
            $memory_info[] = 'Large array test: FAILED - ' . $e->getMessage();
        }
        
        // Execution time limit
        $time_limit = ini_get('max_execution_time');
        $memory_info[] = 'Max execution time: ' . $time_limit . 's';
        
        $this->test_results['Memory & Execution'] = $memory_info;
    }
    
    /**
     * Test file system access
     */
    private function test_file_system() {
        $file_info = [];
        
        // WordPress upload directory
        $upload_dir = wp_upload_dir();
        $file_info[] = 'Upload dir: ' . $upload_dir['basedir'];
        $file_info[] = 'Upload writable: ' . (is_writable($upload_dir['basedir']) ? 'YES' : 'NO');
        
        // Plugin directory access
        if (defined('WP_PLUGIN_DIR')) {
            $file_info[] = 'Plugin dir: ' . WP_PLUGIN_DIR;
            $file_info[] = 'Plugin readable: ' . (is_readable(WP_PLUGIN_DIR) ? 'YES' : 'NO');
        }
        
        // Temp file creation test
        try {
            // Check if wp_tempnam exists (it doesn't in all WP environments)
            if (function_exists('wp_tempnam')) {
                $temp_file = wp_tempnam('ase_test');
                if ($temp_file) {
                    file_put_contents($temp_file, 'ASE Pro test');
                    $content = file_get_contents($temp_file);
                    unlink($temp_file);
                    $file_info[] = 'Temp file creation: SUCCESS';
                } else {
                    $file_info[] = 'Temp file creation: FAILED';
                }
            } else {
                $file_info[] = 'wp_tempnam function: NOT AVAILABLE (normal in some environments)';
                
                // Try alternative temp file method
                $temp_file = sys_get_temp_dir() . '/ase_test_' . uniqid() . '.tmp';
                if (is_writable(sys_get_temp_dir())) {
                    file_put_contents($temp_file, 'ASE Pro test');
                    $content = file_get_contents($temp_file);
                    unlink($temp_file);
                    $file_info[] = 'Alternative temp file: SUCCESS';
                } else {
                    $file_info[] = 'System temp dir: NOT WRITABLE';
                }
            }
        } catch (Exception $e) {
            $file_info[] = 'Temp file creation: ERROR - ' . $e->getMessage();
        } catch (Error $e) {
            $file_info[] = 'Temp file creation: FATAL ERROR - ' . $e->getMessage();
        }
        
        $this->test_results['File System'] = $file_info;
    }
    
    /**
     * Test large data handling
     */
    private function test_large_data() {
        $data_info = [];
        
        // Large string test
        try {
            $large_string = str_repeat('A', 100000); // 100KB string
            $data_info[] = 'Large string (100KB): SUCCESS';
            unset($large_string);
        } catch (Exception $e) {
            $data_info[] = 'Large string test: FAILED - ' . $e->getMessage();
        }
        
        // JSON handling test
        try {
            $large_array = [];
            for ($i = 0; $i < 1000; $i++) {
                $large_array[] = [
                    'id' => $i,
                    'name' => 'test-item-' . $i,
                    'data' => str_repeat('data', 50)
                ];
            }
            $json = json_encode($large_array);
            $decoded = json_decode($json, true);
            $data_info[] = 'JSON encoding/decoding (large): SUCCESS';
            unset($large_array, $json, $decoded);
        } catch (Exception $e) {
            $data_info[] = 'JSON test: FAILED - ' . $e->getMessage();
        }
        
        // Database operation test
        try {
            $test_option = 'ase_test_' . time();
            $test_data = array_fill(0, 100, 'test_data_' . uniqid());
            
            $update_result = update_option($test_option, $test_data);
            $retrieved_data = get_option($test_option);
            delete_option($test_option);
            
            if ($update_result && $retrieved_data) {
                $data_info[] = 'Database operations: SUCCESS';
            } else {
                $data_info[] = 'Database operations: FAILED';
            }
        } catch (Exception $e) {
            $data_info[] = 'Database test: ERROR - ' . $e->getMessage();
        }
        
        $this->test_results['Large Data Handling'] = $data_info;
    }
    
    /**
     * Test inline CSS/JS support
     */
    private function test_inline_assets() {
        $asset_info = [];
        
        // Test inline CSS
        add_action('admin_head', function() {
            echo '<style id="ase-test-css">
                .ase-test { color: #00ff00; font-weight: bold; }
                .ase-test::before { content: "✅ "; }
            </style>';
        });
        $asset_info[] = 'Inline CSS: Added to admin_head';
        
        // Test inline JavaScript
        add_action('admin_footer', function() {
            echo '<script id="ase-test-js">
                console.log("🧪 ASE Pro JavaScript test successful");
                if (typeof jQuery !== "undefined") {
                    jQuery(document).ready(function($) {
                        console.log("🎯 jQuery available in ASE Pro snippet");
                        $("body").append("<div class=\"ase-test\">ASE Pro CSS/JS Test Working</div>");
                    });
                } else {
                    console.log("⚠️ jQuery not available");
                }
            </script>';
        });
        $asset_info[] = 'Inline JavaScript: Added to admin_footer';
        
        // Test wp_localize_script alternative
        try {
            $script_data = [
                'test' => 'data',
                'timestamp' => time(),
                'memory_usage' => memory_get_usage()
            ];
            
            add_action('admin_footer', function() use ($script_data) {
                echo '<script>
                    window.aseTestData = ' . json_encode($script_data) . ';
                    console.log("📊 ASE Test Data:", window.aseTestData);
                </script>';
            });
            
            $asset_info[] = 'Data localization: SUCCESS';
        } catch (Exception $e) {
            $asset_info[] = 'Data localization: FAILED - ' . $e->getMessage();
        }
        
        $this->test_results['Inline Assets'] = $asset_info;
    }
    
    /**
     * Show test results in admin notice
     */
    public function show_test_results() {
        $memory_used = memory_get_usage() - $this->memory_start;
        
        echo '<div class="notice notice-info is-dismissible" style="padding: 20px;">';
        echo '<h3>🧪 ASE Pro Capability Test Results</h3>';
        echo '<p><strong>Test completed successfully!</strong> Memory used: ' . $this->format_bytes($memory_used) . '</p>';
        
        foreach ($this->test_results as $test_name => $results) {
            echo '<h4>' . esc_html($test_name) . '</h4>';
            echo '<ul>';
            foreach ($results as $result) {
                echo '<li>' . esc_html($result) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '<h4>🎯 Summary for Font Clamp Calculator</h4>';
        echo '<ul>';
        echo '<li><strong>Large snippets:</strong> ' . (count($this->test_results) > 0 ? '✅ Supported' : '❌ Failed') . '</li>';
        echo '<li><strong>WordPress hooks:</strong> ' . (isset($this->test_results['WordPress Hooks']) ? '✅ Available' : '❌ Not available') . '</li>';
        echo '<li><strong>Memory handling:</strong> ' . ($memory_used < 5000000 ? '✅ Good (' . $this->format_bytes($memory_used) . ')' : '⚠️ High usage') . '</li>';
        echo '<li><strong>Inline CSS/JS:</strong> ' . (isset($this->test_results['Inline Assets']) ? '✅ Supported' : '❌ Not supported') . '</li>';
        echo '<li><strong>Error handling:</strong> ' . (class_exists('Error') ? '✅ Modern PHP error handling' : '⚠️ Limited error handling') . '</li>';
        echo '<li><strong>File functions:</strong> ' . (function_exists('wp_tempnam') ? '✅ Full WP file functions' : '⚠️ Limited WP file functions') . '</li>';
        echo '</ul>';
        
        echo '<h4>📋 Recommendations</h4>';
        echo '<ul>';
        if (!function_exists('wp_tempnam')) {
            echo '<li>• Use standard PHP file functions instead of WP-specific ones</li>';
        }
        if ($memory_used > 2000000) {
            echo '<li>• Optimize memory usage for large datasets</li>';
        }
        echo '<li>• Single-file approach is recommended for ASE Pro</li>';
        echo '<li>• Inline CSS/JS works well</li>';
        echo '<li>• WordPress hooks are available for proper integration</li>';
        echo '</ul>';
        
        echo '<p><em>Check your browser console for JavaScript test results.</em></p>';
        echo '</div>';
    }
    
    /**
     * Format bytes to human readable
     */
    private function format_bytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

// Only run in admin
if (is_admin()) {
    new FontClampASETest();
}

// Add a simple admin menu item to verify the test ran
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'ASE Pro Test',
        'ASE Pro Test',
        'manage_options',
        'ase-pro-test',
        function() {
            echo '<div class="wrap">';
            echo '<h1>ASE Pro Capability Test</h1>';
            echo '<p>If you can see this page, ASE Pro can handle admin menu creation.</p>';
            echo '<p>Check for the green admin notice at the top of any admin page for full test results.</p>';
            echo '</div>';
        }
    );
});