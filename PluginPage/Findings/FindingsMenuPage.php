<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\Findings;

use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\Database\IGdataAntivirusDatabase;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\AdminNotices;
use Psr\Log\LoggerInterface;

if (! class_exists('FindingsMenuPage')) {
	class FindingsMenuPage {
		private LoggerInterface $logger;
		private AdminNotices $admin_notices;
		private IGdataAntivirusFileSystem $files_system;
		private IGdataAntivirusDatabase $database;

		public function __construct(
			LoggerInterface $logger,
			AdminNotices $admin_notices,
			IGdataAntivirusFileSystem $file_system,
			IGdataAntivirusDatabase $database
		) {
			$logger->debug('FindingsMenuPage::__construct');

			$this->files_system = $file_system;
			$this->database = $database;

			$this->logger = $logger;
			$this->admin_notices = $admin_notices;

			register_activation_hook(WORDPRESS_GDATA_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__, array( $this, 'create_findings_table' ));
			register_deactivation_hook(WORDPRESS_GDATA_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__, array( $this, 'remove_findings_table' ));

			if ($this->get_findings_count() === 0) {
				return;
			}

			\add_action('admin_menu', array( $this, 'setup_menu' ));
			\add_action('admin_post_delete_findings', array( $this, 'delete_findings' ));
		}

		private function get_table_name(): string {
			$this->logger->debug('FindingsMenuPage::get_table_name');
			return $this->database->get_prefix(). WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_TABLE_NAME;
		}

		public function create_findings_table() {
			$charset_collate = $this->database->get_charset_collate();
			$sql             = 'CREATE TABLE ' . $this->get_table_name() . ' (
                file_path VARCHAR(512) NOT NULL,
                UNIQUE KEY file_path (file_path)
            )' . $charset_collate . ';';

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$this->database->db_delta($sql);
			\wp_cache_set($this->get_table_name(), 'true', 'WordPressGdataAntivirus');
		}

		public function findings_table_exist(): bool {
			$tables_exists = \wp_cache_get($this->get_table_name(), 'WordPressGdataAntivirus');
			$this->logger->debug('Exists in cache: ' . ($tables_exists ? 'true' : 'false'));
			if (false === $tables_exists) {
				$exists = $this->database->get_var('SHOW TABLES LIKE %s', $this->get_table_name()) === $this->get_table_name();
				$this->logger->debug('Exists in database: ' . ($exists ? 'true' : 'false'));
				\wp_cache_set($this->get_table_name(), \wp_json_encode($exists), 'WordPressGdataAntivirus');
				return $exists;
			}
			if ('true' === $tables_exists) {
				return true;
			}
			return false;
		}

		public function remove_findings_table() {
			if (! $this->findings_table_exist()) {
				return;
			}
			$this->database->query('DROP TABLE IF EXISTS %i', $this->get_table_name());
			\wp_cache_set($this->get_table_name(), 'false', 'WordPressGdataAntivirus');
		}

		public function add_finding( string $file ): void {
			if (! $this->findings_table_exist()) {
				return;
			}

			try {
				$this->database->insert(
					$this->get_table_name(),
					array( 'file_path' => $file )
				);
			} catch (\Exception $e) {
				$this->logger->debug($e->getMessage());
			}
		}

		public function delete_finding( string $file ): void {
			if (! $this->findings_table_exist()) {
				return;
			}
			$this->database->delete(
				$this->get_table_name(),
				array( 'file_path' => $file )
			);
		}

		public function validate_findings(): void {
			if (! $this->findings_table_exist()) {
				return;
			}
			$findings = $this->get_all_findings();

			foreach ($findings as $finding) {
				if (! file_exists($finding['file_path'])) {
					$this->delete_finding($finding['file_path']);
				}
			}
		}

		public function get_all_findings(): array {
			if (! $this->findings_table_exist()) {
				return array();
			}
			return $this->database->get_results('SELECT file_path FROM %i', ARRAY_A, $this->get_table_name());
		}

		public function get_findings_count(): int {
			$this->logger->debug('FindingsMenuPage::get_findings_count');
			if (! $this->findings_table_exist()) {
				return 0;
			}
			return (int) $this->database->get_var('SELECT COUNT(*) FROM %i', $this->get_table_name());
		}

		public function setup_menu(): void {
			\add_submenu_page(
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
				'Scan Findings',
				'Scan Findings <span class="awaiting-mod">' . $this->get_findings_count() . '</span>',
				'manage_options',
				WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_SLUG,
				array( $this, 'findings_list' )
			);
		}

		public function delete_findings(): void {
			if (! isset($_POST['wordpress-gdata-antivirus-delete-findings-nonce'])) {
				wp_die(
					\esc_html__('Invalid nonce specified', 'wordpress-gdata-antivirus'),
					\esc_html__('Error', 'wordpress-gdata-antivirus'),
					array(
						'response' => \intval(403),
					)
				);
			}
			if (! wp_verify_nonce(sanitize_key($_POST['wordpress-gdata-antivirus-delete-findings-nonce']), 'wordpress-gdata-antivirus-delete-findings')) {
				wp_die(
					\esc_html__('Invalid nonce specified', 'wordpress-gdata-antivirus'),
					\esc_html__('Error', 'wordpress-gdata-antivirus'),
					array(
						'response' => \intval(403),
					)
				);
			}

			if (! isset($_POST['files'])) {
				$this->admin_notices->add_notice(\esc_html__('No files to delete given.', 'wordpress-gdata-antivirus'));
				\wp_safe_redirect(\wp_unslash(\wp_get_referer()));
			}
			if (! \is_array($_POST['files'])) {
				$this->admin_notices->add_notice(\esc_html__('No files to delete given.', 'wordpress-gdata-antivirus'));
				\wp_safe_redirect(\wp_unslash(\wp_get_referer()));
			}

			$files = \array_map('sanitize_text_field', \wp_unslash($_POST['files']));
			foreach ($files as $file) {
				if (!$this->files_system->is_writable($file)) {
					$this->admin_notices->add_notice(\esc_html__('Cannot delete file: ', 'wordpress-gdata-antivirus') . $file);
				} else {
					\wp_delete_file($file);
					$this->delete_finding($file);
				}
			}

			\wp_safe_redirect(\wp_unslash(\wp_get_referer()));
		}

		public function findings_list(): void {
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
						$findings = $this->get_all_findings();
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
