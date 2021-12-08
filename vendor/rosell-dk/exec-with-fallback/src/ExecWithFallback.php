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
     * Check if a function is enabled (function_exists as well as ini is tested)
     *
     * @param string $functionName  The name of the function
     *
     * @return boolean If the function is enabled
     */
    public static function functionEnabled($functionName)
    {
        if (!function_exists($functionName)) {
            return false;
        }
        if (function_exists('ini_get')) {
            if (ini_get('safe_mode')) {
                return false;
            }
            $d = ini_get('disable_functions') . ',' . ini_get('suhosin.executor.func.blacklist');
            if ($d === false) {
                $d = '';
            }
            $d = preg_replace('/,\s*/', ',', $d);
            if (strpos(',' . $d . ',', ',' . $functionName . ',') !== false) {
                return false;
            }
        }
        return is_callable($functionName);
    }


    /**
     * Execute. - A substitute for exec()
     *
     * Same signature and results as exec(): https://www.php.net/manual/en/function.exec.php
     * In case neither exec(), nor emulations are available, it throws an Exception.
     * This is more gentle than real exec(), which on some systems throws a FATAL when exec() is disabled
     * If you want the more acurate substitute, which might halt execution, use execNoMercy() instead.
     *
     * @param string $command  The command to execute
     * @param string &$output (optional)
     * @param int &$result_code (optional)
     *
     * @return string | false   The last line of output or false in case of failure
     * @throws \Exception  If no methods are available
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
        throw new \Exception('exec() is not available');
    }

    /**
     *  Execute. - A substitute for exec(), with exact same errors thrown if exec() is missing.
     *
     *  Danger: On some systems, this results in a fatal (non-catchable) error.
     */
    public static function execNoMercy($command, &$output = null, &$result_code = null)
    {
        if (func_num_args() == 3) {
            return ExecWithFallbackNoMercy::exec($command, $output, $result_code);
        } else {
            return ExecWithFallbackNoMercy::exec($command, $output);
        }
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
