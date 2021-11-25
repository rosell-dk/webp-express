<?php

namespace WebPExpress;

use \WebPConvert\Convert\Converters\Stack;
use \WebPConvert\WebPConvert;
use \ImageMimeTypeGuesser\ImageMimeTypeGuesser;

/**
 *
 */

class WCFMApi
{
    private static function doProcessRequest() {
      if (!check_ajax_referer('webpexpress-wcfm-nonce', 'nonce', false)) {
          throw new \Exception('The security nonce has expired. You need to reload (press F5) and try again)');
      }
      Validate::postHasKey('command');
      $command = sanitize_text_field(stripslashes($_POST['command']));

      switch ($command) {
        /*
        case 'get-tree':
          $result = self::processGetTree();
          break;*/
        case 'get-folder':
          $result = self::processGetFolder();
          break;
        case 'conversion-settings':
          $result = self::processConversionSettings();
          break;
        case 'info':
          $result = self::processInfo();
          break;
        case 'convert':
          $result = self::processConvert();
          break;
        case 'delete-converted':
          $result = self::processDeleteConverted();
          break;
        default:
          throw new \Exception('Unknown command');
      }
      if (!isset($result)) {
          throw new \Exception('Command: ' . $command . ' gave no result');
      }

      $json = wp_json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      if ($json === false) {
          // TODO: We can do better error handling than this!
          throw new \Exception('Failed encoding result to JSON');
      } else {
          echo $json;
      }
      wp_die();
    }

    public static function processRequest() {
      try {
          self::doProcessRequest();
      }
      catch (\Exception $e) {
          wp_send_json_error($e->getMessage());
          wp_die();
      }
    }
/*
{
    "converters": [
        {
            "converter": "cwebp",
            "options": {
                "use-nice": true,
                "try-common-system-paths": true,
                "try-supplied-binary-for-os": true,
                "method": 6,
                "low-memory": true,
                "command-line-options": ""
            },
            "working": true
        },
        {
            "converter": "vips",
            "options": {
                "smart-subsample": false,
                "preset": "none"
            },
            "working": false
        },
        {
            "converter": "imagemagick",
            "options": {
                "use-nice": true
            },
            "working": true,
            "deactivated": true
        },
        {
            "converter": "graphicsmagick",
            "options": {
                "use-nice": true
            },
            "working": false
        },
        {
            "converter": "ffmpeg",
            "options": {
                "use-nice": true,
                "method": 4
            },
            "working": false
        },
        {
            "converter": "wpc",
            "working": false,
            "options": {
                "api-key": ""
            }
        },
        {
            "converter": "ewww",
            "working": false
        },
        {
            "converter": "imagick",
            "working": false
        },
        {
            "converter": "gmagick",
            "working": false
        },
        {
            "converter": "gd",
            "options": {
                "skip-pngs": false
            },
            "working": false
        }
    ]
}*/
    public static function processConversionSettings() {
      require_once __DIR__ . "/../../vendor/autoload.php";
      $availableConverters = Stack::getAvailableConverters();

      /*
      $converters = [];
      //$supportsEncoding = [];
      foreach ($availableConverters as $converter) {
        $converters[] = [
          'id' => $converter,
          'name' => $converter
        ];
        /*if () {
          $supportsEncoding[] = $converter;
        }*/
      //}

      $webpConvertOptionDefinitions = WebPConvert::getConverterOptionDefinitions();

      $config = Config::loadConfigAndFix();
      $defaults = [
          'auto-limit' => (isset($config['quality-auto']) && $config['quality-auto']),
          'alpha-quality' => $config['alpha-quality'],
          'quality' => $config['max-quality'],
          'encoding' => $config['jpeg-encoding'],
          'near-lossless' => ($config['jpeg-enable-near-lossless'] ? $config['jpeg-near-lossless'] : 100),
          'metadata' => $config['metadata'],
          'stack-converters' => ConvertersHelper::getActiveConverterIds($config),

          // 'method' (I could copy from cwebp...)
          // 'sharp-yuv' (n/a)
          // low-memory (n/a)
          // auto-filter (n/a)
          // preset (n/a)
          // size-in-percentage (I could copy from cwebp...)
      ];

      $good = ConvertersHelper::getWorkingAndActiveConverterIds($config);
      if (isset($good[0])) {
        $defaults['converter'] = $good[0];
      }
      //'converter' => 'ewww',


      // TODO:add PNG options
      $pngDefaults = [
          'encoding' => $config['png-encoding'],
          'near-lossless' => ($config['png-enable-near-lossless'] ? $config['png-near-lossless'] : 100),
          'quality' => $config['png-quality'],
      ];


      // Filter active converters
      foreach ($config['converters'] as $converter) {
          /*if (isset($converter['deactivated']) && ($converter['deactivated'])) {
              //continue;
          }*/
          if (isset($converter['options'])) {
            foreach ($converter['options'] as $optionName => $optionValue) {
                $defaults[$converter['converter'] . '-' . $optionName] = $optionValue;
            }

          }
      }


      $systemStatus = [
        'converterRequirements' => [
            'gd' => [
                'extensionLoaded' => extension_loaded('gd'),
                'compiledWithWebP' => function_exists('imagewebp'),
            ]
          // TODO: Add more!
        ]
      ];

//getUnsupportedDefaultOptions
      //supportedStandardOptions: {
      $defaults['png'] = $pngDefaults;

      return [
        //'converters' => $converters,
        'defaults' => $defaults,
        //'pngDefaults' => $pngDefaults,
        'options' => $webpConvertOptionDefinitions,
        'systemStatus' => $systemStatus
      ];

      /*
      $config = Config::loadConfigAndFix();
      // 'working', 'deactivated'
      $foundFirstWorkingAndActive = false;
      foreach ($config['converters'] as $converter) {
        $converters[] = [
          'id' => $converter['converter'],
          'name' => $converter['converter']
        ];
        if ($converter['working']) {
          if
        }
        if (!$foundFirstWorkingAndActive) {

        }
      }*/

      return [
        'converters' => $converters
      ];
    }

