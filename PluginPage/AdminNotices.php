<?php

namespace Gdatacyberdefenseag\WordpressGdataAntivirus\PluginPage;

class AdminNotices
{

    public static $_notices = array();

    /**
     * Constructor
     */
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
        update_option('custom_notices', self::$_notices);
    }

    public function outputNotices()
    {
        $notices = maybe_unserialize(get_option('custom_notices'));

        if (!empty($notices)) {

            echo '<div id="notice" class="notice notice-info is-dismissible">';

            foreach ($notices as $notice) {
                echo '<p>' . wp_kses_post($notice) . '</p>';
            }

            echo '</div>';

            delete_option('custom_notices');
        }
    }
}
