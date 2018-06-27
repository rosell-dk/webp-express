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

    if (get_option( 'webp-express-php-too-old' ) ) {
      delete_option( 'webp-express-php-too-old');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-warning is-dismissible' ),
        esc_html( sprintf(__( 'You are on a very old version of PHP (%s). WebP Express may not work as intended.', 'webp-express' ), phpversion() ) )
      );
      return;
    }

    if ( get_option( 'webp-express-failed-creating-upload-dir' ) ) {
      delete_option( 'webp-express-failed-creating-upload-dir');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-error is-dismissible' ),
        //esc_html( __( 'WebP Express could not create a subfolder in your upload folder. Check your file permissions', 'webp-express' ) )
        '<b>WebP Express could not create a subfolder in your upload folder</b>. Check your file permissions. <a target="_blank" href="https://github.com/rosell-dk/webp-express/wiki/Error-messages-and-warnings#webp-express-could-not-create-a-subfolder-in-your-upload-folder">Click here</a> for more information.'
      );
      return;
    }

/*
    if ( get_option( 'webp-express-htaccess-not-writable' ) ) {
      delete_option( 'webp-express-htaccess-not-writable');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-error is-dismissible' ),
        '<b>.htaccess is not writable</b>. The plugin has been disabled. To fix this, make .htaccess writable and try activating the plugin again. <a target="_blank" href="https://github.com/rosell-dk/webp-express/wiki/Error-messages-and-warnings#htaccess-is-not-writable">Click here</a> for more information.'
        //esc_html( __( '.htaccess is not writable. As WebP Express needs to write its redirection rules to .htaccess in order to function, the plugin has been disabled. To fix this, make .htaccess writable and try activating the plugin again. The file is located in the root of your wordpress installation.', 'webp-express' ) )
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
    }*/

    if ( get_option( 'webp-express-failed-inserting-rules' ) ) {
      delete_option( 'webp-express-failed-inserting-rules');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-error is-dismissible' ),
        '<b>.htaccess is not writable</b>. To fix this, make .htaccess writable and try activating the plugin again. <a target="_blank" href="https://github.com/rosell-dk/webp-express/wiki/Error-messages-and-warnings#htaccess-is-not-writable">Click here</a> for more information.'
      );
      return;
    }

    printf(
      '<div class="%1$s"><p>%2$s</p></div>',
      esc_attr( 'notice notice-info is-dismissible' ),
      'WebP Express was installed successfully. <a href="http://playground/webp-express-test/wordpress/wp-admin/options-general.php?page=webp_express_settings_page">Configure it here</a>.'
    );

    printf(
      '<div class="%1$s"><p>%2$s</p></div>',
      esc_attr( 'notice notice-info is-dismissible' ),
      esc_html( __( 'If you at some point change the upload directory or move Wordpress, you will have to disable and reenable WebPExpress', 'webp-express' ) )
    );
  }
});



?>
