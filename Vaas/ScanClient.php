<?php

namespace Gdatacyberdefenseag\WpVaas\Vaas;

use VaasSdk\Vaas;
use VaasSdk\ClientCredentialsGrantAuthenticator;
use GuzzleHttp\Psr7;

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

        \add_option("wp_vaas_plugin_scan_findings", \json_encode([]));

        \add_filter("wp_handle_upload_prefilter", [$this, "scanSingleFile"]);
        \add_filter("wp_handle_sideload_prefilter", [$this, "scanSingleFile"]);
    }

    public function scanSingleFile($file)
    {
        if (defined('WP_DEBUG_LOG')) {
            \file_put_contents(WP_DEBUG_LOG, "wordpress-gdata-antivirus: scanning " . $file["name"] . "\n", FILE_APPEND);
        };
        $this->vaas->connect($this->clientCredentialsGrantAuthenticator->getToken());

        $verdict = $this->vaas->ForFile($file["tmp_name"]);
        if ($verdict->Verdict == \VaasSdk\Message\Verdict::MALICIOUS) {
            $file['error'] = __("virus found");
        }
        return $file;
    }

    public function fullScan()
    {
        $time_start = microtime(true);
        if (defined('WP_DEBUG_LOG')) {
            \file_put_contents(WP_DEBUG_LOG, "requested full scan\n", FILE_APPEND);
        };
        if (defined('WP_DEBUG_LOG')) {
            \file_put_contents(WP_DEBUG_LOG, "scanning while wordpress directory: " . ABSPATH .  "\n", FILE_APPEND);
        };
        $this->vaas->connect($this->clientCredentialsGrantAuthenticator->getToken());

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(ABSPATH, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $filePath) {
            if (!($filePath instanceof \SplFileInfo)) {
                continue;
            }
            if ($filePath->isDir()) {
                continue;
            }
            if (defined('WP_DEBUG_LOG')) {
                \file_put_contents(WP_DEBUG_LOG, "scanning file: " . $filePath .  "\n", FILE_APPEND);
            };

            $verdict = $this->vaas->ForStream(Psr7\Utils::streamFor(fopen($filePath, 'r')));
            if ($verdict->Verdict == \VaasSdk\Message\Verdict::MALICIOUS) {
                if (defined('WP_DEBUG_LOG')) {
                    \file_put_contents(WP_DEBUG_LOG, "virus found: " . $filePath .  "\n", FILE_APPEND);
                };
                $scanFindings = \json_decode(\get_option('wp_vaas_plugin_scan_findings'));
                if ($scanFindings == null) {
                    $scanFindings = [];
                }
                if (\in_array($filePath->getPathname(), $scanFindings)) {
                    continue;
                }
                \array_push($scanFindings, $filePath->__toString());
                \update_option("wp_vaas_plugin_scan_findings", json_encode($scanFindings));
            }
        }
        $time_end = microtime(true);
        $execution_time = ($time_end - $time_start);
        if (defined('WP_DEBUG_LOG')) {
            \file_put_contents(WP_DEBUG_LOG, "scan finished in $execution_time seconds\n", FILE_APPEND);
        };
    }
}
