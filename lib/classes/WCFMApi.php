<?php

namespace WebPExpress;

use \WebPConvert\Convert\Converters\Stack;

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
        case 'get-tree':
          $result = self::processGetTree();
          break;
        case 'conversion-settings':
          $result = self::processConversionSettings();
          break;
        case 'info':
          $result = self::processInfo();
          break;
      }

      $json = wp_json_encode($result, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
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

      return [
        'converters' => $converters,
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

      // TODO: What if it is a dir?

      $destination = Paths::destinationPathConvenience($rootId, $relPath, $config);

      $absPathDest = $destination['abs-path'] . '/' . $relPath;

      return [
        'original' => [
          'name' => $absPath,
          'size' => filesize($absPath),
          'url' => '',
        ],
        'converted' => [
          'name' => $destination['abs-path'],
          'size' => 70,
          'url' => ''
        ],
        'log' => 'blah blah blah'
      ];
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

}
