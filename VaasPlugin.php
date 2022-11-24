<?php
/**
 * VaaS
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

use Gdatacyberdefenseag\WpVaas\PluginPage\VaasMenuPage;
use Gdatacyberdefenseag\WpVaas\Vaas\ScanClient;

if (!class_exists('VaasPlugin')) {
    class VaasPlugin
    {
        public ScanClient $ScanClient;

        public function __construct()
        {
            new VaasMenuPage();

            $options = \get_option('wp_vaas_plugin_options');
            if (!empty($options['client_id']) && !empty($options['client_secret'])) {
                $this->ScanClient = new ScanClient();
            }
            $this->ValidateFindings();
        }

        public function ValidateFindings()
        {
            $scanFindings = \json_decode(\get_option('wp_vaas_plugin_scan_findings'));
            $beforeCount = count($scanFindings);
            $scanFindings = \array_filter($scanFindings, static function ($element) {
                return file_exists($element);
            });

            $afterCount = count($scanFindings);
            if ($beforeCount != $afterCount) {
                \update_option("wp_vaas_plugin_scan_findings", json_encode($scanFindings));
            }
        }
    }
}