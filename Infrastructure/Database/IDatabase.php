<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database;

interface IDatabase {
    public function create(): void;
    public function remove(): void;
}
