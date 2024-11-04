<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database;

class DetectedFile {
    public function __construct(public string $path, public string $detection, 
                                public string $sha256, public string $request_id) {}
}