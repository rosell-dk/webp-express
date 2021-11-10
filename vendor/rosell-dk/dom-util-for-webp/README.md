# dom-util-for-webp

[![Latest Stable Version](https://img.shields.io/packagist/v/rosell-dk/dom-util-for-webp.svg?style=flat-square)](https://packagist.org/packages/rosell-dk/dom-util-for-webp)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://php.net)
[![Build Status](https://img.shields.io/github/workflow/status/rosell-dk/dom-util-for-webp/PHP?logo=GitHub&style=flat-square)](https://github.com/rosell-dk/dom-util-for-webp/actions/workflows/php.yml)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/rosell-dk/dom-util-for-webp.svg?style=flat-square)](https://scrutinizer-ci.com/g/rosell-dk/dom-util-for-webp/code-structure/master)
[![Quality Score](https://img.shields.io/scrutinizer/g/rosell-dk/dom-util-for-webp.svg?style=flat-square)](https://scrutinizer-ci.com/g/rosell-dk/dom-util-for-webp/)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/rosell-dk/dom-util-for-webp/blob/master/LICENSE)

*Replace image URLs found in HTML*

This library can do two things:

1) Replace image URLs in HTML
2) Replace *&lt;img&gt;* tags with *&lt;picture&gt;* tags, adding webp versions to sources

To setup with composer, run ```composer require rosell-dk/dom-util-for-webp```.

## 1. Replacing image URLs in HTML

The *ImageUrlReplacer::replace($html)* method accepts a piece of HTML and returns HTML where where all image URLs have been replaced - even those in inline styles.

*Usage:*

```php
$modifiedHtml = ImageUrlReplacer::replace($html);
```

### Example replacements:

*input:*

```html
<img src="image.jpg">
<img src="1.jpg" srcset="2.jpg 1000w">
<picture>
    <source srcset="1.jpg" type="image/webp">
    <source srcset="2.png" type="image/webp">
    <source src="3.gif"> <!-- gifs are skipped in default behaviour -->
    <source src="4.jpg?width=200"> <!-- urls with query string are skipped in default behaviour -->
</picture>
<div style="background-image: url('image.jpeg')"></div>
<style>
#hero {
    background: lightblue url("image.png") no-repeat fixed center;;
}
</style>
<input type="button" src="1.jpg">
<img data-src="image.jpg"> <!-- any attribute starting with "data-" are replaced (if it ends with "jpg", "jpeg" or "png"). For lazy-loading -->
```

*output:*

```html
<img src="image.jpg.webp">
<img src="1.jpg.webp" srcset="2.jpg.webp 1000w">
<picture>
    <source srcset="1.jpg.webp" type="image/webp">
    <source srcset="2.jpg.webp" type="image/webp">
    <source srcset="3.gif"> <!-- gifs are skipped in default behaviour -->
    <source srcset="4.jpg?width=200"> <!-- urls with query string are skipped in default behaviour -->
</picture>
<div style="background-image: url('image.jpeg.webp')"></div>
<style>
#hero {
    background: lightblue url("image.png.webp") no-repeat fixed center;;
}
</style>
<input type="button" src="1.jpg.webp">
<img data-src="image.jpg.webp"> <!-- any attribute starting with "data-" are replaced (if it ends with "jpg", "jpeg" or "png"). For lazy-loading -->
```

Default behaviour of *ImageUrlReplacer::replace*:
- The modified URL is the same as the original, with ".webp" appended (to change, override the `replaceUrl` function)
- Only replaces URLs that ends with "png", "jpg" or "jpeg" (no query strings either) (to change, override the `replaceUrl` function)
- Attribute search/replace limits to these tags: *&lt;img&gt;*, *&lt;source&gt;*, *&lt;input&gt;* and *&lt;iframe&gt;* (to change, override the `$searchInTags` property)
- Attribute search/replace limits to these attributes: "src", "src-set" and any attribute starting with "data-" (to change, override the `attributeFilter` function)
- Urls inside styles are replaced too (*background-image* and *background* properties)

The behaviour can be modified by extending *ImageUrlReplacer* and overriding public methods such as *replaceUrl*

ImageUrlReplacer uses the  `Sunra\PhpSimple\HtmlDomParser`[library](https://github.com/sunra/php-simple-html-dom-parser) for parsing and modifying HTML. It wraps [simplehtmldom](http://simplehtmldom.sourceforge.net/). Simplehtmldom supports invalid HTML (it does not touch the invalid parts)


### Example: Customized behaviour

```php
class ImageUrlReplacerCustomReplacer extends ImageUrlReplacer
{
    public function replaceUrl($url) {
        // Only accept urls ending with "png", "jpg", "jpeg"  and "gif"
        if (!preg_match('#(png|jpe?g|gif)$#', $url)) {
            return;
        }

        // Only accept full urls (beginning with http:// or https://)
        if (!preg_match('#^https?://#', $url)) {
            return;
        }

        // PS: You probably want to filter out external images too...

        // Simply append ".webp" after current extension.
        // This strategy ensures that "logo.jpg" and "logo.gif" gets counterparts with unique names
        return $url . '.webp';
    }

    public function attributeFilter($attrName) {
        // Don't allow any "data-" attribute, but limit to attributes that smells like they are used for images
        // The following rule matches all attributes used for lazy loading images that we know of
        return preg_match('#^(src|srcset|(data-[^=]*(lazy|small|slide|img|large|src|thumb|source|set|bg-url)[^=]*))$#i', $attrName);

        // If you want to limit it further, only allowing attributes known to be used for lazy load,
        // use the following regex instead:
        //return preg_match('#^(src|srcset|data-(src|srcset|cvpsrc|cvpset|thumb|bg-url|large_image|lazyload|source-url|srcsmall|srclarge|srcfull|slide-img|lazy-original))$#i', $attrName);
    }
}

$modifiedHtml = ImageUrlReplacerCustomReplacer::replace($html);
```


## 2. Replacing *&lt;img&gt;* tags with *&lt;picture&gt;* tags

The *PictureTags::replace($html)* method accepts a piece of HTML and returns HTML where where all &lt;img&gt; tags have been replaced with &lt;picture&gt; tags, adding webp versions to sources

Usage:

```php
$modifiedHtml = PictureTags::replace($html);
```

#### Example replacements:

*Input:*
```html
<img src="1.png">
<img srcset="3.jpg 1000w" src="3.jpg">
<img data-lazy-src="9.jpg" style="border:2px solid red" class="something">
<figure class="wp-block-image">
    <img src="12.jpg" alt="" class="wp-image-6" srcset="12.jpg 492w, 12-300x265.jpg 300w" sizes="(max-width: 492px) 100vw, 492px">
</figure>
```

*Output*:
```html
<picture><source srcset="1.png.webp" type="image/webp"><img src="1.png" class="webpexpress-processed"></picture>
<picture><source srcset="3.jpg.webp 1000w" type="image/webp"><img srcset="3.jpg 1000w" src="3.jpg" class="webpexpress-processed"></picture>
<picture><source data-lazy-src="9.jpg.webp" type="image/webp"><img data-lazy-src="9.jpg" style="border:2px solid red" class="something webpexpress-processed"></picture>
<figure class="wp-block-image">
  <picture><source srcset="12.jpg.webp 492w, 12-300x265.jpg.webp 300w" sizes="(max-width: 492px) 100vw, 492px" type="image/webp"><img src="12.jpg" alt="" class="wp-image-6 webpexpress-processed" srcset="12.jpg 492w, 12-300x265.jpg 300w" sizes="(max-width: 492px) 100vw, 492px"></picture>
</figure>'
```

Note that with the picture tags, it is still the img tag that shows the selected image. The picture tag is just a wrapper.
So it is correct behaviour not to copy the *style*, *width*, *class* or any other attributes to the picture tag. See [issue #9](https://github.com/rosell-dk/dom-util-for-webp/issues/9).


As with `ImageUrlReplacer`, you can override the *replaceUrl* function. There is however currently no other methods to override.

`PictureTags` currently uses regular expressions to do the replacing. There are plans to change implementation to use `Sunra\PhpSimple\HtmlDomParser`, like our `ImageUrlReplacer` class does.
