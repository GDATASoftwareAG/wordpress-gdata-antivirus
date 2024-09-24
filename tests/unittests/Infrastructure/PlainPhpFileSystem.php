<?php

namespace Gdatacyberdefenseag\GdataAntivirus\tests\unittests\Infrastructure;

use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\FileSystemBase;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;

class PlainPhpFileSystem implements IGdataAntivirusFileSystem {
    use FileSystemBase;

    public function read( string $path ): string {
        return file_get_contents($path);
    }

    public function write( string $path, string $content ): bool {
        return \file_put_contents($path, $content, \FILE_APPEND);
    }

    public function delete( string $path ): bool {
        return unlink($path);
    }

    public function is_writable( string $path ): bool {
        return is_writable($path);
    }
}
