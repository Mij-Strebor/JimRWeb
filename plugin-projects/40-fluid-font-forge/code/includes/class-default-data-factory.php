<?php

/**
 * Fluid Font Forge - Default Data Factory
 * 
 * Single source of truth for all default data used throughout the plugin.
 * Eliminates duplication between PHP and JavaScript by providing centralized defaults.
 * 
 * @package FluidFontForge
 * @since 4.0.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Default Data Factory Class
 * 
 * Provides all default configurations for settings, sizes, and constants.
 * Used by both PHP backend and JavaScript frontend for consistency.
 */
class FluidFontForgeDefaultData
{

    /**
     * Get all default data in a single call
     * 
     * @return array Complete default configuration
     */
    public static function getAllDefaults()
    {
        return [
            'settings' => self::getDefaultSettings(),
            'classSizes' => self::getDefaultClassSizes(),
            'variableSizes' => self::getDefaultVariableSizes(),
            'tagSizes' => self::getDefaultTagSizes(),
            'tailwindSizes' => self::getDefaultTailwindSizes(),
            'constants' => self::getConstants()
        ];
    }

    /**
     * Get default settings configuration
     * 
     * @return array Default settings
     */
    public static function getDefaultSettings()
    {
        return [
            'minRootSize' => FluidFontForge::DEFAULT_MIN_ROOT_SIZE,
            'maxRootSize' => FluidFontForge::DEFAULT_MAX_ROOT_SIZE,
            'minViewport' => FluidFontForge::DEFAULT_MIN_VIEWPORT,
            'maxViewport' => FluidFontForge::DEFAULT_MAX_VIEWPORT,
            'unitType' => 'px',
            'selectedClassSizeId' => 5,
            'selectedVariableSizeId' => 5,
            'selectedTagSizeId' => 7, // 'p' tag
            'activeTab' => 'class',
            'previewFontUrl' => '',
            'minScale' => FluidFontForge::DEFAULT_MIN_SCALE,
            'maxScale' => FluidFontForge::DEFAULT_MAX_SCALE,
            'autosaveEnabled' => true,
            'classBaseValue' => 'medium',
            'varsBaseValue' => '--fs-md',
            'tagBaseValue' => 'p'
        ];
    }

    /**
     * Get default class sizes
     * 
     * @return array Default class size configurations
     */
    public static function getDefaultClassSizes()
    {
        return [
            ['id' => 1, 'className' => 'xxxlarge', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 2, 'className' => 'xxlarge', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 3, 'className' => 'xlarge', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 4, 'className' => 'large', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 5, 'className' => 'medium', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 6, 'className' => 'small', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 7, 'className' => 'xsmall', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 8, 'className' => 'xxsmall', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT]
        ];
    }

    /**
     * Get default CSS variable sizes
     * 
     * @return array Default variable size configurations
     */
    public static function getDefaultVariableSizes()
    {
        return [
            ['id' => 1, 'variableName' => '--fs-xxxl', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 2, 'variableName' => '--fs-xxl', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 3, 'variableName' => '--fs-xl', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 4, 'variableName' => '--fs-lg', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 5, 'variableName' => '--fs-md', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 6, 'variableName' => '--fs-sm', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 7, 'variableName' => '--fs-xs', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 8, 'variableName' => '--fs-xxs', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT]
        ];
    }

    /**
     * Get default HTML tag sizes
     * 
     * @return array Default tag size configurations
     */
    public static function getDefaultTagSizes()
    {
        return [
            ['id' => 1, 'tagName' => 'h1', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 2, 'tagName' => 'h2', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 3, 'tagName' => 'h3', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 4, 'tagName' => 'h4', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 5, 'tagName' => 'h5', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 6, 'tagName' => 'h6', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 7, 'tagName' => 'p', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT]
        ];
    }

    /**
     * Get default Tailwind sizes
     * 
     * @return array Default Tailwind size configurations
     */
    public static function getDefaultTailwindSizes()
    {
        return [
            ['id' => 1, 'tailwindName' => '4xl', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 2, 'tailwindName' => '3xl', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 3, 'tailwindName' => '2xl', 'lineHeight' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT],
            ['id' => 4, 'tailwindName' => 'xl', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 5, 'tailwindName' => 'base', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 6, 'tailwindName' => 'lg', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 7, 'tailwindName' => 'sm', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT],
            ['id' => 8, 'tailwindName' => 'xs', 'lineHeight' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT]
        ];
    }

    /**
     * Get all constants for JavaScript access
     * 
     * @return array All plugin constants
     */
    public static function getConstants()
    {
        return [
            'DEFAULT_MIN_ROOT_SIZE' => FluidFontForge::DEFAULT_MIN_ROOT_SIZE,
            'DEFAULT_MAX_ROOT_SIZE' => FluidFontForge::DEFAULT_MAX_ROOT_SIZE,
            'DEFAULT_MIN_VIEWPORT' => FluidFontForge::DEFAULT_MIN_VIEWPORT,
            'DEFAULT_MAX_VIEWPORT' => FluidFontForge::DEFAULT_MAX_VIEWPORT,
            'DEFAULT_MIN_SCALE' => FluidFontForge::DEFAULT_MIN_SCALE,
            'DEFAULT_MAX_SCALE' => FluidFontForge::DEFAULT_MAX_SCALE,
            'DEFAULT_HEADING_LINE_HEIGHT' => FluidFontForge::DEFAULT_HEADING_LINE_HEIGHT,
            'DEFAULT_BODY_LINE_HEIGHT' => FluidFontForge::DEFAULT_BODY_LINE_HEIGHT,
            'BROWSER_DEFAULT_FONT_SIZE' => FluidFontForge::BROWSER_DEFAULT_FONT_SIZE,
            'CSS_UNIT_CONVERSION_BASE' => FluidFontForge::CSS_UNIT_CONVERSION_BASE,
            'MIN_ROOT_SIZE_RANGE' => FluidFontForge::MIN_ROOT_SIZE_RANGE,
            'VIEWPORT_RANGE' => FluidFontForge::VIEWPORT_RANGE,
            'LINE_HEIGHT_RANGE' => FluidFontForge::LINE_HEIGHT_RANGE,
            'SCALE_RANGE' => FluidFontForge::SCALE_RANGE,
            'VALID_UNITS' => FluidFontForge::VALID_UNITS,
            'VALID_TABS' => FluidFontForge::VALID_TABS
        ];
    }
    /**
     * Get default sizes for a specific type
     * 
     * @param string $type Size type ('class', 'vars', 'tag', 'tailwind')
     * @return array Default sizes for the specified type
     */
    public static function getDefaultSizesByType($type)
    {
        switch ($type) {
            case 'class':
                return self::getDefaultClassSizes();
            case 'vars':
                return self::getDefaultVariableSizes();
            case 'tag':
                return self::getDefaultTagSizes();
            case 'tailwind':
                return self::getDefaultTailwindSizes();
            default:
                return [];
        }
    }

    /**
     * Get property name for a size type
     * 
     * @param string $type Size type
     * @return string Property name (e.g., 'className', 'variableName')
     */
    public static function getPropertyNameForType($type)
    {
        $propertyMap = [
            'class' => 'className',
            'vars' => 'variableName',
            'tag' => 'tagName',
            'tailwind' => 'tailwindName'
        ];

        return $propertyMap[$type] ?? 'className';
    }

    /**
     * Validate size type
     * 
     * @param string $type Size type to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidSizeType($type)
    {
        return in_array($type, ['class', 'vars', 'tag', 'tailwind']);
    }
}
