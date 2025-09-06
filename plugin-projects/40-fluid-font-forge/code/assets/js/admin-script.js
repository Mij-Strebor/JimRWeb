/**
 * Fluid Font Forge - Admin Interface Script
 *
 * Advanced fluid typography calculator with interactive controls,
 * real-time CSS clamp() generation, and responsive font previews.
 *
 * @package FluidFontForge
 * @version 4.0.0
 * @author Jim R (JimRWeb)
 * @link https://jimrweb.com
 * @since 1.0.0
 *
 * Dependencies:
 * - WordPress wp-util (jQuery, AJAX utilities)
 * - Tailwind CSS (loaded via CDN)
 *
 * Features:
 * - Interactive font size calculator with mathematical scaling
 * - Real-time preview with synchronized hover effects
 * - Drag-and-drop table row reordering
 * - Modal editing system with validation
 * - Copy-to-clipboard functionality with visual feedback
 * - Autosave system with status indicators
 * - Multi-tab interface (Classes, Variables, Tags, Tailwind)
 *
 * Based on original concept by Imran Siddiq (WebSquadron)
 * Enhanced and developed with Claude AI assistance
 */

/**
 * Tab Data Utilities - Embedded temporarily to fix 404 issue
 */
const TabDataMap = {
  class: {
    dataKey: "classSizes",
    nameProperty: "className",
    displayName: "Classes",
    tableTitle: "Font Size Classes",
    selectedCSSTitle: "Selected Class CSS",
    generatedCSSTitle: "Generated CSS (All Classes)",
    addButtonText: "add first class",
    baseDefaultValue: "medium",
    baseDefaultId: 5,
  },
  vars: {
    dataKey: "variableSizes",
    nameProperty: "variableName",
    displayName: "Variables",
    tableTitle: "CSS Variables",
    selectedCSSTitle: "Selected Variable CSS",
    generatedCSSTitle: "Generated CSS (All Variables)",
    addButtonText: "add first variable",
    baseDefaultValue: "--fs-md",
    baseDefaultId: 5,
  },
  tailwind: {
    dataKey: "tailwindSizes",
    nameProperty: "tailwindName",
    displayName: "Tailwind Sizes",
    tableTitle: "Tailwind Font Sizes",
    selectedCSSTitle: "Selected Size Config",
    generatedCSSTitle: "Tailwind Config (fontSize Object)",
    addButtonText: "add first size",
    baseDefaultValue: "base",
    baseDefaultId: 5,
  },
  tag: {
    dataKey: "tagSizes",
    nameProperty: "tagName",
    displayName: "Tags",
    tableTitle: "HTML Tag Styles",
    selectedCSSTitle: "Selected Tag CSS",
    generatedCSSTitle: "Generated CSS (All Tags)",
    addButtonText: "add first tag",
    baseDefaultValue: "p",
    baseDefaultId: 7,
  },
};

/*
 * Custom Event Names
 */
const FONTFORGE_EVENTS = {
  TAB_CHANGED: "fontforge:tab:changed",
  DATA_UPDATED: "fontforge:data:updated",
  SETTINGS_CHANGED: "fontforge:settings:changed",
  CORE_READY: "fontforge:core:ready",
  ADVANCED_READY: "fontforge:advanced:ready",
  SIZE_SELECTED: "fontforge:size:selected",
  CALCULATION_COMPLETE: "fontforge:calculation:complete",
};

// Utility functions for tab data management
// Centralizes logic for accessing tab-specific data and properties
const TabDataUtils = {
  getDataForTab(activeTab, data) {
    const config = TabDataMap[activeTab];
    return config && data ? data[config.dataKey] || [] : [];
  },

  getPropertyName(activeTab) {
    return TabDataMap[activeTab]?.nameProperty || "className";
  },

  getSizeDisplayName(size, activeTab) {
    const propertyName = this.getPropertyName(activeTab);
    return size[propertyName] || "";
  },

  getTableTitle(activeTab) {
    return TabDataMap[activeTab]?.tableTitle || "Font Sizes";
  },

  getSelectedCSSTitle(activeTab) {
    return TabDataMap[activeTab]?.selectedCSSTitle || "Selected CSS";
  },

  getGeneratedCSSTitle(activeTab) {
    return TabDataMap[activeTab]?.generatedCSSTitle || "Generated CSS";
  },

  getBaseDefaultValue(activeTab) {
    return TabDataMap[activeTab]?.baseDefaultValue || "medium";
  },
};

// Make available globally for other segments to use
window.TabDataMap = TabDataMap;
window.TabDataUtils = TabDataUtils;

// Simple JavaScript Tooltips
// Lightweight tooltip system for elements with data-tooltip attribute
class SimpleTooltips {
  constructor() {
    this.tooltip = null;
    this.init();
  }

  init() {
    document.addEventListener("mouseover", (e) => {
      if (e.target.dataset.tooltip) {
        this.showTooltip(e.target, e.target.dataset.tooltip);
      }
    });

    document.addEventListener("mouseout", (e) => {
      if (e.target.dataset.tooltip) {
        this.hideTooltip();
      }
    });
  }

