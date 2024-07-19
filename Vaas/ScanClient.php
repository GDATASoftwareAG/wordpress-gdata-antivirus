<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Vaas;

use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\IGdataAntivirusDatabase;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Psr\Log\LoggerInterface;
use VaasSdk\Vaas;
use VaasSdk\Authentication\ClientCredentialsGrantAuthenticator;
use VaasSdk\Authentication\ResourceOwnerPasswordGrantAuthenticator;
use VaasSdk\Message\Verdict;
use VaasSdk\VaasOptions as VaasParameters;

use function Amp\ByteStream\Internal\tryToCreateReadableStreamFromResource;

if (! class_exists('ScanClient')) {
	class ScanClient {
		private Vaas $vaas;
		private LoggerInterface $logger;
		private VaasOptions $vaas_options;
		private IGdataAntivirusFileSystem $file_system;

		public function __construct( LoggerInterface $logger, VaasOptions $vaas_options, IGdataAntivirusFileSystem $file_system ) {
			$logger->info('ScanClient::__construct');
			$this->logger = $logger;
			$this->vaas_options = $vaas_options;
			$this->file_system = $file_system;

			$this->Connect();
			$plugin_upload_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_plugin_upload_scan_enabled', false);
			$media_upload_scan_enabled  = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_media_upload_scan_enabled', false);
			// We don't need to add the filters if both plugin and media upload scan are disabled.
			if ($plugin_upload_scan_enabled === true || $media_upload_scan_enabled === true) {
				\add_filter('wp_handle_upload_prefilter', array( $this, 'scan_single_upload' ));
				\add_filter('wp_handle_sideload_prefilter', array( $this, 'scan_single_upload' ));
			}

			$comment_scan_enabled  = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled', false);
			$pingback_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_pingback_scan_enabled', false);
			// We don't need to add the filter if both comment and pingback scan are disabled.
			if ($comment_scan_enabled === true || $pingback_scan_enabled === true) {
				\add_filter('preprocess_comment', array( $this, 'scan_comment' ));
			}

			$post_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_post_scan_enabled', false);
			if ($post_scan_enabled === true) {
				\add_filter('wp_insert_post_data', array( $this, 'scan_post' ));
			}
		}

		public function connect() {
			$options    = $this->vaas_options->get_options();
			$this->vaas = new Vaas($options['vaas_url'], $this->logger, new VaasParameters(false, false));
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
				$this->vaas->connect($resource_owner_password_grant_authenticator->getToken());
			} else {
				$client_credentials_grant_authenticator = new ClientCredentialsGrantAuthenticator(
					$options['client_id'],
					$options['client_secret'],
					$options['token_endpoint']
				);
				$this->vaas->connect($client_credentials_grant_authenticator->getToken());
			}
		}

		public function scan_post( $data, $postarr, $unsanitized_postarr ) {
			$data = \wp_unslash($unsanitized_postarr);
			if (empty($data['post_content'])) {
				return $data;
			}

			$post_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_post_scan_enabled', false);
			if ($post_scan_enabled === false) {
				return $data;
			}

			if (empty($postdata['post_content'])) {
				return $data;
			}

			$post_content = \wp_unslash($postdata['post_content']);
			$stream       = $this->file_system->get_resource_stream_from_string($post_content);

			$verdict = $this->vaas->ForStream($stream);
			$this->logger->debug(var_export($verdict, true));
			 // phpcs:ignore
			if (\VaasSdk\Message\Verdict::MALICIOUS === $verdict->Verdict) {
				$this->logger->debug('gdata-antivirus: virus found in post');
				wp_die(esc_html__('virus found'));
			}
			return $postdata;
		}

		public function scan_comment( $commentdata ) {
			$comment_scan_enabled  = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled', false);
			$pingback_scan_enabled = (bool) \get_option('wordpress_gdata_antivirus_options_on_demand_scan_pingback_scan_enabled', false);

			$comment_scan_enabled = \get_option('wordpress_gdata_antivirus_options_on_demand_scan_comment_scan_enabled', false);
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

			$commend_content = \wp_unslash($commentdata['comment_content']);
			$stream          = $this->file_system->get_resource_stream_from_string($commend_content);

			$verdict = $this->vaas->ForStream($stream);
			$this->logger->debug(var_export($verdict, true));
			 // phpcs:ignore
			if (\VaasSdk\Message\Verdict::MALICIOUS === $verdict->Verdict) {
				$this->logger->debug('gdata-antivirus: virus found in comment');
				wp_die(\esc_html__('virus found'));
			}
			return $commentdata;
		}

		public function scan_single_upload( $file ) {
			$plugin_upload_scan_enabled = \get_option('wordpress_gdata_antivirus_options_on_demand_scan_plugin_upload_scan_enabled', false);
			$media_upload_scan_enabled  = \get_option('wordpress_gdata_antivirus_options_on_demand_scan_media_upload_scan_enabled', false);

			/**
			 *	When this is a plugin uplaod but the plugin upload scan is disabled,
			 * 	we don't need to scan the file.
			 */
			$is_plugin_uplad = false;

			/**
			 * here should be some kind of nonce check, but I could not get it to work
			 * in case of the media upload, you get the action 'upload-attachment' into this
			 * filter but when you call wp_verify_nonce($_POST['nonce'], $action) it
			 * will return false. In case of the media upload it expects 'media-form' as action
			 * as you can see in the wordpress core
			 */
			$action = $_GET['action'] ?? $_POST['action'] ?? '';
			if ($action === 'upload-plugin') {
				$is_plugin_uplad = true;
				if ($plugin_upload_scan_enabled === false) {
					return $file;
				}
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

			$verdict = $this->scan_file($file['tmp_name']);
			if (\VaasSdk\Message\Verdict::MALICIOUS === $verdict) {
				$file['error'] = __('virus found');
			}
			return $file;
		}

		public function scan_file( $file_path ): Verdict {
			try {
				$verdict = $this->vaas->ForFile($file_path)->Verdict;
			} catch (\Exception $e) {
				$this->logger->debug($e->getMessage());
				return Verdict::UNKNOWN;
			}
			$this->logger->debug(
				'gdata-antivirus: verdict for file ' . $file_path . ': ' . var_export($verdict, true)
			);
			return $verdict;
		}
	}
}
