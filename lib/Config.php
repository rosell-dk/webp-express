<?php

namespace WebPExpress;

require_once "Paths.php";

use \WebPExpress\Paths;

class Config
{

    private static function loadJSONOptions($filename)
    {
        $handle = @fopen($filename, "r");
        $json = fread($handle, filesize($filename));
        fclose($handle);

        $options = json_decode($json, true);
        return $options;
    }

    private static function saveJSONOptions($filename, $obj)
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

    public static function loadWodOptions()
    {
        return self::loadJSONOptions(Paths::getWodOptionsFileName());
    }

    public static function saveConfigurationFile($config)
    {
        if (Paths::createConfigDirIfMissing()) {
            return self::saveJSONOptions(Paths::getConfigFileName(), $config);
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
            foreach ($options['converters'] as &$c) {
                unset ($c['id']);
                if (!isset($c['options'])) {
                    $c = $c['converter'];
                }
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
        /*
        if (empty(get_option('webp-express-configured')) || empty(get_option('webp_express_converters'))) {
            // This should not happen, because generateHTAccessRules should not be called at this stage.
            // But if it did happen anyway, better to exit with a comment than failing totally.
            return '# We shall wait with generating the .htaccess until WebP Express has been configured...';
        }*/

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
            "&config-path=" . Paths::getConfigDirRel() .
            ($config['forward-query-string'] ? '&%1' : '') .
            " [NC,L]\n";

        $rules .="</IfModule>\n" .
        "AddType image/webp .webp\n";

        return $rules;
    }

    public static function saveHTAccessRules($rules) {
      $root_path = Paths::getHomeDirAbs();

      $root_path .= '/';

      if (!file_exists($root_path . '.htaccess')) {
        return false;
      }

      $file_existing_permission = '';

      // Try to make .htaccess writable if its not
      if (file_exists($root_path . '.htaccess') && !is_writable($root_path . '.htaccess')) {
          // Store existing permissions, so we can revert later
          $file_existing_permission = octdec(substr(decoct(fileperms($root_path . '.htaccess')), -4));

          // Try to chmod.
          // It may fail, but we can ignore that. If it fails, insert_with_markers will also fail
          chmod($root_path . '.htaccess', 0550);
      }


      /* Add rules to .htaccess  */
      if (!function_exists('insert_with_markers')) {
          require_once ABSPATH . 'wp-admin/includes/misc.php';
      }
      if (!insert_with_markers($root_path . '.htaccess', 'WebP Express', $rules)) {
        return false;
      }
      else {
        /* Revert File Permission  */
        if (!empty($file_existing_permission)) {
            chmod($root_path . '.htaccess', $file_existing_permission);
        }
        return true;
      }

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
