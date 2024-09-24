<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database;

use Psr\Log\LoggerInterface;
use wpdb;

class ScansQuery implements IScansQuery {
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
        return $this->wpdb->prefix.GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS_TABLE_NAME;
    }

    public function create(): void {
        $charset_collate = $this->wpdb->get_charset_collate();
        $sql             = 'CREATE TABLE ' . $this->get_table_name() . ' (
            scheduled_scans TINYINT NOT NULL DEFAULT 0,
            finished_scans TINYINT NOT NULL DEFAULT 0
        )' . $charset_collate . ';';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        $this->wpdb->query(
            $this->wpdb->prepare('INSERT INTO %i (scheduled_scans, finished_scans) VALUES (0, 0)', $this->get_table_name())
        );
    }

    public function remove(): void {
        $this->wpdb->query(
            $this->wpdb->prepare('DROP TABLE IF EXISTS %i', $this->get_table_name())
        );
    }

    public function write_lock(): void {
        $this->wpdb->query(
            $this->wpdb->prepare('LOCK TABLES %i WRITE', $this->get_table_name())
        );
    }

    public function write_unlock(): void {
        $this->wpdb->query(
            $this->wpdb->prepare('UNLOCK TABLES %i WRITE', $this->get_table_name())
        );
    }

    public function scheduled_count(): int {
        return $this->wpdb->get_var(
            $this->wpdb->prepare('SELECT scheduled_scans FROM %i', $this->get_table_name())
        );
    }

    public function increase_scheduled(): void {
        $this->wpdb->query(
            $this->wpdb->prepare('UPDATE %i SET scheduled_scans = scheduled_scans + 1', $this->get_table_name())
        );
    }

    public function finished_count(): int {
        return $this->wpdb->get_var(
            $this->wpdb->prepare('SELECT finished_scans FROM %i', $this->get_table_name())
        );
    }

    public function increase_finished(): void {
        $this->wpdb->query(
            $this->wpdb->prepare('UPDATE %i SET finished_scans = finished_scans + 1', $this->get_table_name())
        );
    }

    public function reset(): void {
        $this->wpdb->query(
            $this->wpdb->prepare('UPDATE %i SET scheduled_scans = 0, finished_scans = 0', $this->get_table_name())
        );
    }
}
