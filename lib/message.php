<?php

delete_option( 'webp-express-message-pending');

if ( get_option( 'webp-express-deactivate' ) ) {
  add_action( 'admin_notices', function() {
    printf(
      '<div class="%1$s"><p>%2$s</p></div>',
      esc_attr( 'notice notice-error is-dismissible' ),
      __( 'Plugin <b>deactivated</b>' )
    );
  });
}

add_action( 'admin_notices', function() {
  // Possible classes:
  // notice-warning, notice-error, notice-warning, notice-success, or notice-info
  // - add is-dismissible

  if ( get_option( 'webp-express-inserted-rules-ok' ) ) {
      delete_option( 'webp-express-inserted-rules-ok');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-success is-dismissible' ),
        esc_html( __( 'WebP Express updated .htaccess', 'webp-express' ) )
      );
  }


  if ( get_option( 'webp-express-just-activated' ) ) {
    delete_option( 'webp-express-just-activated');


    if ( get_option( 'webp-express-microsoft-iis' ) ) {
      delete_option( 'webp-express-microsoft-iis');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-error is-dismissible' ),
        esc_html( __( 'You are on Microsof IIS server. The plugin does not work on IIS', 'webp-express' ) )
      );
      return;
    }
    else if ( get_option( 'webp-express-not-apache' ) ) {
      delete_option( 'webp-express-not-apache');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-warning is-dismissible' ),
        esc_html( __( 'You are not on Apache server. WebP Express has only been tested on Apache - continue at own risk (but please tell me if it works!)', 'webp-express' ) )
      );
    }


    if ( get_option( 'webp-express-no-multisite' ) ) {
      delete_option( 'webp-express-no-multisite');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-error is-dismissible' ),
        esc_html( __( 'Sorry, WebP Express does not support multisite websites (yet). A donation might resolve the issue ;-)', 'webp-express' ) )
      );
      return;
    }
/*
    if (get_option( 'webp-express-php-too-old' ) ) {
      delete_option( 'webp-express-php-too-old');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-error is-dismissible' ),
        esc_html( sprintf(__( 'Sorry, WebP Express requires PHP >=5.5.0. You are on version %s', 'webp-express' ), phpversion() ) )
      );
      return;
    }

    if (get_option( 'webp-express-imagewebp-not-available' ) ) {
      delete_option( 'webp-express-imagewebp-not-available');
      $text = sprintf(__('Sorry, WebP Express requires that the %simagewebp%s function is available to PHP.'), '<a href="http://php.net/manual/en/function.imagewebp.php">', '</a>');

      if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
        $text .= sprintf(__(' Your PHP version is great enough (%s), however on PHP >= 7.0.0, PHP must be configured with the "--with-webp-dir=DIR" option in order for the imagewebp function to be available. Read more about that %shere%s.'), phpversion(), '<a href="http://il1.php.net/manual/en/image.installation.php">', '</a>');
      }
      else {
        $text .= sprintf(__(' Your PHP version is great enough (%s), however on PHP >= 5.5.0, but less than 7.0.0, PHP must be configured with the "--with-vpx-dir=DIR" option in order for the imagewebp function to be available. Read more about that %shere%s.'), phpversion(), '<a href="http://il1.php.net/manual/en/image.installation.php">', '</a>');
      }
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-error is-dismissible' ),
        $text
      );
      return;
    }*/

    if ( get_option( 'webp-express-failed-creating-upload-dir' ) ) {
      delete_option( 'webp-express-failed-creating-upload-dir');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-error is-dismissible' ),
        esc_html( __( 'WebP Express could not create a subfolder in your upload folder. Check your file permissions', 'webp-express' ) )
      );
      return;
    }

    if ( get_option( 'webp-express-hcaccess-not-writable' ) ) {
      delete_option( 'webp-express-hcaccess-not-writable');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-error is-dismissible' ),
        esc_html( __( 'WebP Express failed writing rules to .htaccess. The file is not writable and could not be made writable either. Probably this is due to a misconfiguration. Check the file permissions and ownership', 'webp-express' ) )
      );
      return;
    }

    if ( get_option( 'webp-express-failed-inserting-rules' ) ) {
      delete_option( 'webp-express-failed-inserting-rules');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-error is-dismissible' ),
        esc_html( __( 'WebP Express failed writing rules to .htaccess. The file is writable, but something went wrong writing to it anyway. More precicely, the Wordpress function "insert_with_markers" returned false. The .htaccess rules are needed for this plugin to function, and the plugin has therefore been deactivated.', 'webp-express' ) )
      );
      return;
    }

    printf(
      '<div class="%1$s"><p>%2$s</p></div>',
      esc_attr( 'notice notice-info is-dismissible' ),
      esc_html( __( 'WebP Express was installed successfully. A subfolder has been successfully created in your uploads folder for storing generated WebP images. Also, rules have been successfully inserted into your .htaccess file. These rules takes care of redirecting jpeg and png files to the generator or -- if a generated WebP image exists -- directly to that image. If you at some point change the upload directory or move Wordpress from subfolder to root or the other way, these rules will have to be updated. You do this by deactivating and reactivating the plugin.', 'webp-express' ) )
    );
  }
});



?>
