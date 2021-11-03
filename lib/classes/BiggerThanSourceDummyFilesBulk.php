<?php

namespace WebPExpress;


class BiggerThanSourceDummyFilesBulk
{

    private static $settings;

    /**
     * Update the status for a all images.
     *
     */
    public static function updateStatus($config = null)
    {
        if (is_null($config)) {
            $config = Config::loadConfigAndFix(false);
        }
        self::$settings = [
            'ext' => $config['destination-extension'],
            'destination-folder' => $config['destination-folder'],  /* hm, "destination-folder" is a bad name... */
            'webExpressContentDirAbs' => Paths::getWebPExpressContentDirAbs(),
            'uploadDirAbs' => Paths::getUploadDirAbs(),
            'useDocRootForStructuringCacheDir' => (($config['destination-structure'] == 'doc-root') && (Paths::canUseDocRootForStructuringCacheDir())),
            //'imageRoots' => new ImageRoots(Paths::getImageRootsDefForSelectedIds($config['scope'])),   // (Paths::getImageRootsDef()
            'imageRoots' => new ImageRoots(Paths::getImageRootsDefForSelectedIds(Paths::getImageRootIds())),   // (Paths::getImageRootsDef()
            'image-types' => $config['image-types'],
        ];


        //$rootIds = Paths::filterOutSubRoots($config['scope']);

        // We want to update status on ALL root dirs (so we don't have to re-run when user changes scope)
        $rootIds = Paths::filterOutSubRoots(Paths::getImageRootIds());
        //$rootIds = ['uploads'];
        //$rootIds = ['uploads', 'themes'];

        foreach ($rootIds as $rootId) {
            self::updateStatusForRoot($rootId);
        }
    }

    /**
     * Pre-requirement: self::$settings is set.
     *
     * Idea for improvement: Traverse destination dirs instead. This will be quicker, as there will not be
     * as many images (unless all have been converted), and not as many folders (non-image folders will not be present.
     * however, index does not take too long to traverse, even though it has many non-image folders, so it will only
     * be a problem if there are plugins or themes with extremely many folders).
     */
    private static function updateStatusForRoot($rootId, $dir = '')
    {
        if ($dir == '') {
            $dir = Paths::getAbsDirById($rootId);
        }

        // Canonicalize because dir might contain "/./", which causes file_exists to fail (#222)
        $dir = PathHelper::canonicalize($dir);

        if (!@file_exists($dir) || !@is_dir($dir)) {
            return [];
        }

        $fileIterator = new \FilesystemIterator($dir);

        $results = [];

        while ($fileIterator->valid()) {
            $filename = $fileIterator->getFilename();

            if (($filename != ".") && ($filename != "..")) {
                if (@is_dir($dir . "/" . $filename)) {
                    $newDir = $dir . "/" . $filename;

                    // The new dir might have its own root id
                    $newRootId = Paths::findImageRootOfPath($newDir, Paths::getImageRootIds());
                    //echo $newRootId . ': ' . $newDir . "\n";
                    self::updateStatusForRoot($newRootId, $newDir);
                } else {
                    // its a file - check if its a valid image type (jpeg or png)
                    $regex = '#\.(jpe?g|png)$#';
                    if (preg_match($regex, $filename)) {

                        $source = $dir . "/" . $filename;

                        $destination = ConvertHelperIndependent::getDestination(
                            $source,
                            self::$settings['destination-folder'],
                            self::$settings['ext'],
                            self::$settings['webExpressContentDirAbs'],
                            self::$settings['uploadDirAbs'],
                            self::$settings['useDocRootForStructuringCacheDir'],
                            self::$settings['imageRoots'],
                            //$rootId

                        );
                        $webpExists = @file_exists($destination);

                        //echo ($webpExists ? 'YES' : 'NO') . ' ' . $rootId . ': ' . $source . "\n";

                        BiggerThanSourceDummyFiles::updateStatus(
                            $source,
                            $destination,
                            self::$settings['webExpressContentDirAbs'],
                            self::$settings['imageRoots'],
                            self::$settings['destination-folder'],
                            self::$settings['ext'],
                            // TODO: send rootId so the function doesn't need to try all
                            // $rootId,
                        );

                    }
                }
            }
            $fileIterator->next();
        }
        return $results;
    }
}
