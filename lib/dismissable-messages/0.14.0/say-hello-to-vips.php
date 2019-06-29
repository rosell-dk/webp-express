<?php
use \WebPExpress\DismissableMessages;
use \WebPExpress\State;
use \WebPExpress\TestRun;

/*
$testResult = TestRun::getConverterStatus();
if ($testResult !== false) {
    $workingConvertersIds = $testResult['workingConverters'];
} else {
    $workingConvertersIds = [];
}
*/

$workingConvertersIds = State::getState('workingConverterIds', []);

if (in_array('vips', $workingConvertersIds)) {
    if (in_array('cwebp', $workingConvertersIds)) {
        DismissableMessages::printDismissableMessage(
            'info',
            '<p>I have some good news and... more good news! WebP Express now supports Vips and Vips is working on your server. ' .
                'Vips is one of the best method for converting WebPs, on par with cwebp, which you also have working. ' .
                'You may want to use Vips instead of cwebp. Your choice.</p>',
            '0.14.0/say-hello-to-vips',
            'Got it!'
        );
    } else {
        DismissableMessages::printDismissableMessage(
            'info',
            '<p>I have some good news and... more good news! WebP Express now supports Vips and Vips is working on your server. ' .
                'Vips is one of the best method for converting WebPs and has therefore been inserted at the top of the list.' .
                '</p>',
            '0.14.0/say-hello-to-vips',
            'Got it!'
        );
    }
} else {
    // show message?
}
