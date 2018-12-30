<?php if ($config['operation-mode'] == 'advanced') : ?>
<fieldset class="block">
    <h3>Redirection rules</h3>
    <p><i>The options here affects the rules created in the .htaccess.</i></p>
    <table class="form-table">
        <tbody>
            <?php
            include_once 'image-types.inc';
            include_once 'only-redirect-to-converter-on-cache-miss.inc';
            include_once 'redirect-to-existing.inc';
            include_once 'do-not-pass-source-path-in-query-string.inc';
            ?>
        </tbody>
    </table>
</fieldset>
<?php
else:
    include_once 'image-types.inc';
endif;
?>
