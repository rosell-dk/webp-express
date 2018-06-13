<?php

namespace WebPConvert\Converters;

//use WebPConvert\Converters\Cwebp;
use WebPConvert\Exceptions\TargetNotFoundException;
use WebPConvert\Exceptions\InvalidFileExtensionException;
use WebPConvert\Exceptions\CreateDestinationFolderException;
use WebPConvert\Exceptions\CreateDestinationFileException;

class ConverterHelper
{
    public static $allowedExtensions = ['jpg', 'jpeg', 'png'];

    public static $defaultOptions = [
        'quality' => 85,
        'metadata' => 'none',
        'method' => 6,
        'low-memory' => false,
        'lossless' => false,
        'converters' =>  ['cwebp', 'imagick', 'gd']
    ];

    /* Call the "convert" method on a converter, by id */
    public static function callConvert($converterId, $source, $destination, $options = [])
    {
        $className = 'WebPConvert\\Converters\\' . ucfirst($converterId);
        if (!is_callable([$className, 'convert'])) {
            //echo 'Converter does not appear to exist!';
            //exit;
            // hm.. throw exception ?
        }
        call_user_func(
            [$className, 'convert'],
            $source,
            $destination,
            $options
        );
    }

    public static function callConvertConverterNotFoundException($converterId, $source, $destination, $options = [])
    {
        try {
            callConvert($converterId, $source, $destination, $options);
        } catch (\WebPConvert\Converters\Exceptions\ConverterNotOperationalException $e) {
            $failure = 'The converter is not operational';
            // throw new ConverterNotOperationalException('exec() is not enabled.');

            // TODO: We should show link to install instructions for the specific converter (WIKI)

            $msg = $e->getMessage();
        } catch (\WebPConvert\Converters\Exceptions\ConverterFailedException $e) {
            $failure = 'The converter failed converting, although requirements seemed to be met';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Converters\Exceptions\ConversionDeclinedException $e) {
            $failure = 'The converter declined converting';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Exceptions\InvalidFileExtensionException $e) {
            $failure = 'The converter does not accept the file extension';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Exceptions\TargetNotFoundException $e) {
            $failure = 'The converter could not locate source file';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Exceptions\CreateDestinationFolderException $e) {
            $failure = 'The converter could not create destination folder. Check file permisions!';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Exceptions\CreateDestinationFileException $e) {
            $failure = 'The converter could not create destination file. Check file permisions!';
            $msg = $e->getMessage();
        } catch (\Exception $e) {
            $failure = 'Unexpected failure';
            $msg = $e->getMessage();
        }
    }

    public static function getExtension($filePath)
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        return strtolower($fileExtension);
    }

    // Throws an exception if the provided file doesn't exist
    public static function isValidTarget($filePath)
    {
        if (!file_exists($filePath)) {
            throw new TargetNotFoundException('File or directory not found: ' . $filePath);
        }

        return true;
    }

    // Throws an exception if the provided file's extension is invalid
    public static function isAllowedExtension($filePath)
    {
        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileExtension), self::$allowedExtensions)) {
            throw new InvalidFileExtensionException('Unsupported file extension: ' . $fileExtension);
        }

        return true;
    }

    // Creates folder in provided path & sets correct permissions
    public static function createWritableFolder($filePath)
    {
        $folder = pathinfo($filePath, PATHINFO_DIRNAME);
        if (!file_exists($folder)) {
            // TODO: what if this is outside open basedir?
            // see http://php.net/manual/en/ini.core.php#ini.open-basedir

            // First, we have to figure out which permissions to set.
            // We want same permissions as parent folder
            // But which parent? - the parent to the first missing folder

            $parentFolders = explode('/', $folder);
            $poppedFolders = [];

            while (!(file_exists(implode('/', $parentFolders))) && count($parentFolders) > 0) {
                array_unshift($poppedFolders, array_pop($parentFolders));
            }

            // Retrieving permissions of closest existing folder
            $closestExistingFolder = implode('/', $parentFolders);
            $permissions = fileperms($closestExistingFolder) & 000777;

            // Trying to create the given folder
            // Notice: mkdir emits a warning on failure. It would be nice to suppress that, if possible
            if (!mkdir($folder, $permissions, true)) {
                throw new CreateDestinationFolderException('Failed creating folder: ' . $folder);
            }


            // `mkdir` doesn't respect permissions, so we have to `chmod` each created subfolder
            foreach ($poppedFolders as $subfolder) {
                $closestExistingFolder .= '/' . $subfolder;
                // Setting directory permissions
                chmod($folder, $permissions);
            }
        }

        // Checks if there's a file in $filePath & if writing permissions are correct
        if (file_exists($filePath) && !is_writable($filePath)) {
            throw new CreateDestinationFileException('Cannot overwrite ' . basename($filePath) . ' - check file permissions.');
        }

        // There's either a rewritable file in $filePath or none at all.
        // If there is, simply attempt to delete it
        if (file_exists($filePath) && !unlink($filePath)) {
            throw new CreateDestinationFileException('Existing file cannot be removed: ' . basename($filePath));
        }

        return true;
    }

    public static function prepareDestinationFolderAndRunCommonValidations($source, $destination)
    {
        self::isValidTarget($source);
        self::isAllowedExtension($source);
        self::createWritableFolder($destination);
    }

    public static function initCurlForConverter()
    {
        if (!extension_loaded('curl')) {
            throw new ConverterNotOperationalException('Required cURL extension is not available.');
        }

        if (!function_exists('curl_init')) {
            throw new ConverterNotOperationalException('Required url_init() function is not available.');
        }

        if (!function_exists('curl_file_create')) {
            throw new ConverterNotOperationalException('Required curl_file_create() function is not available (requires PHP > 5.5).');
        }

        $ch = curl_init();
        if (!$ch) {
            throw new ConverterNotOperationalException('Could not initialise cURL.');
        }
        return $ch;
    }
}
