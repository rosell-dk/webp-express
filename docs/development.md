## Updating the vendor dir:

1. Run `composer update` in the root.
2. Remove unneeded files:

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
```
