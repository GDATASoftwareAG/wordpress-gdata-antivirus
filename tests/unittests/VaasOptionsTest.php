<?php

namespace unittests;

use Gdatacyberdefenseag\GdataAntivirus\Vaas\VaasOptions;
use PHPUnit\Framework\TestCase;

class VaasOptionsTest extends TestCase {
    public function test_credentials_configured_when_ROPG_credentials_are_correct_configured_returns_true() {
        $menu_page = $this->getMockBuilder(VaasOptions::class)
            ->onlyMethods(array( 'get_options' ))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $menu_page
            ->method('get_options')
            ->willReturn(array(
                'authentication_method' => 'ResourceOwnerPasswordGrant',
                'username' => 'username',
                'password' => 'password',
            ));

        assert($menu_page instanceof VaasOptions);
        $this->assertTrue($menu_page->credentials_configured());
    }

    public function test_credentials_configured_when_CCG_credentials_are_correct_configured_returns_true() {
        $menu_page = $this->getMockBuilder(VaasOptions::class)
            ->onlyMethods(array( 'get_options' ))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $menu_page
            ->method('get_options')
            ->willReturn(array(
                'authentication_method' => 'ClientCredentialsGrant',
                'client_id' => 'username',
                'client_secret' => 'password',
            ));

        assert($menu_page instanceof VaasOptions);
        $this->assertTrue($menu_page->credentials_configured());
    }

    public function test_credentials_configured_when_ROPG_credentials_are_incorrect_configured_returns_false() {
        $menu_page = $this->getMockBuilder(VaasOptions::class)
            ->onlyMethods(array( 'get_options' ))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $menu_page
            ->method('get_options')
            ->willReturn(array(
                'authentication_method' => 'ResourceOwnerPasswordGrant',
                'client_id' => 'username',
                'client_secret' => 'password',
            ));

        assert($menu_page instanceof VaasOptions);
        $this->assertFalse($menu_page->credentials_configured());
    }

    public function test_credentials_configured_when_CCG_credentials_are_incorrect_configured_returns_false() {
        $menu_page = $this->getMockBuilder(VaasOptions::class)
            ->onlyMethods(array( 'get_options' ))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $menu_page
            ->method('get_options')
            ->willReturn(array(
                'authentication_method' => 'ClientCredentialsGrant',
                'username' => 'username',
                'password' => 'password',
            ));

        assert($menu_page instanceof VaasOptions);
        $this->assertFalse($menu_page->credentials_configured());
    }


    public function test_credentials_configured_when_CCG_credentials_one_option_missing_configured_returns_false() {
        $menu_page = $this->getMockBuilder(VaasOptions::class)
            ->onlyMethods(array( 'get_options' ))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $menu_page
            ->method('get_options')
            ->willReturn(array(
                'authentication_method' => 'ClientCredentialsGrant',
                'client_id' => 'username',
            ));

        assert($menu_page instanceof VaasOptions);
        $this->assertFalse($menu_page->credentials_configured());
    }

    public function test_credentials_configured_when_ROPG_credentials_one_option_missing_configured_returns_false() {
        $menu_page = $this->getMockBuilder(VaasOptions::class)
            ->onlyMethods(array( 'get_options' ))
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $menu_page
            ->method('get_options')
            ->willReturn(array(
                'authentication_method' => 'ResourceOwnerPasswordGrant',
                'username' => 'username',
            ));

        assert($menu_page instanceof VaasOptions);
        $this->assertFalse($menu_page->credentials_configured());
    }
}
