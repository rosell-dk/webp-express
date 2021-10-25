<?php

namespace WebPExpress;

class DestinationOptions
{

    public $mingled;
    public $useDocRoot;
    public $replaceExt;
    public $scope;

    /**
     * Constructor.
     *
     * @param  array  $imageRootDef   assoc array containing "id", "url" and either "abs-path", "rel-path" or both.
     */
    public function __construct($mingled, $useDocRoot, $replaceExt, $scope)
    {
        $this->mingled = $mingled;
        $this->useDocRoot = $useDocRoot;
        $this->replaceExt = $replaceExt;
        $this->scope = $scope;
    }

    /**
     * Set properties from config file
     *
     * @param  array  $config   WebP Express configuration object
     */
    public static function createFromConfig(&$config)
    {
        return new DestinationOptions(
            $config['destination-folder'] == 'mingled',       // "mingled" or "separate"
            $config['destination-structure'] == 'doc-root',   // "doc-root" or "image-roots"
            $config['destination-extension'] == 'set',        // "set" or "append"
            $config['scope']
        );
    }


}
