## Updating the vendor dir:

1. Run `composer update` in the root.
2. Run `composer dump-autoload -o`
(for some reason, `vendor/composer/autoload_classmap.php` looses all its mappings on composer update). It also looses them on a `composer dump-autoload` (without the -o option).
It actually seems that the mappings are not needed. It seems to work fine when I alter autoload_real to not use the static loader. But well, I'm reluctant to change anything that works.

3. Remove unneeded files:

- Open bash
- cd into the folder

```
rm -r tests

rm -r vendor/rosell-dk/webp-convert/build-scripts
rm -r vendor/rosell-dk/webp-convert/tests
rm -r vendor/rosell-dk/webp-convert/build-tests-webp-convert
rm -r vendor/rosell-dk/webp-convert/build-tests-wod
rm -r vendor/rosell-dk/webp-convert/src-build
rm -r vendor/rosell-dk/webp-convert/docs
rm vendor/rosell-dk/webp-convert/*.sh
rm -r vendor/rosell-dk/webp-convert/src//Helpers/*.txt
rm vendor/rosell-dk/webp-convert/.gitignore

rm -r vendor/rosell-dk/webp-convert-cloud-service/tests
rm -r vendor/rosell-dk/webp-convert-cloud-service/docs

rm vendor/rosell-dk/dom-util-for-webp/phpstan.neon

```

3. Commit on git
