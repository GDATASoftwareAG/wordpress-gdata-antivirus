<?php

/**
 * wordpress-gdata-antivirus
 * 
 * @category Security
 * @package  GD_Scan
 * @author   G DATA CyberDefense AG <oem@gdata.de>
 * @license  https://github.com/GDATASoftwareAG/vaas/blob/main/LICENSE
 * @link     https://github.com/GDATASoftwareAG/vaas
 *
 * @wordpress-plugin
 * Plugin Name: wordpress-gdata-antivirus
 * Version: 0.0.1
 * Requires PHP: 8.1
 * Plugin URI: https://github.com/GDATASoftwareAG/wordpress-gdata-antivirus
 * Description: Vaas is a virus scanner for your WordPress installation.
 * License: MIT License
 * License URI: https://github.com/GDATASoftwareAG/vaas/blob/main/LICENSE
 */

namespace Gdatacyberdefenseag\WordpressGdataAntivirus;

define('PLUGIN_WITH_CLASSES__FILE__', __FILE__);

require_once dirname(__FILE__) . "/vendor/autoload.php";

$plugin = new WordpressGdataAntivirusPlugin();
