<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem;

use Amp\ByteStream\ReadableResourceStream;

interface IGdataAntivirusFileSystem {
    public function open( string $path ): ReadableResourceStream;
    public function read( string $path ): string;
    public function write( string $path, string $content ): bool;
    public function delete( string $path ): bool;
    public function is_writable( string $path ): bool;

    public function get_resource_stream_from_string( string $content ): ReadableResourceStream;
}
