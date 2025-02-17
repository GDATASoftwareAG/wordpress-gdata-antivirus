<?php

namespace Gdatacyberdefenseag\GdataAntivirus\tests\unittests\Infrastructure;

use Gdatacyberdefenseag\GdataAntivirus\PluginPage\AdminNoticesInterface;

class TestAdminNotices implements AdminNoticesInterface
{
	public static function add_notice(string $text): void {}
	public function save_notices(): void {}
	public function output_notices(): void {}
}
