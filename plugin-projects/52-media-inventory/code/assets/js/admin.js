/**
 * Admin JS for Media Inventory plugin
 * Handles scanning, displaying results, and exporting data
 * Author: Jim R
 * Version: 2.0.0
 * Requires jQuery
 * License: GPLv2 or later
 */

// Utility to get ordered categories
function getOrderedCategories(categories) {
  const categoryOrder = [
    "Fonts",
    "SVG",
    "Images",
    "Videos",
    "Audio",
    "PDFs",
    "Documents",
    "Text Files",
    "Other Documents",
    "Other",
  ];
  const orderedCategories = [];

  categoryOrder.forEach(function (catName) {
    if (categories[catName]) {
      orderedCategories.push(catName);
    }
  });

  Object.keys(categories)
    .sort()
    .forEach(function (catName) {
      if (!orderedCategories.includes(catName)) {
        orderedCategories.push(catName);
      }
    });

  return orderedCategories;
}

// Main jQuery ready function
jQuery(document).ready(function ($) {
  let inventoryData = [];
  let isScanning = false;

  // Toggle functionality for about section
  $(".fcc-info-toggle").on("click", function () {
    const target = $(this).data("toggle-target");
    const content = $("#" + target);

    if (content.hasClass("expanded")) {
      content.removeClass("expanded");
      $(this).removeClass("expanded");
    } else {
      content.addClass("expanded");
      $(this).addClass("expanded");
    }
  });

  $("#start-scan").on("click", function () {
    if (isScanning) return;

    isScanning = true;
    inventoryData = [];

    $("#start-scan").prop("disabled", true).text("scanning...").hide();
    $("#stop-scan").show();
    $("#scan-progress").show();
    $("#summary-stats").hide();
    $("#export-csv, #clear-results").hide();
    $("#results-container").html(
      '<div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">Scanning in progress...</div>'
    );

    scanBatch(0);
  });

  $("#stop-scan").on("click", function () {
    isScanning = false;
    $("#start-scan").prop("disabled", false).text("🔍 start scan").show();
    $("#stop-scan").hide();
    $("#scan-progress").hide();
    $("#export-csv, #clear-results").show();
    $("#results-container").html(
      '<div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">Scan stopped. Click "start scan" to resume or "clear results" to start over.</div>'
    );
  });

  $("#export-csv").on("click", function () {
    if (inventoryData.length === 0) {
      alert("No data to export");
      return;
    }

    // Create form and submit
    const form = $("<form>", {
      method: "POST",
      action: mifData.ajaxUrl,
    });

    form.append(
      $("<input>", {
        type: "hidden",
        name: "action",
        value: "media_inventory_export",
      })
    );

    form.append(
      $("<input>", {
        type: "hidden",
        name: "nonce",
        value: mifData.nonce,
      })
    );

    form.append(
      $("<input>", {
        type: "hidden",
        name: "inventory_data",
        value: JSON.stringify(inventoryData),
      })
    );

    $("body").append(form);
    form.submit();
    form.remove();
  });

  $("#clear-results").on("click", function () {
    inventoryData = [];
    $("#results-container").html(
      '<div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">Click "start scan" to begin inventory scanning.</div>'
    );
    $("#summary-stats").hide();
    $("#export-csv, #clear-results").hide();
  });

  function scanBatch(offset) {
    if (!isScanning) return; // Check if user stopped the scan

    $.post({
      url: mifData.ajaxUrl,
      data: {
        action: "media_inventory_scan",
        nonce: mifData.nonce,
        offset: offset,
      },
      timeout: 30000, // 30 second timeout
    })
      .done(function (response) {
        if (!isScanning) return; // Check again in case user stopped during request

        if (response.success) {
          inventoryData = inventoryData.concat(response.data.data);

          // Show any errors
          if (response.data.errors && response.data.errors.length > 0) {
            console.warn("Scan warnings:", response.data.errors);
          }

          // Update progress
          const progress = Math.round(
            (response.data.processed / response.data.total) * 100
          );
          $("#progress-bar").css("width", progress + "%");
          $("#progress-text").text(
            response.data.processed + " / " + response.data.total + " processed"
          );

          if (response.data.complete) {
            // Scanning complete
            isScanning = false;
            $("#start-scan")
              .prop("disabled", false)
              .text("🔍 start scan")
              .show();
            $("#stop-scan").hide();
            $("#scan-progress").hide();
            $("#export-csv, #clear-results").show();

            displayResults();
          } else {
            // Continue scanning
            setTimeout(function () {
              scanBatch(response.data.offset);
            }, 500); // Small delay between batches
          }
        } else {
          alert("Error: " + response.data);
          isScanning = false;
          $("#start-scan").prop("disabled", false).text("🔍 start scan").show();
          $("#stop-scan").hide();
          $("#scan-progress").hide();
        }
      })
      .fail(function (xhr, status, error) {
        if (!isScanning) return; // User already stopped

        let errorMsg = "AJAX request failed";
        if (status === "timeout") {
          errorMsg =
            "Request timed out - try reducing batch size or check server resources";
        } else if (xhr.responseText) {
          errorMsg = "Server error: " + xhr.responseText.substring(0, 200);
        }

        alert(errorMsg);
        isScanning = false;
        $("#start-scan").prop("disabled", false).text("🔍 start scan").show();
        $("#stop-scan").hide();
        $("#scan-progress").hide();
      });
  }

  function displayResults() {
    if (inventoryData.length === 0) {
      $("#results-container").html(
        '<div style="text-align: center; padding: 40px; color: var(--clr-txt); font-style: italic;">No media files found.</div>'
      );
      return;
    }

    // Group by category
    const categories = {};
    const totals = {
      files: 0,
      size: 0,
      items: 0,
    };

    inventoryData.forEach((item) => {
      if (!categories[item.category]) {
        categories[item.category] = {
          items: [],
          totalSize: 0,
          totalFiles: 0,
          itemCount: 0,
        };
      }

      categories[item.category].items.push(item);
      categories[item.category].totalSize += item.total_size;
      categories[item.category].totalFiles += item.file_count;
      categories[item.category].itemCount++;

      totals.files += item.file_count;
      totals.size += item.total_size;
      totals.items++;
    });

    // Build results HTML
    let html = "";

    const mainOrderedCategories = getOrderedCategories(categories);

    // Then add any remaining categories alphabetically
    Object.keys(categories)
      .sort()
      .forEach(function (catName) {
        if (!mainOrderedCategories.includes(catName)) {
          mainOrderedCategories.push(catName);
        }
      });

    mainOrderedCategories.forEach(function (catName) {
      const category = categories[catName];

      html += '<div class="category-section">';
      html +=
        '<h3 class="category-header">' +
        catName +
        " (" +
        category.itemCount +
        " items, " +
        category.totalFiles +
        " files, " +
        formatBytes(category.totalSize) +
        ")</h3>";

      // Debug: Track HTML building order
      console.log("Building HTML for:", catName);
      if (catName === "Fonts") {
        // Group fonts by family
        const fontFamilies = {};
        category.items.forEach((item) => {
          const family = item.font_family || "Unknown Font";
          if (!fontFamilies[family]) {
            fontFamilies[family] = {
              items: [],
              totalSize: 0,
              totalFiles: 0,
            };
          }
          fontFamilies[family].items.push(item);
          fontFamilies[family].totalSize += item.total_size;
          fontFamilies[family].totalFiles += item.file_count;
        });

        html += '<table class="inventory-table">';
        html +=
          "<thead><tr><th>Font Family</th><th>Variants</th><th>Files</th><th>Total Size</th><th>Details</th></tr></thead>";
        html += "<tbody>";

        Object.keys(fontFamilies)
          .sort()
          .forEach((familyName) => {
            const family = fontFamilies[familyName];
            const variants = family.items
              .map((item) => item.extension.toUpperCase())
              .join(", ");
            const details = family.items
              .map(
                (item) =>
                  escapeHtml(item.title) +
                  " (" +
                  formatBytes(item.total_size) +
                  ")"
              )
              .join("<br>");

            html += "<tr>";
            html += "<td><strong>" + escapeHtml(familyName) + "</strong></td>";
            html += "<td>" + variants + "</td>";
            html += "<td>" + family.totalFiles + "</td>";
            html += "<td>" + formatBytes(family.totalSize) + "</td>";
            html += '<td class="file-details">' + details + "</td>";
            html += "</tr>";
          });

        html += "</tbody></table>";
      } else if (catName === "SVG") {
        // Regular display for SVG
        html += '<table class="inventory-table">';
        html +=
          "<thead><tr><th>Title</th><th>Extension</th><th>Dimensions</th><th>Files</th><th>Size</th><th>File Details</th></tr></thead>";
        html += "<tbody>";

        category.items.forEach((item) => {
          const fileDetails = item.files
            .map((f) => {
              let detail = f.type + ": " + formatBytes(f.size);
              if (f.dimensions) {
                detail += " (" + f.dimensions + ")";
              }
              return detail;
            })
            .join("<br>");

          html += "<tr>";
          html += "<td>" + escapeHtml(item.title) + "</td>";
          html += "<td>" + item.extension.toUpperCase() + "</td>";
          html += "<td>" + (item.dimensions || "Unknown") + "</td>";
          html += "<td>" + item.file_count + "</td>";
          html += "<td>" + formatBytes(item.total_size) + "</td>";
          html += '<td class="file-details">' + fileDetails + "</td>";
          html += "</tr>";
        });

        html += "</tbody></table>";
      } else if (catName === "Images") {
        // Group images by WordPress size categories
        const wpSizeCategories = {};

        category.items.forEach((item) => {
          item.files.forEach((file) => {
            const filename = file.filename || "";
            let sizeCategory = "Original Files";
            let sizeSuffix = "original";

            // Extract size suffix from filename (e.g., "-150", "-300x200", "-768")
            const sizeMatch = filename.match(/-(\d+)(?:x\d+)?(?:\.[^.]+)?$/);
            if (sizeMatch) {
              const width = parseInt(sizeMatch[1]);
              sizeSuffix = "-" + width;

              // Categorize by WordPress standard sizes
              if (width <= 150) {
                sizeCategory = "Thumbnails (≤150px)";
              } else if (width <= 300) {
                sizeCategory = "Small (151-300px)";
              } else if (width <= 768) {
                sizeCategory = "Medium (301-768px)";
              } else if (width <= 1024) {
                sizeCategory = "Large (769-1024px)";
              } else if (width <= 1536) {
                sizeCategory = "Extra Large (1025-1536px)";
              } else {
                sizeCategory = "Super Large (>1536px)";
              }
            }

            if (!wpSizeCategories[sizeCategory]) {
              wpSizeCategories[sizeCategory] = {
                items: [],
                totalSize: 0,
                totalFiles: 0,
                sizeSuffixes: new Set(),
              };
            }

            wpSizeCategories[sizeCategory].items.push({
              ...item,
              currentFile: file,
            });
            wpSizeCategories[sizeCategory].totalSize += file.size;
            wpSizeCategories[sizeCategory].totalFiles++;
            wpSizeCategories[sizeCategory].sizeSuffixes.add(sizeSuffix);
          });
        });

        // WordPress Image Sizes Summary title
        html +=
          '<h2 style="color: var(--clr-primary); margin: 20px 0 12px 0; text-align: center;">WordPress Image Sizes Summary</h2>';

        // Summary by WordPress size categories (three separate white columns)
        html +=
          '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 20px;">';

        const wpCategoryOrder = [
          "Original Files",
          "Thumbnails (≤150px)",
          "Small (151-300px)",
          "Medium (301-768px)",
          "Large (769-1024px)",
          "Extra Large (1025-1536px)",
          "Super Large (>1536px)",
        ];

        const sortedWpCategories = wpCategoryOrder.filter(
          (cat) => wpSizeCategories[cat]
        );

        // Split into three columns more evenly
        const leftColumn = [];
        const middleColumn = [];
        const rightColumn = [];

        sortedWpCategories.forEach((cat, index) => {
          if (index % 3 === 0) {
            leftColumn.push(cat);
          } else if (index % 3 === 1) {
            middleColumn.push(cat);
          } else {
            rightColumn.push(cat);
          }
        });
        // Left column
        html +=
          '<div style="background: white; border-radius: var(--jimr-border-radius); padding: 12px; box-shadow: var(--clr-shadow); border: 1px solid var(--jimr-gray-200);">';
        leftColumn.forEach((categoryName) => {
          const wpCategory = wpSizeCategories[categoryName];
          const leftSizeSuffixList = Array.from(wpCategory.sizeSuffixes).join(
            ", "
          );

          html +=
            '<div style="padding: 8px 0; border-bottom: 1px solid var(--jimr-gray-200);">';
          html +=
            '<div><strong style="color: var(--clr-secondary);">' +
            escapeHtml(categoryName) +
            "</strong><br>";
          html +=
            '<small style="color: var(--clr-txt);">Suffixes: ' +
            leftSizeSuffixList +
            "</small><br>";
          html +=
            '<small style="color: var(--clr-txt);">' +
            wpCategory.totalFiles +
            " files, " +
            formatBytes(wpCategory.totalSize) +
            "</small></div>";
          html += "</div>";
        });
        html += "</div>";

        // Middle column
        html +=
          '<div style="background: white; border-radius: var(--jimr-border-radius); padding: 12px; box-shadow: var(--clr-shadow); border: 1px solid var(--jimr-gray-200);">';
        middleColumn.forEach((categoryName) => {
          const wpCategory = wpSizeCategories[categoryName];
          const middleSizeSuffixList = Array.from(wpCategory.sizeSuffixes).join(
            ", "
          );

          html +=
            '<div style="padding: 8px 0; border-bottom: 1px solid var(--jimr-gray-200);">';
          html +=
            '<div><strong style="color: var(--clr-secondary);">' +
            escapeHtml(categoryName) +
            "</strong><br>";
          html +=
            '<small style="color: var(--clr-txt);">Suffixes: ' +
            middleSizeSuffixList +
            "</small><br>";
          html +=
            '<small style="color: var(--clr-txt);">' +
            wpCategory.totalFiles +
            " files, " +
            formatBytes(wpCategory.totalSize) +
            "</small></div>";
          html += "</div>";
        });
        html += "</div>";

        // Right column
        html +=
          '<div style="background: white; border-radius: var(--jimr-border-radius); padding: 12px; box-shadow: var(--clr-shadow); border: 1px solid var(--jimr-gray-200);">';
        rightColumn.forEach((categoryName) => {
          const wpCategory = wpSizeCategories[categoryName];
          const rightSizeSuffixList = Array.from(wpCategory.sizeSuffixes).join(
            ", "
          );

          html +=
            '<div style="padding: 8px 0; border-bottom: 1px solid var(--jimr-gray-200);">';
          html +=
            '<div><strong style="color: var(--clr-secondary);">' +
            escapeHtml(categoryName) +
            "</strong><br>";
          html +=
            '<small style="color: var(--clr-txt);">Suffixes: ' +
            rightSizeSuffixList +
            "</small><br>";
          html +=
            '<small style="color: var(--clr-txt);">' +
            wpCategory.totalFiles +
            " files, " +
            formatBytes(wpCategory.totalSize) +
            "</small></div>";
          html += "</div>";
        });
        html += "</div>";
        html += "</div>";

        // Image cards title
        html +=
          '<h2 style="color: var(--clr-primary); margin: 20px 0 12px 0; text-align: center;">Image Cards</h2>';

        // Individual image cards (detailed view) - Two column layout
        html +=
          '<div class="images-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">';

        category.items.forEach((item) => {
          html += '<div class="image-item">';
          html += '<div class="image-header">';

          // Add thumbnail if available
          if (item.thumbnail_url) {
            html += '<div class="image-thumbnail">';
            html += '<img src="' + escapeHtml(item.thumbnail_url) + '" ';
            html += 'alt="' + escapeHtml(item.title) + '" ';
            html += 'title="' + escapeHtml(item.title) + '" ';
            html += 'loading="lazy" ';
            html +=
              "onerror=\"this.parentElement.innerHTML='📷<br><small>Preview unavailable</small>'; this.parentElement.classList.add('error');\" />";
            html += "</div>";
          } else {
            html +=
              '<div class="image-thumbnail error">📷<br><small>No preview</small></div>';
          }

          html += '<div class="image-info">';
          html += "<strong>" + escapeHtml(item.title) + "</strong><br>";
          html +=
            '<span class="image-stats">(' +
            item.file_count +
            " files, " +
            formatBytes(item.total_size) +
            ")</span>";
          if (item.dimensions) {
            html +=
              '<br><span class="main-dimensions">Original: ' +
              item.dimensions +
              "</span>";
          }
          html += "</div>";
          html += "</div>";

          html += '<div class="image-files">';
          item.files.forEach((file) => {
            html += '<div class="file-item">';
            html +=
              '<span class="filename">' +
              escapeHtml(file.filename || "Unknown file") +
              "</span> ";
            html += '<span class="file-type">(' + file.type + ")</span> - ";
            html +=
              '<span class="file-dimensions">' +
              (file.dimensions || "Unknown size") +
              "</span> - ";
            html +=
              '<span class="file-size">' + formatBytes(file.size) + "</span>";
            html += "</div>";
          });
          html += "</div>";

          html += "</div>";
        });

        html += "</div>";
      }

      html += "</div>";
    });

    $("#results-container").html(html);

    // Update summary
    let summaryHtml = "";
    const orderedCategories = getOrderedCategories(categories);
    orderedCategories.forEach(function (catName) {
      const category = categories[catName];

      // Debug: Show processing order
      console.log("Processing category:", catName);
      summaryHtml +=
        '<div class="summary-item"><span>' +
        catName +
        ":</span><span>" +
        formatBytes(category.totalSize) +
        "</span></div>";
    });
    summaryHtml +=
      '<div class="summary-item"><span>Total:</span><span>' +
      formatBytes(totals.size) +
      "</span></div>";

    $("#summary-content").html(summaryHtml);
    $("#summary-stats").show();
  }

  // Utility to format bytes as human-readable text
  function formatBytes(bytes) {
    const units = ["B", "KB", "MB", "GB", "TB"];
    let size = Math.max(bytes, 0);
    let pow = Math.floor(Math.log(size) / Math.log(1024));
    pow = Math.min(pow, units.length - 1);
    size /= Math.pow(1024, pow);
    return Math.round(size * 100) / 100 + " " + units[pow];
  }

  // Utility to escape HTML
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
});
