<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus;

use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\Database\IGdataAntivirusDatabase;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\Database\WordPressDatabase;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\FileSystem\WordPressFileSystem;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\AdminNotices;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\Findings\FindingsMenuPage;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\FullScan\FullScanMenuPage;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\OnDemandScan\OnDemandScan;
use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\WordpressGdataAntivirusMenuPage;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Vaas\ScanClient;
use Illuminate\Container\Container;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

if (! class_exists('WordpressGdataAntivirusPlugin')) {
	class WordpressGdataAntivirusPlugin extends Container {
		public function __construct( LoggerInterface $logger = new NullLogger() ) {
			$logger->info('WordpressGdataAntivirusPlugin::__construct');
			$this->singleton(FindingsMenuPage::class, FindingsMenuPage::class);
			$this->singleton(FullScanMenuPage::class, FullScanMenuPage::class);
			$this->singleton(OnDemandScan::class, OnDemandScan::class);
			$this->singleton(IGdataAntivirusFileSystem::class, WordPressFileSystem::class);
			$this->singleton(IGdataAntivirusDatabase::class, WordPressDatabase::class);
			$this->singleton(WordpressGdataAntivirusMenuPage::class, WordpressGdataAntivirusMenuPage::class);
			$this->singleton(ScanClient::class, ScanClient::class);
			$this->singleton(AdminNotices::class, AdminNotices::class);
			$this->singleton(LoggerInterface::class, function () use ( $logger ) {
				return $logger;
			});

			$this->make(WordpressGdataAntivirusMenuPage::class);
			$findings_menu = $this->make(FindingsMenuPage::class);
			$this->make(FullScanMenuPage::class);
			$this->make(OnDemandScan::class);

			\assert($findings_menu instanceof FindingsMenuPage);
			$findings_menu->validate_findings();
		}
	}
}
