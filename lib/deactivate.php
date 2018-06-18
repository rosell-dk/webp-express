<?php
include_once( plugin_dir_path( __FILE__ ) . 'helpers.php');

class WebPExpressDeactivate {

  public function deactivate() {
    WebPExpressHelpers::insertHTAccessRules("# deactivated");
  }
}

WebPExpressDeactivate::deactivate();
