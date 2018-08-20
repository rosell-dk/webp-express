<?php

include_once( plugin_dir_path( __FILE__ ) . 'helpers.php');

class WebPExpressActivate {


  public static function activate() {

    update_option( 'webp-express-message-pending', true, false );

    update_option( 'webp-express-just-activated', true, false );


    $server = strtolower($_SERVER['SERVER_SOFTWARE']);

    $server_is_microsoft_iis = ( strpos( $server, 'microsoft-iis') !== false );
    if ($server_is_microsoft_iis) {
      update_option( 'webp-express-microsoft-iis', true, false );
      update_option( 'webp-express-deactivate', true, false );
      return;
    }


    $server_is_litespeed = ( strpos( $server, 'litespeed') !== false );
    $server_is_apache = ( strpos( $server, 'apache') !== false );

    if ($server_is_litespeed || $server_is_apache) {
        // all is well.
    } else {
        update_option( 'webp-express-not-apache-nor-litespeed', true, false );
    }


    if ( is_multisite() ) {
      update_option( 'webp-express-no-multisite', true, false );
      update_option( 'webp-express-deactivate', true, false );
      return;
    }

    if (!version_compare(PHP_VERSION, '5.5.0', '>=')) {
      update_option( 'webp-express-php-too-old', true, false );
      //update_option( 'webp-express-deactivate', true, false );
      return;
    }


    // Create upload dir
    $urlsAndPaths = WebPExpressHelpers::calculateUrlsAndPaths();
    $ourUploadDir = $urlsAndPaths['filePaths']['destinationRoot'];

    if ( ! file_exists( $ourUploadDir ) ) {
      wp_mkdir_p( $ourUploadDir );
    }
    if ( ! file_exists( $ourUploadDir ) ) {
      update_option( 'webp-express-failed-creating-upload-dir', true, false );
      update_option( 'webp-express-deactivate', true, false );
      return;
    }

    // Add rules to .htaccess

    // Test if WebP Express has been configured ('webp_express_converters' will be updated each time settings are saved - the other settings are only saved when changed)
    if (empty(get_option('webp_express_converters'))) {
        // WebP Express has not been configured yet.

        // Write to .htaccess, in order to determine if there is a permission problem or not.
        if (WebPExpressHelpers::doInsertHTAccessRules('#  WebP Express has not been configured yet, so here are no rules yet.')) {

        } else {
            update_option('webp-express-failed-inserting-rules', true, false);
        }
    } else {

        // The plugin has been reactivated.
        // We must regenerate the .htaccess rules.
        $rules = WebPExpressHelpers::generateHTAccessRules();
        WebPExpressHelpers::insertHTAccessRules($rules);

    }


  }

}

WebPExpressActivate::activate();
