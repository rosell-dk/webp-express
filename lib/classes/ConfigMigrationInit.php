<?php

namespace WebPExpress;

/**
 * Initialize config file migration on admin load (CVE-2025-11379 fix)
 * 
 * This class should be loaded early in the plugin initialization to ensure
 * config files are migrated from predictable names to randomized names
 * as soon as possible after plugin update.
 */
class ConfigMigrationInit
{
    /**
     * Register hooks for config migration
     * Should be called during plugin initialization
     */
    public static function init()
    {
        // Run migration check on admin_init (early enough to happen on any admin page load)
        add_action('admin_init', array(__CLASS__, 'runMigrationCheck'), 5);
        
        // Also run on plugin activation to catch updates
        add_action('activated_plugin', array(__CLASS__, 'runMigrationCheck'), 10);
    }
    
    /**
     * Run the migration check
     * This is called on admin_init and plugin activation
     */
    public static function runMigrationCheck()
    {
        // Use the optimized migration function that includes caching
        \WebPExpress\Config::checkAndMigrateConfigIfNeeded();
    }
}
