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

namespace Gdatacyberdefenseag\WordpressGdataAntivirus;

require_once __DIR__ . '/vendor/autoload.php';

use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\FileSystem\WordPressFileSystem;
use Gdatacyberdefenseag\WordpressGdataAntivirus\Infrastructure\Logging\WordpressGdataAntivirusPluginDebugLogger;
use Gdatacyberdefenseag\WordpressGdataAntivirus\WordpressGdataAntivirusPlugin;
use Psr\Log\LoggerInterface;

define('WORDPRESS_GDATA_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__', __FILE__);
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG', 'wordpress-gdata-antivirus-menu');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_SLUG', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG . '-findings');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_TABLE_NAME', 'WORDPRESS_GDATA_ANTIVIRUS_MENU_FINDINGS_TABLE');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_SLUG', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG . '-full-scan');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS_TABLE_NAME', 'WORDPRESS_GDATA_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS');
define('WORDPRESS_GDATA_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG', WORDPRESS_GDATA_ANTIVIRUS_MENU_SLUG . '-on-demand-scan');

$app = new WordpressGdataAntivirusPlugin();
$app->singleton(
    IGdataAntivirusFileSystem::class,
    WordPressFileSystem::class
);
$app->singleton(
    LoggerInterface::class,
    WordpressGdataAntivirusPluginDebugLogger::class
);
