<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database;

use Psr\Log\LoggerInterface;
use wpdb;

class FindingsQuery implements IFindingsQuery {
    private wpdb $wpdb;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
    ) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->logger = $logger;
    }

    private function get_table_name(): string {
        return $this->wpdb->prefix.GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FINDINGS_TABLE_NAME;
    }

    public function create(): void {
        $charset_collate = $this->wpdb->get_charset_collate();
        $sql             = 'CREATE TABLE ' . $this->get_table_name() . ' (
            file_path VARCHAR(512) NOT NULL,
            UNIQUE KEY file_path (file_path)
        )' . $charset_collate . ';';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        \dbDelta($sql);
        \wp_cache_set($this->get_table_name(), 'true', 'GdataAntivirus');
    }

    public function remove(): void {
        if (! $this->table_exists()) {
            return;
        }
        $this->wpdb->query(
            $this->wpdb->prepare('DROP TABLE IF EXISTS %i', $this->get_table_name()));
        \wp_cache_set($this->get_table_name(), 'false', 'GdataAntivirus');
    }

    public function table_exists(): bool {
        $tables_exists = \wp_cache_get($this->get_table_name(), 'GdataAntivirus');
        $this->logger->debug('Exists in cache: ' . ($tables_exists ? 'true' : 'false'));
        if (false === $tables_exists) {
            $exists = $this->wpdb->get_var(
                $this->wpdb->prepare('SHOW TABLES LIKE %s', $this->get_table_name())) === $this->get_table_name();
            $this->logger->debug('Exists in database: ' . ($exists ? 'true' : 'false'));
            \wp_cache_set($this->get_table_name(), \wp_json_encode($exists), 'GdataAntivirus');
            return $exists;
        }
        if ('true' === $tables_exists) {
            return true;
        }
        return false;
    }

    public function add( string $file ): void {
        if (! $this->table_exists()) {
            return;
        }

        try {
            $this->wpdb->insert(
                $this->get_table_name(),
                array( 'file_path' => $file )
            );
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    public function delete( string $file ): void {
        if (! $this->table_exists()) {
            return;
        }
        $this->wpdb->delete(
            $this->get_table_name(),
            array( 'file_path' => $file )
        );
    }

    public function get_all(): array {
        if (! $this->table_exists()) {
            return array();
        }
        return $this->wpdb->get_results(
            $this->wpdb->prepare('SELECT file_path FROM %i', $this->get_table_name()), ARRAY_A);
    }

    public function count(): int {
        $this->logger->debug('FindingsMenuPage::get_findings_count');
        if (! $this->table_exists()) {
            return 0;
        }
        return (int) $this->wpdb->get_var(
            $this->wpdb->prepare('SELECT COUNT(*) FROM %i', $this->get_table_name()));
    }

    public function validate(): void {
        if (! $this->table_exists()) {
            return;
        }
        $findings = $this->get_all();

        foreach ($findings as $finding) {
            if (! file_exists($finding['file_path'])) {
                $this->delete($finding['file_path']);
            }
        }
    }

}
