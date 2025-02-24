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

use Gdatacyberdefenseag\GdataAntivirus\PluginPage\OnDemandScan\OnDemandScanOptions;
use Gdatacyberdefenseag\GdataAntivirus\tests\unittests\Infrastructure\PlainPhpFileSystem;
use Gdatacyberdefenseag\GdataAntivirus\tests\unittests\Infrastructure\TestAdminNotices;
use Gdatacyberdefenseag\GdataAntivirus\tests\unittests\Infrastructure\TestDebugLogger;
use Gdatacyberdefenseag\GdataAntivirus\Vaas\ScanClient;
use Gdatacyberdefenseag\GdataAntivirus\Vaas\VaasOptions;
use PHPUnit\Framework\TestCase;
use VaasSdk\Verdict;

use function PHPUnit\Framework\assertEquals;

class ScanClientTest extends TestCase
{
    public function testScanFunction()
    {
        $vaas_options = $this->getMockBuilder(VaasOptions::class)
            ->onlyMethods(array('get_options', 'credentials_configured'))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $vaas_options
            ->method('get_options')
            ->willReturn(array(
                'authentication_method' => 'ResourceOwnerPasswordGrant',
                'username' => 'wordpress-testing',
                'password' => getenv('VAAS_PASSWORD'),
                'token_endpoint' => 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token',
                'vaas_url' => 'https://gateway.staging.vaas.gdatasecurity.de',
            ));
        $vaas_options
            ->method('credentials_configured')
            ->willReturn(true);

        $on_demand_scan_options = $this->getMockBuilder(OnDemandScanOptions::class)
            ->onlyMethods(array(
                'get_on_demand_scan_media_upload_enabled_option',
                'get_plugin_upload_scan_enabled_option',
                'get_comment_scan_enabled_option',
                'get_pingback_scan_enabled_option',
                'get_post_scan_enabled_option'
            ))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $on_demand_scan_options
            ->method('get_on_demand_scan_media_upload_enabled_option')
            ->willReturn(true);
        $on_demand_scan_options
            ->method('get_plugin_upload_scan_enabled_option')
            ->willReturn(true);
        $on_demand_scan_options
            ->method('get_comment_scan_enabled_option')
            ->willReturn(true);
        $on_demand_scan_options
            ->method('get_pingback_scan_enabled_option')
            ->willReturn(true);
        $on_demand_scan_options
            ->method('get_post_scan_enabled_option')
            ->willReturn(true);

        $logger = new TestDebugLogger();
        $scan_client = new ScanClient(
            $logger,
            $vaas_options,
            new PlainPhpFileSystem(),
            new TestAdminNotices(),
            $on_demand_scan_options
        );

        $scan_client->connect();

        $temp_file = tmpfile();
        fwrite($temp_file, random_bytes(1024));

        $verdict = $scan_client->scan_file(stream_get_meta_data($temp_file)['uri']);
        assertEquals(Verdict::CLEAN, $verdict->verdict);
        fclose($temp_file);
    }
}
