<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\FileHelper;
use \WebPExpress\HTAccessRules;
use \WebPExpress\Paths;
use \WebPExpress\State;

class HTAccess
{

    public static function inlineInstructions($instructions, $marker)
    {
        if ($marker == 'WebP Express') {
            return [];
        } else {
           return $instructions;
        }
    }

    /**
     *  Must be parsed ie "wp-content", "index", etc. Not real dirs
     */
    public static function addToActiveHTAccessDirsArray($dirId)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        if (!in_array($dirId, $activeHtaccessDirs)) {
            $activeHtaccessDirs[] = $dirId;
            State::setState('active-htaccess-dirs', array_values($activeHtaccessDirs));
        }
    }

    public static function removeFromActiveHTAccessDirsArray($dirId)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        if (in_array($dirId, $activeHtaccessDirs)) {
            $activeHtaccessDirs = array_diff($activeHtaccessDirs, [$dirId]);
            State::setState('active-htaccess-dirs', array_values($activeHtaccessDirs));
        }
    }

    public static function isInActiveHTAccessDirsArray($dirId)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        return (in_array($dirId, $activeHtaccessDirs));
    }

    public static function hasRecordOfSavingHTAccessToDir($dir) {
        $dirId = Paths::getAbsDirId($dir);
        if ($dirId !== false) {
            return self::isInActiveHTAccessDirsArray($dirId);
        }
        return false;
    }


    /**
     * @return  string|false  Rules, or false if no rules found or file does not exist.
     */
    public static function extractWebPExpressRulesFromHTAccess($filename) {
        if (FileHelper::fileExists($filename)) {
            $content = FileHelper::loadFile($filename);
            if ($content === false) {
                return false;
            }

            $pos1 = strpos($content, '# BEGIN WebP Express');
            if ($pos1 === false) {
                return false;
            }
            $pos2 = strrpos($content, '# END WebP Express');
            if ($pos2 === false) {
                return false;
            }
            return substr($content, $pos1, $pos2 - $pos1);
        } else {
            // the .htaccess isn't even there. So there are no rules.
            return false;
        }
    }

    /**
     *  Sneak peak into .htaccess to see if we have rules in it
     *  This may not be possible (it requires read permission)
     *  Return true, false, or null if we just can't tell
     */
    public static function haveWeRulesInThisHTAccess($filename) {
        if (FileHelper::fileExists($filename)) {
            $content = FileHelper::loadFile($filename);
            if ($content === false) {
                return null;
            }
            $weRules = (self::extractWebPExpressRulesFromHTAccess($filename));
            if ($weRules === false) {
                return false;
            }

            return (strpos($weRules, '<IfModule ') !== false);
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
            // If we did not, we assume there are no rules there
            $dir = FileHelper::dirName($filename);
            return self::hasRecordOfSavingHTAccessToDir($dir);
        }
    }

    public static function getRootsWithWebPExpressRulesIn()
    {
        $allIds = Paths::getImageRootIds();
        $allIds[] = 'cache';
        $result = [];
        foreach ($allIds as $imageRootId) {
            $filename = Paths::getAbsDirById($imageRootId) . '/.htaccess';
            if (self::haveWeRulesInThisHTAccessBestGuess($filename)) {
                $result[] = $imageRootId;
            }

        }
        return $result;
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

        add_filter('insert_with_markers_inline_instructions', array('\WebPExpress\HTAccess', 'inlineInstructions'), 10, 2);

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

            //$containsRules = (strpos(implode('',$rules), '# Redirect images to webp-on-demand.php') != false);
            $containsRules = (strpos(implode('',$rules), '<IfModule mod_rewrite.c>') !== false);

            $dir = FileHelper::dirName($filename);
            $dirId = Paths::getAbsDirId($dir);
            if ($dirId !== false) {
                if ($containsRules) {
                    self::addToActiveHTAccessDirsArray($dirId);
                } else {
                    self::removeFromActiveHTAccessDirsArray($dirId);
                }
            }
        }

        return $success;
    }

    public static function saveHTAccessRules($rootId, $rules, $createIfMissing = true) {
        $filename = Paths::getAbsDirById($rootId) . '/.htaccess';
        return self::saveHTAccessRulesToFile($filename, $rules, $createIfMissing);
    }

    /* only called in this file */
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
     *  @return  true|array
     */
    public static function deactivateHTAccessRules($comment = '# Plugin is deactivated') {

        $rootsToClean = Paths::getImageRootIds();
        $rootsToClean[] = 'home';
        $rootsToClean[] = 'cache';
        $failures = [];
        $successes = [];

        foreach ($rootsToClean as $imageRootId) {
            $dir = Paths::getAbsDirById($imageRootId);
            $filename = $dir . '/.htaccess';
            if (!FileHelper::fileExists($filename)) {
                //error_log('exists not:' . $filename);
                continue;
            } else {
                if (self::haveWeRulesInThisHTAccessBestGuess($filename)) {
                    if (self::saveHTAccessRulesToFile($filename, $comment, false)) {
                        $successes[] = $imageRootId;
                    } else {
                        $failures[] = $imageRootId;
                    }
                } else {
                    //error_log('no rules:' . $filename);
                }
            }
        }
        $success =  (count($failures) == 0);
        return [$success, $failures, $successes];
    }

    public static function testLinks($config) {
        /*
        if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
            if ($config['operation-mode'] != 'no-conversion') {
                if ($config['image-types'] != 0) {
                    $webpExpressRoot = Paths::getWebPExpressPluginUrlPath();
                    $links = '';
                    if ($config['enable-redirection-to-converter']) {
                        $links = '<br>';
                        $links .= '<a href="/' . $webpExpressRoot . '/test/test.jpg?debug&time=' . time() . '" target="_blank">Convert test image (show debug)</a><br>';
                        $links .= '<a href="/' . $webpExpressRoot . '/test/test.jpg?' . time() . '" target="_blank">Convert test image</a><br>';
                    }
                    // TODO: webp-realizer test links (to missing webp)
                    if ($config['enable-redirection-to-webp-realizer']) {
                    }

                    // TODO: test link for testing redirection to existing
                    if ($config['redirect-to-existing-in-htaccess']) {

                    }

                    return $links;
                }
            }
        }*/
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

        // We need upload too for rewrite rules when destination structure is image-roots.
        // but it is also good otherwise. So lets always do it.

        $uploadToo = 'yes';

        return [
            $minRequired,
            $pluginToo,      // 'yes', 'no' or 'depends'
            $uploadToo
        ];
    }

    public static function saveRules($config, $showMessage = true) {
        list($success, $failedDeactivations, $successfulDeactivations) = self::deactivateHTAccessRules('# The rules have left the building');

        $rootsToPutRewritesIn = $config['scope'];
        if ($config['destination-structure'] == 'doc-root') {
            // Commented out to quickfix #338
            // $rootsToPutRewritesIn = Paths::filterOutSubRoots($rootsToPutRewritesIn);
        }

        $dirsContainingWebps = [];

        $mingled = ($config['destination-folder'] == 'mingled');
        if ($mingled) {
            $dirsContainingWebps[] = 'uploads';
        }
        $scopeOtherThanUpload = (str_replace('uploads', '', implode(',', $config['scope'])) != '');

        if ($scopeOtherThanUpload || (!$mingled)) {
            $dirsContainingWebps[] = 'cache';
        }

        $dirsToPutRewritesIn = array_unique(array_merge($rootsToPutRewritesIn, $dirsContainingWebps));

        $failedWrites = [];
        $successfullWrites = [];
        foreach ($dirsToPutRewritesIn as $rootId) {
            $dirContainsSourceImages = in_array($rootId, $rootsToPutRewritesIn);
            $dirContainsWebPImages = in_array($rootId, $dirsContainingWebps);

            $rules = HTAccessRules::generateHTAccessRulesFromConfigObj(
                $config,
                $rootId,
                $dirContainsSourceImages,
                $dirContainsWebPImages
            );
            $success = self::saveHTAccessRules(
                $rootId,
                $rules,
                true
            );
            if ($success) {
                $successfullWrites[] = $rootId;

                // Remove it from $successfulDeactivations (if it is there)
                if (($key = array_search($rootId, $successfulDeactivations)) !== false) {
                    unset($successfulDeactivations[$key]);
                }
            } else {
                $failedWrites[] = $rootId;

                // Remove it from $failedDeactivations (if it is there)
                if (($key = array_search($rootId, $failedDeactivations)) !== false) {
                    unset($failedDeactivations[$key]);
                }
            }
        }

        $success = ((count($failedDeactivations) == 0) && (count($failedWrites) == 0));

        $return = [$success, $successfullWrites, $successfulDeactivations, $failedWrites, $failedDeactivations];
        if ($showMessage) {
            self::showSaveRulesMessages($return);
        }
        return $return;
    }

    public static function showSaveRulesMessages($saveRulesResult)
    {
        list($success, $successfullWrites, $successfulDeactivations, $failedWrites, $failedDeactivations) = $saveRulesResult;

        $msg = '';
        if (count($successfullWrites) > 0) {
            $msg .= '<p>Rewrite rules were saved to the following files:</p>';
            foreach ($successfullWrites as $rootId) {
                $rootIdName = $rootId;
                if ($rootIdName == 'cache') {
                    $rootIdName = 'webp folder';
                }
                $msg .= '<i>' . Paths::getAbsDirById($rootId) . '/.htaccess</i> (' . $rootIdName . ')<br>';
            }
        }

        if (count($successfulDeactivations) > 0) {
            $msg .= '<p>Rewrite rules were removed from the following files:</p>';
            foreach ($successfulDeactivations as $rootId) {
                $rootIdName = $rootId;
                if ($rootIdName == 'cache') {
                    $rootIdName = 'webp folder';
                }
                $msg .= '<i>' . Paths::getAbsDirById($rootId) . '/.htaccess</i> (' . $rootIdName . ')<br>';
            }
        }

        if ($msg != '') {
            Messenger::addMessage(
                ($success ? 'success' : 'info'),
                $msg
            );
        }

        if (count($failedWrites) > 0) {
            $msg = '<p>Failed writing rewrite rules to the following files:</p>';
            foreach ($failedWrites as $rootId) {
                $msg .= '<i>' . Paths::getAbsDirById($rootId) . '/.htaccess</i> (' . $rootId . ')<br>';
            }
            $msg .= 'You need to change the file permissions to allow WebP Express to save the rules.';
            Messenger::addMessage('error', $msg);
        } else {
            if (count($failedDeactivations) > 0) {
                $msg = '<p>Failed deleting unused rewrite rules in the following files:</p>';
                foreach ($failedDeactivations as $rootId) {
                    $msg .= '<i>' . Paths::getAbsDirById($rootId) . '/.htaccess</i> (' . $rootId . ')<br>';
                }
                $msg .= 'You need to change the file permissions to allow WebP Express to remove the rules or ' .
                    'remove them manually';
                Messenger::addMessage('error', $msg);
            }
        }
    }

}
