<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\Vaas;

use VaasSdk\Vaas;
use VaasSdk\ClientCredentialsGrantAuthenticator;
use VaasSdk\VaasOptions;
use GuzzleHttp\Psr7;
use VaasSdk\Message\Verdict;

class ScanClient
{
    private ClientCredentialsGrantAuthenticator $clientCredentialsGrantAuthenticator;
    private Vaas $vaas;
    public function __construct()
    {
        $options = \get_option('wp_vaas_plugin_options');
        $this->vaas = new Vaas(null, null, new VaasOptions(false, false));
        $this->clientCredentialsGrantAuthenticator = new ClientCredentialsGrantAuthenticator(
            $options['client_id'],
            $options['client_secret'],
            "https://account.gdata.de/realms/vaas-production/protocol/openid-connect/token"
        );
        $this->vaas->connect($this->clientCredentialsGrantAuthenticator->getToken());

        \add_filter("wp_handle_upload_prefilter", [$this, "scanSingleFile"]);
        \add_filter("wp_handle_sideload_prefilter", [$this, "scanSingleFile"]);
    }

    public function scanSingleFile($file)
    {
        $verdict = $this->scanFile($file["tmp_name"]);
        if ($verdict == \VaasSdk\Message\Verdict::MALICIOUS) {
            $file['error'] = __("virus found");
        }
        return $file;
    }

    public function scanFile($filePath): Verdict
    {
        if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
            \file_put_contents(WP_DEBUG_LOG, "wordpress-gdata-antivirus: scanning " . $filePath . "\n", FILE_APPEND);
        };
        return $this->vaas->ForFile($filePath)->Verdict;
    }
}
