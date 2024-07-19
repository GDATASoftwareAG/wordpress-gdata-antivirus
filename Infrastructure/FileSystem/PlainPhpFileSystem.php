<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem;

class PlainPhpFileSystem implements IGdataAntivirusFileSystem {
    public function read( $path ): string {
        return file_get_contents($path);
    }

    public function write( $path, $content ) {
        return \file_put_contents($path, $content, \FILE_APPEND);
    }

    public function delete( $path ) {
        return unlink($path);
    }

    public function is_writable( $path ): bool {
        return is_writable($path);
    }
}
