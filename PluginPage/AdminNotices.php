<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage;

if (!class_exists('AdminNotices')) {
    class AdminNotices
    {

        public static $_notices = array();

        public function __construct()
        {
            add_action('admin_notices', array($this, 'outputNotices'));
            add_action('shutdown', array($this, 'saveNotices'));
        }

        public static function addNotice($text)
        {
            self::$_notices[] = $text;
        }

        public function saveNotices()
        {
            update_option('WordpressGdataAntivirusMenuNotices', self::$_notices);
        }

        public function outputNotices()
        {
            $notices = maybe_unserialize(get_option('WordpressGdataAntivirusMenuNotices'));

            if (!empty($notices)) {

                echo '<div id="notice" class="notice notice-info is-dismissible">';

                foreach ($notices as $notice) {
                    echo '<p>' . wp_kses_post($notice) . '</p>';
                }

                echo '</div>';

                delete_option('WordpressGdataAntivirusMenuNotices');
            }
        }
    }
}
