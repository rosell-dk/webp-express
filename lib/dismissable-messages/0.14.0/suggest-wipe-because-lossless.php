<?php
use \WebPExpress\DismissableMessages;
use \WebPExpress\State;
use \WebPExpress\TestRun;

$convertersSupportingEncodingAuto = ['cwebp', 'vips', 'imagick', 'imagemagick', 'gmagick', 'graphicsmagick'];

$workingConvertersIds = State::getState('workingConverterIds', []);
$workingAndActiveConverterIds = State::getState('workingAndActiveConverterIds', []);

$firstActiveAndWorkingConverterId = (isset($workingAndActiveConverterIds[0]) ? $workingAndActiveConverterIds[0] : '');

if (in_array($firstActiveAndWorkingConverterId, $convertersSupportingEncodingAuto)) {
    DismissableMessages::printDismissableMessage(
        'info',
        '<p>WebP Express 0.14 has new options for the conversions. Especially, it can now produce lossless webps, and ' .
            'it can automatically try both lossy and lossless and select the smallest. You can play around with the ' .
            'new options when your click "test" next to a converter.</p>' .
            '<p>Once satisfied, dont forget to ' .
            'wipe your existing converted files (there is a "Delete converted files" button for that here on this page).</p>',
        '0.14.0/suggest-wipe-because-lossless',
        'Got it!'
    );
} else {
    if ($firstActiveAndWorkingConverterId == 'gd') {
        foreach ($workingConvertersIds as $workingId) {
            if (in_array($workingId, $convertersSupportingEncodingAuto)) {
                DismissableMessages::printDismissableMessage(
                    'info',
                    '<p>WebP Express 0.14 has new options for the conversions. Especially, it can now produce lossless webps, and ' .
                        'it can automatically try both lossy and lossless and select the smallest. You can play around with the ' .
                        'new options when your click "test" next to a converter.</p>' .
                        '<p>Once satisfied, dont forget to wipe your existing converted files (there is a "Delete converted files" ' .
                        'button for that here on this page)</p>' .
                        '<p>Btw: The "gd" conversion method that you are using does not support lossless encoding ' .
                        '(in fact Gd only supports very few conversion options), but fortunately, you have at least one ' .
                        'other conversion method working, so you can simply start using that instead.</p>',
                    '0.14.0/suggest-wipe-because-lossless',
                    'Got it!'
                );
                break;
            }
        }
    }
}
