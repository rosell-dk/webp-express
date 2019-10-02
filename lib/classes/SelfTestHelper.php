<?php

namespace WebPExpress;

use \WebPExpress\Paths;

class SelfTestHelper
{

    public static function deleteFilesInDir($dir, $filePattern = "*")
    {
        foreach (glob($dir . DIRECTORY_SEPARATOR . $filePattern) as $filename) {
            unlink($filename);
        }
    }

    /**
     * Remove files in dir and the dir. Does not remove files recursively.
     */
    public static function deleteDir($dir)
    {
        if (@file_exists($dir)) {
            self::deleteFilesInDir($dir);
            rmdir($dir);
        }
    }


    public static function deleteTestImagesInFolder($rootId)
    {
        $testDir = Paths::getAbsDirById($rootId) . '/webp-express-test-images';
        self::deleteDir($testDir);
    }

    public static function cleanUpTestImages($rootId, $config)
    {
        // Clean up test images in source folder
        self::deleteTestImagesInFolder($rootId);

        // Clean up dummy webp images in cache folder for the root
        $cacheDirForRoot = Paths::getCacheDirForImageRoot(
            $config['destination-folder'],
            $config['destination-structure'],
            $rootId
        );

        $testDir = $cacheDirForRoot . '/webp-express-test-images';
        self::deleteDir($testDir);
    }

    public static function copyFile($source, $destination)
    {
        $log = [];
        if (@copy($source, $destination)) {
            return [true, $log];
        } else {
            $log[] = 'Failed to copy *' . $source . '* to *' . $destination . '*';
            if (!@file_exists($source)) {
                $log[] = 'The source file was not found';
            } else {
                if (!@file_exists(dirname($destination))) {
                    $log[] = 'The destination folder does not exist!';
                } else {
                    $log[] = 'This is probably a permission issue. Check that your webserver has permission to ' .
                        'write files in the directory (*' . dirname($destination) . '*)';
                }
            }
            return [false, $log];
        }
    }

