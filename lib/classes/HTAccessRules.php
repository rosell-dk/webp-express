<?php

namespace WebPExpress;

class HTAccessRules
{
    private static $useDocRootForStructuringCacheDir;
    private static $config;
    private static $mingled;
    private static $appendWebP;
    private static $imageTypes;
    private static $fileExt;
    private static $fileExtensions;
    private static $fileExtIncludingDot;
    private static $htaccessDir;
    private static $htaccessDirRelToDocRoot;
    private static $htaccessDirAbs;
    private static $modHeaderDefinitelyUnavailable;
    private static $passThroughHeaderDefinitelyUnavailable;
    private static $passThroughHeaderDefinitelyAvailable;
    private static $passThroughEnvVarDefinitelyUnavailable;
    private static $passThroughEnvVarDefinitelyAvailable;
    private static $capTests;
    private static $addVary;
    private static $dirContainsSourceImages;
    private static $dirContainsWebPImages;


    /**
     *  @return  string  Info in comments.
     */
    private static function infoRules()
    {

        return "# The rules below is a result of many parameters, including the following:\n" .
            "#\n# WebP Express options:\n" .
            "# - Redirection to existing webp: " .
                (self::$config['redirect-to-existing-in-htaccess'] ? 'enabled' : 'disabled') . "\n" .
            "# - Redirection to converter: " .
                (self::$config['enable-redirection-to-converter'] ? 'enabled' : 'disabled') . "\n" .
            "# - Redirection to converter to create missing webp files upon request for the webp: " .
                (self::$config['enable-redirection-to-webp-realizer'] ? 'enabled' : 'disabled') . "\n" .

            "# - Destination folder: " . self::$config['destination-folder'] . "\n" .
            "# - Destination extension: " . self::$config['destination-extension'] . "\n" .
            "# - Destination structure: " . self::$config['destination-structure'] . (((self::$config['destination-structure'] == 'doc-root') && (!self::$useDocRootForStructuringCacheDir)) ? ' (overruled!)' : '') . "\n" .
            "# - Image types: " . str_replace('?', '', implode(', ', self::$fileExtensions)) . "\n" .

            "#\n# Wordpress/Server configuration:\n" .
            '# - Document root availablity: ' . Paths::docRootStatusText() . "\n" .

            "#\n# .htaccess capability test results:\n" .
            "# - mod_header working?: " .
                (self::$capTests['modHeaderWorking'] === true ? 'yes' : (self::$capTests['modHeaderWorking'] === false ? 'no' : 'could not be determined')) . "\n" .
            "# - pass variable from .htaccess to script through header working?: " .
                (self::$capTests['passThroughHeaderWorking'] === true ? 'yes' : (self::$capTests['passThroughHeaderWorking'] === false ? 'no' : 'could not be determined')) . "\n" .
            "# - pass variable from .htaccess to script through environment variable working?: " .
                (self::$capTests['passThroughEnvWorking'] === true ? 'yes' : (self::$capTests['passThroughEnvWorking'] === false ? 'no' : 'could not be determined')) . "\n" .

            "#\n# Role of the dir that this .htaccess is located in:\n" .
            '# - Is this .htaccess in a dir containing source images?: ' . (self::$dirContainsSourceImages ? 'yes' : 'no') . "\n" .
            '# - Is this .htaccess in a dir containing webp images?: ' . (self::$dirContainsWebPImages ? 'yes' : 'no') . "\n" .
            "\n";
    }

    /**
     *  @return  string  rules for cache control
     */
    private static function cacheRules()
    {
        // Build cache control rules
        $ccRules = '';
        $cacheControlHeader = Config::getCacheControlHeader(self::$config);
        if ($cacheControlHeader != '') {
            $ccRules .= "# Set Cache-Control header for requests to webp images\n";
            $ccRules .= "<IfModule mod_headers.c>\n";
            $ccRules .= "  <FilesMatch \"(?i)\.webp$\">\n";
            $ccRules .= "    Header set Cache-Control \"" . $cacheControlHeader . "\"\n";
            $ccRules .= "  </FilesMatch>\n";
            $ccRules .= "</IfModule>\n\n";

            // Fall back to mod_expires if mod_headers is unavailable
            if (self::$modHeaderDefinitelyUnavailable) {
                $cacheControl = self::$config['cache-control'];

                $expires = '';
                if ($cacheControl == 'custom') {
                    $expires = '';

                    // Do not add Expire header if private is set
                    // - because then the user don't want caching in proxies / CDNs.
                    //   the Expires header doesn't differentiate between private/public
                    if (!(preg_match('/private/', self::$config['cache-control-custom']))) {
                        if (preg_match('/max-age=(\d+)/', self::$config['cache-control-custom'], $matches)) {
                            if (isset($matches[1])) {
                                $expires = $matches[1] . ' seconds';
                            }
                        }
                    }

                } elseif ($cacheControl == 'no-header') {
                    $expires = '';
                } elseif ($cacheControl == 'set') {
                    if (self::$config['cache-control-public']) {
                        $cacheControlOptions = [
                            'no-header' => '',
                            'one-second' => '1 seconds',
                            'one-minute' => '1 minutes',
                            'one-hour' => '1 hours',
                            'one-day' => '1 days',
                            'one-week' => '1 weeks',
                            'one-month' => '1 months',
                            'one-year' => '1 years',
                        ];
                        $expires = $cacheControlOptions[self::$config['cache-control-max-age']];
                    }
                }

                if ($expires != '') {
                    // in case mod_headers is missing, try mod_expires
                    $ccRules .= "# Fall back to mod_expires if mod_headers is unavailable\n";
                    $ccRules .= "<IfModule !mod_headers.c>\n";
                    $ccRules .= "  <IfModule mod_expires.c>\n";
                    $ccRules .= "    ExpiresActive On\n";
                    $ccRules .= "    ExpiresByType image/webp \"access plus " . $expires . "\"\n";
                    $ccRules .= "  </IfModule>\n";
                    $ccRules .= "</IfModule>\n\n";
                }
            }
        }
        return $ccRules;
    }

