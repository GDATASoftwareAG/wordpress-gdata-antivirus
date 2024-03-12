<?php

/**
 * VaaS
 * Version: 0.0.1
 * Requires PHP: 8.1
 * Plugin URI: www.gdata.de
 * 
 * @category Security
 * @package  GD_Scan
 * @author   G DATA CyberDefense AG <info@gdata.de>
 * @license  none www.gdata.de
 * @link     www.gdata.de
 */

namespace Gdatacyberdefenseag\WordpressGdataAntivirus;

use Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage\WordpressGdataAntivirusMenuPage;

if (!class_exists('WordpressGdataAntivirusPlugin')) {
    class WordpressGdataAntivirusPlugin
    {
        public function __construct()
        {
            $wordpressGdataAntivirusMenuPage = new WordpressGdataAntivirusMenuPage();
            $wordpressGdataAntivirusMenuPage->FindingsMenuPage->ValidateFindings();
        }
    }
}
