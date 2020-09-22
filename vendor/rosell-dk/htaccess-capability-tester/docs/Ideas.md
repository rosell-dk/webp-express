Which name is best?
```php


if ($hct->rewriteWorks()) {

}
if ($hct->addTypeWorks()) {

}
if ($hct->serverSignatureWorks()) {

}
if ($hct->contentDigestWorks()) {
}


$hct->rewriteWorks();
$hct->canRewrite();
$hct->rewrite()
$hct->isRewriteWorking();
$hct->canUseRewrite();
$hct->hasRewrite()
$hct->mayRewrite()
$hct->doesRewriteWork();
$hct->rewriting();
$hct->rewritingWorks();
$hct->RewriteRule();
$hct->rewriteSupported();
$hct->rewriteWorks();
$hct->testRewriting();
$hct->test('RewriteRule');
$hct->runTest(new RewriteTester());
$hct->runTest()->RewriteRule();
$hct->canDoRewrite();
$hct->haveRewrite();
$hct->rewriteAvail();
$hct->isRewriteAvailable();
$hct->isRewriteAccessible();
$hct->isRewriteOperative();
$hct->isRewriteOperational();
$hct->isRewriteFunctional();
$hct->isRewritePossible();
$hct->isRewriteOk();
$hct->isRewriteFlying();
$hct->rewritePasses();

if ($hct->canRewrite()) {

}

if ($hct->rewriteWorks()) {

}

if ($hct->rewriteOk()) {

}
if ($hct->rewriteQM()) {

}

if ($hct->rewriteNA()) {

}

// --------------

$hct->canAddType();
$hct->addTypeWorks();
$hct->addTypeQM();

$hct->canUseAddType();
$hct->doesAddTypeWork();
$hct->addType();
$hct->AddType();
$hct->addTypeSupported();
$hct->addTypeWorks();
$hct->addTypeLive();
$hct->addTypeYes();
$hct->addTypeFF();    // fully functional
$hct->testAddType();
$hct->test('AddType');
$hct->run(new AddTypeTester());
$hct->runTest('AddType');
$hct->runTest()->AddType();
$hct->runTest(\HtaccessCapabilityTester\AddType);
$hct->canIUse('AddType');

// ------------------

if ($hct->canContentDigest()) {
}

if ($hct->contentDigestWorks()) {
}

if ($hct->contentDigestOk()) {
}

if ($hct->contentDigestFF()) {
}


$hct->canContentDigest();
$hct->contentDigestWorks();
$hct->canUseContentDigest();
$hct->doesContentDigestWork();
$hct->contentDigest();
$hct->ContentDigest();


// ---------------------

if ($hct->serverSignatureWorks()) {
}

if ($hct->canSetServerSignature()) {

}

if ($hct->testServerSignature()) {

}

if ($hct->doesServerSignatureWork()) {

}
if ($hct->isServerSignatureAllowed()) {

}
if ($hct->isServerSignatureWorking()) {


}

// --------------------

$hct->modRewriteLoaded();

$hct->moduleLoaded('rewrite');

$hct->testModuleLoaded('rewrite');

$hct->modLoaded('rewrite');

// --------------------

$hct->doesThisCrash();
$hct->kaput();
$hct->ooo();
$hct->na();





```

# IDEA:
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
  - [interprete500, status-code, equals, '500']   # inconclusive if innocent also crashes, otherwise failure
  - [inconclusive, status-code, equals, '403']

  - if: [status-code, equals, '500']
    then:
      - if: [doesInnocentCrash()]
        then: inconclusive
        else: failure
  - [inconclusive]

```
or:
```yaml
interpretation:
  - [success, body, equals, '1']
  - [failure, body, equals, '0']
  - [handle-errors]   # Standard error handling (403, 404, 500)


```   


```php
[
  'interpretation' => [
    [
      'if' => ['body', 'equals', '1'],
      'then' => ['success']
    ],
    [
      'if' => ['body', 'equals', '0'],
      'then' => ['failure', 'no-effect']
    ],
    [
      'if' => ['status-code', 'equals', '500'],
      'then' => 'handle500()'
    ],
    [
      'if' => ['status-code', 'equals', '500'],
      'then' => 'handle500()'
    ]
  ]

```

```yaml

```

crashTestInnocent

handle500:
  returns "failure" if innocent request succeeds
  returns "inconclusive" if innocent request fails

handle403:
  if innocent request also 403, all requests probably does
  returns "failure" if innocent request succeeds
