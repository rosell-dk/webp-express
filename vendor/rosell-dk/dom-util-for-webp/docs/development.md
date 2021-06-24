# Development

## Setting up the environment.

First, clone the repository:
```
cd whatever/folder/you/want
git clone git@github.com:rosell-dk/dom-util-for-webp.git
```

Then install the dev tools with composer:

```
composer install
```

If you don't have composer yet:
- Get it ([download phar](https://getcomposer.org/composer.phar) and move it to /usr/local/bin/composer)
- PS: PHPUnit requires php-xml, php-mbstring and php-curl. To install: `sudo apt install php-xml php-mbstring curl php-curl`


Make sure you have [xdebug](https://xdebug.org/docs/install) installed, if you want phpunit tog generate code coverage report

## Unit Testing

To run all the unit tests do this:
```
composer test
```
This also runs tests on the builds.
If you do not the coverage report:
```
composer phpunit
```

Individual test files can be executed like this:
```
composer phpunit tests/ImageUrlReplacerTest.php
composer phpunit tests/PictureTagsTest.php
```

Note:
The code coverage requires [xdebug](https://xdebug.org/docs/install)
