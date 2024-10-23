<?php

namespace Gdatacyberdefenseag\GdataAntivirus\PluginPage\FullScan;

use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\DetectedFile;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\IFindingsQuery;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\IScansQuery;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\AdminNotices;
use Gdatacyberdefenseag\GdataAntivirus\Vaas\ScanClient;
use Gdatacyberdefenseag\GdataAntivirus\Vaas\VaasOptions;
use Psr\Log\LoggerInterface;


if (! class_exists('FullScanMenuPage')) {
    class FullScanMenuPage {
		private ScanClient $scan_client;
		private AdminNotices $admin_notices;
		private LoggerInterface $logger;
		private IFindingsQuery $findings;
		private IScansQuery $scans;

		public function __construct(
			LoggerInterface $logger,
			ScanClient $scan_client,
			AdminNotices $admin_notices,
			IFindingsQuery $findings,
			IScansQuery $scans,
			VaasOptions $vaas_options,
		) {
			$logger->info('FullScanMenuPage::__construct');
			$this->logger = $logger;
			$this->findings = $findings;
			$this->scans = $scans;

			register_activation_hook(GDATACYBERDEFENCEAG_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__, array( $this->scans, 'create' ));
			register_deactivation_hook(GDATACYBERDEFENCEAG_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__, array( $this->scans, 'remove' ));

			if (! $vaas_options->credentials_configured()) {
				return;
			}
			$this->scan_client        = $scan_client;
			$this->admin_notices      = $admin_notices;
			add_action('init', array( $this, 'setup_fields' ));
			add_action('admin_menu', array( $this, 'setup_menu' ));
			add_action('admin_post_full_scan', array( $this, 'full_scan_interactive' ));
			add_action('gdatacyberdefenseag_antivirus_scheduled_full_scan', array( $this, 'full_scan' ));
			add_action(
				'gdatacyberdefenseag_antivirus_scan_batch',
				array( $this, 'scan_batch' ),
			);

			$this->setup_scheduled_scan();
		}

		private function setup_scheduled_scan() {
			$full_scan_enabled = (bool) get_option('gdatacyberdefenseag_antivirus_options_full_scan_schedule_enabled', false);
			$schedule_start    = get_option('gdatacyberdefenseag_antivirus_options_full_scan_schedule_start', '01:00');
			$next              = wp_next_scheduled('gdatacyberdefenseag_antivirus_scheduled_full_scan');

			if (! $full_scan_enabled && $next) {
				wp_unschedule_event($next, 'gdatacyberdefenseag_antivirus_scheduled_full_scan');
				return;
			}

			if ($full_scan_enabled && ! $next) {
				$timestamp = strtotime($schedule_start);
				$this->logger->debug('schedule start timestamp: ' . $timestamp);
				wp_schedule_event($timestamp, 'daily', 'gdatacyberdefenseag_antivirus_scheduled_full_scan');
				return;
			}
			$nextschedule_start = gmdate('H:i', $next);
			if ($nextschedule_start !== $schedule_start) {
				wp_unschedule_event($next, 'gdatacyberdefenseag_antivirus_scheduled_full_scan');
				$timestamp = strtotime($schedule_start);
				wp_schedule_event($timestamp, 'daily', 'gdatacyberdefenseag_antivirus_scheduled_full_scan');
			}
		}

		public function setup_fields(): void {
			register_setting(
				'gdatacyberdefenseag_antivirus_options_full_scan_run',
				'gdatacyberdefenseag_antivirus_options_full_scan_batch_size',
				array(
					'type'              => 'number',
					'default'           => 100,
					'sanitize_callback' => array( $this, 'gdatacyberdefenseag_antivirus_options_full_scan_batch_size_validation' ),
				)
			);
			register_setting(
				'gdatacyberdefenseag_antivirus_options_full_scan_run',
				'gdatacyberdefenseag_antivirus_options_full_scan_schedule_start',
				array(
					'type'              => 'string',
					'default'           => '01:00',
					'sanitize_callback' => array( $this, 'gdatacyberdefenseag_antivirus_options_full_scan_schedule_start_validation' ),
				)
			);
			register_setting(
				'gdatacyberdefenseag_antivirus_options_full_scan_run',
				'gdatacyberdefenseag_antivirus_options_full_scan_schedule_enabled',
				array(
					'type'    => 'boolean',
					'default' => false,
				)
			);
		}

		public function setup_menu(): void {
			add_settings_section(
				'gdatacyberdefenseag_antivirus_options_full_scan',
				esc_html__('Full Scan', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_options_full_scan_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_SLUG
			);

			add_settings_field(
				'gdatacyberdefenseag_antivirus_options_full_scan_batch_size',
				esc_html__('Batch Size', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_options_full_scan_batch_size_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
				'gdatacyberdefenseag_antivirus_options_full_scan'
			);

			add_settings_field(
				'gdatacyberdefenseag_antivirus_options_full_scan_schedule_enabled',
				esc_html__('Scheduled Scan enabled', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_options_full_scan_schedule_enabled_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
				'gdatacyberdefenseag_antivirus_options_full_scan'
			);

			add_settings_field(
				'gdatacyberdefenseag_antivirus_options_full_scan_schedule_start',
				esc_html__('Scheduled Scan starting Hour', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_options_full_scan_schedule_start_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
				'gdatacyberdefenseag_antivirus_options_full_scan'
			);

			add_submenu_page(
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
				'Full scan',
				'Full scan',
				'manage_options',
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_SLUG,
				array( $this, 'full_scan_menu' )
			);
		}

		public function gdatacyberdefenseag_antivirus_options_full_scan_batch_size_validation( $value ) {
			$option = get_option('gdatacyberdefenseag_antivirus_options_full_scan_batch_size', 100);
			if (0 === $value) {
				$value = $option;
				add_settings_error(
					'gdatacyberdefenseag_antivirus_options_full_scan_batch_size',
					'gdatacyberdefenseag_antivirus_options_full',
					__('batch_size cannot be 0', 'gdata-antivirus')
				);
			}
			if ($value < 100) {
				$value = $option;
				add_settings_error(
					'gdatacyberdefenseag_antivirus_options_full_scan_batch_size',
					'gdatacyberdefenseag_antivirus_options_full',
					__('batch_size should be at least 100', 'gdata-antivirus')
				);
			}
			return $value;
		}

		public function gdatacyberdefenseag_antivirus_options_full_scan_schedule_start_validation( $value ) {
			$option            = get_option('gdatacyberdefenseag_antivirus_options_full_scan_schedule_start', '01:00');
			$full_scan_enabled = get_option('gdatacyberdefenseag_antivirus_options_full_scan_schedule_enabled', false);

			if (! $full_scan_enabled) {
				return $option;
			}
			if (preg_match('#^[0-9]{2}:[0-9]{2}$#', $value) !== 1) {
				$value = $option;
				add_settings_error(
					'gdatacyberdefenseag_antivirus_options_full_scan_schedule_start',
					'gdatacyberdefenseag_antivirus_options_full_scan',
					__('schedule start must be of format H:i', 'gdata-antivirus')
				);
			}
			return $value;
		}

		public function gdatacyberdefenseag_antivirus_options_full_scan_text() {
			echo '<p>' . esc_html__('Here you can set options for the full scan', 'gdata-antivirus') . '</p>';
		}

		public function gdatacyberdefenseag_antivirus_options_full_scan_schedule_enabled_text() {
			$full_scan_enabled = (bool) get_option('gdatacyberdefenseag_antivirus_options_full_scan_schedule_enabled', false);
			echo "<input id='gdatacyberdefenseag_antivirus_options_full_scan_schedule_enabled' name='gdatacyberdefenseag_antivirus_options_full_scan_schedule_enabled' type='checkbox' value='true' " . checked(true, $full_scan_enabled, false) . "' />";
		}

		public function gdatacyberdefenseag_antivirus_options_full_scan_batch_size_text() {
			$batch_size = get_option('gdatacyberdefenseag_antivirus_options_full_scan_batch_size', 100);
			echo "<input id='gdatacyberdefenseag_antivirus_options_full_scan_batch_size' name='gdatacyberdefenseag_antivirus_options_full_scan_batch_size' type='text' value='" . esc_attr($batch_size) . "' />";
		}

		public function gdatacyberdefenseag_antivirus_options_full_scan_schedule_start_text() {
			$schedule_start    = get_option('gdatacyberdefenseag_antivirus_options_full_scan_schedule_start', '01:00');
			$full_scan_enabled =
				(bool) get_option('gdatacyberdefenseag_antivirus_options_full_scan_schedule_enabled', false);
			$this->logger->debug('schedule_start: ' . $schedule_start);

			echo "<input id='gdatacyberdefenseag_antivirus_options_full_scan_schedule_start' name='gdatacyberdefenseag_antivirus_options_full_scan_schedule_start' type='text' value='" . esc_attr($schedule_start) . "' " . ( $full_scan_enabled ? '' : 'disabled' ) . '/>';
		}

		public function full_scan_interactive(): void {
			if (! isset($_POST['gdata-antivirus-full-scan-nonce'])) {
				wp_die(
					esc_html__('Invalid nonce specified', 'gdata-antivirus'),
					esc_html__('Error', 'gdata-antivirus'),
					array(
						'response' => 403,
					)
				);
			}
			if (! wp_verify_nonce(sanitize_key($_POST['gdata-antivirus-full-scan-nonce']), 'gdata-antivirus-full-scan')) {
				wp_die(
					esc_html__('Invalid nonce specified', 'gdata-antivirus'),
					esc_html__('Error', 'gdata-antivirus'),
					array(
						'response' => 403,
					)
				);
			}
			$this->full_scan();
			wp_safe_redirect(wp_get_referer());
		}

		public function full_scan(): void {
			$this->admin_notices->add_notice(__('Full Scan started', 'gdata-antivirus'));

			$batch_size = get_option('gdatacyberdefenseag_antivirus_options_full_scan_batch_size', 100);
			$it         = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(ABSPATH, \FilesystemIterator::SKIP_DOTS));
			$files      = array();
			$this->scans->reset();
			foreach ($it as $file_path) {
				if (! ( $file_path instanceof \SplFileInfo )) {
					continue;
				}
				if ($file_path->isDir()) {
					continue;
				}
				// For testing purposes, we only scan files with eicar in the name
				// if (str_contains($file_path->getPathname(), "eicar") === false) {
				// 	continue;
				// }
				$this->logger->debug($file_path->getPathname());
				array_push($files, $file_path->getPathname());
				if (count($files) >= $batch_size) {
					$this->scans->increase_scheduled();

					wp_schedule_single_event(time(), 'gdatacyberdefenseag_antivirus_scan_batch', array( 'files' => $files ));
					$files = array();
				}
			}
			if (count($files) > 0) {
				$this->scans->increase_scheduled();
				wp_schedule_single_event(time(), 'gdatacyberdefenseag_antivirus_scan_batch', array( 'files' => $files ));
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
					if (! file_exists($file)) {
						continue;
					}
					$scan_client = $this->scan_client;
					$vaas_verdict = $scan_client->scan_file($file);
					if ($vaas_verdict->Verdict === \VaasSdk\Message\Verdict::MALICIOUS) {
						$this->logger->debug('add to findings ' . $file);
						$this->findings->add(new DetectedFile($file, $vaas_verdict->Detection, $vaas_verdict->Sha256));
					}
				}
            } finally {
				$this->scans->increase_finished();
				if ($this->scans->scheduled_count() <= $this->scans->finished_count()) {
					$this->admin_notices->add_notice(__('Full Scan finished', 'gdata-antivirus'));
					$this->scans->reset();
				}
            }
		}

		public function full_scan_menu(): void {
			settings_errors('gdatacyberdefenseag_antivirus_options_full_scan_schedule_start');
			settings_errors('gdatacyberdefenseag_antivirus_options_full_scan_batch_size');
			?>
			<h2>Full Scan Settings</h2>
			<form action="options.php" method="post">
				<?php
				settings_fields('gdatacyberdefenseag_antivirus_options_full_scan_run');
				do_settings_sections(GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_SLUG);
				?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save', 'gdata-antivirus'); ?>" />
			</form>
			<?php
			$scheduled_scans = $this->scans->scheduled_count();
			$finished_scans  = $this->scans->finished_count();
            $cron_jobs = wp_get_ready_cron_jobs();
            $still_running=false;
            foreach($cron_jobs as $key => $cron) {
                foreach($cron as $name =>$job) {
                    if ($name=='gdatacyberdefenseag_antivirus_scan_batch') {
                        $still_running=true;
                        break;
                    }
                }
            }
			if ($still_running === false) {
				?>
				<form action="admin-post.php" method="post">
					<input type="hidden" name="action" value="full_scan">
					<?php wp_nonce_field('gdata-antivirus-full-scan', 'gdata-antivirus-full-scan-nonce'); ?>
					<?php submit_button(__('Run Full Scan', 'gdata-antivirus')); ?>
				</form>
				<?php
			} else {
				?>
				<p>
                <?php
                echo esc_html__('Full Scan is running. ', 'gdata-antivirus') . esc_html($finished_scans) . esc_html(' of ', 'gdata-antivirus') . esc_html($scheduled_scans) . esc_html__(' batches are finished', 'gdata-antivirus');
?>
</p>
				<?php
			}
		}
	}
}
