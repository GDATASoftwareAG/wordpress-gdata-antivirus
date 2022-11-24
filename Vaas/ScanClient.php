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
        $this->vaas = new Vaas(null);
        $options = \get_option('wp_vaas_plugin_options');

        $this->clientCredentialsGrantAuthenticator = new ClientCredentialsGrantAuthenticator(
            $options['client_id'], $options['client_secret'],
            "https://keycloak-vaas.gdatasecurity.de/realms/vaas/protocol/openid-connect/token"
        );

        \add_action("gd_scan_single_file_action", [$this, "scanSingleFile"], 10, 1);

        \add_filter("wp_handle_upload", [$this, "scan"]);

        \add_option("wp_vaas_plugin_scan_findings", \json_encode([]));
    }

    public function scanSingleFile(string $fileName): void
    {
        $this->vaas->connect($this->clientCredentialsGrantAuthenticator->getToken());

        $verdict = $this->vaas->ForFile($fileName);
        if ($verdict->Verdict == \VaasSdk\Message\Verdict::MALICIOUS) {
            $scanFindings = \json_decode(\get_option('wp_vaas_plugin_scan_findings'));
            if ($scanFindings == null) {
                $scanFindings = [];
            }
            \array_push($scanFindings, $fileName);
            \update_option("wp_vaas_plugin_scan_findings", json_encode($scanFindings));
        }
    }


    public function scan(array $upload): array
    {
        \file_put_contents(\plugin_dir_path(__FILE__) . "/log", "scan", FILE_APPEND);
        \wp_schedule_single_event(time() + 2, 'gd_scan_single_file_action', array($upload["file"]), true);

        return $upload;
    }
}