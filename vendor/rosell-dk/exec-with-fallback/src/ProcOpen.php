<?php

namespace ExecWithFallback;

/**
 * Emulate exec() with proc_open()
 *
 * @package    ExecWithFallback
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class ProcOpen
{

  /**
   * Emulate exec() with proc_open()
   *
   * @param string $command  The command to execute
   * @param string &$output (optional)
   * @param int &$result_code (optional)
   *
   * @return string | false   The last line of output or false in case of failure
   */
    public static function exec($command, &$output = null, &$result_code = null)
    {
        $descriptorspec = array(
            //0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            //2 => array("pipe", "w"),
            //2 => array("file", "/tmp/error-output.txt", "a")
        );

        $cwd = getcwd(); // or is "/tmp" better?
        $processHandle = proc_open($command, $descriptorspec, $pipes, $cwd);
        $result = "";
        if (is_resource($processHandle)) {
            // Got this solution here:
            // https://stackoverflow.com/questions/5673740/php-or-apache-exec-popen-system-and-proc-open-commands-do-not-execute-any-com
            //fclose($pipes[0]);
            $result = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            //fclose($pipes[2]);
            $result_code = proc_close($processHandle);

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
        } else {
            return false;
        }
    }
}
