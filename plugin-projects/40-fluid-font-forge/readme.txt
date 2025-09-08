=== Fluid Font Forge ===
Contributors: jimrweb
Tags: typography, fonts, responsive, clamp, css
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress admin plugin for generating CSS clamp() functions for responsive typography. Provides a calculator interface for creating fluid font scaling between viewport sizes. Advanced fluid typography calculator with CSS clamp() generation for responsive font scaling.

== Description ==

Fluid Font Forge generates CSS clamp() functions that allow font sizes to scale smoothly between minimum and maximum viewport widths. The plugin provides:

1. Mathematical scaling ratios for typography hierarchy
2. Real-time preview at minimum and maximum viewport sizes
3. Four output formats: CSS classes, CSS custom properties, HTML tags, and Tailwind config
4. Custom naming of items
5. Dynamic font size item list: add, delete, edit
6. Drag-and-drop reordering of font sizes
7. Configurable line heights

== Basic Configuration ==
1. Set Viewport Range: Configure minimum and maximum viewport widths (default: 375px to 1620px)
2. Set Font Size Range: Configure root font sizes at each viewport (default: 16px to 20px)
3. Choose Scaling Ratios: Select mathematical ratios for typography scaling
4. Select Base Size: Choose which size represents your base font size

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/fluid-font-forge/`
2. Activate the plugin through the 'Plugins' screen in WordPress admin
3. Navigate to J Forge > Fluid Font in the admin menu

== Frequently Asked Questions ==

= How do I use the generated CSS? =

Copy the generated CSS from the plugin interface and paste it into your theme's stylesheet.

== Changelog ==

= 1.0.0 =
* Initial release