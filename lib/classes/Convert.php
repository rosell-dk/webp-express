<?php

namespace WebPExpress;

use \WebPConvert\Convert\Converters\Ewww;

use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Config;
use \WebPExpress\ConvertersHelper;
use \WebPExpress\ImageRoots;
use \WebPExpress\SanityCheck;
use \WebPExpress\SanityException;
use \WebPExpress\Validate;
use \WebPExpress\ValidateException;

class Convert
{

    public static function getDestination($source, &$config = null)
    {
        if (is_null($config)) {
            $config = Config::loadConfigAndFix();
        }
        return ConvertHelperIndependent::getDestination(
            $source,
            $config['destination-folder'],
            $config['destination-extension'],
            Paths::getWebPExpressContentDirAbs(),
            Paths::getUploadDirAbs(),
            (($config['destination-structure'] == 'doc-root') && (Paths::canUseDocRootForStructuringCacheDir())),
            new ImageRoots(Paths::getImageRootsDef())
        );
    }

    public static function updateBiggerThanOriginalMark($source, $destination = null, &$config = null)
    {
        if (is_null($config)) {
            $config = Config::loadConfigAndFix();
        }
        if (is_null($destination)) {
            $destination = self::getDestination($config);
        }
        BiggerThanSourceDummyFiles::updateStatus(
            $source,
            $destination,
            Paths::getWebPExpressContentDirAbs(),
            new ImageRoots(Paths::getImageRootsDef()),
            $config['destination-folder'],
            $config['destination-extension']
        );
    }

    public static function convertFile($source, $config = null, $convertOptions = null, $converter = null)
    {
        try {
            // Check source
            // ---------------
            $checking = 'source path';
            $source = SanityCheck::absPathExistsAndIsFile($source);
            //$filename = SanityCheck::absPathExistsAndIsFileInDocRoot($source);
            // PS: No need to check mime type as the WebPConvert library does that (it only accepts image/jpeg and image/png)

            // Check that source is within a valid image root
            $activeRootIds = Paths::getImageRootIds();  // Currently, root ids cannot be selected, so all root ids are active.
            $rootId = Paths::findImageRootOfPath($source, $activeRootIds);
            if ($rootId === false) {
                throw new \Exception('Path of source is not within a valid image root');
            }

            // Check config
            // --------------
            $checking = 'configuration file';
            if (is_null($config)) {
                $config = Config::loadConfigAndFix();  // ps: if this fails to load, default config is returned.
            }
            if (!is_array($config)) {
                throw new SanityException('configuration file is corrupt');
            }

            // Check convert options
            // -------------------------------
            $checking = 'configuration file (options)';
            if (is_null($convertOptions)) {
                $wodOptions = Config::generateWodOptionsFromConfigObj($config);
                if (!isset($wodOptions['webp-convert']['convert'])) {
                    throw new SanityException('conversion options are missing');
                }
                $convertOptions = $wodOptions['webp-convert']['convert'];
            }
            if (!is_array($convertOptions)) {
                throw new SanityException('conversion options are missing');
            }


            // Check destination
            // -------------------------------
            $checking = 'destination';
            $destination = self::getDestination($source, $config);

            $destination = SanityCheck::absPath($destination);

            // Check log dir
            // -------------------------------
            $checking = 'conversion log dir';
            if (isset($config['enable-logging']) && $config['enable-logging']) {
                $logDir = SanityCheck::absPath(Paths::getWebPExpressContentDirAbs() . '/log');
            } else {
                $logDir = null;
            }


        } catch (\Exception $e) {
            return [
                'success' => false,
                'msg' => 'Check failed for ' . $checking . ': '. $e->getMessage(),
                'log' => '',
            ];
        }

        // Done with sanitizing, lets get to work!
        // ---------------------------------------
//return false;
        $result = ConvertHelperIndependent::convert($source, $destination, $convertOptions, $logDir, $converter);

//error_log('looki:' . $source . $converter);
        // If we are using stack converter, check if Ewww discovered invalid api key
        //if (is_null($converter)) {
            if (isset(Ewww::$nonFunctionalApiKeysDiscoveredDuringConversion)) {
                // We got an invalid or exceeded api key (at least one).
                //error_log('look:' . print_r(Ewww::$nonFunctionalApiKeysDiscoveredDuringConversion, true));
                EwwwTools::markApiKeysAsNonFunctional(
                    Ewww::$nonFunctionalApiKeysDiscoveredDuringConversion,
                    Paths::getConfigDirAbs()
                );
            }
        //}

        self::updateBiggerThanOriginalMark($source, $destination, $config);

        if ($result['success'] === true) {
            $result['filesize-original'] = @filesize($source);
            $result['filesize-webp'] = @filesize($destination);
            $result['destination-path'] = $destination;

            $destinationOptions = DestinationOptions::createFromConfig($config);

            $rootOfDestination = Paths::destinationRoot($rootId, $destinationOptions);

            $relPathFromImageRootToSource = PathHelper::getRelDir(
                realpath(Paths::getAbsDirById($rootId)),
                realpath($source)
            );
            $relPathFromImageRootToDest = ConvertHelperIndependent::appendOrSetExtension(
                $relPathFromImageRootToSource,
                $config['destination-folder'],
                $config['destination-extension'],
                ($rootId == 'uploads')
            );

            $result['destination-url'] = $rootOfDestination['url'] . '/' . $relPathFromImageRootToDest;
        }
        return $result;
    }

