<?php

namespace Gdatacyberdefenseag\WpVaas\PluginPage;

if (!class_exists('VaasMenuPage')) {
    class VaasMenuPage
    {
        public function __construct()
        {
            \add_action('admin_init', [$this, "SetupFileds"]);
            \add_action('admin_menu', [$this, "SetupMenu"]);
        }

        public function SetupFileds(): void
        {
            \register_setting("wp_vaas_plugin_options", "wp_vaas_plugin_options", [
                "type" => "array"
            ]);

            \add_settings_section('wp_vaas_credentials', 'Credentials', [$this, 'wp_vaas_credentials_text'], 'wp_vaas_plugin');

            \add_settings_field("wp_vaas_setting_client_id", "Client ID", [$this, 'wp_vaas_setting_client_id'], 'wp_vaas_plugin', 'wp_vaas_credentials');
            \add_settings_field("wp_vaas_setting_client_secret", "Client Secret", [$this, 'wp_vaas_setting_client_secret'], 'wp_vaas_plugin', 'wp_vaas_credentials');
        }

        function wp_vaas_credentials_text()
        {
            echo '<p>Here you can set all the options for using the API</p>';
        }

        function wp_vaas_setting_client_id()
        {
            $options = \get_option('wp_vaas_plugin_options');
            echo "<input id='wp_vaas_setting_client_id' name='wp_vaas_plugin_options[client_id]' type='text' value='" . \esc_attr($options['client_id']) . "' />";
        }

        function wp_vaas_setting_client_secret()
        {
            $options = \get_option('wp_vaas_plugin_options');
            echo "<input id='wp_vaas_setting_client_secret' name='wp_vaas_plugin_options[client_secret]' type='password' value='" . \esc_attr($options['client_secret']) . "' />";
        }

        public function SetupMenu(): void
        {

            \add_menu_page('G Data VaaS', 'VaaS', 'manage_options', 'vaas-menu', [$this, 'MainMenuItem'], \plugin_dir_url(__FILE__) . "../PluginPage/assets/gdata16.png");
            $scanFindings = \json_decode(\get_option('wp_vaas_plugin_scan_findings'));
            if ($scanFindings > 0) {
                \add_submenu_page("vaas-menu", "Scan Findings", "Scan Findings", "manage_options", "vaas-menu-scan-findings", [$this, 'ScanFindings'], \plugin_dir_url(__FILE__) . "../PluginPage/assets/virus.png");
            }
        }

        public function ScanFindings(): void
        {
            echo "<h1>We found Malware</h1>";
            (array) $scanFindings = \json_decode(\get_option('wp_vaas_plugin_scan_findings'));
            if (count($scanFindings) > 0) {
                echo "<ul>";
                foreach ($scanFindings as $finding) {
                    echo "<li>$finding</li>";
                }
                echo "</ul>";
            }
        }

        public function MainMenuItem(): void
        {
?>
<h2>VaaS Settings</h2>
<form action="options.php" method="post">
    <?php
            \settings_fields('wp_vaas_plugin_options');
            \do_settings_sections('wp_vaas_plugin'); ?>
    <input name="submit" class="button button-primary" type="submit" value="
    <?php
            \esc_attr_e('Save');
    ?>
    " />
</form>
<?php
        }
    }
}