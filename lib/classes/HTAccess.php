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

        if (State::getState('last-attempt-to-save-htaccess-failed', false)) {
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
            State::setState('active-htaccess-dirs', $activeHtaccessDirs);
        }
    }

    public static function removeFromActiveHTAccessDirsArray($whichDir)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        if (in_array($whichDir, $activeHtaccessDirs)) {
            $activeHtaccessDirs = array_diff($activeHtaccessDirs, [$whichDir]);
            State::setState('active-htaccess-dirs', $activeHtaccessDirs);
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
        $content = FileHelper::loadFile($filename);
        if ($content === false) {
            return null;
        }
        return (strpos($content, '# Redirect images to webp-on-demand.php') != false);
    }

    function haveWeRulesInThisHTAccessBestGuess($filename)
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

        State::setState('last-attempt-to-save-htaccess-failed', !$success);

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

        $dirsToClean = [$indexDir, $homeDir, $wpContentDir, $pluginDir];

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

    /**
     *  Try to save the rules.
     *  - First tries to save to wp-content, index or home.
     *  - If none of this succeeds, ['dir' => 'failed'] is returned
     *  - Otherwise we look to see if we also need to save in plugin dir.
     *
     *  Returns an array [
     *     'dir' => 'failed' or the dir (ie 'wp-content')
     *     'pluginToo' => boolean indicating if there need to be an .htaccess in plugin too
     *     'pluginSuccess' => boolean indicating if .htaccess was saved in plugins
     *  ]
     */
    public static function saveRules($config) {

        $rules = HTAccess::generateHTAccessRulesFromConfigObj($config);

        $indexDir = Paths::getIndexDirAbs();
        $homeDir = Paths::getHomeDirAbs();
        $wpContentDir = Paths::getWPContentDirAbs();

        $writeToPluginsDirToo = false;

        $result = HTAccess::saveHTAccessRulesToFirstWritableHTAccessDir([$wpContentDir, $indexDir, $homeDir], $rules);

        $dir = '';
        if ($result == false) {
            return ['dir' => 'failed', 'pluginToo' => false];
        } else {
            if ($result == $wpContentDir) {
                $writeToPluginsDirToo = Paths::isPluginDirMovedOutOfWpContent();
                $dir = 'wp-content';
            } else {
                if ($result == $homeDir) {
                    $dir = 'home';
                } else {
                    $dir = 'index';
                }
                /*
                TODO: It is serious, if there are rules in wp-content that can no longer be removed
                We should try to read that file to see if there is a problem.
                */
                $writeToPluginsDirToo = Paths::isPluginDirMovedOutOfAbsPath();
            }
        }
        if ($writeToPluginsDirToo) {
            $pluginDir = Paths::getPluginDirAbs();
            $writtenToPlugins = HTAccess::saveHTAccessRulesToFile($pluginDir . '/.htaccess', $rules, true);
        }
        return [
            'dir' => $dir,
            'pluginToo' => $writeToPluginsDirToo,
            'pluginSuccess' => $writtenToPlugins
        ];
    }

    public static function saveRulesAndMessageUs($config, $context) {

        $rules = HTAccess::generateHTAccessRulesFromConfigObj($config);

        $indexDir = Paths::getIndexDirAbs();
        $homeDir = Paths::getHomeDirAbs();
        $wpContentDir = Paths::getWPContentDirAbs();
        $pluginDir = Paths::getPluginDirAbs();

        $writeToPluginsDirToo = false;
        $showSuccess = true;

        $result = HTAccess::saveHTAccessRulesToFirstWritableHTAccessDir([$wpContentDir, $indexDir, $homeDir], $rules);

        if ($context == 'migrate') {
            $testLink = '';
        } else {
            $testLink = self::testLinks($config);
        }

        if ($result == false) {

            $showSuccess = false;
            if ($context == 'migrate') {
                Messenger::addMessage(
                    'warning',
                    'The <i>.htaccess</i> rules could not be migrated. But configuration was otherwise successfully migrated. Please grant access to either your <i>wp-content</i> dir, ' .
                        'or your main <i>.htaccess</i> file. And then regenerate the <i>.htaccess</i> by going to settings and pressing "save settings". - Or paste the following manually into your <i>.htaccess</i>:' .
                        '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                );
            } else {
                switch ($context) {
                    case 'submit':
                        $beginText = 'Configuration saved, but the ';
                    default:
                        $beginText = 'The ';
                }
                Messenger::addMessage(
                    'warning',
                    $beginText . '<i>.htaccess</i> rules could not be saved. Please grant access to either your <i>wp-content</i> dir, ' .
                        'or your main <i>.htaccess</i> file. ' .
                        '- or, alternatively insert the following rules directly in your Apache configuration:' .
                        '<pre>' . htmlentities(print_r($rules, true)) . '</pre>' .
                        $testLink
                );
            }

        } else {
            if ($result == $wpContentDir) {
                $writeToPluginsDirToo = Paths::isPluginDirMovedOutOfWpContent();
            } else {
                /*
                TODO: It is serious, if there are rules in wp-content that can no longer be removed
                We should try to read that file to see if there is a problem.
                */
                $showSuccess = false;
                if ($context == 'submit') {
                    Messenger::addMessage('success', 'Configuration saved.');
                }

                Messenger::addMessage(
                    'warning',
                    '<i>.htaccess</i> rules were written to your main <i>.htaccess</i>. ' .
                        'However, consider to let us write into you wp-content dir instead.' .
                        $testLink
                );

                $writeToPluginsDirToo = Paths::isPluginDirMovedOutOfAbsPath();
            }
        }
        if ($writeToPluginsDirToo) {
            if (!HTAccess::saveHTAccessRulesToFile($pluginDir . '/.htaccess', $rules, true)) {
                $showSuccess = false;
                if ($context == 'submit') {
                    Messenger::addMessage('success', 'Configuration saved.');
                }
                Messenger::addMessage(
                    'warning',
                    '<i>.htaccess</i> rules could not be written into your plugins folder. ' .
                        'Images stored in your plugins will not be converted to webp (or, if <i>WebP Express</i> has rewrite rules there already, they did not get updated)'
                );
            }
        }
        if ($showSuccess) {
            if ($context = 'migrate') {
                Messenger::addMessage(
                    'success',
                    'WebP Express has successfully migrated its configuration and updated the .htaccess file'
                );
            } else {
                if ($context == 'submit') {
                    $beginText = 'Configuration saved and rewrite ';
                } else {
                    $beginText = 'Rewrite ';
                }

                Messenger::addMessage(
                    'success',
                    $beginText . 'rules were updated (they are placed in your <i>wp-content</i> dir)' .
                        ($writeToPluginsDirToo ? '. Also updated rewrite rules in your plugins dir.' : '.') .
                        $testLink
                );
            }

        }
        if ($context == 'submit') {
            Messenger::addMessage(
                'info',
                'Rules:<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
            );
        }
    }
}
