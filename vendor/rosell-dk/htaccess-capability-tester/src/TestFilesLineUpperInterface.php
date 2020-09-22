<?php

namespace HtaccessCapabilityTester;

interface TestFilesLineUpperInterface
{
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
    public function lineUp($files);
}
