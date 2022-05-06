<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use \WebPExpress\HTAccessCapabilityTestRunner;
use \WebPExpress\Config;
use \WebPExpress\ConvertersHelper;
use \WebPExpress\DismissableMessages;
use \WebPExpress\FileHelper;
use \WebPExpress\HTAccess;
use \WebPExpress\HTAccessRules;
use \WebPExpress\Messenger;
use \WebPExpress\Paths;
use \WebPExpress\PlatformInfo;
use \WebPExpress\State;



// TODO: Move most of this file into a ProblemDetector class (SystemHealth)

if (!(State::getState('configured', false))) {
    include __DIR__ . "/page-welcome.php";

    if (PlatformInfo::isNginx()) {
        DismissableMessages::addDismissableMessage('0.16.0/nginx-link-to-faq');
    }

}

$storedCapTests = $config['base-htaccess-on-these-capability-tests'];

/*
if (HTAccessCapabilityTestRunner::modRewriteWorking()) {
    echo 'mod rewrite works. that is nice';
}*/

/*if (HTAccessCapabilityTestRunner::modHeaderWorking() === true) {
    //echo 'nice!';
}*/

// Dissmiss page messages for which the condition no longer applies
if ($config['image-types'] != 1) {
    DismissableMessages::dismissMessage('0.14.0/suggest-enable-pngs');
}

//DismissableMessages::dismissAll();
//DismissableMessages::addDismissableMessage('0.14.0/suggest-enable-pngs');
//DismissableMessages::addDismissableMessage('0.14.0/suggest-wipe-because-lossless');
//DismissableMessages::addDismissableMessage('0.14.0/say-hello-to-vips');


DismissableMessages::printMessages();

//$dismissableMessageIds = ['suggest-enable-pngs'];

$firstActiveAndWorkingConverterId = ConvertersHelper::getFirstWorkingAndActiveConverterId($config);
$workingIds = ConvertersHelper::getWorkingConverterIds($config);

$cacheEnablerActivated = in_array('cache-enabler/cache-enabler.php', get_option('active_plugins', []));
if ($cacheEnablerActivated) {
    $cacheEnablerSettings = get_option('cache-enabler', []);
    $webpEnabled = (isset($cacheEnablerSettings['webp']) && $cacheEnablerSettings['webp']);
}

if ($cacheEnablerActivated && !$webpEnabled) {
    Messenger::printMessage(
        'warning',
            'You are using Cache Enabler, but have not enabled the webp option, so Cache Enabler is not operating with a separate cache ' .
            'for webp-enabled browsers.'
    );
}
/*
Commented out
In newer PHP, it generates a fatal (uncatchable) error:
Fatal error: Uncaught Error: Call to a member function is_feature_active() on null
See #562

$elementorActivated = in_array('elementor/elementor.php', get_option('active_plugins', []));
if ($elementorActivated) {
    try {
        // The following is wrapped in a try statement because it depends on Elementor classes which might be subject to change
        if (\Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_css_loading' ) === false) {
            if ($config['redirect-to-existing-in-htaccess'] === false) {
                DismissableMessages::addDismissableMessage('0.23.0/elementor');
            }
        }
    } catch (\Exception $e) {
        // Well, just bad luck.
    }
}
*/

if (($config['operation-mode'] == 'cdn-friendly') && !$config['alter-html']['enabled']) {
    //echo print_r(get_option('cache-enabler'), true);

    if ($cacheEnablerActivated) {
        if ($webpEnabled) {
            Messenger::printMessage(
                'info',
                    'You should consider enabling Alter HTML. This is not necessary, as you have <i>Cache Enabler</i> enabled, which alters HTML. ' .
                    'However, it is a good idea because currently <i>Cache Enabler</i> does not replace as many URLs as WebP Express (ie ' .
                    'background images in inline styles)'
            );
        }

    } else {
        Messenger::printMessage(
            'warning',
                'You are in CDN friendly mode but have not enabled Alter HTML (and you are not using Cache Enabler either). ' .
                    'This is usually a misconfiguration because in this mode, the only way to get webp files delivered ' .
                    'is by referencing them in the HTML.'
        );

    }
}

