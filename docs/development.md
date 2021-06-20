## Updating the vendor dir:

1. Run `composer update` in the root (plugin root).
2. Run `composer dump-autoload -o`
(for some reason, `vendor/composer/autoload_classmap.php` looses all its mappings on composer update). It also looses them on a `composer dump-autoload` (without the -o option).
It actually seems that the mappings are not needed. It seems to work fine when I alter autoload_real to not use the static loader. But well, I'm reluctant to change anything that works.

3. Remove unneeded files:

- Open bash
- cd into the folder

```
rm -r vendor/rosell-dk/webp-convert/docs
rm -r vendor/rosell-dk/webp-convert/src/Helpers/*.txt
rm vendor/rosell-dk/dom-util-for-webp/phpstan.neon
rm composer.lock
rmdir vendor/bin
```

3. Commit on git
