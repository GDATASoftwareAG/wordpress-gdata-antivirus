<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\Vaas;

use Gdatacyberdefenseag\WordpressGdataAntivirus\Logging\WordpressGdataAntivirusPluginDebugLogger;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\WordpressGdataAntivirusMenuPage;
use Psr\Log\NullLogger;
use VaasSdk\Vaas;
use VaasSdk\Authentication\ClientCredentialsGrantAuthenticator;
use VaasSdk\Authentication\ResourceOwnerPasswordGrantAuthenticator;
use VaasSdk\Message\Verdict;
use VaasSdk\VaasOptions;

use function Amp\ByteStream\Internal\tryToCreateReadableStreamFromResource;

class ScanClient
{
    private Vaas $vaas;

    public function __construct()
    {
        $this->Connect();
        $pluginUploadScanEnabled = (bool)\get_option('wordpress_gdata_antivirus_options_on_demand_scan_plugin_upload_scan_enabled', false);
        $mediaUploadScanEnabled = (bool)\get_option('wordpress_gdata_antivirus_options_on_demand_scan_media_upload_scan_enabled', false);
        // we don't need to add the filters if both plugin and media upload scan are disabled
        if ($pluginUploadScanEnabled === true || $mediaUploadScanEnabled === true) {
            \add_filter('wp_handle_upload_prefilter', [$this, 'scanSingleUpload']);
            \add_filter('wp_handle_sideload_prefilter', [$this, 'scanSingleUpload']);
        }

        $commentScanEnabled = (bool)\get_option('wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled', false);
        $pingbackScanEnabled = (bool)\get_option('wordpress_gdata_antivirus_options_on_demand_scan_pingback_scan_enabled', false);
        // we don't need to add the filter if both comment and pingback scan are disabled
        if ($commentScanEnabled === true || $pingbackScanEnabled === true) {
            \add_filter('preprocess_comment', [$this, 'scanComment']);
        }

        $postScanEnabled = (bool)\get_option('wordpress_gdata_antivirus_options_on_demand_scan_post_scan_enabled', false);
        if ($postScanEnabled === true) {
            \add_filter('wp_insert_post_data', [$this, 'scanPost']);
        }
    }

    public function Connect() {
        $options = WordpressGdataAntivirusMenuPage::GetVaasOption();
        $this->vaas = new Vaas($options['vaas_url'], new NullLogger(), new VaasOptions(false, false));

        if ($options['authentication_method'] == 'ResourceOwnerPasswordGrant') {
            $resourceOwnerPasswordGrantAuthenticator = new ResourceOwnerPasswordGrantAuthenticator(
                "wordpress-customer",
                $options['username'],
                $options['password'],
                $options['token_endpoint']
            );
            $this->vaas->connect($resourceOwnerPasswordGrantAuthenticator->getToken());
        } else {
            $clientCredentialsGrantAuthenticator = new ClientCredentialsGrantAuthenticator(
                $options['client_id'],
                $options['client_secret'],
                $options['token_endpoint']
            );
            $this->vaas->connect($clientCredentialsGrantAuthenticator->getToken());
        }
    }

    public function scanPost($data, $postarr, $unsanitized_postarr)
    {
        $data = \wp_unslash($unsanitized_postarr);
        if (empty($data['post_content'])) {
            return $data;
        }

        $postScanEnabled = (bool)\get_option('wordpress_gdata_antivirus_options_on_demand_scan_post_scan_enabled', false);
        if ($postScanEnabled === false) {
            return $data;
        }

        if (empty($postdata['post_content'])) {
            return $data;
        }

        $postContent = \wp_unslash($postdata['post_content']);
        $stream = tryToCreateReadableStreamFromResource(fopen(sprintf('data://text/plain,%s', $postContent), 'r'));

        $verdict = $this->vaas->ForStream($stream);
        WordpressGdataAntivirusPluginDebugLogger::Log(var_export($verdict, true));
        if (\VaasSdk\Message\Verdict::MALICIOUS === $verdict->Verdict) {
            WordpressGdataAntivirusPluginDebugLogger::Log('wordpress-gdata-antivirus: virus found in post');
            wp_die(__('virus found'));
        }
        return $postdata;
    }

    public function scanComment($commentdata)
    {
        $commentScanEnabled = (bool)\get_option('wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled', false);
        $pingbackScanEnabled = (bool)\get_option('wordpress_gdata_antivirus_options_on_demand_scan_pingback_scan_enabled', false);

        $commentScanEnabled = \get_option('wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled', false);
        if ($commentScanEnabled === false) {
            return $commentdata;
        }

        if (empty($commentdata['comment_content'])) {
            return $commentdata;
        }

        // if this is a comment and the comment scan is disabled, we don't need to scan the comment
        // 'comment_type' - 'pingback', 'trackback', or empty for regular comments see: https://developer.wordpress.org/reference/hooks/preprocess_comment/
        if (empty($commentdata['comment_type']) && $commentScanEnabled === false) {
            return $commentdata;
            // if this is a pingback and the pingback scan is disabled, we don't need to scan the comment
        } elseif (!empty($commentdata['comment_type']) && $pingbackScanEnabled === false) {
            return $commentdata;
        }

        $commendContent = \wp_unslash($commentdata['comment_content']);
        $stream = tryToCreateReadableStreamFromResource(fopen(sprintf('data://text/plain,%s', $commendContent), 'r'));

        $verdict = $this->vaas->ForStream($stream);
        WordpressGdataAntivirusPluginDebugLogger::Log(var_export($verdict, true));
        if (\VaasSdk\Message\Verdict::MALICIOUS === $verdict->Verdict) {
            WordpressGdataAntivirusPluginDebugLogger::Log('wordpress-gdata-antivirus: virus found in comment');
            wp_die(__('virus found'));
        }
        return $commentdata;
    }

    public function scanSingleUpload($file)
    {
        $pluginUploadScanEnabled = \get_option('wordpress_gdata_antivirus_options_on_demand_scan_plugin_upload_scan_enabled', false);
        $mediaUploadScanEnabled = \get_option('wordpress_gdata_antivirus_options_on_demand_scan_media_upload_scan_enabled', false);

        // when this is a plugin uplaod but the plugin upload scan is disabled, we don't need to scan the file
        $isPluginUplad = false;
        if (isset($_GET['action'])) {
            if ($_GET['action'] === 'upload-plugin') {
                $isPluginUplad = true;
                if ($pluginUploadScanEnabled === false) {
                    return $file;
                }
            }
        }

        // when this is a media upload (not a plugin upload) but the media upload scan is disabled, we don't need to scan the file
        if ($isPluginUplad === false) {
            if ($mediaUploadScanEnabled === false) {
                return $file;
            }
        }

        $verdict = $this->scanFile($file['tmp_name']);
        if (\VaasSdk\Message\Verdict::MALICIOUS === $verdict) {
            $file['error'] = __('virus found');
        }
        return $file;
    }

    public function scanFile($filePath): Verdict
    {
        $verdict = $this->vaas->ForFile($filePath)->Verdict;
        WordpressGdataAntivirusPluginDebugLogger::Log(
            'wordpress-gdata-antivirus: verdict for file ' . $filePath . ': ' . var_export($verdict, true)
        );
        return $verdict;
    }
}
