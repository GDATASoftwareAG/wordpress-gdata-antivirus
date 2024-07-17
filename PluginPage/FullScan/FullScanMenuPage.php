<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\FullScan;

use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\Database\IGdataAntivirusDatabase;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\AdminNotices;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\Findings\FindingsMenuPage;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Vaas\ScanClient;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Vaas\VaasOptions;
use Psr\Log\LoggerInterface;

use function Amp\async;
use function Amp\Future\awaitAll;

if (! class_exists('FullScanMenuPage')) {
    class FullScanMenuPage {
		private ScanClient $scan_client;
		private AdminNotices $admin_notices;
		private FindingsMenuPage $findings_menu_page;
		private LoggerInterface $logger;
		private IGdataAntivirusDatabase $database;

		public function __construct(
			FindingsMenuPage $findings_menu_page,
			LoggerInterface $logger,
			ScanClient $scan_client,
			AdminNotices $admin_notices,
			IGdataAntivirusDatabase $database,
			VaasOptions $vaas_options,
		) {
			$logger->info('FullScanMenuPage::__construct');
			$this->logger = $logger;
			$this->database = $database;

			register_activation_hook(WORDPRESS_GDATA_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__, array( $this, 'create_full_scan_operations_table' ));
			register_deactivation_hook(WORDPRESS_GDATA_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__, array( $this, 'remove_full_scan_operations_table' ));

			if (! $vaas_options->credentials_configured()) {
				return;
			}
			$this->scan_client        = $scan_client;
			$this->admin_notices      = $admin_notices;
			$this->findings_menu_page = $findings_menu_page;
			\add_action('init', array( $this, 'setup_fields' ));
			\add_action('admin_menu', array( $this, 'setup_menu' ));
			\add_action('admin_post_full_scan', array( $this, 'full_scan_interactive' ));
			\add_action('wordpress_gdata_antivirus_scheduled_full_scan', array( $this, 'full_scan' ));
			\add_action(
				'wordpress_gdata_antivirus_scan_batch',
				array( $this, 'scan_batch' ),
			);

			$this->setup_scheduled_scan();
		}

		private function setup_scheduled_scan() {
			$full_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_full_scan_schedule_enabled', false);
			$schedule_start    = \get_option('wordpress_gdata_antivirus_options_full_scan_schedule_start', '01:00');
			$next              = wp_next_scheduled('wordpress_gdata_antivirus_scheduled_full_scan');

			if (! $full_scan_enabled && $next) {
				\wp_unschedule_event($next, 'wordpress_gdata_antivirus_scheduled_full_scan');
				return;
			}

			if ($full_scan_enabled && ! $next) {
				$timestamp = strtotime($schedule_start);
				$this->logger->debug('schedule start timestamp: ' . $timestamp);
				\wp_schedule_event($timestamp, 'daily', 'wordpress_gdata_antivirus_scheduled_full_scan');
				return;
			}
			$nextschedule_start = gmdate('H:i', $next);
			if ($nextschedule_start !== $schedule_start) {
				\wp_unschedule_event($next, 'wordpress_gdata_antivirus_scheduled_full_scan');
				$timestamp = strtotime($schedule_start);
				\wp_schedule_event($timestamp, 'daily', 'wordpress_gdata_antivirus_scheduled_full_scan');
			}
		}

		public function create_full_scan_operations_table() {
			$charset_collate = $this->database->get_charset_collate();
			$sql             = 'CREATE TABLE ' . $this->get_table_name() . ' (
                scheduled_scans TINYINT NOT NULL DEFAULT 0,
                finished_scans TINYINT NOT NULL DEFAULT 0
            )' . $charset_collate . ';';

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta($sql);

			$this->database->query('INSERT INTO %i (scheduled_scans, finished_scans) VALUES (0, 0)', $this->get_table_name());
		}

		private function get_table_name(): string {
			return $this->database->get_prefix() . WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS_TABLE_NAME;
		}

		public function lock_scan_operations_table() {
			$this->database->query('LOCK TABLES %i WRITE', $this->get_table_name());
		}

		public function unlock_scan_operations_table() {
			$this->database->query('UNLOCK TABLES %i WRITE', $this->get_table_name());
		}

		public function remove_full_scan_operations_table() {
			$this->database->query('DROP TABLE IF EXISTS %i', $this->get_table_name());
		}

		public function get_scheduled_scans(): int {
			return $this->database->get_var('SELECT scheduled_scans FROM %i', $this->get_table_name());
		}

		public function increase_scheduled_scans(): void {
			$this->database->query('UPDATE %i SET scheduled_scans = scheduled_scans + 1', $this->get_table_name());
		}

		public function get_finished_scans(): int {
			return $this->database->get_var('SELECT finished_scans FROM %i', $this->get_table_name());
		}

		public function increase_finished_scans(): void {
			$this->database->query('UPDATE %i SET finished_scans = finished_scans + 1', $this->get_table_name());
		}

		public function reset_scan_operations(): void {
			$this->database->query('UPDATE %i SET scheduled_scans = 0, finished_scans = 0', $this->get_table_name());
		}

		public function setup_fields(): void {
			\register_setting(
				'wordpress_gdata_antivirus_options_full_scan_run',
				'wordpress_gdata_antivirus_options_full_scan_batch_size',
				array(
					'type'              => 'number',
					'default'           => 100,
					'sanitize_callback' => array( $this, 'wordpress_gdata_antivirus_options_full_scan_batch_size_validation' ),
				)
			);
			\register_setting(
				'wordpress_gdata_antivirus_options_full_scan_run',
				'wordpress_gdata_antivirus_options_full_scan_schedule_start',
				array(
					'type'              => 'string',
					'default'           => '01:00',
					'sanitize_callback' => array( $this, 'wordpress_gdata_antivirus_options_full_scan_schedule_start_validation' ),
				)
			);
			\register_setting(
				'wordpress_gdata_antivirus_options_full_scan_run',
				'wordpress_gdata_antivirus_options_full_scan_schedule_enabled',
				array(
					'type'    => 'boolean',
					'default' => false,
				)
			);
		}

		public function setup_menu(): void {
			\add_settings_section(
				'wordpress_gdata_antivirus_options_full_scan',
				esc_html__('Full Scan', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_options_full_scan_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG
			);

			\add_settings_field(
				'wordpress_gdata_antivirus_options_full_scan_batch_size',
				esc_html__('Batch Size', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_options_full_scan_batch_size_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
				'wordpress_gdata_antivirus_options_full_scan'
			);

			\add_settings_field(
				'wordpress_gdata_antivirus_options_full_scan_schedule_enabled',
				esc_html__('Scheduled Scan enabled', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_options_full_scan_schedule_enabled_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
				'wordpress_gdata_antivirus_options_full_scan'
			);

			\add_settings_field(
				'wordpress_gdata_antivirus_options_full_scan_schedule_start',
				esc_html__('Scheduled Scan starting Hour', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_options_full_scan_schedule_start_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
				'wordpress_gdata_antivirus_options_full_scan'
			);

			\add_submenu_page(
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
				'Full scan',
				'Full scan',
				'manage_options',
				WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
				array( $this, 'full_scan_menu' )
			);
		}

		public function wordpress_gdata_antivirus_options_full_scan_batch_size_validation( $value ) {
			$option = get_option('wordpress_gdata_antivirus_options_full_scan_batch_size', 100);
			if (0 === $value) {
				$value = $option;
				add_settings_error(
					'wordpress_gdata_antivirus_options_full_scan_batch_size',
					'wordpress_gdata_antivirus_options_full',
					__('batch_size cannot be 0')
				);
			}
			if ($value < 100) {
				$value = $option;
				add_settings_error(
					'wordpress_gdata_antivirus_options_full_scan_batch_size',
					'wordpress_gdata_antivirus_options_full',
					__('batch_size should be at least 100')
				);
			}
			return $value;
		}

		public function wordpress_gdata_antivirus_options_full_scan_schedule_start_validation( $value ) {
			$option            = get_option('wordpress_gdata_antivirus_options_full_scan_schedule_start', '01:00');
			$full_scan_enabled = get_option('wordpress_gdata_antivirus_options_full_scan_schedule_enabled', false);

			if (! $full_scan_enabled) {
				return $option;
			}
			if (preg_match('#^[0-9]{2}:[0-9]{2}$#', $value) !== 1) {
				$value = $option;
				add_settings_error(
					'wordpress_gdata_antivirus_options_full_scan_schedule_start',
					'wordpress_gdata_antivirus_options_full_scan',
					__('schedule start must be of format H:i')
				);
			}
			return $value;
		}

		public function wordpress_gdata_antivirus_options_full_scan_text() {
			echo '<p>' . esc_html__('Here you can set options for the full scan', 'wordpress-gdata-antivirus') . '</p>';
		}

		public function wordpress_gdata_antivirus_options_full_scan_schedule_enabled_text() {
			$full_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_full_scan_schedule_enabled', false);
			echo "<input id='wordpress_gdata_antivirus_options_full_scan_schedule_enabled' name='wordpress_gdata_antivirus_options_full_scan_schedule_enabled' type='checkbox' value='true' " . \checked(true, $full_scan_enabled, false) . "' />";
		}

		public function wordpress_gdata_antivirus_options_full_scan_batch_size_text() {
			$batch_size = \get_option('wordpress_gdata_antivirus_options_full_scan_batch_size', 100);
			echo "<input id='wordpress_gdata_antivirus_options_full_scan_batch_size' name='wordpress_gdata_antivirus_options_full_scan_batch_size' type='text' value='" . \esc_attr($batch_size) . "' />";
		}

		public function wordpress_gdata_antivirus_options_full_scan_schedule_start_text() {
			$schedule_start    = \get_option('wordpress_gdata_antivirus_options_full_scan_schedule_start', '01:00');
			$full_scan_enabled =
				(bool) \get_option('wordpress_gdata_antivirus_options_full_scan_schedule_enabled', false);
			$this->logger->debug('schedule_start: ' . $schedule_start);

			echo "<input id='wordpress_gdata_antivirus_options_full_scan_schedule_start' name='wordpress_gdata_antivirus_options_full_scan_schedule_start' type='text' value='" . \esc_attr($schedule_start) . "' " . ( $full_scan_enabled ? '' : 'disabled' ) . '/>';
		}

		public function full_scan_interactive(): void {
			if (! isset($_POST['wordpress-gdata-antivirus-full-scan-nonce'])) {
				wp_die(
					\esc_html__('Invalid nonce specified', 'wordpress-gdata-antivirus'),
					\esc_html__('Error', 'wordpress-gdata-antivirus'),
					array(
						'response' => 403,
					)
				);
			}
			if (! wp_verify_nonce(\sanitize_key($_POST['wordpress-gdata-antivirus-full-scan-nonce']), 'wordpress-gdata-antivirus-full-scan')) {
				wp_die(
					\esc_html__('Invalid nonce specified', 'wordpress-gdata-antivirus'),
					\esc_html__('Error', 'wordpress-gdata-antivirus'),
					array(
						'response' => 403,
					)
				);
			}
			$this->full_scan();
			\wp_safe_redirect(\wp_get_referer());
		}

		public function full_scan(): void {
			$this->admin_notices->add_notice(__('Full Scan started', 'wordpress-gdata-antivirus'));

			$batch_size = \get_option('wordpress_gdata_antivirus_options_full_scan_batch_size', 100);
			$it         = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(ABSPATH, \FilesystemIterator::SKIP_DOTS));
			$files      = array();
			foreach ($it as $file_path) {
				if (! ( $file_path instanceof \SplFileInfo )) {
					continue;
				}
				if ($file_path->isDir()) {
					continue;
				}
				$this->logger->debug($file_path->getPathname());
				\array_push($files, $file_path->getPathname());
				if (count($files) >= $batch_size) {
					$this->increase_scheduled_scans();

					\wp_schedule_single_event(time(), 'wordpress_gdata_antivirus_scan_batch', array( 'files' => $files ));
					$files = array();
				}
			}
			if (count($files) > 0) {
				$this->increase_scheduled_scans();
				\wp_schedule_single_event(time(), 'wordpress_gdata_antivirus_scan_batch', array( 'files' => $files ));
			}
		}

		public function scan_batch( array $files ): void {
			$this->scan_client->connect();
			try {
				foreach ($files as $file) {
					/**
					 * The scans are scheduled in a different job
					 * the actual scan of the batch is always delayed
					 * therefore the files can already be deleted or moved
					 * we need to check if the file still exists
					 * */
					if (! \file_exists($file)) {
						continue;
					}
					$scan_client = $this->scan_client;
					if ($scan_client->scan_file($file) === \VaasSdk\Message\Verdict::MALICIOUS) {
						$this->logger->debug('add to findings ' . $file);
						$this->findings_menu_page->add_finding($file);
					}
				}
            } finally {
				$this->increase_finished_scans();
				if ($this->get_scheduled_scans() <= $this->get_finished_scans()) {
					$this->admin_notices->add_notice(__('Full Scan finished', 'wordpress-gdata-antivirus'));
					$this->reset_scan_operations();
				}
            }
		}

		public function full_scan_menu(): void {
			settings_errors('wordpress_gdata_antivirus_options_full_scan_schedule_start');
			settings_errors('wordpress_gdata_antivirus_options_full_scan_batch_size');
			?>
			<h2>Full Scan Settings</h2>
			<form action="options.php" method="post">
				<?php
				\settings_fields('wordpress_gdata_antivirus_options_full_scan_run');
				\do_settings_sections(WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG);
				?>
				<input name="submit" class="button button-primary" type="submit" value="<?php \esc_attr_e('Save', 'wordpress-gdata-antivirus'); ?>" />
			</form>
			<?php
			$scheduled_scans = $this->get_scheduled_scans();
			$finished_scans  = $this->get_finished_scans();
			if ($scheduled_scans <= $finished_scans) {
				?>
				<form action="admin-post.php" method="post">
					<input type="hidden" name="action" value="full_scan">
					<?php wp_nonce_field('wordpress-gdata-antivirus-full-scan', 'wordpress-gdata-antivirus-full-scan-nonce'); ?>
					<?php submit_button(__('Run Full Scan', 'wordpress-gdata-antivirus')); ?>
				</form>
				<?php
			} else {
				?>
				<p>
                <?php
                echo \esc_html__('Full Scan is running. ', 'wordpress-gdata-antivirus') . \esc_html($finished_scans) . \esc_html(' of ', 'wordpress-gdata-antivirus') . \esc_html($scheduled_scans) . \esc_html__(' batches are finished', 'wordpress-gdata-antivirus');
?>
</p>
				<?php
			}
		}
	}
}