    /**
     *  @return  string  rules for redirecting to existing
     */
    private static function redirectToExistingRules()
    {
        $rules = '';


        if (self::$mingled) {
            // TODO:
            // Only write mingled rules for "uploads" dir.
            // - UNLESS no .htaccess has been placed in uploads dir (is unwritable) (in that case also write for wp-content / index)
            // (self::$htaccessDir == 'uploads')
            $rules .= "  # Redirect to existing converted image in same dir (if browser supports webp)\n";
            $rules .= "  RewriteCond %{HTTP_ACCEPT} image/webp\n";

            if (self::$htaccessDir == 'index') {
                // TODO: Add the following rule if configured to
                if (false) {
                    // TODO: Full path to wp-admin from doc-root - if possible
                    // (that is: if document root is available).
                    // ie: RewriteCond %{REQUEST_URI} ^/?wordpress/wp-admin
                    $rules .= "  RewriteCond %{REQUEST_URI} !wp-admin\n";
                }
            }

//            $rules .= "  RewriteCond %{REQUEST_FILENAME} (?i)(.*)(" . self::$fileExtIncludingDot . ")$\n";

            // self::$appendWebP cannot be used, we need this:
            // (because we are not sure there are a .htaccess in the uploads folder)

            if (self::$useDocRootForStructuringCacheDir) {
                if (self::$config['destination-extension'] == 'append') {
                    $rules .= "  RewriteCond %{REQUEST_FILENAME}.webp -f\n";
                    //$rules .= "  RewriteCond %{DOCUMENT_ROOT}/" . self::$htaccessDirRelToDocRoot . "/$1.$2.webp -f\n";
                    $rules .= "  RewriteRule ^/?(.*)\.(" . self::$fileExt . ")$ $1.$2.webp [NC,T=image/webp,E=EXISTING:1," . (self::$addVary ? 'E=ADDVARY:1,' : '') . "L]\n\n";
                } else {
                    // extension: set to webp

                    //$rules .= "  RewriteCond %{DOCUMENT_ROOT}/" . self::$htaccessDirRelToDocRoot . "/$1.webp -f\n";
                    //$rules .= "  RewriteRule " . $rewriteRuleStart . "\.(" . self::$fileExt . ")$ $1.webp [T=image/webp,E=EXISTING:1," . (self::$addVary ? 'E=ADDVARY:1,' : '') . "L]\n\n";

                    // Got these new rules here: https://www.digitalocean.com/community/tutorials/how-to-create-and-serve-webp-images-to-speed-up-your-website
                    // (but are they actually better than the ones we use for append?)
                    $rules .= "  RewriteCond %{REQUEST_URI} (?i)(.*)(" . self::$fileExtIncludingDot . ")$\n";
                    $rules .= "  RewriteCond %{DOCUMENT_ROOT}%1\.webp -f\n";
                    $rules .= "  RewriteRule (?i)(.*)(" . self::$fileExtIncludingDot . ")$ %1\.webp [T=image/webp,E=EXISTING:1," . (self::$addVary ? 'E=ADDVARY:1,' : '') . "L]\n\n";

                    // Instead of using REQUEST_URI, I can use REQUEST_FILENAME and remove DOCUMENT_ROOT
                    // I suppose REQUEST_URI is what was requested (ie "/wp-content/uploads/image.jpg").
                    // REQUEST_FILENAME is the filesystem path. (ie "/var/www/example.com/uploads-moved/image.jpg")
                    // But it cant be, because then the digitalocean solution would not work in above case.
                    // TODO: investigate

    //                    RewriteRule (?i)(.*)(\.jpe?g|\.png)$ %1\.webp [T=image/webp,E=EXISTING:1,E=ADDVARY:1,L]
                }
            } else {
                $appendWebP = !(self::$config['destination-extension'] == 'set');

                $rules .= "  RewriteCond %{REQUEST_FILENAME} (?i)(.*)(" . self::$fileExtIncludingDot . ")$\n";
                $rules .= "  RewriteCond %1" . ($appendWebP ? "%2" : "") . "\.webp -f\n";
                $rules .= "  RewriteRule (?i)(.*)(" . self::$fileExtIncludingDot . ")$ %1" . ($appendWebP ? "%2" : "") .
                    "\.webp [T=image/webp,E=EXISTING:1," . (self::$addVary ? 'E=ADDVARY:1,' : '') . "L]\n\n";

            }

/*

            */
        }

        if (self::$htaccessDir != 'uploads') {
            //return '# temporalily disabled';
        }

        // Redirect to existing converted image in cache-dir.
        // Do not write these rules for uploads in mingled (there are no "uploads" images in cache-dir when in mingled mode)
        if (!(self::$mingled && (self::$htaccessDir == 'uploads'))) {
            $rules .= "  # Redirect to existing converted image in cache-dir (if browser supports webp)\n";
            $rules .= "  RewriteCond %{HTTP_ACCEPT} image/webp\n";

            if (self::$useDocRootForStructuringCacheDir) {
                $cacheDirRel = Paths::getCacheDirRelToDocRoot() . '/doc-root';

                $rules .= "  RewriteCond %{REQUEST_FILENAME} -f\n";
                $rules .= "  RewriteCond %{DOCUMENT_ROOT}/" . $cacheDirRel . "/" . self::$htaccessDirRelToDocRoot . "/$1.$2.webp -f\n";
                $rules .= "  RewriteRule ^/?(.+)\.(" . self::$fileExt . ")$ /" . $cacheDirRel . "/" . self::$htaccessDirRelToDocRoot .
                    "/$1.$2.webp [NC,T=image/webp,E=EXISTING:1," . (self::$addVary ? 'E=ADDVARY:1,' : '') . "L]\n\n";

            } else {
                // Make sure source image exists
                $rules .= "  RewriteCond %{REQUEST_FILENAME} -f\n";

                // Find relative path of source (accessible as %2%3)
                $rules .= "  RewriteCond %{REQUEST_FILENAME} (?i)(" . self::$htaccessDirAbs . "/)(.*)(" . self::$fileExtIncludingDot . ")$\n";

                // Make sure there is a webp in the cache-dir
                $cacheDirForThisRoot = Paths::getCacheDirForImageRoot(
                    self::$config['destination-folder'],
                    'image-roots',
                    self::$htaccessDir
                );
                $cacheDirForThisRoot = PathHelper::fixAbsPathToUseUnresolvedDocRoot($cacheDirForThisRoot);

                $rules .= "  RewriteCond " . $cacheDirForThisRoot . "/%2%3.webp -f\n";
                //RewriteCond /var/www/webp-express-tests/we0/wp-content-moved/webp-express/webp-images/uploads/%2%3.webp -f

                $urlPath = '/' . Paths::getContentUrlPath() . "/webp-express/webp-images/" . self::$htaccessDir . "/%2" . (self::$appendWebP ? "%3" : "") . "\.webp";
                //$rules .= "  RewriteCond %1" . (self::$appendWebP ? "%2" : "") . "\.webp -f\n";
                $rules .= "  RewriteRule (?i)(.*)(" . self::$fileExtIncludingDot . ")$ " . $urlPath .
                    " [T=image/webp,E=EXISTING:1," . (self::$addVary ? 'E=ADDVARY:1,' : '') . "L]\n\n";
            }

            //$rules .= "  RewriteRule ^\/?(.*)\.(" . self::$fileExt . ")$ /" . $cacheDirRel . "/" . self::$htaccessDirRelToDocRoot . "/$1.$2.webp [NC,T=image/webp,E=EXISTING:1,L]\n\n";
        }

        return $rules;
    }