/*
if (!$anyRedirectionToConverterEnabled && ($config['operation-mode'] == 'cdn-friendly')) {
    // this can not happen in varied image responses. it is ok in no-conversion, and also tweaked, because one could wish to tweak the no-conversion mode
    Messenger::printMessage(
        'warning',
            'You have not enabled any of the redirects to the converter. ' .
                'At least one of the redirects is required for triggering WebP generation.'
    );
}*/

if ($config['alter-html']['enabled'] && !$config['alter-html']['only-for-webps-that-exists'] && !$config['enable-redirection-to-webp-realizer']) {
    Messenger::printMessage(
        'warning',
            'You have configured Alter HTML to make references to WebP files that are yet to exist, ' .
                '<i>but you have not enabled the option that makes these files come true when requested</i>. Do that!'
    );
}

if ($config['enable-redirection-to-webp-realizer'] && $config['alter-html']['enabled'] && $config['alter-html']['only-for-webps-that-exists']) {
    Messenger::printMessage(
        'warning',
            'You have enabled the option that redirects requests for non-existing webp files to the converter, ' .
                '<i>but you have not enabled the option to point to these in Alter HTML</i>. Please do that!'
    );
}

if ($config['image-types'] == 3) {
    $workingConverters = ConvertersHelper::getWorkingAndActiveConverters($config);
    if (count($workingConverters) == 1) {
        if (ConvertersHelper::getConverterId($workingConverters[0]) == 'gd') {
            if (isset($workingConverters[0]['options']['skip-pngs']) && $workingConverters[0]['options']['skip-pngs']) {
                Messenger::printMessage(
                    'warning',
                        'You have enabled PNGs, but configured Gd to skip PNGs, and Gd is your only active working converter. ' .
                        'This is a bad combination!'
                );
            }
        }
    }
}




/*
if (Config::isConfigFileThereAndOk() ) { // && PlatformInfo::definitelyGotModEnv()
    if (!isset($_SERVER['HTACCESS'])) {
        Messenger::printMessage(
            'warning',
            "Using rewrite rules in <i>.htaccess</i> files seems to be disabled " .
                "(The <i>AllowOverride</i> directive is probably set to <i>None</i>. " .
                "It needs to be set to <i>All</i>, or at least <i>FileInfo</i> to allow rewrite rules in <i>.htaccess</i> files.)<br>" .
                "Disabled <i>.htaccess</i> files is actually a good thing, both performance-wise and security-wise. <br> " .
                "But it means you will have to insert the following rules into your apache configuration manually:" .
                "<pre>" . htmlentities(print_r(Config::hmmm(), true)) . "</pre>"
        );
    }
}*/
if (!Paths::createContentDirIfMissing()) {
    Messenger::printMessage(
        'error',
        'WebP Express needs to create a directory "webp-express" under your wp-content folder, but does not have permission to do so.<br>' .
            'Please create the folder manually, or change the file permissions of your wp-content folder (failed to create this folder: ' .
            esc_html(Paths::getWebPExpressContentDirAbs()) . ')'
    );
} else {
    if (!Paths::createConfigDirIfMissing()) {
        Messenger::printMessage(
            'error',
            'WebP Express needs to create a directory "webp-express/config" under your wp-content folder, but does not have permission to do so.<br>' .
                'Please create the folder manually, or change the file permissions.'
        );
    }

    if (!Paths::createCacheDirIfMissing()) {
        Messenger::printMessage(
            'error',
            'WebP Express needs to create a directory "webp-express/webp-images" under your wp-content folder, but does not have permission to do so.<br>' .
                'Please create the folder manually, or change the file permissions.'
        );
    }
}

