<?php

namespace ExecWithFallback;

/**
 * Emulate exec() with passthru()
 *
 * @package    ExecWithFallback
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class Passthru
{

  /**
   * Emulate exec() with passthru()
   *
   * @param string $command  The command to execute
   * @param string &$output (optional)
   * @param int &$result_code (optional)
   *
   * @return string | false   The last line of output or false in case of failure
   */
    public static function exec($command, &$output = null, &$result_code = null)
    {
        ob_start();
        // Note: We use try/catch in order to close output buffering in case it throws
        try {
            passthru($command, $result_code);
        } catch (\Exception $e) {
            ob_get_clean();
            passthru($command, $result_code);
        } catch (\Throwable $e) {
            ob_get_clean();
            passthru($command, $result_code);
        }
        $result = ob_get_clean();

        // split new lines. Also remove trailing space, as exec() does
        $theOutput = preg_split('/\s*\r\n|\s*\n\r|\s*\n|\s*\r/', $result);

        // remove the last element if it is blank
        if ((count($theOutput) > 0) && ($theOutput[count($theOutput) -1] == '')) {
            array_pop($theOutput);
        }

        if (count($theOutput) == 0) {
            return '';
        }
        if (gettype($output) == 'array') {
            foreach ($theOutput as $line) {
                $output[] = $line;
            }
        } else {
            $output = $theOutput;
        }
        return $theOutput[count($theOutput) -1];
    }
}
