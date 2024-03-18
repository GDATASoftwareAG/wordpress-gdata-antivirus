<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\Logging;

if (!class_exists('WordpressGdataAntivirusPluginDebugLogger')) {
    class WordpressGdataAntivirusPluginDebugLogger
    {
        public static function Log($message)
        {
            if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                global $wp_filesystem;
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
                $wp_filesystem->put_contents(WP_DEBUG_LOG, $message);
            }
        }
    }
}
