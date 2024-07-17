<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\Database;

use wpdb;

class WordPressDatabase implements IGdataAntivirusDatabase {
    private wpdb $wpdb;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function query( string $query_string, ...$query_args ): int|bool {
        // phpcs:ignore
        $prepared_statement = $this->wpdb->prepare($query_string, $query_args);

        // phpcs:ignore
        return $this->wpdb->query($prepared_statement);
    }

    public function get_prefix(): string {
        return $this->wpdb->prefix;
    }

    public function get_charset_collate(): string {
        return $this->wpdb->get_charset_collate();
    }

    public function db_delta( string $query, bool $execute = true ): array {
        return dbDelta($query, $execute);
    }

    public function get_var( string $query_string, ...$query_args ): string|null {
        // phpcs:ignore
        $prepared_statement = $this->wpdb->prepare($query_string, $query_args);

        // phpcs:ignore
        return $this->wpdb->get_var($prepared_statement);
    }

    public function insert( string $table, array $data ): string {
        return $this->wpdb->insert($table, $data);
    }

    public function delete( string $table, array $where ): string {
        return $this->wpdb->delete($table, $where);
    }

    public function get_results( string $query_string, string $output, ...$query_args ): array|object|null {
        // phpcs:ignore
        $prepared_statement = $this->wpdb->prepare($query_string, $query_args);

        // phpcs:ignore
        return $this->wpdb->get_results($prepared_statement, $output);
    }
}
