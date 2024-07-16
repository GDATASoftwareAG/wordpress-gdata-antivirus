<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage;

use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\Findings\FindingsMenuPage;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\FullScan\FullScanMenuPage;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\OnDemandScan\OnDemandScan;

define('WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG', 'wordpress-gdata-antivirus-menu');

if (!class_exists('WordpressGdataAntivirusMenuPage')) {
    class WordpressGdataAntivirusMenuPage
    {
        public FullScanMenuPage $FullScanMenuPage;
        public FindingsMenuPage $FindingsMenuPage;
        public OnDemandScan $OnDemandScan;

        public static Array $vaasOptionDefaults = [
            'authentication_method' => 'ResourceOwnerPasswordGrant',
            'client_id'             => '',
            'client_secret'         => ' ',
            'username'              => '',
            'password'              => '',
            'vaas_url'              => 'wss://gateway.staging.vaas.gdatasecurity.de',
            'token_endpoint'        => 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token'
        ];

        public function __construct()
        {
            \add_action('init', [$this, 'SetupFileds']);
            \add_action('admin_menu', [$this, 'SetupMenu']);
            \add_action( 'admin_enqueue_scripts', [$this, 'EnqueueScripts']);

            $this->FindingsMenuPage = new FindingsMenuPage();
            $this->FullScanMenuPage = new FullScanMenuPage($this->FindingsMenuPage);
            $this->OnDemandScan = new OnDemandScan();
        }

        public function SetupFileds(): void
        {
            \register_setting(
                'wordpress_gdata_antivirus_options_credentials',
                'wordpress_gdata_antivirus_options_credentials ',
                [
                    'type'     => 'array',
                    'default ' => self::$vaasOptionDefaults,
                ]
            );
        }

        public function SetupMenu(): void
        {
            \add_settings_section(
                'wordpress_gdata_antivirus_options_credentials',
                esc_html__('Credentials', 'wordpress-gdata-antivirus'),
                [$this, 'wordpress_gdata_antivirus_credentials_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG
            );
            \add_settings_field(
                'wordpress_gdata_antivirus_credentials_authentication_method',
                esc_html__('Authentication Method', 'wordpress-gdata-antivirus'),
                [$this, 'wordpress_gdata_antivirus_credentials_authentication_method_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'wordpress_gdata_antivirus_options_credentials'
            );
            \add_settings_field(
                'wordpress_gdata_antivirus_credentials_client_id',
                esc_html__('Client ID', 'wordpress-gdata-antivirus'),
                [$this, 'wordpress_gdata_antivirus_credentials_client_id_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'wordpress_gdata_antivirus_options_credentials'
            );
            \add_settings_field(
                'wordpress_gdata_antivirus_credentials_client_secret',
                esc_html__('Client Secret', 'wordpress-gdata-antivirus'),
                [$this, 'wordpress_gdata_antivirus_credentials_client_secret_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'wordpress_gdata_antivirus_options_credentials'
            );
            \add_settings_field(
                'wordpress_gdata_antivirus_credentials_username',
                esc_html__('Username', 'wordpress-gdata-antivirus'),
                [$this, 'wordpress_gdata_antivirus_credentials_username_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'wordpress_gdata_antivirus_options_credentials'
            );
            \add_settings_field(
                'wordpress_gdata_antivirus_credentials_password',
                esc_html__('Password', 'wordpress-gdata-antivirus'),
                [$this, 'wordpress_gdata_antivirus_credentials_password_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'wordpress_gdata_antivirus_options_credentials'
            );
            \add_settings_field(
                'wordpress_gdata_antivirus_credentials_vaas_url',
                esc_html__('VaaS-Url', 'wordpress-gdata-antivirus'),
                [$this, 'wordpress_gdata_antivirus_credentials_vaas_url_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'wordpress_gdata_antivirus_options_credentials'
            );
            \add_settings_field(
                'wordpress_gdata_antivirus_credentials_token_endpoint',
                esc_html__('Token-Endpoint', 'wordpress-gdata-antivirus'),
                [$this, 'wordpress_gdata_antivirus_credentials_token_endpoint_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'wordpress_gdata_antivirus_options_credentials'
            );

            $count = $this->FindingsMenuPage->GetFindingsCount();
            if ($count > 0) {
                $menuTitle = 'VaaS <span class="awaiting-mod">' . $count . '</span>';
                \add_menu_page(
                    'G DATA VaaS',
                    $menuTitle,
                    'manage_options',
                    WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                    [$this, 'CredentialsMenuItem'],
                    \plugin_dir_url(__FILE__) . '../PluginPage/assets/gdata16.png'
                );
                \add_submenu_page(
                    WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                    $menuTitle,
                    'Credentials',
                    'manage_options',
                    WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                    [$this, 'CredentialsMenuItem']
                );
            } else {
                \add_menu_page('G DATA VaaS', 'VaaS', 'manage_options', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG, '', \plugin_dir_url(__FILE__) . '../PluginPage/assets/gdata16.png');
                \add_submenu_page(WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG, 'VaaS', 'Credentials', 'manage_options', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG, [$this, 'CredentialsMenuItem']);
            }
        }

        public function EnqueueScripts($hook_suffix): void {
            // if (!\stristr(\WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG, $hook_suffix)) {
            //     return;
            // }
            \wp_enqueue_script(
                'wordpress_gdata_antivirus_options_credentials_authentication_method_toggle',
                \plugin_dir_url(__FILE__) . '/js/authentication-method-toggle.js',
                [], false, []);
        }

        public function wordpress_gdata_antivirus_credentials_text()
        {
            ?><p><?php \esc_html_e('Here you can set all the options for using the API', 'wordpress-gdata-antivirus'); ?></p><?php
        }

        public function wordpress_gdata_antivirus_credentials_authentication_method_text()
        {
            $options = $this->GetVaasOption();
            ?>
            <select id='wordpress_gdata_antivirus_credentials_authentication_method' name='wordpress_gdata_antivirus_options_credentials[authentication_method]'>";
                <option value='ResourceOwnerPasswordGrant'<?php echo ($options['authentication_method'] == 'ResourceOwnerPasswordGrant') ? ' selected' : ''; ?>>ResourceOwnerPasswordGrant</option>";
                <option value='ClientCredentialsGrant'<?php echo ($options['authentication_method'] == 'ClientCredentialsGrant') ? ' selected' : ''; ?>>ClientCredentialsGrant</option>";
            </select>
            <?php
        }

        public function wordpress_gdata_antivirus_credentials_client_id_text()
        {
            $options = $this->GetVaasOption();
            ?>
                <input id='wordpress_gdata_antivirus_credentials_client_id' name='wordpress_gdata_antivirus_options_credentials[client_id]' type='text' value='<?php \esc_attr_e($options['client_id']); ?>' />
            <?php
        }

        public function wordpress_gdata_antivirus_credentials_client_secret_text()
        {
            $options = $this->GetVaasOption();
            ?>
                <input id='wordpress_gdata_antivirus_credentials_client_secret' name='wordpress_gdata_antivirus_options_credentials[client_secret]' type='password' value='<?php \esc_attr_e($options['client_secret']); ?>' />
            <?php
        }

        public function wordpress_gdata_antivirus_credentials_username_text()
        {
            $options = $this->GetVaasOption();
            ?>
                <input id='wordpress_gdata_antivirus_credentials_username' name='wordpress_gdata_antivirus_options_credentials[username]' type='text' value='<?php \esc_attr_e($options['username']); ?>' />
            <?php
        }

        public function wordpress_gdata_antivirus_credentials_password_text()
        {
            $options = $this->GetVaasOption();
            ?>
                <input id='wordpress_gdata_antivirus_credentials_password' name='wordpress_gdata_antivirus_options_credentials[password]' type='password' value='<?php \esc_attr_e($options['password']); ?>' />
            <?php
        }

        public function wordpress_gdata_antivirus_credentials_vaas_url_text()
        {
            $options = $this->GetVaasOption();
            ?>
                <input id='wordpress_gdata_antivirus_credentials_vaas_url' name='wordpress_gdata_antivirus_options_credentials[vaas_url]' type='text' value='<?php \esc_attr_e($options['vaas_url']); ?>' />
            <?php
        }

        public function wordpress_gdata_antivirus_credentials_token_endpoint_text()
        {
            $options = $this->GetVaasOption();
            ?>
                <input id='wordpress_gdata_antivirus_credentials_token_endpoint' name='wordpress_gdata_antivirus_options_credentials[token_endpoint]' type='text' value='<?php \esc_attr_e($options['token_endpoint']); ?>' />
            <?php
        }

        public static function GetVaasOption(): Array {
            return \get_option('wordpress_gdata_antivirus_options_credentials', self::$vaasOptionDefaults);
        }

        public static function CredentialsConfigured(): bool {
            $options = \get_option('wordpress_gdata_antivirus_options_credentials', self::$vaasOptionDefaults);
            if ($options['authentication_method'] == 'ResourceOwnerPasswordGrant') {
                return !empty($options['username']) && !empty($options['password']);
            } else {
                return !empty($options['client_id']) && !empty($options['client_secret']);
            }
        }

        public function CredentialsMenuItem(): void
        {
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
