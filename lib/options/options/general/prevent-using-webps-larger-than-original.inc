<tr>
    <th scope="row">Prevent using webps larger than original<?php
        echo helpIcon(
            '<p>For some images, the converted webp might turns out bigger than the original. ' .
            'These are always kept on disk. However, with this option, you can choose whether they should be used or not. ' .
            'If you are motivated by limiting bandwidth usage and having a fast website, keep this option enabled. ' .
            'If you are more concerned about SEO and a penalty for serving jpegs and pngs rather than webps, disable it.' .
            '</p>' .
            '<p>The option is used both when generating .htaccess rules and in Alter HTML.</p>' .
            '<p>Note: If you are using Alter HTML with picture tags and have images with a srcset ' .
            'attribute, WebP Express generates a source element with type set to "image/webp". This source ' .
            'thus points to a bundle of webp images. From 0.25.5 and forward, when this option is enabled, ' .
            'WebP Express will only add the source element when ALL those webp images are smaller than their originals. ' .
            'In such a situation (using Alter HTML with picture tags), it might be a better strategy to disable this option. ' .
            'Especially if your images generally have the srcset attribute set. Chances are that only a few of the webps in ' .
            'the source set are bigger than the corresponding originals, and chances are that they are only slightly bigger.' .
            '</p>'
        );
    ?></th>
    <td>
        <input type="checkbox" id="prevent_using_webps_larger_than_original" name="prevent-using-webps-larger-than-original" value="true" <?php echo ($config['prevent-using-webps-larger-than-original'] ? 'checked="checked"' : '') ?> >
    </td>
</tr>
