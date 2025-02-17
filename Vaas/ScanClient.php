<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Vaas;

use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\AdminNoticesInterface;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\OnDemandScan\OnDemandScanOptions;
use Psr\Log\LoggerInterface;
use VaasSdk\Vaas;
use VaasSdk\Authentication\ClientCredentialsGrantAuthenticator;
use VaasSdk\Authentication\ResourceOwnerPasswordGrantAuthenticator;
use VaasSdk\VaasVerdict;
use VaasSdk\Message\VerdictResponse;
use VaasSdk\Options\VaasOptions as VaasParameters;

if (! class_exists('ScanClient')) {
	class ScanClient
	{
		private Vaas $vaas;
		private LoggerInterface $logger;
		private VaasOptions $vaas_options;
		private IGdataAntivirusFileSystem $file_system;
		private AdminNoticesInterface $admin_notices;
		private bool $connected = false;
		private readonly OnDemandScanOptions $on_demand_scan_options;


		public function __construct(
			LoggerInterface $logger,
			VaasOptions $vaas_options,
			IGdataAntivirusFileSystem $file_system,
			AdminNoticesInterface $admin_notices,
			OnDemandScanOptions $on_demand_scan_options
		) {
			$logger->info('ScanClient::__construct');
			$this->logger = $logger;
			$this->vaas_options = $vaas_options;
			$this->file_system = $file_system;
			$this->admin_notices = $admin_notices;

			$plugin_upload_scan_enabled = $on_demand_scan_options->get_plugin_upload_scan_enabled_option();
			$media_upload_scan_enabled  = $on_demand_scan_options->get_on_demand_scan_media_upload_enabled_option();
			// We don't need to add the filters if both plugin and media upload scan are disabled.
			if ($plugin_upload_scan_enabled === true || $media_upload_scan_enabled === true) {
				add_filter('wp_handle_upload_prefilter', array($this, 'scan_single_upload'));
				add_filter('wp_handle_sideload_prefilter', array($this, 'scan_single_upload'));
			}

			$comment_scan_enabled  = $on_demand_scan_options->get_comment_scan_enabled_option();
			$pingback_scan_enabled = $on_demand_scan_options->get_pingback_scan_enabled_option();
			// We don't need to add the filter if both comment and pingback scan are disabled.
			if ($comment_scan_enabled === true || $pingback_scan_enabled === true) {
				add_filter('preprocess_comment', array($this, 'scan_comment'));
			}

			$post_scan_enabled = $on_demand_scan_options->get_post_scan_enabled_option();
			if ($post_scan_enabled === true) {
				add_filter('wp_insert_post_data', array($this, 'scan_post'), 10, 1);
			}
		}

		public function reconnect()
		{
			$this->connected = false;
			$this->connect();
		}

		public function connect()
		{
			if ($this->connected === true) {
				return;
			}

			$options    = $this->vaas_options->get_options();

			$vaas_parameters = new VaasParameters();
			$vaas_parameters->useCache = true;
			$vaas_parameters->useHashLookup = true;
			$vaas_parameters->vaasUrl = $options['vaas_url'];
			if (! $this->vaas_options->credentials_configured()) {
				return;
			}
			if ($options['authentication_method'] == 'ResourceOwnerPasswordGrant') {
				$resource_owner_password_grant_authenticator = new ResourceOwnerPasswordGrantAuthenticator(
					'wordpress-customer',
					$options['username'],
					$options['password'],
					$options['token_endpoint']
				);
				$this->vaas = Vaas::builder()
					->withOptions($vaas_parameters)
					->withAuthenticator($resource_owner_password_grant_authenticator)
					->build();
			} else {
				$client_credentials_grant_authenticator = new ClientCredentialsGrantAuthenticator(
					$options['client_id'],
					$options['client_secret'],
					$options['token_endpoint']
				);
				$this->vaas = Vaas::builder()
					->withOptions($vaas_parameters)
					->withAuthenticator($client_credentials_grant_authenticator)
					->build();
			}
			$this->connected = true;
		}

		public function scan_post($data)
		{
			if (empty($data['post_content'])) {
				return $data;
			}

			$post_scan_enabled = $this->on_demand_scan_options->get_post_scan_enabled_option();
			if ($post_scan_enabled === false) {
				return $data;
			}

			$post_content = wp_unslash($data['post_content']);
			$stream       = $this->file_system->get_resource_stream_from_string($post_content);

			$this->connect();
			try {
				$vaas_verdict = $this->vaas->ForStream($stream);
			} catch (\Exception $e) {
				try {
					$this->reconnect();
					$vaas_verdict = $this->vaas->ForStream($stream);
				} catch (\Exception $e) {
					$this->admin_notices->add_notice(esc_html__('virus scan failed', 'gdata-antivirus'));
					$this->logger->debug($e->getMessage());
					return $data;
				}
			}
			$this->logger->debug(var_export($vaas_verdict->Verdict, true));
			// phpcs:ignore
			if (\VaasSdk\Message\Verdict::MALICIOUS === $vaas_verdict->Verdict) {
				$this->logger->debug('gdata-antivirus: virus found in post');
				wp_die(esc_html__("Virus found! - Detection: $vaas_verdict->Detection - SHA256: $vaas_verdict->Sha256 - Guid: $vaas_verdict->Guid", 'gdata-antivirus'));
			}
			return $data;
		}

		public function scan_comment($commentdata)
		{
			$comment_scan_enabled  = $this->on_demand_scan_options->get_comment_scan_enabled_option();
			$pingback_scan_enabled = $this->on_demand_scan_options->get_pingback_scan_enabled_option();

			$comment_scan_enabled = $this->on_demand_scan_options->get_comment_scan_enabled_option();
			if ($comment_scan_enabled === false) {
				return $commentdata;
			}

			if (empty($commentdata['comment_content'])) {
				return $commentdata;
			}

			/**
			 * If this is a comment and the comment scan is disabled, we don't need to scan the comment.
			 * 'comment_type' - 'pingback', 'trackback', or empty for regular comments see:
			 * https:// developer.wordpress.org/reference/hooks/preprocess_comment/
			 */
			if (empty($commentdata['comment_type']) && $comment_scan_enabled === false) {
				return $commentdata;
				// If this is a pingback and the pingback scan is disabled, we don't need to scan the comment.
			} elseif (! empty($commentdata['comment_type']) && $pingback_scan_enabled === false) {
				return $commentdata;
			}

			$commend_content = wp_unslash($commentdata['comment_content']);
			$stream          = $this->file_system->get_resource_stream_from_string($commend_content);
			$this->connect();
			try {
				$vaas_verdict = $this->vaas->ForStream($stream);
			} catch (\Exception $e) {
				try {
					$this->reconnect();
					$vaas_verdict = $this->vaas->ForStream($stream);
				} catch (\Exception $e) {
					$this->admin_notices->add_notice(esc_html__('virus scan failed', 'gdata-antivirus'));
					$this->logger->debug($e->getMessage());
				}
			}
			$this->logger->debug(var_export($vaas_verdict->Verdict, true));
			// phpcs:ignore
			if (\VaasSdk\Message\Verdict::MALICIOUS === $vaas_verdict->Verdict) {
				$this->logger->debug('gdata-antivirus: virus found in comment');
				wp_die(esc_html__("Virus found! - Detection: $vaas_verdict->Detection - SHA256: $vaas_verdict->Sha256 - Guid: $vaas_verdict->Guid", 'gdata-antivirus'));
			}
			return $commentdata;
		}

		public function scan_single_upload($file)
		{
			$plugin_upload_scan_enabled = $this->on_demand_scan_options->get_plugin_upload_scan_enabled_option();
			$media_upload_scan_enabled  = $this->on_demand_scan_options->get_on_demand_scan_media_upload_enabled_option();

			/**
			 * When this is a plugin uplaod but the plugin upload scan is disabled,
			 * we don't need to scan the file.
			 */
			$is_plugin_uplad = false;

			$action =  sanitize_key($_REQUEST['action'] ?? '');
			$nonce = wp_unslash($_REQUEST['_wpnonce'] ?? $_REQUEST['nonce']);
			if ($action === 'upload-plugin') {
				// if (wp_verify_nonce($nonce, $action) === false) {
				// 	return $file;
				// }
				$is_plugin_uplad = true;
				if ($plugin_upload_scan_enabled === false) {
					return $file;
				}
			} elseif (wp_verify_nonce($nonce, 'media-form') === false) {
				return $file;
			}

			/**
			 * When this is a media upload(not a plugin upload) but the media upload scan is disabled,
			 * we don't need to scan the file.
			 */
			if ($is_plugin_uplad === false) {
				if ($media_upload_scan_enabled === false) {
					return $file;
				}
			}

			$vaas_verdict = $this->scan_file($file['tmp_name']);
			$verdict = $vaas_verdict->Verdict;
			if (\VaasSdk\Message\Verdict::MALICIOUS === $verdict) {
				$file['error'] = __("Virus found! - Detection: $vaas_verdict->Detection - SHA256: $vaas_verdict->Sha256 - Guid: $vaas_verdict->Guid", 'gdata-antivirus');
			}
			return $file;
		}

		public function scan_file($file_path): VaasVerdict
		{
			$this->connect();
			try {
				$vaas_verdict = $this->vaas->forFileAsync($file_path)->await();
			} catch (\Exception $e) {
				try {
					$this->reconnect();
					$vaas_verdict = $this->vaas->forFileAsync($file_path)->await();
				} catch (\Exception $e) {
					$this->logger->debug($e->getMessage());
					return new VaasVerdict(new VerdictResponse);
				}
			}
			$this->logger->debug(
				'gdata-antivirus: verdict for file ' . $file_path . ': ' . var_export($vaas_verdict, true)
			);
			return $vaas_verdict;
		}
	}
}
