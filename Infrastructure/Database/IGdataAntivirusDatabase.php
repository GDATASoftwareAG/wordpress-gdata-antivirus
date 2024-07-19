<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database;

interface IGdataAntivirusDatabase {
    public function query( string $query, ...$args ): int|bool;
    public function get_charset_collate(): string;
    public function db_delta( string $query, bool $execute = true ): array;
    public function get_var( string $query, ...$args ): string|null;
    public function insert( string $table, array $data ): string;
    public function delete( string $table, array $where ): string;
    public function get_results( string $query, string $output, ...$args ): array|object|null;
    public function get_prefix(): string;
}
