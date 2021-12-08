<?php
namespace ExecWithFallback;

/**
 * Execute command with exec(), open_proc() or whatever available
 *
 * @package    ExecWithFallback
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class ExecWithFallbackNoMercy
{

  /**
   * Execute. - A substitute for exec()
   *
   * Same signature and results as exec(): https://www.php.net/manual/en/function.exec.php
   *
   * This is our hardcore version of our exec(). It does not merely throw an Exception, if
   * no methods are available. It calls exec().
   * This ensures exactly same behavior as normal exec() - the same error is thrown.
   * You might want that. But do you really?
   * DANGER: On some systems, calling a disabled exec() results in a fatal (non-catchable) error.
   *
   * @param string $command  The command to execute
   * @param string &$output (optional)
   * @param int &$result_code (optional)
   *
   * @return string | false   The last line of output or false in case of failure
   * @throws \Exception|\Error  If no methods are available. Note: On some systems, it is FATAL!
   */
  public static function exec($command, &$output = null, &$result_code = null)
  {
      foreach (self::$methods as $method) {
          if (self::functionEnabled($method)) {
              if (func_num_args() >= 3) {
                  if ($method == 'shell_exec') {
                      continue;
                  }
                  $result = self::runExec($method, $command, $output, $result_code);
              } else {
                  $result = self::runExec($method, $command, $output);
              }
              if ($result !== false) {
                  return $result;
              }
          }
      }
      if (isset($result) && ($result === false)) {
          return false;
      }
      // MIGHT THROW FATAL!
      return exec($command, $output, $result_code);
  }


}
