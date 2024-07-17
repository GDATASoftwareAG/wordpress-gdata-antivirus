<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\Database;

class NoopDatabase implements IGdataAntivirusDatabase {
    public function query( $query, ...$args ): int|bool {
        return true;
    }

    public function get_charset_collate(): string {
        return '';
    }

    public function db_delta( string $query, bool $execute = true ): array {
        return array();
    }
    public function get_var( string $query, ...$args ): string|null {
        return null;
    }
    public function insert( string $table, array $data ): string {
        return '';
    }
    public function delete( string $table, array $where ): string {
        return '';
    }
    public function get_results( string $query, string $output, ...$args ): array|object|null {
        return array();
    }
    public function get_prefix(): string {
        return '';
    }
}
