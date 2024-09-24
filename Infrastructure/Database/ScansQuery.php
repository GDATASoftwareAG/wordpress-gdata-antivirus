<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database;

class ScansQuery implements IScansQuery {
    public function __construct() {
    }

    private function get_table_name(): string {
        global $wpdb;

        return $wpdb->prefix.GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS_TABLE_NAME;
    }

    public function create(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $sql             = 'CREATE TABLE ' . $this->get_table_name() . ' (
            scheduled_scans TINYINT NOT NULL DEFAULT 0,
            finished_scans TINYINT NOT NULL DEFAULT 0
        )' . $charset_collate . ';';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        $wpdb->query(
            $wpdb->prepare('INSERT INTO %i (scheduled_scans, finished_scans) VALUES (0, 0)', $this->get_table_name())
        );
    }

    public function remove(): void {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare('DROP TABLE IF EXISTS %i', $this->get_table_name())
        );
    }

    public function write_lock(): void {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare('LOCK TABLES %i WRITE', $this->get_table_name())
        );
    }

    public function write_unlock(): void {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare('UNLOCK TABLES %i WRITE', $this->get_table_name())
        );
    }

    public function scheduled_count(): int {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare('SELECT scheduled_scans FROM %i', $this->get_table_name())
        );
    }

    public function increase_scheduled(): void {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare('UPDATE %i SET scheduled_scans = scheduled_scans + 1', $this->get_table_name())
        );
    }

    public function finished_count(): int {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare('SELECT finished_scans FROM %i', $this->get_table_name())
        );
    }

    public function increase_finished(): void {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare('UPDATE %i SET finished_scans = finished_scans + 1', $this->get_table_name())
        );
    }

    public function reset(): void {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare('UPDATE %i SET scheduled_scans = 0, finished_scans = 0', $this->get_table_name())
        );
    }
}
