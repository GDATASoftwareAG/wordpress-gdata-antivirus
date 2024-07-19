<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\FileSystem;

interface IGdataAntivirusFileSystem {
    public function read( $path ): string;
    public function write( $path, $content );
    public function delete( $path );
    public function is_writable( $path ): bool;
}
