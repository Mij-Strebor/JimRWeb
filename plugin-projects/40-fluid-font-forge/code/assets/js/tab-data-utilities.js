/**
 * Tab Data Utilities
 *
 * Centralized utilities for managing tab-specific data and operations.
 * Eliminates repetitive switch statements throughout the codebase.
 *
 * @package FluidFontForge
 * @since 4.0.1
 */

/**
 * Tab Data Configuration Map
 * Single source of truth for tab-specific properties and behaviors
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

/**
 * Tab Data Utility Functions
 */
const TabDataUtils = {
  /**
   * Get data array for the specified tab
   * @param {string} activeTab - Tab name ('class', 'vars', 'tailwind', 'tag')
   * @param {object} data - Data object containing all size arrays
   * @returns {array} Size data for the specified tab
   */
  getDataForTab(activeTab, data) {
    const config = TabDataMap[activeTab];
    return config && data ? data[config.dataKey] || [] : [];
  },

  /**
   * Get property name for the specified tab
   * @param {string} activeTab - Tab name
   * @returns {string} Property name (e.g., 'className', 'variableName')
   */
  getPropertyName(activeTab) {
    return TabDataMap[activeTab]?.nameProperty || "className";
  },

  /**
   * Get display name for the specified tab
   * @param {string} activeTab - Tab name
   * @returns {string} Display name (e.g., 'Classes', 'Variables')
   */
  getDisplayName(activeTab) {
    return TabDataMap[activeTab]?.displayName || "Items";
  },

  /**
   * Get table title for the specified tab
   * @param {string} activeTab - Tab name
   * @returns {string} Table title
   */
  getTableTitle(activeTab) {
    return TabDataMap[activeTab]?.tableTitle || "Font Sizes";
  },

  /**
   * Get selected CSS title for the specified tab
   * @param {string} activeTab - Tab name
   * @returns {string} Selected CSS title
   */
  getSelectedCSSTitle(activeTab) {
    return TabDataMap[activeTab]?.selectedCSSTitle || "Selected CSS";
  },

  /**
   * Get generated CSS title for the specified tab
   * @param {string} activeTab - Tab name
   * @returns {string} Generated CSS title
   */
  getGeneratedCSSTitle(activeTab) {
    return TabDataMap[activeTab]?.generatedCSSTitle || "Generated CSS";
  },

  /**
   * Get add button text for the specified tab
   * @param {string} activeTab - Tab name
   * @returns {string} Add button text
   */
  getAddButtonText(activeTab) {
    return TabDataMap[activeTab]?.addButtonText || "add first item";
  },

  /**
   * Get base default value for the specified tab
   * @param {string} activeTab - Tab name
   * @returns {string} Base default value
   */
  getBaseDefaultValue(activeTab) {
    return TabDataMap[activeTab]?.baseDefaultValue || "medium";
  },

  /**
   * Get base default ID for the specified tab
   * @param {string} activeTab - Tab name
   * @returns {number} Base default ID
   */
  getBaseDefaultId(activeTab) {
    return TabDataMap[activeTab]?.baseDefaultId || 5;
  },

  /**
   * Get size display name from a size object
   * @param {object} size - Size object
   * @param {string} activeTab - Tab name
   * @returns {string} Display name for the size
   */
  getSizeDisplayName(size, activeTab) {
    const propertyName = this.getPropertyName(activeTab);
    return size[propertyName] || "";
  },

  /**
   * Check if tab is valid
   * @param {string} activeTab - Tab name to validate
   * @returns {boolean} True if valid tab
   */
  isValidTab(activeTab) {
    return activeTab && TabDataMap.hasOwnProperty(activeTab);
  },

  /**
   * Get all valid tab names
   * @returns {array} Array of valid tab names
   */
  getValidTabs() {
    return Object.keys(TabDataMap);
  },

  /**
   * Get tab configuration object
   * @param {string} activeTab - Tab name
   * @returns {object|null} Tab configuration or null if invalid
   */
  getTabConfig(activeTab) {
    return TabDataMap[activeTab] || null;
  },

  /**
   * Update data source for a specific tab
   * @param {string} activeTab - Tab name
   * @param {array} newData - New data array
   * @param {object} dataSource - Data source object to update
   */
  updateDataForTab(activeTab, newData, dataSource) {
    const config = TabDataMap[activeTab];
    if (config && dataSource) {
      dataSource[config.dataKey] = newData;
    }
  },

  /**
   * Get empty state message for a tab
   * @param {string} activeTab - Tab name
   * @returns {string} Empty state message
   */
  getEmptyStateMessage(activeTab) {
    const displayName = this.getDisplayName(activeTab);
    return `No ${displayName}`;
  },

  /**
   * Get tooltip text for copy buttons based on tab
   * @param {string} activeTab - Tab name
   * @param {boolean} isSelected - Whether this is for selected (true) or all (false) CSS
   * @returns {string} Tooltip text
   */
  getCopyTooltip(activeTab, isSelected = true) {
    if (isSelected) {
      switch (activeTab) {
        case "class":
          return "Copy the CSS for your selected class. Paste this into your stylesheet.";
        case "vars":
          return "Copy the CSS custom property for your selected variable.";
        case "tailwind":
          return "Copy the Tailwind config for your selected size.";
        case "tag":
          return "Copy the CSS for your selected HTML tag.";
        default:
          return "Copy the selected CSS to your clipboard.";
      }
    } else {
      switch (activeTab) {
        case "class":
          return "Copy all CSS classes with responsive font sizes.";
        case "vars":
          return "Copy all CSS custom properties for your :root selector.";
        case "tailwind":
          return "Copy complete Tailwind fontSize configuration object.";
        case "tag":
          return "Copy all HTML tag styles for automatic responsive typography.";
        default:
          return "Copy all generated CSS for your project.";
      }
    }
  },
};

// Make utilities available globally
window.TabDataMap = TabDataMap;
window.TabDataUtils = TabDataUtils;
