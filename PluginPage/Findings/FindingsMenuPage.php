<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\Findings;

use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\AdminNotices;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Logging\WordpressGdataAntivirusPluginDebugLogger;

define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_SLUG', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG . '-findings');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_TABLE_NAME', 'WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_TABLE');

if (!class_exists('FindingsMenuPage')) {
    class FindingsMenuPage
    {
        private AdminNotices $AdminNotices;

        public function __construct()
        {
            register_activation_hook(PLUGIN_WITH_CLASSES__FILE__, [$this, 'CreateFindingsTable']);
            register_deactivation_hook(PLUGIN_WITH_CLASSES__FILE__, [$this, 'RemoveFindingsTable']);

            if ($this->GetFindingsCount() === 0) {
                return;
            }

            $this->AdminNotices = new AdminNotices();
            \add_action('admin_menu', [$this, 'SetupMenu']);
            \add_action('admin_post_delete_findings', [$this, 'DeleteFindings']);
        }

        private function getTableName(): string
        {
            global $wpdb;
            return $wpdb->prefix . WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_TABLE_NAME;
        }

        public function CreateFindingsTable()
        {
            global $wpdb;

            $charset_collate = $wpdb->get_charset_collate();
            $sql = 'CREATE TABLE ' . $this->getTableName() . ' (
                file_path VARCHAR(512) NOT NULL,
                UNIQUE KEY file_path (file_path)
            )' . $charset_collate . ';';

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($sql);
            \wp_cache_set($this->getTableName(), 'true', 'WordPressGdataAntivirus');
        }

        public function FindingsTableExist(): bool
        {
            $tablesExists = \wp_cache_get($this->getTableName(), 'WordPressGdataAntivirus');
            if (false === $tablesExists) {
                global $wpdb;
                $exists = $wpdb->get_var('SHOW TABLES LIKE \'' . $this->getTableName() . '\'') === $this->getTableName();
                \wp_cache_set($this->getTableName(), \wp_json_encode($exists), 'WordPressGdataAntivirus');
                return $exists;
            }
            if ('true' === $tablesExists) {
                return true;
            }
            return false;
        }

        public function RemoveFindingsTable()
        {
            if (!$this->FindingsTableExist()) {
                return;
            }
            global $wpdb;
            $wpdb->query('DROP TABLE IF EXISTS ' . $this->getTableName());
            \wp_cache_set($this->getTableName(), 'false', 'WordPressGdataAntivirus');
        }

        public function AddFinding(string $file): void
        {
            if (!$this->FindingsTableExist()) {
                return;
            }

            global $wpdb;
            try {
                $wpdb->insert(
                    $this->getTableName(),
                    ['file_path' => $file]
                );
            } catch (\Exception $e) {
                WordpressGdataAntivirusPluginDebugLogger::Log($e->getMessage());
            }
        }

        public function DeleteFinding(string $file): void
        {
            if (!$this->FindingsTableExist()) {
                return;
            }
            global $wpdb;
            $wpdb->delete(
                $this->getTableName(),
                ['file_path' => $file]
            );
        }

        public function ValidateFindings(): void
        {
            if (!$this->FindingsTableExist()) {
                return;
            }
            $findings = $this->GetAllFindings();

            foreach ($findings as $finding) {
                if (!file_exists($finding['file_path'])) {
                    $this->DeleteFinding($finding['file_path']);
                }
            }
        }

        public function GetAllFindings(): array
        {
            if (!$this->FindingsTableExist()) {
                return [];
            }
            global $wpdb;
            return $wpdb->get_results('SELECT file_path FROM ' . $this->getTableName(), ARRAY_A);
        }

        public function GetFindingsCount(): int
        {
            if (!$this->FindingsTableExist()) {
                return 0;
            }
            global $wpdb;
            return $wpdb->get_var('SELECT COUNT(*) FROM ' . $this->getTableName());
        }

        public function SetupMenu(): void
        {
            \add_submenu_page(
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'Scan Findings',
                'Scan Findings <span class="awaiting-mod">' . $this->GetFindingsCount() . '</span>',
                'manage_options',
                WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_SLUG,
                [$this, 'FindingsList']
            );
        }

        public function DeleteFindings(): void
        {
            if (!isset($_POST['wordpress-gdata-antivirus-delete-findings-nonce'])) {
                wp_die(\esc_html__('Invalid nonce specified', 'wordpress-gdata-antivirus'), \esc_html__('Error', 'wordpress-gdata-antivirus'), [
                    'response' => \intval(403),
                ]);
            }
            if (!wp_verify_nonce(sanitize_key($_POST['wordpress-gdata-antivirus-delete-findings-nonce']), 'wordpress-gdata-antivirus-delete-findings')) {
                wp_die(\esc_html__('Invalid nonce specified', 'wordpress-gdata-antivirus'), \esc_html__('Error', 'wordpress-gdata-antivirus'), [
                    'response' => \intval(403),
                ]);
            }

            if (!isset($_POST['files'])) {
                $this->AdminNotices->addNotice(\esc_html__('No files to delete given.', 'wordpress-gdata-antivirus'));
                \wp_safe_redirect(\wp_unslash(\wp_get_referer()));
            }
            if (!\is_array($_POST['files'])) {
                $this->AdminNotices->addNotice(\esc_html__('No files to delete given.', 'wordpress-gdata-antivirus'));
                \wp_safe_redirect(\wp_unslash(\wp_get_referer()));
            }

            $files = array_values(\wp_unslash($_POST['files']));
            foreach ($files as $file) {
                if (!is_writable($file)) {
                    $this->AdminNotices->addNotice(\esc_html__('Cannot delete file: ', 'wordpress-gdata-antivirus') . $file);
                } else {
                    \wp_delete_file($file);
                    $this->DeleteFinding($file);
                }
            }

            \wp_safe_redirect(\wp_unslash(\wp_get_referer()));
        }

        public function FindingsList(): void
        {
?>
            <h1><?php esc_html_e('We found Malware'); ?></h1>
            <form action="admin-post.php" method="post">

                <table class="wp-list-table widefat fixed striped table-view-list pages">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td>
                            <th scope="col" id="title" class="manage-column column-title column-primary">
                                File
                            </th>
                        </tr>
                    </thead>

                    <tbody id="the-list">
                        <?php
                        $findings = $this->GetAllFindings();
                        if (count($findings) > 0) {
                            foreach ($findings as $finding) {
                        ?>
                                <tr>
                                    <th scope="row" class="check-column"> <label class="screen-reader-text" for="cb-select-3">
                                            Delete File</label>
                                        <input id="cb-select-3" type="checkbox" name="files[]" value="<?php echo \esc_html($finding['file_path']); ?>">
                                        <div class="locked-indicator">
                                            <span class="locked-indicator-icon" aria-hidden="true"></span>
                                            <span class="screen-reader-text">
                                                Delete File</span>
                                        </div>
                                    </th>
                                    <td>
                                        <?php
                                        echo \esc_html($finding['file_path']);
                                        ?>
                                    </td>
                                </tr>
                        <?php
                            }
                        }
                        ?>

                    </tbody>
                </table>

                <input type="hidden" name="action" value="delete_findings">
                <?php wp_nonce_field('wordpress-gdata-antivirus-delete-findings', 'wordpress-gdata-antivirus-delete-findings-nonce'); ?>
                <?php submit_button(__('Remove Files', 'wordpress-gdata-antivirus')); ?>
            </form>

<?php
        }
    }
}
