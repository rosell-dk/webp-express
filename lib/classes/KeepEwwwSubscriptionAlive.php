<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\State;
use \WebPConvert\Converters\Ewww;

/**
 *
 */

class KeepEwwwSubscriptionAlive
{
    public static function keepAlive($config = null) {
        include_once __DIR__ . '/../../vendor/autoload.php';

        if (is_null($config)) {
            $config = Config::loadConfigAndFix(false);  // false, because we do not need to test if quality detection is working
        }

        $ewww = Config::getConverterByName($config, 'ewww');
        if (!isset($ewww['options']['key'])) {
            return;
        }
        if (!$ewww['working']) {
            return;
        }

        $ewwwConvertResult = Ewww::keepSubscriptionAlive(__DIR__ . '/../../test/very-small.jpg', $ewww['options']['key']);
        if ($ewwwConvertResult === true) {
            Messenger::addMessage(
                'info',
                'Successfully optimized regular jpg with <i>ewww</i> converter in order to keep the subscription alive'
            );
            State::setState('last-ewww-optimize', time());
        } else {
            Messenger::addMessage(
                'warning',
                'Failed optimizing regular jpg with <i>ewww</i> converter in order to keep the subscription alive'
            );
        }
    }

    public static function keepAliveIfItIsTime($config = null) {

        $timeSinseLastSuccesfullOptimize = time() - State::getState('last-ewww-optimize', 0);
        if ($timeSinseLastSuccesfullOptimize > 3 * 30 * 24 * 60 * 60) {

            $timeSinseLastOptimizeAttempt = time() - State::getState('last-ewww-optimize-attempt', 0);
            if ($timeSinseLastOptimizeAttempt > 14 * 24 * 60 * 60) {
                State::setState('last-ewww-optimize-attempt', time());
                self::keepAlive($config);
            }
        }

    }

}
