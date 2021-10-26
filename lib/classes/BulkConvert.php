<?php

namespace WebPExpress;

//use \Onnov\DetectEncoding\EncodingDetector;

class BulkConvert
{

    public static function defaultListOptions($config)
    {
        return [
            //'root' => Paths::getUploadDirAbs(),
            'ext' => $config['destination-extension'],
            'destination-folder' => $config['destination-folder'],  /* hm, "destination-folder" is a bad name... */
            'webExpressContentDirAbs' => Paths::getWebPExpressContentDirAbs(),
            'uploadDirAbs' => Paths::getUploadDirAbs(),
            'useDocRootForStructuringCacheDir' => (($config['destination-structure'] == 'doc-root') && (Paths::canUseDocRootForStructuringCacheDir())),
            'imageRoots' => new ImageRoots(Paths::getImageRootsDefForSelectedIds($config['scope'])),   // (Paths::getImageRootsDef()
            'filter' => [
                'only-converted' => false,
                'only-unconverted' => true,
                'image-types' => $config['image-types'],
                'max-depth' => 100,
            ],
            'flattenList' => true,
        ];
    }

    /**
     *  Get grouped list of files. They are grouped by image roots.
     *
     */
    public static function getList($config, $listOptions = null)
    {

        /*
        isUploadDirMovedOutOfWPContentDir
        isUploadDirMovedOutOfAbsPath
        isPluginDirMovedOutOfAbsPath
        isPluginDirMovedOutOfWpContent
        isWPContentDirMovedOutOfAbsPath */

        if (is_null($listOptions)) {
            $listOptions = self::defaultListOptions($config);
        }

        $rootIds = Paths::filterOutSubRoots($config['scope']);

        $groups = [];
        foreach ($rootIds as $rootId) {
            $groups[] = [
                'groupName' => $rootId,
                'root' => Paths::getAbsDirById($rootId)
            ];
        }

        foreach ($groups as $i => &$group) {
            $listOptions['root'] = $group['root'];
            /*
            No use, because if uploads is in wp-content, the cache root will be different for the files in uploads (if mingled)
            $group['image-root'] = ConvertHelperIndependent::getDestinationFolder(
                $group['root'],
                $listOptions['destination-folder'],
                $listOptions['ext'],
                $listOptions['webExpressContentDirAbs'],
                $listOptions['uploadDirAbs']
            );*/
            $group['files'] = self::getListRecursively('.', $listOptions);
            //'image-root' => ConvertHelperIndependent::getDestinationFolder()
        }

        return $groups;
        //self::moveRecursively($toDir, $fromDir, $srcDir, $fromExt, $toExt);
    }