    private static function webpRealizerRules()
    {
        /*
        # Pass REQUEST_FILENAME to PHP in request header
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{DOCUMENT_ROOT}/wordpress/uploads-moved/$1 -f
        RewriteRule ^(.*)\.(webp)$ - [E=REQFN:%{REQUEST_FILENAME}]
        <IfModule mod_headers.c>
          RequestHeader set REQFN "%{REQFN}e" env=REQFN
        </IfModule>

        # WebP Realizer: Redirect non-existing webp images to converter when a corresponding jpeg/png is found
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{DOCUMENT_ROOT}/wordpress/uploads-moved/$1 -f
        RewriteRule ^(.*)\.(webp)$ /plugins-moved/webp-express/wod/webp-realizer.php?wp-content=wp-content-moved [NC,L]
        */

        $rules = '';
        $rules .= "# WebP Realizer: Redirect non-existing webp images to webp-realizer.php, which will locate corresponding jpg/png, \n" .
            "# convert it, and deliver the freshly converted webp\n";
        $rules .= "<IfModule mod_rewrite.c>\n" .
            "  RewriteEngine On\n";


        if (self::$useDocRootForStructuringCacheDir) {
            /*
            Generate something like this:

            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule ^/?(.+)\.(webp)$ /plugins-moved/webp-express/wod/webp-realizer.php [E=DESTINATIONREL:wp-content-moved/$0,E=WPCONTENT:wp-content-moved,NC,L]
            */
            $rules .= "  RewriteCond %{REQUEST_FILENAME} !-f\n";
            $params = [];
            $flags = [];
            if (!self::$passThroughEnvVarDefinitelyUnavailable) {
                $flags[] = 'E=DESTINATIONREL:' . self::$htaccessDirRelToDocRoot . '/$0';
            }
            if (!self::$passThroughEnvVarDefinitelyUnavailable) {
                $flags[] = 'E=WPCONTENT:' . Paths::getContentDirRel();
            }
            $flags[] = 'NC';
            $flags[] = 'L';

            $passRelativePathToSourceInQS = !(self::$passThroughEnvVarDefinitelyAvailable || self::$passThroughHeaderDefinitelyAvailable);
            if ($passRelativePathToSourceInQS) {
                $params[] = 'xdestination-rel=x' . self::$htaccessDirRelToDocRoot . '/$1.$2';
            }
            if (!self::$passThroughEnvVarDefinitelyAvailable) {
                $params[] = "wp-content=" . Paths::getContentDirRel();
            }

            $rewriteRuleStart = '^/?(.+)';
            $rules .= "  RewriteRule " . $rewriteRuleStart . "\.(webp)$ " .
                "/" . Paths::getWebPRealizerUrlPath() .
                ((count($params) > 0) ?  "?" . implode('&', $params) : '') .
                " [" . implode(',', $flags) . "]\n\n";
        } else {
            /*
            Generate something like this:
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule (?i).*(\.jpe?g|\.png)\.webp$ /plugins-moved/webp-express/wod/webp-realizer.php [E=WE_WP_CONTENT_REL_TO_PLUGIN_DIR:../wp-content-moved,E=WE_DESTINATION_REL_HTACCESS:$0,E=WE_HTACCESS_ID:cache,NC,L]
            */
            // Add condition for making sure the webp does not already exist
            $rules .= "  RewriteCond %{REQUEST_FILENAME} !-f\n";

            $params = [];
            $flags = [];

            if (!self::$passThroughEnvVarDefinitelyUnavailable) {
                $flags[] = 'E=WE_WP_CONTENT_REL_TO_PLUGIN_DIR:' . Paths::getContentDirRelToPluginDir();
                $flags[] = 'E=WE_DESTINATION_REL_HTACCESS:$0';
                $flags[] = 'E=WE_HTACCESS_ID:' . self::$htaccessDir;    // this will btw either be "uploads" or "cache"
            }
            $flags[] = 'NC';  // case-insensitive match (so file extension can be jpg, JPG or even jPg)
            $flags[] = 'L';

            if (!self::$passThroughEnvVarDefinitelyAvailable) {
                $params[] = 'xwp-content-rel-to-plugin-dir=x' . Paths::getContentDirRelToPluginDir();
                $params[] = 'xdestination-rel-htaccess=x$0';
                $params[] = 'htaccess-id=' . self::$htaccessDir;
            }

            // self::$appendWebP cannot be used, we need the following in order for
            // it to work for uploads in: Mingled, "Set to WebP", "Image roots".
            // TODO! Will it work for ie theme images?
            // - well, it should, because the script is passed $0. Not matching the ".png" part of the filename
            // only means it is a bit more greedy than it has to
            $appendWebP = !(self::$config['destination-extension'] == 'set');

            $rules .= "  RewriteRule (?i).*" . ($appendWebP ? "(" . self::$fileExtIncludingDot . ")" : "") . "\.webp$ " .
                "/" . Paths::getWebPRealizerUrlPath() .
                (count($params) > 0 ? "?" . implode('&', $params) : "") .
                " [" . implode(',', $flags) . "]\n";

            /*
            Generate something like this:
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteRule (?i).*\.webp$ /plugins-moved/webp-express/wod/webp-realizer.php [E=WE_WP_CONTENT_REL_TO_PLUGIN_DIR:../wp-content-moved,E=WE_DESTINATION_REL_IMAGE_ROOT:$0,E=WE_IMAGE_ROOT_ID:wp-content,NC,L]
            */
/*
            // Add condition for making sure the webp does not already exist
            $rules .= "  RewriteCond %{REQUEST_FILENAME} !-f\n";

            $params = [];
            $flags = [];

            if (!self::$passThroughEnvVarDefinitelyUnavailable) {
                $flags[] = 'E=WE_WP_CONTENT_REL_TO_PLUGIN_DIR:' . Paths::getContentDirRelToPluginDir();
                $flags[] = 'E=WE_DESTINATION_REL_IMAGE_ROOT:$0';
                $flags[] = 'E=WE_IMAGE_ROOT_ID:' . self::$htaccessDir;
            }
            $flags[] = 'NC';  // case-insensitive match (so file extension can be jpg, JPG or even jPg)
            $flags[] = 'L';

            if (!self::$passThroughEnvVarDefinitelyAvailable) {
                $params[] = 'image-root-id=' . self::$htaccessDir;
                $params[] = 'xdestination-rel-image-root=x$0';
                $params[] = 'xwp-content-rel-to-plugin-dir=x' . Paths::getContentDirRelToPluginDir();
            }

            // self::$appendWebP cannot be used, we need the following in order for
            // it to work for uploads in: Mingled, "Set to WebP", "Image roots".
            // TODO! Will it work for ie theme images?
            // - well, it should, because the script is passed $0. Not matching the ".png" part of the filename
            // only means it is a bit more greedy than it has to
            $appendWebP = !(self::$config['destination-extension'] == 'set');

            $rules .= "  RewriteRule (?i).*" . ($appendWebP ? "(" . self::$fileExtIncludingDot . ")" : "") . "\.webp$ " .
                "/" . Paths::getWebPRealizerUrlPath() .
                (count($params) > 0 ? "?" . implode('&', $params) : "") .
                " [" . implode(',', $flags) . "]\n\n";
*/


            /*
            Generate something like this:

            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} (?i)(/var/www/webp-express-tests/we0/wordpress/uploads-moved/)(.*)(\.jpe?g|\.png)(\.webp)$
            RewriteRule (?i).*\.webp$ /plugins-moved/webp-express/wod/webp-realizer.php?root-id=uploads&xdest-rel-to-root-id=x%2%3%4 [E=WE_WP_CONTENT_REL_TO_PLUGIN_DIR:../wp-content-moved,E=REQFN:%{REQUEST_FILENAME},NC,L]

            */
            /*
            // Bugger! When the requested file does not exist, %{REQUEST_FILENAME} will always contain the full path.
            // - it is set to the closest existing path plus one path component.
            // So we cannot use %{REQUEST_FILENAME} for webp realizer.
            // It seems we must use REQUEST_URI. But this could get tricky as we may not have access to the resolved document root in the scripts

            // Add condition for making sure the webp does not already exist
            $rules .= "  RewriteCond %{REQUEST_FILENAME} !-f\n";

            $rules .= "  RewriteCond %{REQUEST_FILENAME} (?i)(" .
                self::$htaccessDirAbs . "/)(.*)" . (self::$appendWebP ? "(" . self::$fileExtIncludingDot . ")" : "") . "(\.webp)$\n";

            $params = [];
            $flags = [];

            if (!self::$passThroughEnvVarDefinitelyUnavailable) {
                $flags[] = 'E=WE_WP_CONTENT_REL_TO_PLUGIN_DIR:' . Paths::getContentDirRelToPluginDir();
                $flags[] = 'E=WEDESTINATIONABS:%0';
            }
            $flags[] = 'NC';  // case-insensitive match (so file extension can be jpg, JPG or even jPg)
            $flags[] = 'L';

            if (!self::$passThroughEnvVarDefinitelyAvailable) {
                $params[] = 'root-id=' . self::$htaccessDir;
                $params[] = 'xdest-rel-to-root-id=x%2%3' . (self::$appendWebP ? "%4" : "");
            }

            $rules .= "  RewriteRule (?i).*\.webp$ " .
                "/" . Paths::getWebPRealizerUrlPath() .
                (count($params) > 0 ? "?" . implode('&', $params) : "") .
                " [" . implode(',', $flags) . "]\n\n";
            */
        }

        /*if (!self::$config['redirect-to-existing-in-htaccess']) {
            $rules .= self::cacheRules();
        }*/
        $rules .= "</IfModule>\n\n";

        return $rules;
    }

