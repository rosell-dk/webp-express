<?php

namespace HtaccessCapabilityTester;

class SimpleTestFileLineUpper implements TestFilesLineUpperInterface
{

    private function writeFileIfMissingOrChanged($file)
    {
        $success = true;
        list($filename, $content) = $file;
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                // TODO: Use custom exception
                throw new \Exception('Failed creating dir: ' . $dir);
            }
        }
        if (file_exists($filename)) {
            // file already exists, now check if content is the same
            $existingContent = file_get_contents($filename);
            if (($existingContent === false) || ($content != $existingContent)) {
                $success = file_put_contents($filename, $content);
            }
        } else {
            $success = file_put_contents($filename, $content);
        }
        if (!$success) {
            // TODO: Use custom exception
            throw new \Exception('Failed creating file: ' . $filename);
        }
    }

    /**
     * Write missing and changed files.
     *
     * @param  array  $files   The files that needs to be there
     *
     * @return  void
     */
    private function writeMissingAndChangedFiles($files)
    {
        foreach ($files as $file) {
            $this->writeFileIfMissingOrChanged($file);
        }
    }

    /**
     * Remove unused files.
     *
     * @param  array  $files   The files that needs to be there (others will be removed)
     *
     * @return  void
     */
    private function removeUnusedFiles($files)
    {
        $dirs = [];
        foreach ($files as $file) {
            list($filename, $content) = $file;
            $dir = dirname($filename);
            if (!isset($dirs[$dir])) {
                $dirs[$dir] = [];
            }
            $dirs[$dir][] = basename($filename);
        }

        foreach ($dirs as $dir => $filesSupposedToBeInDir) {
            $fileIterator = new \FilesystemIterator($dir);
            while ($fileIterator->valid()) {
                $filename = $fileIterator->getFilename();
                if (!in_array($filename, $filesSupposedToBeInDir)) {
                    unlink($dir . '/' . $filename);
                }
                $fileIterator->next();
            }
        }
    }

    /**
     * Line-up test files.
     *
     * This method should make sure that the files passed in are there and are up-to-date.
     * - If a file is missing, it should be created.
     * - If a file has changed content, it should be updated
     * - If the directory contains a file/dir that should not be there, it should be removed
     *
     * @param  array  $files   The files that needs to be there
     *
     * @return  void
     */
    public function lineUp($files)
    {
        // 1. Put missing files / changed files
        $this->writeMissingAndChangedFiles($files);

        // 2. Remove unused files
        $this->removeUnusedFiles($files);
    }
}
