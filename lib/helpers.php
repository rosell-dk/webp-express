<?php

require_once "Paths.php";

use \WebPExpress\Paths;

class WebPExpressHelpers
{


/*
    public static function insertHTAccessRules($rules)
    {
      if (self::doInsertHTAccessRules($rules)) {
        update_option('webp-express-message-pending', true, false );
        update_option('webp-express-inserted-rules-ok', true, false);
      } else {
        update_option('webp-express-failed-inserting-rules', true, false);
        update_option('webp-express-deactivate', true, false);
      }
    }
*/

  /* Get relative path between one dir and the other.
     ie
        from:   /var/www/wordpress/wp-content/plugins/webp-express
        to:     /var/www/wordpress/wp-content/uploads
        result: ../../uploads/

     or
        from:  /mnt/Work/playground/webp-express-test/wordpress/
        to:    /mnt/Work/playground/webp-express-test/wordpress/wp-content/uploads/webp-express
        result: wp-content/uploads/webp-express

     */
    public static function get_rel_dir($from_dir, $to_dir) {
    $from_dir = untrailingslashit($from_dir);
    $to_dir = untrailingslashit($to_dir);

    $from_dir_parts = explode('/', str_replace( '\\', '/', $from_dir ));
    $to_dir_parts = explode('/', str_replace( '\\', '/', $to_dir ));
    $i = 0;
    while (($i < count($from_dir_parts)) && ($i < count($to_dir_parts)) && ($from_dir_parts[$i] == $to_dir_parts[$i])) {
      $i++;
    }
    $rel = "";
    for ($j = $i; $j < count($from_dir_parts); $j++) {
      $rel .= "../";
    }

    for ($j = $i; $j < count($to_dir_parts); $j++) {
      $rel .= $to_dir_parts[$j] . '/';
    }
    if ($rel == '') {
        $rel = '.';
    }
    return $rel;
    }


      public static function calculateUrlsAndPaths()
      {
          // Calculate URL's
          $upload_dir = wp_upload_dir();
          $destinationRootUrlPathAbs = $upload_dir['baseurl'] . '/' . 'webp-express';

          // Calculate url path to directory of image converter
          $pluginUrlAbs = plugins_url('', WEBPEXPRESS_PLUGIN);

          $converterUrlPath = parse_url($pluginUrlAbs)['path'];

          // Calculate destination root url path
          $destinationRootUrlPath = parse_url($destinationRootUrlPathAbs)['path'];

          // Calculate Wordpress url path
          // If site for example is accessed example.com/blog/, then the url path is "blog"
          $wpUrlPath = trailingslashit(parse_url(site_url())['path']);    // ie "/blog/" or "/"

          // TODO: WHOOPSIE! Shouldn't we use the home_url rather than site_url ?

          $converterUrlPathRelativeToSiteUrl = WebPExpressHelpers::get_rel_dir(untrailingslashit($wpUrlPath), $converterUrlPath);
          $siteUrlPathRelativeToConverterPath = WebPExpressHelpers::get_rel_dir($converterUrlPath, untrailingslashit($wpUrlPath));


          // Calculate file dirs
          // --------------------

          $destinationRoot = trailingslashit($upload_dir['basedir']) . 'webp-express';

          $WebExpressRoot = untrailingslashit(WEBPEXPRESS_PLUGIN_DIR);

          $destinationRootRelativeToWebExpressRoot = untrailingslashit(WebPExpressHelpers::get_rel_dir($WebExpressRoot, $destinationRoot));

          // $destinationRoot is ie '/webp-express-test/wordpress/wp-content/uploads/webp-express/'
          // $wordpressRoot is ie '/mnt/Work/playground/webp-express-test/wordpress/'
          $wordpressRoot = untrailingslashit(ABSPATH);
          $destinationRootRelativeToWordpressRoot = untrailingslashit(WebPExpressHelpers::get_rel_dir($wordpressRoot . '/', $destinationRoot));

          return [
              'urls' => [
                  'webpExpressRoot' => untrailingslashit($converterUrlPath),
                  'convert' => untrailingslashit($converterUrlPath) . 'convert.php',
                  'destinationRoot' => untrailingslashit($destinationRootUrlPath),
                  'wpRoot' => untrailingslashit($wpUrlPath),  // ie "/blog" or ""
                  'converterUrlPathRelativeToSiteUrl' => $converterUrlPathRelativeToSiteUrl,
                  'siteUrlPathRelativeToConverterPath' => $siteUrlPathRelativeToConverterPath
              ],
              'filePaths' => [
                  'wordpressRoot' => $wordpressRoot,
                  'destinationRoot' => $destinationRoot,
                  'webpExpressRoot' => $WebExpressRoot,
                  'destinationRootRelativeToWebExpressRoot' => $destinationRootRelativeToWebExpressRoot,
                  'destinationRootRelativeToWordpressRoot' => $destinationRootRelativeToWordpressRoot
              ],
              // TODO: read up on this, and make complete tests
              // https://wordpress.stackexchange.com/questions/188448/whats-the-difference-between-get-home-path-and-abspath
              'pathsForHtaccess' => [
                  //$basePath, $destinationRoot, $scriptPath
                  'basePath' => untrailingslashit(WebPExpressHelpers::get_rel_dir($_SERVER['DOCUMENT_ROOT'], untrailingslashit(ABSPATH))),
                  'destinationRoot' => $destinationRootRelativeToWordpressRoot,   //  Where to place converted files, relative to the base path.
                  'scriptPath' => untrailingslashit($converterUrlPathRelativeToSiteUrl),

                  //'abspath' => ABSPATH,
                  //'dr' => $_SERVER['DOCUMENT_ROOT'],
                  //'bp' => str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', untrailingslashit(ABSPATH)),
              ]
          ];
      }

}