    private static function webpOnDemandRules()
    {
        $setEnvVar = !self::$passThroughEnvVarDefinitelyUnavailable;
        $passRelativePathToSourceInQS = !(self::$passThroughEnvVarDefinitelyAvailable || self::$passThroughHeaderDefinitelyAvailable);

        $rules = '';

        // Do not add header magic if passing through env is definitely working
        // Do not add either, if we definitily know it isn't working
        /*
        if ((!self::$passThroughEnvVarDefinitelyAvailable) && (!self::$passThroughHeaderDefinitelyUnavailable)) {
            if (self::$config['enable-redirection-to-converter']) {
                $rules .= "  # Pass REQUEST_FILENAME to webp-on-demand.php in request header\n";
                //$rules .= $basicConditions;
                //$rules .= "  RewriteRule ^(.*)\.(" . self::$fileExt . ")$ - [E=REQFN:%{REQUEST_FILENAME}]\n" .
                $rules .= "  <IfModule mod_headers.c>\n" .
                    "    RequestHeader set REQFN \"%{REQFN}e\" env=REQFN\n" .
                    "  </IfModule>\n\n";

            }
            if (self::$config['enable-redirection-to-webp-realizer']) {
                // We haven't implemented a clever way to pass through header for webp-realizer yet
            }
        }*/
        $rules .= "  # Redirect images to webp-on-demand.php ";
        if (self::$config['only-redirect-to-converter-for-webp-enabled-browsers']) {
            $rules .= "(if browser supports webp)\n";
        } else {
            $rules .= "(regardless whether browser supports webp or not!)\n";
        }
        if (self::$config['only-redirect-to-converter-for-webp-enabled-browsers']) {
            $rules .= "  RewriteCond %{HTTP_ACCEPT} image/webp\n";
        }

        if (self::$useDocRootForStructuringCacheDir) {
            /*
            Generate something like this:

            RewriteCond %{HTTP_ACCEPT} image/webp
            RewriteCond %{REQUEST_FILENAME} -f
            RewriteRule ^/?(.+)\.(jpe?g|png)$ /plugins-moved/webp-express/wod/webp-on-demand.php [NC,L,E=REQFN:%{REQUEST_FILENAME},E=WPCONTENT:wp-content-moved]
            */

            $params = [];
            $flags = ['NC', 'L'];
            if ($setEnvVar) {
                $flags[] = 'E=REQFN:%{REQUEST_FILENAME}';
            }
            $rules .= "  RewriteCond %{REQUEST_FILENAME} -f\n";
            if ($passRelativePathToSourceInQS) {
                $params[] = 'xsource-rel=x' . self::$htaccessDirRelToDocRoot . '/$1.$2';
            }
            if (!self::$passThroughEnvVarDefinitelyAvailable) {
                $params[] = "wp-content=" . Paths::getContentDirRel();
            }
            if (!self::$passThroughEnvVarDefinitelyUnavailable) {
                $flags[] = 'E=WPCONTENT:' . Paths::getContentDirRel();
            }

            // TODO: When $rewriteRuleStart is empty, we don't need the .*, do we? - test
            $rewriteRuleStart = '^/?(.+)';
            $rules .= "  RewriteRule " . $rewriteRuleStart . "\.(" . self::$fileExt . ")$ " .
                "/" . Paths::getWodUrlPath() .
                (count($params) > 0 ? "?" . implode('&', $params) : "") .
                " [" . implode(',', $flags) . "]\n";

        } else {

            /*
            Create something like this:

            RewriteCond %{REQUEST_FILENAME} -f
            RewriteRule (?i).*(\.jpe?g|\.png)$ /plugins-moved/webp-express/wod/webp-on-demand.php [E=WE_WP_CONTENT_REL_TO_PLUGIN_DIR:../../we0-content,E=WE_SOURCE_REL_HTACCESS:$0,E=WE_HTACCESS_ID:themes,NC,L]
            */

            // Making sure the source exists
            $rules .= "  RewriteCond %{REQUEST_FILENAME} -f\n";

            $params = [];
            $flags = [];

            if (!self::$passThroughEnvVarDefinitelyUnavailable) {
                $flags[] = 'E=WE_WP_CONTENT_REL_TO_PLUGIN_DIR:' . Paths::getContentDirRelToPluginDir();
                $flags[] = 'E=WE_SOURCE_REL_HTACCESS:$0';
                $flags[] = 'E=WE_HTACCESS_ID:' . self::$htaccessDir;    // this will btw be one of the image roots. It will not be "cache"
            }
            $flags[] = 'NC';  // case-insensitive match (so file extension can be jpg, JPG or even jPg)
            $flags[] = 'L';

            if (!self::$passThroughEnvVarDefinitelyAvailable) {
                $params[] = 'xwp-content-rel-to-plugin-dir=x' . Paths::getContentDirRelToPluginDir();
                $params[] = 'xsource-rel-htaccess=x$0';
                $params[] = 'htaccess-id=' . self::$htaccessDir;
            }

            // self::$appendWebP cannot be used, we need the following in order for
            // it to work for uploads in: Mingled, "Set to WebP", "Image roots".
            // TODO! Will it work for ie theme images?
            // - well, it should, because the script is passed $0. Not matching the ".png" part of the filename
            // only means it is a bit more greedy than it has to
            $appendWebP = !(self::$config['destination-extension'] == 'set');

            $rules .= "  RewriteRule (?i).*" . ($appendWebP ? "(" . self::$fileExtIncludingDot . ")" : "") . "$ " .
                "/" . Paths::getWodUrlPath() .
                (count($params) > 0 ? "?" . implode('&', $params) : "") .
                " [" . implode(',', $flags) . "]\n";


            /*
*/

            /*
            Create something like this (for wp-content):

            # Redirect to existing converted image in cache-dir (if browser supports webp)
            RewriteCond %{HTTP_ACCEPT} image/webp
            RewriteCond %{REQUEST_FILENAME} (?i)(/var/www/webp-express-tests/we0/wp-content-moved/)(.*)(\.jpe?g|\.png)$
            RewriteRule (?i)(.*)(\.jpe?g|\.png)$ /plugins-moved/webp-express/wod/webp-on-demand.php?root-id=wp-content&xsource-rel-to-root-id=%2%3

            PS: Actually, the whole REQUEST_FILENAME could be passed in querystring by adding "&req-fn=%0" to above.
            */
            /*
            $rules .= "  RewriteCond %{REQUEST_FILENAME} (?i)(" .
                self::$htaccessDirAbs . "/)(.*)(" . self::$fileExtIncludingDot . ")$\n";

            $params = [];
            $flags = [];

            if (!self::$passThroughEnvVarDefinitelyUnavailable) {
                $flags[] = 'E=WE_WP_CONTENT_REL_TO_PLUGIN_DIR:' . Paths::getContentDirRelToPluginDir();
                $flags[] = 'E=REQFN:%{REQUEST_FILENAME}';
            }
            $flags[] = 'NC';  // case-insensitive match (so file extension can be jpg, JPG or even jPg)
            $flags[] = 'L';

            $params[] = 'root-id=' . self::$htaccessDir;
            $params[] = 'xsource-rel-to-root-id=x%2' . (self::$appendWebP ? "%3" : "");

            $rules .= "  RewriteRule (?i)(.*)(" . self::$fileExtIncludingDot . ")$ " .
                "/" . Paths::getWodUrlPath() .
                (count($params) > 0 ? "?" . implode('&', $params) : "") .
                " [" . implode(',', $flags) . "]\n";
            */

            /*
            TODO: NO, this will not do on systems that cannot pass through ENV.
            (Or is REQUEST_FILENAME useable at all? If it is, then we could perhaps
            catch the whole %{REQUEST_FILENAME} and pass it in %1)

            $params = [];
            $flags = ['NC', 'L'];

            if ($passRelativePathToSourceInQS) {
                $params[] = 'xsource-rel-to-plugin-dir=x' . self::$htaccessDirRelToDocRoot . '/$1.$2';
            }
            if (!self::$passThroughEnvVarDefinitelyAvailable) {
                $params[] = "xwp-content-rel-to-plugin-dir=x" . Paths::getContentDirRelToPluginDir();
            }

//                $rules .= "  RewriteCond %{REQUEST_FILENAME} -f\n";
            $rules .= "  RewriteCond %{REQUEST_FILENAME} (?i)(" .
                self::$htaccessDirAbs . "/)(.*)(" . self::$fileExtIncludingDot . ")$\n";

            $rules .= "  RewriteRule (?i)(.*)(" . self::$fileExtIncludingDot . ")$ " .
                "/" . Paths::getWodUrlPath() .
                (count($params) > 0 ? "?" . implode('&', $params) : "") .
                " [" . implode(',', $flags) . "]\n";

            //$urlPath = '/' . Paths::getUrlPathById('plugins') . "/%2" . (self::$appendWebP ? "%3" : "") . "\.webp";
            //            $urlPath = '/' . Paths::getUrlPathById(self::$htaccessDir) . "/%2" . (self::$appendWebP ? "%3" : "") . "\.webp";
            //$urlPath = '/' . Paths::getContentUrlPath() . "/webp-express/webp-images/" . self::$htaccessDir . "/%2" . (self::$appendWebP ? "%3" : "") . "\.webp";
            //$rules .= "  RewriteCond %1" . (self::$appendWebP ? "%2" : "") . "\.webp -f\n";
            //$rules .= "  RewriteRule (?i)(.*)(" . self::$fileExtIncludingDot . ")$ " . $urlPath ." [T=image/webp,E=EXISTING:1," . (self::$addVary ? 'E=ADDVARY:1,' : '') . "L]\n\n";
            */





        }


        /*
        $rules .= "  RewriteCond %{REQUEST_FILENAME} (?i)(" . self::$htaccessDirAbs . "/)(.*)(" . self::$fileExtIncludingDot . ")$\n";
        $urlPath = '/' . Paths::getContentUrlPath() . "/webp-express/webp-images/" . self::$htaccessDir . "/%2" . (self::$appendWebP ? "%3" : "") . "\.webp";
        //$rules .= "  RewriteCond %1" . (self::$appendWebP ? "%2" : "") . "\.webp -f\n";
        $rules .= "  RewriteRule (?i)(.*)(" . self::$fileExtIncludingDot . ")$ " . $urlPath .
            " [T=image/webp,E=EXISTING:1," . (self::$addVary ? 'E=ADDVARY:1,' : '') . "L]\n\n";
        */

        /*
        $rules .= "  RewriteCond %{REQUEST_FILENAME} (?i)(.*)(\.jpe?g|\.png)$\n";
        $rules .= "  RewriteCond %1%2\.webp -f\n";
        $rules .= "  RewriteRule (?i)(.*)(\.jpe?g|\.png)$ %1%2\.webp [T=image/webp,E=EXISTING:1,E=ADDVARY:1,L]\n";
        */

        $rules .= "\n";
        return $rules;
    }

