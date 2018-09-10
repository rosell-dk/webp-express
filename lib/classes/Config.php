<?php

namespace WebPExpress;

include_once "Paths.php";
use \WebPExpress\Paths;

include_once "State.php";
use \WebPExpress\State;

class Config
{

    public static function fileExists($filename) {
        return @file_exists($filename);
    }

    /**
     *  Return object or false, if config file does not exist, or read error
     */
    public static function loadJSONOptions($filename)
    {
        if (!self::fileExists($filename)) {
            return false;
        }
        $handle = @fopen($filename, "r");
        if ($handle === false) {
            return false;
        }
        $json = @fread($handle, filesize($filename));
        if ($json === false) {
            return false;
        }
        fclose($handle);

        $options = json_decode($json, true);
        if ($options === null) {
            return false;
        }
        return $options;
    }

    public static function saveJSONOptions($filename, $obj)
    {
        $result = @file_put_contents($filename, json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        /*if ($result === false) {
            echo 'COULD NOT' . $filename;
        }*/
        return ($result !== false);
    }


    public static function loadConfig()
    {
        return self::loadJSONOptions(Paths::getConfigFileName());
    }

    public static function isConfigFileThere()
    {
        return (self::fileexists(Paths::getConfigFileName()));
    }

    public static function isConfigFileThereAndOk()
    {
        return (self::loadConfig() !== false);
    }

    public static function loadWodOptions()
    {
        return self::loadJSONOptions(Paths::getWodOptionsFileName());
    }

    public static function saveConfigurationFile($config)
    {
        $config['paths-used-in-htaccess'] = [
            'existing' => Paths::getPathToExisting(),
            'wod-url-path' => Paths::getWodUrlPath(),
            'config-dir-rel' => Paths::getConfigDirRel()
        ];

        if (Paths::createConfigDirIfMissing()) {
            $success = self::saveJSONOptions(Paths::getConfigFileName(), $config);
            if ($success) {
                State::setState('configured', true);
            }
            return $success;
        }
        return false;
    }

    public static function generateWodOptionsFromConfigObj($config)
    {
        $options = $config;
        $options['converters'] = [];
        foreach ($config['converters'] as $converter) {
            if (isset($converter['deactivated'])) continue;

            $options['converters'][] = $converter;
        }
        foreach ($options['converters'] as &$c) {
            unset ($c['id']);
            if (!isset($c['options'])) {
                $c = $c['converter'];
            }
        }

        unset($options['image-types']);
        return $options;
    }

    public static function saveWodOptionsFile($options)
    {
        if (Paths::createConfigDirIfMissing()) {
            return self::saveJSONOptions(Paths::getWodOptionsFileName(), $options);
        }
        return false;
    }

    public static function generateHTAccessRulesFromConfigObj($config)
    {

        /* Calculate $fileExt */
        $imageTypes = $config['image-types'];
        $fileExtensions = [];
        if ($imageTypes & 1) {
          $fileExtensions[] = 'jpe?g';
        }
        if ($imageTypes & 2) {
          $fileExtensions[] = 'png';
        }
        $fileExt = implode('|', $fileExtensions);

        if ($imageTypes == 0) {
            return '# WebP Express disabled (no image types have been choosen to be converted)';
        }
        /* Build rules */
        $rules = '';
        $rules .= "<IfModule mod_rewrite.c>\n" .
        "  RewriteEngine On\n\n";

        $pathToExisting = Paths::getPathToExisting();

        /*
        // TODO: handle when wp-content is outside document root.
        // TODO: this should be made optional
        if (true) {
            # Redirect to existing converted image (under appropriate circumstances)
            $rules .= "  RewriteCond %{HTTP_ACCEPT} image/webp\n";
            $rules .= "  RewriteCond %{DOCUMENT_ROOT}/" . $pathToExisting . "/$1.$2.webp -f\n";
            $rules .= "  RewriteRule ^\/?(.*)\.(" . $fileExt . ")$ /" . $pathToExisting . "/$1.$2.webp [NC,T=image/webp,QSD,L]\n\n";
        }*/


        $rules .= "  # Redirect images to webp-on-demand.php (if browser supports webp)\n";
        $rules .= "  RewriteCond %{HTTP_ACCEPT} image/webp\n";
        if ($config['forward-query-string']) {
            $rules .= "  RewriteCond %{QUERY_STRING} (.*)\n";
        }
        $rules .= "  RewriteRule ^(.*)\.(" . $fileExt . ")$ " .
            "/" . Paths::getWodUrlPath() .
            "?source=%{SCRIPT_FILENAME}" .
            "&wp-content=" . Paths::getWPContentDirRel() .
            ($config['forward-query-string'] ? '&%1' : '') .
            " [NC,L]\n";

        $rules .="</IfModule>\n" .
        "AddType image/webp .webp\n";

        return $rules;
    }

    public static function doesHTAccessExists() {
        return self::fileExists(Paths::getHTAccessFilename());
    }

    public static function arePathsUsedInHTAccessOutdated() {
        if (!self::isConfigFileThere()) {
            // this properly means that rewrite rules have never been generated
            return false;
        }

        $pathsGoingToBeUsedInHtaccess = [
            'existing' => Paths::getPathToExisting(),
            'wod-url-path' => Paths::getWodUrlPath(),
            'config-dir-rel' => Paths::getConfigDirRel()
        ];

        $config = self::loadConfig();
        if ($config === false) {
            // corrupt or not readable
            return true;
        }

        foreach ($config['paths-used-in-htaccess'] as $prop => $value) {
            if ($value != $pathsGoingToBeUsedInHtaccess[$prop]) {
                return true;
            }
        }
    }

    public static function doesRewriteRulesNeedUpdate($newConfig) {
        if (!self::isConfigFileThere()) {
            // this properly means that rewrite rules have never been generated
            return true;
        }

        if (State::getState('last-attempt-to-save-htaccess-failed', false)) {
            return true;
        }

        $oldConfig = self::loadConfig();
        if ($oldConfig === false) {
            // corrupt or not readable
            return true;
        }

        $propsToCompare = ['forward-query-string', 'image-types'];

        foreach ($propsToCompare as $prop) {
            if ($newConfig[$prop] != $oldConfig[$prop]) {
                return true;
            }
        }

        if (!isset($oldConfig['paths-used-in-htaccess'])) {
            return true;
        }

        return self::arePathsUsedInHTAccessOutdated();
    }

    public static function saveHTAccessRules($rules) {
      $filename = Paths::getHTAccessFilename();

      if (!@file_exists($filename)) {
        return false;
      }

      $existingPermission = '';

      // Try to make .htaccess writable if its not
      if (@file_exists($filename) && !@is_writable($filename)) {
          // Store existing permissions, so we can revert later
          $existingPermission = octdec(substr(decoct(fileperms($filename)), -4));

          // Try to chmod.
          // It may fail, but we can ignore that. If it fails, insert_with_markers will also fail
          chmod($filename, 0550);
      }


      /* Add rules to .htaccess  */
      if (!function_exists('insert_with_markers')) {
          require_once ABSPATH . 'wp-admin/includes/misc.php';
      }

      $success = insert_with_markers($filename, 'WebP Express', $rules);

      State::setState('last-attempt-to-save-htaccess-failed', !$success);

      if ($success) {
          State::setState('htaccess-rules-saved-at-some-point', true);

          /* Revert File Permission  */
          if (!empty($existingPermission)) {
              @chmod($filename, $existingPermission);
          }
      }

      return $success;
    }

    public static function deactivateHTAccessRules() {
        return self::saveHTAccessRules('# Plugin is deactivated');
    }

    /**
     *  - Saves configuration file
     *   AND generates WOD options, and saves them too
     *   AND saves .htaccess
     */
    public static function saveConfiguration($config)
    {
        if (self::saveConfigurationFile($config)) {
            $options = self::generateWodOptionsFromConfigObj($config);
            if (self::saveWodOptionsFile($options)) {
                $rules = self::generateHTAccessRulesFromConfigObj($config);
                return self::saveHTAccessRules($rules);

            }
        } else {
            return false;
        }
    }

}
