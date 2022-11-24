<?php
/**
 * Plugin Name: wp-vaas
 * Version: 0.0.1
 * Requires PHP: 7.3
 * Plugin URI: www.gdata.de
 * 
 * @category Security
 * @package  GD_Scan
 * @author   G DATA CyberDefense AG <info@gdata.de>
 * @license  none www.gdata.de
 * @link     www.gdata.de
 */
namespace Gdatacyberdefenseag\WpVaas;

require_once dirname(__FILE__) . "/vendor/autoload.php";

$plugin = new VaasPlugin();