    /*
     * Get mime
     * @return string
     */
    private static function setMime($path, &$info) {
        require_once __DIR__ . "/../../vendor/autoload.php";
        $mimeResult = ImageMimeTypeGuesser::detect($path);
        if (!$mimeResult) {
            return;
        }
        $info['mime'] = $mimeResult;
        if ($mimeResult == 'image/webp') {
            $handle = @fopen($path, 'r');
            if ($handle !== false) {
                // 20 bytes is sufficient for all our sniffers, except image/svg+xml.
                // The svg sniffer takes care of reading more
                $sampleBin = @fread($handle, 20);
                if ($sampleBin !== false) {
                    if (preg_match("/^RIFF.{4}WEBPVP8\ /", $sampleBin) === 1) {
                        $info['mime'] .= ' (lossy)';
                    } else if (preg_match("/^RIFF.{4}WEBPVP8L/", $sampleBin) === 1) {
                        $info['mime'] .= ' (lossless)';
                    }
                }
            }

        }
    }

    public static function processInfo() {

      Validate::postHasKey('args');

      //$args = json_decode(sanitize_text_field(stripslashes($_POST['args'])), true);

      //$args = $_POST['args'];
      $args = self::getArgs();
      if (!array_key_exists('path', $args)) {
          throw new \Exception('"path" argument missing for command');
      }

      $path = SanityCheck::pathWithoutDirectoryTraversal($args['path']);
      $path = ltrim($path, '/');
      $pathTokens = explode('/', $path);

      $rootId = array_shift($pathTokens);  // Shift off the first item, which is the scope
      $relPath = implode('/', $pathTokens);
      $config = Config::loadConfigAndFix();
      /*$rootIds = Paths::filterOutSubRoots($config['scope']);
      if (!in_array($rootId, $rootIds)) {
          throw new \Exception('Invalid scope (have you perhaps changed the scope setting after igniting the file manager?)');
      }*/
      $rootIds = $rootIds = Paths::getImageRootIds();

      $absPath = Paths::getAbsDirById($rootId) . '/' . $relPath;
      //absPathExistsAndIsFile
      SanityCheck::absPathExists($absPath);

      $result = [
          'original' => [
            //'filename' => $absPath,
            //'abspath' => $absPath,
            'size' => filesize($absPath),
            // PS: I keep "&original" because some might have set up Nginx rules for ?original
            'url' => Paths::getUrlById($rootId) . '/' . $relPath . '?' . SelfTestHelper::randomDigitsAndLetters(8) . '&dontreplace&original',
          ]
      ];
      self::setMime($absPath, $result['original']);

      // TODO: NO!
      // We must use ConvertHelper::getDestination for the abs path.
      // And we must use logic from AlterHtmlHelper to get the URL
      //error_log('path:' . $absPathDest);

      $destinationOptions = DestinationOptions::createFromConfig($config);
      if ($destinationOptions->useDocRoot) {
          if (!(Paths::canUseDocRootForStructuringCacheDir())) {
              $destinationOptions->useDocRoot = false;
          }
      }
      $imageRoots = new ImageRoots(Paths::getImageRootsDef());
      $destinationPath = Paths::getDestinationPathCorrespondingToSource($absPath, $destinationOptions);
      list($rootId, $destRelPath) = Paths::getRootAndRelPathForDestination($destinationPath, $imageRoots);
      if ($rootId != '') {
          $absPathDest = Paths::getAbsDirById($rootId) . '/' . $destRelPath;
          $destinationUrl = Paths::getUrlById($rootId) . '/' . $destRelPath;

          SanityCheck::absPath($absPathDest);

          if (@file_exists($absPathDest)) {
              $result['converted'] = [
                //'abspath' => $absPathDest,
                'size' => filesize($absPathDest),
                'url' => $destinationUrl . '?' . SelfTestHelper::randomDigitsAndLetters(8),
              ];
              self::setMime($absPathDest, $result['converted']);
          }

          // Get log, if exists. Ignore errors.
          $log = '';
          try {
            $logFile = ConvertHelperIndependent::getLogFilename($absPath, Paths::getLogDirAbs());
            if (@file_exists($logFile)) {
                $logContent = file_get_contents($logFile);
                if ($log !== false) {
                    $log = $logContent;
                }
            }
          }
          catch (\Exception $e) {
            //throw $e;
          }

          $result['log'] = $log;
      }


      //$destinationUrl = DestinationUrl::

      /*
      error_log('dest:' . $destinationPath);
      error_log('dest root:' . $rootId);
      error_log('dest path:' . $destRelPath);
      error_log('dest abs-dir:' . Paths::getAbsDirById($rootId) . '/' . $destRelPath);
      error_log('dest url:' . Paths::getUrlById($rootId) . '/' . $destRelPath);
      */

      //error_log('url:' . $destinationPath);
      //error_log('destinationOptions' . print_r($destinationOptions, true));

      /*
      $destination = Paths::destinationPathConvenience($rootId, $relPath, $config);
      $absPathDest = $destination['abs-path'];
      SanityCheck::absPath($absPathDest);
      error_log('path:' . $absPathDest);

      if (@file_exists($absPathDest)) {
          $result['converted'] = [
            'abspath' => $destination['abs-path'],
            'size' => filesize($destination['abs-path']),
            'url' => $destination['url'],
            'log' => ''
          ];
      }
      */
      return $result;
    }

