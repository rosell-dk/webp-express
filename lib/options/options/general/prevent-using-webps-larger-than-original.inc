<tr>
    <th scope="row">Prevent using webps larger than original<?php
        echo helpIcon(
            '<p>For some images, the converted webp might turns out bigger than the original. ' .
            'These are always kept on disk. However, with this option, you can choose whether they should be used or not. ' .
            'If you are motivated by limiting bandwidth usage and having a fast website, keep this option enabled. ' .
            'If you are more concerned about SEO and a penalty for serving jpegs and pngs rather than webps, disable it.' .
            '</p>' .
            '<p>The option is used both when generating .htaccess rules and in Alter HTML.</p>'
        );
    ?></th>
    <td>
        <input type="checkbox" id="prevent_using_webps_larger_than_original" name="prevent-using-webps-larger-than-original" value="true" <?php echo ($config['prevent-using-webps-larger-than-original'] ? 'checked="checked"' : '') ?> >
    </td>
</tr>
