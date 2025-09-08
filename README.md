# J Forge - WordPress Developer Tools
<img src="resources\00-assets\design-assets\icons\svg\jimrweb_banner.png" alt="JimRWeb logo"/>

**Professional WordPress development tools for modern developers**

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![WCAG](https://img.shields.io/badge/WCAG-2.1%20AA%2FAAA-green.svg)](https://www.w3.org/WAI/WCAG21/quickref/)

> A comprehensive collection of WordPress admin tools built as code snippets that solve real development challenges. From responsive design systems to performance optimization and accessibility compliance - everything you need for professional WordPress development.
> 
> The tool set exists currently as code snippets. They are being moved over to be full-scale, professional quality plugins. Look for the 🚀 mark for a superior WordPress plugin!

---

## 🎨 CSS Clamp Design System Tools

Transform your responsive design workflow with mathematically precise CSS scaling using modern `clamp()` functions.

### 📐 [Fluid Font Forge](https://github.com/Mij-Strebor/JimRWeb/blob/main/plugin-projects/40-fluid-font-forge/)
Generate responsive typography systems with mathematical precision. Create fluid font scaling that works beautifully from mobile to desktop using proven typographic ratios.


🚀 This is the first to be migrated to full WordPress plugin status.

**Features:**
- Typography scales (Minor Second, Major Third, Golden Ratio, etc.)
- Four output modes: CSS Classes, Custom Properties, HTML Tags, Tailwind Config
- Live preview with custom font loading
- Mathematical scaling with custom line heights
- Drag-and-drop reordering with autosave

### 📏 [Fluid Space Forge](https://github.com/Mij-Strebor/JimRWeb/blob/main/snippet-projects/41-fluid-space-forge/)
Create consistent, scalable spacing systems for margins, padding, and gaps. The perfect companion to Fluid Font Forge and Fluid Button Forge for complete design system harmony.

**Features:** 
- Responsive spacing with CSS `clamp()` functions
- Classes, Variables, and Utility class outputs
- Mathematical scaling ratios
- Dual unit support (px/rem)

 🛠️ (WORK IN PROGRESS) Exisits as a code snippet; professional WordPress plugin development underway.

### 🎨 [Fluid Button Forge](https://github.com/Mij-Strebor/JimRWeb/blob/main/snippet-projects/42-fluid-button-forge/)
Design professional button hierarchies that scale perfectly across all devices. Generate responsive button systems for CTAs, forms, and UI components.

 🛠️ (WORK IN PROGRESS) Exisits as a code snippet; professional WordPress plugin development is planned.

**Features:**
- Professional button hierarchies (small, medium, large)
- Framework-ready CSS for Elementor, Bricks, and custom themes
- CSS Classes and Custom Properties output
- Perfect touch targets across all devices

---

## 🔧 WordPress Management & Optimization Tools

Professional utilities for WordPress administration, performance optimization, and site management.

### 🧹 [Enhanced Cache Purge](./enhanced-cache-purge/)
Professional one-click cache clearing for WordPress administrators. A comprehensive admin bar button that purges all caches with a single click.

 🛠️ (WORK IN PROGRESS) Not yet available.

**Features:**
- Universal cache support (WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed, etc.)
- Enterprise-grade security with CSRF protection
- Real-time feedback with success/error notifications
- AJAX-powered with no page refreshes required
- Comprehensive error handling and logging

### 📊 [Media Inventory Forge](https://github.com/Mij-Strebor/JimRWeb/blob/main/snippet-projects/52-media-inventory/)
Professional media library scanner and analyzer. Scan, analyze, and optimize your WordPress media library with detailed insights and storage breakdowns.

**Features:**
- Comprehensive file categorization (Images, SVG, Fonts, Videos, Audio, Documents)
- Storage breakdown by category with visual analytics
- Progressive scanning with batch processing
- Live thumbnails with hover effects
- CSV export for detailed reporting
- Performance optimization recommendations

---

## 🎨 Elementor Integration Tools

Specialized tools for Elementor developers focusing on design systems, color management, and accessibility compliance.

### 🌈 [Elementor Color Inventory Forge](https://github.com/Mij-Strebor/JimRWeb/blob/main/snippet-projects/51-color-forge/)
Professional color management, analysis, and WCAG accessibility compliance tool. Complete inventory system for Elementor Global Colors with detailed analysis and usage tracking.

**Features:**
- **Complete Color Analysis**: System & Custom Colors with multi-format conversion (HEX, RGB, HSL)
- **WCAG Contrast Analysis**: Real-time accessibility compliance checker with AA/AAA standards
- **Smart Color Suggestions**: Intelligent recommendations while preserving brand identity
- **Usage Tracking**: Site-wide color usage detection and analytics
- **CSS Generation**: Four output modes (Variables, Classes, SCSS, Fallbacks)
- **Elementor CSS Capture**: Provides Delementor's unique CSS Variables naming of your Custom Colors
- **Educational Content**: Built-in WCAG guidelines and accessibility explanations

#### ♿ Accessibility Compliance Features
```
WCAG Compliance Levels Supported:
✅ WCAG AA Normal Text (4.5:1 contrast ratio)
✅ WCAG AA Large Text (3:1 contrast ratio)  
✅ WCAG AAA Normal Text (7:1 contrast ratio)
✅ WCAG AAA Large Text (4.5:1 contrast ratio)
```

---

## 🚀 Why These Tools?

As WordPress developers, we face common challenges: responsive design complexity, cache management across multiple plugins, media library optimization, color consistency, and accessibility compliance. These tools eliminate guesswork and deliver professional solutions using modern techniques.

**Perfect for:**
- **WordPress Theme Developers** building custom themes and design systems
- **Agency Developers** managing multiple client sites efficiently
- **Elementor Specialists** needing advanced color management and accessibility tools
- **Performance Optimizers** requiring comprehensive site analysis capabilities
- **Government/Enterprise Sites** needing WCAG accessibility compliance
- **Freelancers** wanting professional workflows and time-saving utilities
- **Anyone** tired of manual processes and inconsistent results



---




## 🏗️ Technical Architecture

### Professional WordPress Standards
- **Security**: Proper nonces, capability checks, input sanitization
- **Performance**: Optimized queries, minimal resource usage, efficient caching
- **Accessibility**: WCAG 2.1 AA/AAA compliance throughout
- **Internationalization**: Translation-ready with proper text domains
- **Integration**: Clean admin menu system under J Forge parent

### Modern Development Practices
- **Object-Oriented PHP**: Clean class structures with separation of concerns
- **ES6+ JavaScript**: Modern JavaScript with graceful degradation
- **AJAX Architecture**: Asynchronous processing for better user experience
- **Error Handling**: Comprehensive try/catch blocks with user-friendly messages
- **Responsive Design**: Mobile-first approach with desktop enhancements

### Code Quality Standards
- **WordPress Coding Standards**: Follows official guidelines
- **PSR Standards**: PHP standards compliance where applicable
- **Documentation**: Comprehensive inline documentation
- **Version Control**: Git-friendly structure with clear commit practices



---





## 📞 Support & Community

- **Issues**: [GitHub Issues](https://github.com/your-username/j-forge-toolkit/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your-username/j-forge-toolkit/discussions)
- **Website**: [JimRWeb.com](https://jimrweb.com)
- **Documentation**: [Complete Documentation](https://jimrweb.com/j-forge-toolkit)

---


<div align="center">

**Made with ❤️ for the WordPress developer community**

[⭐ Star this repo](https://github.com/your-username/j-forge-toolkit) if it helps your projects!

**J Forge - Professional WordPress Development Toolkit**

*Building better WordPress sites, one tool at a time* 🛠️♿🎨

</div>