    private static function setInternalProperties($config, $htaccessDir = 'index')
    {
        self::$useDocRootForStructuringCacheDir = (
            ($config['destination-structure'] == 'doc-root') &&
            Paths::canUseDocRootForStructuringCacheDir()
        );
        self::$htaccessDir = $htaccessDir;
        self::$htaccessDirAbs = Paths::getAbsDirById(self::$htaccessDir);

        self::$htaccessDirRelToDocRoot = '';
        if (self::$useDocRootForStructuringCacheDir) {
            self::$htaccessDirRelToDocRoot = PathHelper::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed(
                self::$htaccessDirAbs
            );
        }

        // When using the absolute dir, the rewrite rules needs document root and does not work
        // if the symlinks have been resolved.
        // We can fix this - but only if document root is available and resolvable.
        // - which is sad, because the image-roots was introduced in order to get it to work on setups
        // where it isn't.
        self::$htaccessDirAbs = PathHelper::fixAbsPathToUseUnresolvedDocRoot(self::$htaccessDirAbs);

        // Fix config.
        $defaults = [
            'enable-redirection-to-converter' => true,
            'forward-query-string' => true,
            'image-types' => 1,
            'do-not-pass-source-in-query-string' => false,
            'redirect-to-existing-in-htaccess' => false,
            'only-redirect-to-converter-on-cache-miss' => false,
            'destination-folder' => 'separate',
            'destination-extension' => 'append',
            'destination-structure' => 'doc-root',
            'success-response' => 'converted',
        ];
        $config = array_merge($defaults, $config);

        if (!isset($config['base-htaccess-on-these-capability-tests'])) {
            $config['base-htaccess-on-these-capability-tests'] = Config::runAndStoreCapabilityTests();
        }
        self::$config = $config;

        $capTests = self::$config['base-htaccess-on-these-capability-tests'];
        self::$modHeaderDefinitelyUnavailable = ($capTests['modHeaderWorking'] === false);
        self::$passThroughHeaderDefinitelyUnavailable = ($capTests['passThroughHeaderWorking'] === false);
        self::$passThroughHeaderDefinitelyAvailable = ($capTests['passThroughHeaderWorking'] === true);
        self::$passThroughEnvVarDefinitelyUnavailable = ($capTests['passThroughEnvWorking'] === false);
        self::$passThroughEnvVarDefinitelyAvailable =($capTests['passThroughEnvWorking'] === true);
        self::$capTests = $capTests;

        self::$imageTypes = self::$config['image-types'];
        self::$fileExtensions = [];
        if (self::$imageTypes & 1) {
          self::$fileExtensions[] = 'jpe?g';
        }
        if (self::$imageTypes & 2) {
          self::$fileExtensions[] = 'png';
        }
        self::$fileExt = implode('|', self::$fileExtensions);
        self::$fileExtIncludingDot = "\." . implode("|\.", self::$fileExtensions);

        self::$mingled = (self::$config['destination-folder'] == 'mingled');

        // TODO: If we cannot store all .htaccess files we would like, we need to take into account which dir
        $setWebPExt = ((self::$config['destination-extension'] == 'set') && (self::$htaccessDir == 'uploads'));
        self::$appendWebP = !$setWebPExt;
    }

