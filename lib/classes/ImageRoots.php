<?php

namespace WebPExpress;

use \WebPExpress\ImageRoot;

class ImageRoots
{
    private $imageRootsDef;
    private $imageRoots;

    /**
     * Constructor.
     *
     * @param   array  $imageRoots   Array representation of image roots
     */
    public function __construct($imageRootsDef)
    {
        $this->imageRootsDef = $imageRootsDef;

        $this->imageRoots = [];
        foreach ($imageRootsDef as $i => $def)
        {
            $this->imageRoots[] = new ImageRoot($def);
        }
    }

    /**
     * Get image root by id.
     *
     * @return  \WebPExpress\ImageRoot  An image root object
     */
    public function byId($id)
    {
        foreach ($this->imageRoots as $i => $imageRoot) {
            if ($imageRoot->id == $id) {
                return $imageRoot;
            }
        }
        throw new \Exception('Image root not found');
    }

    /**
     * Get the image roots array
     *
     * @return  array  An array of ImageRoot objects
     */
    public function getArray()
    {
        return $this->imageRoots;
    }
}
