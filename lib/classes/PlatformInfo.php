<?php

namespace WebPExpress;

class PlatformInfo
{

    public static function isMicrosoftIis()
    {
        $server = strtolower($_SERVER['SERVER_SOFTWARE']);
        return ( strpos( $server, 'microsoft-iis') !== false );
    }

    /**
     *  Check if Apache handles the PHP requests (Note that duel setups are possible and ie Nginx could be handling the image requests).
     */
    public static function isApache()
    {
        return (stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false);
    }

    public static function isLiteSpeed()
    {
        $server = strtolower($_SERVER['SERVER_SOFTWARE']);
        return ( strpos( $server, 'litespeed') !== false );
    }

    public static function isNginx()
    {
        return (stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false);
    }

    public static function isApacheOrLiteSpeed()
    {
        return self::isApache() || self::isLiteSpeed();
    }

    /**
     * Check if an Apache module is available.
     *
     * If apache_get_modules() exists, it is used. That function is however only available in mod_php installs.
     * Otherwise the Wordpress function "apache_mod_loaded" is tried, which examines phpinfo() output.
     * However, it seems there is no module output on php-fpm setups.
     * So on php-fpm, we cannot come with an answer.
     * https://stackoverflow.com/questions/9021425/how-to-check-if-mod-rewrite-is-enabled-in-php
     *
     * @param   string  $mod  Name of module - ie "mod_rewrite"
     * @return  boolean|null  Return if module is available, or null if indeterminate
     */
    public static function gotApacheModule($mod)
    {
        if (function_exists('apache_get_modules')) {
            return in_array($mod, apache_get_modules());
        }

        // Revert to Wordpress method, which examines output from phpinfo as well
        if (function_exists('apache_mod_loaded')) {
            $result = apache_mod_loaded($mod, null);

            // If we got a real result, return it.
            if ($result != null) {
                return $result;
            }
        }

        // We could run shell_exec("apachectl -l"), as suggested here:
        // https://stackoverflow.com/questions/9021425/how-to-check-if-mod-rewrite-is-enabled-in-php
        // But it does not seem to return all modules in my php-fpm setup.

        // Currently we got no more tools in this function...
        // you might want to take a look at the "htaccess_capability_tester" library...
        return null;

    }

    /**
     *  It is not always possible to determine if apache has a given module...
     *  We shall not fool anyone into thinking otherwise by providing a "got" method like Wordpress does...
     */
    public static function definitelyGotApacheModule($mod)
    {
        return (self::gotApacheModule($mod) === true);
    }

    public static function definitelyNotGotApacheModule($mod)
    {
        return (self::gotApacheModule($mod) === false);
    }

    /**
     * Check if mod_rewrite or IIS rewrite is available.
     *
     * @return  boolean|null  Return bool if it can be determined, or null if not
     */
    public static function gotRewriteModule()
    {
        $gotModRewrite = self::gotApacheModule('mod_rewrite');
        if (!is_null($gotModRewrite)) {
            return $gotModRewrite;
        }

        // Got the IIS check here: https://stackoverflow.com/a/21249745/842756
        // but have not tested it...
        if (isset($_SERVER['IIS_UrlRewriteModule'])) {
            return true;
        }
        return null;
    }


    public static function definitelyNotGotModRewrite()
    {
        return self::definitelyNotGotApacheModule('mod_rewrite');
    }

    public static function definitelyGotModEnv()
    {
        return self::definitelyGotApacheModule('mod_env');
    }


}
