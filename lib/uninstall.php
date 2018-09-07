<?php


$optionsToDelete = [
    'webp-express-messages-pending',
    'webp-express-action-pending',
    'webp-express-state',
    'webp-express-version',
    'webp-express-activation-error',
];
foreach ($optionsToDelete as $i => $optionName) {
    delete_option($optionName);
}
/*
TODO: delete config
@unlink(rtrim(WFWAF_LOG_PATH . '/') . '/.htaccess');
@rmdir(WFWAF_LOG_PATH);
*/
/*
webp_express_fail_action
webp_express_method
webp_express_quality
*/
// Should we also call unregister_setting ?
