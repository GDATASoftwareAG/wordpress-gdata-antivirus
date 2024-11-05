<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database;

use Psr\Log\LoggerInterface;
use wpdb;

class FindingsQuery implements IFindingsQuery {
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
    ) {
        $this->logger = $logger;
    }

    private function get_table_name(): string {
        global $wpdb;

        return $wpdb->prefix.GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FINDINGS_TABLE_NAME;
    }

    public function create(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $sql             = 'CREATE TABLE ' . $this->get_table_name() . ' (
            file_path VARCHAR(512) NOT NULL,
            detection VARCHAR(128) NOT NULL,
            sha256 VARCHAR(64) NOT NULL,
            request_id VARCHAR(256) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY file_path (file_path)
        )' . $charset_collate . ';';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        wp_cache_set($this->get_table_name(), 'true', 'GdataAntivirus');
    }   

    public function remove(): void {
        global $wpdb;

        if (! $this->table_exists()) {
            return;
        }
        $wpdb->query(
            $wpdb->prepare('DROP TABLE IF EXISTS %i', $this->get_table_name())
        );
        wp_cache_set($this->get_table_name(), 'false', 'GdataAntivirus');
    }

    public function table_exists(): bool {
        global $wpdb;

        $tables_exists = wp_cache_get($this->get_table_name(), 'GdataAntivirus');
        $this->logger->debug('Exists in cache: ' . ($tables_exists ? 'true' : 'false'));
        if (false === $tables_exists) {
            $exists = $wpdb->get_var(
                $wpdb->prepare('SHOW TABLES LIKE %s', $this->get_table_name())
            ) === $this->get_table_name();
            $this->logger->debug('Exists in database: ' . ($exists ? 'true' : 'false'));
            wp_cache_set($this->get_table_name(), wp_json_encode($exists), 'GdataAntivirus');
            return $exists;
        }
        if ('true' === $tables_exists) {
            return true;
        }
        return false;
    }

    private function exits( string $file_path ): bool {
        global $wpdb;

        if (!$this->table_exists()) {
            return false;
        }
        return $wpdb->get_var(
            $wpdb->prepare('SELECT COUNT(*) FROM %i WHERE file_path = %s', $this->get_table_name(), $file_path)
        ) > 0;
    }

    public function add( DetectedFile $detected_file ): void {
        global $wpdb;
        assert($wpdb instanceof wpdb);

        if (! $this->table_exists()) {
            return;
        }

        try {
            if ($this->exits($detected_file->path)) {
                $wpdb->update(
                    $this->get_table_name(),
                    array( 
                        'detection' => $detected_file->detection,
                        'sha256' => $detected_file->sha256,
                        'request_id' => $detected_file->request_id
                    ),
                    array( 'file_path' => $detected_file->path )
                );
                return; 
            }
            $wpdb->insert(
                $this->get_table_name(),
                array( 
                    'file_path' => $detected_file->path,
                    'detection' => $detected_file->detection,
                    'sha256' => $detected_file->sha256,
                    'request_id' => $detected_file->request_id
                )
            );
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }

    public function delete( string $file ): void {
        global $wpdb;

        if (! $this->table_exists()) {
            return;
        }
        $wpdb->delete(
            $this->get_table_name(),
            array( 'file_path' => $file )
        );
    }

    public function delete_all(): void {
        global $wpdb;
        assert($wpdb instanceof wpdb);

        if (! $this->table_exists()) {
            return;
        }
        $wpdb->query(
            $wpdb->prepare('TRUNCATE TABLE %i', $this->get_table_name())
        );
    }

    public function get_all(): array {
        global $wpdb;

        if (! $this->table_exists()) {
            return array();
        }
        return $wpdb->get_results(
            $wpdb->prepare('SELECT file_path, detection, sha256, request_id, updated_at FROM %i', $this->get_table_name()),
            ARRAY_A
        );
    }

    public function count(): int {
        global $wpdb;

        $this->logger->debug('FindingsMenuPage::get_findings_count');
        if (! $this->table_exists()) {
            return 0;
        }
        return (int) $wpdb->get_var(
            $wpdb->prepare('SELECT COUNT(*) FROM %i', $this->get_table_name())
        );
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
