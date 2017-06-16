<?php
include( plugin_dir_path( __FILE__ ) . 'helpers.php');

class WebPExpressDeactivate {

  public function deactivate() {
    WebPExpressHelpers::insert_htaccess_rules("# deactivated");
  }
}

WebPExpressDeactivate::deactivate();

