<?php

namespace WebPExpress;

class CLI extends \WP_CLI_Command
{

    private static function printableSize($bytes) {
        return ($bytes < 10000) ? $bytes . " bytes" : round($bytes / 1024) . ' kb';
    }

    /**
     * Convert images to webp
     *
     * ## OPTIONS
     * [<location>]
     * : Limit which folders to process to a single location. Ie "uploads/2021". The first part is the
     *   "image root", which must be "uploads", "themes", "plugins", "wp-content" or "index"
     *
     * [--reconvert]
     * : Even convert images that are already converted (new conversions replaces the old conversions)
     *
     * [--only-png]
     * : Only convert PNG images
     *
     * [--only-jpeg]
     * : Only convert jpeg images
     *
     * [--quality]
     * : Override quality with specified (0-100)
     *
     * [--near-lossless]
     * : Override near-lossless quality with specified (0-100)
     *
     * [--alpha-quality]
     * : Override alpha-quality quality with specified (0-100)
     *
     * [--encoding]
     * : Override encoding quality with specified ("auto", "lossy" or "lossless")
     *
     * [--converter=<converter>]
     * : Specify the converter to use (default is to use the stack). Valid options: cwebp | vips | ewww | imagemagick | imagick | gmagick | graphicsmagick | ffmpeg | gd | wpc | ewww
     */
    public function convert($args, $assoc_args)
    {
        $config = Config::loadConfigAndFix();
        $override = [];

        if (isset($assoc_args['quality'])) {
            $override['max-quality'] = intval($assoc_args['quality']);
            $override['png-quality'] = intval($assoc_args['quality']);
        }
        if (isset($assoc_args['near-lossless'])) {
            $override['png-near-lossless'] = intval($assoc_args['near-lossless']);
            $override['jpeg-near-lossless'] = intval($assoc_args['near-lossless']);
        }
        if (isset($assoc_args['alpha-quality'])) {
            $override['alpha-quality'] = intval($assoc_args['alpha-quality']);
        }
        if (isset($assoc_args['encoding'])) {
            if (!in_array($assoc_args['encoding'], ['auto', 'lossy', 'lossless'])) {
                \WP_CLI::error('encoding must be auto, lossy or lossless');
            }
            $override['png-encoding'] = $assoc_args['encoding'];
            $override['jpeg-encoding'] = $assoc_args['encoding'];
        }
        if (isset($assoc_args['converter'])) {
            if (!in_array($assoc_args['converter'], ConvertersHelper::getDefaultConverterNames())) {
                \WP_CLI::error(
                  '"' . $assoc_args['converter'] . '" is not a valid converter id. ' .
                  'Valid converters are: ' . implode(', ', ConvertersHelper::getDefaultConverterNames())
                );
            }
        }

        $config = array_merge($config, $override);

        \WP_CLI::log('Converting with the following settings:');
        \WP_CLI::log('- Lossless quality: ' . $config['png-quality'] . ' for PNG, ' . $config['max-quality'] . " for jpeg");
        \WP_CLI::log(
            '- Near lossless: ' .
            ($config['png-enable-near-lossless'] ? $config['png-near-lossless'] : 'disabled') . ' for PNG, ' .
            ($config['jpeg-enable-near-lossless'] ? $config['jpeg-near-lossless'] : 'disabled') . ' for jpeg, '
        );
        \WP_CLI::log('- Alpha quality: ' . $config['alpha-quality']);
        \WP_CLI::log('- Encoding: ' . $config['png-encoding'] . ' for PNG, ' . $config['jpeg-encoding'] . " for jpeg");

        if (count($override) == 0) {
            \WP_CLI::log('Note that you can override these with --quality=<quality>, etc');
        }
        \WP_CLI::log('');


        $listOptions = BulkConvert::defaultListOptions($config);
        if (isset($assoc_args['reconvert'])) {
            $listOptions['filter']['only-unconverted'] = false;
        }
        if (isset($assoc_args['only-png'])) {
            $listOptions['filter']['image-types'] = 2;
        }
        if (isset($assoc_args['only-jpeg'])) {
            $listOptions['filter']['image-types'] = 1;
        }

        if (!isset($args[0])) {
          $groups = BulkConvert::getList($config, $listOptions);
          foreach($groups as $group){
              \WP_CLI::log($group['groupName'] . ' contains ' . count($group['files']) . ' ' .
              (isset($assoc_args['reconvert']) ? '' : 'unconverted ') .
              'files');
          }
          \WP_CLI::log('');
        } else {
          $location = $args[0];
          if (strpos($location, '/') === 0) {
              $location = substr($location, 1);
          }
          if (strpos($location, '/') === false) {
              $rootId = $location;
              $path = '.';
          } else {
              list($rootId, $path) = explode('/', $location, 2);
          }

          if (!in_array($rootId, Paths::getImageRootIds())) {
              \WP_CLI::error(
                '"' . $args[0] . '" is not a valid image root. ' .
                'Valid roots are: ' . implode(', ', Paths::getImageRootIds())
              );
          }

          $root = Paths::getAbsDirById($rootId) . '/' . $path;
          if (!file_exists($root)) {
            \WP_CLI::error(
              '"' . $args[0] . '" does not exist. '
            );
          }
          $listOptions['root'] = $root;
          $groups = [
              [
                  'groupName' => $args[0],
                  'root' => $root,
                  'files' => BulkConvert::getListRecursively('.', $listOptions)
              ]
          ];
          if (count($groups[0]['files']) == 0) {
            \WP_CLI::log('Nothing to convert in ' . $args[0]);
          }
        }

        $orgTotalFilesize = 0;
        $webpTotalFilesize = 0;

        $converter = null;
        $convertOptions = null;

        if (isset($assoc_args['converter'])) {

            $converter = $assoc_args['converter'];
            $convertOptions = Config::generateWodOptionsFromConfigObj($config)['webp-convert']['convert'];

            // find the converter
            $optionsForThisConverter = null;
            foreach ($convertOptions['converters'] as $c) {
                if ($c['converter'] == $converter) {
                    $optionsForThisConverter = (isset($c['options']) ? $c['options'] : []);
                    break;
                }
            }
            if (!is_array($optionsForThisConverter)) {
                \WP_CLI::error('Failed handling options');
            }

            $convertOptions = array_merge($convertOptions, $optionsForThisConverter);
            unset($convertOptions['converters']);
        }

        foreach($groups as $group){
            if (count($group['files']) == 0) continue;

            \WP_CLI::log('Converting ' . count($group['files']) . ' files in ' . $group['groupName']);
            \WP_CLI::log('------------------------------');
            $root = $group['root'];

            $files = array_reverse($group['files']);
            //echo count($group["files"]);
            foreach($files as $key => $file)
            {
                $path = trailingslashit($group['root']) . $file;
                \WP_CLI::log('Converting: ' . $file);

                $result = Convert::convertFile($path, $config, $convertOptions, $converter);

                if ($result['success']) {
                    $orgSize = $result['filesize-original'];
                    $webpSize = $result['filesize-webp'];

                    $orgTotalFilesize += $orgSize;
                    $webpTotalFilesize += $webpSize;

                    //$percentage = round(($orgSize - $webpSize)/$orgSize * 100);
                    $percentage = ($orgSize == 0 ? 100 : round(($webpSize/$orgSize) * 100));

                    \WP_CLI::log(
                        \WP_CLI::colorize(
                            "%GOK%n. " .
                            "Size: " .
                            ($percentage<90 ? "%G" : ($percentage<100 ? "%Y" : "%R")) .
                            $percentage .
                            "% %nof original" .
                            " (" . self::printableSize($orgSize) . ' => ' . self::printableSize($webpSize) .
                            ") "
                        )
                    );
                    //print_r($result);
                } else {
                    \WP_CLI::log(
                        \WP_CLI::colorize("%RConversion failed. " . $result['msg'] . "%n")
                    );
                }
            }
        }

        if ($orgTotalFilesize > 0) {
          $percentage = ($orgTotalFilesize == 0 ? 100 : round(($webpTotalFilesize/$orgTotalFilesize) * 100));
          \WP_CLI::log(
              \WP_CLI::colorize(
                  "Done. " .
                  "Size of webps: " .
                  ($percentage<90 ? "%G" : ($percentage<100 ? "%Y" : "%R")) .
                  $percentage .
                  "% %nof original" .
                  " (" . self::printableSize($orgTotalFilesize) . ' => ' . self::printableSize($webpTotalFilesize) .
                  ") "
              )
          );
        }
    }

    /**
     *  Flush webps
     *
     *  ## OPTIONS
     *  [--only-png]
     *  : Only flush webps that are conversions of a PNG)
     */
    public function flushwebp($args, $assoc_args)
    {
        $config = Config::loadConfigAndFix();

        $onlyPng = isset($assoc_args['only-png']);

        if ($onlyPng) {
            \WP_CLI::log('Flushing webp files that are conversions of PNG images');
        } else {
            \WP_CLI::log('Flushing all webp files');
        }

        $result = CachePurge::purge($config, $onlyPng);

        \WP_CLI::log(
          \WP_CLI::colorize("%GFlushed " . $result['delete-count'] . " webp files%n")
        );
        if ($result['fail-count'] > 0) {
          \WP_CLI::log(
            \WP_CLI::colorize("%RFailed deleting " . $result['fail-count'] . " webp files%n")
          );
        }
    }


}
