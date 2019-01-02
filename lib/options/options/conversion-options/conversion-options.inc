<?php if ($config['operation-mode'] == 'tweaked') : ?>
<fieldset class="block">
    <h3>Conversion options</h3>
    <p><i>The options here affects the conversion process</i></p>
    <table class="form-table">
        <tbody>
            <?php
            include_once 'quality.inc';
            include_once 'metadata.inc';
            include_once 'converters.inc';
            include_once 'destination-folder.inc';
            include_once 'destination-extension.inc';
            ?>
        </tbody>
    </table>
</fieldset>
<?php
else:
    include_once 'quality.inc';
    include_once 'metadata.inc';
    include_once 'converters.inc';

    if ($config['operation-mode'] == 'standard') {
        include_once 'destination-folder.inc';
    }
    include_once 'destination-extension.inc';
endif;
?>
