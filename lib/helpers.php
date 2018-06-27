<?php

class WebPExpressHelpers
{

    public static function calculateUrlsAndPaths()
    {
        // Calculate URL's
        $upload_dir = wp_upload_dir();
        $uploadUrlAbs = $upload_dir['baseurl'] . '/' . 'webp-express';

        // Calculate url path to directory of image converter
        $pluginUrlAbs = plugins_url('', WEBPEXPRESS_PLUGIN);

        $converterUrlPath = parse_url($pluginUrlAbs)['path'];

        // Calculate upload url path
        $uploadUrlPath = parse_url($uploadUrlAbs)['path'];

        // Calculate Wordpress url path
        // If site for example is accessed example.com/blog/, then the url path is "blog"
        $wpUrlPath = trailingslashit(parse_url(site_url())['path']);    // ie "/blog/" or "/"

        $converterUrlPathRelativeToSiteUrl = WebPExpressHelpers::get_rel_dir(untrailingslashit($wpUrlPath), $converterUrlPath);
        $siteUrlPathRelativeToConverterPath = WebPExpressHelpers::get_rel_dir($converterUrlPath, untrailingslashit($wpUrlPath));


        // Calculate file dirs
        // --------------------

        $uploadDir = trailingslashit($upload_dir['basedir']) . 'webp-express';

        $uploadPathRelativeToWebExpressRoot = untrailingslashit(WebPExpressHelpers::get_rel_dir(WEBPEXPRESS_PLUGIN_DIR, $uploadDir));

        return [
            'urls' => [
                'webpExpressRoot' => untrailingslashit($converterUrlPath),
                'convert' => untrailingslashit($converterUrlPath) . 'convert.php',
                'destinationRoot' => untrailingslashit($uploadUrlPath),
                'wpRoot' => untrailingslashit($wpUrlPath),  // ie "/blog" or ""
                'converterUrlPathRelativeToSiteUrl' => $converterUrlPathRelativeToSiteUrl,
                'siteUrlPathRelativeToConverterPath' => $siteUrlPathRelativeToConverterPath
            ],
            'filePaths' => [
                'destinationRoot' => untrailingslashit($uploadDir),
                'webpExpressRoot' => untrailingslashit(WEBPEXPRESS_PLUGIN_DIR),
                'uploadPath' => $uploadDir,
                'uploadPathRelativeToWebExpressRoot' => $uploadPathRelativeToWebExpressRoot
            ]
        ];
    }

    public static function generateHTAccessRules()
    {
        $options = '';
        $options .= '&quality=' . get_option('webp_express_quality');
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

        $rules = "<IfModule mod_rewrite.c>\n" .
        "  RewriteEngine On\n" .
        "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
        "  RewriteCond %{QUERY_STRING} (^reconvert.*)|(^debug.*) [OR]\n" .
        "  RewriteCond %{DOCUMENT_ROOT}" . $urls['destinationRoot'] . "/$1.$2.webp !-f\n" .
        "  RewriteCond %{QUERY_STRING} (.*)\n" .
        "  RewriteRule ^(.*)\.(jpe?g|png)$ " . $urls['converterUrlPathRelativeToSiteUrl'] . "convert.php?source=" . $urls['siteUrlPathRelativeToConverterPath'] . "$1.$2&destination-root=" . $filePaths['uploadPathRelativeToWebExpressRoot'] . $options . "&%1 [NC,T=image/webp,E=accept:1]\n" .
        "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
        "  RewriteCond %{QUERY_STRING} !((^reconvert.*)|(^debug.*))\n" .
        "  RewriteCond %{DOCUMENT_ROOT}" . $urls['destinationRoot'] . "/$1.$2.webp -f\n" .
        "  RewriteRule ^(.*)\.(jpe?g|png)$ " . $urls['destinationRoot'] . "/$1.$2.webp [NC,T=image/webp,E=accept:1,QSD]\n" .
        "</IfModule>\n" .
        "<IfModule mod_headers.c>\n" .
        "  Header append Vary Accept env=REDIRECT_accept\n" .
        "</IfModule>\n" .
        "AddType image/webp .webp\n";

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
     */
  public static function get_rel_dir($from_dir, $to_dir) {
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
