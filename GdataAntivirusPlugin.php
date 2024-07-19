<?php

namespace Gdatacyberdefenseag\GdataAntivirus;

use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\IGdataAntivirusDatabase;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\WordPressDatabase;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\WordPressFileSystem;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\AdminNotices;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\Findings\FindingsMenuPage;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\FullScan\FullScanMenuPage;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\OnDemandScan\OnDemandScan;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\GdataAntivirusMenuPage;
use Gdatacyberdefenseag\GdataAntivirus\Vaas\ScanClient;
use Illuminate\Container\Container;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

if (! class_exists('GdataAntivirusPlugin')) {
	class GdataAntivirusPlugin extends Container {
		public function __construct( LoggerInterface $logger = new NullLogger() ) {
			$logger->info('GdataAntivirusPlugin::__construct');
			$this->singleton(FindingsMenuPage::class, FindingsMenuPage::class);
			$this->singleton(FullScanMenuPage::class, FullScanMenuPage::class);
			$this->singleton(OnDemandScan::class, OnDemandScan::class);
			$this->singleton(IGdataAntivirusFileSystem::class, WordPressFileSystem::class);
			$this->singleton(IGdataAntivirusDatabase::class, WordPressDatabase::class);
			$this->singleton(GdataAntivirusMenuPage::class, GdataAntivirusMenuPage::class);
			$this->singleton(ScanClient::class, ScanClient::class);
			$this->singleton(AdminNotices::class, AdminNotices::class);
			$this->singleton(LoggerInterface::class, function () use ( $logger ) {
				return $logger;
			});

			$this->make(GdataAntivirusMenuPage::class);
			$findings_menu = $this->make(FindingsMenuPage::class);
			$this->make(FullScanMenuPage::class);
			$this->make(OnDemandScan::class);

			\assert($findings_menu instanceof FindingsMenuPage);
			$findings_menu->validate_findings();
		}
	}
}
