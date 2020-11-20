<?php

namespace WebPExpress;

/**
 *
 */

class WCFMApi
{

    public static function processRequest() {
      if (!check_ajax_referer('webpexpress-wcfm-nonce', 'nonce', false)) {
          wp_send_json_error('The security nonce has expired. You need to reload (press F5) and try again)');
          wp_die();
      }

      $result = self::processGetTree();

      $json = wp_json_encode($result, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      if ($json === false) {
          // TODO: We can do better error handling than this!
          echo '';
      } else {
          echo $json;
      }

      wp_die();
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
