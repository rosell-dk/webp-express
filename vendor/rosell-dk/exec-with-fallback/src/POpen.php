<?php

namespace ExecWithFallback;

/**
 * Emulate exec() with system()
 *
 * @package    ExecWithFallback
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class POpen
{

  /**
   * Emulate exec() with system()
   *
   * @param string $command  The command to execute
   * @param string &$output (optional)
   * @param int &$result_code (optional)
   *
   * @return string | false   The last line of output or false in case of failure
   */
    public static function exec($command, &$output = null, &$result_code = null)
    {

        $handle = @popen($command, "r");
        if ($handle === false) {
            return false;
        }

        $result = '';
        while (!@feof($handle)) {
            $result .= fread($handle, 1024);
        }

        //Note: Unix Only:
        // pclose() is internally implemented using the waitpid(3) system call.
        // To obtain the real exit status code the pcntl_wexitstatus() function should be used.
        $result_code = pclose($handle);

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
