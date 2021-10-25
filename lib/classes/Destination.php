<?php

/*
This class is made to not be dependent on Wordpress functions and must be kept like that.
It is used by webp-on-demand.php, which does not register an auto loader. It is also used for bulk conversion.
*/
namespace WebPExpress;

class Destination
{

    /**
     *
     * @return boolean  Whether or not the destination corresponding to a given source should be stored in the same folder
     *                  or the separate folder (in wp-content/webp-express)
     */
    private static function storeMingledOrNot($source, $mingled, $uploadDirAbs)
    {
        if ($mingled == false) {
            return false;
        }

        // Option is set for mingled, but this does not neccessarily means we should store "mingled".
        // - because the mingled option only applies to upload folder, the rest is stored in separate cache folder
        // So, return true, if $source is located in upload folder
        return (strpos($source, $uploadDirAbs) === 0);
    }

    /**
     * Append ".webp" to path or replace extension with "webp", depending on what is appropriate.
     *
     * If destination-folder is set to mingled and destination-extension is set to "set" and
     * the path is inside upload folder, the appropriate thing is to SET the extension.
     * Otherwise, it is to APPEND.
     *
     * @param  string   $path
     * @param  boolean  $mingled                    Mingled setting (notice that mingled only applies to uploads)
     * @param  string   $replaceExt                 If file extension should be replaced with ".webp". If false, ".webp" is appended.
     * @param  boolean  $inUploadFolder
     */
    public static function appendOrSetExtension($path, $mingled, $replaceExt, $inUploadFolder)
    {
        if ($mingled && $replaceExt && $inUploadFolder) {
            return preg_replace('/\\.(jpe?g|png)$/i', '', $path) . '.webp';
        } else {
            return $path . '.webp';
        }
    }

