<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage;

use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\Findings\FindingsMenuPage;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Vaas\VaasOptions;
use Psr\Log\LoggerInterface;

if (! class_exists('WordpressGdataAntivirusMenuPage')) {
    class WordpressGdataAntivirusMenuPage {
		public FindingsMenuPage $findings_menu_page;
		public VaasOptions $vaas_options;

		public function __construct(
			FindingsMenuPage $findings_menu_page,
			LoggerInterface $logger,
			VaasOptions $vaas_options,
		) {
			$logger->info('WordpressGdataAntivirusMenuPage::__construct');

			\add_action('admin_menu', array( $this, 'setup_menu' ));
			\add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ));

			$this->findings_menu_page  = $findings_menu_page;
			$this->vaas_options        = $vaas_options;
		}

		public function setup_menu(): void {
			\add_settings_section(
				'wordpress_gdata_antivirus_options_credentials',
				esc_html__('Credentials', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_credentials_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG
			);
			\add_settings_field(
				'wordpress_gdata_antivirus_credentials_authentication_method',
				esc_html__('Authentication Method', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_credentials_authentication_method_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
				'wordpress_gdata_antivirus_options_credentials'
			);
			\add_settings_field(
				'wordpress_gdata_antivirus_credentials_client_id',
				esc_html__('Client ID', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_credentials_client_id_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
				'wordpress_gdata_antivirus_options_credentials'
			);
			\add_settings_field(
				'wordpress_gdata_antivirus_credentials_client_secret',
				esc_html__('Client Secret', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_credentials_client_secret_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
				'wordpress_gdata_antivirus_options_credentials'
			);
			\add_settings_field(
				'wordpress_gdata_antivirus_credentials_username',
				esc_html__('Username', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_credentials_username_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
				'wordpress_gdata_antivirus_options_credentials'
			);
			\add_settings_field(
				'wordpress_gdata_antivirus_credentials_password',
				esc_html__('Password', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_credentials_password_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
				'wordpress_gdata_antivirus_options_credentials'
			);
			\add_settings_field(
				'wordpress_gdata_antivirus_credentials_vaas_url',
				esc_html__('VaaS-Url', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_credentials_vaas_url_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
				'wordpress_gdata_antivirus_options_credentials'
			);
			\add_settings_field(
				'wordpress_gdata_antivirus_credentials_token_endpoint',
				esc_html__('Token-Endpoint', 'wordpress-gdata-antivirus'),
				array( $this, 'wordpress_gdata_antivirus_credentials_token_endpoint_text' ),
				WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
				'wordpress_gdata_antivirus_options_credentials'
			);

			$count = $this->findings_menu_page->get_findings_count();
			if ($count > 0) {
				$menu_title = 'VaaS <span class="awaiting-mod">' . $count . '</span>';
				\add_menu_page(
					'G DATA VaaS',
					$menu_title,
					'manage_options',
					WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
					array( $this, 'credentials_menu_item' ),
					\plugin_dir_url(__FILE__) . '../PluginPage/assets/gdata16.png'
				);
				\add_submenu_page(
					WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
					$menu_title,
					'Credentials',
					'manage_options',
					WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
					array( $this, 'credentials_menu_item' )
				);
			} else {
				\add_menu_page(
					'G DATA VaaS',
                    'VaaS',
                    'manage_options',
					WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
					array( $this, 'credentials_menu_item' ),
                    \plugin_dir_url(__FILE__) . '../PluginPage/assets/gdata16.png'
                );
				\add_submenu_page(
					WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
					'VaaS',
					'Credentials',
					'manage_options',
					WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
					array( $this, 'credentials_menu_item' )
				);
			}
		}

		public function enqueue_scripts( $hook_suffix ): void {
			\wp_enqueue_script(
				'wordpress_gdata_antivirus_options_credentials_authentication_method_toggle',
				\plugin_dir_url(__FILE__) . '/js/authentication-method-toggle.js',
				array(),
				"1.0.0",
				array()
			);
		}

		public function wordpress_gdata_antivirus_credentials_text() {
			?><p><?php \esc_html_e('Here you can set all the options for using the API', 'wordpress-gdata-antivirus'); ?></p>
			<?php
		}

		public function wordpress_gdata_antivirus_credentials_authentication_method_text() {
			$options = $this->vaas_options->get_options();
			?>
			<select id='wordpress_gdata_antivirus_credentials_authentication_method' name='wordpress_gdata_antivirus_options_credentials[authentication_method]'>";
				<option value='ResourceOwnerPasswordGrant'<?php echo ( $options['authentication_method'] === 'ResourceOwnerPasswordGrant' ) ? ' selected' : ''; ?>>ResourceOwnerPasswordGrant</option>";
				<option value='ClientCredentialsGrant'<?php echo ( $options['authentication_method'] === 'ClientCredentialsGrant' ) ? ' selected' : ''; ?>>ClientCredentialsGrant</option>";
			</select>
			<?php
		}

		public function wordpress_gdata_antivirus_credentials_client_id_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='wordpress_gdata_antivirus_credentials_client_id' name='wordpress_gdata_antivirus_options_credentials[client_id]' type='text' value='<?php echo \esc_attr($options['client_id']); ?>' />
			<?php
		}

		public function wordpress_gdata_antivirus_credentials_client_secret_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='wordpress_gdata_antivirus_credentials_client_secret' name='wordpress_gdata_antivirus_options_credentials[client_secret]' type='password' value='<?php echo \esc_attr($options['client_secret']); ?>' />
			<?php
		}

		public function wordpress_gdata_antivirus_credentials_username_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='wordpress_gdata_antivirus_credentials_username' name='wordpress_gdata_antivirus_options_credentials[username]' type='text' value='<?php echo \esc_attr($options['username']); ?>' />
			<?php
		}

		public function wordpress_gdata_antivirus_credentials_password_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='wordpress_gdata_antivirus_credentials_password' name='wordpress_gdata_antivirus_options_credentials[password]' type='password' value='<?php echo \esc_attr($options['password']); ?>' />
			<?php
		}

		public function wordpress_gdata_antivirus_credentials_vaas_url_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='wordpress_gdata_antivirus_credentials_vaas_url' name='wordpress_gdata_antivirus_options_credentials[vaas_url]' type='text' value='<?php echo \esc_attr($options['vaas_url']); ?>' />
			<?php
		}

		public function wordpress_gdata_antivirus_credentials_token_endpoint_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='wordpress_gdata_antivirus_credentials_token_endpoint' name='wordpress_gdata_antivirus_options_credentials[token_endpoint]' type='text' value='<?php echo \esc_attr($options['token_endpoint']); ?>' />
			<?php
		}

		public function credentials_menu_item(): void {
			?>
			<h2>VaaS Settings</h2>
			<form action="options.php" method="post">
				<?php
				\settings_fields('wordpress_gdata_antivirus_options_credentials');
				\do_settings_sections(WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG);
				?>
				<input name="submit" class="button button-primary" type="submit" value="<?php \esc_attr_e('Save', 'wordpress-gdata-antivirus'); ?>" />
			</form>
			<?php
		}
	}
}
