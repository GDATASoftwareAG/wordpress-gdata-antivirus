<?php

namespace Gdatacyberdefenseag\GdataAntivirus\PluginPage;

use Psr\Log\LoggerInterface;

if (! class_exists('AdminNotices')) {
	interface AdminNoticesInterface
	{
		public static function add_notice(string $text): void;
		public function save_notices(): void;
		public function output_notices(): void;
	}

	class AdminNotices implements AdminNoticesInterface
	{
		private static $notices = array();

		public function __construct(LoggerInterface $logger)
		{
			$logger->info('AdminNotices::__construct');
			add_action('admin_notices', array($this, 'output_notices'));
			add_action('shutdown', array($this, 'save_notices'));
		}

		public static function add_notice(string $text): void
		{
			self::$notices[] = $text;
		}

		public function save_notices(): void
		{
			update_option('gdatacyberdefenseag_antivirus_notices', self::$notices);
		}

		public function output_notices(): void
		{
			$notices = maybe_unserialize(get_option('gdatacyberdefenseag_antivirus_notices'));

			if (! empty($notices)) {
				echo '<div id="notice" class="notice notice-info is-dismissible">';

				foreach ($notices as $notice) {
					echo '<p>' . wp_kses_post($notice) . '</p>';
				}

				echo '</div>';

				delete_option('gdatacyberdefenseag_antivirus_notices');
			}
		}
	}
}
