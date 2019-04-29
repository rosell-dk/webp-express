<?php

namespace WebPExpress;

use \WebPExpress\Multisite;

class Option
{

    public static function getOption($optionName, $default = false)
    {
        if (Multisite::isNetworkActivated()) {
            return get_site_option($optionName, $default);
        } else {
            return get_option($optionName, $default);
        }
    }

    public static function updateOption($optionName, $value, $autoload = null)
    {
        if (Multisite::isNetworkActivated()) {
            //error_log('update option (network):' . $optionName . ':' . $value);
            return update_site_option($optionName, $value);
        } else {
            //error_log('update option:' . $optionName . ':' . $value);
            return update_option($optionName, $value, $autoload);
        }
    }

    public static function deleteOption($optionName)
    {
        if (Multisite::isNetworkActivated()) {
            return delete_site_option($optionName);
        } else {
            return delete_option($optionName);
        }

    }
}
