<?php

namespace WebPExpress;

class PlatformInfo
{

    public static function isMicrosoftIis()
    {
        $server = strtolower($_SERVER['SERVER_SOFTWARE']);
        return ( strpos( $server, 'microsoft-iis') !== false );
    }

    public static function isApache()
    {
        $server = strtolower($_SERVER['SERVER_SOFTWARE']);
        return ( strpos( $server, 'apache') !== false );
    }

    public static function isLiteSpeed()
    {
        $server = strtolower($_SERVER['SERVER_SOFTWARE']);
        return ( strpos( $server, 'litespeed') !== false );
    }

    public static function isApacheOrLiteSpeed()
    {
        return self::isApache() || self::isLiteSpeed();
    }

    /**
     *  It is not always possible to determine if apache has a given module...
     *  We shall not fool anyone into thinking otherwise by providing a "got" method like Wordpress does...
     */
    public static function definitelyNotGotApacheModule($mod)
    {
        if (function_exists( 'apache_get_modules')) {
            $mods = apache_get_modules();
            if (!in_array($mod, $mods)) {
                return true;
            }
        }
        // TODO: Perhaps also try looking at phpinfo, like Wordpress does in apache_mod_loaded

        return false;
    }

    public static function definitelyGotApacheModule($mod)
    {
        if (function_exists( 'apache_get_modules')) {
            $mods = apache_get_modules();
            if (in_array($mod, $mods)) {
                return true;
            }
        }
        return false;
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