    public static function addVaryHeaderEnvRules($indent = 0)
    {
        $rules = [];
        $rules[] = "# Set Vary:Accept header if we came here by way of our redirect, which set the ADDVARY environment variable";
        $rules[] = "# The purpose is to make proxies and CDNs aware that the response varies with the Accept header";
        $rules[] = "<IfModule mod_headers.c>";
        $rules[] = "  <IfModule mod_setenvif.c>";
        $rules[] = "    # Apache appends \"REDIRECT_\" in front of the environment variables defined in mod_rewrite, but LiteSpeed does not";
        $rules[] = "    # So, the next lines are for Apache, in order to set environment variables without \"REDIRECT_\"";
        $rules[] = "    SetEnvIf REDIRECT_EXISTING 1 EXISTING=1";
        $rules[] = "    SetEnvIf REDIRECT_ADDVARY 1 ADDVARY=1";
        $rules[] = "";
        $rules[] = "    Header append \"Vary\" \"Accept\" env=ADDVARY";
        $rules[] = "";

        if (self::$config['redirect-to-existing-in-htaccess']) {
            $rules[] = "    # Set X-WebP-Express header for diagnose purposes";
                //"  SetEnvIf REDIRECT_WOD 1 WOD=1\n\n" .
                //"  # Set the debug header\n" .
                $rules[] = "    Header set \"X-WebP-Express\" \"Redirected directly to existing webp\" env=EXISTING";
                //"  Header set \"X-WebP-Express\" \"Redirected to image converter\" env=WOD\n" .
        }
        $rules[] = "  </IfModule>";
        $rules[] = "</IfModule>";
        $rules[] = "";

        if ($indent > 0) {
            $indentStr = '';
            for ($x=0; $x<$indent; $x++) {
                $indentStr .= ' ';
            }
            foreach ($rules as $i => $rule) {
                if ($rule != '') {
                    $rules[$i] = $indentStr . $rule;
                }
            }
        }
        return implode("\n", $rules);
    }

