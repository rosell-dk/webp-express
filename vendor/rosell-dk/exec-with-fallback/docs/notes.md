
Read up on proc_open
https://www.sitepoint.com/proc-open-communicate-with-the-outside-world/
https://www.php.net/manual/en/function.proc-open.php
https://www.php.net/manual/en/function.proc-close.php
https://www.php.net/manual/en/function.stream-set-blocking.php
https://www.php.net/manual/en/function.stream-select.php
https://stackoverflow.com/questions/21353611/how-to-wait-for-a-process-executed-by-proc-open-in-php
https://pretagteam.com/question/how-to-wait-for-a-process-executed-by-procopen-in-php
https://www.google.com/search?q=proc_open+wait&oq=proc_open+wait&aqs=chrome..69i57.2386j0j1&sourceid=chrome&ie=UTF-8
https://wuhzat.wordpress.com/2011/01/11/php-exec-vs-proc_open/
https://stackoverflow.com/questions/5673740/php-or-apache-exec-popen-system-and-proc-open-commands-do-not-execute-any-com
https://stackoverflow.com/questions/25424506/the-difference-between-exec-and-popen/25424608

And see how other uses it:
https://packagist.org/packages/tivie/command
https://github.com/tivie/command/blob/master/src/Command.php
https://packagist.org/packages/symfony/process
https://github.com/axute/process
https://packagist.org/?query=proc_open
https://stackoverflow.com/questions/6014819/how-to-get-output-of-proc-open


https://linuxhint.com/wait_command_linux/


#pcntl_exec
Should we use it?
https://www.php.net/manual/en/function.proc-open.php#102239
https://www.php.net/manual/en/function.pcntl-exec.php
https://www.php.net/manual/en/function.posix-mkfifo.php


#popen
https://www.php.net/manual/en/function.popen.php


#shell_exec
Hm... no exit code.. how to deal with that?
https://www.php.net/manual/en/function.shell-exec.php


# disable_functions
Perhaps check `ini_get('disable_functions')` too ?
https://stackoverflow.com/questions/3938120/check-if-exec-is-disabled
https://stackoverflow.com/questions/2749591/php-exec-check-if-enabled-or-disabled
https://www.py4u.net/discuss/26911

# passthrough
https://www.php.net/manual/en/function.passthru.php

According to this, you cannot store in variable:
https://stackoverflow.com/questions/732832/php-exec-vs-system-vs-passthru

However, it can be done with output buffering:
```
function yemenEx($in)
{
    $out = '';
    if (function_exists('exec')) {
        @exec($in, $out);
        $out = @join("\n", $out);
    } elseif (function_exists('passthru')) {
        ob_start();
        @passthru($in);
        $out = ob_get_clean();
    } elseif (function_exists('system')) {
        ob_start();
        @system($in);
        $out = ob_get_clean();
    } elseif (function_exists('shell_exec')) {
        $out = shell_exec($in);
    } elseif (is_resource($f = @popen($in, "r"))) {
        $out = "";
        while (!@feof($f)) {
            $out .= fread($f, 1024);
        }
        pclose($f);
    }
    return $out;
}
```
(got it from here: https://hotexamples.com/examples/-/-/passthru/php-passthru-function-examples.html)
Also check this little overview out: https://stackoverflow.com/questions/732832/php-exec-vs-system-vs-passthru
https://stackoverflow.com/questions/732832/php-exec-vs-system-vs-passthru



#system


Should I call the library ExecWithBackup or something like that instead? - nah, only if I find a better name than that


# What should this be called?
Simply what it does:
- ExecWithFallback
- ExecOrSimilar
- TryHarderExec / ExecTryHarder

The rat finds its way… And dont give up easily. Tries different ways
- ExecRat / RatExec

Eller navngiv efter Wolfe, “the fixer” i Pulp Fiction - a no nonense charactor who gets the job done (with style)
- WolfeExec / ExecWolfe

Nationality:
- GermanExec
- DanishExec

Material:
- RubberExec
- RubberbandExec

Other ideas:
StrongExec, ResilientExec, EmulatedExec, GoalOrientedExec, ExecContractor, ExecDeLuxe, DropInExec, BetterExec, ExecMaster, ExecDragon, CuteExec, PersistantExec, ToughExec, ExecWithFallback, AdaptableExec, CompliantExec, ImprovedExec, ResoluteExec, StautExec, ObstinateExec, KeenExec, AgileExec, Smooth, Flexible, ExecAndFriends, SturdyExex, ResilientExec
