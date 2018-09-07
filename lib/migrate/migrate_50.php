<?php

namespace WebPExpress;

//echo 'migrate 05';
/*
$optionsToDelete = [
    'webp_express_max_quality',
    'webp_express_image_types_to_convert',
    'webp_express_failure_response',
    'webp_express_converters',
    'webp-express-inserted-rules-ok',
    'webp-express-configured',
    'webp-express-pending-messages',
    'webp-express-just-activated',
    'webp-express-message-pending',
    'webp-express-failed-inserting-rules',
    'webp-express-deactivate'
];
foreach ($optionsToDelete as $i => $optionName) {
    delete_option($optionName);
}
update_option('webp-express-version', '0.5', true);
*/
/*
class Migrate05
{

    public static function getWebPOnDemandOptions()
    {
        $converters_including_deactivated = json_decode(get_option('webp_express_converters', []), true);
        $converters = [];
        foreach ($converters_including_deactivated as $converter) {
            if (isset($converter['deactivated'])) continue;

            // Search for options containing "-2".
            // If they exists, we must duplicate the converter.
            $shouldDuplicate = false;
            if (isset($converter['options'])) {
                foreach ($converter['options'] as $converterOption => $converterValue) {
                    if (substr($converterOption, -2) == '-2') {
                        $shouldDuplicate = true;
                    }
                }
            };

            if ($shouldDuplicate) {
                // Duplicate converter
                $converter2 = $converter;
                foreach ($converter['options'] as $converterOption => $converterValue) {
                    if (substr($converterOption, -2) == '-2') {
                        unset ($converter['options'][$converterOption]);
                        unset ($converter2['options'][$converterOption]);
                        $one = substr($converterOption, 0, -2);
                        $converter2['options'][$one] = $converterValue;
                        //$converter[$converterOption] = null;
                    }
                }
                $converters[] = $converter;
                $converters[] = $converter2;
            } else {
                $converters[] = $converter;
            }
        }
        $options = [
            'max-quality' => get_option('webp_express_max_quality', '85'),
            'fail' => get_option('webp_express_failure_response', 'original'),
            'converters' => $converters
        ];
        return $options;
    }
}
*/
