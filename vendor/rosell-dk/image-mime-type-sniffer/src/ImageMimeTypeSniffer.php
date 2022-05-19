<?php

namespace ImageMimeTypeSniffer;

class ImageMimeTypeSniffer
{

    private static function checkFilePathIsRegularFile($input)
    {
        if (gettype($input) !== 'string') {
            throw new \Exception('File path must be string');
        }
        if (strpos($input, chr(0)) !== false) {
            throw new \Exception('NUL character is not allowed in file path!');
        }
        if (preg_match('#[\x{0}-\x{1f}]#', $input)) {
            // prevents line feed, new line, tab, charater return, tab, ets.
            throw new \Exception('Control characters #0-#20 not allowed in file path!');
        }
        // Prevent phar stream wrappers (security threat)
        if (preg_match('#^phar://#', $input)) {
            throw new \Exception('phar stream wrappers are not allowed in file path');
        }
        if (preg_match('#^(php|glob)://#', $input)) {
            throw new \Exception('php and glob stream wrappers are not allowed in file path');
        }
        if (empty($input)) {
            throw new \Exception('File path is empty!');
        }
        if (!@file_exists($input)) {
            throw new \Exception('File does not exist');
        }
        if (@is_dir($input)) {
            throw new \Exception('Expected a regular file, not a dir');
        }
    }


    /**
     * Try to detect mime type by sniffing the signature
     *
     * Returns:
     * - mime type (string) (if it is in fact an image, and type could be determined)
     * - null  (if nothing can be determined)
     *
     * @param  string  $filePath  The path to the file
     * @return string|null  mimetype (if it is an image, and type could be determined),
     *    or null (if the file does not match any of the signatures tested)
     * @throws \Exception  if file cannot be opened/read
     */
    public static function detect($filePath)
    {
        self::checkFilePathIsRegularFile($filePath);


        $handle = @fopen($filePath, 'r');
        if ($handle === false) {
            throw new \Exception('File could not be opened');
        }
        // 20 bytes is sufficient for all our sniffers, except image/svg+xml.
        // The svg sniffer takes care of reading more
        $sampleBin = @fread($handle, 20);
        if ($sampleBin === false) {
            throw new \Exception('File could not be read');
        }
        if (strlen($sampleBin) < 20) {
            return null;  // File is too small for us to deal with
        }
        $firstByte = $sampleBin[0];
        $sampleHex = strtoupper(bin2hex($sampleBin));

        $hexPatterns = [];
        $binPatterns = [];

        //$hexPatterns[] = ['image/heic', "/667479706865(6963|6978|7663|696D|6973|766D|7673)/"];
        //$hexPatterns[] = ['image/heif', "/667479706D(69|73)6631)/"];
        // heic:

        // HEIC signature: https://github.com/strukturag/libheif/issues/83#issuecomment-421427091
        // https://nokiatech.github.io/heif/technical.html
        // https://perkeep.org/internal/magic/magic.go
        // https://www.file-recovery.com/mp4-signature-format.htm
        $binPatterns[] = ['image/heic', "/^(.{4}|.{8})ftyphe(ic|ix|vc|im|is|vm|vs)/"];
        //$binPatterns[] = ['image/heif', "/^(.{4}|.{8})ftypm(i|s)f1/"];

        // https://www.rapidtables.com/convert/number/hex-to-ascii.html
        switch ($firstByte) {
            case "\x00":
                $hexPatterns[] = ['image/x-icon', "/^00000(1?2)00/"];

                $binPatterns[] = ['image/avif', "/^(.{4}|.{8})ftypavif/"];

                if (preg_match("/^.{8}6A502020/", $sampleHex) === 1) {
                    // jpeg-2000 - a bit more complex, as block size may vary
                    // https://www.file-recovery.com/jp2-signature-format.htm
                    $block1Size = hexdec("0x" . substr($sampleHex, 0, 8));

                    $moreBytes = @fread($handle, $block1Size + 4 + 8);
                    if ($moreBytes !== false) {
                        $sampleBin .= $moreBytes;
                    }
                    if (substr($sampleBin, $block1Size + 4, 4) == 'ftyp') {
                        $subtyp = substr($sampleBin, $block1Size + 8, 4);
                        // "jp2 " (.JP2), "jp20" (.JPA), "jpm " (.JPM), "jpx " (.JPX).

                        if ($subtyp == 'mjp2') {
                            return 'video/mj2';
                        } else {
                            return 'image/' . rtrim($subtyp);
                        }
                    }
                }

                break;

            case "8":
                $binPatterns[] = ['application/psd', "/^8BPS/"];
                break;

            case "B":
                $binPatterns[] = ['image/bmp', "/^BM/"];
                break;

            case "G":
                $binPatterns[] = ['image/gif', "/^GIF8(7|9)a/"];
                break;

            case "I":
                $hexPatterns[] = ['image/tiff', "/^(49492A00|4D4D002A)/"];
                break;

            case "R":
                // PS: Another library is more specific: /^RIFF.{4}WEBPVP/
                // Is "VP" always there?
                $binPatterns[] = ['image/webp', "/^RIFF.{4}WEBP/"];
                break;

            case "<":
                // Another library looks for end bracket for svg.
                // We do not, as it requires more bytes read.
                // Note that <xml> tag might be big too... - so we read in 200 extra
                $moreBytes = @fread($handle, 200);
                if ($moreBytes !== false) {
                    $sampleBin .= $moreBytes;
                }
                $binPatterns[] = ['image/svg+xml', "/^(<\?xml[^>]*\?>.*)?<svg/is"];
                break;

            case "\x89":
                $hexPatterns[] = ['image/png', "/^89504E470D0A1A0A/"];
                break;

            case "\xFF":
                $hexPatterns[] = ['image/jpeg', "/^FFD8FF(DB|E0|EE|E1)/"];
                break;
        }

        foreach ($hexPatterns as list($mime, $pattern)) {
            if (preg_match($pattern, $sampleHex) === 1) {
                return $mime;
            }
        }
        foreach ($binPatterns as list($mime, $pattern)) {
            if (preg_match($pattern, $sampleBin) === 1) {
                return $mime;
            }
        }
        return null;

        /*
        https://en.wikipedia.org/wiki/List_of_file_signatures
        https://github.com/zjsxwc/mime-type-sniffer/blob/master/src/MimeTypeSniffer/MimeTypeSniffer.php
        http://phil.lavin.me.uk/2011/12/php-accurately-detecting-the-type-of-a-file/

*/
    }
}