    // https://stackoverflow.com/questions/34124819/mod-rewrite-set-custom-header-through-htaccess
    public static function generateHTAccessRulesFromConfigObj($config, $htaccessDir = 'index', $dirContainsSourceImages = true, $dirContainsWebPImages = true)
    {
        self::setInternalProperties($config, $htaccessDir);
        self::$dirContainsSourceImages = $dirContainsSourceImages;
        self::$dirContainsWebPImages = $dirContainsWebPImages;

        if (
            (!self::$config['enable-redirection-to-converter']) &&
            (!self::$config['redirect-to-existing-in-htaccess']) &&
            (!self::$config['enable-redirection-to-webp-realizer'])
        ) {
            return '# WebP Express does not need to write any rules (it has not been set up to redirect to converter, nor' .
                ' to existing webp, and the "convert non-existing webp-files upon request" option has not been enabled)';
        }

        if (self::$imageTypes == 0) {
            return '# WebP Express disabled (no image types has been choosen to be converted/redirected)';
        }

        self::$addVary = self::$config['redirect-to-existing-in-htaccess'];
        if (self::$modHeaderDefinitelyUnavailable) {
            self::$addVary = false;
        }

        /* Build rules */
        $rules = '';
        $rules .= self::infoRules();

        if ($dirContainsSourceImages) {
            $rules .= "# Rules for handling requests for source images\n";
            $rules .= "# ---------------------------------------------\n\n";
            $rules .= "<IfModule mod_rewrite.c>\n" .
                "  RewriteEngine On\n\n";

            if (self::$config['redirect-to-existing-in-htaccess']) {
                $rules .= self::redirectToExistingRules();
            }

            if (self::$config['enable-redirection-to-converter']) {
                $rules .= self::webpOnDemandRules();
            }

            //if (self::$addVary) {
            if (
                (self::$config['redirect-to-existing-in-htaccess']) ||
                (self::$config['enable-redirection-to-converter'])
            ) {
                $rules .= "  # Make sure that browsers which does not support webp also gets the Vary:Accept header\n" .
                    "  # when requesting images that would be redirected to webp on browsers that does.\n";

                $rules .= "  <IfModule mod_headers.c>\n";
                $rules .= '    <FilesMatch "(?i)\.(jpe?g|png)$">' . "\n";
                $rules .= '      Header append "Vary" "Accept"' . "\n";
                $rules .= "    </FilesMatch>\n";
                $rules .= "  </IfModule>\n\n";
            }

            /*
            "  <IfModule mod_setenvif.c>\n" .
            "    SetEnvIf Request_URI \"\.(" . self::$fileExt . ")$\" ADDVARY\n" .
            "  </IfModule>\n\n";
            */

            //self::$addVary = (self::$config['enable-redirection-to-converter'] && (self::$config['success-response'] == 'converted')) || (self::$config['redirect-to-existing-in-htaccess']);

            /*
            if (self::$addVary) {
                if ($dirContainsWebPImages) {
                    $rules .= self::addVaryHeaderEnvRules(2);
                }
            }*/
            $rules .= "</IfModule>\n";
        } /*else {
            if ($dirContainsWebPImages) {
                $rules .= self::addVaryHeaderEnvRules();
            }
        }*/
        if ($dirContainsWebPImages) {
            $rules .= "\n# Rules for handling requests for webp images\n";
            $rules .= "# ---------------------------------------------\n\n";
            if (self::$config['enable-redirection-to-webp-realizer']) {
                $rules .= self::webpRealizerRules();
            }
            $rules .= self::cacheRules();

            /*
            if (
                (self::$config['enable-redirection-to-webp-realizer']) ||
                (self::$config['redirect-to-existing-in-htaccess'])
            ) {
            }*/
            $rules .= self::addVaryHeaderEnvRules();

            $rules .= "\n# Register webp mime type \n";
            $rules .= "<IfModule mod_mime.c>\n";
            $rules .= "  AddType image/webp .webp\n";
            $rules .= "</IfModule>\n";
        }

        /*if (self::$config['redirect-to-existing-in-htaccess']) {
            $rules .=
            "<IfModule mod_headers.c>\n" .
                "  # Append Vary Accept header, when the rules above are redirecting to existing webp\n" .
                "  # or existing jpg" .

                "  # Apache appends \"REDIRECT_\" in front of the environment variables, but LiteSpeed does not.\n" .
                "  # These next line is for Apache, in order to set environment variables without \"REDIRECT_\"\n" .
                "  SetEnvIf REDIRECT_WEBPACCEPT 1 WEBPACCEPT=1\n\n" .

                "  # Make CDN caching possible.\n" .
                "  # The effect is that the CDN will cache both the webp image and the jpeg/png image and return the proper\n" .
                "  # image to the proper clients (for this to work, make sure to set up CDN to forward the \"Accept\" header)\n" .
                "  Header append Vary Accept env=WEBPACCEPT\n" .
            "</IfModule>\n\n";
        }*/

        return $rules;
    }
}
