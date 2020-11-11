<?php

namespace WebPExpress;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$indexDir = Paths::getIndexDirAbs();
$homeDir = Paths::getHomeDirAbs();
$wpContentDir = Paths::getContentDirAbs();
$pluginDir = Paths::getPluginDirAbs();
$uploadDir = Paths::getUploadDirAbs();

$weKnowThereAreNoWorkingConverters = false;
if ($testResult !== false) {
    $workingConverters = $testResult['workingConverters'];
    $weKnowThereAreNoWorkingConverters = (count($workingConverters) == 0);
}
$bgColor = ($weKnowThereAreNoWorkingConverters ? '#fff' : '#cfc');
echo '<div style="background-color: ' . $bgColor . '; padding: 10px 20px; border: 1px solid #ccc; color: black; margin-top:15px">';
echo '<h3>Welcome!</h3>';

//if ($localQualityDetectionWorking) {
    //echo 'Local quality detection working :)';
//}

if ($weKnowThereAreNoWorkingConverters) {
    // server does not meet the requirements for converting images to webp without resorting to cloud conversion
    echo '<p>Unfortunately your server cannot convert webp files in PHP without resorting to cloud conversion.</p>' .
        '<p>But do not despear! - You have options!</p>' .
        '<ol style="list-style-position:outside">' .
        '<li>You can install this plugin on another website, which supports a "local" webp conversion method and connect to that using the "Remote WebP Express" conversion method' .
        '<li>You can purchase a key for the <a target="_blank" href="https://ewww.io/plans/">ewww cloud converter</a>. They do not charge credits for webp conversions, so all you ever have to pay is the one dollar start-up fee :)</li>' .
        '<li>I have written a <a target="_blank" href="https://github.com/rosell-dk/webp-convert/wiki/A-template-letter-for-shared-hosts">template letter</a> which you can try sending to your webhost</li>' .
        '<li>You can set up a <a target="_blank" href="https://github.com/rosell-dk/webp-convert-cloud-service">webp-convert-cloud-service</a> on another server and connect to that. Its open source.</li>' .
        '<li>You can try to meet the server requirements of cwebp, imagick, vips, gmagick, ffmpeg or gd. Check out <a target="_blank" href="https://github.com/rosell-dk/webp-convert/wiki/Meeting-the-requirements-of-the-converters">this wiki page</a> on how to do that</li>' .
        '</ol>' .
        '<p>Of course, there is also the option of using another plugin altogether. ' .
        'I can recommend <i>Optimole</i>. ' .
        'If you want to try that out and want to support me in the process, ' .
        '<a href="https://optimole.pxf.io/20b0M">follow this link</a> ' .
        '(it will give me a reward in case you decide to sign up).' .
        '</p>' .
        "<p>Btw, don't worry, your images still works. The rewrite rules will not be saved until you click the " .
        '"Save settings" button.</p>';
        //'(and you also have "Response on failure" set to "Original image", so they will work even if you click save)</p>';
} else {
    echo '<p>The rewrite rules are not active yet. They will be activated the first time you click the "Save settings" button.</p>';
}

//echo 'working converters:';
//print_r($workingConverters);

//echo '<p>Before you do that, I suggest you find out which converters that works. Start from the top. Click "test" next to a converter to test it. Try also clicking the "configure" buttons</p>';

/*
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
    if (Paths::isUploadDirMovedOutOfWPContentDir()) {
        if (!Paths::canWriteHTAccessRulesHere($uploadDir)) {
            echo '<p><b>Oh, one more thing</b>. We also need to write rules to your uploads dir (because you have moved it). ';
            echo 'Please grant us write access to your ';
            if (FileHelper::fileExists($uploadDir . '/.htaccess')) {
                echo '<i>.htaccess</i> file in your upload dir';
            } else {
                echo 'upload dir, so we can plant an <i>.htaccess</i> there';
            }
            echo '. Your upload dir is: <i>' . $uploadDir . '</i>. ';
            echo '- Or alternatively, you can leave it be and update the rules manually, whenever they need to be changed. ';
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
    if (Paths::isUploadDirMovedOutOfWPContentDir()) {
        if (!Paths::canWriteHTAccessRulesHere($uploadDir)) {
            echo '<p><b>Oh, one more thing</b>. We also need to write rules to your uploads dir (because you have moved it). ';
            echo 'Please grant us write access to your ';
            if (FileHelper::fileExists($uploadDir . '/.htaccess')) {
                echo '<i>.htaccess</i> file in your upload dir';
            } else {
                echo 'upload dir, so we can plant an <i>.htaccess</i> there';
            }
            echo '. Your upload dir is: <i>' . $uploadDir . '</i>. ';
            echo '- Or alternatively, you can leave it and update the rules manually, whenever they need to be changed. ';
        }
    }
    if (Paths::isPluginDirMovedOutOfAbsPath()) {
        if (!Paths::canWriteHTAccessRulesHere($pluginDir)) {
            echo '<p>Oh, one more thing. I see you have moved your plugins dir out of your root. ';
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
}
*/
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
