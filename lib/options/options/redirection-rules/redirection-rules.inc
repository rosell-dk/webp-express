<fieldset class="block">
    <?php  if ($config['operation-mode'] == 'no-conversion') : ?>
    <h2>Redirecting jpeg/png to existing webp (varied image response)</h2>
    <p>
        Enabling this adds rules to the <i>.htaccess</i> which internally redirects jpg/pngs to webp
        and sets the <i>Vary:Accept</i> header.
        <i>Beware that special attention is needed if you are using a CDN.</i>
    </p>
    <?php elseif ($config['operation-mode'] == 'cdn-friendly') : ?>
    <h2><i>.htaccess</i> rules for webp generation</h2>
    <p>
        The following redirect rules will not produce varied image responses.
        Their job is to trigger webp conversion <i>without</i> producing varied responses.
        On most systems you need only to activate the first option. The second option can be used if the first one fails on your system.
    </p>
    <?php else : ?>
    <h3>Redirection rules</h3>
    <div><i>The options here affects the rules created in the .htaccess. <?php echo helpIcon('And so does some other options. If "Redirect directly to converted image" is set, the "Destination folder" and "File Extension" and "Caching" options will be used'); ?></i></div>
    <?php endif; ?>

    <table class="form-table">
        <tbody>
            <?php
            switch ($config['operation-mode']) {
                case 'tweaked':
                    include_once 'enable-redirection-to-converter.inc';
                    include_once 'only-redirect-to-converter-for-webp-enabled-browsers.inc';
                    include_once 'only-redirect-to-converter-on-cache-miss.inc';
                    include_once 'do-not-pass-source-path-in-query-string.inc';
                    include_once 'redirect-to-existing.inc';
                    include_once 'enable-redirection-to-webp-realizer.inc';
                    break;
                case 'no-conversion':
                    include_once 'redirect-to-existing.inc';
                    include_once __DIR__. '/../general/cache-control.inc';

                    break;
                case 'cdn-friendly':
                    //include_once 'redirect-to-existing.inc';
                    include_once 'enable-redirection-to-webp-realizer.inc';

                    // ps: we call it "auto convert", when in this mode
                    include_once 'enable-redirection-to-converter.inc';
                    break;
                case 'varied-image-responses':
                    include_once 'redirect-to-existing.inc';
                    include_once 'enable-redirection-to-webp-realizer.inc';
                    break;
            }
            ?>
        </tbody>
    </table>
</fieldset>
