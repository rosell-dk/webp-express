<?php
namespace ExecWithFallback;

/**
 * Execute command with exec(), open_proc() or whatever available
 *
 * @package    ExecWithFallback
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class ExecWithFallback
{

    protected static $methods = ['exec', 'passthru', 'popen', 'proc_open', 'shell_exec'];

    /**
     * Check if any of the methods are available on the system.
     *
     * @param boolean $needResultCode  Whether the code using this library is going to supply $result_code to the exec
     *         call. This matters because shell_exec is only available when not.
     */
    public static function anyAvailable($needResultCode = true)
    {
        return Availability::anyAvailable($needResultCode);
    }

    /**
     * Execute. - A substitute for exec()
     *
     * Same signature and results as exec(): https://www.php.net/manual/en/function.exec.php
     *
     * @param string $command  The command to execute
     * @param string &$output (optional)
     * @param int &$result_code (optional)
     *
     * @return string | false   The last line of output or false in case of failure
     */
    public static function exec($command, &$output = null, &$result_code = null)
    {
        foreach (self::$methods as $method) {
            if (function_exists($method)) {
                if (($method == 'shell_exec') && (func_num_args() == 3)) {
                    continue;
                }
                $result = self::runExec($method, $command, $output, $result_code);
                if ($result !== false) {
                    return $result;
                }
            }
        }
        if (isset($result) && ($result === false)) {
            return false;
        }
        return exec($command, $output, $result_code);
    }

    public static function runExec($method, $command, &$output = null, &$result_code = null)
    {
        switch ($method) {
            case 'exec':
                return exec($command, $output, $result_code);
            case 'passthru':
                return Passthru::exec($command, $output, $result_code);
            case 'popen':
                return POpen::exec($command, $output, $result_code);
            case 'proc_open':
                return ProcOpen::exec($command, $output, $result_code);
            case 'shell_exec':
                if (func_num_args() == 4) {
                    return ShellExec::exec($command, $output, $result_code);
                } else {
                    return ShellExec::exec($command, $output);
                }
        }
        return false;
    }
}
