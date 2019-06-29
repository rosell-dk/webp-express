<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\FileHelper;
use \WebPExpress\Paths;
use \WebPExpress\State;

class HTAccess
{
    // (called from this file only. BUT our saveRules methods calls it, and it is called from several classes)
    public static function generateHTAccessRulesFromConfigObj($config, $htaccessDir = 'index')
    {
        // Any option that is newer than ~v.0.2 may not be set yet.
        // So, in order to not have to use isset() all over the place, set to values
        // that results in same behaviour as before the option was introduced.
        // Beware that this may not be same as the default value in the UI (but it is generally)

        // TODO: can we use the new fix method instead?

        $defaults = [
            'enable-redirection-to-converter' => true,
            'forward-query-string' => true,
            'image-types' => 1,
            'do-not-pass-source-in-query-string' => false,
            'redirect-to-existing-in-htaccess' => false,
            'only-redirect-to-converter-on-cache-miss' => false,
            'destination-folder' => 'separate',
            'destination-extension' => 'append',
            'success-response' => 'converted',
        ];
        $config = array_merge($defaults, $config);

        if ((!$config['enable-redirection-to-converter']) && (!$config['redirect-to-existing-in-htaccess']) && (!$config['enable-redirection-to-webp-realizer'])) {
            return '# WebP Express does not need to write any rules (it has not been set up to redirect to converter, nor to existing webp, and the "convert non-existing webp-files upon request" option has not been enabled)';
        }



        if (isset($config['base-htaccess-on-these-capability-tests'])) {
            $capTests = $config['base-htaccess-on-these-capability-tests'];
            $modHeaderDefinitelyUnavailable = ($capTests['modHeaderWorking'] === false);
            $passThroughHeaderDefinitelyUnavailable = ($capTests['passThroughHeaderWorking'] === false);
            $passThroughHeaderDefinitelyAavailable = ($capTests['passThroughHeaderWorking'] === true);
            $passThrougEnvVarDefinitelyUnavailable = ($capTests['passThroughEnvWorking'] === false);
            $passThrougEnvVarDefinitelyAvailable =($capTests['passThroughEnvWorking'] === true);
        } else {
            $modHeaderDefinitelyUnavailable = false;
            $passThroughHeaderDefinitelyUnavailable = false;
            $passThroughHeaderDefinitelyAavailable = false;
            $passThrougEnvVarDefinitelyUnavailable = false;
            $passThrougEnvVarDefinitelyAvailable = false;
        }

        $setEnvVar = !$passThrougEnvVarDefinitelyUnavailable;
        $passFullFilePathInQS = false;
        $passRelativeFilePathInQS = !($passThrougEnvVarDefinitelyAvailable || $passThroughHeaderDefinitelyAavailable);
        $passFullFilePathInQSRealizer = false;
        $passRelativeFilePathInQSRealizer = $passRelativeFilePathInQS;


        $addVary = $config['redirect-to-existing-in-htaccess'];
        if ($modHeaderDefinitelyUnavailable) {
            $addVary = false;
        }


        /* Calculate $fileExt */
        $imageTypes = $config['image-types'];
        $fileExtensions = [];
        if ($imageTypes & 1) {
          $fileExtensions[] = 'jpe?g';
        }
        if ($imageTypes & 2) {
          $fileExtensions[] = 'png';
        }
        $fileExt = implode('|', $fileExtensions);

        if ($imageTypes == 0) {
            return '# WebP Express disabled (no image types have been choosen to be converted/redirected)';
        }


        // Build cache control rules
        $ccRules = '';
        $cacheControlHeader = Config::getCacheControlHeader($config);
        if ($cacheControlHeader != '') {

            if ($config['redirect-to-existing-in-htaccess']) {
                $ccRules .= "  # Set Cache-Control header so these direct redirections also get the header set\n";
                if ($config['enable-redirection-to-webp-realizer']) {
                    $ccRules .= "  # (and also webp-realizer.php)\n";
                }
            } else {
                if ($config['enable-redirection-to-webp-realizer']) {
                    $ccRules .= "  # Set Cache-Control header for requests to webp images\n";
                }
            }
            $ccRules .= "  <IfModule mod_headers.c>\n";
            $ccRules .= "    <FilesMatch \"\.webp$\">\n";
            $ccRules .= "      Header set Cache-Control \"" . $cacheControlHeader . "\"\n";
            $ccRules .= "    </FilesMatch>\n";
            $ccRules .= "  </IfModule>\n\n";

            // Fall back to mod_expires if mod_headers is unavailable



            if ($modHeaderDefinitelyUnavailable) {
                $cacheControl = $config['cache-control'];

                if ($cacheControl == 'custom') {
                    $expires = '';

                    // Do not add Expire header if private is set
                    // - because then the user don't want caching in proxies / CDNs.
                    //   the Expires header doesn't differentiate between private/public
                    if (!(preg_match('/private/', $config['cache-control-custom']))) {
                        if (preg_match('/max-age=(\d+)/', $config['cache-control-custom'], $matches)) {
                            if (isset($matches[1])) {
                                $expires = $matches[1] . ' seconds';
                            }
                        }
                    }

                } elseif ($cacheControl == 'no-header') {
                    $expires = '';
                } elseif ($cacheControl == 'set') {
                    if ($config['cache-control-public']) {
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
                        $expires = $cacheControlOptions[$config['cache-control-max-age']];
                    }
                }

                if ($expires != '') {
                    // in case mod_headers is missing, try mod_expires
                    $ccRules .= "  # Fall back to mod_expires if mod_headers is unavailable\n";
                    $ccRules .= "  <IfModule !mod_headers.c>\n";
                    $ccRules .= "    <IfModule mod_expires.c>\n";
                    $ccRules .= "      ExpiresActive On\n";
                    $ccRules .= "      ExpiresByType image/webp \"access plus " . $expires . "\"\n";
                    $ccRules .= "    </IfModule>\n";
                    $ccRules .= "  </IfModule>\n\n";
                }
            }
        }


        /* Build rules */

        /* When .htaccess is placed in root (index), the rules needs to be confined only to work in
           content folder (if uploads folder is moved, perhaps also that)
           Rules needs to start with ie "^/?(wp-content/.+)" rather than "^/?(.+)"
           In the case that upload folder is in root too, rules needs to apply to both.
           We do it like this: "^/?((?:wp-content|uploads)/.+)"   (using non capturing group)
        */

        $rewriteRuleStart = '^/?(.+)';
        if ($htaccessDir == 'index') {
            // Get relative path between index dir and wp-content dir / uploads
            // Because we want to restrict the rule so it doesn't work on wp-admin, but only those two.

            $wpContentRel = PathHelper::getRelDir(Paths::getIndexDirAbs(), Paths::getContentDirAbs());
            $uploadsRel = PathHelper::getRelDir(Paths::getIndexDirAbs(), Paths::getUploadDirAbs());

            //$rules .= '# rel: ' . $uploadsRel . "\n";

            if (strpos($wpContentRel, '.') !== 0) {

                if (strpos($uploadsRel, $wpContentRel) === 0) {
                    $rewriteRuleStart = '^/?(' . $wpContentRel . '/.+)';
                } else {
                    $rewriteRuleStart = '^/?((?:' . $wpContentRel . '|' . $uploadsRel . '/.+)';
                }
            }
        }

        $rules = '';


        /*
        // The next line sets an environment variable.
        // On the options page, we verify if this is set to diagnose if "AllowOverride None" is presented in 'httpd.conf'
        //$rules .= "# The following SetEnv allows to diagnose if .htaccess files are turned off\n";
        //$rules .= "SetEnv HTACCESS on\n\n";
        */
        $rules .= "# The rules below are a result of the WebP Express options, Wordpress configuration and the following .htaccess capability tests:\n" .
            "# - mod_header working?: " . ($capTests['modHeaderWorking'] === true ? 'yes' : ($capTests['modHeaderWorking'] === false ? 'no' : 'could not be determined')) . "\n" .
            "# - pass variable from .htaccess to script through header working?: " . ($capTests['passThroughHeaderWorking'] === true ? 'yes' : ($capTests['passThroughHeaderWorking'] === false ? 'no' : 'could not be determined')) . "\n" .
            "# - pass variable from .htaccess to script through environment variable working?: " . ($capTests['passThroughEnvWorking'] === true ? 'yes' : ($capTests['passThroughEnvWorking'] === false ? 'no' : 'could not be determined')) . "\n";

        $rules .= "<IfModule mod_rewrite.c>\n" .
        "  RewriteEngine On\n\n";

        $cacheDirRel = Paths::getCacheDirRel() . '/doc-root';

        $htaccessDirRel = '';
        switch ($htaccessDir) {
            case 'index':
                $htaccessDirRel = Paths::getIndexDirRel();
                break;
            case 'home':
                $htaccessDirRel = Paths::getHomeDirRel();
                break;
            case 'plugin':
                $htaccessDirRel = Paths::getPluginDirRel();
                break;
            case 'uploads':
                $htaccessDirRel = Paths::getUploadDirRel();
                break;
            case 'wp-content':
                $htaccessDirRel = Paths::getContentDirRel();
                break;
        }


        // TODO: Is it possible to handle when wp-content is outside document root?

        // TODO: It seems $pathToExisting needs to be adjusted, depending on where the .htaccess is located
        // Ie, if plugin folder has been moved out of ABSPATH, we should ie set
        // $pathToExisting to 'doc-root/plugins-moved/'
        // to get: RewriteRule ^\/?(.*)\.(jpe?g)$ /wp-content-moved/webp-express/webp-images/doc-root/plugins-moved/$1.$2.webp [NC,T=image/webp,E=WEBPACCEPT:1,E=EXISTING:1,L]

        // https://stackoverflow.com/questions/34124819/mod-rewrite-set-custom-header-through-htaccess
        $mingled = ($config['destination-folder'] == 'mingled');

        if ($config['redirect-to-existing-in-htaccess']) {
            if ($mingled) {
                $rules .= "  # Redirect to existing converted image in same dir (if browser supports webp)\n";
                $rules .= "  RewriteCond %{HTTP_ACCEPT} image/webp\n";

                if ($config['destination-extension'] == 'append') {
                    $rules .= "  RewriteCond %{DOCUMENT_ROOT}/" . $htaccessDirRel . "/$1.$2.webp -f\n";
                    $rules .= "  RewriteRule " . $rewriteRuleStart . "\.(" . $fileExt . ")$ $1.$2.webp [T=image/webp,E=EXISTING:1," . ($addVary ? 'E=ADDVARY:1,' : '') . "L]\n\n";
                } else {
                    $rules .= "  RewriteCond %{DOCUMENT_ROOT}/" . $htaccessDirRel . "/$1.webp -f\n";
                    $rules .= "  RewriteRule " . $rewriteRuleStart . "\.(" . $fileExt . ")$ $1.webp [T=image/webp,E=EXISTING:1," . ($addVary ? 'E=ADDVARY:1,' : '') . "L]\n\n";
                    //$rules .= "  RewriteRule ^(.+)\.(" . $fileExt . ")$ $1.webp [T=image/webp,E=EXISTING:1,L]\n\n";
                }
            }

            $rules .= "  # Redirect to existing converted image in cache-dir (if browser supports webp)\n";
            $rules .= "  RewriteCond %{HTTP_ACCEPT} image/webp\n";
            $rules .= "  RewriteCond %{REQUEST_FILENAME} -f\n";
            $rules .= "  RewriteCond %{DOCUMENT_ROOT}/" . $cacheDirRel . "/" . $htaccessDirRel . "/$1.$2.webp -f\n";
            $rules .= "  RewriteRule " . $rewriteRuleStart . "\.(" . $fileExt . ")$ /" . $cacheDirRel . "/" . $htaccessDirRel . "/$1.$2.webp [NC,T=image/webp,E=EXISTING:1,L]\n\n";
            //$rules .= "  RewriteRule ^\/?(.*)\.(" . $fileExt . ")$ /" . $cacheDirRel . "/" . $htaccessDirRel . "/$1.$2.webp [NC,T=image/webp,E=EXISTING:1,L]\n\n";

            if ($addVary) {
                $rules .= "  # Make sure that browsers which does not support webp also gets the Vary:Accept header\n" .
                    "  # when requesting images that would be redirected to existing webp on browsers that does.\n" .
                    "  <IfModule mod_setenvif.c>\n" .
                    "    SetEnvIf Request_URI \"\.(" . $fileExt . ")$\" ADDVARY\n" .
                    "  </IfModule>\n\n";
            }

            $rules .= $ccRules;

        }

        // Do not add header magic if passing through env is definitely working
        // Do not add either, if we definitily know it isn't working
        if ((!$passThrougEnvVarDefinitelyAvailable) && (!$passThroughHeaderDefinitelyUnavailable)) {
            if ($config['enable-redirection-to-converter']) {
                $rules .= "  # Pass REQUEST_FILENAME to webp-on-demand.php in request header\n";
                //$rules .= $basicConditions;
                //$rules .= "  RewriteRule ^(.*)\.(" . $fileExt . ")$ - [E=REQFN:%{REQUEST_FILENAME}]\n" .
                $rules .= "  <IfModule mod_headers.c>\n" .
                    "    RequestHeader set REQFN \"%{REQFN}e\" env=REQFN\n" .
                    "  </IfModule>\n\n";

            }
            if ($config['enable-redirection-to-webp-realizer']) {
                // We haven't implemented a clever way to pass through header for webp-realizer yet
            }
        }

        if ($config['enable-redirection-to-webp-realizer']) {
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

            $basicConditionsRealizer = '';
            $basicConditionsRealizer .= "  RewriteCond %{REQUEST_FILENAME} !-f\n";
            if ($mingled) {
                if ($config['destination-extension'] == 'append') {
                    $basicConditionsRealizer .= "  RewriteCond %{DOCUMENT_ROOT}/" . $htaccessDirRel . "/$1 -f\n";
                } else {
                    //$basicConditionsRealizer .= "  RewriteCond %{DOCUMENT_ROOT}/" . $htaccessDirRel . "/$1.webp !-f\n";
                }
            } else {
                //$basicConditionsRealizer .= "  RewriteCond %{DOCUMENT_ROOT}/" . $cacheDirRel . "/" . $htaccessDirRel . "/$1.$2.webp !-f\n";
            }

            $rules .= "  # WebP Realizer: Redirect non-existing webp images to webp-realizer.php, which will locate corresponding jpg/png, convert it, and deliver the webp (if possible) \n";
            $rules .= $basicConditionsRealizer;

            /*
            $rules .= "  RewriteRule " . $rewriteRuleStart . "\.(webp)$ " .
                "/" . Paths::getWebPRealizerUrlPath() .
                ($passFullFilePathInQS ? "?xdestination=x%{SCRIPT_FILENAME}&" : "?") .
                "wp-content=" . Paths::getContentDirRel() .
                " [" . ($setEnvVar ? ('E=REQFN:%{REQUEST_FILENAME}' . ','): '') . "NC,L]\n\n";        // E=WOD:1
            */
            $params = [];
            if ($passFullFilePathInQSRealizer) {
                $params[] = 'xdestination=x%{SCRIPT_FILENAME}';
            } elseif ($passRelativeFilePathInQSRealizer) {
                $params[] = 'xdestination-rel=x' . $htaccessDirRel . '/$1.$2';
            }
            if (!$passThrougEnvVarDefinitelyAvailable) {
                $params[] = "wp-content=" . Paths::getContentDirRel();
            }

            // TODO: When $rewriteRuleStart is empty, we don't need the .*, do we? - test
            $rules .= "  RewriteRule " . $rewriteRuleStart . "\.(webp)$ " .
                "/" . Paths::getWebPRealizerUrlPath() .
                ((count($params) > 0) ?  "?" . implode('&', $params) : '') .
                " [" . ($setEnvVar ? ('E=DESTINATIONREL:' . $htaccessDirRel . '/$0' . ','): '') . (!$passThrougEnvVarDefinitelyUnavailable ? 'E=WPCONTENT:' . Paths::getContentDirRel() . ',' : '') . "NC,L]\n\n";        // E=WOD:1


            if (!$config['redirect-to-existing-in-htaccess']) {
                $rules .= $ccRules;
            }
        }

        if ($config['enable-redirection-to-converter']) {
            $basicConditions = '';
            if ($config['only-redirect-to-converter-for-webp-enabled-browsers']) {
                $basicConditions = "  RewriteCond %{HTTP_ACCEPT} image/webp\n";
            }
            $basicConditions .= "  RewriteCond %{REQUEST_FILENAME} -f\n";
            if ($config['only-redirect-to-converter-on-cache-miss']) {
                if ($mingled) {
                    if ($config['destination-extension'] == 'append') {
                        $basicConditions .= "  RewriteCond %{DOCUMENT_ROOT}/" . $htaccessDirRel . "/$1.$2.webp !-f\n";
                    } else {
                        $basicConditions .= "  RewriteCond %{DOCUMENT_ROOT}/" . $htaccessDirRel . "/$1.webp !-f\n";
                    }
                } else {
                    $basicConditions .= "  RewriteCond %{DOCUMENT_ROOT}/" . $cacheDirRel . "/" . $htaccessDirRel . "/$1.$2.webp !-f\n";
                }
            }

            $rules .= "  # Redirect images to webp-on-demand.php ";
            if ($config['only-redirect-to-converter-for-webp-enabled-browsers']) {
                $rules .= "(if browser supports webp)\n";
            } else {
                $rules .= "(regardless whether browser supports webp or not!)\n";
            }
            if ($config['only-redirect-to-converter-on-cache-miss']) {
                $rules .= "  # - but only, when no existing converted image is found\n";
            }
            $rules .= $basicConditions;

            if ($config['forward-query-string']) {
                $rules .= "  RewriteCond %{QUERY_STRING} (.*)\n";
            }
            /*
            if ($config['forward-query-string']) {
            }*/


            // TODO:
            // Add "NE" flag?
            // https://github.com/rosell-dk/webp-convert/issues/95
            // (and try testing spaces in directory paths)

            $params = [];
            if ($passFullFilePathInQS) {
                $params[] = 'xsource=x%{SCRIPT_FILENAME}';
            } elseif ($passRelativeFilePathInQS) {
                $params[] = 'xsource-rel=x' . $htaccessDirRel . '/$1.$2';
            }
            if (!$passThrougEnvVarDefinitelyAvailable) {
                $params[] = "wp-content=" . Paths::getContentDirRel();
            }
            if ($config['forward-query-string']) {
                $params[] = '%1';
            }

            // TODO: When $rewriteRuleStart is empty, we don't need the .*, do we? - test
            $rules .= "  RewriteRule " . $rewriteRuleStart . "\.(" . $fileExt . ")$ " .
                "/" . Paths::getWodUrlPath() .
                "?" . implode('&', $params) .
                " [" . ($setEnvVar ? ('E=REQFN:%{REQUEST_FILENAME},'): '') . (!$passThrougEnvVarDefinitelyUnavailable ? 'E=WPCONTENT:' . Paths::getContentDirRel() . ',' : '') . "NC,L]\n";        // E=WOD:1

            $rules .= "\n";
        }

        //$addVary = ($config['enable-redirection-to-converter'] && ($config['success-response'] == 'converted')) || ($config['redirect-to-existing-in-htaccess']);

        if ($addVary) {
            $rules .= "  <IfModule mod_headers.c>\n";
            $rules .= "    <IfModule mod_setenvif.c>\n";

            $rules .= "      # Apache appends \"REDIRECT_\" in front of the environment variables defined in mod_rewrite, but LiteSpeed does not.\n" .
                "      # So, the next lines are for Apache, in order to set environment variables without \"REDIRECT_\"\n" .
                "      SetEnvIf REDIRECT_EXISTING 1 EXISTING=1\n" .
                "      SetEnvIf REDIRECT_ADDVARY 1 ADDVARY=1\n\n";

            $rules .= "      # Set Vary:Accept header for the image types handled by WebP Express.\n" .
                "      # The purpose is to make proxies and CDNs aware that the response varies with the Accept header. \n" .
                "      Header append \"Vary\" \"Accept\" env=ADDVARY\n\n";

            if ($config['redirect-to-existing-in-htaccess']) {
                $rules .= "      # Set X-WebP-Express header for diagnose purposes\n" .
                    //"  SetEnvIf REDIRECT_WOD 1 WOD=1\n\n" .
                    //"  # Set the debug header\n" .
                    "      Header set \"X-WebP-Express\" \"Redirected directly to existing webp\" env=EXISTING\n";
                    //"  Header set \"X-WebP-Express\" \"Redirected to image converter\" env=WOD\n" .
            }
            $rules .= "    </IfModule>\n" .
            "  </IfModule>\n\n";
        }
        $rules .="</IfModule>\n";

        /*if ($config['redirect-to-existing-in-htaccess']) {
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

        $rules .= "<IfModule mod_mime.c>\n";
        $rules .= "  AddType image/webp .webp\n";
        $rules .= "</IfModule>\n";
        return $rules;
    }

    /* only called from page-messages.inc, but commented out there... */
    public static function generateHTAccessRulesFromConfigFile($htaccessDir = '') {
        if (Config::isConfigFileThereAndOk()) {
            return self::generateHTAccessRulesFromConfigObj(Config::loadConfig(), $htaccessDir);
        } else {
            return false;
        }
    }

    public static function arePathsUsedInHTAccessOutdated() {
        if (!Config::isConfigFileThere()) {
            // this properly means that rewrite rules have never been generated
            return false;
        }

        $pathsGoingToBeUsedInHtaccess = [
            'existing' => Paths::getPathToExisting(),
            'wod-url-path' => Paths::getWodUrlPath(),
            'config-dir-rel' => Paths::getConfigDirRel()
        ];

        $config = Config::loadConfig();
        if ($config === false) {
            // corrupt or not readable
            return true;
        }

        foreach ($config['paths-used-in-htaccess'] as $prop => $value) {
            if ($value != $pathsGoingToBeUsedInHtaccess[$prop]) {
                return true;
            }
        }
    }

    public static function doesRewriteRulesNeedUpdate($newConfig) {
        if (!Config::isConfigFileThere()) {
            // this properly means that rewrite rules have never been generated
            return true;
        }

        $oldConfig = Config::loadConfig();
        if ($oldConfig === false) {
            // corrupt or not readable
            return true;
        }

        $propsToCompare = [
            'forward-query-string' => true,
            'image-types' => 1,
            'redirect-to-existing-in-htaccess' => false,
            'only-redirect-to-converter-on-cache-miss' => false,
            'success-response' => 'converted',
            'cache-control' => 'no-header',
            'cache-control-custom' => 'public, max-age:3600',
            'cache-control-max-age' => 'one-week',
            'cache-control-public' => true,
            'enable-redirection-to-webp-realizer' => false,
            'enable-redirection-to-converter' => true
        ];

        if (isset($newConfig['redirect-to-existing-in-htaccess']) && $newConfig['redirect-to-existing-in-htaccess']) {
            $propsToCompare['destination-folder'] = 'separate';
            $propsToCompare['destination-extension'] = 'append';
        }

        foreach ($propsToCompare as $prop => $behaviourBeforeIntroduced) {
            if (!isset($newConfig[$prop])) {
                continue;
            }
            if (!isset($oldConfig[$prop])) {
                // Do not trigger .htaccess update if the new value results
                // in same old behaviour (before this option was introduced)
                if ($newConfig[$prop] == $behaviourBeforeIntroduced) {
                    continue;
                } else {
                    // Otherwise DO trigger .htaccess update
                    return true;
                }
            }
            if ($newConfig[$prop] != $oldConfig[$prop]) {
                return true;
            }
        }

        if (!isset($oldConfig['paths-used-in-htaccess'])) {
            return true;
        }

        return self::arePathsUsedInHTAccessOutdated();
    }

    /**
     *  Must be parsed ie "wp-content", "index", etc. Not real dirs
     */
    public static function addToActiveHTAccessDirsArray($dirId)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        if (!in_array($dirId, $activeHtaccessDirs)) {
            $activeHtaccessDirs[] = $dirId;
            State::setState('active-htaccess-dirs', array_values($activeHtaccessDirs));
        }
    }

