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
            \add_action('admin_post_full_scan', [$this, "FullScan"]);
            \add_action(
                'wordpress_gdata_antivirus_scan_batch',
                [$this, 'scanBatch'],
            );
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
            \register_setting("wordpress_gdata_antivirus_options_full_scan_run", "wordpress_gdata_antivirus_options_full_scan", [
                "type" => "number",
                "default" => [
                    "batch_size" => 100,
                ]
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

            \add_submenu_page(
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'FullScan',
                'FullScan',
                'manage_options',
                WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
                [$this, 'FullScanMenu']
            );
        }

        function wordpress_gdata_antivirus_options_full_scan_text()
        {
            echo '<p>' . esc_html__("Here you can set options for the full scan", "wordpress-gdata-antivirus") . '</p>';
        }

        function wordpress_gdata_antivirus_options_full_scan_batch_size_text()
        {
            $options = \get_option('wordpress_gdata_antivirus_options_full_scan', [
                "batch_size" => 100,
            ]);
            echo "<input id='wordpress_gdata_antivirus_options_full_scan_batch_size' name='wordpress_gdata_antivirus_options_full_scan[batch_size]' type='text' value='" . \esc_attr($options['batch_size']) . "' />";
        }

        public function FullScan(): void
        {
            $this->AdminNotices->addNotice(__("Full Scan started", "wordpress-gdata-antivirus"));

            if (!wp_verify_nonce($_POST['wordpress-gdata-antivirus-full-scan-nonce'], 'wordpress-gdata-antivirus-full-scan')) {
                wp_die(__('Invalid nonce specified', "wordpress-gdata-antivirus"), __('Error', "wordpress-gdata-antivirus"), array(
                    'response'     => 403,
                    'back_link' => $_SERVER["HTTP_REFERER"],

                ));
                return;
            }

            $fullScanOptions = \get_option('wordpress_gdata_antivirus_options_full_scan', [
                "batch_size" => 100,
            ]);
            $batchSize = $fullScanOptions["batch_size"];
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

            \wp_redirect($_SERVER["HTTP_REFERER"]);
        }

        public function scanBatch(array $files): void
        {
            try {
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
