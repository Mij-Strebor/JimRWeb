# Media Inventory Forge - WordPress Plugin
<img src="..\..\resources\00-assets\design-assets\icons\svg\JimRWeb-logo-black.svg" alt="JimRWeb logo" style="height: 15px; width:100px;"/>

**Professional media library scanner and analyzer WordPress plugin for developers and agencies**

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/jimrweb/media-inventory-forge)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/license-GPL%20v2%2B-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)

> **Comprehensive Media Library Analysis Plugin**
> Professional WordPress plugin to scan, analyze, and optimize your media library with detailed insights, storage breakdowns, and actionable reporting capabilities.

![Media Inventory](./resources/reference-materials/media.png) 

## ✨ Why Media Inventory Forge?

Every WordPress site accumulates media files over time, but understanding what you have, where storage is being consumed, and which files need optimization becomes nearly impossible without proper tools. Media Inventory Forge provides comprehensive analysis of your entire media library with beautiful visualizations and actionable insights through a professionally architected WordPress plugin.

### 🎯 Perfect For
- **WordPress Developers** optimizing client sites with detailed media audits
- **Agency Teams** managing multiple WordPress installations efficiently
- **Site Administrators** planning storage and cleanup strategies
- **Performance Specialists** identifying optimization opportunities
- **WordPress Professionals** needing comprehensive media library insights

---

## 🚀 Key Features

### **📊 Comprehensive Analysis**
- **File Categorization**: Images, SVG, Fonts, Videos, Audio, Documents, PDFs
- **Storage Breakdown**: Total usage by category with detailed file counts
- **Image Type Analysis**: JPG, PNG, WEBP, GIF breakdown with sample previews
- **WordPress Size Analysis**: Detailed breakdown of thumbnail, medium, large sizes

### **⚡ Developer-Friendly Interface**
- **Progressive Scanning**: Handles thousands of files with batch processing
- **Live Thumbnails**: Visual previews of all images with hover effects
- **Professional Styling**: Beautiful JimRWeb design system integration
- **Responsive Design**: Works perfectly on mobile and desktop
- **CSV Export**: Detailed reports for analysis and documentation
- **Error Handling**: Graceful handling of missing files and corrupted media
- **Real-time Progress**: Visual progress bars with status updates

### **🗃️ Professional Plugin Architecture**
- **Object-Oriented Design**: Modular class structure for maintainability
- **WordPress Standards**: Proper hooks, nonces, sanitization, and security
- **Performance Optimized**: Batch processing with memory and timeout management
- **Extensible Framework**: Clean architecture for future enhancements
- **Error Logging**: Comprehensive debugging and monitoring capabilities
- **Memory Management**: Intelligent resource usage for large media libraries

---

## 🎮 Quick Start Guide

### 1. Install & Activate Plugin

#### Option A: Direct Installation
1. **Download** the plugin files
2. **Upload** to `/wp-content/plugins/media-inventory-forge/`
3. **Activate** the plugin through WordPress admin

#### Option B: Development Setup
1. **Clone** the repository to your plugins directory
2. **Set up symbolic link** for development workflow (see Development section)
3. **Activate** through WordPress admin

### 2. Access Media Inventory Forge
Navigate to: **J Forge → Media Inventory** in your WordPress admin

### 3. Run Your First Scan

```
Default Scan Settings:
✅ Batch Size: 10 files per request
✅ Timeout: 30 seconds per batch
✅ Categories: All media types
✅ Thumbnails: Automatic generation
✅ Progress Tracking: Real-time updates
✅ Memory Management: Intelligent resource monitoring
```

### 4. Analyze Results
- **Storage Summary**: See total usage by category with visual breakdowns
- **Image Analysis**: Review type and dimension breakdowns with WordPress size categories
- **Individual Files**: Examine detailed file information with thumbnails
- **Export Data**: Generate comprehensive CSV reports for further analysis

---

## 💡 Advanced Usage

### Storage Optimization Workflow
Use Media Inventory Forge to identify optimization opportunities:

| File Type | Optimization Strategy | Plugin Insights |
|-----------|----------------------|-----------------|
| Large JPGs | Convert to WEBP or compress | Shows original vs. generated sizes |
| Oversized PNGs | Optimize or convert to JPG | Identifies compression candidates |
| Unused Dimensions | Remove unnecessary image sizes | WordPress size analysis |
| Duplicate Files | Identify and consolidate | File pattern recognition |
| Old Formats | Modernize to current standards | Format distribution analysis |

### WordPress Size Categories Analysis
The plugin provides detailed breakdown of WordPress generated image sizes:

```
WordPress Image Size Distribution:
├── Thumbnails (≤150px): 245 files = 1.8 MB
├── Small (151-300px): 89 files = 3.2 MB
├── Medium (301-768px): 156 files = 12.4 MB
├── Large (769-1024px): 67 files = 8.9 MB
├── Extra Large (1025-1536px): 34 files = 15.2 MB
└── Super Large (>1536px): 23 files = 45.6 MB
```