    public static function removeFromActiveHTAccessDirsArray($dirId)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        if (in_array($dirId, $activeHtaccessDirs)) {
            $activeHtaccessDirs = array_diff($activeHtaccessDirs, [$dirId]);
            State::setState('active-htaccess-dirs', array_values($activeHtaccessDirs));
        }
    }

    public static function isInActiveHTAccessDirsArray($dirId)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        return (in_array($dirId, $activeHtaccessDirs));
    }

    public static function hasRecordOfSavingHTAccessToDir($dir) {
        $dirId = Paths::getAbsDirId($dir);
        if ($dirId !== false) {
            return self::isInActiveHTAccessDirsArray($dirId);
        }
        return false;
    }


    /**
     *  Sneak peak into .htaccess to see if we have rules in it
     *  This may not be possible (it requires read permission)
     *  Return true, false, or null if we just can't tell
     */
    public static function haveWeRulesInThisHTAccess($filename) {
        if (FileHelper::fileExists($filename)) {
            $content = FileHelper::loadFile($filename);
            if ($content === false) {
                return null;
            }

            $pos1 = strpos($content, '# BEGIN WebP Express');
            if ($pos1 === false) {
                return false;
            }
            $pos2 = strrpos($content, '# END WebP Express');
            if ($pos2 === false) {
                return false;
            }

            $weRules = substr($content, $pos1, $pos2 - $pos1);

            return (strpos($weRules, '<IfModule mod_rewrite.c>') !== false);

        } else {
            // the .htaccess isn't even there. So there are no rules.
            return false;
        }
    }

    public static function haveWeRulesInThisHTAccessBestGuess($filename)
    {
        // First try to sneak peak. May return null if it cannot be determined.
        $result = self::haveWeRulesInThisHTAccess($filename);
        if ($result === true) {
            return true;
        }
        if ($result === null) {
            // We were not allowed to sneak-peak.
            // Well, good thing that we stored successful .htaccess write locations ;)
            // If we recorded a successful write, then we assume there are still rules there
            // If we did not, we assume there are no rules there
            $dir = FileHelper::dirName($filename);
            return self::hasRecordOfSavingHTAccessToDir($dir);
        }
    }

    public static function saveHTAccessRulesToFile($filename, $rules, $createIfMissing = false) {
        if (!@file_exists($filename)) {
            if (!$createIfMissing) {
                return false;
            }
            // insert_with_markers will create file if it doesn't exist, so we can continue...
        }

        $existingFilePermission = null;
        $existingDirPermission = null;

        // Try to make .htaccess writable if its not
        if (@file_exists($filename)) {
            if (!@is_writable($filename)) {
                $existingFilePermission = FileHelper::filePerm($filename);
                @chmod($filename, 0664);        // chmod may fail, we know...
            }
        } else {
            $dir = FileHelper::dirName($filename);
            if (!@is_writable($dir)) {
                $existingDirPermission = FileHelper::filePerm($dir);
                @chmod($dir, 0775);
            }
        }

        /* Add rules to .htaccess  */
        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        // Convert to array, because string version has bugs in Wordpress 4.3
        $rules = explode("\n", $rules);
        $success = insert_with_markers($filename, 'WebP Express', $rules);

        // Revert file or dir permissions
        if (!is_null($existingFilePermission)) {
            @chmod($filename, $existingFilePermission);
        }
        if (!is_null($existingDirPermission)) {
            @chmod($dir, $existingDirPermission);
        }

        if ($success) {
            State::setState('htaccess-rules-saved-at-some-point', true);

            //$containsRules = (strpos(implode('',$rules), '# Redirect images to webp-on-demand.php') != false);
            $containsRules = (strpos(implode('',$rules), '<IfModule mod_rewrite.c>') !== false);

            $dir = FileHelper::dirName($filename);
            $dirId = Paths::getAbsDirId($dir);
            if ($dirId !== false) {
                if ($containsRules) {
                    self::addToActiveHTAccessDirsArray($dirId);
                } else {
                    self::removeFromActiveHTAccessDirsArray($dirId);
                }
            }
        }

        return $success;
    }

    /* only called in this file */
    public static function saveHTAccessRulesToFirstWritableHTAccessDir($dirs, $rules)
    {
        foreach ($dirs as $dir) {
            if (self::saveHTAccessRulesToFile($dir . '/.htaccess', $rules, true)) {
                return $dir;
            }
        }
        return false;
    }


    /**
     *  Try to deactivate all .htaccess rules.
     *  If success, we return true.
     *  If we fail, we return an array of filenames that have problems
     */
    public static function deactivateHTAccessRules() {
        //return self::saveHTAccessRules('# Plugin is deactivated');
        $indexDir = Paths::getIndexDirAbs();
        $homeDir = Paths::getHomeDirAbs();
        $wpContentDir = Paths::getContentDirAbs();
        $pluginDir = Paths::getPluginDirAbs();
        $uploadDir = Paths::getUploadDirAbs();

        $dirsToClean = [$indexDir, $homeDir, $wpContentDir, $pluginDir, $uploadDir];

        $failures = [];

        foreach ($dirsToClean as $dir) {
            $filename = $dir . '/.htaccess';
            if (!FileHelper::fileExists($filename)) {
                continue;
            } else {
                if (self::haveWeRulesInThisHTAccessBestGuess($filename)) {
                    if (!self::saveHTAccessRulesToFile($filename, '# Plugin is deactivated', false)) {
                        $failures[] = $filename;
                    }
                }
            }
        }
        if (count($failures) == 0) {
            return true;
        }
        return $failures;
    }

    public static function testLinks($config) {
        if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
            if ($config['operation-mode'] != 'no-conversion') {
                if ($config['image-types'] != 0) {
                    $webpExpressRoot = Paths::getPluginUrlPath();
                    $links = '';
                    if ($config['enable-redirection-to-converter']) {
                        $links = '<br>';
                        $links .= '<a href="/' . $webpExpressRoot . '/test/test.jpg?debug&time=' . time() . '" target="_blank">Convert test image (show debug)</a><br>';
                        $links .= '<a href="/' . $webpExpressRoot . '/test/test.jpg?' . time() . '" target="_blank">Convert test image</a><br>';
                    }
                    // TODO: webp-realizer test links (to missing webp)
                    if ($config['enable-redirection-to-webp-realizer']) {
                    }

                    // TODO: test link for testing redirection to existing
                    if ($config['redirect-to-existing-in-htaccess']) {

                    }

                    return $links;
                }
            }
        }
        return '';
    }


    public static function getHTAccessDirRequirements() {
        $minRequired = 'index';
        if (Paths::isWPContentDirMovedOutOfAbsPath()) {
            $minRequired = 'wp-content';
            $pluginToo = Paths::isPluginDirMovedOutOfWpContent() ? 'yes' : 'no';
            $uploadToo = Paths::isUploadDirMovedOutOfWPContentDir() ? 'yes' : 'no';
        } else {
            // plugin requirement depends...
            // - if user grants access to 'index', the requirement is Paths::isPluginDirMovedOutOfAbsPath()
            // - if user grants access to 'wp-content', the requirement is Paths::isPluginDirMovedOutOfWpContent()
            $pluginToo = 'depends';

            // plugin requirement depends...
            // - if user grants access to 'index', we should be fine, as UPLOADS is always in ABSPATH.
            // - if user grants access to 'wp-content', the requirement is Paths::isUploadDirMovedOutOfWPContentDir()
            $uploadToo = 'depends';
        }

        return [
            $minRequired,
            $pluginToo,      // 'yes', 'no' or 'depends'
            $uploadToo
        ];
    }

    /**
     *  Try to save the rules.
     *  Returns many details
     *  (called from migrate1.php, reactivate.php, Config.php and this file)
     */
    public static function saveRules($config) {


        list($minRequired, $pluginToo, $uploadToo) = self::getHTAccessDirRequirements();

        $rules = HTAccess::generateHTAccessRulesFromConfigObj($config, 'wp-content');
        $wpContentDir = Paths::getContentDirAbs();
        $wpContentFailed = !(HTAccess::saveHTAccessRulesToFile($wpContentDir . '/.htaccess', $rules, true));

        $overidingRulesInWpContentWarning = false;
        if ($wpContentFailed) {
            if ($minRequired == 'index') {
                $rules = HTAccess::generateHTAccessRulesFromConfigObj($config, 'index');
                $indexFailed = !(HTAccess::saveHTAccessRulesToFile(Paths::getIndexDirAbs() . '/.htaccess', $rules, true));

                if ($indexFailed) {
                    $mainResult = 'failed';
                } else {
                    $mainResult = 'index';
                    $overidingRulesInWpContentWarning = self::haveWeRulesInThisHTAccessBestGuess($wpContentDir . '/.htaccess');
                }
            }
        } else {
            $mainResult = 'wp-content';
            // TODO: Change to something like "The rules are placed in the .htaccess file in your wp-content dir."
            //       BUT! - current text is searched for in page-messages.php
            HTAccess::saveHTAccessRulesToFile(Paths::getIndexDirAbs() . '/.htaccess', '# WebP Express has placed its rules in your wp-content dir. Go there.', false);
        }

        /* plugin */
        if ($pluginToo == 'depends') {
            if ($mainResult == 'wp-content') {
                $pluginToo = (Paths::isPluginDirMovedOutOfWpContent() ? 'yes' : 'no');
            } elseif ($mainResult == 'index') {
                $pluginToo = (Paths::isPluginDirMovedOutOfAbsPath() ? 'yes' : 'no');
            } else {
                // $result must be false. So $pluginToo should still be 'depends'
            }
        }
        $pluginFailed = false;
        $pluginFailedBadly = true;
        if ($pluginToo == 'yes') {
            $rules = HTAccess::generateHTAccessRulesFromConfigObj($config, 'plugin');
            $pluginDir = Paths::getPluginDirAbs();
            $pluginFailed = !(HTAccess::saveHTAccessRulesToFile($pluginDir . '/.htaccess', $rules, true));
            if ($pluginFailed) {
                $pluginFailedBadly = self::haveWeRulesInThisHTAccessBestGuess($pluginDir . '/.htaccess');
            }
        }

        /* upload */
        if ($uploadToo == 'depends') {
            if ($mainResult == 'wp-content') {
                $uploadToo = (Paths::isUploadDirMovedOutOfWPContentDir() ? 'yes' : 'no');
            } elseif ($mainResult == 'index') {
                $uploadToo = (Paths::isUploadDirMovedOutOfAbsPath() ? 'yes' : 'no');
            } else {
                // $result must be false. So $uploadToo should still be 'depends'
            }
        }
        $uploadFailed = false;
        $uploadFailedBadly = true;
        if ($uploadToo == 'yes') {
            $uploadDir = Paths::getUploadDirAbs();
            $rules = HTAccess::generateHTAccessRulesFromConfigObj($config, 'uploads');
            $uploadFailed = !(HTAccess::saveHTAccessRulesToFile($uploadDir . '/.htaccess', $rules, true));
            if ($uploadFailed) {
                $uploadFailedBadly = self::haveWeRulesInThisHTAccessBestGuess($uploadDir . '/.htaccess');
            }
        }

        return [
            'mainResult' => $mainResult,                // 'index', 'wp-content' or 'failed'
            'minRequired' => $minRequired,              // 'index' or 'wp-content'
            'overidingRulesInWpContentWarning' => $overidingRulesInWpContentWarning,  // true if main result is 'index' but we cannot remove those in wp-content
            'rules' => $rules,                          // The rules we generated
            'pluginToo' => $pluginToo,                  // 'yes', 'no' or 'depends'
            'pluginFailed' => $pluginFailed,            // true if failed to write to plugin folder (it only tries that, if pluginToo == 'yes')
            'pluginFailedBadly' => $pluginFailedBadly,  // true if plugin failed AND it seems we have rewrite rules there
            'uploadToo' => $uploadToo,                  // 'yes', 'no' or 'depends'
            'uploadFailed' => $uploadFailed,
            'uploadFailedBadly' => $uploadFailedBadly,
        ];
    }
}
