<?php

class WebPExpressHelpers
{

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

    public static function generateHTAccessRules()
    {
        $options = '';
        $options .= '&max-quality=' . get_option('webp_express_max_quality');
        //$options .= '&method=' . get_option('webp_express_method');
        $options .= '&fail=' . get_option('webp_express_failure_response');
        $options .= '&critical-fail=report';

        $converters_and_options = json_decode(get_option('webp_express_converters'), true);

        $converter_options = '';
        foreach ($converters_and_options as $converter) {
            if (isset($converter['deactivated'])) continue;
            $converters[] = $converter['converter'];
            if (isset($converter['options'])) {
                foreach ($converter['options'] as $converter_option => $converter_value) {
                    $converter_options .= '&' . $converter['id'] . '-' . $converter_option . '=' . $converter_value;
                }
            };
        }
        $options .= '&converters=' . implode(',', $converters);
        $options .= $converter_options;

        $urlsAndPaths = WebPExpressHelpers::calculateUrlsAndPaths();
        $urls = $urlsAndPaths['urls'];
        $filePaths = $urlsAndPaths['filePaths'];

        $imageTypes = get_option('webp_express_image_types_to_convert');
        $fileExtensions = [];
        if ($imageTypes & 1) {
          $fileExtensions[] = 'jpe?g';
        }
        if ($imageTypes & 2) {
          $fileExtensions[] = 'png';
        }
        $fileExt = implode('|', $fileExtensions);

        $paths = $urlsAndPaths['pathsForHtaccess'];
        return self::generateHTAccessRules2($fileExt, $paths['basePath'], $paths['destinationRoot'], $paths['scriptPath'], $options);
    }
/*
        if ($imageTypes == 0) {
          $rules = '# Configured not to convert anything!';
          //$rules .= 'php_value include_path ".:/usr/local/lib/php:/your/dir"';
          $rules .= 'php_value include_path ".:/usr/local/lib/php:/hsphere/local/home/z84733/mingo.net/wp-content/plugins/webp-express/vendor/webp-convert/Converters/Binaries"';
        } else {
          $rules = "<IfModule mod_rewrite.c>\n" .

          "  RewriteEngine On\n\n" .
          "  # Redirect to existing converted image (under appropriate circumstances)\n" .
          "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
          "  RewriteCond %{QUERY_STRING} !((^reconvert.*)|(^debug.*))\n" .
          "  RewriteCond %{DOCUMENT_ROOT}" . $urls['destinationRoot'] . "/$1.$2.webp -f\n" .
          "  RewriteRule ^\\/?(.*)\.(" . $fileExt . ")$ " . $urls['destinationRoot'] . "/$1.$2.webp [NC,T=image/webp,E=webpaccept:1,E=WEBPEXISTING:1,QSD]\n\n" .
          "  # Redirect to image converter (under appropriate circumstances)\n" .
          "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
          "  RewriteCond %{QUERY_STRING} (^reconvert.*)|(^debug.*) [OR]\n" .
          "  RewriteCond %{DOCUMENT_ROOT}" . $urls['destinationRoot'] . "/$1.$2.webp !-f\n" .
          "  RewriteCond %{QUERY_STRING} (.*)\n" .
          //"  RewriteRule ^\\/?(.*)\.(" . $fileExt . ")$ " . $urls['converterUrlPathRelativeToSiteUrl'] . "convert.php?source=" . $urls['siteUrlPathRelativeToConverterPath'] . "$1.$2&destination-root=" . $filePaths['destinationRootRelativeToWebExpressRoot'] . $options . "&%1 [NC,E=accept:1]\n" .
          "  RewriteRule ^\\/?(.*)\.(" . $fileExt . ")$ " . $urls['converterUrlPathRelativeToSiteUrl'] . "convert.php?htaccess-path=" . $filePaths['wordpressRoot'] . '&destination-root-rel-to-htaccess-path=' . $filePaths['destinationRootRelativeToWordpressRoot'] . '&source-rel-to-htaccess-path=$1.$2' . $options . "&%1 [NC,E=webpaccept:1,E=WEBPNEW]\n" .

          "</IfModule>\n" .

          "<IfModule mod_headers.c>\n" .
          "  # Apache appends \"REDIRECT_\" in front of the environment variables, but LiteSpeed does not\n" .
          "  # These next three lines are for Apache, in order to set environment variables without \"REDIRECT_\"\n" .
          "  SetEnvIf REDIRECT_WEBPACCEPT 1 WEBPACCEPT=1\n" .
          "  SetEnvIf REDIRECT_WEBPEXISTING 1 WEBPEXISTING=1\n" .
          "  SetEnvIf REDIRECT_WEBPNEW 1 WEBPNEW=1\n\n" .

          "  # Make CDN caching possible." .
          "  Header append Vary Accept env=WEBPACCEPT\n\n" .

          "  # Add headers for debugging\n" .
          "  Header append X-WebP-Express \"Routed to existing converted image\" env=WEBPEXISTING\n" .
          "  Header append X-WebP-Express \"Routed to image converter\" env=WEBPNEW\n" .
          "</IfModule>\n\n" .
          "AddType image/webp .webp\n";
        }
        return $rules;
    }
        */
    /**
     Create rewrite rules for WebP On Demand.

     @param $fileExt            To convert both jpegs and pngs, use "jpe?g|png". To disable converting, use ""
     @param $basePath           Path of the .htaccess relative to document root. Ie "." or "my-sub-site"
     @param $destinationRoot    Where to place converted files, relative to the base path.
     @param $scriptPath         Url path to webp-on-demand.php, relative to the base directory.
     @param $options            String of options. If not empty, it must start with "&". Ie "&converters=cwebp,gd&quality=auto"

     Note: None of the paths supplied may start or end with a forward slash.
     */
    private static function generateHTAccessRules2($fileExt, $basePath, $destinationRoot, $scriptPath, $options)
    {
        $rules = '';
        if ($fileExt == '') {
          $rules .= '# Configured not to convert anything!';
        } else {
          $rules .= "<IfModule mod_rewrite.c>\n" .

          "  RewriteEngine On\n\n" .

          "  # Redirect to existing converted image (under appropriate circumstances)\n" .
          "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
          "  RewriteCond %{QUERY_STRING} !((^reconvert.*)|(^debug.*))\n" .
          "  RewriteCond %{DOCUMENT_ROOT}/" . $basePath . "/" . $destinationRoot . "/$1.$2.webp -f\n" .
          "  RewriteRule ^\/?(.*)\.(jpe?g|png)$ /" . $basePath . "/" . $destinationRoot . "/$1.$2.webp [NC,T=image/webp,E=WEBPACCEPT:1,E=WEBPEXISTING:1,QSD]\n\n" .

          "  # Redirect to converter (under appropriate circumstances)\n" .
          "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
          "  RewriteCond %{QUERY_STRING} (^reconvert.*)|(^debug.*) [OR]\n" .
          "  RewriteCond %{DOCUMENT_ROOT}/" . $basePath . "/" . $destinationRoot . "/$1.$2.webp !-f\n" .
          "  RewriteCond %{QUERY_STRING} (.*)\n" .
          "  RewriteRule ^\/?(.*)\.(jpe?g|png)$ " . $scriptPath . "/webp-on-demand.php?base-path=" . $basePath . "&destination-root=" . $destinationRoot . "&source=$1.$2" . $options . "&%1 [NC,E=WEBPACCEPT:1,E=WEBPNEW:1]\n" .
          "</IfModule>\n\n" .

          "<IfModule mod_headers.c>\n" .
          "  # Apache appends \"REDIRECT_\" in front of the environment variables, but LiteSpeed does not\n" .
          "  # These next three lines are for Apache, in order to set environment variables without \"REDIRECT_\"\n" .
          "  SetEnvIf REDIRECT_WEBPACCEPT 1 WEBPACCEPT=1\n" .
          "  SetEnvIf REDIRECT_WEBPEXISTING 1 WEBPEXISTING=1\n" .
          "  SetEnvIf REDIRECT_WEBPNEW 1 WEBPNEW=1\n\n" .

          "  # Make CDN caching possible.\n" .
          "  Header append Vary Accept env=WEBPACCEPT\n\n" .

          "  # Add headers for debugging\n" .
          "  Header append X-WebP-On-Demand \"Routed to existing converted image\" env=WEBPEXISTING\n" .
          "  Header append X-WebP-On-Demand \"Routed to image converter\" env=WEBPNEW\n" .
          "</IfModule>\n\n" .
          "AddType image/webp .webp\n";
        }
        return $rules;
    }

    private static function doInsertHTAccessRules($rules) {
      if (!function_exists('get_home_path')) {
          require_once ABSPATH . 'wp-admin/includes/file.php';
      }
      $root_path = get_home_path();

      if (!file_exists($root_path . '.htaccess')) {
        return false;
      }

      $file_existing_permission = '';

      // Try to make .htaccess writable if its not
      if (file_exists($root_path . '.htaccess') && !is_writable($root_path . '.htaccess')) {
          // Store existing permissions, so we can revert later
          $file_existing_permission = octdec(substr(decoct(fileperms($root_path . '.htaccess')), -4));

          // Try to chmod.
          // It may fail, but we can ignore that. If it fails, insert_with_markers will also fail
          chmod($root_path . '.htaccess', 0550);
      }


      /* Add rules to .htaccess  */
      if (!function_exists('insert_with_markers')) {
          require_once ABSPATH . 'wp-admin/includes/misc.php';
      }
      if (!insert_with_markers($root_path . '.htaccess', 'WebP Express', $rules)) {
        return false;
      }
      else {
        /* Revert File Permission  */
        if (!empty($file_existing_permission)) {
            chmod($root_path . '.htaccess', $file_existing_permission);
        }
        return true;
      }

    }

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
    return $rel;
  }

}
