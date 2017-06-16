<?php

class WebPExpressHelpers {

  public static function insert_htaccess_rules($rules) {
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
      if (!chmod($root_path . '.htaccess', 0777)) {
        set_transient( 'webp-express-hcaccess-not-writable', true, 5 );
        set_transient( 'webp-express-deactivate', true, 60 );
      }
    }
    /* Appending .htaccess  */
    if (file_exists($root_path . '.htaccess') && is_writable($root_path . '.htaccess')) {

      if (!function_exists('insert_with_markers')) {
        require_once ABSPATH . 'wp-admin/includes/misc.php';
      }
      if (!insert_with_markers($root_path . '.htaccess', 'WebP Express', $rules)) {
        set_transient( 'webp-express-failed-inserting-rules', true, 5 );
        set_transient( 'webp-express-deactivate', true, 60 );

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
