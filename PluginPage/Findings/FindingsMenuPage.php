<?php

namespace Gdatacyberdefenseag\GdataAntivirus\PluginPage\Findings;

use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\IFindingsQuery;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\AdminNotices;
use Psr\Log\LoggerInterface;

if (! class_exists('FindingsMenuPage')) {
	class FindingsMenuPage {
		private LoggerInterface $logger;
		private AdminNotices $admin_notices;
		private IGdataAntivirusFileSystem $files_system;
		private IFindingsQuery $findings;

		public function __construct(
			LoggerInterface $logger,
			AdminNotices $admin_notices,
			IGdataAntivirusFileSystem $file_system,
			IFindingsQuery $findings
		) {
			$logger->debug('FindingsMenuPage::__construct');

			$this->files_system = $file_system;
			$this->findings = $findings;

			$this->logger = $logger;
			$this->admin_notices = $admin_notices;

			register_activation_hook(GDATACYBERDEFENCEAG_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__, array( $this->findings, 'create' ));
			register_deactivation_hook(GDATACYBERDEFENCEAG_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__, array( $this->findings, 'remove' ));

			if ($this->findings->count() === 0) {
				return;
			}

			add_action('admin_menu', array( $this, 'setup_menu' ));
			add_action('admin_post_delete_findings', array( $this, 'delete_findings' ));
		}

		public function setup_menu(): void {
			add_submenu_page(
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
				'Scan Findings',
				'Scan Findings <span class="awaiting-mod">' . $this->findings->count() . '</span>',
				'manage_options',
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FINDINGS_SLUG,
				array( $this, 'findings_list' )
			);
		}

		public function validate_findings(): void {
			$this->logger->debug('FindingsMenuPage::validate_findings');
			$this->findings->validate();
		}

		public function delete_findings(): void {
			$this->logger->debug('FindingsMenuPage::delete_findings');
			if (! isset($_POST['gdata-antivirus-delete-findings-nonce'])) {
				wp_die(
					esc_html__('Invalid nonce specified', 'gdata-antivirus'),
					esc_html__('Error', 'gdata-antivirus'),
					array(
						'response' => intval(403),
					)
				);
			}
			if (! wp_verify_nonce(sanitize_key($_POST['gdata-antivirus-delete-findings-nonce']), 'gdata-antivirus-delete-findings')) {
				wp_die(
					esc_html__('Invalid nonce specified', 'gdata-antivirus'),
					esc_html__('Error', 'gdata-antivirus'),
					array(
						'response' => intval(403),
					)
				);
			}

			if (! isset($_POST['files'])) {
				$this->admin_notices->add_notice(esc_html__('No files to delete given.', 'gdata-antivirus'));
				wp_safe_redirect(wp_unslash(wp_get_referer()));
			}
			if (! is_array($_POST['files'])) {
				$this->admin_notices->add_notice(esc_html__('No files to delete given.', 'gdata-antivirus'));
				wp_safe_redirect(wp_unslash(wp_get_referer()));
			}

			$files = array_map('sanitize_text_field', wp_unslash($_POST['files']));
			foreach ($files as $file) {
				if (!$this->files_system->is_writable($file)) {
					$this->admin_notices->add_notice(esc_html__('Cannot delete file: ', 'gdata-antivirus') . $file);
				} else {
					wp_delete_file($file);
					$this->findings->delete($file);
				}
			}

			wp_safe_redirect(wp_unslash(wp_get_referer()));
		}

		public function findings_list(): void {
			?>
			<h1><?php esc_html_e('We found Malware', 'gdata-antivirus'); ?></h1>
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
						$findings = $this->findings->get_all();
						if (count($findings) > 0) {
							foreach ($findings as $finding) {
								?>
								<tr>
									<th scope="row" class="check-column"> <label class="screen-reader-text" for="cb-select-3">
											Delete File</label>
										<input id="cb-select-3" type="checkbox" name="files[]" value="<?php echo esc_html($finding['file_path']); ?>">
										<div class="locked-indicator">
											<span class="locked-indicator-icon" aria-hidden="true"></span>
											<span class="screen-reader-text">
												Delete File</span>
										</div>
									</th>
									<td>
										<?php
										echo esc_html($finding['file_path']);
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
				<?php wp_nonce_field('gdata-antivirus-delete-findings', 'gdata-antivirus-delete-findings-nonce'); ?>
				<?php submit_button(__('Remove Files', 'gdata-antivirus')); ?>
			</form>

			<?php
		}
	}
}
