<fieldset class="block">
    <h3>Alter HTML</h3>
    <p>
        Enabling this alters the HTML code such that webp images are served to browsers that supports webp.
        It is <i>recommended to enable</i> this even when the redirection is also enabled, so the varied image responses are only used for
        those images that cannot be replaced in HTML. The varied responses generally leads to poorer caching in proxies and CDN's. And if
        users download those images, they will have jpg/png extension, even though they are webp.
    </p>
    <table class="form-table">
        <tbody>
            <?php
            include_once 'alter-html-options.inc';
            ?>
        </tbody>
    </table>
</fieldset>