    /**
     * Translate path received (ie "/uploads/2021/...") to absolute path.
     *
     * @param string $path
     *
     * @return array [$absPath, $relPath, $rootId]
     * @throws \Exception  if root id is invalid or path doesn't pass sanity check
     */
    private static function analyzePathReceived($path) {
        try {
          $path = SanityCheck::pathWithoutDirectoryTraversal($path);
          $path = ltrim($path, '/');
          $pathTokens = explode('/', $path);

          $rootId = array_shift($pathTokens);
          $relPath = implode('/', $pathTokens);

          $rootIds = Paths::getImageRootIds();
          if (!in_array($rootId, $rootIds)) {
              throw new \Exception('Invalid rootId');
          }
          if ($relPath == '') {
            $relPath = '.';
          }

          $absPath = PathHelper::canonicalize(Paths::getAbsDirById($rootId) . '/' . $relPath);
          SanityCheck::absPathExists($absPath);

          return [$absPath, $relPath, $rootId];
        }
        catch (\Exception $e) {
          //throw new \Exception('Invalid path received (' . $e->getMessage() . ')');
          throw new \Exception('Invalid path');
        }
    }

    public static function processGetFolder() {

        Validate::postHasKey('args');

        //$args = json_decode(sanitize_text_field(stripslashes($_POST['args'])), true);

        $args = self::getArgs();
        if (!array_key_exists('path', $args)) {
            throw new \Exception('"path" argument missing for command');
        }

        $path = SanityCheck::noStreamWrappers($args['path']);
        //$pathTokens = explode('/', $path);
        if ($path == '') {
            $result = [
                'children' => [
                    [
                      'name' => '/',
                      'isDir' => true,
                      'nickname' => 'scope'
                    ]
                ]
            ];
            return $result;
        }

        $config = Config::loadConfigAndFix();
        $rootIds = Paths::getImageRootIds();
        if ($path == '/') {
            $rootIds = Paths::filterOutSubRoots($config['scope']);
            $result = ['children'=>[]];
            foreach ($rootIds as $rootId) {
                $result['children'][] = [
                    'name' => $rootId,
                    'isDir' => true,
                ];
            }
            return $result;
        }
        list($absPath, $relPath, $rootId) = self::analyzePathReceived($path);

        $listOptions = BulkConvert::defaultListOptions($config);
        $listOptions['root'] = Paths::getAbsDirById($rootId);

        $listOptions['filter']['only-unconverted'] = false;
        $listOptions['flattenList'] = false;
        $listOptions['max-depth'] = 0;

        //throw new \Exception('Invalid rootId' . print_r($listOptions));

        $list = BulkConvert::getListRecursively($relPath, $listOptions);

        return ['children' => $list];
    }