if (Config::isConfigFileThere()) {
    if (!Config::isConfigFileThereAndOk()) {
        $json = FileHelper::loadFile(Paths::getConfigFileName());
        if ($json === false) {
            Messenger::printMessage(
                'warning',
                'Warning: The configuration file is not ok! (cant be read).<br>' .
                    'file: "' . esc_html(Paths::getConfigFileName()) . '"'
            );
        } else {
            Messenger::printMessage(
                'warning',
                'Warning: The configuration file is not ok! (not valid json).<br>' .
                    'file: "' . esc_html(Paths::getConfigFileName()) . '"'
            );
        }

    } else {

        if ($config['redirect-to-existing-in-htaccess']) {
            if (PlatformInfo::isApacheOrLiteSpeed() && !(HTAccessCapabilityTestRunner::modHeaderWorking())) {
                Messenger::printMessage(
                    'warning',
                    'It seems your server setup does not support headers in <i>.htaccess</i>. You should either fix this (install <i>mod_headers</i>) <i>or</i> ' .
                        'deactivate the "Enable direct redirection to existing converted images?" option. Otherwise the <i>Vary:Accept</i> header ' .
                        'will not be added and this can result in problems for users behind proxy servers (ie used in larger companies)'
                );
            }
        }

        $anyRedirectionToConverterEnabled = (($config['enable-redirection-to-converter']) || ($config['enable-redirection-to-webp-realizer']));
        $anyRedirectionEnabled = ($anyRedirectionToConverterEnabled || $config['redirect-to-existing-in-htaccess']);

        if ($anyRedirectionEnabled) {
            if (PlatformInfo::isApacheOrLiteSpeed() && PlatformInfo::definitelyNotGotModRewrite()) {
                Messenger::printMessage(
                    'warning',
                    "Rewriting isn't enabled on your server. " .
                        'You must either switch to "CDN friendly" mode or enable rewriting. ' .
                        "Tell your host or system administrator to enable the 'mod_rewrite' module. " .
                        'If you are on a shared host, chances are that mod_rewrite can be turned on in your control panel.'
                );
            }
        }

        if ($anyRedirectionToConverterEnabled) {
            $canRunInWod = HTAccessCapabilityTestRunner::canRunTestScriptInWOD();
            $canRunInWod2 = HTAccessCapabilityTestRunner::canRunTestScriptInWOD2();
            if (!$canRunInWod && !$canRunInWod2) {
                $turnedOn = [];
                if ($config['enable-redirection-to-converter']) {
                    $turnedOn[] = '"Enable redirection to converter"';
                }
                if ($config['enable-redirection-to-webp-realizer']) {
                    $turnedOn[] = '"Create webp files upon request?""';
                }
                Messenger::printMessage(
                    'warning',
                    '<p>You have turned on ' . implode(' and ', $turnedOn) .
                    '. However, ' . (count($turnedOn) == 2 ? 'these features' : 'this feature') .
                    ' does not work on your current server settings / wordpress setup, ' .
                    ' because the PHP scripts in the plugin folder (in the "wod" and "wod2" subfolders) fails to run ' .
                    ' when requested directly. You can try to fix the problem or simply turn ' .
                    (count($turnedOn) == 2 ? 'them' : 'it') .
                    ' off and rely on "Convert on upload" and "Bulk Convert" to get the images converted.</p>' .
                    '<p>If you are going to try to solve the problem, you need at least one of the following pages ' .
                    'to display "pong": ' .
                    '<a href="' . Paths::getWebPExpressPluginUrl() . '/wod/ping.php" target="_blank">wod-test</a>' .
                    ' or <a href="' . Paths::getWebPExpressPluginUrl() . '/wod2/ping.php" target="_blank">wod2-test</a>' .
                    '. The problem will typically be found in the server configuration or a security plugin. ' .
                    'If one of the links results in a 403 Permission denied, look out for "deny" and "denied" in ' .
                    'httpd.conf, /etc/apache/sites-enabled/your-site.conf and in parent .htaccess files.' .
                    '</p>.'
                );
            }
            // We currently allow the "canRunTestScriptInWOD" test not to be stored,
            // If it is not stored, it means .htaccess files are pointing to "wod"
            // PS: the logic of where it is stored happens in HTAccessRules::getWodUrlPath
            // - we mimic it here.
            $pointingToWod = true;  // true = pointing to "wod", false = pointing to "wod2"
            $hasWODTestBeenRun = isset($storedCapTests['canRunTestScriptInWOD']);
            if ($hasWODTestBeenRun && !($storedCapTests['canRunTestScriptInWOD'])) {
                $pointingToWod = false;
            }
            $canOnlyRunInWod = $canRunInWod && !$canRunInWod2;
            if ($canOnlyRunInWod && !$pointingToWod) {
                Messenger::printMessage(
                    'warning',
                    'The conversion script cannot currently be run. ' .
                    'However, simply click "Save settings <b>and force new .htaccess rules</b>" to fix it. ' .
                    '(this will point to the script in the "wod" folder rather than "wod2")'
                );
            }

            $canOnlyRunInWod2 = $canRunInWod2 && !$canRunInWod;
            if ($canOnlyRunInWod2 && $pointingToWod) {
                Messenger::printMessage(
                    'warning',
                    'The conversion script cannot currently be run. ' .
                    'However, simply click "Save settings <b>and force new .htaccess rules</b>" to fix it. ' .
                    '(this will point to the script in the "wod2" folder rather than "wod")'
                );
            }

        }

        if (HTAccessRules::arePathsUsedInHTAccessOutdated()) {

            $pathsGoingToBeUsedInHtaccess = [
                'wod-url-path' => Paths::getWodUrlPath(),
            ];

            $config2 = Config::loadConfig();
            if ($config2 === false) {
                Messenger::printMessage(
                    'warning',
                    'Warning: Config file cannot be loaded. Perhaps clicking ' .
                    '<i>Save settings</i> will solve it<br>'
                );
            }

            $warningMsg = 'Warning: Wordpress paths have changed since the last time the Rewrite Rules was generated. The rules ' .
                'needs updating! (click <i>Save settings</i> to do so)<br><br>' .
                'The following have changed:<br>';

            foreach ($config2['paths-used-in-htaccess'] as $prop => $value) {
                if (isset($pathsGoingToBeUsedInHtaccess[$prop])) {
                    if ($value != $pathsGoingToBeUsedInHtaccess[$prop]) {
                        $warningMsg .= '- ' . $prop . '(was: ' . $value . '- but is now: ' . $pathsGoingToBeUsedInHtaccess[$prop] . ')<br>';
                    }
                }
            }


            Messenger::printMessage(
                'warning',
                $warningMsg
            );
        }
    }
}

