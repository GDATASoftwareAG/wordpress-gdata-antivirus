<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database;

interface IFindingsQuery extends IDatabase {
    public function add( string $file ): void;
    public function delete( string $file ): void;
    public function delete_all(): void;
    public function get_all(): array;
    public function table_exists(): bool;
    public function count(): int;
    public function validate(): void;
}
