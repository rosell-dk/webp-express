<?php
namespace ExecWithFallback;

/**
 * Check if any of the methods are available on the system.
 *
 * @package    ExecWithFallback
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class Availability extends ExecWithFallback
{

    /**
     * Check if any of the methods are available on the system.
     *
     * @param boolean $needResultCode  Whether the code using this library is going to supply $result_code to the exec
     *         call. This matters because shell_exec is only available when not.
     */
    public static function anyAvailable($needResultCode = true)
    {
        foreach (self::$methods as $method) {
            if (self::methodAvailable($method, $needResultCode)) {
                return true;
            }
        }
        return false;
    }

    public static function methodAvailable($method, $needResultCode = true)
    {
        if (!ExecWithFallback::functionEnabled($method)) {
            return false;
        }
        if ($needResultCode) {
            return ($method != 'shell_exec');
        }
        return true;
    }
}
