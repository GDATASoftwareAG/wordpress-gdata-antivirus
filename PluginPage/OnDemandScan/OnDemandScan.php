<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\OnDemandScan;

use Gdatacyberdefenseag\WordpressGdataAntivirus\Vaas\VaasOptions;
use Psr\Log\LoggerInterface;

if (! class_exists('OnDemandScan')) {
	class OnDemandScan {
		public function __construct(
			LoggerInterface $logger,
			VaasOptions $vaas_options
		) {
			$logger->info('OnDemandScan::__construct');
			if (! $vaas_options->credentials_configured()) {
				return;
			}
			\add_action('init', array( $this, 'setup_fields' ));
			\add_action('admin_menu', array( $this, 'setup_menu' ));
		}

		public function setup_fields(): void {
			\register_setting(
				'wordpress_gdata_antivirus_options_on_demand_scan',
				'wordpress_gdata_antivirus_options_on_demand_scan_media_upload_scan_enabled',
				array(
					'type'    => 'boolean',
					'default' => false,
				)
			);
			\register_setting(
				'wordpress_gdata_antivirus_options_on_demand_scan',
				'wordpress_gdata_antivirus_options_on_demand_scan_plugin_upload_scan_enabled',
				array(
					'type'    => 'boolean',
					'default' => false,
				)
			);
			\register_setting(
				'wordpress_gdata_antivirus_options_on_demand_scan',
				'wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled',
				array(
					'type'    => 'boolean',
					'default' => false,
				)
			);
			\register_setting(
				'wordpress_gdata_antivirus_options_on_demand_scan',
				'wordpress_gdata_antivirus_options_on_demand_scan_pingback_scan_enabled',
				array(
					'type'    => 'boolean',
					'default' => false,
				)
			);
			\register_setting(
				'wordpress_gdata_antivirus_options_on_demand_scan',
				'wordpress_gdata_antivirus_options_on_demand_scan_post_scan_enabled',
				array(
					'type'    => 'boolean',
					'default' => false,
				)
			);
		}

		public function setup_menu(): void {
			\add_settings_section(
				'wordpress_gdata_antivirus_options_on_demand_scan',
				esc_html__('OnDemand scans', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_options_on_demand_scans_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG
			);

			\add_settings_field(
				'wordpress_gdata_antivirus_options_on_demand_scan_media_upload_scan_enabled',
				esc_html__('Media upload scan enabled', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_options_media_upload_scan_enabled_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG,
				'wordpress_gdata_antivirus_options_on_demand_scan'
			);

			\add_settings_field(
				'wordpress_gdata_antivirus_options_on_demand_scan_plugin_upload_scan_enabled',
				esc_html__('Plugin upload scan enabled', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_options_plugin_upload_scan_enabled_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG,
				'wordpress_gdata_antivirus_options_on_demand_scan'
			);

			\add_settings_field(
				'wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled',
				esc_html__('Comment scan enabled', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_options_comment_scan_enabled_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG,
				'wordpress_gdata_antivirus_options_on_demand_scan'
			);

			\add_settings_field(
				'wordpress_gdata_antivirus_options_on_demand_scan_pingback_scan_enabled',
				esc_html__('Pingback scan enabled', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_options_pingback_scan_enabled_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG,
				'wordpress_gdata_antivirus_options_on_demand_scan'
			);

			\add_settings_field(
				'wordpress_gdata_antivirus_options_on_demand_scan_post_scan_enabled',
				esc_html__('Post scan enabled', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_options_post_scan_enabled_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG,
				'wordpress_gdata_antivirus_options_on_demand_scan'
			);

			\add_submenu_page(
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
				'OnDemandScan',
				'OnDemandScan',
				'manage_options',
				WORDPRESS_GDATA_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG,
				array( $this, 'on_demand_scan_menu' )
			);
		}

		public function wordpress_gdata_antivirus_options_on_demand_scans_text() {
			echo '<p>' . esc_html__('Here you can set options for the on demand scans', 'wordpress-gdata-antivirus') . '</p>';
		}

		public function wordpress_gdata_antivirus_options_media_upload_scan_enabled_text() {
			$media_upload_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_media_upload_scan_enabled', false);
			echo '<input type="checkbox" id="wordpress_gdata_antivirus_options_on_demand_scan_media_upload_scan_enabled" name="wordpress_gdata_antivirus_options_on_demand_scan_media_upload_scan_enabled" ' . \checked(true, $media_upload_scan_enabled, false) . '>';
		}

		public function wordpress_gdata_antivirus_options_plugin_upload_scan_enabled_text() {
			$plugin_upload_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_plugin_upload_scan_enabled', false);
			echo '<input type="checkbox" id="wordpress_gdata_antivirus_options_on_demand_scan_plugin_upload_scan_enabled" name="wordpress_gdata_antivirus_options_on_demand_scan_plugin_upload_scan_enabled" ' . \checked(true, $plugin_upload_scan_enabled, false) . '>';
		}

		public function wordpress_gdata_antivirus_options_comment_scan_enabled_text() {
			$comment_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled', false);
			echo '<input type="checkbox" id="wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled" name="wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled" ' . \checked(true, $comment_scan_enabled, false) . '>';
		}

		public function wordpress_gdata_antivirus_options_pingback_scan_enabled_text() {
			$pingback_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_pingback_scan_enabled', false);
			echo '<input type="checkbox" id="wordpress_gdata_antivirus_options_on_demand_scan_pingback_scan_enabled" name="wordpress_gdata_antivirus_options_on_demand_scan_pingback_scan_enabled" ' . \checked(true, $pingback_scan_enabled, false) . '>';
		}

		public function wordpress_gdata_antivirus_options_post_scan_enabled_text() {
			$post_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_post_scan_enabled', false);
			echo '<input type="checkbox" id="wordpress_gdata_antivirus_options_on_demand_scan_post_scan_enabled" name="wordpress_gdata_antivirus_options_on_demand_scan_post_scan_enabled" ' . \checked(true, $post_scan_enabled, false) . '>';
		}

		public function on_demand_scan_menu(): void {
			?>
			<h2>OnDenamns Scan Settings</h2>
			<form action="options.php" method="post">
				<?php
				settings_fields('wordpress_gdata_antivirus_options_on_demand_scan');
				do_settings_sections(WORDPRESS_GDATA_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG);
				?>
				<input name="submit" class="button button-primary" type="submit" value="<?php \esc_attr_e('Save', 'wordpress-gdata-antivirus'); ?>" />
			</form>
			<?php
		}
	}
}
