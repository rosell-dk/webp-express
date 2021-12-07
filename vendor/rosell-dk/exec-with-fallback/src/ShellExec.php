<?php

namespace ExecWithFallback;

/**
 * Emulate exec() with system()
 *
 * @package    ExecWithFallback
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class ShellExec
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
      //echo "\NSHELL:" . $command . ':' . func_num_args() . "\n";

        $resultCodeSupplied = (func_num_args() >= 3);
        if ($resultCodeSupplied) {
            return false;
        }

        $result = shell_exec($command);

        // result:
        // - A string containing the output from the executed command,
        // - false if the pipe cannot be established
        // - or null if an error occurs or the command produces no output.

        if ($result === false) {
            return false;
        }
        if (is_null($result)) {
            // hm, "null if an error occurs or the command produces no output."
            // What were they thinking?
            // And yes, it does return null, when no output, which is confirmed in the test "echo hi 1>/dev/null"
            // What should we do? Throw or accept?
            // Perhaps shell_exec throws in newer versions of PHP instead of returning null.
            // We are counting on it until proved wrong.
            return '';
        }

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