    /**
     *  Determine the location of a source from the location of a destination.
     *
     *  If for example Operation mode is set to "mingled" and extension is set to "Append .webp",
     *  the result of looking passing "/path/to/logo.jpg.webp" will be "/path/to/logo.jpg".
     *
     *  Additionally, it is tested if the source exists. If not, false is returned.
     *  The destination does not have to exist.
     *
     *  @return  string|null  The source path corresponding to a destination path
     *                        - or false on failure (if the source does not exist or $destination is not sane)
     *
     */
    public static function findSource($destination, &$config = null)
    {
        try {
            // Check that destination path is sane and inside document root
            $destination = SanityCheck::absPathIsInDocRoot($destination);
        } catch (SanityException $e) {
            return false;
        }

        // Load config if not already loaded
        if (is_null($config)) {
            $config = Config::loadConfigAndFix();
        }

        return ConvertHelperIndependent::findSource(
            $destination,
            $config['destination-folder'],
            $config['destination-extension'],
            $config['destination-structure'],
            Paths::getWebPExpressContentDirAbs(),
            new ImageRoots(Paths::getImageRootsDef())
        );
    }

    public static function processAjaxConvertFile()
    {

        if (!check_ajax_referer('webpexpress-ajax-convert-nonce', 'nonce', false)) {
        //if (true) {
            //wp_send_json_error('The security nonce has expired. You need to reload the settings page (press F5) and try again)');
            //wp_die();

            $result = [
                'success' => false,
                'msg' => 'The security nonce has expired. You need to reload the settings page (press F5) and try again)',
                'stop' => true
            ];

            echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
            wp_die();
        }

        // Check input
        // --------------
        try {
            // Check "filename"
            $checking = '"filename" argument';
            Validate::postHasKey('filename');

            $filename = sanitize_text_field(stripslashes($_POST['filename']));

            // holy moly! Wordpress automatically adds slashes to the global POST vars - https://stackoverflow.com/questions/2496455/why-are-post-variables-getting-escaped-in-php
            $filename = wp_unslash($_POST['filename']);

            //$filename = SanityCheck::absPathExistsAndIsFileInDocRoot($filename);
            // PS: No need to check mime version as webp-convert does that.


            // Check converter id
            // ---------------------
            $checking = '"converter" argument';
            if (isset($_POST['converter'])) {
                $converterId = sanitize_text_field($_POST['converter']);
                Validate::isConverterId($converterId);
            }


            // Check "config-overrides"
            // ---------------------------
            $checking = '"config-overrides" argument';
            if (isset($_POST['config-overrides'])) {
                $configOverridesJSON = SanityCheck::noControlChars($_POST['config-overrides']);
                $configOverridesJSON = preg_replace('/\\\\"/', '"', $configOverridesJSON); // We got crazy encoding, perhaps by jQuery. This cleans it up

                $configOverridesJSON = SanityCheck::isJSONObject($configOverridesJSON);
                $configOverrides = json_decode($configOverridesJSON, true);

                // PS: We do not need to validate the overrides.
                // webp-convert checks all options. Nothing can be passed to webp-convert which causes harm.
            }

        } catch (SanityException $e) {
            wp_send_json_error('Sanitation check failed for ' . $checking . ': '. $e->getMessage());
            wp_die();
        } catch (ValidateException $e) {
            wp_send_json_error('Validation failed for ' . $checking . ': '. $e->getMessage());
            wp_die();
        }


        // Input has been processed, now lets get to work!
        // -----------------------------------------------
        if (isset($configOverrides)) {
            $config = Config::loadConfigAndFix();


            // convert using specific converter
            if (!is_null($converterId)) {

                // Merge in the config-overrides (config-overrides only have effect when using a specific converter)
                $config = array_merge($config, $configOverrides);

                $converter = ConvertersHelper::getConverterById($config, $converterId);
                if ($converter === false) {
                    wp_send_json_error('Converter could not be loaded');
                    wp_die();
                }

                // the converter options stored in config.json is not precisely the same as the ones
                // we send to webp-convert.
                // We need to "regenerate" webp-convert options in order to use the ones specified in the config-overrides
                // And we need to merge the general options (such as quality etc) into the option for the specific converter

                $generalWebpConvertOptions = Config::generateWodOptionsFromConfigObj($config)['webp-convert']['convert'];
                $converterSpecificWebpConvertOptions = $converter['options'];

                $webpConvertOptions = array_merge($generalWebpConvertOptions, $converterSpecificWebpConvertOptions);
                unset($webpConvertOptions['converters']);

                // what is this? - I forgot why!
                //$config = array_merge($config, $converter['options']);
                $result = self::convertFile($filename, $config, $webpConvertOptions, $converterId);

            } else {
                $result = self::convertFile($filename, $config);
            }
        } else {
            $result = self::convertFile($filename);
        }

        $nonceTick = wp_verify_nonce($_REQUEST['nonce'], 'webpexpress-ajax-convert-nonce');
        if ($nonceTick == 2) {
            $result['new-convert-nonce'] = wp_create_nonce('webpexpress-ajax-convert-nonce');
            //  wp_create_nonce('webpexpress-ajax-convert-nonce')
        }

        $result['nonce-tick'] = $nonceTick;


        $result = self::utf8ize($result);

        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);

        wp_die();
    }

    private static function utf8ize($d) {
        if (is_array($d)) {
            foreach ($d as $k => $v) {
                $d[$k] = self::utf8ize($v);
            }
        } else if (is_string ($d)) {
            return utf8_encode($d);
        }
        return $d;
    }
}
