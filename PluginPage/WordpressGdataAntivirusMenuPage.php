<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage;

use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\Findings\FindingsMenuPage;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\FullScan\FullScanMenuPage;

define('WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG', "wordpress-gdata-antivirus-menu");

if (!class_exists('WordpressGdataAntivirusMenuPage')) {
    class WordpressGdataAntivirusMenuPage
    {
        public FullScanMenuPage $FullScanMenuPage;
        public FindingsMenuPage $FindingsMenuPage;

        public function __construct()
        {
            \add_action('init', [$this, "SetupFileds"]);
            \add_action('admin_menu', [$this, "SetupMenu"]);

            $this->FindingsMenuPage = new FindingsMenuPage();
            $this->FullScanMenuPage = new FullScanMenuPage($this->FindingsMenuPage);
        }

        public function SetupFileds(): void
        {
            \register_setting(
                "wordpress_gdata_antivirus_options_credentials",
                "wordpress_gdata_antivirus_options_credentials",
                [
                    "type" => "array",
                    "default" => [
                        "client_id" => "",
                        "client_secret" => ""
                    ]
                ]
            );
        }

        public function SetupMenu(): void
        {
            \add_settings_section(
                'wordpress_gdata_antivirus_options_credentials',
                esc_html__('Credentials', "wordpress-gdata-antivirus"),
                [$this, 'wordpress_gdata_antivirus_credentials_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG
            );
            \add_settings_field(
                "wordpress_gdata_antivirus_credentials_client_id",
                esc_html__("Client ID", "wordpress-gdata-antivirus"),
                [$this, 'wordpress_gdata_antivirus_credentials_client_id_text'],
                WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG,
                'wordpress_gdata_antivirus_options_credentials'
            );
            \add_settings_field(
                "wordpress_gdata_antivirus_credentials_client_secret",
                esc_html__("Client Secret", "wordpress-gdata-antivirus"),
                [$this, 'wordpress_gdata_antivirus_credentials_client_secret_text'],
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
                    \plugin_dir_url(__FILE__) . "../PluginPage/assets/gdata16.png"
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
                \add_menu_page('G DATA VaaS', 'VaaS', 'manage_options', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG, "", \plugin_dir_url(__FILE__) . "../PluginPage/assets/gdata16.png");
                \add_submenu_page(WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG, 'VaaS', 'Credentials', 'manage_options', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG, [$this, 'CredentialsMenuItem']);
            }
        }

        function wordpress_gdata_antivirus_credentials_text()
        {
            echo '<p>' . esc_html__("Here you can set all the options for using the API", "wordpress-gdata-antivirus") . '</p>';
        }

        function wordpress_gdata_antivirus_credentials_client_id_text()
        {
            $options = \get_option('wordpress_gdata_antivirus_options_credentials', [
                "client_id" => "",
                "client_secret" => ""
            ]);
            echo "<input id='wordpress_gdata_antivirus_credentials_client_id' name='wordpress_gdata_antivirus_options_credentials[client_id]' type='text' value='" . \esc_attr($options['client_id']) . "' />";
        }

        function wordpress_gdata_antivirus_credentials_client_secret_text()
        {
            $options = \get_option('wordpress_gdata_antivirus_options_credentials', [
                "client_id" => "",
                "client_secret" => ""
            ]);
            echo "<input id='wordpress_gdata_antivirus_credentials_client_secret' name='wordpress_gdata_antivirus_options_credentials[client_secret]' type='password' value='" . \esc_attr($options['client_secret']) . "' />";
        }

        public function CredentialsMenuItem(): void
        {
?>
            <h2>VaaS Settings</h2>
            <form action="options.php" method="post">
                <?php
                \settings_fields('wordpress_gdata_antivirus_options_credentials');
                \do_settings_sections(WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG); ?>
                <input name="submit" class="button button-primary" type="submit" value="<?php \esc_attr_e('Save', "wordpress-gdata-antivirus"); ?>" />
            </form>
<?php
        }
    }
}
