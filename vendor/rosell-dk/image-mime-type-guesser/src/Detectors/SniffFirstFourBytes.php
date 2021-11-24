<?php

namespace ImageMimeTypeGuesser\Detectors;

use \ImageMimeTypeGuesser\Detectors\AbstractDetector;

class SniffFirstFourBytes extends AbstractDetector
{

    /**
     * Try to detect mime type by sniffing the first four bytes.
     *
     * Returns:
     * - mime type (string) (if it is in fact an image, and type could be determined)
     * - false (if it is not an image type that the server knowns about)
     * - null  (if nothing can be determined)
     *
     * @param  string  $filePath  The path to the file
     * @return string|false|null  mimetype (if it is an image, and type could be determined),
     *    false (if it is not an image type that the server knowns about)
     *    or null (if nothing can be determined)
     */
    protected function doDetect($filePath)
    {
        $handle = @fopen($filePath, 'r');
        if ($handle === false) {
            return null;
        }
        // 20 bytes is sufficient for all our sniffers, except image/svg+xml.
        // The svg sniffer takes care of reading more
        $sampleBin = @fread($handle, 20);
        if ($sampleBin === false) {
            return null;
        }
        $firstByte = $sampleBin[0];
        $sampleHex = strtoupper(bin2hex($sampleBin));

        $hexPatterns = [];
        $binPatterns = [];

        // https://www.rapidtables.com/convert/number/hex-to-ascii.html
        switch ($firstByte) {
            case "\x00":
                $hexPatterns[] = ['image/x-icon', "/^00000(1?2)00/"];

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

            case "f":
                //$hexPatterns[] = ['image/heic', "/667479706865(6963|6978|7663|696D|6973|766D|7673)/"];
                //$hexPatterns[] = ['image/heif', "/667479706D(69|73)6631)/"];
                $binPatterns[] = ['image/heic', "/ftyphe(ic|ix|vc|im|is|vm|vs)/"];
                $binPatterns[] = ['image/heif', "/ftypm(i|s)f1/"];
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
        // TODO: JPEG 2000
        // mime types: image/jp2, image/jpf, image/jpx, image/jpm
        // http://fileformats.archiveteam.org/wiki/JPEG_2000
        // https://www.file-recovery.com/jp2-signature-format.htm
        /*
        From: https://github.com/Tinram/File-Identifier/blob/master/src/FileSignatures.php
        'JPG 2000' => '00 00 00 0c 6a 50 20 20 0d 0a 87 0a',
        https://filesignatures.net/index.php?page=search&search=JP2&mode=EXT
        */
    }
}
