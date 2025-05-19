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
 * Version: 1.2.3
 * Requires at least: 6.2
 * Tested up to: 6.6
 * Requires PHP: 8.1
 * Plugin URI: https://github.com/GDATASoftwareAG/wordpress-gdata-antivirus
 * Description: Vaas is a virus scanner for your WordPress installation.
 * License: GNU General Public License v3.0
 * License URI: https://github.com/GDATASoftwareAG/vaas/blob/main/LICENSE
 * Text Domain: gdata-antivirus
 * Domain Path: /languages/
 */

namespace Gdatacyberdefenseag\GdataAntivirus;

require_once file_exists(__DIR__ . '/vendor/scoper-autoload.php')
    ? __DIR__ . '/vendor/scoper-autoload.php'
    : __DIR__ . '/vendor/autoload.php';

use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\IGdataAntivirusFileSystem;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\FileSystem\WordPressFileSystem;
use Gdatacyberdefenseag\GdataAntivirus\Infrastructure\Logging\GdataAntivirusPluginDebugLogger;
use Gdatacyberdefenseag\GdataAntivirus\GdataAntivirusPlugin;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\AdminNotices;
use Gdatacyberdefenseag\GdataAntivirus\PluginPage\AdminNoticesInterface;
use Psr\Log\LoggerInterface;

define('GDATACYBERDEFENCEAG_ANTIVIRUS_PLUGIN_WITH_CLASSES__FILE__', __FILE__);
define('GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG', 'gdata-antivirus-menu');
define('GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FINDINGS_SLUG', GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG . '-findings');
define('GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FINDINGS_TABLE_NAME', 'GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FINDINGS_TABLE');
define('GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_SLUG', GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG . '-full-scan');
define('GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS_TABLE_NAME', 'GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_FULL_SCAN_OPERATIONS');
define('GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_ON_DEMAND_SCAN_SLUG', GDATACYBERDEFENCEAG_ANTIVIRUS_MENU_SLUG . '-on-demand-scan');

$app = new GdataAntivirusPlugin();
$app->singleton(
    IGdataAntivirusFileSystem::class,
    WordPressFileSystem::class
);
$app->singleton(
    LoggerInterface::class,
    GdataAntivirusPluginDebugLogger::class
);
$app->singleton(
    AdminNoticesInterface::class,
    AdminNotices::class
);
$app->start();
