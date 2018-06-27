<?php

include_once( plugin_dir_path( __FILE__ ) . 'helpers.php');

class WebPExpressActivate {


  public function activate() {

    update_option( 'webp-express-message-pending', true, false );

    update_option( 'webp-express-just-activated', true, false );


    if ( strpos( strtolower($_SERVER['SERVER_SOFTWARE']), 'microsoft-iis') !== false ) {
      update_option( 'webp-express-microsoft-iis', true, false );
      update_option( 'webp-express-deactivate', true, false );
      return;
    }

    if (!( strpos( strtolower($_SERVER['SERVER_SOFTWARE']), 'apache') !== false )) {
      update_option( 'webp-express-not-apache', true, false );
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
    $ourUploadDir = $urlsAndPaths['filePaths']['uploadPath'];

    if ( ! file_exists( $ourUploadDir ) ) {
      wp_mkdir_p( $ourUploadDir );
    }
    if ( ! file_exists( $ourUploadDir ) ) {
      update_option( 'webp-express-failed-creating-upload-dir', true, false );
      update_option( 'webp-express-deactivate', true, false );
      return;
    }

    // Add rules to .htaccess
    $rules = WebPExpressHelpers::generateHTAccessRules();
    WebPExpressHelpers::insertHTAccessRules($rules);


  }

}

WebPExpressActivate::activate();
