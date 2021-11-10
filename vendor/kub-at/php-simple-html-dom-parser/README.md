php-simple-html-dom-parser
==========================

Version 1.9.1 - PHP 7.3 compatible
PHP Simple HTML DOM Parser changelog: https://sourceforge.net/projects/simplehtmldom/files/simplehtmldom/1.9.1/


Install
-------

```
composer require kub-at/php-simple-html-dom-parser
```

Usage
-----

```php
use KubAT\PhpSimple\HtmlDomParser;

...
$dom = HtmlDomParser::str_get_html( $str );
or
$dom = HtmlDomParser::file_get_html( $file_name );

$elems = $dom->find($elem_name);
...

```
