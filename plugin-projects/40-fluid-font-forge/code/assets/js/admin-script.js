/**
 * Fluid Font Forge - Admin Interface Script
 *
 * Advanced fluid typography calculator with interactive controls,
 * real-time CSS clamp() generation, and responsive font previews.
 *
 * @package FluidFontForge
 * @version 3.7.0
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

// Simple JavaScript Tooltips
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

const FontClampUtils = {
  getCurrentSizes(activeTab = null, fontClampAdvanced = null) {
    const tab = activeTab || window.fontClampCore?.activeTab || "class";

    let sizes = [];
    switch (tab) {
      case "class":
        sizes = window.fontClampAjax?.data?.classSizes || [];
        break;
      case "vars":
        sizes = window.fontClampAjax?.data?.variableSizes || [];
        break;
      case "tailwind":
        sizes = window.fontClampAjax?.data?.tailwindSizes || [];
        break;
      case "tag":
        sizes = window.fontClampAjax?.data?.tagSizes || [];
        break;
      default:
        sizes = [];
    }

    // Handle defaults if needed and fontClampAdvanced instance is available
    if (sizes.length === 0 && fontClampAdvanced) {
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

// Enhanced Core Interface Controller
class FontClampEnhancedCoreInterface {
  constructor() {
    console.log("🚀 Fluid Font Forge");

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
      const infoToggle = document.querySelector(
        '[data-toggle-target="info-content"]'
      );
      if (infoToggle) {
        infoToggle.addEventListener("click", () => this.toggleInfo());
      }

      const aboutToggle = document.querySelector(
        '[data-toggle-target="about-content"]'
      );
      if (aboutToggle) {
        aboutToggle.addEventListener("click", () => this.toggleAbout());
      }
    }, 100);
  }

  toggleInfo() {
    const button = document.querySelector(
      '[data-toggle-target="info-content"]'
    );
    const content = document.getElementById("info-content");

    if (content && button) {
      if (content.classList.contains("expanded")) {
        content.classList.remove("expanded");
        button.classList.remove("expanded");
      } else {
        content.classList.add("expanded");
        button.classList.add("expanded");
      }
    }
  }

  toggleAbout() {
    const button = document.querySelector(
      '[data-toggle-target="about-content"]'
    );
    const content = document.getElementById("about-content");

    if (content && button) {
      if (content.classList.contains("expanded")) {
        content.classList.remove("expanded");
        button.classList.remove("expanded");
      } else {
        content.classList.add("expanded");
        button.classList.add("expanded");
      }
    }
  }

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
    window.addEventListener("fontClampAdvancedReady", () => {
      this.loadingSteps.advancedReady = true;
      this.checkAndRevealInterface();
    });

    window.addEventListener("fontClamp_dataUpdated", () => {
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

  isInterfaceRevealed() {
    const mainContainer = document.getElementById("fcc-main-container");
    return mainContainer && mainContainer.classList.contains("ready");
  }

  syncVisualState() {
    document.querySelectorAll("[data-tab]").forEach((tab) => {
      tab.classList.remove("active");
    });
    document
      .querySelector(`[data-tab="${this.activeTab}"]`)
      ?.classList.add("active");
  }

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
    } else {
      console.log("❌ Tailwind tab not found!");
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

  triggerCalculation() {
    window.dispatchEvent(new CustomEvent("fontClamp_settingsChanged"));
    if (window.fontClampAdvanced && window.fontClampAdvanced.calculateSizes) {
      window.fontClampAdvanced.calculateSizes();
    }
  }

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

  switchTab(tabName) {
    this.activeTab = tabName;

    document.querySelectorAll("[data-tab]").forEach((tab) => {
      tab.classList.remove("active");
    });
    document.querySelector(`[data-tab="${tabName}"]`)?.classList.add("active");

    if (tabName === "class") {
      this.elements.tableTitle.textContent = "Font Size Classes";
      this.elements.selectedCodeTitle.textContent = "Selected Class CSS";
      this.elements.generatedCodeTitle.textContent =
        "Generated CSS (All Classes)";
    } else if (tabName === "vars") {
      this.elements.tableTitle.textContent = "CSS Variables";
      this.elements.selectedCodeTitle.textContent = "Selected Variable CSS";
      this.elements.generatedCodeTitle.textContent =
        "Generated CSS (All Variables)";
    } else if (tabName === "tailwind") {
      this.elements.tableTitle.textContent = "Tailwind Font Sizes";
      this.elements.selectedCodeTitle.textContent = "Selected Size Config";
      this.elements.generatedCodeTitle.textContent =
        "Tailwind Config (fontSize Object)";
    } else if (tabName === "tag") {
      this.elements.tableTitle.textContent = "HTML Tag Styles";
      this.elements.selectedCodeTitle.textContent = "Selected Tag CSS";
      this.elements.generatedCodeTitle.textContent = "Generated CSS (All Tags)";
    }

    if (typeof this.updateBaseValueDropdown === "function") {
      this.updateBaseValueDropdown(tabName);
    }

    this.triggerHook("tabChanged", {
      activeTab: tabName,
    });
  }

  updateBaseValueDropdown(tabName) {
    const baseValueSelect = document.getElementById("base-value");
    if (!baseValueSelect) {
      return;
    }

    baseValueSelect.innerHTML = "";

    let currentSizes, propertyName, defaultValue;

    if (tabName === "class") {
      currentSizes = this.classSizes.filter(
        (size) => !size.className || !size.className.startsWith("custom-")
      );
      propertyName = "className";
      defaultValue = "medium";
    } else if (tabName === "vars") {
      currentSizes = this.variableSizes.filter(
        (size) => !size.variableName || !size.variableName.startsWith("custom-")
      );
      propertyName = "variableName";
      defaultValue = "--fs-md";
    } else if (tabName === "tailwind") {
      currentSizes = FontClampUtils.getCurrentSizes(
        "tailwind",
        window.fontClampAdvanced
      );
      propertyName = "tailwindName";
      defaultValue = "base";
      console.log("🔍 Fixed tailwind sizes:", currentSizes);
    } else if (tabName === "tag") {
      currentSizes = this.tagSizes.filter(
        (size) => !size.tagName || !size.tagName.startsWith("custom-")
      );
      propertyName = "tagName";
      defaultValue = "p";
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

  triggerHook(hookName, data) {
    window.dispatchEvent(
      new CustomEvent(`fontClamp_${hookName}`, {
        detail: {
          ...data,
          coreInterface: this,
        },
      })
    );
  }

  getCurrentSizes() {
    return FontClampUtils.getCurrentSizes(this.activeTab);
  }

  updateData(newData) {
    Object.assign(this, newData);
    this.triggerHook("dataUpdated", newData);
  }
}

/**
 * Fluid Font Advanced Features Controller
 */
