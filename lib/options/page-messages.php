<?php


use \WebPExpress\Paths;
use \WebPExpress\Config;

if (empty(get_option('webp-express-configured'))) {
    echo '<div style="background-color: #cfc; padding: 20px; border: 1px solid #ccc">';
    echo '<h3>Welcome!<h3>';
    echo '<p>The rewrite rules are not active yet. They will be activated the first time you click the "Save settings" button.</p>';
    echo '<p>Before you do that, I suggest you find out which converters that works. Start from the top. Click "test" next to a converter to test it. Try also clicking the "configure" buttons</p>';
    echo '</div>';
}


if (!Paths::createContentDirIfMissing()) {
    Messenger::printMessage(
        'error',
        'WebP Express needs to create a directory "webp-express" under your wp-content folder, but does not have permission to do so.<br>' .
            'Please create the folder manually, or change the file permissions of your wp-content folder.'
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
        Messenger::printMessage(
            'warning',
            'Warning: The configuration file is not ok! (cant be read, or not valid json).<br>' .
                'file: "' . Paths::getConfigFileName() . '"'
        );
    } else {
        if (Config::arePathsUsedInHTAccessOutdated()) {
            Messenger::printMessage(
                'warning',
                'Warning: Wordpress paths have changed since the last time the Rewrite Rules was generated. The rules needs updating! (click <i>Save settings</i> to do so)<br>'
            );
        }
    }
}
