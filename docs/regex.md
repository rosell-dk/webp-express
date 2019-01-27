Pattern used for image urls:
    in attributes: https://regexr.com/46jat
    in css:        https://regexr.com/46jcg

In case reqexr.com should be down, here are the content:


# in attributes

*pattern:*
(?<=(?:<(img|source|input|iframe)[^>]*\s+(src|srcset|data-[^=]*)\s*=\s*[\"\']?))(?:[^\"\'>]+)(\.png|\.jp[e]?g)(\s\d+w)?(?=\/?[\"\'\s\>])

*text:*
Notice: The pattern is meant for PHP and contains syntax which only works in some browsers. It works in Chrome. Not in Firefox.

The following should produce matches:
<img src="header.jpg">
<img src="/header.jpg">
<img src="http://example.com/header.jpeg" alt="">
<img src="http://example.com/header.jpg">
<img src="http://example.com/header.jpg"/>
<img src = "http://example.com/header.jpg">
<img src=http://example.com/header.jpg alt="">
<img src=http://example.com/header.jpg>
<img src=http://example.com/header.jpg alt="hello">
<img src=http://example.com/header.jpg />
<img src=http://example.com/header_.jpg/>
<picture><source src="http://example.com/header.jpg"><img src="http://example.com/header.jpg"></picture>
<input type="image" src="http://example.com/flamingo.jpg">
<iframe src="http://example.com/image.jpg"></iframe>



In srcset, the whole attribute must be matched
<img src="http://example.com/header.jpg" srcset="http://example.com/header.jpg 1000w">
<img src="http://example.com/header.jpg" srcset="http://example.com/header.jpg 1000w,http://example.com/header.jpg 1000w, http://example.com/header.jpg 2000w">
<img src="http://example.com/header.jpg" srcset="http://example.com/header-150x150.jpg 500w,http://example.com/header.jpg-300x300.jpg" sizes="(max-width: 480px) 100vw, (max-width: 900px) 33vw, 254px" alt="" width="100" height="100">

Common lazy load attributes are matched:
<img data-cvpsrc="http://example.com/header.jpg">
<img data-cvpset="http://example.com/header.jpg">
<img data-thumb="http://example.com/header.jpg">
<img data-bg-url="http://example.com/header.jpg">
<img data-large_image="http://example.com/header.jpg">
<img data-lazyload="http://example.com/header.jpg">
<img data-source-url="http://example.com/header.jpg">
<img data-srcsmall="http://example.com/header.jpg">
<img data-srclarge="http://example.com/header.jpg">
<img data-srcfull="http://example.com/header.jpg">
<img data-slide-img="http://example.com/header.jpg">
<img data-lazy-original="http://example.com/header.jpg">


The following should NOT produce matches:
-----------------------------------------

Ignore URLs with query string:
<img src="http://example.com/header.jpg?width=200">

<img src="http://example.com/tegning.jpg.webp" alt="">
<img src="http://example.com/tegning.jpglidilo" alt="">
<img src="http://example.com/header.jpg/hi-res">
<img src=http://example.com/header.gif alt=nice-jpg>
<img src="http://example.com/tegning.webp" alt="">
src="http://example.com/header.jpeg"
<article data-src="http://example.com/header.jpg" />
<img><script src="http://example.com/script.js?preload=image.jpg">


I use another pattern for matching image urls in styles: https://regexr.com/46jcg

It matches stuff like this:
<div style="background-image: url('http://example.com/image.png'), url("/image2.jpeg", url(http://example.com/image3.jpg);"></div>
<div style="background: url ("http://example.com/image2.jpg")"></div>
<style>#myphoto {background: url("http://example.com/image2.jpg")}</style>

I have another pattern where we allow QS here: https://regexr.com/46ivi

PS: The rules are used for the WebP Express plugin for Wordpress

PPS: This regex is used in WPFastestCache (not just images)
// $content = preg_replace_callback("/(srcset|src|href|data-cvpsrc|data-cvpset|data-thumb|data-bg-url|data-large_image|data-lazyload|data-source-url|data-srcsmall|data-srclarge|data-srcfull|data-slide-img|data-lazy-original)\s{0,2}\=[\'\"]([^\'\"]+)[\'\"]/i", array($this, 'cdn_replace_urls'), $content);

PPPS:
As we are limiting to a few tags (img, source, input, etc), and only match image urls ending with (png|jpe?g), I deem it ok to match in ANY "data-" attribute.
But if you want to limit it to attributes that smells like they are used for images you can do this:
(src|srcset|data-[^=]*(lazy|small|slide|img|large|src|thumb|source|set|bg-url)[^=]*)
That will catch the following known and more: data-cvpsrc|data-cvpset|data-thumb|data-bg-url|data-large_image|data-lazyload|data-source-url|data-srcsmall|data-srclarge|data-srcfull|data-slide-img|data-lazy-original


# in style

*pattern:*
((?<=(?:((style\s*=)|(\<\s*style)).*background(-image)?\s*:\s*url\s*\([\"\']?)|(((style\s*=)|(\<\s*style)).*url.*,\s*url\([\"\']?))[^\"\']*\.(jpe?g|png))(?=[\"\'\s\>)])

*text:*
Notice: The pattern is meant for PHP and contains syntax which only works in some browsers. It works in Chrome. Not in Firefox.

The following should produce matches:

<style>#myphoto {background: url("http://example.com/image2.jpg")}</style>
<div style="background-image: url('http://example.com/image.png'), url("/image2.jpeg"), url(http://example.com/image3.jpg);"></div>
<div style="background: url ("http://example.com/image2.jpg")"></div>
<style>#myphoto {background: url("http://example.com/image2.jpg"), url("image2.jpeg"}</style>

Not these:
----------

GIFs are disallowed:
<div style="background-image: url("http://example.com/image.gif"), url("http://example.com/image2.gif", url("image3.gif");"></div>

Querystrings are disallowed:
<div style="background-image: url('http://example.com/image.jpg?no-qs!')"></div>

HTML attributes disallowed:
<img src="header.jpg">

Go with style: background: url("http://example.com/image2.jpg")


And none of this either:

<div style="background-image: url('http://example.com/image.jpgelegi')"></div>
<img src="header.jpg">
<img src="/header.jpg">
<img src="http://example.com/header.jpeg" alt="">
<img src="http://example.com/header.jpg">
<img src="http://example.com/header.jpg"/>
<img src = "http://example.com/header.jpg">
<img src=http://example.com/header.jpg alt="">
<img src=http://example.com/header.jpg>
<img src=http://example.com/header.jpg alt="hello">
<img src=http://example.com/header.jpg />
<img src=http://example.com/header_.jpg/>
<picture><source src="http://example.com/header.jpg"><img src="http://example.com/header.jpg"></picture>
<input type="image" src="http://example.com/flamingo.jpg">
<iframe src="http://example.com/image.jpg"></iframe>

<img src="http://example.com/header.jpg" srcset="http://example.com/header.jpg 1000w">
<img src="http://example.com/header.jpg" srcset="http://example.com/header.jpg 1000w,http://example.com/header.jpg 1000w, http://example.com/header.jpg 2000w">
<img src="http://example.com/header.jpg" srcset="http://example.com/header-150x150.jpg 500w,http://example.com/header.jpg-300x300.jpg" sizes="(max-width: 480px) 100vw, (max-width: 900px) 33vw, 254px" alt="" width="100" height="100">

<img src="http://example.com/tegning.jpg.webp" alt="">
<img src="http://example.com/tegning.jpglidilo" alt="">
<img src="http://example.com/header.jpg/hi-res">
<img src=http://example.com/header.gif alt=nice-jpg>
<img src="http://example.com/tegning.webp" alt="">
src="http://example.com/header.jpeg"
<article data-src="http://example.com/header.jpg" />
<img><script src="http://example.com/script.js?preload=image.jpg">


I use another pattern for matching image urls in HTML attributes:
https://regexr.com/46jat
