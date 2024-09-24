<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database;

interface IScansQuery extends IDatabase {
    public function write_lock(): void;
    public function write_unlock(): void;
    public function scheduled_count(): int;
    public function increase_scheduled(): void;
    public function finished_count(): int;
    public function increase_finished(): void;
    public function reset(): void;
}
