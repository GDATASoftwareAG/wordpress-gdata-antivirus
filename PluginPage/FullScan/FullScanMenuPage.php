<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\FullScan;

use Gdatacyberdefenseag\WordpressGdataAntivirus\Vaas\ScanClient;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\AdminNotices;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\Findings\FindingsMenuPage;

define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG . "-findings");
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS_TABLE_NAME', "WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS");

if (!class_exists('FullScanMenuPage')) {
    class FullScanMenuPage
    {
        private ScanClient $ScanClient;
        private AdminNotices $AdminNotices;
        private FindingsMenuPage $FindingsMenuPage;

        public function __construct(FindingsMenuPage $findingsMenuPage)
        {
            register_activation_hook(PLUGIN_WITH_CLASSES__FILE__, [$this, "CreateFullScanOperationsTable"]);
            register_deactivation_hook(PLUGIN_WITH_CLASSES__FILE__, [$this, 'RemoveFullScanOperationsTable']);

            $options = \get_option('wordpress_gdata_antivirus_options_credentials', [
                "client_id" => "",
                "client_secret" => ""
            ]);
            if (empty($options['client_id']) || empty($options['client_secret'])) {
                return;
            }
            $this->ScanClient = new ScanClient();
            $this->AdminNotices = new AdminNotices();
            $this->FindingsMenuPage = $findingsMenuPage;
            \add_action('init', [$this, "SetupFields"]);
            \add_action('admin_menu', [$this, "SetupMenu"]);
            \add_action('admin_post_full_scan', [$this, "FullScanInteractive"]);
            \add_action('wordpress_gdata_antivirus_scheduled_full_scan', [$this, "FullScan"]);
            \add_action(
                'wordpress_gdata_antivirus_scan_batch',
                [$this, 'scanBatch'],
            );

            $this->setupScheduledScan();
        }

        private function setupScheduledScan()
        {
            $fullScanEnabled = (bool)\get_option('wordpress_gdata_antivirus_options_full_scan_schedule_enabled', false);
            $scheduleStart = \get_option('wordpress_gdata_antivirus_options_full_scan_schedule_start', "01:00");
            $next = wp_next_scheduled("wordpress_gdata_antivirus_scheduled_full_scan");
            if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                \file_put_contents(WP_DEBUG_LOG, "fullScanEnabled: " . $fullScanEnabled . "\n", FILE_APPEND);
                \file_put_contents(WP_DEBUG_LOG, "scheduleStart: " . $scheduleStart . "\n", FILE_APPEND);
                \file_put_contents(WP_DEBUG_LOG, "next: " . $next . "\n", FILE_APPEND);
            };

            if (!$fullScanEnabled && $next) {
                \wp_unschedule_event($next, "wordpress_gdata_antivirus_scheduled_full_scan");
                return;
            }

            if ($fullScanEnabled && !$next) {
                $timestamp = strtotime($scheduleStart);
                if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                    \file_put_contents(WP_DEBUG_LOG, "schedule start timestamp: " . $timestamp . "\n", FILE_APPEND);
                };
                $scheduled = \wp_schedule_event($timestamp, 'daily', "wordpress_gdata_antivirus_scheduled_full_scan");
                if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                    \file_put_contents(WP_DEBUG_LOG, "scheduled: " . var_export($scheduled, true) . "\n", FILE_APPEND);
                };
                return;
            }
            $nextScheduleStart = date("H:i", $next);
            if ($nextScheduleStart !== $scheduleStart) {
                \wp_unschedule_event($next, "wordpress_gdata_antivirus_scheduled_full_scan");
                $timestamp = strtotime($scheduleStart);
                \wp_schedule_event($timestamp, 'daily', "wordpress_gdata_antivirus_scheduled_full_scan");
            }
        }

        public function CreateFullScanOperationsTable()
        {
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE " . $this->getTableName() . " (
                scheduled_scans TINYINT NOT NULL DEFAULT 0,
                finished_scans TINYINT NOT NULL DEFAULT 0
            ) $charset_collate;";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);

            $wpdb->query("INSERT INTO " . $this->getTableName() . " (scheduled_scans, finished_scans) VALUES (0, 0)");
        }

        private function getTableName(): string
        {
            global $wpdb;
            return $wpdb->prefix . WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS_TABLE_NAME;
        }

        public function LockScanOperationsTable()
        {
            global $wpdb;
            $wpdb->query("LOCK TABLES " . $this->getTableName() . " WRITE");
        }

        public function UnlockScanOperationsTable()
        {
            global $wpdb;
            $wpdb->query("UNLOCK TABLES " . $this->getTableName() . " WRITE");
        }

        public function RemoveFullScanOperationsTable()
        {
            global $wpdb;
            $wpdb->query("DROP TABLE IF EXISTS " . $this->getTableName());
        }

        public function GetScheduledScans(): int
        {
            global $wpdb;
            return $wpdb->get_var("SELECT scheduled_scans FROM " . $this->getTableName());
        }

        public function IncreaseScheduledScans(): void
        {
            global $wpdb;
            $wpdb->query("UPDATE " . $this->getTableName() . " SET scheduled_scans = scheduled_scans + 1");
        }

        public function GetFinishedScans(): int
        {
            global $wpdb;
            return $wpdb->get_var("SELECT finished_scans FROM " . $this->getTableName());
        }

        public function IncreaseFinishedScans(): void
        {
            global $wpdb;
            $wpdb->query("UPDATE " . $this->getTableName() . " SET finished_scans = finished_scans + 1");
        }

        public function ResetScanOperations(): void
        {
            global $wpdb;
            $wpdb->query("UPDATE " . $this->getTableName() . " SET scheduled_scans = 0, finished_scans = 0");
        }

        public function SetupFields(): void
        {
            \register_setting("wordpress_gdata_antivirus_options_full_scan_run", "wordpress_gdata_antivirus_options_full_scan_batch_size", [
                "type" => "number",
                "default" => 100,
                "sanitize_callback" => [$this, "wordpress_gdata_antivirus_options_full_scan_batch_size_validation"]
            ]);
            \register_setting("wordpress_gdata_antivirus_options_full_scan_run", "wordpress_gdata_antivirus_options_full_scan_schedule_start", [
                "type" => "string",
                "default" => "01:00",
                "sanitize_callback" => [$this, "wordpress_gdata_antivirus_options_full_scan_schedule_start_validation"]
            ]);
            \register_setting("wordpress_gdata_antivirus_options_full_scan_run", "wordpress_gdata_antivirus_options_full_scan_schedule_enabled", [
                "type" => "boolean",
                "default" => false
            ]);
        }

        public function SetupMenu(): void
        {
            \add_settings_section(
                'wordpress_gdata_antivirus_options_full_scan',
                esc_html__('Full Scan', "wordpress-gdata-antivirus"),
                [$this, 'wordpress_gdata_antivirus_options_full_scan_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG
            );

            \add_settings_field(
                "wordpress_gdata_antivirus_options_full_scan_batch_size",
                esc_html__("Batch Size", "wordpress-gdata-antivirus"),
                [$this, 'wordpress_gdata_antivirus_options_full_scan_batch_size_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
                'wordpress_gdata_antivirus_options_full_scan'
            );

            \add_settings_field(
                "wordpress_gdata_antivirus_options_full_scan_schedule_enabled",
                esc_html__("Scheduled Scan enabled", "wordpress-gdata-antivirus"),
                [$this, 'wordpress_gdata_antivirus_options_full_scan_schedule_enabled_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
                'wordpress_gdata_antivirus_options_full_scan'
            );

            \add_settings_field(
                "wordpress_gdata_antivirus_options_full_scan_schedule_start",
                esc_html__("Scheduled Scan starting Hour", "wordpress-gdata-antivirus"),
                [$this, 'wordpress_gdata_antivirus_options_full_scan_schedule_start_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
                'wordpress_gdata_antivirus_options_full_scan'
            );

            \add_submenu_page(
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'FullScan',
                'FullScan',
                'manage_options',
                WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
                [$this, 'FullScanMenu']
            );
        }

        public function wordpress_gdata_antivirus_options_full_scan_batch_size_validation($value)
        {
            $option = get_option('wordpress_gdata_antivirus_options_full_scan_batch_size', 100);
            if ($value == 0) {
                $value = $option;
                add_settings_error(
                    'wordpress_gdata_antivirus_options_full_scan_batch_size',
                    'wordpress_gdata_antivirus_options_full',
                    __("batch_size cannot be 0")
                );
            }
            if ($value < 100) {
                $value = $option;
                add_settings_error(
                    'wordpress_gdata_antivirus_options_full_scan_batch_size',
                    'wordpress_gdata_antivirus_options_full',
                    __("batch_size should be at least 100")
                );
            }
            return $value;
        }

        public function wordpress_gdata_antivirus_options_full_scan_schedule_start_validation($value)
        {
            $option = get_option('wordpress_gdata_antivirus_options_full_scan_schedule_start', "01:00");
            $fullScanEnabled = get_option('wordpress_gdata_antivirus_options_full_scan_schedule_enabled', false);

            if (!$fullScanEnabled)
                return $option;
            if (preg_match("#^[0-9]{2}:[0-9]{2}$#", $value) !== 1) {
                if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                    \file_put_contents(WP_DEBUG_LOG, "does not match pattern\n", FILE_APPEND);
                };
                $value = $option;
                add_settings_error(
                    'wordpress_gdata_antivirus_options_full_scan_schedule_start',
                    'wordpress_gdata_antivirus_options_full_scan',
                    __("schedule start must be of format H:i")
                );
            }
            return $value;
        }

        function wordpress_gdata_antivirus_options_full_scan_text()
        {
            echo '<p>' . esc_html__("Here you can set options for the full scan", "wordpress-gdata-antivirus") . '</p>';
        }

        function wordpress_gdata_antivirus_options_full_scan_schedule_enabled_text()
        {
            $fullScanEnabled = (bool)\get_option('wordpress_gdata_antivirus_options_full_scan_schedule_enabled', false);
            echo "<input id='wordpress_gdata_antivirus_options_full_scan_schedule_enabled' name='wordpress_gdata_antivirus_options_full_scan_schedule_enabled' type='checkbox' value='true' " . \checked(true, $fullScanEnabled, false) . "' />";
        }

        function wordpress_gdata_antivirus_options_full_scan_batch_size_text()
        {
            $batchSize = \get_option('wordpress_gdata_antivirus_options_full_scan_batch_size', 100);
            echo "<input id='wordpress_gdata_antivirus_options_full_scan_batch_size' name='wordpress_gdata_antivirus_options_full_scan_batch_size' type='text' value='" . \esc_attr($batchSize) . "' />";
        }

        function wordpress_gdata_antivirus_options_full_scan_schedule_start_text()
        {
            $scheduleStart = \get_option('wordpress_gdata_antivirus_options_full_scan_schedule_start', "01:00");
            $fullScanEnabled =
                (bool)\get_option('wordpress_gdata_antivirus_options_full_scan_schedule_enabled', false);
            if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                \file_put_contents(WP_DEBUG_LOG, "scheduleStart: " . $scheduleStart . "\n", FILE_APPEND);
            };
            echo "<input id='wordpress_gdata_antivirus_options_full_scan_schedule_start' name='wordpress_gdata_antivirus_options_full_scan_schedule_start' type='text' value='" . \esc_attr($scheduleStart) . "' " . ($fullScanEnabled ? '' : 'disabled') . "/>";
        }

        public function FullScanInteractive(): void
        {
            if (!wp_verify_nonce($_POST['wordpress-gdata-antivirus-full-scan-nonce'], 'wordpress-gdata-antivirus-full-scan')) {
                wp_die(__('Invalid nonce specified', "wordpress-gdata-antivirus"), __('Error', "wordpress-gdata-antivirus"), array(
                    'response'     => 403,
                    'back_link' => $_SERVER["HTTP_REFERER"],

                ));
                return;
            }
            $this->FullScan();
            \wp_redirect($_SERVER["HTTP_REFERER"]);
        }

        public function FullScan(): void
        {
            $this->AdminNotices->addNotice(__("Full Scan started", "wordpress-gdata-antivirus"));

            $batchSize = \get_option('wordpress_gdata_antivirus_options_full_scan_batch_size', 100);
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(ABSPATH, \FilesystemIterator::SKIP_DOTS));
            $files = [];
            foreach ($it as $filePath) {
                if (!($filePath instanceof \SplFileInfo)) {
                    continue;
                }
                if ($filePath->isDir()) {
                    continue;
                }
                \array_push($files, $filePath->getPathname());
                if (count($files) >= $batchSize) {
                    $this->IncreaseScheduledScans();

                    \wp_schedule_single_event(time(), 'wordpress_gdata_antivirus_scan_batch', ['files' => $files]);
                    $files = [];
                }
            }
        }

        public function scanBatch(array $files): void
        {
            try {
                if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                    \file_put_contents(WP_DEBUG_LOG, "does not match pattern" + "\n", FILE_APPEND);
                };
                foreach ($files as $file) {
                    if ($this->ScanClient->scanFile($file) == \VaasSdk\Message\Verdict::MALICIOUS) {
                        $this->FindingsMenuPage->AddFinding($file);
                    }
                }
            } finally {
                $this->IncreaseFinishedScans();
                if ($this->GetScheduledScans() <= $this->GetFinishedScans()) {
                    $this->AdminNotices->addNotice(__("Full Scan finished", "wordpress-gdata-antivirus"));
                    $this->ResetScanOperations();
                }
            }
        }



        public function FullScanMenu(): void
        {
            settings_errors("wordpress_gdata_antivirus_options_full_scan_schedule_start");
            settings_errors("wordpress_gdata_antivirus_options_full_scan_batch_size");
?>
            <h2>VaaS Settings</h2>
            <form action="options.php" method="post">
                <?
                \settings_fields('wordpress_gdata_antivirus_options_full_scan_run');
                \do_settings_sections(WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG); ?>
                <input name="submit" class="button button-primary" type="submit" value="<? \esc_attr_e('Save', "wordpress-gdata-antivirus"); ?>" />
            </form>
            <?
            $scheduledScans = $this->GetScheduledScans();
            $finishedScans = $this->GetFinishedScans();
            if ($scheduledScans <= $finishedScans) {
            ?>
                <form action="admin-post.php" method="post">
                    <input type="hidden" name="action" value="full_scan">
                    <? wp_nonce_field('wordpress-gdata-antivirus-full-scan', 'wordpress-gdata-antivirus-full-scan-nonce'); ?>
                    <?php submit_button(__('Run Full Scan', "wordpress-gdata-antivirus")); ?>
                </form>
            <?
            } else {
            ?>
                <p><? _e("Full Scan is running. " . $finishedScans . " of " . $scheduledScans . " batches are finished", "wordpress-gdata-antivirus"); ?></p>
<?
            }
        }
    }
}