    /**
     * Get destination path corresponding to the source path given (and some configurations)
     *
     * If for example Operation mode is set to "mingled" and extension is set to "Append .webp",
     * the result of finding the destination path that corresponds to "/path/to/logo.jpg" will be "/path/to/logo.jpg.webp".
     *
     * The imageRoots are tried in order.
     * This means that ie "uploads" is preferred over "wp-content" even though the source resides in both (when uploads is inside wp-content)
     * So destination is ie [..]/wp-content/webp-express/uploads/[..]", rather than same but with "wp-content"
     *
     * @param  string   $source                       Path to source file
     * @param  string   $webExpressContentDirAbs
     * @param  string   $uploadDirAbs
     * @param  DestinationOptions  $destinationOptions
     * @param  ImageRoots  $imageRoots                An image roots object
     *
     * @return string|false   Returns path to destination corresponding to source, or false on failure
     */
    public static function getDestinationPathCorrespondingToSource(
        $source,
        $webExpressContentDirAbs,
        $uploadDirAbs,
        $destinationOptions,
        $imageRoots
    ) {
        // At this point, everything has already been checked for sanity. But for good meassure, lets
        // check the most important parts again. This is after all a public method.
        // ------------------------------------------------------------------

        $mingled = $destinationOptions->mingled;
        $replaceExt = $destinationOptions->replaceExt;
        $useDocRoot = $destinationOptions->useDocRoot;

        try {
            // Check source
            // --------------
            // TODO: make this check work with symlinks
            //$source = SanityCheck::absPathExistsAndIsFileInDocRoot($source);

            // Calculate destination and check that the result is sane
            // -------------------------------------------------------
            if (self::storeMingledOrNot($source, $mingled, $uploadDirAbs)) {
                $destination = self::appendOrSetExtension($source, $mingled, $replaceExt, true);
            } else {

                if ($useDocRoot) {
                    // We must find the relative path from document root to source.
                    // However, we dont know if document root is resolved or not.
                    // We also do not know if source begins with a resolved or unresolved document root.
                    // And we cannot be sure that document root is resolvable.

                    // Lets say:
                    // 1. document root is unresolvable.
                    // 2. document root is configured to something unresolved ("/my-website")
                    // 3. source is resolved and within an image root ("/var/www/my-website/wp-content/uploads/test.jpg")
                    // 4. all image roots are resolvable.
                    // 5. Paths::canUseDocRootForRelPaths()) returned true

                    // Can the relative path then be found?
                    // Actually, yes.
                    // We can loop through the image roots.
                    // When we get to the "uploads" root, it must neccessarily contain the unresolved document root.
                    // It will in other words be: "my-website/wp-content/uploads"
                    // It can not be configured to the resolved path because canUseDocRootForRelPaths would have then returned false as
                    // It would not be possible to establish that "/var/www/my-website/wp-content/uploads/" is within document root, as
                    // document root is "/my-website" and unresolvable.
                    // To sum up, we have:
                    // If document root is unresolvable while canUseDocRootForRelPaths() succeeded, then the image roots will all begin with
                    // the unresolved path.
                    // In this method, if $useDocRootForStructuringCacheDir is true, then it is assumed that canUseDocRootForRelPaths()
                    // succeeded.
                    // OH!
                    // I realize that the image root can be passed as well:
                    // $imageRoot = $webExpressContentDirAbs . '/webp-images';
                    // So the question is: Will $webExpressContentDirAbs also be the unresolved path?
                    // That variable is calculated in WodConfigLoader based on various methods available.
                    // I'm not digging into it, but would expect it to in some cases be resolved. Which means that relative path can not
                    // be found.
                    // So. Lets play it safe and require that document root is resolvable in order to use docRoot for structure

                    if (!PathHelper::isDocRootAvailable()) {
                        throw new \Exception(
                            'Can not calculate destination using "doc-root" structure as document root is not available. $_SERVER["DOCUMENT_ROOT"] is empty. ' .
                            'This is probably a misconfiguration on the server. ' .
                            'However, WebP Express can function without using documument root. If you resave options and regenerate the .htaccess files, it should ' .
                            'automatically start to structure the webp files in subfolders that are relative the image root folders rather than document-root.'
                        );
                    }

                    if (!PathHelper::isDocRootAvailableAndResolvable()) {
                        throw new \Exception(
                            'Can not calculate destination using "doc-root" structure as document root cannot be resolved for symlinks using "realpath". The ' .
                            'reason for that is probably that open_basedir protection has been set up and that document root is outside outside that open_basedir. ' .
                            'WebP Express can function in that setting, however you will need to resave options and regenerate the .htaccess files. It should then ' .
                            'automatically stop to structure the webp files as relative to document root and instead structure them as relative to image root folders.'
                        );
                    }
                    $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
                    $imageRoot = $webExpressContentDirAbs . '/webp-images';

                    // TODO: make this check work with symlinks
                    //SanityCheck::absPathIsInDocRoot($imageRoot);

                    $sourceRel = substr(realpath($source), strlen($docRoot) + 1);
                    $destination = $imageRoot . '/doc-root/' . $sourceRel;
                    $destination = self::appendOrSetExtension($destination, $mingled, $replaceExt, false);


                    // TODO: make this check work with symlinks
                    //$destination = SanityCheck::absPathIsInDocRoot($destination);
                } else {
                    $destination = '';

                    $sourceResolved = realpath($source);


                    // Check roots until we (hopefully) get a match.
                    // (that is: find a root which the source is inside)
                    foreach ($imageRoots->getArray() as $i => $imageRoot) {
                        // in $obj, "rel-path" is only set when document root can be used for relative paths.
                        // So, if it is set, we can use it (beware: we cannot neccessarily use realpath on document root,
                        // but we do not need to - see the long comment in Paths::canUseDocRootForRelPaths())

                        $rootPath = $imageRoot->getAbsPath();
                        /*
                        if (isset($obj['rel-path'])) {
                            $docRoot = rtrim($_SERVER["DOCUMENT_ROOT"], '/');
                            $rootPath = $docRoot . '/' . $obj['rel-path'];
                        } else {
                            // If "rel-path" isn't set, then abs-path is, and we can use that.
                            $rootPath = $obj['abs-path'];
                        }*/

                        // $source may be resolved or not. Same goes for $rootPath.
                        // We can assume that $rootPath is resolvable using realpath (it ought to exist and be within open_basedir for WP to function)
                        // We can also assume that $source is resolvable (it ought to exist and within open_basedir)
                        // So: Resolve both! and test if the resolved source begins with the resolved rootPath.
                        if (strpos($sourceResolved, realpath($rootPath)) !== false) {
                            $relPath = substr($sourceResolved, strlen(realpath($rootPath)) + 1);
                            $relPath = self::appendOrSetExtension($relPath, $mingled, $replaceExt, false);

                            $destination = $webExpressContentDirAbs . '/webp-images/' . $imageRoot->id . '/' . $relPath;
                            break;
                        }
                    }
                    if ($destination == '') {
                        return false;
                    }
                }
            }

        } catch (SanityException $e) {
            return false;
        }

        return $destination;
    }

}
