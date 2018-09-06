<?php


namespace WebPOnDemand;

class WebPOnDemand
{
    public static $defaultOptions = [
        'show-report' => false,
        'reconvert' => false,
        'original' => false,
        'add-x-webp-on-demand-headers' => true,
        'add-vary-header' => true,
    ];

    private static function serveOriginal($source)
    {
        // Serve original image
        $arr = explode('.', $source);
        $ext = array_pop($arr);
        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                header('Content-type: image/jpeg');
                break;
            case 'png':
                header('Content-type: image/png');
                break;
        }
        if (@readfile($source) === false) {
            header('X-WebP-On-Demand-Error: Could not read file');
        }
    }

    private static function addWebPOnDemandHeader($text, $options)
    {
        if ($options['add-x-webp-on-demand-headers']) {
            header('X-WebP-On-Demand: ' . $text);
        }
    }

    private static function addVaryHeader($options)
    {
        if ($options['add-vary-header']) {
            header('Vary: Accept');
        }
    }


    public static function serve($source, $destination, $options)
    {
        $options = array_merge(self::$defaultOptions, $options);

        if (empty($source)) {
            self::addWebPOnDemandHeader('Failed (Missing source argument)', $options);
        }
        if (empty($destination)) {
            self::addWebPOnDemandHeader('Failed (Missing destination argument)', $options);
        }

        if ($options['show-report']) {
            // Load WebPConvertAndServe (only when needed)
            if (isset($options['require-for-conversion'])) {
                require($options['require-for-conversion']);
            }

            self::addWebPOnDemandHeader('Reporting...', $options);

            \WebPConvertAndServe\WebPConvertAndServe::convertAndReport($source, $destination, $options);
            return;
        }

        if ($options['original']) {
            self::addWebPOnDemandHeader('Serving original image (was explicitly told to)', $options);
            self::serveOriginal($source);
        }
        if (file_exists($destination) && (!$options['reconvert'])) {
            $timestampSource = filemtime($source);
            $timestampDestination = filemtime($destination);

            if (($timestampSource === false) &&
                ($timestampDestination !== false) &&
                ($timestampSource > $timestampDestination)) {
                // It must be reconverted...
                // will be done in a subsequent block...
            } else {
                $filesizeDestination = filesize($destination);
                $filesizeSource = filesize($source);

                // Serve original image, if the converted image is larger
                if (($filesizeSource !== false) &&
                    ($filesizeDestination !== false) &&
                    ($filesizeDestination > $filesizeSource)) {
                    self::addWebPOnDemandHeader(
                        'Serving original image - because it is smaller than the converted!',
                        $options
                    );
                    self::addVaryHeader($options);
                    self::serveOriginal($source);
                } else {
                    // Serve existing converted image
                    //echo $destination;
                    header('Content-type: image/webp');
                    self::addWebPOnDemandHeader('Serving existing converted image', $options);
                    self::addVaryHeader($options);

                    if (@readfile($destination) === false) {
                        header('X-WebP-On-Demand-Error: Could not read file');
                    }
                }
                return;
            }
        }

        // We are still here... This means we should ignite the converter, possibly doing a reconversion

        // Load WebPConvertAndServe (we only do that when it is needed)
        if (isset($options['require-for-conversion'])) {
            require($options['require-for-conversion']);
        }

        self::addWebPOnDemandHeader('Converting image (handed over to WebPConvertAndServe)', $options);

        // We do not add "Vary Accept" header here, because WebPConvertAndServe will do that (if successful)

        unset($options['show-report']);
        unset($options['reconvert']);
        unset($options['original']);
        unset($options['add-x-webp-on-demand-headers']);
        unset($options['require-for-conversion']);


        \WebPConvertAndServe\WebPConvertAndServe::convertAndServe($source, $destination, $options);
    }
}
