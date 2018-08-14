<?php
/*
URL parameters:

base-path:
    Sets the base path used for "source" and "destination-root" options.
    Must be relative to document root, or absolute (not recommended)
    When used in .htaccess, set it to the folder containing the .htaccess file, relative to document root.
    If for example document root is /var/www/example.com/ and you have a subdirectory "wordpress", which you
    want WebPOnDemand to work on, you should place .htaccess rules in the "wordpress" directory, and
    your "base-path" will be "wordpress"
    If not set, it defaults to be the path of webp-on-demand.php

source: Path to source file.
    Path to source file, relative to 'base-path' option.
    The final path is calculated like this:
        [base-path] + [path to source file] + ".webp".
    absolute path is depreciated, but supported for backwards compatability.

destination-root:
    The path of where you want the converted files to reside, relative to the 'base-path' option.
    If you want converted files to be put in the same folder as the originals, you can set destination-root to ".", or
    leave it blank. If you on the other hand want all converted files to reside in their own folder, set the
    destination-root to point to that folder. The converted files will be stored in a hierarchy that matches the source
    files. With destination-root set to "webp-cache", the source file "images/2017/cool.jpg" will be stored at
    "webp-cache/images/2017/cool.jpg.webp".
    Double-dots in paths are allowed, ie "../webp-cache"
    The final destination is calculated like this:
        [base-path] + [destination-root] + [path to source file] + ".webp".
    Default is "."
    You can also supply an absolute path

quality (optional):
    The quality of the generated WebP image, "auto" or 0-100. Defaults to "auto"

max-quality (optional):
    The maximum quality. Only relevant when quality is set to "auto"

default-quality (optional):
    Fallback value for quality, if it isn't possible to detect quality of jpeg. Only relevant when quality is set to "auto"

metadata (optional):
    If set to "none", all metadata will be stripped
    If set to "all", all metadata will be preserved
    Note however that not all converters supports preserving metadata. cwebp supports it, imagewebp does not.

converters (optional):
    Comma-separated list of converters. Ie. "cwebp,gd".
    To pass options to the individual converters, see next.
    Also, check out the WebPConvert docs

[converter-id]-[option-name] (optional):
    This pattern is used for setting options on the individual converters.
    Ie, in order to set the "key" option of the "ewww" converter, you pass "ewww-key".

[converter-id]-[n]-[option-name] (optional):
    Use this pattern for targeting options of a converter, that are used multiple times. However, use the pattern above
    for targeting the first occurence. `n` stands for the nth occurence of that converter in the `converters` option.
    Example: `...&converters=cwebp,ewww,ewww,gd,ewww&ewww-key=xxx&ewww-2-key=yyy&ewww-3-key=zzz&gd-skip-pngs=1`

[converter-id]-[option-name]-[2] (optional):
    This is an alternative, and simpler pattern than the above, for providing fallback for a single converter.
    If WebPOnDemand detects that such an option is provided (ie ewww-key-2=yyy), it will automatically insert an extra
    converter into the array (immidiately after), configured with the options with the '-2' postfix.
    Example: `...&converters=cwebp,ewww,gd&ewww-key=xxx&ewww-key-2=yyy`
    - will result in converter order: cwebp, ewww (with key=xxx), ewww (with key=yyy), gd

converters (optional):
    Comma-separated list of converters. Ie. "cwebp,gd".
    Passing options to the individual converters is done by passing options named like this:
    [converter-name]-[option-name] (see below)

    See WebPConvert documentation for more info

[converter]-[option-name] (optional):
    Options for the converters can be passed as parameters with names like this: [converter]-[option-name].
    Ie, in order to set the "key" option of the "ewww" converter, you pass "ewww-key".

    If the same converter is going to be used with different configurations, you can add "-[n]" after the converter id.
    Ie: ...&converters=ewww,ewww&ewww-key=xxx&ewww-2-key=yyy

    See WebPConvert documentation for more info

debug (optional):
    If set, a report will be served (as text) instead of an image

fail:
   Default:  "original"
   What to serve if conversion fails

   Possible values:
   - "original":        Serves the original image (source)
   - "404":             Serves a 404 header
   - "report":          Serves the error message as plain text
   - "report-as-image":  Serves the error message as an image

critical-fail:
  Default:  "report-as-image"
  What to serve if conversion fails and source image is not available

  Possible values:
  - "404":             Serves a 404 header
  - "report":          Serves the error message as plain text
  - "report-as-image":  Serves the error message as an image

*/

