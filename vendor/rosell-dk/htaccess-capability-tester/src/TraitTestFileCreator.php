<?php

namespace HtaccessCapabilityTester;


/**
 * Trait for creating test files
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since the beginning
 */
trait TraitTestFileCreator
{

    public function createTestFilesIfNeeded() {
        foreach ($this->testFiles as list($fileName, $content, $subSubDir)) {
            self::createTestFileIfNeeded($fileName, $content, $subSubDir);
        }
    }

    /** Create/update test file if needed (missing or changed)
     *
     *  @return bool  Success or not
     */
    private function createTestFileIfNeeded($fileName, $content, $subSubDir = '') {
        $dir = $this->baseDir . '/' . $this->subDir;
        if ($subSubDir != '') {
            $dir .= '/' . $subSubDir;
        }
        $path = $dir . '/' . $fileName;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!file_exists($path)) {
            return file_put_contents($path, $content);
        }
        // file already exists, now check if content is the same
        $existingContent = file_get_contents($path);
        if ($existingContent === false) {
            return false;
        }
        if ($content != $existingContent) {
            return file_put_contents($path, $content);
        }
        return false;
    }

}
