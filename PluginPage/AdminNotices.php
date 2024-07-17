<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage;

use Psr\Log\LoggerInterface;

if (! class_exists('AdminNotices')) {
	class AdminNotices {
		private static $notices = array();

		public function __construct( LoggerInterface $logger ) {
			$logger->info('AdminNotices::__construct');
			add_action('admin_notices', array( $this, 'output_notices' ));
			add_action('shutdown', array( $this, 'save_notices' ));
		}

		public static function add_notice( $text ) {
			self::$notices[] = $text;
		}

		public function save_notices() {
			update_option('WordpressGdataAntivirusMenuNotices', self::$notices);
		}

		public function output_notices() {
			$notices = maybe_unserialize(get_option('WordpressGdataAntivirusMenuNotices'));

			if (! empty($notices)) {
				echo '<div id="notice" class="notice notice-info is-dismissible">';

				foreach ($notices as $notice) {
					echo '<p>' . wp_kses_post($notice) . '</p>';
				}

				echo '</div>';

				delete_option('WordpressGdataAntivirusMenuNotices');
			}
		}
	}
}
