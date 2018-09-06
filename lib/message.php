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

    $pendingMessagesJSON = get_option('webp-express-pending-messages', '[]');
    $pendingMessages = json_decode($pendingMessagesJSON, true);
    foreach ($pendingMessages as $message) {
        $msg = __( $message['message'], 'webp-express');
        //$msg = esc_html($msg);
        printf(
          '<div class="%1$s"><p>%2$s</p></div>',
          esc_attr('notice notice-' . $message['level'] . ' is-dismissible'),
          $msg
        );
    }
    update_option('webp-express-pending-messages', json_encode([], JSON_UNESCAPED_SLASHES), false);


  // Possible classes:
  // notice-warning, notice-error, notice-warning, notice-success, or notice-info
  // - add is-dismissible


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
    } else if ( get_option( 'webp-express-not-apache-nor-litespeed' ) ) {
      delete_option( 'webp-express-not-apache-nor-litespeed');
      printf(
        '<div class="%1$s"><p>%2$s</p></div>',
        esc_attr( 'notice notice-warning is-dismissible' ),
        esc_html( __( 'You are not on Apache server, nor on LiteSpeed. WebP Express has only been tested on Apache and LiteSpeed - continue at own risk (but please tell me if it works!). Your server is: ' . $_SERVER['SERVER_SOFTWARE'], 'webp-express' ) )
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

/*
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
*/
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

    if (empty(get_option('webp-express-configured'))) {
        printf(
          '<div class="%1$s"><p>%2$s</p></div>',
          esc_attr( 'notice notice-info is-dismissible' ),
          'WebP Express was installed successfully. To start using it, you must <a href="options-general.php?page=webp_express_settings_page">configure it here</a>.'
        );
    } else {
        printf(
          '<div class="%1$s"><p>%2$s</p></div>',
          esc_attr( 'notice notice-info is-dismissible' ),
          'WebP Express reactivated successfully.<br>The image redirections should be in effect again (you should see a "WebP Express updated .htaccess" message above this...)<br><br>Just a quick reminder: If you at some point change the upload directory or move Wordpress, you will have to regenerate the .htaccess.<br>You do that by changing the configuration <a href="options-general.php?page=webp_express_settings_page">(here)</a>'
        );
    }
  }
});



?>
