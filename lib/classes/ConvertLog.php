<?php

namespace WebPExpress;

use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Paths;

class ConvertLog
{

    public static function processAjaxViewLog()
    {
        $source = $_POST['source'];

        $logFile = ConvertHelperIndependent::getLogFilename($source, Paths::getWebPExpressContentDirAbs() . '/log');
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
        wp_die();
    }

}
