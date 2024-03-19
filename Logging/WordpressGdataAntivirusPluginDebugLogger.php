<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\Logging;

if (!class_exists('WordpressGdataAntivirusPluginDebugLogger')) {
    class WordpressGdataAntivirusPluginDebugLogger
    {
        public static function Log($message)
        {
            if (defined('WP_DEBUG_LOG') && is_string(WP_DEBUG_LOG)) {
                \file_put_contents(WP_DEBUG_LOG, $message, \FILE_APPEND);
            }
        }
    }
}
