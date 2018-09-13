<?php

use \WebPExpress\Paths;
use \WebPExpress\Config;
use \WebPExpress\State;
use \WebPExpress\Messenger;
use \WebPExpress\PlatformInfo;
use \WebPExpress\FileHelper;

$indexDir = Paths::getIndexDirAbs();
$homeDir = Paths::getHomeDirAbs();
$wpContentDir = Paths::getWPContentDirAbs();
$pluginDir = Paths::getPluginDirAbs();

echo '<div style="background-color: #cfc; padding: 20px; border: 1px solid #ccc; color: black">';
echo '<h3>Welcome!</h3>';
echo '<p>The rewrite rules are not active yet. They will be activated the first time you click the "Save settings" button.</p>';
echo '<p>Before you do that, I suggest you find out which converters that works. Start from the top. Click "test" next to a converter to test it. Try also clicking the "configure" buttons</p>';


if (Paths::isWPContentDirMovedOutOfAbsPath()) {
    if (!Paths::canWriteHTAccessRulesHere($wpContentDir)) {
        echo '<p><b>Oh, one more thing</b>. Unless you are going to put the rewrite rules into your configuration manually, ';
        echo '<i>WebP Express</i> would be needing to store the rewrite rules in a <i>.htaccess</i> file in your <i>wp-content</i> directory ';
        echo '(we need to store them there rather than in your root, because you have moved your wp-content folder out of the Wordpress root). ';
        echo 'Please adjust the file permissions of your <i>wp-content</i> dir. ';

        if (Paths::isPluginDirMovedOutOfWpContent()) {
            echo '<br>But that is not all. Besides moving your wp-content dir, you have <i>also</i> moved your plugin dir... ';
            echo 'If you want WebP-Express to work on the images delivered by your plugins, you must also grant write access to your plugin dir (you can revoke the access after we have written the rules).<br>';
        }
        echo 'You can reload this page aftewards, and this message should be gone</p>';
    } else {
        if (Paths::isPluginDirMovedOutOfWpContent()) {
            echo '<p><b>Oh, one more thing</b>. I can see that your plugin dir has been moved out of your wp-content folder. ';
            echo 'If you want WebP-Express to work on the images delivered by your plugins, you must grant write access to your ';
            echo 'plugin dir (you can revoke the access after we have written the rules, but beware that the plugin may need ';
            echo 'access rights again. Some of the options affects the .htaccess rules. And WebP Express also have to remove the rules if the plugin is disabled)';
        }
    }
} else {
    $firstWritable = Paths::returnFirstWritableHTAccessDir([$wpContentDir, $indexDir]);
    if ($firstWritable === false) {
        echo '<p><b>Oh, one more thing</b>. Unless you are going to put the rewrite rules into your configuration manually, ';
        echo '<i>WebP Express</i> would be needing to store the rewrite rules in a <i>.htaccess</i> file. ';
        echo 'However, your current file permissions does not allow that. ';
        echo '<i>WebP Express</i> would prefer to put the rewrite rules into your <i>wp-content</i> folder, but ';
        echo 'will store them in your main <i>.htaccess</i> file if it can write to that, but not your wp-content. ';
        echo '(The preference for storing in wp-content is simply that it minimizes the risk of conflicts with rules from other plugins. ';
        echo 'deeper <i>.htaccess</i> files takes precedence). ';
        echo 'Anyway: Could you please adjust the file permissions of either your main <i>.htaccess</i> file or your wp-content dir?';
        echo 'You can reload this page aftewards, and this message should be gone</p>';
    } else {
        if ($firstWritable != $wpContentDir) {
            echo '<p>Oh, one more thing. Unless you are going to put the rewrite rules into your configuration manually, ';
            echo '<i>WebP Express</i> would be needing to store the rewrite rules in a <i>.htaccess</i> file. ';
            echo 'Your current file permissions <i>does</i> allow us to store rules in your main <i>.htaccess</i> file. ';
            echo 'However, <i>WebP Express</i> would prefer to put the rewrite rules into your <i>wp-content</i> folder. ';
            echo 'Putting them there will minimize the risk of conflict with rules from other plugins, as ';
            echo 'deeper <i>.htaccess</i> files takes precedence. ';
            echo 'If you would like the <i>.htaccess</i> file to be stored in your wp-content folder, please adjust your file permissions. ';
            echo 'You can reload this page aftewards, and this message should be gone</p>';
        }
    }
    if (Paths::isPluginDirMovedOutOfAbsPath()) {
        echo '<p>Oh, yet another thing. I see you have moved your plugins dir out of your root. ';
        echo 'If you want WebP-Express to work on the images delivered by your plugins, you must also grant write access ';
        echo 'to your ';
        if (FileHelper::fileExists($pluginDir . '/.htaccess')) {
            echo '<i>.htaccess</i> file in your plugin dir';
        } else {
            echo 'plugin dir, so we can plant an <i>.htaccess</i> there';
        }
        echo ' (you can revoke the access after we have written the rules).';
        echo '</p>';
    }
}

/*
if(Paths::canWriteHTAccessRulesHere($wpContentDir)) {

    if ($firstWritable === false) {
        echo 'Actually, WebP Express does not have permission to write to your main <i>.htaccess</i> either. Please fix. Preferably ';
    }


    $firstWritable = Paths::returnFirstWritableHTAccessDir([$indexDir, $homeDir]);
    if ($firstWritable === false) {
        echo 'Actually, WebP Express does not have permission to write to your main <i>.htaccess</i> either. Please fix. Preferably ';
    }
    if(Paths::canWriteHTAccessRulesHere($wpContentDir)) {
        echo '<i>WebP Express</i> however does have rights to write to your main <i>.htaccess</i>. It will work too - probably. But to minimize risk of conflict with rules from other plugins, I recommended you to adjust the file permissions to allow us to write to a <i>.htaccess</i> file in your <i>wp-content dir</i>';
    }
    echo '</p>';
}*/

echo '</div>';
