<?php

namespace WebPExpress;

use \WebPExpress\Option;

/**
 *   Store state in db
 *   We are using update_option WITHOUT autoloading.
 *   So this class is not intended for storing stuff that is needed on every page load.
 *   For such things, use update_option / get_option directly
 */


class State
{

    public static function getStateObj() {
        // TODO: cache
        $json = Option::getOption('webp-express-state', '[]');
        return json_decode($json, true);
    }

    /**
     *  Return state by key. Returns supplied default if key doesn't exist, or state object is corrupt
     */
    public static function getState($key, $default = null) {
        $obj = self::getStateObj();
        if ($obj != false) {
            if (isset($obj[$key])) {
                return $obj[$key];
            }
        }
        return $default;
    }

    public static function setState($key, $value) {
        $currentStateObj = self::getStateObj();
        $currentStateObj[$key] = $value;
        $json = json_encode($currentStateObj, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

        // Store in db. No autoloading.
        Option::updateOption('webp-express-state', $json, false);
    }
}