namespace WebPOnDemand;

use WebPConvertAndServe\WebPConvertAndServe;
use WebPConvert\WebPConvert;
use WebPConvert\Converters\ConverterHelper;

class WebPOnDemand
{
    // transform options with '-2' postfix into new converters
    // Idea: rename function to ie "transformFallbackOptionsIntoNewConverters"
    private static function transformFallbackOptions($converters) {
        foreach ($converters as $i => &$converter) {
            $duplicateConverter = false;
            foreach ($converter['options'] as $optionName => $optionValue) {
                if (substr($optionName, -2) === '-2') {
                    $duplicateConverter = true;
                    break;
                }
            }
            if ($duplicateConverter) {
                $options2 = [];
                foreach ($converter['options'] as $optionName => $optionValue) {
                    if (substr($optionName, -2) === '-2') {
                        $options2[substr($optionName, 0, -2)] = $optionValue;
                        unset($converter['options'][$optionName]);
                    }
                }
                array_splice($converters, $i+1, 0, [['converter' => $converter['converter'], 'options' => $options2]]);
            }
        }
        return $converters;
    }

    private static function setOption(&$array, $parameterName, $optionName, $optionType)
    {
        if (!isset($_GET[$parameterName])) {
            return;
        }
        switch ($optionType) {
            case 'string':
                //$options['converters'][$i]['options'][$optionName] = $_GET[$parameterName];
                $array[$optionName] = $_GET[$parameterName];
            break;
            case 'boolean':
                //$options['converters'][$i]['options'][$optionName] = ($_GET[$parameterName] == '1');
                $array[$optionName] = ($_GET[$parameterName] == '1');
            break;
        }
    }
    private static function removeDoubleSlash($str)
    {
        return preg_replace('/\/\//', '/', $str);
    }
    private static function getRelDir($from_dir, $to_dir)
    {
        $fromDirParts = explode('/', str_replace('\\', '/', $from_dir));
        $toDirParts = explode('/', str_replace('\\', '/', $to_dir));
        $i = 0;
        while (($i < count($fromDirParts)) && ($i < count($toDirParts)) && ($fromDirParts[$i] == $toDirParts[$i])) {
            $i++;
        }
        $rel = "";
        for ($j = $i; $j < count($fromDirParts); $j++) {
            $rel .= "../";
        }

        for ($j = $i; $j < count($toDirParts); $j++) {
            $rel .= $toDirParts[$j];
            if ($j < count($toDirParts)-1) {
                $rel .= '/';
            }
        }
        return $rel;
    }

