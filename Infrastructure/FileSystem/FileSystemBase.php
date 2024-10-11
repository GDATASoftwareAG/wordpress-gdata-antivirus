<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem;

use Amp\ByteStream\ReadableResourceStream;
use function Amp\ByteStream\Internal\tryToCreateReadableStreamFromResource;

/**
 * This implements the functions that some filesystems have in common
 * for example, there is no proper replacement for fopen (where you get a stream from)
 * in the WP_Filesystem_Base class, so we need to implement it here
 */
trait FileSystemBase {
    public function get_resource_stream_from_string( string $content ): ReadableResourceStream {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        return tryToCreateReadableStreamFromResource($stream);
    }

    public function open( string $path ): ReadableResourceStream {
        return tryToCreateReadableStreamFromResource(fopen($path, 'r'));
    }
}
