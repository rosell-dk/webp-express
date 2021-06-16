<?php

namespace WebPExpress;

class CLI extends \WP_CLI_Command
{

    private static function printableSize($bytes) {
        return ($bytes < 10000) ? $bytes . " bytes" : round($bytes / 1024) . ' kb';
    }
    /**
     *  Convert images to webp
     *
     *  ## OPTIONS
     *  [<location>]
     *  : Limit which folders to process to a single location. Must be uploads|themes|plugins|wp-content|index
     *
     *  [--quality]
     *  : Override quality with specified (0-100)
     *
     *  [--near-lossless]
     *  : Override near-lossless quality with specified (0-100)
     *
     *  [--alpha-quality]
     *  : Override alpha-quality quality with specified (0-100)
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
        $config = array_merge($config, $override);

        \WP_CLI::log('Converting with the following settings:');
        \WP_CLI::log('- Lossless quality: ' . $config['png-quality'] . ' for PNG, ' . $config['max-quality'] . " for jpeg");
        \WP_CLI::log(
          '- Near lossless: ' .
          ($config['png-enable-near-lossless'] ? $config['png-near-lossless'] : 'disabled') . ' for PNG, ' .
          ($config['jpeg-enable-near-lossless'] ? $config['jpeg-near-lossless'] : 'disabled') . ' for jpeg, '
        );
        \WP_CLI::log('- Alpha quality: ' . $config['alpha-quality']);

        if (count($override) == 0) {
          \WP_CLI::log('Note that you can override these with --quality=<quality>, ect');
        }
        \WP_CLI::log('');


        //print_r($config);
        //\WP_CLI::log($args[0]);
        if (!isset($args[0])) {
          $arr = BulkConvert::getList($config);
          foreach($arr as $group){
              \WP_CLI::log($group['groupName'] . ' contains ' . count($group['files']) . ' unconverted files');
              //$files = array_reverse($group['files']);
          }
          //\WP_CLI::log('To only convert ie uploads, you can do: "wp webp_express convert uploads"');
          \WP_CLI::log('');
        } else {
          if (!in_array($args[0], Paths::getImageRootIds())) {
              \WP_CLI::error(
                '"' . $args[0] . '" is not a valid location. ' .
                'Valid locations are: ' . implode(', ', Paths::getImageRootIds())
              );
          }
          $config['scope'] = [$args[0]];
          $arr = BulkConvert::getList($config);
          if (count($arr[0]['files']) == 0) {
            \WP_CLI::log('Nothing to convert in ' . $args[0]);
          }
        }

        $orgTotalFilesize = 0;
        $webpTotalFilesize = 0;

        foreach($arr as $group){
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


                $result = Convert::convertFile($path, $config);

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
                        \WP_CLI::colorize("%RFailed%n")
                    );
                    \WP_CLI::log($result['msg']);
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
