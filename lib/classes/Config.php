<?php

namespace WebPExpress;

include_once "FileHelper.php";
use \WebPExpress\FileHelper;

include_once "Paths.php";
use \WebPExpress\Paths;

include_once "State.php";
use \WebPExpress\State;

class Config
{

    /**
     *  Return object or false, if config file does not exist, or read error
     */
    public static function loadJSONOptions($filename)
    {
        $json = FileHelper::loadFile($filename);
        if ($json === false) {
            return false;
        }

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
        return (FileHelper::fileExists(Paths::getConfigFileName()));
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

        // The next line sets an environment variable.
        // On the options page, we verify if this is set to diagnose if "AllowOverride None" is presented in 'httpd.conf'
        //$rules .= "# The following SetEnv allows to diagnose if .htaccess files are turned off\n";
        //$rules .= "SetEnv HTACCESS on\n\n";

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

    public static function generateHTAccessRulesFromConfigFile() {
        if (self::isConfigFileThereAndOk()) {
            return self::generateHTAccessRulesFromConfigObj(self::loadConfig());
        } else {
            return false;
        }
    }


    public static function doesHTAccessExists() {
        return FileHelper::fileExists(Paths::getHTAccessFilename());
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

    public static function saveHTAccessRulesToFile($filename, $rules, $createIfMissing = false) {
        if (!@file_exists($filename)) {
            if (!$createIfMissing) {
                return false;
            }
            // insert_with_markers will create file if it doesn't exist, so we can continue...
        }

        $existingFilePermission = null;
        $existingDirPermission = null;

        // Try to make .htaccess writable if its not
        if (@file_exists($filename)) {
            if (!@is_writable($filename)) {
                $existingFilePermission = FileHelper::filePerm($filename);
                @chmod($filename, 0664);        // chmod may fail, we know...
            }
        } else {
            $dir = FileHelper::dirName($filename);
            if (!@is_writable($dir)) {
                $existingDirPermission = FileHelper::filePerm($dir);
                @chmod($dir, 0775);
            }
        }

        /* Add rules to .htaccess  */
        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        // Convert to array, because string version has bugs in Wordpress 4.3
        $rules = explode("\n", $rules);
        $success = insert_with_markers($filename, 'WebP Express', $rules);

        // Revert file or dir permissions
        if (!is_null($existingFilePermission)) {
            @chmod($filename, $existingFilePermission);
        }
        if (!is_null($existingDirPermission)) {
            @chmod($dir, $existingDirPermission);
        }

        State::setState('last-attempt-to-save-htaccess-failed', !$success);

        if ($success) {
            State::setState('htaccess-rules-saved-at-some-point', true);

            $containsRules = (strpos(implode('',$rules), '# Redirect images to webp-on-demand.php') != false);

            $dir = FileHelper::dirName($filename);
            $whichDir = self::whichHTAccessDirIsThis($dir);
            if ($whichDir != '') {
                State::setState('htaccess-rules-saved-to-' . $whichDir, $containsRules);
            }
        }

        return $success;
    }

    public static function saveHTAccessRulesToFirstWritableHTAccessDir($dirs, $rules)
    {
        foreach ($dirs as $dir) {
            if (self::saveHTAccessRulesToFile($dir . '/.htaccess', $rules, true)) {
                return $dir;
            }
        }
        return false;
    }


    public static function saveHTAccessRules($rules) {
        //$filename = Paths::getHTAccessFilename();
        //$success = self::saveHTAccessRulesToFile(Paths::getHTAccessFilename(), $rules);

        $createIfMissing = true;

        // First try to save to index dir.
        // (index dir might be in a subfolder to home dir - but not the other way, right?)
        $success = self::saveHTAccessRulesToFile(Paths::getIndexDirAbs() . '/.htaccess', $rules, $createIfMissing);

        if (!$success) {
            // If index dir failed, try to save to homedir.
            // It is a more risky place in terms of other mod_rewrite stuff interfering
            $success = self::saveHTAccessRulesToFile(Paths::getHomeDirAbs() . '/.htaccess', $rules, $createIfMissing);
        }

        // Regardles of how the above went, we now try to save the rules in the wp-content folder
        // This is the least risky place in terms of other mod_rewrite stuff interfering
        if (self::saveHTAccessRulesToFile(Paths::getWPContentDirAbs() . '/.htaccess', $rules, $createIfMissing)) {
            $success = true;
            if (Paths::isPluginDirMovedOutOfWpContent()) {
                $success = self::saveHTAccessRulesToFile(Paths::getPluginDirAbs() . '/.htaccess', $rules, $createIfMissing);
            }
        } else {
            if (Paths::isWPContentDirMovedOutOfAbsPath()) {
                // We absolutely need rules in this dir, when wp-content is moved out of root
                $success = false;
            } elseif (Paths::isPluginDirMovedOutOfAbsPath()) {
                $success = self::saveHTAccessRulesToFile(Paths::getPluginDirAbs() . '/.htaccess', $rules, $createIfMissing);
            }
        }
/*
        Paths::getIndexDirAbs() . '/.htaccess'
Paths::getHomeDirAbs();

        $createIfMissing
        if (Paths::isWPContentDirMovedOutOfAbsPath()) {
            // If main .htaccess exists, it probably means that .htaccess files are generally working on this site
            // So we can create them other places.
            $createIfMissing = self::doesHTAccessExists();
            $success = $success && self::saveHTAccessRulesToFile(Paths::getWPContentDirAbs() . '/.htaccess', $rules, $createIfMissing);
            if (Paths::isPluginDirMovedOutOfWpContent()) {

            }
        } elseif (Paths::isPluginDirMovedOutOfAbsPath()) {
            $createIfMissing = self::doesHTAccessExists();
            $success = $success && self::saveHTAccessRulesToFile(Paths::getPluginDirAbs() . '/.htaccess', $rules, $createIfMissing);
        }*/
        return $success;
    }

    public static function whichHTAccessDirIsThis($dir) {
        switch ($dir) {
            case Paths::getWPContentDirAbs():
                return 'wp-content';
            case Paths::getIndexDirAbs():
                return 'index';
            case Paths::getHomeDirAbs():
                return 'home';
            case Paths::getPluginDirAbs():
                return 'plugins';
        }
        return '';
    }

    public static function hasRecordOfSavingHTAccessToDir($dir) {
        $whichDir = self::whichHTAccessDirIsThis($dir);
        if ($whichDir != '') {
            return State::getState('htaccess-rules-saved-to-' . $whichDir, false);
        }
        return false;
    }

    /**
     *  Try to deactivate all .htaccess rules.
     *  If success, we return true.
     *  If we fail, we return an array of filenames that have problems
     */
    public static function deactivateHTAccessRules() {
        //return self::saveHTAccessRules('# Plugin is deactivated');
        $indexDir = Paths::getIndexDirAbs();
        $homeDir = Paths::getHomeDirAbs();
        $wpContentDir = Paths::getWPContentDirAbs();
        $pluginDir = Paths::getPluginDirAbs();

        $dirsToClean = [$indexDir, $homeDir, $wpContentDir, $pluginDir];

        $failures = [];

        foreach ($dirsToClean as $dir) {
            $filename = $dir . '/.htaccess';
            if (FileHelper::fileExists($filename)) {
                continue;
            } else {
                //if (State::getState('htaccess-rules-saved-at-some-point', false)) {

                // Have we rules in this file? (note: may return null if it cannot be determined)
                $result = self::haveWeRulesInThisHTAccess($filename);
                if ($result === true) {
                    if (!saveHTAccessRulesToFile($filename, '# Plugin is deactivated', false)) {
                        $failures[] = $filename;
                    }
                }
                if ($result === null) {
                    // We were not allowed to sneak-peak.
                    // What to do?
                    // We are surely not allowed to change the file either.
                    // But how to decide if there are rules in that file?
                    // Well, good thing that we stored successful .htaccess write locations ;)
                    // If we recorded a successful write, then we assume there are still rules there
                    if (self::hasRecordOfSavingHTAccessToDir($dir)) {
                        $failures[] = $filename;
                    }
                }
            }
        }
        if (count($failures) == 0) {
            return true;
        }
        return $failures;
    }

    /**
     *  Sneak peak into .htaccess to see if we have rules in it
     *  This may not be possible.
     *  Return true, false, or null if we just can't tell
     */
    public static function haveWeRulesInThisHTAccess($filename) {
        $content = FileHelper::loadFile($filename);
        if ($content === false) {
            return null;
        }
        return (strpos($content, '# Redirect images to webp-on-demand.php') != false);
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