    /**
     * $filter: all | converted | not-converted. "not-converted" for example returns paths to images that has not been converted
     */
    public static function getListRecursively($relDir, &$listOptions, $depth = 0)
    {
        $dir = $listOptions['root'] . '/' . $relDir;

        // Canonicalize because dir might contain "/./", which causes file_exists to fail (#222)
        $dir = PathHelper::canonicalize($dir);

        if (!@file_exists($dir) || !@is_dir($dir)) {
            return [];
        }

        $fileIterator = new \FilesystemIterator($dir);

        $results = [];
        $filter = &$listOptions['filter'];

        while ($fileIterator->valid()) {
            $filename = $fileIterator->getFilename();

            if (($filename != ".") && ($filename != "..")) {

                if (@is_dir($dir . "/" . $filename)) {
                    if ($listOptions['flattenList']) {
                        $results = array_merge($results, self::getListRecursively($relDir . "/" . $filename, $listOptions, $depth+1));
                    } else {
                        $r = [
                            'name' => $filename,
                            'isDir' => true,
                        ];
                        if ($depth > $listOptions['max-depth']) {
                            return $r;  // one item is enough to determine that it is not empty
                        }
                        if ($depth < $listOptions['max-depth']) {
                            $r['children'] = self::getListRecursively($relDir . "/" . $filename, $listOptions, $depth+1);
                            $r['isEmpty'] = (count($r['children']) == 0);
                        } else if ($depth == $listOptions['max-depth']) {
                            $c = self::getListRecursively($relDir . "/" . $filename, $listOptions, $depth+1);
                            $r['isEmpty'] = (count($c) == 0);
                            //$r['isEmpty'] = !(new \FilesystemIterator($dir))->valid();
                        }
                        $results[] = $r;
                    }
                } else {
                    // its a file - check if its a jpeg or png

                    if (!isset($filter['_regexPattern'])) {
                        $imageTypes = $filter['image-types'];
                        $fileExtensions = [];
                        if ($imageTypes & 1) {
                          $fileExtensions[] = 'jpe?g';
                        }
                        if ($imageTypes & 2) {
                          $fileExtensions[] = 'png';
                        }
                        $filter['_regexPattern'] = '#\.(' . implode('|', $fileExtensions) . ')$#';
                    }

                    if (preg_match($filter['_regexPattern'], $filename)) {
                        $addThis = true;

                        $destination = ConvertHelperIndependent::getDestination(
                            $dir . "/" . $filename,
                            $listOptions['destination-folder'],
                            $listOptions['ext'],
                            $listOptions['webExpressContentDirAbs'],
                            $listOptions['uploadDirAbs'],
                            $listOptions['useDocRootForStructuringCacheDir'],
                            $listOptions['imageRoots']
                        );
                        $webpExists = @file_exists($destination);

                        if (($filter['only-converted']) || ($filter['only-unconverted'])) {
                            //$cacheDir = $listOptions['image-root'] . '/' . $relDir;

                            // Check if corresponding webp exists
                            /*
                            if ($listOptions['ext'] == 'append') {
                                $webpExists = @file_exists($cacheDir . "/" . $filename . '.webp');
                            } else {
                                $webpExists = @file_exists(preg_replace("/\.(jpe?g|png)\.webp$/", '.webp', $filename));
                            }*/

                            if (!$webpExists && ($filter['only-converted'])) {
                                $addThis = false;
                            }
                            if ($webpExists && ($filter['only-unconverted'])) {
                                $addThis = false;
                            }
                        } else {
                            $addThis = true;
                        }

                        if ($addThis) {

                            $path = substr($relDir . "/", 2) . $filename;   // (we cut the leading "./" off with substr)

                            // Check if the string can be encoded to json (if not: change it to a string that can)
                            if (json_encode($path, JSON_UNESCAPED_UNICODE) === false) {
                                /*
                                json_encode failed. This means that the string was not UTF-8.
                                Lets see if we can convert it to UTF-8.
                                This is however tricky business (see #471)
                                */

                                $encodedToUTF8 = false;

                                // First try library that claims to do better than mb_detect_encoding
                                /*
                                DISABLED, because Onnov EncodingDetector requires PHP 7.2
                                https://wordpress.org/support/topic/get-http-error-500-after-new-update-2/

                                if (!$encodedToUTF8) {
                                    $detector = new EncodingDetector();

                                    $dectedEncoding = $detector->getEncoding($path);

                                    if ($dectedEncoding !== 'utf-8') {
                                        if (function_exists('iconv')) {
                                            $res = iconv($dectedEncoding, 'utf-8//TRANSLIT', $path);
                                            if ($res !== false) {
                                                $path = $res;
                                                $encodedToUTF8 = true;
                                            }
                                        }
                                    }


                                    try {
                                        // iconvXtoEncoding should work now hm, issue #5 has been fixed
                                        $path = $detector->iconvXtoEncoding($path);
                                        $encodedToUTF8 = true;
                                    } catch (\Exception $e) {

                                    }
                                }*/

                                // Try mb_detect_encoding
                                if (!$encodedToUTF8) {
                                    if (function_exists('mb_convert_encoding')) {
                                        $encoding = mb_detect_encoding($path, mb_detect_order(), true);
                                    		if ($encoding) {
                                    			$path = mb_convert_encoding($path, 'UTF-8', $encoding);
                                          $encodedToUTF8 = true;
                                    		}
                                    }
                                }

                                if (!$encodedToUTF8) {
                                    /*
                                    We haven't yet succeeded in encoding to UTF-8.
                                    What should we do?
                                    1. Skip the file? (no, the user will not know about the problem then)
                                    2. Add it anyway? (no, if this string causes problems to json_encode, then we will have
                                          the same problem when encoding the entire list - result: an empty list)
                                    3. Try wp_json_encode? (no, it will fall back on "wp_check_invalid_utf8", which has a number of
                                          things we do not want)
                                    4. Encode it to UTF-8 assuming that the string is encoded in the most common encoding (Windows-1252) ?
                                          (yes, if we are lucky with the guess, it will work. If it is in another encoding, the conversion
                                          will not be correct, and the user will then know about the problem. And either way, we will
                                          have UTF-8 string, which will not break encoding of the list)
                                    */

                                    // https://stackoverflow.com/questions/6606713/json-encode-non-utf-8-strings
                                    if (function_exists('mb_convert_encoding')) {
                                        $path = mb_convert_encoding($path, "UTF-8", "Windows-1252");
                                    } elseif (function_exists('iconv')) {
                                        $path = iconv("CP1252", "UTF-8", $path);
                                    } elseif (function_exists('utf8_encode')) {
                                        // utf8_encode converts from ISO-8859-1 to UTF-8
                                        $path = utf8_encode($path);
                                    } else {
                                        $path = '[cannot encode this filename to UTF-8]';
                                    }

                                }

                            }
                            if ($listOptions['flattenList']) {
                              $results[] = $path;
                            } else {
                              $results[] = [
                                'name' => basename($path),
                                'isConverted' => $webpExists
                              ];
                              if ($depth > $listOptions['max-depth']) {
                                  return $results;  // one item is enough to determine that it is not empty
                              }

                            }
                        }
                    }
                }
            }
            $fileIterator->next();
        }
        return $results;
    }

/*
    public static function convertFile($source)
    {
        $config = Config::loadConfigAndFix();
        $options = Config::generateWodOptionsFromConfigObj($config);

        $destination = ConvertHelperIndependent::getDestination(
            $source,
            $options['destination-folder'],
            $options['destination-extension'],
            Paths::getWebPExpressContentDirAbs(),
            Paths::getUploadDirAbs()
        );
        $result = ConvertHelperIndependent::convert($source, $destination, $options);

        //$result['destination'] = $destination;
        if ($result['success']) {
            $result['filesize-original'] = @filesize($source);
            $result['filesize-webp'] = @filesize($destination);
        }
        return $result;
    }
*/

    public static function processAjaxListUnconvertedFiles()
    {
        if (!check_ajax_referer('webpexpress-ajax-list-unconverted-files-nonce', 'nonce', false)) {
            wp_send_json_error('The security nonce has expired. You need to reload the settings page (press F5) and try again)');
            wp_die();
        }

        $config = Config::loadConfigAndFix();
        $arr = self::getList($config);

        // We use "wp_json_encode" rather than "json_encode" because it handles problems if there is non UTF-8 characters
        // There should be none, as we have taken our measures, but no harm in taking extra precautions
        $json = wp_json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            // TODO: We can do better error handling than this!
            echo '';
        } else {
            echo $json;
        }

        wp_die();
    }

}
