<?php

namespace WebPExpress;

include_once "Config.php";
use \WebPExpress\Config;

include_once "FileHelper.php";
use \WebPExpress\FileHelper;

include_once "Paths.php";
use \WebPExpress\Paths;

include_once "State.php";
use \WebPExpress\State;

class HTAccess
{

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
        $rules .= "  RewriteCond %{REQUEST_FILENAME} -f\n";
        if ($config['forward-query-string']) {
            $rules .= "  RewriteCond %{QUERY_STRING} (.*)\n";
        }
        $rules .= "  RewriteRule ^(.*)\.(" . $fileExt . ")$ " .
            "/" . Paths::getWodUrlPath() .
            "?xsource=x%{SCRIPT_FILENAME}" .
            "&wp-content=" . Paths::getWPContentDirRel() .
            ($config['forward-query-string'] ? '&%1' : '') .
            " [NC,L]\n";

        $rules .="</IfModule>\n" .
        "AddType image/webp .webp\n";

        return $rules;
    }

    public static function generateHTAccessRulesFromConfigFile() {
        if (Config::isConfigFileThereAndOk()) {
            return self::generateHTAccessRulesFromConfigObj(Config::loadConfig());
        } else {
            return false;
        }
    }

    public static function arePathsUsedInHTAccessOutdated() {
        if (!Config::isConfigFileThere()) {
            // this properly means that rewrite rules have never been generated
            return false;
        }

        $pathsGoingToBeUsedInHtaccess = [
            'existing' => Paths::getPathToExisting(),
            'wod-url-path' => Paths::getWodUrlPath(),
            'config-dir-rel' => Paths::getConfigDirRel()
        ];

        $config = Config::loadConfig();
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
        if (!Config::isConfigFileThere()) {
            // this properly means that rewrite rules have never been generated
            return true;
        }

        $oldConfig = Config::loadConfig();
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

    /**
     *  Must be parsed ie "wp-content", "index", etc. Not real dirs
     */
    public static function addToActiveHTAccessDirsArray($whichDir)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        if (!in_array($whichDir, $activeHtaccessDirs)) {
            $activeHtaccessDirs[] = $whichDir;
            State::setState('active-htaccess-dirs', array_values($activeHtaccessDirs));
        }
    }

    public static function removeFromActiveHTAccessDirsArray($whichDir)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        if (in_array($whichDir, $activeHtaccessDirs)) {
            $activeHtaccessDirs = array_diff($activeHtaccessDirs, [$whichDir]);
            State::setState('active-htaccess-dirs', array_values($activeHtaccessDirs));
        }
    }

    public static function isInActiveHTAccessDirsArray($whichDir)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        return (in_array($whichDir, $activeHtaccessDirs));
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
            case Paths::getUploadDirAbs():
                return 'uploads';
        }
        return '';
    }

    public static function hasRecordOfSavingHTAccessToDir($dir) {
        $whichDir = self::whichHTAccessDirIsThis($dir);
        if ($whichDir != '') {
            return self::isInActiveHTAccessDirsArray($whichDir);
        }
        return false;
    }


    /**
     *  Sneak peak into .htaccess to see if we have rules in it
     *  This may not be possible.
     *  Return true, false, or null if we just can't tell
     */
    public static function haveWeRulesInThisHTAccess($filename) {
        if (FileHelper::fileExists($filename)) {
            $content = FileHelper::loadFile($filename);
            if ($content === false) {
                return null;
            }
            return (strpos($content, '# Redirect images to webp-on-demand.php') != false);
        } else {
            // the .htaccess isn't even there. So there are no rules.
            return false;
        }
    }

    public static function haveWeRulesInThisHTAccessBestGuess($filename)
    {
        // First try to sneak peak. May return null if it cannot be determined.
        $result = self::haveWeRulesInThisHTAccess($filename);
        if ($result === true) {
            return true;
        }
        if ($result === null) {
            // We were not allowed to sneak-peak.
            // Well, good thing that we stored successful .htaccess write locations ;)
            // If we recorded a successful write, then we assume there are still rules there
            $dir = FileHelper::dirName($filename);
            return self::hasRecordOfSavingHTAccessToDir($dir);
        }
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

        if ($success) {
            State::setState('htaccess-rules-saved-at-some-point', true);

            $containsRules = (strpos(implode('',$rules), '# Redirect images to webp-on-demand.php') != false);

            $dir = FileHelper::dirName($filename);
            $whichDir = self::whichHTAccessDirIsThis($dir);
            if ($whichDir != '') {
                if ($containsRules) {
                    self::addToActiveHTAccessDirsArray($whichDir);
                } else {
                    self::removeFromActiveHTAccessDirsArray($whichDir);
                }
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
        $uploadDir = Paths::getUploadDirAbs();

        $dirsToClean = [$indexDir, $homeDir, $wpContentDir, $pluginDir, $uploadDir];

        $failures = [];

        foreach ($dirsToClean as $dir) {
            $filename = $dir . '/.htaccess';
            if (!FileHelper::fileExists($filename)) {
                continue;
            } else {
                if (self::haveWeRulesInThisHTAccessBestGuess($filename)) {
                    if (!self::saveHTAccessRulesToFile($filename, '# Plugin is deactivated', false)) {
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

    public static function testLinks($config) {
        if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
            if ($config['image-types'] != 0) {
                $webpExpressRoot = Paths::getPluginUrlPath();
                return '<br>' .
                    '<a href="/' . $webpExpressRoot . '/test/test.jpg?debug&time=' . time() . '" target="_blank">Convert test image (show debug)</a><br>' .
                    '<a href="/' . $webpExpressRoot . '/test/test.jpg?' . time() . '" target="_blank">Convert test image</a><br>';
            }
        }
        return '';
    }


    public static function getHTAccessDirRequirements() {
        $minRequired = 'index';
        if (Paths::isWPContentDirMovedOutOfAbsPath()) {
            $minRequired = 'wp-content';
            $pluginToo = Paths::isPluginDirMovedOutOfWpContent() ? 'yes' : 'no';
            $uploadToo = Paths::isUploadDirMovedOutOfWPContentDir() ? 'yes' : 'no';
        } else {
            // plugin requirement depends...
            // - if user grants access to 'index', the requirement is Paths::isPluginDirMovedOutOfAbsPath()
            // - if user grants access to 'wp-content', the requirement is Paths::isPluginDirMovedOutOfWpContent()
            $pluginToo = 'depends';

            // plugin requirement depends...
            // - if user grants access to 'index', we should be fine, as UPLOADS is always in ABSPATH.
            // - if user grants access to 'wp-content', the requirement is Paths::isUploadDirMovedOutOfWPContentDir()
            $uploadToo = 'depends';
        }

        return [
            $minRequired,
            $pluginToo,      // 'yes', 'no' or 'depends'
            $uploadToo
        ];
    }

    /**
     *  Try to save the rules.
     *  Returns many details
     */
    public static function saveRules($config) {

        $rules = HTAccess::generateHTAccessRulesFromConfigObj($config);

        list($minRequired, $pluginToo, $uploadToo) = self::getHTAccessDirRequirements();

        $indexDir = Paths::getIndexDirAbs();
        $wpContentDir = Paths::getWPContentDirAbs();

        $acceptableDirs = [
            $wpContentDir
        ];
        if ($minRequired == 'index') {
            $acceptableDirs[] = $indexDir;
        }

        $overidingRulesInWpContentWarning = false;
        $result = HTAccess::saveHTAccessRulesToFirstWritableHTAccessDir($acceptableDirs, $rules);
        if ($result == $wpContentDir) {
            $mainResult = 'wp-content';
            //if (self::haveWeRulesInThisHTAccessBestGuess($indexDir . '/.htaccess')) {
            HTAccess::saveHTAccessRulesToFile($indexDir . '/.htaccess', '# WebP Express has placed its rules in your wp-content dir. Go there.', false);
            //}
        } elseif ($result == $indexDir) {
            $mainResult = 'index';
            $overidingRulesInWpContentWarning = self::haveWeRulesInThisHTAccessBestGuess($wpContentDir . '/.htaccess');
        } elseif ($result === false) {
            $mainResult = 'failed';
        }

        /* plugin */
        if ($pluginToo == 'depends') {
            if ($mainResult == 'wp-content') {
                $pluginToo = (Paths::isPluginDirMovedOutOfWpContent() ? 'yes' : 'no');
            } elseif ($mainResult == 'index') {
                $pluginToo = (Paths::isPluginDirMovedOutOfAbsPath() ? 'yes' : 'no');
            } else {
                // $result must be false. So $pluginToo should still be 'depends'
            }
        }
        $pluginFailed = false;
        $pluginFailedBadly = true;
        if ($pluginToo == 'yes') {
            $pluginDir = Paths::getPluginDirAbs();
            $pluginFailed = !(HTAccess::saveHTAccessRulesToFile($pluginDir . '/.htaccess', $rules, true));
            if ($pluginFailed) {
                $pluginFailedBadly = self::haveWeRulesInThisHTAccessBestGuess($pluginDir . '/.htaccess');
            }
        }

        /* upload */
        if ($uploadToo == 'depends') {
            if ($mainResult == 'wp-content') {
                $uploadToo = (Paths::isUploadDirMovedOutOfWPContentDir() ? 'yes' : 'no');
            } elseif ($mainResult == 'index') {
                $uploadToo = (Paths::isUploadDirMovedOutOfAbsPath() ? 'yes' : 'no');
            } else {
                // $result must be false. So $uploadToo should still be 'depends'
            }
        }
        $uploadFailed = false;
        $uploadFailedBadly = true;
        if ($uploadToo == 'yes') {
            $uploadDir = Paths::getUploadDirAbs();
            $uploadFailed = !(HTAccess::saveHTAccessRulesToFile($uploadDir . '/.htaccess', $rules, true));
            if ($uploadFailed) {
                $uploadFailedBadly = self::haveWeRulesInThisHTAccessBestGuess($uploadDir . '/.htaccess');
            }
        }

        return [
            'mainResult' => $mainResult,                // 'index', 'wp-content' or 'failed'
            'minRequired' => $minRequired,              // 'index' or 'wp-content'
            'overidingRulesInWpContentWarning' => $overidingRulesInWpContentWarning,  // true if main result is 'index' but we cannot remove those in wp-content
            'rules' => $rules,                          // The rules we generated
            'pluginToo' => $pluginToo,                  // 'yes', 'no' or 'depends'
            'pluginFailed' => $pluginFailed,            // true if failed to write to plugin folder (it only tries that, if pluginToo == 'yes')
            'pluginFailedBadly' => $pluginFailedBadly,  // true if plugin failed AND it seems we have rewrite rules there
            'uploadToo' => $uploadToo,                  // 'yes', 'no' or 'depends'
            'uploadFailed' => $uploadFailed,
            'uploadFailedBadly' => $uploadFailedBadly,
        ];
    }
}
