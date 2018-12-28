<fieldset class="block">
    <h3>Serve options</h3>
    <p><i>The options here affects how the image is served after a successful / unsuccessful conversion</i></p>
    <table class="form-table">
        <tbody>
            <?php
            include_once 'cache-control.inc';
            include_once 'response-on-failure.inc';
            ?>
        </tbody>
    </table>
</fieldset>
