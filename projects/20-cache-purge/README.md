# Enhanced Cache Purge - WordPress Admin Code Snippet
<img src="..\..\resources\00-assets\design-assets\icons\svg\JimRWeb-logo-black.svg" alt="JimRWeb logo" style="height: 15px; width:100px;"/>

**Professional one-click cache clearing for WordPress administrators**

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/your-username/enhanced-cache-purge)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)

> **Enhanced Cache Purge**
> 
> A comprehensive admin bar button that allows WordPress administrators to manually purge all caches with a single click. Features modern UI, security protection, and support for all major caching plugins.

## ✨ Why Enhanced Cache Purge?

**Tired of hunting through multiple plugin interfaces to clear caches?** This tool puts cache clearing exactly where you need it - right in the admin bar. One click purges everything: WordPress object cache, plugin caches, OPcache, and file-based caches.

### 🎯 Perfect For
- **WordPress Developers** testing theme and plugin changes
- **Agency Teams** who need quick cache clearing during development  
- **Site Administrators** managing multiple caching systems
- **Performance Optimizers** working with complex caching setups
- **Anyone** who values efficiency over clicking through multiple dashboards

---

## 🚀 Key Features

### **🔐 Enterprise-Grade Security**
- **CSRF Protection**: WordPress nonce verification for all requests
- **Permission Checking**: Restricted to administrators with `manage_options` capability
- **Input Validation**: Comprehensive sanitization and error handling

### **🎯 Universal Cache Support**
```php
// Supported Cache Types
WordPress Object Cache    ✅
WP Super Cache            ✅
W3 Total Cache            ✅
WP Rocket                 ✅
LiteSpeed Cache           ✅
WP Fastest Cache          ✅
Autoptimize               ✅
OPcache                   ✅
File-based Caches         ✅
Rewrite Rules             ✅
```

### **⚡ Modern User Experience**
- **Admin Bar Integration**: Clean button placement in WordPress admin bar
- **Real-time Feedback**: Non-intrusive notifications with success/error states
- **Loading States**: Visual feedback during cache purging operations
- **Mobile Responsive**: Works perfectly on all screen sizes
- **AJAX-Powered**: No page refreshes required

### **🏗️ Professional Architecture**

- **WordPress Standards**: Proper hooks, nonces, sanitization, and logging
- **Object-Oriented**: Clean class structure with separation of concerns
- **Error Handling**: Comprehensive try/catch blocks with detailed logging
- **Internationalization**: Translation-ready with proper text domains
- **Performance Optimized**: Scripts load only when needed for admin users

---

## 🎮 Quick Start Guide

### 1. Installation
```php
// Add to your theme's functions.php or create as a code snippet.
include_once 'cache-purge.php';
```

### 2. Instant Access
- Look for **"Purge Cache"** button in your admin bar
- Click to get confirmation dialog
- Confirm to clear all caches instantly

### 3. Visual Feedback
```javascript
// Success notification shows:
"Cache cleared successfully! (WordPress Object Cache, WP Rocket, OPcache)"

// Error handling includes:
- Network timeouts
- Permission failures  
- Security check failures
```

---

## 💡 Advanced Usage

### Programmatic Cache Clearing
```php
// Clear all caches from your code
if (function_exists('enhanced_cache_purge_all')) {
    $cleared_types = enhanced_cache_purge_all();
    error_log('Cleared: ' . implode(', ', $cleared_types));
}

// Check user permissions
if (can_user_purge_cache()) {
    // User can purge cache
}
```

### Hook Integration
```php
// Add custom cache clearing logic
add_action('enhanced_cache_purge_complete', function($cleared_types) {
    // Your custom cache clearing logic here
    custom_cdn_purge();
    custom_redis_flush();
});
```

### Logging and Debugging
```php
// Enable detailed logging in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Logs will show:
// [Enhanced Cache Purge] Cache successfully purged. Types: WordPress Object Cache, WP Rocket (User ID: 1)
```

---

## 🏗️ Technical Architecture

### Key Components

#### Security Layer
- WordPress nonce verification
- Capability checking (`manage_options`)
- AJAX request validation
- Input sanitization

#### Cache Detection Engine
```php
// Smart plugin detection
if (function_exists('wp_rocket_clean_domain')) {
    wp_rocket_clean_domain();
    $cleared[] = __('WP Rocket', 'enhanced-cache-purge');
}

if (class_exists('LiteSpeed\Purge')) {
    do_action('litespeed_purge_all');
    $cleared[] = __('LiteSpeed Cache', 'enhanced-cache-purge');
}
```

#### User Interface
- CSS animations and loading states
- jQuery-powered AJAX interactions
- Responsive notification system
- Accessibility-compliant markup

