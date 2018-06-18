<?php

class WebPExpressHelpers
{

    public static function testConverters($convertersToTest)
    {
        include_once __DIR__ . '/../vendor/webp-convert/autoload.php';

        $upload_dir = wp_upload_dir();
        $uploadDir = trailingslashit($upload_dir['basedir']) . 'webp-express';
        $converterDir = WEBPEXPRESS_PLUGIN_DIR;
        $source = trailingslashit($converterDir) . 'test/test.jpg';
        $destination = $uploadDir . '/test.jpg.webp';

        $result = [];
        $numOperationalConverters = 0;
        foreach ($convertersToTest as $converter) {
            $msg = 'Not operational';
            try {
                $options = [
                    'converters' => [$converter]
                ];
                $success = WebPConvert\WebPConvert::convert($source, $destination, $options);
            } catch (\Exception $e) {
                $success = false;
                $msg = $e->getMessage();
            }
            if ($success) {
                $result[] = [
                    'converter' => $converter,
                    'success' => true
                ];
                $numOperationalConverters++;
            } else {
                $result[] = [
                    'converter' => $converter,
                    'success' => false,
                    'message' => $msg
                ];
            }
        }
        return [
            'numOperationalConverters' => $numOperationalConverters,
            'results' => $result
        ];
    }

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
                'uploadPathRelativeToWebExpressRoot' => $uploadPathRelativeToWebExpressRoot
            ]
        ];
    }

    public static function generateHTAccessRules()
    {
        // TODO: Use calculateUrlsAndPaths() call

        // Calculate URL's
        $upload_dir = wp_upload_dir();
        $uploadUrlAbs = $upload_dir['baseurl'] . '/' . 'webp-express';
        //echo 'upload dir: ' . $uploadUrlAbs . '<br>';

        // Calculate url path to directory of image converter
        $pluginUrlAbs = plugins_url('', WEBPEXPRESS_PLUGIN);

        //echo 'plugin url: ' . $pluginUrlAbs . '<br>';

        $converterUrlPath = parse_url($pluginUrlAbs)['path'];
        //echo 'converter url path: ' . $converterUrlPath . '<br>';

        // Calculate upload url path
        $uploadUrlPath = parse_url($uploadUrlAbs)['path'];
        //echo 'our upload url path: ' . $uploadUrlPath . '<br>';

        // Calculate Wordpress url path
        // If site for example is accessed example.com/blog/, then the url path is "blog"
        $wpUrlPath = trailingslashit(parse_url(site_url())['path']);    // ie "/blog/" or "/"
        //echo 'wp url path: ' . $wpUrlPath . '<br>';

        $converterUrlPathRelativeToSiteUrl = WebPExpressHelpers::get_rel_dir(untrailingslashit($wpUrlPath), $converterUrlPath);
        //echo 'converter url path (relative to site url): ' . $converterUrlPathRelativeToSiteUrl . '<br>';
        $siteUrlPathRelativeToConverterPath = WebPExpressHelpers::get_rel_dir($converterUrlPath, untrailingslashit($wpUrlPath));

        // Calculate file dirs

        // Calculate relative file path between converter dir and upload dir
        $uploadDir = trailingslashit($upload_dir['basedir']) . 'webp-express';
        //echo 'upload dir: ' . $uploadDir . '<br>';

        $converterDir = WEBPEXPRESS_PLUGIN_DIR;
        //echo 'converter dir: ' . $converterDir . '<br>';

        $destinationRoot = untrailingslashit(WebPExpressHelpers::get_rel_dir($converterDir, $uploadDir));
        //echo 'destination root:' . $destinationRoot . '<br>';


        /*
        $rules = "<IfModule mod_rewrite.c>\n" .
        "  RewriteEngine On\n" .
        "  RewriteBase /\n" .
        "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
        "  RewriteCond %{DOCUMENT_ROOT}" . trailingslashit($uploadUrlPath) . "$1.$2.webp !-f\n" .
        "  RewriteRule ^(.*)\.(jpe?g|png)$ " . $converterUrlPath . "?source=$1.$2&quality=80&root-folder=" . $wp_root_folder . "&destination-root=" . $destinationRoot . "&preferred-converters=imagick,cwebp,gd&serve-image=yes [T=image/webp,E=accept:1]\n" .
        "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
        "  RewriteCond %{DOCUMENT_ROOT}" . trailingslashit($uploadUrlPath) . "$1.$2.webp -f\n" .
        "  RewriteRule ^(.*)\.(jpe?g|png)$ " . trailingslashit($uploadUrlPath) . "$1.$2.webp [T=image/webp,E=accept:1]\n" .
        "</IfModule>\n\n" .
        "<IfModule mod_headers.c>\n" .
        "  Header append Vary Accept env=REDIRECT_accept\n" .
        "</IfModule>\n\n" .
        "AddType image/webp .webp\n";
        #  RewriteEngine On
        #  RewriteBase /
        #  RewriteCond %{HTTP_ACCEPT} image/webp
        #  RewriteCond %{DOCUMENT_ROOT}/webp-express-test/wordpress/wp-content/uploads/webp-express/$1.$2.webp !-f
        #  RewriteRule ^(.*)\.(jpe?g|png)$ /webp-express-test/wordpress/wp-content/plugins/webp-express/webp-convert/webp-convert.php?source=$1.$2&quality=80&root-folder=webp-express-test/wordpress&destination-root=wp-content/uploads/webp-express/&preferred-converters=imagick,cwebp,gd&serve-image=yes [T=image/webp,E=accept:1]
        #  RewriteCond %{HTTP_ACCEPT} image/webp
        #  RewriteCond %{DOCUMENT_ROOT}/webp-express-test/wordpress/wp-content/uploads/webp-express/$1.$2.webp -f
        #  RewriteRule ^(.*)\.(jpe?g|png)$ /webp-express-test/wordpress/wp-content/uploads/webp-express/$1.$2.webp [T=image/webp,E=accept:1]

        */

        /*
        RewriteEngine On
        RewriteCond %{HTTP_ACCEPT} image/webp
        RewriteCond %{QUERY_STRING} (^reconvert.*)|(^debug.*) [OR]
        RewriteCond %{DOCUMENT_ROOT}/webp-cache/$1.$2.webp !-f
        RewriteCond %{QUERY_STRING} (.*)
        RewriteRule ^(.*)\.(jpe?g|png)$ wp-content/plugins/webp-express/convert.php?source=$1.$2&destination-root=../../webp-cache&quality=80&fail=original&critical-fail=report&%1 [NC,T=image/webp,E=accept:1]
        #RewriteCond %{HTTP_ACCEPT} image/webp
        #RewriteCond %{QUERY_STRING} !((^reconvert.*)|(^debug.*))
        #RewriteCond %{DOCUMENT_ROOT}/webp-cache/$1.$2.webp -f
        #RewriteRule ^(.*)\.(jpe?g|png)$ /webp-cache/$1.$2.webp [NC,T=image/webp,E=accept:1,QSD]
        */

        $options = '';
        $options .= '&quality=' . get_option('webp_express_quality');
        $options .= '&method=' . get_option('webp_express_method');
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



        $rules = "<IfModule mod_rewrite.c>\n" .
        "  RewriteEngine On\n" .
        "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
        "  RewriteCond %{QUERY_STRING} (^reconvert.*)|(^debug.*) [OR]\n" .
        "  RewriteCond %{DOCUMENT_ROOT}" . trailingslashit($uploadUrlPath) . "$1.$2.webp !-f\n" .
        "  RewriteCond %{QUERY_STRING} (.*)\n" .
        "  RewriteRule ^(.*)\.(jpe?g|png)$ " . $converterUrlPathRelativeToSiteUrl . "convert.php?source=" . $siteUrlPathRelativeToConverterPath . "$1.$2&destination-root=" . $destinationRoot . $options . "&%1 [NC,T=image/webp,E=accept:1]\n" .
        "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
        "  RewriteCond %{QUERY_STRING} !((^reconvert.*)|(^debug.*))\n" .
        "  RewriteCond %{DOCUMENT_ROOT}" . trailingslashit($uploadUrlPath) . "$1.$2.webp -f\n" .
        "  RewriteRule ^(.*)\.(jpe?g|png)$ " . trailingslashit($uploadUrlPath) . "$1.$2.webp [NC,T=image/webp,E=accept:1,QSD]\n" .
        "</IfModule>\n" .
        "<IfModule mod_headers.c>\n" .
        "  Header append Vary Accept env=REDIRECT_accept\n" .
        "</IfModule>\n" .
        "AddType image/webp .webp\n";

        //echo '<pre>' . $rules . '</pre>';
        return $rules;
    }

    public static function insertHTAccessRules($rules)
    {
        //update_option('webp-express-htaccess-not-writable', true, false);
        //update_option('webp-express-deactivate', true, false);
        //return;
    //    esc_html_e( 'Insertion failed');
    //    die();
    // taken from the "job_board_rewrite" example at:
    // https://hotexamples.com/examples/-/-/insert_with_markers/php-insert_with_markers-function-examples.html

        if (!function_exists('get_home_path')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $root_path = get_home_path();
        $file_existing_permission = '';

        // Try to make .htaccess writable if its not
        if (file_exists($root_path . '.htaccess') && !is_writable($root_path . '.htaccess')) {
            $file_existing_permission = substr(decoct(fileperms($root_path . '.htaccess')), -4);
            if (!chmod($root_path . '.htaccess', 0770)) {
                update_option('webp-express-htaccess-not-writable', true, false);
                update_option('webp-express-deactivate', true, false);
            }
        }
        /* Appending .htaccess  */
        if (file_exists($root_path . '.htaccess') && is_writable($root_path . '.htaccess')) {
            if (!function_exists('insert_with_markers')) {
                require_once ABSPATH . 'wp-admin/includes/misc.php';
            }
            if (!insert_with_markers($root_path . '.htaccess', 'WebP Express', $rules)) {
                update_option('webp-express-failed-inserting-rules', true, false);
                update_option('webp-express-deactivate', true, false);
            }
            else {
                //set_transient('webp-express-inserted-rules-ok', true, 60);
                update_option( 'webp-express-message-pending', true, false );
                update_option('webp-express-inserted-rules-ok', true, false);
            }

            /* Revert File Permission  */
            if (!empty($file_existing_permission)) {
                chmod($root_path . '.htaccess', $file_existing_permission);
            }
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