### Performance Analysis Reports
```php
// Comprehensive site analysis output:
Total Media Library: 127.4 MB
├── Images: 89.3 MB (70%) - 612 files
├── Documents: 23.1 MB (18%) - 45 files  
├── Videos: 12.8 MB (10%) - 8 files
├── Fonts: 1.8 MB (1.4%) - 12 font families
└── Other: 0.4 MB (0.6%) - 15 files

Optimization Opportunities:
• Large uncompressed images: 23 files = 34.2 MB potential savings
• Legacy formats: 156 files = 12.8 MB potential savings
• Duplicate size generations: 89 files = 8.4 MB potential savings
```

---

## 🗃️ Technical Architecture

### Plugin Structure
```
media-inventory-forge/
├── media-inventory-forge.php      # Main plugin file
├── includes/
│   ├── core/                      # Core business logic
│   │   ├── class-scanner.php      # Batch processing engine
│   │   └── class-file-processor.php # Individual file analysis
│   ├── utilities/                 # Helper classes
│   │   └── class-file-utils.php   # File system utilities
│   └── admin/                     # Admin interface (expandable)
├── assets/                        # CSS/JS assets (ready for optimization)
├── templates/                     # Template system (future enhancement)
└── languages/                     # Internationalization (future)
```

### Core Classes

#### **MIF_Scanner**
- **Batch Processing**: Intelligent chunking for large media libraries
- **Memory Management**: Monitors and manages resource usage
- **Progress Tracking**: Real-time scan progress with statistics
- **Error Handling**: Comprehensive logging and graceful failure recovery

#### **MIF_File_Processor**
- **Individual File Analysis**: Detailed processing of each media item
- **WordPress Integration**: Native metadata and size variation handling
- **Category Classification**: Intelligent MIME type categorization
- **Performance Optimization**: Efficient file system operations

#### **MIF_File_Utils**
- **File System Operations**: Safe file access and validation
- **Format Utilities**: Size formatting and path sanitization
- **Security Validation**: Upload directory boundary checking
- **Cross-platform Compatibility**: Robust path handling

### Key Features for Developers

#### WordPress Best Practices
- **Proper sanitization and validation** of all inputs
- **Nonce verification** for AJAX security
- **Capability checks** for user permissions
- **Error logging** and graceful failure handling
- **Memory and timeout management** for shared hosting
- **Object-oriented architecture** following WordPress coding standards

#### Scanning Engine
- **Batch processing architecture** for libraries of any size
- **Progressive disclosure** of results with real-time updates
- **Thumbnail generation and caching** through WordPress APIs
- **File system safety checks** with validation
- **Metadata extraction and analysis** with WordPress integration
- **Performance monitoring** with execution statistics

#### Data Export & Reporting
- **CSV generation** with comprehensive file details
- **Thumbnail URL inclusion** for external analysis
- **File path mapping** for cleanup workflows
- **Size calculations** with human-readable formatting
- **WordPress size breakdown** for optimization planning

---

## 🧪 Browser Support & Compatibility

- **Modern Browsers**: Full support with all features
- **Mobile Devices**: Fully responsive interface
- **Accessibility**: Screen reader support and keyboard navigation
- **JavaScript**: ES6+ features with graceful degradation
- **WordPress**: 5.0+ compatibility with multisite support

## 📊 Performance Specifications
- **Batch Size**: 10 files per request (configurable)
- **Memory Usage**: Optimized for shared hosting environments
- **Timeout Handling**: 30-second request limits with monitoring
- **File Support**: All WordPress media types and custom uploads
- **Scalability**: Tested with libraries containing 10,000+ files

---

## 🔧 Development Setup

### Development Workflow
Media Inventory Forge uses a professional development setup with symbolic links for seamless git integration:

```bash
# Development location (git repository)
E:\OneDrive\WordPress Project Data\JimRWeb\plugin-projects\52-media-inventory\code

# WordPress location (symbolic link)
[Local-Site-Path]\wp-content\plugins\media-inventory-forge\

# Create symbolic link (Windows, run as Administrator)
mklink /D "[WordPress-Path]\plugins\media-inventory-forge" "[Git-Repo-Path]\code"
```

### Benefits of This Setup
- **Edit in your preferred location** (OneDrive, git repository)
- **WordPress sees changes instantly** - no file copying needed
- **Version control integration** - commit directly from your repository
- **Professional workflow** - standard development practice

### Local Development Requirements
- **Local by Flywheel** or similar WordPress development environment
- **PHP 7.4+** for plugin compatibility
- **WordPress 5.0+** for proper plugin integration
- **Git** for version control (recommended)

---

## 📚 Use Cases & Examples

