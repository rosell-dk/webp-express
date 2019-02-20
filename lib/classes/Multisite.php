<?php

namespace WebPExpress;

class Multisite
{
    public static $networkActive;

    /*
    Needed because is_plugin_active_for_network() does not return true right after network activation
    */
    public static function overrideIsNetworkActivated($networkActive)
    {
        self::$networkActive = $networkActive;
    }

    public static function isNetworkActivated()
    {
        if (!is_null(self::$networkActive)) {
            return self::$networkActive;
        }
        if (!self::isMultisite()) {
            return false;
        }
        if (!function_exists( 'is_plugin_active_for_network')) {
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        }
        return is_plugin_active_for_network('webp-express/webp-express.php');
    }

    public static function isMultisite()
    {
        return is_multisite();
    }

}
