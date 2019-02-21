<?php if ($config['operation-mode'] == 'tweaked') : ?>
<fieldset class="block">
    <h3>Alter HTML</h3>
    <table class="form-table">
        <tbody>
            <?php
            include_once 'alter-html-options.inc';
            ?>
        </tbody>
    </table>
</fieldset>
<?php
else:

    //if ($config['operation-mode'] != 'no-conversion') {
        include_once 'alter-html-options.inc';
    //}
endif;
?>