    public static function randomDigitsAndLetters($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function copyTestImageToRoot($rootId, $imageType = 'jpeg')
    {
        // TODO: Copy to a subfolder instead
        // TODO: Use smaller jpeg / pngs please.
        $log = [];
        switch ($imageType) {
            case 'jpeg':
                $fileNameToCopy = 'very-small.jpg';
                break;
            case 'png':
                $fileNameToCopy = 'test.png';
                break;
        }
        $testSource = Paths::getPluginDirAbs() . '/webp-express/test/' . $fileNameToCopy;
        $filenameOfDestination = self::randomDigitsAndLetters(6) . '.' . strtoupper($imageType);
        //$filenameOfDestination = self::randomDigitsAndLetters(6) . '.' . $imageType;
        $log[] = 'Copying ' . strtoupper($imageType) . ' to ' . $rootId . ' folder (*webp-express-test-images/' . $filenameOfDestination . '*)';

        $destDir = Paths::getAbsDirById($rootId) . '/webp-express-test-images';
        $destination = $destDir . '/' . $filenameOfDestination;

        if (!@file_exists($destDir)) {
            if (!@mkdir($destDir)) {
                $log[count($log) - 1] .= '. FAILED';
                $log[] = 'Failed to create folder for test images: ' . $destDir;
                return [$log, false, ''];
            }
        }

        list($success, $errors) = self::copyFile($testSource, $destination);
        if (!$success) {
            $log[count($log) - 1] .= '. FAILED';
            $log = array_merge($log, $errors);
            return [$log, false, ''];
        } else {
            $log[count($log) - 1] .= '. ok!';
            $log[] = 'We now have a ' . $imageType . ' stored here:';
            $log[] = '*' . $destination . '*';
        }
        return [$log, true, $filenameOfDestination];
    }

    public static function copyTestImageToUploadFolder($imageType = 'jpeg')
    {
        return self::copyTestImageToRoot('uploads', $imageType);
    }

    public static function copyDummyWebPToCacheFolder($rootId, $destinationFolder, $destinationExtension, $destinationStructure, $sourceFileName, $imageType = 'jpeg')
    {
        $log = [];
        $dummyWebP = Paths::getPluginDirAbs() . '/webp-express/test/test.jpg.webp';

        $log[] = 'Copying dummy webp to the cache root for ' . $rootId;
        $destDir = Paths::getCacheDirForImageRoot($destinationFolder, $destinationStructure, $rootId);
        if (!file_exists($destDir)) {
            $log[] = 'The folder did not exist. Creating folder at: ' . $destinationFolder;
            if (!mkdir($destDir, 0777, true)) {
                $log[] = 'Failed creating folder!';
                return [$log, false, ''];
            }
        }
        $destDir .= '/webp-express-test-images';
        if (!file_exists($destDir)) {
            if (!mkdir($destDir, 0755, false)) {
                $log[] = 'Failed creating the folder for the test images:';
                $log[] = $destDir;
                $log[] = 'To run this test, you must grant write permissions';
                return [$log, false, ''];
            }
        }

        $filenameOfDestination = ConvertHelperIndependent::appendOrSetExtension(
            $sourceFileName,
            $destinationFolder,
            $destinationExtension,
            ($rootId == 'uploads')
        );

        //$filenameOfDestination = $destinationFileNameNoExt . ($destinationExtension == 'append' ? '.' . $imageType : '') . '.webp';
        $destination = $destDir . '/' . $filenameOfDestination;

        list($success, $errors) = self::copyFile($dummyWebP, $destination);
        if (!$success) {
            $log[count($log) - 1] .= '. FAILED';
            $log = array_merge($log, $errors);
            return [$log, false, ''];
        } else {
            $log[count($log) - 1] .= '. ok!';
            $log[] = 'We now have a webp file stored here:';
            $log[] = '*' . $destination . '*';
            $log[] = '';
        }
        return [$log, true, $destination];
    }

    /**
     *  Perform HTTP request.
     *
     *  @param  string  $requestUrl    URL
     *  @param  array   $args          Args to pass to wp_remote_get. Note however that "redirection" is set to 0
     *  @param  int     $maxRedirects  For internal use
     *  @return array   The result
     *                  $success (boolean):  If we got a 200 response in the end (after max 2 redirects)
     *                  $log (array)      :  Message log
     *                  $results          :  Array of results from wp_remote_get. If no redirection occured, it will only contain one item.
     *
     */
    public static function remoteGet($requestUrl, $args = [], $maxRedirects = 2)
    {
        $log = [];
        $args['redirection'] = 0;

        $log[] = 'Request URL: ' . $requestUrl;

        $results = [];
        $wpResult = wp_remote_get($requestUrl, $args);
        if (!isset($wpResult['headers'])) {
            $wpResult['headers'] = [];
        }
        $results[] = $wpResult;
        if (is_wp_error($wpResult)) {
            $log[] = 'The remote request errored';
            return [false, $log, $results];
        }
        $responseCode = $wpResult['response']['code'];

        $log[] = 'Response: ' . $responseCode . ' ' . $wpResult['response']['message'];
        $log = array_merge($log, SelfTestHelper::printHeaders($wpResult['headers']));

        if (isset($wpResult['headers']['content-type'])) {
            if (strpos($wpResult['headers']['content-type'], 'text/html') !== false) {
                if (isset($wpResult['body']) && (!empty($wpResult['body']))) {
                    $log[] = 'Body:';
                    $log[] = print_r($wpResult['body'], true);
                }
            }
        }

        if (($responseCode == '302') || ($responseCode == '301')) {
            if ($maxRedirects > 0) {
                if (isset($wpResult['headers']['location'])) {
                    $url = $wpResult['headers']['location'];
                    if (strpos($url, 'http') !== 0) {
                        $url = $requestUrl . $url;
                    }
                    $log[] = 'Following that redirect';

                    list($success, $newLog, $newResult) = self::remoteGet($url, $args, $maxRedirects - 1);
                    $log = array_merge($log, $newLog);
                    $results = array_merge($results, $newResult);

                    return [$success, $log, $results];

                }
            } else {
                $log[] = 'Not following the redirect (max redirects exceeded)';
            }
        }

        $success = ($responseCode == '200');
        return [$success, $log, $results];
    }

    public static function hasHeaderContaining($headers, $headerToInspect, $containString)
    {
        if (!isset($headers[$headerToInspect])) {
            return false;
        }

        // If there are multiple headers, check all
        if (gettype($headers[$headerToInspect]) == 'string') {
            $h = [$headers[$headerToInspect]];
        } else {
            $h = $headers[$headerToInspect];
        }
        foreach ($h as $headerValue) {
            if (stripos($headerValue, $containString) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function hasVaryAcceptHeader($headers)
    {
        if (!isset($headers['vary'])) {
            return false;
        }

        // There may be multiple Vary headers. Or they might be combined in one.
        // Both are acceptable, according to https://stackoverflow.com/a/28799169/842756
        if (gettype($headers['vary']) == 'string') {
            $varyHeaders = [$headers['vary']];
        } else {
            $varyHeaders = $headers['vary'];
        }
        foreach ($varyHeaders as $headerValue) {
            $values = explode(',', $headerValue);
            foreach ($values as $value) {
                if (strtolower($value) == 'accept') {
                    return true;
                }
            }
        }
        return false;
    }

    public static function hasCacheControlOrExpiresHeader($headers)
    {
        if (isset($headers['cache-control'])) {
            return true;
        }
        if (isset($headers['expires'])) {
            return true;
        }
        return false;
    }


    public static function flattenHeaders($headers)
    {
        $log = [];
        foreach ($headers as $headerName => $headerValue) {
            if (gettype($headerValue) == 'array') {
                foreach ($headerValue as $i => $value) {
                    $log[] = [$headerName, $value];
                }
            } else {
                $log[] = [$headerName, $headerValue];
            }
        }
        return $log;
    }

    public static function printHeaders($headers)
    {
        $log = [];
        $log[] = '#### Response headers:';

        $headersFlat = self::flattenHeaders($headers);
        //
        foreach ($headersFlat as $i => list($headerName, $headerValue)) {
            if ($headerName == 'x-webp-express-error') {
                $headerValue = '**' . $headerValue . '**{: .error}';
            }
            $log[] = '- ' . $headerName . ': ' . $headerValue;
        }
        $log[] = '';
        return $log;
    }

    private static function trueFalseNullString($var)
    {
        if ($var === true) {
            return 'yes';
        }
        if ($var === false) {
            return 'no';
        }
        return 'could not be determined';
    }

    public static function systemInfo()
    {
        $log = [];
        $log[] = '#### System info:';
        $log[] = '- PHP version: ' . phpversion();
        $log[] = '- OS: ' . PHP_OS;
        $log[] = '- Server software: ' . $_SERVER["SERVER_SOFTWARE"];
        $log[] = '- Document Root status: ' . Paths::docRootStatusText();
        if (PathHelper::isDocRootAvailable()) {
            $log[] = '- Document Root: ' . $_SERVER['DOCUMENT_ROOT'];
        }
        if (PathHelper::isDocRootAvailableAndResolvable()) {
            if ($_SERVER['DOCUMENT_ROOT'] != realpath($_SERVER['DOCUMENT_ROOT'])) {
                $log[] = '- Document Root (symlinked resolved): ' . realpath($_SERVER['DOCUMENT_ROOT']);
            }
        }

        $log[] = '- Document Root: ' . Paths::docRootStatusText();
        $log[] = '- Apache module "mod_rewrite" enabled?: ' . self::trueFalseNullString(PlatformInfo::gotApacheModule('mod_rewrite'));
        $log[] = '- Apache module "mod_headers" enabled?: ' . self::trueFalseNullString(PlatformInfo::gotApacheModule('mod_headers'));
        return $log;
    }

    public static function wordpressInfo()
    {
        $log = [];
        $log[] = '#### Wordpress info:';
        $log[] = '- Version: ' . get_bloginfo('version');
        $log[] = '- Multisite?: ' . self::trueFalseNullString(is_multisite());
        $log[] = '- Is wp-content moved?: ' . self::trueFalseNullString(Paths::isWPContentDirMoved());
        $log[] = '- Is uploads moved out of wp-content?: ' . self::trueFalseNullString(Paths::isUploadDirMovedOutOfWPContentDir());
        $log[] = '- Is plugins moved out of wp-content?: ' . self::trueFalseNullString(Paths::isPluginDirMovedOutOfWpContent());

        $log[] = '';

        $log[] = '#### Image roots (absolute paths)';
        foreach (Paths::getImageRootIds() as $rootId) {
            $absDir = Paths::getAbsDirById($rootId);

            if (PathHelper::pathExistsAndIsResolvable($absDir) && ($absDir != realpath($absDir))) {
                $log[] = '*' . $rootId . '*: ' . $absDir . ' (resolved for symlinks: ' .  realpath($absDir) . ')';
            } else {
                $log[] = '*' . $rootId . '*: ' . $absDir;

            }
        }

        $log[] = '#### Image roots (relative to document root)';
        foreach (Paths::getImageRootIds() as $rootId) {
            $absPath = Paths::getAbsDirById($rootId);
            if (PathHelper::canCalculateRelPathFromDocRootToDir($absPath)) {
                $log[] = '*' . $rootId . '*: ' . PathHelper::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed($absPath);
            } else {
                $log[] = '*' . $rootId . '*: ' . 'n/a (not within document root)';
            }
        }

        $log[] = '#### Image roots (URLs)';
        foreach (Paths::getImageRootIds() as $rootId) {
            $url = Paths::getUrlById($rootId);
            $log[] = '*' . $rootId . '*: ' . $url;
        }


        return $log;
    }

    public static function configInfo($config)
    {
        $log = [];
        $log[] = '#### WebP Express configuration info:';
        $log[] = '- Destination folder: ' . $config['destination-folder'];
        $log[] = '- Destination extension: ' . $config['destination-extension'];
        $log[] = '- Destination structure: ' . $config['destination-structure'];
        //$log[] = 'Image types: ' . ;
        //$log[] = '';
        $log[] = '(To view all configuration, take a look at the config file, which is stored in *' . Paths::getConfigFileName() . '*)';
        return $log;
    }

    public static function htaccessInfo($config, $printRules = true)
    {
        $log = [];
        //$log[] = '*.htaccess info:*';
        //$log[] = '- Image roots with WebP Express rules: ' . implode(', ', HTAccess::getRootsWithWebPExpressRulesIn());
        $log[] = '#### .htaccess files that WebP Express have placed rules in the following files:';
        $rootIds = HTAccess::getRootsWithWebPExpressRulesIn();
        foreach ($rootIds as $imageRootId) {
            $log[] = '- ' . Paths::getAbsDirById($imageRootId) . '/.htaccess';
        }

        foreach ($rootIds as $imageRootId) {
            $log = array_merge($log, self::rulesInImageRoot($config, $imageRootId));
        }

        return $log;
    }

    public static function rulesInImageRoot($config, $imageRootId)
    {
        $log = [];
        $log[] = '#### WebP rules in *' . $imageRootId . '*:';
        $file = Paths::getAbsDirById($imageRootId) . '/.htaccess';
        if (!HTAccess::haveWeRulesInThisHTAccess($file)) {
            $log[] = '**NONE!**{: .warn}';
        } else {
            $weRules = HTAccess::extractWebPExpressRulesFromHTAccess($file);
            // remove unindented comments
            //$weRules = preg_replace('/^\#\s[^\n\r]*[\n\r]+/ms', '', $weRules);

            // remove comments in the beginning
            $weRulesArr = preg_split("/\r\n|\n|\r/", $weRules);  // https://stackoverflow.com/a/11165332/842756
            while ((strlen($weRulesArr[0]) > 0) && ($weRulesArr[0][0] == '#')) {
                array_shift($weRulesArr);
            }
            $weRules = implode("\n", $weRulesArr);

            $log[] = '```' . $weRules . '```';
        }
        return $log;
    }

    public static function rulesInUpload($config)
    {
        return self::rulesInImageRoot($config, 'uploads');
    }

    public static function allInfo($config)
    {
        $log = [];
        $log = array_merge($log, self::systemInfo());
        $log = array_merge($log, self::wordpressInfo());
        $log = array_merge($log, self::configInfo($config));
        $log = array_merge($log, self::capabilityTests($config));
        $log = array_merge($log, self::htaccessInfo($config, true));
        //$log = array_merge($log, self::rulesInImageRoot($config, 'upload'));
        //$log = array_merge($log, self::rulesInImageRoot($config, 'wp-content'));
        return $log;
    }

    public static function capabilityTests($config)
    {
        $capTests = $config['base-htaccess-on-these-capability-tests'];
        $log = [];
        $log[] = '#### Live tests of .htaccess capabilities:';
        /*$log[] = 'Exactly what you can do in a *.htaccess* depends on the server setup. WebP Express ' .
            'makes some live tests to verify if a certain feature in fact works. This is done by creating ' .
            'test files (*.htaccess* files and php files) in a dir inside the content dir and running these. ' .
            'These test results are used when creating the rewrite rules. Here are the results:';*/

//        $log[] = '';
        $log[] = '- mod_rewrite working?: ' . self::trueFalseNullString(CapabilityTest::modRewriteWorking());
        $log[] = '- mod_header working?: ' . self::trueFalseNullString($capTests['modHeaderWorking']);
        /*$log[] = '- pass variable from *.htaccess* to script through header working?: ' .
            self::trueFalseNullString($capTests['passThroughHeaderWorking']);*/
        $log[] = '- passing variables from *.htaccess* to PHP script through environment variable working?: ' . self::trueFalseNullString($capTests['passThroughEnvWorking']);
        return $log;
    }

    public static function diagnoseFailedRewrite($config)
    {
        if (($config['destination-structure'] == 'image-roots') && (!PathHelper::isDocRootAvailableAndResolvable())) {
            $log[] = 'The problem is probably this combination:';
            if (!PathHelper::isDocRootAvailable()) {
                $log[] = '1. Your document root isn`t available';
            } else {
                $log[] = '1. Your document root isn`t resolvable for symlinks (it is probably subject to open_basedir restriction)';
            }
            $log[] = '2. Your document root is symlinked';
            $log[] = '3. The wordpress function that tells the path of the uploads folder returns the symlink resolved path';

            $log[] = 'I cannot check if your document root is in fact symlinked (as document root isnt resolvable). ' .
                'But if it is, there you have it. The line beginning with "RewriteCond %{REQUEST_FILENAME}"" points to your resolved root, ' .
                'but it should point to your symlinked root. WebP Express cannot do that for you because it cannot discover what the symlink is. ' .
                'Try changing the line manually. When it works, you can move the rules outside the WebP Express block so they dont get ' .
                'overwritten. OR you can change your server configuration (document root / open_basedir restrictions)';
        }

        //$log[] = '## Diagnosing';
        if (PlatformInfo::isNginx()) {
            // Nginx
            $log[] = 'Notice that you are on Nginx and the rules that WebP Express stores in the *.htaccess* files probably does not ' .
                'have any effect. ';
            $log[] = 'Please read the "I am on Nginx" section in the FAQ (https://wordpress.org/plugins/webp-express/)';
            $log[] = 'And did you remember to restart the nginx service after updating the configuration?';

            $log[] = 'PS: If you cannot get the redirect to work, you can simply rely on Alter HTML as described in the FAQ.';
            return $log;
        }

        $modRewriteWorking = CapabilityTest::modRewriteWorking();
        if ($modRewriteWorking !== null) {
            $log[] = 'Running a special designed capability test to test if rewriting works with *.htaccess* files';
        }
        if ($modRewriteWorking === true) {
            $log[] = 'Result: Yes, rewriting works.';
            $log[] = 'It seems something is wrong with the *.htaccess* rules then. You could try ' .
                'to change "Destination structure" - the rules there are quite different.';
            $log[] = 'It could also be that the server has cached the configuration a while. Some servers ' .
                'does that. In that case, simply give it a few minutes and try again.';
        } elseif ($modRewriteWorking === false) {
            $log[] = 'Result: No, rewriting does not seem to work within *.htaccess* rules.';
            if (PlatformInfo::definitelyNotGotModRewrite()) {
                $log[] = 'It actually seems "mod_write" is disabled on your server. ' .
                    '**You must enable mod_rewrite on the server**';
            } elseif (PlatformInfo::definitelyGotApacheModule('mod_rewrite')) {
                $log[] = 'However, "mod_write" *is* enabled on your server. This seems to indicate that ' .
                    '*.htaccess* files has been disabled for configuration on your server. ' .
                    'In that case, you need to copy the WebP Express rules from the *.htaccess* files into your virtual host configuration files. ' .
                    '(WebP Express generates multiple *.htaccess* files. Look in the upload folder, the wp-content folder, etc).';
                $log[] = 'It could however alse simply be that your server simply needs some time. ' .
                    'Some servers caches the *.htaccess* rules for a bit. In that case, simply give it a few minutes and try again.';
            } else {
                $log[] = 'However, this could be due to your server being a bit slow on picking up changes in *.htaccess*.' .
                    'Give it a few minutes and try again.';
            }
        } else {
            // The mod_rewrite test could not conclude anything.
            if (PlatformInfo::definitelyNotGotApacheModule('mod_rewrite')) {
                $log[] = 'It actually seems "mod_write" is disabled on your server. ' .
                    '**You must enable mod_rewrite on the server**';
            } elseif (PlatformInfo::definitelyGotApacheModule('mod_rewrite')) {
                $log[] = '"mod_write" is enabled on your server, so rewriting ought to work. ' .
                    'However, it could be that your server setup has disabled *.htaccess* files for configuration. ' .
                    'In that case, you need to copy the WebP Express rules from the *.htaccess* files into your virtual host configuration files. ' .
                    '(WebP Express generates multiple *.htaccess* files. Look in the upload folder, the wp-content folder, etc). ';
            } else {
                $log[] = 'It seems something is wrong with the *.htaccess* rules. ';
                $log[] = 'Or perhaps the server has cached the configuration a while. Some servers ' .
                    'does that. In that case, simply give it a few minutes and try again.';
            }
        }
        $log[] = 'Note that if you cannot get redirection to work, you can switch to "CDN friendly" mode and ' .
            'rely on the "Alter HTML" functionality to point to the webp images. If you do a bulk conversion ' .
            'and make sure that "Convert upon upload" is activated, you should be all set. Alter HTML even handles ' .
            'inline css (unless you select "picture tag" syntax). It does however not handle images in external css or ' .
            'which is added dynamically with javascript.';

        $log[] = '## Info for manually diagnosing';
        $log = array_merge($log, self::allInfo($config));
        return $log;
    }
}
