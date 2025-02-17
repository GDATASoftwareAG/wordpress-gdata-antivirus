<?php

namespace Gdatacyberdefenseag\GdataAntivirus\PluginPage\OnDemandScan;

class OnDemandScanOptions
{
    public static array $option_defaults = array(
        'authentication_method' => 'ResourceOwnerPasswordGrant',
        'client_id'             => '',
        'client_secret'         => '',
        'username'              => '',
        'password'              => '',
        'vaas_url'              => 'wss://gateway.staging.vaas.gdatasecurity.de',
        'token_endpoint'        => 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token',
    );

    public function __construct()
    {
        add_action('init', array($this, 'setup_fields'));
    }

    public function setup_fields(): void
    {
        register_setting(
            'gdatacyberdefenseag_antivirus_options_on_demand_scan',
            'gdatacyberdefenseag_antivirus_options_on_demand_scan_media_upload_scan_enabled',
            array(
                'type'    => 'boolean',
                'default' => true,
            )
        );
        register_setting(
            'gdatacyberdefenseag_antivirus_options_on_demand_scan',
            'gdatacyberdefenseag_antivirus_options_on_demand_scan_plugin_upload_scan_enabled',
            array(
                'type'    => 'boolean',
                'default' => true,
            )
        );
        register_setting(
            'gdatacyberdefenseag_antivirus_options_on_demand_scan',
            'gdatacyberdefenseag_antivirus_options_on_demand_scan_comment_scan_enabled',
            array(
                'type'    => 'boolean',
                'default' => true,
            )
        );
        register_setting(
            'gdatacyberdefenseag_antivirus_options_on_demand_scan',
            'gdatacyberdefenseag_antivirus_options_on_demand_scan_pingback_scan_enabled',
            array(
                'type'    => 'boolean',
                'default' => true,
            )
        );
        register_setting(
            'gdatacyberdefenseag_antivirus_options_on_demand_scan',
            'gdatacyberdefenseag_antivirus_options_on_demand_scan_post_scan_enabled',
            array(
                'type'    => 'boolean',
                'default' => true,
            )
        );
    }

    public function get_on_demand_scan_media_upload_enabled_option(): bool
    {
        return get_option('gdatacyberdefenseag_antivirus_options_on_demand_scan_media_upload_scan_enabled', true);
    }

    public function get_plugin_upload_scan_enabled_option(): bool
    {
        return get_option('gdatacyberdefenseag_antivirus_options_on_demand_scan_plugin_upload_scan_enabled', true);
    }

    public function get_comment_scan_enabled_option(): bool
    {
        return get_option('gdatacyberdefenseag_antivirus_options_on_demand_scan_comment_scan_enabled', true);
    }

    public function get_pingback_scan_enabled_option(): bool
    {
        return get_option('gdatacyberdefenseag_antivirus_options_on_demand_scan_pingback_scan_enabled', true);
    }

    public function get_post_scan_enabled_option(): bool
    {
        return get_option('gdatacyberdefenseag_antivirus_options_on_demand_scan_post_scan_enabled', true);
    }
}
