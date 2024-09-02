<?php

namespace Gdatacyberdefenseag\GdataAntivirus\Vaas;

class VaasOptions {
    public static array $option_defaults = array(
        'authentication_method' => 'ResourceOwnerPasswordGrant',
        'client_id'             => '',
        'client_secret'         => '',
        'username'              => '',
        'password'              => '',
        'vaas_url'              => 'wss://gateway.staging.vaas.gdatasecurity.de',
        'token_endpoint'        => 'https://account-staging.gdata.de/realms/vaas-staging/protocol/openid-connect/token',
    );

    public function __construct() {
        \add_action('init', array( $this, 'setup_fields' ));
    }

    public function setup_fields(): void {
        \register_setting(
            'gdatacyberdefenseag_antivirus_options_credentials',
            'gdatacyberdefenseag_antivirus_options_credentials ',
            array(
                'type'     => 'array',
                'default ' => self::$option_defaults,
            )
        );
    }

    public function get_options(): array {
        $options = \get_option('gdatacyberdefenseag_antivirus_options_credentials', self::$option_defaults);
        $options = array_merge(self::$option_defaults, $options);
        return $options;
    }

    public function credentials_configured(): bool {
        $options = $this->get_options();
        if ($options['authentication_method'] === 'ResourceOwnerPasswordGrant') {
            $credentials_configured = ! empty($options['username']) && ! empty($options['password']);
        } else {
            $credentials_configured = ! empty($options['client_id']) && ! empty($options['client_secret']);
        }
        return $credentials_configured;
    }
}
