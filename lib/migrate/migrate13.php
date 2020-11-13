<?php

namespace WebPExpress;


function webpexpress_migrate13_add_ffmpeg_message_if_relevant()
{
    $config = Config::loadConfigAndFix(false);  // false, because we do not need to test if quality detection is working
    $config = Config::updateConverterStatusWithFreshTest($config);  // Test all converters (especially we are excited to learn if the new ffmpeg converter is working)

    $workingConverterIds = ConvertersHelper::getWorkingAndActiveConverterIds($config);

    if (!in_array('ffmpeg', $workingConverterIds)) {
        // ffmpeg is not working on the host, so no need to announce ffmpeg
        return;
    }

    $betterConverterIds = ['cwebp', 'vips', 'imagemagick', 'graphicsmagick', 'imagick', 'gmagick', 'wpc'];
    $workingAndBetter = array_intersect($workingConverterIds, $betterConverterIds);

    if (count($workingAndBetter) > 0) {
        // the user already has a better conversion method working. No reason to disturb
        return;
    }

    if (in_array('gd', $workingConverterIds)) {
        DismissableGlobalMessages::addDismissableMessage('0.19.0/meet-ffmpeg-better-than-gd');
    } elseif (in_array('ewww', $workingConverterIds)) {
        DismissableGlobalMessages::addDismissableMessage('0.19.0/meet-ffmpeg-better-than-ewww');
    } else {
        DismissableGlobalMessages::addDismissableMessage('0.19.0/meet-ffmpeg-a-working-conversion-method');
    }

}

function webpexpress_migrate13() {
    Option::updateOption('webp-express-migration-version', '13');

    webpexpress_migrate13_add_ffmpeg_message_if_relevant();
}

webpexpress_migrate13();