#### Error Handling
- Network timeout protection (30-second limit)
- Graceful failure modes
- Detailed error logging for debugging
- User-friendly error messages

---

## 🧪 Compatibility

### WordPress Versions
- **WordPress 5.0+**: Full compatibility
- **Multisite**: Works on network and individual sites
- **PHP 7.4+**: Modern PHP syntax and features

### Supported Caching Plugins
| Plugin | Status | Method |
|--------|--------|---------|
| WP Super Cache | ✅ | `wp_cache_clear_cache()` |
| W3 Total Cache | ✅ | `w3tc_flush_all()` |
| WP Rocket | ✅ | `wp_rocket_clean_domain()` |
| LiteSpeed Cache | ✅ | `litespeed_purge_all` action |
| WP Fastest Cache | ✅ | `wpfc_clear_all_cache` action |
| Autoptimize | ✅ | `autoptimizeCache::clearall()` |
| Object Cache Pro | ✅ | WordPress Object Cache API |

### Server Requirements
- **PHP OPcache**: Optional but recommended
- **File System**: Write permissions for cache directories
- **WordPress**: Admin privileges required

---

## 📚 Implementation Examples

### Theme Integration
```php
// In your theme's functions.php
if (!class_exists('Enhanced_Cache_Purge')) {
    require_once get_template_directory() . '/includes/cache-purge.php';
}
```

### Plugin Development
```php
// Clear cache after important updates
add_action('save_post', function($post_id) {
    if (function_exists('enhanced_cache_purge_all')) {
        enhanced_cache_purge_all();
    }
});
```

### Custom Hook Integration
```php
// Add your own cache types
add_action('enhanced_cache_purge_complete', function($cleared_types) {
    // Clear Cloudflare cache
    if (class_exists('Cloudflare\API')) {
        cloudflare_purge_everything();
    }
    
    // Clear custom Redis cache
    if (class_exists('Redis')) {
        $redis = new Redis();
        $redis->flushAll();
    }
});
```

### Development Workflow
```php
// Auto-clear cache in development
if (WP_DEBUG) {
    add_action('wp_footer', function() {
        if (current_user_can('manage_options')) {
            enhanced_cache_purge_all();
        }
    });
}
```

---

## 🔧 Configuration Options

### Customizing Button Placement
```php
// Change admin bar priority
add_action('admin_bar_menu', function($wp_admin_bar) {
    // Your custom admin bar logic
}, 1000); // Higher priority than default 999
```

### Custom Notification Styling
```css
/* Override notification styles */
.cache-notification {
    background: #your-brand-color !important;
    border-radius: 8px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
}
```

### Debugging Configuration
```php
// Enable verbose logging
add_filter('enhanced_cache_purge_debug', '__return_true');

// Custom log location
add_filter('enhanced_cache_purge_log_file', function() {
    return WP_CONTENT_DIR . '/cache-purge.log';
});
```

---

## 🛡️ Security Features

### Protection Mechanisms
- **Nonce Verification**: Every AJAX request verified
- **Capability Checking**: Only administrators can trigger purges
- **Input Validation**: All POST data sanitized
- **Rate Limiting**: Built-in timeout protection

### Best Practices
```php
// The code follows WordPress security standards:
wp_verify_nonce($_POST['nonce'], 'purge_cache_nonce')  // CSRF protection
current_user_can('manage_options')                     // Permission check
wp_send_json_error()                                   // Secure JSON responses
```

---

## 📊 Performance Impact

### Minimal Footprint
- **Admin Only**: Scripts load only for logged-in administrators
- **Conditional Loading**: JavaScript/CSS only when admin bar is showing
- **Efficient Caching**: Leverages existing WordPress cache APIs
- **Memory Usage**: < 1MB memory impact

### Optimization Features
- **Script Minification**: Inline CSS/JS optimized for performance
- **Event Delegation**: Efficient jQuery event handling
- **Debounced Actions**: Prevents rapid-fire cache clearing

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- **Jim R.** ([JimRWeb](https://jimrweb.com)) - Original concept and development
- **Claude AI** ([Anthropic](https://anthropic.com)) - Development assistance and code review
- **WordPress Community** - Inspiration from existing caching solutions

---

## 📞 Support & Questions

- **Issues**: [GitHub Issues](https://github.com/your-username/enhanced-cache-purge/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your-username/enhanced-cache-purge/discussions)
- **Website**: [JimRWeb.com](https://jimrweb.com)

---

<div align="center">

**Made with ❤️ for WordPress developers who value efficiency**

[⭐ Star this repo](https://github.com/your-username/enhanced-cache-purge) if it saves you time!

</div>