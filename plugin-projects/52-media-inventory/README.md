# Media Inventory Forge - WordPress Plugin

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org/)
[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/jimrweb/media-inventory-forge/releases)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/license-GPL%20v2%2B-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)

> **🔍 Professional WordPress Media Library Analysis Plugin**
> 
> Comprehensive media library scanner and analyzer for WordPress developers, agencies, and site administrators. Provides detailed insights, storage breakdowns, and actionable optimization recommendations.

![Media Inventory Forge Screenshot](docs/screenshots/media.png)

---

## 🚀 Quick Install

### For WordPress Users

#### Method 1: Download from GitHub Releases (Recommended)
1. **Go to [Releases](https://github.com/jimrweb/media-inventory-forge/releases)**
2. **Download** the latest `media-inventory-forge.zip`
3. **WordPress Admin** → **Plugins** → **Add New** → **Upload Plugin**
4. **Choose downloaded ZIP** → **Install Now** → **Activate**

#### Method 2: Direct Repository Clone
```bash
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/jimrweb/media-inventory-forge.git
```

### For Developers

```bash
# Clone for development
git clone https://github.com/jimrweb/media-inventory-forge.git
cd media-inventory-forge

# Set up symbolic link for development (Windows)
mklink /D "C:\path\to\wordpress\wp-content\plugins\media-inventory-forge" "C:\path\to\this\repository"
```

---

## ✨ Why Media Inventory Forge?

Every WordPress site accumulates media files over time, but understanding what you have, where storage is being consumed, and which files need optimization becomes nearly impossible without proper tools. Media Inventory Forge provides comprehensive analysis of your entire media library with beautiful visualizations and actionable insights.

### 🎯 Perfect For
- **WordPress Developers** optimizing client sites with detailed media audits
- **Agency Teams** managing multiple WordPress installations efficiently
- **Site Administrators** planning storage and cleanup strategies
- **Performance Specialists** identifying optimization opportunities
- **WordPress Professionals** needing comprehensive media library insights

---

## 🎮 Quick Start

### 1. Install & Activate
- Download from [Releases](https://github.com/jimrweb/media-inventory-forge/releases)
- Upload via WordPress Admin or extract to `/wp-content/plugins/`
- Activate through WordPress admin

### 2. Access Plugin
Navigate to: **J Forge → Media Inventory** in your WordPress admin

### 3. Run First Scan
```
Default Settings:
✅ Batch Size: 10 files per request
✅ Timeout: 30 seconds per batch  
✅ Categories: All media types
✅ Progress: Real-time tracking
```

### 4. Analyze Results
- **Storage Summary** with visual breakdowns
- **WordPress Size Analysis** (thumbnails, medium, large)
- **Professional CSV exports** for further analysis

---

## 🚀 Key Features

### 📊 Comprehensive Analysis
- **File Categorization**: Images, SVG, Fonts, Videos, Audio, Documents, PDFs
- **Storage Breakdown**: Total usage by category with detailed file counts
- **WordPress Size Analysis**: Detailed breakdown of thumbnail, medium, large sizes
- **Performance Impact**: Shows how media affects site speed

### ⚡ Professional Interface
- **Progressive Scanning**: Handles thousands of files with batch processing
- **Live Thumbnails**: Visual previews with hover effects
- **Memory Optimized**: Intelligent resource management
- **Error Handling**: Graceful handling of missing/corrupted files
- **Real-time Progress**: Visual progress bars with statistics

### 🗃️ Enterprise Architecture
- **Object-Oriented Design**: Modular class structure for maintainability
- **WordPress Standards**: Proper hooks, nonces, sanitization, security
- **Performance Optimized**: Memory and timeout management
- **Extensible Framework**: Clean architecture for future enhancements

---

## 💡 Advanced Usage

### Storage Optimization Workflow

| File Type | Strategy | Plugin Insights |
|-----------|----------|-----------------|
| Large JPGs | Convert to WEBP | Shows original vs. generated sizes |
| Oversized PNGs | Optimize compression | Identifies compression candidates |
| Unused Sizes | Remove unnecessary thumbnails | WordPress size analysis |
| Legacy Formats | Modernize to current standards | Format distribution analysis |

### WordPress Size Categories
```
WordPress Image Size Distribution:
├── Thumbnails (≤150px): 245 files = 1.8 MB
├── Small (151-300px): 89 files = 3.2 MB
├── Medium (301-768px): 156 files = 12.4 MB
├── Large (769-1024px): 67 files = 8.9 MB
├── Extra Large (1025-1536px): 34 files = 15.2 MB
└── Super Large (>1536px): 23 files = 45.6 MB
```

---

## 🏗️ Technical Architecture

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
│   └── admin/                     # Admin interface
│       └── class-admin.php        # Asset management
├── assets/                        # CSS/JS assets
│   ├── css/admin.css              # Professional styling
│   └── js/admin.js                # Interactive functionality
└── templates/                     # Template system
    └── admin/                     # Admin page templates
```

### Core Classes

- **`MIF_Scanner`**: Batch processing with memory management
- **`MIF_File_Processor`**: Individual file analysis and WordPress integration  
- **`MIF_File_Utils`**: File system utilities and security validation
- **`MIF_Admin`**: Professional asset management and enqueueing

---

## 📊 Performance Specifications

- **Batch Size**: 10 files per request (configurable)
- **Memory Usage**: Optimized for shared hosting
- **Timeout Handling**: 30-second limits with monitoring
- **File Support**: All WordPress media types
- **Scalability**: Tested with 10,000+ file libraries

---

## 🛠️ Development

### Requirements
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **Development Environment**: Local by Flywheel recommended

### Setup for Contributions
```bash
# Clone repository
git clone https://github.com/jimrweb/media-inventory-forge.git
cd media-inventory-forge

# Create feature branch
git checkout -b feature/your-feature-name

# Make changes, test, commit
git commit -m "Add: Your feature description"

# Push and create pull request
git push origin feature/your-feature-name
```

### Development Workflow
This plugin uses a professional development setup with symbolic links:

```bash
# Create symbolic link (Windows - run as Administrator)
mklink /D "[WordPress-Path]\plugins\media-inventory-forge" "[Git-Repo-Path]"

# Create symbolic link (Mac/Linux)  
ln -s /path/to/git/repo /path/to/wordpress/wp-content/plugins/media-inventory-forge
```

**Benefits**:
- Edit in your preferred location with git integration
- WordPress sees changes instantly
- Professional development workflow

---

## 📚 Use Cases & Examples

### Agency Client Audits
```
Site Audit Report - Media Inventory Forge
═══════════════════════════════════════════

📊 Library Overview:
   • Total Files: 1,247 media items
   • Storage Used: 127.4 MB  
   • Categories: 7 types identified
   • WordPress Sizes: 6 categories analyzed

🎯 Optimization Potential: 34.2 MB (27% reduction)
   • Priority: Convert 156 uncompressed PNGs
   • Quick Win: WEBP conversion = ~20 MB savings

📈 Recommended Actions:
   1. Convert large PNGs to WEBP format
   2. Remove unused thumbnail sizes  
   3. Implement progressive JPEG compression
```

### Performance Optimization
```
Performance Impact Analysis
════════════════════════════

🔍 Bottlenecks Identified:
   • Large Images (>1MB): 23 files = 45.6 MB
   • Uncompressed PNGs: 89 files = 34.2 MB
   • Legacy Formats: 156 files = 12.8 MB

⚡ Optimization Priority:
   1. Convert 89 PNGs → Save ~20 MB (High Impact)
   2. Resize 23 large images → Save ~15 MB  
   3. Update legacy formats → Save ~8 MB

📊 Expected Results: ~47 MB savings (37% reduction)
```

---

## 🔄 Version History

### Version 2.0.0 (Latest)
🎉 **Major Architecture Overhaul**
- **Plugin Conversion**: Code snippet → Professional WordPress plugin
- **Object-Oriented Architecture**: Modular class structure
- **Enhanced Performance**: Improved memory management
- **WordPress Standards**: Complete compliance
- **Professional Workflow**: Git integration and symbolic links

### Version 1.0.0 (Legacy)
- Initial code snippet release
- Basic scanning functionality
- CSV export capabilities

---

## 🤝 Contributing

We welcome contributions! Here's how:

### Bug Reports & Feature Requests
- **Issues**: [Report bugs or request features](https://github.com/jimrweb/media-inventory-forge/issues)
- **Discussions**: [General questions and ideas](https://github.com/jimrweb/media-inventory-forge/discussions)

### Development Contributions
1. **Fork** the repository
2. **Create feature branch**: `git checkout -b feature/amazing-feature`
3. **Follow WordPress coding standards**
4. **Test thoroughly** on different WordPress versions
5. **Commit with clear messages**: `git commit -m 'Add: Amazing feature'`
6. **Push to branch**: `git push origin feature/amazing-feature`
7. **Open Pull Request** with detailed description

### Development Guidelines
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Include comprehensive testing for new features
- Update documentation for any user-facing changes
- Ensure backward compatibility when possible

---

## 📄 License

**GPL v2 or later** - [License Details](LICENSE)

**Why GPL v2+?**
- WordPress compatibility and community standards
- Encourages open source development and contributions
- Compatible with WordPress.org submission requirements

---

## 🙏 Acknowledgments

- **[Jim R.](https://jimrweb.com)** - Original concept, development, and JimRWeb design system
- **[Claude AI](https://anthropic.com)** - Development assistance and architecture guidance
- **WordPress Community** - Inspiration, best practices, and coding standards
- **Contributors** - Everyone who helps improve this plugin

---

## 📞 Support & Professional Services

### Community Support
- **GitHub Issues**: Bug reports and feature requests
- **GitHub Discussions**: Community Q&A and general discussion
### Professional Services by JimRWeb
- **Custom Plugin Development**: Tailored solutions if they're interesting
- **WordPress Websites for Twelve Step Groups:** Elementor or Bricks based solutions for recovery orginizations or individuals
- **WordPress Performance Optimization**: Complete site analysis and optimization for a Twelve Step orginization

**Contact**: [JimRWeb.com](https://jimrweb.com) for professional services

---

<div align="center">

**🚀 Made with ❤️ for the WordPress Community**

[⭐ Star this repository](https://github.com/jimrweb/media-inventory-forge) • [🐛 Report Issues](https://github.com/jimrweb/media-inventory-forge/issues) • [💬 Join Discussion](https://github.com/jimrweb/media-inventory-forge/discussions)

**Professional WordPress Plugin Development** | **[JimRWeb.com](https://jimrweb.com)**

</div>