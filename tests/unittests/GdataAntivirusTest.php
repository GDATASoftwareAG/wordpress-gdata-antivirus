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

namespace Gdatacyberdefenseag\GdataAntivirus;

use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Database\IGdataAntivirusDatabase;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\Findings\FindingsMenuPage;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\GdataAntivirusMenuPage;
use Gdatacyberdefenseag\GdataAntivirus\Vaas\ScanClient;
use Gdatacyberdefenseag\GdataAntivirus\Vaas\VaasOptions;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use unittests\Infrastructure\TestDebugLogger;
use unittests\Infrastructure\NoopDatabase;
use unittests\Infrastructure\PlainPhpFileSystem;

global $_GET;
$_GET['load'] = array( "foobar" );



$GLOBALS['_global_function_handler_e'] = 'my_global_function_handler_e';
// phpcs:ignore
$GLOBALS['wp_plugin_paths'] = array();

define('ABSPATH', __DIR__."/../../wordpress/");
define('WP_CONTENT_DIR', \ABSPATH . 'wp-content');
define('WP_LANG_DIR', \WP_CONTENT_DIR . 'languages');
define('WPINC', 'wp-includes');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', 'php://stdout');
define('WP_PLUGIN_DIR', \WP_CONTENT_DIR . '/plugins');
define('WPMU_PLUGIN_DIR', '');

require_once ABSPATH . WPINC . '/version.php';
require_once ABSPATH . WPINC . '/compat.php';
require_once ABSPATH . WPINC . '/load.php';
require_once ABSPATH . WPINC . '/functions.php';
require_once ABSPATH . WPINC . '/pomo/mo.php';
require_once ABSPATH . WPINC . '/l10n.php';
require_once ABSPATH . WPINC . '/plugin.php';
require_once ABSPATH . WPINC . '/cache.php';

// include __DIR__ . '/wordpress/wp-includes/formatting.php';.

define('WORDPRESS_GDATA_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__', __FILE__);
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG', 'gdata-antivirus-menu');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_SLUG', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG . '-findings');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_TABLE_NAME', 'WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_TABLE');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG . '-full-scan');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS_TABLE_NAME', 'WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG . '-on-demand-scan');

class GdataAntivirusTest extends TestCase {
    public function testDependencyInjection() {
        $this->markTestSkipped('must be revisited.');

        wp_cache_init();

        $vaas_options = $this->getMockBuilder(VaasOptions::class)
        ->onlyMethods(array( 'get_options' ))
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
        $app->singleton(IGdataAntivirusDatabase::class, NoopDatabase::class);
        $app->singleton(VaasOptions::class, function () use ( $vaas_options ) {
        return $vaas_options;
        });
        $app->singleton(ScanClient::class, function () use ( $scan_client ) {
        return $scan_client;
        });

        $logger->debug("get FindingsMenuPage");
        $findings_menu = $app->get(FindingsMenuPage::class);
        $logger->debug("get GdataAntivirusMenuPage");
        $gdata_menu_page = $app->get(GdataAntivirusMenuPage::class);

        assert($findings_menu instanceof PluginPage\Findings\FindingsMenuPage);
        $findings_menu->validate_findings();
        echo "validate_findings()\n";
        $this->assertTrue(true);
    }
}
