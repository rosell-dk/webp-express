<?php

namespace WebPExpress;

$elementorActivated = in_array('elementor/elementor.php', get_option('active_plugins', []));
$showMessage = false;
if ($elementorActivated) {
    try {
        // The following is wrapped in a try statement because it depends on Elementor classes which might be subject to change
        if (\Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_css_loading' ) === false) {
            $showMessage = true;
        }
    } catch (\Exception $e) {
        // Well, just bad luck.
    }
}

if ($showMessage) {
    DismissableMessages::printDismissableMessage(
        'info',
        '<p>' .
            'You see this message because you using Elementor, you rely solely on Alter HTML for webp, and Elementor is currently set up to use external css. ' .
            'You might want to reconfigure Elementor so it inlines the CSS. This will allow Alter HTML to replace the image urls of backgrounds. ' .
            'To reconfigure, go to <i>Elementor > Settings > Experiments</i> and activate "Improved CSS Loading". ' .
            'Note: This requires that Alter HTML is configured to "Replace image URLs". ' .
            'For more information, <a target="_blank" href="https://wordpress.org/support/topic/background-images-not-working-as-webp-elementor/#post-15060686">' .
            'head over here</a>' .
        '</p>',
        '0.23.0/elementor',
        'Got it!'
    );
} else {
    DismissableMessages::dismissMessage('0.23.0/elementor');
}
