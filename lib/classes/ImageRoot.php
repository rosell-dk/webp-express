<?php

namespace WebPExpress;

use \WebPExpress\PathHelper;

class ImageRoot
{

    public $id;
    
    /**
     * Constructor.
     *
     * @param  array  $imageRootDef   assoc array containing "id", "url" and either "abs-path", "rel-path" or both.
     */
    public function __construct($imageRootDef)
    {
        $this->imageRootDef = $imageRootDef;
        $this->id = $imageRootDef['id'];
    }

    /**
     *  Get / calculate abs path.
     *
     *  If "rel-path" is set and document root is available, the abs path will be calculated from the relative path.
     *  Otherwise the "abs-path" is returned.
     *  @throws Exception In case rel-path is not
     */
    public function getAbsPath()
    {
        $def = $this->imageRootDef;
        if (isset($def['rel-path']) && PathHelper::isDocRootAvailable()) {
            return rtrim($_SERVER["DOCUMENT_ROOT"], '/') . '/' . $def['rel-path'];
        } elseif (isset($def['abs-path'])) {
            return $def['abs-path'];
        } else {
            if (!isset($def['rel-path'])) {
                throw new \Exception(
                    'Image root definition in config file is must either have a "rel-path" or "abs-path" property defined. ' .
                    'Probably your system setup has changed. Please re-save WebP Express options and regenerate .htaccess'
                );
            } else {
                throw new \Exception(
                    'Image root definition in config file is defined by "rel-path". However, DOCUMENT_ROOT is unavailable so we ' .
                    'cannot use that (as the rel-path is relative to that. ' .
                    'Probably your system setup has changed. Please re-save WebP Express options and regenerate .htaccess'
                );
            }
        }
    }

}