$haveRulesInIndexDir = HTAccess::haveWeRulesInThisHTAccessBestGuess(Paths::getIndexDirAbs() . '/.htaccess');
$haveRulesInContentDir = HTAccess::haveWeRulesInThisHTAccessBestGuess(Paths::getContentDirAbs() . '/.htaccess');

if ($haveRulesInIndexDir && $haveRulesInContentDir) {
    // TODO: Use new method for determining if htaccess contains rules.
    // (either haveWeRulesInThisHTAccessBestGuess($filename) or haveWeRulesInThisHTAccess($filename))
    if (!HTAccess::saveHTAccessRulesToFile(Paths::getIndexDirAbs() . '/.htaccess', '# WebP Express has placed its rules in your wp-content dir. Go there.', false)) {
        Messenger::printMessage(
            'warning',
            'Warning: WebP Express have rules in both your wp-content folder and in your Wordpress folder.<br>' .
                'Please remove those in the <i>.htaccess</i> in your Wordress folder manually, or let us handle it, by granting us write access'
        );
    }
}

$ht = FileHelper::loadFile(Paths::getIndexDirAbs() . '/.htaccess');
if ($ht !== false) {
    $posWe = strpos($ht, '# BEGIN WebP Express');
    $posWo = strpos($ht, '# BEGIN WordPress');
    if (($posWe !== false) && ($posWo !== false) && ($posWe > $posWo)) {

        $haveRulesInIndexDir = HTAccess::haveWeRulesInThisHTAccessBestGuess(Paths::getIndexDirAbs() . '/.htaccess');
        if ($haveRulesInIndexDir) {
            Messenger::printMessage(
                'warning',
                'Problem detected. ' .
                    'In order for the "Convert non-existing webp-files upon request" functionality to work, you need to either:<br>' .
                    '- Move the WebP Express rules above the Wordpress rules in the .htaccess file located in your root dir<br>' .
                    '- Grant the webserver permission to your wp-content dir, so it can create its rules there instead.'
            );
        }
    }
}
