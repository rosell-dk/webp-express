# Running your own custom tests using the *customTest* method

A typical test s mentioned, a test has three phases:
1. Writing the test files to the directory in question
2. Doing a request (in advanced cases, more)
3. Interpreting the request

So, in order for *customTest()*, it needs to know. 1) What files are needed? 2) Which file should be requested? 3) How should the response be interpreted?

Here is a definition which can be used for implementing the *headerSetWorks()* functionality yourself. It's in YAML because it is more readable like this.

<details><summary><u>Click here to see the PHP example</u></summary>
<p><br>
<b>PHP example</b>

```php
<?php
require 'vendor/autoload.php';
use HtaccessCapabilityTester\HtaccessCapabilityTester;

$hct = new HtaccessCapabilityTester($baseDir, $baseUrl);

$htaccessFile = <<<'EOD'
<IfModule mod_headers.c>
Header set X-Response-Header-Test: test
</IfModule>
EOD;

$test = [
    'subdir' => 'header-set',
    'files' => [
        ['.htaccess', $htaccessFile],
        ['request-me.txt', "hi"],
    ],
    'request' => 'request-me.txt',
    'interpretation' => [
        ['success', 'headers', 'contains-key-value', 'X-Response-Header-Test', 'test'],

        // the next three mappings are actually not necessary, as customTest() does standard
        // error handling automatically (can be turned off)
        ['failure', 'status-code', 'equals', '500'],       
        ['inconclusive', 'status-code', 'equals', '403'],
        ['inconclusive', 'status-code', 'equals', '404'],
    ]
];

if ($hct->customTest($test)) {
    // setting a header in the .htaccess works!
}
```

</p>
</details>

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
    - [success, headers, contains-key-value, 'X-Response-Header-Test', 'test']
    - [failure, status-code, equals, '500']       # actually not needed (part of standard error handling)
    - [inconclusive, status-code, equals, '403']  # actually not needed (part of standard error handling)
    - [inconclusive, status-code, equals, '404']  # actually not needed (part of standard error handling)
    - [failure]
```

In fact, this is more or less how this library implements it.

The test definition has the following sub-definitions:
- *subdir*: Defines which subdir the test files should reside in
- *files*: Defines the files for the test (filename and content)
- *request*: Defines which file that should be requested
- *interpretation*: Defines how to interprete the response. It consists of a list of mappings is read from the top until one of the conditions is met. The first line for example translates to "Map to success if the body of the response equals '1'". If none of the conditions are met, the result is automatically mapped to 'inconclusive'.

For more info, look in the API (below). For real examples, check out the classes in the "Testers" dir - most of them are defined in this "language"