    public static function processGetTree() {
      $config = Config::loadConfigAndFix();
      $rootIds = Paths::filterOutSubRoots($config['scope']);

      $listOptions = [
          //'root' => Paths::getUploadDirAbs(),
          'ext' => $config['destination-extension'],
          'destination-folder' => $config['destination-folder'],  /* hm, "destination-folder" is a bad name... */
          'webExpressContentDirAbs' => Paths::getWebPExpressContentDirAbs(),
          'uploadDirAbs' => Paths::getUploadDirAbs(),
          'useDocRootForStructuringCacheDir' => (($config['destination-structure'] == 'doc-root') && (Paths::canUseDocRootForStructuringCacheDir())),
          'imageRoots' => new ImageRoots(Paths::getImageRootsDefForSelectedIds($config['scope'])),   // (Paths::getImageRootsDef()
          'filter' => [
              'only-converted' => false,
              'only-unconverted' => false,
              'image-types' => $config['image-types'],
          ],
          'flattenList' => false
      ];

      $children = [];
      foreach ($rootIds as $rootId) {
          $listOptions['root'] = Paths::getAbsDirById($rootId);
          $grandChildren = BulkConvert::getListRecursively('.', $listOptions);
          $children[] = [
              'name' => $rootId,
              'isDir' => true,
              'children' => $grandChildren
          ];
      }
      return ['name' => '', 'isDir' => true, 'isOpen' => true, 'children' => $children];

    }

    private static function getArgs() {
        //return $_POST['args'];

        $args = $_POST['args'];
//        $args = '{\"path\":\"\"}';
        //$args = '{"path":"hollo"}';

        //error_log('get args:' . gettype($args));
        //error_log(print_r($args, true));
        //error_log(print_r(($_POST['args'] + ''), true));

        //error_log('type:' . gettype($_POST['args']));
        $args = json_decode('"' . $args . '"', true);
        $args = json_decode($args, true);
        //error_log('decoded:' . gettype($args));
        //error_log(print_r($args, true));
        //$args = json_decode($args, true);

        return $args;
    }

