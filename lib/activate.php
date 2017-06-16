<?php

include( plugin_dir_path( __FILE__ ) . 'helpers.php');

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
      update_option( 'webp-express-deactivate', true, false );
      return;
    }

    if (!function_exists(imagewebp)) {
      update_option( 'webp-express-imagewebp-not-available', true, false );
      update_option( 'webp-express-deactivate', true, false );
      return;
    }


    // Create upload dir
    $upload_dir = wp_upload_dir();
    $our_upload_dir = $upload_dir['basedir'] . '/' . 'webp-express';
    if ( ! file_exists( $our_upload_dir ) ) {
      wp_mkdir_p( $our_upload_dir );
    }
    if ( ! file_exists( $our_upload_dir ) ) {
      update_option( 'webp-express-failed-creating-upload-dir', true, false );
      update_option( 'webp-express-deactivate', true, false );
      return;
    }

    // The rest in this function is all about creating the htaccess rules

    // Calculate destination folder
    $our_upload_url = $upload_dir['baseurl'] . '/' . 'webp-express';
    $plugin_dir = untrailingslashit(plugin_dir_path( WEBPEXPRESS_PLUGIN ));
    $destination_folder = WebPExpressHelpers::get_rel_dir($plugin_dir, $our_upload_dir);

    // Calculate url path to image converter
    $converter_url = plugins_url('webp-convert.php', WEBPEXPRESS_PLUGIN);
    $converter_url_path = parse_url($converter_url)['path'];

    // Calculate upload url path
    $our_upload_url_path = parse_url($our_upload_url)['path'];

    // Calculate Wordpress url path
    // If site for example is accessed example.com/blog/, then the url path is "blog"
    $wp_url_path = trailingslashit(parse_url(site_url())['path']);    // ie "/blog/" or "/"

    // Calculate relative path between wordpress "ABSPATH" and Document Root
    // webp-convert.php needs this, because it has no direct access to ABSPATH    
    $wp_abs_rel = WebPExpressHelpers::get_rel_dir($_SERVER['DOCUMENT_ROOT'], untrailingslashit(ABSPATH)); // ie "subdir/" or ""

    
	  $rules = "<IfModule mod_rewrite.c>\n" .
      "  RewriteEngine On\n" .
      "  RewriteBase /\n" .
      "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
      "  RewriteCond %{DOCUMENT_ROOT}" . trailingslashit($our_upload_url_path) . "$1.$2.webp !-f\n" .
      "  RewriteRule ^(.*)\.(jpe?g|png)$ " . $converter_url_path . "?file=$1.$2&quality=80&absrel=" . $wp_abs_rel . "&destination-folder=" . $destination_folder . " [T=image/webp,E=accept:1]\n" .
      "  RewriteCond %{HTTP_ACCEPT} image/webp\n" .
      "  RewriteCond %{DOCUMENT_ROOT}" . trailingslashit($our_upload_url_path) . "$1.$2.webp -f\n" .
      "  RewriteRule ^(.*)\.(jpe?g|png)$ " . trailingslashit($our_upload_url_path) . "$1.$2.webp [T=image/webp,E=accept:1]\n" .
      "</IfModule>\n\n" .
      "<IfModule mod_headers.c>\n" .
      "  Header append Vary Accept env=REDIRECT_accept\n" .
      "</IfModule>\n\n" .
      "AddType image/webp .webp\n";


    WebPExpressHelpers::insert_htaccess_rules($rules);


  }

}

WebPExpressActivate::activate();


