[![Build Status](https://travis-ci.org/onnov/detect-encoding.svg?branch=master)](https://travis-ci.org/onnov/detect-encoding)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/onnov/detect-encoding/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/onnov/detect-encoding/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/onnov/detect-encoding/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/onnov/detect-encoding/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/onnov/detect-encoding/v/stable)](https://packagist.org/packages/onnov/detect-encoding)
[![License](https://poser.pugx.org/onnov/detect-encoding/license)](https://packagist.org/packages/onnov/detect-encoding)

# Detect encoding

Text encoding definition class based on a range of code page character numbers.

So far, in PHP v7.* the `mb_detect_encoding` function does not work well.
Therefore, you have to somehow solve this problem.
This class is one solution.

Built-in encodings and accuracy:

letters ->   | 5     | 15    | 30    | 60    | 120   | 180   | 270
---          |   --- |  ---  | ---   |---    |---    |---    |---
windows-1251 | 99.13 | 98.83 | 98.54 | 99.04 | 99.73 | 99.93 | 100.0
koi8-r       | 99.89 | 99.98 | 100.0 | 100.0 | 100.0 | 100.0 | 100.0
iso-8859-5   | 81.79 | 99.27 | 99.98 | 100.0 | 100.0 | 100.0 | 100.0
ibm866       | 99.81 | 99.99 | 100.0 | 100.0 | 100.0 | 100.0 | 100.0
mac-cyrillic | 12.79 | 47.49 | 73.48 | 92.15 | 99.30 | 99.94 | 100.0 

Worst accuracy with mac-cyrillic, you need at least 60 characters to determine this encoding with an accuracy of 92.15%. Windows-1251 encoding also has very poor accuracy. This is because the numbers of their characters in the tables overlap very much.

Fortunately, mac-cyrillic and ibm866 encodings are not used to encode web pages. By default, they are disabled in the script, but you can enable them if necessary.

letters ->       | 5     | 10    | 15    | 30    | 60    |
---              |   --- |  ---  | ---   |---    |---    |
windows-1251     | 99.40 | 99.69 | 99.86 | 99.97 | 100.0 |
koi8-r           | 99.89 | 99.98 | 99.98 | 100.0 | 100.0 |
iso-8859-5       | 81.79 | 96.41 | 99.27 | 99.98 | 100.0 |

The accuracy of the determination is high even in short sentences from 5 to 10 letters. And for phrases from 60 letters, the accuracy of determination reaches 100%.

Determining the encoding is very fast, for example, text longer than 1,300,000 Cyrillic characters is checked in 0.00096 sec. (on my computer)

Link to the idea: http://patttern.blogspot.com/2012/07/php-python.html

## Installation
[Composer](https://getcomposer.org) (recommended)
Use Composer to install this library from Packagist: onnov/captcha

Run the following command from your project directory to add the dependency:
```bash
composer require onnov/detect-encoding
```

Alternatively, add the dependency directly to your composer.json file:
```json
{
    "require": {
        "onnov/detect-encoding": "^1.0"
    }
}
```

The classes in the project are structured according to the PSR-4 standard, so you can also use your own autoloader or require the needed files directly in your code.

## Usage
```php
use Onnov\DetectEncoding\EncodingDetector;
        
$detector = new EncodingDetector();
```

* Definition of text encoding:
```php
$text = 'Проверяемый текст';
$detector->getEncoding($text)
```

* Method for converting text of an unknown encoding into a given encoding, by default in utf-8
  optional parameters:
```php
/**
 * Method for converting text of an unknown encoding into a given encoding, by default in utf-8
 * optional parameters:
 * $extra = '//TRANSLIT' (default setting) , other options: '' or '//IGNORE'
 * $encoding = 'utf-8' (default setting) , other options: any encoding that is available iconv
 *
 * @param string $text
 * @param string $extra
 * @param string $encoding
 *
 * @return string
 * @throws RuntimeException
 */

$detector->iconvXtoEncoding($text)
```

* Method to enable encoding definition:
```php
$detector->enableEncoding([
    $detector::IBM866,
    $detector::MAC_CYRILLIC,
]);
```

* Method to disable encoding definition:
```php
$detector->disableEncoding([
    $detector::ISO_8859_5,
]);
```

* Method for adding custom encoding:
```php
$detector->addEncoding([
    'encodingName' => [
        'upper' => '1-50,200-250,253', // uppercase character number range
        'lower' => '55-100,120-180,199', // lowercase character number range
    ],
]);
```

* Method to get a custom encoding range:
```php
use Onnov\DetectEncoding\CodePage;
    
// utf-8 encoded alphabet
$cyrillicUppercase = 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФЧЦЧШЩЪЫЬЭЮЯ';
$cyrillicLowercase = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';
    
$codePage = new CodePage();
$encodingRange = $codePage->getRange($cyrillicUppercase, $cyrillicLowercase, 'koi8-u'));
```

## Symfony use
Add in services.yaml file:
```yaml
services:
    Onnov\DetectEncoding\EncodingDetector:
        autowire: true
```