    public static function processConvert() {

        Validate::postHasKey('args');

        //$args = json_decode(sanitize_text_field(stripslashes($_POST['args'])), true);

        $args = self::getArgs();
        if (!array_key_exists('path', $args)) {
            throw new \Exception('"path" argument missing for command');
        }

        $path = SanityCheck::noStreamWrappers($args['path']);

        $convertOptions = null;
        if (isset($args['convertOptions'])) {
            $convertOptions = $args['convertOptions'];
            $convertOptions['log-call-arguments'] = true;
            //unset($convertOptions['converter']);
            //$convertOptions['png'] = ['quality' => 7];
            //$convertOptions['png-quality'] = 8;
        }

        //error_log(print_r(json_encode($convertOptions, JSON_PRETTY_PRINT), true));

        list($absPath, $relPath, $rootId) = self::analyzePathReceived($path);

        $convertResult = Convert::convertFile($absPath, null, $convertOptions);

        $result = [
          'success' => $convertResult['success'],
          'data' => $convertResult['msg'],
          'log' => $convertResult['log'],
          'args' => $args,  // for debugging. TODO
        ];
        $info = [];
        if (isset($convertResult['filesize-webp'])) {
          $info['size'] = $convertResult['filesize-webp'];
        }
        if (isset($convertResult['destination-url'])) {
          $info['url'] = $convertResult['destination-url'] . '?' . SelfTestHelper::randomDigitsAndLetters(8);
        }
        if (isset($convertResult['destination-path'])) {
          self::setMime($convertResult['destination-path'], $info);
        }

        $result['converted'] = $info;
        return $result;

        /*if (!array_key_exists('convertOptions', $args)) {
            throw new \Exception('"convertOptions" argument missing for command');
        }
        //return ['success' => true, 'optionsReceived' => $args['convertOptions']];
        */


        /*
        $path = SanityCheck::pathWithoutDirectoryTraversal($args['path']);
        $path = ltrim($path, '/');
        $pathTokens = explode('/', $path);

        $rootId = array_shift($pathTokens);  // Shift off the first item, which is the scope
        $relPath = implode('/', $pathTokens);
        $config = Config::loadConfigAndFix();
        $rootIds = Paths::filterOutSubRoots($config['scope']);
        if (!in_array($rootId, $rootIds)) {
            throw new \Exception('Invalid scope');
        }

        $absPath = Paths::getAbsDirById($rootId) . '/' . $relPath;
        //absPathExistsAndIsFile
        SanityCheck::absPathExists($absPath);      */
    }

    public static function processDeleteConverted() {

        Validate::postHasKey('args');

        //$args = json_decode(sanitize_text_field(stripslashes($_POST['args'])), true);

        //$args = $_POST['args'];
        $args = self::getArgs();
        if (!array_key_exists('path', $args)) {
            throw new \Exception('"path" argument missing for command');
        }

        $path = SanityCheck::noStreamWrappers($args['path']);
        list($absPath, $relPath, $rootId) = self::analyzePathReceived($path);

        $config = Config::loadConfigAndFix();
        $destinationOptions = DestinationOptions::createFromConfig($config);
        if ($destinationOptions->useDocRoot) {
            if (!(Paths::canUseDocRootForStructuringCacheDir())) {
                $destinationOptions->useDocRoot = false;
            }
        }
        $destinationPath = Paths::getDestinationPathCorrespondingToSource($absPath, $destinationOptions);

        if (@!file_exists($destinationPath)) {
            throw new \Exception('file not found: ' . $destinationPath);
        }

        if (@!unlink($destinationPath)) {
            throw new \Exception('failed deleting file');
        }

        $result = [
          'success' => true,
          'data' => $destinationPath
        ];
        return $result;

    }

}
