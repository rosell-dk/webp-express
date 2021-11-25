# htaccess-capability-tester

[![Latest Stable Version](https://img.shields.io/packagist/v/rosell-dk/htaccess-capability-tester.svg?style=flat-square)](https://packagist.org/packages/rosell-dk/htaccess-capability-tester)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://php.net)
[![Build Status](https://img.shields.io/github/workflow/status/rosell-dk/webp-convert/PHP?logo=GitHub&style=flat-square)](https://github.com/rosell-dk/webp-convert/actions/workflows/php.yml)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/rosell-dk/htaccess-capability-tester.svg?style=flat-square)](https://scrutinizer-ci.com/g/rosell-dk/htaccess-capability-tester/code-structure/master/code-coverage/src/)
[![Quality Score](https://img.shields.io/scrutinizer/g/rosell-dk/htaccess-capability-tester.svg?style=flat-square)](https://scrutinizer-ci.com/g/rosell-dk/htaccess-capability-tester/)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/rosell-dk/htaccess-capability-tester/blob/master/LICENSE)


Detect *.htaccess* capabilities through live tests.

There are cases where the only way to to learn if a given *.htaccess* capability is enabled / supported on a system is by examining it "from the outside" through a HTTP request. This library is build to handle such testing easily.

This is what happens behind the scenes:
1. Some test files for a given test are put on the server (at least an *.htaccess* file)
2. The test is triggered by doing a HTTP request
3. The response is interpreted

## Usage

To use the library, you must provide a path to where the test files are going to be put and the corresponding URL that they can be reached. Besides that, you just need to pick one of the tests that you want to run.

```php
require 'vendor/autoload.php';
use HtaccessCapabilityTester\HtaccessCapabilityTester;

$hct = new HtaccessCapabilityTester($baseDir, $baseUrl);

if ($hct->moduleLoaded('headers')) {
    // mod_headers is loaded (tested in a real .htaccess by using the "IfModule" directive)
}
if ($hct->rewriteWorks()) {    
    // rewriting works

}
if ($hct->htaccessEnabled() === false) {
    // Apache has been configured to ignore .htaccess files
}

// A bunch of other tests are available - see API
```
While having a reliable *moduleLoaded()* method is a great improvement over current state of affairs, beware that it is possible that the server has ie *mod_rewrite* enabled, but at the same time has disallowed using ie the "RewriteRule" directive in *.htaccess* files. This is why the library has the *rewriteWorks()* method and similar methods for testing various capabilites fully (check the API overview below). Providing tests for all kinds of functionality, would however be too much for any library. Instead this library makes it a breeze to define a custom test and run it through the *customTest($def)* method. To learn more, check out the [Running your own custom tests](https://github.com/rosell-dk/htaccess-capability-tester/blob/master/docs/Running%20your%20own%20custom%20tests.md) document.

## API overview

### Test methods in HtaccessCapabilityTester

All the test methods returns a test result, which is *true* for success, *false* for failure or *null* for inconclusive.

The tests have the following in common:
- If the server has been set up to ignore *.htaccess* files entirely, the result will be *failure*.
- If the server has been set up to disallow the directive being tested (AllowOverride), the result is *failure* (both when configured to ignore and when configured to go fatal)
- A *403 Forbidden* results in *inconclusive*. Why? Because it could be that the server has been set up to forbid access to files matching a pattern that our test file unluckily matches. In most cases, this is unlikely, as most tests requests files with harmless-looking file extensions (often a "request-me.txt"). A few of the tests however requests a "test.php", which is more likely to be denied.
- A *404 Not Found* results in *inconclusive*
- If the request fails completely (ie timeout), the result is *inconclusive*


Most tests are implemented as a definition such as the one accepted in *customTest()*. This means that if you want one of the tests provided by this library to work slightly differently, you can easily grab the code in the corresponding class in the *Testers* directory, make your modification and call *customTest()*.

<details><summary><b>addTypeWorks()</b></summary>
<p><br>
Tests if the *AddType* directive works.

Implementation (YAML definition):

```yaml
subdir: add-type
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_mime.c>
          AddType image/gif .test
      </IfModule>
  - filename: 'request-me.test'
    content: 'hi'
request:
  url: 'request-me.test'

interpretation:
 - ['success', 'headers', 'contains-key-value', 'Content-Type', 'image/gif']
 - ['inconclusive', 'status-code', 'not-equals', '200']
 - ['failure', 'headers', 'not-contains-key-value', 'Content-Type', 'image/gif']
```

</p>
</details>

<details><summary><b>contentDigestWorks()</b></summary>
<p>

Implementation (YAML definition):

```yaml
subdir: content-digest
subtests:
  - subdir: on
    files:
    - filename: '.htaccess'
      content: |
        ContentDigest On
    - filename: 'request-me.txt'
      content: 'hi'
    request:
      url: 'request-me.txt'
    interpretation:
      - ['failure', 'headers', 'not-contains-key', 'Content-MD5'],

    - subdir: off
      files:
        - filename: '.htaccess'
          content: |
             ContentDigest Off
        - filename: 'request-me.txt'
          content: 'hi'
      request:
        url: 'request-me.txt'

      interpretation:
        - ['failure', 'headers', 'contains-key', 'Content-MD5']
        - ['inconclusive', 'status-code', 'not-equals', '200']
        - ['success', 'status-code', 'equals', '200']
```

</p>
</details>

<details><summary><b>crashTest($rules, $subdir)</b></summary>
<p><br>
Test if some rules makes the server "crash" (respond with 500 Internal Server Error for requests to files in the folder).
You pass the rules that you want to check.
You can optionally pass in a subdir for the tests. If you do not do that, a hash of the rules will be used.

Implementation (PHP):

```php
/**
 * @param string $htaccessRules  The rules to check
 * @param string $subSubDir      subdir for the test files. If not supplied, a fingerprint of the rules will be used
 */
public function __construct($htaccessRules, $subSubDir = null)
{
    if (is_null($subSubDir)) {
        $subSubDir = hash('md5', $htaccessRules);
    }

    $test = [
        'subdir' => 'crash-tester/' . $subSubDir,
        'subtests' => [
            [
                'subdir' => 'the-suspect',
                'files' => [
                    ['.htaccess', $htaccessRules],
                    ['request-me.txt', 'thanks'],
                ],
                'request' => [
                    'url' => 'request-me.txt',
                    'bypass-standard-error-handling' => ['all']
                ],
                'interpretation' => [
                    ['success', 'status-code', 'not-equals', '500'],
                ]
            ],
            [
                'subdir' => 'the-innocent',
                'files' => [
                    ['.htaccess', '# I am no trouble'],
                    ['request-me.txt', 'thanks'],
                ],
                'request' => [
                    'url' => 'request-me.txt',
                    'bypass-standard-error-handling' => ['all']
                ],
                'interpretation' => [
                    // The suspect crashed. But if the innocent crashes too, we cannot judge
                    ['inconclusive', 'status-code', 'equals', '500'],

                    // The innocent did not crash. The suspect is guilty!
                    ['failure'],
                ]
            ],
        ]
    ];

    parent::__construct($test);
}
```

</p>
</details>

<details><summary><b>customTest($definition)</b></summary>
<p>

Allows you to run a custom test. Check out README.md for instructions

</p>
</details>

<details><summary><b>directoryIndexWorks()</b></summary>
<p><br>
Tests if DirectoryIndex works.

Implementation (YAML definition):

```yaml
subdir: directory-index
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_dir.c>
          DirectoryIndex index2.html
      </IfModule>
  - filename: 'index.html'
    content: '0'
  - filename: 'index2.html'
    content: '1'

request:
  url: ''   # We request the index, that is why its empty
  bypass-standard-error-handling: ['404']

interpretation:
  - ['success', 'body', 'equals', '1']
  - ['failure', 'body', 'equals', '0']
  - ['failure', 'status-code', 'equals', '404']  # "index.html" might not be set to index
```

</p>
</details>

<details><summary><b>headerSetWorks()</b></summary>
<p><br>
Tests if setting a response header works using the *Header* directive.

Implementation (YAML definition):

```yaml
subdir: header-set
files:
    - filename: '.htaccess'
      content: |
          <IfModule mod_headers.c>
              Header set X-Response-Header-Test: test
          </IfModule>
    - filename: 'request-me.txt'
      content: 'hi'

request:
    url: 'request-me.txt'

interpretation:
    - [success, headers, contains-key-value, 'X-Response-Header-Test', 'test'],
    - [failure]
```

</p>
</details>

<details><summary><b>htaccessEnabled()</b></summary>
<p><br>
Apache can be configured to ignore *.htaccess* files altogether. This method tests if the *.htaccess* file is processed at all

The method works by trying out a series of subtests until a conclusion is reached. It will never come out inconclusive.

How does it work?
- The first strategy is testing a series of features, such as `rewriteWorks()`. If any of them works, well, then the *.htaccess* must have been processed.
- Secondly, the `serverSignatureWorks()` is tested. The "ServerSignature" directive is special because it is in core and cannot be disabled with AllowOverride. If this test comes out as a failure, it is so *highly likely* that the .htaccess has not been processed, that we conclude that it has not.
- Lastly, if all other methods failed, we try calling `crashTest()` on an .htaccess file that we on purpose put syntax errors in. If it crashes, the .htaccess file must have been proccessed. If it does not crash, it has not. This last method is bulletproof - so why not do it first? Because it might generate an entry in the error log.

Main part of implementation:
```php
// If we can find anything that works, well the .htaccess must have been proccesed!
if ($hct->serverSignatureWorks()    // Override: None,  Status: Core, REQUIRES PHP
    || $hct->contentDigestWorks()   // Override: Options,  Status: Core
    || $hct->addTypeWorks()         // Override: FileInfo, Status: Base, Module: mime
    || $hct->directoryIndexWorks()  // Override: Indexes,  Status: Base, Module: mod_dir
    || $hct->rewriteWorks()         // Override: FileInfo, Status: Extension, Module: rewrite
    || $hct->headerSetWorks()       // Override: FileInfo, Status: Extension, Module: headers
) {
    $status = true;
} else {
    // The serverSignatureWorks() test is special because if it comes out as a failure,
    // we can be *almost* certain that the .htaccess has been completely disabled

    $serverSignatureWorks = $hct->serverSignatureWorks();
    if ($serverSignatureWorks === false) {
        $status = false;
        $info = 'ServerSignature directive does not work - and it is in core';
    } else {
        // Last bullet in the gun:
        // Try an .htaccess with syntax errors in it.
        // (we do this lastly because it may generate an entry in the error log)
        $crashTestResult = $hct->crashTest('aoeu', 'htaccess-enabled-malformed-htaccess');
        if ($crashTestResult === false) {
            // It crashed, - which means .htaccess is processed!
            $status = true;
            $info = 'syntax error in an .htaccess causes crash';
        } elseif ($crashTestResult === true) {
            // It did not crash. So the .htaccess is not processed, as syntax errors
            // makes servers crash
            $status = false;
            $info = 'syntax error in an .htaccess does not cause crash';
        } elseif (is_null($crashTestResult)) {
            // It did crash. But so did a request to an innocent text file in a directory
            // without a .htaccess file in it. Something is making all requests fail and
            // we cannot judge.
            $status = null;
            $info = 'all requests results in 500 Internal Server Error';
        }
    }
}
return new TestResult($status, $info);
```

</p>
</details>

<details><summary><b>innocentRequestWorks()</b></summary>
<p><br>
Tests if an innocent request to a text file works. Most tests use this test when they get a 500 Internal Error, in order to decide if this is a general problem (general problem => inconclusive, specific problem => failure).

Implementation (YAML definition):

```yaml
subdir: innocent-request
files:
  - filename: 'request-me.txt'
    content: 'thank you my dear'

request:
  url: 'request-me.txt'
  bypass-standard-error-handling: 'all'

interpretation:
  - ['success', 'status-code', 'equals', '200']
  - ['inconclusive', 'status-code', 'equals', '403']
  - ['inconclusive', 'status-code', 'equals', '404']
  - ['failure']
```

</p>
</details>

<details><summary><b>moduleLoaded($moduleName)</b></summary>
<p><br>
Tests if a given module is loaded. Note that you in most cases would want to not just know if a module is loaded, but also ensure that the directives you are using are allowed. So for example, instead of calling `moduleLoaded("rewrite")`, you should probably call `rewriteWorks()`;

Implementation:

The method has many ways to test if a module is loaded, based on what works. If for example setting headers has been established to be working and we want to know if "setenvif" module is loaded, the following .htaccess rules will be tested, and the response will be examined.
```
<IfModule mod_setenvif.c>
    Header set X-Response-Header-Test: 1
</IfModule>
<IfModule !mod_setenvif.c>
    Header set X-Response-Header-Test: 0
</IfModule>
```

</p>
</details>

<details><summary><b>passingInfoFromRewriteToScriptThroughEnvWorks()</b></summary>
<p><br>
Say you have a rewrite rule that points to a PHP script and you would like to pass some information along to the PHP. Usually, you will just pass it in the query string. But this won't do if the information is sensitive. In that case, there are some tricks available. The trick being tested here tells the RewriteRule directive to set an environment variable, which in many setups can be picked up in the script.

Implementation (YAML definition):

```yaml
subdir: pass-info-from-rewrite-to-script-through-env
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_rewrite.c>

          # Testing if we can pass environment variable from .htaccess to script in a RewriteRule
          # We pass document root, because that can easily be checked by the script

          RewriteEngine On
          RewriteRule ^test\.php$ - [E=PASSTHROUGHENV:%{DOCUMENT_ROOT},L]

      </IfModule>
  - filename: 'test.php'
    content: |
      <?php

      /**
       *  Get environment variable set with mod_rewrite module
       *  Return false if the environment variable isn't found
       */
      function getEnvPassedInRewriteRule($envName) {
          // Environment variables passed through the REWRITE module have "REWRITE_" as a prefix
          // (in Apache, not Litespeed, if I recall correctly).
          // Multiple iterations causes multiple REWRITE_ prefixes, and we get many environment variables set.
          // We simply look for an environment variable that ends with what we are looking for.
          // (so make sure to make it unique)
          $len = strlen($envName);
          foreach ($_SERVER as $key => $item) {
              if (substr($key, -$len) == $envName) {
                  return $item;
              }
          }
          return false;
      }

      $result = getEnvPassedInRewriteRule('PASSTHROUGHENV');
      if ($result === false) {
          echo '0';
          exit;
      }
      echo ($result == $_SERVER['DOCUMENT_ROOT'] ? '1' : '0');

request:
  url: 'test.php'

interpretation:
  - ['success', 'body', 'equals', '1']
  - ['failure', 'body', 'equals', '0']
  - ['inconclusive', 'body', 'begins-with', '<?php']
  - ['inconclusive']
 ```

</p>
</details>

<details><summary><b>passingInfoFromRewriteToScriptThroughRequestHeaderWorks()</b></summary>
<p><br>
Say you have a rewrite rule that points to a PHP script and you would like to pass some information along to the PHP. Usually, you will just pass it in the query string. But this won't do if the information is sensitive. In that case, there are some tricks available. The trick being tested here tells the RewriteRule directive to set an environment variable which a RequestHeader directive picks up on and passes on to the script in a request header.

Implementation (YAML definition):

```yaml
subdir: pass-info-from-rewrite-to-script-through-request-header
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_rewrite.c>
          RewriteEngine On

          # Testing if we can pass an environment variable through a request header
          # We pass document root, because that can easily be checked by the script

          <IfModule mod_headers.c>
            RequestHeader set PASSTHROUGHHEADER "%{PASSTHROUGHHEADER}e" env=PASSTHROUGHHEADER
          </IfModule>
          RewriteRule ^test\.php$ - [E=PASSTHROUGHHEADER:%{DOCUMENT_ROOT},L]

      </IfModule>
  - filename: 'test.php'
    content: |
      <?php
      if (isset($_SERVER['HTTP_PASSTHROUGHHEADER'])) {
          echo ($_SERVER['HTTP_PASSTHROUGHHEADER'] == $_SERVER['DOCUMENT_ROOT'] ? 1 : 0);
          exit;
      }
      echo '0';

request:
  url: 'test.php'

interpretation:
  - ['success', 'body', 'equals', '1']
  - ['failure', 'body', 'equals', '0']
  - ['inconclusive', 'body', 'begins-with', '<?php']
  - ['inconclusive']
```

</p>
</details>

<details><summary><b>rewriteWorks()</b></summary>
<p><br>
Tests if rewriting works.

Implementation (YAML definition):
```yaml
subdir: rewrite
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_rewrite.c>
          RewriteEngine On
          RewriteRule ^0\.txt$ 1\.txt [L]
      </IfModule>
  - filename: '0.txt'
    content: '0'
  - filename: '1.txt'
    content: '1'

request:
  url: '0.txt'

interpretation:
  - [success, body, equals, '1']
  - [failure, body, equals, '0']
```

</p>
</details>

<details><summary><b>requestHeaderWorks()</b></summary>
<p><br>
Tests if a request header can be set using the *RequestHeader* directive.

Implementation (YAML definition):

```yaml
subdir: request-header
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_headers.c>
          # Certain hosts seem to strip non-standard request headers,
          # so we use a standard one to avoid a false negative
          RequestHeader set User-Agent "request-header-test"
      </IfModule>
  - filename: 'test.php'
    content: |
      <?php
      if (isset($_SERVER['HTTP_USER_AGENT'])) {
          echo  $_SERVER['HTTP_USER_AGENT'] == 'request-header-test' ? 1 : 0;
      } else {
          echo 0;
      }

request:
  url: 'test.php'

interpretation:
  - ['success', 'body', 'equals', '1']
  - ['failure', 'body', 'equals', '0']
  - ['inconclusive', 'body', 'begins-with', '<?php']
```

</p>
</details>

<details><summary><b>serverSignatureWorks()</b></summary>
<p><br>
Tests if the *ServerSignature* directive works.

Implementation (YAML definition):
```yaml
subdir: server-signature
subtests:
  - subdir: on
    files:
    - filename: '.htaccess'
      content: |
        ServerSignature On
    - filename: 'test.php'
      content: |
      <?php
      if (isset($_SERVER['SERVER_SIGNATURE']) && ($_SERVER['SERVER_SIGNATURE'] != '')) {
          echo 1;
      } else {
          echo 0;
      }
    request:
      url: 'test.php'
    interpretation:
      - ['inconclusive', 'body', 'isEmpty']
      - ['inconclusive', 'status-code', 'not-equals', '200']
      - ['failure', 'body', 'equals', '0']

  - subdir: off
    files:
    - filename: '.htaccess'
      content: |
        ServerSignature Off
    - filename: 'test.php'
      content: |
      <?php
      if (isset($_SERVER['SERVER_SIGNATURE']) && ($_SERVER['SERVER_SIGNATURE'] != '')) {
          echo 0;
      } else {
          echo 1;
      }
    request:
      url: 'test.php'
    interpretation:
      - ['inconclusive', 'body', 'isEmpty']
      - ['success', 'body', 'equals', '1']
      - ['failure', 'body', 'equals', '0']
      - ['inconclusive']
```

</p>
</details>

### Other methods in HtaccessCapabilityTester

<details><summary><b>setHttpRequester($requester)</b></summary>
<p><br>
This allows you to use another object for making HTTP requests than the standard one provided by this library. The standard one uses `file_get_contents` to make the request and is implemented in `SimpleHttpRequester.php`. You might for example prefer to use *curl* or, if you are making a Wordpress plugin, you might want to use the one provided by the Wordpress framework.
</p>
</details>

<details><summary><b>setTestFilesLineUpper($testFilesLineUpper)</b></summary>
<p><br>
This allows you to use another object for lining up the test files than the standard one provided by this library. The standard one uses `file_put_contents` to save files and is implemented in `SimpleTestFileLineUpper.php`. You will probably not need to swap the test file line-upper.
</p>
</details>

## Stable API?
The 0.9 release is just about right. I do not expect any changes in the part of the API that is mentioned above. So, if you stick to that, it should still work, when the 1.0 release comes.

Changes in the new 0.9 release:
- Request failures (such as timeout) results in *inconclusive*.
- If you have implemented your own HttpRequester rather than using the default, you need to update it. It must now return status code "0" if the request failed (ie timeout)

Expected changes in the 1.0 release:
- TestResult class might be disposed off so the "internal" Tester classes also returns bool|null.
- Throw custom exception when test file cannot be created

## Installation
Require the library with *Composer*, like this:

```text
composer require rosell-dk/htaccess-capability-tester
```
