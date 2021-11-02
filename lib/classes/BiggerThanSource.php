<?php

/*
This class is made to not be dependent on Wordpress functions and must be kept like that.
It is used by webp-on-demand.php. It is also used for bulk conversion.
*/
namespace WebPExpress;


class BiggerThanSource
{
    /**
     * Check if webp is bigger than original.
     *
     * @return boolean|null   True if it is bigger than original, false if not. NULL if it cannot be determined
     */
    public static function bigger($source, $destination)
    {
        /*
        if ((!@file_exists($source)) || (!@file_exists($destination) {
            return null;
        }*/
        $filesizeDestination = @filesize($destination);
        $filesizeSource = @filesize($source);

        // sizes are FALSE on failure (ie if file does not exists)
        if (($filesizeDestination === false) || ($filesizeDestination === false)) {
            return null;
        }

        return ($filesizeDestination > $filesizeSource);
    }
}
