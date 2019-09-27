<?php

namespace WebPExpress;

// This class does NOT, and MAY NOT rely on Wordpress functions (it is used in WebPOnDemand)
class EwwwTools
{
    /**
     *  Mark ewww api keys as non functional in config.json
     *
     *  @return  boolean  If it went well.
     */
    private static function markApiKeysAsNonFunctionalInConfig($apiKeysToMarkAsNonFunctional, $configDir)
    {
        $config = FileHelper::loadJSONOptions($configDir . '/config.json');
        if ($config === false) {
            return false;
        }
        if (!isset($config['converters'])) {
            return false;
        }

        if (!is_array($config['converters'])) {
            return false;
        }

        foreach ($config['converters'] as &$c) {
            if (!isset($c['converter'])) {
                continue;
            }
            if ($c['converter'] == 'ewww') {
                if (!isset($c['non-functional-api-keys'])) {
                    $c['non-functional-api-keys'] = [];
                }
                $c['non-functional-api-keys'] = array_unique(
                    array_merge($c['non-functional-api-keys'], $apiKeysToMarkAsNonFunctional)
                );

                // Do we have an api-key-2 which is not "blacklisted"?
                $haveBackupKey = (isset($c['options']['api-key-2']) && !empty($c['options']['api-key-2']));
                $switchToBackupKey = $haveBackupKey && (!in_array($c['options']['api-key-2'], $c['non-functional-api-keys']));

                if ($switchToBackupKey) {
                    $temp = $c['options']['api-key'];
                    $c['options']['api-key'] = $c['options']['api-key-2'];
                    $c['options']['api-key-2'] = $temp;
                } else {
                    // deactivate converter, we must then.
                    $c['deactivated'] = true;
                    $c['working'] = false;
                }

                //$successfulWrite = Config::saveConfigurationFileAndWodOptions($config);
                $successfulWrite = FileHelper::saveJSONOptions($configDir . '/config.json', $config);
                return $successfulWrite;
            }
        }
    }

    /**
     *  Remove ewww in wod-options.json
     *
     *  @return  boolean  If it went well.
     */
     private static function removeEwwwFromWodOptions($apiKeysToMarkAsNonFunctional, $configDir)
     {
         $wodOptions = FileHelper::loadJSONOptions($configDir . '/wod-options.json');
         if ($config === false) {
             return false;
         }
         if (!isset($wodOptions['webp-convert']['convert']['converters'])) {
             return false;
         }

         if (!is_array($wodOptions['webp-convert']['convert']['converters'])) {
             return false;
         }

         foreach ($wodOptions['webp-convert']['convert']['converters'] as $i => $c) {
             if (!isset($c['converter'])) {
                 continue;
             }
             if ($c['converter'] == 'ewww') {
                 //unset($wodOptions['webp-convert']['convert']['converters'][$i]);
                 array_splice($wodOptions['webp-convert']['convert']['converters'], $i, 1);

                 //$successfulWrite = Config::saveConfigurationFileAndWodOptions($config);
                 $successfulWrite = FileHelper::saveJSONOptions($configDir . '/wod-options.json', $wodOptions);
                 return $successfulWrite;
             }
         }
     }

    /**
     *  Mark ewww api keys as non functional.
     *
     *  Current implementation simply removes ewww from wod-options.json.
     *  It will reappear when options are saved - but be removed again upon next failure 
     *
     *  @return  boolean  If it went well.
     */
    public static function markApiKeysAsNonFunctional($apiKeysToMarkAsNonFunctional, $configDir)
    {
        //self::markApiKeysAsNonFunctionalInConfig($apiKeysToMarkAsNonFunctional, $configDir);

        // TODO: We should update the key to api-key-2 the first time.
        // But I am going to change the structure of wod-options so ewww becomes a stack converter, so
        // I don't bother implementing this right now.
        self::removeEwwwFromWodOptions($apiKeysToMarkAsNonFunctional, $configDir);

    }

}
