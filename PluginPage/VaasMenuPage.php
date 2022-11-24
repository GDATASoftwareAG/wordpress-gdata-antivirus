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
            $scanFindings = \json_decode(\get_option('wp_vaas_plugin_scan_findings'));
            if (count($scanFindings) > 0) {
                \add_menu_page('G Data VaaS', 'VaaS <span class="awaiting-mod">' . count($scanFindings) . '</span>', 'manage_options', 'vaas-menu', "", \plugin_dir_url(__FILE__) . "../PluginPage/assets/gdata16.png");
                \add_submenu_page('vaas-menu', 'Credentials', 'Credentials', 'manage_options', 'vaas-menu', [$this, 'MainMenuItem']);
                \add_submenu_page("vaas-menu", "Scan Findings", 'Scan Findings <span class="awaiting-mod">' . count($scanFindings) . '</span>', "manage_options", "vaas-menu-scan-findings", [$this, 'ScanFindings'], \plugin_dir_url(__FILE__) . "../PluginPage/assets/virus.png");
            } else {
                \add_menu_page('G Data VaaS', 'VaaS', 'manage_options', 'vaas-menu', "", \plugin_dir_url(__FILE__) . "../PluginPage/assets/gdata16.png");
                \add_submenu_page('vaas-menu', 'Credentials', 'Credentials', 'manage_options', 'vaas-menu', [$this, 'MainMenuItem']);
            }
        }

        public function ScanFindings(): void
        {

?>
<h1>We found Malware</h1>
<table class="wp-list-table widefat fixed striped table-view-list pages">
    <thead>
        <tr>
            <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text"
                    for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td>
            <th scope="col" id="title" class="manage-column column-title column-primary">
                File
            </th>
        </tr>
    </thead>

    <tbody id="the-list">
        <?
        (array) $scanFindings = \json_decode(\get_option('wp_vaas_plugin_scan_findings'));
        if (count($scanFindings) > 0) {
            foreach ($scanFindings as $finding) {
            ?>
        <tr>
            <th scope="row" class="check-column"> <label class="screen-reader-text" for="cb-select-3">
                    Delete File</label>
                <input id="cb-select-3" type="checkbox" name="post[]" value="3">
                <div class="locked-indicator">
                    <span class="locked-indicator-icon" aria-hidden="true"></span>
                    <span class="screen-reader-text">
                        Delete File</span>
                </div>
            </th>
            <td>
                <?
                echo $finding;
            ?>
            </td>
        </tr>
        <?
            }
        }
        ?>

    </tbody>
</table>
<?
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