<?php

namespace WebPExpress;

use \WebPConvert\Convert\Converters\Stack;
use \WebPConvert\WebPConvert;

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

      return [
        //'converters' => $converters,
        //'options' => WebPConvert::getConverterOptionDefinitions('png', false, true)['general'],
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


    public static function processInfo() {

      Validate::postHasKey('args');

      //$args = json_decode(sanitize_text_field(stripslashes($_POST['args'])), true);

      $args = $_POST['args'];
      if (!array_key_exists('path', $args)) {
          throw new \Exception('"path" argument missing for command');
      }

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
      SanityCheck::absPathExists($absPath);
      $result = [
          'original' => [
            //'filename' => $absPath,
            //'abspath' => $absPath,
            'size' => filesize($absPath),
            'url' => Paths::getUrlById($rootId) . '/' . $relPath,
          ]
      ];

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
                'abspath' => $absPathDest,
                'size' => filesize($absPathDest),
                'url' => $destinationUrl,
                'log' => ''
              ];
          }

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

    public static function processGetFolder() {

        Validate::postHasKey('args');

        //$args = json_decode(sanitize_text_field(stripslashes($_POST['args'])), true);

        $args = $_POST['args'];
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
        $rootIds = Paths::filterOutSubRoots($config['scope']);

        if ($path == '/') {
            $result = ['children'=>[]];
            foreach ($rootIds as $rootId) {
                $result['children'][] = [
                    'name' => $rootId,
                    'isDir' => true,
                ];
            }
            return $result;
        }
        $path = SanityCheck::pathWithoutDirectoryTraversal($path);
        $path = ltrim($path, '/');
        $pathTokens = explode('/', $path);

        $rootId = array_shift($pathTokens);
        $relPath = implode('/', $pathTokens);

        if (!in_array($rootId, $rootIds)) {
            throw new \Exception('Invalid rootId');
        }

        if ($relPath == '') {
          $relPath = '.';
        }

        $absPath = Paths::getAbsDirById($rootId) . '/' . $relPath;
        SanityCheck::absPathExists($absPath);

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

    public static function processConvert() {

        Validate::postHasKey('args');

        //$args = json_decode(sanitize_text_field(stripslashes($_POST['args'])), true);

        $args = $_POST['args'];
        if (!array_key_exists('path', $args)) {
            throw new \Exception('"path" argument missing for command');
        }
        if (!array_key_exists('convertOptions', $args)) {
            throw new \Exception('"convertOptions" argument missing for command');
        }

        return ['success' => true, 'optionsReceived' => $args['convertOptions']];

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
}