    public static function serve($scriptPath)
    {

        $debug = (isset($_GET['debug']) ? ($_GET['debug'] != 'no') : false);

        //$source = $root . '/' . $_GET['source'];

        if (!isset($_GET['base-path'])) {
            $basePath = $scriptPath;
        } else {
            $basePath = $_GET['base-path'];
            if ((substr($basePath, 0, 1) == '/')) {
            } else {
                $basePath = $_SERVER["DOCUMENT_ROOT"] . '/' . $basePath;
            }
        }

        // Calculate $source and $sourceRelToBasePath (needed for calculating $destination)
        $sourcePath = $_GET['source'];  // this path includes filename
        if ((substr($sourcePath, 0, 1) == '/')) {
            $sourcePathAbs = $sourcePath;
            $sourceRelToBasePath = self::getRelDir($basePath, $sourcePathAbs);
            //echo $basePath . '<br>' . $sourcePathAbs . '<br>' . $sourceRelToBasePath . '<br><br>';

        } else {
            $sourceRelToBasePath = $sourcePath;
            $sourcePathAbs = $basePath . '/' . $sourcePath;
        }
        $source = self::removeDoubleSlash($sourcePathAbs);

        // Calculate $destination from destination-root and $basePath
        if (!isset($_GET['destination-root'])) {
            $destinationRoot = '.';
        } else {
            $destinationRoot = $_GET['destination-root'];
        }
        if ((substr($destinationRoot, 0, 1) == '/')) {
            // absolute path - overrides basepath
            $destinationRootAbs = $destinationRoot;
        } else {
            $destinationRootAbs = $basePath . '/' . $destinationRoot;
        }
        $destination = self::removeDoubleSlash($destinationRootAbs . '/' . $sourceRelToBasePath . '.webp');




        $options = [];

        // quality
        if (isset($_GET['quality'])) {
            if ($_GET['quality'] == 'auto') {
                $options['quality'] = 'auto';
            } else {
                $options['quality'] = intval($_GET['quality']);
            }
        }

        // max-quality
        if (isset($_GET['max-quality'])) {
            $options['max-quality'] = intval($_GET['max-quality']);
        }

        // default-quality
        if (isset($_GET['default-quality'])) {
            $options['default-quality'] = intval($_GET['default-quality']);
        }

        // method
        if (isset($_GET['method'])) {
            $options['method'] = $_GET['method'];
        }

        // metadata
        if (isset($_GET['metadata'])) {
            $options['metadata'] = $_GET['metadata'];
        }

        // converters
        if (isset($_GET['converters'])) {
            $conv = explode(',', $_GET['converters']);
            $options['converters'] = [];
            foreach ($conv as $i => $converter_name) {
                $options['converters'][] = ['converter' => $converter_name, 'options' => []];
            }
        } else {
            // Copy default converters.
            // We need them in case some has options
            foreach (ConverterHelper::$defaultOptions['converters'] as $i => $converter_name) {
                $options['converters'][] = ['converter' => $converter_name, 'options' => []];
            }
        }


        // Converter options
        $counts = [];
        foreach ($options['converters'] as $i => $converter_object) {
            $converter = $converter_object['converter'];
            //echo $i . ':' . $converter;
            if (!isset($counts[$converter])) {
                $counts[$converter] = 1;
                $id = $converter;
            }
            else {
                $counts[$converter]++;
            }

            $className = ConverterHelper::getClassNameOfConverter($converter);
            $availOptions = array_column($className::$extraOptions, 'type', 'name');
            //print_r($availOptions);

            foreach ($availOptions as $optionName => $optionType) {
                $parameterName = $converter . (($counts[$converter] > 1 ? '-' . $counts[$converter] : '')) . '-' . $optionName;

                self::setOption($options['converters'][$i]['options'], $parameterName, $optionName, $optionType);
                self::setOption($options['converters'][$i]['options'], $parameterName . '-2', $optionName . '-2', $optionType);

            }
        }

        // transform options with '-2' postfix into new converters
        $options['converters'] = self::transformFallbackOptions($options['converters']);

        //echo '<pre>' . print_r($options, true) . '</pre>';
        // Failure actions
        $failCodes = [
            "original" => WebPConvertAndServe::$ORIGINAL,
            "404" => WebPConvertAndServe::$HTTP_404,
            "report-as-image" => WebPConvertAndServe::$REPORT_AS_IMAGE,
            "report" => WebPConvertAndServe::$REPORT,
        ];

        $fail = 'original';
        if (isset($_GET['fail'])) {
            $fail = $_GET['fail'];
        }
        $fail = $failCodes[$fail];

        $criticalFail = 'report';
        if (isset($_GET['critical-fail'])) {
            $criticalFail = $_GET['critical-fail'];
        }
        $criticalFail = $failCodes[$criticalFail];

        if (!$debug) {
            return WebPConvertAndServe::convertAndServeImage($source, $destination, $options, $fail, $criticalFail);
        } else {

            // TODO
            // As we do not want to leak api keys, I have commented out the following.
/*
            echo 'GET parameters:<br>';
            foreach ($_GET as $key => $value) {
                echo '<i>' . $key . '</i>: ' . htmlspecialchars($value) . '<br>';
            }
            echo '<br>';*/

            //echo $_SERVER['DOCUMENT_ROOT'];
            WebPConvertAndServe::convertAndReport($source, $destination, $options);
            return 1;
        }
    }
}