### Agency Workflows
Perfect for comprehensive client site audits:
```
Client Site Audit Report - Media Inventory Forge Analysis
═══════════════════════════════════════════════════════

📊 Library Overview:
   • Total Files: 1,247 media items
   • Storage Used: 127.4 MB
   • File Categories: 7 types identified
   • WordPress Sizes: 6 size categories analyzed

🎯 Optimization Potential: 34.2 MB (27% reduction possible)
   • Largest Category: Images (70% of storage)
   • Optimization Targets: 156 uncompressed PNGs
   • Quick Wins: WEBP conversion = ~20 MB savings

📈 Recommended Actions:
   1. Convert large PNGs to WEBP format
   2. Remove unused thumbnail sizes
   3. Implement progressive JPEG compression
   4. Clean up duplicate size generations
```

### Performance Optimization Workflow
Systematic approach to site optimization:
```
Performance Impact Analysis - Media Inventory Forge
════════════════════════════════════════════════════

🔍 Bottleneck Identification:
   • Large Images (>1MB): 23 files = 45.6 MB
   • Uncompressed PNGs: 89 files = 34.2 MB  
   • Legacy Formats: 156 files = 12.8 MB
   • Oversized Thumbnails: 245 files = 8.9 MB

⚡ Optimization Priority Queue:
   1. Convert 89 PNGs → Save ~20 MB (High Impact)
   2. Resize 23 large images → Save ~15 MB (Medium Impact)
   3. Update 156 legacy formats → Save ~8 MB (Low Impact)
   4. Cleanup thumbnails → Save ~4 MB (Maintenance)

📊 Expected Results:
   • Total Savings: ~47 MB (37% reduction)
   • Load Time Improvement: 2-4 seconds
   • Storage Optimization: 37% space reclaim
```

### Content Management Insights
Understanding your media library composition:
```php
Content Distribution Analysis - Media Inventory Forge
══════════════════════════════════════════════════════

📁 Category Breakdown:
├── Images (612 files - 89.3 MB)
│   ├── Product Photos: 245 files (45.2 MB)
│   ├── Blog Graphics: 123 files (23.1 MB)
│   ├── Marketing Assets: 89 files (12.8 MB)
│   └── User Uploads: 155 files (8.2 MB)
│
├── Documents (45 files - 23.1 MB)
│   ├── PDFs: 34 files (18.9 MB)
│   └── Office Docs: 11 files (4.2 MB)
│
├── Fonts (24 files - 1.8 MB)
│   ├── 12 Font Families Identified
│   └── WOFF2, TTF, OTF formats
│
└── Other (23 files - 0.4 MB)
    └── SVG, XML, misc files
```

---

## 🔄 Version History

### Version 2.0.0 (Current)
🎉 **Major Architecture Overhaul**
- **Plugin Conversion**: Transformed from code snippet to professional WordPress plugin
- **Object-Oriented Architecture**: Modular class structure for maintainability
- **Enhanced Performance**: Improved memory management and batch processing
- **Professional Development Workflow**: Symbolic link integration with git repositories
- **Improved Error Handling**: Comprehensive logging and validation
- **WordPress Standards Compliance**: Following official coding guidelines

### Version 1.0.0 (Legacy)
- Initial code snippet release
- Basic media scanning functionality
- CSV export capabilities
- JimRWeb design integration

---

## 📄 License

This project is licensed under the **GPL v2 or later** - see the [LICENSE](LICENSE) file for details.

**Why GPL v2+?**
- **WordPress Compatibility**: Matches WordPress core licensing
- **Community Friendly**: Encourages open source development
- **Plugin Repository Ready**: Compatible with WordPress.org submission requirements

---

## 🙏 Acknowledgments

- **Jim R.** ([JimRWeb](https://jimrweb.com)) - Original concept, development, and professional design system
- **Claude AI** ([Anthropic](https://anthropic.com)) - Development assistance, architecture guidance, and code review
- **WordPress Community** - Inspiration, best practices, and coding standards
- **Local by Flywheel** - Development environment enabling rapid iteration

---

## 📞 Support & Development

### Community Support
- **GitHub Issues**: [Report bugs and request features](https://github.com/jimrweb/media-inventory-forge/issues)
- **GitHub Discussions**: [Community discussion and support](https://github.com/jimrweb/media-inventory-forge/discussions)  
- **JimRWeb Website**: [Professional services and consulting](https://jimrweb.com)

### Professional Development
- **Custom Development**: Available for agency-specific features
- **WordPress Consultation**: Performance optimization and architecture review
- **Training Services**: WordPress development best practices

### Contributing
We welcome contributions! Please read our contributing guidelines:
1. **Fork the repository** and create a feature branch
2. **Follow WordPress coding standards** throughout
3. **Include comprehensive testing** for new features
4. **Document all changes** with clear commit messages
5. **Submit pull requests** with detailed descriptions

---

<div align="center">

**Made with ❤️ for the WordPress developer community**

[⭐ Star this repo](https://github.com/jimrweb/media-inventory-forge) if it helps optimize your projects!

**Professional WordPress Plugin Development** | **JimRWeb.com**

</div>