<?php if ($config['operation-mode'] == 'tweaked') : ?>
    <fieldset class="block">
    <h3>Serve options</h3>
    <p><i>The options here affects how the image is served after a successful / unsuccessful conversion</i></p>
    <table class="form-table">
        <tbody>
            <?php
            include_once 'cache-control.inc';
            include_once 'response-on-failure.inc';
            include_once 'response-on-success.inc';
            ?>
        </tbody>
    </table>
</fieldset>
<?php
else:
    if ($config['operation-mode'] != 'just-convert') {
        include_once 'cache-control.inc';
    }
endif;
?>
