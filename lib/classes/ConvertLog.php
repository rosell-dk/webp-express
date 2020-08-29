<?php

namespace WebPExpress;

use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Paths;

class ConvertLog
{
    public static function processAjaxViewLog()
    {
        if (!check_ajax_referer('webpexpress-ajax-view-log-nonce', 'nonce', false)) {
            wp_send_json_error('The security nonce has expired. You need to reload the settings page (press F5) and try again)');
            wp_die();
        }

        // We need to be absolute certain that this feature cannot be misused.
        // - so disabling until I get the time...

        $msg = 'This feature is on the road map...';
        echo json_encode($msg, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        /*
        $source = sanitize_text_field($_POST['source']);
        $logFile = ConvertHelperIndependent::getLogFilename($source, Paths::getLogDirAbs());
        $msg = 'Log file: <i>' . $logFile . '</i><br><br><hr>';

        if (!file_exists($logFile)) {
            $msg .= '<b>No log file found on that location</b>';

        } else {
            $log = file_get_contents($logFile);
            if ($log === false) {
                $msg .= '<b>Could not read log file</b>';
            } else {
                $msg .= nl2br($log);
            }

        }

        //$log = $source;
        //file_get_contents

        echo json_encode($msg, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        */
        wp_die();
    }

}
