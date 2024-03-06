<?php

namespace Gdatacyberdefenseag\WpVaas\Vaas;

use VaasSdk\Vaas;
use VaasSdk\ClientCredentialsGrantAuthenticator;

class ScanClient
{
    private ClientCredentialsGrantAuthenticator $clientCredentialsGrantAuthenticator;
    private Vaas $vaas;
    public function __construct()
    {
        $options = \get_option('wp_vaas_plugin_options');
        $this->vaas = new Vaas(null);
        $this->clientCredentialsGrantAuthenticator = new ClientCredentialsGrantAuthenticator(
            $options['client_id'],
            $options['client_secret'],
            "https://account.gdata.de/realms/vaas-production/protocol/openid-connect/token"
        );

        \add_action("gd_scan_single_file_action", [$this, "scanSingleFile"], 10, 1);
        \add_filter("wp_handle_upload_prefilter", [$this, "scanSingleFile"]);
        \add_filter("wp_handle_sideload_prefilter", [$this, "scanSingleFile"]);
    }

    public function scanSingleFile($file)
    {
        if (defined('WP_DEBUG_LOG')) {
            \file_put_contents(WP_DEBUG_LOG, "wp-vaas: scanning " . $file["name"] . "\n", FILE_APPEND);
        };
        $this->vaas->connect($this->clientCredentialsGrantAuthenticator->getToken());

        $verdict = $this->vaas->ForFile($file["tmp_name"]);
        if ($verdict->Verdict == \VaasSdk\Message\Verdict::MALICIOUS) {
            $file['error'] = __("virus found");
        }
        return $file;
    }

    public function scan(array $upload): array
    {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG !== false && WP_DEBUG_LOG !== "") {
            \file_put_contents(WP_DEBUG_LOG, "wp-vaas: schedule scan " . $upload["file"] . "\n", FILE_APPEND);
        };

        \wp_schedule_single_event(time() + 5, 'gd_scan_single_file_action', array($upload["file"]), true);

        return $upload;
    }
}
