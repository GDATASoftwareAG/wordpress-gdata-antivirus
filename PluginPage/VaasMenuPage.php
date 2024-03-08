<?php

namespace Gdatacyberdefenseag\WpVaas\PluginPage;

use Gdatacyberdefenseag\WpVaas\Vaas\ScanClient;

if (!class_exists('VaasMenuPage')) {
    class VaasMenuPage
    {
        private ?ScanClient $ScanClient;
        private AdminNotices $AdminNotices;

        public function __construct(?ScanClient $scanClient)
        {
            $this->ScanClient = $scanClient;
            $this->AdminNotices = new AdminNotices();
            \add_action('admin_init', [$this, "SetupFileds"]);
            \add_action('admin_menu', [$this, "SetupMenu"]);
            \add_action('admin_post_full_scan', [$this, "FullScan"]);
            \add_action('admin_post_delete_findings', [$this, "DeleteFindings"]);
        }

        public function SetupFileds(): void
        {
            \register_setting("wp_vaas_plugin_options", "wp_vaas_plugin_options", [
                "type" => "array",
                "default" => [
                    "client_id" => "",
                    "client_secret" => ""
                ]
            ]);

            \add_settings_section('wp_vaas_credentials', esc_html__('Credentials', "wordpress-gdata-antivirus"), [$this, 'wp_vaas_credentials_text'], 'wp_vaas_plugin');
            \add_settings_field("wp_vaas_setting_client_id", esc_html__("Client ID", "wordpress-gdata-antivirus"), [$this, 'wp_vaas_setting_client_id'], 'wp_vaas_plugin', 'wp_vaas_credentials');
            \add_settings_field("wp_vaas_setting_client_secret", esc_html__("Client Secret", "wordpress-gdata-antivirus"), [$this, 'wp_vaas_setting_client_secret'], 'wp_vaas_plugin', 'wp_vaas_credentials');
        }

        function wp_vaas_credentials_text()
        {
            echo '<p>' . esc_html__("Here you can set all the options for using the API", "wordpress-gdata-antivirus") . '</p>';
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
            $scanFindings = \get_option('wp_vaas_plugin_scan_findings');
            if (count($scanFindings) > 0) {
                $menuTitle = 'VaaS <span class="awaiting-mod">' . count($scanFindings) . '</span>';
                \add_menu_page('G Data VaaS', $menuTitle, 'manage_options', 'vaas-menu', [$this, 'CredentialsMenuItem'], \plugin_dir_url(__FILE__) . "../PluginPage/assets/gdata16.png");
                \add_submenu_page('vaas-menu', $menuTitle, 'Credentials', 'manage_options', 'vaas-menu', [$this, 'CredentialsMenuItem']);
                \add_submenu_page("vaas-menu", "Scan Findings", 'Scan Findings <span class="awaiting-mod">' . count($scanFindings) . '</span>', "manage_options", "vaas-menu-scan-findings", [$this, 'ScanFindings']);
            } else {
                \add_menu_page('G Data VaaS', 'VaaS', 'manage_options', 'vaas-menu', "", \plugin_dir_url(__FILE__) . "../PluginPage/assets/gdata16.png");
                \add_submenu_page('vaas-menu', 'VaaS', 'Credentials', 'manage_options', 'vaas-menu', [$this, 'CredentialsMenuItem']);
            }


            if ($this->ScanClient) {
                \add_submenu_page('vaas-menu', 'FullScan', 'FullScan', 'manage_options', 'vaas-menu-full-scan', [$this, 'FullScanMenu']);
            }
        }

        public function DeleteFindings(): void
        {
            if (!wp_verify_nonce($_POST['wordpress-gdata-antivirus-delete-findings-nonce'], 'wordpress-gdata-antivirus-delete-findings')) {
                wp_die(__('Invalid nonce specified', "wordpress-gdata-antivirus"), __('Error', "wordpress-gdata-antivirus"), array(
                    'response'     => 403,
                    'back_link' => $_SERVER["HTTP_REFERER"],

                ));
                return;
            }

            if (!isset($_POST["files"])) {
                $this->AdminNotices->addNotice(__("No files to delete given.", "wordpress-gdata-antivirus"));
                \wp_redirect($_SERVER["HTTP_REFERER"]);
                return;
            }

            $deletedFiles = [];
            foreach ($_POST["files"] as $file) {
                if (!is_writable($file)) {
                    $this->AdminNotices->addNotice(__("Cannot delete file: ", "wordpress-gdata-antivirus") . $file);
                } else {
                    \unlink($file);
                    $deletedFiles = \array_push($deletedFiles, $file);
                }
            }

            $scanFindings = \get_option('wp_vaas_plugin_scan_findings');
            $beforeCount = count($scanFindings);
            $scanFindings = \array_filter($scanFindings, function ($element) use ($deletedFiles) {
                return !\in_array($element, $deletedFiles);
            });
            $afterCount = count($scanFindings);
            if ($beforeCount != $afterCount) {
                \update_option("wp_vaas_plugin_scan_findings", $scanFindings);
            }

            \wp_redirect($_SERVER["HTTP_REFERER"]);
        }

        public function FullScan(): void
        {
            $this->AdminNotices->addNotice(__("Full Scan started", "wordpress-gdata-antivirus"));

            if (!wp_verify_nonce($_POST['wordpress-gdata-antivirus-full-scan-nonce'], 'wordpress-gdata-antivirus-full-scan')) {
                wp_die(__('Invalid nonce specified', "wordpress-gdata-antivirus"), __('Error', "wordpress-gdata-antivirus"), array(
                    'response'     => 403,
                    'back_link' => $_SERVER["HTTP_REFERER"],

                ));
                return;
            }

            $this->ScanClient->fullScan();

            \wp_redirect($_SERVER["HTTP_REFERER"]);

            $this->AdminNotices->addNotice(__("Scan finished", "wordpress-gdata-antivirus"));
        }

        public function FullScanMenu(): void
        {
?>
            <form action="admin-post.php" method="post">
                <input type="hidden" name="action" value="full_scan">
                <? wp_nonce_field('wordpress-gdata-antivirus-full-scan', 'wordpress-gdata-antivirus-full-scan-nonce'); ?>
                <?php submit_button(__('Full Scan', "wordpress-gdata-antivirus")); ?>
            </form>
        <?
        }

        public function ScanFindings(): void
        {
        ?>
            <h1><? _e("We found Malware"); ?></h1>
            <form action="admin-post.php" method="post">

                <table class="wp-list-table widefat fixed striped table-view-list pages">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td>
                            <th scope="col" id="title" class="manage-column column-title column-primary">
                                File
                            </th>
                        </tr>
                    </thead>

                    <tbody id="the-list">
                        <?
                        (array) $scanFindings = \get_option('wp_vaas_plugin_scan_findings');
                        if (count($scanFindings) > 0) {
                            foreach ($scanFindings as $finding) {
                        ?>
                                <tr>
                                    <th scope="row" class="check-column"> <label class="screen-reader-text" for="cb-select-3">
                                            Delete File</label>
                                        <input id="cb-select-3" type="checkbox" name="files[]" value="<? echo $finding ?>">
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

                <input type="hidden" name="action" value="delete_findings">
                <? wp_nonce_field('wordpress-gdata-antivirus-delete-findings', 'wordpress-gdata-antivirus-delete-findings-nonce'); ?>
                <?php submit_button(__('Remove Files', "wordpress-gdata-antivirus")); ?>
            </form>

        <?
        }

        public function CredentialsMenuItem(): void
        {
        ?>
            <h2>VaaS Settings</h2>
            <form action="options.php" method="post">
                <?
                \settings_fields('wp_vaas_plugin_options');
                \do_settings_sections('wp_vaas_plugin'); ?>
                <input name="submit" class="button button-primary" type="submit" value="
    <?
            \esc_attr_e('Save', "wordpress-gdata-antivirus");
    ?>
    " />
            </form>
<?php
        }
    }
}
