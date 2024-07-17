<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\Logging;

use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Psr\Log\LoggerInterface;

if (!class_exists('WordpressGdataAntivirusPluginDebugLogger')) {
	class WordpressGdataAntivirusPluginDebugLogger implements LoggerInterface {
        private IGdataAntivirusFileSystem $files_system;

		public function __construct( IGdataAntivirusFileSystem $file_system ) {
			$this->files_system = $file_system;

			$this->info('WordpressGdataAntivirusPluginDebugLogger::__construct');
        }

		public function emergency( string|\Stringable $message, array $context = array() ): void {
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                $this->files_system->write(WP_DEBUG_LOG, '[emergency] '.$message."\n");
			}
        }

		public function alert( string|\Stringable $message, array $context = array() ): void {
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                $this->files_system->write(WP_DEBUG_LOG, '[alert] '.$message."\n");
			}
		}

		public function critical( string|\Stringable $message, array $context = array() ): void {
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                $this->files_system->write(WP_DEBUG_LOG, '[critical] '.$message."\n");
			}
		}

		public function error( string|\Stringable $message, array $context = array() ): void {
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                $this->files_system->write(WP_DEBUG_LOG, '[error] '.$message."\n");
			}
		}

		public function warning( string|\Stringable $message, array $context = array() ): void {
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                $this->files_system->write(WP_DEBUG_LOG, '[warning] '.$message."\n");
			}
		}

		public function notice( string|\Stringable $message, array $context = array() ): void {
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                $this->files_system->write(WP_DEBUG_LOG, '[notice] '.$message."\n");
			}
		}

		public function info( string|\Stringable $message, array $context = array() ): void {
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                $this->files_system->write(WP_DEBUG_LOG, '[info] '.$message."\n");
			}
		}

		public function debug( string|\Stringable $message, array $context = array() ): void {
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                $this->files_system->write(WP_DEBUG_LOG, "[debug] ".$message."\n");
			}
		}

		public function log( $level, string|\Stringable $message, array $context = array() ): void {
			if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                $this->files_system->write(WP_DEBUG_LOG, '[$level] '.$message."\n");
			}
		}
	}
}
