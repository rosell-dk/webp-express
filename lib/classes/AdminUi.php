<?php

namespace WebPExpress;

use \WebPExpress\Multisite;

/**
 *
 */

class AdminUi
{

    // Add settings link on the plugins page
    // The hook was registred in AdminInit
    public static function pluginActionLinksFilter($links)
    {
        if (Multisite::isNetworkActivated()) {
            $mylinks= [
                '<a href="https://ko-fi.com/rosell" target="_blank">donate?</a>',
            ];
        } else {
            $mylinks = array(
                '<a href="' . admin_url('options-general.php?page=webp_express_settings_page') . '">Settings</a>',
                '<a href="https://wordpress.org/plugins/webp-express/#%0Ahow%20do%20i%20buy%20you%20a%20cup%20of%20coffee%3F%0A" target="_blank">Provide coffee for the developer</a>',
            );

        }
        return array_merge($links, $mylinks);
    }

    // Add settings link in multisite
    // The hook was registred in AdminInit
    public static function networkPluginActionLinksFilter($links)
    {
        $mylinks = array(
            '<a href="' . network_admin_url('settings.php?page=webp_express_settings_page') . '">Settings</a>',
            '<a href="https://ko-fi.com/rosell" target="_blank">donate?</a>',
        );
        return array_merge($links, $mylinks);
    }


    // callback for 'network_admin_menu' (registred in AdminInit)
    public static function networAdminMenuHook()
    {
        add_submenu_page(
            'settings.php', // Parent element
            'WebP Express settings (for network)', // Text in browser title bar
            'WebP Express', // Text to be displayed in the menu.
            'manage_network_options', // Capability
            'webp_express_settings_page', // slug
            array('\WebPExpress\OptionsPage', 'display') // Callback function which displays the page
        );

        add_submenu_page(
            'settings.php', // Parent element
            'WebP Express File Manager', //Page Title
            'WebP Express File Manager', //Menu Title
            'manage_network_options', //capability
            'webp_express_conversion_page', // slug
            array('\WebPExpress\WCFMPage', 'display') //The function to be called to output the content for this page.
        );

    }

    public static function adminMenuHookMultisite()
    {
        // Add Media page
        /*
        not ready - it should not display images for the other blogs!

        add_submenu_page(
          'upload.php', // Parent element
          'WebP Express', //Page Title
          'WebP Express', //Menu Title
          'manage_network_options', //capability
          'webp_express_conversion_page', // slug
          array('\WebPExpress\WCFMPage', 'display') //The function to be called to output the content for this page.
        );
        */

    }

    public static function adminMenuHook()
    {
        //Add Settings Page
        add_options_page(
            'WebP Express Settings', //Page Title
            'WebP Express', //Menu Title
            'manage_options', //capability
            'webp_express_settings_page', // slug
            array('\WebPExpress\OptionsPage', 'display') //The function to be called to output the content for this page.
        );

        // Add Media page
        add_media_page(
          'WebP Express', //Page Title
          'WebP Express', //Menu Title
          'manage_options', //capability
          'webp_express_conversion_page', // slug
          array('\WebPExpress\WCFMPage', 'display') //The function to be called to output the content for this page.
        );

    }
}
