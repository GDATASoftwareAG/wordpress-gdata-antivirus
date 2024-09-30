<?php

namespace Gdatacyberdefenseag\GdataAntivirus\PluginPage;

use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\IFindingsQuery;
use Gdatacyberdefenseag\GdataAntivirus\Vaas\VaasOptions;
use Psr\Log\LoggerInterface;

if (! class_exists('GdataAntivirusMenuPage')) {
    class GdataAntivirusMenuPage {
		public VaasOptions $vaas_options;
		public IFindingsQuery $findings;

		public function __construct(
			IFindingsQuery $findings,
			LoggerInterface $logger,
			VaasOptions $vaas_options,
		) {
			$logger->info('GdataAntivirusMenuPage::__construct');

			add_action('admin_menu', array( $this, 'setup_menu' ));
			add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ));

			$this->findings     = $findings;
			$this->vaas_options = $vaas_options;
		}

		public function setup_menu(): void {
			add_settings_section(
				'gdatacyberdefenseag_antivirus_options_credentials',
				esc_html__('Credentials', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_credentials_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG
			);
			add_settings_field(
				'gdatacyberdefenseag_antivirus_credentials_authentication_method',
				esc_html__('Authentication Method', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_credentials_authentication_method_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
				'gdatacyberdefenseag_antivirus_options_credentials'
			);
			add_settings_field(
				'gdatacyberdefenseag_antivirus_credentials_client_id',
				esc_html__('Client ID', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_credentials_client_id_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
				'gdatacyberdefenseag_antivirus_options_credentials'
			);
			add_settings_field(
				'gdatacyberdefenseag_antivirus_credentials_client_secret',
				esc_html__('Client Secret', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_credentials_client_secret_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
				'gdatacyberdefenseag_antivirus_options_credentials'
			);
			add_settings_field(
				'gdatacyberdefenseag_antivirus_credentials_username',
				esc_html__('Username', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_credentials_username_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
				'gdatacyberdefenseag_antivirus_options_credentials'
			);
			add_settings_field(
				'gdatacyberdefenseag_antivirus_credentials_password',
				esc_html__('Password', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_credentials_password_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
				'gdatacyberdefenseag_antivirus_options_credentials'
			);
			add_settings_field(
				'gdatacyberdefenseag_antivirus_credentials_vaas_url',
				esc_html__('VaaS-Url', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_credentials_vaas_url_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
				'gdatacyberdefenseag_antivirus_options_credentials'
			);
			add_settings_field(
				'gdatacyberdefenseag_antivirus_credentials_token_endpoint',
				esc_html__('Token-Endpoint', 'gdata-antivirus'),
				array( $this, 'gdatacyberdefenseag_antivirus_credentials_token_endpoint_text' ),
				GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
				'gdatacyberdefenseag_antivirus_options_credentials'
			);

			$count = $this->findings->count();
			if ($count > 0) {
				$menu_title = 'VaaS <span class="awaiting-mod">' . $count . '</span>';
				add_menu_page(
					'G DATA VaaS',
					$menu_title,
					'manage_options',
					GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
					array( $this, 'credentials_menu_item' ),
					plugin_dir_url(__FILE__) . '../PluginPage/assets/gdata16.png'
				);
				add_submenu_page(
					GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
					$menu_title,
					'Credentials',
					'manage_options',
					GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
					array( $this, 'credentials_menu_item' )
				);
			} else {
				add_menu_page(
					'G DATA VaaS',
                    'VaaS',
                    'manage_options',
					GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
					array( $this, 'credentials_menu_item' ),
                    plugin_dir_url(__FILE__) . '../PluginPage/assets/gdata16.png'
                );
				add_submenu_page(
					GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
					'VaaS',
					'Credentials',
					'manage_options',
					GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG,
					array( $this, 'credentials_menu_item' )
				);
			}
		}

		public function enqueue_scripts( $hook_suffix ): void {
			wp_enqueue_script(
				'gdatacyberdefenseag_antivirus_options_credentials_authentication_method_toggle',
				plugin_dir_url(__FILE__) . '/js/authentication-method-toggle.js',
				array(),
				"1.0.0",
				array()
			);
		}

		public function gdatacyberdefenseag_antivirus_credentials_text() {
			?><p><?php \esc_html_e('Here you can set all the options for using the API', 'gdata-antivirus'); ?></p>
			  <p><a href="https://www.gdata.de/vaas-files/vaas-technical-onboarding.html"><?php \esc_html_e('Get your credentials here.', 'gdata-antivirus'); ?></a></p>
			<?php
		}

		public function gdatacyberdefenseag_antivirus_credentials_authentication_method_text() {
			$options = $this->vaas_options->get_options();
			?>
			<select id='gdatacyberdefenseag_antivirus_credentials_authentication_method' name='gdatacyberdefenseag_antivirus_options_credentials[authentication_method]'>";
				<option value='ResourceOwnerPasswordGrant'<?php echo ( $options['authentication_method'] === 'ResourceOwnerPasswordGrant' ) ? ' selected' : ''; ?>>ResourceOwnerPasswordGrant</option>";
				<option value='ClientCredentialsGrant'<?php echo ( $options['authentication_method'] === 'ClientCredentialsGrant' ) ? ' selected' : ''; ?>>ClientCredentialsGrant</option>";
			</select>
			<?php
		}

		public function gdatacyberdefenseag_antivirus_credentials_client_id_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='gdatacyberdefenseag_antivirus_credentials_client_id' name='gdatacyberdefenseag_antivirus_options_credentials[client_id]' type='text' value='<?php echo esc_attr($options['client_id']); ?>' />
			<?php
		}

		public function gdatacyberdefenseag_antivirus_credentials_client_secret_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='gdatacyberdefenseag_antivirus_credentials_client_secret' name='gdatacyberdefenseag_antivirus_options_credentials[client_secret]' type='password' value='<?php echo esc_attr($options['client_secret']); ?>' />
			<?php
		}

		public function gdatacyberdefenseag_antivirus_credentials_username_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='gdatacyberdefenseag_antivirus_credentials_username' name='gdatacyberdefenseag_antivirus_options_credentials[username]' type='text' value='<?php echo esc_attr($options['username']); ?>' />
			<?php
		}

		public function gdatacyberdefenseag_antivirus_credentials_password_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='gdatacyberdefenseag_antivirus_credentials_password' name='gdatacyberdefenseag_antivirus_options_credentials[password]' type='password' value='<?php echo esc_attr($options['password']); ?>' />
			<?php
		}

		public function gdatacyberdefenseag_antivirus_credentials_vaas_url_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='gdatacyberdefenseag_antivirus_credentials_vaas_url' name='gdatacyberdefenseag_antivirus_options_credentials[vaas_url]' type='text' value='<?php echo esc_attr($options['vaas_url']); ?>' />
			<?php
		}

		public function gdatacyberdefenseag_antivirus_credentials_token_endpoint_text() {
			$options = $this->vaas_options->get_options();
			?>
				<input id='gdatacyberdefenseag_antivirus_credentials_token_endpoint' name='gdatacyberdefenseag_antivirus_options_credentials[token_endpoint]' type='text' value='<?php echo esc_attr($options['token_endpoint']); ?>' />
			<?php
		}

		public function credentials_menu_item(): void {
			?>
			<h2>VaaS Settings</h2>
			<form action="options.php" method="post">
				<?php
				settings_fields('gdatacyberdefenseag_antivirus_options_credentials');
				do_settings_sections(GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG);
				?>
				<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save', 'gdata-antivirus'); ?>" />
			</form>
			<?php
		}
	}
}