class FontClampAdvanced {
  constructor() {
    this.version = "3.6.0";
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
        new CustomEvent("fontClampAdvancedReady", {
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

  bindEvents() {
    const settingsInputs = [
      "minRootSizeInput",
      "maxRootSizeInput",
      "minViewportInput",
      "maxViewportInput",
    ];

    settingsInputs.forEach((elementKey) => {
      const element = this.elements[elementKey];
      if (element) {
        element.addEventListener("input", () => this.calculateSizes());
      }
    });

    const settingsSelects = [
      "baseValueSelect",
      "minScaleSelect",
      "maxScaleSelect",
    ];

    settingsSelects.forEach((elementKey) => {
      const element = this.elements[elementKey];
      if (element) {
        element.addEventListener("change", () => this.calculateSizes());
      }
    });

    if (this.elements.previewFontUrlInput) {
      this.elements.previewFontUrlInput.addEventListener(
        "input",
        this.debounce(() => this.updatePreviewFont(), 500)
      );
    }

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

            alert("Error saving data");
          });
      });
    }

    window.addEventListener("fontClamp_tabChanged", (e) => {
      this.handleTabChange(e.detail);
    });

    window.addEventListener("fontClamp_unitTypeChanged", () => {
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
    const saveBtn = document.getElementById("save-btn");
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

    this.log("Display initialization complete with constants");
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

  calculateSizes() {
    const baseValue = this.elements.baseValueSelect?.value;
    if (!baseValue) {
      this.log("❌ No base value selected");
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

  updatePreview() {
    try {
      const sizes = this.getCurrentSizes();
      const previewMin = this.elements.previewMinContainer;
      const previewMax = this.elements.previewMaxContainer;

      if (!previewMin || !previewMax) {
        return;
      }

      previewMin.innerHTML = "";
      previewMax.innerHTML = "";

      if (sizes.length === 0) {
        previewMin.innerHTML =
          '<div style="text-align: center; color: #6b7280; font-style: italic; padding: 60px 20px;">No sizes to preview</div>';
        previewMax.innerHTML =
          '<div style="text-align: center; color: #6b7280; font-style: italic; padding: 60px 20px;">No sizes to preview</div>';
        return;
      }

      const minRootSize = parseFloat(this.elements.minRootSizeInput?.value);
      const maxRootSize = parseFloat(this.elements.maxRootSizeInput?.value);
      const unitType = window.fontClampCore?.unitType || "rem";

      if (isNaN(minRootSize) || isNaN(maxRootSize)) {
        console.error("❌ Invalid root size values in updatePreview");
        return;
      }

      const activeTab = window.fontClampCore?.activeTab || "class";

      sizes.forEach((size, index) => {
        const displayName = this.getSizeDisplayName(size, activeTab);
        const minSize = size.min || this.constants.DEFAULT_MIN_ROOT_SIZE;
        const maxSize = size.max || this.constants.DEFAULT_MAX_ROOT_SIZE;

        let minSizePx, maxSizePx;
        if (unitType === "rem") {
          // Why multiply: Convert rem back to pixels for preview display
          // Formula: pixels = rem_value × current_root_font_size
          minSizePx = minSize * minRootSize;
          maxSizePx = maxSize * maxRootSize;
        } else {
          // Why direct use: px values are already in display units
          minSizePx = minSize;
          maxSizePx = maxSize;
        }

        const lineHeight =
          size.lineHeight || this.constants.DEFAULT_LINE_HEIGHT;

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

        const minRow = this.createPreviewRow(
          displayName,
          minSizePx,
          "px",
          lineHeight,
          unifiedRowHeight,
          size.id,
          index,
          minPadding
        );
        const maxRow = this.createPreviewRow(
          displayName,
          maxSizePx,
          "px",
          lineHeight,
          unifiedRowHeight,
          size.id,
          index,
          maxPadding
        );
        this.addSynchronizedHover(minRow, maxRow);

        previewMin.appendChild(minRow);
        previewMax.appendChild(maxRow);
      });
    } catch (error) {
      console.error("❌ Preview update error:", error);
    }
  }
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

  renderSizes() {
    const wrapper = this.elements.sizesTableWrapper;
    if (!wrapper) return;

    const sizes = this.getCurrentSizes();
    const activeTab = window.fontClampCore?.activeTab || "class";
    const unitType = window.fontClampCore?.unitType || "rem";

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

    const tbody = document.getElementById("sizes-table");

    sizes.forEach((size, index) => {
      const row = document.createElement("tr");
      row.className = "size-row";
      row.draggable = true;
      row.dataset.id = size.id;
      row.dataset.index = index;

      const displayName = this.getSizeDisplayName(size, activeTab);

      row.innerHTML = `
                            <td class="drag-handle" style="text-align: center; color: #9ca3af; cursor: grab; user-select: none;" 
            data-tooltip="Drag to reorder" data-tooltip-position="right">⋮⋮</td>
                            <td style="font-weight: 500; overflow: hidden; text-overflow: ellipsis;" title="${displayName}">${displayName}</td>
                            <td style="text-align: center; font-family: monospace; font-size: 10px;">${this.formatSize(
                              size.min,
                              unitType
                            )}</td>
                            <td style="text-align: center; font-family: monospace; font-size: 10px;">${this.formatSize(
                              size.max,
                              unitType
                            )}</td>
                            <td style="text-align: center; font-size: 11px;">${
                              size.lineHeight
                            }</td>
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
    row.addEventListener("click", (e) => {
      if (e.target.closest("button")) return;

      const sizeId = parseInt(row.dataset.id);
      const index = row.dataset.index;

      document.querySelectorAll(".size-row.selected").forEach((r) => {
        r.classList.remove("selected");
      });

      row.classList.add("selected");
      this.selectedRowId = sizeId;
      this.highlightPreviewRows(sizeId);
      this.updateCSS();
    });

    // Only allow dragging from the drag handle
    const dragHandle = row.querySelector(".drag-handle");
    if (dragHandle) {
      // Make only the handle initiate drag, but set data on the row
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

      row.addEventListener("dragenter", (e) => {
        if (this.dragState.draggedRow && this.dragState.draggedRow !== row) {
          // Remove existing insertion indicators
          document.querySelectorAll(".size-row").forEach((r) => {
            r.style.borderTop = "";
            r.style.boxShadow = "";
          });

          // Add border insertion line
          row.style.borderTop = "4px solid #3b82f6";
          row.style.boxShadow = "0 -2px 8px rgba(59, 130, 246, 0.5)";
        }
      });

      row.addEventListener("dragend", (e) => {
        row.style.opacity = "1";
        row.classList.remove("dragging");
        row.draggable = false;
        this.dragState.draggedRow = null;

        // Clean up visual feedback
        document.querySelectorAll(".size-row").forEach((r) => {
          r.classList.remove("drag-over");
        });

        // Remove insertion line
        const insertionLine = document.getElementById("drag-insertion-line");
        if (insertionLine) insertionLine.remove();
      });

      row.addEventListener("dragover", (e) => {
        e.preventDefault(); // Always prevent default to allow drop
        e.dataTransfer.dropEffect = "move";
      });

      row.addEventListener("drop", (e) => {
        e.preventDefault();

        if (this.dragState.draggedRow && this.dragState.draggedRow !== row) {
          // Remove insertion line
          const insertionLine = document.getElementById("drag-insertion-line");
          if (insertionLine) insertionLine.remove();

          // Get the current sizes array
          const sizes = this.getCurrentSizes();
          const draggedId = parseInt(this.dragState.draggedRow.dataset.id);
          const targetId = parseInt(row.dataset.id);

          // Find the items to reorder
          const draggedIndex = sizes.findIndex((s) => s.id === draggedId);
          const targetIndex = sizes.findIndex((s) => s.id === targetId);

          if (draggedIndex !== -1 && targetIndex !== -1) {
            // Remove the dragged item and insert it at the target position
            const [draggedItem] = sizes.splice(draggedIndex, 1);
            sizes.splice(targetIndex, 0, draggedItem);

            // Re-render the table with new order
            this.renderSizes();
            this.updatePreview();
            this.markDataChanged();
          } else {
            console.log("Could not find indices for reordering");
          }
        } else {
          console.log("Drop conditions not met");
        }
      });

      row.addEventListener("dragleave", (e) => {
        // Add small delay to prevent immediate removal during cursor movement
        setTimeout(() => {
          // Only remove if we're not actively dragging over another row
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
      });
    }
  }

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

  generateAndUpdateCSS(selectedElement, generatedElement) {
    try {
      const sizes = this.getCurrentSizes();
      const activeTab = window.fontClampCore?.activeTab || "class";
      const unitType = window.fontClampCore?.unitType || "rem";

      const generateClampCSS = (minSize, maxSize) => {
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

        if (isNaN(minRootSize) || isNaN(maxRootSize)) {
          this.log("❌ Invalid root size value from Settings inputs");
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
        // Formula: rise over run - how much font size changes per pixel of viewport
        const slope = (maxSize - minSize) / (maxViewport - minViewport);

        // Why intersection: Y-intercept where the scaling line crosses viewport=0
        // Mathematical formula: y = mx + b, solving for b when x=minViewport, y=minSize
        const intersection = -minViewport * slope + minSize;

        // Why multiply by 100: Convert decimal slope to vw units (1vw = 1% of viewport width)
        const slopeInViewportWidth = (slope * 100).toFixed(4);

        const intersectionValue =
          unitType === "rem"
            ? `${intersection.toFixed(4)}rem`
            : `${intersection.toFixed(4)}px`;

        // Why clamp() structure: min(floor), preferred(linear scaling), max(ceiling)
        // Creates smooth font scaling between viewports with safe boundaries
        return `clamp(${minValue}, ${intersectionValue} + ${slopeInViewportWidth}vw, ${maxValue})`;
      };

      const selectedId = this.getSelectedSizeId();
      const selectedSize = sizes.find((s) => s.id === selectedId);
      if (selectedSize && selectedSize.min && selectedSize.max) {
        const clampValue = generateClampCSS(selectedSize.min, selectedSize.max);
        const displayName = this.getSizeDisplayName(selectedSize, activeTab);

        let selectedCSS = "";
        if (activeTab === "class") {
          selectedCSS = `.${displayName} {\n  font-size: ${clampValue};\n  line-height: ${selectedSize.lineHeight};\n}`;
        } else if (activeTab === "vars") {
          selectedCSS = `:root {\n  ${displayName}: ${clampValue};\n}`;
        } else if (activeTab === "tailwind") {
          selectedCSS = `'${displayName}': '${clampValue}'`;
        } else {
          selectedCSS = `${displayName} {\n  font-size: ${clampValue};\n  line-height: ${selectedSize.lineHeight};\n}`;
        }

        selectedElement.textContent = selectedCSS;
      } else {
        selectedElement.textContent = "/* No size selected or calculated */";
      }

      let allCSS = "";

      if (activeTab === "class") {
        sizes.forEach((size) => {
          if (size.min && size.max) {
            const clampValue = generateClampCSS(size.min, size.max);
            allCSS += `.${size.className} {\n  font-size: ${clampValue};\n  line-height: ${size.lineHeight};\n}\n\n`;
          }
        });
      } else if (activeTab === "vars") {
        allCSS = ":root {\n";
        sizes.forEach((size) => {
          if (size.min && size.max) {
            const clampValue = generateClampCSS(size.min, size.max);
            allCSS += `  ${size.variableName}: ${clampValue};\n`;
          }
        });
        allCSS += "}";
      } else if (activeTab === "tailwind") {
        allCSS =
          "module.exports = {\n  theme: {\n    extend: {\n      fontSize: {\n";
        sizes.forEach((size, index) => {
          if (size.min && size.max) {
            const clampValue = generateClampCSS(size.min, size.max);
            const comma = index < sizes.length - 1 ? "," : "";
            allCSS += `        '${size.tailwindName}': '${clampValue}'${comma}\n`;
          }
        });
        allCSS += "      }\n    }\n  }\n}";
      } else {
        sizes.forEach((size) => {
          if (size.min && size.max) {
            const clampValue = generateClampCSS(size.min, size.max);
            allCSS += `${size.tagName} {\n  font-size: ${clampValue};\n  line-height: ${size.lineHeight};\n}\n\n`;
          }
        });
      }

      generatedElement.textContent = allCSS || "/* No sizes calculated */";
    } catch (error) {
      console.error("❌ CSS generation error:", error);
    }
  }

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
      console.log("❌ No CSS element found");
      return;
    }

    const cssText = cssElement.textContent || cssElement.innerText;

    if (
      !cssText ||
      cssText.includes("Loading CSS") ||
      cssText.includes("No CSS")
    ) {
      console.log("⚠️ No CSS available to copy");
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
      console.log("❌ No CSS element found");
      return;
    }

    const cssText = cssElement.textContent || cssElement.innerText;

    if (
      !cssText ||
      cssText.includes("Loading CSS") ||
      cssText.includes("No CSS")
    ) {
      console.log("⚠️ No CSS available to copy");
      return;
    }
    const button = document.getElementById("copy-all-btn");
    this.copyToClipboard(cssText, button);
  }

  /**
   * Copy text to clipboard with visual feedback
   */
  copyToClipboard(text, button) {
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

  editSize(id) {
    const sizes = this.getCurrentSizes();
    const size = sizes.find((s) => s.id == id);
    if (!size) return;

    this.editingId = id;

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
      // Edit existing size
      size = sizes.find((s) => s.id == this.editingId);
      if (!size) return;
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
    }

    this.updateBaseValueOptions();
    this.calculateSizes();
    this.renderSizes();
    this.updatePreview();
    this.markDataChanged();
    this.closeModal();
  }

  closeModal() {
    const modal = document.getElementById("edit-modal");
    if (modal) {
      modal.classList.remove("show");
    }
    this.editingId = null;
  }

  showFieldError(field, message) {
    field.classList.add("error");
    field.focus();
    alert(message);
    setTimeout(() => field.classList.remove("error"), 3000);
  }

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

  addNewSize() {
    const activeTab = window.fontClampCore?.activeTab || "class";

    // Generate next custom name and ID
    const { nextId, customName } = this.generateNextCustomEntry(activeTab);

    // Open add modal
    this.openAddModal(activeTab, nextId, customName);
  }

  // Generate next custom entry data
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

  resetDefaults() {
    const activeTab = window.fontClampCore?.activeTab || "class";
    const tabName =
      activeTab === "class"
        ? "Classes"
        : activeTab === "vars"
        ? "Variables"
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
        case "tag":
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

  clearSizes() {
    const activeTab = window.fontClampCore?.activeTab || "class";
    const tabName =
      activeTab === "class"
        ? "Classes"
        : activeTab === "vars"
        ? "Variables"
        : "Tags";

    // Get current data for backup
    let currentData, dataArrayRef;
    if (activeTab === "class") {
      currentData = [...(window.fontClampAjax?.data?.classSizes || [])];
      dataArrayRef = "classSizes";
    } else if (activeTab === "vars") {
      currentData = [...(window.fontClampAjax?.data?.variableSizes || [])];
      dataArrayRef = "variableSizes";
    } else if (activeTab === "tag") {
      currentData = [...(window.fontClampAjax?.data?.tagSizes || [])];
      dataArrayRef = "tagSizes";
    }

    // Show confirmation dialog
    const confirmed = confirm(
      `Are you sure you want to clear all ${tabName}?\n\nThis will remove all ${currentData.length} entries from the current tab.\n\nYou can undo this action immediately after.`
    );

    if (!confirmed) return;

    // Clear the data source
    if (window.fontClampAjax?.data) {
      window.fontClampAjax.data[dataArrayRef] = [];
    }

    // Directly render empty table instead of calling renderSizes()
    this.renderEmptyTable();

    // Handle empty base dropdown directly
    const baseSelect = document.getElementById("base-value");
    if (baseSelect) {
      const emptyOptionText =
        activeTab === "class"
          ? "No classes"
          : activeTab === "vars"
          ? "No variables"
          : "No tags";
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
    const activeTab = window.fontClampCore?.activeTab || "class";
    const sizes = FontClampUtils.getCurrentSizes(activeTab, this);
    console.log(`🔍 getCurrentSizes for ${activeTab}:`, sizes);
    return sizes;
  }

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

  formatSize(value, unitType) {
    if (!value) return "—";
    if (unitType === "px") {
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
}

// Initialize Advanced Features
document.addEventListener("DOMContentLoaded", () => {
  window.fontClampAdvanced = new FontClampAdvanced();
});

new SimpleTooltips();

document.addEventListener("DOMContentLoaded", () => {
  window.fontClampCore = new FontClampEnhancedCoreInterface();
});
