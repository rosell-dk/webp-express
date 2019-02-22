<?php if ($config['operation-mode'] == 'tweaked') : ?>
<fieldset class="block">
    <h3>Redirection rules</h3>
    <div><i>The options here affects the rules created in the .htaccess. <?php echo helpIcon('And so does some other options. If "Redirect directly to converted image" is set, the "Destination folder" and "File Extension" and "Caching" options will be used'); ?></i></div>
    <table class="form-table">
        <tbody>
            <?php
            include_once 'enable-redirection-to-converter.inc';
            include_once 'only-redirect-to-converter-for-webp-enabled-browsers.inc';
            include_once 'image-types.inc';
            include_once 'do-not-pass-source-path-in-query-string.inc';
            include_once 'only-redirect-to-converter-on-cache-miss.inc';
            include_once 'redirect-to-existing.inc';
            include_once 'enable-redirection-to-webp-realizer.inc';
            ?>
        </tbody>
    </table>
</fieldset>
<?php
else:
    if ($config['operation-mode'] != 'no-conversion') {
        include_once 'image-types.inc';
    }

    if ($config['operation-mode'] == 'cdn-friendly') {
        // ps: we call it "auto convert", when in this mode
        // PPS: we include it directly in page.php now.
        //include_once 'enable-redirection-to-converter.inc';

        //include_once 'only-redirect-to-converter-on-cache-miss.inc';

    }
    else {
    //if ($config['operation-mode'] == 'varied-responses') {
        include_once 'redirect-to-existing.inc';
        //include_once 'enable-redirection-to-webp-realizer.inc';
    }

endif;
?>
