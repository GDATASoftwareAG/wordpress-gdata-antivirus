<?php

/**
 * G DATA Antivirus
 *
 * @category Security
 * @package  GD_Scan
 * @author   G DATA CyberDefense AG <oem@gdata.de>
 * @license  https://github.com/GDATASoftwareAG/vaas/blob/main/LICENSE
 * @link     https://github.com/GDATASoftwareAG/vaas
 *
 * @wordpress-plugin
 * Plugin Name: G DATA Antivirus
 * Version: 0.0.1
 * Requires PHP: 8.1
 * Plugin URI: https://github.com/GDATASoftwareAG/wordpress-gdata-antivirus
 * Description: Vaas is a virus scanner for your WordPress installation.
 * License: GNU General Public License v3.0
 * License URI: https://github.com/GDATASoftwareAG/vaas/blob/main/LICENSE
 */

namespace Gdatacyberdefenseag\GdataAntivirus\tests\unittests;

use Gdatacyberdefenseag\GdataAntivirus\GdataAntivirusPlugin;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\IFindingsQuery;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\Findings\FindingsMenuPage;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\GdataAntivirusMenuPage;
use Gdatacyberdefenseag\GdataAntivirus\tests\unittests\Infrastructure\NoopFindingsQuery;
use Gdatacyberdefenseag\GdataAntivirus\tests\unittests\Infrastructure\PlainPhpFileSystem;
use Gdatacyberdefenseag\GdataAntivirus\tests\unittests\Infrastructure\TestDebugLogger;
use Gdatacyberdefenseag\GdataAntivirus\Vaas\ScanClient;
use Gdatacyberdefenseag\GdataAntivirus\Vaas\VaasOptions;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GdataAntivirusTest extends TestCase
{
    public function testDependencyInjection()
    {
        wp_cache_init();

        $vaas_options = $this->getMockBuilder(VaasOptions::class)
            ->onlyMethods(array('get_options'))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $vaas_options
            ->method('get_options')
            ->willReturn(array(
                'authentication_method' => 'ResourceOwnerPasswordGrant',
                'username' => 'username',
                'password' => 'password',
                'token_endpoint' => 'https://token_endpoint',
                'vaas_url' => 'wws://vaas_url',
            ));

        $scan_client = $this->getMockBuilder(ScanClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger = new TestDebugLogger();
        $app = new GdataAntivirusPlugin($logger);
        $app->singleton(LoggerInterface::class, TestDebugLogger::class);
        $app->singleton(IGdataAntivirusFileSystem::class, PlainPhpFileSystem::class);
        $app->singleton(IFindingsQuery::class, NoopFindingsQuery::class);
        $app->singleton(VaasOptions::class, function () use ($vaas_options) {
            return $vaas_options;
        });
        $app->singleton(ScanClient::class, function () use ($scan_client) {
            return $scan_client;
        });

        $logger->debug("get FindingsMenuPage");
        $findings_menu = $app->get(FindingsMenuPage::class);
        $logger->debug("get GdataAntivirusMenuPage");
        $gdata_menu_page = $app->get(GdataAntivirusMenuPage::class);

        assert($findings_menu instanceof FindingsMenuPage);
        assert($gdata_menu_page instanceof GdataAntivirusMenuPage);
        $findings_menu->validate_findings();
        echo "validate_findings()\n";
        $this->assertTrue(true);
    }
}