  showTooltip(element, text) {
    this.hideTooltip();

    this.tooltip = document.createElement("div");
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

    let left =
      rect.left + window.scrollX + rect.width / 2 - tooltipRect.width / 2;
    let top = rect.top + window.scrollY - tooltipRect.height - 8;

    if (left < 5) left = 5;
    if (left + tooltipRect.width > window.innerWidth - 5) {
      left = window.innerWidth - tooltipRect.width - 5;
    }
    if (top < window.scrollY + 5) {
      top = rect.bottom + window.scrollY + 8;
    }

    this.tooltip.style.left = left + "px";
    this.tooltip.style.top = top + "px";
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

// Utility to get current sizes based on active tab and ensure defaults are loaded if none exist
// Requires access to fontClampAjax data and fontClampAdvanced instance
// Returns array of sizes for the active tab
const FontForgeUtils = {
  getCurrentSizes(activeTab = null, fontClampAdvanced = null) {
    const tab = activeTab || window.fontClampCore?.activeTab || "class";

    // Get data from localized script
    const data = window.fontClampAjax?.data || {};
    let sizes = TabDataUtils.getDataForTab(tab, data);

    // Handle defaults if needed and fontClampAdvanced instance is available
    // But don't restore if this tab was explicitly cleared by user
    const isExplicitlyCleared =
      window.fontClampAjax?.data?.explicitlyClearedTabs?.[tab];
    if (sizes.length === 0 && fontClampAdvanced && !isExplicitlyCleared) {
      switch (tab) {
        case "class":
          sizes = fontClampAdvanced.getDefaultClassSizes();
          if (window.fontClampAjax?.data) {
            window.fontClampAjax.data.classSizes = sizes;
          }
          break;
        case "vars":
          sizes = fontClampAdvanced.getDefaultVariableSizes();
          if (window.fontClampAjax?.data) {
            window.fontClampAjax.data.variableSizes = sizes;
          }
          break;
        case "tailwind":
          sizes = fontClampAdvanced.getDefaultTailwindSizes();
          if (window.fontClampAjax?.data) {
            window.fontClampAjax.data.tailwindSizes = sizes;
          }
          break;
        case "tag":
          sizes = fontClampAdvanced.getDefaultTagSizes();
          if (window.fontClampAjax?.data) {
            window.fontClampAjax.data.tagSizes = sizes;
          }
          break;
      }
    }

    return sizes;
  },
};

// Make FontForgeUtils available globally
window.FontForgeUtils = FontForgeUtils;

// WordPress Admin Notice System
// The WordPress Admin Notice System is a core feature that allows plugins, themes, and WordPress itself to display
// messages and notifications within the WordPress administration area. These notices serve various purposes,
// including:
// + Providing feedback on actions: Confirming successful operations (e.g., "Post updated"),
//   indicating errors (e.g., "Failed to save settings"), or giving warnings
//   (e.g., "Plugin update available").
// + Delivering important information: Notifying administrators about new features, security
//   updates, or critical issues.
// + Guiding users: Offering instructions or suggestions for using specific functionalities.
class WordPressAdminNotices {
  constructor() {
    this.notices = [];
    this.container = null;
    this.init();
  }

  init() {
    // Create notices container if it doesn't exist
    this.createContainer();
  }

  createContainer() {
    // Always create our own container at the very top of wrap
    const wrap = document.querySelector(".wrap");
    if (wrap) {
      // Remove any existing container first
      const existing = document.getElementById("fcc-admin-notices");
      if (existing) {
        existing.remove();
      }

      this.container = document.createElement("div");
      this.container.id = "fcc-admin-notices";
      this.container.style.cssText =
        "margin: 0 0 20px 0; position: relative; z-index: 1000;";

      // Insert at the very beginning, before any other content
      const firstChild = wrap.querySelector("h1") || wrap.firstChild;
      if (firstChild && firstChild.parentNode === wrap) {
        wrap.insertBefore(this.container, firstChild);
      } else {
        wrap.appendChild(this.container);
      }
    }
  }

  show(message, type = "info", dismissible = true, autoHide = true) {
    if (!this.container) {
      console.error("Admin notices container not available");
      return;
    }

    const notice = document.createElement("div");
    const noticeId =
      "notice-" + Date.now() + "-" + Math.random().toString(36).substr(2, 9);
    notice.id = noticeId;

    let classes = ["notice"];
    switch (type) {
      case "success":
        classes.push("notice-success");
        break;
      case "error":
        classes.push("notice-error");
        break;
      case "warning":
        classes.push("notice-warning");
        break;
      default:
        classes.push("notice-info");
        break;
    }

    if (dismissible) classes.push("is-dismissible");
    notice.className = classes.join(" ");

    notice.innerHTML = `
      <p style="margin: 0.5em 0;">${message}</p>
      ${
        dismissible
          ? '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
          : ""
      }
    `;

    this.container.appendChild(notice);

    // Handle dismiss button
    if (dismissible) {
      const dismissBtn = notice.querySelector(".notice-dismiss");
      if (dismissBtn) {
        dismissBtn.addEventListener("click", () => {
          this.dismiss(noticeId);
        });
      }
    }

    // Auto-hide after delay
    if (autoHide && type !== "error") {
      setTimeout(
        () => {
          this.dismiss(noticeId);
        },
        type === "success" ? 4000 : 6000
      );
    }

    // Scroll to notice for visibility
    notice.scrollIntoView({ behavior: "smooth", block: "nearest" });

    return noticeId;
  }

  dismiss(noticeId) {
    const notice = document.getElementById(noticeId);
    if (notice) {
      notice.style.transition = "opacity 0.3s ease, transform 0.3s ease";
      notice.style.opacity = "0";
      notice.style.transform = "translateX(-10px)";

      setTimeout(() => {
        if (notice.parentNode) {
          notice.parentNode.removeChild(notice);
        }
      }, 300);
    }
  }

  // Simple confirm dialog with callbacks
  confirm(message, onConfirm, onCancel = null) {
    const result = confirm(
      // replace <br> with newlines for better readability and strip HTML tags
      message.replace(/<br>/g, "\n").replace(/<[^>]*>/g, "")
    );

    if (result && onConfirm) {
      onConfirm();
    } else if (!result && onCancel) {
      onCancel();
    }
  }

  success(message, dismissible = true) {
    return this.show(message, "success", dismissible);
  }

  error(message, dismissible = true) {
    return this.show(message, "error", dismissible, false);
  }

  warning(message, dismissible = true) {
    return this.show(message, "warning", dismissible);
  }

  info(message, dismissible = true) {
    return this.show(message, "info", dismissible);
  }
}

// Enhanced Core Interface Controller
// Manages tabs, unit selection, and coordination with advanced features
// Initializes data from localized script and triggers events for other segments
// Handles toggling of expandable sections and loading sequence
// Ensures interface is only revealed when fully ready
// Syncs visual state of tabs and unit buttons
// Provides utility to get current sizes based on active tab
// Usage: new FontClampEnhancedCoreInterface()
// Returns: instance of FontClampEnhancedCoreInterface
// Example usage: const coreInterface = new FontClampEnhancedCoreInterface();
// Access current sizes: const sizes = coreInterface.getCurrentSizes();
// Switch tabs: coreInterface.switchTab('vars');
// Switch unit type: coreInterface.switchUnitType('rem');
// Update data: coreInterface.updateData({ settings: newSettings, classSizes: newClassSizes });
// Trigger custom hooks: coreInterface.triggerHook('customEvent', { key: value });
// Note: Requires FontForgeUtils to be defined
// Note: Requires fontClampAjax and fontClampAdvanced to be available globally
// Note: Designed to work within WordPress admin environment
// Note: Integrates with FontClampAdvanced for full functionality

class FontClampEnhancedCoreInterface {
  constructor() {
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
    this.loadingSteps.coreReady = true; // Mark core interface as ready
  }

  // ========================================================================
  // INITIALIZATION & DATA METHODS
  // ========================================================================

  // Toggle expandable sections
  // Uses direct event binding to ensure reliability
  // Handles multiple attempts to bind in case of delayed DOM availability
  // Toggles 'expanded' class on target content and button
  // Rotates icon to indicate expanded/collapsed state
  // Usage: Call bindToggleEvents() during initialization
  // Example: this.bindToggleEvents();
  // Note: Requires elements with data-toggle-target attributes on buttons
  // Note: Target content must have corresponding IDs (e.g., info-content, about-content)
  // Note: Icon within button should have class 'fcc-toggle-icon' for rotation
  // Note: Prevents default link behavior to avoid page jumps
  // Note: Ensures accessibility by allowing keyboard interaction
  // Note: Designed to work within WordPress admin environment
  bindToggleEvents() {
    // Wait for DOM to be ready and bind directly
    const bindWhenReady = () => {
      const infoToggle = document.querySelector(
        '[data-toggle-target="info-content"]'
      );
      const aboutToggle = document.querySelector(
        '[data-toggle-target="about-content"]'
      );

      infoToggle.addEventListener("click", (e) => {
        e.preventDefault();
        this.handleToggle(infoToggle, "info-content");
      });

      if (aboutToggle) {
        aboutToggle.addEventListener("click", (e) => {
          e.preventDefault();
          this.handleToggle(aboutToggle, "about-content");
        });
      }
    };

    // Try multiple times to ensure elements are available
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", bindWhenReady);
    } else {
      bindWhenReady();
    }

    // Also try after a delay as backup
    setTimeout(bindWhenReady, 500);
  }

  // Handle the toggle action
  //
  handleToggle(button, targetId) {
    const content = document.getElementById(targetId);

    if (!content || !button) {
      return;
    }

    const isExpanded = content.classList.contains("expanded");

    if (isExpanded) {
      content.classList.remove("expanded");
      button.classList.remove("expanded");
    } else {
      content.classList.add("expanded");
      button.classList.add("expanded");
    }

    // Also toggle the icon rotation
    const icon = button.querySelector(".fcc-toggle-icon");
    if (icon) {
      if (isExpanded) {
        icon.style.transform = "rotate(0deg)";
      } else {
        icon.style.transform = "rotate(180deg)";
      }
    }
  }

  // Manage loading sequence and interface reveal
  // Uses event listeners to track readiness of components
  // Implements timeout fallback to ensure interface is revealed eventually
  // Prevents flash of unstyled content by hiding interface until ready

  initLoadingSequence() {
    // Why loading steps: Users need visual feedback during complex initialization
    // Prevents flash of unstyled content and confusing intermediate states
    this.loadingSteps = {
      coreReady: false, // Why: Core interface must be ready first
      advancedReady: false, // Why: Advanced features needed for full functionality
      contentPopulated: false, // Why: Data must be loaded before revealing interface
    };

    // Why event listeners: Components signal readiness asynchronously
    // Can't predict which loads first in WordPress admin environment
    window.addEventListener("fontforge:advanced:ready", () => {
      this.loadingSteps.advancedReady = true;
      this.checkAndRevealInterface();
    });

    window.addEventListener("fontforge:data:updated", () => {
      this.loadingSteps.contentPopulated = true;
      this.checkAndRevealInterface();
    });

    setTimeout(() => {
      if (!this.isInterfaceRevealed()) {
        this.revealInterface();
      }
    }, 5000);
  }

  // Check if all loading steps are complete and reveal interface
  checkAndRevealInterface() {
    // Why wait for both: Revealing interface too early shows broken/empty state
    // Advanced features needed for interactions, content needed for display
    if (this.loadingSteps.advancedReady && this.loadingSteps.contentPopulated) {
      // Why 300ms delay: Allows final calculations to complete before reveal
      setTimeout(() => this.revealInterface(), 300);
    }
  }

  // Reveal the main interface and hide loading screen
  revealInterface() {
    if (this.isInterfaceRevealed()) return;

    const loadingScreen = document.getElementById("fcc-loading-screen");
    if (loadingScreen) {
      loadingScreen.classList.add("hidden");
    }

    const mainContainer = document.getElementById("fcc-main-container");
    if (mainContainer) {
      mainContainer.classList.add("ready");
    }

    const autosaveIcon = document.getElementById("autosave-icon");
    const autosaveText = document.getElementById("autosave-text");
    if (autosaveIcon && autosaveText) {
      autosaveIcon.textContent = "💾";
      autosaveText.textContent = "Ready";
    }

    // Create ARIA live region for screen reader announcements
    const ariaRegion = document.createElement("div");
    ariaRegion.id = "fcc-announcements";
    ariaRegion.setAttribute("aria-live", "polite");
    ariaRegion.setAttribute("aria-atomic", "false");
    ariaRegion.style.cssText =
      "position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;";
    document.body.appendChild(ariaRegion);
  }

  // Check if the interface is already revealed
  isInterfaceRevealed() {
    const mainContainer = document.getElementById("fcc-main-container");
    return mainContainer && mainContainer.classList.contains("ready");
  }

  // Sync visual state of tabs and unit buttons
  syncVisualState() {
    document.querySelectorAll("[data-tab]").forEach((tab) => {
      tab.classList.remove("active");
    });
    document
      .querySelector(`[data-tab="${this.activeTab}"]`)
      ?.classList.add("active");
  }

  // Initialize data from localized script
  initializeData() {
    // Get data from wp_localize_script
    const data = window.fontClampAjax?.data || {};

    this.settings = data.settings || {};
    this.classSizes = data.classSizes || [];
    this.variableSizes = data.variableSizes || [];
    this.tagSizes = data.tagSizes || [];
    this.tailwindSizes = data.tailwindSizes || [];

    this.activeTab = this.settings.activeTab || "class";
    this.unitType = this.settings.unitType || "px";
  }

  // ========================================================================
  // EVENT BINDING & SETUP METHODS
  // ========================================================================

  // Cache frequently accessed DOM elements
  // Improves performance and simplifies code by reducing repeated queries
  // Centralizes element references for easier maintenance and updates
  // Ensures elements are available before binding events (avoids null reference errors)
  cacheElements() {
    this.elements = {
      classTab: document.getElementById("class-tab"),
      varsTab: document.getElementById("vars-tab"),
      tagTab: document.getElementById("tag-tab"),
      pxTab: document.getElementById("px-tab"),
      remTab: document.getElementById("rem-tab"),
      tableTitle: document.getElementById("table-title"),
      selectedCodeTitle: document.getElementById("selected-code-title"),
      generatedCodeTitle: document.getElementById("generated-code-title"),
      previewMinContainer: document.getElementById("preview-min-container"),
      previewMaxContainer: document.getElementById("preview-max-container"),
      sizesTableWrapper: document.getElementById("sizes-table-wrapper"),
      classCode: document.getElementById("class-code"),
      generatedCode: document.getElementById("generated-code"),
      minViewportDisplay: document.getElementById("min-viewport-display"),
      maxViewportDisplay: document.getElementById("max-viewport-display"),
    };
  }

  // Bind basic event listeners for tab and unit switching
  // Uses optional chaining to avoid errors if elements are missing
  // Triggers calculation on input changes to keep data up-to-date
  bindBasicEvents() {
    this.elements.classTab?.addEventListener("click", () =>
      this.switchTab("class")
    );
    this.elements.varsTab?.addEventListener("click", () =>
      this.switchTab("vars")
    );

    // Force bind tailwind tab with direct query
    const tailwindTab = document.getElementById("tailwind-tab");
    if (tailwindTab) {
      tailwindTab.addEventListener("click", () => this.switchTab("tailwind"));
    }

    this.elements.tagTab?.addEventListener("click", () =>
      this.switchTab("tag")
    );

    this.elements.pxTab?.addEventListener("click", () =>
      this.switchUnitType("px")
    );
    this.elements.remTab?.addEventListener("click", () =>
      this.switchUnitType("rem")
    );

    document
      .getElementById("min-root-size")
      ?.addEventListener("input", () => this.triggerCalculation());
    document
      .getElementById("max-root-size")
      ?.addEventListener("input", () => this.triggerCalculation());
    document
      .getElementById("min-viewport")
      ?.addEventListener("input", () => this.triggerCalculation());
    document
      .getElementById("max-viewport")
      ?.addEventListener("input", () => this.triggerCalculation());
    document
      .getElementById("min-scale")
      ?.addEventListener("change", () => this.triggerCalculation());
    document
      .getElementById("max-scale")
      ?.addEventListener("change", () => this.triggerCalculation());
  }

  // Trigger calculation in advanced features segment
  // Dispatches custom event to notify advanced features of settings change
  triggerCalculation() {
    window.dispatchEvent(new CustomEvent("fontClamp_settingsChanged"));
    if (window.fontClampAdvanced && window.fontClampAdvanced.calculateSizes) {
      window.fontClampAdvanced.calculateSizes();
    }
  }

  // Bind enhanced event listeners for real-time display updates
  // Updates viewport size displays as sliders are adjusted
  // Uses optional chaining to avoid errors if elements are missing
  bindEnhancedEvents() {
    document.getElementById("min-viewport")?.addEventListener("input", (e) => {
      if (this.elements.minViewportDisplay) {
        this.elements.minViewportDisplay.textContent = e.target.value + "px";
      }
    });

    document.getElementById("max-viewport")?.addEventListener("input", (e) => {
      if (this.elements.maxViewportDisplay) {
        this.elements.maxViewportDisplay.textContent = e.target.value + "px";
      }
    });
  }

  // ========================================================================
  // TAB & UNIT MANAGEMENT METHODS
  // ========================================================================

  // Switch active tab and update interface accordingly
  switchTab(tabName) {
    this.activeTab = tabName;

    document.querySelectorAll("[data-tab]").forEach((tab) => {
      tab.classList.remove("active");
    });
    document.querySelector(`[data-tab="${tabName}"]`)?.classList.add("active");

    this.elements.tableTitle.textContent = TabDataUtils.getTableTitle(tabName);
    this.elements.selectedCodeTitle.textContent =
      TabDataUtils.getSelectedCSSTitle(tabName);
    this.elements.generatedCodeTitle.textContent =
      TabDataUtils.getGeneratedCSSTitle(tabName);

    if (typeof this.updateBaseValueDropdown === "function") {
      this.updateBaseValueDropdown(tabName);
    }

    this.triggerHook("tab:changed", {
      activeTab: tabName,
    });
  }

  // Update base value dropdown options based on active tab
  // Filters out custom sizes to keep list manageable
  updateBaseValueDropdown(tabName) {
    const baseValueSelect = document.getElementById("base-value");
    if (!baseValueSelect) {
      return;
    }

    baseValueSelect.innerHTML = "";

    const propertyName = TabDataUtils.getPropertyName(tabName);
    const defaultValue = TabDataUtils.getBaseDefaultValue(tabName);

    let currentSizes;
    if (tabName === "tailwind") {
      currentSizes = FontForgeUtils.getCurrentSizes(
        "tailwind",
        window.fontClampAdvanced
      );
    } else {
      const allSizes = TabDataUtils.getDataForTab(tabName, {
        classSizes: this.classSizes,
        variableSizes: this.variableSizes,
        tagSizes: this.tagSizes,
      });

      // Filter out custom entries for base dropdown
      currentSizes = allSizes.filter((size) => {
        const name = TabDataUtils.getSizeDisplayName(size, tabName);
        return !name.startsWith("custom-") && !name.startsWith("--fs-custom-");
      });
    }

    if (!currentSizes || currentSizes.length === 0) {
      return;
    }

    let defaultFound = false;
    currentSizes.forEach((size, index) => {
      const option = document.createElement("option");
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

  // Switch unit type and update interface accordingly
  switchUnitType(unitType) {
    this.unitType = unitType;

    document.querySelectorAll("[data-unit]").forEach((btn) => {
      btn.classList.remove("active");
    });
    document
      .querySelector(`[data-unit="${unitType}"]`)
      ?.classList.add("active");

    this.triggerHook("unitTypeChanged", {
      unitType: unitType,
    });
  }

  // ========================================================================
  // CUSTOM EVENT & DATA MANAGEMENT METHODS
  // ========================================================================

  // Trigger custom event to notify other segments of readiness
  // Passes current settings and sizes for synchronization
  triggerSegmentHooks() {
    window.dispatchEvent(
      new CustomEvent("fontClampCoreReady", {
        detail: {
          coreInterface: this,
          data: {
            settings: this.settings,
            classSizes: this.classSizes,
            variableSizes: this.variableSizes,
            tagSizes: this.tagSizes,
          },
          elements: this.elements,
        },
      })
    );
  }

  // Trigger custom hooks for extensibility
  // Dispatches event with core interface reference and additional data
  triggerHook(hookName, data) {
    // Support both new unified format and legacy format
    const eventName = hookName.includes(":")
      ? `fontforge:${hookName}`
      : `fontClamp_${hookName}`;

    window.dispatchEvent(
      new CustomEvent(eventName, {
        detail: {
          ...data,
          coreInterface: this,
        },
      })
    );
  }

  getCurrentSizes() {
    return FontForgeUtils.getCurrentSizes(this.activeTab);
  }

  // Update internal data and notify other segments
  updateData(newData) {
    Object.assign(this, newData);
    this.triggerHook("dataUpdated", newData);
  }
}

/**
 * Fluid Font Advanced Features Controller
 */

// Manages advanced features like drag-and-drop, modal editing, real-time preview updates, and autosave
// Provides robust error handling and debugging utilities
// Ensures initialization only occurs when all dependencies are ready
class FontClampAdvanced {
  constructor() {
    this.version = "4.0.0";
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
      segmentBReady: false, // Why: Can't function without core interface ready
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
        CSS_UNIT_CONVERSION_BASE: 16,
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
      CSS_UNIT_CONVERSION_BASE: 16,
    };
  }

  initializeWhenReady() {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => {
        this.initState.domReady = true;
        this.checkReadinessAndInit();
      });
    } else {
      this.initState.domReady = true;
    }

    if (window.fontClampAjax && window.fontClampAjax.data) {
      this.initState.dataReady = true;
    } else {
      window.addEventListener("fontClampDataReady", () => {
        this.initState.dataReady = true;
        this.checkReadinessAndInit();
      });
    }

    if (window.fontClampCore) {
      this.initState.segmentBReady = true;
    } else {
      window.addEventListener("fontClampCoreReady", () => {
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
    const { domReady, dataReady, segmentBReady } = this.initState;

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

  // Simple debug utility
  log(message, ...args) {
    if (this.DEBUG_MODE) {
      console.log(`[FluidFontForge] ${message}`, ...args);
    }
  }

  // Initialize drag state
  initDragState() {
    return {
      isDragging: false,
      draggedRow: null,
      startY: 0,
      currentY: 0,
      offset: 0,
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

      window.dispatchEvent(
        new CustomEvent("fontforge:advanced:ready", {
          detail: {
            advancedFeatures: this,
            version: this.version,
          },
        })
      );
    } catch (error) {
      console.error(
        "❌ Failed to initialize Fluid Font Advanced Features:",
        error
      );
      this.showError("Failed to initialize advanced features");
    }
  }

  // ========================================================================
  // ELEMENT & EVENT MANAGEMENT
  // ========================================================================

  // Cache frequently used DOM elements
  // Why caching: Improves performance by reducing repeated DOM queries
  // Simplifies code by centralizing element references in one place
  // Ensures elements are available before binding events to them (avoids null reference errors)
  // Facilitates easier maintenance and updates to element selectors (only need to change in one location)
  // Enhances readability by providing clear variable names for elements
  // Supports dynamic interfaces where elements may be added/removed (can re-cache as needed)
  // Enables bulk operations on groups of elements (e.g., adding event listeners to multiple inputs)
  // Provides a single source of truth for element references (reduces risk of inconsistencies across codebase)
  // Aids in debugging by allowing quick inspection of cached elements (can log or breakpoint on cached variables)
  // Improves scalability for larger interfaces with many elements (easier to manage and organize)
  // Facilitates integration with other components/modules (can pass cached elements as needed)
  // Supports advanced features like drag-and-drop or modals (centralized access to required elements)
  // Enables better separation of concerns (UI logic can focus on cached elements rather than querying DOM)
  // Helps ensure elements are only queried once (reduces redundant operations)
  // Provides a foundation for building more complex interactions (e.g., synchronized previews, dynamic tables)
  // Overall, caching DOM elements is a best practice for efficient, maintainable, and robust front-end code.
  cacheElements() {
    this.elements = {
      minRootSizeInput: document.getElementById("min-root-size"),
      maxRootSizeInput: document.getElementById("max-root-size"),
      baseValueSelect: document.getElementById("base-value"),
      minViewportInput: document.getElementById("min-viewport"),
      maxViewportInput: document.getElementById("max-viewport"),
      minScaleSelect: document.getElementById("min-scale"),
      maxScaleSelect: document.getElementById("max-scale"),
      previewFontUrlInput: document.getElementById("preview-font-url"),
      fontFilenameSpan: document.getElementById("font-filename"),
      autosaveStatus: document.getElementById("autosave-status"),
      autosaveIcon: document.getElementById("autosave-icon"),
      autosaveText: document.getElementById("autosave-text"),
      autosaveToggle: document.getElementById("autosave-toggle"),
      sizesTableWrapper: document.getElementById("sizes-table-wrapper"),
      previewMinContainer: document.getElementById("preview-min-container"),
      previewMaxContainer: document.getElementById("preview-max-container"),
      tableHeader: document.getElementById("table-header"),
      tableActionButtons: document.getElementById("table-action-buttons"),
      minViewportDisplay: document.getElementById("min-viewport-display"),
      maxViewportDisplay: document.getElementById("max-viewport-display"),
    };
  }

  // Bind event listeners to elements
  // Why binding: Enables interactivity and dynamic behavior in the interface
  // Centralizes event management for easier maintenance and updates
  // Ensures events are only bound once (avoids duplicate handlers)
  bindEvents() {
    const settingsInputs = [
      "minRootSizeInput",
      "maxRootSizeInput",
      "minViewportInput",
      "maxViewportInput",
    ];

    // Why input events: Capture real-time changes for immediate feedback
    settingsInputs.forEach((elementKey) => {
      const element = this.elements[elementKey];
      if (element) {
        element.addEventListener("input", () => this.calculateSizes());
      }
    });

    // Why change events: Capture final selection changes (e.g., dropdowns)
    const settingsSelects = [
      "baseValueSelect",
      "minScaleSelect",
      "maxScaleSelect",
    ];

    // Bind change events to dropdowns and selects
    settingsSelects.forEach((elementKey) => {
      const element = this.elements[elementKey];
      if (element) {
        element.addEventListener("change", () => this.calculateSizes());
      }
    });

    // Debounced font URL input for preview updates
    if (this.elements.previewFontUrlInput) {
      this.elements.previewFontUrlInput.addEventListener(
        "input",
        this.debounce(() => this.updatePreviewFont(), 500)
      );
    }

    // Autosave toggle change handler
    if (this.elements.autosaveToggle) {
      this.elements.autosaveToggle.addEventListener("change", () => {
        this.handleAutosaveToggle();
      });

      // Check initial state and start autosave if already enabled
      if (this.elements.autosaveToggle.checked) {
        this.startAutosaveTimer();
      }
    }

    // Save button click handler
    const saveBtn = document.getElementById("save-btn");
    if (saveBtn) {
      saveBtn.addEventListener("click", () => {
        // Update status to show saving
        const autosaveStatus = document.getElementById("autosave-status");
        const autosaveIcon = document.getElementById("autosave-icon");
        const autosaveText = document.getElementById("autosave-text");

        if (autosaveStatus && autosaveIcon && autosaveText) {
          autosaveStatus.className = "autosave-status saving";
          autosaveIcon.textContent = "⏳";
          autosaveText.textContent = "Saving...";
        }

        // Disable save button during save
        saveBtn.disabled = true;
        saveBtn.textContent = "Saving...";

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
          autosaveEnabled: this.elements.autosaveToggle?.checked,
        };

        // Collect current sizes for all tabs
        const allSizes = {
          classSizes: window.fontClampAjax?.data?.classSizes || [],
          variableSizes: window.fontClampAjax?.data?.variableSizes || [],
          tagSizes: window.fontClampAjax?.data?.tagSizes || [],
        };

        const data = {
          action: "save_font_clamp_settings",
          nonce: window.fontClampAjax.nonce,
          settings: JSON.stringify(settings),
          sizes: JSON.stringify(allSizes),
        };
        fetch(window.fontClampAjax.ajaxurl, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: new URLSearchParams(data),
        })
          .then((response) => response.json())

          .then((result) => {
            // Update status to show success
            if (autosaveStatus && autosaveIcon && autosaveText) {
              autosaveStatus.className = "autosave-status saved";
              autosaveIcon.textContent = "✅";
              autosaveText.textContent = "Saved!";

              // Reset to ready after 2 seconds
              setTimeout(() => {
                autosaveStatus.className = "autosave-status idle";
                autosaveIcon.textContent = "💾";
                autosaveText.textContent = "Ready";
              }, 2000);
            }

            // Re-enable save button
            saveBtn.disabled = false;
            saveBtn.textContent = "Save";
          })
          .catch((error) => {
            console.error("Save error:", error);

            // Update status to show error
            if (autosaveStatus && autosaveIcon && autosaveText) {
              autosaveStatus.className = "autosave-status error";
              autosaveIcon.textContent = "❌";
              autosaveText.textContent = "Error";

              // Reset to ready after 3 seconds
              setTimeout(() => {
                autosaveStatus.className = "autosave-status idle";
                autosaveIcon.textContent = "💾";
                autosaveText.textContent = "Ready";
              }, 3000);
            }

            // Re-enable save button
            saveBtn.disabled = false;
            saveBtn.textContent = "Save";

            // Show WordPress admin error notice
            if (!window.fluidFontNotices) {
              window.fluidFontNotices = new WordPressAdminNotices();
            }
            window.fluidFontNotices.error(
              `<strong>Save Failed:</strong> Unable to save your font settings. Please check your connection and try again.`
            );
          });
      });
    }

    // Listen for tab and unit changes from core interface
    window.addEventListener("fontforge:tab:changed", (e) => {
      this.handleTabChange(e.detail);
    });

    window.addEventListener("fontforge:unit:changed", () => {
      this.calculateSizes();
    });
  }

  // Handle autosave toggle changes
  handleAutosaveToggle() {
    const isEnabled = this.elements.autosaveToggle?.checked;

    if (isEnabled) {
      this.startAutosaveTimer();
    } else {
      this.stopAutosaveTimer();
    }

    this.updateSettings();
  }

  // Start autosave timer
  startAutosaveTimer() {
    this.stopAutosaveTimer(); // Clear any existing timer

    this.autosaveTimer = setInterval(() => {
      this.performSave(true); // true = isAutosave
    }, 30000); // 30 seconds
  }

  // Stop autosave timer
  stopAutosaveTimer() {
    if (this.autosaveTimer) {
      clearInterval(this.autosaveTimer);
      this.autosaveTimer = null;
    }
  }

  // Perform save action
  performSave(isAutosave = false) {
    const saveBtn = document.getElementById("save-btn");
    if (saveBtn) {
      saveBtn.click();
    }
  }

  // Setup action buttons above the sizes table
  // Why setup: Centralizes button creation and event binding
  // Ensures buttons are only created once (avoids duplicates)
  // Facilitates easier updates to button functionality (single location)
  setupTableActions() {
    const tableButtons = this.elements.tableActionButtons;
    if (!tableButtons) return;

    tableButtons.innerHTML = `
        <button id="add-size" class="fcc-btn">Add Size</button>
        <button id="reset-defaults" class="fcc-btn">Reset</button>
        <button id="clear-sizes" class="fcc-btn fcc-btn-danger">clear all</button>
    `;

    // Event delegation for header buttons
    tableButtons.addEventListener("click", (e) => {
      const button = e.target.closest("button");
      if (!button) return;

      e.preventDefault();

      switch (button.id) {
        case "add-size":
          this.addNewSize();
          break;
        case "reset-defaults":
          this.resetDefaults();
          break;
        case "clear-sizes":
          this.clearSizes();
          break;
      }
    });

    // Event delegation for empty state buttons in table wrapper
    const tableWrapper = this.elements.sizesTableWrapper;
    if (tableWrapper) {
      tableWrapper.addEventListener("click", (e) => {
        const button = e.target.closest("button");
        if (!button) return;

        e.preventDefault();

        switch (button.id) {
          case "add-size":
            this.addNewSize();
            break;
          case "reset-defaults":
            this.resetDefaults();
            break;
        }
      });
    }
  }

  /**
   * Initialize display with proper constants instead of magic numbers
   * Why initialization: Ensures interface reflects current settings and data
   * Provides immediate feedback to user on current configuration
   * Sets up necessary event bindings and calculations for interactivity
   */
  initializeDisplay() {
    this.populateSettings();
    this.updateBaseValueOptions();

    // Use constants instead of magic numbers
    const minViewportSize =
      this.elements.minViewportInput?.value ||
      this.constants.DEFAULT_MIN_VIEWPORT;
    const maxViewportSize =
      this.elements.maxViewportInput?.value ||
      this.constants.DEFAULT_MAX_VIEWPORT;

    if (this.elements.minViewportDisplay) {
      this.elements.minViewportDisplay.textContent = minViewportSize + "px";
    }
    if (this.elements.maxViewportDisplay) {
      this.elements.maxViewportDisplay.textContent = maxViewportSize + "px";
    }

    this.calculateSizes();
    this.renderSizes();
    this.updatePreviewFont();
    this.createCopyButtons();
    this.setupKeyboardShortcuts();
    this.updatePreview();
  }

  /**
   * Populate settings using constants instead of magic numbers
   */
  populateSettings() {
    const data = window.fontClampAjax?.data;

    if (!data) {
      console.error("❌ No fluid font data available!");
      return;
    }

    // Use constants instead of magic numbers for validation
    const minRootSize =
      data.settings?.minRootSize || this.constants.DEFAULT_MIN_ROOT_SIZE;
    const maxRootSize =
      data.settings?.maxRootSize || this.constants.DEFAULT_MAX_ROOT_SIZE;
    const minViewport =
      data.settings?.minViewport || this.constants.DEFAULT_MIN_VIEWPORT;
    const maxViewport =
      data.settings?.maxViewport || this.constants.DEFAULT_MAX_VIEWPORT;

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
      this.elements.autosaveToggle.checked =
        data.settings?.autosaveEnabled !== false;
    }
  }

  handleTabChange(detail) {
    this.log("Tab changed to:", detail.activeTab);

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

    const activeTab = window.fontClampCore?.activeTab || "class";
    const nameHeader = headerRow.children[1];

    if (nameHeader) {
      switch (activeTab) {
        case "class":
          nameHeader.innerHTML = "Class";
          break;
        case "vars":
          nameHeader.innerHTML = "Variable";
          break;
        case "tag":
          nameHeader.innerHTML = "Tag";
          break;
      }
    }
  }

  // ========================================================================
  // BASE VALUE MANAGEMENT
  // ========================================================================

  // Populate base value dropdown based on active tab and available sizes
  // Why dynamic population: Different tabs have different size options
  // Ensures relevant options are presented to user
  // Improves usability by adapting to current context
  updateBaseValueOptions() {
    const select = this.elements.baseValueSelect;
    if (!select) return;

    const activeTab = window.fontClampCore?.activeTab || "class";
    const sizes = this.getCurrentSizes();

    // Store current selection to preserve it
    const currentSelection = select.value;

    select.innerHTML = "";
    select.disabled = false; // Re-enable when we have data

    let selectionFound = false;
    sizes.forEach((size) => {
      const option = document.createElement("option");
      switch (activeTab) {
        case "class":
          option.value = size.id;
          option.textContent = size.className;
          // Preserve current selection or use default
          if (
            (currentSelection && size.id == currentSelection) ||
            (!currentSelection && size.className === "medium")
          ) {
            option.selected = true;
            selectionFound = true;
          }
          break;
        case "vars":
          option.value = size.id;
          option.textContent = size.variableName;
          if (
            (currentSelection && size.id == currentSelection) ||
            (!currentSelection && size.variableName === "--fs-md")
          ) {
            option.selected = true;
            selectionFound = true;
          }
          break;
        case "tailwind":
          option.value = size.id;
          option.textContent = size.tailwindName;
          if (
            (currentSelection && size.id == currentSelection) ||
            (!currentSelection && size.tailwindName === "base")
          ) {
            option.selected = true;
            selectionFound = true;
          }
          break;
        case "tag":
          option.value = size.id;
          option.textContent = size.tagName;
          if (
            (currentSelection && size.id == currentSelection) ||
            (!currentSelection && size.tagName === "p")
          ) {
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

  // Calculate sizes based on current settings and base value
  // Why calculation: Core functionality to determine fluid font sizes
  // Uses mathematical scaling principles for responsive typography
  // Updates UI and preview to reflect new calculations
  calculateSizes() {
    const baseValue = this.elements.baseValueSelect?.value;
    if (!baseValue) {
      return;
    }

    const sizes = this.getCurrentSizes();
    const baseSize = sizes.find((size) => {
      return size.id == baseValue; // Compare ID to ID (use == to handle string/number conversion)
    });
    if (!baseSize) {
      this.log("❌ Base size not found for:", baseValue);
      return;
    }
    const baseIndex = sizes.indexOf(baseSize);
    const minScale = parseFloat(this.elements.minScaleSelect?.value);
    const maxScale = parseFloat(this.elements.maxScaleSelect?.value);
    const minRootSize = parseFloat(this.elements.minRootSizeInput?.value);
    const maxRootSize = parseFloat(this.elements.maxRootSizeInput?.value);
    const unitType = window.fontClampCore?.unitType || "rem";

    if (
      isNaN(minScale) ||
      isNaN(maxScale) ||
      isNaN(minRootSize) ||
      isNaN(maxRootSize)
    ) {
      this.log("❌ Invalid form values");
      return;
    }

    let minBaseSize, maxBaseSize;
    if (unitType === "rem") {
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

  // Update preview containers with current size data
  updatePreview() {
    try {
      const previewContext = this.createPreviewContext();
      if (!this.validatePreviewContext(previewContext)) return;

      this.clearPreviewContainers(previewContext);

      if (previewContext.sizes.length === 0) {
        this.renderEmptyPreview(previewContext);
        return;
      }

      this.renderPreviewRows(previewContext);
    } catch (error) {
      console.error("⚠ Preview update error:", error);
    }
  }

  // Create preview context with all needed data
  createPreviewContext() {
    return {
      sizes: this.getCurrentSizes(),
      previewMin: this.elements.previewMinContainer,
      previewMax: this.elements.previewMaxContainer,
      minRootSize: parseFloat(this.elements.minRootSizeInput?.value),
      maxRootSize: parseFloat(this.elements.maxRootSizeInput?.value),
      unitType: window.fontClampCore?.unitType || "rem",
      activeTab: window.fontClampCore?.activeTab || "class",
    };
  }

  // Validate preview context data
  // Why validation: Prevents runtime errors from missing/invalid data
  // Ensures all required elements and values are present before proceeding
  // Provides clear error messages for easier debugging
  validatePreviewContext(context) {
    if (!context.previewMin || !context.previewMax) {
      return false;
    }

    if (isNaN(context.minRootSize) || isNaN(context.maxRootSize)) {
      console.error("⚠ Invalid root size values in updatePreview");
      return false;
    }

    return true;
  }

  // Clear preview containers
  // Why clearing: Removes old preview rows before rendering new ones
  // Prevents duplication and ensures accurate display of current data
  clearPreviewContainers(context) {
    context.previewMin.innerHTML = "";
    context.previewMax.innerHTML = "";
  }

  // Render empty state message
  // Why empty state: Provides user feedback when no sizes are available
  // Enhances user experience by guiding next steps (e.g., add sizes)
  renderEmptyPreview(context) {
    const emptyMessage =
      '<div style="text-align: center; color: #6b7280; font-style: italic; padding: 60px 20px;">No sizes to preview</div>';
    context.previewMin.innerHTML = emptyMessage;
    context.previewMax.innerHTML = emptyMessage;
  }

  // Render preview rows for all sizes
  // Why modular rendering: Separates concerns for cleaner code
  // Facilitates easier updates to row structure and styling
  // Enhances maintainability by isolating row rendering logic
  renderPreviewRows(context) {
    context.sizes.forEach((size, index) => {
      const rowData = this.calculatePreviewRowData(size, index, context);
      const minRow = this.createPreviewRow(
        rowData.displayName,
        rowData.minSizePx,
        "px",
        rowData.lineHeight,
        rowData.unifiedRowHeight,
        size.id,
        index,
        rowData.minPadding
      );
      const maxRow = this.createPreviewRow(
        rowData.displayName,
        rowData.maxSizePx,
        "px",
        rowData.lineHeight,
        rowData.unifiedRowHeight,
        size.id,
        index,
        rowData.maxPadding
      );

      this.addSynchronizedHover(minRow, maxRow);
      context.previewMin.appendChild(minRow);
      context.previewMax.appendChild(maxRow);
    });
  }

  // Calculate data needed for a preview row
  // Why calculation: Centralizes logic for determining row appearance
  // Ensures consistent styling and sizing across all preview rows
  // Facilitates easier adjustments to row calculations in one location
  calculatePreviewRowData(size, index, context) {
    const displayName = this.getSizeDisplayName(size, context.activeTab);
    const minSize = size.min || this.constants.DEFAULT_MIN_ROOT_SIZE;
    const maxSize = size.max || this.constants.DEFAULT_MAX_ROOT_SIZE;

    let minSizePx, maxSizePx;
    if (context.unitType === "rem") {
      minSizePx = minSize * context.minRootSize;
      maxSizePx = maxSize * context.maxRootSize;
    } else {
      minSizePx = minSize;
      maxSizePx = maxSize;
    }

    const lineHeight = size.lineHeight || this.constants.DEFAULT_LINE_HEIGHT;
    const minTextHeight = minSizePx * lineHeight;
    const maxTextHeight = maxSizePx * lineHeight;
    const unifiedRowHeight = Math.max(minTextHeight, maxTextHeight) + 16;
    const paddingDiff = Math.abs(maxSizePx - minSizePx);

    return {
      displayName,
      minSizePx,
      maxSizePx,
      lineHeight,
      unifiedRowHeight,
      minPadding: minSizePx < maxSizePx ? paddingDiff : 0,
      maxPadding: maxSizePx < minSizePx ? paddingDiff : 0,
    };
  }

  // Create a single preview row element
  // Why modular row creation: Encapsulates row structure and styling
  // Facilitates consistent appearance across all preview rows
  createPreviewRow(
    displayName,
    fontSize,
    unitType,
    lineHeight,
    rowHeight,
    sizeId,
    index,
    topPadding = 0
  ) {
    const row = document.createElement("div");
    row.className = "preview-row";
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

    const text = document.createElement("div");
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
    row.addEventListener("click", () => {
      this.selectedRowId = sizeId;
      this.highlightDataTableRow(sizeId, index);
      this.highlightPreviewRows(sizeId);
      this.updateCSS();
    });

    row.appendChild(text);
    return row;
  }

  // Highlight corresponding data table row when a preview row is clicked
  // Why highlighting: Provides visual feedback on selection
  // Enhances user experience by linking preview and data table
  // Improves navigation in larger datasets
  highlightDataTableRow(sizeId, index) {
    document.querySelectorAll(".size-row.selected").forEach((row) => {
      row.classList.remove("selected");
    });

    const dataTableRow = document.querySelector(
      `.size-row[data-id="${sizeId}"]`
    );
    if (dataTableRow) {
      dataTableRow.classList.add("selected");
      dataTableRow.scrollIntoView({
        behavior: "smooth",
        block: "nearest",
      });
    }
  }

  // Highlight preview rows corresponding to selected data table row
  // Why highlighting: Links data table selection to visual preview
  // Aids in understanding size relationships
  // Enhances overall interactivity of the interface
  highlightPreviewRows(sizeId) {
    document.querySelectorAll(".preview-row.selected").forEach((row) => {
      row.classList.remove("selected");
    });

    document
      .querySelectorAll(`.preview-row[data-size-id="${sizeId}"]`)
      .forEach((row) => {
        row.classList.add("selected");
      });
  }

  // Add synchronized hover effect between two elements
  // Why synchronized hover: Enhances user experience by linking related elements
  // Provides clear visual feedback on relationships
  // Improves discoverability of connections between data and preview
  addSynchronizedHover(element1, element2) {
    const hoverIn = () => {
      element1.style.backgroundColor = "rgba(59, 130, 246, 0.1)";
      element2.style.backgroundColor = "rgba(59, 130, 246, 0.1)";
    };

    const hoverOut = () => {
      element1.style.backgroundColor = "transparent";
      element2.style.backgroundColor = "transparent";
    };

    element1.addEventListener("mouseenter", hoverIn);
    element1.addEventListener("mouseleave", hoverOut);
    element2.addEventListener("mouseenter", hoverIn);
    element2.addEventListener("mouseleave", hoverOut);
  }

  // Render sizes table with current size data
  renderSizes() {
    const wrapper = this.elements.sizesTableWrapper;
    if (!wrapper) return;

    const renderContext = this.createRenderContext();
    this.createTableStructure(wrapper);
    this.populateTableRows(renderContext);
    this.finalizeTableRender();
  }

  // Create rendering context with all needed data
  // Why context: Encapsulates all necessary data for rendering
  // Simplifies function signatures by passing a single object
  // Facilitates easier debugging and state management
  createRenderContext() {
    return {
      sizes: this.getCurrentSizes(),
      activeTab: window.fontClampCore?.activeTab || "class",
      unitType: window.fontClampCore?.unitType || "rem",
      tbody: null, // Will be set after table creation
    };
  }

  // Create the table HTML structure
  // Why structure creation: Separates HTML layout from data population
  // Ensures consistent table format
  // Facilitates easier updates to table layout
  createTableStructure(wrapper) {
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
  }

  // Populate table rows with size data
  // Why row population: Dynamically fills table with current data
  // Separates data handling from HTML structure
  // Facilitates easier updates to row content and event binding
  populateTableRows(context) {
    context.tbody = document.getElementById("sizes-table");

    context.sizes.forEach((size, index) => {
      const row = this.createTableRow(size, index, context);
      this.bindRowEvents(row);
      context.tbody.appendChild(row);
    });
  }

  // Create individual table row
  // Why modular row creation: Encapsulates row structure and styling
  // Facilitates consistent appearance across all rows
  createTableRow(size, index, context) {
    const row = document.createElement("tr");
    row.className = "size-row";
    row.draggable = true;
    row.dataset.id = size.id;
    row.dataset.index = index;

    const displayName = this.getSizeDisplayName(size, context.activeTab);

    row.innerHTML = `
    <td class="drag-handle" style="text-align: center; color: #9ca3af; cursor: grab; user-select: none;" 
      data-tooltip="Drag to reorder" data-tooltip-position="right">⋮⋮</td>
    <td style="font-weight: 500; overflow: hidden; text-overflow: ellipsis;" title="${displayName}">${displayName}</td>
    <td style="text-align: center; font-family: monospace; font-size: 10px;">${this.formatSize(
      size.min,
      context.unitType
    )}</td>
    <td style="text-align: center; font-family: monospace; font-size: 10px;">${this.formatSize(
      size.max,
      context.unitType
    )}</td>
    <td style="text-align: center; font-size: 11px;">${size.lineHeight}</td>
    <td style="text-align: center; padding: 2px;">
      <button class="edit-btn" style="color: #3b82f6; background: none; border: none; cursor: pointer; margin-right: 6px; font-size: 13px; padding: 2px;" title="Edit">✎</button>
      <button class="delete-btn" style="color: #ef4444; background: none; border: none; cursor: pointer; font-size: 12px; padding: 2px;" title="Delete">🗑️</button>
    </td>
  `;

    return row;
  }

  // Finalize table rendering
  // Why finalization: Completes rendering process with necessary updates
  // Ensures headers reflect current context
  // Applies any necessary CSS updates post-render
  finalizeTableRender() {
    this.updateTableHeaders();
    this.updateCSS();
  }

  // Bind event listeners to a table row
  // Why event binding: Enables interactivity for each row
  // Separates event logic from row creation for cleaner code
  // Facilitates easier updates to event handling in one location
  bindRowEvents(row) {
    this.bindRowButtonEvents(row);
    this.bindRowSelectionEvents(row);
    this.bindRowDragEvents(row);
  }

  // Bind edit and delete button events
  // Why button events: Provides functionality for editing and deleting sizes
  // Enhances user control over size data
  // Improves overall interactivity of the interface
  bindRowButtonEvents(row) {
    const editBtn = row.querySelector(".edit-btn");
    if (editBtn) {
      editBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        this.editSize(parseInt(row.dataset.id));
      });
    }

    const deleteBtn = row.querySelector(".delete-btn");
    if (deleteBtn) {
      deleteBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        this.deleteSize(parseInt(row.dataset.id));
      });
    }
  }

  // Bind row selection events
  // Why selection events: Allows users to select a row for detailed view/editing
  // Provides visual feedback on selection
  // Links selection to preview highlighting for better context
  bindRowSelectionEvents(row) {
    row.addEventListener("click", (e) => {
      if (e.target.closest("button")) return;

      const sizeId = parseInt(row.dataset.id);

      // Clear previous selections
      document.querySelectorAll(".size-row.selected").forEach((r) => {
        r.classList.remove("selected");
      });

      // Select current row
      row.classList.add("selected");
      this.selectedRowId = sizeId;
      this.highlightPreviewRows(sizeId);
      this.updateCSS();
    });
  }

  // Bind drag and drop events
  // Why drag events: Enables reordering of sizes via drag-and-drop
  // Enhances user experience by allowing intuitive rearrangement
  // Provides visual feedback during drag operations
  bindRowDragEvents(row) {
    const dragHandle = row.querySelector(".drag-handle");
    if (!dragHandle) return;

    this.bindDragInitiation(row, dragHandle);
    this.bindDragEvents(row);
  }

  // Bind drag initiation events
  // Why initiation events: Sets up the row to be draggable
  // Provides visual feedback when dragging starts
  // Prepares necessary data for drag-and-drop operations
  bindDragInitiation(row, dragHandle) {
    dragHandle.addEventListener("mousedown", (e) => {
      row.draggable = true;
    });

    row.addEventListener("dragstart", (e) => {
      this.dragState.draggedRow = row;
      e.dataTransfer.effectAllowed = "move";
      e.dataTransfer.setData("text/plain", row.dataset.id);
      row.style.opacity = "0.5";
      row.classList.add("dragging");
    });
  }

  // Bind drag interaction events
  // Why interaction events: Manages the drag-and-drop lifecycle
  // Provides visual cues for drop targets
  // Handles the actual reordering logic upon drop
  bindDragEvents(row) {
    row.addEventListener("dragenter", (e) => {
      if (this.dragState.draggedRow && this.dragState.draggedRow !== row) {
        this.showDragInsertionIndicator(row);
      }
    });

    row.addEventListener("dragend", (e) => {
      this.cleanupDragState(row);
    });

    row.addEventListener("dragover", (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = "move";
    });

    row.addEventListener("drop", (e) => {
      e.preventDefault();
      this.handleRowDrop(row);
    });

    row.addEventListener("dragleave", (e) => {
      this.handleDragLeave();
    });
  }

  // Show drag insertion indicator
  // Why indicator: Provides visual feedback on where the row will be dropped
  // Enhances user experience by clarifying drop target
  // Improves overall interactivity of the drag-and-drop process
  showDragInsertionIndicator(row) {
    // Remove existing insertion indicators
    document.querySelectorAll(".size-row").forEach((r) => {
      r.style.borderTop = "";
      r.style.boxShadow = "";
    });

    // Add border insertion line
    row.style.borderTop = "4px solid #3b82f6";
    row.style.boxShadow = "0 -2px 8px rgba(59, 130, 246, 0.5)";
  }

  // Clean up drag state
  // Why cleanup: Resets visual and state changes after drag operation
  // Prevents lingering styles or states that could confuse users
  // Ensures interface returns to normal state post-drag
  cleanupDragState(row) {
    row.style.opacity = "1";
    row.classList.remove("dragging");
    row.draggable = false;
    this.dragState.draggedRow = null;

    // Clean up visual feedback
    document.querySelectorAll(".size-row").forEach((r) => {
      r.classList.remove("drag-over");
      r.style.borderTop = "";
      r.style.boxShadow = "";
    });

    // Remove insertion line
    const insertionLine = document.getElementById("drag-insertion-line");
    if (insertionLine) insertionLine.remove();
  }

  // Handle row drop operation
  // Why drop handling: Finalizes the drag-and-drop operation
  // Performs the actual reordering of sizes
  // Updates UI and data to reflect new order
  handleRowDrop(row) {
    if (!this.dragState.draggedRow || this.dragState.draggedRow === row) return;

    // Remove insertion line
    const insertionLine = document.getElementById("drag-insertion-line");
    if (insertionLine) insertionLine.remove();

    // Perform the reorder
    this.reorderSizes(this.dragState.draggedRow, row);
  }

  // Reorder sizes based on drag and drop
  // Why reordering: Updates the underlying data to match new visual order
  // Ensures consistency between UI and data model
  // Triggers necessary updates to reflect changes
  reorderSizes(draggedRow, targetRow) {
    const sizes = this.getCurrentSizes();
    const draggedId = parseInt(draggedRow.dataset.id);
    const targetId = parseInt(targetRow.dataset.id);

    const draggedIndex = sizes.findIndex((s) => s.id === draggedId);
    const targetIndex = sizes.findIndex((s) => s.id === targetId);

    if (draggedIndex !== -1 && targetIndex !== -1) {
      // Remove dragged item and insert at target position
      const [draggedItem] = sizes.splice(draggedIndex, 1);
      sizes.splice(targetIndex, 0, draggedItem);

      // Re-render and update
      this.renderSizes();
      this.updatePreview();
      this.markDataChanged();
    }
  }

  // Handle drag leave
  // Why drag leave: Cleans up visual feedback when dragging leaves a row
  // Prevents confusion from lingering styles
  // Ensures a clean interface during drag operations
  handleDragLeave() {
    setTimeout(() => {
      if (
        this.dragState.draggedRow &&
        !document.querySelector(".size-row:hover")
      ) {
        document.querySelectorAll(".size-row").forEach((r) => {
          r.style.borderTop = "";
          r.style.boxShadow = "";
        });
      }
    }, 100);
  }

  // ========================================================================
  // CSS GENERATION & COPY METHODS
  // ========================================================================

  // Generate and update CSS code snippets based on current selection and settings
  // Why CSS generation: Provides users with ready-to-use code for their projects
  // Reflects real-time changes in settings and selections
  // Enhances usability by simplifying the implementation process
  updateCSS() {
    try {
      const selectedElement = document.getElementById("class-code");
      const generatedElement = document.getElementById("generated-code");

      if (selectedElement && generatedElement) {
        this.generateAndUpdateCSS(selectedElement, generatedElement);
      }

      const currentData = {
        classSizes: window.fontClampAjax?.data?.classSizes || [],
        variableSizes: window.fontClampAjax?.data?.variableSizes || [],
        tagSizes: window.fontClampAjax?.data?.tagSizes || [],
        coreInterface: window.fontClampCore,
      };

      window.dispatchEvent(
        new CustomEvent("fontClamp_dataUpdated", {
          detail: currentData,
        })
      );
    } catch (error) {
      console.error("❌ CSS update error:", error);
    }
  }

  // ========================================================================
  // COPY & CSS GENERATION
  // ========================================================================

  // Generate CSS code snippets and update the display elements
  generateAndUpdateCSS(selectedElement, generatedElement) {
    try {
      const context = this.getGenerationContext();
      const selectedCSS = this.generateSelectedCSS(context);
      const allCSS = this.generateAllCSS(context);

      this.updateCSSElements(
        selectedElement,
        generatedElement,
        selectedCSS,
        allCSS
      );
    } catch (error) {
      console.error("⚠ CSS generation error:", error);
    }
  }

  // Get all data needed for CSS generation
  // Why context gathering: Centralizes data retrieval for generation methods
  // Simplifies parameter passing and method signatures
  // Ensures consistency across different generation functions
  getGenerationContext() {
    const sizes = this.getCurrentSizes();
    const activeTab = window.fontClampCore?.activeTab || "class";
    const unitType = window.fontClampCore?.unitType || "rem";
    const selectedId = this.getSelectedSizeId();

    const minViewport = parseFloat(
      this.elements?.minViewportInput?.value ||
        document.getElementById("min-viewport")?.value ||
        this.constants.DEFAULT_MIN_VIEWPORT
    );
    const maxViewport = parseFloat(
      this.elements?.maxViewportInput?.value ||
        document.getElementById("max-viewport")?.value ||
        this.constants.DEFAULT_MAX_VIEWPORT
    );
    const minRootSize = parseFloat(this.elements.minRootSizeInput?.value);
    const maxRootSize = parseFloat(this.elements.maxRootSizeInput?.value);

    return {
      sizes,
      activeTab,
      unitType,
      selectedId,
      minViewport,
      maxViewport,
      minRootSize,
      maxRootSize,
    };
  }

  // Generate clamp() CSS function for given min/max sizes
  // Why clamp() generation: Core of fluid typography implementation
  // Uses mathematical principles for responsive scaling
  // Produces clean, efficient CSS for real-world use
  generateClampCSS(minSize, maxSize, context) {
    const { unitType, minViewport, maxViewport, minRootSize, maxRootSize } =
      context;

    if (isNaN(minRootSize) || isNaN(maxRootSize)) {
      this.log("⚠ Invalid root size value from Settings inputs");
      return "clamp(1rem, 1rem, 1rem)";
    }

    let minValue, maxValue;
    if (unitType === "rem") {
      minValue = `${minSize}rem`;
      maxValue = `${maxSize}rem`;
    } else {
      minValue = `${minSize}px`;
      maxValue = `${maxSize}px`;
    }

    // Why slope calculation: Creates linear interpolation between viewport sizes
    const slope = (maxSize - minSize) / (maxViewport - minViewport);

    // Why intersection: Y-intercept where the scaling line crosses viewport=0
    const intersection = -minViewport * slope + minSize;

    // Why multiply by 100: Convert decimal slope to vw units
    const slopeInViewportWidth = (slope * 100).toFixed(4);

    const intersectionValue =
      unitType === "rem"
        ? `${intersection.toFixed(4)}rem`
        : `${intersection.toFixed(4)}px`;

    // Why clamp() structure: min(floor), preferred(linear scaling), max(ceiling)
    return `clamp(${minValue}, ${intersectionValue} + ${slopeInViewportWidth}vw, ${maxValue})`;
  }

  // Generate CSS for the selected size only
  // Why focused generation: Allows users to quickly copy code for a specific size
  // Tailors output to current tab context for relevance
  // Simplifies implementation by providing only necessary code
  generateSelectedCSS(context) {
    const { sizes, activeTab, selectedId } = context;

    const selectedSize = sizes.find((s) => s.id === selectedId);
    if (!selectedSize || !selectedSize.min || !selectedSize.max) {
      return "/* No size selected or calculated */";
    }

    const clampValue = this.generateClampCSS(
      selectedSize.min,
      selectedSize.max,
      context
    );
    const displayName = this.getSizeDisplayName(selectedSize, activeTab);
    console.log(
      "🔍 Debug: Display name:",
      displayName,
      "for size:",
      selectedSize,
      "tab:",
      activeTab
    );

    switch (activeTab) {
      case "class":
        return `.${displayName} {\n  font-size: ${clampValue};\n  line-height: ${selectedSize.lineHeight};\n}`;
      case "vars":
        return `:root {\n  ${displayName}: ${clampValue};\n}`;
      case "tailwind":
        return `'${displayName}': '${clampValue}'`;
      default:
        return `${displayName} {\n  font-size: ${clampValue};\n  line-height: ${selectedSize.lineHeight};\n}`;
    }
  }

  // Generate CSS for all sizes
  // Why comprehensive generation: Provides full codebase for entire size set
  // Useful for initial setup or complete implementation
  // Reflects all current settings and selections for accuracy
  generateAllCSS(context) {
    const { sizes, activeTab } = context;
    if (!sizes || sizes.length === 0) {
      return "/* No sizes calculated */";
    }

    return this.generateCSSByType(sizes, context, activeTab);
  }

  /**
   * Unified CSS generator using strategy pattern
   * Why unified generation: Consolidates all CSS generation logic
   * Reduces code duplication and improves maintainability
   * Facilitates easy addition of new output types in the future
   */
  generateCSSByType(sizes, context, type) {
    const generators = {
      class: {
        wrapper: (content) => content,
        rule: (size, clampValue) =>
          `.${size.className} {\n  font-size: ${clampValue};\n  line-height: ${size.lineHeight};\n}\n\n`,
      },
      vars: {
        wrapper: (content) => `:root {\n${content}}`,
        rule: (size, clampValue) => `  ${size.variableName}: ${clampValue};\n`,
      },
      tailwind: {
        wrapper: (content) =>
          `module.exports = {\n  theme: {\n    extend: {\n      fontSize: {\n${content}      }\n    }\n  }\n}`,
        rule: (size, clampValue, index, total) => {
          const comma = index < total - 1 ? "," : "";
          return `        '${size.tailwindName}': '${clampValue}'${comma}\n`;
        },
      },
      tag: {
        wrapper: (content) => content,
        rule: (size, clampValue) =>
          `${size.tagName} {\n  font-size: ${clampValue};\n  line-height: ${size.lineHeight};\n}\n\n`,
      },
    };

    const generator = generators[type] || generators.class;
    let content = "";

    sizes.forEach((size, index) => {
      if (size.min && size.max) {
        const clampValue = this.generateClampCSS(size.min, size.max, context);
        content += generator.rule(size, clampValue, index, sizes.length);
      }
    });

    return generator.wrapper(content);
  }
  // Generate CSS for class-based output
  // Why modular generation: Encapsulates class-specific CSS structure
  // Ensures consistent formatting across all class definitions
  generateClassCSS(sizes, context) {
    let css = "";
    sizes.forEach((size) => {
      if (size.min && size.max) {
        const clampValue = this.generateClampCSS(size.min, size.max, context);
        css += `.${size.className} {\n  font-size: ${clampValue};\n  line-height: ${size.lineHeight};\n}\n\n`;
      }
    });
    return css;
  }

  // Generate CSS for variable-based output
  // Why modular generation: Encapsulates variable-specific CSS structure
  // Ensures consistent formatting across all variable definitions
  generateVariableCSS(sizes, context) {
    let css = ":root {\n";
    sizes.forEach((size) => {
      if (size.min && size.max) {
        const clampValue = this.generateClampCSS(size.min, size.max, context);
        css += `  ${size.variableName}: ${clampValue};\n`;
      }
    });
    css += "}";
    return css;
  }

  // Generate Tailwind config for fontSize object
  // Why modular generation: Encapsulates Tailwind-specific config structure
  // Ensures consistent formatting across all Tailwind size definitions
  generateTailwindCSS(sizes, context) {
    let css =
      "module.exports = {\n  theme: {\n    extend: {\n      fontSize: {\n";
    sizes.forEach((size, index) => {
      if (size.min && size.max) {
        const clampValue = this.generateClampCSS(size.min, size.max, context);
        const comma = index < sizes.length - 1 ? "," : "";
        css += `        '${size.tailwindName}': '${clampValue}'${comma}\n`;
      }
    });
    css += "      }\n    }\n  }\n}";
    return css;
  }

  // Generate CSS for tag-based output
  // Why modular generation: Encapsulates tag-specific CSS structure
  // Ensures consistent formatting across all tag definitions
  generateTagCSS(sizes, context) {
    let css = "";
    sizes.forEach((size) => {
      if (size.min && size.max) {
        const clampValue = this.generateClampCSS(size.min, size.max, context);
        css += `${size.tagName} {\n  font-size: ${clampValue};\n  line-height: ${size.lineHeight};\n}\n\n`;
      }
    });
    return css;
  }

  // Update CSS display elements and create copy buttons
  // Why modular update: Separates UI update logic from generation logic
  // Ensures display elements are always in sync with generated CSS
  // Facilitates easy addition of copy functionality
  updateCSSElements(selectedElement, generatedElement, selectedCSS, allCSS) {
    selectedElement.textContent = selectedCSS;
    generatedElement.textContent = allCSS;
    this.createCopyButtons();
  }

  // Create copy buttons and bind their events
  // Why modular buttons: Encapsulates button creation and event binding
  // Ensures buttons are always in sync with current state and tooltips
  // Enhances user experience with clear actions for copying CSS
  createCopyButtons() {
    // Create selected CSS copy button
    const selectedCopyContainer = document.getElementById(
      "selected-copy-button"
    );
    if (selectedCopyContainer) {
      const activeTab = window.fontClampCore?.activeTab || "class";
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
    const generatedCopyContainer = document.getElementById(
      "generated-copy-buttons"
    );
    if (generatedCopyContainer) {
      const activeTab = window.fontClampCore?.activeTab || "class";
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
      const copySelectedBtn = document.getElementById("copy-selected-btn");
      const copyAllBtn = document.getElementById("copy-all-btn");

      if (copySelectedBtn) {
        copySelectedBtn.addEventListener("click", () => this.copySelectedCSS());
      }

      if (copyAllBtn) {
        copyAllBtn.addEventListener("click", () => this.copyGeneratedCSS());
      }
    }, 100);
  }

  /**
   * Copy selected CSS to clipboard
   */
  copySelectedCSS() {
    const cssElement = document.getElementById("class-code");
    if (!cssElement) {
      return;
    }

    const cssText = cssElement.textContent || cssElement.innerText;

    if (
      !cssText ||
      cssText.includes("Loading CSS") ||
      cssText.includes("No CSS")
    ) {
      return;
    }
    const button = document.getElementById("copy-selected-btn");
    this.copyToClipboard(cssText, button);
  }

  /**
   * Copy generated CSS to clipboard
   */
  copyGeneratedCSS() {
    const cssElement = document.getElementById("generated-code");
    if (!cssElement) {
      return;
    }

    const cssText = cssElement.textContent || cssElement.innerText;

    if (
      !cssText ||
      cssText.includes("Loading CSS") ||
      cssText.includes("No CSS")
    ) {
      return;
    }
    const button = document.getElementById("copy-all-btn");
    this.copyToClipboard(cssText, button);
  }

  /**
   * Copy text to clipboard with visual feedback
   */
  copyToClipboard(text, button) {
    if (!text || text.trim() === "") {
      console.warn("No text to copy");
      return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard
        .writeText(text)
        .then(() => {
          this.showButtonSuccess(button);
        })
        .catch((err) => {
          console.error("Copy failed:", err);
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
    const textarea = document.createElement("textarea");
    textarea.value = text;
    textarea.style.position = "fixed";
    textarea.style.opacity = "0";
    textarea.style.top = "-9999px";
    textarea.style.left = "-9999px";
    document.body.appendChild(textarea);
    textarea.select();
    textarea.setSelectionRange(0, 99999);

    try {
      document.execCommand("copy");
    } catch (err) {
      console.error("Fallback copy failed:", err);
    }

    document.body.removeChild(textarea);
  }

  /**
   * Get tooltip text for selected CSS based on active tab
   */
  getSelectedCSSTooltip(activeTab) {
    switch (activeTab) {
      case "class":
        return "Copy the CSS for your selected class. Paste this into your stylesheet.";
      case "vars":
        return "Copy the CSS custom property for your selected variable.";
      case "tag":
        return "Copy the CSS for your selected HTML tag.";
      default:
        return "Copy the selected CSS to your clipboard.";
    }
  }

  /**
   * Get tooltip text for generated CSS based on active tab
   */
  getGeneratedCSSTooltip(activeTab) {
    switch (activeTab) {
      case "class":
        return "Copy all CSS classes with responsive font sizes.";
      case "vars":
        return "Copy all CSS custom properties for your :root selector.";
      case "tag":
        return "Copy all HTML tag styles for automatic responsive typography.";
      default:
        return "Copy all generated CSS for your project.";
    }
  }

  /**
   * Show button success state
   */
  showButtonSuccess(button) {
    if (!button) return;

    button.classList.add("success");

    setTimeout(() => {
      button.classList.remove("success");
    }, 1500);
  }

  /**
   * Setup keyboard shortcuts for copy operations
   */
  setupKeyboardShortcuts() {
    document.addEventListener("keydown", (e) => {
      // Ctrl/Cmd + Shift + C for selected CSS
      if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === "C") {
        e.preventDefault();
        this.copySelectedCSS();
      }

      // Ctrl/Cmd + Shift + A for all CSS
      if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === "A") {
        e.preventDefault();
        this.copyGeneratedCSS();
      }
    });
  }

  // Get the currently selected size ID
  // Why selection logic: Determines which size to generate CSS for
  // Prioritizes explicit row selection, falls back to base value dropdown
  // Ensures consistent behavior across different tabs and contexts
  getSelectedSizeId() {
    // If a row is specifically selected, use that
    if (this.selectedRowId) {
      return this.selectedRowId;
    }

    // Otherwise fall back to base value dropdown
    const activeTab = window.fontClampCore?.activeTab || "class";
    const baseValue = document.getElementById("base-value")?.value;

    if (!baseValue) return null;

    const sizes = this.getCurrentSizes();
    const selectedSize = sizes.find((size) => {
      switch (activeTab) {
        case "class":
          return size.className === baseValue;
        case "vars":
          return size.variableName === baseValue;
        case "tag":
          return size.tagName === baseValue;
      }
    });

    return selectedSize ? selectedSize.id : null;
  }

  // ========================================================================
  // FONT PREVIEW METHODS
  // ========================================================================

  // Dynamically load and apply custom font for preview
  // Why dynamic font loading: Allows users to see their selected font in action
  // Enhances preview realism and usability
  // Provides immediate visual feedback on font choices
  updatePreviewFont() {
    const fontUrl = this.elements.previewFontUrlInput?.value;
    const filenameSpan = this.elements.fontFilenameSpan;

    if (fontUrl && fontUrl.trim()) {
      const filename = fontUrl.split("/").pop().split("?")[0] || "Custom Font";
      if (filenameSpan) {
        filenameSpan.textContent = filename;
      }

      if (this.lastFontStyle) {
        this.lastFontStyle.remove();
      }

      const fontStyle = document.createElement("style");
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
        filenameSpan.textContent = "Default";
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

  // Setup modal HTML structure and bind events
  // Why modular modal setup: Encapsulates modal creation and event binding
  // Ensures modal is always in sync with current state and inputs
  // Enhances user experience with clear editing interface
  setupModal() {
    const existing = document.getElementById("edit-modal");
    if (existing) existing.remove();

    const modal = document.createElement("div");
    modal.id = "edit-modal";
    modal.className = "fcc-modal";
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

  // Bind event listeners to modal elements
  // Why binding: Enables interactivity for closing and saving edits
  // Centralizes event management for easier maintenance and updates
  // Ensures accessibility with keyboard support
  bindModalEvents(modal) {
    const closeBtn = modal.querySelector(".fcc-modal-close");
    const cancelBtn = modal.querySelector("#modal-cancel");
    const saveBtn = modal.querySelector("#modal-save");

    closeBtn.addEventListener("click", () => this.closeModal());
    cancelBtn.addEventListener("click", () => this.closeModal());

    modal.addEventListener("click", (e) => {
      if (e.target === modal) this.closeModal();
    });

    saveBtn.addEventListener("click", () => {
      this.saveEdit();
    });
    modal.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        this.saveEdit();
      } else if (e.key === "Escape") {
        this.closeModal();
      }
    });
  }

  // Open modal for editing a specific size
  // Why modal editing: Provides a focused interface for modifying size properties
  // Enhances user experience with clear input fields and validation
  // Supports both editing existing sizes and adding new ones
  editSize(id) {
    const sizes = this.getCurrentSizes();
    const size = sizes.find((s) => s.id == id);
    if (!size) return;

    this.editingId = id;
    this.isAddingNew = false; // Explicitly set to false for editing existing items

    const modal = document.getElementById("edit-modal");
    const nameInput = document.getElementById("edit-name");
    const nameField = document.getElementById("name-field");
    const lineHeightInput = document.getElementById("edit-line-height");

    if (!modal || !nameInput || !lineHeightInput) return;

    const activeTab = window.fontClampCore?.activeTab || "class";
    const displayName = this.getSizeDisplayName(size, activeTab);

    nameInput.value = displayName;
    lineHeightInput.value = size.lineHeight;

    // For tags, show the name field but disable it
    if (activeTab === "tag") {
      nameField.style.display = "block";
      nameInput.disabled = true;
      nameInput.style.opacity = "0.6";
      nameInput.style.cursor = "not-allowed";
    } else {
      nameField.style.display = "block";
      nameInput.disabled = false;
      nameInput.style.opacity = "1";
      nameInput.style.cursor = "text";
    }

    const header = modal.querySelector(".fcc-modal-header");
    if (header) {
      header.firstChild.textContent = `Edit ${displayName}`;
    }

    modal.classList.add("show");

    setTimeout(() => {
      (activeTab === "tag" ? lineHeightInput : nameInput).focus();
    }, 100);
  }

  // Open modal for adding a new size
  // Why add modal: Provides a clear interface for creating new size entries
  // Pre-fills default values to streamline the creation process
  saveEdit() {
    if (!this.editingId) return;

    const sizes = this.getCurrentSizes();
    const activeTab = window.fontClampCore?.activeTab || "class";
    let size;

    if (this.isAddingNew) {
      // Create new size entry
      size = {
        id: this.editingId,
        min: this.constants.DEFAULT_MIN_ROOT_SIZE,
        max: this.constants.DEFAULT_MAX_ROOT_SIZE,
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      };

      // Add type-specific properties
      if (activeTab === "class") {
        size.className = "";
      } else if (activeTab === "vars") {
        size.variableName = "";
      } else if (activeTab === "tag") {
        size.tagName = "";
      }
    } else {
      // Edit existing size - find by ID
      size = sizes.find((s) => s.id == this.editingId);
      if (!size) {
        console.error("Cannot find size to edit with ID:", this.editingId);
        return;
      }
    }

    const nameInput = document.getElementById("edit-name");
    const lineHeightInput = document.getElementById("edit-line-height");

    const newName = nameInput?.value.trim();
    const newLineHeight = parseFloat(lineHeightInput?.value);

    if (!newName && activeTab !== "tag") {
      this.showFieldError(nameInput, "Name cannot be empty");
      return;
    }

    if (isNaN(newLineHeight) || newLineHeight < 0.8 || newLineHeight > 3.0) {
      this.showFieldError(
        lineHeightInput,
        "Line height must be between 0.8 and 3.0"
      );
      return;
    }

    if (activeTab !== "tag") {
      const isDuplicate = sizes.some((s) => {
        if (s.id === this.editingId) return false;
        const existingName = this.getSizeDisplayName(s, activeTab);
        return existingName === newName;
      });

      if (isDuplicate) {
        this.showFieldError(nameInput, "A size with this name already exists");
        return;
      }
    }

    if (activeTab === "class") {
      size.className = newName;
    } else if (activeTab === "vars") {
      size.variableName = newName;
    } else if (activeTab === "tag") {
      size.tagName = newName;
    }
    size.lineHeight = newLineHeight;

    // Add new size to array if we're adding
    if (this.isAddingNew) {
      sizes.push(size);

      // Update the main data source to persist the change
      const activeTab = window.fontClampCore?.activeTab || "class";
      if (activeTab === "class") {
        window.fontClampAjax.data.classSizes = sizes;
      } else if (activeTab === "vars") {
        window.fontClampAjax.data.variableSizes = sizes;
      } else if (activeTab === "tag") {
        window.fontClampAjax.data.tagSizes = sizes;
      }
    }
    // Note: For editing existing items, the size object is already
    // in the array and has been modified by reference

    this.updateBaseValueOptions();
    this.calculateSizes();
    this.renderSizes();
    this.updatePreview();
    this.markDataChanged();
    this.closeModal();
  }

  // Close the modal and reset state
  // Why modal closure: Cleans up state and UI after editing
  // Resets editing context to prevent accidental edits
  // Hides modal to return focus to main interface
  closeModal() {
    const modal = document.getElementById("edit-modal");
    if (modal) {
      modal.classList.remove("show");
    }
    this.editingId = null;
    this.isAddingNew = false; // Reset the adding flag
  }

  // Show error state on input field
  // Why field error display: Provides immediate feedback on input validation
  // Highlights the specific field with an error
  // Displays a clear error message within the modal
  showFieldError(field, message) {
    field.classList.add("error");
    field.focus();

    // Show error within the modal instead of main window
    const modal = document.getElementById("edit-modal");
    let errorDiv = modal.querySelector(".modal-error");

    if (!errorDiv) {
      errorDiv = document.createElement("div");
      errorDiv.className = "modal-error";
      errorDiv.style.cssText = `
        background: #dc3545;
        color: white;
        padding: 12px;
        margin: 0 0 16px 0;
        border-radius: 4px;
        font-size: 14px;
        border: 1px solid #c82333;
      `;

      const modalContent = modal.querySelector(".fcc-modal-content");
      modalContent.insertBefore(errorDiv, modalContent.firstChild);
    }

    errorDiv.innerHTML = `<strong>⚠️ Validation Error:</strong> ${message}`;
    errorDiv.style.display = "block";

    // Auto-hide error after 5 seconds
    setTimeout(() => {
      if (errorDiv) {
        errorDiv.style.display = "none";
      }
      field.classList.remove("error");
    }, 5000);
  }

  // Delete size entry
  // Why deletion logic: Provides a way to remove unwanted size entries
  // Confirms action to prevent accidental deletions
  // Updates UI and state to reflect changes immediately
  deleteSize(id) {
    if (confirm("Delete this size?")) {
      const sizes = this.getCurrentSizes();
      const index = sizes.findIndex((s) => s.id == id);
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

  // Open add new size modal with pre-filled data
  // Why add new size: Streamlines the process of creating new size entries
  // Pre-fills default values to reduce user effort
  // Ensures unique naming to prevent conflicts
  addNewSize() {
    const activeTab = window.fontClampCore?.activeTab || "class";

    // Generate next custom name and ID
    const { nextId, customName } = this.generateNextCustomEntry(activeTab);

    // Open add modal
    this.openAddModal(activeTab, nextId, customName);
  }

  // Generate next custom entry data
  // Why unique naming: Prevents conflicts with existing entries
  // Ensures a clear naming convention for custom sizes
  // Simplifies user experience when adding new sizes
  generateNextCustomEntry(activeTab) {
    let currentData;
    if (activeTab === "class") {
      currentData = window.fontClampAjax?.data?.classSizes || [];
    } else if (activeTab === "vars") {
      currentData = window.fontClampAjax?.data?.variableSizes || [];
    } else if (activeTab === "tag") {
      currentData = window.fontClampAjax?.data?.tagSizes || [];
    }

    // Find next available ID
    const maxId =
      currentData.length > 0
        ? Math.max(...currentData.map((item) => item.id))
        : 0;
    const nextId = maxId + 1;

    // Generate custom name based on existing custom entries
    const customEntries = currentData.filter((item) => {
      const name = this.getSizeDisplayName(item, activeTab);
      return name.includes("custom-") || name.includes("--fs-custom-");
    });

    const nextCustomNumber = customEntries.length + 1;
    let customName;

    if (activeTab === "class") {
      customName = `custom-${nextCustomNumber}`;
    } else if (activeTab === "vars") {
      customName = `--fs-custom-${nextCustomNumber}`;
    } else if (activeTab === "tag") {
      customName = "span";
    }

    return {
      nextId,
      customName,
    };
  }

  // Open add modal with pre-filled data
  // Why modal opening: Provides a clear interface for adding new sizes
  // Pre-fills default values to streamline the creation process
  // Focuses input for immediate user interaction
  openAddModal(activeTab, newId, defaultName) {
    const modal = document.getElementById("edit-modal");
    const header = modal.querySelector(".fcc-modal-header");
    const nameInput = document.getElementById("edit-name");
    const nameField = document.getElementById("name-field");
    const lineHeightInput = document.getElementById("edit-line-height");

    header.firstChild.textContent = `Add ${
      activeTab === "class"
        ? "Class"
        : activeTab === "vars"
        ? "Variable"
        : "Tag"
    }`;

    nameInput.value = defaultName;
    lineHeightInput.value = this.constants.DEFAULT_BODY_LINE_HEIGHT;

    // For tags, show the name field but disable it
    if (activeTab === "tag") {
      nameField.style.display = "block";
      nameInput.disabled = true;
      nameInput.style.opacity = "0.6";
      nameInput.style.cursor = "not-allowed";
    } else {
      nameField.style.display = "block";
      nameInput.disabled = false;
      nameInput.style.opacity = "1";
      nameInput.style.cursor = "text";
    }

    // Store add context for save function
    this.editingId = newId;
    this.isAddingNew = true;

    modal.classList.add("show");

    // Focus on the name input field and add Enter key handling
    setTimeout(() => {
      const focusInput = activeTab === "tag" ? lineHeightInput : nameInput;
      if (focusInput) {
        focusInput.focus();
        if (focusInput === nameInput) {
          focusInput.setSelectionRange(0, focusInput.value.length); // Select all text
        }
      }
    }, 100);
  }

  // Reset sizes to default with confirmation
  // Why reset functionality: Allows users to quickly restore original settings
  // Confirms action to prevent accidental data loss
  // Updates UI and state to reflect changes immediately
  resetDefaults() {
    const activeTab = window.fontClampCore?.activeTab || "class";
    const tabName =
      activeTab === "class"
        ? "Classes"
        : activeTab === "vars"
        ? "Variables"
        : activeTab === "tailwind"
        ? "Tailwind Sizes"
        : "Tags";

    if (
      confirm(
        `Reset ${tabName} to defaults?\n\nThis will replace all current entries with the original default sizes.\n\nAny custom entries will be lost.`
      )
    ) {
      switch (activeTab) {
        case "class":
          window.fontClampAjax.data.classSizes = this.getDefaultClassSizes();
          break;
        case "vars":
          window.fontClampAjax.data.variableSizes =
            this.getDefaultVariableSizes();
          break;
        case "tailwind":
          window.fontClampAjax.data.tailwindSizes =
            this.getDefaultTailwindSizes();
          break;
        case "tag":
          window.fontClampAjax.data.tagSizes = this.getDefaultTagSizes();
          break;
      }

      // Clear the explicitly cleared flag since we're restoring defaults
      if (window.fontClampAjax.data.explicitlyClearedTabs) {
        delete window.fontClampAjax.data.explicitlyClearedTabs[activeTab];
      }

      this.calculateSizes();
      this.renderSizes();
      this.updatePreview();
      this.markDataChanged();

      setTimeout(() => {
        if (window.fontClampCore) {
          const activeTab = window.fontClampCore.activeTab;
          switch (activeTab) {
            case "class":
              window.fontClampCore.classSizes =
                window.fontClampAjax.data.classSizes;
              break;
            case "vars":
              window.fontClampCore.variableSizes =
                window.fontClampAjax.data.variableSizes;
              break;
            case "tailwind":
              window.fontClampCore.tailwindSizes =
                window.fontClampAjax.data.tailwindSizes;
              break;
            case "tag":
              window.fontClampCore.tagSizes =
                window.fontClampAjax.data.tagSizes;
              break;
          }
          window.fontClampCore.updateBaseValueDropdown(activeTab);
        }
      }, 200);

      // Show admin notice using WordPressAdminNotices if available
      try {
        if (!window.fluidFontNotices) {
          window.fluidFontNotices = new WordPressAdminNotices();
        }
        window.fluidFontNotices.success(
          `${tabName} have been reset to defaults successfully.`
        );
      } catch (error) {
        console.error("Failed to create success notification:", error);
        // Fallback: simple alert for user feedback
        alert(`${tabName} have been reset to defaults successfully.`);
      }
    }
  }

  // Show reset success notification
  // Why notification: Provides user feedback confirming the reset action
  // Enhances user experience with clear communication
  // Auto-dismisses to avoid cluttering the interface
  showResetNotification(tabName) {
    // Create reset notification
    const notification = document.createElement("div");
    notification.id = "reset-notification";
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
        notification.style.transition =
          "transform 0.25s ease-out, opacity 0.25s ease-out";
        notification.style.transform = "translateY(100%)";
        notification.style.opacity = "0";

        setTimeout(() => {
          if (document.body.contains(notification)) {
            document.body.removeChild(notification);
          }
        }, 250);
      }
    }, 3000);
  }

  // Clear all sizes with confirmation and undo option
  clearSizes() {
    const clearContext = this.createClearContext();
    if (!this.validateClearContext(clearContext)) return;

    if (!this.confirmClear(clearContext)) return;

    this.performClear(clearContext);
    this.showUndoNotification(
      clearContext.tabName,
      clearContext.currentData,
      clearContext.dataArrayRef,
      clearContext.activeTab
    );
  }

  // Create context for clear operation
  // Why context creation: Encapsulates all necessary data for the clear operation
  // Simplifies passing multiple parameters through functions
  // Enhances code readability and maintainability
  createClearContext() {
    const activeTab = window.fontClampCore?.activeTab || "class";
    const tabName = this.getTabDisplayName(activeTab);
    const { currentData, dataArrayRef } = this.getTabDataForClear(activeTab);

    return {
      activeTab,
      tabName,
      currentData,
      dataArrayRef,
    };
  }

  // Get display name for tab
  // Why display name: Provides user-friendly names for confirmation dialogs and notifications
  // Enhances clarity in user communications
  // Supports multiple tab types with appropriate naming
  getTabDisplayName(activeTab) {
    switch (activeTab) {
      case "class":
        return "Classes";
      case "vars":
        return "Variables";
      case "tailwind":
        return "Tailwind Sizes";
      case "tag":
        return "Tags";
      default:
        return "Items";
    }
  }

  // Get current data and reference for clear operation
  // Why data retrieval: Centralizes logic for accessing tab-specific data
  // Simplifies validation and manipulation of the correct data array
  // Supports multiple tab types with appropriate data handling
  getTabDataForClear(activeTab) {
    let currentData, dataArrayRef;

    switch (activeTab) {
      case "class":
        currentData = [...(window.fontClampAjax?.data?.classSizes || [])];
        dataArrayRef = "classSizes";
        break;
      case "vars":
        currentData = [...(window.fontClampAjax?.data?.variableSizes || [])];
        dataArrayRef = "variableSizes";
        break;
      case "tailwind":
        currentData = [...(window.fontClampAjax?.data?.tailwindSizes || [])];
        dataArrayRef = "tailwindSizes";
        break;
      case "tag":
        currentData = [...(window.fontClampAjax?.data?.tagSizes || [])];
        dataArrayRef = "tagSizes";
        break;
      default:
        currentData = null;
        dataArrayRef = null;
    }

    return { currentData, dataArrayRef };
  }

  // Validate clear context
  // Why validation: Ensures all necessary data is present before proceeding
  // Prevents errors during the clear operation
  // Provides clear error messaging for debugging
  validateClearContext(context) {
    if (!context.currentData || !context.dataArrayRef) {
      console.error(
        "Unable to clear sizes - invalid tab or missing data:",
        context.activeTab
      );
      return false;
    }
    return true;
  }

  // Show confirmation dialog
  // Why confirmation dialog: Prevents accidental data loss
  // Clearly communicates the consequences of the action
  // Provides an opportunity to cancel before proceeding
  confirmClear(context) {
    return confirm(
      `Are you sure you want to clear all ${context.tabName}?\n\nThis will remove all ${context.currentData.length} entries from the current tab.\n\nYou can undo this action immediately after.`
    );
  }

  // Perform the clear operation
  // Why clear operation: Encapsulates all steps needed to clear data and update the UI
  // Ensures data sources, UI, and state are all updated consistently
  // Provides a single point of change for easier maintenance
  performClear(context) {
    this.clearDataSources(context);
    this.updateCoreInterfaceData(context.activeTab);
    this.renderClearedState(context.activeTab);
    this.updateAfterClear();
  }

  // Clear data sources
  // Why data clearing: Removes all entries from the relevant data array
  // Marks the tab as explicitly cleared for state tracking
  // Ensures consistency across the application state
  clearDataSources(context) {
    if (window.fontClampAjax?.data) {
      window.fontClampAjax.data[context.dataArrayRef] = [];
      // Mark tab as explicitly cleared
      window.fontClampAjax.data.explicitlyClearedTabs =
        window.fontClampAjax.data.explicitlyClearedTabs || {};
      window.fontClampAjax.data.explicitlyClearedTabs[context.activeTab] = true;
    }
  }

  // Update core interface data
  // Why core data update: Ensures the core interface reflects the cleared state
  // Prevents stale data from causing inconsistencies
  // Supports immediate UI updates without waiting for other processes
  updateCoreInterfaceData(activeTab) {
    if (!window.fontClampCore) return;

    switch (activeTab) {
      case "class":
        window.fontClampCore.classSizes = [];
        break;
      case "vars":
        window.fontClampCore.variableSizes = [];
        break;
      case "tag":
        window.fontClampCore.tagSizes = [];
        break;
      case "tailwind":
        window.fontClampCore.tailwindSizes = [];
        break;
    }
  }

  // Render cleared state UI
  // Why UI rendering: Provides immediate visual feedback of the cleared state
  // Updates table and dropdown to reflect no available sizes
  // Enhances user experience with clear communication of current state
  renderClearedState(activeTab) {
    this.renderEmptyTable();
    this.updateBaseDropdownForClear(activeTab);
  }

  // Update base dropdown for cleared state
  // Why dropdown update: Reflects the absence of sizes in the dropdown
  // Disables the dropdown to prevent invalid selections
  // Provides clear messaging about the lack of available options
  updateBaseDropdownForClear(activeTab) {
    const baseSelect = document.getElementById("base-value");
    if (!baseSelect) return;

    const emptyOptionText = this.getEmptyOptionText(activeTab);
    baseSelect.innerHTML = `<option>${emptyOptionText}</option>`;
    baseSelect.disabled = true;
  }

  // Get empty option text for base dropdown
  // Why empty option text: Provides contextually relevant messaging
  // Enhances user understanding of the current state
  // Supports multiple tab types with appropriate messaging
  getEmptyOptionText(activeTab) {
    switch (activeTab) {
      case "class":
        return "No classes";
      case "vars":
        return "No variables";
      case "tailwind":
        return "No sizes";
      case "tag":
        return "No tags";
      default:
        return "No items";
    }
  }

  // Update UI after clear operation
  // Why UI update: Ensures all dependent components reflect the cleared state
  // Triggers preview and CSS updates for consistency
  // Marks data as changed for state tracking
  updateAfterClear() {
    this.updatePreview();
    this.updateCSS();
    this.markDataChanged();
  }

  // Show undo notification after clearing sizes
  // Why undo notification: Provides immediate recovery option after clearing
  // Enhances user experience with clear communication and control
  // Auto-dismisses to avoid cluttering the interface
  showUndoNotification(tabName, backupData, dataArrayRef, tabType) {
    // Create undo notification
    const notification = document.createElement("div");
    notification.id = "clear-undo-notification";
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
    if (!document.getElementById("notification-animations")) {
      const style = document.createElement("style");
      style.id = "notification-animations";
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
    document.getElementById("undo-clear-btn").addEventListener("click", () => {
      // Restore the data
      if (window.fontClampAjax?.data) {
        window.fontClampAjax.data[dataArrayRef] = backupData;
        // Clear the explicitly cleared flag since data is restored
        if (window.fontClampAjax.data.explicitlyClearedTabs) {
          delete window.fontClampAjax.data.explicitlyClearedTabs[tabType];
        }
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
    document
      .getElementById("dismiss-clear-btn")
      .addEventListener("click", () => {
        this.removeNotification(notification);
      });

    // Enter key handling
    const handleUndoKeydown = (event) => {
      if (event.key === "Enter" && document.body.contains(notification)) {
        event.preventDefault();
        event.stopPropagation();
        this.removeNotification(notification);
        document.removeEventListener("keydown", handleUndoKeydown);
      }
    };
    document.addEventListener("keydown", handleUndoKeydown);

    // Auto-dismiss after 10 seconds
    setTimeout(() => {
      if (document.body.contains(notification)) {
        this.removeNotification(notification);
        document.removeEventListener("keydown", handleUndoKeydown);
      }
    }, 10000);
  }

  // Remove notification with animation
  // Why animated removal: Provides a smooth visual transition when dismissing
  // Enhances user experience with polished UI interactions
  // Prevents abrupt disappearance that can be jarring
  removeNotification(notification) {
    // Prevent multiple calls
    if (notification.dataset.removing === "true") return;
    notification.dataset.removing = "true";

    // Use transform and opacity for smoother animation
    notification.style.transition =
      "transform 0.25s ease-out, opacity 0.25s ease-out";
    notification.style.transform = "translateY(100%)";
    notification.style.opacity = "0";

    setTimeout(() => {
      if (document.body.contains(notification)) {
        document.body.removeChild(notification);
      }
    }, 250);
  }

  // Render a friendly message when no sizes are present
  // Why empty state: Provides clear guidance when no data is available
  // Encourages user action to add new sizes or reset to defaults
  // Enhances overall user experience with a polished interface
  renderEmptyTable() {
    const wrapper = this.elements.sizesTableWrapper;
    if (!wrapper) return;

    const activeTab = window.fontClampCore?.activeTab || "class";
    const tabDisplayName =
      activeTab === "class"
        ? "Font Size Classes"
        : activeTab === "vars"
        ? "CSS Variables"
        : "HTML Tag Styles";
    const addButtonText =
      activeTab === "class"
        ? "add first class"
        : activeTab === "vars"
        ? "add first variable"
        : "add first tag";

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

  // Update settings and recalculate sizes
  // Why settings update: Centralizes the process of recalculating and rendering
  // Ensures all dependent components are updated consistently
  // Marks data as changed for potential save operations
  updateSettings() {
    this.calculateSizes();
    this.renderSizes();
    this.updatePreview();
    this.markDataChanged();
  }

  // Mark data as changed for save tracking
  // Why change tracking: Enables save button when data is modified
  // Prevents unnecessary saves when no changes have occurred
  // Enhances user experience with clear save state indication
  markDataChanged() {
    this.dataChanged = true;
  }

  // ========================================================================
  // DATA MANAGEMENT & UTILITY METHODS
  // ========================================================================

  // Get sizes for the currently active tab
  // Why dynamic size retrieval: Ensures operations always use the correct dataset
  // Adapts to user context for accurate calculations and rendering
  // Simplifies code by centralizing size access logic
  getCurrentSizes() {
    const activeTab = window.fontClampCore?.activeTab || "class";
    const sizes = FontForgeUtils.getCurrentSizes(activeTab, this);
    return sizes;
  }

  // Get display name for a size based on active tab
  // Why dynamic naming: Adapts to different naming conventions across tabs
  // Ensures consistent display of size names in the UI
  // Simplifies code by centralizing name retrieval logic
  getSizeDisplayName(size, activeTab) {
    switch (activeTab) {
      case "class":
        return size.className || "";
      case "vars":
        return size.variableName || "";
      case "tailwind":
        return size.tailwindName || "";
      case "tag":
        return size.tagName || "";
      default:
        return "";
    }
  }

  // Format size value with appropriate unit
  // Why size formatting: Ensures consistent display of size values
  // Adapts formatting based on unit type for clarity
  // Handles empty or null values gracefully
  formatSize(value, unitType) {
    if (!value) return "—";
    if (unitType === "px") {
      return `${Math.round(value)} ${unitType}`;
    }
    return `${value.toFixed(3)} ${unitType}`;
  }

  // Debounce function to limit how often a function can be called
  // Why debounce: Improves performance by reducing excessive calls
  // Enhances user experience by preventing laggy interactions
  // Useful for input events where rapid changes can occur
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

  // Show error message in console
  // Why error logging: Provides feedback for debugging and issue tracking
  // Centralizes error handling for easier maintenance
  // Can be expanded to include user-facing notifications if needed
  showError(message) {
    console.error(message);
  }

  // ========================================================================
  // DEFAULT DATA FACTORIES
  // ========================================================================

  // Default sizes for classes, variables, and tags
  // Why default data: Provides a baseline for users to start with
  // Ensures consistent initial state across installations
  // Simplifies reset functionality by having predefined defaults
  getDefaultClassSizes() {
    return [
      {
        id: 1,
        className: "xxxlarge",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 2,
        className: "xxlarge",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 3,
        className: "xlarge",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 4,
        className: "large",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 5,
        className: "medium",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 6,
        className: "small",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 7,
        className: "xsmall",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 8,
        className: "xxsmall",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
    ];
  }

  // Default variable sizes
  // Why variable defaults: Provides a standard set of CSS variables for users
  // Ensures consistency with class sizes for easier adoption
  // Simplifies reset functionality by having predefined defaults
  getDefaultVariableSizes() {
    return [
      {
        id: 1,
        variableName: "--fs-xxxl",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 2,
        variableName: "--fs-xxl",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 3,
        variableName: "--fs-xl",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 4,
        variableName: "--fs-lg",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 5,
        variableName: "--fs-md",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 6,
        variableName: "--fs-sm",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 7,
        variableName: "--fs-xs",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 8,
        variableName: "--fs-xxs",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
    ];
  }

  // Default tag sizes
  // Why tag defaults: Provides a standard set of HTML tags for users
  // Ensures consistency with class and variable sizes for easier adoption
  // Simplifies reset functionality by having predefined defaults
  getDefaultTagSizes() {
    return [
      {
        id: 1,
        tagName: "h1",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 2,
        tagName: "h2",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 3,
        tagName: "h3",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 4,
        tagName: "h4",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 5,
        tagName: "h5",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 6,
        tagName: "h6",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 7,
        tagName: "p",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
    ];
  }

  // Tailwind sizes are read-only defaults
  // Why tailwind defaults: Provides a reference set of Tailwind CSS sizes
  // Ensures users have a familiar baseline for Tailwind integration
  // Simplifies reset functionality by having predefined defaults
  getDefaultTailwindSizes() {
    return [
      {
        id: 1,
        tailwindName: "4xl",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 2,
        tailwindName: "3xl",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 3,
        tailwindName: "2xl",
        lineHeight: this.constants.DEFAULT_HEADING_LINE_HEIGHT,
      },
      {
        id: 4,
        tailwindName: "xl",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 5,
        tailwindName: "base",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 6,
        tailwindName: "lg",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 7,
        tailwindName: "sm",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
      {
        id: 8,
        tailwindName: "xs",
        lineHeight: this.constants.DEFAULT_BODY_LINE_HEIGHT,
      },
    ];
  }

  // Generate CSS for class-based output
  // Why class CSS generation: Provides ready-to-use CSS for font size classes
  // Ensures consistent application of responsive font sizing
  // Simplifies user implementation with clear class definitions
  generateClassCSS(sizes, context) {
    let css = "";
    sizes.forEach((size) => {
      if (size.min && size.max) {
        const clampValue = this.generateClampCSS(size.min, size.max, context);
        css += `.${size.className} {\n  font-size: ${clampValue};\n  line-height: ${size.lineHeight};\n}\n\n`;
      }
    });
    return css;
  }

  // Generate CSS for variable-based output
  // Why variable CSS generation: Provides ready-to-use CSS for font size variables
  // Ensures consistent application of responsive font sizing
  // Simplifies user implementation with clear variable definitions
  generateVariableCSS(sizes, context) {
    let css = ":root {\n";
    sizes.forEach((size) => {
      if (size.min && size.max) {
        const clampValue = this.generateClampCSS(size.min, size.max, context);
        css += `  ${size.variableName}: ${clampValue};\n`;
      }
    });
    css += "}";
    return css;
  }

  // Generate Tailwind config for fontSize object
  // Why Tailwind config generation: Provides a ready-to-use Tailwind CSS configuration
  // Ensures seamless integration with Tailwind projects
  // Simplifies user implementation with clear configuration structure
  generateTailwindCSS(sizes, context) {
    let css =
      "module.exports = {\n  theme: {\n    extend: {\n      fontSize: {\n";
    sizes.forEach((size, index) => {
      if (size.min && size.max) {
        const clampValue = this.generateClampCSS(size.min, size.max, context);
        const comma = index < sizes.length - 1 ? "," : "";
        css += `        '${size.tailwindName}': '${clampValue}'${comma}\n`;
      }
    });
    css += "      }\n    }\n  }\n}";
    return css;
  }

  // Generate CSS for tag-based output
  // Why tag CSS generation: Provides ready-to-use CSS for HTML tags
  generateTagCSS(sizes, context) {
    let css = "";
    sizes.forEach((size) => {
      if (size.min && size.max) {
        const clampValue = this.generateClampCSS(size.min, size.max, context);
        css += `${size.tagName} {\n  font-size: ${clampValue};\n  line-height: ${size.lineHeight};\n}\n\n`;
      }
    });
    return css;
  }
}

// Initialize Advanced Features
// Why initialization: Sets up the advanced features of the plugin
// Ensures all event listeners and UI components are ready
// Provides a seamless experience for users interacting with size management
document.addEventListener("DOMContentLoaded", () => {
  window.fontClampAdvanced = new FontClampAdvanced();
});

new SimpleTooltips();

document.addEventListener("DOMContentLoaded", () => {
  window.fontClampCore = new FontClampEnhancedCoreInterface();
});
